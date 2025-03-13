<?php
/**
 * Template Tags for PriceWise Plugin
 * 
 * Provides template functions for displaying API data in themes.
 *
 * @package PriceWise
 * @subpackage Template
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Display API data
 *
 * @param string $api_id The API ID
 * @param string $endpoint_id The endpoint ID
 * @param array $params Optional. Parameters for the API request
 * @param array $args Optional. Display arguments
 * @return void
 */
function pricewise_display_api_data($api_id, $endpoint_id, $params = array(), $args = array()) {
    $defaults = array(
        'format' => 'html',        // Output format (html, raw, json)
        'template' => '',          // Template for HTML output
        'cache' => true,           // Whether to use cache
        'before' => '',            // HTML before the output
        'after' => '',             // HTML after the output
        'field' => '',             // Field path to extract from response
        'fallback' => '',          // Fallback content if API call fails
        'class' => '',             // CSS class for the container
        'echo' => true             // Whether to echo or return the output
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Get API and endpoint
    $api = Pricewise_API_Settings::get_api($api_id);
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$api || !$endpoint) {
        if ($args['echo']) {
            echo $args['fallback'];
            return;
        }
        return $args['fallback'];
    }
    
    // Check if API and endpoint are active
    if ((isset($api['active']) && !$api['active']) || (isset($endpoint['active']) && !$endpoint['active'])) {
        if ($args['echo']) {
            echo $args['fallback'];
            return;
        }
        return $args['fallback'];
    }
    
    // Process API request
    if (class_exists('Pricewise_API_Shortcodes')) {
        $api_shortcodes = pricewise_api_shortcodes();
        $result = $api_shortcodes->process_api_request($api, $endpoint, $params, $args['cache']);
        
        if (is_wp_error($result)) {
            if ($args['echo']) {
                echo $args['fallback'];
                return;
            }
            return $args['fallback'];
        }
        
        // Extract field if specified
        if (!empty($args['field'])) {
            $result = $api_shortcodes->extract_field_from_api_response($result, $args['field']);
        }
        
        // Format the output
        $output = $api_shortcodes->format_api_output($result, $args['format'], $args['template']);
        
        // Add container with class if needed
        if (!empty($args['class'])) {
            $output = '<div class="' . esc_attr($args['class']) . '">' . $output . '</div>';
        }
        
        // Add before/after content
        $output = $args['before'] . $output . $args['after'];
        
        if ($args['echo']) {
            echo $output;
            return;
        }
        return $output;
    } else {
        if ($args['echo']) {
            echo $args['fallback'];
            return;
        }
        return $args['fallback'];
    }
}

/**
 * Display API form
 *
 * @param string $api_id The API ID
 * @param string $endpoint_id The endpoint ID
 * @param array $args Optional. Form arguments
 * @return void|string HTML output of the form
 */
function pricewise_display_api_form($api_id, $endpoint_id, $args = array()) {
    $defaults = array(
        'submit_text' => 'Submit',       // Text for submit button
        'result_template' => '',         // Template for results
        'loading_text' => 'Loading...',  // Text to show during loading
        'redirect' => '',                // URL to redirect after successful submission
        'class' => '',                   // CSS class for the form
        'button_class' => 'button',      // CSS class for submit button
        'fields_template' => '',         // Template for form fields
        'echo' => true                   // Whether to echo or return the output
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Get API and endpoint
    $api = Pricewise_API_Settings::get_api($api_id);
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$api || !$endpoint) {
        if ($args['echo']) {
            echo '<div class="pricewise-error">API or endpoint not found.</div>';
            return;
        }
        return '<div class="pricewise-error">API or endpoint not found.</div>';
    }
    
    // Check if API and endpoint are active
    if ((isset($api['active']) && !$api['active']) || (isset($endpoint['active']) && !$endpoint['active'])) {
        if ($args['echo']) {
            echo '<div class="pricewise-error">API or endpoint is inactive.</div>';
            return;
        }
        return '<div class="pricewise-error">API or endpoint is inactive.</div>';
    }
    
    // Create unique ID for this form
    $form_id = 'pricewise-api-form-' . md5($api_id . $endpoint_id . microtime());
    
    // Generate form fields
    $form_fields = pricewise_generate_api_form_fields($endpoint);
    
    // Build the form HTML
    $output = '<div class="pricewise-api-form-container">';
    $output .= '<form id="' . esc_attr($form_id) . '" class="pricewise-api-form pricewise-ajax-form ' . esc_attr($args['class']) . '" data-api="' . esc_attr($api_id) . '" data-endpoint="' . esc_attr($endpoint_id) . '" data-redirect="' . esc_attr($args['redirect']) . '">';
    
    // Add nonce for security
    $output .= wp_nonce_field('pricewise_api_form', 'pricewise_api_nonce', true, false);
    
    // Add form fields
    if (!empty($args['fields_template'])) {
        // Use custom template
        $template = $args['fields_template'];
        
        // Replace field placeholders
        foreach ($form_fields as $field_name => $field_html) {
            $template = str_replace('{{' . $field_name . '}}', $field_html, $template);
        }
        
        $output .= $template;
    } else {
        // Default form layout
        $output .= '<div class="pricewise-form-fields">';
        foreach ($form_fields as $field_html) {
            $output .= $field_html;
        }
        $output .= '</div>';
    }
    
    // Add submit button
    $output .= '<div class="pricewise-form-submit">';
    $output .= '<button type="submit" class="' . esc_attr($args['button_class']) . '">' . esc_html($args['submit_text']) . '</button>';
    $output .= '<span class="pricewise-loading" style="display: none;">' . esc_html($args['loading_text']) . '</span>';
    $output .= '</div>';
    
    $output .= '</form>';
    
    // Add results container
    $output .= '<div class="pricewise-api-results" style="display: none;" data-template="' . esc_attr($args['result_template']) . '"></div>';
    
    $output .= '</div>'; // Close form container
    
    if ($args['echo']) {
        echo $output;
        return;
    }
    
    return $output;
}

