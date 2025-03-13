<?php
/**
 * API Error Handler Class
 * Provides consistent error handling across the API system.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API errors consistently
 */
class Pricewise_API_Error_Handler {
    
    /**
     * Maximum number of errors to store in the log
     *
     * @var int
     */
    private $max_log_entries = 100;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Handle errors consistently
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context data
     * @param string $output Existing output to append error message to
     * @return string HTML error output
     */
    public function handle_error($code, $message, $context = array(), $output = '') {
        // Log the error for debugging
        $this->log_error($code, $message, $context);
        
        // Generate user-friendly error message
        $error_output = '<p style="color: red; background: #fff0f0; padding: 10px; border-left: 4px solid #dc3232;">';
        $error_output .= '<strong>Error:</strong> ' . esc_html($message);
        
        // Add suggestions based on error code
        $suggestion = $this->get_error_suggestion($code, $context);
        if (!empty($suggestion)) {
            $error_output .= '<br><strong>Suggestion:</strong> ' . esc_html($suggestion);
        }
        
        $error_output .= '</p>';
        
        return $output . $error_output;
    }
    
    /**
     * Handle WordPress error objects consistently
     *
     * @param WP_Error $wp_error WordPress error object
     * @param string $code Error code for logging
     * @param array $context Additional context data
     * @param string $output Existing output to append error message to
     * @return string HTML error output
     */
    public function handle_wp_error($wp_error, $code, $context = array(), $output = '') {
        if (!is_wp_error($wp_error)) {
            return $output;
        }
        
        // Get error message from WP_Error
        $message = $wp_error->get_error_message();
        
        // Add WP_Error code to context
        $context['wp_error_code'] = $wp_error->get_error_code();
        $context['wp_error_data'] = $wp_error->get_error_data();
        
        // Log the error
        $this->log_error($code, $message, $context);
        
        // Generate user-friendly error message
        $error_output = '<p style="color: red; background: #fff0f0; padding: 10px; border-left: 4px solid #dc3232;">';
        $error_output .= '<strong>Error:</strong> ' . esc_html($message);
        
        // Add suggestions based on error code
        $suggestion = $this->get_error_suggestion($context['wp_error_code'] ?? $code, $context);
        if (!empty($suggestion)) {
            $error_output .= '<br><strong>Suggestion:</strong> ' . esc_html($suggestion);
        }
        
        $error_output .= '</p>';
        
        return $output . $error_output;
    }
    
    /**
     * Handle exceptions consistently
     *
     * @param Exception $exception The exception to handle
     * @param string $code Error code for logging
     * @param array $context Additional context data
     * @param string $output Existing output to append error message to
     * @return string HTML error output
     */
    public function handle_exception($exception, $code, $context = array(), $output = '') {
        $message = $exception->getMessage();
        
        // Add exception details to context
        $context['exception_file'] = $exception->getFile();
        $context['exception_line'] = $exception->getLine();
        $context['exception_trace'] = $exception->getTraceAsString();
        
        // Log the error
        $this->log_error($code, $message, $context);
        
        // Generate user-friendly error message
        $error_output = '<p style="color: red; background: #fff0f0; padding: 10px; border-left: 4px solid #dc3232;">';
        $error_output .= '<strong>Error:</strong> ' . esc_html($message);
        
        // Don't expose file/line details to end users, but do include a suggestion
        $suggestion = $this->get_error_suggestion($code, $context);
        if (!empty($suggestion)) {
            $error_output .= '<br><strong>Suggestion:</strong> ' . esc_html($suggestion);
        }
        
        $error_output .= '</p>';
        
        return $output . $error_output;
    }
    
