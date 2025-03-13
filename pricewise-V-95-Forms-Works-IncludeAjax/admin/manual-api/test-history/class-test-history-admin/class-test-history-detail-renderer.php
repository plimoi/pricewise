<?php
/**
 * Test History Detail Renderer Class
 * Handles UI rendering for detail views and specialized UI components.
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Detail_Renderer {
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
     * Render manual api data view screen option
     */
    public function render_manual_data_list_preferences() {
        // Get all field names from the database
        $all_fields = $this->db->get_all_field_names();
        
        // Define default fields for the new section
        $default_fields = array(
            'api_name' => __('API Name', 'pricewise'),
            'endpoint' => __('Endpoint', 'pricewise'),
            'status_code' => __('Status Code', 'pricewise'),
            'response_time' => __('Response Time', 'pricewise'),
            'test_date' => __('Test Date', 'pricewise')
        );
        
        // Get user's saved preferences
        $user_id = get_current_user_id();
        $saved_default_fields = get_user_meta($user_id, 'pricewise_manual_data_default_fields', true);
        $saved_param_fields = get_user_meta($user_id, 'pricewise_manual_data_param_fields', true);
        $saved_header_fields = get_user_meta($user_id, 'pricewise_manual_data_header_fields', true);
        
        if (!is_array($saved_default_fields)) {
            $saved_default_fields = array();
        }
        
        if (!is_array($saved_param_fields)) {
            $saved_param_fields = array();
        }
        
        if (!is_array($saved_header_fields)) {
            $saved_header_fields = array();
        }
        
        // If no preferences saved yet, default to showing all fields
        $default_all = empty($saved_default_fields) && empty($saved_param_fields) && empty($saved_header_fields);
        
        ?>
        <div class="pricewise-manual-data-list">
            <h3><?php _e('Table Columns', 'pricewise'); ?></h3>
            
            <div class="pricewise-manual-data-content">
                <p class="description"><?php _e('Select which columns to display in the table.', 'pricewise'); ?></p>
                
                <div id="manual-data-message" style="display:none;"></div>
                
                <div class="manual-data-section">
                    <h4><?php _e('Default Columns', 'pricewise'); ?></h4>
                    <div class="checkbox-group">
                        <?php foreach ($default_fields as $field => $label): 
                            $field = sanitize_key($field);
                        ?>
                        <label>
                            <input type="checkbox" name="default_field[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($field); ?>" 
                                   <?php checked($default_all || isset($saved_default_fields[$field])); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($all_fields['params'])): ?>
                <div class="manual-data-section">
                    <h4><?php _e('Request Parameters', 'pricewise'); ?></h4>
                    <div class="param-selector-container">
                        <div class="param-selector-wrapper">
                            <input type="text" id="param-search" class="param-search" placeholder="<?php _e('Search parameters...', 'pricewise'); ?>">
                            <div class="param-dropdown-content" id="param-dropdown-content">
                                <?php foreach ($all_fields['params'] as $field): 
                                    $field = sanitize_key($field);
                                ?>
                                <div class="param-dropdown-item">
                                    <label>
                                        <input type="checkbox" name="param_field[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($field); ?>" 
                                               <?php checked($default_all || isset($saved_param_fields[$field])); ?>>
                                        <?php echo esc_html($field); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selected-params" id="selected-params">
                            <div class="selected-params-header"><?php _e('Selected Parameters:', 'pricewise'); ?></div>
                            <div class="selected-params-list" id="selected-params-list">
                                <?php 
                                $selected_count = 0;
                                foreach ($all_fields['params'] as $field): 
                                    $field = sanitize_key($field);
                                    if ($default_all || isset($saved_param_fields[$field])):
                                        $selected_count++;
                                ?>
                                <span class="selected-param-tag" data-param="<?php echo esc_attr($field); ?>">
                                    <?php echo esc_html($field); ?>
                                    <span class="remove-param">×</span>
                                </span>
                                <?php 
                                    endif;
                                endforeach; 
                                if ($selected_count === 0):
                                ?>
                                <span class="no-params-selected"><?php _e('No parameters selected', 'pricewise'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($all_fields['headers'])): ?>
                <div class="manual-data-section">
                    <h4><?php _e('Response Headers', 'pricewise'); ?></h4>
                    <div class="header-selector-container">
                        <div class="header-selector-wrapper">
                            <input type="text" id="header-search" class="header-search" placeholder="<?php _e('Search headers...', 'pricewise'); ?>">
                            <div class="header-dropdown-content" id="header-dropdown-content">
                                <?php foreach ($all_fields['headers'] as $field):
                                    $field = sanitize_key($field);
                                ?>
                                <div class="header-dropdown-item">
                                    <label>
                                        <input type="checkbox" name="header_field[<?php echo esc_attr($field); ?>]" value="<?php echo esc_attr($field); ?>" 
                                               <?php checked($default_all || isset($saved_header_fields[$field])); ?>>
                                        <?php echo esc_html($field); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selected-headers" id="selected-headers">
                            <div class="selected-headers-header"><?php _e('Selected Headers:', 'pricewise'); ?></div>
                            <div class="selected-headers-list" id="selected-headers-list">
                                <?php 
                                $selected_count = 0;
                                foreach ($all_fields['headers'] as $field): 
                                    $field = sanitize_key($field);
                                    if ($default_all || isset($saved_header_fields[$field])):
                                        $selected_count++;
                                ?>
                                <span class="selected-header-tag" data-header="<?php echo esc_attr($field); ?>">
                                    <?php echo esc_html($field); ?>
                                    <span class="remove-header">×</span>
                                </span>
                                <?php 
                                    endif;
                                endforeach; 
                                if ($selected_count === 0):
                                ?>
                                <span class="no-headers-selected"><?php _e('No headers selected', 'pricewise'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (empty($all_fields['params']) && empty($all_fields['headers']) && empty($default_fields)): ?>
                <p><?php _e('No additional fields found in test history. Run some API tests to populate this list.', 'pricewise'); ?></p>
                <?php else: ?>
                <button type="button" id="save-manual-data-fields" class="button button-primary"><?php _e('Apply', 'pricewise'); ?></button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render cleanup form
     */
    public function render_cleanup_form() {
        // Get current max records setting
        global $wpdb;
        $db = new Pricewise_Test_History_DB();
        $max_records = $db->get_max_records();
        ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php _e('Test History Settings', 'pricewise'); ?></h2>
            <p><?php _e('The system automatically maintains the specified maximum number of test records. Older records are automatically deleted when new tests are added.', 'pricewise'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_cleanup_tests'); ?>
                <input type="hidden" name="action" value="set_max_records">
                
                <p>
                    <label for="records_count"><?php _e('Maximum records to keep:', 'pricewise'); ?></label>
                    <input type="number" name="records_count" id="records_count" value="<?php echo esc_attr($max_records); ?>" min="1" max="1000" class="small-text">
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Setting', 'pricewise'); ?>">
                </p>
            </form>
            
            <h3><?php _e('Manual Cleanup', 'pricewise'); ?></h3>
            <p><?php _e('You can also run a manual cleanup to immediately apply the current setting.', 'pricewise'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_cleanup_tests'); ?>
                <input type="hidden" name="action" value="cleanup">
                <input type="hidden" name="records_count" value="<?php echo esc_attr($max_records); ?>">
                
                <p>
                    <input type="submit" class="button" value="<?php esc_attr_e('Run Manual Cleanup', 'pricewise'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete old test records? This cannot be undone.', 'pricewise'); ?>')">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render detail view
     *
     * @param int $id Test ID
     */
    public function render_detail_view($id) {
        // Verify user has proper permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Get test record
        $test = $this->db->get_test(absint($id));

        if (!$test) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Test record not found.', 'pricewise') . '</p></div>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=pricewise-test-history')) . '" class="button">' . esc_html__('Back to List', 'pricewise') . '</a></p>';
            return;
        }

        // Get user preferences for field visibility
        $user_id = get_current_user_id();
        $saved_default_fields = get_user_meta($user_id, 'pricewise_manual_data_default_fields', true);
        $saved_param_fields = get_user_meta($user_id, 'pricewise_manual_data_param_fields', true);
        $saved_header_fields = get_user_meta($user_id, 'pricewise_manual_data_header_fields', true);
        
        if (!is_array($saved_default_fields)) {
            $saved_default_fields = array();
        }
        
        if (!is_array($saved_param_fields)) {
            $saved_param_fields = array();
        }
        
        if (!is_array($saved_header_fields)) {
            $saved_header_fields = array();
        }
        
        // If no saved preferences, show all fields by default
        $show_all_fields = empty($saved_default_fields) && empty($saved_param_fields) && empty($saved_header_fields);

        // Main Title
		echo '<h1 class="wp-heading-inline">API Detailed Test History</h1>';

        // Back button
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=pricewise-test-history')) . '" class="button">' . esc_html__('Back to List', 'pricewise') . '</a></p>';

        // Test details
        echo '<div class="pricewise-history-detail">';
        
		// Sub Title
		echo '<h2>' . esc_html(sprintf(
			__('Test Details: %s', 'pricewise'),
			$test['api_name']
		)) . '</h2>';

        // Response meta - Show fields based on user preferences
        echo '<div class="response-meta">';
        
        // Default fields
        $default_field_display = array(
            'api_name' => __('API Name', 'pricewise'),
            'endpoint' => __('Endpoint', 'pricewise'),
            'status_code' => __('Status Code', 'pricewise'),
            'response_time' => __('Response Time', 'pricewise'),
            'test_date' => __('Test Date', 'pricewise')
        );
        
        foreach ($default_field_display as $field => $label) {
            if ($show_all_fields || isset($saved_default_fields[$field])) {
                if ($field == 'test_date' && isset($test[$field])) {
                    echo '<div class="meta-item">';
                    echo '<div class="label">' . esc_html($label) . '</div>';
                    echo '<div class="value">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($test[$field]))) . '</div>';
                    echo '</div>';
                } else if ($field == 'status_code' && isset($test[$field])) {
                    echo '<div class="meta-item">';
                    echo '<div class="label">' . esc_html($label) . '</div>';
                    $status_class = '';
                    if ($test[$field] >= 200 && $test[$field] < 300) {
                        $status_class = 'status-success';
                    } elseif ($test[$field] >= 400 && $test[$field] < 500) {
                        $status_class = 'status-warning';
                    } else {
                        $status_class = 'status-error';
                    }
                    echo '<div class="value ' . esc_attr($status_class) . '">' . esc_html($test[$field]) . '</div>';
                    echo '</div>';
                } else if ($field == 'response_time' && isset($test[$field])) {
                    echo '<div class="meta-item">';
                    echo '<div class="label">' . esc_html($label) . '</div>';
                    echo '<div class="value">' . esc_html(round($test[$field], 3)) . ' s</div>';
                    echo '</div>';
                } else if (isset($test[$field])) {
                    echo '<div class="meta-item">';
                    echo '<div class="label">' . esc_html($label) . '</div>';
                    echo '<div class="value">' . esc_html($test[$field]) . '</div>';
                    echo '</div>';
                }
            }
        }
        
        echo '</div>'; // End response meta

        // Request headers
        if (!empty($test['request_headers'])) {
            echo '<div class="section">';
            echo '<h3>' . esc_html__('Request Headers', 'pricewise') . '</h3>';
            echo '<pre>' . esc_html($this->format_array_for_display($test['request_headers'])) . '</pre>';
            echo '</div>';
        }

        // Request parameters - Always show without filtering
        if (!empty($test['request_params'])) {
            // Ensure request_params is properly formatted as array
            $params = $test['request_params'];
            if (!is_array($params)) {
                $params = json_decode($params, true);
                if (!is_array($params)) {
                    // If still not an array, try to unserialize
                    $params = maybe_unserialize($params);
                }
            }
            
            if (is_array($params)) {
                echo '<div class="section">';
                echo '<h3>' . esc_html__('Request Parameters', 'pricewise') . '</h3>';
                echo '<pre>' . esc_html($this->format_array_for_display($params)) . '</pre>';
                echo '</div>';
            } else {
                // Fallback if we couldn't convert to array
                echo '<div class="section">';
                echo '<h3>' . esc_html__('Request Parameters', 'pricewise') . '</h3>';
                echo '<pre>' . esc_html($test['request_params']) . '</pre>';
                echo '</div>';
            }
        }

        // Response headers
        if (!empty($test['response_headers'])) {
            // Determine which headers to display based on user preferences
            $display_headers = array();
            
            if ($show_all_fields) {
                // Show all headers
                $display_headers = $test['response_headers'];
            } else {
                // Show only selected headers
                foreach ($test['response_headers'] as $key => $value) {
                    if (isset($saved_header_fields[$key])) {
                        $display_headers[$key] = $value;
                    }
                }
            }
            
            if (!empty($display_headers)) {
                echo '<div class="section">';
                echo '<h3>' . esc_html__('Response Headers', 'pricewise') . '</h3>';
                echo '<pre>' . esc_html($this->format_array_for_display($display_headers)) . '</pre>';
                echo '</div>';
            }
        }

        // Response snippet
        if (!empty($test['response_snippet'])) {
            echo '<div class="section">';
            echo '<h3>' . esc_html__('Response Body', 'pricewise') . '</h3>';
            echo '<pre>' . esc_html($test['response_snippet']) . '</pre>';
            echo '</div>';
        }

        // Error message
        if ((!empty($test['error_message'])) && ($show_all_fields || isset($saved_default_fields['error_message']))) {
            echo '<div class="section">';
            echo '<h3>' . esc_html__('Error Message', 'pricewise') . '</h3>';
            echo '<div class="status-error">' . esc_html($test['error_message']) . '</div>';
            echo '</div>';
        }

        echo '</div>'; // End detail view
    }

    /**
     * Format array for display
     *
     * @param array $array Array to format
     * @return string Formatted string
     */
    private function format_array_for_display($array) {
        if (empty($array)) {
            return '';
        }
        
        ob_start();
        foreach ($array as $key => $value) {
            $key = sanitize_text_field($key);
            echo $key . ': ';
            
            if (is_array($value)) {
                echo "\n";
                foreach ($value as $sub_key => $sub_value) {
                    $sub_key = sanitize_text_field($sub_key);
                    $sub_value = sanitize_text_field($sub_value);
                    echo '  ' . $sub_key . ': ' . $sub_value . "\n";
                }
            } else {
                echo sanitize_text_field($value) . "\n";
            }
        }
        
        return trim(ob_get_clean());
    }
}