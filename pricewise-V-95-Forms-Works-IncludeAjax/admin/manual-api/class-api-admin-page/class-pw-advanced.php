<?php
/**
 * API Admin Page Advanced Configuration Class
 * Handles the advanced configuration options for the API.
 * 
 * @package PriceWise
 * @subpackage ManualAPI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle API advanced configuration
 */
class Pricewise_API_Advanced {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Empty constructor
    }
    
    /**
     * Render advanced configuration section
     * 
     * @param array $api The API data
     * @return string HTML output
     */
    public function render_advanced_config($api) {
        // Prepare advanced config data - ensure it exists
        $advanced_config = isset($api['advanced_config']) ? $api['advanced_config'] : array(
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
        
        // Set defaults
        $auth_type = isset($advanced_config['auth']['type']) ? $advanced_config['auth']['type'] : 'api_key';
        $endpoint = isset($advanced_config['endpoint']) ? $advanced_config['endpoint'] : '';
        $method = isset($advanced_config['method']) ? $advanced_config['method'] : 'GET';
        $response_format = isset($advanced_config['response_format']) ? $advanced_config['response_format'] : 'json';
        $request_timeout = isset($advanced_config['request_timeout']) ? intval($advanced_config['request_timeout']) : 30;
        $cache_duration = isset($advanced_config['cache_duration']) ? intval($advanced_config['cache_duration']) : 3600;
        $headers = isset($advanced_config['auth']['headers']) ? $advanced_config['auth']['headers'] : array();
        $disable_headers = isset($advanced_config['auth']['disable_headers']) ? $advanced_config['auth']['disable_headers'] : false;
        $params = isset($advanced_config['params']) ? $advanced_config['params'] : array();
        
        // Body settings
        $body_enabled = isset($advanced_config['body']['enabled']) ? $advanced_config['body']['enabled'] : false;
        $body_type = isset($advanced_config['body']['type']) ? $advanced_config['body']['type'] : 'json';
        $body_content = isset($advanced_config['body']['content']) ? $advanced_config['body']['content'] : '';
        
        // Response headers to display
        $response_headers = isset($advanced_config['response_headers']) ? $advanced_config['response_headers'] : array(
            array('name' => 'x-ratelimit-requests-limit', 'save' => false),
            array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
        );
        
        // Test history settings
        $save_test_headers = isset($advanced_config['save_test_headers']) ? $advanced_config['save_test_headers'] : false;
        $save_test_params = isset($advanced_config['save_test_params']) ? $advanced_config['save_test_params'] : false;
        $save_test_response_headers = isset($advanced_config['save_test_response_headers']) ? $advanced_config['save_test_response_headers'] : false;
        $save_test_response_body = isset($advanced_config['save_test_response_body']) ? $advanced_config['save_test_response_body'] : false;
        
        // Ensure we have at least one empty header if none exist
        if (empty($headers)) {
            $headers = array(array('name' => 'X-API-Key', 'value' => '', 'save' => false));
        }
        
        // Ensure we have at least one empty param if none exist
        if (empty($params)) {
            $params = array(
                array('name' => 'currency', 'value' => 'USD', 'save' => false),
                array('name' => 'market', 'value' => 'en-US', 'save' => false)
            );
        }
        
        // Ensure we have at least one response header if none exist
        if (empty($response_headers)) {
            $response_headers = array(
                array('name' => 'x-ratelimit-requests-limit', 'save' => false),
                array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
            );
        }
        
        ob_start();
        ?>
        <div class="pricewise-api-advanced-toggle">
            <button type="button" class="button" id="show-advanced-details">API Advanced Details</button>
        </div>
        
        <div id="pricewise-api-advanced-details" style="display: none; margin-top: 20px;">
            <h3>Advanced API Configuration</h3>
            <p>Configure additional settings for this API provider.</p>
            
            <div class="pricewise-api-endpoints">
                <h4>Endpoint Configuration</h4>
                
                <table class="form-table">
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
            
            <div class="pricewise-api-auth">
                <h4>Authentication Configuration</h4>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_auth_type">Auth Type</label></th>
                        <td>
                            <select id="api_auth_type" name="api_auth_type">
                                <option value="api_key" <?php selected($auth_type, 'api_key'); ?>>API Key</option>
                                <option value="bearer" <?php selected($auth_type, 'bearer'); ?>>Bearer Token</option>
                                <option value="basic" <?php selected($auth_type, 'basic'); ?>>Basic Auth</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>HTTP Headers</label></th>
                        <td>
                            <label><input type="checkbox" name="api_disable_headers" value="1" <?php checked($disable_headers, true); ?>> Disable Headers</label>
                            <p class="description">Check this if your API doesn't require any HTTP headers</p>
                        </td>
                    </tr>
                </table>
                
                <div class="pricewise-api-headers" <?php echo $disable_headers ? 'style="display:none;"' : ''; ?>>
                    <h4>HTTP Headers</h4>
                    <p class="description">Drag and drop to reorder headers.</p>
                    <div id="headers-container" class="sortable-container">
                        <?php foreach ($headers as $index => $header): ?>
                        <div class="header-row sortable-row" style="margin-bottom: 10px;">
                            <div class="drag-handle"></div>
                            <input type="text" name="header_name[]" placeholder="Header Name" value="<?php echo esc_attr($header['name']); ?>" class="regular-text" style="width: 25%;">
                            <input type="text" name="header_value[]" placeholder="Header Value" value="<?php echo esc_attr(isset($header['value']) ? $header['value'] : ''); ?>" class="regular-text" style="width: 40%;">
                            <button type="button" class="button remove-header" <?php echo (count($headers) <= 1) ? 'style="display:none;"' : ''; ?>>Remove</button>
                            <label style="margin-left: 5px;">
                                <input type="checkbox" name="header_save[<?php echo $index; ?>]" value="1" <?php checked(isset($header['save']) && $header['save'], true); ?>>
                                <span class="description">Save</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button button-secondary" id="add-header">Add Header</button>
                    <div style="margin-top: 10px;">
                        <label>
                            <input type="checkbox" name="api_advanced_config[save_test_headers]" value="1" <?php checked($save_test_headers, true); ?>>
                            <strong>Save headers in test history</strong>
                        </label>
                        <p class="description">When enabled, request headers will be saved when testing this API.</p>
                    </div>
                </div>
            </div>
            
            <div class="pricewise-api-body">
                <h4>Request Body</h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_body_enabled">Enable Request Body</label></th>
                        <td>
                            <input type="checkbox" id="api_body_enabled" name="api_body_enabled" value="1" <?php checked($body_enabled, true); ?>>
                            <p class="description">Enable this for APIs that require a request body (typically for POST/PUT requests)</p>
                        </td>
                    </tr>
                </table>
                
                <div id="body-options" <?php echo !$body_enabled ? 'style="display:none;"' : ''; ?>>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="api_body_type">Body Type</label></th>
                            <td>
                                <select id="api_body_type" name="api_body_type">
                                    <option value="json" <?php selected($body_type, 'json'); ?>>JSON</option>
                                    <option value="form" <?php selected($body_type, 'form'); ?>>Form Data</option>
                                    <option value="raw" <?php selected($body_type, 'raw'); ?>>Raw</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="api_body_content">Body Content</label></th>
                            <td>
                                <textarea id="api_body_content" name="api_body_content" rows="8" class="large-text code"><?php echo esc_textarea($body_content); ?></textarea>
                                <p class="description body-json-desc" <?php echo $body_type !== 'json' ? 'style="display:none;"' : ''; ?>>Enter JSON format: {"key": "value"}</p>
                                <p class="description body-form-desc" <?php echo $body_type !== 'form' ? 'style="display:none;"' : ''; ?>>Enter Form format: key=value&another=value2</p>
                                <p class="description body-raw-desc" <?php echo $body_type !== 'raw' ? 'style="display:none;"' : ''; ?>>Enter raw body content</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="pricewise-api-params">
                <h4>Default Parameters</h4>
                <p class="description">Drag and drop to reorder parameters.</p>
                <div id="params-container" class="sortable-container">
                    <?php foreach ($params as $index => $param): ?>
                    <div class="param-row sortable-row" style="margin-bottom: 10px;">
                        <div class="drag-handle"></div>
                        <input type="text" name="param_name[]" placeholder="Parameter Name" value="<?php echo esc_attr($param['name']); ?>" class="regular-text" style="width: 25%;">
                        <input type="text" name="param_value[]" placeholder="Parameter Value" value="<?php echo esc_attr($param['value']); ?>" class="regular-text" style="width: 40%;">
                        <button type="button" class="button remove-param" <?php echo (count($params) <= 1) ? 'style="display:none;"' : ''; ?>>Remove</button>
                        <label style="margin-left: 5px;">
                            <input type="checkbox" name="param_save[<?php echo $index; ?>]" value="1" <?php checked(isset($param['save']) && $param['save'], true); ?>>
                            <span class="description">Save</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-secondary" id="add-param">Add Parameter</button>
                <div style="margin-top: 10px;">
                    <label>
                        <input type="checkbox" name="api_advanced_config[save_test_params]" value="1" <?php checked($save_test_params, true); ?>>
                        <strong>Save parameters in test history</strong>
                    </label>
                    <p class="description">When enabled, request parameters will be saved when testing this API.</p>
                </div>
            </div>
            
            <div class="pricewise-api-response-headers">
                <h4>Response Headers to Display</h4>
                <p class="description">Specify which response headers should be prominently displayed in the test results (e.g., rate limit headers)</p>
                <p class="description">Drag and drop to reorder response headers.</p>
                <div id="response-headers-container" class="sortable-container">
                    <?php foreach ($response_headers as $index => $header): ?>
                    <div class="response-header-row sortable-row" style="margin-bottom: 10px;">
                        <div class="drag-handle"></div>
                        <input type="text" name="response_header_name[]" placeholder="Header Name" value="<?php echo esc_attr($header['name']); ?>" class="regular-text" style="width: 40%;">
                        <button type="button" class="button remove-response-header" <?php echo (count($response_headers) <= 1) ? 'style="display:none;"' : ''; ?>>Remove</button>
                        <label style="margin-left: 5px;">
                            <input type="checkbox" name="response_header_save[<?php echo $index; ?>]" value="1" <?php checked(isset($header['save']) && $header['save'], true); ?>>
                            <span class="description">Save</span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="description">Common rate limit headers: x-ratelimit-limit, x-ratelimit-remaining, x-ratelimit-reset, etc.</p>
                <button type="button" class="button button-secondary" id="add-response-header">Add Response Header</button>
                <div style="margin-top: 10px;">
                    <label>
                        <input type="checkbox" name="api_advanced_config[save_test_response_headers]" value="1" <?php checked($save_test_response_headers, true); ?>>
                        <strong>Save response headers in test history</strong>
                    </label>
                    <p class="description">When enabled, response headers will be saved when testing this API.</p>
                </div>
            </div>
            
            <div class="pricewise-api-test-history">
                <h4>Test History Options</h4>
                <p class="description">Configure what data should be saved to the test history when testing this API.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Save Response Body</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_advanced_config[save_test_response_body]" value="1" <?php checked($save_test_response_body, true); ?>>
                                Save response body in test history
                            </label>
                            <p class="description">When enabled, a snippet of the response body will be saved when testing this API.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php
            // Allow for additional sections to be added through an action hook
            do_action('pricewise_api_advanced_config_sections', $api);
            ?>
        </div>
        
        <style>
        .sortable-container .sortable-row {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .sortable-container .sortable-row:hover {
            background-color: #f0f0f0;
        }
        .sortable-container .sortable-row.ui-sortable-helper {
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .sortable-container .ui-sortable-placeholder {
            visibility: visible !important;
            background-color: #e0e0e0;
            border: 1px dashed #aaa;
            height: 40px;
        }
        .drag-handle {
            width: 20px;
            height: 20px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" fill="%23999"/></svg>') no-repeat center;
            margin-right: 10px;
            cursor: move;
            flex-shrink: 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize sortable containers if jQuery UI is available
            if (typeof $.ui !== 'undefined') {
                $('.sortable-container').sortable({
                    handle: '.drag-handle',
                    placeholder: 'ui-sortable-placeholder',
                    forcePlaceholderSize: true,
                    opacity: 0.8,
                    tolerance: 'pointer'
                });
            }
            
            // Toggle advanced settings
            $('#show-advanced-details').on('click', function() {
                $('#pricewise-api-advanced-details').slideToggle();
                
                var buttonText = $(this).text();
                if (buttonText === 'API Advanced Details') {
                    $(this).text('Hide Advanced Details');
                } else {
                    $(this).text('API Advanced Details');
                }
            });
            
            // Toggle headers section based on disable checkbox
            $('input[name="api_disable_headers"]').on('change', function() {
                if($(this).is(':checked')) {
                    $('.pricewise-api-headers').slideUp();
                } else {
                    $('.pricewise-api-headers').slideDown();
                }
            });
            
            // Toggle body options based on enable checkbox
            $('#api_body_enabled').on('change', function() {
                if($(this).is(':checked')) {
                    $('#body-options').slideDown();
                } else {
                    $('#body-options').slideUp();
                }
            });
            
            // Toggle body type descriptions
            $('#api_body_type').on('change', function() {
                var selectedType = $(this).val();
                $('.description[class*="body-"]').hide();
                $('.body-' + selectedType + '-desc').show();
            });
            
            // Add new header
            $('#add-header').on('click', function() {
                var index = $('.header-row').length;
                var newRow = $('<div class="header-row sortable-row" style="margin-bottom: 10px;">' +
                    '<div class="drag-handle"></div>' +
                    '<input type="text" name="header_name[]" placeholder="Header Name" class="regular-text" style="width: 25%;">' +
                    '<input type="text" name="header_value[]" placeholder="Header Value" class="regular-text" style="width: 40%;">' +
                    '<button type="button" class="button remove-header">Remove</button>' +
                    '<label style="margin-left: 5px;">' +
                    '<input type="checkbox" name="header_save[' + index + ']" value="1">' +
                    '<span class="description">Save</span>' +
                    '</label>' +
                    '</div>');
                
                $('#headers-container').append(newRow);
                $('.remove-header').show();
                
                if (typeof $.ui !== 'undefined') {
                    $('#headers-container').sortable('refresh');
                }
            });
            
            // Remove header
            $(document).on('click', '.remove-header', function() {
                $(this).closest('.header-row').remove();
                
                if ($('.header-row').length === 1) {
                    $('.remove-header').hide();
                }
                
                $('#headers-container .header-row').each(function(i) {
                    $(this).find('input[name^="header_save"]').attr('name', 'header_save[' + i + ']');
                });
            });
            
            // Add new parameter
            $('#add-param').on('click', function() {
                var index = $('.param-row').length;
                var newRow = $('<div class="param-row sortable-row" style="margin-bottom: 10px;">' +
                    '<div class="drag-handle"></div>' +
                    '<input type="text" name="param_name[]" placeholder="Parameter Name" class="regular-text" style="width: 25%;">' +
                    '<input type="text" name="param_value[]" placeholder="Parameter Value" class="regular-text" style="width: 40%;">' +
                    '<button type="button" class="button remove-param">Remove</button>' +
                    '<label style="margin-left: 5px;">' +
                    '<input type="checkbox" name="param_save[' + index + ']" value="1">' +
                    '<span class="description">Save</span>' +
                    '</label>' +
                    '</div>');
                
                $('#params-container').append(newRow);
                $('.remove-param').show();
                
                if (typeof $.ui !== 'undefined') {
                    $('#params-container').sortable('refresh');
                }
            });
            
            // Remove parameter
            $(document).on('click', '.remove-param', function() {
                $(this).closest('.param-row').remove();
                
                if ($('.param-row').length === 1) {
                    $('.remove-param').hide();
                }
                
                $('#params-container .param-row').each(function(i) {
                    $(this).find('input[name^="param_save"]').attr('name', 'param_save[' + i + ']');
                });
            });
            
            // Add new response header
            $('#add-response-header').on('click', function() {
                var index = $('.response-header-row').length;
                var newRow = $('<div class="response-header-row sortable-row" style="margin-bottom: 10px;">' +
                    '<div class="drag-handle"></div>' +
                    '<input type="text" name="response_header_name[]" placeholder="Header Name" class="regular-text" style="width: 40%;">' +
                    '<button type="button" class="button remove-response-header">Remove</button>' +
                    '<label style="margin-left: 5px;">' +
                    '<input type="checkbox" name="response_header_save[' + index + ']" value="1">' +
                    '<span class="description">Save</span>' +
                    '</label>' +
                    '</div>');
                
                $('#response-headers-container').append(newRow);
                $('.remove-response-header').show();
                
                if (typeof $.ui !== 'undefined') {
                    $('#response-headers-container').sortable('refresh');
                }
            });
            
            // Remove response header
            $(document).on('click', '.remove-response-header', function() {
                $(this).closest('.response-header-row').remove();
                
                if ($('.response-header-row').length === 1) {
                    $('.remove-response-header').hide();
                }
                
                $('#response-headers-container .response-header-row').each(function(i) {
                    $(this).find('input[name^="response_header_save"]').attr('name', 'response_header_save[' + i + ']');
                });
            });
            
            // Update checkbox states when sorting
            $('.sortable-container').on('sortstop', function() {
                // Renumber header save checkboxes
                $('#headers-container .header-row').each(function(i) {
                    $(this).find('input[name^="header_save"]').attr('name', 'header_save[' + i + ']');
                });
                
                // Renumber parameter save checkboxes
                $('#params-container .param-row').each(function(i) {
                    $(this).find('input[name^="param_save"]').attr('name', 'param_save[' + i + ']');
                });
                
                // Renumber response header save checkboxes
                $('#response-headers-container .response-header-row').each(function(i) {
                    $(this).find('input[name^="response_header_save"]').attr('name', 'response_header_save[' + i + ']');
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
}