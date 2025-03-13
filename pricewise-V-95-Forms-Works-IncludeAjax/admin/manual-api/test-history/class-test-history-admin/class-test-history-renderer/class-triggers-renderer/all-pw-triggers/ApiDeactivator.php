<?php
/**
 * API Account Deactivator Trigger Action
 * Automatically deactivates an API account when trigger conditions are met.
 *
 * @package PriceWise
 * @subpackage Triggers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * API Account Deactivator trigger action class
 */
class PW_Trigger_ApiDeactivator {
    /**
     * Trigger name
     *
     * @var string
     */
    public $name = 'API Account Deactivator';
    
    /**
     * Trigger description
     *
     * @var string
     */
    public $description = 'Automatically deactivates an API account when the trigger condition is met.';
    
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
        
        // Get the API ID from the test data
        $api_id = isset($test_data['api_id']) ? $test_data['api_id'] : null;
        
        if (empty($api_id)) {
            $this->log_error('API ID not found in test data');
            return false;
        }
        
        // Get all configured APIs
        $apis = get_option('pricewise_manual_apis', array());
        
        if (empty($apis) || !is_array($apis)) {
            $this->log_error('No APIs configured or invalid API configuration format');
            return false;
        }
        
        // Find the API by ID and deactivate it
        $api_found = false;
        $deactivated = false;
        
        foreach ($apis as $index => $api) {
            if (isset($api['id_name']) && $api['id_name'] === $api_id) {
                $api_found = true;
                
                // Check if the API is already inactive
                if (isset($api['active']) && $api['active'] === false) {
                    $this->log_info("API '{$api['name']}' ({$api_id}) is already inactive");
                    return true;
                }
                
                // Deactivate the API
                $apis[$index]['active'] = false;
                $deactivated = true;
                
                // Log the action
                $this->log_info("Deactivating API '{$api['name']}' ({$api_id}) due to trigger: {$trigger['trigger_name']}");
                
                break;
            }
        }
        
        if (!$api_found) {
            $this->log_error("API with ID '{$api_id}' not found in configuration");
            return false;
        }
        
        if (!$deactivated) {
            $this->log_error("Failed to deactivate API '{$api_id}'");
            return false;
        }
        
        // Save the updated APIs configuration
        $result = update_option('pricewise_manual_apis', $apis);
        
        if ($result) {
            // Send admin notification
            $this->send_notification($api_id, $test_data, $trigger);
        }
        
        return $result;
    }
    
    /**
     * Send admin notification about API deactivation
     *
     * @param string $api_id The API ID
     * @param array $test_data The test data
     * @param array $trigger The trigger configuration
     */
    private function send_notification($api_id, $test_data, $trigger) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $api_name = isset($test_data['api_name']) ? $test_data['api_name'] : $api_id;
        $endpoint = isset($test_data['endpoint']) ? $test_data['endpoint'] : 'Unknown endpoint';
        $status_code = isset($test_data['status_code']) ? $test_data['status_code'] : 'N/A';
        $response_time = isset($test_data['response_time']) ? round($test_data['response_time'], 2) . ' seconds' : 'N/A';
        $field_value = $this->get_field_value($test_data, $trigger['trigger_special_field']);
        
        $subject = sprintf(
            '[PriceWise Alert] API "%s" Deactivated by Trigger: %s',
            $api_name,
            $trigger['trigger_name']
        );
        
        $message = sprintf(
            "Hello Admin,\n\n" .
            "An API has been automatically deactivated by PriceWise Triggers.\n\n" .
            "API: %s\n" .
            "Trigger: %s\n" .
            "Condition: %s %s %s\n" .
            "Actual Value: %s\n\n" .
            "Test Details:\n" .
            "- Endpoint: %s\n" .
            "- Status Code: %s\n" .
            "- Response Time: %s\n" .
            "- Time: %s\n\n" .
            "You can review and reactivate this API in the PriceWise Manual API settings page.\n\n" .
            "This is an automated notification from %s.",
            $api_name,
            $trigger['trigger_name'],
            $trigger['trigger_special_field'],
            $this->get_comparison_text($trigger['trigger_comparison']),
            $trigger['trigger_value'],
            $field_value,
            $endpoint,
            $status_code,
            $response_time,
            current_time('mysql'),
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
            error_log('PriceWise API Deactivator Error: ' . $message);
        }
    }
    
    /**
     * Log an info message
     *
     * @param string $message Info message to log
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('PriceWise API Deactivator: ' . $message);
        }
    }
}