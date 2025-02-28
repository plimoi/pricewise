<?php
// includes/main-api.php - Admin settings and RapidAPI endpoints loader for PriceWise

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add PriceWise menu with API Settings submenu
 */
function pricewise_add_admin_menu() {
    add_menu_page(
        'PriceWise',                // Page title
        'PriceWise',                // Menu title
        'manage_options',           // Capability
        'pricewise',                // Menu slug
        '',                         // Callback function (main menu not displaying a page directly)
        'dashicons-admin-generic',  // Icon
        25                          // Position
    );

    add_submenu_page(
        'pricewise',                // Parent slug
        'Settings API',             // Page title
        'Settings API',             // Menu title
        'manage_options',           // Capability
        'pricewise-api-settings',   // Menu slug
        'pricewise_api_settings_page' // Callback function
    );
}
add_action( 'admin_menu', 'pricewise_add_admin_menu' );

/**
 * Display the API settings page
 */
function pricewise_api_settings_page() {
    ?>
    <div class="wrap">
        <h1>PriceWise API Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'pricewise_api_settings_group' );
                do_settings_sections( 'pricewise-api-settings' );
                submit_button();
            ?>
        </form>

        <h2>Test Step 1 API (Destination Auto-Complete)</h2>
        <form method="post">
            <input type="submit" name="pricewise_test_api" value="Test Basic" class="button button-primary" />
            <input type="submit" name="pricewise_test_api_detailed" value="Test Detailed" class="button" />
        </form>
        <?php
        if ( isset( $_POST['pricewise_test_api'] ) ) {
            $test_result = pricewise_test_api();
            echo '<div style="margin-top: 10px; padding: 10px; background: #f1f1f1;">' . esc_html( $test_result ) . '</div>';
        }
        if ( isset( $_POST['pricewise_test_api_detailed'] ) ) {
            $detailed_result = pricewise_test_api_detailed();
            echo '<div style="margin-top: 10px; padding: 10px; background: #f9f9f9;">' . wp_kses_post( $detailed_result ) . '</div>';
        }
        ?>

        <hr>
        <h2>Test Step 2 API (Hotel Search by Destination)</h2>
        <form method="post">
            <input type="submit" name="pricewise_test_api_stage2" value="Test Basic" class="button button-primary" />
            <input type="submit" name="pricewise_test_api_stage2_detailed" value="Test Detailed" class="button" />
        </form>
        <?php
        if ( isset( $_POST['pricewise_test_api_stage2'] ) ) {
            $stage2_result = pricewise_test_api_stage2();
            echo '<div style="margin-top: 10px; padding: 10px; background: #e1f7d5;">' . wp_kses_post( $stage2_result ) . '</div>';
        }
        if ( isset( $_POST['pricewise_test_api_stage2_detailed'] ) ) {
            $stage2_detailed_result = pricewise_test_api_stage2_detailed();
            echo '<div style="margin-top: 10px; padding: 10px; background: #e1f7d5;">' . wp_kses_post( $stage2_detailed_result ) . '</div>';
        }
        ?>
    </div>
    <?php
}

/**
 * Register API settings fields using the WordPress Settings API
 */
function pricewise_register_api_settings() {
    register_setting( 'pricewise_api_settings_group', 'pricewise_api_settings' );

    // Step 1 API Configuration (Destination Auto-Complete)
    add_settings_section(
        'pricewise_api_settings_section',
        'Step 1 API Configuration',
        'pricewise_api_settings_section_callback',
        'pricewise-api-settings'
    );
    add_settings_field(
        'api_key',
        'RapidAPI Key (Step 1)',
        'pricewise_api_key_callback',
        'pricewise-api-settings',
        'pricewise_api_settings_section'
    );
    add_settings_field(
        'api_host',
        'RapidAPI Host (Step 1)',
        'pricewise_api_host_callback',
        'pricewise-api-settings',
        'pricewise_api_settings_section'
    );
    add_settings_field(
        'api_endpoint',
        'API Endpoint (Step 1)',
        'pricewise_api_endpoint_callback',
        'pricewise-api-settings',
        'pricewise_api_settings_section'
    );

    // Step 2 API Configuration (Hotel Search by Destination)
    add_settings_section(
        'pricewise_api_stage2_section',
        'Step 2 API Configuration',
        'pricewise_api_stage2_section_callback',
        'pricewise-api-settings'
    );
    add_settings_field(
        'api_key_stage2',
        'RapidAPI Key (Step 2)',
        'pricewise_api_key_stage2_callback',
        'pricewise-api-settings',
        'pricewise_api_stage2_section'
    );
    add_settings_field(
        'api_host_stage2',
        'RapidAPI Host (Step 2)',
        'pricewise_api_host_stage2_callback',
        'pricewise-api-settings',
        'pricewise_api_stage2_section'
    );
    add_settings_field(
        'api_endpoint_stage2',
        'API Endpoint (Step 2)',
        'pricewise_api_endpoint_stage2_callback',
        'pricewise-api-settings',
        'pricewise_api_stage2_section'
    );
}
add_action( 'admin_init', 'pricewise_register_api_settings' );

function pricewise_api_settings_section_callback() {
    echo 'Enter your RapidAPI configuration details for Step 1 (Destination Auto-Complete) below:';
}
function pricewise_api_key_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_key = isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_key]' value='{$api_key}' size='50' />";
}
function pricewise_api_host_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_host = isset( $options['api_host'] ) ? esc_attr( $options['api_host'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_host]' value='{$api_host}' size='50' />";
}
function pricewise_api_endpoint_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_endpoint = isset( $options['api_endpoint'] ) ? esc_attr( $options['api_endpoint'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_endpoint]' value='{$api_endpoint}' size='50' />";
}

function pricewise_api_stage2_section_callback() {
    echo 'Enter your RapidAPI configuration details for Step 2 (Hotel Search by Destination) below:';
}
function pricewise_api_key_stage2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_key = isset( $options['api_key_stage2'] ) ? esc_attr( $options['api_key_stage2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_key_stage2]' value='{$api_key}' size='50' />";
}
function pricewise_api_host_stage2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_host = isset( $options['api_host_stage2'] ) ? esc_attr( $options['api_host_stage2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_host_stage2]' value='{$api_host}' size='50' />";
}
function pricewise_api_endpoint_stage2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_endpoint = isset( $options['api_endpoint_stage2'] ) ? esc_attr( $options['api_endpoint_stage2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_endpoint_stage2]' value='{$api_endpoint}' size='50' />";
}

/* 
 * Include RapidAPI endpoint functions from separate files.
 */
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-dest-step1.php';
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-deta-step2.php';
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-sear-step3.php';
