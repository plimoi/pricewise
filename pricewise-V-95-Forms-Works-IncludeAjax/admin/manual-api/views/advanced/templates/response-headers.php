<?php
/**
 * Template for response headers configuration
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

// Extract response headers values
$response_headers = isset($advanced_config['response_headers']) ? $advanced_config['response_headers'] : array();
$save_test_response_headers = isset($advanced_config['save_test_response_headers']) ? (bool)$advanced_config['save_test_response_headers'] : false;

// Ensure we have at least one response header
if (empty($response_headers)) {
    $response_headers = array(
        array('name' => 'x-ratelimit-requests-limit', 'save' => false),
        array('name' => 'x-ratelimit-requests-remaining', 'save' => false)
    );
}
?>

<!-- Response Headers Configuration Section -->
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