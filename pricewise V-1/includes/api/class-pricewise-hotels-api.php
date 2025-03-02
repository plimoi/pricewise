<?php
/**
 * The Hotels API integration functionality of the plugin.
 *
 * @package    Pricewise
 * @subpackage Pricewise/includes/api
 */

class Pricewise_Hotels_API extends Pricewise_API {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Search for locations using autocomplete.
     *
     * @since    1.0.0
     * @param    string     $query       The search query.
     * @param    string     $market      The market (default: 'US').
     * @param    string     $locale      The locale (default: 'en-US').
     * @param    boolean    $use_cache   Whether to use cached results if available.
     * @return   array|WP_Error          The API response or WP_Error on failure.
     */
    public function autocomplete($query, $market = 'US', $locale = 'en-US', $use_cache = true) {
        $params = array(
            'query' => sanitize_text_field($query),
            'market' => sanitize_text_field($market),
            'locale' => sanitize_text_field($locale),
        );
        
        $response = $this->request('hotels/auto-complete', $params, 'GET', $use_cache);
        
        if (is_wp_error($response)) {
            $this->log_error($response);
            return $response;
        }
        
        return $response;
    }

    /**
     * Search for hotels.
     *
     * @since    1.0.0
     * @param    string     $entity_id     The entity ID from autocomplete.
     * @param    string     $checkin       The check-in date (YYYY-MM-DD).
     * @param    string     $checkout      The check-out date (YYYY-MM-DD).
     * @param    array      $options       Additional search options.
     * @param    boolean    $use_cache     Whether to use cached results if available.
     * @return   array|WP_Error            The API response or WP_Error on failure.
     */
    public function search($entity_id, $checkin, $checkout, $options = [], $use_cache = true) {
        // Validate dates
        if (!$this->validate_date($checkin) || !$this->validate_date($checkout)) {
            return new WP_Error(
                'invalid_dates',
                __('Invalid date format. Use YYYY-MM-DD format.', 'pricewise')
            );
        }
        
        // Check if checkout date is after check-in date
        if (strtotime($checkout) <= strtotime($checkin)) {
            return new WP_Error(
                'invalid_date_range',
                __('Check-out date must be after check-in date.', 'pricewise')
            );
        }
        
        // Set required parameters
        $params = array(
            'entityId' => sanitize_text_field($entity_id),
            'checkin' => sanitize_text_field($checkin),
            'checkout' => sanitize_text_field($checkout),
        );
        
        // Add optional parameters
        $valid_options = array(
            'resultsPerPage', 'page', 'rooms', 'adults', 'childrenAges',
            'market', 'locale', 'currency', 'sorting', 'priceType',
            'minPrice', 'maxPrice', 'confidentType', 'stars',
            'mealPlan', 'rating', 'guestType', 'chain', 'cancellation',
            'amenities', 'propertyType', 'district', 'discountTypes'
        );
        
        foreach ($valid_options as $option) {
            if (isset($options[$option])) {
                $params[$option] = sanitize_text_field($options[$option]);
            }
        }
        
        $response = $this->request('hotels/search', $params, 'GET', $use_cache);
        
        if (is_wp_error($response)) {
            $this->log_error($response);
            return $response;
        }
        
        // Check if we need to poll for complete results
        if (isset($response['data']['status']['completionPercentage']) 
            && $response['data']['status']['completionPercentage'] < 100) {
            
            // Get search ID for polling
            $search_id = $response['data']['status']['searchId'];
            
            // Poll for complete results (up to 3 times)
            for ($i = 0; $i < 3; $i++) {
                // Wait a moment before polling
                sleep(2);
                
                // Same request with the search ID
                $response = $this->request('hotels/search', $params, 'GET', false);
                
                if (is_wp_error($response)) {
                    $this->log_error($response);
                    return $response;
                }
                
                // Check if search is complete
                if (isset($response['data']['status']['completionPercentage']) 
                    && $response['data']['status']['completionPercentage'] >= 100) {
                    break;
                }
            }
            
            // Cache the final result
            if ($use_cache) {
                $cache_key = md5('hotels/search' . serialize($params));
                $this->cache->set($cache_key, $response);
            }
        }
        
        return $response;
    }

    /**
     * Get hotel details.
     *
     * @since    1.0.0
     * @param    string     $id           The hotel ID.
     * @param    string     $market       The market (default: 'US').
     * @param    string     $locale       The locale (default: 'en-US').
     * @param    string     $currency     The currency (default: 'USD').
     * @param    boolean    $use_cache    Whether to use cached results if available.
     * @return   array|WP_Error           The API response or WP_Error on failure.
     */
    public function get_details($id, $market = 'US', $locale = 'en-US', $currency = 'USD', $use_cache = true) {
        $params = array(
            'id' => sanitize_text_field($id),
            'market' => sanitize_text_field($market),
            'locale' => sanitize_text_field($locale),
            'currency' => sanitize_text_field($currency),
        );
        
        $response = $this->request('hotels/detail', $params, 'GET', $use_cache);
        
        if (is_wp_error($response)) {
            $this->log_error($response);
            return $response;
        }
        
        return $response;
    }

    /**
     * Get hotel prices.
     *
     * @since    1.0.0
     * @param    string     $hotel_id     The hotel ID.
     * @param    string     $checkin      The check-in date (YYYY-MM-DD).
     * @param    string     $checkout     The check-out date (YYYY-MM-DD).
     * @param    array      $options      Additional options.
     * @param    boolean    $use_cache    Whether to use cached results if available.
     * @return   array|WP_Error           The API response or WP_Error on failure.
     */
    public function get_prices($hotel_id, $checkin, $checkout, $options = [], $use_cache = true) {
        // Validate dates
        if (!$this->validate_date($checkin) || !$this->validate_date($checkout)) {
            return new WP_Error(
                'invalid_dates',
                __('Invalid date format. Use YYYY-MM-DD format.', 'pricewise')
            );
        }
        
        // Set required parameters
        $params = array(
            'hotelId' => sanitize_text_field($hotel_id),
            'checkin' => sanitize_text_field($checkin),
            'checkout' => sanitize_text_field($checkout),
        );
        
        // Add optional parameters
        $valid_options = array(
            'rooms', 'adults', 'childrenAges', 'market', 'locale', 'currency'
        );
        
        foreach ($valid_options as $option) {
            if (isset($options[$option])) {
                $params[$option] = sanitize_text_field($options[$option]);
            }
        }
        
        $response = $this->request('hotels/prices', $params, 'GET', $use_cache);
        
        if (is_wp_error($response)) {
            $this->log_error($response);
            return $response;
        }
        
        return $response;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     *
     * @since    1.0.0
     * @param    string     $date    The date string to validate.
     * @return   boolean             True if valid, false otherwise.
     */
    private function validate_date($date) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dt = DateTime::createFromFormat('Y-m-d', $date);
            return $dt && $dt->format('Y-m-d') === $date;
        }
        return false;
    }
}