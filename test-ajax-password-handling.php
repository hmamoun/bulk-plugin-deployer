<?php
/**
 * Test script for AJAX password handling
 * 
 * This script simulates the AJAX password handling logic to ensure it works correctly
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

function test_ajax_password_handling() {
    echo "=== Testing AJAX Password Handling Logic ===\n\n";
    
    $site_manager = new BPD_Site_Manager();
    
    // Create a test site first
    echo "Creating test site...\n";
    $test_site_data = array(
        'name' => 'AJAX Test Site ' . time(),
        'url' => 'https://example.com',
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => 'originalpassword123',
        'ftp_path' => '/wp-content/plugins/'
    );
    
    $site_id = $site_manager->save_site($test_site_data);
    if (!$site_id) {
        echo "✗ Failed to create test site\n";
        return;
    }
    echo "✓ Test site created with ID: {$site_id}\n\n";
    
    // Test 1: Simulate AJAX save with new password
    echo "Test 1: AJAX save with new password...\n";
    $ajax_data_1 = array(
        'id' => $site_id,
        'name' => 'Updated Site',
        'url' => 'https://example.com',
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => 'newpassword456', // New password provided
        'ftp_path' => '/wp-content/plugins/'
    );
    
    $result_1 = simulate_ajax_save($site_manager, $ajax_data_1);
    if ($result_1) {
        echo "✓ AJAX save with new password successful\n";
        
        // Verify password was updated
        $site = $site_manager->get_site($site_id);
        $decrypted = $site_manager->decrypt_password($site['ftp_password']);
        if ($decrypted === 'newpassword456') {
            echo "✓ Password correctly updated to: {$decrypted}\n";
        } else {
            echo "✗ Password not updated correctly. Expected: 'newpassword456', Got: '{$decrypted}'\n";
        }
    } else {
        echo "✗ AJAX save with new password failed\n";
    }
    
    // Test 2: Simulate AJAX save with empty password (should keep existing)
    echo "\nTest 2: AJAX save with empty password...\n";
    $ajax_data_2 = array(
        'id' => $site_id,
        'name' => 'Updated Site Again',
        'url' => 'https://example.com',
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => '', // Empty password
        'ftp_path' => '/wp-content/plugins/'
    );
    
    $result_2 = simulate_ajax_save($site_manager, $ajax_data_2);
    if ($result_2) {
        echo "✓ AJAX save with empty password successful\n";
        
        // Verify password was preserved
        $site = $site_manager->get_site($site_id);
        $decrypted = $site_manager->decrypt_password($site['ftp_password']);
        if ($decrypted === 'newpassword456') {
            echo "✓ Password correctly preserved: {$decrypted}\n";
        } else {
            echo "✗ Password not preserved. Expected: 'newpassword456', Got: '{$decrypted}'\n";
        }
    } else {
        echo "✗ AJAX save with empty password failed\n";
    }
    
    // Test 3: Simulate AJAX test connection with empty password
    echo "\nTest 3: AJAX test connection with empty password...\n";
    $ajax_data_3 = array(
        'id' => $site_id,
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => '', // Empty password
        'ftp_path' => '/wp-content/plugins/'
    );
    
    $result_3 = simulate_ajax_test_connection($site_manager, $ajax_data_3);
    if ($result_3) {
        echo "✓ AJAX test connection logic works correctly\n";
        echo "✓ Retrieved password for connection test: {$result_3}\n";
    } else {
        echo "✗ AJAX test connection logic failed\n";
    }
    
    // Clean up
    echo "\nCleaning up...\n";
    $delete_result = $site_manager->delete_site($site_id);
    if ($delete_result) {
        echo "✓ Test site deleted\n";
    } else {
        echo "✗ Failed to delete test site\n";
    }
    
    echo "\n=== AJAX Password Handling Test Complete ===\n";
}

function simulate_ajax_save($site_manager, $ajax_data) {
    // Simulate the AJAX handler logic
    $site_data = array(
        'name' => sanitize_text_field($ajax_data['name']),
        'url' => esc_url_raw($ajax_data['url']),
        'ftp_host' => sanitize_text_field($ajax_data['ftp_host']),
        'ftp_port' => (int) $ajax_data['ftp_port'],
        'ftp_username' => sanitize_text_field($ajax_data['ftp_username']),
        'ftp_path' => sanitize_text_field($ajax_data['ftp_path'])
    );
    
    // Handle password logic
    if (isset($ajax_data['ftp_password']) && $ajax_data['ftp_password'] !== '') {
        // New password provided - use it
        $site_data['ftp_password'] = $ajax_data['ftp_password'];
    } elseif (isset($ajax_data['id']) && $ajax_data['id']) {
        // Editing existing site with empty password - get from database
        $existing_site = $site_manager->get_site((int) $ajax_data['id']);
        if ($existing_site) {
            $site_data['ftp_password'] = $site_manager->decrypt_password($existing_site['ftp_password']);
        } else {
            return false;
        }
    } else {
        // New site with empty password - this should not happen due to validation
        return false;
    }
    
    if (isset($ajax_data['id']) && $ajax_data['id']) {
        $site_data['id'] = (int) $ajax_data['id'];
    }
    
    return $site_manager->save_site($site_data);
}

function simulate_ajax_test_connection($site_manager, $ajax_data) {
    // Simulate the AJAX test connection logic
    $site_data = array(
        'ftp_host' => sanitize_text_field($ajax_data['ftp_host']),
        'ftp_port' => (int) $ajax_data['ftp_port'],
        'ftp_username' => sanitize_text_field($ajax_data['ftp_username']),
        'ftp_path' => sanitize_text_field($ajax_data['ftp_path'])
    );
    
    // Handle password logic for test connection
    if (isset($ajax_data['ftp_password']) && $ajax_data['ftp_password'] !== '') {
        // New password provided - use it
        $site_data['ftp_password'] = $ajax_data['ftp_password'];
    } elseif (isset($ajax_data['id']) && $ajax_data['id']) {
        // Testing existing site with empty password - get from database
        $existing_site = $site_manager->get_site((int) $ajax_data['id']);
        if ($existing_site) {
            $site_data['ftp_password'] = $site_manager->decrypt_password($existing_site['ftp_password']);
        } else {
            return false;
        }
    } else {
        // New connection test with empty password
        return false;
    }
    
    return $site_data['ftp_password'];
}

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    test_ajax_password_handling();
} 