<?php
/**
 * SFTP-based deployment class
 * 
 * Alternative deployment method for Docker environments where FTP is blocked
 */
class BPD_SFTP_Deployer {
    
    /**
     * Deploy plugin via SFTP
     */
    public function deploy_plugin_sftp($plugin_path, $plugin_name, $site_data) {
        // Check if SSH2 extension is available
        if (!function_exists('ssh2_connect')) {
            return array(
                'success' => false,
                'message' => 'SSH2 extension is not available. Please install php-ssh2 extension.'
            );
        }
        
        $host = $site_data['ftp_host'];
        $port = isset($site_data['sftp_port']) ? (int) $site_data['sftp_port'] : 22;
        $username = $site_data['ftp_username'];
        $password = $site_data['ftp_password'];
        $remote_path = isset($site_data['ftp_path']) ? $site_data['ftp_path'] : '/wp-content/plugins/';
        
        // Create temporary zip file
        $temp_zip = $this->create_plugin_zip($plugin_path, $plugin_name);
        if (!$temp_zip) {
            return array('success' => false, 'message' => 'Failed to create plugin zip');
        }
        
        // Connect via SFTP
        $connection = @ssh2_connect($host, $port);
        if (!$connection) {
            unlink($temp_zip);
            return array('success' => false, 'message' => 'Could not connect to SFTP server');
        }
        
        // Authenticate
        if (!@ssh2_auth_password($connection, $username, $password)) {
            unlink($temp_zip);
            return array('success' => false, 'message' => 'SFTP authentication failed');
        }
        
        // Create SFTP session
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) {
            unlink($temp_zip);
            return array('success' => false, 'message' => 'Could not create SFTP session');
        }
        
        // Upload file
        $remote_file = $remote_path . $plugin_name . '.zip';
        $stream = @fopen("ssh2.sftp://{$sftp}{$remote_file}", 'w');
        if (!$stream) {
            unlink($temp_zip);
            return array('success' => false, 'message' => 'Could not open remote file for writing');
        }
        
        $local_stream = fopen($temp_zip, 'r');
        $uploaded = stream_copy_to_stream($local_stream, $stream);
        fclose($local_stream);
        fclose($stream);
        
        // Clean up local zip
        unlink($temp_zip);
        
        if ($uploaded === false) {
            return array('success' => false, 'message' => 'Failed to upload plugin zip');
        }
        
        return array('success' => true, 'message' => 'Plugin uploaded successfully via SFTP');
    }
    
    /**
     * Create plugin zip file
     */
    private function create_plugin_zip($plugin_path, $plugin_name) {
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
     * Test SFTP connection
     */
    public function test_sftp_connection($site_data) {
        if (!function_exists('ssh2_connect')) {
            return array(
                'success' => false,
                'message' => 'SSH2 extension is not available'
            );
        }
        
        $host = $site_data['ftp_host'];
        $port = isset($site_data['sftp_port']) ? (int) $site_data['sftp_port'] : 22;
        $username = $site_data['ftp_username'];
        $password = $site_data['ftp_password'];
        
        $connection = @ssh2_connect($host, $port, 10);
        if (!$connection) {
            return array('success' => false, 'message' => 'Could not connect to SFTP server');
        }
        
        if (!@ssh2_auth_password($connection, $username, $password)) {
            return array('success' => false, 'message' => 'SFTP authentication failed');
        }
        
        $sftp = @ssh2_sftp($connection);
        if (!$sftp) {
            return array('success' => false, 'message' => 'Could not create SFTP session');
        }
        
        return array('success' => true, 'message' => 'SFTP connection successful');
    }
} 