<?php
/**
 * Test SFTP Connection Script
 * 
 * This script tests SFTP connectivity to DreamHost servers
 * Run this from within your Docker container to test SFTP connections.
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

function test_sftp_connection() {
    echo "=== SFTP Connection Test for DreamHost ===\n\n";
    
    // Test site data (replace with your actual credentials)
    $test_site = array(
        'ftp_host' => 'iad1-shared-b7-03.dreamhost.com',
        'ftp_port' => 22, // SFTP port
        'ftp_username' => 'dh_dsiw53', // Replace with your username
        'ftp_password' => 'your_password_here', // Replace with your password
        'ftp_path' => '/wp-content/plugins/'
    );
    
    echo "Testing SFTP connection to: {$test_site['ftp_host']}:{$test_site['ftp_port']}\n";
    echo "Username: {$test_site['ftp_username']}\n\n";
    
    // Test 1: Check if SSH2 extension is available
    echo "1. SSH2 Extension Test:\n";
    if (function_exists('ssh2_connect')) {
        echo "   ✓ SSH2 extension is available\n";
    } else {
        echo "   ✗ SSH2 extension is NOT available\n";
        echo "   Please install php-ssh2 extension:\n";
        echo "   - Ubuntu/Debian: sudo apt-get install php-ssh2\n";
        echo "   - Docker: Add 'RUN docker-php-ext-install ssh2' to Dockerfile\n";
        return;
    }
    
    // Test 2: Test basic connectivity
    echo "\n2. Basic Connectivity Test:\n";
    $connection = ssh2_connect_compat($test_site['ftp_host'], $test_site['ftp_port']);
    if ($connection) {
        echo "   ✓ SSH connection established\n";
    } else {
        $error = error_get_last();
        echo "   ✗ SSH connection failed: " . ($error['message'] ?? 'Unknown error') . "\n";
        echo "   This could be due to:\n";
        echo "   - Network connectivity issues\n";
        echo "   - Firewall blocking port 22\n";
        echo "   - Docker networking restrictions\n";
        return;
    }
    
    // Test 3: Test authentication
    echo "\n3. Authentication Test:\n";
    if (@ssh2_auth_password($connection, $test_site['ftp_username'], $test_site['ftp_password'])) {
        echo "   ✓ Authentication successful\n";
    } else {
        echo "   ✗ Authentication failed\n";
        echo "   Please check your username and password\n";
        ssh2_disconnect($connection);
        return;
    }
    
    // Test 4: Test SFTP session creation
    echo "\n4. SFTP Session Test:\n";
    $sftp = @ssh2_sftp($connection);
    if ($sftp) {
        echo "   ✓ SFTP session created successfully\n";
    } else {
        echo "   ✗ Failed to create SFTP session\n";
        ssh2_disconnect($connection);
        return;
    }
    
    // Test 5: Test directory access
    echo "\n5. Directory Access Test:\n";
    $test_path = "ssh2.sftp://{$sftp}{$test_site['ftp_path']}";
    if (is_dir($test_path)) {
        echo "   ✓ Can access plugins directory: {$test_site['ftp_path']}\n";
    } else {
        echo "   ✗ Cannot access plugins directory: {$test_site['ftp_path']}\n";
        echo "   Please check the path and permissions\n";
    }
    
    // Test 6: Test file operations
    echo "\n6. File Operations Test:\n";
    $test_file = "ssh2.sftp://{$sftp}{$test_site['ftp_path']}test.txt";
    $stream = @fopen($test_file, 'w');
    if ($stream) {
        fwrite($stream, "Test file created by Bulk Plugin Deployer\n");
        fclose($stream);
        echo "   ✓ Can create files in plugins directory\n";
        
        // Clean up test file
        @unlink($test_file);
        echo "   ✓ Can delete files from plugins directory\n";
    } else {
        echo "   ✗ Cannot create files in plugins directory\n";
        echo "   Please check write permissions\n";
    }
    
    // Clean up
    ssh2_disconnect($connection);
    
    echo "\n=== SFTP Test Complete ===\n";
    echo "\nIf all tests passed, SFTP should work with the Bulk Plugin Deployer!\n";
    echo "\nTo use SFTP in the plugin:\n";
    echo "1. Set the port to 22 in the site configuration\n";
    echo "2. Use your SFTP username and password\n";
    echo "3. The plugin will automatically detect SFTP and use it\n";
}

/**
 * SSH2 connect with compatibility for different PHP versions
 */
function ssh2_connect_compat($host, $port) {
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

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    test_sftp_connection();
} 