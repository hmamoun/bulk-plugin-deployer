<?php
/**
 * Admin interface class
 */
class BPD_Admin {
    
    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Bulk Plugin Deployer',
            'Plugin Deployer',
            'manage_options',
            'bulk-plugin-deployer',
            array($this, 'render_admin_page'),
            'dashicons-upload',
            30
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_bulk-plugin-deployer' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'bpd-admin-js',
            BPD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            BPD_PLUGIN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'bpd-admin-css',
            BPD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BPD_PLUGIN_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('bpd-admin-js', 'bpd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bpd_nonce'),
            'strings' => array(
                'deploying' => __('Deploying plugins...', 'bulk-plugin-deployer'),
                'success' => __('Deployment completed successfully!', 'bulk-plugin-deployer'),
                'error' => __('An error occurred during deployment.', 'bulk-plugin-deployer'),
                'confirm_delete' => __('Are you sure you want to delete this site?', 'bulk-plugin-deployer'),
                'testing_connection' => __('Testing connection...', 'bulk-plugin-deployer'),
                'connection_success' => __('Connection successful!', 'bulk-plugin-deployer'),
                'connection_failed' => __('Connection failed. Please check your credentials.', 'bulk-plugin-deployer')
            )
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $site_manager = new BPD_Site_Manager();
        $sites = $site_manager->get_sites();
        $plugins = $this->get_available_plugins();
        
        include BPD_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Get available plugins for deployment
     */
    private function get_available_plugins() {
        $plugins = array();
        
        // Get plugins from wp-content/plugins directory
        $plugins_dir = WP_CONTENT_DIR . '/plugins/';
        if (is_dir($plugins_dir)) {
            $plugin_dirs = glob($plugins_dir . '*', GLOB_ONLYDIR);
            
            foreach ($plugin_dirs as $plugin_dir) {
                $plugin_name = basename($plugin_dir);
                $plugin_file = $plugin_dir . '/' . $plugin_name . '.php';
                
                if (file_exists($plugin_file)) {
                    $plugin_data = get_plugin_data($plugin_file);
                    $plugins[$plugin_name] = array(
                        'name' => $plugin_data['Name'] ?: $plugin_name,
                        'description' => $plugin_data['Description'] ?: '',
                        'version' => $plugin_data['Version'] ?: '1.0.0',
                        'path' => $plugin_dir
                    );
                }
            }
        }
        
        return $plugins;
    }
} 