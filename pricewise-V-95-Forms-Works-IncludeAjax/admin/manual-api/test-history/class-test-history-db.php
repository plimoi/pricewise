<?php
/**
 * Test History Database Class
 * Handles database operations for the API test history feature.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_DB {
    /**
     * The table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pricewise_test_history';
    }

    /**
     * Create or update the database table
     *
     * @return bool Success or failure
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            api_id varchar(100) NOT NULL,
            api_name varchar(255) NOT NULL,
            test_date datetime NOT NULL,
            endpoint varchar(255) NOT NULL,
            status_code int(5),
            response_time float,
            request_headers longtext,
            request_params longtext, 
            response_headers longtext,
            response_snippet text,
            error_message text,
            PRIMARY KEY (id),
            KEY api_id (api_id),
            KEY test_date (test_date),
            KEY status_code (status_code),
            KEY api_endpoint (api_id,endpoint), 
            KEY api_date (api_id,test_date),
            KEY endpoint_status (endpoint,status_code),
            KEY date_status (test_date,status_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Check if table was created successfully
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    /**
     * Get table name
     *
     * @return string Table name
     */
    public function get_table_name() {
        return $this->table_name;
    }

    /**
     * Save test result to the database
     *
     * @param array $data Test data to save
     * @return int|false The record ID or false on failure
     */
    public function save_test($data) {
        global $wpdb;

        // Ensure required fields
        if (empty($data['api_id']) || empty($data['api_name']) || empty($data['endpoint'])) {
            return false;
        }

        // Format data for insertion
        $insert_data = array(
            'api_id' => sanitize_text_field($data['api_id']),
            'api_name' => sanitize_text_field($data['api_name']),
            'test_date' => current_time('mysql'),
            'endpoint' => sanitize_text_field($data['endpoint']),
            'status_code' => isset($data['status_code']) ? intval($data['status_code']) : null,
            'response_time' => isset($data['response_time']) ? floatval($data['response_time']) : null,
            'request_headers' => isset($data['request_headers']) ? $this->prepare_for_storage($data['request_headers']) : null,
            'request_params' => isset($data['request_params']) ? $this->prepare_for_storage($data['request_params']) : null,
            'response_headers' => isset($data['response_headers']) ? $this->prepare_for_storage($data['response_headers']) : null,
            'response_snippet' => isset($data['response_snippet']) ? sanitize_textarea_field($data['response_snippet']) : null,
            'error_message' => isset($data['error_message']) ? sanitize_textarea_field($data['error_message']) : null
        );

        // Insert into database
        $result = $wpdb->insert($this->table_name, $insert_data);

        if ($result === false) {
            return false;
        }

        $new_id = $wpdb->insert_id;
        
        // Auto cleanup to maintain maximum record count
        $this->auto_cleanup();
        
        return $new_id;
    }

    /**
     * Retrieve test history records with optional filtering
     *
     * @param array $args Query arguments
     * @return array Test records
     */
    public function get_tests($args = array()) {
        global $wpdb;

        // Default arguments
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'api_id' => '',
            'status_code' => '',
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'test_date',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);
        
        // Start building query
        $sql = "SELECT * FROM $this->table_name WHERE 1=1";
        $prepare_args = array();

        // Add filters if specified
        if (!empty($args['api_id'])) {
            $sql .= " AND api_id = %s";
            $prepare_args[] = $args['api_id'];
        }

        if (!empty($args['status_code'])) {
            $sql .= " AND status_code = %d";
            $prepare_args[] = $args['status_code'];
        }

        if (!empty($args['date_from'])) {
            $sql .= " AND test_date >= %s";
            $prepare_args[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $sql .= " AND test_date <= %s";
            $prepare_args[] = $args['date_to'];
        }

        // Add order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby = in_array($args['orderby'], array('test_date', 'api_name', 'status_code')) ? $args['orderby'] : 'test_date';
        
        $sql .= " ORDER BY $orderby $order";

        // Add pagination
        $per_page = intval($args['per_page']);
        $page = max(1, intval($args['page']));
        $offset = ($page - 1) * $per_page;
        
        $sql .= " LIMIT %d OFFSET %d";
        $prepare_args[] = $per_page;
        $prepare_args[] = $offset;

        // Prepare and execute query
        if (!empty($prepare_args)) {
            $sql = $wpdb->prepare($sql, $prepare_args);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        // Process the results
        if (!empty($results)) {
            foreach ($results as &$result) {
                $result['request_headers'] = $this->prepare_from_storage($result['request_headers']);
                $result['request_params'] = $this->prepare_from_storage($result['request_params']);
                $result['response_headers'] = $this->prepare_from_storage($result['response_headers']);
            }
        }

        return $results ?: array();
    }

    /**
     * Get a single test record
     *
     * @param int $id Test record ID
     * @return array|false Test record or false if not found
     */
    public function get_test($id) {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id);
        $result = $wpdb->get_row($sql, ARRAY_A);

        if (!$result) {
            return false;
        }

        // Process the results
        $result['request_headers'] = $this->prepare_from_storage($result['request_headers']);
        $result['request_params'] = $this->prepare_from_storage($result['request_params']);
        $result['response_headers'] = $this->prepare_from_storage($result['response_headers']);

        return $result;
    }

    /**
     * Delete test records
     *
     * @param int|array $ids Single ID or array of IDs to delete
     * @return int Number of records deleted
     */
    public function delete_tests($ids) {
        global $wpdb;

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        $query = $wpdb->prepare(
            "DELETE FROM $this->table_name WHERE id IN ($placeholders)",
            $ids
        );

        $wpdb->query($query);
        return $wpdb->rows_affected;
    }

    /**
     * Clean up old test records keeping only a specific number of most recent records
     * 
     * @param int $records_count Number of most recent records to keep (if null, uses saved setting)
     * @param int $batch_size Number of entries to delete in each batch
     * @param int $max_time Maximum execution time in seconds (0 for no limit)
     * @param bool $return_stats Whether to return detailed stats (true) or just count (false)
     * @return int|array Number of records deleted or stats array if $return_stats is true
     */
    public function cleanup_old_tests($records_count = null, $batch_size = 1000, $max_time = 0, $return_stats = false) {
        // If no specific count provided, use the saved setting
        if ($records_count === null) {
            $records_count = $this->get_max_records();
        }
        global $wpdb;
        $start_time = microtime(true);
        $total_deleted = 0;
        $batches = 0;
        $errors = array();

        // Get total number of records
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        
        if ($wpdb->last_error) {
            $errors[] = "Error counting records: " . $wpdb->last_error;
            return $return_stats ? array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => false,
                'errors' => $errors
            ) : 0;
        }
        
        // If we have fewer records than we want to keep, nothing to do
        if ($total_records <= $records_count) {
            return $return_stats ? array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => true,
                'errors' => array(),
                'total_records' => $total_records,
                'records_to_keep' => $records_count
            ) : 0;
        }
        
        // Find the ID threshold - records with IDs lower than this should be deleted
        $threshold_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $this->table_name ORDER BY test_date DESC, id DESC LIMIT %d,1",
                $records_count - 1
            )
        );
        
        if (!$threshold_id) {
            $errors[] = "Error determining threshold ID";
            return $return_stats ? array(
                'total_deleted' => 0,
                'batches' => 0,
                'duration' => 0,
                'complete' => false,
                'errors' => $errors
            ) : 0;
        }
        
        // Calculate how many records will be deleted
        $records_to_delete = $total_records - $records_count;
        
        // Use batched deletion to handle large datasets
        do {
            // Delete one batch of records older than the threshold
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $this->table_name WHERE id < %d LIMIT %d",
                    $threshold_id,
                    $batch_size
                )
            );
            
            if ($wpdb->last_error) {
                $errors[] = "Error deleting batch: " . $wpdb->last_error;
                break;
            }
            
            if ($deleted) {
                $total_deleted += $deleted;
                $batches++;
            }
            
            // Check if we've reached a time limit
            $elapsed_time = microtime(true) - $start_time;
            $time_limit_reached = ($max_time > 0 && $elapsed_time >= $max_time);
            
            // Small pause to reduce database load if doing many batches
            if ($batches % 5 === 0 && $deleted >= $batch_size) {
                usleep(50000); // 50ms pause every 5 batches
            }
            
            // Continue if we deleted a full batch and haven't reached time limit
        } while ($deleted >= $batch_size && !$time_limit_reached);
        
        $stats = array(
            'total_deleted' => $total_deleted,
            'batches' => $batches,
            'duration' => round(microtime(true) - $start_time, 2),
            'complete' => ($deleted < $batch_size),
            'errors' => $errors,
            'total_records' => $total_records,
            'records_to_keep' => $records_count
        );
        
        return $return_stats ? $stats : $total_deleted;
    }

    /**
     * Get total number of test records
     *
     * @param array $args Filter arguments
     * @return int Number of records
     */
    public function get_tests_count($args = array()) {
        global $wpdb;

        // Start building query
        $sql = "SELECT COUNT(*) FROM $this->table_name WHERE 1=1";
        $prepare_args = array();

        // Add filters if specified
        if (!empty($args['api_id'])) {
            $sql .= " AND api_id = %s";
            $prepare_args[] = $args['api_id'];
        }

        if (!empty($args['status_code'])) {
            $sql .= " AND status_code = %d";
            $prepare_args[] = $args['status_code'];
        }

        if (!empty($args['date_from'])) {
            $sql .= " AND test_date >= %s";
            $prepare_args[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $sql .= " AND test_date <= %s";
            $prepare_args[] = $args['date_to'];
        }

        // Prepare and execute query
        if (!empty($prepare_args)) {
            $sql = $wpdb->prepare($sql, $prepare_args);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get available APIs with test records
     *
     * @return array API IDs and names
     */
    public function get_api_list() {
        global $wpdb;

        $query = "SELECT DISTINCT api_id, api_name FROM $this->table_name ORDER BY api_name ASC";
        $results = $wpdb->get_results($query, ARRAY_A);

        return $results ?: array();
    }

    /**
     * Get summary statistics for tests
     *
     * @param string $api_id Optional API ID to filter by
     * @return array Statistics
     */
    public function get_stats($api_id = '') {
        global $wpdb;

        $where = '';
        $prepare_args = array();

        if (!empty($api_id)) {
            $where = "WHERE api_id = %s";
            $prepare_args[] = $api_id;
        }

        $query = "SELECT 
            COUNT(*) as total_tests,
            SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed,
            MIN(test_date) as first_test,
            MAX(test_date) as last_test,
            AVG(response_time) as avg_response_time
            FROM $this->table_name $where";

        if (!empty($prepare_args)) {
            $query = $wpdb->prepare($query, $prepare_args);
        }

        $stats = $wpdb->get_row($query, ARRAY_A);

        if (!$stats) {
            return array(
                'total_tests' => 0,
                'successful' => 0,
                'failed' => 0,
                'first_test' => null,
                'last_test' => null,
                'avg_response_time' => 0
            );
        }

        return $stats;
    }

    /**
     * Check if the test history table exists
     *
     * @return bool True if exists, false otherwise
     */
    public function table_exists() {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    /**
     * Get all unique field names from request parameters and response headers
     *
     * @param int $limit Maximum number of records to scan
     * @return array Associative array of field names by type
     */
    public function get_all_field_names($limit = 100) {
        global $wpdb;
        
        // Get the most recent test records
        $query = "SELECT request_params, response_headers FROM $this->table_name 
                 WHERE request_params IS NOT NULL OR response_headers IS NOT NULL 
                 ORDER BY test_date DESC LIMIT %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit), ARRAY_A);
        
        // Initialize arrays to hold field names
        $param_fields = array();
        $header_fields = array();
        
        // Process each record
        foreach ($results as $result) {
            // Extract request parameter field names
            if (!empty($result['request_params'])) {
                $params = $this->prepare_from_storage($result['request_params']);
                if (is_array($params)) {
                    foreach ($params as $key => $value) {
                        if (!in_array($key, $param_fields)) {
                            $param_fields[] = $key;
                        }
                    }
                }
            }
            
            // Extract response header field names
            if (!empty($result['response_headers'])) {
                $headers = $this->prepare_from_storage($result['response_headers']);
                if (is_array($headers)) {
                    foreach ($headers as $key => $value) {
                        if (!in_array($key, $header_fields)) {
                            $header_fields[] = $key;
                        }
                    }
                }
            }
        }
        
        // Sort fields alphabetically
        sort($param_fields);
        sort($header_fields);
        
        return array(
            'params' => $param_fields,
            'headers' => $header_fields
        );
    }

    /**
     * Prepare data for storage in the database
     *
     * @param mixed $data Data to prepare
     * @return string|null JSON encoded data or null
     */
    private function prepare_for_storage($data) {
        if (empty($data)) {
            return null;
        }
        return wp_json_encode($data);
    }

    /**
     * Prepare data from storage for use
     *
     * @param string $data JSON encoded data
     * @return mixed Decoded data or empty array
     */
    private function prepare_from_storage($data) {
        if (empty($data)) {
            return array();
        }
        $decoded = json_decode($data, true);
        return $decoded ?: array();
    }
    
    /**
     * Get the maximum number of test records to keep
     *
     * @return int Maximum number of records to keep
     */
    public function get_max_records() {
        $max_records = get_option('pricewise_max_test_records', 30);
        return absint($max_records);
    }
    
    /**
     * Set the maximum number of test records to keep
     *
     * @param int $count Maximum number of records to keep
     * @return bool Success or failure
     */
    public function set_max_records($count) {
        $count = absint($count);
        if ($count < 1) {
            $count = 30; // Default minimum
        }
        return update_option('pricewise_max_test_records', $count);
    }
    
    /**
     * Automatically cleanup old records to maintain maximum record count
     *
     * @return int Number of records deleted
     */
    public function auto_cleanup() {
        $max_records = $this->get_max_records();
        
        // No cleanup if max_records is 0 (unlimited)
        if ($max_records < 1) {
            return 0;
        }
        
        return $this->cleanup_old_tests($max_records, 500, 3, false);
    }
}