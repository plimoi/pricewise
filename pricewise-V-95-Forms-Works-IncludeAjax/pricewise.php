<?php
/*
Plugin Name: PriceWise
Description: A plugin for managing API connections with robust caching capabilities.
Version: 1.3.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('PRICEWISE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRICEWISE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRICEWISE_VERSION', '1.3.0');

// Include cache system
require_once PRICEWISE_PLUGIN_DIR . 'includes/cache/cache-functions.php';

// Include admin files
require_once PRICEWISE_PLUGIN_DIR . 'admin/admin-dashboard.php';
require_once PRICEWISE_PLUGIN_DIR . 'admin/admin-help.php';
require_once PRICEWISE_PLUGIN_DIR . 'admin/manual-api.php';

// Include core functionality
require_once PRICEWISE_PLUGIN_DIR . 'includes/shortcodes.php';
require_once PRICEWISE_PLUGIN_DIR . 'includes/settings.php';

// Include AJAX functions
require_once PRICEWISE_PLUGIN_DIR . 'includes/ajax-functions.php';

// Include form integration
require_once PRICEWISE_PLUGIN_DIR . 'includes/integrations/form-integration.php';
require_once PRICEWISE_PLUGIN_DIR . 'includes/integrations/form-handlers.php';

// Include template tags
require_once PRICEWISE_PLUGIN_DIR . 'includes/template-tags.php';

// Include API shortcodes
require_once PRICEWISE_PLUGIN_DIR . 'includes/shortcodes/api-data-shortcodes.php';

/**
 * Plugin activation function
 */
function pricewise_activate_plugin() {
    // Set up cache system
    pricewise_cache_setup();
    
    // Set a transient to indicate the plugin was just activated
    set_transient('pricewise_activated', true, 30);
    
    // Schedule maintenance tasks
    if (!wp_next_scheduled('pricewise_daily_maintenance')) {
        wp_schedule_event(time(), 'daily', 'pricewise_daily_maintenance');
    }
    
    // Create default form integration settings
    add_option('pricewise_form_api_configs', array());
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    /**
     * Action that fires after PriceWise is activated
     *
     * @since 1.3.0
     */
    do_action('pricewise_after_activation');
}

/**
 * Plugin deactivation function
 */
function pricewise_deactivate_plugin() {
    // Clear scheduled tasks
    wp_clear_scheduled_hook('pricewise_daily_maintenance');
    
    /**
     * Action that fires before PriceWise is deactivated
     *
     * @since 1.3.0
     */
    do_action('pricewise_before_deactivation');
}

/**
 * Run maintenance tasks
 */
function pricewise_do_maintenance() {
    // Run cache maintenance
    pricewise_cache_maintenance();
    
    /**
     * Action that fires during PriceWise maintenance
     *
     * @since 1.3.0
     */
    do_action('pricewise_maintenance');
}
add_action('pricewise_daily_maintenance', 'pricewise_do_maintenance');

/**
 * Add admin notice after plugin activation
 */
function pricewise_admin_activation_notice() {
    if (get_transient('pricewise_activated')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('PriceWise has been activated. Configure your APIs in the PriceWise admin page.', 'pricewise'); ?></p>
        </div>
        <?php
        delete_transient('pricewise_activated');
    }
}
add_action('admin_notices', 'pricewise_admin_activation_notice');

/**
 * Enqueue frontend scripts and styles
 */
function pricewise_enqueue_frontend_scripts() {
    // Enqueue form integration script
    wp_enqueue_script(
        'pricewise-form-integration',
        PRICEWISE_PLUGIN_URL . 'js/form-integration.js',
        array('jquery'),
        PRICEWISE_VERSION,
        true
    );
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('pricewise-form-integration', 'pricewise_form', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pricewise_form_nonce')
    ));
    
    // Enqueue API shortcodes script
    wp_enqueue_script(
        'pricewise-api-shortcodes',
        PRICEWISE_PLUGIN_URL . 'js/api-shortcodes.js',
        array('jquery'),
        PRICEWISE_VERSION,
        true
    );
    
    // Localize script with AJAX URL and nonce
    wp_localize_script('pricewise-api-shortcodes', 'pricewise_api', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pricewise_api_nonce')
    ));
    
    // Enqueue styles
    wp_enqueue_style(
        'pricewise-api-shortcodes',
        PRICEWISE_PLUGIN_URL . 'style/api-shortcodes.css',
        array(),
        PRICEWISE_VERSION
    );
}
add_action('wp_enqueue_scripts', 'pricewise_enqueue_frontend_scripts');

// Register hooks
register_activation_hook(__FILE__, 'pricewise_activate_plugin');
register_deactivation_hook(__FILE__, 'pricewise_deactivate_plugin');

/**
 * Initialize the plugin
 */
function pricewise_init() {
    // Load text domain for translations
    load_plugin_textdomain('pricewise', false, basename(dirname(__FILE__)) . '/languages');
    
    /**
     * Action that fires when PriceWise is initialized
     *
     * @since 1.3.0
     */
    do_action('pricewise_init');
}
add_action('init', 'pricewise_init');