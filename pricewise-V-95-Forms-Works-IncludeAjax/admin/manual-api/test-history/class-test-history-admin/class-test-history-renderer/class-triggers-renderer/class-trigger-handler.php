<?php
/**
 * Trigger Handler Class
 * Handles the execution of trigger rules based on test results.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Trigger_Handler {
    /**
     * Database handler instance
     * @var Pricewise_Trigger_DB
     */
    private $db;
    
    /**
     * Available trigger actions
     * @var array
     */
    private $available_actions = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(__FILE__) . '/class-trigger-db.php';
        $this->db = new Pricewise_Trigger_DB();
        $this->load_available_actions();
        
        // Add hook to process triggers after a test is logged
        add_action('pricewise_after_test_logged', array($this, 'process_triggers'), 10, 2);
    }
    
    /**
     * Load available trigger actions from the triggers directory
     */
    private function load_available_actions() {
        // Get the path to the triggers directory
        $plugin_dir = plugin_dir_path(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
        $triggers_dir = $plugin_dir . 'admin/manual-api/test-history/class-test-history-admin/class-test-history-renderer/class-triggers-renderer/all-pw-triggers';
        
        // Check if directory exists and is readable
        if (!is_dir($triggers_dir) || !is_readable($triggers_dir)) {
            return;
        }
        
        // Get all PHP files in the directory
        $files = glob($triggers_dir . '/*.php');
        
        if (empty($files)) {
            return;
        }
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            // Include the file
            include_once $file;
            
            // Expected class name based on filename
            $class_name = 'PW_Trigger_' . ucfirst($filename);
            
            // Check if the class exists
            if (class_exists($class_name)) {
                $this->available_actions[$filename] = $class_name;
            }
        }
    }
    
    /**
     * Process all active triggers against test results
     *
     * @param int $test_id The ID of the test in the database
     * @param array $test_data The test data
     * @return void
     */
    public function process_triggers($test_id, $test_data) {
        // Get all active triggers
        $triggers = $this->db->get_triggers(array('is_active' => true));
        
        if (empty($triggers)) {
            return;
        }
        
        foreach ($triggers as $trigger) {
            $this->evaluate_trigger($trigger, $test_data);
        }
    }
    
    /**
     * Evaluate a single trigger against test data
     *
     * @param array $trigger The trigger configuration
     * @param array $test_data The test data
     * @return bool Whether the trigger was executed
     */
    private function evaluate_trigger($trigger, $test_data) {
        // Get the value to compare from the test data
        $field_value = $this->get_field_value($trigger['trigger_special_field'], $test_data);
        
        // If the field doesn't exist in the test data, skip this trigger
        if ($field_value === null) {
            return false;
        }
        
        // Evaluate the condition
        $condition_met = $this->evaluate_condition(
            $field_value,
            $trigger['trigger_comparison'],
            $trigger['trigger_value'],
            $trigger['trigger_condition']
        );
        
        // If the condition is met, execute the action
        if ($condition_met) {
            return $this->execute_action($trigger['trigger_action'], $test_data, $trigger);
        }
        
        return false;
    }
    
    /**
     * Get a field value from test data
     *
     * @param string $field_key The field key to look for
     * @param array $test_data The test data
     * @return mixed|null The field value or null if not found
     */
    private function get_field_value($field_key, $test_data) {
        // Check direct fields
        if (isset($test_data[$field_key])) {
            return $test_data[$field_key];
        }
        
        // Check for response headers
        if (!empty($test_data['response_headers']) && is_array($test_data['response_headers'])) {
            foreach ($test_data['response_headers'] as $header_key => $header_value) {
                if (strtolower($header_key) === strtolower($field_key)) {
                    return $header_value;
                }
            }
        }
        
        // Check for request params
        if (!empty($test_data['request_params']) && is_array($test_data['request_params'])) {
            foreach ($test_data['request_params'] as $param_key => $param_value) {
                if (strtolower($param_key) === strtolower($field_key)) {
                    return $param_value;
                }
            }
        }
        
        // Special case for status_code
        if ($field_key === 'status_code' && isset($test_data['status_code'])) {
            return $test_data['status_code'];
        }
        
        // Special case for response_time
        if ($field_key === 'response_time' && isset($test_data['response_time'])) {
            return $test_data['response_time'];
        }
        
        return null;
    }
    
    /**
     * Evaluate a condition based on comparison type
     *
     * @param mixed $actual_value The actual value from test data
     * @param string $comparison_type The type of comparison (equals, less_than, etc.)
     * @param mixed $expected_value The expected value to compare against
     * @param string $condition_type The condition type (if, unless, etc.)
     * @return bool Whether the condition is met
     */
    private function evaluate_condition($actual_value, $comparison_type, $expected_value, $condition_type) {
        $result = false;
        
        // Convert values for numeric comparison if needed
        if (in_array($comparison_type, array('more_than', 'less_than', 'greater_equal', 'less_equal'))) {
            $actual_value = is_numeric($actual_value) ? floatval($actual_value) : 0;
            $expected_value = is_numeric($expected_value) ? floatval($expected_value) : 0;
        }
        
        // Perform the comparison
        switch ($comparison_type) {
            case 'same':
                $result = ($actual_value === $expected_value);
                break;
                
            case 'equals':
                $result = ($actual_value == $expected_value);
                break;
                
            case 'not_equals':
                $result = ($actual_value != $expected_value);
                break;
                
            case 'contains':
                $result = is_string($actual_value) && is_string($expected_value) && 
                          (strpos($actual_value, $expected_value) !== false);
                break;
                
            case 'starts_with':
                $result = is_string($actual_value) && is_string($expected_value) && 
                          (strpos($actual_value, $expected_value) === 0);
                break;
                
            case 'ends_with':
                $result = is_string($actual_value) && is_string($expected_value) && 
                          (substr($actual_value, -strlen($expected_value)) === $expected_value);
                break;
                
            case 'more_than':
                $result = ($actual_value > $expected_value);
                break;
                
            case 'less_than':
                $result = ($actual_value < $expected_value);
                break;
                
            case 'greater_equal':
                $result = ($actual_value >= $expected_value);
                break;
                
            case 'less_equal':
                $result = ($actual_value <= $expected_value);
                break;
                
            default:
                $result = false;
        }
        
        // Apply the condition type (if, unless, etc.)
        switch ($condition_type) {
            case 'if':
                return $result;
                
            case 'unless':
                return !$result;
                
            case 'when':
                return $result;
                
            case 'always':
                return true;
                
            case 'only_if':
                return $result;
                
            default:
                return $result;
        }
    }
    
    /**
     * Execute a trigger action
     *
     * @param string $action_key The action key
     * @param array $test_data The test data
     * @param array $trigger The trigger configuration
     * @return bool Whether the action was executed successfully
     */
    private function execute_action($action_key, $test_data, $trigger) {
        if (!isset($this->available_actions[$action_key])) {
            return false;
        }
        
        $class_name = $this->available_actions[$action_key];
        
        // Create an instance of the action class
        $action = new $class_name();
        
        // Check if the execute method exists
        if (!method_exists($action, 'execute')) {
            return false;
        }
        
        // Execute the action
        return $action->execute(array(
            'test_data' => $test_data,
            'trigger' => $trigger
        ));
    }
    
    /**
     * Get all available trigger actions
     *
     * @return array List of trigger actions
     */
    public function get_available_actions() {
        $actions = array();
        
        foreach ($this->available_actions as $key => $class_name) {
            // Create an instance to get metadata
            $instance = new $class_name();
            
            $actions[$key] = array(
                'key' => $key,
                'name' => isset($instance->name) ? $instance->name : $key,
                'description' => isset($instance->description) ? $instance->description : ''
            );
        }
        
        return $actions;
    }
}