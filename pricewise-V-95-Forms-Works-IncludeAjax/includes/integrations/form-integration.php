<?php
/**
 * Form Integration for PriceWise Plugin
 * 
 * Provides functionality to integrate form submissions with API calls.
 * Handles variable placeholders and processing of form data.
 *
 * @package PriceWise
 * @subpackage Integrations
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle form integration with API calls
 */
class Pricewise_Form_Integration {
    
    /**
     * Instance of this class.
     *
     * @var Pricewise_Form_Integration
     */
    protected static $_instance = null;
    
    /**
     * Main Form Integration Instance.
     *
     * Ensures only one instance of Form Integration is loaded or can be loaded.
     *
     * @return Pricewise_Form_Integration
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the integration
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the integration
     */
    public function init() {
        // Register AJAX handler for form submissions
        add_action('wp_ajax_pricewise_process_form', array($this, 'process_form_ajax'));
        add_action('wp_ajax_nopriv_pricewise_process_form', array($this, 'process_form_ajax'));
        
        // Add form plugins integration
        $this->init_form_plugins_integration();
        
        // Enqueue necessary scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize integration with popular form plugins
     */
    private function init_form_plugins_integration() {
        // Check if Contact Form 7 is active
        if (class_exists('WPCF7')) {
            // Add hook for Contact Form 7 submission
            add_action('wpcf7_before_send_mail', array($this, 'process_cf7_submission'), 10, 3);
        }
        
        // Check if Gravity Forms is active
        if (class_exists('GFForms')) {
            // Add hook for Gravity Forms submission
            add_action('gform_after_submission', array($this, 'process_gravity_forms_submission'), 10, 2);
        }
        
        // Add hook for standard WordPress form submission (comment form, etc.)
        add_action('comment_post', array($this, 'process_comment_submission'), 10, 3);
        
        // Add more hooks for other form plugins as needed
    }
    
    /**
     * Enqueue necessary scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'pricewise-form-integration',
            PRICEWISE_PLUGIN_URL . 'js/form-integration.js',
            array('jquery'),
            PRICEWISE_VERSION,
            true
        );
        
        wp_localize_script('pricewise-form-integration', 'pricewise_form', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pricewise_form_nonce')
        ));
    }
    
    /**
     * Process form submission via AJAX
     */
    public function process_form_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_api_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }
        
        // Get form data
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
        $endpoint_id = isset($_POST['endpoint_id']) ? sanitize_text_field($_POST['endpoint_id']) : '';
        
        // Validate required data
        if (empty($form_data) || empty($api_id) || empty($endpoint_id)) {
            wp_send_json_error(array('message' => 'Required parameters missing.'));
            return;
        }
        
        // Convert serialized form data to associative array
        $processed_form_data = array();
        foreach ($form_data as $field) {
            if (isset($field['name']) && isset($field['value'])) {
                $processed_form_data[$field['name']] = $field['value'];
            }
        }
        
        // Log form data for debugging
        error_log('AJAX Form Data: ' . print_r($processed_form_data, true));
        
