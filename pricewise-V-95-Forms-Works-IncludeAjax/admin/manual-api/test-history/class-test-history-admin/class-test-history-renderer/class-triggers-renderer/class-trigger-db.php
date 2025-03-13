<?php
/**
 * Trigger Database Class
 * Handles database operations for the PW Triggers feature.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Trigger_DB {
    /**
     * The table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pricewise_triggers';
        
        // Ensure the table exists
        $this->create_table();
    }

    /**
     * Create or update the database table
     *
     * @return bool Success or failure
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            trigger_name varchar(255) NOT NULL,
            trigger_condition varchar(50) NOT NULL,
            trigger_special_field varchar(255) NOT NULL,
            trigger_comparison varchar(50) NOT NULL,
            trigger_value varchar(255) NOT NULL,
            trigger_action varchar(100) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_condition (trigger_condition),
            KEY trigger_special_field (trigger_special_field),
            KEY trigger_comparison (trigger_comparison),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Check if table was created successfully
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    /**
     * Save a trigger configuration
     *
     * @param array $data Trigger data
     * @return int|false The record ID or false on failure
     */
    public function save_trigger($data) {
        global $wpdb;

        // Ensure required fields
        if (empty($data['trigger_name']) || empty($data['trigger_condition']) || 
            empty($data['trigger_special_field']) || empty($data['trigger_comparison']) || 
            !isset($data['trigger_value']) || empty($data['trigger_action'])) {
            return false;
        }

        // Format data for insertion
        $insert_data = array(
            'trigger_name' => sanitize_text_field($data['trigger_name']),
            'trigger_condition' => sanitize_text_field($data['trigger_condition']),
            'trigger_special_field' => sanitize_text_field($data['trigger_special_field']),
            'trigger_comparison' => sanitize_text_field($data['trigger_comparison']),
            'trigger_value' => sanitize_text_field($data['trigger_value']),
            'trigger_action' => sanitize_text_field($data['trigger_action']),
            'is_active' => isset($data['is_active']) ? 1 : 0
        );

        // Check if we're updating an existing trigger
        if (!empty($data['id'])) {
            $result = $wpdb->update(
                $this->table_name,
                $insert_data,
                array('id' => absint($data['id']))
            );
            
            return ($result !== false) ? absint($data['id']) : false;
        }

        // Insert new trigger
        $result = $wpdb->insert($this->table_name, $insert_data);
        
        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get all saved triggers
     *
     * @param array $args Optional arguments for filtering
     * @return array List of triggers
     */
    public function get_triggers($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'is_active' => null,
            'orderby' => 'id',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $this->table_name WHERE 1=1";
        $prepare_args = array();
        
        if (isset($args['is_active'])) {
            $sql .= " AND is_active = %d";
            $prepare_args[] = $args['is_active'] ? 1 : 0;
        }
        
        // Add ordering
        $orderby = in_array($args['orderby'], array('id', 'trigger_name', 'created_at', 'updated_at')) ? 
                   $args['orderby'] : 'id';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql .= " ORDER BY $orderby $order";
        
        if (!empty($prepare_args)) {
            $sql = $wpdb->prepare($sql, $prepare_args);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results ?: array();
    }

    /**
     * Get a single trigger
     *
     * @param int $id Trigger ID
     * @return array|null Trigger data or null if not found
     */
    public function get_trigger($id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $id
        );
        
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Delete a trigger
     *
     * @param int $id Trigger ID
     * @return bool Success or failure
     */
    public function delete_trigger($id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => absint($id)),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Toggle a trigger's active status
     *
     * @param int $id Trigger ID
     * @param bool $active Active status
     * @return bool Success or failure
     */
    public function toggle_trigger_status($id, $active) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('is_active' => $active ? 1 : 0),
            array('id' => absint($id)),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Table exists check
     *
     * @return bool Whether the table exists
     */
    public function table_exists() {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }
}