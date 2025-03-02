<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Pricewise
 * @subpackage Pricewise/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area,
 * including the settings page, options, and admin-specific assets.
 *
 * @package    Pricewise
 * @subpackage Pricewise/admin
 * @author     Your Name <email@example.com>
 */
class Pricewise_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The API handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Pricewise_Hotels_API    $api    The API handler instance.
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string               $plugin_name    The name of this plugin.
     * @param    string               $version        The version of this plugin.
     * @param    Pricewise_Hotels_API $api            The API handler instance.
     */
    public function __construct($plugin_name, $version, $api) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api = $api;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/pricewise-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Add WordPress color picker
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/pricewise-admin.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'pricewise_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pricewise_admin_nonce'),
            'strings' => array(
                'cache_cleared' => __('Cache cleared successfully!', 'pricewise'),
                'cache_error' => __('Error clearing cache. Please try again.', 'pricewise'),
                'api_success' => __('API connection successful!', 'pricewise'),
                'api_error' => __('API connection failed. Please check your credentials.', 'pricewise'),
            )
        ));
    }

    /**
     * Add menu items to the admin dashboard.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Pricewise', 'pricewise'),
            __('Pricewise', 'pricewise'),
            'manage_options',
            'pricewise',
            array($this, 'display_settings_page'),
            'dashicons-money-alt',
            30
        );

        // Settings submenu
        add_submenu_page(
            'pricewise',
            __('Settings', 'pricewise'),
            __('Settings', 'pricewise'),
            'manage_options',
            'pricewise',
            array($this, 'display_settings_page')
        );

        // Statistics submenu
        add_submenu_page(
            'pricewise',
            __('Search Stats', 'pricewise'),
            __('Search Stats', 'pricewise'),
            'manage_options',
            'pricewise-stats',
            array($this, 'display_stats_page')
        );

        // Help submenu
        add_submenu_page(
            'pricewise',
            __('Help', 'pricewise'),
            __('Help', 'pricewise'),
            'manage_options',
            'pricewise-help',
            array($this, 'display_help_page')
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // API Settings
        register_setting(
            'pricewise_api_settings',
            'pricewise_rapidapi_key',
            array('sanitize_callback' => 'sanitize_text_field')
        );

        register_setting(
            'pricewise_api_settings',
            'pricewise_rapidapi_host',
            array('sanitize_callback' => 'sanitize_text_field')
        );

        // Default Search Settings
        register_setting(
            'pricewise_search_settings',
            'pricewise_default_location',
            array('sanitize_callback' => 'sanitize_text_field')
        );

        register_setting(
            'pricewise_search_settings',
            'pricewise_default_adults',
            array('sanitize_callback' => 'intval')
        );

        register_setting(
            'pricewise_search_settings',
            'pricewise_default_children',
            array('sanitize_callback' => 'intval')
        );

        register_setting(
            'pricewise_search_settings',
            'pricewise_default_rooms',
            array('sanitize_callback' => 'intval')
        );

        // Performance Settings
        register_setting(
            'pricewise_performance_settings',
            'pricewise_cache_expiry',
            array('sanitize_callback' => 'intval')
        );

        register_setting(
            'pricewise_performance_settings',
            'pricewise_rate_limit_searches',
            array('sanitize_callback' => 'intval')
        );

        // API Settings Section
        add_settings_section(
            'pricewise_api_settings_section',
            __('API Credentials', 'pricewise'),
            array($this, 'api_settings_section_callback'),
            'pricewise_api_settings'
        );

        add_settings_field(
            'pricewise_rapidapi_key',
            __('RapidAPI Key', 'pricewise'),
            array($this, 'rapidapi_key_callback'),
            'pricewise_api_settings',
            'pricewise_api_settings_section'
        );

        add_settings_field(
            'pricewise_rapidapi_host',
            __('RapidAPI Host', 'pricewise'),
            array($this, 'rapidapi_host_callback'),
            'pricewise_api_settings',
            'pricewise_api_settings_section'
        );

        // Default Search Settings Section
        add_settings_section(
            'pricewise_search_settings_section',
            __('Default Search Parameters', 'pricewise'),
            array($this, 'search_settings_section_callback'),
            'pricewise_search_settings'
        );

        add_settings_field(
            'pricewise_default_location',
            __('Default Location', 'pricewise'),
            array($this, 'default_location_callback'),
            'pricewise_search_settings',
            'pricewise_search_settings_section'
        );

        add_settings_field(
            'pricewise_default_adults',
            __('Default Number of Adults', 'pricewise'),
            array($this, 'default_adults_callback'),
            'pricewise_search_settings',
            'pricewise_search_settings_section'
        );

        add_settings_field(
            'pricewise_default_children',
            __('Default Number of Children', 'pricewise'),
            array($this, 'default_children_callback'),
            'pricewise_search_settings',
            'pricewise_search_settings_section'
        );

        add_settings_field(
            'pricewise_default_rooms',
            __('Default Number of Rooms', 'pricewise'),
            array($this, 'default_rooms_callback'),
            'pricewise_search_settings',
            'pricewise_search_settings_section'
        );

        // Performance Settings Section
        add_settings_section(
            'pricewise_performance_settings_section',
            __('Performance Settings', 'pricewise'),
            array($this, 'performance_settings_section_callback'),
            'pricewise_performance_settings'
        );

        add_settings_field(
            'pricewise_cache_expiry',
            __('Cache Expiry (seconds)', 'pricewise'),
            array($this, 'cache_expiry_callback'),
            'pricewise_performance_settings',
            'pricewise_performance_settings_section'
        );

        add_settings_field(
            'pricewise_rate_limit_searches',
            __('Search Rate Limit (per hour)', 'pricewise'),
            array($this, 'rate_limit_callback'),
            'pricewise_performance_settings',
            'pricewise_performance_settings_section'
        );
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get the active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api';

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=pricewise&tab=api" class="nav-tab <?php echo $active_tab == 'api' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('API Settings', 'pricewise'); ?>
                </a>
                <a href="?page=pricewise&tab=search" class="nav-tab <?php echo $active_tab == 'search' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Search Settings', 'pricewise'); ?>
                </a>
                <a href="?page=pricewise&tab=performance" class="nav-tab <?php echo $active_tab == 'performance' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Performance', 'pricewise'); ?>
                </a>
                <a href="?page=pricewise&tab=shortcodes" class="nav-tab <?php echo $active_tab == 'shortcodes' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Shortcodes', 'pricewise'); ?>
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                if ($active_tab == 'api') {
                    settings_fields('pricewise_api_settings');
                    do_settings_sections('pricewise_api_settings');
                    
                    // Add API test button
                    ?>
                    <div class="pricewise-api-test">
                        <button type="button" id="pricewise-test-api" class="button button-secondary">
                            <?php _e('Test API Connection', 'pricewise'); ?>
                        </button>
                        <span id="pricewise-api-test-result"></span>
                    </div>
                    <?php
                } 
                elseif ($active_tab == 'search') {
                    settings_fields('pricewise_search_settings');
                    do_settings_sections('pricewise_search_settings');
                } 
                elseif ($active_tab == 'performance') {
                    settings_fields('pricewise_performance_settings');
                    do_settings_sections('pricewise_performance_settings');
                    
                    // Add cache clear button
                    ?>
                    <div class="pricewise-cache-clear">
                        <button type="button" id="pricewise-clear-cache" class="button button-secondary">
                            <?php _e('Clear Cache', 'pricewise'); ?>
                        </button>
                        <span id="pricewise-cache-clear-result"></span>
                    </div>
                    <?php
                }
                elseif ($active_tab == 'shortcodes') {
                    $this->display_shortcodes_info();
                }
                
                if ($active_tab != 'shortcodes') {
                    submit_button();
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display shortcodes information.
     *
     * @since    1.0.0
     */
    private function display_shortcodes_info() {
        ?>
        <div class="pricewise-shortcodes-info">
            <h2><?php _e('Available Shortcodes', 'pricewise'); ?></h2>
            
            <div class="pricewise-shortcode-box">
                <h3><?php _e('Hotel Search Form', 'pricewise'); ?></h3>
                <code>[pricewise_search_form]</code>
                <p><?php _e('Displays the hotel search form.', 'pricewise'); ?></p>
                <h4><?php _e('Parameters:', 'pricewise'); ?></h4>
                <ul>
                    <li><code>destination</code> - <?php _e('Default destination (optional)', 'pricewise'); ?></li>
                    <li><code>adults</code> - <?php _e('Default number of adults (optional)', 'pricewise'); ?></li>
                    <li><code>children</code> - <?php _e('Default number of children (optional)', 'pricewise'); ?></li>
                    <li><code>rooms</code> - <?php _e('Default number of rooms (optional)', 'pricewise'); ?></li>
                </ul>
                <h4><?php _e('Example:', 'pricewise'); ?></h4>
                <code>[pricewise_search_form destination="Paris" adults="2" rooms="1"]</code>
            </div>
            
            <div class="pricewise-shortcode-box">
                <h3><?php _e('Hotel Search Results', 'pricewise'); ?></h3>
                <code>[pricewise_search_results]</code>
                <p><?php _e('Displays hotel search results. This shortcode should be placed on the page where you want the results to appear. The search form will automatically redirect to this page.', 'pricewise'); ?></p>
                <h4><?php _e('Parameters:', 'pricewise'); ?></h4>
                <ul>
                    <li><code>per_page</code> - <?php _e('Number of results per page (default: 10)', 'pricewise'); ?></li>
                </ul>
                <h4><?php _e('Example:', 'pricewise'); ?></h4>
                <code>[pricewise_search_results per_page="15"]</code>
            </div>
            
            <div class="pricewise-shortcode-box">
                <h3><?php _e('Hotel Details', 'pricewise'); ?></h3>
                <code>[pricewise_hotel_details]</code>
                <p><?php _e('Displays detailed information for a specific hotel. The hotel ID is taken from the URL parameter.', 'pricewise'); ?></p>
                <h4><?php _e('Example:', 'pricewise'); ?></h4>
                <code>[pricewise_hotel_details]</code>
            </div>
        </div>
        <?php
    }

    /**
     * Display the statistics page.
     *
     * @since    1.0.0
     */
    public function display_stats_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Create an instance of the data manager
        $data_manager = new Pricewise_Data_Manager();
        $logs = $data_manager->get_search_logs(100);

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="pricewise-stats-overview">
                <h2><?php _e('Search Statistics', 'pricewise'); ?></h2>
                
                <?php if (empty($logs)) : ?>
                    <p><?php _e('No search data available yet.', 'pricewise'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date/Time', 'pricewise'); ?></th>
                                <th><?php _e('User', 'pricewise'); ?></th>
                                <th><?php _e('IP Address', 'pricewise'); ?></th>
                                <th><?php _e('Search Query', 'pricewise'); ?></th>
                                <th><?php _e('Parameters', 'pricewise'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log) : ?>
                                <tr>
                                    <td><?php echo esc_html($log['created']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($log['user_id'])) {
                                            $user = get_user_by('id', $log['user_id']);
                                            echo $user ? esc_html($user->user_login) : __('Unknown User', 'pricewise');
                                        } else {
                                            _e('Guest', 'pricewise');
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($log['user_ip']); ?></td>
                                    <td><?php echo esc_html($log['search_query']); ?></td>
                                    <td>
                                        <?php
                                        if (is_array($log['search_params'])) {
                                            echo '<ul>';
                                            foreach ($log['search_params'] as $key => $value) {
                                                if (is_array($value)) {
                                                    $value = implode(', ', $value);
                                                }
                                                echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo esc_html($log['search_params']);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display the help page.
     *
     * @since    1.0.0
     */
    public function display_help_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="pricewise-help">
                <h2><?php _e('Pricewise Plugin Help', 'pricewise'); ?></h2>
                
                <div class="pricewise-help-section">
                    <h3><?php _e('Getting Started', 'pricewise'); ?></h3>
                    <ol>
                        <li><?php _e('Go to the API Settings tab and enter your RapidAPI Key and Host.', 'pricewise'); ?></li>
                        <li><?php _e('Configure default search parameters under the Search Settings tab.', 'pricewise'); ?></li>
                        <li><?php _e('Add the [pricewise_search_form] shortcode to any page where you want the search form to appear.', 'pricewise'); ?></li>
                        <li><?php _e('Create a dedicated page for search results and add the [pricewise_search_results] shortcode to it.', 'pricewise'); ?></li>
                    </ol>
                </div>
                
                <div class="pricewise-help-section">
                    <h3><?php _e('Troubleshooting', 'pricewise'); ?></h3>
                    <h4><?php _e('API Connection Issues', 'pricewise'); ?></h4>
                    <ul>
                        <li><?php _e('Verify your RapidAPI key and host are correct.', 'pricewise'); ?></li>
                        <li><?php _e('Check if you have reached your API rate limits.', 'pricewise'); ?></li>
                        <li><?php _e('Use the "Test API Connection" button in the settings to verify connectivity.', 'pricewise'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Search Not Working', 'pricewise'); ?></h4>
                    <ul>
                        <li><?php _e('Ensure you have added both the search form and results shortcodes.', 'pricewise'); ?></li>
                        <li><?php _e('Check for JavaScript errors in your browser console.', 'pricewise'); ?></li>
                        <li><?php _e('Verify that your theme is not conflicting with the plugin styles.', 'pricewise'); ?></li>
                    </ul>
                    
                    <h4><?php _e('Performance Issues', 'pricewise'); ?></h4>
                    <ul>
                        <li><?php _e('Try increasing the cache expiry time.', 'pricewise'); ?></li>
                        <li><?php _e('Clear the cache if results seem outdated.', 'pricewise'); ?></li>
                        <li><?php _e('Adjust rate limiting settings to prevent API overuse.', 'pricewise'); ?></li>
                    </ul>
                </div>
                
                <div class="pricewise-help-section">
                    <h3><?php _e('Getting Support', 'pricewise'); ?></h3>
                    <p><?php _e('If you need further assistance, please contact support at:', 'pricewise'); ?></p>
                    <p><a href="mailto:support@example.com">support@example.com</a></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Callback for the API settings section.
     *
     * @since    1.0.0
     */
    public function api_settings_section_callback() {
        echo '<p>' . __('Enter your RapidAPI credentials for the SkyScanner API.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the RapidAPI key field.
     *
     * @since    1.0.0
     */
    public function rapidapi_key_callback() {
        $key = get_option('pricewise_rapidapi_key', '');
        echo '<input type="password" id="pricewise_rapidapi_key" name="pricewise_rapidapi_key" value="' . esc_attr($key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your RapidAPI key for accessing the SkyScanner API.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the RapidAPI host field.
     *
     * @since    1.0.0
     */
    public function rapidapi_host_callback() {
        $host = get_option('pricewise_rapidapi_host', 'sky-scanner3.p.rapidapi.com');
        echo '<input type="text" id="pricewise_rapidapi_host" name="pricewise_rapidapi_host" value="' . esc_attr($host) . '" class="regular-text" />';
        echo '<p class="description">' . __('The RapidAPI host for the SkyScanner API (default: sky-scanner3.p.rapidapi.com).', 'pricewise') . '</p>';
    }

    /**
     * Callback for the search settings section.
     *
     * @since    1.0.0
     */
    public function search_settings_section_callback() {
        echo '<p>' . __('Configure default search parameters for the search form.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the default location field.
     *
     * @since    1.0.0
     */
    public function default_location_callback() {
        $location = get_option('pricewise_default_location', 'Rome');
        echo '<input type="text" id="pricewise_default_location" name="pricewise_default_location" value="' . esc_attr($location) . '" class="regular-text" />';
        echo '<p class="description">' . __('The default location for hotel searches.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the default adults field.
     *
     * @since    1.0.0
     */
    public function default_adults_callback() {
        $adults = get_option('pricewise_default_adults', 1);
        echo '<input type="number" id="pricewise_default_adults" name="pricewise_default_adults" value="' . esc_attr($adults) . '" class="small-text" min="1" max="10" />';
        echo '<p class="description">' . __('The default number of adults for hotel searches.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the default children field.
     *
     * @since    1.0.0
     */
    public function default_children_callback() {
        $children = get_option('pricewise_default_children', 0);
        echo '<input type="number" id="pricewise_default_children" name="pricewise_default_children" value="' . esc_attr($children) . '" class="small-text" min="0" max="6" />';
        echo '<p class="description">' . __('The default number of children for hotel searches.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the default rooms field.
     *
     * @since    1.0.0
     */
    public function default_rooms_callback() {
        $rooms = get_option('pricewise_default_rooms', 1);
        echo '<input type="number" id="pricewise_default_rooms" name="pricewise_default_rooms" value="' . esc_attr($rooms) . '" class="small-text" min="1" max="8" />';
        echo '<p class="description">' . __('The default number of rooms for hotel searches.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the performance settings section.
     *
     * @since    1.0.0
     */
    public function performance_settings_section_callback() {
        echo '<p>' . __('Configure performance settings to optimize API usage and cache.', 'pricewise') . '</p>';
    }

    /**
     * Callback for the cache expiry field.
     *
     * @since    1.0.0
     */
    public function cache_expiry_callback() {
        $expiry = get_option('pricewise_cache_expiry', 3600);
        echo '<input type="number" id="pricewise_cache_expiry" name="pricewise_cache_expiry" value="' . esc_attr($expiry) . '" class="regular-text" min="300" step="300" />';
        echo '<p class="description">' . __('How long to keep cached API responses (in seconds). Minimum 300 seconds (5 minutes).', 'pricewise') . '</p>';
        
        // Add some common presets
        echo '<div class="pricewise-presets">';
        echo '<button type="button" class="button button-secondary pricewise-preset" data-value="1800">' . __('30 Minutes', 'pricewise') . '</button>';
        echo '<button type="button" class="button button-secondary pricewise-preset" data-value="3600">' . __('1 Hour', 'pricewise') . '</button>';
        echo '<button type="button" class="button button-secondary pricewise-preset" data-value="86400">' . __('24 Hours', 'pricewise') . '</button>';
        echo '</div>';
    }

    /**
     * Callback for the rate limit field.
     *
     * @since    1.0.0
     */
    public function rate_limit_callback() {
        $rate_limit = get_option('pricewise_rate_limit_searches', 10);
        echo '<input type="number" id="pricewise_rate_limit_searches" name="pricewise_rate_limit_searches" value="' . esc_attr($rate_limit) . '" class="small-text" min="1" max="100" />';
        echo '<p class="description">' . __('Maximum number of searches per hour per user. This helps prevent API abuse.', 'pricewise') . '</p>';
    }

    /**
     * AJAX handler for clearing the cache.
     *
     * @since    1.0.0
     */
    public function ajax_clear_cache() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pricewise')));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'pricewise')));
        }

        // Clear the cache
        $cache = new Pricewise_Cache();
        $result = $cache->clear_all();

        if ($result) {
            wp_send_json_success(array('message' => __('Cache cleared successfully!', 'pricewise')));
        } else {
            wp_send_json_error(array('message' => __('Error clearing cache.', 'pricewise')));
        }
    }

    /**
     * AJAX handler for testing the API connection.
     *
     * @since    1.0.0
     */
    public function ajax_test_api() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pricewise_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'pricewise')));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'pricewise')));
        }

        // Test API connection with a simple location search
        $response = $this->api->autocomplete('London');

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('API connection failed:', 'pricewise') . ' ' . $response->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('API connection successful!', 'pricewise')
            ));
        }
    }