<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Pricewise
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define global $wpdb
global $wpdb;

// Delete plugin options
$options = array(
    'pricewise_rapidapi_key',
    'pricewise_rapidapi_host',
    'pricewise_default_location',
    'pricewise_default_adults',
    'pricewise_default_children',
    'pricewise_default_rooms',
    'pricewise_rate_limit_searches',
    'pricewise_cache_expiry',
);

foreach ($options as $option) {
    delete_option($option);
}

// Drop custom database tables
$tables = array(
    $wpdb->prefix . 'pricewise_cache',
    $wpdb->prefix . 'pricewise_search_logs',
    $wpdb->prefix . 'pricewise_hotels',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clear any cached data that might be stored in transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%\_transient\_%pricewise%'");

// Flush cached rewrites
flush_rewrite_rules();