<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pricewise
 * @subpackage Pricewise/includes
 */

class Pricewise {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Pricewise_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The API handler for this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Pricewise_Hotels_API    $api    The API handler instance.
     */
    protected $api;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('PRICEWISE_VERSION')) {
            $this->version = PRICEWISE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'pricewise';

        $this->load_dependencies();
        $this->set_locale();
        $this->init_api();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Pricewise_Loader. Orchestrates the hooks of the plugin.
     * - Pricewise_i18n. Defines internationalization functionality.
     * - Pricewise_Admin. Defines all hooks for the admin area.
     * - Pricewise_Public. Defines all hooks for the public side of the site.
     * - Pricewise_API. Handles API requests.
     * - Pricewise_Cache. Handles API response caching.
     * - Pricewise_Hotel. Data model for hotels.
     * - Pricewise_Search. Data model for search parameters.
     * - Pricewise_Data_Manager. Database interactions.
     * - Pricewise_Shortcodes. Defines shortcodes.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once PRICEWISE_PLUGIN_DIR . 'includes/class-pricewise-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once PRICEWISE_PLUGIN_DIR . 'includes/class-pricewise-i18n.php';

        /**
         * Classes for data handling
         */
        require_once PRICEWISE_PLUGIN_DIR . 'includes/data/class-pricewise-data-manager.php';

        /**
         * API-related classes
         */
        require_once PRICEWISE_PLUGIN_DIR . 'includes/api/class-pricewise-cache.php';
        require_once PRICEWISE_PLUGIN_DIR . 'includes/api/class-pricewise-api.php';
        require_once PRICEWISE_PLUGIN_DIR . 'includes/api/class-pricewise-hotels-api.php';

        /**
         * Model classes
         */
        require_once PRICEWISE_PLUGIN_DIR . 'includes/models/class-pricewise-hotel.php';
        require_once PRICEWISE_PLUGIN_DIR . 'includes/models/class-pricewise-search.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once PRICEWISE_PLUGIN_DIR . 'admin/class-pricewise-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once PRICEWISE_PLUGIN_DIR . 'public/class-pricewise-public.php';

        /**
         * The class responsible for defining all shortcodes.
         */
        require_once PRICEWISE_PLUGIN_DIR . 'public/class-pricewise-shortcodes.php';

        $this->loader = new Pricewise_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Pricewise_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Pricewise_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Initialize the API handler.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_api() {
        $this->api = new Pricewise_Hotels_API();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Pricewise_Admin($this->get_plugin_name(), $this->get_version(), $this->api);

        // Admin assets
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');

        // Settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');

        // AJAX handlers
        $this->loader->add_action('wp_ajax_pricewise_clear_cache', $plugin_admin, 'ajax_clear_cache');
        $this->loader->add_action('wp_ajax_pricewise_test_api', $plugin_admin, 'ajax_test_api');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Pricewise_Public($this->get_plugin_name(), $this->get_version(), $this->api);
        $plugin_shortcodes = new Pricewise_Shortcodes($this->api);

        // Public assets
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Register shortcodes
        $this->loader->add_action('init', $plugin_shortcodes, 'register_shortcodes');

        // AJAX handlers
        $this->loader->add_action('wp_ajax_pricewise_autocomplete', $plugin_public, 'ajax_autocomplete');
        $this->loader->add_action('wp_ajax_nopriv_pricewise_autocomplete', $plugin_public, 'ajax_autocomplete');
        
        $this->loader->add_action('wp_ajax_pricewise_search', $plugin_public, 'ajax_search');
        $this->loader->add_action('wp_ajax_nopriv_pricewise_search', $plugin_public, 'ajax_search');
    }

    /**
     * Get the API handler instance.
     *
     * @since     1.0.0
     * @return    Pricewise_Hotels_API    The API handler instance.
     */
    public function get_api() {
        return $this->api;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Pricewise_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}