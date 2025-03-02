<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Pricewise
 * @subpackage Pricewise/includes
 */

class Pricewise_Activator {

    /**
     * Create necessary database tables and initialize plugin settings on activation.
     *
     * This function is called when the plugin is activated. It creates the
     * required database tables and sets default options for the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create cache table
        $cache_table = $wpdb->prefix . 'pricewise_cache';
        $cache_sql = "CREATE TABLE IF NOT EXISTS `{$cache_table}` (
            `cache_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `cache_key` varchar(255) NOT NULL,
            `cache_value` longtext NOT NULL,
            `expiry` datetime NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`cache_id`),
            UNIQUE KEY `cache_key` (`cache_key`),
            KEY `expiry` (`expiry`)
        ) {$charset_collate};";
        
        // Create search logs table
        $logs_table = $wpdb->prefix . 'pricewise_search_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS `{$logs_table}` (
            `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned DEFAULT NULL,
            `user_ip` varchar(45) NOT NULL,
            `search_query` text NOT NULL,
            `search_params` longtext NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`log_id`),
            KEY `user_id` (`user_id`),
            KEY `user_ip` (`user_ip`),
            KEY `created` (`created`)
        ) {$charset_collate};";
        
        // Create hotels table
        $hotels_table = $wpdb->prefix . 'pricewise_hotels';
        $hotels_sql = "CREATE TABLE IF NOT EXISTS `{$hotels_table}` (
            `hotel_id` varchar(50) NOT NULL,
            `entity_id` varchar(50) NOT NULL, 
            `name` varchar(255) NOT NULL,
            `location` varchar(255) NOT NULL,
            `stars` tinyint(1) unsigned DEFAULT NULL,
            `image_url` text DEFAULT NULL,
            `last_updated` datetime NOT NULL,
            PRIMARY KEY (`hotel_id`),
            KEY `entity_id` (`entity_id`),
            KEY `location` (`location`)
        ) {$charset_collate};";
        
        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute SQL
        dbDelta($cache_sql);
        dbDelta($logs_sql);
        dbDelta($hotels_sql);
        
        // Set default plugin options if they don't exist
        if (!get_option('pricewise_rapidapi_key')) {
            add_option('pricewise_rapidapi_key', '');
        }
        
        if (!get_option('pricewise_rapidapi_host')) {
            add_option('pricewise_rapidapi_host', 'sky-scanner3.p.rapidapi.com');
        }
        
        if (!get_option('pricewise_default_location')) {
            add_option('pricewise_default_location', 'Rome');
        }
        
        if (!get_option('pricewise_default_adults')) {
            add_option('pricewise_default_adults', 1);
        }
        
        if (!get_option('pricewise_default_children')) {
            add_option('pricewise_default_children', 0);
        }
        
        if (!get_option('pricewise_default_rooms')) {
            add_option('pricewise_default_rooms', 1);
        }
        
        if (!get_option('pricewise_rate_limit_searches')) {
            add_option('pricewise_rate_limit_searches', 10); // 10 searches per hour by default
        }
        
        if (!get_option('pricewise_cache_expiry')) {
            add_option('pricewise_cache_expiry', 3600); // 1 hour by default
        }
        
        // Flush rewrite rules to ensure any custom endpoints work
        flush_rewrite_rules();
    }
}