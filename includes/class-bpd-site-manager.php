<?php
/**
 * Site management class
 */
class BPD_Site_Manager {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bpd_sites';
    }
    
    /**
     * Get all sites
     */
    public function get_sites() {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->table_name} ORDER BY name ASC";
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get site by ID
     */
    public function get_site($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $wpdb->get_row($sql, ARRAY_A);
    }
    
    /**
     * Save site
     */
    public function save_site($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'url' => '',
            'ftp_host' => '',
            'ftp_port' => 21,
            'ftp_username' => '',
            'ftp_password' => '',
            'ftp_path' => '/wp-content/plugins/',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name']) || empty($data['url']) || empty($data['ftp_username'])) {
            return false;
        }
        
        // Encrypt password
        $data['ftp_password'] = $this->encrypt_password($data['ftp_password']);
        
        if (isset($data['id']) && $data['id']) {
            // Update existing site
            $result = $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $data['id']),
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            return $result !== false ? $data['id'] : false;
        } else {
            // Insert new site
            $result = $wpdb->insert(
                $this->table_name,
                $data,
                array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
            );
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete site
     */
    public function delete_site($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("DELETE FROM {$this->table_name} WHERE id = %d", $id);
        return $wpdb->query($sql);
    }
    
    /**
     * Test FTP connection
     */
    public function test_connection($site_data) {
        // Check if FTP extension is available
        if (!function_exists('ftp_connect')) {
            return array(
                'success' => false, 
                'message' => 'FTP extension is not available. Please install php-ftp extension.'
            );
        }
        
        $ftp_host = $site_data['ftp_host'];
        $ftp_port = isset($site_data['ftp_port']) ? (int) $site_data['ftp_port'] : 21;
        $ftp_username = $site_data['ftp_username'];
        $ftp_password = $site_data['ftp_password'];
        $ftp_path = isset($site_data['ftp_path']) ? $site_data['ftp_path'] : '/wp-content/plugins/';
        
        error_log("Attempting FTP connection to: {$ftp_host}:{$ftp_port}");
        
        // Connect to FTP with more detailed error handling
        $conn = @ftp_connect($ftp_host, $ftp_port, 10);
        if (!$conn) {
            $error = error_get_last();
            error_log("FTP connection failed: " . ($error['message'] ?? 'Unknown error'));
            return array('success' => false, 'message' => 'Could not connect to FTP server. Check Docker networking and firewall settings.');
        }
        
        // Login
        if (!@ftp_login($conn, $ftp_username, $ftp_password)) {
            ftp_close($conn);
            return array('success' => false, 'message' => 'FTP login failed');
        }
        
        // Test passive mode
        @ftp_pasv($conn, true);
        
        // Test directory access
        if (!@ftp_chdir($conn, $ftp_path)) {
            ftp_close($conn);
            return array('success' => false, 'message' => 'Cannot access plugins directory');
        }
        
        ftp_close($conn);
        return array('success' => true, 'message' => 'Connection successful');
    }
    
    /**
     * Encrypt password
     */
    private function encrypt_password($password) {
        if (empty($password)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($password, $method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt password
     */
    public function decrypt_password($encrypted_password) {
        if (empty($encrypted_password)) {
            return '';
        }
        
        $key = wp_salt('auth');
        $method = 'AES-256-CBC';
        
        $data = base64_decode($encrypted_password);
        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
    
    /**
     * Handle save site AJAX
     */
    public function handle_save_site_ajax() {
        check_ajax_referer('bpd_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $site_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'url' => esc_url_raw($_POST['url']),
            'ftp_host' => sanitize_text_field($_POST['ftp_host']),
            'ftp_port' => (int) $_POST['ftp_port'],
            'ftp_username' => sanitize_text_field($_POST['ftp_username']),
            'ftp_password' => $_POST['ftp_password'],
            'ftp_path' => sanitize_text_field($_POST['ftp_path'])
        );
        
        if (isset($_POST['id']) && $_POST['id']) {
            $site_data['id'] = (int) $_POST['id'];
        }
        
        $result = $this->save_site($site_data);
        
        if ($result) {
            wp_send_json_success(array('id' => $result, 'message' => 'Site saved successfully'));
        } else {
            wp_send_json_error('Failed to save site');
        }
    }
    
    /**
     * Handle delete site AJAX
     */
    public function handle_delete_site_ajax() {
        check_ajax_referer('bpd_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $id = (int) $_POST['id'];
        $result = $this->delete_site($id);
        
        if ($result) {
            wp_send_json_success('Site deleted successfully');
        } else {
            wp_send_json_error('Failed to delete site');
        }
    }
    
    /**
     * Handle test connection AJAX
     */
    public function handle_test_connection_ajax() {
        check_ajax_referer('bpd_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $site_data = array(
            'ftp_host' => sanitize_text_field($_POST['ftp_host']),
            'ftp_port' => (int) $_POST['ftp_port'],
            'ftp_username' => sanitize_text_field($_POST['ftp_username']),
            'ftp_password' => $_POST['ftp_password'],
            'ftp_path' => sanitize_text_field($_POST['ftp_path'])
        );
        
        $result = $this->test_connection($site_data);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
} 