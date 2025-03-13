<?php
/**
 * Advanced Configuration Renderer Class
 * Handles rendering of advanced configuration options for API settings.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API advanced configuration rendering
 */
class Pricewise_API_Advanced_Renderer {
    
    /**
     * Path to template directory
     *
     * @var string
     */
    private $template_path;
    
    /**
     * Path to assets directory
     *
     * @var string
     */
    private $assets_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->template_path = plugin_dir_path( __FILE__ ) . 'templates/';
        $this->assets_path = plugin_dir_url( __FILE__ );
        
        // Enqueue assets when needed
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }
    
    /**
     * Enqueue assets for the advanced configuration
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets( $hook ) {
        // Only enqueue on API admin page
        if ( strpos( $hook, 'pricewise-manual-api' ) !== false ) {
            // Enqueue CSS
            wp_enqueue_style(
                'pricewise-advanced-config',
                $this->assets_path . 'css/advanced-config.css',
                array(),
                PRICEWISE_VERSION
            );
            
            // Enqueue JavaScript
            wp_enqueue_script(
                'pricewise-advanced-config',
                $this->assets_path . 'js/advanced-config.js',
                array( 'jquery', 'jquery-ui-sortable' ),
                PRICEWISE_VERSION,
                true
            );
        }
    }
    
    /**
     * Render advanced configuration section
     * 
     * @param array $api The API data
     * @return string HTML output
     */
    public function render_advanced_config( $api ) {
        // Start output buffering
        ob_start();
        
        // Load main template
        $this->load_template( 'main', array( 'api' => $api, 'renderer' => $this ) );
        
        // Return the generated HTML
        return ob_get_clean();
    }
    
    /**
     * Load a template file
     *
     * @param string $template Template name (without .php extension)
     * @param array $args Arguments to pass to the template
     */
    public function load_template( $template, $args = array() ) {
        $template_file = $this->template_path . $template . '.php';
        
        if ( file_exists( $template_file ) ) {
            // Extract args to make them available in the template
            extract( $args );
            
            include $template_file;
        }
    }
    
    /**
     * Backward compatibility function
     * 
     * This allows the original class to be a wrapper for the new renderer
     * 
     * @param array $api The API data
     * @return string HTML output
     */
    public static function render_advanced_config_legacy( $api ) {
        $renderer = new self();
        return $renderer->render_advanced_config( $api );
    }
    
    /**
     * Get default values for advanced configuration
     *
     * @return array Default values
     */
    public function get_default_values() {
        return array(
            'endpoint' => '',
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
            ),
            'save_test_headers' => false,
            'save_test_params' => false,
            'save_test_response_headers' => false,
            'save_test_response_body' => false
        );
    }
    
    /**
     * Process API data to ensure it has the required structure
     *
     * @param array $api API configuration
     * @return array Processed API data
     */
    public function process_api_data( $api ) {
        // Make sure advanced_config exists
        if ( ! isset( $api['advanced_config'] ) || ! is_array( $api['advanced_config'] ) ) {
            $api['advanced_config'] = array();
        }
        
        // Merge with defaults to ensure all required keys exist
        $defaults = $this->get_default_values();
        $api['advanced_config'] = array_merge( $defaults, $api['advanced_config'] );
        
        // Process nested structures
        if ( ! isset( $api['advanced_config']['auth'] ) || ! is_array( $api['advanced_config']['auth'] ) ) {
            $api['advanced_config']['auth'] = $defaults['auth'];
        } else {
            $api['advanced_config']['auth'] = array_merge( $defaults['auth'], $api['advanced_config']['auth'] );
        }
        
        if ( ! isset( $api['advanced_config']['body'] ) || ! is_array( $api['advanced_config']['body'] ) ) {
            $api['advanced_config']['body'] = $defaults['body'];
        } else {
            $api['advanced_config']['body'] = array_merge( $defaults['body'], $api['advanced_config']['body'] );
        }
        
        // Ensure we have at least one header if headers are empty
        if ( empty( $api['advanced_config']['auth']['headers'] ) ) {
            $api['advanced_config']['auth']['headers'] = $defaults['auth']['headers'];
        }
        
        // Ensure we have at least one param if params are empty
        if ( empty( $api['advanced_config']['params'] ) ) {
            $api['advanced_config']['params'] = $defaults['params'];
        }
        
        // Ensure we have at least one response header if response_headers are empty
        if ( empty( $api['advanced_config']['response_headers'] ) ) {
            $api['advanced_config']['response_headers'] = $defaults['response_headers'];
        }
        
        return $api;
    }
}