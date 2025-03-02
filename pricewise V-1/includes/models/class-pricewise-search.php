<?php
/**
 * The search parameters model.
 *
 * @package    Pricewise
 * @subpackage Pricewise/includes/models
 */

class Pricewise_Search {

    /**
     * The destination name.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $destination    The destination name.
     */
    public $destination;

    /**
     * The entity ID for the destination.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $entity_id    The entity ID.
     */
    public $entity_id;

    /**
     * The check-in date.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $checkin    The check-in date (YYYY-MM-DD).
     */
    public $checkin;

    /**
     * The check-out date.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $checkout    The check-out date (YYYY-MM-DD).
     */
    public $checkout;

    /**
     * The number of adults.
     *
     * @since    1.0.0
     * @access   public
     * @var      int    $adults    The number of adults.
     */
    public $adults;

    /**
     * The number of children.
     *
     * @since    1.0.0
     * @access   public
     * @var      int    $children    The number of children.
     */
    public $children;

    /**
     * The ages of children.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $children_ages    The ages of children.
     */
    public $children_ages = array();

    /**
     * The number of rooms.
     *
     * @since    1.0.0
     * @access   public
     * @var      int    $rooms    The number of rooms.
     */
    public $rooms;

    /**
     * The market.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $market    The market (e.g., 'US').
     */
    public $market = 'US';

    /**
     * The locale.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $locale    The locale (e.g., 'en-US').
     */
    public $locale = 'en-US';

    /**
     * The currency.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $currency    The currency (e.g., 'USD').
     */
    public $currency = 'USD';

    /**
     * The search filters.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $filters    The search filters.
     */
    public $filters = array();

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    array    $params    Optional. The search parameters.
     */
    public function __construct($params = array()) {
        // Set default values
        $this->destination = get_option('pricewise_default_location', 'Rome');
        $this->adults = intval(get_option('pricewise_default_adults', 1));
        $this->children = intval(get_option('pricewise_default_children', 0));
        $this->rooms = intval(get_option('pricewise_default_rooms', 1));
        
        // Set default dates if not provided
        $this->checkin = date('Y-m-d'); // Today
        $this->checkout = date('Y-m-d', strtotime('+2 days')); // Day after tomorrow
        
        // Override defaults with provided params
        if (!empty($params)) {
            $this->set_params($params);
        }
    }

    /**
     * Set search parameters.
     *
     * @since    1.0.0
     * @param    array    $params    The search parameters.
     * @return   void
     */
    public function set_params($params) {
        // Basic parameters
        if (isset($params['destination'])) {
            $this->destination = sanitize_text_field($params['destination']);
        }
        
        if (isset($params['entity_id'])) {
            $this->entity_id = sanitize_text_field($params['entity_id']);
        }
        
        if (isset($params['checkin']) && $this->validate_date($params['checkin'])) {
            $this->checkin = sanitize_text_field($params['checkin']);
        }
        
        if (isset($params['checkout']) && $this->validate_date($params['checkout'])) {
            $this->checkout = sanitize_text_field($params['checkout']);
        }
        
        if (isset($params['adults']) && is_numeric($params['adults'])) {
            $this->adults = max(1, intval($params['adults']));
        }
        
        if (isset($params['children']) && is_numeric($params['children'])) {
            $this->children = max(0, intval($params['children']));
        }
        
        if (isset($params['rooms']) && is_numeric($params['rooms'])) {
            $this->rooms = max(1, intval($params['rooms']));
        }
        
        // Handle children ages
        if (isset($params['children_ages'])) {
            if (is_array($params['children_ages'])) {
                $this->children_ages = array_map('intval', $params['children_ages']);
            } else {
                // Handle comma-separated string
                $ages = explode(',', $params['children_ages']);
                $this->children_ages = array_map('intval', $ages);
            }
            
            // Ensure we have the correct number of ages
            while (count($this->children_ages) < $this->children) {
                $this->children_ages[] = 5; // Default age
            }
            
            // Trim extra ages
            $this->children_ages = array_slice($this->children_ages, 0, $this->children);
        }
        
        // Optional parameters
        if (isset($params['market'])) {
            $this->market = sanitize_text_field($params['market']);
        }
        
        if (isset($params['locale'])) {
            $this->locale = sanitize_text_field($params['locale']);
        }
        
        if (isset($params['currency'])) {
            $this->currency = sanitize_text_field($params['currency']);
        }
        
        // Handle filters
        $filter_types = array(
            'stars', 'rating', 'price_min', 'price_max', 'amenities',
            'meal_plan', 'property_type', 'cancellation'
        );
        
        foreach ($filter_types as $filter_type) {
            if (isset($params[$filter_type])) {
                $this->filters[$filter_type] = is_array($params[$filter_type])
                    ? array_map('sanitize_text_field', $params[$filter_type])
                    : sanitize_text_field($params[$filter_type]);
            }
        }
    }

