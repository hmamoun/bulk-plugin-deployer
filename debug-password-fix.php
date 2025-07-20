<?php
/**
 * Debug script to test password saving functionality
 * 
 * This script tests the password saving logic to ensure it works correctly
 * for both new sites and existing sites.
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

function test_password_saving() {
    echo "=== Testing Password Saving Functionality ===\n\n";
    
    $site_manager = new BPD_Site_Manager();
    
    // Test 1: Create a new site with password
    echo "Test 1: Creating new site with password...\n";
    $new_site_data = array(
        'name' => 'Test Site ' . time(),
        'url' => 'https://example.com',
        'ftp_host' => 'ftp.example.com',
        'ftp_port' => 21,
        'ftp_username' => 'testuser',
        'ftp_password' => 'testpassword123',
        'ftp_path' => '/wp-content/plugins/'
    );
    
    $site_id = $site_manager->save_site($new_site_data);
    if ($site_id) {
        echo "✓ New site created with ID: {$site_id}\n";
        
        // Test 2: Update site without password (should keep existing password)
        echo "\nTest 2: Updating site without password...\n";
        $update_data = array(
            'id' => $site_id,
            'name' => 'Updated Test Site',
            'url' => 'https://example.com',
            'ftp_host' => 'ftp.example.com',
            'ftp_port' => 21,
            'ftp_username' => 'testuser',
            'ftp_password' => '', // Empty password
            'ftp_path' => '/wp-content/plugins/'
        );
        
        $update_result = $site_manager->save_site($update_data);
        if ($update_result) {
            echo "✓ Site updated successfully\n";
            
            // Test 3: Verify password was preserved
            echo "\nTest 3: Verifying password was preserved...\n";
            $retrieved_site = $site_manager->get_site($site_id);
            $decrypted_password = $site_manager->decrypt_password($retrieved_site['ftp_password']);
            
            if ($decrypted_password === 'testpassword123') {
                echo "✓ Password was preserved correctly\n";
            } else {
                echo "✗ Password was not preserved. Expected: 'testpassword123', Got: '{$decrypted_password}'\n";
            }
            
            // Test 4: Update site with new password
            echo "\nTest 4: Updating site with new password...\n";
            $update_data['ftp_password'] = 'newpassword456';
            $update_data['name'] = 'Updated Test Site with New Password';
            
            $update_result = $site_manager->save_site($update_data);
            if ($update_result) {
                echo "✓ Site updated with new password\n";
                
                // Test 5: Verify new password was saved
                echo "\nTest 5: Verifying new password was saved...\n";
                $retrieved_site = $site_manager->get_site($site_id);
                $decrypted_password = $site_manager->decrypt_password($retrieved_site['ftp_password']);
                
                if ($decrypted_password === 'newpassword456') {
                    echo "✓ New password was saved correctly\n";
                } else {
                    echo "✗ New password was not saved correctly. Expected: 'newpassword456', Got: '{$decrypted_password}'\n";
                }
            } else {
                echo "✗ Failed to update site with new password\n";
            }
            
        } else {
            echo "✗ Failed to update site\n";
        }
        
        // Test 6: Test AJAX-style password handling simulation
        echo "\nTest 6: Testing AJAX-style password handling...\n";
        
        // Simulate AJAX data for editing with empty password
        $ajax_data = array(
            'id' => $site_id,
            'name' => 'AJAX Test Site',
            'url' => 'https://example.com',
            'ftp_host' => 'ftp.example.com',
            'ftp_port' => 21,
            'ftp_username' => 'testuser',
            'ftp_password' => '', // Empty password from form
            'ftp_path' => '/wp-content/plugins/'
        );
        
        // Simulate the AJAX handler logic
        if (isset($ajax_data['ftp_password']) && $ajax_data['ftp_password'] !== '') {
            $final_password = $ajax_data['ftp_password'];
        } elseif (isset($ajax_data['id']) && $ajax_data['id']) {
            $existing_site = $site_manager->get_site($ajax_data['id']);
            if ($existing_site) {
                $final_password = $site_manager->decrypt_password($existing_site['ftp_password']);
            } else {
                echo "✗ Site not found for AJAX test\n";
                $final_password = '';
            }
        } else {
            echo "✗ Invalid AJAX data\n";
            $final_password = '';
        }
        
        if ($final_password === 'newpassword456') {
            echo "✓ AJAX password handling works correctly\n";
        } else {
            echo "✗ AJAX password handling failed. Expected: 'newpassword456', Got: '{$final_password}'\n";
        }
        
        // Clean up: Delete test site
        echo "\nCleaning up: Deleting test site...\n";
        $delete_result = $site_manager->delete_site($site_id);
        if ($delete_result) {
            echo "✓ Test site deleted\n";
        } else {
            echo "✗ Failed to delete test site\n";
        }
        
    } else {
        echo "✗ Failed to create new site\n";
    }
    
    echo "\n=== Test Complete ===\n";
}

// Run test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    test_password_saving();
} 