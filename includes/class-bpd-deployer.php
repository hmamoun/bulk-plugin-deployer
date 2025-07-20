<?php
/**
 * Plugin deployment class
 */
class BPD_Deployer {
    
    /**
     * Site manager instance
     */
    private $site_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->site_manager = new BPD_Site_Manager();
    }
    
    /**
     * Deploy plugins to sites
     */
    public function deploy_plugins($plugin_names, $site_ids) {
        $results = array();
        $site_manager = new BPD_Site_Manager();
        
        foreach ($site_ids as $site_id) {
            $site = $site_manager->get_site($site_id);
            if (!$site) {
                $results[] = array(
                    'site_id' => $site_id,
                    'success' => false,
                    'message' => 'Site not found'
                );
                continue;
            }
            
            $site_results = $this->deploy_plugins_to_site($plugin_names, $site);
            $results = array_merge($results, $site_results);
        }
        
        return $results;
    }
    
    /**
     * Deploy plugins to a single site
     */
    private function deploy_plugins_to_site($plugin_names, $site) {
        $results = array();
        
        // Auto-detect SFTP (port 22) vs FTP (port 21)
        $ftp_port = isset($site['ftp_port']) ? (int) $site['ftp_port'] : 21;
        
        if ($ftp_port == 22) {
            // Use SFTP deployment
            foreach ($plugin_names as $plugin_name) {
                $result = $this->deploy_single_plugin_sftp($plugin_name, $site);
                $results[] = $result;
            }
        } else {
            // Use FTP deployment
            $conn = $this->connect_to_ftp($site);
            if (!$conn['success']) {
                return array(array(
                    'site_id' => $site['id'],
                    'site_name' => $site['name'],
                    'success' => false,
                    'message' => $conn['message']
                ));
            }
            
            $ftp_conn = $conn['connection'];
            
            foreach ($plugin_names as $plugin_name) {
                $result = $this->deploy_single_plugin($plugin_name, $site, $ftp_conn);
                $results[] = $result;
            }
            
            ftp_close($ftp_conn);
        }
        
        return $results;
    }
    
    /**
     * Deploy a single plugin to a site
     */
    private function deploy_single_plugin($plugin_name, $site, $ftp_conn) {
        $plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin_name;
        
        if (!is_dir($plugin_path)) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Plugin directory not found locally'
            );
        }
        
        // Create temporary zip file
        $temp_zip = $this->create_plugin_zip($plugin_path, $plugin_name);
        if (!$temp_zip) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Failed to create plugin zip'
            );
        }
        
        // Upload zip file with force overwrite
        $remote_zip = $this->normalize_ftp_path($site['ftp_path']) . $plugin_name . '.zip';
        
        // Delete existing zip file if it exists (force overwrite)
        @ftp_delete($ftp_conn, $remote_zip);
        
        if (!ftp_put($ftp_conn, $remote_zip, $temp_zip, FTP_BINARY)) {
            unlink($temp_zip);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Failed to upload plugin zip'
            );
        }
        
        // Extract plugin on remote server
        $extract_result = $this->extract_plugin_remote($ftp_conn, $site, $plugin_name);
        
        // Clean up local zip
        unlink($temp_zip);
        
        if (!$extract_result['success']) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => $extract_result['message']
            );
        }
        
        return array(
            'site_id' => $site['id'],
            'site_name' => $site['name'],
            'plugin_name' => $plugin_name,
            'success' => true,
            'message' => 'Plugin deployed successfully'
        );
    }
    
    /**
     * Deploy a single plugin to a site via SFTP
     */
    private function deploy_single_plugin_sftp($plugin_name, $site) {
        $plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin_name;
        
        if (!is_dir($plugin_path)) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Plugin directory not found locally'
            );
        }
        
        // Check if SSH2 extension is available
        if (!function_exists('ssh2_connect')) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'SSH2 extension is not available. Please install php-ssh2 extension for SFTP support.'
            );
        }
        
        $host = $site['ftp_host'];
        $port = isset($site['ftp_port']) ? (int) $site['ftp_port'] : 22;
        $username = $site['ftp_username'];
        $password = $this->site_manager->decrypt_password($site['ftp_password']);
        $remote_path = $this->normalize_ftp_path(isset($site['ftp_path']) ? $site['ftp_path'] : '/wp-content/plugins/');
        
        // Create temporary zip file
        $temp_zip = $this->create_plugin_zip($plugin_path, $plugin_name);
        if (!$temp_zip) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Failed to create plugin zip'
            );
        }
        
        // Connect via SFTP with compatibility for different PHP versions
        $connection = $this->ssh2_connect_compat($host, $port);
        if (!$connection) {
            unlink($temp_zip);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Could not connect to SFTP server'
            );
        }
        
        // Authenticate
        if (!@ssh2_auth_password($connection, $username, $password)) {
            unlink($temp_zip);
            ssh2_disconnect($connection);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'SFTP authentication failed'
            );
        }
        
        // Create SFTP session
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) {
            unlink($temp_zip);
            ssh2_disconnect($connection);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Could not create SFTP session'
            );
        }
        
        // Upload zip file with force overwrite
        $remote_zip = $remote_path . $plugin_name . '.zip';
        
        // Delete existing zip file if it exists (force overwrite)
        @ssh2_sftp_unlink($sftp, $remote_zip);
        
        $stream = @fopen("ssh2.sftp://{$sftp}{$remote_zip}", 'w');
        if (!$stream) {
            unlink($temp_zip);
            ssh2_disconnect($connection);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Could not open remote file for writing'
            );
        }
        
        $local_stream = fopen($temp_zip, 'r');
        $uploaded = stream_copy_to_stream($local_stream, $stream);
        fclose($local_stream);
        fclose($stream);
        
        // Clean up local zip
        unlink($temp_zip);
        
        if ($uploaded === false) {
            ssh2_disconnect($connection);
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => 'Failed to upload plugin zip via SFTP'
            );
        }
        
        // Extract plugin on remote server (simplified - assumes server can handle extraction)
        $extract_result = $this->extract_plugin_remote_sftp($sftp, $site, $plugin_name);
        
        ssh2_disconnect($connection);
        
        if (!$extract_result['success']) {
            return array(
                'site_id' => $site['id'],
                'site_name' => $site['name'],
                'plugin_name' => $plugin_name,
                'success' => false,
                'message' => $extract_result['message']
            );
        }
        
        return array(
            'site_id' => $site['id'],
            'site_name' => $site['name'],
            'plugin_name' => $plugin_name,
            'success' => true,
            'message' => 'Plugin deployed successfully via SFTP'
        );
    }
    
    /**
     * Connect to FTP server
     */
    private function connect_to_ftp($site) {
        // Check if FTP extension is available
        if (!function_exists('ftp_connect')) {
            return array(
                'success' => false, 
                'message' => 'FTP extension is not available. Please install php-ftp extension.'
            );
        }
        
        $ftp_host = $site['ftp_host'];
        $ftp_port = isset($site['ftp_port']) ? (int) $site['ftp_port'] : 21;
        $ftp_username = $site['ftp_username'];
        $ftp_password = $this->site_manager->decrypt_password($site['ftp_password']);
        
        // Connect to FTP
        $conn = @ftp_connect($ftp_host, $ftp_port, 10);
        if (!$conn) {
            return array('success' => false, 'message' => 'Could not connect to FTP server');
        }
        
        // Login
        if (!@ftp_login($conn, $ftp_username, $ftp_password)) {
            ftp_close($conn);
            return array('success' => false, 'message' => 'FTP login failed');
        }
        
        // Enable passive mode
        @ftp_pasv($conn, true);
        
        // Navigate to plugins directory
        if (!@ftp_chdir($conn, $this->normalize_ftp_path($site['ftp_path']))) {
            ftp_close($conn);
            return array('success' => false, 'message' => 'Cannot access plugins directory');
        }
        
        return array('success' => true, 'connection' => $conn);
    }
    
    /**
     * Create plugin zip file
     */
    public function create_plugin_zip($plugin_path, $plugin_name) {
        $temp_dir = sys_get_temp_dir();
        $zip_file = $temp_dir . '/' . $plugin_name . '_' . time() . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        $this->add_folder_to_zip($zip, $plugin_path, $plugin_name);
        $zip->close();
        
        return $zip_file;
    }
    
    /**
     * Add folder contents to zip
     */
    private function add_folder_to_zip($zip, $folder_path, $base_name) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = $base_name . '/' . substr($file_path, strlen($folder_path) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }
    }
    
    /**
     * Extract plugin on remote server
     */
    private function extract_plugin_remote($ftp_conn, $site, $plugin_name) {
        $remote_zip = $plugin_name . '.zip';
        $remote_dir = $plugin_name;
        
        // Check if plugin directory already exists and remove it for force overwrite
        $current_dir = ftp_pwd($ftp_conn);
        
        // Force overwrite: Remove existing plugin directory if it exists
        $this->remove_plugin_directory_ftp($ftp_conn, $remote_dir);
        
        // Create fresh directory
        @ftp_mkdir($ftp_conn, $remote_dir);
        
        // Extract zip file using FTP commands
        $extract_result = $this->extract_zip_via_ftp($ftp_conn, $remote_zip, $remote_dir);
        
        if ($extract_result['success']) {
            // Clean up the zip file after successful extraction
            @ftp_delete($ftp_conn, $remote_zip);
            return array('success' => true, 'message' => 'Plugin extracted successfully with force overwrite');
        } else {
            return $extract_result;
        }
    }
    
    /**
     * Extract plugin on remote server via SFTP
     */
    private function extract_plugin_remote_sftp($sftp, $site, $plugin_name) {
        $remote_path = $this->normalize_ftp_path(isset($site['ftp_path']) ? $site['ftp_path'] : '/wp-content/plugins/');
        $remote_zip = $remote_path . $plugin_name . '.zip';
        $remote_dir = $remote_path . $plugin_name;
        
        // Force overwrite: Remove existing plugin directory if it exists
        $this->remove_plugin_directory_sftp($sftp, $remote_dir);
        
        // Create fresh directory
        @ssh2_sftp_mkdir($sftp, $remote_dir, 0755, true);
        
        // Extract zip file using SFTP
        $extract_result = $this->extract_zip_via_sftp($sftp, $remote_zip, $remote_dir);
        
        if ($extract_result['success']) {
            // Clean up the zip file after successful extraction
            @ssh2_sftp_unlink($sftp, $remote_zip);
            return array('success' => true, 'message' => 'Plugin extracted successfully via SFTP with force overwrite');
        } else {
            return $extract_result;
        }
    }
    
    /**
     * SSH2 connect with compatibility for different PHP versions
     */
    private function ssh2_connect_compat($host, $port) {
        // Try different function signatures for compatibility
        try {
            // Newer PHP versions - no third parameter
            return @ssh2_connect($host, $port);
        } catch (TypeError $e) {
            // Older PHP versions - third parameter as timeout
            try {
                return @ssh2_connect($host, $port, 10);
            } catch (TypeError $e2) {
                // Try with methods array
                try {
                    return @ssh2_connect($host, $port, array());
                } catch (Exception $e3) {
                    return false;
                }
            }
        }
    }
    
    /**
     * Handle deploy AJAX request
     */
    public function handle_deploy_ajax() {
        check_ajax_referer('bpd_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Debug logging
        error_log('BPD: Raw POST data: ' . var_export($_POST, true));
        
        // Handle plugins - could be array or JSON string
        $plugins_data = $_POST['plugins'];
        error_log('BPD: Raw plugins data: ' . var_export($plugins_data, true));
        if (is_string($plugins_data)) {
            // Handle double-escaped JSON (WordPress escaping)
            $plugins_data = stripslashes($plugins_data);
            error_log('BPD: After stripslashes plugins data: ' . var_export($plugins_data, true));
            
            $plugins_data = json_decode($plugins_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('BPD: JSON decode error for plugins: ' . json_last_error_msg());
                $plugins_data = array();
            }
        }
        if (!is_array($plugins_data)) {
            error_log('BPD: plugins_data is not an array: ' . var_export($plugins_data, true));
            $plugins_data = array();
        }
        $plugin_names = array_map('sanitize_text_field', $plugins_data);
        
        // Handle sites - could be array or JSON string
        $sites_data = $_POST['sites'];
        error_log('BPD: Raw sites data: ' . var_export($sites_data, true));
        if (is_string($sites_data)) {
            // Handle double-escaped JSON (WordPress escaping)
            $sites_data = stripslashes($sites_data);
            error_log('BPD: After stripslashes sites data: ' . var_export($sites_data, true));
            
            $sites_data = json_decode($sites_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('BPD: JSON decode error for sites: ' . json_last_error_msg());
                $sites_data = array();
            }
        }
        if (!is_array($sites_data)) {
            error_log('BPD: sites_data is not an array: ' . var_export($sites_data, true));
            $sites_data = array();
        }
        $site_ids = array_map('intval', $sites_data);
        
        if (empty($plugin_names)) {
            wp_send_json_error('No plugins selected for deployment');
        }
        
        if (empty($site_ids)) {
            wp_send_json_error('No sites selected for deployment');
        }
        
        $results = $this->deploy_plugins($plugin_names, $site_ids);
        
        // Count successes and failures
        $success_count = 0;
        $failure_count = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $success_count++;
            } else {
                $failure_count++;
            }
        }
        
        $response = array(
            'results' => $results,
            'summary' => array(
                'total' => count($results),
                'success' => $success_count,
                'failure' => $failure_count
            )
        );
        
        if ($failure_count === 0) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }
    
    /**
     * Normalize FTP path to ensure it ends with forward slash
     */
    private function normalize_ftp_path($path) {
        if (empty($path)) {
            return '/wp-content/plugins/';
        }
        
        // Remove any trailing slashes first
        $path = rtrim($path, '/');
        
        // Add forward slash
        return $path . '/';
    }
    
    /**
     * Remove plugin directory via FTP (force overwrite)
     */
    private function remove_plugin_directory_ftp($ftp_conn, $remote_dir) {
        // Try to remove the directory and all its contents
        $this->remove_directory_contents_ftp($ftp_conn, $remote_dir);
        
        // Remove the directory itself
        @ftp_rmdir($ftp_conn, $remote_dir);
    }
    
    /**
     * Remove directory contents via FTP recursively
     */
    private function remove_directory_contents_ftp($ftp_conn, $dir) {
        $files = ftp_nlist($ftp_conn, $dir);
        
        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file);
                $full_path = $dir . '/' . $filename;
                
                // Check if it's a directory
                if (@ftp_chdir($ftp_conn, $full_path)) {
                    // It's a directory, go back and remove it
                    ftp_chdir($ftp_conn, '..');
                    $this->remove_directory_contents_ftp($ftp_conn, $full_path);
                    @ftp_rmdir($ftp_conn, $full_path);
                } else {
                    // It's a file, delete it
                    @ftp_delete($ftp_conn, $full_path);
                }
            }
        }
    }
    
    /**
     * Remove plugin directory via SFTP (force overwrite)
     */
    private function remove_plugin_directory_sftp($sftp, $remote_dir) {
        // Try to remove the directory and all its contents
        $this->remove_directory_contents_sftp($sftp, $remote_dir);
        
        // Remove the directory itself
        @ssh2_sftp_rmdir($sftp, $remote_dir);
    }
    
    /**
     * Remove directory contents via SFTP recursively
     */
    private function remove_directory_contents_sftp($sftp, $dir) {
        $handle = @opendir("ssh2.sftp://{$sftp}{$dir}");
        
        if ($handle) {
            while (($file = readdir($handle)) !== false) {
                if ($file != '.' && $file != '..') {
                    $full_path = $dir . '/' . $file;
                    $sftp_path = "ssh2.sftp://{$sftp}{$full_path}";
                    
                    if (is_dir($sftp_path)) {
                        // It's a directory, remove it recursively
                        $this->remove_directory_contents_sftp($sftp, $full_path);
                        @ssh2_sftp_rmdir($sftp, $full_path);
                    } else {
                        // It's a file, delete it
                        @ssh2_sftp_unlink($sftp, $full_path);
                    }
                }
            }
            closedir($handle);
        }
    }
    
    /**
     * Extract zip file via FTP using server-side commands
     */
    private function extract_zip_via_ftp($ftp_conn, $remote_zip, $remote_dir) {
        // Try to execute unzip command on the server
        $unzip_command = "cd {$remote_dir} && unzip -o ../{$remote_zip}";
        
        // Execute the command via FTP
        $result = @ftp_exec($ftp_conn, $unzip_command);
        
        if ($result) {
            return array('success' => true, 'message' => 'Zip extracted successfully');
        } else {
            // Fallback: Try alternative extraction methods
            return $this->extract_zip_via_ftp_fallback($ftp_conn, $remote_zip, $remote_dir);
        }
    }
    
    /**
     * Fallback zip extraction via FTP (manual file extraction)
     */
    private function extract_zip_via_ftp_fallback($ftp_conn, $remote_zip, $remote_dir) {
        // Download the zip file locally
        $temp_local_zip = sys_get_temp_dir() . '/' . basename($remote_zip);
        
        if (!@ftp_get($ftp_conn, $temp_local_zip, $remote_zip, FTP_BINARY)) {
            return array('success' => false, 'message' => 'Could not download zip file for extraction');
        }
        
        // Extract locally
        $zip = new ZipArchive();
        if ($zip->open($temp_local_zip) !== TRUE) {
            unlink($temp_local_zip);
            return array('success' => false, 'message' => 'Could not open zip file for extraction');
        }
        
        $extract_path = sys_get_temp_dir() . '/' . uniqid('extract_');
        if (!$zip->extractTo($extract_path)) {
            $zip->close();
            unlink($temp_local_zip);
            return array('success' => false, 'message' => 'Could not extract zip file locally');
        }
        $zip->close();
        
        // Find the actual plugin directory (it might be nested)
        $plugin_dir = $this->find_plugin_root_directory($extract_path);
        
        // Upload extracted files to remote directory
        $upload_result = $this->upload_extracted_files_ftp($ftp_conn, $plugin_dir, $remote_dir);
        
        // Clean up
        $this->remove_directory_recursive($extract_path);
        unlink($temp_local_zip);
        
        return $upload_result;
    }
    
    /**
     * Upload extracted files via FTP
     */
    private function upload_extracted_files_ftp($ftp_conn, $local_path, $remote_dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($local_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($local_path) + 1);
            
            // Debug logging
            error_log("BPD: File path: " . $file_path);
            error_log("BPD: Local path: " . $local_path);
            error_log("BPD: Relative path: " . $relative_path);
            error_log("BPD: Remote file: " . $remote_dir . '/' . $relative_path);
            
            // Upload file directly to remote directory (no subdirectory)
            $remote_file = $remote_dir . '/' . $relative_path;
            
            // Create remote directory if needed
            $remote_file_dir = dirname($remote_file);
            if ($remote_file_dir !== $remote_dir) {
                @ftp_mkdir($ftp_conn, $remote_file_dir);
            }
            
            // Upload file
            if (!@ftp_put($ftp_conn, $remote_file, $file_path, FTP_BINARY)) {
                return array('success' => false, 'message' => "Failed to upload file: {$relative_path}");
            }
        }
        
        return array('success' => true, 'message' => 'All files uploaded successfully');
    }
    
    /**
     * Extract zip file via SFTP
     */
    private function extract_zip_via_sftp($sftp, $remote_zip, $remote_dir) {
        // Download the zip file locally
        $temp_local_zip = sys_get_temp_dir() . '/' . basename($remote_zip);
        
        $stream = @fopen("ssh2.sftp://{$sftp}{$remote_zip}", 'r');
        if (!$stream) {
            return array('success' => false, 'message' => 'Could not open remote zip file for extraction');
        }
        
        $local_stream = fopen($temp_local_zip, 'w');
        $downloaded = stream_copy_to_stream($stream, $local_stream);
        fclose($local_stream);
        fclose($stream);
        
        if ($downloaded === false) {
            return array('success' => false, 'message' => 'Could not download zip file for extraction');
        }
        
        // Extract locally
        $zip = new ZipArchive();
        if ($zip->open($temp_local_zip) !== TRUE) {
            unlink($temp_local_zip);
            return array('success' => false, 'message' => 'Could not open zip file for extraction');
        }
        
        $extract_path = sys_get_temp_dir() . '/' . uniqid('extract_');
        if (!$zip->extractTo($extract_path)) {
            $zip->close();
            unlink($temp_local_zip);
            return array('success' => false, 'message' => 'Could not extract zip file locally');
        }
        $zip->close();
        
        // Find the actual plugin directory (it might be nested)
        $plugin_dir = $this->find_plugin_root_directory($extract_path);
        
        // Upload extracted files to remote directory
        $upload_result = $this->upload_extracted_files_sftp($sftp, $plugin_dir, $remote_dir);
        
        // Clean up
        $this->remove_directory_recursive($extract_path);
        unlink($temp_local_zip);
        
        return $upload_result;
    }
    
    /**
     * Upload extracted files via SFTP
     */
    private function upload_extracted_files_sftp($sftp, $local_path, $remote_dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($local_path),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($local_path) + 1);
            
            // Debug logging
            error_log("BPD: SFTP File path: " . $file_path);
            error_log("BPD: SFTP Local path: " . $local_path);
            error_log("BPD: SFTP Relative path: " . $relative_path);
            error_log("BPD: SFTP Remote file: " . $remote_dir . '/' . $relative_path);
            
            $remote_file = $remote_dir . '/' . $relative_path;
            
            // Create remote directory if needed
            $remote_file_dir = dirname($remote_file);
            if ($remote_file_dir !== $remote_dir) {
                @ssh2_sftp_mkdir($sftp, $remote_file_dir, 0755, true);
            }
            
            // Upload file
            $stream = @fopen("ssh2.sftp://{$sftp}{$remote_file}", 'w');
            if (!$stream) {
                return array('success' => false, 'message' => "Failed to create remote file: {$relative_path}");
            }
            
            $local_stream = fopen($file_path, 'r');
            $uploaded = stream_copy_to_stream($local_stream, $stream);
            fclose($local_stream);
            fclose($stream);
            
            if ($uploaded === false) {
                return array('success' => false, 'message' => "Failed to upload file: {$relative_path}");
            }
        }
        
        return array('success' => true, 'message' => 'All files uploaded successfully');
    }
    
    /**
     * Remove directory recursively (local filesystem)
     */
    private function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Find the actual plugin root directory from extracted files
     */
    private function find_plugin_root_directory($extract_path) {
        // Check if the extract path contains the plugin files directly
        if (file_exists($extract_path . '/plugin.php') || 
            file_exists($extract_path . '/index.php') ||
            file_exists($extract_path . '/style.css') ||
            file_exists($extract_path . '/ai-story-maker.php')) {
            return $extract_path;
        }
        
        // Look for a subdirectory that contains the plugin files
        $items = scandir($extract_path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $subdir = $extract_path . '/' . $item;
            if (is_dir($subdir)) {
                // Check if this subdirectory contains plugin files
                if (file_exists($subdir . '/plugin.php') || 
                    file_exists($subdir . '/index.php') ||
                    file_exists($subdir . '/style.css') ||
                    file_exists($subdir . '/ai-story-maker.php') ||
                    file_exists($subdir . '/' . $item . '.php')) {
                    return $subdir;
                }
            }
        }
        
        // If no plugin files found, return the extract path
        return $extract_path;
    }
} 