<?php
/**
 * Test deployment script for Bulk Plugin Deployer
 * 
 * This script can be used to test the deployment functionality
 * without going through the WordPress admin interface.
 * 
 * Usage: php deploy-test.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!function_exists('wp_loaded')) {
        require_once('../../../wp-load.php');
    }
}

// Check if we're in WordPress context
if (!function_exists('wp_loaded')) {
    die('This script must be run from within WordPress context.');
}

// Test deployment function
function test_deployment() {
    echo "=== Bulk Plugin Deployer Test Deployment ===\n\n";
    
    // Test site data
    $test_site = array(
        'name' => 'Test Site',
        'url' => 'https://example.com',
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => 'testpass',
        'ftp_path' => '/wp-content/plugins/'
    );
    
    // Test plugin
    $test_plugin = 'hello-dolly'; // WordPress default plugin
    
    echo "Testing deployment of plugin: {$test_plugin}\n";
    echo "To site: {$test_site['name']} ({$test_site['url']})\n\n";
    
    // Initialize deployer
    $deployer = new BPD_Deployer();
    
    // Test connection
    echo "Testing FTP connection...\n";
    $site_manager = new BPD_Site_Manager();
    $connection_result = $site_manager->test_connection($test_site);
    
    if ($connection_result['success']) {
        echo "✓ Connection successful\n\n";
    } else {
        echo "✗ Connection failed: {$connection_result['message']}\n\n";
        return;
    }
    
    // Test plugin deployment
    echo "Testing plugin deployment...\n";
    $plugin_path = WP_CONTENT_DIR . '/plugins/' . $test_plugin;
    
    if (!is_dir($plugin_path)) {
        echo "✗ Plugin directory not found: {$plugin_path}\n";
        return;
    }
    
    echo "✓ Plugin directory found\n";
    
    // Test zip creation
    echo "Testing zip creation...\n";
    $temp_zip = $deployer->create_plugin_zip($plugin_path, $test_plugin);
    
    if ($temp_zip && file_exists($temp_zip)) {
        echo "✓ Zip file created: " . basename($temp_zip) . "\n";
        echo "  Size: " . number_format(filesize($temp_zip)) . " bytes\n";
        
        // Clean up
        unlink($temp_zip);
        echo "✓ Temporary zip file cleaned up\n\n";
    } else {
        echo "✗ Failed to create zip file\n\n";
        return;
    }
    
    echo "=== Test completed successfully ===\n";
    echo "The plugin is ready for deployment!\n\n";
    
    echo "To use the plugin:\n";
    echo "1. Activate the Bulk Plugin Deployer plugin in WordPress admin\n";
    echo "2. Go to 'Plugin Deployer' in the admin menu\n";
    echo "3. Add your target sites with FTP credentials\n";
    echo "4. Select plugins and sites for deployment\n";
    echo "5. Click 'Deploy Selected Plugins'\n\n";
    
    echo "For more information, see the README.md file.\n";
}

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    test_deployment();
} 