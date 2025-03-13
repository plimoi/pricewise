<?php
// admin/admin-dashboard.php - Main admin dashboard for PriceWise plugin

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add PriceWise main menu
 */
function pricewise_add_admin_main_menu() {
    add_menu_page(
        'PriceWise',                // Page title
        'PriceWise',                // Menu title
        'manage_options',           // Capability
        'pricewise',                // Menu slug
        'pricewise_main_admin_page',// Callback function
        'dashicons-admin-generic',  // Icon
        25                          // Position
    );
    
    // Add Cache Management subpage
    add_submenu_page(
        'pricewise',                  // Parent slug
        'Cache Management',           // Page title
        'Cache',                      // Menu title
        'manage_options',             // Capability
        'pricewise-cache',            // Menu slug
        'pricewise_cache_admin_page'  // Callback function
    );
}
add_action( 'admin_menu', 'pricewise_add_admin_main_menu' );

/**
 * Display the main admin dashboard page
 */
function pricewise_main_admin_page() {
    // Check if cache system exists and get stats
    $has_new_cache = function_exists('pricewise_cache_get_stats');
    $cache_stats = $has_new_cache ? pricewise_cache_get_stats(true) : array();
    
    // Get legacy cache stats
    global $wpdb;
    $legacy_table_name = $wpdb->prefix . 'pricewise_cache';
    $legacy_total_cache = $wpdb->get_var("SELECT COUNT(*) FROM $legacy_table_name");
    $legacy_active_cache = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $legacy_table_name WHERE expiration > %s", 
        current_time('mysql')
    ));
    ?>
    <div class="wrap">
        <h1>PriceWise Dashboard</h1>

        <div class="card">
            <h2>Welcome to PriceWise</h2>
            <p>PriceWise is a powerful plugin for managing and connecting to external APIs.</p>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>Cache Statistics</h2>
            
            <?php if ($has_new_cache): ?>
                <table class="widefat striped" style="width: auto; min-width: 50%;">
                    <tr>
                        <th>Total Entries</th>
                        <td><?php echo isset($cache_stats['total_entries']) ? esc_html($cache_stats['total_entries']) : '0'; ?></td>
                    </tr>
                    <tr>
                        <th>Cache Hit Ratio</th>
                        <td><?php echo isset($cache_stats['hit_ratio']) ? esc_html($cache_stats['hit_ratio']) : '0%'; ?></td>
                    </tr>
                    <tr>
                        <th>Total Hits</th>
                        <td><?php echo isset($cache_stats['hits']) ? esc_html($cache_stats['hits']) : '0'; ?></td>
                    </tr>
                    <tr>
                        <th>Total Misses</th>
                        <td><?php echo isset($cache_stats['misses']) ? esc_html($cache_stats['misses']) : '0'; ?></td>
                    </tr>
                    <tr>
                        <th>Last Cleanup</th>
                        <td><?php echo isset($cache_stats['last_cleanup']) ? esc_html($cache_stats['last_cleanup']) : 'Never'; ?></td>
                    </tr>
                </table>
                
                <p class="description">
                    <a href="<?php echo admin_url('admin.php?page=pricewise-cache'); ?>" class="button">Manage Cache</a>
                </p>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>Getting Started</h2>
            <p>Follow these steps to set up PriceWise on your website:</p>
            <ol>
                <li>Configure your API settings under <a href="<?php echo admin_url('admin.php?page=pricewise-manual-api'); ?>">PriceWise > Manual API</a></li>
                <li>Test your API configurations to ensure they're working correctly</li>
                <li>Manage your cache settings under <a href="<?php echo admin_url('admin.php?page=pricewise-cache'); ?>">PriceWise > Cache</a></li>
            </ol>
        </div>
    </div>
    <?php
}

/**
 * Display the cache management admin page
 */
