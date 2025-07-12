<?php
/**
 * Uninstall file for Bulk Plugin Deployer
 * 
 * This file is executed when the plugin is deleted from WordPress
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include WordPress database functions
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Get database prefix
global $wpdb;

// Drop the custom table
$table_name = $wpdb->prefix . 'bpd_sites';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete plugin options
delete_option('bpd_settings');
delete_option('bpd_db_version');

// Clear any cached data that has been removed
wp_cache_flush(); 