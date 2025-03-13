<?php
/**
 * Test History Data Class
 * Handles data processing and AJAX functionality for the API test history.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Data {
    /**
     * Database handler
     *
     * @var Pricewise_Test_History_DB
     */
    private $db;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * AJAX handler for saving manual data field preferences
     */
    public function ajax_save_manual_data_fields() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_manual_data_fields')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check current user can manage options
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            return;
        }
        
        // Get the fields - ensure we have arrays
        $default_fields = isset($_POST['default_fields']) ? $_POST['default_fields'] : array();
        $param_fields = isset($_POST['param_fields']) ? $_POST['param_fields'] : array();
        $header_fields = isset($_POST['header_fields']) ? $_POST['header_fields'] : array();
        
        // Sanitize default fields
        $default_fields_assoc = array();
        if (is_array($default_fields)) {
            foreach ($default_fields as $field => $value) {
                $field = sanitize_key($field);
                $default_fields_assoc[$field] = 1;
            }
        }
        
        // Sanitize param fields
        $param_fields_assoc = array();
        if (is_array($param_fields)) {
            foreach ($param_fields as $field => $value) {
                $field = sanitize_key($field);
                $param_fields_assoc[$field] = 1;
            }
        }
        
        // Sanitize header fields
        $header_fields_assoc = array();
        if (is_array($header_fields)) {
            foreach ($header_fields as $field => $value) {
                $field = sanitize_key($field);
                $header_fields_assoc[$field] = 1;
            }
        }
        
        // Save to user meta
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'pricewise_manual_data_default_fields', $default_fields_assoc);
        update_user_meta($user_id, 'pricewise_manual_data_param_fields', $param_fields_assoc);
        update_user_meta($user_id, 'pricewise_manual_data_header_fields', $header_fields_assoc);
        
        // Return success
        wp_send_json_success(array(
            'message' => 'Column preferences saved successfully',
            'default_fields' => $default_fields_assoc,
            'param_fields' => $param_fields_assoc,
            'header_fields' => $header_fields_assoc
        ));
    }
    
    /**
     * Get all field names from request parameters and response headers
     * 
     * @param int $limit Maximum number of records to scan
     * @return array Field names grouped by type
     */
    public function get_all_field_names($limit = 100) {
        return $this->db->get_all_field_names($limit);
    }
    
    /**
     * Get user preference for a specific field
     * 
     * @param string $field_name Field name
     * @param string $field_type Field type (default, params, or headers)
     * @return bool Whether the field should be displayed
     */
    public function is_field_visible($field_name, $field_type = 'params') {
        $user_id = get_current_user_id();
        
        if ($field_type === 'default') {
            $saved_fields = get_user_meta($user_id, 'pricewise_manual_data_default_fields', true);
        } elseif ($field_type === 'params') {
            $saved_fields = get_user_meta($user_id, 'pricewise_manual_data_param_fields', true);
        } else {
            $saved_fields = get_user_meta($user_id, 'pricewise_manual_data_header_fields', true);
        }
        
        if (!is_array($saved_fields) || empty($saved_fields)) {
            // If no preferences saved, show all fields by default
            return true;
        }
        
        return isset($saved_fields[$field_name]);
    }
    
    /**
     * Format data for display
     * 
     * @param mixed $data Data to format
     * @return string Formatted data
     */
    public function format_data_for_display($data) {
        if (is_array($data)) {
            return $this->format_array_for_display($data);
        } elseif (is_object($data)) {
            return $this->format_array_for_display((array)$data);
        } else {
            return (string)$data;
        }
    }
    
    /**
     * Format array for display
     * 
     * @param array $array Array to format
     * @return string Formatted string
     */
    public function format_array_for_display($array) {
        if (empty($array)) {
            return '';
        }
        
        ob_start();
        foreach ($array as $key => $value) {
            $key = sanitize_text_field($key);
            echo $key . ': ';
            
            if (is_array($value)) {
                echo "\n";
                foreach ($value as $sub_key => $sub_value) {
                    $sub_key = sanitize_text_field($sub_key);
                    $sub_value = is_scalar($sub_value) ? sanitize_text_field($sub_value) : json_encode($sub_value);
                    echo '  ' . $sub_key . ': ' . $sub_value . "\n";
                }
            } else {
                echo is_scalar($value) ? sanitize_text_field($value) : json_encode($value);
                echo "\n";
            }
        }
        
        return trim(ob_get_clean());
    }
    
    /**
     * Prepare test data for display
     * 
     * @param array $test Test record
     * @return array Processed test data
     */
    public function prepare_test_for_display($test) {
        if (!is_array($test)) {
            return $test;
        }
        
        // Process request headers if present
        if (!empty($test['request_headers'])) {
            $test['request_headers'] = $this->ensure_array($test['request_headers']);
        }
        
        // Process request parameters if present
        if (!empty($test['request_params'])) {
            $test['request_params'] = $this->ensure_array($test['request_params']);
        }
        
        // Process response headers if present
        if (!empty($test['response_headers'])) {
            $test['response_headers'] = $this->ensure_array($test['response_headers']);
        }
        
        return $test;
    }
    
    /**
     * Ensure value is an array
     * 
     * @param mixed $value Value to convert
     * @return array Value as array
     */
    private function ensure_array($value) {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Try JSON decode first
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            // Try unserialize
            $unserialized = @maybe_unserialize($value);
            if (is_array($unserialized)) {
                return $unserialized;
            }
        }
        
        // Fallback - wrap in array
        return array('value' => $value);
    }
}