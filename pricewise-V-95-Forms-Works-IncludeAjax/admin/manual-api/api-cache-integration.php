<?php
/**
 * API Cache Integration
 * 
 * Connects the Manual API functionality with the cache system. This file provides
 * the bridge between API operations and the caching layer, ensuring that API 
 * requests are properly cached based on configuration settings.
 *
 * @package PriceWise
 * @subpackage ManualAPI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Cache API responses for manual API providers
 * 
 * This function intercepts API requests, checks if a cached response exists,
 * and either returns the cached data or makes a fresh API request.
 * 
 * @since 1.0.0
 * @param mixed  $default_result Default (probably false) result from filter
 * @param string $api_id         API identifier
 * @param string $endpoint       API endpoint
 * @param array  $params         Request parameters
 * @param callable $request_func Function to make the actual API request
 * @return mixed                 Response data, either from cache or fresh
 */
function pricewise_cache_api_integration($default_result, $api_id, $endpoint, $params, $request_func) {
    // Get the API configuration to check for endpoint-specific cache duration
    $api_config = Pricewise_API_Settings::get_api($api_id);
    
    // Default expiration (1 hour)
    $expiration = 3600;
    
    // If we have a valid API configuration, get the specific endpoint
    if ($api_config && isset($api_config['endpoints'])) {
        // Find the endpoint configuration by path
        foreach ($api_config['endpoints'] as $ep) {
            if ($ep['path'] === $endpoint && isset($ep['config']['cache_duration'])) {
                $expiration = intval($ep['config']['cache_duration']);
                break;
            }
        }
    } else {
        // If no API config, use default durations based on endpoint type
        switch ($endpoint) {
            case 'destination_search':
                $expiration = 6 * HOUR_IN_SECONDS; // 6 hours for destination data
                break;
            case 'price_comparison':
                $expiration = 30 * MINUTE_IN_SECONDS; // 30 minutes for price data
                break;
            case 'hotel_details':
                $expiration = 2 * HOUR_IN_SECONDS; // 2 hours for hotel details
                break;
        }
    }
    
    /**
     * Filter to customize cache expiration for API requests
     * 
     * Allows developers to modify the cache duration for specific API endpoints
     * based on custom logic.
     *
     * @since 1.0.0
     * @param int $expiration The cache expiration time in seconds
     * @param string $api_id The API identifier
     * @param string $endpoint The API endpoint
     * @param array $params The request parameters
     * @return int Modified expiration time in seconds
     */
    $expiration = apply_filters('pricewise_api_cache_expiration', $expiration, $api_id, $endpoint, $params);
    
    // Use the cache API request function
    return pricewise_cache_api_request($api_id, $endpoint, $params, $request_func, $expiration);
}

/**
 * Helper function to clear cache for a specific API
 * 
 * This function allows targeted clearing of cache data for a specific API
 * or even a specific endpoint within an API.
 * 
 * @since 1.0.0
 * @param string $api_id   API identifier
 * @param string $endpoint Optional specific endpoint to clear, or null for all
 * @return int             Number of cache entries cleared
 */
function pricewise_clear_api_cache($api_id, $endpoint = null) {
    if ($endpoint) {
        // Clear specific endpoint cache
        $group = "api_{$api_id}_{$endpoint}";
    } else {
        // Clear all API cache
        $group = "api_{$api_id}";
    }
    
    return pricewise_cache_delete_group($group);
}

/**
 * Display cache info on API admin page
 * 
 * Shows statistics about cached API data on the admin interface.
 * This is automatically hooked to the 'pricewise_after_api_list' action.
 *
 * @since 1.0.0
 * @return void
 */
function pricewise_display_api_cache_info() {
    $stats = pricewise_cache_get_stats(true);
    
    // Look for API-specific cache groups
    $api_groups = array();
    if (!empty($stats['groups'])) {
        foreach ($stats['groups'] as $group => $count) {
            if (strpos($group, 'api_') === 0) {
                $api_groups[$group] = $count;
            }
        }
    }
    
    if (empty($api_groups)) {
        return;
    }
    
    /**
     * Action that fires before displaying API cache information
     * 
     * Allows developers to add custom content before the API cache info display
     *
     * @since 1.0.0
     */
    do_action('pricewise_before_api_cache_info');
    
    echo '<div class="card" style="margin-top: 20px;">';
    echo '<h2>API Cache Statistics</h2>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>API Group</th><th>Cached Entries</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($api_groups as $group => $count) {
        echo '<tr>';
        echo '<td>' . esc_html($group) . '</td>';
        echo '<td>' . esc_html(number_format($count)) . '</td>';
        echo '<td>';
        echo '<form method="post" action="" style="display: inline;">';
        wp_nonce_field('pricewise_cache_management');
        echo '<input type="hidden" name="pricewise_cache_action" value="delete_group">';
        echo '<input type="hidden" name="cache_group" value="' . esc_attr($group) . '">';
        echo '<input type="submit" class="button button-small" value="Clear Cache" onclick="return confirm(\'Are you sure you want to clear this cache?\');">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    /**
     * Action that fires after displaying API cache statistics table
     * 
     * Allows developers to add custom content after the API cache statistics table
     *
     * @since 1.0.0
     * @param array $api_groups The array of API cache groups and their counts
     */
    do_action('pricewise_after_api_cache_stats', $api_groups);
    
    echo '</div>';
}
add_action('pricewise_after_api_list', 'pricewise_display_api_cache_info');