<?php
// includes/search-form.php - Search form for PriceWise with hotel results display and process logging.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form id="pricewise-search-form" method="post" action="">
    <div>
        <label for="destination">Destination:</label>
        <input type="text" id="destination" name="destination" autocomplete="off" required>
        <!-- Suggestions will appear dynamically below via JS -->
    </div>
    <div>
        <label for="checkin_date">Check-in Date:</label>
        <input type="date" id="checkin_date" name="checkin_date" required>
    </div>
    <div>
        <label for="checkout_date">Check-out Date:</label>
        <input type="date" id="checkout_date" name="checkout_date" required>
    </div>
    <div>
        <label for="adults">Number of Adults:</label>
        <input type="number" id="adults" name="adults" min="1" value="1" required>
    </div>
    <div>
        <label for="children">Number of Children:</label>
        <input type="number" id="children" name="children" min="0" value="0">
    </div>
    <div>
        <label for="rooms">Number of Rooms:</label>
        <input type="number" id="rooms" name="rooms" min="1" value="1" required>
    </div>
    <div>
        <button type="submit" name="pricewise_search_submit">Search</button>
    </div>
</form>

<?php
if ( isset( $_POST['pricewise_search_submit'] ) ) {
    // Initialize log array for process tracking
    $log = array();
    
    // Sanitize input values
    $destination = sanitize_text_field( $_POST['destination'] );
    $checkin     = sanitize_text_field( $_POST['checkin_date'] );
    $checkout    = sanitize_text_field( $_POST['checkout_date'] );
    $adults      = intval( $_POST['adults'] );
    $rooms       = intval( $_POST['rooms'] );
    $children    = isset( $_POST['children'] ) ? intval( $_POST['children'] ) : 0;
    
    $log[] = "Step 1: Searching destinations with query: '$destination'";
    // Step 1: Get destination ID using the auto-complete API function
    $destinations = pricewise_search_destinations( $destination );
    if ( is_wp_error( $destinations ) ) {
        $log[] = "Error in Step 1: " . $destinations->get_error_message();
        echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
    } elseif ( empty( $destinations ) || !isset( $destinations['data'] ) || empty( $destinations['data'] ) ) {
        $log[] = "No destination found for '$destination'";
        echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
    } else {
        // Select the first suggestion's destination ID
        $first         = reset( $destinations['data'] );
        $destination_id = isset( $first['entityId'] ) ? $first['entityId'] : '';
        if ( empty( $destination_id ) ) {
            $log[] = "Invalid destination selected.";
            echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
        } else {
            $log[] = "Step 1 completed: Destination ID found: $destination_id";
            // Step 2: Search hotels using the Step 2 API endpoint (with polling)
            $log[] = "Step 2: Searching hotels with parameters - Destination ID: $destination_id, Checkin: $checkin, Checkout: $checkout, Adults: $adults, Rooms: $rooms, Children: $children";
            $search_results = pricewise_search_hotels( $destination_id, $checkin, $checkout, $adults, $rooms, $children );
            if ( is_wp_error( $search_results ) ) {
                $log[] = "Error in Step 2: " . $search_results->get_error_message();
                echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
                exit;
            } elseif ( empty( $search_results ) || !isset( $search_results['results']['hotelCards'] ) || empty( $search_results['results']['hotelCards'] ) ) {
                $log[] = "No hotels found in Step 2.";
                echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
                exit;
            } else {
                $log[] = "Step 2 completed: Hotels found.";
                // Append any internal log messages from Step 2 polling if available
                if ( isset( $search_results['log'] ) ) {
                    $log[] = "Step 2 Log: " . $search_results['log'];
                }
                
                // Step 3: Retrieve detailed hotel information using the Step 3 API endpoint
                $hotel_card = reset( $search_results['results']['hotelCards'] );
                $hotel_detail_id = isset( $hotel_card['id'] ) ? $hotel_card['id'] : '';
                $hotel_price_id  = isset( $hotel_card['hotelId'] ) ? $hotel_card['hotelId'] : '';
                
                if ( empty( $hotel_detail_id ) || empty( $hotel_price_id ) ) {
                    $log[] = "Required hotel identifiers not found in search results.";
                    echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
                    exit;
                }
                $log[] = "Step 3: Retrieved hotel detail ID: $hotel_detail_id and hotel price ID: $hotel_price_id";
                $details = pricewise_get_hotel_details( $hotel_detail_id );
                if ( is_wp_error( $details ) ) {
                    $log[] = "Error in Step 3: " . $details->get_error_message();
                    echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
                    exit;
                } else {
                    $log[] = "Step 3 completed: Hotel details retrieved.";
                    // Step 4: Retrieve hotel prices using the Step 4 API endpoint,
                    // passing the hotelId obtained from the hotel card (or from details)
                    $log[] = "Step 4: Searching hotel prices with hotelId: $hotel_price_id";
                    $price_results = pricewise_search_hotel_prices( $destination_id, $checkin, $checkout, $adults, $rooms, $children, $hotel_price_id );
                    if ( is_wp_error( $price_results ) ) {
                        $log[] = "Error in Step 4: " . $price_results->get_error_message();
                        echo '<div><pre>' . implode("\n", $log) . '</pre></div>';
                        exit;
                    } else {
                        $log[] = "Step 4 completed: Hotel prices retrieved.";
                        // Display final results and log
                        echo '<h2>Hotel Search Results for ' . esc_html( $destination ) . '</h2>';
                        echo '<h3>Hotel Details:</h3>';
                        echo '<pre>' . print_r( $details, true ) . '</pre>';
                        echo '<h3>Hotel Prices:</h3>';
                        if ( isset( $price_results['hotels'] ) && is_array( $price_results['hotels'] ) && !empty( $price_results['hotels'] ) ) {
                            echo '<ul>';
                            foreach ( $price_results['hotels'] as $hotel ) {
                                $hotel_name = isset( $hotel['name'] ) ? $hotel['name'] : 'Unknown';
                                $price      = isset( $hotel['price'] ) ? $hotel['price'] : 'N/A';
                                $currency   = isset( $hotel['currency'] ) ? $hotel['currency'] : '';
                                echo '<li>';
                                echo '<strong>' . esc_html( $hotel_name ) . '</strong> - ' . esc_html( $price ) . ' ' . esc_html( $currency );
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<pre>' . print_r( $price_results, true ) . '</pre>';
                        }
                        echo '<hr><h3>Process Log:</h3>';
                        echo '<pre>' . implode("\n", $log) . '</pre>';
                    }
                }
            }
        }
    }
}
?>
