<?php
/**
 * The shortcode functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Pricewise
 * @subpackage Pricewise/public
 */

/**
 * The shortcode functionality of the plugin.
 *
 * Defines and handles all shortcodes used in the plugin.
 *
 * @package    Pricewise
 * @subpackage Pricewise/public
 * @author     Your Name <email@example.com>
 */
class Pricewise_Shortcodes {

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
     * @param    Pricewise_Hotels_API    $api    The API handler instance.
     */
    public function __construct($api) {
        $this->api = $api;
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('pricewise_search_form', array($this, 'render_search_form'));
        add_shortcode('pricewise_search_results', array($this, 'render_search_results'));
        add_shortcode('pricewise_hotel_details', array($this, 'render_hotel_details'));
    }

    /**
     * Render the search form shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function render_search_form($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'destination' => get_option('pricewise_default_location', 'Rome'),
                'adults' => get_option('pricewise_default_adults', 1),
                'children' => get_option('pricewise_default_children', 0),
                'rooms' => get_option('pricewise_default_rooms', 1),
                'results_page' => '', // URL of the results page
            ),
            $atts,
            'pricewise_search_form'
        );

        // Set up current date and default check-in/check-out dates
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $day_after_tomorrow = date('Y-m-d', strtotime('+2 days'));

        // Get any search parameters from URL to pre-fill the form
        $destination = isset($_GET['destination']) ? sanitize_text_field($_GET['destination']) : $atts['destination'];
        $entity_id = isset($_GET['entity_id']) ? sanitize_text_field($_GET['entity_id']) : '';
        $checkin = isset($_GET['checkin']) ? sanitize_text_field($_GET['checkin']) : $tomorrow;
        $checkout = isset($_GET['checkout']) ? sanitize_text_field($_GET['checkout']) : $day_after_tomorrow;
        $adults = isset($_GET['adults']) ? intval($_GET['adults']) : $atts['adults'];
        $children = isset($_GET['children']) ? intval($_GET['children']) : $atts['children'];
        $rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : $atts['rooms'];
        
        // Get children ages if any
        $children_ages = array();
        if (isset($_GET['children_ages']) && !empty($_GET['children_ages'])) {
            $children_ages = explode(',', sanitize_text_field($_GET['children_ages']));
        }
        
        // Ensure we have the right number of ages
        while (count($children_ages) < $children) {
            $children_ages[] = 5; // Default age
        }
        
        // Start output buffering
        ob_start();
        
        // Include the search form template
        include PRICEWISE_PLUGIN_DIR . 'public/partials/search-form.php';
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Render the search results shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function render_search_results($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'per_page' => 10,
            ),
            $atts,
            'pricewise_search_results'
        );

        // Start output buffering
        ob_start();
        
        // Check if we have search parameters
        if (isset($_GET['destination']) || isset($_GET['entity_id'])) {
            // Create search object
            $search = new Pricewise_Search($_GET);
            
            // Check if rate limited
            $data_manager = new Pricewise_Data_Manager();
            if ($data_manager->is_rate_limited()) {
                ?>
                <div class="pricewise-rate-limit-error">
                    <p><?php _e('You have reached the maximum number of searches allowed per hour. Please try again later.', 'pricewise'); ?></p>
                </div>
                <?php
                return ob_get_clean();
            }
            
            // Log the search
            $data_manager->log_search($search);
            
            // Check if we have the entity ID
            if (empty($search->entity_id)) {
                // Try to get entity ID from destination
                $locations = $this->api->autocomplete($search->destination);
                
                if (is_wp_error($locations)) {
                    ?>
                    <div class="pricewise-error">
                        <p><?php echo esc_html($locations->get_error_message()); ?></p>
                    </div>
                    <?php
                    return ob_get_clean();
                }
                
                // Check if we found any locations
                if (empty($locations['data']) || empty($locations['data']['results'])) {
                    ?>
                    <div class="pricewise-error">
                        <p><?php _e('No locations found for the given destination. Please try a different search.', 'pricewise'); ?></p>
                    </div>
                    <?php
                    return ob_get_clean();
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
                ?>
                <div class="pricewise-error">
                    <p><?php echo esc_html($results->get_error_message()); ?></p>
                </div>
                <?php
                return ob_get_clean();
            }
            
            // Check if we have results
            if (empty($results['data']) || empty($results['data']['hotels'])) {
                ?>
                <div class="pricewise-no-results">
                    <p><?php _e('No hotels found for the given search criteria. Please try a different search.', 'pricewise'); ?></p>
                </div>
                <?php
                return ob_get_clean();
            }
            
            // Process results
            $hotels = array();
            foreach ($results['data']['hotels'] as $hotel_data) {
                $hotel = new Pricewise_Hotel($hotel_data);
                $hotels[] = $hotel;
            }
            
            // Include the search results template
            include PRICEWISE_PLUGIN_DIR . 'public/partials/search-results.php';
        } else {
            // No search parameters - show a message
            ?>
            <div class="pricewise-no-search">
                <p><?php _e('Please use the search form to find hotels.', 'pricewise'); ?></p>
            </div>
            <?php
        }
        
        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Render the hotel details shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function render_hotel_details($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'hotel_id' => '',
            ),
            $atts,
            'pricewise_hotel_details'
        );

        // Get hotel ID from attribute or URL
        $hotel_id = !empty($atts['hotel_id']) ? $atts['hotel_id'] : (isset($_GET['hotel_id']) ? sanitize_text_field($_GET['hotel_id']) : '');
        
        // Check if we have a hotel ID
        if (empty($hotel_id)) {
            return '<p>' . __('No hotel specified.', 'pricewise') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Try to get hotel details
        $result = $this->api->get_details($hotel_id);
        
        if (is_wp_error($result)) {
            ?>
            <div class="pricewise-error">
                <p><?php echo esc_html($result->get_error_message()); ?></p>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Process hotel data
        $hotel = new Pricewise_Hotel();
        $hotel->id = $hotel_id;
        
        if (isset($result['data'])) {
            $hotel->populate($result['data']);
        }
        
        // Get check-in/check-out dates from URL or use defaults
        $checkin = isset($_GET['checkin']) ? sanitize_text_field($_GET['checkin']) : date('Y-m-d', strtotime('+1 day'));
        $checkout = isset($_GET['checkout']) ? sanitize_text_field($_GET['checkout']) : date('Y-m-d', strtotime('+2 days'));
        
        // Get room options
        $adults = isset($_GET['adults']) ? intval($_GET['adults']) : 1;
        $children = isset($_GET['children']) ? intval($_GET['children']) : 0;
        $children_ages = isset($_GET['children_ages']) ? explode(',', sanitize_text_field($_GET['children_ages'])) : array();
        $rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : 1;
        
        // Get price information
        $options = array(
            'adults' => $adults,
            'rooms' => $rooms,
        );
        
        if ($children > 0 && !empty($children_ages)) {
            $options['childrenAges'] = implode(',', $children_ages);
        }
        
        $hotel->fetch_prices($this->api, $checkin, $checkout, $options);
        
        // Include the hotel details template
        include PRICEWISE_PLUGIN_DIR . 'public/partials/hotel-details.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
}