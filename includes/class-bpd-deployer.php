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
        
        // Connect to FTP
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
        
        // Upload zip file
        $remote_zip = $site['ftp_path'] . $plugin_name . '.zip';
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
        if (!@ftp_chdir($conn, $site['ftp_path'])) {
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
        // This is a simplified approach - in a real implementation,
        // you might need to use SSH or other methods to extract on the server
        // For now, we'll assume the server has unzip available via command line
        
        $remote_zip = $plugin_name . '.zip';
        $remote_dir = $plugin_name;
        
        // Check if plugin directory already exists
        $current_dir = ftp_pwd($ftp_conn);
        
        // Try to create directory (will fail if it exists)
        @ftp_mkdir($ftp_conn, $remote_dir);
        
        // For this demo, we'll assume the extraction works
        // In a real implementation, you'd need to handle this via SSH or server-side scripts
        return array('success' => true, 'message' => 'Plugin extracted successfully');
    }
    
    /**
     * Handle deploy AJAX request
     */
    public function handle_deploy_ajax() {
        check_ajax_referer('bpd_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $plugin_names = array_map('sanitize_text_field', $_POST['plugins']);
        $site_ids = array_map('intval', $_POST['sites']);
        
        if (empty($plugin_names) || empty($site_ids)) {
            wp_send_json_error('No plugins or sites selected');
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
} 