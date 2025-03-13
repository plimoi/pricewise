<?php
/**
 * Test History Actions Class
 * Handles processing of actions for the API test history admin interface.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Actions {
    /**
     * Database handler
     *
     * @var Pricewise_Test_History_DB
     */
    private $db;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test record deleted successfully.', 'pricewise') . '</p></div>';
        }

        if (isset($_GET['bulk-deleted']) && is_numeric($_GET['bulk-deleted'])) {
            $count = absint($_GET['bulk-deleted']);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d test records deleted successfully.', 'pricewise'), $count) . '</p></div>';
        }

        if (isset($_GET['cleaned']) && is_numeric($_GET['cleaned'])) {
            $count = absint($_GET['cleaned']);
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d old test records cleaned up successfully.', 'pricewise'), $count) . '</p></div>';
        }
        
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Maximum record count setting updated successfully.', 'pricewise') . '</p></div>';
        }
    }

    /**
     * Handle admin actions
     */
    public function handle_actions() {
        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'pricewise-test-history') {
            return;
        }

        // Handle delete action
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && isset($_REQUEST['id'])) {
            $this->handle_delete_action();
        }

        // Handle bulk delete action
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'bulk-delete' && isset($_REQUEST['test_ids'])) {
            $this->handle_bulk_delete_action();
        }

        // Handle cleanup action
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'cleanup') {
            $this->handle_cleanup_action();
        }
        
        // Handle set max records action
        if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'set_max_records') {
            $this->handle_set_max_records_action();
        }
    }

    /**
     * Handle delete action
     */
    private function handle_delete_action() {
        // Verify nonce
        check_admin_referer('pricewise_delete_test');
        
        $id = absint($_REQUEST['id']);
        $this->db->delete_tests($id);
        
        wp_redirect(add_query_arg(array('deleted' => 'true'), remove_query_arg(array('action', 'id', '_wpnonce'))));
        exit;
    }

    /**
     * Handle bulk delete action
     */
    private function handle_bulk_delete_action() {
        // Verify nonce
        check_admin_referer('bulk-tests');
        
        $ids = array_map('absint', (array) $_REQUEST['test_ids']);
        $this->db->delete_tests($ids);
        
        wp_redirect(add_query_arg(array('bulk-deleted' => count($ids)), remove_query_arg(array('action', 'test_ids', '_wpnonce'))));
        exit;
    }

    /**
     * Handle cleanup action
     */
    private function handle_cleanup_action() {
        // Verify nonce
        check_admin_referer('pricewise_cleanup_tests');
        
        $records_count = isset($_REQUEST['records_count']) ? absint($_REQUEST['records_count']) : null;
        $deleted = $this->db->cleanup_old_tests($records_count);
        
        wp_redirect(add_query_arg(array('cleaned' => $deleted), remove_query_arg(array('action', 'records_count', '_wpnonce'))));
        exit;
    }
    
    /**
     * Handle set max records action
     */
    private function handle_set_max_records_action() {
        // Verify nonce
        check_admin_referer('pricewise_cleanup_tests');
        
        $records_count = isset($_REQUEST['records_count']) ? absint($_REQUEST['records_count']) : 30;
        $this->db->set_max_records($records_count);
        
        // Run cleanup immediately with new setting
        $deleted = $this->db->cleanup_old_tests($records_count);
        
        wp_redirect(add_query_arg(
            array(
                'settings-updated' => 'true',
                'cleaned' => $deleted
            ), 
            remove_query_arg(array('action', 'records_count', '_wpnonce'))
        ));
        exit;
    }
}