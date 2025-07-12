<?php
/**
 * Plugin Name: Bulk Plugin Deployer
 * Plugin URI: https://github.com/your-username/bulk-plugin-deployer
 * Description: Deploy WordPress plugins to multiple sites via FTP. Select plugins and target sites, then deploy them in bulk.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bulk-plugin-deployer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BPD_PLUGIN_VERSION', '1.0.0');
define('BPD_PLUGIN_FILE', __FILE__);
define('BPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BPD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BPD_PLUGIN_DIR . 'includes/class-bulk-plugin-deployer.php';
require_once BPD_PLUGIN_DIR . 'includes/class-bpd-admin.php';
require_once BPD_PLUGIN_DIR . 'includes/class-bpd-deployer.php';
require_once BPD_PLUGIN_DIR . 'includes/class-bpd-site-manager.php';
require_once BPD_PLUGIN_DIR . 'includes/class-bpd-sftp-deployer.php';

// Initialize the plugin
function bpd_init() {
    // Check system requirements
    if (!function_exists('ftp_connect')) {
        add_action('admin_notices', 'bpd_ftp_extension_notice');
        return;
    }
    
    $plugin = new Bulk_Plugin_Deployer();
    $plugin->init();
}
add_action('plugins_loaded', 'bpd_init');

// Show notice if FTP extension is missing
function bpd_ftp_extension_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Bulk Plugin Deployer:</strong> 
            The PHP FTP extension is required but not installed. Please contact your hosting provider to install the php-ftp extension.
        </p>
    </div>
    <?php
}

// Activation hook
register_activation_hook(__FILE__, 'bpd_activate');
function bpd_activate() {
    // Create necessary database tables
    require_once BPD_PLUGIN_DIR . 'includes/class-bpd-installer.php';
    $installer = new BPD_Installer();
    $installer->install();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'bpd_deactivate');
function bpd_deactivate() {
    // Cleanup if needed
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'bpd_uninstall');
function bpd_uninstall() {
    // Remove database tables and options
    require_once BPD_PLUGIN_DIR . 'includes/class-bpd-installer.php';
    $installer = new BPD_Installer();
    $installer->uninstall();
} 