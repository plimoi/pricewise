<?php
// includes/rapidapi/rapidapi-hotels-deta-2nd.php - Stage 2 API functions for Hotel Search by Destination

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test API Connection for Stage 2 - uses a sample destination ID.
 * For testing purposes, we use a sample destination ID (e.g., "27539793").
 */
function pricewise_test_api_stage2() {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_stage2  = isset( $options['api_key_stage2'] ) ? $options['api_key_stage2'] : '';
    $api_host_stage2 = isset( $options['api_host_stage2'] ) ? $options['api_host_stage2'] : '';
    $api_endpoint_stage2 = isset( $options['api_endpoint_stage2'] ) ? $options['api_endpoint_stage2'] : '';

    if ( empty( $api_key_stage2 ) || empty( $api_host_stage2 ) || empty( $api_endpoint_stage2 ) ) {
        return 'Error: Stage 2 API configuration not complete';
    }
    // Sample destination ID (should be replaced by the actual selected destination in real usage)
    $sample_destination = '27539793';
    $url = add_query_arg( [ 'destinationId' => $sample_destination ], $api_endpoint_stage2 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_stage2,
            'x-rapidapi-host' => $api_host_stage2,
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
    return 'Status: 200 OK<br><br>Full Response (Stage 2):<br><pre>' . esc_html( $body ) . '</pre>';
}

/**
 * Detailed Test API Connection for Stage 2 - uses sample parameters for a more complete test
 */
function pricewise_test_api_stage2_detailed() {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_stage2  = isset( $options['api_key_stage2'] ) ? $options['api_key_stage2'] : '';
    $api_host_stage2 = isset( $options['api_host_stage2'] ) ? $options['api_host_stage2'] : '';
    $api_endpoint_stage2 = isset( $options['api_endpoint_stage2'] ) ? $options['api_endpoint_stage2'] : '';

    if ( empty( $api_key_stage2 ) || empty( $api_host_stage2 ) || empty( $api_endpoint_stage2 ) ) {
        return 'Error: Stage 2 API configuration not complete';
    }

    // Sample parameters for a more complete test
    $sample_destination = '27539793'; // Sample destination ID (Rome)
    $sample_checkin = date('Y-m-d', strtotime('+7 days'));
    $sample_checkout = date('Y-m-d', strtotime('+10 days'));
    $sample_adults = 2;
    $sample_rooms = 1;

    // Build query arguments
    $query_args = [
        'entityId' => $sample_destination,
        'checkin' => $sample_checkin,
        'checkout' => $sample_checkout,
        'adults' => $sample_adults,
        'rooms' => $sample_rooms
    ];

    $url = add_query_arg($query_args, $api_endpoint_stage2);
    
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_stage2,
            'x-rapidapi-host' => $api_host_stage2,
        ],
        'timeout' => 30,
    ];
    
    $response = wp_remote_get( $url, $args );
    
    if ( is_wp_error( $response ) ) {
        return 'Error: ' . $response->get_error_message();
    }
    
    $status_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    
    // Create detailed response with request info
    $result = '<strong>Test Parameters:</strong><br>';
    $result .= 'Destination ID: ' . $sample_destination . '<br>';
    $result .= 'Check-in: ' . $sample_checkin . '<br>';
    $result .= 'Check-out: ' . $sample_checkout . '<br>';
    $result .= 'Adults: ' . $sample_adults . '<br>';
    $result .= 'Rooms: ' . $sample_rooms . '<br><br>';
    
    $result .= '<strong>API Endpoint:</strong><br>';
    $result .= esc_url($url) . '<br><br>';
    
    if ( $status_code != 200 ) {
        $result .= '<strong>Error:</strong> HTTP ' . $status_code . '<br><br>Response Body: <pre>' . esc_html( $body ) . '</pre>';
    } else {
        $result .= '<strong>Status:</strong> 200 OK<br><br><strong>Full Response:</strong><br><pre>' . esc_html( $body ) . '</pre>';
    }
    
    return $result;
}

/**
 * Search hotels using the Stage 2 API endpoint.
 */
function pricewise_search_hotels( $destination_id, $checkin, $checkout, $adults, $rooms, $children = 0 ) {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_stage2  = isset( $options['api_key_stage2'] ) ? $options['api_key_stage2'] : '';
    $api_host_stage2 = isset( $options['api_host_stage2'] ) ? $options['api_host_stage2'] : '';
    $api_endpoint_stage2 = isset( $options['api_endpoint_stage2'] ) ? $options['api_endpoint_stage2'] : '';

    if ( empty( $api_key_stage2 ) || empty( $api_host_stage2 ) || empty( $api_endpoint_stage2 ) ) {
        return new WP_Error( 'api_configuration', 'Stage 2 API configuration not complete' );
    }

    // Build query arguments
    $query_args = [
        'entityId' => $destination_id,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'adults' => $adults,
        'rooms' => $rooms
    ];

    if ( $children > 0 ) {
        $query_args['children'] = $children;
    }

    $url = add_query_arg( $query_args, $api_endpoint_stage2 );
    
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_stage2,
            'x-rapidapi-host' => $api_host_stage2,
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