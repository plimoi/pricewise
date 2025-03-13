<?php
/**
 * Test History Stats Renderer Class
 * Handles rendering of statistics and filters for the test history
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Stats_Renderer extends Pricewise_Test_History_Base_Renderer {
    /**
     * Detail renderer
     *
     * @var Pricewise_Test_History_Detail_Renderer
     */
    private $detail_renderer;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     * @param Pricewise_Test_History_Detail_Renderer $detail_renderer The detail renderer
     */
    public function __construct($db, $detail_renderer) {
        parent::__construct($db);
        $this->detail_renderer = $detail_renderer;
    }

    /**
     * Render statistics section
     *
     * @param array $stats Statistics
     */
    public function render_stats($stats) {
        $success_rate = ($stats['total_tests'] > 0) ? round(($stats['successful'] / $stats['total_tests']) * 100, 1) : 0;
        ?>
        <div class="pricewise-history-stats">
            <div class="stat-box">
                <h3><?php _e('Total Tests', 'pricewise'); ?></h3>
                <div class="stat-value"><?php echo esc_html(number_format_i18n($stats['total_tests'])); ?></div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Success Rate', 'pricewise'); ?></h3>
                <div class="stat-value"><?php echo esc_html($success_rate); ?>%</div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Successful Tests', 'pricewise'); ?></h3>
                <div class="stat-value status-success"><?php echo esc_html(number_format_i18n($stats['successful'])); ?></div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Failed Tests', 'pricewise'); ?></h3>
                <div class="stat-value status-error"><?php echo esc_html(number_format_i18n($stats['failed'])); ?></div>
            </div>
            <div class="stat-box">
                <h3><?php _e('Avg Response Time', 'pricewise'); ?></h3>
                <div class="stat-value"><?php echo esc_html(round($stats['avg_response_time'], 2)); ?> s</div>
            </div>
        </div>
        <?php
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
        ?>
        <div class="pricewise-history-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="pricewise-test-history">
                
                <div class="form-row">
                    <label for="api_id"><?php _e('API:', 'pricewise'); ?></label>
                    <select name="api_id" id="api_id">
                        <option value=""><?php _e('All APIs', 'pricewise'); ?></option>
                        <?php foreach ($api_list as $api) : ?>
                            <option value="<?php echo esc_attr($api['api_id']); ?>" <?php selected($api_id, $api['api_id']); ?>>
                                <?php echo esc_html($api['api_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="status_code"><?php _e('Status:', 'pricewise'); ?></label>
                    <select name="status_code" id="status_code">
                        <option value=""><?php _e('All Statuses', 'pricewise'); ?></option>
                        <option value="200" <?php selected($status_code, '200'); ?>>200 - Success</option>
                        <option value="400" <?php selected($status_code, '400'); ?>>400 - Bad Request</option>
                        <option value="401" <?php selected($status_code, '401'); ?>>401 - Unauthorized</option>
                        <option value="403" <?php selected($status_code, '403'); ?>>403 - Forbidden</option>
                        <option value="404" <?php selected($status_code, '404'); ?>>404 - Not Found</option>
                        <option value="500" <?php selected($status_code, '500'); ?>>500 - Server Error</option>
                        <option value="0" <?php selected($status_code, '0'); ?>>0 - Connection Error</option>
                    </select>
                    
                    <label for="per_page"><?php _e('Show:', 'pricewise'); ?></label>
                    <select name="per_page" id="per_page">
                        <option value="10" <?php selected($per_page, 10); ?>>10</option>
                        <option value="20" <?php selected($per_page, 20); ?>>20</option>
                        <option value="50" <?php selected($per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($per_page, 100); ?>>100</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <label for="date_from"><?php _e('Date From:', 'pricewise'); ?></label>
                    <input type="text" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>" class="datepicker" placeholder="YYYY-MM-DD">
                    
                    <label for="date_to"><?php _e('Date To:', 'pricewise'); ?></label>
                    <input type="text" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>" class="datepicker" placeholder="YYYY-MM-DD">
                    
                    <button type="submit" class="button"><?php _e('Filter', 'pricewise'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-test-history')); ?>" class="button"><?php _e('Reset', 'pricewise'); ?></a>
                </div>
            </form>
        </div>
        <?php
        
        // Add Data Screen Options button
        ?>
        <div class="pricewise-data-screen-options">
            <button type="button" id="pricewise-data-screen-options-toggle" class="button">
                <?php _e('Data Screen Options', 'pricewise'); ?>
            </button>
            <div class="pricewise-data-screen-options-panel">
                <?php $this->detail_renderer->render_manual_data_list_preferences(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render cleanup form
     */
    public function render_cleanup_form() {
        $this->detail_renderer->render_cleanup_form();
    }
}