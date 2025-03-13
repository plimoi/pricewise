<?php
/**
 * API Settings Class
 * 
 * This class manages storage, retrieval, and validation of API configurations.
 * It handles CRUD operations for API settings and their endpoints.
 *
 * @package PriceWise
 * @subpackage ManualAPI
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API settings management.
 */
class Pricewise_API_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Initialize the settings
     *
     * Registers WordPress settings and hooks.
     */
    public function init() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    /**
     * Register settings for Manual API
     *
     * Registers WordPress settings for the API configurations.
     */
    public function register_settings() {
        register_setting(
            'pricewise_manual_api_settings_group',
            'pricewise_manual_apis',
            array( $this, 'sanitize_settings' )
        );
    }
    
    /**
     * Sanitize manual API settings
     * 
     * Sanitizes and validates all API settings before saving to the database.
     *
     * @param array $input The input to sanitize
     * @return array Sanitized input
     */
    public function sanitize_settings( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }
        
        $new_input = array();
        
        foreach ( $input as $id => $api ) {
            $id = sanitize_key($id);
            $new_input[$id] = array();
            
            // Sanitize text fields
            if ( isset( $api['name'] ) ) {
                $new_input[$id]['name'] = sanitize_text_field( $api['name'] );
            }
            
            if ( isset( $api['id_name'] ) ) {
                $new_input[$id]['id_name'] = sanitize_key( $api['id_name'] );
            }
            
            if ( isset( $api['api_key'] ) ) {
                $new_input[$id]['api_key'] = sanitize_text_field( $api['api_key'] );
            }
            
            // Sanitize URL
            if ( isset( $api['base_endpoint'] ) ) {
                $new_input[$id]['base_endpoint'] = esc_url_raw( $api['base_endpoint'] );
            }
            
            // Sanitize active state (convert to bool)
            if ( isset( $api['active'] ) ) {
                $new_input[$id]['active'] = (bool) $api['active'];
            } else {
                // Default to active if not set
                $new_input[$id]['active'] = true;
            }
            
            // Handle endpoints
            if ( isset( $api['endpoints'] ) && is_array( $api['endpoints'] ) ) {
                $new_input[$id]['endpoints'] = array();
                
                foreach ( $api['endpoints'] as $endpoint_id => $endpoint ) {
                    $endpoint_id = sanitize_key($endpoint_id);
                    $sanitized_endpoint = $this->sanitize_endpoint($endpoint);
                    if (!empty($sanitized_endpoint)) {
                        $new_input[$id]['endpoints'][$endpoint_id] = $sanitized_endpoint;
                    }
                }
            }
        }
        
        return $new_input;
    }
    
    /**
     * Sanitize an endpoint configuration
     *
     * @param array $endpoint The endpoint configuration to sanitize
     * @return array Sanitized endpoint configuration
     */
    private function sanitize_endpoint($endpoint) {
        if (!is_array($endpoint)) {
            return array();
        }
        
        $sanitized = array();
        
        // Basic endpoint information
        if (isset($endpoint['name'])) {
            $sanitized['name'] = sanitize_text_field($endpoint['name']);
        }
        
        if (isset($endpoint['path'])) {
            $sanitized['path'] = sanitize_text_field($endpoint['path']);
        }
        
        // Sanitize active state for endpoints (convert to bool)
        if (isset($endpoint['active'])) {
            $sanitized['active'] = (bool) $endpoint['active'];
        } else {
            // Default to active if not set
            $sanitized['active'] = true;
        }
        
        // Include the rest of the advanced configuration
        if (isset($endpoint['config']) && is_array($endpoint['config'])) {
            $sanitized['config'] = $this->sanitize_advanced_config($endpoint['config']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize advanced configuration
     *
     * @param array $config The advanced configuration to sanitize
     * @return array Sanitized advanced configuration
     */
    private function sanitize_advanced_config($config) {
        if (!is_array($config)) {
            return array();
        }
        
        $sanitized = array();
        
        // Sanitize HTTP method
        if (isset($config['method'])) {
            // Validate against allowed methods
            $allowed_methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH');
            $method = strtoupper(sanitize_text_field($config['method']));
            $sanitized['method'] = in_array($method, $allowed_methods) ? $method : 'GET';
        }
        
        // Sanitize response format
        if (isset($config['response_format'])) {
            // Validate against allowed formats
            $allowed_formats = array('json', 'array', 'object', 'xml', 'html');
            $format = strtolower(sanitize_text_field($config['response_format']));
            $sanitized['response_format'] = in_array($format, $allowed_formats) ? $format : 'json';
        }
        
        // Sanitize request timeout
        if (isset($config['request_timeout'])) {
            $sanitized['request_timeout'] = max(1, min(120, absint($config['request_timeout'])));
        }
        
        // Sanitize cache duration
        if (isset($config['cache_duration'])) {
            $sanitized['cache_duration'] = max(1, absint($config['cache_duration']));
        }
        
        // Sanitize test history options (convert to bool)
        $bool_fields = array('save_test_headers', 'save_test_params', 'save_test_response_headers', 'save_test_response_body');
        foreach ($bool_fields as $field) {
            if (isset($config[$field])) {
                $sanitized[$field] = (bool) $config[$field];
            }
        }
        
        // Sanitize auth configuration
        if (isset($config['auth']) && is_array($config['auth'])) {
            $sanitized['auth'] = array();
            
            // Auth type
            if (isset($config['auth']['type'])) {
                $allowed_types = array('api_key', 'bearer', 'basic');
                $type = sanitize_text_field($config['auth']['type']);
                $sanitized['auth']['type'] = in_array($type, $allowed_types) ? $type : 'api_key';
            }
            
            // Headers disabled flag
            if (isset($config['auth']['disable_headers'])) {
                $sanitized['auth']['disable_headers'] = (bool)$config['auth']['disable_headers'];
            }
            
            // Headers
            if (isset($config['auth']['headers']) && is_array($config['auth']['headers'])) {
                $sanitized['auth']['headers'] = array();
                
                foreach ($config['auth']['headers'] as $header) {
                    if (isset($header['name']) && !empty($header['name'])) {
                        $sanitized_header = array(
                            'name' => sanitize_text_field($header['name']),
                            'value' => isset($header['value']) ? sanitize_text_field($header['value']) : '',
                            'save' => isset($header['save']) ? (bool)$header['save'] : false
                        );
                        
                        $sanitized['auth']['headers'][] = $sanitized_header;
                    }
                }
            }
        }
        
        // Sanitize response headers
        if (isset($config['response_headers']) && is_array($config['response_headers'])) {
            $sanitized['response_headers'] = array();
            
            foreach ($config['response_headers'] as $header) {
                if (isset($header['name']) && !empty($header['name'])) {
                    $sanitized_header = array(
                        'name' => sanitize_text_field($header['name']),
                        'save' => isset($header['save']) ? (bool)$header['save'] : false
                    );
                    
                    $sanitized['response_headers'][] = $sanitized_header;
                }
            }
        }
        
        // Sanitize body configuration
        if (isset($config['body']) && is_array($config['body'])) {
            $sanitized['body'] = array();
            
            if (isset($config['body']['enabled'])) {
                $sanitized['body']['enabled'] = (bool)$config['body']['enabled'];
            }
            
            if (isset($config['body']['type'])) {
                $allowed_types = array('json', 'form', 'raw');
                $type = sanitize_text_field($config['body']['type']);
                $sanitized['body']['type'] = in_array($type, $allowed_types) ? $type : 'json';
            }
            
            if (isset($config['body']['content'])) {
                // Content sanitization depends on type
                $content_type = isset($sanitized['body']['type']) ? $sanitized['body']['type'] : 'json';
                
                switch ($content_type) {
                    case 'json':
                        // Try to validate as JSON
                        $json_test = json_decode($config['body']['content']);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // It's valid JSON, preserve it
                            $sanitized['body']['content'] = $config['body']['content'];
                        } else {
                            // Not valid JSON, sanitize as plain text
                            $sanitized['body']['content'] = sanitize_textarea_field($config['body']['content']);
                        }
                        break;
                    
                    case 'form':
                        // Form data (key=value&key2=value2) format
                        $sanitized['body']['content'] = sanitize_textarea_field($config['body']['content']);
                        break;
                    
                    case 'raw':
                    default:
                        // For raw content, sanitize as textarea
                        $sanitized['body']['content'] = sanitize_textarea_field($config['body']['content']);
                        break;
                }
            }
        }
        
        // Sanitize parameters
        if (isset($config['params']) && is_array($config['params'])) {
            $sanitized['params'] = array();
            
            foreach ($config['params'] as $param) {
                if (isset($param['name']) && !empty($param['name'])) {
                    $sanitized_param = array(
                        'name' => sanitize_text_field($param['name']),
                        'value' => isset($param['value']) ? sanitize_text_field($param['value']) : '',
                        'save' => isset($param['save']) ? (bool)$param['save'] : false
                    );
                    
                    $sanitized['params'][] = $sanitized_param;
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get all manual APIs
     *
     * @return array Array of APIs
     */
    public static function get_apis() {
        return get_option('pricewise_manual_apis', array());
    }
    
    /**
     * Get a specific API by ID
     *
     * @param string $api_id The API ID
     * @return array|false The API configuration or false if not found
     */
    public static function get_api($api_id) {
        $api_id = sanitize_key($api_id);
        $apis = self::get_apis();
        return isset($apis[$api_id]) ? $apis[$api_id] : false;
    }
    
    /**
     * Get a specific endpoint from an API
     *
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID
     * @return array|false The endpoint configuration or false if not found
     */
    public static function get_endpoint($api_id, $endpoint_id) {
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        $api = self::get_api($api_id);
        
        if (!$api || !isset($api['endpoints']) || !isset($api['endpoints'][$endpoint_id])) {
            return false;
        }
        
        return $api['endpoints'][$endpoint_id];
    }
    
    /**
     * Get all endpoints for an API
     *
     * @param string $api_id The API ID
     * @return array Array of endpoint configurations
     */
    public static function get_endpoints($api_id) {
        $api_id = sanitize_key($api_id);
        $api = self::get_api($api_id);
        
        if (!$api || !isset($api['endpoints'])) {
            return array();
        }
        
        return $api['endpoints'];
    }
    
    /**
     * Save an API
     *
     * @param array $api_data The API data to save
     * @param string $old_id The old API ID if updating
     * @return bool|string True on success, error message on failure
     */
    public static function save_api($api_data, $old_id = '') {
        // Sanitize inputs
        $api_data = is_array($api_data) ? $api_data : array();
        $old_id = sanitize_key($old_id);
        
        // Validate required fields
        if (empty($api_data['name']) || empty($api_data['id_name'])) {
            return 'API name and ID are required.';
        }
        
        // Sanitize API data
        $api_data['name'] = sanitize_text_field($api_data['name']);
        $api_data['id_name'] = sanitize_key($api_data['id_name']);
        if (isset($api_data['api_key'])) {
            $api_data['api_key'] = sanitize_text_field($api_data['api_key']);
        }
        if (isset($api_data['base_endpoint'])) {
            $api_data['base_endpoint'] = esc_url_raw($api_data['base_endpoint']);
        }
        
        // Set active state (default to true if not set)
        if (!isset($api_data['active'])) {
            $api_data['active'] = true;
        } else {
            $api_data['active'] = (bool) $api_data['active'];
        }
        
        $apis = self::get_apis();
        $id = $api_data['id_name'];
        
        // Check if ID exists (except when updating the same API)
        if ($id !== $old_id && isset($apis[$id])) {
            return 'An API with this ID already exists.';
        }
        
        // If updating, remove old entry
        if (!empty($old_id) && $id !== $old_id && isset($apis[$old_id])) {
            unset($apis[$old_id]);
        }
        
        // Create default endpoints if none exist
        if (!isset($api_data['endpoints']) || empty($api_data['endpoints'])) {
            $api_data['endpoints'] = array(
                'default' => array(
                    'name' => 'Default',
                    'path' => '',
                    'active' => true,
                    'config' => array(
                        'method' => 'GET',
                        'response_format' => 'json',
                        'request_timeout' => 30,
                        'cache_duration' => 3600
                    )
                )
            );
        }
        
        // Save the API
        $apis[$id] = $api_data;
        update_option('pricewise_manual_apis', $apis);
        
        return true;
    }
    
    /**
     * Toggle API activation state
     *
     * @param string $api_id The API ID
     * @return bool True on success, false on failure
     */
    public static function toggle_api_activation($api_id) {
        $api_id = sanitize_key($api_id);
        $api = self::get_api($api_id);
        
        if (!$api) {
            return false;
        }
        
        // Toggle the active state
        $api['active'] = isset($api['active']) ? !(bool)$api['active'] : false;
        
        // Save the updated API
        return self::save_api($api, $api_id) === true;
    }
    
    /**
     * Toggle endpoint activation state
     *
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID
     * @return bool True on success, false on failure
     */
    public static function toggle_endpoint_activation($api_id, $endpoint_id) {
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        
        // Get API and endpoint
        $api = self::get_api($api_id);
        $endpoint = self::get_endpoint($api_id, $endpoint_id);
        
        if (!$api || !$endpoint) {
            return false;
        }
        
        // Check if API is active
        $api_active = isset($api['active']) ? (bool)$api['active'] : true;
        $endpoint_active = isset($endpoint['active']) ? (bool)$endpoint['active'] : true;
        
        // If API is inactive, only allow deactivating endpoints (not activating)
        if (!$api_active && !$endpoint_active) {
            return false; // Cannot activate an endpoint if parent API is inactive
        }
        
        // Toggle the active state
        $api['endpoints'][$endpoint_id]['active'] = isset($endpoint['active']) ? !(bool)$endpoint['active'] : false;
        
        // Save the updated API
        return self::save_api($api, $api_id) === true;
    }
    
    /**
     * Save an endpoint for an API
     *
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID
     * @param array $endpoint_data The endpoint data to save
     * @param string $old_endpoint_id The old endpoint ID if renaming
     * @return bool|string True on success, error message on failure
     */
    public static function save_endpoint($api_id, $endpoint_id, $endpoint_data, $old_endpoint_id = '') {
        // Sanitize inputs
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        $old_endpoint_id = sanitize_key($old_endpoint_id);
        $endpoint_data = is_array($endpoint_data) ? $endpoint_data : array();
        
        // Get the full API data
        $api = self::get_api($api_id);
        
        if (!$api) {
            return 'API not found.';
        }
        
        // Validate endpoint data
        if (empty($endpoint_data['name']) || !isset($endpoint_data['path'])) {
            return 'Endpoint name and path are required.';
        }
        
        // Sanitize endpoint data
        $endpoint_data['name'] = sanitize_text_field($endpoint_data['name']);
        $endpoint_data['path'] = sanitize_text_field($endpoint_data['path']);
        
        // Set active state (default to true if not set)
        if (!isset($endpoint_data['active'])) {
            $endpoint_data['active'] = true;
        } else {
            $endpoint_data['active'] = (bool) $endpoint_data['active'];
        }
        
        // Initialize endpoints array if it doesn't exist
        if (!isset($api['endpoints'])) {
            $api['endpoints'] = array();
        }
        
        // If changing endpoint ID, check if new ID exists
        if (!empty($old_endpoint_id) && $endpoint_id !== $old_endpoint_id && isset($api['endpoints'][$endpoint_id])) {
            return 'An endpoint with this ID already exists.';
        }
        
        // If renaming endpoint, remove old entry
        if (!empty($old_endpoint_id) && $endpoint_id !== $old_endpoint_id && isset($api['endpoints'][$old_endpoint_id])) {
            unset($api['endpoints'][$old_endpoint_id]);
        }
        
        // Save the endpoint
        $api['endpoints'][$endpoint_id] = $endpoint_data;
        
        // Save the updated API
        return self::save_api($api, $api_id);
    }
    
    /**
     * Delete an endpoint from an API
     *
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID to delete
     * @return bool True on success, false on failure
     */
    public static function delete_endpoint($api_id, $endpoint_id) {
        // Sanitize inputs
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        
        // Get the full API data
        $api = self::get_api($api_id);
        
        if (!$api || !isset($api['endpoints']) || !isset($api['endpoints'][$endpoint_id])) {
            return false;
        }
        
        // Don't allow deleting the last endpoint
        if (count($api['endpoints']) <= 1) {
            return false;
        }
        
        // Remove the endpoint
        unset($api['endpoints'][$endpoint_id]);
        
        // Save the updated API
        return self::save_api($api, $api_id) === true;
    }
    
    /**
     * Duplicate an endpoint
     *
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID to duplicate
     * @return bool|string New endpoint ID on success, false on failure
     */
    public static function duplicate_endpoint($api_id, $endpoint_id) {
        // Sanitize inputs
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        
        // Get the endpoint data
        $endpoint = self::get_endpoint($api_id, $endpoint_id);
        
        if (!$endpoint) {
            return false;
        }
        
        // Get the API data
        $api = self::get_api($api_id);
        
        if (!$api) {
            return false;
        }
        
        // Create a new endpoint ID
        $base_name = $endpoint['name'];
        $new_endpoint_id = sanitize_key($endpoint_id . '_copy');
        
        // Make sure the new ID is unique
        $counter = 1;
        while (isset($api['endpoints'][$new_endpoint_id])) {
            $new_endpoint_id = sanitize_key($endpoint_id . '_copy_' . $counter);
            $counter++;
        }
        
        // Create a copy of the endpoint with a new name
        $new_endpoint = $endpoint;
        $new_endpoint['name'] = $base_name . ' (Copy)';
        
        // Add the new endpoint
        $api['endpoints'][$new_endpoint_id] = $new_endpoint;
        
        // Save the updated API
        $result = self::save_api($api, $api_id);
        
        return $result === true ? $new_endpoint_id : false;
    }
    
    /**
     * Delete an API
     *
     * @param string $api_id The API ID to delete
     * @return bool True on success, false on failure
     */
    public static function delete_api($api_id) {
        $api_id = sanitize_key($api_id);
        $apis = self::get_apis();
        
        if (!isset($apis[$api_id])) {
            return false;
        }
        
        unset($apis[$api_id]);
        return update_option('pricewise_manual_apis', $apis);
    }
    
    /**
     * Duplicate an API account
     *
     * @param string $api_id The API ID to duplicate
     * @return string|bool New API ID on success, false on failure
     */
    public static function duplicate_api($api_id) {
        // Sanitize input
        $api_id = sanitize_key($api_id);
        
        // Get the API data
        $api = self::get_api($api_id);
        
        if (!$api) {
            return false;
        }
        
        // Create a copy of the API data
        $new_api = $api;
        
        // Modify the name and ID for the duplicate
        $base_name = $api['name'];
        $base_id = $api['id_name'];
        
        $new_api['name'] = $base_name . ' (Copy)';
        $new_api_id = sanitize_key($base_id . '_copy');
        
        // Make sure the new ID is unique
        $counter = 1;
        $apis = self::get_apis();
        while (isset($apis[$new_api_id])) {
            $new_api_id = sanitize_key($base_id . '_copy_' . $counter);
            $counter++;
        }
        
        $new_api['id_name'] = $new_api_id;
        
        // Save the new API
        $result = self::save_api($new_api);
        
        return $result === true ? $new_api_id : false;
    }
}