        // Process the form submission
        $result = $this->process_form_submission($processed_form_data, $api_id, $endpoint_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'API request successful.',
            'data' => $result
        ));
    }
    
    /**
     * Process Contact Form 7 submission
     *
     * @param WPCF7_ContactForm $contact_form The contact form instance
     * @param bool|array $abort Whether to abort the form submission
     * @param WPCF7_Submission $submission The submission instance
     */
    public function process_cf7_submission($contact_form, $abort, $submission) {
        if ($abort) {
            return;
        }
        
        // Get form data
        $form_data = $submission->get_posted_data();
        
        // Check if this form has API integration configured
        $form_id = $contact_form->id();
        $api_config = $this->get_form_api_config('cf7', $form_id);
        
        if (!$api_config) {
            return;
        }
        
        // Process the form submission
        $result = $this->process_form_submission($form_data, $api_config['api_id'], $api_config['endpoint_id']);
        
        // Store the result in submission object for later use
        $submission->set_response($result);
    }
    
    /**
     * Process Gravity Forms submission
     *
     * @param array $entry The entry that was created
     * @param array $form The form object
     */
    public function process_gravity_forms_submission($entry, $form) {
        // Get form data
        $form_data = array();
        
        foreach ($form['fields'] as $field) {
            $field_id = $field->id;
            if (isset($entry[$field_id])) {
                $form_data[$field->adminLabel ?: 'input_' . $field_id] = $entry[$field_id];
            }
        }
        
        // Check if this form has API integration configured
        $form_id = $form['id'];
        $api_config = $this->get_form_api_config('gravity_forms', $form_id);
        
        if (!$api_config) {
            return;
        }
        
        // Process the form submission
        $result = $this->process_form_submission($form_data, $api_config['api_id'], $api_config['endpoint_id']);
        
        // You might want to store the result in entry meta
        if (function_exists('gform_add_meta')) {
            gform_add_meta($entry['id'], 'pricewise_api_result', $result);
        }
    }
    
    /**
     * Process comment submission
     *
     * @param int $comment_id The comment ID
     * @param int $comment_approved Whether the comment is approved
     * @param array $commentdata The comment data
     */
    public function process_comment_submission($comment_id, $comment_approved, $commentdata) {
        // Get comment data
        $form_data = array(
            'comment_post_ID' => $commentdata['comment_post_ID'],
            'comment_author' => $commentdata['comment_author'],
            'comment_author_email' => $commentdata['comment_author_email'],
            'comment_content' => $commentdata['comment_content'],
        );
        
        // Check if comment form has API integration configured
        $api_config = $this->get_form_api_config('comment', 'default');
        
        if (!$api_config) {
            return;
        }
        
        // Process the form submission
        $result = $this->process_form_submission($form_data, $api_config['api_id'], $api_config['endpoint_id']);
        
        // Store the result in comment meta
        add_comment_meta($comment_id, 'pricewise_api_result', $result);
    }
    
    /**
     * Process form submission
     *
     * @param array $form_data The form data
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID
     * @return mixed|WP_Error The API response or error
     */
    public function process_form_submission($form_data, $api_id, $endpoint_id) {
        // Sanitize and validate data
        $form_data = $this->sanitize_form_data($form_data);
        $api_id = sanitize_key($api_id);
        $endpoint_id = sanitize_key($endpoint_id);
        
        // Check if API and endpoint exist
        $api = Pricewise_API_Settings::get_api($api_id);
        if (!$api) {
            return new WP_Error('api_not_found', 'API configuration not found.');
        }
        
        $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
        if (!$endpoint) {
            return new WP_Error('endpoint_not_found', 'API endpoint not found.');
        }
        
        // Check if API and endpoint are active
        if (isset($api['active']) && !$api['active']) {
            return new WP_Error('api_inactive', 'API is inactive.');
        }
        
        if (isset($endpoint['active']) && !$endpoint['active']) {
            return new WP_Error('endpoint_inactive', 'API endpoint is inactive.');
        }
        
        // Process API request
        return $this->make_api_request($api, $endpoint, $form_data);
    }
    
    /**
     * Make API request
     *
     * @param array $api The API configuration
     * @param array $endpoint The endpoint configuration
     * @param array $form_data The form data
     * @return mixed|WP_Error The API response or error
     */
    private function make_api_request($api, $endpoint, $form_data) {
        // Create API data for request
        $api_data = array(
            'name' => $api['name'],
            'id_name' => $api['id_name'],
            'api_key' => $api['api_key'],
            'base_endpoint' => $api['base_endpoint'],
            'active' => $api['active'],
        );
        
        // Set up advanced config with endpoint data
        $api_data['advanced_config'] = array(
            'endpoint' => $endpoint['path']
        );
        
        // Merge endpoint config
        if (isset($endpoint['config'])) {
            $api_data['advanced_config'] = array_merge($api_data['advanced_config'], $endpoint['config']);
        }
        
        // DEBUG - Log form data
        error_log('Form Data before processing: ' . print_r($form_data, true));
        
        // Extract parameter definitions from endpoint config
        $param_definitions = array();
        if (isset($endpoint['config']['params']) && is_array($endpoint['config']['params'])) {
            foreach ($endpoint['config']['params'] as $param) {
                if (isset($param['name'])) {
                    $param_definitions[$param['name']] = $param;
                }
            }
        }
        
        // Initialize final parameters array
        $processed_params = array();
        
        // First, use form data directly for any parameters defined in the endpoint
        foreach ($form_data as $key => $value) {
            if (isset($param_definitions[$key])) {
                $processed_params[$key] = $value;
            }
        }
        
        // Then fill in any missing parameters from the endpoint config
        foreach ($param_definitions as $param_name => $param) {
            if (!isset($processed_params[$param_name]) && isset($param['value'])) {
                // Try to replace any placeholders in the default value
                $processed_params[$param_name] = $this->replace_placeholders($param['value'], $form_data);
            }
        }
        
        // Finally, include any remaining form fields as parameters (excluding WordPress internal fields)
        foreach ($form_data as $key => $value) {
            if (!isset($processed_params[$key]) && !in_array($key, array('pricewise_api_nonce', '_wp_http_referer'))) {
                $processed_params[$key] = $value;
            }
        }
        
        // DEBUG - Log processed parameters
        error_log('Final processed parameters: ' . print_r($processed_params, true));
        
        // Create API request function
        $request_func = function($params) use ($api_data) {
            return $this->execute_api_request($api_data, $params);
        };
        
        // Get API ID and endpoint for cache
        $api_id = $api['id_name'];
        $endpoint_path = $endpoint['path'];
        
        // Use cache integration if available
        if (function_exists('pricewise_cache_api_integration')) {
            return pricewise_cache_api_integration(false, $api_id, $endpoint_path, $processed_params, $request_func);
        } else {
            // Otherwise make direct request
            return $request_func($processed_params);
        }
    }
    
    /**
     * Execute API request
     *
     * @param array $api_data The API configuration
     * @param array $params The request parameters
     * @return mixed|WP_Error The API response or error
     */
    private function execute_api_request($api_data, $params) {
        // Get method and body config
        $method = isset($api_data['advanced_config']['method']) ? 
                  strtoupper($api_data['advanced_config']['method']) : 'GET';
        
        $body_enabled = isset($api_data['advanced_config']['body']['enabled']) ? 
                        $api_data['advanced_config']['body']['enabled'] : false;
        
        $body_type = isset($api_data['advanced_config']['body']['type']) ? 
                    $api_data['advanced_config']['body']['type'] : 'json';
        
        $body_content = isset($api_data['advanced_config']['body']['content']) ? 
                        $api_data['advanced_config']['body']['content'] : '';
        
        // Prepare request URL
        $url = rtrim($api_data['base_endpoint'], '/') . '/' . ltrim($api_data['advanced_config']['endpoint'], '/');
        
        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // Prepare headers
        $headers = array();
        
        // Add API key header if not disabled
        $disable_headers = isset($api_data['advanced_config']['auth']['disable_headers']) ? 
                          $api_data['advanced_config']['auth']['disable_headers'] : false;
        
        if (!$disable_headers && isset($api_data['advanced_config']['auth']['headers'])) {
            foreach ($api_data['advanced_config']['auth']['headers'] as $header) {
                if (!empty($header['name']) && isset($header['value'])) {
                    $headers[$header['name']] = $header['value'];
                }
            }
        }
        
        // Prepare request arguments
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => isset($api_data['advanced_config']['request_timeout']) ? 
                        intval($api_data['advanced_config']['request_timeout']) : 30
        );
        
        // Add body for non-GET requests
        if ($method !== 'GET' && $body_enabled) {
            if ($body_type === 'json') {
                $args['headers']['Content-Type'] = 'application/json';
                $args['body'] = $body_content;
            } elseif ($body_type === 'form') {
                $args['body'] = $body_content;
            } else {
                $args['body'] = $body_content;
            }
        } elseif ($method !== 'GET' && !empty($params)) {
            // If body is not enabled but we have params, use them as the body
            $args['body'] = $params;
        }
        
        // DEBUG - Log request details
        error_log('API Request URL: ' . $url);
        error_log('API Request Args: ' . print_r($args, true));
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // DEBUG - Log response
        error_log('API Response Code: ' . $response_code);
        error_log('API Response Body: ' . wp_remote_retrieve_body($response));
        
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf('API returned error: %s', $response_code),
                array('response' => $response)
            );
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        
        // Process response based on format
        $format = isset($api_data['advanced_config']['response_format']) ? 
                 $api_data['advanced_config']['response_format'] : 'json';
        
        if (function_exists('pricewise_process_api_response')) {
            return pricewise_process_api_response($body, $format);
        } else {
            // Basic processing if the helper function is not available
            if ($format === 'json') {
                return json_decode($body, true);
            } elseif ($format === 'array') {
                return json_decode($body, true);
            } elseif ($format === 'object') {
                return json_decode($body);
            } else {
                return $body;
            }
        }
    }
    
    /**
     * Replace placeholders in a string with form data values
     *
     * @param string $string The string with placeholders
     * @param array $form_data The form data
     * @return string The string with replaced placeholders
     */
    public function replace_placeholders($string, $form_data) {
        if (empty($string) || !is_string($string)) {
            return $string;
        }
        
        // Replace {field_name} placeholders
        preg_match_all('/{([a-z0-9_\-]+)}/i', $string, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $field_name) {
                $placeholder = $matches[0][$index];
                $value = isset($form_data[$field_name]) ? $form_data[$field_name] : '';
                $string = str_replace($placeholder, $value, $string);
            }
        }
        
        return $string;
    }
    
    /**
     * Sanitize form data
     *
     * @param array $form_data The form data
     * @return array Sanitized form data
     */
    private function sanitize_form_data($form_data) {
        $sanitized_data = array();
        
        foreach ($form_data as $key => $value) {
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                $sanitized_data[$key] = $this->sanitize_form_data($value);
            } else {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized_data;
    }
    
    /**
     * Get form API configuration
     *
     * @param string $form_type The form type (cf7, gravity_forms, etc.)
     * @param string|int $form_id The form ID
     * @return array|false The API configuration or false if not found
     */
    public function get_form_api_config($form_type, $form_id) {
        $form_configs = get_option('pricewise_form_api_configs', array());
        
        $key = $form_type . '_' . $form_id;
        
        if (isset($form_configs[$key])) {
            return $form_configs[$key];
        }
        
        return false;
    }
    
    /**
     * Save form API configuration
     *
     * @param string $form_type The form type (cf7, gravity_forms, etc.)
     * @param string|int $form_id The form ID
     * @param array $config The API configuration
     * @return bool Whether the configuration was saved successfully
     */
    public function save_form_api_config($form_type, $form_id, $config) {
        $form_configs = get_option('pricewise_form_api_configs', array());
        
        $key = $form_type . '_' . $form_id;
        
        $form_configs[$key] = $config;
        
        return update_option('pricewise_form_api_configs', $form_configs);
    }
    
    /**
     * Delete form API configuration
     *
     * @param string $form_type The form type (cf7, gravity_forms, etc.)
     * @param string|int $form_id The form ID
     * @return bool Whether the configuration was deleted successfully
     */
    public function delete_form_api_config($form_type, $form_id) {
        $form_configs = get_option('pricewise_form_api_configs', array());
        
        $key = $form_type . '_' . $form_id;
        
        if (isset($form_configs[$key])) {
            unset($form_configs[$key]);
            return update_option('pricewise_form_api_configs', $form_configs);
        }
        
        return false;
    }
}

// Initialize the form integration
function pricewise_form_integration() {
    return Pricewise_Form_Integration::instance();
}

// Start the integration
pricewise_form_integration();