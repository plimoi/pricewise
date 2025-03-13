<?php
/**
 * Cache Functions
 * 
 * Simple wrapper functions for the cache system.
 * This file provides a clean API interface to the cache manager class,
 * allowing for simple function calls instead of class method calls.
 *
 * @package PriceWise
 * @subpackage Cache
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Make sure the cache classes are loaded
require_once dirname(__FILE__) . '/class-cache-manager.php';
require_once dirname(__FILE__) . '/class-cache-helper.php';

/**
 * Set up the cache system and ensure the database table exists.
 */
function pricewise_cache_setup() {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->setup_table();
}

/**
 * Get an item from the cache.
 *
 * @param string $key   Cache key.
 * @param string $group Cache group.
 * @return mixed|false  Cached data or false if not found.
 */
function pricewise_cache_get($key, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->get($key, $group);
}

/**
 * Store an item in the cache.
 *
 * @param string $key        Cache key.
 * @param mixed  $data       Data to cache.
 * @param string $group      Cache group.
 * @param int    $expiration Time in seconds until expiration.
 * @return bool              Success or failure.
 */
function pricewise_cache_set($key, $data, $group = 'default', $expiration = 3600) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->set($key, $data, $group, $expiration);
}

/**
 * Delete an item from the cache.
 *
 * @param string $key   Cache key.
 * @param string $group Cache group.
 * @return bool         Success or failure.
 */
function pricewise_cache_delete($key, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->delete($key, $group);
}

/**
 * Delete all items in a cache group.
 *
 * @param string $group Cache group.
 * @return int          Number of items deleted.
 */
function pricewise_cache_delete_group($group) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->delete_group($group);
}

/**
 * Delete cache items by pattern.
 *
 * @param string $pattern Pattern to match in cache keys.
 * @param string $group   Optional. Cache group.
 * @return int            Number of items deleted.
 */
function pricewise_cache_delete_by_pattern($pattern, $group = null) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->delete_by_pattern($pattern, $group);
}

/**
 * Flush the entire cache.
 *
 * @return bool Success or failure.
 */
function pricewise_cache_flush() {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->flush();
}

/**
 * Run cache maintenance tasks.
 *
 * @param int $batch_size Optional. Number of entries to clean up per batch.
 * @param int $max_time   Optional. Maximum execution time in seconds.
 * @return array          Cleanup statistics.
 */
function pricewise_cache_maintenance($batch_size = 1000, $max_time = 30) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->maintenance($batch_size, $max_time);
}

/**
 * Get cache statistics.
 *
 * @param bool $format Whether to format the stats for display.
 * @return array       Cache statistics.
 */
function pricewise_cache_get_stats($format = false) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    $stats = $cache_manager->get_stats();
    
    if ($format) {
        return PriceWise_Cache_Helper::format_stats_for_display($stats);
    }
    
    return $stats;
}

/**
 * Check cache system health.
 *
 * @return array Health status information.
 */
function pricewise_cache_check_health() {
    return PriceWise_Cache_Helper::check_health();
}

/**
 * Generate a consistent cache key for API requests.
 *
 * @param string $api_id   API identifier.
 * @param string $endpoint API endpoint.
 * @param array  $params   Request parameters.
 * @return string          Cache key.
 */
function pricewise_cache_generate_api_key($api_id, $endpoint, $params = array()) {
    return PriceWise_Cache_Helper::generate_api_key($api_id, $endpoint, $params);
}

/**
 * Get cached API response or fetch fresh data.
 *
 * @param string   $api_id     API identifier.
 * @param string   $endpoint   API endpoint.
 * @param array    $params     Request parameters.
 * @param callable $fetch_func Function to fetch fresh data if not in cache.
 * @param int      $expiration Cache expiration time in seconds.
 * @return mixed               API response data.
 */
function pricewise_cache_api_request($api_id, $endpoint, $params, $fetch_func, $expiration = 3600) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->api_request($api_id, $endpoint, $params, $fetch_func, $expiration);
}

/**
 * Determine appropriate cache expiration based on content type
 *
 * @param string $content_type Content type of data (e.g., 'hotel_details', 'price_comparison')
 * @param int    $default      Default expiration time in seconds
 * @return int                 Appropriate expiration time
 */
function pricewise_get_cache_expiration($content_type, $default = 3600) {
    return PriceWise_Cache_Helper::get_expiration($content_type, $default);
}

/**
 * Prefetch multiple cache items in a single operation
 *
 * @param array  $keys   Array of cache keys to fetch
 * @param string $group  Cache group
 * @return array         Associative array of found items
 */
function pricewise_cache_get_multiple($keys, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->get_multiple($keys, $group);
}

/**
 * Set multiple cache items in a single operation
 *
 * @param array  $items      Associative array of key => value pairs to cache
 * @param string $group      Cache group
 * @param int    $expiration Time in seconds until expiration
 * @return bool              Success or failure
 */
function pricewise_cache_set_multiple($items, $group = 'default', $expiration = 3600) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->set_multiple($items, $group, $expiration);
}

/**
 * Get or set cache item (convenience function)
 *
 * @param string   $key        Cache key
 * @param callable $callback   Function to generate value if not in cache
 * @param string   $group      Cache group
 * @param int      $expiration Time in seconds until expiration
 * @return mixed               Cached data or callback result
 */
function pricewise_cache_remember($key, $callback, $group = 'default', $expiration = 3600) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->remember($key, $callback, $group, $expiration);
}

/**
 * Check if an item exists in cache without returning its value
 *
 * @param string $key   Cache key
 * @param string $group Cache group
 * @return bool         Whether item exists in cache
 */
function pricewise_cache_has($key, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->has($key, $group);
}

/**
 * Increment a numeric cache value
 *
 * @param string $key    Cache key
 * @param int    $offset Amount to increment by
 * @param string $group  Cache group
 * @return int|false     New value or false on failure
 */
function pricewise_cache_increment($key, $offset = 1, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->increment($key, $offset, $group);
}

/**
 * Decrement a numeric cache value
 *
 * @param string $key    Cache key
 * @param int    $offset Amount to decrement by
 * @param string $group  Cache group
 * @return int|false     New value or false on failure
 */
function pricewise_cache_decrement($key, $offset = 1, $group = 'default') {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->decrement($key, $offset, $group);
}

/**
 * Touch a cache item to reset its expiration time
 *
 * @param string $key        Cache key
 * @param string $group      Cache group
 * @param int    $expiration New expiration time in seconds
 * @return bool              Success or failure
 */
function pricewise_cache_touch($key, $group = 'default', $expiration = 3600) {
    $cache_manager = PriceWise_Cache_Manager::instance();
    return $cache_manager->touch($key, $group, $expiration);
}