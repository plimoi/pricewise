<?php
/**
 * API Tester Request Processor Class
 * Handles building and executing API requests.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API request processing
 */
class Pricewise_API_Request_Processor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }

    /**
     * Build the complete request URL with parameters
     * 
     * @param array $api The API configuration
     * @param array $params Additional parameters to add to the URL
     * @return string Complete URL
     */
    public function build_request_url($api, $params = array()) {
        // Determine endpoint to use from advanced config
        $endpoint = $api['base_endpoint'];
        $endpoint_path = '';
        
        // First check the advanced_config['endpoint'] for backward compatibility
        if (isset($api['advanced_config']) && isset($api['advanced_config']['endpoint'])) {
            $endpoint_path = $api['advanced_config']['endpoint'];
        }
        
        // Build full URL - if path starts with / or http, handle appropriately
        $url = $endpoint;
        if (!empty($endpoint_path)) {
            if (strpos($endpoint_path, 'http') === 0) {
                $url = $endpoint_path; // Full URL provided in path
            } else {
                // Make sure we have exactly one slash between endpoint and path
                $url = rtrim($endpoint, '/') . '/' . ltrim($endpoint_path, '/');
            }
        }

        // Default test query parameter
        $query_params = array('query' => 'Rome');
        
        // Add parameters from API configuration
        if (isset($api['advanced_config']) && isset($api['advanced_config']['params']) && is_array($api['advanced_config']['params'])) {
            foreach ($api['advanced_config']['params'] as $key => $param) {
                if (!empty($param['name']) && isset($param['value'])) {
                    $query_params[$param['name']] = $param['value'];
                    
                    // Make sure each parameter with a 'save' checkbox checked is properly marked
                    if (isset($param['save']) && $param['save']) {
                        // Explicitly set the save flag to true for this parameter
                        $api['advanced_config']['params'][$key]['save'] = true;
                        
                        // Also set the global save_test_params flag to ensure parameters are saved
                        $api['advanced_config']['save_test_params'] = true;
                    }
                }
            }
        }

        // Add additional parameters passed to this method
        if (!empty($params)) {
            $query_params = array_merge($query_params, $params);
        }
        
        // Create the final URL with all parameters
        $url = add_query_arg($query_params, $url);
        
        return $url;
    }

    /**
     * Build request headers based on API configuration
     * 
     * @param array $api The API configuration
     * @return array Request headers
     */
    public function build_request_headers($api) {
        $headers = array();
        
        // Check if headers are disabled
        $disable_headers = false;
        if (isset($api['advanced_config']) && 
            isset($api['advanced_config']['auth']) && 
            isset($api['advanced_config']['auth']['disable_headers'])) {
            $disable_headers = (bool)$api['advanced_config']['auth']['disable_headers'];
        }
        
        if ($disable_headers) {
            return $headers;
        }
        
        // Set up auth headers based on advanced config
        if (isset($api['advanced_config']) && isset($api['advanced_config']['auth'])) {
            $auth_type = isset($api['advanced_config']['auth']['type']) ? $api['advanced_config']['auth']['type'] : 'api_key';
            
            // Get headers from the advanced config
            if (isset($api['advanced_config']['auth']['headers']) && is_array($api['advanced_config']['auth']['headers'])) {
                foreach ($api['advanced_config']['auth']['headers'] as $header) {
                    if (!empty($header['name'])) {
                        $header_name = $header['name'];
                        $header_value = isset($header['value']) ? $header['value'] : '';
                        

                        
                        $headers[$header_name] = $header_value;
                    }
                }
            }
        }
        
        // If no headers were set and headers aren't disabled, use default API key header
        if (empty($headers)) {
            $headers['X-API-Key'] = $api['api_key'];
        }
        
        return $headers;
    }

    /**
     * Build request body if needed
     * 
     * @param array $api The API configuration
     * @return array Request args containing body if enabled
     */
    public function build_request_body($api) {
        $request_args = array();
        
        // Determine request method - default to GET unless specified
        $request_method = 'GET';
        if (isset($api['advanced_config']) && isset($api['advanced_config']['method'])) {
            $request_method = strtoupper($api['advanced_config']['method']);
        }
        
        // Prepare request body if enabled
        $body_enabled = false;
        $body_type = 'json';
        $body_content = '';
        
        if (isset($api['advanced_config']) && 
            isset($api['advanced_config']['body']) && 
            isset($api['advanced_config']['body']['enabled'])) {
            
            $body_enabled = (bool)$api['advanced_config']['body']['enabled'];
            
            if ($body_enabled) {
                $body_type = isset($api['advanced_config']['body']['type']) ? 
                             $api['advanced_config']['body']['type'] : 'json';
                $body_content = isset($api['advanced_config']['body']['content']) ? 
                              $api['advanced_config']['body']['content'] : '';
                
                // Default to POST if body is enabled and no method specified
                if (!isset($api['advanced_config']['method'])) {
                    $request_method = 'POST';
                }
                
                // Add the body to the request
                if (!empty($body_content)) {
                    switch ($body_type) {
                        case 'json':
                            $request_args['headers']['Content-Type'] = 'application/json';
                            $request_args['body'] = $body_content;
                            break;
                        case 'form':
                            $request_args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                            $request_args['body'] = $body_content;
                            break;
                        case 'raw':
                            $request_args['body'] = $body_content;
                            break;
                    }
                }
            }
        }
        
        // Always set the method
        $request_args['method'] = $request_method;
        
        return array(
            'args' => $request_args,
            'method' => $request_method,
            'body_enabled' => $body_enabled,
            'body_type' => $body_type,
            'body_content' => $body_content
        );
    }

    /**
     * Execute an API request
     * 
     * @param string $url The URL to request
     * @param array $args Request arguments
     * @return array|WP_Error Response or error
     */
    public function execute_request($url, $args) {
        // Get custom timeout if set, otherwise use default
        $timeout = 30; // Default timeout
        
        if (isset($args['api']) && 
            isset($args['api']['advanced_config']) && 
            isset($args['api']['advanced_config']['request_timeout'])) {
            $timeout = intval($args['api']['advanced_config']['request_timeout']);
        } elseif (isset($args['timeout'])) {
            $timeout = $args['timeout'];
        }
        
        // Ensure timeout is within reasonable limits
        $timeout = max(1, min(120, $timeout));
        
        // Set the timeout in the request args
        $args['timeout'] = $timeout;
        
        // Remove api data from args to avoid sending it to the remote server
        if (isset($args['api'])) {
            unset($args['api']);
        }
        
        // Make the request - use wp_remote_request to handle different methods
        return wp_remote_request($url, $args);
    }
}