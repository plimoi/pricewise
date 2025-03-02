<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Pricewise
 * @subpackage Pricewise/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and public-facing hooks.
 *
 * @package    Pricewise
 * @subpackage Pricewise/public
 * @author     Your Name <email@example.com>
 */
class Pricewise_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The API handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Pricewise_Hotels_API    $api    The API handler instance.
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string               $plugin_name    The name of this plugin.
     * @param    string               $version        The version of this plugin.
     * @param    Pricewise_Hotels_API $api            The API handler instance.
     */
    public function __construct($plugin_name, $version, $api) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = $api;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/pricewise-public.css',
            array(),
            $this->version,
            'all'
        );

        // Add jQuery UI datepicker styles
        wp_enqueue_style(
            'jquery-ui',
            '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
            array(),
            '1.13.2',
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // jQuery and jQuery UI are dependencies
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/pricewise-public.js',
            array('jquery', 'jquery-ui-datepicker', 'jquery-ui-autocomplete'),
            $this->version,
            true
        );

        // Pass data to JavaScript
        wp_localize_script($this->plugin_name, 'pricewise_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pricewise_public_nonce'),
            'strings' => array(
                'location_placeholder' => __('Enter a destination', 'pricewise'),
                'date_format' => 'yy-mm-dd', // Format for the datepicker (YYYY-MM-DD)
                'search_error' => __('Error performing search. Please try again.', 'pricewise'),
                'no_results' => __('No results found. Please try a different search.', 'pricewise'),
                'loading' => __('Loading...', 'pricewise'),
            )
        ));
    }

    /**
     * AJAX handler for location autocomplete.
     *
     * @since    1.0.0
     */
    public function ajax_autocomplete() {
        // Check nonce for security
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'pricewise_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pricewise')));
        }

        // Get the query term
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        
        if (empty($term) || strlen($term) < 2) {
            wp_send_json_error(array('message' => __('Search term is too short.', 'pricewise')));
        }
        
        // Check rate limiting
        $data_manager = new Pricewise_Data_Manager();
        if ($data_manager->is_rate_limited()) {
            wp_send_json_error(array(
                'message' => __('You have reached the maximum number of searches allowed per hour. Please try again later.', 'pricewise')
            ));
            return;
        }
        
        // Get location suggestions from API
        $results = $this->api->autocomplete($term);
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }
        
        $suggestions = array();
        
        if (!empty($results['data']) && !empty($results['data']['results'])) {
            foreach ($results['data']['results'] as $result) {
                $suggestions[] = array(
                    'label' => $result['name'] . (isset($result['country']) ? ', ' . $result['country'] : ''),
                    'value' => $result['name'],
                    'entity_id' => isset($result['entityId']) ? $result['entityId'] : '',
                );
            }
        }
        
        wp_send_json_success($suggestions);
    }

    /**
     * AJAX handler for hotel search.
     *
     * @since    1.0.0
     */
    public function ajax_search() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_public_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pricewise')));
        }
        
        // Get search parameters
        $params = array();
        
        $required_fields = array('destination', 'checkin', 'checkout', 'adults', 'rooms');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error(array('message' => sprintf(
                    /* translators: %s: field name */
                    __('Required field "%s" is missing.', 'pricewise'),
                    $field
                )));
                return;
            }
            
            $params[$field] = sanitize_text_field($_POST[$field]);
        }
        
        // Optional fields
        $optional_fields = array('entity_id', 'children', 'children_ages');
        foreach ($optional_fields as $field) {
            if (isset($_POST[$field])) {
                $params[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        // Validate dates
        $checkin_date = strtotime($params['checkin']);
        $checkout_date = strtotime($params['checkout']);
        $today = strtotime(date('Y-m-d'));
        
        if ($checkin_date < $today) {
            wp_send_json_error(array('message' => __('Check-in date cannot be in the past.', 'pricewise')));
            return;
        }
        
        if ($checkout_date <= $checkin_date) {
            wp_send_json_error(array('message' => __('Check-out date must be after check-in date.', 'pricewise')));
            return;
        }
        
        // Check rate limiting
        $data_manager = new Pricewise_Data_Manager();
        if ($data_manager->is_rate_limited()) {
            wp_send_json_error(array(
                'message' => __('You have reached the maximum number of searches allowed per hour. Please try again later.', 'pricewise')
            ));
            return;
        }
        
        // Create search object
        $search = new Pricewise_Search($params);
        
        // Log the search
        $data_manager->log_search($search);
        
        // Check if we have entity ID
        if (empty($search->entity_id)) {
            // Try to get entity ID from destination
            $locations = $this->api->autocomplete($search->destination);
            
            if (is_wp_error($locations)) {
                wp_send_json_error(array('message' => $locations->get_error_message()));
                return;
            }
            
            if (empty($locations['data']) || empty($locations['data']['results'])) {
                wp_send_json_error(array('message' => __('No locations found for the given destination. Please try a different search.', 'pricewise')));
                return;
            }
            
            // Use the first location
            $search->entity_id = $locations['data']['results'][0]['entityId'];
        }
        
        // Now search for hotels
        $results = $this->api->search(
            $search->entity_id,
            $search->checkin,
            $search->checkout,
            $search->get_api_params()
        );
        
        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
            return;
        }
        
        // Check if we have results
        if (empty($results['data']) || empty($results['data']['hotels'])) {
            wp_send_json_error(array('message' => __('No hotels found for the given search criteria. Please try a different search.', 'pricewise')));
            return;
        }
        
        // Process results
        $hotels = array();
        foreach ($results['data']['hotels'] as $hotel_data) {
            $hotel = new Pricewise_Hotel($hotel_data);
            
            // Save hotel data for future use
            $hotel->save();
            
            // Format data for response
            $hotels[] = array(
                'id' => $hotel->id,
                'name' => $hotel->name,
                'location' => $hotel->location,
                'stars' => $hotel->stars,
                'image' => $hotel->image_url,
                'price' => isset($hotel->prices['lowest']['price']) ? $hotel->prices['lowest']['price'] : '',
                'raw_price' => isset($hotel->prices['lowest']['raw_price']) ? $hotel->prices['lowest']['raw_price'] : 0,
                'provider' => isset($hotel->prices['lowest']['partner']) ? $hotel->prices['lowest']['partner'] : '',
                'deeplink' => isset($hotel->prices['lowest']['deeplink']) ? $hotel->prices['lowest']['deeplink'] : '',
                'review_score' => isset($hotel->reviews['score']) ? $hotel->reviews['score'] : 0,
                'review_count' => isset($hotel->reviews['total']) ? $hotel->reviews['total'] : 0,
                'review_description' => isset($hotel->reviews['description']) ? $hotel->reviews['description'] : '',
            );
        }
        
        // Create response data
        $response = array(
            'hotels' => $hotels,
            'search' => array(
                'destination' => $search->destination,
                'entity_id' => $search->entity_id,
                'checkin' => $search->checkin,
                'checkout' => $search->checkout,
                'adults' => $search->adults,
                'children' => $search->children,
                'rooms' => $search->rooms,
                'total_results' => count($hotels),
            ),
        );
        
        wp_send_json_success($response);
    }
}