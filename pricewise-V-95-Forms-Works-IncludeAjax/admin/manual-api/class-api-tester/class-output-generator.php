<?php
/**
 * API Tester Output Generator Class
 * Handles generating HTML output for test results.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API test output generation
 */
class Pricewise_API_Output_Generator {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Generate output for request information
     * 
     * @param array $request_data Request data
     * @return string HTML output
     */
    public function generate_request_info_output($request_data) {
        $output = '';
        
        // Request method and URL
        $output .= '<p><strong>Test Query:</strong> ' . esc_html($request_data['test_query']) . '</p>';
        $output .= '<p><strong>Endpoint URL:</strong> ' . esc_html($request_data['url']) . '</p>';
        $output .= '<p><strong>Request Method:</strong> ' . esc_html($request_data['method']) . '</p>';
        $output .= '<p><strong>Response Format:</strong> ' . esc_html($request_data['response_format']) . '</p>';
        $output .= '<p><strong>Request Timeout:</strong> ' . esc_html($request_data['timeout']) . ' seconds</p>';
        
        // Display cache duration
        if (isset($request_data['cache_duration'])) {
            $output .= '<p><strong>Cache Duration:</strong> ' . esc_html($request_data['cache_duration']) . ' seconds</p>';
        }
        
        // Request headers
        if (!empty($request_data['headers'])) {
            $output .= '<p><strong>Request Headers:</strong></p>';
            $output .= '<ul>';
            foreach ($request_data['headers'] as $header_name => $header_value) {
                $output .= '<li>' . esc_html($header_name) . ': ' . esc_html($header_value) . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p><strong>Request Headers:</strong> <em>Headers disabled</em></p>';
        }
        
        // Request parameters
        if (!empty($request_data['parameters'])) {
            $output .= '<p><strong>Request Parameters:</strong></p>';
            $output .= '<ul>';
            foreach ($request_data['parameters'] as $param_name => $param_value) {
                $output .= '<li>' . esc_html($param_name) . ': ' . esc_html($param_value) . '</li>';
            }
            $output .= '</ul>';
        }
        
        // Request body
        if ($request_data['body_enabled'] && !empty($request_data['body_content'])) {
            $output .= '<p><strong>Request Body:</strong> <em>(' . esc_html($request_data['body_type']) . ')</em></p>';
            $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">' . esc_html($request_data['body_content']) . '</pre>';
        }
        
        return $output;
    }
    
    /**
     * Generate output for response status and configured headers
     * 
     * @param int $status_code HTTP status code
     * @param array $important_headers Extracted important headers
     * @return string HTML output
     */
    public function generate_response_status_output($status_code, $important_headers) {
        $output = '<p><strong>Status Code:</strong> ' . esc_html($status_code) . '</p>';
        
        // Display success or error message based on status code
        if ($status_code === 200) {
            $output .= '<p style="color: green; font-weight: bold;">✓ Successfully connected to API</p>';
        } else {
            $status_meaning = $this->get_status_code_meaning($status_code);
            $output .= '<p style="color: red; font-weight: bold;">Error: Received status code ' . esc_html($status_code) . 
                       ($status_meaning ? ' - ' . esc_html($status_meaning) : '') . '</p>';
        }
        
        // Note: The "Response Headers (Configured)" section has been removed
        // The important_headers parameter is still received and processed for test history functionality
        // but no longer displayed directly in the test results output
        
        return $output;
    }
    
    /**
     * Get human-readable meaning for common HTTP status codes
     * 
     * @param int $status_code HTTP status code
     * @return string|null Human-readable meaning or null if not found
     */
    private function get_status_code_meaning($status_code) {
        $status_messages = array(
            // 2xx Success
            200 => 'OK - Request succeeded',
            201 => 'Created - Resource was successfully created',
            204 => 'No Content - Request succeeded with no response body',
            
            // 3xx Redirection
            301 => 'Moved Permanently - Resource has been moved permanently',
            302 => 'Found - Resource is temporarily located at a different URL',
            304 => 'Not Modified - Resource hasn\'t changed since last request',
            
            // 4xx Client Errors
            400 => 'Bad Request - The server could not understand the request',
            401 => 'Unauthorized - Authentication is required and has failed',
            403 => 'Forbidden - Server understood but refuses to authorize the request',
            404 => 'Not Found - The requested resource could not be found',
            405 => 'Method Not Allowed - Request method is not supported for this resource',
            408 => 'Request Timeout - Server timed out waiting for the request',
            429 => 'Too Many Requests - You have sent too many requests in a given time',
            
            // 5xx Server Errors
            500 => 'Internal Server Error - The server encountered an unexpected condition',
            502 => 'Bad Gateway - The server received an invalid response from upstream',
            503 => 'Service Unavailable - The server is currently unavailable',
            504 => 'Gateway Timeout - The server timed out waiting for another server'
        );
        
        return isset($status_messages[$status_code]) ? $status_messages[$status_code] : null;
    }
    
    /**
     * Generate output for format mismatch warning if needed
     * 
     * @param string $response_format Selected response format
     * @param string $actual_format Actual format detected
     * @return string HTML output or empty string if no mismatch
     */
    public function generate_format_mismatch_warning($response_format, $actual_format) {
        if ($response_format === 'html' && $actual_format !== 'html') {
            $output = '<div style="margin: 15px 0; padding: 10px; background-color: #fff8e5; border-left: 4px solid #ffb900;">';
            $output .= '<h4 style="margin-top: 0;">Warning: Format Mismatch</h4>';
            $output .= '<p>You selected <strong>HTML</strong> as the response format, but the API returned <strong>' . esc_html(strtoupper($actual_format)) . '</strong> content.</p>';
            $output .= '<p>For better results, consider changing the Response Format to <strong>' . esc_html(strtoupper($actual_format)) . '</strong> in your endpoint settings.</p>';
            $output .= '</div>';
            return $output;
        }
        return '';
    }
    
    /**
     * Generate output for response body based on format
     * 
     * @param string $body Response body
     * @param string $format Response format
     * @param mixed $processed_response Processed response
     * @param string $content_type Content type
     * @return string HTML output
     */
    public function generate_response_body_output($body, $format, $processed_response, $content_type) {
        $output = '';
        
        if (is_wp_error($processed_response)) {
            $error_message = $processed_response->get_error_message();
            $error_code = $processed_response->get_error_code();
            
            $output .= '<div style="margin: 15px 0; padding: 10px; background-color: #ffeaea; border-left: 4px solid #dc3232;">';
            $output .= '<h4 style="margin-top: 0;">Response Processing Error</h4>';
            $output .= '<p><strong>Error:</strong> ' . esc_html($error_message) . '</p>';
            
            $suggestion = $this->get_processing_error_suggestion($error_code, $format);
            if (!empty($suggestion)) {
                $output .= '<p><strong>Suggestion:</strong> ' . esc_html($suggestion) . '</p>';
            }
            
            $output .= '</div>';
            
            $output .= '<p><strong>Raw Response Preview:</strong></p>';
            $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html(substr($body, 0, 500)) . (strlen($body) > 500 ? '...' : '') . '</pre>';
        } else {
            $is_valid_format = !is_wp_error($processed_response);
            $format_status = $is_valid_format ? 'Valid' : 'Invalid';
            $output .= '<p><strong>Response Format:</strong> ' . esc_html($format_status . ' ' . $format) . '</p>';
            
            if ($format === 'array' || $format === 'object') {
                $output .= $this->generate_array_object_output($processed_response);
            } elseif ($format === 'xml') {
                $output .= $this->generate_xml_output($body);
            } elseif ($format === 'html') {
                $output .= $this->generate_html_output($body);
            } else {
                $output .= $this->generate_json_output($body);
            }
        }
        
        return $output;
    }
    
    /**
     * Get suggestion for processing error based on error code
     * 
     * @param string $error_code Error code
     * @param string $format Response format
     * @return string Suggestion
     */
    private function get_processing_error_suggestion($error_code, $format) {
        $suggestions = array(
            'json_decode_error' => 'The response isn\'t valid JSON. Check that your endpoint returns proper JSON or try a different response format.',
            'xml_processing_unavailable' => 'Your server doesn\'t have SimpleXML enabled. Contact your hosting provider or choose a different response format.',
            'xml_parse_error' => 'The response isn\'t valid XML. Check that your endpoint returns proper XML or try a different response format.',
            'html_invalid' => 'The response doesn\'t appear to be HTML. Try setting the response format to JSON or check if you\'re using the correct endpoint URL.',
            'api_response_invalid' => 'The API response format doesn\'t match what was expected. Try changing the response format setting to match what the API is actually returning.'
        );
        
        if (isset($suggestions[$error_code])) {
            return $suggestions[$error_code];
        }
        
        // Default suggestions based on format
        $default_suggestions = array(
            'json' => 'The response couldn\'t be processed as JSON. Verify that your API returns valid JSON data or try a different response format.',
            'array' => 'The response couldn\'t be converted to a PHP array/object. Verify that your API returns valid JSON data.',
            'object' => 'The response couldn\'t be converted to a PHP array/object. Verify that your API returns valid JSON data.',
            'xml' => 'The response couldn\'t be processed as XML. Verify that your API returns valid XML data or try a different response format.',
            'html' => 'The response couldn\'t be processed as HTML. Verify that your API returns HTML or try a different response format.'
        );
        
        return isset($default_suggestions[$format]) ? 
            $default_suggestions[$format] : 
            'The API response couldn\'t be processed correctly. Try a different response format.';
    }
    
    /**
     * Generate output for array or object response
     * 
     * @param array|object $processed_response Processed response
     * @return string HTML output
     */
    private function generate_array_object_output($processed_response) {
        $output = '';
        $pretty_json = json_encode($processed_response, JSON_PRETTY_PRINT);
        
        if (is_array($processed_response) && isset($processed_response['data']) && is_array($processed_response['data'])) {
            $result_count = count($processed_response['data']);
            $output .= '<p><strong>Results Found:</strong> ' . esc_html($result_count) . '</p>';
            
            if ($result_count > 0) {
                $first_result = $processed_response['data'][0];
                $output .= '<p><strong>Sample Result:</strong></p>';
                $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">' . esc_html(json_encode($first_result, JSON_PRETTY_PRINT)) . '</pre>';
            }
        }
        
        $output .= '<p><strong>Full Processed Response:</strong></p>';
        $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html($pretty_json) . '</pre>';
        
        return $output;
    }
    
    /**
     * Generate output for XML response
     * 
     * @param string $body XML response body
     * @return string HTML output
     */
    private function generate_xml_output($body) {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        
        @$dom->loadXML($body);
        $formatted_xml = $dom->saveXML();
        
        $output = '<p><strong>XML Response:</strong></p>';
        $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html($formatted_xml) . '</pre>';
        
        return $output;
    }
    
    /**
     * Generate output for HTML response
     * 
     * @param string $body HTML response body
     * @return string HTML output
     */
    private function generate_html_output($body) {
        $output = '';
        
        $output .= '<p><strong>HTML Response (Source):</strong></p>';
        $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html(substr($body, 0, 1000)) . (strlen($body) > 1000 ? '...' : '') . '</pre>';
        
        $output .= '<p><strong>HTML Preview:</strong></p>';
        $output .= '<div style="border: 1px solid #ddd; padding: 10px; max-height: 300px; overflow: auto; background: white;">';
        
        $iframe_id = 'html-preview-' . mt_rand();
        $output .= '<iframe id="' . esc_attr($iframe_id) . '" style="width: 100%; height: 300px; border: none;" sandbox="allow-same-origin"></iframe>';
        $output .= '<script>
            (function() {
                var iframe = document.getElementById("' . esc_js($iframe_id) . '");
                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write(' . wp_json_encode($body) . ');
                doc.close();
            })();
        </script>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Generate output for JSON response
     * 
     * @param string $body JSON response body
     * @return string HTML output
     */
    private function generate_json_output($body) {
        $output = '';
        
        $json_data = json_decode($body);
        if (json_last_error() === JSON_ERROR_NONE) {
            $pretty_json = json_encode($json_data, JSON_PRETTY_PRINT);
            $output .= '<p><strong>Full Response:</strong></p>';
            $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html($pretty_json) . '</pre>';
        } else {
            $output .= '<p><strong>Raw Response:</strong></p>';
            $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">' . esc_html(substr($body, 0, 1000)) . (strlen($body) > 1000 ? '...' : '') . '</pre>';
        }
        
        return $output;
    }
    
    /**
     * Generate output for all response headers
     * 
     * @param array|object $response_headers Response headers
     * @return string HTML output
     */
    public function generate_all_headers_output($response_headers) {
        $output = '<p><strong>Response Headers:</strong></p>';
        $output .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 300px;">';
        
        if (is_array($response_headers) || is_object($response_headers)) {
            foreach ($response_headers as $header => $value) {
                if (is_array($value)) {
                    foreach ($value as $single_value) {
                        $output .= esc_html($header) . ': ' . esc_html($single_value) . "\n";
                    }
                } else {
                    $output .= esc_html($header) . ': ' . esc_html($value) . "\n";
                }
            }
        } else {
            $output .= esc_html($response_headers);
        }
        
        $output .= '</pre>';
        
        return $output;
    }
    
    /**
     * Generate help information based on HTTP status code
     * 
     * @param int $status_code The HTTP status code
     * @param string $request_method The HTTP method used (GET, POST, etc.)
     * @return string HTML help message
     */
    public function get_status_code_help($status_code, $request_method = 'GET') {
        $output = '<div style="margin-top: 10px; padding: 15px; background: #fff8e5; border-left: 4px solid #ffb900;">';
        $output .= '<h4 style="margin-top: 0; margin-bottom: 10px;">Troubleshooting Guide</h4>';
        
        switch ($status_code) {
            case 400:
                $output .= '<p><strong>Bad Request (400)</strong> - The server couldn\'t understand your request due to invalid syntax.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Check your request parameters for errors or special characters that need to be URL encoded</li>';
                $output .= '<li>Verify the API documentation for the correct format of query parameters</li>';
                $output .= '<li>If using a request body, ensure it\'s formatted correctly according to the API requirements</li>';
                $output .= '<li>Try removing parameters one by one to identify which one might be causing the issue</li>';
                $output .= '</ul>';
                break;
                
            case 401:
                $output .= '<p><strong>Unauthorized (401)</strong> - Authentication is required and has failed or has not been provided.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Verify your API key is correct - check for typos or leading/trailing whitespace</li>';
                $output .= '<li>Ensure your API key is being sent in the correct header format (check API documentation)</li>';
                $output .= '<li>Check if your API key has expired or been revoked</li>';
                $output .= '<li>Try regenerating a new API key if your provider allows it</li>';
                $output .= '<li>Some APIs require specific additional headers like "Origin" or "Referer" - check the documentation</li>';
                $output .= '</ul>';
                break;
                
            case 403:
                $output .= '<p><strong>Forbidden (403)</strong> - The server understood the request but refuses to authorize it.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Verify your account has access to this specific API endpoint</li>';
                $output .= '<li>Check if your API subscription level includes access to this resource</li>';
                $output .= '<li>Some APIs restrict access based on IP address - verify your server\'s IP is allowed</li>';
                $output .= '<li>Check if you\'ve exceeded your API plan\'s usage limits or quotas</li>';
                $output .= '<li>Look for any geographic restrictions that might apply to your account</li>';
                $output .= '</ul>';
                break;
                
            case 404:
                $output .= '<p><strong>Not Found (404)</strong> - The server can\'t find the requested resource.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Double-check the endpoint URL for typos or incorrect path segments</li>';
                $output .= '<li>Verify that you\'re using the correct API version in your URL</li>';
                $output .= '<li>Check if the resource ID or parameters used in the URL are valid</li>';
                if ($request_method !== 'GET') {
                    $output .= '<li><strong>Try changing the request method to GET</strong> - This endpoint might not support ' . esc_html($request_method) . '</li>';
                }
                $output .= '<li>Review the API documentation for the exact path format</li>';
                $output .= '<li>Some APIs use query parameters instead of path components - check the documentation</li>';
                $output .= '</ul>';
                break;
                
            case 405:
                $output .= '<p><strong>Method Not Allowed (405)</strong> - The API doesn\'t support the HTTP method you\'re using for this endpoint.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Change your request method from <strong>' . esc_html($request_method) . '</strong> to ';
                if ($request_method === 'GET') {
                    $output .= '<strong>POST</strong> - Many APIs require POST for data submission</li>';
                } else {
                    $output .= '<strong>GET</strong> - This endpoint may only support read operations</li>';
                }
                $output .= '<li>Check the API documentation for which HTTP methods are allowed for this endpoint</li>';
                $output .= '<li>Some APIs use different endpoints for different operations (GET vs POST)</li>';
                $output .= '<li>If sending data, check if it should be in the request body or as query parameters</li>';
                $output .= '</ul>';
                break;
                
            case 408:
                $output .= '<p><strong>Request Timeout (408)</strong> - The server timed out waiting for the request to complete.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Increase the request timeout value in your endpoint settings (currently set to ' . esc_html($this->get_timeout()) . ' seconds)</li>';
                $output .= '<li>Check if the API server is experiencing high load or performance issues</li>';
                $output .= '<li>Simplify your request by reducing the amount of data or parameters</li>';
                $output .= '<li>Check your network connection if you\'re testing from a slow or unstable connection</li>';
                $output .= '<li>Consider implementing retry logic for important operations</li>';
                $output .= '</ul>';
                break;
                
            case 415:
                $output .= '<p><strong>Unsupported Media Type (415)</strong> - The API doesn\'t support the content type you\'re sending.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Check your Content-Type header - it may need to be "application/json", "application/x-www-form-urlencoded", etc.</li>';
                $output .= '<li>Verify the format of your request body matches the Content-Type header you\'re sending</li>';
                $output .= '<li>Some APIs only accept specific formats - check the documentation</li>';
                $output .= '<li>Try changing the body type in your endpoint settings</li>';
                $output .= '<li>Consider using query parameters instead of a request body if possible</li>';
                $output .= '</ul>';
                break;
                
            case 429:
                $output .= '<p><strong>Too Many Requests (429)</strong> - You\'ve exceeded the API\'s rate limits.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Wait before trying again - most APIs have a per-minute, per-hour, or per-day limit</li>';
                $output .= '<li>Check the response headers for rate limit information (remaining requests, reset time)</li>';
                $output .= '<li>Implement caching to reduce the number of API calls you need to make</li>';
                $output .= '<li>Consider upgrading your API subscription plan if you consistently hit rate limits</li>';
                $output .= '<li>Optimize your code to make fewer API calls by batching requests or filtering data locally</li>';
                $output .= '</ul>';
                break;
                
            case 500:
            case 502:
            case 503:
            case 504:
                $output .= '<p><strong>Server Error (' . $status_code . ')</strong> - The API service is experiencing issues.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">Possible Solutions:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>This is likely an issue on the API provider\'s side, not with your configuration</li>';
                $output .= '<li>Wait and try your request again later</li>';
                $output .= '<li>Check the API provider\'s status page or Twitter account for service announcements</li>';
                $output .= '<li>If the problem persists, contact the API provider\'s support team</li>';
                $output .= '<li>Consider implementing fallback behavior in your application for when the API is unavailable</li>';
                $output .= '</ul>';
                break;
                
            default:
                $output .= '<p><strong>Status Code ' . $status_code . '</strong> - Unexpected response from the API.</p>';
                $output .= '<h5 style="margin-bottom: 5px;">General Troubleshooting Steps:</h5>';
                $output .= '<ul style="margin-top: 5px; margin-left: 20px;">';
                $output .= '<li>Check the API documentation for information about this specific status code</li>';
                $output .= '<li>Verify all your request parameters and headers meet the API requirements</li>';
                $output .= '<li>Try simplifying your request to identify which component might be causing the issue</li>';
                $output .= '<li>Use a tool like Postman or cURL to test the API directly outside this plugin</li>';
                $output .= '<li>Contact the API provider\'s support if the issue persists</li>';
                $output .= '</ul>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Get the current request timeout setting
     * This is a helper method for the troubleshooting guide
     * 
     * @return int Timeout in seconds
     */
    private function get_timeout() {
        // Default WordPress timeout is 5 seconds
        $timeout = 5;
        
        if (defined('PRICEWISE_API_TIMEOUT')) {
            $timeout = PRICEWISE_API_TIMEOUT;
        }
        
        return $timeout;
    }
}