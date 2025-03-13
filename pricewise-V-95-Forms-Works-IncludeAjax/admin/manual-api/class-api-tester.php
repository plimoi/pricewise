<?php
/**
 * API Tester Class
 * Handles the API testing functionality.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load component classes
require_once dirname(__FILE__) . '/class-api-tester/class-request-processor.php';
require_once dirname(__FILE__) . '/class-api-tester/class-response-formatter.php';
require_once dirname(__FILE__) . '/class-api-tester/class-header-processor.php';
require_once dirname(__FILE__) . '/class-api-tester/class-output-generator.php';

/**
 * Class to handle API testing
 */
class Pricewise_API_Tester {
    
    /**
     * Request processor
     *
     * @var Pricewise_API_Request_Processor
     */
    private $request_processor;
    
    /**
     * Response formatter
     *
     * @var Pricewise_API_Response_Formatter
     */
    private $response_formatter;
    
    /**
     * Header processor
     *
     * @var Pricewise_API_Header_Processor
     */
    private $header_processor;
    
    /**
     * Output generator
     *
     * @var Pricewise_API_Output_Generator
     */
    private $output_generator;
    
    /**
     * Test history logger
     *
     * @var Pricewise_Test_History_Logger
     */
    private $history_logger;
    
    /**
     * Error handler instance
     *
     * @var Pricewise_API_Error_Handler
     */
    private $error_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->request_processor = new Pricewise_API_Request_Processor();
        $this->response_formatter = new Pricewise_API_Response_Formatter();
        $this->header_processor = new Pricewise_API_Header_Processor();
        $this->output_generator = new Pricewise_API_Output_Generator();
        
        // Load and initialize error handler
        require_once dirname(__FILE__) . '/class-api-tester/class-error-handler.php';
        $this->error_handler = new Pricewise_API_Error_Handler();
        
