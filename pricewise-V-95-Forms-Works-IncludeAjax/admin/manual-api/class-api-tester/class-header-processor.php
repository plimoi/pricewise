<?php
/**
 * API Tester Header Processor Class
 * Handles processing of response headers.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API response header processing
 */
class Pricewise_API_Header_Processor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Extract configured headers from response headers
     * 
     * @param array|object $headers Response headers
     * @param array $configured_headers Array of configured header names
     * @return array Extracted header information
     */
    public function extract_configured_headers($headers, $configured_headers) {
        $extracted_headers = array();
        
        // If no configured headers, return empty array
        if (empty($configured_headers)) {
            return $extracted_headers;
        }
        
        // Check for headers in both object and array format
        if (is_object($headers)) {
            foreach ($configured_headers as $header_name) {
                // Convert to lowercase for case-insensitive comparison
                $header_name_lower = strtolower($header_name);
                
                // Check each header in the response
                foreach ($headers as $header => $value) {
                    if (strtolower($header) === $header_name_lower) {
                        $extracted_headers[$header] = $value;
                        break;
                    }
                }
            }
        } else if (is_array($headers)) {
            foreach ($configured_headers as $header_name) {
                // Convert to lowercase for case-insensitive comparison
                $header_name_lower = strtolower($header_name);
                
                // Check each header in the response
                foreach ($headers as $header => $value) {
                    if (strtolower($header) === $header_name_lower) {
                        $extracted_headers[$header] = $value;
                        break;
                    }
                }
            }
        }
        
        return $extracted_headers;
    }
    
    /**
     * Extract rate limit information from response headers
     * 
     * @param array|object $headers Response headers
     * @return array Rate limit information
     */
    public function extract_rate_limit_info($headers) {
        $rate_limit_info = array();
        
        // Common rate limit header patterns
        $rate_limit_patterns = array(
            'x-ratelimit-limit' => 'Total Limit',
            'x-ratelimit-remaining' => 'Remaining',
            'x-ratelimit-reset' => 'Reset Time',
            'x-ratelimit-requests-limit' => 'Requests Limit',
            'x-ratelimit-requests-remaining' => 'Requests Remaining',
            'x-ratelimit-requests-reset' => 'Requests Reset',
            'x-ratelimit-rapid-free-plans-hard-limit-limit' => 'Hard Limit',
            'x-ratelimit-rapid-free-plans-hard-limit-remaining' => 'Hard Limit Remaining',
            'x-ratelimit-rapid-free-plans-hard-limit-reset' => 'Hard Limit Reset',
        );
        
        // Check for headers in both object and array format
        if (is_object($headers)) {
            foreach ($rate_limit_patterns as $header => $label) {
                if (isset($headers->$header)) {
                    $rate_limit_info[$label] = $headers->$header;
                }
            }
        } else if (is_array($headers)) {
            foreach ($rate_limit_patterns as $header => $label) {
                if (isset($headers[$header])) {
                    $rate_limit_info[$label] = $headers[$header];
                }
            }
        }
        
        // Look for any other headers that match rate limit patterns
        if (is_object($headers) || is_array($headers)) {
            foreach ($headers as $header => $value) {
                $header_lower = strtolower($header);
                
                // Check for headers containing 'rate' or 'limit' that we didn't catch above
                if ((strpos($header_lower, 'rate') !== false || 
                     strpos($header_lower, 'limit') !== false) && 
                    !array_key_exists($header, $rate_limit_patterns)) {
                    
                    // Format the label from the header name
                    $label = str_replace(array('x-', '-'), array('', ' '), $header);
                    $label = ucwords($label);
                    
                    $rate_limit_info[$label] = $value;
                }
            }
        }
        
        return $rate_limit_info;
    }
    
    /**
     * Mask sensitive headers for display (e.g., API keys)
     * 
     * @param array $headers Headers to mask
     * @return array Masked headers
     */
    public function mask_sensitive_headers($headers) {
        $masked_headers = array();
        
        foreach ($headers as $header_name => $header_value) {
            // Make a copy of the value before potentially masking it
            $display_value = $header_value;
            
            // Mask API keys and tokens for security
            if (strpos(strtolower($header_name), 'key') !== false || 
                strpos(strtolower($header_name), 'token') !== false || 
                strpos(strtolower($header_name), 'auth') !== false) {
                $display_value = substr($header_value, 0, 5) . '****';
            }
            
            $masked_headers[$header_name] = $display_value;
        }
        
        return $masked_headers;
    }
}