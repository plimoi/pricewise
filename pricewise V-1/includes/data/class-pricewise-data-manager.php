<?php
/**
 * The data manager class handles database interactions.
 *
 * This class defines methods for interacting with the plugin's database tables,
 * including CRUD operations for hotels, search logs, and other data.
 *
 * @since      1.0.0
 * @package    Pricewise
 * @subpackage Pricewise/includes/data
 */

class Pricewise_Data_Manager {

    /**
     * Table names for the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $tables    Array of table names used by the plugin.
     */
    private $tables;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        
        $this->tables = array(
            'cache' => $wpdb->prefix . 'pricewise_cache',
            'logs' => $wpdb->prefix . 'pricewise_search_logs',
            'hotels' => $wpdb->prefix . 'pricewise_hotels'
        );
    }

    /**
     * Save a hotel to the database.
     *
     * @since    1.0.0
     * @param    Pricewise_Hotel    $hotel    The hotel object to save.
     * @return   boolean                     True on success, false on failure.
     */
    public function save_hotel($hotel) {
        global $wpdb;
        
        if (!$hotel instanceof Pricewise_Hotel || empty($hotel->id) || empty($hotel->name)) {
            return false;
        }
        
        $data = array(
            'hotel_id' => $hotel->id,
            'entity_id' => $hotel->entity_id,
            'name' => $hotel->name,
            'location' => $hotel->location,
            'stars' => $hotel->stars,
            'image_url' => $hotel->image_url,
            'last_updated' => current_time('mysql')
        );
        
        $formats = array('%s', '%s', '%s', '%s', '%d', '%s', '%s');
        
        // Check if hotel already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['hotels']} WHERE hotel_id = %s",
            $hotel->id
        ));
        
        if ($exists) {
            // Update existing hotel
            $result = $wpdb->update(
                $this->tables['hotels'],
                $data,
                array('hotel_id' => $hotel->id),
                $formats,
                array('%s')
            );
        } else {
            // Insert new hotel
            $result = $wpdb->insert(
                $this->tables['hotels'],
                $data,
                $formats
            );
        }
        
        return $result !== false;
    }

    /**
     * Get a hotel from the database by ID.
     *
     * @since    1.0.0
     * @param    string    $hotel_id    The hotel ID.
     * @return   Pricewise_Hotel|false  The hotel object or false if not found.
     */
    public function get_hotel($hotel_id) {
        global $wpdb;
        
        $hotel_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['hotels']} WHERE hotel_id = %s",
            $hotel_id
        ), ARRAY_A);
        
        if (!$hotel_data) {
            return false;
        }
        
        $hotel = new Pricewise_Hotel();
        $hotel->id = $hotel_data['hotel_id'];
        $hotel->entity_id = $hotel_data['entity_id'];
        $hotel->name = $hotel_data['name'];
        $hotel->location = $hotel_data['location'];
        $hotel->stars = $hotel_data['stars'];
        $hotel->image_url = $hotel_data['image_url'];
        
        return $hotel;
    }

    /**
     * Get hotels from the database by entity ID (location).
     *
     * @since    1.0.0
     * @param    string    $entity_id    The entity/location ID.
     * @param    int       $limit        Optional. Maximum number of hotels to return. Default 10.
     * @param    int       $offset       Optional. Number of hotels to skip. Default 0.
     * @return   array                  Array of Pricewise_Hotel objects.
     */
    public function get_hotels_by_entity($entity_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $hotels_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['hotels']} WHERE entity_id = %s LIMIT %d OFFSET %d",
            $entity_id,
            $limit,
            $offset
        ), ARRAY_A);
        
        $hotels = array();
        
        foreach ($hotels_data as $hotel_data) {
            $hotel = new Pricewise_Hotel();
            $hotel->id = $hotel_data['hotel_id'];
            $hotel->entity_id = $hotel_data['entity_id'];
            $hotel->name = $hotel_data['name'];
            $hotel->location = $hotel_data['location'];
            $hotel->stars = $hotel_data['stars'];
            $hotel->image_url = $hotel_data['image_url'];
            
            $hotels[] = $hotel;
        }
        
        return $hotels;
    }

    /**
     * Search for hotels in the database.
     *
     * @since    1.0.0
     * @param    string    $search_term    The search term.
     * @param    int       $limit          Optional. Maximum number of hotels to return. Default 10.
     * @param    int       $offset         Optional. Number of hotels to skip. Default 0.
     * @return   array                    Array of Pricewise_Hotel objects.
     */
    public function search_hotels($search_term, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $hotels_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['hotels']} 
            WHERE name LIKE %s OR location LIKE %s
            LIMIT %d OFFSET %d",
            $search_term,
            $search_term,
            $limit,
            $offset
        ), ARRAY_A);
        
        $hotels = array();
        
        foreach ($hotels_data as $hotel_data) {
            $hotel = new Pricewise_Hotel();
            $hotel->id = $hotel_data['hotel_id'];
            $hotel->entity_id = $hotel_data['entity_id'];
            $hotel->name = $hotel_data['name'];
            $hotel->location = $hotel_data['location'];
            $hotel->stars = $hotel_data['stars'];
            $hotel->image_url = $hotel_data['image_url'];
            
            $hotels[] = $hotel;
        }
        
        return $hotels;
    }

    /**
     * Delete a hotel from the database.
     *
     * @since    1.0.0
     * @param    string    $hotel_id    The hotel ID.
     * @return   boolean               True on success, false on failure.
     */
    public function delete_hotel($hotel_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->tables['hotels'],
            array('hotel_id' => $hotel_id),
            array('%s')
        );
        
        return $result !== false;
    }

    /**
     * Log a search query.
     *
     * @since    1.0.0
     * @param    Pricewise_Search    $search    The search object.
     * @return   int|false                     The log ID on success, false on failure.
     */
    public function log_search($search) {
        global $wpdb;
        
        $data = array(
            'user_id' => get_current_user_id(),
            'user_ip' => $this->get_user_ip(),
            'search_query' => $search->destination,
            'search_params' => maybe_serialize(array(
                'destination' => $search->destination,
                'entity_id' => $search->entity_id,
                'checkin' => $search->checkin,
                'checkout' => $search->checkout,
                'adults' => $search->adults,
                'children' => $search->children,
                'children_ages' => $search->children_ages,
                'rooms' => $search->rooms,
                'filters' => $search->filters,
            )),
            'created' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $this->tables['logs'],
            $data,
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Check if a user has exceeded the rate limit for searches.
     *
     * @since    1.0.0
     * @return   boolean    True if rate limit is exceeded, false otherwise.
     */
    public function is_rate_limited() {
        global $wpdb;
        
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
                "SELECT COUNT(*) FROM {$this->tables['logs']} WHERE user_id = %d AND created > %s",
                $user_id,
                $time_threshold
            );
        } else {
            // Guest - check by IP
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tables['logs']} WHERE user_ip = %s AND created > %s",
                $user_ip,
                $time_threshold
            );
        }
        
        $search_count = intval($wpdb->get_var($query));
        
        return $search_count >= $rate_limit;
    }

    /**
     * Get search logs.
     *
     * @since    1.0.0
     * @param    int       $limit     Optional. Maximum number of logs to return. Default 100.
     * @param    int       $offset    Optional. Number of logs to skip. Default 0.
     * @return   array                Array of search logs.
     */
    public function get_search_logs($limit = 100, $offset = 0) {
        global $wpdb;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['logs']} ORDER BY created DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
        
        foreach ($logs as &$log) {
            $log['search_params'] = maybe_unserialize($log['search_params']);
        }
        
        return $logs;
    }

    /**
     * Delete old search logs.
     *
     * @since    1.0.0
     * @param    int       $days    Optional. Delete logs older than this many days. Default 30.
     * @return   int                Number of logs deleted.
     */
    public function clean_search_logs($days = 30) {
        global $wpdb;
        
        $time_threshold = date('Y-m-d H:i:s', time() - ($days * 86400)); // X days ago
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->tables['logs']} WHERE created < %s",
            $time_threshold
        ));
        
        return $result;
    }

    /**
     * Get the user's IP address.
     *
     * @since    1.0.0
     * @access   private
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