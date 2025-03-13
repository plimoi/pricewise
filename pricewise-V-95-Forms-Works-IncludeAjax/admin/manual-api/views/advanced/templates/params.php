<?php
/**
 * Template for request parameters configuration
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

// Extract params-related values
$params = isset($advanced_config['params']) ? $advanced_config['params'] : array();
$save_test_params = isset($advanced_config['save_test_params']) ? (bool)$advanced_config['save_test_params'] : false;

// Ensure we have at least one param
if (empty($params)) {
    $params = array(
        array('name' => 'currency', 'value' => 'USD', 'save' => false),
        array('name' => 'market', 'value' => 'en-US', 'save' => false)
    );
}
?>

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