/**
 * Generate form fields for API endpoint
 *
 * @param array $endpoint The endpoint configuration
 * @return array Associative array of field name => HTML
 */
function pricewise_generate_api_form_fields($endpoint) {
    $fields = array();
    
    // Get parameters from endpoint configuration
    if (isset($endpoint['config']['params']) && is_array($endpoint['config']['params'])) {
        foreach ($endpoint['config']['params'] as $param) {
            if (!empty($param['name'])) {
                $field_id = 'pricewise-form-field-' . sanitize_key($param['name']);
                $field_name = $param['name'];
                $field_value = isset($param['value']) ? $param['value'] : '';
                
                // Generate field HTML
                $field_html = '<div class="pricewise-form-field">';
                $field_html .= '<label for="' . esc_attr($field_id) . '">' . esc_html(ucfirst($field_name)) . '</label>';
                $field_html .= '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field_value) . '">';
                $field_html .= '</div>';
                
                $fields[$field_name] = $field_html;
            }
        }
    }
    
    return $fields;
}

/**
 * Get API data
 *
 * @param string $api_id The API ID
 * @param string $endpoint_id The endpoint ID
 * @param array $params Optional. Parameters for the API request
 * @param array $args Optional. Request arguments
 * @return mixed|WP_Error API response or error
 */
function pricewise_get_api_data($api_id, $endpoint_id, $params = array(), $args = array()) {
    $defaults = array(
        'cache' => true,      // Whether to use cache
        'field' => '',        // Field path to extract from response
        'format' => 'array'   // Return format (array, object, raw)
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Get API and endpoint
    $api = Pricewise_API_Settings::get_api($api_id);
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$api || !$endpoint) {
        return new WP_Error('api_not_found', 'API or endpoint not found.');
    }
    
    // Check if API and endpoint are active
    if ((isset($api['active']) && !$api['active']) || (isset($endpoint['active']) && !$endpoint['active'])) {
        return new WP_Error('api_inactive', 'API or endpoint is inactive.');
    }
    
    // Process API request
    if (class_exists('Pricewise_API_Shortcodes')) {
        $api_shortcodes = pricewise_api_shortcodes();
        $result = $api_shortcodes->process_api_request($api, $endpoint, $params, $args['cache']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Extract field if specified
        if (!empty($args['field'])) {
            $result = $api_shortcodes->extract_field_from_api_response($result, $args['field']);
        }
        
        // Format the result if needed
        if ($args['format'] === 'array' && !is_array($result)) {
            if (is_string($result) && function_exists('json_decode')) {
                $decoded = json_decode($result, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            return array($result);
        } elseif ($args['format'] === 'object' && !is_object($result)) {
            if (is_array($result)) {
                return (object) $result;
            } elseif (is_string($result) && function_exists('json_decode')) {
                $decoded = json_decode($result);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
            return (object) array('value' => $result);
        }
        
        return $result;
    }
    
    return new WP_Error('api_shortcodes_missing', 'API shortcodes functionality is not available.');
}

/**
 * Check if API and endpoint exist and are active
 *
 * @param string $api_id The API ID
 * @param string $endpoint_id The endpoint ID
 * @return bool Whether API and endpoint exist and are active
 */
function pricewise_api_is_available($api_id, $endpoint_id) {
    // Get API and endpoint
    $api = Pricewise_API_Settings::get_api($api_id);
    $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
    
    if (!$api || !$endpoint) {
        return false;
    }
    
    // Check if API and endpoint are active
    if ((isset($api['active']) && !$api['active']) || (isset($endpoint['active']) && !$endpoint['active'])) {
        return false;
    }
    
    return true;
}

/**
 * Get list of available APIs
 *
 * @param bool $active_only Optional. Whether to return only active APIs
 * @return array List of APIs
 */
function pricewise_get_available_apis($active_only = true) {
    $apis = Pricewise_API_Settings::get_apis();
    
    if ($active_only) {
        foreach ($apis as $api_id => $api) {
            if (isset($api['active']) && !$api['active']) {
                unset($apis[$api_id]);
            }
        }
    }
    
    return $apis;
}

/**
 * Get list of available endpoints for an API
 *
 * @param string $api_id The API ID
 * @param bool $active_only Optional. Whether to return only active endpoints
 * @return array List of endpoints
 */
function pricewise_get_available_endpoints($api_id, $active_only = true) {
    $endpoints = Pricewise_API_Settings::get_endpoints($api_id);
    
    if ($active_only) {
        foreach ($endpoints as $endpoint_id => $endpoint) {
            if (isset($endpoint['active']) && !$endpoint['active']) {
                unset($endpoints[$endpoint_id]);
            }
        }
    }
    
    return $endpoints;
}