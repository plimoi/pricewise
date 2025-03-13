<?php
/**
 * Main entry point for Manual API functionality
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define the directory path
if ( ! defined( 'PRICEWISE_MANUAL_API_DIR' ) ) {
    define( 'PRICEWISE_MANUAL_API_DIR', plugin_dir_path( __FILE__ ) );
}

// Include the class files
require_once PRICEWISE_MANUAL_API_DIR . 'class-api-settings.php';
require_once PRICEWISE_MANUAL_API_DIR . 'class-api-admin-page.php';
require_once PRICEWISE_MANUAL_API_DIR . 'class-api-tester.php';

// Include the API cache integration
require_once PRICEWISE_MANUAL_API_DIR . 'api-cache-integration.php';

/**
 * Initialize the Manual API functionality
 */
function pricewise_init_manual_api() {
    // Initialize settings
    $api_settings = new Pricewise_API_Settings();
    $api_settings->init();
    
    // Initialize admin page
    $api_admin_page = new Pricewise_API_Admin_Page();
    $api_admin_page->init();
    
    // Initialize test history
    if (is_admin()) {
        $test_history_path = PRICEWISE_MANUAL_API_DIR . 'test-history/class-test-history-admin.php';
        if (file_exists($test_history_path)) {
            require_once $test_history_path;
            new Pricewise_Test_History_Admin();
        }
    }
}
add_action( 'init', 'pricewise_init_manual_api' );

/**
 * Set up test history table when plugin is activated
 */
function pricewise_setup_test_history_table() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    $test_history_db_path = PRICEWISE_MANUAL_API_DIR . 'test-history/class-test-history-db.php';
    if (file_exists($test_history_db_path)) {
        require_once $test_history_db_path;
        $db = new Pricewise_Test_History_DB();
        $db->create_table();
    }
}
add_action('pricewise_activate_plugin', 'pricewise_setup_test_history_table');

/**
 * Add Manual API submenu under PriceWise menu
 */
function pricewise_add_manual_api_menu() {
    add_submenu_page(
        'pricewise',                // Parent slug
        'Manual API Configuration',  // Page title
        'Manual API',                // Menu title
        'manage_options',            // Capability
        'pricewise-manual-api',      // Menu slug
        'pricewise_display_manual_api_page' // Callback function
    );
}
add_action( 'admin_menu', 'pricewise_add_manual_api_menu', 12 ); // Priority 12 to make it appear after other menu items

/**
 * Display Manual API admin page
 */
function pricewise_display_manual_api_page() {
    do_action('pricewise_display_api_admin_page');
    
    // For backward compatibility - if nothing handles the action
    if (!did_action('pricewise_display_api_admin_page')) {
        $api_admin_page = new Pricewise_API_Admin_Page();
        $api_admin_page->display_page();
    }
}

/**
 * Enqueue jQuery UI Sortable for drag-and-drop functionality
 */
function pricewise_admin_enqueue_scripts($hook) {
    // Only load on our plugin's page
    if (strpos($hook, 'pricewise-manual-api') !== false) {
        wp_enqueue_script('jquery-ui-sortable');
    }
}
add_action('admin_enqueue_scripts', 'pricewise_admin_enqueue_scripts');

/**
 * Clear cache when API settings are updated
 * 
 * @param array $old_settings Old API settings
 * @param array $new_settings New API settings
 */
function pricewise_clear_cache_on_api_update($old_settings, $new_settings) {
    // Clear all API cache
    if (function_exists('pricewise_cache_delete_group')) {
        pricewise_cache_delete_group('api');
    }
}
add_action('pricewise_api_settings_updated', 'pricewise_clear_cache_on_api_update', 10, 2);

/**
 * Add hooks for displaying extra content on API admin pages
 */
function pricewise_register_api_admin_hooks() {
    // Hook to display after API list
    do_action('pricewise_after_api_list');
    
    // Hook to display after API form
    do_action('pricewise_after_api_form');
}
add_action('pricewise_api_admin_after_content', 'pricewise_register_api_admin_hooks');