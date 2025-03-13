<?php
/**
 * Test History Logger Class
 * Handles logging API test results.
 *
 * @package PriceWise
 * @subpackage TestHistory
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class for logging API test results to the database.
 *
 * @since 1.0.0
 */
class Pricewise_Test_History_Logger {
    /**
     * Database handler instance
     * @var Pricewise_Test_History_DB
     */
    private $db;

    /**
     * Default maximum snippet size in characters
     * @var int
     */
    private $default_max_size = 1000;

    /**
     * Maximum memory usage percentage threshold (0-1)
     * @var float
     */
    private $memory_threshold = 0.8;

    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(__FILE__) . '/class-test-history-db.php';
        $this->db = new Pricewise_Test_History_DB();
        
        $this->default_max_size = apply_filters('pricewise_test_history_max_snippet_size', $this->default_max_size);
        $this->memory_threshold = apply_filters('pricewise_test_history_memory_threshold', $this->memory_threshold);
    }

    /**
     * Log an API test result
     *
     * @param array $api API configuration
     * @param string $endpoint Endpoint being tested
     * @param array $request Request data
     * @param array|WP_Error $response Response data or error
     * @param float $response_time Response time in seconds
     * @return int|false Test record ID or false on failure
     */
    public function log_test($api, $endpoint, $request, $response, $response_time = 0) {
        // Don't log if no test history settings are enabled
        if (!$this->should_save_test_results($api)) {
            return false;
        }

        // Make sure the table exists
        if (!$this->db->table_exists()) {
            $this->db->create_table();
        }

        // Prepare test data
        $test_data = array(
            'api_id' => $api['id_name'],
            'api_name' => $api['name'],
            'endpoint' => $endpoint,
            'response_time' => $response_time
        );

        // Handle request data
        if ($this->should_save_headers($api) && isset($request['headers'])) {
            $headers_to_save = array();
            
            if (isset($api['advanced_config']['auth']['headers']) && is_array($api['advanced_config']['auth']['headers'])) {
                foreach ($api['advanced_config']['auth']['headers'] as $header) {
                    if (isset($header['save']) && $header['save'] && isset($header['name'])) {
                        $header_name = $header['name'];
                        if (isset($request['headers'][$header_name])) {
                            $headers_to_save[$header_name] = $request['headers'][$header_name];
                        }
                    }
                }
            }
            
            $test_data['request_headers'] = !empty($headers_to_save) ? 
                                           $this->format_headers($headers_to_save) : 
                                           $this->format_headers($request['headers']);
        }

        if ($this->should_save_params($api) && isset($request['params'])) {
            $params_to_save = array();
            
            if (isset($api['advanced_config']['params']) && is_array($api['advanced_config']['params'])) {
                foreach ($api['advanced_config']['params'] as $param) {
                    if (isset($param['save']) && $param['save'] && isset($param['name'])) {
                        $param_name = $param['name'];
                        if (isset($request['params'][$param_name])) {
                            $params_to_save[$param_name] = $request['params'][$param_name];
                        }
                    }
                }
            }
            
            $test_data['request_params'] = !empty($params_to_save) ? 
                                          $params_to_save : 
                                          $request['params'];
        }

        // Handle response data
        if (is_wp_error($response)) {
            $test_data['status_code'] = 0;
            $test_data['error_message'] = $response->get_error_message();
        } else {
            $test_data['status_code'] = wp_remote_retrieve_response_code($response);
            
            if ($this->should_save_response_headers($api)) {
                $headers = wp_remote_retrieve_headers($response);
                $headers_array = array();
                
                if (is_object($headers)) {
                    foreach ($headers as $key => $value) {
                        $headers_array[$key] = $value;
                    }
                } else {
                    $headers_array = $headers;
                }
                
                $response_headers_to_save = array();
                
                if (isset($api['advanced_config']['response_headers']) && is_array($api['advanced_config']['response_headers'])) {
                    foreach ($api['advanced_config']['response_headers'] as $header) {
                        if (isset($header['save']) && $header['save'] && isset($header['name'])) {
                            $header_name = strtolower($header['name']);
                            foreach ($headers_array as $key => $value) {
                                if (strtolower($key) === $header_name) {
                                    $response_headers_to_save[$key] = $value;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                $test_data['response_headers'] = !empty($response_headers_to_save) ? 
                                               $response_headers_to_save : 
                                               $headers_array;
            }
            
            if ($this->should_save_response_body($api)) {
                $body = wp_remote_retrieve_body($response);
                $content_type = wp_remote_retrieve_header($response, 'content-type');
                $test_data['response_snippet'] = $this->get_response_snippet($body, $content_type);
            }
        }

        // Save to database
        $test_id = $this->db->save_test($test_data);
        
        // Run trigger processing if test was saved successfully
        if ($test_id) {
            /**
             * Hook that fires after a test is logged successfully
             * This is used by the trigger system to process triggers
             * 
             * @param int $test_id The ID of the test in the database
             * @param array $test_data The test data
             */
            do_action('pricewise_after_test_logged', $test_id, $test_data);
        }
        
        return $test_id;
    }

    /**
     * Check if test results should be saved for this API
     *
     * @param array $api API configuration
     * @return bool True if results should be saved
     */
    private function should_save_test_results($api) {
        return (
            $this->should_save_headers($api) ||
            $this->should_save_params($api) ||
            $this->should_save_response_headers($api) ||
            $this->should_save_response_body($api)
        );
    }

    /**
     * Check if request headers should be saved
     *
     * @param array $api API configuration
     * @return bool True if headers should be saved
     */
    private function should_save_headers($api) {
        if (isset($api['advanced_config']['save_test_headers']) && $api['advanced_config']['save_test_headers']) {
            return true;
        }
        
        if (isset($api['advanced_config']['auth']['headers']) && is_array($api['advanced_config']['auth']['headers'])) {
            foreach ($api['advanced_config']['auth']['headers'] as $header) {
                if (isset($header['save']) && $header['save']) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if request parameters should be saved
     *
     * @param array $api API configuration
     * @return bool True if parameters should be saved
     */
    private function should_save_params($api) {
        if (isset($api['advanced_config']['params']) && is_array($api['advanced_config']['params'])) {
            foreach ($api['advanced_config']['params'] as $param) {
                if (isset($param['save']) && $param['save']) {
                    return true;
                }
            }
        }
        
        if (isset($api['advanced_config']['save_test_params']) && $api['advanced_config']['save_test_params']) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if response headers should be saved
     *
     * @param array $api API configuration
     * @return bool True if response headers should be saved
     */
    private function should_save_response_headers($api) {
        if (isset($api['advanced_config']['save_test_response_headers']) && $api['advanced_config']['save_test_response_headers']) {
            return true;
        }
        
        if (isset($api['advanced_config']['response_headers']) && is_array($api['advanced_config']['response_headers'])) {
            foreach ($api['advanced_config']['response_headers'] as $header) {
                if (isset($header['save']) && $header['save']) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if response body should be saved
     *
     * @param array $api API configuration
     * @return bool True if response body should be saved
     */
    private function should_save_response_body($api) {
        return isset($api['advanced_config']['save_test_response_body']) && 
               $api['advanced_config']['save_test_response_body'];
    }

    /**
     * Format headers for storage, masking sensitive values
     *
     * @param array|object $headers Headers to format
     * @return array Formatted headers
     */
    private function format_headers($headers) {
        $formatted = array();

        if (is_object($headers)) {
            $headers_array = array();
            foreach ($headers as $key => $value) {
                $headers_array[$key] = $value;
            }
            $headers = $headers_array;
        }

        foreach ($headers as $key => $value) {
            $key_lower = strtolower($key);
            
            if (strpos($key_lower, 'key') !== false || 
                strpos($key_lower, 'token') !== false || 
                strpos($key_lower, 'auth') !== false || 
                strpos($key_lower, 'secret') !== false) {
                
                if (is_array($value)) {
                    $formatted[$key] = array_map(array($this, 'mask_sensitive_value'), $value);
                } else {
                    $formatted[$key] = $this->mask_sensitive_value($value);
                }
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Mask sensitive values
     *
     * @param string $value Value to mask
     * @return string Masked value
     */
    private function mask_sensitive_value($value) {
        $length = strlen($value);
        
        if ($length <= 8) {
            return '********';
        }
        
        return substr($value, 0, 4) . str_repeat('*', $length - 8) . substr($value, -4);
    }

    /**
     * Check current memory usage against threshold
     *
     * @return bool True if memory usage is below threshold
     */
    private function is_memory_available() {
        if (!function_exists('memory_get_usage') || !function_exists('ini_get')) {
            return true; // Can't check, assume it's available
        }
        
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return true; // No limit
        }
        
        $memory_limit = $this->convert_to_bytes($memory_limit);
        $current_usage = memory_get_usage(true);
        
        return ($current_usage / $memory_limit) < $this->memory_threshold;
    }
    
    /**
     * Convert PHP memory size string to bytes
     *
     * @param string $size_str Memory size string (e.g., '128M')
     * @return int Size in bytes
     */
    private function convert_to_bytes($size_str) {
        $size_str = trim($size_str);
        $unit = strtolower(substr($size_str, -1));
        $size = (int) substr($size_str, 0, -1);
        
        switch ($unit) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }

    /**
     * Get a snippet of the response body for storage
     *
     * @param string $body Response body
     * @param string $content_type Optional content type header
     * @return string Snippet
     */
    private function get_response_snippet($body, $content_type = '') {
        $max_length = apply_filters('pricewise_test_history_snippet_size', $this->default_max_size);
        
        if (strlen($body) <= $max_length) {
            return $body;
        }
        
        if (!$this->is_memory_available()) {
            return $this->get_safe_snippet($body, $max_length);
        }
        
        $content_type = strtolower($content_type);
        
        if (strpos($content_type, 'application/json') !== false || 
            strpos($content_type, 'json') !== false ||
            $this->appears_to_be_json($body)) {
            
            return $this->get_json_snippet($body, $max_length);
        }
        elseif (strpos($content_type, 'application/xml') !== false || 
                strpos($content_type, 'text/xml') !== false || 
                $this->appears_to_be_xml($body)) {
                
            return $this->get_xml_snippet($body, $max_length);
        }
        elseif (strpos($content_type, 'text/html') !== false || 
                $this->appears_to_be_html($body)) {
                
            return $this->get_html_snippet($body, $max_length);
        }
        
        return $this->get_safe_snippet($body, $max_length);
    }
    
    /**
     * Get a safe snippet of a response body with minimal processing
     */
    private function get_safe_snippet($body, $max_length) {
        return substr($body, 0, $max_length) . (strlen($body) > $max_length ? '...' : '');
    }
    
    /**
     * Check if content appears to be JSON
     */
    private function appears_to_be_json($body) {
        $trimmed = trim($body);
        return (
            ($trimmed[0] === '{' && substr($trimmed, -1) === '}') || 
            ($trimmed[0] === '[' && substr($trimmed, -1) === ']')
        );
    }
    
    /**
     * Check if content appears to be XML
     */
    private function appears_to_be_xml($body) {
        $trimmed = trim($body);
        return (
            strpos($trimmed, '<?xml') === 0 ||
            preg_match('/<[a-z0-9_:]+(\s+[a-z0-9_:]+=".*?")*\s*(\/)?>/', $trimmed)
        );
    }
    
    /**
     * Check if content appears to be HTML
     */
    private function appears_to_be_html($body) {
        $trimmed = trim($body);
        return (
            strpos($trimmed, '<!DOCTYPE html>') === 0 ||
            strpos($trimmed, '<html') !== false ||
            preg_match('/<(!DOCTYPE|html|head|body|div|p|h[1-6]|span|a)\b/i', $trimmed)
        );
    }
    
    /**
     * Get a snippet of a JSON response with intelligent truncation
     */
    private function get_json_snippet($body, $max_length) {
        if (strlen($body) > 1048576) { // 1MB
            return $this->extract_json_structure($body, $max_length);
        }
        
        $temp_json = json_decode($body);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->get_safe_snippet($body, $max_length);
        }
        
        try {
            if (is_object($temp_json) || is_array($temp_json)) {
                $simplified = $this->simplify_large_structure($temp_json);
                $result = json_encode($simplified, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                
                if (strlen($result) > $max_length) {
                    return substr($result, 0, $max_length) . '...';
                }
                
                return $result;
            }
        } catch (Exception $e) {
            error_log('PriceWise: Error processing JSON snippet: ' . $e->getMessage());
        }
        
        return $this->get_safe_snippet($body, $max_length);
    }
    
    /**
     * Extract basic JSON structure without full parsing
     */
    private function extract_json_structure($body, $max_length) {
        $trimmed = trim($body);
        $is_array = $trimmed[0] === '[';
        $result = '';
        
        if ($is_array) {
            preg_match_all('/(\{[^{}]*\})/', $trimmed, $matches, PREG_PATTERN_ORDER, 0, 3);
            if (!empty($matches[0])) {
                $elements = array_slice($matches[0], 0, 3);
                $result = "[\n  " . implode(",\n  ", $elements) . "\n  ...\n]";
            } else {
                $result = "[...]";
            }
        } else {
            preg_match_all('/"([^"]+)"\s*:\s*("[^"]*"|null|\d+|\{[^{}]*\}|\[[^\[\]]*\])/', $trimmed, $matches);
            if (!empty($matches[0])) {
                $properties = array_slice($matches[0], 0, 10);
                $result = "{\n  " . implode(",\n  ", $properties);
                if (count($matches[0]) > 10) {
                    $result .= ",\n  ...";
                }
                $result .= "\n}";
            } else {
                $result = "{...}";
            }
        }
        
        if (strlen($result) > $max_length) {
            return substr($result, 0, $max_length) . '...';
        }
        
        return $result;
    }
    
    /**
     * Simplify large data structures for better snippet generation
     */
    private function simplify_large_structure($data, $depth = 0, $max_depth = 3, $max_items = 5) {
        if ($depth >= $max_depth) {
            if (is_array($data)) {
                return count($data) > 0 ? "[... " . count($data) . " items]" : "[]";
            } elseif (is_object($data)) {
                return count((array)$data) > 0 ? "{... " . count((array)$data) . " properties}" : "{}";
            }
            return $data;
        }
        
        if (is_array($data)) {
            $result = array();
            $count = 0;
            
            foreach ($data as $key => $value) {
                if ($count >= $max_items) {
                    $remaining = count($data) - $max_items;
                    if ($remaining > 0) {
                        $result[] = "... " . $remaining . " more items";
                    }
                    break;
                }
                
                $result[$key] = $this->simplify_large_structure($value, $depth + 1, $max_depth, $max_items);
                $count++;
            }
            
            return $result;
        }
        elseif (is_object($data)) {
            $result = new stdClass();
            $count = 0;
            
            foreach ($data as $key => $value) {
                if ($count >= $max_items) {
                    $remaining = count((array)$data) - $max_items;
                    if ($remaining > 0) {
                        $result->{'...'} = $remaining . " more properties";
                    }
                    break;
                }
                
                $result->$key = $this->simplify_large_structure($value, $depth + 1, $max_depth, $max_items);
                $count++;
            }
            
            return $result;
        }
        
        return $data;
    }
    
    /**
     * Get a snippet of an XML response
     */
    private function get_xml_snippet($body, $max_length) {
        if (strlen($body) > 524288) { // 512KB
            return $this->extract_xml_structure($body, $max_length);
        }
        
        if (!function_exists('simplexml_load_string')) {
            return $this->get_safe_snippet($body, $max_length);
        }
        
        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            
            if ($xml === false) {
                libxml_clear_errors();
                return $this->get_safe_snippet($body, $max_length);
            }
            
            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            
            $formatted = $dom->saveXML();
            
            if (strlen($formatted) > $max_length) {
                return substr($formatted, 0, $max_length) . '...';
            }
            
            return $formatted;
            
        } catch (Exception $e) {
            error_log('PriceWise: Error processing XML snippet: ' . $e->getMessage());
            return $this->get_safe_snippet($body, $max_length);
        }
    }
    
    /**
     * Extract basic XML structure without full parsing
     */
    private function extract_xml_structure($body, $max_length) {
        preg_match('/<([a-z0-9_:]+)(\s+[^>]*)?>/i', $body, $root_match);
        if (empty($root_match)) {
            return $this->get_safe_snippet($body, $max_length);
        }
        
        $root_tag = $root_match[1];
        $root_attributes = isset($root_match[2]) ? $root_match[2] : '';
        
        preg_match_all('/<([a-z0-9_:]+)(\s+[^>]*)?>[^<]*(?:<\/\1>)?/i', $body, $children_matches, PREG_PATTERN_ORDER, strlen($root_match[0]));
        
        $result = "<?xml version=\"1.0\"?>\n<" . $root_tag . $root_attributes . ">\n";
        
        if (!empty($children_matches[0])) {
            $children = array_slice($children_matches[0], 0, 5);
            $result .= "  " . implode("\n  ", $children);
            
            if (count($children_matches[0]) > 5) {
                $result .= "\n  <!-- " . (count($children_matches[0]) - 5) . " more elements -->";
            }
        }
        
        $result .= "\n</" . $root_tag . ">";
        
        if (strlen($result) > $max_length) {
            return substr($result, 0, $max_length) . '...';
        }
        
        return $result;
    }
    
    /**
     * Get a snippet of an HTML response
     */
    private function get_html_snippet($body, $max_length) {
        if (strlen($body) > 524288) { // 512KB
            return $this->extract_html_structure($body, $max_length);
        }
        
        $title = '';
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $title_match);
        if (!empty($title_match[1])) {
            $title = trim($title_match[1]);
        }
        
        $description = '';
        preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $body, $desc_match);
        if (empty($desc_match[1])) {
            preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']description["\'][^>]*>/is', $body, $desc_match);
        }
        if (!empty($desc_match[1])) {
            $description = trim($desc_match[1]);
        }
        
        $body_content = '';
        preg_match('/<body[^>]*>(.*)<\/body>/is', $body, $body_match);
        if (!empty($body_match[1])) {
            $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $body_match[1]);
            $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
            
            $body_content = trim(strip_tags($content));
            
            if (strlen($body_content) > ($max_length / 2)) {
                $body_content = substr($body_content, 0, ($max_length / 2)) . '...';
            }
        }
        
        $summary = "HTML Document Summary:\n";
        
        if (!empty($title)) {
            $summary .= "Title: " . $title . "\n";
        }
        
        if (!empty($description)) {
            $summary .= "Description: " . $description . "\n";
        }
        
        $summary .= "\nContent Preview:\n" . $body_content;
        
        if (strlen($summary) > $max_length) {
            return substr($summary, 0, $max_length) . '...';
        }
        
        return $summary;
    }
    
    /**
     * Extract basic HTML structure without full parsing
     */
    private function extract_html_structure($body, $max_length) {
        $structure = "HTML Structure Summary:\n";
        
        preg_match('/<!DOCTYPE[^>]*>/i', $body, $doctype_match);
        if (!empty($doctype_match[0])) {
            $structure .= "Doctype: " . trim($doctype_match[0]) . "\n";
        }
        
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $title_match);
        if (!empty($title_match[1])) {
            $structure .= "Title: " . trim($title_match[1]) . "\n";
        }
        
        $elements = array(
            'div' => 0, 'p' => 0, 'a' => 0, 'img' => 0, 'ul' => 0,
            'ol' => 0, 'li' => 0, 'table' => 0, 'form' => 0, 'script' => 0, 'style' => 0
        );
        
        foreach ($elements as $tag => $count) {
            preg_match_all('/<' . $tag . '[^>]*>/i', $body, $matches);
            $elements[$tag] = count($matches[0]);
        }
        
        $structure .= "\nElement Counts:\n";
        foreach ($elements as $tag => $count) {
            if ($count > 0) {
                $structure .= "- " . strtoupper($tag) . ": " . $count . "\n";
            }
        }
        
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $body, $h1_match);
        if (!empty($h1_match[1])) {
            $structure .= "\nMain Heading: " . trim(strip_tags($h1_match[1])) . "\n";
        }
        
        if (strlen($structure) > $max_length) {
            return substr($structure, 0, $max_length) . '...';
        }
        
        return $structure;
    }
}