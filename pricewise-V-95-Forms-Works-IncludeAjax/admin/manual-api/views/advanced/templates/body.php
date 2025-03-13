<?php
/**
 * Template for request body configuration
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

// Extract body-related values
$body = isset($advanced_config['body']) ? $advanced_config['body'] : array();
$body_enabled = isset($body['enabled']) ? (bool)$body['enabled'] : false;
$body_type = isset($body['type']) ? $body['type'] : 'json';
$body_content = isset($body['content']) ? $body['content'] : '';
?>

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