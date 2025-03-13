<?php
/**
 * API Admin Page View Class
 * Handles the UI rendering for the API admin page.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API admin page view functionality
 */
class Pricewise_API_Admin_View {
    
    /**
     * Advanced config instance
     *
     * @var Pricewise_API_Advanced
     */
    private $advanced;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once plugin_dir_path( __FILE__ ) . 'class-pw-advanced.php';
        $this->advanced = new Pricewise_API_Advanced();
    }
    
    /**
     * Render the API form
     * 
     * @param array $api The API data
     * @param bool $is_edit Whether this is an edit form
     */
    public function render_api_form( $api, $is_edit = false ) {
        // Check for success message from transient
        $save_success = get_transient('pricewise_api_save_success');
        if ($save_success) {
            delete_transient('pricewise_api_save_success');
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>API configuration saved successfully.</p>';
            echo '</div>';
        }
        
        // Check for activation toggle message
        if (isset($_GET['activation_toggled']) && $_GET['activation_toggled'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>API activation state changed successfully.</p>';
            echo '</div>';
        }
        
        // Display the form
        ?>
        <div class="card">
            <h2><?php echo $is_edit ? 'Edit API' : 'Add New API'; ?></h2>
            
            <?php if ($is_edit): ?>
                <?php 
                // Only show activation toggle for existing APIs
                $is_active = isset($api['active']) ? (bool)$api['active'] : true;
                $toggle_url = wp_nonce_url(
                    admin_url('admin.php?page=pricewise-manual-api&action=toggle_activation&api=' . $api['id_name']),
                    'toggle_api_' . $api['id_name']
                );
                ?>
                <div class="api-toggle-container" style="margin-bottom: 20px;">
                    <span class="api-toggle-label">Activate/Deactivate API Account</span>
                    <label class="pricewise-toggle-switch">
                        <input type="checkbox" class="api-toggle-checkbox" <?php checked($is_active); ?> 
                               onclick="window.location.href='<?php echo esc_url($toggle_url); ?>'">
                        <span class="pricewise-toggle-slider"></span>
                    </label>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field( 'pricewise_save_manual_api', 'pricewise_manual_api_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_name">API Name</label></th>
                        <td>
                            <input type="text" id="api_name" name="api_name" value="<?php echo esc_attr( $api['name'] ); ?>" class="regular-text" required>
                            <p class="description">Display name for this API configuration</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_id_name">API ID</label></th>
                        <td>
                            <input type="text" id="api_id_name" name="api_id_name" value="<?php echo esc_attr( $api['id_name'] ); ?>" class="regular-text">
                            <p class="description">Unique identifier for this API (letters, numbers, underscores only)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_key">API Key</label></th>
                        <td>
                            <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr( $api['api_key'] ); ?>" class="regular-text" autocomplete="off">
                            <p class="description">Your API Key</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_base_endpoint">Base endpoint url</label></th>
                        <td>
                            <input type="url" id="api_base_endpoint" name="api_base_endpoint" value="<?php echo esc_attr( isset( $api['base_endpoint'] ) ? $api['base_endpoint'] : '' ); ?>" class="regular-text">
                            <p class="description">The API's base URL that you are connecting to</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="pricewise_save_manual_api" class="button button-primary" value="Save API Configuration">
                    
                    <?php if ($is_edit && isset($api['active']) && $api['active']): ?>
                    <input type="submit" name="pricewise_test_api_from_form" class="button" value="Test API">
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api' ) ); ?>" class="button">Return to API List</a>
                </p>
            </form>
        </div>
        
        <?php if ($is_edit): ?>
            <?php $this->render_endpoints_section($api); ?>
        <?php endif; ?>
        
        <?php
        // Add action for after form content
        do_action('pricewise_after_api_form', $api, $is_edit);
    }
    
    /**
     * Render the endpoints section
     * 
     * @param array $api The API data
     */
    private function render_endpoints_section($api) {
        // Get all endpoints for this API
        $endpoints = Pricewise_API_Settings::get_endpoints($api['id_name']);
        
        // Check for endpoint messages
        $endpoint_save_success = get_transient('pricewise_endpoint_save_success');
        if ($endpoint_save_success) {
            delete_transient('pricewise_endpoint_save_success');
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint configuration saved successfully.</p>';
            echo '</div>';
        }
        
        if (isset($_GET['duplicated']) && $_GET['duplicated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint duplicated successfully.</p>';
            echo '</div>';
        }
        
        if (isset($_GET['deleted_endpoint']) && $_GET['deleted_endpoint'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint deleted successfully.</p>';
            echo '</div>';
        }
        
        if (isset($_GET['endpoint_activation_toggled']) && $_GET['endpoint_activation_toggled'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint activation state changed successfully.</p>';
            echo '</div>';
        }
        ?>
        <div class="card" style="margin-top: 20px;">
            <h2>API Endpoints</h2>
            <p>Configure endpoints for this API. Each endpoint can have its own configuration.</p>
            
            <?php if (empty($endpoints)): ?>
                <p>No endpoints configured yet. <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-manual-api&action=endpoint&api=' . $api['id_name'] . '&endpoint=default&_wpnonce=' . wp_create_nonce('edit_endpoint_default'))); ?>" class="button">Add First Endpoint</a></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Path</th>
                            <th>Method</th>
                            <th>Format</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($endpoints as $endpoint_id => $endpoint): 
                            // Get API account activation state
                            $api_active = isset($api['active']) ? (bool)$api['active'] : true;
                            
                            // Get endpoint activation state
                            $endpoint_active = isset($endpoint['active']) ? (bool)$endpoint['active'] : true;
                            
                            // If API is inactive, endpoints should appear inactive regardless of their stored state
                            $display_as_inactive = !$api_active || !$endpoint_active;
                            $row_class = $display_as_inactive ? 'inactive-endpoint-row' : '';
                        ?>
                            <tr class="<?php echo esc_attr($row_class); ?>" style="<?php echo $display_as_inactive ? 'opacity: 0.7;' : ''; ?>">
                                <td>
                                    <?php echo esc_html($endpoint['name']); ?>
                                    <?php if ($display_as_inactive): ?>
                                        <span style="color: #999; font-style: italic;"> - Inactive</span>
                                        <?php if (!$api_active): ?>
                                            <span style="color: #999; font-size: 11px; display: block;">(API account is inactive)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($endpoint['path']); ?></td>
                                <td>
                                    <?php 
                                    if (isset($endpoint['config']) && isset($endpoint['config']['method'])) {
                                        echo esc_html($endpoint['config']['method']);
                                    } else {
                                        echo 'GET';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($endpoint['config']) && isset($endpoint['config']['response_format'])) {
                                        echo esc_html(strtoupper($endpoint['config']['response_format']));
                                    } else {
                                        echo 'JSON';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $toggle_url = wp_nonce_url(
                                        admin_url('admin.php?page=pricewise-manual-api&action=toggle_endpoint_activation&api=' . $api['id_name'] . '&endpoint=' . $endpoint_id),
                                        'toggle_endpoint_' . $endpoint_id
                                    );
                                    ?>
                                    <div class="api-toggle-container" style="display: inline-block; vertical-align: middle;">
                                        <label class="pricewise-toggle-switch" style="margin-right: 0;" <?php echo !$api_active ? 'title="Endpoint cannot be activated while API account is inactive"' : ''; ?>>
                                            <input type="checkbox" class="api-toggle-checkbox" 
                                                <?php checked($endpoint_active); ?> 
                                                <?php disabled(!$api_active); ?> 
                                                onclick="<?php echo $api_active ? "window.location.href='" . esc_url($toggle_url) . "'" : "alert('You cannot activate endpoints while the API account is inactive. Please activate the API account first.')"; ?>">
                                            <span class="pricewise-toggle-slider" style="<?php echo !$api_active ? 'opacity: 0.5;' : ''; ?>"></span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-manual-api&action=endpoint&api=' . $api['id_name'] . '&endpoint=' . $endpoint_id . '&_wpnonce=' . wp_create_nonce('edit_endpoint_' . $endpoint_id))); ?>" class="button button-small">Edit</a>
                                    
                                    <?php if (count($endpoints) > 1): ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pricewise-manual-api&action=delete_endpoint&api=' . $api['id_name'] . '&endpoint=' . $endpoint_id), 'delete_endpoint_' . $endpoint_id)); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this endpoint?');">Delete</a>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=pricewise-manual-api&action=duplicate_endpoint&api=' . $api['id_name'] . '&endpoint=' . $endpoint_id), 'duplicate_endpoint_' . $endpoint_id)); ?>" class="button button-small">Duplicate</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 10px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-manual-api&action=endpoint&api=' . $api['id_name'] . '&endpoint=new&_wpnonce=' . wp_create_nonce('edit_endpoint_new'))); ?>" class="button">Add New Endpoint</a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render the endpoint form
     * 
     * @param string $api_id The API ID
     * @param string $endpoint_id The endpoint ID
     * @param array $endpoint The endpoint data
     */
    public function render_endpoint_form($api_id, $endpoint_id, $endpoint) {
        // Get API data
        $api = Pricewise_API_Settings::get_api($api_id);
        
        if (!$api) {
            wp_die('API not found');
        }
        
        // Check for test results
        $test_results = get_transient('pricewise_endpoint_test_results');
        if ($test_results) {
            delete_transient('pricewise_endpoint_test_results');
        }
        
        // Check for success messages
        if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint configuration saved successfully.</p>';
            echo '</div>';
        }
        
        // Check for endpoint activation toggle message
        if (isset($_GET['endpoint_activation_toggled']) && $_GET['endpoint_activation_toggled'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint activation state changed successfully.</p>';
            echo '</div>';
        }
        
        $is_new = ($endpoint_id === 'new');
        $is_edit = !$is_new;
        
        // Generate a unique ID for new endpoints
        if ($is_new) {
            $endpoint_id = 'endpoint_' . time();
        }
        
        // Apply defaults for new endpoints
        if ($is_new && empty($endpoint)) {
            $endpoint = array(
                'name' => '',
                'path' => '',
                'active' => true,
                'config' => array(
                    'method' => 'GET',
                    'response_format' => 'json',
                    'request_timeout' => 30,
                    'cache_duration' => 3600,
                    'auth' => array(
                        'type' => 'api_key',
                        'headers' => array(
                            array('name' => 'X-API-Key', 'value' => '', 'save' => false)
                        ),
                        'disable_headers' => false
                    ),
                    'body' => array(
                        'enabled' => false,
                        'type' => 'json',
                        'content' => ''
                    ),
                    'params' => array(
                        array('name' => 'currency', 'value' => 'USD', 'save' => false),
                        array('name' => 'market', 'value' => 'en-US', 'save' => false)
                    ),
                    'response_headers' => array(
                        array('name' => 'x-ratelimit-requests-limit', 'save' => false),
                        array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
                    )
                )
            );
        }
        
        // Get endpoint activation state
        $endpoint_active = isset($endpoint['active']) ? (bool)$endpoint['active'] : true;
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo $is_edit ? esc_html('Edit Endpoint: ' . $endpoint['name']) : 'Add New Endpoint'; ?>
            </h1>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-manual-api&edit=' . $api_id)); ?>" class="page-title-action">Back to API</a>
            
            <hr class="wp-header-end">
            
            <div class="card">
                <h2><?php echo $is_edit ? 'Edit Endpoint' : 'Add New Endpoint'; ?> for <?php echo esc_html($api['name']); ?></h2>
                
                <?php if ($is_edit): ?>
                <?php 
                    // Check if API is active
                    $api_active = isset($api['active']) ? (bool)$api['active'] : true;
                    
                    $toggle_url = wp_nonce_url(
                        admin_url('admin.php?page=pricewise-manual-api&action=toggle_endpoint_activation&api=' . $api_id . '&endpoint=' . $endpoint_id),
                        'toggle_endpoint_' . $endpoint_id
                    );
                ?>
                <div class="api-toggle-container" style="margin-bottom: 20px;">
                    <span class="api-toggle-label">Activate/Deactivate Endpoint</span>
                    <label class="pricewise-toggle-switch" <?php echo !$api_active ? 'title="Endpoint cannot be activated while API account is inactive"' : ''; ?>>
                        <input type="checkbox" class="api-toggle-checkbox" 
                               <?php checked($endpoint_active); ?> 
                               <?php disabled(!$api_active); ?> 
                               onclick="<?php echo $api_active ? "window.location.href='" . esc_url($toggle_url) . "'" : "alert('You cannot activate endpoints while the API account is inactive. Please activate the API account first.')"; ?>">
                        <span class="pricewise-toggle-slider" style="<?php echo !$api_active ? 'opacity: 0.5;' : ''; ?>"></span>
                    </label>
                    <?php if (!$api_active): ?>
                    <p class="description" style="color: #d63638; margin-top: 5px;">API account is inactive. Activate the API account to enable this endpoint.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('pricewise_save_endpoint', 'pricewise_endpoint_nonce'); ?>
                    <input type="hidden" name="api_id" value="<?php echo esc_attr($api_id); ?>">
                    <input type="hidden" name="old_endpoint_id" value="<?php echo esc_attr($endpoint_id); ?>">
                    <input type="hidden" name="endpoint_active" value="<?php echo $endpoint_active ? '1' : '0'; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="endpoint_name">Endpoint Name</label></th>
                            <td>
                                <input type="text" id="endpoint_name" name="endpoint_name" value="<?php echo esc_attr($endpoint['name']); ?>" class="regular-text" required>
                                <p class="description">Display name for this endpoint</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="endpoint_id">Endpoint ID</label></th>
                            <td>
                                <input type="text" id="endpoint_id" name="endpoint_id" value="<?php echo esc_attr($endpoint_id); ?>" class="regular-text">
                                <p class="description">Unique identifier for this endpoint (letters, numbers, underscores only)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="endpoint_path">Endpoint Path</label></th>
                            <td>
                                <input type="text" id="endpoint_path" name="endpoint_path" value="<?php echo esc_attr($endpoint['path']); ?>" class="regular-text" required>
                                <p class="description">Path that will be appended to the base URL (e.g., /api/search)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php 
                    // Render advanced configuration
                    $config_data = array(
                        'advanced_config' => $endpoint['config']
                    );
                    echo $this->advanced->render_advanced_config($config_data);
                    ?>
                    
                    <p class="submit">
                        <input type="submit" name="pricewise_save_endpoint" class="button button-primary" value="Save Endpoint">
                        <?php if (isset($api['active']) && $api['active'] && $endpoint_active): ?>
                        <input type="submit" name="pricewise_test_endpoint" class="button" value="Test Endpoint">
                        <?php endif; ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=pricewise-manual-api&edit=' . $api_id)); ?>" class="button">Cancel</a>
                    </p>
                </form>
            </div>
            
            <?php if ($test_results): ?>
                <?php $this->render_test_results($test_results); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render the test results
     * 
     * @param string $test_results The test results HTML
     */
    public function render_test_results( $test_results ) {
        ?>
        <div class="card" style="margin-top: 20px;">
            <h2>API Test Results</h2>
            <div style="margin-top: 10px; padding: 10px; background: #f9f9f9;">
                <?php echo wp_kses_post( $test_results ); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the API list
     * 
     * @param array $apis Array of APIs
     */
    public function render_api_list( $apis ) {
        // Check for activation toggle message
        if (isset($_GET['activation_toggled']) && $_GET['activation_toggled'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>API activation state changed successfully.</p>';
            echo '</div>';
        }
        
        // Check for endpoint activation toggle message
        if (isset($_GET['endpoint_activation_toggled']) && $_GET['endpoint_activation_toggled'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Endpoint activation state changed successfully.</p>';
            echo '</div>';
        }
        
        // Check for API duplication message
        if (isset($_GET['duplicated_account']) && $_GET['duplicated_account'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>API account duplicated successfully.</p>';
            echo '</div>';
        }
        ?>
        <div class="card">
            <h2>Manual API Settings</h2>
            <p>Configure your manual API settings for connecting to external services.</p>
            
            <?php if ( empty( $apis ) ): ?>
                <p>No manual APIs configured yet. <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=new' ) ); ?>">Add your first API</a>.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID</th>
                            <th>Base URL</th>
                            <th>Endpoints</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $apis as $api_id => $api ): ?>
                            <?php 
                            // Get activation status
                            $is_active = isset($api['active']) ? (bool)$api['active'] : true;
                            $row_class = $is_active ? '' : 'inactive-api-row';
                            ?>
                            <tr class="<?php echo esc_attr($row_class); ?>" style="<?php echo !$is_active ? 'opacity: 0.7;' : ''; ?>">
                                <td>
                                    <?php echo esc_html( $api['name'] ); ?>
                                    <?php if (!$is_active): ?>
                                        <span style="color: #999; font-style: italic;"> - Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $api['id_name'] ); ?></td>
                                <td><?php echo esc_html( isset( $api['base_endpoint'] ) ? $api['base_endpoint'] : '' ); ?></td>
                                <td>
                                    <?php 
                                    $endpoint_count = 0;
                                    if (isset($api['endpoints'])) {
                                        $endpoint_count = count($api['endpoints']);
                                    } elseif (isset($api['advanced_config'])) {
                                        $endpoint_count = 1; // Legacy format with one endpoint
                                    }
                                    echo esc_html($endpoint_count);
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ( ! empty( $api['api_key'] ) && ! empty( $api['base_endpoint'] ) ) {
                                        echo '<span style="color: green;">✓ Configured</span>';
                                    } else {
                                        echo '<span style="color: red;">✗ Incomplete</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    // Generate activation toggle URL
                                    $toggle_url = wp_nonce_url(
                                        admin_url('admin.php?page=pricewise-manual-api&action=toggle_activation&api=' . $api_id),
                                        'toggle_api_' . $api_id
                                    );
                                    ?>
                                    <div class="api-toggle-container" style="display: inline-block; vertical-align: middle; margin-right: 10px;">
                                        <span class="api-toggle-label">Activate/Deactivate API Account</span>
                                        <label class="pricewise-toggle-switch">
                                            <input type="checkbox" class="api-toggle-checkbox" <?php checked($is_active); ?> 
                                                   onclick="window.location.href='<?php echo esc_url($toggle_url); ?>'">
                                            <span class="pricewise-toggle-slider"></span>
                                        </label>
                                    </div>
                                    
                                    <?php if ($is_active): ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=test&api=' . $api_id . '&_wpnonce=' . wp_create_nonce( 'test_api_' . $api_id ) ) ); ?>" class="button button-small">Test API</a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&edit=' . $api_id ) ); ?>" class="button button-small">Edit</a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=delete&api=' . $api_id . '&_wpnonce=' . wp_create_nonce( 'delete_api_' . $api_id ) ) ); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this API configuration?');">Delete</a>
                                    
                                    <?php if (function_exists('pricewise_clear_api_cache')): ?>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=clear_cache&api=' . $api_id . '&_wpnonce=' . wp_create_nonce( 'clear_cache_' . $api_id ) ) ); ?>" class="button button-small" onclick="return confirm('Are you sure you want to clear the cache for this API?');">Clear Cache</a>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=duplicate_account&api=' . $api_id . '&_wpnonce=' . wp_create_nonce( 'duplicate_account_' . $api_id ) ) ); ?>" class="button button-small">Duplicate Account</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php
        // Add action for after API list content
        do_action('pricewise_after_api_list', $apis);
    }
    
    /**
     * Render the page header section
     * 
     * @param array $page_data Page data including action and related parameters
     */
    public function render_page_header($page_data) {
        $show_add_new = empty($page_data['action']) || $page_data['action'] === 'test' || $page_data['action'] === 'toggle_activation' || $page_data['action'] === 'duplicate_account';
        ?>
        <h1 class="wp-heading-inline">PW Manual Api Account configuration</h1>
        
        <?php if ($show_add_new): ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pricewise-manual-api&action=new' ) ); ?>" class="page-title-action">Add New API</a>
        <?php endif; ?>
        
        <hr class="wp-header-end">
        
        <?php settings_errors( 'pricewise_manual_api' ); ?>
        
        <?php if ( isset( $_GET['updated'] ) ): ?>
            <div class="notice notice-success is-dismissible">
                <p>API configuration saved successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if ( isset( $_GET['deleted'] ) ): ?>
            <div class="notice notice-success is-dismissible">
                <p>API configuration deleted successfully.</p>
            </div>
        <?php endif; ?>
        
        <?php if ( isset( $_GET['cache_cleared'] ) ): ?>
            <div class="notice notice-success is-dismissible">
                <p>API cache cleared successfully.</p>
            </div>
        <?php endif; ?>
        <?php
    }
}