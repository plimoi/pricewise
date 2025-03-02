<?php
/**
 * The API integration functionality of the plugin.
 *
 * @package    Pricewise
 * @subpackage Pricewise/includes/api
 */

class Pricewise_API {

    /**
     * The RapidAPI Key.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_key    The RapidAPI Key.
     */
    protected $api_key;

    /**
     * The RapidAPI Host.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_host    The RapidAPI Host.
     */
    protected $api_host;

    /**
     * The API base URL.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_base_url    The API base URL.
     */
    protected $api_base_url = 'https://sky-scanner3.p.rapidapi.com';

    /**
     * The cache instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Pricewise_Cache    $cache    The cache instance.
     */
    protected $cache;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->api_key = get_option('pricewise_rapidapi_key', '');
        $this->api_host = get_option('pricewise_rapidapi_host', 'sky-scanner3.p.rapidapi.com');
        $this->cache = new Pricewise_Cache();
    }

    /**
     * Check if API credentials are set.
     *
     * @since    1.0.0
     * @return   boolean    True if credentials are set, false otherwise.
     */
    public function has_credentials() {
        return !empty($this->api_key) && !empty($this->api_host);
    }

    /**
     * Make an API request.
     *
     * @since    1.0.0
     * @param    string    $endpoint     The API endpoint.
     * @param    array     $params       The query parameters.
     * @param    string    $method       The HTTP method (GET, POST, etc.).
     * @param    boolean   $use_cache    Whether to use cached results if available.
     * @return   array|WP_Error          The API response or WP_Error on failure.
     */
    protected function request($endpoint, $params = [], $method = 'GET', $use_cache = true) {
        // Check if credentials are set
        if (!$this->has_credentials()) {
            return new WP_Error('api_credentials_missing', __('API credentials are not set.', 'pricewise'));
        }
        
        // Build the URL
        $url = trailingslashit($this->api_base_url) . ltrim($endpoint, '/');
        
        // For GET requests, add params to URL
        if ($method === 'GET' && !empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        // Check cache first if enabled
        if ($use_cache) {
            $cache_key = md5($url . serialize($params));
            $cached_response = $this->cache->get($cache_key);
            
            if ($cached_response !== false) {
                return $cached_response;
            }
        }
        
        // Set up the request arguments
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'x-rapidapi-key' => $this->api_key,
                'x-rapidapi-host' => $this->api_host,
            ),
            'cookies' => array(),
        );
        
        // For POST requests, add params to body
        if ($method === 'POST' && !empty($params)) {
            $args['body'] = $params;
        }
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Check if response is successful
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP response code */
                    __('API request failed with code %d', 'pricewise'),
                    $response_code
                ),
                $response
            );
        }
        
        // Get the response body
        $body = wp_remote_retrieve_body($response);
        
        // Decode JSON response
        $data = json_decode($body, true);
        
        // Check if JSON decode was successful
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_decode_error',
                __('Error decoding API response', 'pricewise'),
                json_last_error()
            );
        }
        
        // Store in cache if enabled
        if ($use_cache) {
            $this->cache->set($cache_key, $data);
        }
        
        return $data;
    }

    /**
     * Log an API error.
     *
     * @since    1.0.0
     * @param    WP_Error    $error    The error to log.
     * @return   void
     */
    protected function log_error($error) {
        if (!is_wp_error($error)) {
            return;
        }
        
        $error_message = $error->get_error_message();
        $error_data = $error->get_error_data();
        
        error_log(sprintf(
            '[Pricewise API Error] %s: %s',
            $error->get_error_code(),
            $error_message
        ));
        
        if ($error_data) {
            error_log('[Pricewise API Error Data] ' . print_r($error_data, true));
        }
    }
}