<?php
/**
 * Cache Helper Class
 * 
 * Helper functions and utilities for the cache system.
 *
 * @package PriceWise
 * @subpackage Cache
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PriceWise_Cache_Helper {
    /**
     * Generate a standardized cache key from parameters.
     *
     * @param array  $params Parameters to include in the key.
     * @param string $prefix Optional prefix for the key.
     * @return string        Generated cache key.
     */
    public static function generate_key($params, $prefix = '') {
        // Sort keys to ensure consistent order
        if (is_array($params)) {
            ksort($params);
            $key_string = json_encode($params);
        } else {
            $key_string = strval($params);
        }
        
        // Add prefix if provided
        if (!empty($prefix)) {
            $key_string = $prefix . '_' . $key_string;
        }
        
        return $key_string;
    }
    
    /**
     * Generate a cache key for API requests.
     *
     * @param string $api_id   API identifier.
     * @param string $endpoint API endpoint.
     * @param array  $params   Request parameters.
     * @return string          Cache key.
     */
    public static function generate_api_key($api_id, $endpoint, $params = array()) {
        // Generate a unique representation of the API request
        $key_parts = array(
            'api' => $api_id,
            'endpoint' => $endpoint,
            'params' => $params
        );
        
        return self::generate_key($key_parts, 'api');
    }
    
    /**
     * Determine appropriate cache expiration for different content types.
     *
     * @param string $content_type Type of content being cached.
     * @return int                 Expiration time in seconds.
     */
    public static function get_expiration($content_type) {
        $expirations = array(
            'api_response' => HOUR_IN_SECONDS,
            'price_comparison' => 30 * MINUTE_IN_SECONDS,
            'destination_search' => 6 * HOUR_IN_SECONDS,
            'hotel_search' => HOUR_IN_SECONDS,
            'hotel_details' => 2 * HOUR_IN_SECONDS,
            'default' => HOUR_IN_SECONDS
        );
        
        return isset($expirations[$content_type]) ? $expirations[$content_type] : $expirations['default'];
    }
    
    /**
     * Determine if cache should be bypassed based on request parameters.
     *
     * @param array $params Request parameters.
     * @return bool         Whether to bypass cache.
     */
    public static function should_bypass_cache($params) {
        // Skip cache for admin or logged-in users if configured
        if (is_admin() && apply_filters('pricewise_bypass_cache_admin', false)) {
            return true;
        }
        
        // Skip cache if explicitly requested
        if (isset($params['nocache']) && $params['nocache']) {
            return true;
        }
        
        // Skip cache if it's a test or debug request
        if (isset($params['test_mode']) && $params['test_mode']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Format cache data for display in admin.
     *
     * @param array $stats Cache statistics.
     * @return array       Formatted statistics.
     */
    public static function format_stats_for_display($stats) {
        $formatted = array();
        
        // Format timestamps
        foreach (array('last_updated', 'last_cleanup', 'last_prune', 'last_flush') as $timestamp_key) {
            if (isset($stats[$timestamp_key])) {
                $formatted[$timestamp_key] = date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'), 
                    $stats[$timestamp_key]
                );
            }
        }
        
        // Format ratios and percentages
        if (isset($stats['hit_ratio'])) {
            $formatted['hit_ratio'] = number_format($stats['hit_ratio'], 2) . '%';
        }
        
        // Format counts
        foreach (array('hits', 'misses', 'writes', 'total_entries') as $count_key) {
            if (isset($stats[$count_key])) {
                $formatted[$count_key] = number_format($stats[$count_key]);
            }
        }
        
        return array_merge($stats, $formatted);
    }
    
    /**
     * Check if the cache system is healthy.
     *
     * @return array Status information.
     */
    public static function check_health() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pricewise_cache';
        
        $status = array(
            'status' => 'healthy',
            'messages' => array(),
            'table_exists' => false,
            'object_cache' => wp_using_ext_object_cache()
        );
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $status['table_exists'] = $table_exists;
        
        if (!$table_exists) {
            $status['status'] = 'error';
            $status['messages'][] = 'Cache table does not exist.';
        } else {
            // Check table structure
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            $required_columns = array('id', 'cache_key', 'cache_value', 'expiration', 'created_at');
            $missing_columns = array_diff($required_columns, $column_names);
            
            if (!empty($missing_columns)) {
                $status['status'] = 'warning';
                $status['messages'][] = 'Cache table is missing columns: ' . implode(', ', $missing_columns);
            }
            
            // Check for indexes
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
            $index_names = array();
            foreach ($indexes as $index) {
                $index_names[] = $index->Key_name;
            }
            
            $required_indexes = array('PRIMARY', 'cache_key', 'expiration');
            $missing_indexes = array_diff($required_indexes, $index_names);
            
            if (!empty($missing_indexes)) {
                $status['status'] = 'warning';
                $status['messages'][] = 'Cache table is missing indexes: ' . implode(', ', $missing_indexes);
            }
            
            // Check for expired entries
            $current_time = current_time('mysql');
            $expired_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE expiration < %s",
                    $current_time
                )
            );
            
            if ($expired_count > 1000) {
                $status['status'] = 'warning';
                $status['messages'][] = 'Cache has ' . number_format($expired_count) . ' expired entries.';
            }
            
            // Check table size
            $total_entries = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $status['total_entries'] = $total_entries;
            
            if ($total_entries > 50000) {
                $status['status'] = 'warning';
                $status['messages'][] = 'Cache table is very large with ' . number_format($total_entries) . ' entries.';
            }
        }
        
        // Check object cache
        if (!$status['object_cache']) {
            $status['messages'][] = 'WordPress object cache is not being used. Consider installing a persistent object cache plugin for better performance.';
        }
        
        return $status;
    }
}