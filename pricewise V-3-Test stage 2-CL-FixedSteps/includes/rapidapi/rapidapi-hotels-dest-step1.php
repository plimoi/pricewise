<?php
// includes/rapidapi/rapidapi-hotels-dest-step1.php - Step 1 API functions for Destination Auto-Complete

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Basic Test API Connection for Step 1
 */
function pricewise_test_api() {
    $options     = get_option( 'pricewise_api_settings' );
    $api_key     = isset( $options['api_key'] ) ? $options['api_key'] : '';
    $api_host    = isset( $options['api_host'] ) ? $options['api_host'] : '';
    $api_endpoint= isset( $options['api_endpoint'] ) ? $options['api_endpoint'] : '';

    if ( empty( $api_key ) || empty( $api_host ) || empty( $api_endpoint ) ) {
        return 'Error: API configuration not complete';
    }
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key,
            'x-rapidapi-host' => $api_host,
        ],
        'timeout' => 15,
    ];
    $response = wp_remote_get( $api_endpoint, $args );
    if ( is_wp_error( $response ) ) {
        return 'Error: ' . $response->get_error_message();
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    return ( $status_code == 200 ) ? 'Ok' : 'Error: HTTP ' . $status_code;
}

/**
 * Detailed Test API Connection for Step 1 - tries a sample auto-complete query
 */
function pricewise_test_api_detailed() {
    $options     = get_option( 'pricewise_api_settings' );
    $api_key     = isset( $options['api_key'] ) ? $options['api_key'] : '';
    $api_host    = isset( $options['api_host'] ) ? $options['api_host'] : '';
    $api_endpoint= isset( $options['api_endpoint'] ) ? $options['api_endpoint'] : '';

    if ( empty( $api_key ) || empty( $api_host ) || empty( $api_endpoint ) ) {
        return 'Error: API configuration not complete';
    }
    $test_query = 'Rome';
    $url = add_query_arg( [ 'query' => $test_query ], $api_endpoint );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key,
            'x-rapidapi-host' => $api_host,
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
    return 'Status: 200 OK<br><br>Full Response:<br><pre>' . esc_html( $body ) . '</pre>';
}

/**
 * Search destinations using the Step 1 API endpoint.
 */
function pricewise_search_destinations( $query ) {
    $options      = get_option( 'pricewise_api_settings' );
    $api_key      = isset( $options['api_key'] ) ? $options['api_key'] : '';
    $api_host     = isset( $options['api_host'] ) ? $options['api_host'] : '';
    $api_endpoint = isset( $options['api_endpoint'] ) ? $options['api_endpoint'] : '';

    if ( empty( $api_key ) || empty( $api_host ) || empty( $api_endpoint ) ) {
        return new WP_Error( 'api_configuration', 'API configuration not complete' );
    }
    $url  = add_query_arg( [ 'query' => $query ], $api_endpoint );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key,
            'x-rapidapi-host' => $api_host,
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

/**
 * AJAX handler for searching destinations.
 */
function pricewise_ajax_search_destination() {
    $query = isset( $_GET['query'] ) ? sanitize_text_field( $_GET['query'] ) : '';
    if ( empty( $query ) ) {
        wp_send_json_error( 'No query provided' );
    }
    $result = pricewise_search_destinations( $query );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    } else {
        $suggestions = array();
        if ( ! empty( $result['data'] ) && is_array( $result['data'] ) ) {
            foreach ( $result['data'] as $place ) {
                $suggestions[] = array(
                    'place_id'   => isset( $place['entityId'] ) ? $place['entityId'] : '',
                    'place_name' => isset( $place['entityName'] ) ? $place['entityName'] : '',
                );
            }
        }
        wp_send_json_success( $suggestions );
    }
}
add_action( 'wp_ajax_pricewise_search_destination', 'pricewise_ajax_search_destination' );
add_action( 'wp_ajax_nopriv_pricewise_search_destination', 'pricewise_ajax_search_destination' );
