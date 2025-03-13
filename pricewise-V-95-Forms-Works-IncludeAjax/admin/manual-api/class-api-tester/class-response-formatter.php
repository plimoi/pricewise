<?php
/**
 * API Tester Response Formatter Class
 * Handles formatting and processing of API responses.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API response formatting
 */
class Pricewise_API_Response_Formatter {
    
    /**
     * Maximum safe response size for full processing (in bytes)
     */
    private $max_safe_size = 2097152; // 2MB default
    
    /**
     * Memory usage threshold percentage (0-1)
     */
    private $memory_threshold = 0.8;

    /**
     * Constructor
     */
    public function __construct() {
        // Apply filters to allow customization of size limits
        $this->max_safe_size = apply_filters('pricewise_api_max_safe_size', $this->max_safe_size);
        $this->memory_threshold = apply_filters('pricewise_api_memory_threshold', $this->memory_threshold);
    }
    
    /**
     * Determine the content type from HTTP header
     *
     * @param string $content_type Content-Type header
     * @return string Simplified content type (json, xml, html, etc.)
     */
    public function determine_content_type($content_type) {
        $content_type = strtolower($content_type);
        
        if (strpos($content_type, 'application/json') !== false) {
            return 'json';
        } elseif (strpos($content_type, 'text/html') !== false) {
            return 'html';
        } elseif (strpos($content_type, 'application/xml') !== false || strpos($content_type, 'text/xml') !== false) {
            return 'xml';
        } elseif (strpos($content_type, 'text/plain') !== false) {
            return 'text';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Check if content appears to be JSON
     *
     * @param string $body Content to check
     * @return bool True if content appears to be JSON
     */
    public function appears_to_be_json($body) {
        $trimmed = trim($body);
        return (
            ($trimmed[0] === '{' && substr($trimmed, -1) === '}') || 
            ($trimmed[0] === '[' && substr($trimmed, -1) === ']')
        );
    }
    
    /**
     * Check if content appears to be XML
     *
     * @param string $body Content to check
     * @return bool True if content appears to be XML
     */
    public function appears_to_be_xml($body) {
        $trimmed = trim($body);
        return (
            strpos($trimmed, '<?xml') === 0 ||
            preg_match('/<[a-z0-9_:]+(\s+[a-z0-9_:]+=".*?")*\s*(\/)?>/', $trimmed)
        );
    }
    
    /**
     * Process API response based on selected format
     * 
     * @param string $body Response body
     * @param string $format Response format (json, array, object, xml, html)
     * @param string $content_type Content-Type header from response
     * @return mixed|WP_Error Processed response or error
     */
    public function process_api_response($body, $format = 'json', $content_type = '') {
        // Check response size first
        $size = strlen($body);
        $very_large = $size > $this->max_safe_size;
        
        // Check memory availability
        $mem_available = $this->is_memory_available();
        
        // For very large responses with limited memory, use adaptive processing
        if ($very_large && !$mem_available) {
            return $this->process_oversized_response($body, $format, $content_type);
        }
        
        // Determine actual format from content type if not explicitly specified
        if (empty($format) || $format === 'auto') {
            $format = $this->determine_format_from_content($body, $content_type);
        }
        
        try {
            switch ($format) {
                case 'array':
                    return $this->format_array_response($body, $very_large);
                    
                case 'object':
                    return $this->format_object_response($body, $very_large);
                    
                case 'xml':
                    return $this->format_xml_response($body, $very_large);
                    
                case 'html':
                    return $this->format_html_response($body, $content_type);
                    
                case 'json':
                default:
                    return $this->format_json_response($body);
            }
        } catch (Exception $e) {
            error_log('PriceWise API Response Formatter Error: ' . $e->getMessage());
            return new WP_Error(
                'response_processing_exception', 
                'Exception processing response: ' . $e->getMessage(),
                array(
                    'format' => $format,
                    'size' => $size,
                    'exception' => $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Determine the best format based on content type and body inspection
     *
     * @param string $body Response body
     * @param string $content_type Content-Type header
     * @return string Best format to use
     */
    private function determine_format_from_content($body, $content_type) {
        // First check content type header
        $format = $this->determine_content_type($content_type);
        
        // If still unknown, try to determine from content
        if ($format === 'unknown' || $format === 'text') {
            if ($this->appears_to_be_json($body)) {
                return 'json';
            } elseif ($this->appears_to_be_xml($body)) {
                return 'xml';
            } elseif (strpos($body, '<html') !== false || strpos($body, '<!DOCTYPE html>') !== false) {
                return 'html';
            }
        }
        
        // Default to json if we couldn't determine
        return $format !== 'unknown' ? $format : 'json';
    }
    
    /**
     * Process oversized responses with adaptive strategies
     *
     * @param string $body Response body
     * @param string $format Response format
     * @param string $content_type Content-Type header
     * @return mixed|WP_Error Processed response or error with partial data
     */
    private function process_oversized_response($body, $format, $content_type) {
        $size = strlen($body);
        $sample_size = min(10240, $size); // Take max 10KB sample
        $sample = substr($body, 0, $sample_size);
        
        // Create a partial response warning
        $warning = sprintf(
            'Response is very large (%s). Only partial processing performed.',
            $this->format_size($size)
        );
        
        // Different strategies based on format
        switch ($format) {
            case 'array':
            case 'object':
                // For arrays and objects, extract just the structure
                if ($this->appears_to_be_json($sample)) {
                    $structure = $this->extract_json_structure($body);
                    
                    // Return with warning
                    return new WP_Error(
                        'oversized_response',
                        $warning,
                        array(
                            'format' => $format,
                            'size' => $size,
                            'partial_data' => $structure
                        )
                    );
                }
                break;
                
            case 'xml':
                // For XML, extract just the structure
                if ($this->appears_to_be_xml($sample)) {
                    $structure = $this->extract_xml_structure($body);
                    
                    // Return with warning
                    return new WP_Error(
                        'oversized_response',
                        $warning,
                        array(
                            'format' => $format,
                            'size' => $size,
                            'partial_data' => $structure
                        )
                    );
                }
                break;
                
            case 'html':
                // For HTML, extract important elements
                $structure = $this->extract_html_structure($body);
                
                // Return with warning
                return new WP_Error(
                    'oversized_response',
                    $warning,
                    array(
                        'format' => $format,
                        'size' => $size,
                        'partial_data' => $structure
                    )
                );
                
            case 'json':
            default:
                // For JSON string, return the raw sample
                return new WP_Error(
                    'oversized_response',
                    $warning,
                    array(
                        'format' => 'json',
                        'size' => $size,
                        'partial_data' => $sample . '...'
                    )
                );
        }
        
        // Default fallback for any other format
        return new WP_Error(
            'oversized_response',
            $warning,
            array(
                'format' => $format,
                'size' => $size,
                'partial_data' => $sample . '...'
            )
        );
    }
    
    /**
     * Check if memory is available for processing
     *
     * @return bool True if sufficient memory is available
     */
    private function is_memory_available() {
        if (!function_exists('memory_get_usage') || !function_exists('ini_get')) {
            return true; // Can't check, assume it's available
        }
        
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') {
            return true; // No limit
        }
        
        // Convert memory limit to bytes
        $memory_limit = $this->convert_to_bytes($memory_limit);
        $current_usage = memory_get_usage(true);
        
        // Check if usage is below threshold percentage
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
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * Format file size for display
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size string
     */
    private function format_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
    
    /**
     * Format response as a PHP array (from JSON)
     * 
     * @param string $body Response body
     * @param bool $is_large Whether this is a large response
     * @return array|WP_Error Processed array or error
     */
    public function format_array_response($body, $is_large = false) {
        if ($is_large) {
            // Try stream parsing for large JSON arrays
            return $this->stream_parse_json_array($body);
        }
        
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_response_invalid', 'Invalid JSON format: ' . json_last_error_msg());
        }
        return $data;
    }
    
    /**
     * Format response as a PHP object (from JSON)
     * 
     * @param string $body Response body
     * @param bool $is_large Whether this is a large response
     * @return object|WP_Error Processed object or error
     */
    public function format_object_response($body, $is_large = false) {
        if ($is_large) {
            // For large responses, convert to array first then to object for better memory management
            $array_data = $this->format_array_response($body, true);
            
            if (is_wp_error($array_data)) {
                return $array_data;
            }
            
            // Convert top level to object but keep nested arrays for efficiency
            return json_decode(json_encode($array_data));
        }
        
        $data = json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_response_invalid', 'Invalid JSON format: ' . json_last_error_msg());
        }
        return $data;
    }
    
    /**
     * Stream parse JSON array with better memory management
     *
     * @param string $body JSON response body
     * @return array|WP_Error Parsed array or error
     */
    private function stream_parse_json_array($body) {
        $sample = substr($body, 0, 1000); // Take a small sample
        
        // Check if it's actually an array
        if (trim($sample)[0] !== '[') {
            // If not an array, just do normal parsing for now
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('api_response_invalid', 'Invalid JSON format: ' . json_last_error_msg());
            }
            return $data;
        }
        
        // For large arrays, we'll extract just a subset of items
        $max_items = 100; // Cap at 100 items for very large arrays
        
        // Find array elements more efficiently
        preg_match_all('/\{(?:[^{}]|(?R))*\}/x', $body, $matches, PREG_PATTERN_ORDER, 0, $max_items + 1);
        
        if (empty($matches[0])) {
            // Fallback to normal parsing if regex approach fails
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error('api_response_invalid', 'Invalid JSON format: ' . json_last_error_msg());
            }
            return $data;
        }
        
        // Parse each element individually
        $result = array();
        $count = 0;
        
        foreach ($matches[0] as $item_json) {
            if ($count >= $max_items) {
                // Add a placeholder for remaining items
                $result[] = array(
                    '__note' => 'Response truncated. ' . (count($matches[0]) - $max_items) . ' more items not shown.'
                );
                break;
            }
            
            $item = json_decode($item_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result[] = $item;
                $count++;
            }
        }
        
        return $result;
    }
    
    /**
     * Extract structure from JSON without full parsing
     *
     * @param string $body JSON body
     * @return array Structure information
     */
    private function extract_json_structure($body) {
        $trimmed = trim($body);
        $first_char = $trimmed[0];
        $is_array = ($first_char === '[');
        $result = array();
        
        // Determine total size and type
        $result['__meta'] = array(
            'size' => $this->format_size(strlen($body)),
            'type' => $is_array ? 'array' : 'object'
        );
        
        if ($is_array) {
            // Extract a sample of array items
            preg_match_all('/(\{[^{}]*\})/', $trimmed, $matches, PREG_PATTERN_ORDER, 0, 5);
            
            if (!empty($matches[0])) {
                $result['__sample_items'] = array();
                foreach (array_slice($matches[0], 0, 3) as $item_json) {
                    $item = json_decode($item_json, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $result['__sample_items'][] = $item;
                    }
                }
                
                // Count approximate total items
                preg_match_all('/(\{)/', $trimmed, $count_matches);
                $result['__meta']['total_items'] = count($count_matches[0]);
            }
        } else {
            // For objects, extract top-level properties
            preg_match_all('/"([^"]+)"\s*:\s*/', $trimmed, $matches);
            
            if (!empty($matches[1])) {
                $result['__meta']['properties'] = array_slice($matches[1], 0, 20);
                $result['__meta']['property_count'] = count($matches[1]);
            }
            
            // Try to extract a few complete key/value pairs
            preg_match_all('/"([^"]+)"\s*:\s*("[^"]*"|null|\d+|\{[^{}]*\}|\[[^\[\]]*\])/', $trimmed, $pair_matches);
            
            if (!empty($pair_matches[0])) {
                $result['__sample'] = new stdClass();
                
                foreach (array_slice($pair_matches[0], 0, 5) as $pair) {
                    // Extract key and value
                    preg_match('/"([^"]+)"\s*:\s*(.*)/', $pair, $kv_match);
                    
                    if (!empty($kv_match)) {
                        $key = $kv_match[1];
                        $value = $kv_match[2];
                        
                        // Try to parse the value
                        $parsed_value = json_decode($value);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $result['__sample']->{$key} = $parsed_value;
                        } else {
                            $result['__sample']->{$key} = substr($value, 0, 100);
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Format response as XML
     * 
     * @param string $body Response body
     * @param bool $is_large Whether this is a large response
     * @return array|WP_Error Processed XML as array or error
     */
    public function format_xml_response($body, $is_large = false) {
        // Check if SimpleXML is available
        if (!function_exists('simplexml_load_string')) {
            return new WP_Error('xml_processing_unavailable', 'SimpleXML is required for XML processing');
        }
        
        if ($is_large) {
            // For large XML, return a structure analysis instead
            return $this->extract_xml_structure($body);
        }
        
        // Attempt to parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return new WP_Error('xml_parse_error', 'XML parsing failed: ' . ($errors ? $errors[0]->message : 'Unknown error'));
        }
        
        // Convert to array to maintain consistency with other formats
        $result = json_decode(json_encode($xml), true);
        
        // If conversion failed, return the SimpleXML object directly
        if ($result === null) {
            return $xml;
        }
        
        return $result;
    }
    
    /**
     * Extract structure from XML without full parsing
     *
     * @param string $body XML body
     * @return array Structure information
     */
    private function extract_xml_structure($body) {
        $result = array(
            '__meta' => array(
                'size' => $this->format_size(strlen($body)),
                'type' => 'xml'
            )
        );
        
        // Extract root element
        preg_match('/<([a-z0-9_:]+)(\s+[^>]*)?>/i', $body, $root_match);
        if (!empty($root_match)) {
            $result['__meta']['root_element'] = $root_match[1];
            
            // Get any root attributes
            if (!empty($root_match[2])) {
                preg_match_all('/([a-z0-9_:]+)="([^"]*)"/i', $root_match[2], $attr_matches);
                
                if (!empty($attr_matches[1])) {
                    $result['__meta']['root_attributes'] = array();
                    
                    foreach ($attr_matches[1] as $i => $attr_name) {
                        $result['__meta']['root_attributes'][$attr_name] = $attr_matches[2][$i];
                    }
                }
            }
        }
        
        // Count all elements
        preg_match_all('/<([a-z0-9_:]+)(\s+[^>]*)?>/i', $body, $all_elements);
        
        if (!empty($all_elements[1])) {
            $result['__meta']['total_elements'] = count($all_elements[1]);
            
            // Count occurrences of each element type
            $element_counts = array_count_values($all_elements[1]);
            arsort($element_counts); // Sort by frequency
            
            $result['__meta']['element_counts'] = array_slice($element_counts, 0, 10, true);
        }
        
        // Try to extract a few complete elements with content
        preg_match_all('/<([a-z0-9_:]+)(?:\s+[^>]*)?>((?:(?!<\1).)*)<\/\1>/is', $body, $content_matches, PREG_SET_ORDER, 0, 5);
        
        if (!empty($content_matches)) {
            $result['__sample_elements'] = array();
            
            foreach (array_slice($content_matches, 0, 3) as $match) {
                $element_name = $match[1];
                $content = $match[2];
                
                // Limit content length
                if (strlen($content) > 200) {
                    $content = substr($content, 0, 200) . '...';
                }
                
                $result['__sample_elements'][$element_name] = $content;
            }
        }
        
        return $result;
    }
    
    /**
     * Format response as HTML
     * 
     * @param string $body Response body
     * @param string $content_type Content-Type header
     * @return string|WP_Error HTML content or error
     */
    public function format_html_response($body, $content_type) {
        // For large HTML responses, extract structure only
        if (strlen($body) > $this->max_safe_size) {
            return $this->extract_html_structure($body);
        }
        
        // For HTML responses, check if it likely contains HTML content
        if (stripos($content_type, 'text/html') !== false || 
            preg_match('/<(!DOCTYPE|html|head|body|div|p|h[1-6]|span|a)\b/i', $body)) {
            return $body; // Return HTML content as is
        }
        
        // If the Content-Type is not HTML and no HTML tags found, return an error
        return new WP_Error('html_invalid', 'Response does not appear to contain HTML. Detected Content-Type: ' . $content_type);
    }
    
    /**
     * Extract structure from HTML content
     *
     * @param string $body HTML content
     * @return array Structure information
     */
    private function extract_html_structure($body) {
        $result = array(
            '__meta' => array(
                'size' => $this->format_size(strlen($body)),
                'type' => 'html'
            )
        );
        
        // Extract title
        preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $title_match);
        if (!empty($title_match[1])) {
            $result['__meta']['title'] = trim($title_match[1]);
        }
        
        // Extract meta description
        preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $body, $desc_match);
        if (empty($desc_match[1])) {
            preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']description["\'][^>]*>/is', $body, $desc_match);
        }
        if (!empty($desc_match[1])) {
            $result['__meta']['description'] = trim($desc_match[1]);
        }
        
        // Count HTML elements
        $elements = array(
            'div', 'p', 'span', 'a', 'img', 'ul', 'ol', 'li', 'table', 'tr', 'td',
            'form', 'input', 'button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
        );
        
        $result['__meta']['element_counts'] = array();
        
        foreach ($elements as $element) {
            preg_match_all('/<' . $element . '[^>]*>/i', $body, $matches);
            $count = count($matches[0]);
            
            if ($count > 0) {
                $result['__meta']['element_counts'][$element] = $count;
            }
        }
        
        // Extract heading hierarchy
        $result['__headings'] = array();
        
        for ($i = 1; $i <= 3; $i++) {
            preg_match_all('/<h' . $i . '[^>]*>(.*?)<\/h' . $i . '>/is', $body, $matches);
            
            if (!empty($matches[1])) {
                foreach (array_slice($matches[1], 0, 3) as $heading) {
                    $clean_heading = trim(strip_tags($heading));
                    if (!empty($clean_heading)) {
                        $result['__headings']['h' . $i][] = $clean_heading;
                    }
                }
            }
        }
        
        // Extract links
        preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $body, $link_matches, PREG_SET_ORDER);
        
        if (!empty($link_matches)) {
            $result['__links'] = array();
            
            foreach (array_slice($link_matches, 0, 5) as $match) {
                $url = $match[1];
                $text = trim(strip_tags($match[2]));
                
                if (!empty($text)) {
                    $result['__links'][] = array(
                        'url' => $url,
                        'text' => $text
                    );
                }
            }
            
            $result['__meta']['total_links'] = count($link_matches);
        }
        
        return $result;
    }
    
    /**
     * Format response as JSON string
     * 
     * @param string $body Response body
     * @return string|WP_Error JSON string or error
     */
    public function format_json_response($body) {
        // For large JSON, return a partial sample
        if (strlen($body) > $this->max_safe_size) {
            $sample = substr($body, 0, 10240); // 10KB sample
            
            // Add warning note
            $sample_note = "\n\n/* Response truncated due to size (" . $this->format_size(strlen($body)) . ") */";
            
            return $sample . $sample_note;
        }
        
        // Check if it's valid JSON
        json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('api_response_invalid', 'Invalid JSON format: ' . json_last_error_msg());
        }
        return $body; // Return raw JSON string
    }
    
    /**
     * Generate a pretty-printed version of JSON data
     * 
     * @param string|array|object $data JSON string, array or object
     * @return string Pretty-printed JSON
     */
    public function pretty_print_json($data) {
        // For large data, limit pretty printing
        $is_string = is_string($data);
        $size = $is_string ? strlen($data) : strlen(json_encode($data));
        
        if ($size > $this->max_safe_size) {
            if ($is_string) {
                // Return a sample of the raw string
                $sample = substr($data, 0, 10240); // 10KB sample
                return $sample . "\n\n/* Response truncated due to size (" . $this->format_size($size) . ") */";
            } else {
                // For large objects/arrays, convert to string first to get size
                $json_str = json_encode($data);
                $sample = substr($json_str, 0, 10240); // 10KB sample
                return $sample . "\n\n/* Response truncated due to size (" . $this->format_size(strlen($json_str)) . ") */";
            }
        }
        
        // If it's a string, try to decode it first
        if ($is_string) {
            $json_data = json_decode($data);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($json_data, JSON_PRETTY_PRINT);
            }
            return $data; // Return as is if not valid JSON
        }
        
        // If it's already an array or object, encode it with pretty print
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}