<?php
/**
 * Main template for API advanced configuration
 *
 * @package PriceWise
 * @subpackage ManualAPI
 * 
 * @var array $api The API configuration
 * @var Pricewise_API_Advanced_Renderer $renderer The renderer instance
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Process the API data to ensure all required fields exist
$api = $renderer->process_api_data($api);
$advanced_config = $api['advanced_config'];
?>

<div class="pricewise-api-advanced-toggle">
    <button type="button" class="button" id="show-advanced-details">API Advanced Details</button>
</div>

<div id="pricewise-api-advanced-details" style="display: none; margin-top: 20px;">
    <h3>Advanced API Configuration</h3>
    <p>Configure additional settings for this API provider.</p>
    
    <?php 
    // Load endpoint configuration template
    $renderer->load_template('endpoints', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Load authentication template
    $renderer->load_template('auth', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Load body configuration template
    $renderer->load_template('body', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Load parameters template
    $renderer->load_template('params', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Load response headers template
    $renderer->load_template('response-headers', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Load test history template
    $renderer->load_template('test-history', array(
        'api' => $api,
        'advanced_config' => $advanced_config
    ));
    
    // Allow for additional sections to be added through an action hook
    do_action('pricewise_api_advanced_config_sections', $api);
    ?>
</div>