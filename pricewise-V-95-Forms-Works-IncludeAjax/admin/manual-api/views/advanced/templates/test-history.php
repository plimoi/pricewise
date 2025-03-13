<?php
/**
 * Template for test history configuration
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

// Extract test history values
$save_test_response_body = isset($advanced_config['save_test_response_body']) ? (bool)$advanced_config['save_test_response_body'] : false;
?>

<!-- Test History Section -->
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