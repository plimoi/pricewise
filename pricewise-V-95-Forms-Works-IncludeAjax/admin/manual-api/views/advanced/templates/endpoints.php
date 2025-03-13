<?php
/**
 * Template for endpoint configuration
 *
 * @package PriceWise
 * @subpackage ManualAPI
 * 
 * @var array $api The API configuration
 * @var array $advanced_config The advanced configuration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Extract endpoint-related values
$endpoint = isset($advanced_config['endpoint']) ? $advanced_config['endpoint'] : '';
$method = isset($advanced_config['method']) ? $advanced_config['method'] : 'GET';
$response_format = isset($advanced_config['response_format']) ? $advanced_config['response_format'] : 'json';
$request_timeout = isset($advanced_config['request_timeout']) ? intval($advanced_config['request_timeout']) : 30;
$cache_duration = isset($advanced_config['cache_duration']) ? intval($advanced_config['cache_duration']) : 3600;

// Note: The actual endpoint activation toggle is now handled in the main endpoint form
// in class-view.php rather than in this template file
?>

<div class="pricewise-api-endpoints">
    <h4>Endpoint Configuration</h4>
    
    <table class="form-table">
        <!-- API Endpoint field removed to avoid duplication with the main Endpoint Path field -->
        <!-- The endpoint path is now managed entirely by the main field in the endpoint form -->
        <!-- This value is automatically synced to advanced_config['endpoint'] for backward compatibility -->
        
        <tr>
            <th scope="row"><label for="api_method">HTTP Method</label></th>
            <td>
                <select id="api_method" name="api_method">
                    <option value="GET" <?php selected($method, 'GET'); ?>>GET</option>
                    <option value="POST" <?php selected($method, 'POST'); ?>>POST</option>
                    <option value="PUT" <?php selected($method, 'PUT'); ?>>PUT</option>
                    <option value="DELETE" <?php selected($method, 'DELETE'); ?>>DELETE</option>
                    <option value="PATCH" <?php selected($method, 'PATCH'); ?>>PATCH</option>
                </select>
                <p class="description">HTTP request method to use</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="api_response_format">Response Format</label></th>
            <td>
                <select id="api_response_format" name="api_response_format">
                    <option value="json" <?php selected($response_format, 'json'); ?>>JSON string</option>
                    <option value="array" <?php selected($response_format, 'array'); ?>>PHP array data</option>
                    <option value="object" <?php selected($response_format, 'object'); ?>>PHP object</option>
                    <option value="xml" <?php selected($response_format, 'xml'); ?>>XML (if supported)</option>
                    <option value="html" <?php selected($response_format, 'html'); ?>>HTML</option>
                </select>
                <p class="description">Select how API responses should be processed and cached</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="api_request_timeout">Request Timeout</label></th>
            <td>
                <input type="number" id="api_request_timeout" name="api_request_timeout" 
                       value="<?php echo esc_attr($request_timeout); ?>" min="1" max="120" class="small-text">
                <span class="description">seconds</span>
                <p class="description">Set the maximum time in seconds to wait for API response (default: 30)</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="api_cache_duration">Cache Duration</label></th>
            <td>
                <input type="number" id="api_cache_duration" name="api_cache_duration" 
                       value="<?php echo esc_attr($cache_duration); ?>" min="1" class="small-text">
                <span class="description">seconds</span>
                <p class="description">Duration to cache API responses specific to this endpoint (default: 3600 seconds / 1 hour)</p>
            </td>
        </tr>
    </table>
</div>