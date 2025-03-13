<?php
// includes/settings.php - Settings functionality for PriceWise plugin

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add general settings for the plugin
 */
function pricewise_add_settings_section() {
    // Register a new section for API general settings
    add_settings_section(
        'pricewise_general_section',
        'General Settings',
        'pricewise_general_section_callback',
        'pricewise-built-in-api-settings'
    );
    
    // Add settings field for cache duration
    add_settings_field(
        'default_cache_duration',
        'Default Cache Duration',
        'pricewise_cache_duration_callback',
        'pricewise-built-in-api-settings',
        'pricewise_general_section'
    );
    
    // Register the settings
    register_setting('pricewise_api_settings_group', 'pricewise_default_cache_duration', 'intval');
}
add_action('admin_init', 'pricewise_add_settings_section');

/**
 * Section description callback
 */
function pricewise_general_section_callback() {
    echo 'Configure general settings for the PriceWise plugin:';
}

/**
 * Callback function for cache duration setting
 */
function pricewise_cache_duration_callback() {
    $duration = get_option('pricewise_default_cache_duration', 3600); // 1 hour default
    
    echo '<input type="number" id="pricewise_default_cache_duration" name="pricewise_default_cache_duration" value="' . esc_attr($duration) . '" min="60" step="60" class="small-text"> seconds';
    echo '<p class="description">Default time to cache API responses (in seconds). Minimum 60 seconds recommended.</p>';
    
    $human_readable = '';
    if ($duration >= 86400) {
        $days = floor($duration / 86400);
        $human_readable .= $days . ' day' . ($days > 1 ? 's' : '') . ' ';
        $duration %= 86400;
    }
    if ($duration >= 3600) {
        $hours = floor($duration / 3600);
        $human_readable .= $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ';
        $duration %= 3600;
    }
    if ($duration >= 60) {
        $minutes = floor($duration / 60);
        $human_readable .= $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ';
        $duration %= 60;
    }
    if ($duration > 0) {
        $human_readable .= $duration . ' second' . ($duration > 1 ? 's' : '');
    }
    
    echo '<p class="description">Current setting: ' . trim($human_readable) . '</p>';
}