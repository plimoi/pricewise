<?php
// includes/rapidapi/rapidapi-hotels-sear-step4.php - Step 4 API functions for Hotel Prices Search

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test API Connection for Step 4 - uses sample parameters for hotel price search.
 */
function pricewise_test_api_step4() {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step4      = isset( $options['api_key_step4'] ) ? $options['api_key_step4'] : '';
    $api_host_step4     = isset( $options['api_host_step4'] ) ? $options['api_host_step4'] : '';
    $api_endpoint_step4 = isset( $options['api_endpoint_step4'] ) ? $options['api_endpoint_step4'] : '';

    if ( empty( $api_key_step4 ) || empty( $api_host_step4 ) || empty( $api_endpoint_step4 ) ) {
        return 'Error: Step 4 API configuration not complete';
    }
    // Sample parameters for testing
    $sample_destination = '27539793'; // Example destination ID
    $sample_checkin     = date('Y-m-d', strtotime('+7 days'));
    $sample_checkout    = date('Y-m-d', strtotime('+10 days'));
    $sample_adults      = 2;
    $sample_rooms       = 1;
    $sample_children    = 1;

    // Build query arguments for Step 4
    $query_args = [
        'entityId' => $sample_destination,
        'checkin'  => $sample_checkin,
        'checkout' => $sample_checkout,
        'adults'   => $sample_adults,
        'rooms'    => $sample_rooms,
        'children' => $sample_children
    ];

    // Optionally add hotelId if available for testing
    // $query_args['hotelId'] = 'sample_hotelId';

    $url  = add_query_arg( $query_args, $api_endpoint_step4 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step4,
            'x-rapidapi-host' => $api_host_step4,
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
    return 'Status: 200 OK<br><br>Full Response (Step 4):<br><pre>' . esc_html( $body ) . '</pre>';
}

/**
 * Detailed Test API Connection for Step 4 - provides detailed info and parameters.
 */
function pricewise_test_api_step4_detailed() {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step4      = isset( $options['api_key_step4'] ) ? $options['api_key_step4'] : '';
    $api_host_step4     = isset( $options['api_host_step4'] ) ? $options['api_host_step4'] : '';
    $api_endpoint_step4 = isset( $options['api_endpoint_step4'] ) ? $options['api_endpoint_step4'] : '';

    if ( empty( $api_key_step4 ) || empty( $api_host_step4 ) || empty( $api_endpoint_step4 ) ) {
        return 'Error: Step 4 API configuration not complete';
    }

    // Sample parameters for detailed test
    $sample_destination = '27539793'; // Example destination ID
    $sample_checkin     = date('Y-m-d', strtotime('+7 days'));
    $sample_checkout    = date('Y-m-d', strtotime('+10 days'));
    $sample_adults      = 2;
    $sample_rooms       = 1;
    $sample_children    = 1;

    $query_args = [
        'entityId' => $sample_destination,
        'checkin'  => $sample_checkin,
        'checkout' => $sample_checkout,
        'adults'   => $sample_adults,
        'rooms'    => $sample_rooms,
        'children' => $sample_children
    ];

    // Optionally add hotelId if available for testing
    // $query_args['hotelId'] = 'sample_hotelId';

    $url  = add_query_arg( $query_args, $api_endpoint_step4 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step4,
            'x-rapidapi-host' => $api_host_step4,
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
    $result .= 'Destination ID: ' . $sample_destination . '<br>';
    $result .= 'Check-in: ' . $sample_checkin . '<br>';
    $result .= 'Check-out: ' . $sample_checkout . '<br>';
    $result .= 'Adults: ' . $sample_adults . '<br>';
    $result .= 'Rooms: ' . $sample_rooms . '<br>';
    $result .= 'Children: ' . $sample_children . '<br><br>';
    $result .= '<strong>API Endpoint:</strong><br>' . esc_url( $url ) . '<br><br>';

    if ( $status_code != 200 ) {
        $result .= '<strong>Error:</strong> HTTP ' . $status_code . '<br><br>Response Body: <pre>' . esc_html( $body ) . '</pre>';
    } else {
        $result .= '<strong>Status:</strong> 200 OK<br><br><strong>Full Response:</strong><br><pre>' . esc_html( $body ) . '</pre>';
    }
    
    return $result;
}

/**
 * Search hotel prices using the Step 4 API endpoint, optionally using hotelId from Step 3.
 *
 * @param string $destination_id
 * @param string $checkin
 * @param string $checkout
 * @param int    $adults
 * @param int    $rooms
 * @param int    $children
 * @param string $hotelId    Optional hotel identifier from Step 3.
 *
 * @return array|WP_Error
 */
function pricewise_search_hotel_prices( $destination_id, $checkin, $checkout, $adults, $rooms, $children = 0, $hotelId = '' ) {
    $options            = get_option( 'pricewise_api_settings' );
    $api_key_step4      = isset( $options['api_key_step4'] ) ? $options['api_key_step4'] : '';
    $api_host_step4     = isset( $options['api_host_step4'] ) ? $options['api_host_step4'] : '';
    $api_endpoint_step4 = isset( $options['api_endpoint_step4'] ) ? $options['api_endpoint_step4'] : '';

    if ( empty( $api_key_step4 ) || empty( $api_host_step4 ) || empty( $api_endpoint_step4 ) ) {
        return new WP_Error( 'api_configuration', 'Step 4 API configuration not complete' );
    }

    $query_args = [
        'entityId' => $destination_id,
        'checkin'  => $checkin,
        'checkout' => $checkout,
        'adults'   => $adults,
        'rooms'    => $rooms
    ];

    if ( $children > 0 ) {
        $query_args['children'] = $children;
    }
    
    // Add hotelId parameter if provided from Step 3
    if ( ! empty( $hotelId ) ) {
        $query_args['hotelId'] = $hotelId;
    }

    $url  = add_query_arg( $query_args, $api_endpoint_step4 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step4,
            'x-rapidapi-host' => $api_host_step4,
        ],
        'timeout' => 30,
    ];

    $log = array();
    $log[] = "Step 4: Calling API with URL: $url";
    $response = wp_remote_get( $url, $args );
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_error', 'Error: ' . $response->get_error_message() );
    }
    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code != 200 ) {
        return new WP_Error( 'api_error', 'HTTP ' . $status_code );
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( is_array( $data ) ) {
        $data['log'] = implode("\n", $log);
    }
    return $data;
}
?>