    /**
     * Validate date format (YYYY-MM-DD).
     *
     * @since    1.0.0
     * @param    string    $date    The date string to validate.
     * @return   boolean           True if valid, false otherwise.
     */
    private function validate_date($date) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dt = DateTime::createFromFormat('Y-m-d', $date);
            return $dt && $dt->format('Y-m-d') === $date;
        }
        return false;
    }

    /**
     * Get the search parameters as an array for API requests.
     *
     * @since    1.0.0
     * @return   array    The search parameters.
     */
    public function get_api_params() {
        $params = array(
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'rooms' => $this->rooms,
            'adults' => $this->adults,
            'market' => $this->market,
            'locale' => $this->locale,
            'currency' => $this->currency,
        );
        
        // Add entity ID if available
        if (!empty($this->entity_id)) {
            $params['entityId'] = $this->entity_id;
        }
        
        // Add children ages if applicable
        if ($this->children > 0 && !empty($this->children_ages)) {
            $params['childrenAges'] = implode(',', $this->children_ages);
        }
        
        // Add filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $value) {
                switch ($key) {
                    case 'price_min':
                        $params['minPrice'] = $value;
                        break;
                    case 'price_max':
                        $params['maxPrice'] = $value;
                        break;
                    case 'stars':
                    case 'rating':
                    case 'amenities':
                    case 'meal_plan':
                    case 'property_type':
                    case 'cancellation':
                        // Convert array to comma-separated string
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        
                        // Map to API parameter names
                        $api_key = $key === 'rating' ? 'rating' : $key;
                        $params[$api_key] = $value;
                        break;
                }
            }
        }
        
        return $params;
    }

    /**
     * Get the search parameters as a query string.
     *
     * @since    1.0.0
     * @return   string    The query string.
     */
    public function get_query_string() {
        $query_params = array(
            'destination' => urlencode($this->destination),
            'checkin' => $this->checkin,
            'checkout' => $this->checkout,
            'adults' => $this->adults,
            'children' => $this->children,
            'rooms' => $this->rooms,
        );
        
        // Add entity ID if available
        if (!empty($this->entity_id)) {
            $query_params['entity_id'] = urlencode($this->entity_id);
        }
        
        // Add children ages if applicable
        if ($this->children > 0 && !empty($this->children_ages)) {
            $query_params['children_ages'] = implode(',', $this->children_ages);
        }
        
        // Add filters
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $query_params[$key] = urlencode($value);
            }
        }
        
        return http_build_query($query_params);
    }

    /**
     * Log the search for rate limiting.
     *
     * @since    1.0.0
     * @return   int|false    The log ID on success, false on failure.
     */
    public function log_search() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pricewise_search_logs';
        
        $data = array(
            'user_id' => get_current_user_id(),
            'user_ip' => $this->get_user_ip(),
            'search_query' => $this->destination,
            'search_params' => maybe_serialize(array(
                'destination' => $this->destination,
                'entity_id' => $this->entity_id,
                'checkin' => $this->checkin,
                'checkout' => $this->checkout,
                'adults' => $this->adults,
                'children' => $this->children,
                'children_ages' => $this->children_ages,
                'rooms' => $this->rooms,
                'filters' => $this->filters,
            )),
            'created' => current_time('mysql'),
        );
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Check if the user has exceeded the rate limit.
     *
     * @since    1.0.0
     * @return   boolean    True if rate limit is exceeded, false otherwise.
     */
    public function is_rate_limited() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pricewise_search_logs';
        $rate_limit = intval(get_option('pricewise_rate_limit_searches', 10));
        
        // Skip rate limiting for admins
        if (current_user_can('manage_options')) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $user_ip = $this->get_user_ip();
        
        // Get user search count in the last hour
        $time_threshold = date('Y-m-d H:i:s', time() - 3600); // Last hour
        
        if ($user_id > 0) {
            // Logged in user - check by user ID
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND created > %s",
                $user_id,
                $time_threshold
            );
        } else {
            // Guest - check by IP
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_ip = %s AND created > %s",
                $user_ip,
                $time_threshold
            );
        }
        
        $search_count = intval($wpdb->get_var($query));
        
        return $search_count >= $rate_limit;
    }

    /**
     * Get the user's IP address.
     *
     * @since    1.0.0
     * @return   string    The user's IP address.
     */
    private function get_user_ip() {
        // Check for various server variables
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return sanitize_text_field($_SERVER[$key]);
            }
        }
        
        // Default
        return '127.0.0.1';
    }
}