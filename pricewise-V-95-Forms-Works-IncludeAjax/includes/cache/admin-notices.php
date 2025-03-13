<?php
/**
 * Admin Notices for Cache System
 * 
 * Displays admin notices related to the cache system.
 *
 * @package PriceWise
 * @subpackage Cache
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Reset admin notices when plugin is updated
 */
function pricewise_reset_cache_notices() {
    // Get current version
    $current_version = PRICEWISE_VERSION;
    $previous_version = get_option('pricewise_version', '1.0');
    
    // If version changed, update version number
    if (version_compare($current_version, $previous_version, '>')) {
        update_option('pricewise_version', $current_version);
    }
}
add_action('admin_init', 'pricewise_reset_cache_notices');