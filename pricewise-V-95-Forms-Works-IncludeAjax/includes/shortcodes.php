<?php
/**
 * Shortcode Functionality for PriceWise Plugin
 *
 * This file contains shortcodes available in the PriceWise plugin.
 * It provides ways to display API information and cache statistics.
 *
 * @package PriceWise
 * @subpackage Shortcodes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register API demonstration shortcode
 * Usage: [pricewise_api_status]
 * 
 * This shortcode shows basic status information about configured APIs.
 *
 * @since 1.0.0
 * @return string HTML output of the shortcode
 */
function pricewise_api_status_shortcode() {
    // Check if user has permission to see this information
    if (!current_user_can('edit_posts')) {
        return '<p>API status information is only available to authorized users.</p>';
    }
    
    // Get configured APIs
    $apis = array();
    if (function_exists('Pricewise_API_Settings::get_apis')) {
        $apis = Pricewise_API_Settings::get_apis();
    }
    
    // Get cache stats if available
    $cache_stats = array(
        'count' => 0,
        'hit_ratio' => 0
    );
    
    if (function_exists('pricewise_cache_get_stats')) {
        $stats = pricewise_cache_get_stats(true);
        $cache_stats['count'] = isset($stats['total_entries']) ? $stats['total_entries'] : 0;
        $cache_stats['hit_ratio'] = isset($stats['hit_ratio']) ? $stats['hit_ratio'] : 0;
    }
    
    // Build output
    $output = '<div class="pricewise-api-status">';
    $output .= '<h3>PriceWise API Status</h3>';
    
    if (empty($apis)) {
        $output .= '<p>No APIs have been configured yet. Please visit the admin dashboard to set up your APIs.</p>';
    } else {
        $output .= '<p>' . count($apis) . ' API(s) configured | ' . 
                  $cache_stats['count'] . ' cached items | ' . 
                  $cache_stats['hit_ratio'] . ' cache hit ratio</p>';
        
        $output .= '<ul class="pricewise-api-list">';
        foreach ($apis as $api_id => $api) {
            $name = isset($api['name']) ? esc_html($api['name']) : 'Unnamed API';
            $status = (!empty($api['api_key']) && !empty($api['base_endpoint'])) ? 
                      '<span style="color:green;">✓</span>' : 
                      '<span style="color:red;">✗</span>';
            
            $output .= '<li>' . $name . ' ' . $status . '</li>';
        }
        $output .= '</ul>';
    }
    
    $output .= '</div>';
    
    // Add minimal styling
    $output .= '<style>
        .pricewise-api-status {
            padding: 15px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            margin: 15px 0;
        }
        .pricewise-api-status h3 {
            margin-top: 0;
        }
        .pricewise-api-list {
            margin-left: 20px;
        }
    </style>';
    
    /**
     * Filter the API status shortcode output
     *
     * @since 1.0.0
     * @param string $output The HTML output
     * @param array $apis The array of configured APIs
     * @return string Modified HTML output
     */
    return apply_filters('pricewise_api_status_output', $output, $apis);
}
add_shortcode('pricewise_api_status', 'pricewise_api_status_shortcode');