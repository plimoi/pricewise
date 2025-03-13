<?php
/**
 * Test History Table Renderer Class
 * Handles rendering of the test history table
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Table_Renderer extends Pricewise_Test_History_Base_Renderer {
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
        $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;'),
            'next_text' => __('&raquo;'),
            'total' => $total_pages,
            'current' => $paged
        ));
        
        // Get user's saved column preferences
        $preferences = $this->get_user_column_preferences();
        $saved_default_fields = $preferences['default_fields'];
        $saved_param_fields = $preferences['param_fields'];
        $saved_header_fields = $preferences['header_fields'];
        $show_all_columns = $preferences['show_all_columns'];
        
        // Default columns
        $default_columns = array(
            'api_name' => __('API', 'pricewise'),
            'endpoint' => __('Endpoint', 'pricewise'),
            'status_code' => __('Status', 'pricewise'),
            'response_time' => __('Response Time', 'pricewise'),
            'test_date' => __('Date', 'pricewise')
        );
        
        // Determine visible columns - including all selected fields from all sections
        $visible_columns = array();
        
        // Default columns first
        foreach ($default_columns as $column_key => $column_label) {
            if ($show_all_columns || isset($saved_default_fields[$column_key])) {
                $visible_columns[$column_key] = $column_label;
            }
        }
        
        // Add parameter columns if selected
        if (!empty($saved_param_fields)) {
            foreach ($saved_param_fields as $param_key => $value) {
                $visible_columns['param_' . $param_key] = $param_key;
            }
        }
        
        // Add header columns if selected
        if (!empty($saved_header_fields)) {
            foreach ($saved_header_fields as $header_key => $value) {
                $visible_columns['header_' . $header_key] = $header_key;
            }
        }
        
        ?>
		<div>
			<h3>Manual API Data View</h3>
			<form method="post" action="">
				<?php wp_nonce_field('bulk-tests'); ?>
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="action">
							<option value="-1"><?php _e('Bulk Actions', 'pricewise'); ?></option>
							<option value="bulk-delete"><?php _e('Delete', 'pricewise'); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'pricewise'); ?>">
					</div>
					
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php echo sprintf(
								_n('%s item', '%s items', $total_tests, 'pricewise'),
								number_format_i18n($total_tests)
							); ?>
						</span>
						<?php if ($page_links) : ?>
							<span class="pagination-links"><?php echo $page_links; ?></span>
						<?php endif; ?>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all-1">
							</td>
							<?php foreach ($visible_columns as $column_key => $column_label) : ?>
                                <th scope="col" class="manage-column"><?php echo esc_html($column_label); ?></th>
                            <?php endforeach; ?>
							<th scope="col" class="manage-column"><?php _e('Actions', 'pricewise'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($tests)) : ?>
							<tr>
								<td colspan="<?php echo count($visible_columns) + 2; ?>"><?php _e('No test records found.', 'pricewise'); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ($tests as $test) : ?>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="test_ids[]" value="<?php echo esc_attr($test['id']); ?>">
									</th>
									<?php foreach ($visible_columns as $column_key => $column_label) : 
                                        // Handle different column types
                                        if (strpos($column_key, 'param_') === 0) {
                                            // Parameter column
                                            $param_name = substr($column_key, 6); // Remove 'param_' prefix
                                            $param_value = '';
                                            
                                            // Get value from request_params
                                            if (!empty($test['request_params'])) {
                                                $params = $test['request_params'];
                                                if (!is_array($params)) {
                                                    $params = json_decode($params, true);
                                                    if (!is_array($params)) {
                                                        $params = maybe_unserialize($params);
                                                    }
                                                }
                                                
                                                if (is_array($params) && isset($params[$param_name])) {
                                                    $param_value = $params[$param_name];
                                                }
                                            }
                                            ?>
                                            <td><?php echo esc_html(is_scalar($param_value) ? $param_value : json_encode($param_value)); ?></td>
                                            <?php
                                        } elseif (strpos($column_key, 'header_') === 0) {
                                            // Header column
                                            $header_name = substr($column_key, 7); // Remove 'header_' prefix
                                            $header_value = '';
                                            
                                            // Get value from response_headers
                                            if (!empty($test['response_headers'])) {
                                                $headers = $test['response_headers'];
                                                if (!is_array($headers)) {
                                                    $headers = json_decode($headers, true);
                                                    if (!is_array($headers)) {
                                                        $headers = maybe_unserialize($headers);
                                                    }
                                                }
                                                
                                                if (is_array($headers) && isset($headers[$header_name])) {
                                                    $header_value = $headers[$header_name];
                                                }
                                            }
                                            ?>
                                            <td><?php echo esc_html(is_scalar($header_value) ? $header_value : json_encode($header_value)); ?></td>
                                            <?php
                                        } else {
                                            // Default column
                                            ?>
                                            <td>
                                                <?php if ($column_key === 'status_code') : ?>
                                                    <?php 
                                                    $status_class = '';
                                                    if ($test[$column_key] >= 200 && $test[$column_key] < 300) {
                                                        $status_class = 'status-success';
                                                    } elseif ($test[$column_key] >= 400 && $test[$column_key] < 500) {
                                                        $status_class = 'status-warning';
                                                    } else {
                                                        $status_class = 'status-error';
                                                    }
                                                    ?>
                                                    <span class="<?php echo esc_attr($status_class); ?>">
                                                        <?php echo esc_html($test[$column_key]); ?>
                                                    </span>
                                                <?php elseif ($column_key === 'response_time') : ?>
                                                    <?php 
                                                    if ($test[$column_key]) {
                                                        echo esc_html(round($test[$column_key], 3)) . ' s';
                                                    } else {
                                                        echo '&mdash;';
                                                    }
                                                    ?>
                                                <?php elseif ($column_key === 'test_date') : ?>
                                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($test[$column_key]))); ?>
                                                <?php else : ?>
                                                    <?php echo esc_html($test[$column_key]); ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php
                                        }
                                    endforeach; ?>
									<td>
										<a href="<?php echo esc_url(add_query_arg(array('view' => 'detail', 'id' => $test['id']))); ?>" class="button button-small"><?php _e('View', 'pricewise'); ?></a>
										<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete', 'id' => $test['id'])), 'pricewise_delete_test')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this test record?', 'pricewise'); ?>')"><?php _e('Delete', 'pricewise'); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all-2">
							</td>
							<?php foreach ($visible_columns as $column_key => $column_label) : ?>
                                <th scope="col" class="manage-column"><?php echo esc_html($column_label); ?></th>
                            <?php endforeach; ?>
							<th scope="col" class="manage-column"><?php _e('Actions', 'pricewise'); ?></th>
						</tr>
					</tfoot>
				</table>
				
				<div class="tablenav bottom">
					<div class="alignleft actions bulkactions">
						<select name="action2">
							<option value="-1"><?php _e('Bulk Actions', 'pricewise'); ?></option>
							<option value="bulk-delete"><?php _e('Delete', 'pricewise'); ?></option>
						</select>
						<input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'pricewise'); ?>">
					</div>
					
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php echo sprintf(
								_n('%s item', '%s items', $total_tests, 'pricewise'),
								number_format_i18n($total_tests)
							); ?>
						</span>
						<?php if ($page_links) : ?>
							<span class="pagination-links"><?php echo $page_links; ?></span>
						<?php endif; ?>
					</div>
				</div>
			</form>
		</div>
        <?php
    }
}