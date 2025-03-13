<?php
/**
 * Email Alert Trigger Action
 * Sends email notifications based on trigger conditions.
 *
 * @package PriceWise
 * @subpackage Triggers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Email alert trigger action class
 */
class PW_Trigger_MailAlert {
    /**
     * Trigger name
     *
     * @var string
     */
    public $name = 'Email Alert';
    
    /**
     * Trigger description
     *
     * @var string
     */
    public $description = 'Sends an email notification to the site admin when the trigger condition is met.';
    
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
        
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Build the email subject
        $subject = sprintf(
            '[PriceWise Alert] %s: %s %s %s',
            $trigger['trigger_name'],
            $trigger['trigger_special_field'],
            $this->get_comparison_text($trigger['trigger_comparison']),
            $trigger['trigger_value']
        );
        
        // Build the email content
        $content = $this->build_email_content($test_data, $trigger);
        
        // Set email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>'
        );
        
        // Send the email
        $result = wp_mail($admin_email, $subject, $content, $headers);
        
        // Log the action if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'PriceWise Email Alert: %s - Result: %s',
                $trigger['trigger_name'],
                $result ? 'Success' : 'Failed'
            ));
        }
        
        return $result;
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
     * Build the email content with HTML formatting
     *
     * @param array $test_data The test data
     * @param array $trigger The trigger configuration
     * @return string HTML email content
     */
    private function build_email_content($test_data, $trigger) {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $admin_url = admin_url('admin.php?page=pricewise-test-history');
        
        // Format the timestamp
        $time = isset($test_data['test_date']) ? 
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($test_data['test_date'])) : 
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
        
        // Get API info
        $api_name = isset($test_data['api_name']) ? $test_data['api_name'] : 'Unknown API';
        $endpoint = isset($test_data['endpoint']) ? $test_data['endpoint'] : 'Unknown endpoint';
        $status_code = isset($test_data['status_code']) ? $test_data['status_code'] : 'N/A';
        $response_time = isset($test_data['response_time']) ? round($test_data['response_time'], 2) . ' seconds' : 'N/A';
        
        // Start building HTML content
        $content = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . esc_html($subject) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #0073aa; margin-bottom: 5px;">PriceWise Alert Notification</h1>
                <p style="font-size: 16px; color: #666;">Trigger condition met: <strong>' . esc_html($trigger['trigger_name']) . '</strong></p>
            </div>
            
            <div style="background-color: #f9f9f9; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 20px;">
                <p><strong>Trigger Condition:</strong> ' . esc_html($trigger['trigger_special_field']) . ' ' . 
                    esc_html($this->get_comparison_text($trigger['trigger_comparison'])) . ' ' . 
                    esc_html($trigger['trigger_value']) . '</p>
                <p><strong>Actual Value:</strong> ' . esc_html($this->get_field_value($test_data, $trigger['trigger_special_field'])) . '</p>
            </div>
            
            <h2 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Test Details</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee; width: 30%;"><strong>API:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . esc_html($api_name) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Endpoint:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . esc_html($endpoint) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Status Code:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . esc_html($status_code) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Response Time:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . esc_html($response_time) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong>Time:</strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">' . esc_html($time) . '</td>
                </tr>
            </table>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #666;">
                <p>View all test history in the <a href="' . esc_url($admin_url) . '" style="color: #0073aa; text-decoration: none;">PriceWise admin panel</a>.</p>
                <p>This is an automated notification from <a href="' . esc_url($site_url) . '" style="color: #0073aa; text-decoration: none;">' . esc_html($site_name) . '</a>.</p>
            </div>
        </body>
        </html>';
        
        return $content;
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
        
        // Special case for status_code
        if ($field_key === 'status_code' && isset($test_data['status_code'])) {
            return (string)$test_data['status_code'];
        }
        
        // Special case for response_time
        if ($field_key === 'response_time' && isset($test_data['response_time'])) {
            return (string)round($test_data['response_time'], 2) . ' seconds';
        }
        
        return 'N/A';
    }
}