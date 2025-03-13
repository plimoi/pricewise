<?php
/**
 * Response Processor Helper
 * 
 * Provides utility functions for processing API responses in different formats.
 * Handles standardized error handling, response formatting, and data extraction.
 *
 * @package PriceWise
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Error handler class for PriceWise plugin
 *
 * Provides standardized error logging and handling for API responses.
 */
class Pricewise_Response_Error_Handler {
    /**
     * Log an error
     *
     * @param string $code Error code
     * @param string $message Error message
     * @param array $context Additional context data
     * @return void
     */
    public static function log_error($code, $message, $context = array()) {
        // Log detailed error for debugging
        error_log('PriceWise Response Error (' . $code . '): ' . $message . ' - Context: ' . json_encode($context));
        
        // Also store in error log option for admin viewing
        $error_log = get_option('pricewise_error_log', array());
        
        // Limit log size
        if (count($error_log) > 100) {
            array_shift($error_log); // Remove oldest error
        }
        
        // Add new error
        $error_log[] = array(
            'code' => $code,
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql')
        );
        
        update_option('pricewise_error_log', $error_log);
    }
    
    /**
     * Handle WP_Error consistently
     *
     * @param WP_Error $wp_error The WordPress error object
     * @param array $context Additional context data
     * @return WP_Error The original error with possibly enhanced data
     */
    public static function handle_wp_error($wp_error, $context = array()) {
        if (!is_wp_error($wp_error)) {
            return $wp_error;
        }
        
        $code = $wp_error->get_error_code();
        $message = $wp_error->get_error_message();
        
        // Log the error
        self::log_error($code, $message, $context);
        
        // Return the original error
        return $wp_error;
    }
}

/**
 * Process API response based on selected format
 * 
 * Converts API response to appropriate format and handles errors.
 *
 * @param string $body    Response body
 * @param string $format  Response format (json, array, object, xml, html)
 * @return mixed|WP_Error Processed response or error
 */
