<?php
/**
 * AJAX Functions for PriceWise Plugin
 * 
 * Provides AJAX handlers for form integration and API data retrieval.
 *
 * @package PriceWise
 * @subpackage AJAX
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register AJAX handlers
 */
function pricewise_register_ajax_handlers() {
    // Form processing
    add_action('wp_ajax_pricewise_process_form', 'pricewise_process_form_ajax_handler');
    add_action('wp_ajax_nopriv_pricewise_process_form', 'pricewise_process_form_ajax_handler');
    
    // Get API data
    add_action('wp_ajax_pricewise_get_api_data', 'pricewise_get_api_data_ajax_handler');
    add_action('wp_ajax_nopriv_pricewise_get_api_data', 'pricewise_get_api_data_ajax_handler');
    
    // Get endpoints for an API
    add_action('wp_ajax_pricewise_get_endpoints', 'pricewise_get_endpoints_ajax_handler');
    add_action('wp_ajax_nopriv_pricewise_get_endpoints', 'pricewise_get_endpoints_ajax_handler');
    
    // Get parameters for an endpoint
    add_action('wp_ajax_pricewise_get_endpoint_params', 'pricewise_get_endpoint_params_ajax_handler');
    add_action('wp_ajax_nopriv_pricewise_get_endpoint_params', 'pricewise_get_endpoint_params_ajax_handler');
}
add_action('init', 'pricewise_register_ajax_handlers');

/**
 * Process form submission via AJAX
 */
function pricewise_process_form_ajax_handler() {
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
    
    
    // Process the form submission
    if (class_exists('Pricewise_Form_Integration')) {
        $form_integration = pricewise_form_integration();
        $result = $form_integration->process_form_submission($processed_form_data, $api_id, $endpoint_id);
        
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
    } else {
        wp_send_json_error(array('message' => 'Form integration is not available.'));
    }
}

/**
 * Get API data via AJAX
 */
function pricewise_get_api_data_ajax_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_api_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Get parameters
    $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
    $endpoint_id = isset($_POST['endpoint_id']) ? sanitize_text_field($_POST['endpoint_id']) : '';
    $field_path = isset($_POST['field_path']) ? sanitize_text_field($_POST['field_path']) : '';
    $use_cache = isset($_POST['use_cache']) ? (bool)$_POST['use_cache'] : true;
    $params = isset($_POST['params']) ? $_POST['params'] : array();
    
    // Validate required parameters
    if (empty($api_id) || empty($endpoint_id)) {
        wp_send_json_error(array('message' => 'API ID and Endpoint ID are required.'));
        return;
    }
    
    // Get API and endpoint
    $api = Pricewise_API_Settings::get_api($api_id);
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$api || !$endpoint) {
        wp_send_json_error(array('message' => 'API or endpoint not found.'));
        return;
    }
    
    // Check if API and endpoint are active
    if (isset($api['active']) && !$api['active']) {
        wp_send_json_error(array('message' => 'API is inactive.'));
        return;
    }
    
    if (isset($endpoint['active']) && !$endpoint['active']) {
        wp_send_json_error(array('message' => 'API endpoint is inactive.'));
        return;
    }
    
    // Process API request
    if (class_exists('Pricewise_API_Shortcodes')) {
        $api_shortcodes = pricewise_api_shortcodes();
        $result = $api_shortcodes->process_api_request($api, $endpoint, $params, $use_cache);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
            return;
        }
        
        // Extract field if specified
        $field_value = '';
        if (!empty($field_path)) {
            $field_value = $api_shortcodes->extract_field_from_api_response($result, $field_path);
            
            wp_send_json_success(array(
                'field_value' => $field_value,
                'data' => $result
            ));
        } else {
            wp_send_json_success(array('data' => $result));
        }
    } else {
        wp_send_json_error(array('message' => 'API shortcodes functionality is not available.'));
    }
}

/**
 * Get endpoints for an API via AJAX
 */
function pricewise_get_endpoints_ajax_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_get_endpoints')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Get API ID
    $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
    
    if (empty($api_id)) {
        wp_send_json_error(array('message' => 'API ID is required.'));
        return;
    }
    
    // Get endpoints for the API
    $endpoints = Pricewise_API_Settings::get_endpoints($api_id);
    
    if (empty($endpoints)) {
        wp_send_json_error(array('message' => 'No endpoints found for this API.'));
        return;
    }
    
    // Format endpoints for select field
    $endpoint_options = array();
    foreach ($endpoints as $endpoint_id => $endpoint) {
        if (isset($endpoint['active']) && !$endpoint['active']) {
            continue; // Skip inactive endpoints
        }
        
        $endpoint_options[$endpoint_id] = $endpoint['name'];
    }
    
    wp_send_json_success($endpoint_options);
}

/**
 * Get parameters for an endpoint via AJAX
 */
function pricewise_get_endpoint_params_ajax_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_get_endpoints')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Get API ID and endpoint ID
    $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
    $endpoint_id = isset($_POST['endpoint_id']) ? sanitize_text_field($_POST['endpoint_id']) : '';
    
    if (empty($api_id) || empty($endpoint_id)) {
        wp_send_json_error(array('message' => 'API ID and Endpoint ID are required.'));
        return;
    }
    
    // Get endpoint configuration
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$endpoint) {
        wp_send_json_error(array('message' => 'Endpoint not found.'));
        return;
    }
    
    // Get parameters from endpoint configuration
    $params = array();
    
    if (isset($endpoint['config']['params']) && is_array($endpoint['config']['params'])) {
        foreach ($endpoint['config']['params'] as $param) {
            if (!empty($param['name'])) {
                $params[] = array(
                    'name' => $param['name'],
                    'value' => isset($param['value']) ? $param['value'] : '',
                    'description' => 'Parameter for API request. Use {field_name} for form field values.'
                );
            }
        }
    }
    
    wp_send_json_success($params);
}