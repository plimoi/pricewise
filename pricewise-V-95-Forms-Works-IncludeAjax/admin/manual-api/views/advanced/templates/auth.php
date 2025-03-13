<?php
/**
 * Template for authentication configuration
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

// Extract auth-related values
$auth = isset($advanced_config['auth']) ? $advanced_config['auth'] : array();
$auth_type = isset($auth['type']) ? $auth['type'] : 'api_key';
$headers = isset($auth['headers']) ? $auth['headers'] : array();
$disable_headers = isset($auth['disable_headers']) ? (bool)$auth['disable_headers'] : false;
$save_test_headers = isset($advanced_config['save_test_headers']) ? (bool)$advanced_config['save_test_headers'] : false;

// Ensure we have at least one header
if (empty($headers)) {
    $headers = array(array('name' => 'X-API-Key', 'value' => '', 'save' => false));
}
?>

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