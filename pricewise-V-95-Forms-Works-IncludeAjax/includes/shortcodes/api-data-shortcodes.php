<?php
/**
 * API Data Shortcodes for PriceWise Plugin
 * 
 * Provides shortcodes for displaying and interacting with API data
 *
 * @package PriceWise
 * @subpackage Shortcodes
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API Data Shortcodes
 */
class Pricewise_API_Shortcodes {
    
    /**
     * Instance of this class.
     *
     * @var Pricewise_API_Shortcodes
     */
    protected static $_instance = null;
    
    /**
     * Main API Shortcodes Instance.
     *
     * Ensures only one instance of API Shortcodes is loaded or can be loaded.
     *
     * @return Pricewise_API_Shortcodes
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
        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));
        
        // Enqueue necessary scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Main API data shortcode
        add_shortcode('pricewise_api', array($this, 'api_data_shortcode'));
        
        // API request form shortcode
        add_shortcode('pricewise_api_form', array($this, 'api_form_shortcode'));
        
        // API data display shortcode
        add_shortcode('pricewise_api_display', array($this, 'api_display_shortcode'));
    }
    
    /**
     * Enqueue necessary scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'pricewise-api-shortcodes',
            PRICEWISE_PLUGIN_URL . 'js/api-shortcodes.js',
            array('jquery'),
            PRICEWISE_VERSION,
            true
        );
        
        wp_localize_script('pricewise-api-shortcodes', 'pricewise_api', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pricewise_api_nonce')
        ));
        
        wp_enqueue_style(
            'pricewise-api-shortcodes',
            PRICEWISE_PLUGIN_URL . 'style/api-shortcodes.css',
            array(),
            PRICEWISE_VERSION
        );
    }
    
    /**
     * API data shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Shortcode output
     */
    public function api_data_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'api' => '',                // API ID
            'endpoint' => '',           // Endpoint ID
            'field' => '',              // Field path in API response
            'format' => 'raw',          // Output format (raw, html, json)
            'template' => '',           // Template for HTML output
            'placeholder' => 'Loading...', // Placeholder text while loading
            'cache' => 'yes',           // Whether to use cache
            'params' => '',             // Comma-separated list of param=value pairs
        ), $atts, 'pricewise_api');
        
        // Validate required attributes
        if (empty($atts['api']) || empty($atts['endpoint'])) {
            return '<div class="pricewise-api-error">Error: API and endpoint are required attributes.</div>';
        }
        
        // Create unique ID for this instance
        $instance_id = 'pricewise-api-' . md5(serialize($atts));
        
        // Parse parameters
        $params = array();
        if (!empty($atts['params'])) {
            $param_pairs = explode(',', $atts['params']);
            foreach ($param_pairs as $pair) {
                $pair = trim($pair);
                if (strpos($pair, '=') !== false) {
                    list($key, $value) = explode('=', $pair, 2);
                    $params[trim($key)] = trim($value);
                }
            }
        }
        
        // Check if we're using cache
        $use_cache = ($atts['cache'] === 'yes');
        
        // Get the API and endpoint
        $api = Pricewise_API_Settings::get_api($atts['api']);
        $endpoint = Pricewise_API_Settings::get_endpoint($atts['api'], $atts['endpoint']);
        
        if (!$api || !$endpoint) {
            return '<div class="pricewise-api-error">Error: API or endpoint not found.</div>';
        }
        
        // Check if API and endpoint are active
        if (isset($api['active']) && !$api['active']) {
            return '<div class="pricewise-api-error">Error: API is inactive.</div>';
        }
        
        if (isset($endpoint['active']) && !$endpoint['active']) {
            return '<div class="pricewise-api-error">Error: API endpoint is inactive.</div>';
        }
        
        // Process API request
        $result = $this->process_api_request($api, $endpoint, $params, $use_cache);
        
        if (is_wp_error($result)) {
            return '<div class="pricewise-api-error">Error: ' . esc_html($result->get_error_message()) . '</div>';
        }
        
        // Extract field if specified
        if (!empty($atts['field'])) {
            $result = $this->extract_field_from_api_response($result, $atts['field']);
        }
        
        // Format the output
        $output = $this->format_api_output($result, $atts['format'], $atts['template']);
        
        return '<div id="' . esc_attr($instance_id) . '" class="pricewise-api-data">' . $output . '</div>';
    }
    
    /**
     * API form shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Shortcode output
     */
    public function api_form_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'api' => '',                // API ID
            'endpoint' => '',           // Endpoint ID
            'template' => '',           // Template for form
            'result_template' => '',    // Template for results
            'submit_text' => 'Submit',  // Text for submit button
            'loading_text' => 'Loading...', // Text to show during loading
            'redirect' => '',           // URL to redirect after successful submission
            'form_class' => '',         // Additional CSS class for the form
            'submit_class' => 'button', // CSS class for submit button
        ), $atts, 'pricewise_api_form');
        
        // Validate required attributes
        if (empty($atts['api']) || empty($atts['endpoint'])) {
            return '<div class="pricewise-api-error">Error: API and endpoint are required attributes.</div>';
        }
        
        // Get the API and endpoint
        $api = Pricewise_API_Settings::get_api($atts['api']);
        $endpoint = Pricewise_API_Settings::get_endpoint($atts['api'], $atts['endpoint']);
        
        if (!$api || !$endpoint) {
            return '<div class="pricewise-api-error">Error: API or endpoint not found.</div>';
        }
        
        // Check if API and endpoint are active
        if (isset($api['active']) && !$api['active']) {
            return '<div class="pricewise-api-error">Error: API is inactive.</div>';
        }
        
        if (isset($endpoint['active']) && !$endpoint['active']) {
            return '<div class="pricewise-api-error">Error: API endpoint is inactive.</div>';
        }
        
        // Create unique ID for this form
        $form_id = 'pricewise-api-form-' . md5($atts['api'] . $atts['endpoint'] . microtime());
        
        // Generate form fields based on API parameters
        $form_fields = $this->generate_form_fields($endpoint);
        
        // Build the form HTML
        $output = '<div class="pricewise-api-form-container">';
        $output .= '<form id="' . esc_attr($form_id) . '" class="pricewise-api-form ' . esc_attr($atts['form_class']) . '" data-api="' . esc_attr($atts['api']) . '" data-endpoint="' . esc_attr($atts['endpoint']) . '" data-redirect="' . esc_attr($atts['redirect']) . '">';
        
        // Add nonce for security
        $output .= wp_nonce_field('pricewise_api_form', 'pricewise_api_nonce', true, false);
        
        // Add the form fields
        if (!empty($content)) {
            // Use content as form template if provided
            $output .= do_shortcode($content);
        } elseif (!empty($atts['template'])) {
            // Use custom template if provided
            $template = $atts['template'];
            // Replace field placeholders with actual fields
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
        $output .= '<button type="submit" class="' . esc_attr($atts['submit_class']) . '">' . esc_html($atts['submit_text']) . '</button>';
        $output .= '<span class="pricewise-loading" style="display: none;">' . esc_html($atts['loading_text']) . '</span>';
        $output .= '</div>';
        
        $output .= '</form>';
        
        // Add results container
        $output .= '<div class="pricewise-api-results" style="display: none;" data-template="' . esc_attr($atts['result_template']) . '"></div>';
        
        $output .= '</div>'; // Close form container
        
        // Add JavaScript for form submission
        $output .= '<script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#' . esc_js($form_id) . '").on("submit", function(e) {
                    e.preventDefault();
                    
                    var form = $(this);
                    var resultsContainer = form.siblings(".pricewise-api-results");
                    var loadingElement = form.find(".pricewise-loading");
                    var submitButton = form.find("button[type=submit]");
                    
                    // Show loading indicator
                    submitButton.prop("disabled", true);
                    loadingElement.show();
                    
                    // Get form data
                    var formData = form.serializeArray();
                    var apiId = form.data("api");
                    var endpointId = form.data("endpoint");
                    var redirectUrl = form.data("redirect");
                    
                    // Make AJAX request
                    $.ajax({
                        url: pricewise_api.ajax_url,
                        type: "POST",
                        data: {
                            action: "pricewise_process_form",
                            nonce: pricewise_api.nonce,
                            form_data: formData,
                            api_id: apiId,
                            endpoint_id: endpointId
                        },
                        success: function(response) {
                            // Hide loading indicator
                            submitButton.prop("disabled", false);
                            loadingElement.hide();
                            
                            if (response.success) {
                                // Handle successful response
                                if (redirectUrl) {
                                    // Redirect if URL is provided
                                    window.location.href = redirectUrl;
                                } else {
                                    // Display results
                                    var resultTemplate = resultsContainer.data("template");
                                    
                                    if (resultTemplate) {
                                        // Use custom template
                                        var resultHtml = resultTemplate;
                                        
                                        // Replace data placeholders
                                        if (response.data && response.data.data) {
                                            resultHtml = replaceDataPlaceholders(resultHtml, response.data.data);
                                        }
                                        
                                        resultsContainer.html(resultHtml);
                                    } else {
                                        // Use default template
                                        var resultHtml = "<div class=\"pricewise-api-success\">";
                                        resultHtml += "<h3>API Response:</h3>";
                                        resultHtml += "<pre>" + JSON.stringify(response.data.data, null, 2) + "</pre>";
                                        resultHtml += "</div>";
                                        
                                        resultsContainer.html(resultHtml);
                                    }
                                    
                                    resultsContainer.show();
                                }
                            } else {
                                // Handle error response
                                var errorHtml = "<div class=\"pricewise-api-error\">";
                                errorHtml += "<p>Error: " + (response.data ? response.data.message : "Unknown error") + "</p>";
                                errorHtml += "</div>";
                                
                                resultsContainer.html(errorHtml).show();
                            }
                        },
                        error: function() {
                            // Hide loading indicator
                            submitButton.prop("disabled", false);
                            loadingElement.hide();
                            
                            // Display error message
                            var errorHtml = "<div class=\"pricewise-api-error\">";
                            errorHtml += "<p>Error: Could not connect to the server. Please try again later.</p>";
                            errorHtml += "</div>";
                            
                            resultsContainer.html(errorHtml).show();
                        }
                    });
                });
                
                // Function to replace data placeholders in template
                function replaceDataPlaceholders(template, data, prefix) {
                    prefix = prefix || "data";
                    
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            var placeholder = "{{" + prefix + "." + key + "}}";
                            var value = data[key];
                            
                            if (typeof value === "object" && value !== null) {
                                // Recursively process nested objects
                                template = replaceDataPlaceholders(template, value, prefix + "." + key);
                                
                                // Also replace the object itself (as JSON)
                                template = template.replace(new RegExp(placeholder, "g"), JSON.stringify(value));
                            } else {
                                // Replace simple value
                                template = template.replace(new RegExp(placeholder, "g"), value);
                            }
                        }
                    }
                    
                    return template;
                }
            });
        </script>';
        
        return $output;
    }
    
    /**
     * API display shortcode
     *
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Shortcode output
     */
    public function api_display_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'api' => '',                // API ID
            'endpoint' => '',           // Endpoint ID
            'cache' => 'yes',           // Whether to use cache
            'params' => '',             // Comma-separated list of param=value pairs
            'template' => '',           // Template for rendering results
            'placeholder' => 'Loading...', // Placeholder text while loading
            'element' => 'div',         // HTML element to use for display
            'class' => '',              // CSS class for the display element
            'id' => '',                 // ID for the display element
            'ajax' => 'no',             // Whether to load data via AJAX
            'interval' => '0',          // Auto-refresh interval in seconds (0 = no refresh)
        ), $atts, 'pricewise_api_display');
        
        // Validate required attributes
        if (empty($atts['api']) || empty($atts['endpoint'])) {
            return '<div class="pricewise-api-error">Error: API and endpoint are required attributes.</div>';
        }
        
        // Create unique ID for this display if not provided
        $display_id = !empty($atts['id']) ? $atts['id'] : 'pricewise-api-display-' . md5(serialize($atts));
        
        // Parse parameters
        $params = array();
        if (!empty($atts['params'])) {
            $param_pairs = explode(',', $atts['params']);
            foreach ($param_pairs as $pair) {
                $pair = trim($pair);
                if (strpos($pair, '=') !== false) {
                    list($key, $value) = explode('=', $pair, 2);
                    $params[trim($key)] = trim($value);
                }
            }
        }
        
        // Check if we're using cache
        $use_cache = ($atts['cache'] === 'yes');
        
        // Check if we're using AJAX
        $use_ajax = ($atts['ajax'] === 'yes');
        
        // Get the element to use
        $element = !empty($atts['element']) ? $atts['element'] : 'div';
        
        // Initialize output
        $output = '<' . $element . ' id="' . esc_attr($display_id) . '" class="pricewise-api-display ' . esc_attr($atts['class']) . '"';
        
        if ($use_ajax) {
            // Add data attributes for AJAX loading
            $output .= ' data-api="' . esc_attr($atts['api']) . '"';
            $output .= ' data-endpoint="' . esc_attr($atts['endpoint']) . '"';
            $output .= ' data-cache="' . esc_attr($atts['cache']) . '"';
            $output .= ' data-params="' . esc_attr($atts['params']) . '"';
            $output .= ' data-template="' . esc_attr($atts['template']) . '"';
            $output .= ' data-interval="' . esc_attr($atts['interval']) . '"';
            $output .= ' data-loading="true"';
            
            // Add placeholder as content
            $output .= '>' . esc_html($atts['placeholder']);
            
            // Add script for AJAX loading
            $output .= '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    var displayElement = $("#' . esc_js($display_id) . '");
                    
                    function loadApiData() {
                        var apiId = displayElement.data("api");
                        var endpointId = displayElement.data("endpoint");
                        var useCache = displayElement.data("cache") === "yes";
                        var paramsStr = displayElement.data("params");
                        var template = displayElement.data("template");
                        
                        // Parse parameters
                        var params = {};
                        if (paramsStr) {
                            var paramPairs = paramsStr.split(",");
                            paramPairs.forEach(function(pair) {
                                pair = pair.trim();
                                if (pair.indexOf("=") !== -1) {
                                    var keyValue = pair.split("=");
                                    params[keyValue[0].trim()] = keyValue[1].trim();
                                }
                            });
                        }
                        
                        // Make AJAX request
                        $.ajax({
                            url: pricewise_api.ajax_url,
                            type: "POST",
                            data: {
                                action: "pricewise_get_api_data",
                                nonce: pricewise_api.nonce,
                                api_id: apiId,
                                endpoint_id: endpointId,
                                use_cache: useCache,
                                params: params
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Update display element
                                    displayElement.data("loading", false);
                                    
                                    if (template) {
                                        // Use custom template
                                        var html = template;
                                        
                                        // Replace data placeholders
                                        if (response.data && response.data.data) {
                                            for (var key in response.data.data) {
                                                var placeholder = "{{data." + key + "}}";
                                                var value = response.data.data[key];
                                                
                                                if (typeof value === "object") {
                                                    html = html.replace(new RegExp(placeholder, "g"), JSON.stringify(value));
                                                } else {
                                                    html = html.replace(new RegExp(placeholder, "g"), value);
                                                }
                                            }
                                        }
                                        
                                        displayElement.html(html);
                                    } else {
                                        // Use default template
                                        var html = "<pre>" + JSON.stringify(response.data.data, null, 2) + "</pre>";
                                        displayElement.html(html);
                                    }
                                } else {
                                    // Display error message
                                    var errorHtml = "<div class=\"pricewise-api-error\">";
                                    errorHtml += "<p>Error: " + (response.data ? response.data.message : "Unknown error") + "</p>";
                                    errorHtml += "</div>";
                                    
                                    displayElement.html(errorHtml);
                                }
                            },
                            error: function() {
                                // Display error message
                                var errorHtml = "<div class=\"pricewise-api-error\">";
                                errorHtml += "<p>Error: Could not connect to the server. Please try again later.</p>";
                                errorHtml += "</div>";
                                
                                displayElement.html(errorHtml);
                            }
                        });
                    }
                    
                    // Load API data initially
                    loadApiData();
                    
                    // Set up auto-refresh if interval is greater than 0
                    var interval = parseInt(displayElement.data("interval"));
                    if (interval > 0) {
                        setInterval(loadApiData, interval * 1000);
                    }
                });
            </script>';
        } else {
            // Load data directly
            $api = Pricewise_API_Settings::get_api($atts['api']);
            $endpoint = Pricewise_API_Settings::get_endpoint($atts['api'], $atts['endpoint']);
            
            if (!$api || !$endpoint) {
                return '<div class="pricewise-api-error">Error: API or endpoint not found.</div>';
            }
            
            // Check if API and endpoint are active
            if (isset($api['active']) && !$api['active']) {
                return '<div class="pricewise-api-error">Error: API is inactive.</div>';
            }
            
            if (isset($endpoint['active']) && !$endpoint['active']) {
                return '<div class="pricewise-api-error">Error: API endpoint is inactive.</div>';
            }
            
            // Process API request
            $result = $this->process_api_request($api, $endpoint, $params, $use_cache);
            
            if (is_wp_error($result)) {
                return '<div class="pricewise-api-error">Error: ' . esc_html($result->get_error_message()) . '</div>';
            }
            
            // Render output
            if (!empty($atts['template'])) {
                // Use custom template
                $content = $atts['template'];
                
                // Replace data placeholders
                $content = $this->replace_data_placeholders($content, $result);
            } else {
                // Use default template
                $content = '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
            }
            
            $output .= '>' . $content;
        }
        
        // Close the element
        $output .= '</' . $element . '>';
        
        return $output;
    }
    
    /**
     * Process API request
     *
     * @param array $api The API configuration
     * @param array $endpoint The endpoint configuration
     * @param array $params The request parameters
     * @param bool $use_cache Whether to use cache
     * @return mixed|WP_Error The API response or error
     */
    private function process_api_request($api, $endpoint, $params, $use_cache = true) {
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
        
        // Process parameters with form data placeholders
        if (isset($api_data['advanced_config']['params']) && is_array($api_data['advanced_config']['params'])) {
            foreach ($api_data['advanced_config']['params'] as &$param) {
                if (isset($params[$param['name']])) {
                    $param['value'] = $params[$param['name']];
                }
            }
        }
        
        // Create API request function
        $request_func = function($params) use ($api_data) {
            return $this->execute_api_request($api_data, $params);
        };
        
        // Get API ID and endpoint for cache
        $api_id = $api['id_name'];
        $endpoint_path = $endpoint['path'];
        
        // Process all params
        $processed_params = array();
        if (isset($api_data['advanced_config']['params']) && is_array($api_data['advanced_config']['params'])) {
            foreach ($api_data['advanced_config']['params'] as $param) {
                $processed_params[$param['name']] = $param['value'];
            }
        }
        
        // Merge with additional params
        $processed_params = array_merge($processed_params, $params);
        
        // Use cache integration if available and enabled
        if ($use_cache && function_exists('pricewise_cache_api_integration')) {
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
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Handle errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
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
     * Extract field from API response
     *
     * @param mixed $response The API response
     * @param string $field_path The field path (dot notation)
     * @return mixed The extracted field value
     */
    private function extract_field_from_api_response($response, $field_path) {
        if (empty($field_path)) {
            return $response;
        }
        
        // Split path into parts
        $parts = explode('.', $field_path);
        
        // Navigate through the response
        $current = $response;
        
        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } elseif (is_object($current) && isset($current->$part)) {
                $current = $current->$part;
            } else {
                return null;
            }
        }
        
        return $current;
    }
    
    /**
     * Format API output
     *
     * @param mixed $result The API result
     * @param string $format The output format
     * @param string $template The template to use
     * @return string The formatted output
     */
    private function format_api_output($result, $format, $template) {
        if ($format === 'raw') {
            return '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
        } elseif ($format === 'json') {
            return json_encode($result);
        } elseif ($format === 'html') {
            if (!empty($template)) {
                return $this->replace_data_placeholders($template, $result);
            } else {
                // Default HTML format if no template provided
                $output = '<div class="pricewise-api-result">';
                
                if (is_array($result)) {
                    $output .= $this->array_to_html($result);
                } elseif (is_object($result)) {
                    $output .= $this->object_to_html($result);
                } else {
                    $output .= esc_html($result);
                }
                
                $output .= '</div>';
                
                return $output;
            }
        }
        
        // Default to raw format
        return '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
    }
    
    /**
     * Convert array to HTML
     *
     * @param array $array The array to convert
     * @return string The HTML representation
     */
    private function array_to_html($array) {
        if (empty($array)) {
            return '<p>No data</p>';
        }
        
        // Check if it's a simple array of values or a complex array of objects/arrays
        $is_simple = true;
        foreach ($array as $value) {
            if (is_array($value) || is_object($value)) {
                $is_simple = false;
                break;
            }
        }
        
        if ($is_simple) {
            // Simple array
            $output = '<ul>';
            
            foreach ($array as $value) {
                $output .= '<li>' . esc_html($value) . '</li>';
            }
            
            $output .= '</ul>';
        } else {
            // Complex array
            $output = '<div class="pricewise-api-complex-array">';
            
            foreach ($array as $key => $value) {
                $output .= '<div class="pricewise-api-item">';
                $output .= '<h4>' . esc_html($key) . '</h4>';
                
                if (is_array($value)) {
                    $output .= $this->array_to_html($value);
                } elseif (is_object($value)) {
                    $output .= $this->object_to_html($value);
                } else {
                    $output .= '<p>' . esc_html($value) . '</p>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Convert object to HTML
     *
     * @param object $object The object to convert
     * @return string The HTML representation
     */
    private function object_to_html($object) {
        if (empty($object)) {
            return '<p>No data</p>';
        }
        
        $output = '<div class="pricewise-api-object">';
        
        foreach ($object as $key => $value) {
            $output .= '<div class="pricewise-api-property">';
            $output .= '<strong>' . esc_html($key) . ':</strong> ';
            
            if (is_array($value)) {
                $output .= $this->array_to_html($value);
            } elseif (is_object($value)) {
                $output .= $this->object_to_html($value);
            } else {
                $output .= esc_html($value);
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Generate form fields based on API parameters
     *
     * @param array $endpoint The endpoint configuration
     * @return array Associative array of field name => HTML
     */
    private function generate_form_fields($endpoint) {
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
     * Replace data placeholders in a template
     *
     * @param string $template The template
     * @param mixed $data The data
     * @param string $prefix The prefix for nested data
     * @return string The template with placeholders replaced
     */
    private function replace_data_placeholders($template, $data, $prefix = 'data') {
        if (!is_array($data) && !is_object($data)) {
            // For non-array/object data, just return as is
            return $template;
        }
        
        // Convert objects to arrays for consistent handling
        if (is_object($data)) {
            $data = (array)$data;
        }
        
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $prefix . '.' . $key . '}}';
            
            if (is_array($value) || is_object($value)) {
                // Recursively process nested arrays/objects
                $template = $this->replace_data_placeholders($template, $value, $prefix . '.' . $key);
                
                // Also replace the array/object itself (as JSON)
                $template = str_replace($placeholder, json_encode($value), $template);
            } else {
                // Replace simple value
                $template = str_replace($placeholder, $value, $template);
            }
        }
        
        return $template;
    }
}

// Initialize the API shortcodes
function pricewise_api_shortcodes() {
    return Pricewise_API_Shortcodes::instance();
}

// Start the shortcodes
pricewise_api_shortcodes();