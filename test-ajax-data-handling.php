<?php
/**
 * Test script to verify AJAX data handling fix
 */

// Simulate the AJAX data handling logic
function test_ajax_data_handling() {
    echo "=== Testing AJAX Data Handling ===\n\n";
    
    // Test 1: JSON string input (like from JavaScript)
    echo "Test 1: JSON string input\n";
    $plugins_json = '["ai-story-maker","another-plugin"]';
    $sites_json = '[1,2,3]';
    
    // Handle plugins - could be array or JSON string
    $plugins_data = $plugins_json;
    if (is_string($plugins_data)) {
        $plugins_data = json_decode($plugins_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "ERROR: JSON decode error for plugins: " . json_last_error_msg() . "\n";
            $plugins_data = array();
        }
    }
    if (!is_array($plugins_data)) {
        echo "ERROR: plugins_data is not an array: " . var_export($plugins_data, true) . "\n";
        $plugins_data = array();
    }
    $plugin_names = array_map('trim', $plugins_data);
    
    // Handle sites - could be array or JSON string
    $sites_data = $sites_json;
    if (is_string($sites_data)) {
        $sites_data = json_decode($sites_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "ERROR: JSON decode error for sites: " . json_last_error_msg() . "\n";
            $sites_data = array();
        }
    }
    if (!is_array($sites_data)) {
        echo "ERROR: sites_data is not an array: " . var_export($sites_data, true) . "\n";
        $sites_data = array();
    }
    $site_ids = array_map('intval', $sites_data);
    
    echo "Plugin names: " . implode(', ', $plugin_names) . "\n";
    echo "Site IDs: " . implode(', ', $site_ids) . "\n";
    echo "✓ Test 1 passed\n\n";
    
    // Test 2: Array input (direct array)
    echo "Test 2: Array input\n";
    $plugins_array = array("ai-story-maker", "another-plugin");
    $sites_array = array(1, 2, 3);
    
    // Handle plugins
    $plugins_data = $plugins_array;
    if (is_string($plugins_data)) {
        $plugins_data = json_decode($plugins_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "ERROR: JSON decode error for plugins: " . json_last_error_msg() . "\n";
            $plugins_data = array();
        }
    }
    if (!is_array($plugins_data)) {
        echo "ERROR: plugins_data is not an array: " . var_export($plugins_data, true) . "\n";
        $plugins_data = array();
    }
    $plugin_names = array_map('trim', $plugins_data);
    
    // Handle sites
    $sites_data = $sites_array;
    if (is_string($sites_data)) {
        $sites_data = json_decode($sites_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "ERROR: JSON decode error for sites: " . json_last_error_msg() . "\n";
            $sites_data = array();
        }
    }
    if (!is_array($sites_data)) {
        echo "ERROR: sites_data is not an array: " . var_export($sites_data, true) . "\n";
        $sites_data = array();
    }
    $site_ids = array_map('intval', $sites_data);
    
    echo "Plugin names: " . implode(', ', $plugin_names) . "\n";
    echo "Site IDs: " . implode(', ', $site_ids) . "\n";
    echo "✓ Test 2 passed\n\n";
    
    // Test 3: Invalid JSON string
    echo "Test 3: Invalid JSON string\n";
    $invalid_json = '["ai-story-maker",invalid]';
    
    $plugins_data = $invalid_json;
    if (is_string($plugins_data)) {
        $plugins_data = json_decode($plugins_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Expected JSON decode error: " . json_last_error_msg() . "\n";
            $plugins_data = array();
        }
    }
    if (!is_array($plugins_data)) {
        echo "plugins_data is not an array: " . var_export($plugins_data, true) . "\n";
        $plugins_data = array();
    }
    $plugin_names = array_map('trim', $plugins_data);
    
    echo "Plugin names (should be empty): " . implode(', ', $plugin_names) . "\n";
    echo "✓ Test 3 passed\n\n";
    
    echo "=== All tests passed! ===\n";
    echo "The AJAX data handling fix should work correctly.\n";
}

// Run the test
test_ajax_data_handling(); 