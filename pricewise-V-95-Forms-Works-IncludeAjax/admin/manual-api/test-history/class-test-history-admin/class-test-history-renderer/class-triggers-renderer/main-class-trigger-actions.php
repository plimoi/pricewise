<?php
/**
 * Main Triggers Action Class
 * Handles the trigger action form renderer and processing.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include required classes
require_once dirname(__FILE__) . '/class-trigger-db.php';
require_once dirname(__FILE__) . '/class-trigger-handler.php';

class Pricewise_Triggers_Actions {
    /**
     * Database handler
     *
     * @var Pricewise_Trigger_DB
     */
    protected $db;
    
    /**
     * Trigger handler
     *
     * @var Pricewise_Trigger_Handler
     */
    protected $handler;
    
    /**
     * Available trigger actions
     *
     * @var array
     */
    private $available_actions = array();
    
    /**
     * Test history database
     * 
     * @var Pricewise_Test_History_DB
     */
    private $test_history_db;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        $this->test_history_db = $db;
        $this->db = new Pricewise_Trigger_DB();
        $this->handler = new Pricewise_Trigger_Handler();
        
        // Initialize the triggers table
        $this->db->create_table();
        
        // Set up form processing
        add_action('admin_init', array($this, 'process_form_submission'));
        
        // Get available actions from handler
        $this->available_actions = $this->handler->get_available_actions();
    }
    
    /**
     * Process form submissions
     */
    public function process_form_submission() {
        // Check if we're processing our form
        if (!isset($_POST['pricewise_save_trigger_action_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['pricewise_save_trigger_action_nonce'], 'pricewise_save_trigger_action')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        // Process form based on action
        if (isset($_POST['save_action'])) {
            $this->save_trigger();
        } elseif (isset($_POST['delete_trigger']) && isset($_POST['trigger_id'])) {
            $this->delete_trigger((int)$_POST['trigger_id']);
        } elseif (isset($_POST['toggle_trigger']) && isset($_POST['trigger_id'])) {
            $this->toggle_trigger((int)$_POST['trigger_id'], isset($_POST['is_active']));
        }
        
        // Redirect to prevent form resubmission
        wp_redirect(add_query_arg(array('page' => 'pricewise-test-history', 'tab' => 'triggers'), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Save a trigger
     */
    private function save_trigger() {
        // Sanitize and validate form data
        $trigger_data = array(
            'trigger_name' => isset($_POST['trigger_name']) ? sanitize_text_field($_POST['trigger_name']) : '',
            'trigger_condition' => isset($_POST['trigger_condition']) ? sanitize_text_field($_POST['trigger_condition']) : 'if',
            'trigger_special_field' => isset($_POST['trigger_special_fields']) ? sanitize_text_field($_POST['trigger_special_fields']) : '',
            'trigger_comparison' => isset($_POST['trigger_comparison']) ? sanitize_text_field($_POST['trigger_comparison']) : 'equals',
            'trigger_value' => isset($_POST['trigger_value']) ? sanitize_text_field($_POST['trigger_value']) : '',
            'trigger_action' => isset($_POST['trigger_action']) ? sanitize_text_field($_POST['trigger_action']) : '',
            'is_active' => true
        );
        
        // Add ID if updating
        if (isset($_POST['trigger_id']) && !empty($_POST['trigger_id'])) {
            $trigger_data['id'] = (int)$_POST['trigger_id'];
        }
        
        // Save the trigger
        $result = $this->db->save_trigger($trigger_data);
        
        // Set admin notice
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Trigger saved successfully.', 'pricewise') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error saving trigger.', 'pricewise') . '</p></div>';
            });
        }
    }
    
    /**
     * Delete a trigger
     * 
     * @param int $trigger_id Trigger ID
     */
    private function delete_trigger($trigger_id) {
        $result = $this->db->delete_trigger($trigger_id);
        
        // Set admin notice
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Trigger deleted successfully.', 'pricewise') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error deleting trigger.', 'pricewise') . '</p></div>';
            });
        }
    }
    
    /**
     * Toggle a trigger's active status
     * 
     * @param int $trigger_id Trigger ID
     * @param bool $is_active Active status
     */
    private function toggle_trigger($trigger_id, $is_active) {
        $result = $this->db->toggle_trigger_status($trigger_id, $is_active);
        
        // Set admin notice
        if ($result) {
            add_action('admin_notices', function() use ($is_active) {
                $message = $is_active ? 
                    __('Trigger activated successfully.', 'pricewise') : 
                    __('Trigger deactivated successfully.', 'pricewise');
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating trigger status.', 'pricewise') . '</p></div>';
            });
        }
    }
    
    /**
     * Get all available field keys from test history
     * 
     * @return array Field keys
     */
    private function get_available_field_keys() {
        // Get field names from test history database
        $field_names = $this->test_history_db->get_all_field_names(50);
        
        // Add standard fields
        $standard_fields = array(
            'status_code' => 'Status Code',
            'response_time' => 'Response Time',
            'error_message' => 'Error Message',
        );
        
        // Combine all fields
        $all_fields = array_merge(
            $standard_fields,
            array_combine($field_names['params'], $field_names['params']),
            array_combine($field_names['headers'], $field_names['headers'])
        );
        
        return $all_fields;
    }
    
    /**
     * Render the trigger action form
     */
    public function render_trigger_form() {
        // Get existing triggers
        $triggers = $this->db->get_triggers();
        
        // Get available field keys
        $available_fields = $this->get_available_field_keys();
        
        // Get editing trigger if applicable
        $editing_trigger = null;
        $editing_id = isset($_GET['edit_trigger']) ? (int)$_GET['edit_trigger'] : 0;
        
        if ($editing_id) {
            $editing_trigger = $this->db->get_trigger($editing_id);
        }
        
        ?>
        <div class="trigger-form-container" style="margin-top: 10px; padding: 15px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: 3px;">
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_save_trigger_action', 'pricewise_save_trigger_action_nonce'); ?>
                
                <?php if ($editing_trigger): ?>
                    <input type="hidden" name="trigger_id" value="<?php echo esc_attr($editing_trigger['id']); ?>">
                <?php endif; ?>
                
                <div class="form-row" style="margin-bottom: 15px; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <!-- Trigger name field -->
                    <div style="flex: 0 1 150px;">
                        <label for="trigger_name" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Trigger name:', 'pricewise'); ?>
                        </label>
                        <input type="text" name="trigger_name" id="trigger_name" class="regular-text" 
                               style="width: 100%;" placeholder="<?php esc_attr_e('Enter trigger name', 'pricewise'); ?>"
                               value="<?php echo $editing_trigger ? esc_attr($editing_trigger['trigger_name']) : ''; ?>" required>
                    </div>
                    
                    <!-- Condition dropdown (if, unless, etc.) -->
                    <div style="flex: 0 1 100px;">
                        <label for="trigger_condition" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Condition:', 'pricewise'); ?>
                        </label>
                        <select name="trigger_condition" id="trigger_condition" style="width: 100%;">
                            <?php
                            $conditions = array(
                                'if' => __('if', 'pricewise'),
                                'unless' => __('unless', 'pricewise'),
                                'when' => __('when', 'pricewise'),
                                'always' => __('always', 'pricewise'),
                                'only_if' => __('only if', 'pricewise')
                            );
                            
                            foreach ($conditions as $value => $label) {
                                $selected = $editing_trigger && $editing_trigger['trigger_condition'] === $value ? 'selected' : '';
                                echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Trigger special fields -->
                    <div style="flex: 0 1 180px;">
                        <label for="trigger_special_fields" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Trigger special fields:', 'pricewise'); ?>
                        </label>
                        <input type="text" name="trigger_special_fields" id="trigger_special_fields" class="regular-text" 
                               style="width: 100%;" placeholder="<?php esc_attr_e('Enter special fields', 'pricewise'); ?>"
                               value="<?php echo $editing_trigger ? esc_attr($editing_trigger['trigger_special_field']) : ''; ?>"
                               list="available_fields" required>
                        <datalist id="available_fields">
                            <?php foreach ($available_fields as $field_key => $field_label): ?>
                                <option value="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <!-- Comparison dropdown (Same, Equals, etc.) -->
                    <div style="flex: 0 1 120px;">
                        <label for="trigger_comparison" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Comparison:', 'pricewise'); ?>
                        </label>
                        <select name="trigger_comparison" id="trigger_comparison" style="width: 100%;">
                            <?php
                            $comparisons = array(
                                'same' => __('Same', 'pricewise'),
                                'equals' => __('Equals', 'pricewise'),
                                'not_equals' => __('Not Equals', 'pricewise'),
                                'contains' => __('Contains', 'pricewise'),
                                'starts_with' => __('Starts with', 'pricewise'),
                                'ends_with' => __('Ends with', 'pricewise'),
                                'more_than' => __('More than', 'pricewise'),
                                'less_than' => __('Less than', 'pricewise'),
                                'greater_equal' => __('Greater or Equal', 'pricewise'),
                                'less_equal' => __('Less or Equal', 'pricewise')
                            );
                            
                            foreach ($comparisons as $value => $label) {
                                $selected = $editing_trigger && $editing_trigger['trigger_comparison'] === $value ? 'selected' : '';
                                echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Trigger value field -->
                    <div style="flex: 0 1 150px;">
                        <label for="trigger_value" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Trigger value:', 'pricewise'); ?>
                        </label>
                        <input type="text" name="trigger_value" id="trigger_value" class="regular-text" 
                               style="width: 100%;" placeholder="<?php esc_attr_e('Enter value', 'pricewise'); ?>"
                               value="<?php echo $editing_trigger ? esc_attr($editing_trigger['trigger_value']) : ''; ?>" required>
                    </div>
                    
                    <!-- Trigger action dropdown -->
                    <div style="flex: 0 1 150px;">
                        <label for="trigger_action" style="display: block; margin-bottom: 5px; font-weight: bold; font-size: 13px;">
                            <?php _e('Trigger Action:', 'pricewise'); ?>
                        </label>
                        <select name="trigger_action" id="trigger_action" style="width: 100%;" required>
                            <option value=""><?php _e('- Select Action -', 'pricewise'); ?></option>
                            <?php if (!empty($this->available_actions)) : ?>
                                <?php foreach ($this->available_actions as $key => $action) : ?>
                                    <?php $selected = $editing_trigger && $editing_trigger['trigger_action'] === $key ? 'selected' : ''; ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php echo $selected; ?> title="<?php echo esc_attr($action['description']); ?>">
                                        <?php echo esc_html($action['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled><?php _e('No trigger actions available', 'pricewise'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="display: flex; gap: 10px;">
                    <?php if ($editing_trigger): ?>
                        <input type="submit" name="save_action" class="button button-primary" value="<?php esc_attr_e('Update Trigger', 'pricewise'); ?>">
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'pricewise-test-history', 'tab' => 'triggers'), admin_url('admin.php'))); ?>" class="button">
                            <?php _e('Cancel', 'pricewise'); ?>
                        </a>
                    <?php else: ?>
                        <input type="submit" name="save_action" class="button button-primary" value="<?php esc_attr_e('Save PW Actions', 'pricewise'); ?>">
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- List of existing triggers -->
        <div class="existing-triggers" style="margin-top: 15px;">
            <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 14px;"><?php _e('Existing Triggers', 'pricewise'); ?></h4>
            
            <?php if (empty($triggers)): ?>
                <p style="font-style: italic; color: #666;"><?php _e('No triggers defined yet.', 'pricewise'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Name', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Condition', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Field', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Comparison', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Value', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Action', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Status', 'pricewise'); ?></th>
                            <th scope="col"><?php _e('Actions', 'pricewise'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($triggers as $trigger): ?>
                            <tr>
                                <td><?php echo esc_html($trigger['trigger_name']); ?></td>
                                <td><?php echo esc_html($trigger['trigger_condition']); ?></td>
                                <td><?php echo esc_html($trigger['trigger_special_field']); ?></td>
                                <td><?php echo esc_html($trigger['trigger_comparison']); ?></td>
                                <td><?php echo esc_html($trigger['trigger_value']); ?></td>
                                <td>
                                    <?php 
                                    $action_key = $trigger['trigger_action'];
                                    if (isset($this->available_actions[$action_key])) {
                                        echo esc_html($this->available_actions[$action_key]['name']);
                                    } else {
                                        echo esc_html($action_key);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($trigger['is_active']): ?>
                                        <span style="color: #46b450;"><?php _e('Active', 'pricewise'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3232;"><?php _e('Inactive', 'pricewise'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo esc_url(add_query_arg(array('edit_trigger' => $trigger['id']), add_query_arg(array('page' => 'pricewise-test-history', 'tab' => 'triggers'), admin_url('admin.php')))); ?>">
                                                <?php _e('Edit', 'pricewise'); ?>
                                            </a> | 
                                        </span>
                                        <span class="toggle">
                                            <form method="post" action="" style="display:inline;">
                                                <?php wp_nonce_field('pricewise_save_trigger_action', 'pricewise_save_trigger_action_nonce'); ?>
                                                <input type="hidden" name="trigger_id" value="<?php echo esc_attr($trigger['id']); ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $trigger['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" name="toggle_trigger" class="button-link">
                                                    <?php echo $trigger['is_active'] ? __('Deactivate', 'pricewise') : __('Activate', 'pricewise'); ?>
                                                </button>
                                            </form> | 
                                        </span>
                                        <span class="delete">
                                            <form method="post" action="" style="display:inline;">
                                                <?php wp_nonce_field('pricewise_save_trigger_action', 'pricewise_save_trigger_action_nonce'); ?>
                                                <input type="hidden" name="trigger_id" value="<?php echo esc_attr($trigger['id']); ?>">
                                                <button type="submit" name="delete_trigger" class="button-link" 
                                                        onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this trigger?', 'pricewise')); ?>');">
                                                    <?php _e('Delete', 'pricewise'); ?>
                                                </button>
                                            </form>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}