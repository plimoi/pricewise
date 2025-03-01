<?php
// includes/rapidapi/rapidapi-hotels-details-step3.php - Step 3 API functions for Hotel Details

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test API Connection for Step 3 - uses sample parameters for hotel details retrieval.
 */
function pricewise_test_api_step3() {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step3      = isset( $options['api_key_step3'] ) ? $options['api_key_step3'] : '';
    $api_host_step3     = isset( $options['api_host_step3'] ) ? $options['api_host_step3'] : '';
    $api_endpoint_step3 = isset( $options['api_endpoint_step3'] ) ? $options['api_endpoint_step3'] : '';

    if ( empty( $api_key_step3 ) || empty( $api_host_step3 ) || empty( $api_endpoint_step3 ) ) {
        return 'Error: Step 3 API configuration not complete';
    }
    // Sample parameter for testing - example hotel ID
    $sample_hotel_id = '123456';

    // Build query arguments for Step 3. NOTE: Use parameter key "id" as required.
    $query_args = [
        'id' => $sample_hotel_id
    ];

    $url  = add_query_arg( $query_args, $api_endpoint_step3 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step3,
            'x-rapidapi-host' => $api_host_step3,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return 'Error: ' . $response->get_error_message();
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );
    if ( $status_code != 200 ) {
        return 'Error: HTTP ' . $status_code . '<br><br>Response Body: <pre>' . esc_html( $body ) . '</pre>';
    }
    return 'Status: 200 OK<br><br>Full Response (Step 3):<br><pre>' . esc_html( $body ) . '</pre>';
}

/**
 * Detailed Test API Connection for Step 3 - provides detailed info and parameters.
 */
function pricewise_test_api_step3_detailed() {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step3      = isset( $options['api_key_step3'] ) ? $options['api_key_step3'] : '';
    $api_host_step3     = isset( $options['api_host_step3'] ) ? $options['api_host_step3'] : '';
    $api_endpoint_step3 = isset( $options['api_endpoint_step3'] ) ? $options['api_endpoint_step3'] : '';

    if ( empty( $api_key_step3 ) || empty( $api_host_step3 ) || empty( $api_endpoint_step3 ) ) {
        return 'Error: Step 3 API configuration not complete';
    }
    // Sample parameter for detailed test - example hotel ID
    $sample_hotel_id = '123456';

    $query_args = [
        'id' => $sample_hotel_id
    ];

    $url  = add_query_arg( $query_args, $api_endpoint_step3 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step3,
            'x-rapidapi-host' => $api_host_step3,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return 'Error: ' . $response->get_error_message();
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = wp_remote_retrieve_body( $response );

    $result  = '<strong>Test Parameters:</strong><br>';
    $result .= 'Hotel ID: ' . $sample_hotel_id . '<br><br>';
    $result .= '<strong>API Endpoint:</strong><br>' . esc_url( $url ) . '<br><br>';

    if ( $status_code != 200 ) {
        $result .= '<strong>Error:</strong> HTTP ' . $status_code . '<br><br>Response Body: <pre>' . esc_html( $body ) . '</pre>';
    } else {
        $result .= '<strong>Status:</strong> 200 OK<br><br><strong>Full Response:</strong><br><pre>' . esc_html( $body ) . '</pre>';
    }
    
    return $result;
}

/**
 * Retrieve hotel details using the Step 3 API endpoint.
 *
 * @param string $hotel_id
 *
 * @return array|WP_Error
 */
function pricewise_get_hotel_details( $hotel_id ) {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step3      = isset( $options['api_key_step3'] ) ? $options['api_key_step3'] : '';
    $api_host_step3     = isset( $options['api_host_step3'] ) ? $options['api_host_step3'] : '';
    $api_endpoint_step3 = isset( $options['api_endpoint_step3'] ) ? $options['api_endpoint_step3'] : '';

    if ( empty( $api_key_step3 ) || empty( $api_host_step3 ) || empty( $api_endpoint_step3 ) ) {
        return new WP_Error( 'api_configuration', 'Step 3 API configuration not complete' );
    }

    // Use parameter key "id" as required by the API.
    $query_args = [
        'id' => $hotel_id
    ];

    $url  = add_query_arg( $query_args, $api_endpoint_step3 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step3,
            'x-rapidapi-host' => $api_host_step3,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code != 200 ) {
        return new WP_Error( 'api_error', 'HTTP ' . $status_code );
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    return $data;
}
?>
