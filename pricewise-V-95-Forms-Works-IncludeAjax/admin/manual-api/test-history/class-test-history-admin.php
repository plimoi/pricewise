<?php
/**
 * Test History Admin Class
 * Handles the admin interface for viewing and managing API general test history.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load the split components
require_once dirname(__FILE__) . '/class-test-history-admin/class-test-history-renderer.php';
require_once dirname(__FILE__) . '/class-test-history-admin/class-test-history-actions.php';
require_once dirname(__FILE__) . '/class-test-history-admin/class-test-history-data.php';

class Pricewise_Test_History_Admin {
    /**
     * Database handler
     *
     * @var Pricewise_Test_History_DB
     */
    private $db;

    /**
     * Renderer component
     *
     * @var Pricewise_Test_History_Renderer
     */
    private $renderer;

    /**
     * Actions component
     *
     * @var Pricewise_Test_History_Actions 
     */
    private $actions;

    /**
     * Data component
     *
     * @var Pricewise_Test_History_Data
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct() {
        require_once dirname(__FILE__) . '/class-test-history-db.php';
        $this->db = new Pricewise_Test_History_DB();

        // Initialize components
        $this->renderer = new Pricewise_Test_History_Renderer($this->db);
        $this->actions = new Pricewise_Test_History_Actions($this->db);
        $this->data = new Pricewise_Test_History_Data($this->db);

        // Initialize hooks
        add_action('admin_menu', array($this, 'add_submenu_page'), 20);
        add_action('admin_init', array($this->actions, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this->renderer, 'enqueue_scripts'));
        
        // Add AJAX handler for manual data list settings
        add_action('wp_ajax_pricewise_save_manual_data_fields', array($this->data, 'ajax_save_manual_data_fields'));
    }

    /**
     * Add submenu page for test history
     */
    public function add_submenu_page() {
        add_submenu_page(
            'pricewise',                // Parent slug
            'API General Test History',         // Page title
            'Test History',             // Menu title
            'manage_options',           // Capability
            'pricewise-test-history',   // Menu slug
            array($this, 'render_page') // Callback function
        );
    }

    /**
     * Render the test history page
     */
    public function render_page() {
        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Make sure the table exists
        if (!$this->db->table_exists()) {
            $this->db->create_table();
        }

        // Get the current view
        $view = isset($_REQUEST['view']) ? sanitize_text_field($_REQUEST['view']) : 'list';

        // Show admin notices
        $this->actions->show_admin_notices();

		echo '<div class="wrap">';

		if ($view === 'detail' && isset($_GET['id'])) {
			// For detail view, we'll let the detail renderer add the title
			$this->renderer->render_detail_view(absint($_GET['id']));
		} else {
			// Only show the general title on the list view
			echo '<h1 class="wp-heading-inline">API General Test History</h1>';
			// Render list view with filters and stats
			$this->render_list_view();
		}
        
        echo '</div>';
    }

    /**
     * Render the list view
     */
    private function render_list_view() {
        // Process filter parameters with proper sanitization
        $api_id = isset($_GET['api_id']) ? sanitize_text_field($_GET['api_id']) : '';
        $status_code = isset($_GET['status_code']) ? sanitize_text_field($_GET['status_code']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

        // Ensure per_page is within reasonable limits
        $per_page = max(1, min(100, $per_page));
        
        // Validate date format
        if (!empty($date_from) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
            $date_from = '';
        }
        if (!empty($date_to) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
            $date_to = '';
        }

        // Get API list for filter
        $api_list = $this->db->get_api_list();

        // Get test statistics
        $stats = $this->db->get_stats($api_id);

        // Query arguments
        $args = array(
            'per_page' => $per_page,
            'page' => $paged,
            'api_id' => $api_id,
            'status_code' => $status_code,
            'date_from' => $date_from,
            'date_to' => $date_to
        );

        // Get test history records
        $tests = $this->db->get_tests($args);
        $total_tests = $this->db->get_tests_count($args);

        // Calculate pagination
        $total_pages = ceil($total_tests / $per_page);

        // Render statistics
        $this->renderer->render_stats($stats);

        // Render filters
        $this->renderer->render_filters($api_list, $api_id, $status_code, $date_from, $date_to, $per_page);
        
        // Render tests table
        $this->renderer->render_tests_table($tests, $total_tests, $paged, $total_pages, $per_page);
        
        // Render PW Triggers section
        $this->renderer->render_triggers_section();

        // Render cleanup form
        $this->renderer->render_cleanup_form();
    }
}