        // Initialize test history logger if available
        $logger_path = dirname(__FILE__) . '/test-history/class-test-history-logger.php';
        if (file_exists($logger_path)) {
            require_once $logger_path;
            $this->history_logger = new Pricewise_Test_History_Logger();
        }
    }
    
    /**
     * Test an API configuration
     * 
     * @param array $api The API configuration to test
     * @return string HTML output with test results
     */
    public function test_api( $api ) {
        $output = '<h3>Testing API: ' . esc_html( $api['name'] ) . '</h3>';
        
        // Validate API configuration before proceeding
        $validation_error = $this->validate_api_configuration($api);
        if ($validation_error) {
            return $validation_error;
        }
        
        // Check for multiple endpoints (new structure)
        if (isset($api['endpoints']) && !empty($api['endpoints'])) {
            $all_results = '';
            foreach ($api['endpoints'] as $endpoint_id => $endpoint) {
                try {
                    // Create a temporary API data with just this endpoint
                    $temp_api = $api;
                    
                    // Set the advanced_config based on this endpoint for backward compatibility
                    $temp_api['advanced_config'] = array(
                        'endpoint' => $endpoint['path']
                    );
                    
                    // If the endpoint has config, merge it
                    if (isset($endpoint['config'])) {
                        $temp_api['advanced_config'] = array_merge($temp_api['advanced_config'], $endpoint['config']);
                    }
                    
                    // Test this specific endpoint
                    $endpoint_result = $this->test_api_endpoint($temp_api, $endpoint['name']);
                    $all_results .= $endpoint_result;
                    
                    // Add a separator between endpoints
                    if (count($api['endpoints']) > 1) {
                        $all_results .= '<hr style="margin: 20px 0; border-top: 1px dashed #ccc;">';
                    }
                } catch (Exception $e) {
                    $all_results .= $this->error_handler->handle_exception(
                        $e,
                        'endpoint_test_error',
                        array(
                            'api' => $api['name'],
                            'endpoint' => $endpoint['name']
                        )
                    );
                }
            }
            
            return $output . $all_results;
        }
        
        // Legacy structure - single endpoint
        try {
            return $output . $this->test_api_endpoint($api);
        } catch (Exception $e) {
            return $output . $this->error_handler->handle_exception(
                $e,
                'api_test_error',
                array('api' => $api['name'])
            );
        }
    }
    
    /**
     * Validate API configuration before testing
     * 
     * @param array $api The API configuration to validate
     * @return string|null Error message HTML or null if valid
     */
    private function validate_api_configuration($api) {
        $output = '';
        
        // Check for required fields
        if (empty($api['api_key'])) {
            return $this->error_handler->handle_error(
                'missing_api_key',
                'API Key is missing',
                array('api' => $api['name']),
                $output
            );
        }
        
        if (empty($api['base_endpoint'])) {
            return $this->error_handler->handle_error(
                'missing_base_endpoint',
                'Base endpoint URL is missing',
                array('api' => $api['name']),
                $output
            );
        }
        
        // Validate base URL format
        if (!filter_var($api['base_endpoint'], FILTER_VALIDATE_URL)) {
            return $this->error_handler->handle_error(
                'invalid_base_url',
                'Base endpoint URL is not a valid URL format',
                array('api' => $api['name'], 'url' => $api['base_endpoint']),
                $output
            );
        }
        
        return null;
    }
    
    /**
     * Test API endpoint functionality
     * 
     * @param array $api The API configuration
     * @param string $endpoint_name Optional name to display for the endpoint
     * @return string HTML output with test results
     */
    private function test_api_endpoint( $api, $endpoint_name = null ) {
        // If endpoint name is provided, use it in the header
        if ($endpoint_name) {
            $output = '<h4>Testing Endpoint: ' . esc_html($endpoint_name) . '</h4>';
        } else {
            $output = '<h4>Testing API Endpoint</h4>';
        }
        
        try {
            // Start timing the request
            $start_time = microtime(true);
            
            // Build the request URL
            $url = $this->request_processor->build_request_url($api);
            if (empty($url)) {
                return $this->error_handler->handle_error(
                    'invalid_url',
                    'Failed to build a valid request URL',
                    array(
                        'api' => $api['name'],
                        'endpoint' => $endpoint_name,
                        'base_url' => isset($api['base_endpoint']) ? $api['base_endpoint'] : 'undefined',
                        'endpoint_path' => isset($api['advanced_config']['endpoint']) ? $api['advanced_config']['endpoint'] : 'undefined'
                    ),
                    $output
                );
            }
            
            // Build the request headers
            $headers = $this->request_processor->build_request_headers($api);
            
            // Build the request body
            $body_info = $this->request_processor->build_request_body($api);
            $request_method = $body_info['method'];
            $body_enabled = $body_info['body_enabled'];
            $body_type = $body_info['body_type'];
            $body_content = $body_info['body_content'];
            
            // Prepare the request args
            $args = array(
                'headers' => $headers,
            );
            
            // Add custom timeout if configured
            if (isset($api['advanced_config']['request_timeout'])) {
                $args['timeout'] = intval($api['advanced_config']['request_timeout']);
            }
            
            // Merge in any body-related args
            $args = array_merge($args, $body_info['args']);
            
            // Include the API configuration in the args for access in the request processor
            $args['api'] = $api;
            
            // Get response format from config
            $response_format = isset($api['advanced_config']['response_format']) ? $api['advanced_config']['response_format'] : 'json';
            
            // Get configured headers to track
            $configured_headers = array();
            if (isset($api['advanced_config']) && 
                isset($api['advanced_config']['response_headers'])) {
                foreach ($api['advanced_config']['response_headers'] as $header) {
                    if (!empty($header['name'])) {
                        $configured_headers[] = strtolower($header['name']);
                    }
                }
            }
            
            // Get cache duration from config
            $cache_duration = isset($api['advanced_config']['cache_duration']) ? intval($api['advanced_config']['cache_duration']) : 3600;
            
            // Generate request info output
            $test_query = 'Rome';
            
            // Get parameters from the API configuration to include in the test
            $parameters = array('query' => $test_query);
            if (isset($api['advanced_config']['params']) && is_array($api['advanced_config']['params'])) {
                foreach ($api['advanced_config']['params'] as $key => $param) {
                    if (!empty($param['name']) && isset($param['value'])) {
                        $parameters[$param['name']] = $param['value'];
                        
                        // If the "save" flag is checked in the UI, make sure it's set in the config
                        // This ensures parameters with "Save" checkbox checked are saved to test history
                        if (isset($param['save']) && $param['save']) {
                            $api['advanced_config']['save_test_params'] = true;
                        }
                    }
                }
            }
            
            $request_data = array(
                'test_query' => $test_query,
                'url' => $url,
                'method' => $request_method,
                'response_format' => $response_format,
                'headers' => $this->header_processor->mask_sensitive_headers($headers),
                'parameters' => $parameters,
                'body_enabled' => $body_enabled,
                'body_type' => $body_type,
                'body_content' => $body_content,
                'timeout' => isset($args['timeout']) ? $args['timeout'] : 30,
                'cache_duration' => $cache_duration, // Include cache duration
            );
            
            $output .= $this->output_generator->generate_request_info_output($request_data);
            
            // Make the request
            $response = $this->request_processor->execute_request($url, $args);
            
            // Calculate response time
            $response_time = microtime(true) - $start_time;
            
            // Log test results if history logger is available
            if ($this->history_logger) {
                // Prepare request data for logging
                $log_request = array(
                    'headers' => $headers,
                    'params' => $parameters, // Use the configured parameters
                    'method' => $request_method,
                    'body' => $body_enabled ? $body_content : ''
                );
                
                // Process and verify all parameters with save flags are marked for saving
                if (isset($api['advanced_config']['params']) && is_array($api['advanced_config']['params'])) {
                    // First, collect all parameters that should be saved
                    $params_to_save = array();
                    foreach ($api['advanced_config']['params'] as $index => $param) {
                        if (isset($param['save']) && $param['save'] && isset($param['name'])) {
                            $params_to_save[] = $param['name'];
                        }
                    }
                    
                    // If we have any parameters to save, ensure they're all in the request params
                    if (!empty($params_to_save)) {
                        $api['advanced_config']['save_test_params'] = true;
                        
                        // Make sure all parameters are in the request with proper save flags
                        foreach ($params_to_save as $param_name) {
                            if (isset($parameters[$param_name])) {
                                $log_request['params'][$param_name] = $parameters[$param_name];
                            }
                        }
                    }
                }
                
                // Get endpoint path for logging
                $endpoint = isset($api['advanced_config']['endpoint']) ? $api['advanced_config']['endpoint'] : '';
                
                // Log the test
                $this->history_logger->log_test($api, $endpoint, $log_request, $response, $response_time);
            }
            
            // Handle request errors
            if (is_wp_error($response)) {
                $error_code = $response->get_error_code();
                $error_message = $response->get_error_message();
                $error_data = $response->get_error_data();
                
                // Improve error message based on the error code
                $user_friendly_message = $this->get_user_friendly_wp_error_message($error_code, $error_message);
                
                // Create a new WP_Error with the improved message
                $improved_error = new WP_Error(
                    $error_code,
                    $user_friendly_message,
                    $error_data
                );
                
                return $output . $this->error_handler->handle_wp_error(
                    $improved_error,
                    'api_request_failed',
                    array(
                        'api' => $api['name'],
                        'endpoint' => $endpoint_name,
                        'url' => $url,
                        'method' => $request_method,
                        'original_message' => $error_message
                    )
                );
            }
            
            // Process response
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            
            // Extract configured headers
            $important_headers = $this->header_processor->extract_configured_headers($response_headers, $configured_headers);
            
            // Generate status output
            $output .= $this->output_generator->generate_response_status_output($status_code, $important_headers);
            
            // Display response time
            $output .= '<p><strong>Response Time:</strong> ' . round($response_time, 3) . ' seconds</p>';
            
            // If status is not 200, display error and return
            if ($status_code !== 200) {
                $output .= '<p><strong>Response Body:</strong></p>';
                $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html(substr($body, 0, 500)) . (strlen($body) > 500 ? '...' : '') . '</pre>';
                
                // Add troubleshooting information based on status code
                $output .= $this->output_generator->get_status_code_help($status_code, $request_method);
                
                // Log this non-200 response
                $this->error_handler->log_error(
                    'non_200_response',
                    'API returned non-200 status code: ' . $status_code,
                    array(
                        'api' => $api['name'],
                        'endpoint' => $endpoint_name,
                        'status_code' => $status_code,
                        'url' => $url,
                        'method' => $request_method
                    )
                );
                
                return $output;
            }
            
            // Check the actual content type from response headers
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            $actual_format = $this->response_formatter->determine_content_type($content_type);
            
            // If HTML was selected but the response is not HTML, warn the user
            $output .= $this->output_generator->generate_format_mismatch_warning($response_format, $actual_format);
            
            // Process the response based on the selected format
            $processed_response = $this->response_formatter->process_api_response($body, $response_format, $content_type);
            
            // Check if there was an error in processing
            if (is_wp_error($processed_response)) {
                $this->error_handler->log_wp_error(
                    $processed_response,
                    'response_processing_error',
                    array(
                        'api' => $api['name'],
                        'endpoint' => $endpoint_name,
                        'format' => $response_format,
                        'content_type' => $content_type
                    )
                );
            }
            
            // Generate response body output
            $output .= $this->output_generator->generate_response_body_output($body, $response_format, $processed_response, $content_type);
            
            // Display all response headers
            $output .= $this->output_generator->generate_all_headers_output($response_headers);
            
            return $output;
            
        } catch (Exception $e) {
            return $output . $this->error_handler->handle_exception(
                $e,
                'endpoint_test_error',
                array(
                    'api' => $api['name'],
                    'endpoint' => $endpoint_name,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                )
            );
        }
    }
    
    /**
     * Get user-friendly error message for WP_Error objects
     * 
     * @param string $error_code The error code
     * @param string $original_message The original error message
     * @return string Improved user-friendly error message
     */
    private function get_user_friendly_wp_error_message($error_code, $original_message) {
        $messages = array(
            'http_request_failed' => 'Connection to the API server failed. This could be due to network issues or the API server being unavailable.',
            'http_404' => 'The API endpoint URL could not be found (404 Not Found). Please verify the endpoint URL is correct.',
            'http_403' => 'Access to the API is forbidden (403 Forbidden). Please check your API key and permissions.',
            'http_401' => 'Authentication with the API failed (401 Unauthorized). Please verify your API key is correct and active.',
            'http_request_not_executed' => 'The request to the API could not be executed. Please check your server\'s connectivity.',
            'http_request_timeout' => 'The request to the API timed out. Try increasing the timeout setting or try again later.',
            'ssl_certificate_error' => 'There was a problem with the SSL certificate. This may be due to an outdated certificate or security configuration on your server.',
            'invalid_response' => 'The API returned an invalid response that couldn\'t be processed correctly.',
            'invalid_url' => 'The API URL is not valid. Please check the base endpoint URL and endpoint path for proper formatting.'
        );
        
        // Return improved message if available, otherwise return the original
        return isset($messages[$error_code]) ? $messages[$error_code] : $original_message;
    }
}