<?php
// admin/admin-help.php - Help page for PriceWise plugin

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Display the help page content
 */
function pricewise_display_help_page() {
    ?>
    <div class="wrap">
        <h1>PriceWise Help</h1>
        
        <div class="card">
            <h2>About PriceWise</h2>
            <p>PriceWise is a plugin for configuring and managing external APIs with powerful caching capabilities.</p>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Getting Started</h2>
            <p>To get started with PriceWise:</p>
            <ol>
                <li>Configure your API settings under PriceWise > Manual API</li>
                <li>Test your API connections to ensure they're working correctly</li>
                <li>Monitor and manage your cache under PriceWise > Cache</li>
                <li>Use the <code>[pricewise_api_status]</code> shortcode to display API status information</li>
            </ol>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h2>Manual API Configuration</h2>
            <p>PriceWise allows you to configure external APIs to fetch data. You can configure different API providers in the Manual API settings page.</p>
            
            <h3>Setting Up an API Provider</h3>
            <ol>
                <li>Go to PriceWise > Manual API</li>
                <li>Click "Add New API"</li>
                <li>Enter a name and ID for your API</li>
                <li>Configure the API key and base endpoint URL</li>
                <li>Set up advanced configuration if needed</li>
                <li>Save your API configuration</li>
                <li>Test the connection using the "Test API" button</li>
            </ol>
            
            <h3>Advanced API Configuration</h3>
            <p>The Manual API settings allow for advanced configuration:</p>
            <ul>
                <li>HTTP Headers: Set custom headers for authentication</li>
                <li>Request Parameters: Configure default parameters</li>
                <li>Response Format: Choose how to process API responses</li>
                <li>Request Body: Configure request body for POST/PUT requests</li>
            </ul>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Shortcodes</h2>
            
            <h3>API Status Shortcode</h3>
            <p><code>[pricewise_api_status]</code></p>
            <p>This shortcode displays basic information about your configured APIs, including:</p>
            <ul>
                <li>Number of configured APIs</li>
                <li>Status of each API</li>
                <li>Basic cache statistics</li>
            </ul>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Caching</h2>
            <p>PriceWise implements a caching system to minimize API calls and improve performance:</p>
            <ul>
                <li>API responses are cached in the <code>wp_pricewise_cache</code> database table</li>
                <li>Default cache duration is 1 hour (3600 seconds)</li>
                <li>Cache duration can be customized per API</li>
                <li>Expired cache entries are automatically cleaned during WordPress maintenance</li>
            </ul>
            <p>Cache statistics can be viewed on the main PriceWise dashboard.</p>
        </div>
    </div>
    <?php
}

/**
 * Add help page to admin menu
 */
function pricewise_add_help_menu() {
    add_submenu_page(
        'pricewise',               // Parent slug
        'PriceWise Help',          // Page title
        'PW Help',                 // Menu title
        'manage_options',          // Capability
        'pricewise-help',          // Menu slug
        'pricewise_display_help_page' // Callback function
    );
}
add_action('admin_menu', 'pricewise_add_help_menu', 11); // Priority 11 to make it appear after other menu items