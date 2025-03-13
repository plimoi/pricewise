<?php
/**
 * Endpoint Deactivator Trigger Action
 * Automatically deactivates a specific API endpoint when trigger conditions are met.
 *
 * @package PriceWise
 * @subpackage Triggers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Endpoint Deactivator trigger action class
 */
class PW_Trigger_EndpointDeactivator {
    /**
     * Trigger name
     *
     * @var string
     */
    public $name = 'Endpoint Deactivator';
    
    /**
     * Trigger description
     *
     * @var string
     */
    public $description = 'Automatically deactivates a specific API endpoint when the trigger condition is met.';
    
    /**
     * Execute the trigger action
     *
     * @param array $data Trigger data
     * @return bool Success status
     */
    public function execute($data) {
        // Extract the test data and trigger configuration
        $test_data = isset($data['test_data']) ? $data['test_data'] : array();
        $trigger = isset($data['trigger']) ? $data['trigger'] : array();
        
        if (empty($test_data) || empty($trigger)) {
            return false;
        }
        
        // Get the API ID and endpoint path from the test data
        $api_id = isset($test_data['api_id']) ? $test_data['api_id'] : null;
        $endpoint = isset($test_data['endpoint']) ? $test_data['endpoint'] : null;
        
        if (empty($api_id) || empty($endpoint)) {
            $this->log_error('API ID or endpoint not found in test data');
            return false;
        }
        
        // Get all configured APIs
        $apis = get_option('pricewise_manual_apis', array());
        
        if (empty($apis) || !is_array($apis)) {
            $this->log_error('No APIs configured or invalid API configuration format');
            return false;
        }
        
        // Find the API by ID
        if (!isset($apis[$api_id])) {
            $this->log_error("API with ID '{$api_id}' not found in configuration");
            return false;
        }
        
        $api = $apis[$api_id];
        $api_name = isset($api['name']) ? $api['name'] : $api_id;
        
        // Find the endpoint
        $endpoint_found = false;
        $endpoint_id = null;
        $endpoint_name = null;
        
        // First try to find by endpoint path
        if (isset($api['endpoints']) && is_array($api['endpoints'])) {
            foreach ($api['endpoints'] as $id => $endpoint_config) {
                if (isset($endpoint_config['path']) && $endpoint_config['path'] === $endpoint) {
                    $endpoint_found = true;
                    $endpoint_id = $id;
                    $endpoint_name = isset($endpoint_config['name']) ? $endpoint_config['name'] : $id;
                    
                    // Check if the endpoint is already inactive
                    if (isset($endpoint_config['active']) && $endpoint_config['active'] === false) {
                        $this->log_info("Endpoint '{$endpoint_name}' in API '{$api_name}' is already inactive");
                        return true;
                    }
                    
                    // Set the endpoint to inactive
                    $apis[$api_id]['endpoints'][$id]['active'] = false;
                    
                    break;
                }
            }
        }
        
        // If not found by path, try to find by matching the endpoint name with path (some setups might use this)
        if (!$endpoint_found && isset($api['endpoints']) && is_array($api['endpoints'])) {
            foreach ($api['endpoints'] as $id => $endpoint_config) {
                if (isset($endpoint_config['name']) && $endpoint_config['name'] === $endpoint) {
                    $endpoint_found = true;
                    $endpoint_id = $id;
                    $endpoint_name = $endpoint_config['name'];
                    
                    // Check if the endpoint is already inactive
                    if (isset($endpoint_config['active']) && $endpoint_config['active'] === false) {
                        $this->log_info("Endpoint '{$endpoint_name}' in API '{$api_name}' is already inactive");
                        return true;
                    }
                    
                    // Set the endpoint to inactive
                    $apis[$api_id]['endpoints'][$id]['active'] = false;
                    
                    break;
                }
            }
        }
        
        // If endpoint isn't found, check if this is a legacy setup with a single endpoint in advanced_config
        if (!$endpoint_found && isset($api['advanced_config']) && isset($api['advanced_config']['endpoint'])) {
            if ($api['advanced_config']['endpoint'] === $endpoint) {
                $endpoint_found = true;
                $endpoint_id = 'default';
                $endpoint_name = 'Default Endpoint';
                
                // Set the endpoint in advanced_config to inactive
                if (!isset($api['advanced_config']['active'])) {
                    $apis[$api_id]['advanced_config']['active'] = false;
                } else {
                    // Already has an active property
                    $apis[$api_id]['advanced_config']['active'] = false;
                }
            }
        }
        
        if (!$endpoint_found) {
            $this->log_error("Endpoint '{$endpoint}' not found in API '{$api_name}'");
            return false;
        }
        
        // Save the updated APIs configuration
        $result = update_option('pricewise_manual_apis', $apis);
        
        if ($result) {
            $this->log_info("Deactivated endpoint '{$endpoint_name}' in API '{$api_name}'");
            
            // Send admin notification
            $this->send_notification($api_id, $api_name, $endpoint_id, $endpoint_name, $test_data, $trigger);
        } else {
            $this->log_error("Failed to save updated configuration for endpoint '{$endpoint_name}' in API '{$api_name}'");
        }
        
        return $result;
    }
    