function pricewise_process_api_response($body, $format = 'json') {
    $context = array(
        'format' => $format,
        'body_length' => strlen($body)
    );
    
    try {
        switch ($format) {
            case 'array':
                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = new WP_Error(
                        'json_decode_error', 
                        'Invalid JSON format: ' . json_last_error_msg(),
                        array('format' => 'array', 'error_code' => json_last_error())
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                return $data;
                
            case 'object':
                $data = json_decode($body);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = new WP_Error(
                        'json_decode_error', 
                        'Invalid JSON format: ' . json_last_error_msg(),
                        array('format' => 'object', 'error_code' => json_last_error())
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                return $data;
                
            case 'xml':
                // Check if SimpleXML is available
                if (!function_exists('simplexml_load_string')) {
                    $error = new WP_Error(
                        'xml_processing_unavailable', 
                        'SimpleXML is required for XML processing'
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                
                // Attempt to parse XML
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($body);
                
                if ($xml === false) {
                    $errors = libxml_get_errors();
                    libxml_clear_errors();
                    $error_msg = !empty($errors) ? $errors[0]->message : 'Unknown error';
                    
                    $error = new WP_Error(
                        'xml_parse_error', 
                        'XML parsing failed: ' . $error_msg,
                        array('errors' => $errors)
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                
                // Convert to array to maintain consistency with other formats
                return json_decode(json_encode($xml), true);
                
            case 'html':
                // For HTML responses, do more thorough validation
                // Check for common HTML elements rather than just any tag
                if (preg_match('/<(!DOCTYPE|html|head|body|div|p|h[1-6]|span|a)\b/i', $body)) {
                    return $body; // Return HTML content as is
                } else {
                    // Try to detect if it's actually JSON or XML mistakenly labeled as HTML
                    if (preg_match('/^\s*[\{\[]/', $body)) { // Starts with { or [ (likely JSON)
                        $json_data = json_decode($body);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $error = new WP_Error(
                                'html_invalid', 
                                'Response appears to be JSON, not HTML. Consider changing format to JSON.',
                                array('detected_format' => 'json')
                            );
                            Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                            return $error;
                        }
                    } elseif (preg_match('/^\s*<\?xml/i', $body)) { // Starts with <?xml (likely XML)
                        $error = new WP_Error(
                            'html_invalid', 
                            'Response appears to be XML, not HTML. Consider changing format to XML.',
                            array('detected_format' => 'xml')
                        );
                        Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                        return $error;
                    }
                    
                    $error = new WP_Error(
                        'html_invalid', 
                        'Response does not appear to contain valid HTML'
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                
            case 'json':
            default:
                // Validate it's proper JSON
                json_decode($body);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = new WP_Error(
                        'json_decode_error', 
                        'Invalid JSON format: ' . json_last_error_msg(),
                        array('format' => 'json', 'error_code' => json_last_error())
                    );
                    Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
                    return $error;
                }
                return $body; // Return raw JSON string
        }
    } catch (Exception $e) {
        $error = new WP_Error(
            'response_processing_exception',
            'Exception processing response: ' . $e->getMessage(),
            array(
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            )
        );
        Pricewise_Response_Error_Handler::handle_wp_error($error, $context);
        return $error;
    }
}

/**
 * Get formatted value from API response based on format
 * 
 * Extracts values from API responses using dot notation path, regardless of format.
 *
 * @param mixed  $data    Processed API response data
 * @param string $path    Dot notation path to value (e.g., 'data.hotels.0.name')
 * @param mixed  $default Default value if path not found
 * @param string $format  Format the data was processed in
 * @return mixed Value at path or default
 */
function pricewise_get_response_value($data, $path, $default = null, $format = 'array') {
    try {
        // For JSON string, convert to array first
        if ($format === 'json') {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Pricewise_Response_Error_Handler::log_error(
                    'json_decode_error',
                    'Invalid JSON format when extracting value: ' . json_last_error_msg(),
                    array('path' => $path, 'format' => 'json', 'error_code' => json_last_error())
                );
                return $default;
            }
            $format = 'array'; // Process as array from here
        }
        
        // If data is WP_Error, log and return default
        if (is_wp_error($data)) {
            Pricewise_Response_Error_Handler::handle_wp_error($data, array(
                'function' => 'pricewise_get_response_value',
                'path' => $path,
                'format' => $format
            ));
            return $default;
        }
        
        // Split path into parts
        $parts = explode('.', $path);
        
        // Navigate through path parts
        if ($format === 'array') {
            $current = $data;
            foreach ($parts as $part) {
                if (!is_array($current) || !isset($current[$part])) {
                    return $default;
                }
                $current = $current[$part];
            }
            return $current;
        } elseif ($format === 'object') {
            $current = $data;
            foreach ($parts as $part) {
                if (!is_object($current) || !isset($current->$part)) {
                    return $default;
                }
                $current = $current->$part;
            }
            return $current;
        } elseif ($format === 'xml') {
            // For XML data (converted to array), special handling may be needed
            // since the structure can be more complex
            $current = $data;
            foreach ($parts as $part) {
                if (!is_array($current) || !isset($current[$part])) {
                    // Try handling some common XML-to-array conversion quirks
                    if (is_array($current) && isset($current['@attributes']) && isset($current['@attributes'][$part])) {
                        return $current['@attributes'][$part];
                    }
                    return $default;
                }
                $current = $current[$part];
            }
            return $current;
        } elseif ($format === 'html') {
            // For HTML, we can't easily extract values using dot notation
            // Return the whole HTML or use a more specialized function for HTML parsing
            return $data;
        }
        
        return $default;
    } catch (Exception $e) {
        Pricewise_Response_Error_Handler::log_error(
            'get_response_value_exception',
            'Exception when extracting value from response: ' . $e->getMessage(),
            array(
                'path' => $path,
                'format' => $format,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            )
        );
        return $default;
    }
}

/**
 * Format API response for display or debugging
 * 
 * @param mixed  $data   Processed API data
 * @param string $format Format the data is in
 * @return string Formatted output
 */
function pricewise_format_response_for_display($data, $format = 'array') {
    try {
        if ($format === 'json') {
            // Pretty print JSON
            $json_data = json_decode($data);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($json_data, JSON_PRETTY_PRINT);
            }
            return $data; // Return as is if not valid JSON
        } elseif ($format === 'array' || $format === 'object') {
            // Convert to pretty JSON for display
            return json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($format === 'xml') {
            // Try to format XML if it's a string
            if (is_string($data)) {
                $dom = new DOMDocument('1.0');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                
                // Load the XML response
                @$dom->loadXML($data);
                return $dom->saveXML();
            } else {
                // If it's already processed, convert back to XML or to JSON
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        } elseif ($format === 'html') {
            // For HTML, just return as is or with minimal formatting
            if (is_string($data)) {
                // Return raw HTML with line breaks preserved
                return $data;
            } else {
                // If somehow we have a non-string, convert to string
                return is_array($data) || is_object($data) ? json_encode($data, JSON_PRETTY_PRINT) : (string)$data;
            }
        }
        
        // Default fallback
        return is_string($data) ? $data : print_r($data, true);
    } catch (Exception $e) {
        Pricewise_Response_Error_Handler::log_error(
            'format_response_exception',
            'Exception when formatting response for display: ' . $e->getMessage(),
            array(
                'format' => $format,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(), 
                'line' => $e->getLine()
            )
        );
        
        // Return fallback representation
        return is_string($data) ? $data : 'Error formatting data: ' . $e->getMessage();
    }
}

/**
 * Debug helper to print readable API response
 * 
 * @param mixed  $data   The API response data
 * @param string $format Format the data is in
 * @param bool   $exit   Whether to exit after printing
 * @return void
 */
function pricewise_debug_response($data, $format = 'array', $exit = true) {
    try {
        echo '<pre>';
        echo esc_html(pricewise_format_response_for_display($data, $format));
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    } catch (Exception $e) {
        // Handle exception to avoid breaking the page
        echo '<div class="error">Error debugging response: ' . esc_html($e->getMessage()) . '</div>';
        
        Pricewise_Response_Error_Handler::log_error(
            'debug_response_exception',
            'Exception in debug response: ' . $e->getMessage(),
            array(
                'format' => $format,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            )
        );
        
        if ($exit) {
            exit;
        }
    }
}

/**
 * Determine the response format based on Content-Type header
 *
 * @param string $content_type Content-Type header value
 * @return string Format identifier (json, xml, html, etc.)
 */
function pricewise_determine_response_format($content_type) {
    $content_type = strtolower($content_type);
    
    if (strpos($content_type, 'application/json') !== false) {
        return 'json';
    } elseif (strpos($content_type, 'text/html') !== false) {
        return 'html';
    } elseif (strpos($content_type, 'application/xml') !== false || 
              strpos($content_type, 'text/xml') !== false) {
        return 'xml';
    } elseif (strpos($content_type, 'text/plain') !== false) {
        // For text/plain, try to guess if it's JSON
        return 'text';
    }
    
    return 'unknown';
}