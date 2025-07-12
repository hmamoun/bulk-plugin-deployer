<?php
/**
 * Main plugin class
 */
class Bulk_Plugin_Deployer {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Admin class instance
     */
    private $admin;
    
    /**
     * Deployer class instance
     */
    private $deployer;
    
    /**
     * Site manager instance
     */
    private $site_manager;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize components
        $this->admin = new BPD_Admin();
        $this->deployer = new BPD_Deployer();
        $this->site_manager = new BPD_Site_Manager();
        
        // Hook into WordPress
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            $this->admin->init();
        }
        
        // AJAX handlers
        add_action('wp_ajax_bpd_deploy_plugins', array($this->deployer, 'handle_deploy_ajax'));
        add_action('wp_ajax_bpd_save_site', array($this->site_manager, 'handle_save_site_ajax'));
        add_action('wp_ajax_bpd_delete_site', array($this->site_manager, 'handle_delete_site_ajax'));
        add_action('wp_ajax_bpd_test_connection', array($this->site_manager, 'handle_test_connection_ajax'));
    }
    
    /**
     * Get admin instance
     */
    public function get_admin() {
        return $this->admin;
    }
    
    /**
     * Get deployer instance
     */
    public function get_deployer() {
        return $this->deployer;
    }
    
    /**
     * Get site manager instance
     */
    public function get_site_manager() {
        return $this->site_manager;
    }
} 