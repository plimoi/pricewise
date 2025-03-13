<?php
/**
 * API Admin Page Class
 * Handles the admin UI rendering and form handling.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include the controller and view classes
require_once plugin_dir_path( __FILE__ ) . 'class-api-admin-page/class-controller.php';
require_once plugin_dir_path( __FILE__ ) . 'class-api-admin-page/class-view.php';

/**
 * Class to handle API admin page
 */
class Pricewise_API_Admin_Page {
    
    /**
     * Controller instance
     *
     * @var Pricewise_API_Admin_Controller
     */
    private $controller;
    
    /**
     * View instance
     *
     * @var Pricewise_API_Admin_View
     */
    private $view;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize component classes
        $this->load_dependencies();
    }
    
    /**
     * Load and initialize dependencies
     */
    private function load_dependencies() {
        // Initialize component classes only if the files exist
        if (file_exists(plugin_dir_path( __FILE__ ) . 'class-api-admin-page/class-controller.php') && 
            file_exists(plugin_dir_path( __FILE__ ) . 'class-api-admin-page/class-view.php')) {
            
            $this->controller = new Pricewise_API_Admin_Controller();
            $this->view = new Pricewise_API_Admin_View();
        } else {
            // Log the error if the files don't exist
            error_log('PriceWise: Required component files for API admin page not found.');
        }
    }
    
    /**
     * Initialize the admin page
     */
    public function init() {
        // Add hook to handle form submissions and actions
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Register the page display callback
        add_action('pricewise_display_api_admin_page', array($this, 'display_page'));
        
        // Add custom styles for inactive API rows and toggle switch
        add_action('admin_head', array($this, 'add_admin_styles'));
    }
    
    /**
     * Add admin styles for API activation status and toggle switch
     */
    public function add_admin_styles() {
        ?>
        <style type="text/css">
            /* Styles for inactive API rows */
            .inactive-api-row {
                background-color: #f9f9f9 !important;
            }
            
            /* Toggle Switch Styles */
            .pricewise-toggle-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 28px;
                vertical-align: middle;
            }
            
            .pricewise-toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .pricewise-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            
            .pricewise-toggle-slider:before {
                position: absolute;
                content: "";
                height: 20px;
                width: 20px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            input:checked + .pricewise-toggle-slider {
                background-color: #2271b1;
            }
            
            input:focus + .pricewise-toggle-slider {
                box-shadow: 0 0 1px #2271b1;
            }
            
            input:checked + .pricewise-toggle-slider:before {
                transform: translateX(32px);
            }
            
            .api-toggle-container {
                margin: 10px 0;
                display: flex;
                align-items: center;
            }
            
            .api-toggle-label {
                margin-right: 10px;
                font-weight: 500;
                vertical-align: middle;
            }
        </style>
        <?php
    }
    
    /**
     * Handle admin actions
     * 
     * This is a wrapper around the controller's form submission handler
     * that ensures the controller is properly initialized before use
     */
    public function handle_admin_actions() {
        // Verify controller is available
        if ($this->is_controller_available()) {
            $this->controller->handle_form_submissions();
        }
    }
    
    /**
     * Check if controller is available
     * 
     * @return bool Whether controller is available
     */
    private function is_controller_available() {
        return isset($this->controller) && is_object($this->controller);
    }
    
    /**
     * Check if view is available
     * 
     * @return bool Whether view is available
     */
    private function is_view_available() {
        return isset($this->view) && is_object($this->view);
    }
    
    /**
     * Display the admin page
     * 
     * This method serves as the main entry point for rendering the admin page.
     * It coordinates between the controller and view to prepare and display content.
     */
    public function display_page() {
        // Verify user has permissions to access this page
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Check if components are properly initialized
        if (!$this->is_controller_available() || !$this->is_view_available()) {
            wp_die(__('Error: Admin page components could not be loaded. Please check the plugin installation.'));
        }
        
        // Get current request action and parameters from controller
        $page_data = $this->controller->get_page_parameters();
        $page_action = $page_data['action'];
        
        // Start page output
        echo '<div class="wrap">';
        
        // Render page header based on current action
        $this->view->render_page_header($page_data);
        
        // Render the appropriate page content based on the action
        switch ($page_action) {
            case 'new':
                // Add new API form
                $this->render_new_api_form();
                break;
                
            case 'edit':
                // Edit API form
                $this->render_edit_api_form($page_data['edit_api_id']);
                break;
                
            case 'endpoint':
                // Handle endpoint editing
                $this->render_endpoint_form($page_data);
                break;
                
            case 'test':
                // Test results display
                $this->render_test_results($page_data);
                break;
                
            case 'toggle_activation':
                // Toggle API activation
                $this->handle_toggle_activation($page_data);
                break;
                
            case 'duplicate_account':
                // Duplicate API account
                $this->handle_duplicate_account($page_data);
                break;
                
            default:
                // Default API list
                $this->render_api_list();
                break;
        }
        
        echo '</div>'; // End wrap
    }
    
    /**
     * Handle toggle activation action
     * 
     * @param array $page_data Page data including API ID
     */
    private function handle_toggle_activation($page_data) {
        // This is handled in the controller, but we need to show the API list
        $this->render_api_list();
    }
    
    /**
     * Handle duplicate account action
     * 
     * @param array $page_data Page data including API ID
     */
    private function handle_duplicate_account($page_data) {
        // This is handled in the controller, but we need to show the API list
        $this->render_api_list();
    }
    
    /**
     * Render the new API form
     */
    private function render_new_api_form() {
        $empty_api = $this->controller->get_empty_api_template();
        $this->view->render_api_form($empty_api, false);
    }
    
    /**
     * Render the edit API form
     * 
     * @param string $api_id The API ID to edit
     */
    private function render_edit_api_form($api_id) {
        // Get the API data
        $api = $this->controller->get_api_for_editing($api_id);
        
        if (!$api) {
            // API not found - show error message
            echo '<div class="notice notice-error"><p>' . esc_html__('API not found.', 'pricewise') . '</p></div>';
            $this->render_api_list(); // Fall back to list view
            return;
        }
        
        // Render the API form
        $this->view->render_api_form($api, true);
        
        // If there are test results, show them
        $test_results = $this->controller->get_test_results();
        if (!empty($test_results)) {
            $this->view->render_test_results($test_results);
        }
    }
    
    /**
     * Render the endpoint form
     * 
     * @param array $page_data Page data including API and endpoint information
     */
    private function render_endpoint_form($page_data) {
        $api_id = $page_data['api_id'];
        $endpoint_id = $page_data['endpoint_id'];
        
        // Get endpoint data from controller
        $endpoint = $this->controller->get_endpoint_for_editing($api_id, $endpoint_id);
        
        // Render the endpoint form
        $this->view->render_endpoint_form($api_id, $endpoint_id, $endpoint);
    }
    
    /**
     * Render API test results
     * 
     * @param array $page_data Page data including test results
     */
    private function render_test_results($page_data) {
        // Get all APIs for the list view
        $apis = $this->controller->get_all_apis();
        
        // Render the API list
        $this->view->render_api_list($apis);
        
        // Get test results from controller
        $test_results = $this->controller->get_test_results_for_api($page_data['test_api_id']);
        
        // Render the test results
        if (!empty($test_results)) {
            $this->view->render_test_results($test_results);
        }
    }
    
    /**
     * Render the API list
     */
    private function render_api_list() {
        // Get all APIs
        $apis = $this->controller->get_all_apis();
        
        // Render the API list
        $this->view->render_api_list($apis);
    }
    
    /**
     * Static entry point for the admin page display
     * 
     * This maintains backward compatibility with existing code that
     * calls this method statically.
     * 
     * @deprecated 1.3.0 Use instance method display_page() instead.
     */
    public static function display_page_static() {
        _deprecated_function(__METHOD__, '1.3.0', 'Create an instance of Pricewise_API_Admin_Page and call display_page()');
        
        // Create an instance and display
        $instance = new self();
        $instance->display_page();
    }
}