    /**
     * Log an error for debugging purposes
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context data
     */
    public function log_error($code, $message, $context = array()) {
        // Log to PHP error log for immediate debugging
        error_log('PriceWise API Error (' . $code . '): ' . $message . ' - Context: ' . json_encode($context));
        
        // Also store in WordPress options for admin viewing
        $error_log = get_option('pricewise_api_error_log', array());
        
        // Limit log size
        if (count($error_log) >= $this->max_log_entries) {
            // Remove oldest entries to make room for new one
            $error_log = array_slice($error_log, -($this->max_log_entries - 1));
        }
        
        // Add new error with timestamp
        $error_log[] = array(
            'code' => $code,
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql')
        );
        
        update_option('pricewise_api_error_log', $error_log);
    }
    
    /**
     * Log a WordPress error
     *
     * @param WP_Error $wp_error WordPress error object
     * @param string $code Error code for logging
     * @param array $context Additional context data
     */
    public function log_wp_error($wp_error, $code, $context = array()) {
        if (!is_wp_error($wp_error)) {
            return;
        }
        
        $message = $wp_error->get_error_message();
        $context['wp_error_code'] = $wp_error->get_error_code();
        $context['wp_error_data'] = $wp_error->get_error_data();
        
        $this->log_error($code, $message, $context);
    }
    
    /**
     * Get a suggestion for fixing an error based on error code
     *
     * @param string $code Error code
     * @param array $context Error context data
     * @return string Suggestion text
     */
    private function get_error_suggestion($code, $context = array()) {
        $suggestions = array(
            // API configuration errors
            'missing_api_key' => 'Make sure you\'ve entered your API key in the API settings',
            'missing_base_endpoint' => 'Configure the API base endpoint URL in the API settings',
            'invalid_url' => 'Check the base endpoint URL and endpoint path for proper formatting',
            'invalid_url_format' => 'The URL format is invalid. Check for special characters or incorrect protocol',
            
            // Request errors
            'empty_request_url' => 'The request URL could not be constructed. Check your API configuration',
            'header_build_error' => 'Failed to build request headers. Check authentication configuration',
            'body_build_error' => 'Failed to build request body. Check body configuration and format',
            'invalid_json_body' => 'The JSON body is not valid. Check for syntax errors',
            'unknown_body_type' => 'The specified body type is not supported',
            'request_execution_error' => 'Failed to execute the request. Check connectivity and API configuration',
            
            // WordPress request errors
            'http_request_failed' => 'WordPress could not complete the HTTP request. Check your server\'s connectivity',
            'ssl_verification_failed' => 'SSL certificate verification failed. Check your server\'s SSL configuration',
            'request_timeout' => 'The request timed out. Try increasing the timeout setting',
            
            // Response processing errors
            'api_request_failed' => 'The API request failed. Check your API credentials and endpoint configuration',
            'response_processing_error' => 'Failed to process the API response. Response format may not match expected format',
            'json_decode_error' => 'Could not parse JSON response. The API may not be returning valid JSON',
            'xml_parse_error' => 'Could not parse XML response. The API may not be returning valid XML',
            'html_invalid' => 'The response does not appear to contain valid HTML',
            
            // Testing errors
            'endpoint_test_error' => 'An error occurred while testing the endpoint',
            'api_test_error' => 'An error occurred while testing the API',
            'non_200_response' => 'The API returned a non-success status code. Check the error details',
            
            // Default suggestion
            'default' => 'Check your API configuration and ensure the API service is available'
        );
        
        // Return specific suggestion or default
        return isset($suggestions[$code]) ? $suggestions[$code] : $suggestions['default'];
    }
    
    /**
     * Clear the error log
     *
     * @return bool Success or failure
     */
    public function clear_error_log() {
        return update_option('pricewise_api_error_log', array());
    }
    
    /**
     * Get the error log
     *
     * @param int $limit Maximum number of entries to return (0 for all)
     * @return array Error log entries
     */
    public function get_error_log($limit = 0) {
        $error_log = get_option('pricewise_api_error_log', array());
        
        // Sort by timestamp, newest first
        usort($error_log, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Limit results if requested
        if ($limit > 0 && count($error_log) > $limit) {
            $error_log = array_slice($error_log, 0, $limit);
        }
        
        return $error_log;
    }
}