<?php
// includes/rapidapi/rapidapi-hotels-deta-step2.php - Step 2 API functions for Hotel Search by Destination

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test API Connection for Step 2 - uses a sample destination ID.
 */
function pricewise_test_api_step2() {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_step2   = isset( $options['api_key_step2'] ) ? $options['api_key_step2'] : '';
    $api_host_step2  = isset( $options['api_host_step2'] ) ? $options['api_host_step2'] : '';
    $api_endpoint_step2 = isset( $options['api_endpoint_step2'] ) ? $options['api_endpoint_step2'] : '';

    if ( empty( $api_key_step2 ) || empty( $api_host_step2 ) || empty( $api_endpoint_step2 ) ) {
        return 'Error: Step 2 API configuration not complete';
    }
    // Sample destination ID (should be replaced by the actual selected destination in real usage)
    $sample_destination = '27539793';
    $url = add_query_arg( [ 'destinationId' => $sample_destination ], $api_endpoint_step2 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step2,
            'x-rapidapi-host' => $api_host_step2,
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
    return 'Status: 200 OK<br><br>Full Response (Step 2):<br><pre>' . esc_html( $body ) . '</pre>';
}

/**
 * Detailed Test API Connection for Step 2 - uses sample parameters for a more complete test.
 */
function pricewise_test_api_step2_detailed() {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_step2   = isset( $options['api_key_step2'] ) ? $options['api_key_step2'] : '';
    $api_host_step2  = isset( $options['api_host_step2'] ) ? $options['api_host_step2'] : '';
    $api_endpoint_step2 = isset( $options['api_endpoint_step2'] ) ? $options['api_endpoint_step2'] : '';

    if ( empty( $api_key_step2 ) || empty( $api_host_step2 ) || empty( $api_endpoint_step2 ) ) {
        return 'Error: Step 2 API configuration not complete';
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

    $url = add_query_arg($query_args, $api_endpoint_step2);
    
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step2,
            'x-rapidapi-host' => $api_host_step2,
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
 * Search hotels using the Step 2 API endpoint with polling until completionPercentage==100.
 */
function pricewise_search_hotels( $destination_id, $checkin, $checkout, $adults, $rooms, $children = 0 ) {
    $options         = get_option( 'pricewise_api_settings' );
    $api_key_step2   = isset( $options['api_key_step2'] ) ? $options['api_key_step2'] : '';
    $api_host_step2  = isset( $options['api_host_step2'] ) ? $options['api_host_step2'] : '';
    $api_endpoint_step2 = isset( $options['api_endpoint_step2'] ) ? $options['api_endpoint_step2'] : '';

    if ( empty( $api_key_step2 ) || empty( $api_host_step2 ) || empty( $api_endpoint_step2 ) ) {
        return new WP_Error( 'api_configuration', 'Step 2 API configuration not complete' );
    }

    // Build query arguments
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

    $url = add_query_arg( $query_args, $api_endpoint_step2 );
    $args = [
        'headers' => [
            'x-rapidapi-key'  => $api_key_step2,
            'x-rapidapi-host' => $api_host_step2,
        ],
        'timeout' => 30,
    ];

    $log = array();
    $log[] = "Step 2 polling initiated. URL: $url";
    $max_attempts = 10;
    $attempt = 0;
    $data = null;
    while ( $attempt < $max_attempts ) {
        $attempt++;
        $log[] = "Step 2 attempt $attempt: Calling API...";
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
        // Check if the completionPercentage is set and equals 100
        if ( isset( $data['status']['completionPercentage'] ) ) {
            $completion = intval( $data['status']['completionPercentage'] );
            $log[] = "Completion Percentage: $completion%";
            if ( $completion >= 100 ) {
                $log[] = "Polling complete.";
                break;
            } else {
                $log[] = "Not complete yet. Waiting for 2 seconds before retrying.";
                sleep(2);
            }
        } else {
            $log[] = "No completionPercentage found in response. Assuming complete.";
            break;
        }
    }
    if ( $attempt == $max_attempts ) {
        $log[] = "Max attempts reached without completion.";
    }
    // Append log to result
    if ( is_array( $data ) ) {
        $data['log'] = implode("\n", $log);
    }
    return $data;
}
?>