    /**
     * Send admin notification about endpoint deactivation
     *
     * @param string $api_id The API ID
     * @param string $api_name The API name
     * @param string $endpoint_id The endpoint ID
     * @param string $endpoint_name The endpoint name
     * @param array $test_data The test data
     * @param array $trigger The trigger configuration
     */
    private function send_notification($api_id, $api_name, $endpoint_id, $endpoint_name, $test_data, $trigger) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $endpoint = isset($test_data['endpoint']) ? $test_data['endpoint'] : 'Unknown endpoint';
        $status_code = isset($test_data['status_code']) ? $test_data['status_code'] : 'N/A';
        $response_time = isset($test_data['response_time']) ? round($test_data['response_time'], 2) . ' seconds' : 'N/A';
        $field_value = $this->get_field_value($test_data, $trigger['trigger_special_field']);
        
        $admin_url = admin_url('admin.php?page=pricewise-manual-api&edit=' . urlencode($api_id));
        
        $subject = sprintf(
            '[PriceWise Alert] Endpoint "%s" in API "%s" Deactivated by Trigger',
            $endpoint_name,
            $api_name
        );
        
        $message = sprintf(
            "Hello Admin,\n\n" .
            "An API endpoint has been automatically deactivated by PriceWise Triggers.\n\n" .
            "API: %s\n" .
            "Endpoint: %s\n" .
            "Trigger: %s\n" .
            "Condition: %s %s %s\n" .
            "Actual Value: %s\n\n" .
            "Test Details:\n" .
            "- Endpoint Path: %s\n" .
            "- Status Code: %s\n" .
            "- Response Time: %s\n" .
            "- Time: %s\n\n" .
            "You can review and reactivate this endpoint in the PriceWise Manual API settings page:\n" .
            "%s\n\n" .
            "This is an automated notification from %s.",
            $api_name,
            $endpoint_name,
            $trigger['trigger_name'],
            $trigger['trigger_special_field'],
            $this->get_comparison_text($trigger['trigger_comparison']),
            $trigger['trigger_value'],
            $field_value,
            $endpoint,
            $status_code,
            $response_time,
            current_time('mysql'),
            $admin_url,
            $site_name
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Get human-readable comparison text
     *
     * @param string $comparison The comparison type
     * @return string Human-readable comparison text
     */
    private function get_comparison_text($comparison) {
        $texts = array(
            'same' => 'is exactly',
            'equals' => 'equals',
            'not_equals' => 'does not equal',
            'contains' => 'contains',
            'starts_with' => 'starts with',
            'ends_with' => 'ends with',
            'more_than' => 'is greater than',
            'less_than' => 'is less than',
            'greater_equal' => 'is greater than or equal to',
            'less_equal' => 'is less than or equal to'
        );
        
        return isset($texts[$comparison]) ? $texts[$comparison] : $comparison;
    }
    
    /**
     * Helper to get field value for display
     *
     * @param array $test_data The test data
     * @param string $field_key The field key to retrieve
     * @return string The field value
     */
    private function get_field_value($test_data, $field_key) {
        // Check direct fields
        if (isset($test_data[$field_key])) {
            return (string)$test_data[$field_key];
        }
        
        // Check for response headers
        if (!empty($test_data['response_headers']) && is_array($test_data['response_headers'])) {
            foreach ($test_data['response_headers'] as $header_key => $header_value) {
                if (strtolower($header_key) === strtolower($field_key)) {
                    return is_array($header_value) ? implode(', ', $header_value) : (string)$header_value;
                }
            }
        }
        
        // Check for request params
        if (!empty($test_data['request_params']) && is_array($test_data['request_params'])) {
            foreach ($test_data['request_params'] as $param_key => $param_value) {
                if (strtolower($param_key) === strtolower($field_key)) {
                    return is_array($param_value) ? implode(', ', $param_value) : (string)$param_value;
                }
            }
        }
        
        // Special fields
        if ($field_key === 'status_code' && isset($test_data['status_code'])) {
            return (string)$test_data['status_code'];
        }
        
        if ($field_key === 'response_time' && isset($test_data['response_time'])) {
            return (string)round($test_data['response_time'], 2) . ' seconds';
        }
        
        return 'N/A';
    }
    
    /**
     * Log an error message
     *
     * @param string $message Error message to log
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PriceWise Endpoint Deactivator Error: ' . $message);
        }
    }
    
    /**
     * Log an info message
     *
     * @param string $message Info message to log
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PriceWise Endpoint Deactivator: ' . $message);
        }
    }
}