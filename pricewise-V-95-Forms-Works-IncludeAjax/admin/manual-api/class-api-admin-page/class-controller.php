<?php
/**
 * API Admin Page Controller Class
 * Handles the form processing and data manipulation for the API admin page.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API admin page controller functionality
 */
class Pricewise_API_Admin_Controller {
    
    /**
     * Test results from form submissions
     *
     * @var string
     */
    private $test_results = '';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Get current page parameters
     * 
     * Analyzes the current request to determine the action and related parameters.
     * 
     * @return array Page parameters including action and related data
     */
    public function get_page_parameters() {
        $page_data = array(
            'action' => '',
            'is_new_api' => false,
            'edit_api_id' => '',
            'test_api_id' => '',
            'is_endpoint' => false,
            'api_id' => '',
            'endpoint_id' => '',
            'show_test_results' => false
        );
        
        // Check if we're adding a new API
        if (isset($_GET['action']) && $_GET['action'] === 'new') {
            $page_data['action'] = 'new';
            $page_data['is_new_api'] = true;
        }
        
        // Check if we're editing an existing API
        elseif (isset($_GET['edit'])) {
            $page_data['action'] = 'edit';
            $page_data['edit_api_id'] = sanitize_text_field($_GET['edit']);
        }
        
        // Check if we're testing an API from the list
        elseif (isset($_GET['action']) && $_GET['action'] === 'test' && !empty($_GET['api'])) {
            $page_data['action'] = 'test';
            $page_data['test_api_id'] = sanitize_text_field($_GET['api']);
            
            // Verify nonce for test action
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'test_api_' . $page_data['test_api_id'])) {
                $page_data['show_test_results'] = true;
            } else {
                wp_die('Security check failed. Please try again.');
            }
        }
        
        // Check if we're toggling API activation
        elseif (isset($_GET['action']) && $_GET['action'] === 'toggle_activation' && !empty($_GET['api'])) {
            $page_data['action'] = 'toggle_activation';
            $page_data['api_id'] = sanitize_text_field($_GET['api']);
            
            // Verify nonce for toggle action
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'toggle_api_' . $page_data['api_id'])) {
                wp_die('Security check failed. Please try again.');
            }
        }
        
        // Check if we're toggling endpoint activation
        elseif (isset($_GET['action']) && $_GET['action'] === 'toggle_endpoint_activation' && 
               !empty($_GET['api']) && !empty($_GET['endpoint'])) {
            $page_data['action'] = 'toggle_endpoint_activation';
            $page_data['api_id'] = sanitize_text_field($_GET['api']);
            $page_data['endpoint_id'] = sanitize_text_field($_GET['endpoint']);
            
            // Verify nonce for toggle action
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'toggle_endpoint_' . $page_data['endpoint_id'])) {
                wp_die('Security check failed. Please try again.');
            }
        }
        
        // Check if we're duplicating an API account
        elseif (isset($_GET['action']) && $_GET['action'] === 'duplicate_account' && !empty($_GET['api'])) {
            $page_data['action'] = 'duplicate_account';
            $page_data['api_id'] = sanitize_text_field($_GET['api']);
            
            // Verify nonce for duplicate action
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'duplicate_account_' . $page_data['api_id'])) {
                wp_die('Security check failed. Please try again.');
            }
        }
        
        // Check if we're managing an endpoint
        elseif (isset($_GET['action']) && $_GET['action'] === 'endpoint' && 
               isset($_GET['api']) && isset($_GET['endpoint'])) {
            $page_data['action'] = 'endpoint';
            $page_data['is_endpoint'] = true;
            $page_data['api_id'] = sanitize_text_field($_GET['api']);
            $page_data['endpoint_id'] = sanitize_text_field($_GET['endpoint']);
            
            // Verify nonce except for new endpoints
            if ($page_data['endpoint_id'] !== 'new') {
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'edit_endpoint_' . $page_data['endpoint_id'])) {
                    wp_die('Security check failed. Please try again.');
                }
            }
        }
        
        return $page_data;
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle save API form
        if (isset($_POST['pricewise_save_manual_api']) && isset($_POST['api_name']) && isset($_POST['api_id_name'])) {
            $this->handle_save_api();
        }
        
        // Handle test API from edit form
        if (isset($_POST['pricewise_test_api_from_form']) && isset($_POST['api_name']) && isset($_POST['api_id_name'])) {
            $this->handle_test_api_from_form();
        }
        
        // Handle delete API
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['api'])) {
            $this->handle_delete_api();
        }
        
        // Handle toggle API activation
        if (isset($_GET['action']) && $_GET['action'] === 'toggle_activation' && !empty($_GET['api'])) {
            $this->handle_toggle_api_activation();
        }
        
        // Handle toggle endpoint activation
        if (isset($_GET['action']) && $_GET['action'] === 'toggle_endpoint_activation' && 
             !empty($_GET['api']) && !empty($_GET['endpoint'])) {
            $this->handle_toggle_endpoint_activation();
        }
        
        // Handle duplicate API account
        if (isset($_GET['action']) && $_GET['action'] === 'duplicate_account' && !empty($_GET['api'])) {
            $this->handle_duplicate_account();
        }
        
        // Handle clear cache
        if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && !empty($_GET['api'])) {
            $this->handle_clear_api_cache();
        }
        
        // Handle save endpoint form
        if (isset($_POST['pricewise_save_endpoint']) && isset($_POST['api_id']) && isset($_POST['endpoint_id'])) {
            $this->handle_save_endpoint();
        }
        
        // Handle test endpoint from form
        if (isset($_POST['pricewise_test_endpoint']) && isset($_POST['api_id']) && isset($_POST['endpoint_id'])) {
            $this->handle_test_endpoint();
        }
        
        // Handle endpoint duplication
        if (isset($_GET['action']) && $_GET['action'] === 'duplicate_endpoint' && 
             !empty($_GET['api']) && !empty($_GET['endpoint']) && isset($_GET['_wpnonce'])) {
            $this->handle_duplicate_endpoint();
        }
        
        // Handle endpoint deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete_endpoint' && 
             !empty($_GET['api']) && !empty($_GET['endpoint']) && isset($_GET['_wpnonce'])) {
            $this->handle_delete_endpoint();
        }
    }
    
    /**
     * Handle toggle API activation
     */
    private function handle_toggle_api_activation() {
        // Verify nonce (already checked in get_page_parameters)
        $api_id = sanitize_text_field($_GET['api']);
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Toggle API activation
        $result = Pricewise_API_Settings::toggle_api_activation($api_id);
        
        if ($result) {
            // Get referrer to determine where to redirect
            $referrer = wp_get_referer();
            $redirect_url = '';
            
            if (strpos($referrer, 'edit=') !== false) {
                // If coming from edit page, go back there
                $redirect_url = add_query_arg(array(
                    'edit' => $api_id,
                    'activation_toggled' => 'true'
                ), admin_url('admin.php?page=pricewise-manual-api'));
            } else {
                // Otherwise go to the main list
                $redirect_url = add_query_arg(array(
                    'activation_toggled' => 'true'
                ), admin_url('admin.php?page=pricewise-manual-api'));
            }
            
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Failed to toggle API activation state.');
        }
    }
    
    /**
     * Handle toggle endpoint activation
     */
    private function handle_toggle_endpoint_activation() {
        // Verify nonce (already checked in get_page_parameters)
        $api_id = sanitize_text_field($_GET['api']);
        $endpoint_id = sanitize_text_field($_GET['endpoint']);
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Check if the API is active before allowing endpoint activation
        $api = Pricewise_API_Settings::get_api($api_id);
        if (!$api) {
            wp_die('API not found.');
        }
        
        // If API is inactive, prevent endpoint activation
        $api_active = isset($api['active']) ? (bool)$api['active'] : true;
        $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
        $endpoint_active = isset($endpoint['active']) ? (bool)$endpoint['active'] : true;
        
        // Only allow enabling an endpoint if the parent API is active
        if (!$api_active && !$endpoint_active) {
            wp_die('Cannot activate an endpoint while the parent API account is inactive. Please activate the API account first.');
        }
        
        // Toggle endpoint activation
        $result = Pricewise_API_Settings::toggle_endpoint_activation($api_id, $endpoint_id);
        
        if ($result) {
            // Get referrer to determine where to redirect
            $referrer = wp_get_referer();
            $redirect_url = '';
            
            if (strpos($referrer, 'action=endpoint') !== false) {
                // If coming from endpoint edit page, go back there
                $redirect_url = add_query_arg(array(
                    'page' => 'pricewise-manual-api',
                    'action' => 'endpoint',
                    'api' => $api_id,
                    'endpoint' => $endpoint_id,
                    '_wpnonce' => wp_create_nonce('edit_endpoint_' . $endpoint_id),
                    'endpoint_activation_toggled' => 'true'
                ), admin_url('admin.php'));
            } else {
                // Otherwise go to the API edit page
                $redirect_url = add_query_arg(array(
                    'edit' => $api_id,
                    'endpoint_activation_toggled' => 'true'
                ), admin_url('admin.php?page=pricewise-manual-api'));
            }
            
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Failed to toggle endpoint activation state.');
        }
    }
    
    /**
     * Handle duplicate API account
     */
    private function handle_duplicate_account() {
        // Verify nonce (already checked in get_page_parameters)
        $api_id = sanitize_text_field($_GET['api']);
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Duplicate the API account
        $new_api_id = Pricewise_API_Settings::duplicate_api($api_id);
        
        if ($new_api_id) {
            // Redirect to the main list with success message
            wp_redirect(add_query_arg(array(
                'duplicated_account' => 'true'
            ), admin_url('admin.php?page=pricewise-manual-api')));
            exit;
        } else {
            wp_die('Failed to duplicate API account.');
        }
    }
    
    /**
     * Handle save API form
     */
    private function handle_save_api() {
        // Verify nonce
        if (!isset($_POST['pricewise_manual_api_nonce']) || 
            !wp_verify_nonce($_POST['pricewise_manual_api_nonce'], 'pricewise_save_manual_api')) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get form data
        $api_name = isset($_POST['api_name']) ? sanitize_text_field($_POST['api_name']) : '';
        $api_id_name = isset($_POST['api_id_name']) ? sanitize_key($_POST['api_id_name']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        // Auto-generate ID if empty
        if (empty($api_id_name)) {
            $api_id_name = sanitize_key($api_name);
        }
        
        // Make sure ID is properly sanitized
        $api_id_name = sanitize_key($api_id_name);
        
        // Get edit API ID if editing
        $edit_api_id = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
        
        // Get existing API data if editing
        $existing_api = false;
        if (!empty($edit_api_id)) {
            $existing_api = Pricewise_API_Settings::get_api($edit_api_id);
        }
        
        // Prepare API data
        $api_data = array(
            'name' => $api_name,
            'id_name' => $api_id_name,
            'api_key' => $api_key,
            'base_endpoint' => isset($_POST['api_base_endpoint']) ? esc_url_raw($_POST['api_base_endpoint']) : '',
        );
        
        // Preserve active state if editing
        if ($existing_api && isset($existing_api['active'])) {
            $api_data['active'] = (bool) $existing_api['active'];
        } else {
            // Default to active for new APIs
            $api_data['active'] = true;
        }
        
        // If editing and there are existing endpoints, keep them
        if ($existing_api && isset($existing_api['endpoints'])) {
            $api_data['endpoints'] = $existing_api['endpoints'];
        }
        
        // Get old settings for comparison
        $old_settings = Pricewise_API_Settings::get_apis();
        
        // Save API
        $result = Pricewise_API_Settings::save_api($api_data, $edit_api_id);
        
        if ($result === true) {
            // Store success message in transient
            set_transient('pricewise_api_save_success', true, 30);
            
            // Get new settings
            $new_settings = Pricewise_API_Settings::get_apis();
            
            // Trigger an action to allow cache clearing or other operations
            do_action('pricewise_api_settings_updated', $old_settings, $new_settings);
            
            // If this is a new API, update the edit ID to the new ID
            if (empty($edit_api_id)) {
                $_GET['edit'] = $api_id_name;
            }
            
            // If this is a new API, redirect to endpoint creation
            if (empty($edit_api_id) || !isset($api_data['endpoints'])) {
                wp_redirect(add_query_arg(array(
                    'action' => 'endpoint',
                    'api' => $api_id_name,
                    'endpoint' => 'default',
                    '_wpnonce' => wp_create_nonce('edit_endpoint_default')
                ), admin_url('admin.php?page=pricewise-manual-api')));
                exit;
            }
        } else {
            // Show error message
            add_settings_error(
                'pricewise_manual_api',
                'api_save_error',
                $result,
                'error'
            );
        }
    }
    
    /**
     * Handle save endpoint form
     */
    private function handle_save_endpoint() {
        // Verify nonce
        if (!isset($_POST['pricewise_endpoint_nonce']) || 
            !wp_verify_nonce($_POST['pricewise_endpoint_nonce'], 'pricewise_save_endpoint')) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get form data
        $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
        $endpoint_id = isset($_POST['endpoint_id']) ? sanitize_key($_POST['endpoint_id']) : '';
        $old_endpoint_id = isset($_POST['old_endpoint_id']) ? sanitize_key($_POST['old_endpoint_id']) : '';
        
        // Endpoint basic info
        $endpoint_name = isset($_POST['endpoint_name']) ? sanitize_text_field($_POST['endpoint_name']) : '';
        $endpoint_path = isset($_POST['endpoint_path']) ? sanitize_text_field($_POST['endpoint_path']) : '';
        
        // Get endpoint active state
        $endpoint_active = isset($_POST['endpoint_active']) && $_POST['endpoint_active'] == '1';
        
        // Auto-generate ID if empty
        if (empty($endpoint_id)) {
            $endpoint_id = sanitize_key($endpoint_name);
        }
        
        // Process endpoint configuration
        $endpoint_data = array(
            'name' => $endpoint_name,
            'path' => $endpoint_path,
            'active' => $endpoint_active,
            'config' => $this->process_endpoint_config_from_form()
        );
        
        // Save endpoint
        $result = Pricewise_API_Settings::save_endpoint($api_id, $endpoint_id, $endpoint_data, $old_endpoint_id);
        
        if ($result === true) {
            // Store success message in transient
            set_transient('pricewise_endpoint_save_success', true, 30);
            
            // Redirect back to the same endpoint edit page instead of the API edit page
            wp_redirect(add_query_arg(array(
                'page' => 'pricewise-manual-api',
                'action' => 'endpoint',
                'api' => $api_id,
                'endpoint' => $endpoint_id,
                '_wpnonce' => wp_create_nonce('edit_endpoint_' . $endpoint_id),
                'updated' => 'true'
            ), admin_url('admin.php')));
            exit;
        } else {
            // Show error message
            add_settings_error(
                'pricewise_manual_api',
                'endpoint_save_error',
                $result,
                'error'
            );
        }
    }
    
    /**
     * Process endpoint configuration from form data
     *
     * @return array Processed endpoint configuration
     */
    private function process_endpoint_config_from_form() {
        // Get HTTP method
        $http_method = isset($_POST['api_method']) ? sanitize_text_field($_POST['api_method']) : 'GET';
        
        // Get response format
        $response_format = isset($_POST['api_response_format']) ? sanitize_text_field($_POST['api_response_format']) : 'json';
        
        // Get request timeout
        $request_timeout = isset($_POST['api_request_timeout']) ? intval($_POST['api_request_timeout']) : 30;
        // Ensure timeout is within reasonable limits
        $request_timeout = max(1, min(120, $request_timeout));
        
        // Get cache duration
        $cache_duration = isset($_POST['api_cache_duration']) ? intval($_POST['api_cache_duration']) : 3600;
        // Ensure cache duration is at least 1 second
        $cache_duration = max(1, $cache_duration);
        
        // Process test history options
        $save_test_headers = isset($_POST['api_advanced_config']['save_test_headers']) && $_POST['api_advanced_config']['save_test_headers'] == '1';
        $save_test_params = isset($_POST['api_advanced_config']['save_test_params']) && $_POST['api_advanced_config']['save_test_params'] == '1';
        $save_test_response_headers = isset($_POST['api_advanced_config']['save_test_response_headers']) && $_POST['api_advanced_config']['save_test_response_headers'] == '1';
        $save_test_response_body = isset($_POST['api_advanced_config']['save_test_response_body']) && $_POST['api_advanced_config']['save_test_response_body'] == '1';
        
        // Process headers from the form
        $headers = array();
        $disable_headers = isset($_POST['api_disable_headers']) && $_POST['api_disable_headers'] == '1';
        
        if (!$disable_headers && isset($_POST['header_name']) && is_array($_POST['header_name']) && 
            isset($_POST['header_value']) && is_array($_POST['header_value'])) {
            
            foreach ($_POST['header_name'] as $index => $name) {
                if (!empty($name) && isset($_POST['header_value'][$index])) {
                    $header_save = isset($_POST['header_save']) && 
                                  is_array($_POST['header_save']) && 
                                  isset($_POST['header_save'][$index]) && 
                                  $_POST['header_save'][$index] == '1';
                    
                    $headers[] = array(
                        'name' => sanitize_text_field($name),
                        'value' => sanitize_text_field($_POST['header_value'][$index]),
                        'save' => $header_save
                    );
                }
            }
        }
        
        // Process body if enabled
        $body_enabled = isset($_POST['api_body_enabled']) && $_POST['api_body_enabled'] == '1';
        $body_type = isset($_POST['api_body_type']) ? sanitize_text_field($_POST['api_body_type']) : 'json';
        $body_content = isset($_POST['api_body_content']) ? stripslashes($_POST['api_body_content']) : '';
        
        // Process parameters from the form
        $params = array();
        if (isset($_POST['param_name']) && is_array($_POST['param_name']) && 
            isset($_POST['param_value']) && is_array($_POST['param_value'])) {
            
            foreach ($_POST['param_name'] as $index => $name) {
                if (!empty($name) && isset($_POST['param_value'][$index])) {
                    $param_save = isset($_POST['param_save']) && 
                                 is_array($_POST['param_save']) && 
                                 isset($_POST['param_save'][$index]) && 
                                 $_POST['param_save'][$index] == '1';
                    
                    $params[] = array(
                        'name' => sanitize_text_field($name),
                        'value' => sanitize_text_field($_POST['param_value'][$index]),
                        'save' => $param_save
                    );
                }
            }
        }
        
        // Process response headers from the form
        $response_headers = array();
        if (isset($_POST['response_header_name']) && is_array($_POST['response_header_name'])) {
            foreach ($_POST['response_header_name'] as $index => $name) {
                if (!empty($name)) {
                    $response_header_save = isset($_POST['response_header_save']) && 
                                          is_array($_POST['response_header_save']) && 
                                          isset($_POST['response_header_save'][$index]) && 
                                          $_POST['response_header_save'][$index] == '1';
                    
                    $response_headers[] = array(
                        'name' => sanitize_text_field($name),
                        'save' => $response_header_save
                    );
                }
            }
        }
        
        // Get endpoint path from the main form
        $endpoint_path = isset($_POST['endpoint_path']) ? sanitize_text_field($_POST['endpoint_path']) : '';
        
        // Prepare advanced configuration
        $config = array(
            'method' => $http_method,
            'response_format' => $response_format,
            'request_timeout' => $request_timeout,
            'cache_duration' => $cache_duration,
            'save_test_headers' => $save_test_headers,
            'save_test_params' => $save_test_params,
            'save_test_response_headers' => $save_test_response_headers,
            'save_test_response_body' => $save_test_response_body,
            'auth' => array(
                'type' => isset($_POST['api_auth_type']) ? sanitize_text_field($_POST['api_auth_type']) : 'api_key',
                'headers' => $headers,
                'disable_headers' => $disable_headers
            ),
            'body' => array(
                'enabled' => $body_enabled,
                'type' => $body_type,
                'content' => $body_content
            ),
            'params' => $params,
            'response_headers' => $response_headers
        );
        
        // Add endpoint path to advanced config for backward compatibility
        $config['endpoint'] = $endpoint_path;
        
        return $config;
    }
    
    /**
     * Handle test API from edit form
     */
    private function handle_test_api_from_form() {
        // Verify nonce
        if (!isset($_POST['pricewise_manual_api_nonce']) || 
            !wp_verify_nonce($_POST['pricewise_manual_api_nonce'], 'pricewise_save_manual_api')) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get form data and create temporary API data for testing
        $api_name = isset($_POST['api_name']) ? sanitize_text_field($_POST['api_name']) : '';
        $api_id_name = isset($_POST['api_id_name']) ? sanitize_key($_POST['api_id_name']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $base_endpoint = isset($_POST['api_base_endpoint']) ? esc_url_raw($_POST['api_base_endpoint']) : '';
        
        // Get existing API to check its active status
        $existing_api = Pricewise_API_Settings::get_api($api_id_name);
        if ($existing_api && isset($existing_api['active']) && !$existing_api['active']) {
            // API is inactive, don't allow testing
            $this->test_results = '<div class="notice notice-error"><p>This API is currently inactive. Please activate it before testing.</p></div>';
            set_transient('pricewise_api_test_results', $this->test_results, 60);
            return;
        }
        
        // Create temporary API data for testing
        $temp_api_data = array(
            'name' => $api_name,
            'id_name' => $api_id_name,
            'api_key' => $api_key,
            'base_endpoint' => $base_endpoint
        );
        
        // Get endpoint to test - use first available endpoint or default
        $endpoints = Pricewise_API_Settings::get_endpoints($api_id_name);
        if (!empty($endpoints)) {
            $first_endpoint = reset($endpoints);
            $endpoint_path = $first_endpoint['path'];
            
            // Set advanced config for legacy support
            $temp_api_data['advanced_config'] = array(
                'endpoint' => $endpoint_path
            );
            
            if (isset($first_endpoint['config'])) {
                $temp_api_data['advanced_config'] = array_merge($temp_api_data['advanced_config'], $first_endpoint['config']);
            }
        } else {
            // No endpoints available, use default approach
            $temp_api_data['advanced_config'] = $this->process_endpoint_config_from_form();
            $temp_api_data['advanced_config']['endpoint'] = isset($_POST['endpoint_path']) ? sanitize_text_field($_POST['endpoint_path']) : '';
        }
        
        // Run the test on the temporary API data
        $tester = new Pricewise_API_Tester();
        $this->test_results = $tester->test_api($temp_api_data);
        
        // Store the test results in a transient for persistence across page loads
        set_transient('pricewise_api_test_results', $this->test_results, 60);
    }
    
    /**
     * Handle test endpoint from endpoint form
     */
    private function handle_test_endpoint() {
        // Verify nonce
        if (!isset($_POST['pricewise_endpoint_nonce']) || 
            !wp_verify_nonce($_POST['pricewise_endpoint_nonce'], 'pricewise_save_endpoint')) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Get API data
        $api_id = isset($_POST['api_id']) ? sanitize_text_field($_POST['api_id']) : '';
        $api = Pricewise_API_Settings::get_api($api_id);
        
        if (!$api) {
            wp_die('API not found');
        }
        
        // Check if API is active
        if (isset($api['active']) && !$api['active']) {
            // API is inactive, don't allow testing
            $test_results = '<div class="notice notice-error"><p>This API is currently inactive. Please activate it before testing.</p></div>';
            set_transient('pricewise_endpoint_test_results', $test_results, 60);
            return;
        }
        
        // Get endpoint data
        $endpoint_id = isset($_POST['endpoint_id']) ? sanitize_text_field($_POST['endpoint_id']) : '';
        $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
        
        // Check if endpoint is active (for existing endpoints)
        if ($endpoint && isset($endpoint['active']) && !$endpoint['active']) {
            // Endpoint is inactive, don't allow testing
            $test_results = '<div class="notice notice-error"><p>This endpoint is currently inactive. Please activate it before testing.</p></div>';
            set_transient('pricewise_endpoint_test_results', $test_results, 60);
            return;
        }
        
        // Get endpoint data from form
        $endpoint_path = isset($_POST['endpoint_path']) ? sanitize_text_field($_POST['endpoint_path']) : '';
        
        // Create temporary API data for testing
        $temp_api_data = array(
            'name' => $api['name'],
            'id_name' => $api['id_name'],
            'api_key' => $api['api_key'],
            'base_endpoint' => $api['base_endpoint']
        );
        
        // Process endpoint configuration from form
        $endpoint_config = $this->process_endpoint_config_from_form();
        
        // Set up advanced configuration with endpoint path for backward compatibility
        $temp_api_data['advanced_config'] = $endpoint_config;
        
        // Run the test on the temporary API data
        $tester = new Pricewise_API_Tester();
        $test_results = $tester->test_api($temp_api_data);
        
        // Store the test results in a transient
        set_transient('pricewise_endpoint_test_results', $test_results, 60);
    }
    
    /**
     * Handle duplicate endpoint
     */
    private function handle_duplicate_endpoint() {
        $api_id = sanitize_text_field($_GET['api']);
        $endpoint_id = sanitize_text_field($_GET['endpoint']);
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'duplicate_endpoint_' . $endpoint_id)) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Duplicate the endpoint
        $new_endpoint_id = Pricewise_API_Settings::duplicate_endpoint($api_id, $endpoint_id);
        
        if ($new_endpoint_id) {
            // Redirect to the API edit page
            wp_redirect(add_query_arg(array(
                'edit' => $api_id,
                'duplicated' => 'true'
            ), admin_url('admin.php?page=pricewise-manual-api')));
            exit;
        } else {
            wp_die('Failed to duplicate endpoint');
        }
    }
    
    /**
     * Handle delete endpoint
     */
    private function handle_delete_endpoint() {
        $api_id = sanitize_text_field($_GET['api']);
        $endpoint_id = sanitize_text_field($_GET['endpoint']);
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_endpoint_' . $endpoint_id)) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Delete the endpoint
        $result = Pricewise_API_Settings::delete_endpoint($api_id, $endpoint_id);
        
        if ($result) {
            // Redirect to the API edit page
            wp_redirect(add_query_arg(array(
                'edit' => $api_id,
                'deleted_endpoint' => 'true'
            ), admin_url('admin.php?page=pricewise-manual-api')));
            exit;
        } else {
            wp_die('Failed to delete endpoint. Make sure it\'s not the last endpoint.');
        }
    }
    
    /**
     * Handle delete API
     */
    private function handle_delete_api() {
        $api_to_delete = sanitize_text_field($_GET['api']);
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_api_' . $api_to_delete)) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Delete the API
        $result = Pricewise_API_Settings::delete_api($api_to_delete);
        
        if ($result) {
            // Clear cache if the function exists
            if (function_exists('pricewise_clear_api_cache')) {
                pricewise_clear_api_cache($api_to_delete);
            }
            
            // Redirect to show success message
            wp_redirect(admin_url('admin.php?page=pricewise-manual-api&deleted=true'));
            exit;
        } else {
            wp_die('Failed to delete API configuration.');
        }
    }
    
    /**
     * Handle clear API cache
     */
    private function handle_clear_api_cache() {
        $api_id = sanitize_text_field($_GET['api']);
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'clear_cache_' . $api_id)) {
            wp_die('Security check failed. Please try again.');
        }
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Clear cache if the function exists
        if (function_exists('pricewise_clear_api_cache')) {
            pricewise_clear_api_cache($api_id);
            
            // Redirect to show success message
            wp_redirect(admin_url('admin.php?page=pricewise-manual-api&cache_cleared=true'));
            exit;
        } else {
            wp_die('Cache system function not available.');
        }
    }
    
    /**
     * Get empty API template for new API form
     * 
     * @return array Empty API template with default values
     */
    public function get_empty_api_template() {
        return array(
            'name' => '',
            'id_name' => '',
            'api_key' => '',
            'base_endpoint' => '',
            'active' => true,
            'endpoints' => array()
        );
    }
    
    /**
     * Get API for editing
     * 
     * @param string $api_id API ID to edit
     * @return array|false API data or false if not found
     */
    public function get_api_for_editing($api_id) {
        // Get API data
        $api = Pricewise_API_Settings::get_api($api_id);
        
        if (!$api) {
            return false;
        }
        
        // Process the API data to ensure it has all needed fields
        return $this->process_api_data_for_form($api);
    }
    
    /**
     * Get endpoint for editing
     * 
     * @param string $api_id API ID
     * @param string $endpoint_id Endpoint ID
     * @return array Endpoint data
     */
    public function get_endpoint_for_editing($api_id, $endpoint_id) {
        // For 'new' endpoint, return empty template
        if ($endpoint_id === 'new') {
            return array(
                'name' => '',
                'path' => '',
                'active' => true,
                'config' => array(
                    'method' => 'GET',
                    'response_format' => 'json',
                    'request_timeout' => 30,
                    'cache_duration' => 3600,
                    'auth' => array(
                        'type' => 'api_key',
                        'headers' => array(
                            array('name' => 'X-API-Key', 'value' => '', 'save' => false)
                        ),
                        'disable_headers' => false
                    ),
                    'body' => array(
                        'enabled' => false,
                        'type' => 'json',
                        'content' => ''
                    ),
                    'params' => array(
                        array('name' => 'currency', 'value' => 'USD', 'save' => false),
                        array('name' => 'market', 'value' => 'en-US', 'save' => false)
                    ),
                    'response_headers' => array(
                        array('name' => 'x-ratelimit-requests-limit', 'save' => false),
                        array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
                    )
                )
            );
        }
        
        // Get the endpoint data
        $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
        if (!$endpoint) {
            return array(
                'name' => '',
                'path' => '',
                'active' => true,
                'config' => array()
            );
        }
        
        // Process endpoint data
        return $this->process_endpoint_data_for_form($endpoint);
    }
    
    /**
     * Get test results
     * 
     * @return string Test results HTML
     */
    public function get_test_results() {
        // Check for transient first
        $transient_results = get_transient('pricewise_api_test_results');
        if ($transient_results) {
            delete_transient('pricewise_api_test_results');
            return $transient_results;
        }
        
        // Otherwise return instance variable
        return $this->test_results;
    }
    
	/**
	 * Get test results for API
	 * 
	 * @param string $api_id API ID
	 * @return string Test results HTML
	 */
	public function get_test_results_for_api($api_id) {
		// Verify nonce for test action
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'test_api_' . $api_id)) {
			return '';
		}
		
		$api = Pricewise_API_Settings::get_api($api_id);
		if (!$api) {
			return '';
		}
		
		// Check if API is active
		if (isset($api['active']) && !$api['active']) {
			// API is inactive, don't allow testing
			return '<div class="notice notice-error"><p>This API is currently inactive. Please activate it before testing.</p></div>';
		}
		
		// Filter out inactive endpoints before testing
		if (isset($api['endpoints']) && !empty($api['endpoints'])) {
			$active_endpoints = array();
			foreach ($api['endpoints'] as $endpoint_id => $endpoint) {
				if (isset($endpoint['active']) && $endpoint['active']) {
					$active_endpoints[$endpoint_id] = $endpoint;
				}
			}
			
			// If no active endpoints, show error
			if (empty($active_endpoints)) {
				return '<div class="notice notice-error"><p>This API has no active endpoints to test. Please activate at least one endpoint before testing.</p></div>';
			}
			
			// Replace endpoints with only active ones
			$api['endpoints'] = $active_endpoints;
		}
		
		// Run the test
		$tester = new Pricewise_API_Tester();
		return $tester->test_api($api);
	}

	/**
	 * Get all APIs
	 * 
	 * @return array All APIs
	 */
	public function get_all_apis() {
		return Pricewise_API_Settings::get_apis();
	}
    /**
     * Process API data for edit form
     * 
     * @param array $api Raw API data
     * @return array Processed API data with defaults
     */
    public function process_api_data_for_form($api) {
        // Set default for active state if not present
        if (!isset($api['active'])) {
            $api['active'] = true;
        }
        
        // Set defaults if advanced config is missing
        if (!isset($api['advanced_config'])) {
            $api['advanced_config'] = array();
        }
        
        // Set default HTTP method if not present
        if (!isset($api['advanced_config']['method'])) {
            $api['advanced_config']['method'] = 'GET';
        }
        
        // Set default response format if not present
        if (!isset($api['advanced_config']['response_format'])) {
            $api['advanced_config']['response_format'] = 'json';
        }
        
        // Set default request timeout if not present
        if (!isset($api['advanced_config']['request_timeout'])) {
            $api['advanced_config']['request_timeout'] = 30;
        }
        
        // Set default cache duration if not present
        if (!isset($api['advanced_config']['cache_duration'])) {
            $api['advanced_config']['cache_duration'] = 3600;
        }
        
        // Set default test history options if not present
        if (!isset($api['advanced_config']['save_test_headers'])) {
            $api['advanced_config']['save_test_headers'] = false;
        }
        
        if (!isset($api['advanced_config']['save_test_params'])) {
            $api['advanced_config']['save_test_params'] = false;
        }
        
        if (!isset($api['advanced_config']['save_test_response_headers'])) {
            $api['advanced_config']['save_test_response_headers'] = false;
        }
        
        if (!isset($api['advanced_config']['save_test_response_body'])) {
            $api['advanced_config']['save_test_response_body'] = false;
        }
        
        // Ensure auth structure exists
        if (!isset($api['advanced_config']['auth'])) {
            $api['advanced_config']['auth'] = array(
                'type' => 'api_key',
                'headers' => array(),
                'disable_headers' => false
            );
        }
        
        // Ensure disable_headers exists
        if (!isset($api['advanced_config']['auth']['disable_headers'])) {
            $api['advanced_config']['auth']['disable_headers'] = false;
        }
        
        // Ensure auth headers exist and have save property
        if (!isset($api['advanced_config']['auth']['headers']) || !is_array($api['advanced_config']['auth']['headers'])) {
            $api['advanced_config']['auth']['headers'] = array(
                array('name' => 'X-API-Key', 'value' => '', 'save' => false)
            );
        } else {
            // Add save property if missing
            foreach ($api['advanced_config']['auth']['headers'] as &$header) {
                if (!isset($header['save'])) {
                    $header['save'] = false;
                }
            }
        }
        
        // Ensure body settings exist
        if (!isset($api['advanced_config']['body'])) {
            $api['advanced_config']['body'] = array(
                'enabled' => false,
                'type' => 'json',
                'content' => ''
            );
        }
        
        // Ensure response headers exist and have save property
        if (!isset($api['advanced_config']['response_headers']) || !is_array($api['advanced_config']['response_headers'])) {
            $api['advanced_config']['response_headers'] = array(
                array('name' => 'x-ratelimit-requests-limit', 'save' => false),
                array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
            );
        } else {
            // Add save property if missing
            foreach ($api['advanced_config']['response_headers'] as &$header) {
                if (!isset($header['save'])) {
                    $header['save'] = false;
                }
            }
        }
        
        // Ensure params exist and have save property
        if (!isset($api['advanced_config']['params'])) {
            $api['advanced_config']['params'] = array(
                array('name' => 'currency', 'value' => 'USD', 'save' => false),
                array('name' => 'market', 'value' => 'en-US', 'save' => false)
            );
        } else {
            // Add save property if missing
            foreach ($api['advanced_config']['params'] as &$param) {
                if (!isset($param['save'])) {
                    $param['save'] = false;
                }
            }
        }
        
        return $api;
    }
    
    /**
     * Process endpoint data for edit form
     * 
     * @param array $endpoint Endpoint data
     * @return array Processed endpoint data
     */
    public function process_endpoint_data_for_form($endpoint) {
        if (!isset($endpoint['config'])) {
            $endpoint['config'] = array();
        }
        
        // Set default active state if not present
        if (!isset($endpoint['active'])) {
            $endpoint['active'] = true;
        }
        
        // Apply the same processing as for APIs to ensure all config values exist
        $processed = array(
            'name' => isset($endpoint['name']) ? $endpoint['name'] : 'Default',
            'path' => isset($endpoint['path']) ? $endpoint['path'] : '',
            'active' => $endpoint['active'],
            'config' => $this->process_api_data_for_form(array('advanced_config' => $endpoint['config']))['advanced_config']
        );
        
        // Make sure the endpoint path is copied to config['endpoint'] for backward compatibility
        $processed['config']['endpoint'] = $processed['path'];
        
        return $processed;
    }
}