<?php
/**
 * PriceWise Uninstall
 *
 * Uninstalling PriceWise deletes tables, options, and other data.
 * This file runs when the plugin is uninstalled via the WordPress admin.
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom database tables
global $wpdb;

// Delete cache table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pricewise_cache");

// Delete test history table
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pricewise_test_history");

// Delete plugin options
delete_option('pricewise_manual_apis');
delete_option('pricewise_search_form_page_id');
delete_option('pricewise_results_page_id');
delete_option('pricewise_cache_stats');
delete_option('pricewise_cache_migration_status');
delete_option('pricewise_cache_migration_notice_dismissed');
delete_option('pricewise_cache_migration_progress');
delete_option('pricewise_cache_migration_batch');
delete_option('pricewise_migration_notice_dismissed');
delete_option('pricewise_version');

// Clear any transients we've set
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%pricewise%' AND option_name LIKE '%transient%'");

// Clean up any object cache if available
if (function_exists('wp_cache_flush_group')) {
    wp_cache_flush_group('pricewise');
} elseif (wp_using_ext_object_cache()) {
    // If group-specific flush isn't available but using object cache, 
    // at least try to delete common keys
    $common_keys = array(
        'pw_api_',
        'pw_default_',
        'pw_destination_',
        'pw_hotel_'
    );
    
    foreach ($common_keys as $key_prefix) {
        wp_cache_delete($key_prefix, 'pricewise');
    }
}

// Clear scheduled hooks
wp_clear_scheduled_hook('pricewise_daily_maintenance');