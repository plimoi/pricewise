<?php
/**
 * Test History Renderer Class
 * Handles main UI rendering for the API test history admin interface.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the detail renderer from the same directory
require_once dirname(__FILE__) . '/class-test-history-detail-renderer.php';

// Include the new renderer classes from the parallel directory
require_once dirname(__FILE__) . '/class-test-history-renderer/class-base-renderer.php';
require_once dirname(__FILE__) . '/class-test-history-renderer/class-stats-renderer.php';
require_once dirname(__FILE__) . '/class-test-history-renderer/class-table-renderer.php';
require_once dirname(__FILE__) . '/class-test-history-renderer/class-triggers-renderer.php';

class Pricewise_Test_History_Renderer {
    /**
     * Database handler
     *
     * @var Pricewise_Test_History_DB
     */
    private $db;
    
    /**
     * Detail renderer
     *
     * @var Pricewise_Test_History_Detail_Renderer
     */
    private $detail_renderer;
    
    /**
     * Stats renderer
     *
     * @var Pricewise_Test_History_Stats_Renderer
     */
    private $stats_renderer;
    
    /**
     * Table renderer
     *
     * @var Pricewise_Test_History_Table_Renderer
     */
    private $table_renderer;
    
    /**
     * Triggers renderer
     *
     * @var Pricewise_Test_History_Triggers_Renderer
     */
    private $triggers_renderer;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        $this->db = $db;
        $this->detail_renderer = new Pricewise_Test_History_Detail_Renderer($db);
        $this->stats_renderer = new Pricewise_Test_History_Stats_Renderer($db, $this->detail_renderer);
        $this->table_renderer = new Pricewise_Test_History_Table_Renderer($db);
        $this->triggers_renderer = new Pricewise_Test_History_Triggers_Renderer($db);
    }

    /**
     * Enqueue scripts and styles
     *
     * @param string $hook Current admin page
     */
    public function enqueue_scripts($hook) {
        $this->stats_renderer->enqueue_scripts($hook);
    }

    /**
     * Render statistics section
     *
     * @param array $stats Statistics
     */
    public function render_stats($stats) {
        $this->stats_renderer->render_stats($stats);
    }

    /**
     * Render filters
     *
     * @param array $api_list List of APIs
     * @param string $api_id Current API ID filter
     * @param string $status_code Current status code filter
     * @param string $date_from Current from date filter
     * @param string $date_to Current to date filter
     * @param int $per_page Items per page
     */
    public function render_filters($api_list, $api_id, $status_code, $date_from, $date_to, $per_page) {
        $this->stats_renderer->render_filters($api_list, $api_id, $status_code, $date_from, $date_to, $per_page);
    }

    /**
     * Render tests table
     *
     * @param array $tests Test records
     * @param int $total_tests Total number of tests
     * @param int $paged Current page
     * @param int $total_pages Total pages
     * @param int $per_page Items per page
     */
    public function render_tests_table($tests, $total_tests, $paged, $total_pages, $per_page) {
        $this->table_renderer->render_tests_table($tests, $total_tests, $paged, $total_pages, $per_page);
    }
    
    /**
     * Render PW Triggers section
     */
    public function render_triggers_section() {
        $this->triggers_renderer->render_triggers_section();
    }

    /**
     * Render detail view
     *
     * @param int $id Test ID
     */
    public function render_detail_view($id) {
        $this->detail_renderer->render_detail_view($id);
    }

    /**
     * Render cleanup form
     */
    public function render_cleanup_form() {
        $this->stats_renderer->render_cleanup_form();
    }
}