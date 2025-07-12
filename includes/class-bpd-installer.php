<?php
/**
 * Plugin installer class
 */
class BPD_Installer {
    
    /**
     * Install plugin
     */
    public function install() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    /**
     * Uninstall plugin
     */
    public function uninstall() {
        $this->drop_tables();
        $this->delete_options();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bpd_sites';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            ftp_host varchar(255) NOT NULL,
            ftp_port int(5) DEFAULT 21,
            ftp_username varchar(255) NOT NULL,
            ftp_password text NOT NULL,
            ftp_path varchar(255) DEFAULT '/wp-content/plugins/',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name (name),
            KEY url (url)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add version to options
        add_option('bpd_db_version', '1.0.0');
    }
    
    /**
     * Drop database tables
     */
    private function drop_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bpd_sites';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        add_option('bpd_settings', array(
            'max_execution_time' => 300,
            'memory_limit' => '256M',
            'enable_logging' => true,
            'log_level' => 'info'
        ));
    }
    
    /**
     * Delete options
     */
    private function delete_options() {
        delete_option('bpd_settings');
        delete_option('bpd_db_version');
    }
} 