function pricewise_cache_admin_page() {
    // Handle cache operations
    if (isset($_POST['pricewise_cache_action']) && check_admin_referer('pricewise_cache_management')) {
        $action = sanitize_text_field($_POST['pricewise_cache_action']);
        
        switch ($action) {
            case 'flush':
                pricewise_cache_flush();
                add_settings_error(
                    'pricewise_cache',
                    'cache_flushed',
                    'Cache has been successfully flushed.',
                    'updated'
                );
                break;
                
            case 'cleanup':
                $deleted = pricewise_cache_maintenance();
                add_settings_error(
                    'pricewise_cache',
                    'cache_cleaned',
                    'Cache maintenance completed successfully.',
                    'updated'
                );
                break;
                
            case 'delete_group':
                if (isset($_POST['cache_group'])) {
                    $group = sanitize_text_field($_POST['cache_group']);
                    $count = pricewise_cache_delete_group($group);
                    add_settings_error(
                        'pricewise_cache',
                        'group_deleted',
                        sprintf('Successfully deleted %d items from the "%s" cache group.', $count, $group),
                        'updated'
                    );
                }
                break;
        }
    }
    
    // Get cache statistics
    $cache_stats = pricewise_cache_get_stats(true);
    $health_info = pricewise_cache_check_health();
    ?>
    <div class="wrap">
        <h1>PriceWise Cache Management</h1>
        
        <?php settings_errors('pricewise_cache'); ?>
        
        <div class="card">
            <h2>Cache Status</h2>
            
            <p>
                Cache status: 
                <?php if ($health_info['status'] === 'healthy'): ?>
                    <span style="color: green;">✓ Healthy</span>
                <?php elseif ($health_info['status'] === 'warning'): ?>
                    <span style="color: orange;">⚠ Needs attention</span>
                <?php else: ?>
                    <span style="color: red;">✗ Error</span>
                <?php endif; ?>
            </p>
            
            <?php if (!empty($health_info['messages'])): ?>
                <ul class="ul-disc">
                    <?php foreach ($health_info['messages'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <p>
                <strong>Object Cache:</strong> 
                <?php echo $health_info['object_cache'] ? 'Enabled' : 'Disabled'; ?>
            </p>
            
            <table class="widefat striped" style="width: auto; min-width: 50%;">
                <tr>
                    <th>Total Entries</th>
                    <td><?php echo isset($cache_stats['total_entries']) ? esc_html($cache_stats['total_entries']) : '0'; ?></td>
                </tr>
                <tr>
                    <th>Cache Hit Ratio</th>
                    <td><?php echo isset($cache_stats['hit_ratio']) ? esc_html($cache_stats['hit_ratio']) : '0%'; ?></td>
                </tr>
                <tr>
                    <th>Total Hits</th>
                    <td><?php echo isset($cache_stats['hits']) ? esc_html($cache_stats['hits']) : '0'; ?></td>
                </tr>
                <tr>
                    <th>Total Misses</th>
                    <td><?php echo isset($cache_stats['misses']) ? esc_html($cache_stats['misses']) : '0'; ?></td>
                </tr>
                <tr>
                    <th>Last Cleanup</th>
                    <td><?php echo isset($cache_stats['last_cleanup']) ? esc_html($cache_stats['last_cleanup']) : 'Never'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Cache Maintenance</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_cache_management'); ?>
                
                <p>
                    <input type="hidden" name="pricewise_cache_action" value="cleanup">
                    <input type="submit" class="button button-secondary" value="Run Cache Maintenance">
                    <span class="description">This will clean up expired cache entries and optimize the cache.</span>
                </p>
            </form>
            
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_cache_management'); ?>
                
                <p>
                    <input type="hidden" name="pricewise_cache_action" value="flush">
                    <input type="submit" class="button button-secondary" value="Flush All Cache" onclick="return confirm('Are you sure you want to flush the entire cache? This will delete all cached data.');">
                    <span class="description">This will delete all cached data. Use with caution.</span>
                </p>
            </form>
        </div>
        
        <?php if (!empty($cache_stats['groups'])): ?>
        <div class="card" style="margin-top: 20px;">
            <h2>Cache Groups</h2>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Entries</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cache_stats['groups'] as $group => $count): ?>
                    <tr>
                        <td><?php echo esc_html($group); ?></td>
                        <td><?php echo esc_html(number_format($count)); ?></td>
                        <td>
                            <form method="post" action="" style="display: inline;">
                                <?php wp_nonce_field('pricewise_cache_management'); ?>
                                <input type="hidden" name="pricewise_cache_action" value="delete_group">
                                <input type="hidden" name="cache_group" value="<?php echo esc_attr($group); ?>">
                                <input type="submit" class="button button-small" value="Delete Group" onclick="return confirm('Are you sure you want to delete this cache group?');">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}