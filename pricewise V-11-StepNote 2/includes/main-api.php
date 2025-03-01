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
            <input type="submit" name="pricewise_test_api_step2" value="Test Basic" class="button button-primary" />
            <input type="submit" name="pricewise_test_api_step2_detailed" value="Test Detailed" class="button" />
        </form>
        <?php
        if ( isset( $_POST['pricewise_test_api_step2'] ) ) {
            $step2_result = pricewise_test_api_step2();
            echo '<div style="margin-top: 10px; padding: 10px; background: #e1f7d5;">' . wp_kses_post( $step2_result ) . '</div>';
        }
        if ( isset( $_POST['pricewise_test_api_step2_detailed'] ) ) {
            $step2_detailed_result = pricewise_test_api_step2_detailed();
            echo '<div style="margin-top: 10px; padding: 10px; background: #e1f7d5;">' . wp_kses_post( $step2_detailed_result ) . '</div>';
        }
        ?>

        <hr>
        <h2>Test Step 3 API (Hotel Details)</h2>
        <form method="post">
            <input type="submit" name="pricewise_test_api_step3" value="Test Basic" class="button button-primary" />
            <input type="submit" name="pricewise_test_api_step3_detailed" value="Test Detailed" class="button" />
        </form>
        <?php
        if ( isset( $_POST['pricewise_test_api_step3'] ) ) {
            $step3_result = pricewise_test_api_step3();
            echo '<div style="margin-top: 10px; padding: 10px; background: #d1e7dd;">' . wp_kses_post( $step3_result ) . '</div>';
        }
        if ( isset( $_POST['pricewise_test_api_step3_detailed'] ) ) {
            $step3_detailed_result = pricewise_test_api_step3_detailed();
            echo '<div style="margin-top: 10px; padding: 10px; background: #d1e7dd;">' . wp_kses_post( $step3_detailed_result ) . '</div>';
        }
        ?>

        <hr>
        <h2>Test Step 4 API (Hotel Prices Search)</h2>
        <form method="post">
            <input type="submit" name="pricewise_test_api_step4" value="Test Basic" class="button button-primary" />
            <input type="submit" name="pricewise_test_api_step4_detailed" value="Test Detailed" class="button" />
        </form>
        <?php
        if ( isset( $_POST['pricewise_test_api_step4'] ) ) {
            $step4_result = pricewise_test_api_step4();
            echo '<div style="margin-top: 10px; padding: 10px; background: #d1e7dd;">' . wp_kses_post( $step4_result ) . '</div>';
        }
        if ( isset( $_POST['pricewise_test_api_step4_detailed'] ) ) {
            $step4_detailed_result = pricewise_test_api_step4_detailed();
            echo '<div style="margin-top: 10px; padding: 10px; background: #d1e7dd;">' . wp_kses_post( $step4_detailed_result ) . '</div>';
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
        'pricewise_api_step2_section',
        'Step 2 API Configuration',
        'pricewise_api_step2_section_callback',
        'pricewise-api-settings'
    );
    add_settings_field(
        'api_key_step2',
        'RapidAPI Key (Step 2)',
        'pricewise_api_key_step2_callback',
        'pricewise-api-settings',
        'pricewise_api_step2_section'
    );
    add_settings_field(
        'api_host_step2',
        'RapidAPI Host (Step 2)',
        'pricewise_api_host_step2_callback',
        'pricewise-api-settings',
        'pricewise_api_step2_section'
    );
    add_settings_field(
        'api_endpoint_step2',
        'API Endpoint (Step 2)',
        'pricewise_api_endpoint_step2_callback',
        'pricewise-api-settings',
        'pricewise_api_step2_section'
    );

    // Step 3 API Configuration (Hotel Details)
    add_settings_section(
        'pricewise_api_step3_section',
        'Step 3 API Configuration',
        'pricewise_api_step3_section_callback',
        'pricewise-api-settings'
    );
    add_settings_field(
        'api_key_step3',
        'RapidAPI Key (Step 3)',
        'pricewise_api_key_step3_callback',
        'pricewise-api-settings',
        'pricewise_api_step3_section'
    );
    add_settings_field(
        'api_host_step3',
        'RapidAPI Host (Step 3)',
        'pricewise_api_host_step3_callback',
        'pricewise-api-settings',
        'pricewise_api_step3_section'
    );
    add_settings_field(
        'api_endpoint_step3',
        'API Endpoint (Step 3)',
        'pricewise_api_endpoint_step3_callback',
        'pricewise-api-settings',
        'pricewise_api_step3_section'
    );

    // Step 4 API Configuration (Hotel Prices Search)
    add_settings_section(
        'pricewise_api_step4_section',
        'Step 4 API Configuration',
        'pricewise_api_step4_section_callback',
        'pricewise-api-settings'
    );
    add_settings_field(
        'api_key_step4',
        'RapidAPI Key (Step 4)',
        'pricewise_api_key_step4_callback',
        'pricewise-api-settings',
        'pricewise_api_step4_section'
    );
    add_settings_field(
        'api_host_step4',
        'RapidAPI Host (Step 4)',
        'pricewise_api_host_step4_callback',
        'pricewise-api-settings',
        'pricewise_api_step4_section'
    );
    add_settings_field(
        'api_endpoint_step4',
        'API Endpoint (Step 4)',
        'pricewise_api_endpoint_step4_callback',
        'pricewise-api-settings',
        'pricewise_api_step4_section'
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

function pricewise_api_step2_section_callback() {
    echo 'Enter your RapidAPI configuration details for Step 2 (Hotel Search by Destination) below:';
}
function pricewise_api_key_step2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_key = isset( $options['api_key_step2'] ) ? esc_attr( $options['api_key_step2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_key_step2]' value='{$api_key}' size='50' />";
}
function pricewise_api_host_step2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_host = isset( $options['api_host_step2'] ) ? esc_attr( $options['api_host_step2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_host_step2]' value='{$api_host}' size='50' />";
}
function pricewise_api_endpoint_step2_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_endpoint = isset( $options['api_endpoint_step2'] ) ? esc_attr( $options['api_endpoint_step2'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_endpoint_step2]' value='{$api_endpoint}' size='50' />";
}

function pricewise_api_step3_section_callback() {
    echo 'Enter your RapidAPI configuration details for Step 3 (Hotel Details) below:';
}
function pricewise_api_key_step3_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_key = isset( $options['api_key_step3'] ) ? esc_attr( $options['api_key_step3'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_key_step3]' value='{$api_key}' size='50' />";
}
function pricewise_api_host_step3_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_host = isset( $options['api_host_step3'] ) ? esc_attr( $options['api_host_step3'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_host_step3]' value='{$api_host}' size='50' />";
}
function pricewise_api_endpoint_step3_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_endpoint = isset( $options['api_endpoint_step3'] ) ? esc_attr( $options['api_endpoint_step3'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_endpoint_step3]' value='{$api_endpoint}' size='50' />";
}

function pricewise_api_step4_section_callback() {
    echo 'Enter your RapidAPI configuration details for Step 4 (Hotel Prices Search) below:';
}
function pricewise_api_key_step4_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_key = isset( $options['api_key_step4'] ) ? esc_attr( $options['api_key_step4'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_key_step4]' value='{$api_key}' size='50' />";
}
function pricewise_api_host_step4_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_host = isset( $options['api_host_step4'] ) ? esc_attr( $options['api_host_step4'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_host_step4]' value='{$api_host}' size='50' />";
}
function pricewise_api_endpoint_step4_callback() {
    $options = get_option( 'pricewise_api_settings' );
    $api_endpoint = isset( $options['api_endpoint_step4'] ) ? esc_attr( $options['api_endpoint_step4'] ) : '';
    echo "<input type='text' name='pricewise_api_settings[api_endpoint_step4]' value='{$api_endpoint}' size='50' />";
}

/* 
 * Include RapidAPI endpoint functions from separate files.
 */
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-dest-step1.php';
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-deta-step2.php';
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-details-step3.php';
require_once plugin_dir_path( __FILE__ ) . 'rapidapi/rapidapi-hotels-sear-step4.php';

/* 
 * Include Search Logs functionality.
 */
require_once plugin_dir_path( __FILE__ ) . 'search-logs.php';
?>
