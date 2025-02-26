<?php
/**
 * Pricewise Admin Page
 * 
 * This file handles the "Partners" admin page for the Pricewise plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'pricewise_add_admin_menu');

function pricewise_add_admin_menu() {
    add_menu_page(
        'Pricewise',                // Page title
        'Pricewise',                // Menu title
        'manage_options',           // Capability
        'pricewise',                // Menu slug
        'pricewise_main_page',      // Function
        'dashicons-chart-line',     // Icon
        30                          // Position
    );
    
    add_submenu_page(
        'pricewise',                // Parent slug
        'Partners',                 // Page title
        'Partners',                 // Menu title
        'manage_options',           // Capability
        'pricewise-partners',       // Menu slug
        'pricewise_partners_page'   // Function
    );
    
    // Add settings submenu
    add_submenu_page(
        'pricewise',                // Parent slug
        'Settings',                 // Page title
        'Settings',                 // Menu title
        'manage_options',           // Capability
        'pricewise-settings',       // Menu slug
        'pricewise_settings_page'   // Function
    );
}

// Initialize empty partners option when plugin is activated
function pricewise_initialize_partners() {
    // Check if partners are already defined
    if (!get_option('pricewise_partners')) {
        // Define an empty array for partners
        add_option('pricewise_partners', array());
    }
    
    // Initialize default settings
    if (!get_option('pricewise_settings')) {
        add_option('pricewise_settings', array(
            'default_rooms' => 1,
            'enable_pets' => 1,
            'enable_child_ages' => 1,
            'max_children' => 5,
            'max_rooms' => 5
        ));
    }
}

// Register admin settings
add_action('admin_init', 'pricewise_register_settings');

function pricewise_register_settings() {
    register_setting('pricewise_partners_group', 'pricewise_partners', 'pricewise_validate_partners');
    register_setting('pricewise_settings_group', 'pricewise_settings', 'pricewise_validate_settings');
}

// Validate partners data
function pricewise_validate_partners($input) {
    // Sanitize each field
    $new_input = array();
    
    foreach ($input as $key => $partner) {
        $partner_key = sanitize_key($key);
        $new_input[$partner_key] = array(
            'name' => sanitize_text_field($partner['name']),
            'url_structure' => sanitize_text_field($partner['url_structure']), // Changed from esc_url_raw to keep placeholders
            'active' => isset($partner['active']) ? 1 : 0
        );
    }
    
    return $new_input;
}

// Validate settings
function pricewise_validate_settings($input) {
    $new_input = array();
    
    // Sanitize each setting
    $new_input['default_rooms'] = isset($input['default_rooms']) ? intval($input['default_rooms']) : 1;
    $new_input['enable_pets'] = isset($input['enable_pets']) ? 1 : 0;
    $new_input['enable_child_ages'] = isset($input['enable_child_ages']) ? 1 : 0;
    $new_input['max_children'] = isset($input['max_children']) ? intval($input['max_children']) : 5;
    $new_input['max_rooms'] = isset($input['max_rooms']) ? intval($input['max_rooms']) : 5;
    
    return $new_input;
}

// Display main admin page
function pricewise_main_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Welcome to Pricewise hotel price comparison plugin. Use the submenu to manage partners and settings.</p>
        
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h2>Quick Start Guide</h2>
            <p>Follow these steps to set up your hotel price comparison:</p>
            <ol>
                <li>Go to <a href="<?php echo admin_url('admin.php?page=pricewise-partners'); ?>">Partners</a> and add booking sites</li>
                <li>Configure plugin <a href="<?php echo admin_url('admin.php?page=pricewise-settings'); ?>">Settings</a></li>
                <li>Add the shortcode <code>[pricewise_search]</code> to any page or post to display the search form</li>
            </ol>
            <p>Need help with shortcode parameters? Here's an example with all options:</p>
            <pre style="background: #f0f0f0; padding: 10px; overflow: auto;">[pricewise_search default_destination="Paris" default_rooms="2" partner_url="yes"]</pre>
            
            <h3>Shortcode Parameters</h3>
            <ul>
                <li><code>default_destination</code> - Set a default destination for the search form</li>
                <li><code>default_rooms</code> - Set the default number of rooms</li>
                <li><code>partner_url</code> - Display partner URLs for debugging (values: "yes" or "no", default: "no")</li>
            </ul>
        </div>
    </div>
    <?php
}

// Display settings page
function pricewise_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get current settings
    $settings = get_option('pricewise_settings', array(
        'default_rooms' => 1,
        'enable_pets' => 1,
        'enable_child_ages' => 1,
        'max_children' => 5,
        'max_rooms' => 5
    ));
    
    // Save settings if form was submitted
    if (isset($_POST['pricewise_save_settings'])) {
        // Verify nonce
        if (isset($_POST['pricewise_settings_nonce']) && wp_verify_nonce($_POST['pricewise_settings_nonce'], 'pricewise_settings_nonce')) {
            $settings = array(
                'default_rooms' => isset($_POST['default_rooms']) ? intval($_POST['default_rooms']) : 1,
                'enable_pets' => isset($_POST['enable_pets']) ? 1 : 0,
                'enable_child_ages' => isset($_POST['enable_child_ages']) ? 1 : 0,
                'max_children' => isset($_POST['max_children']) ? intval($_POST['max_children']) : 5,
                'max_rooms' => isset($_POST['max_rooms']) ? intval($_POST['max_rooms']) : 5
            );
            
            update_option('pricewise_settings', $settings);
            add_settings_error('pricewise_settings', 'settings_updated', 'Settings saved successfully.', 'success');
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors('pricewise_settings'); ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('pricewise_settings_nonce', 'pricewise_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="default_rooms">Default Number of Rooms</label></th>
                    <td>
                        <input type="number" name="default_rooms" id="default_rooms" 
                               value="<?php echo esc_attr($settings['default_rooms']); ?>" min="1" max="10">
                        <p class="description">Default number of rooms to show in the search form.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pet-Friendly Option</th>
                    <td>
                        <fieldset>
                            <label for="enable_pets">
                                <input type="checkbox" name="enable_pets" id="enable_pets" 
                                       <?php checked($settings['enable_pets'], 1); ?>>
                                Enable pet-friendly option in search form
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Child Ages</th>
                    <td>
                        <fieldset>
                            <label for="enable_child_ages">
                                <input type="checkbox" name="enable_child_ages" id="enable_child_ages" 
                                       <?php checked($settings['enable_child_ages'], 1); ?>>
                                Allow specifying ages for each child
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_children">Maximum Children</label></th>
                    <td>
                        <input type="number" name="max_children" id="max_children" 
                               value="<?php echo esc_attr($settings['max_children']); ?>" min="1" max="10">
                        <p class="description">Maximum number of children allowed in search.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_rooms">Maximum Rooms</label></th>
                    <td>
                        <input type="number" name="max_rooms" id="max_rooms" 
                               value="<?php echo esc_attr($settings['max_rooms']); ?>" min="1" max="10">
                        <p class="description">Maximum number of rooms allowed in search.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="pricewise_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
        
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h3>URL Placeholders</h3>
            <p>When setting up partner URLs, you can use these additional placeholders:</p>
            <ul>
                <li><code>{rooms}</code> - Number of rooms</li>
                <li><code>{child_ages}</code> - Comma-separated list of child ages</li>
                <li><code>{has_pets}</code> - "1" if pets are included, "0" otherwise</li>
            </ul>
            <p>Example: <code>https://example.com/search?rooms={rooms}&pets={has_pets}</code></p>
        </div>
        
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h3>Shortcode Debugging</h3>
            <p>If you're experiencing "Error fetching data" messages, you can enable URL debugging with the <code>partner_url</code> parameter:</p>
            <pre style="background: #f0f0f0; padding: 10px; overflow: auto;">[pricewise_search partner_url="yes"]</pre>
            <p>This will display the exact URLs being used to fetch data from partners, helping you to troubleshoot connection issues.</p>
        </div>
    </div>
    <?php
}

// Display partners admin page
function pricewise_partners_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle partner actions (add, edit, delete)
    if (isset($_POST['pricewise_add_partner']) || isset($_POST['pricewise_edit_partner'])) {
        // Save partner data
        save_partner_data();
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['partner'])) {
        // Delete partner
        delete_partner($_GET['partner']);
    }
    
    // Get current partners
    $partners = get_option('pricewise_partners', array());
    
    // Determine if we're in edit mode
    $edit_mode = false;
    $partner_to_edit = array(
        'key' => '',
        'name' => '',
        'url_structure' => '',
        'active' => 1
    );
    
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['partner'])) {
        $edit_mode = true;
        $partner_key = sanitize_key($_GET['partner']);
        
        if (isset($partners[$partner_key])) {
            $partner_to_edit = array(
                'key' => $partner_key,
                'name' => $partners[$partner_key]['name'],
                'url_structure' => $partners[$partner_key]['url_structure'],
                'active' => $partners[$partner_key]['active']
            );
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Add New / Edit Partner Form -->
        <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px;">
            <h2><?php echo $edit_mode ? 'Edit Partner' : 'Add New Partner'; ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('pricewise_partners_nonce', 'pricewise_partners_nonce'); ?>
                
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="partner_key" value="<?php echo esc_attr($partner_to_edit['key']); ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="partner_name">Partner Name</label></th>
                        <td>
                            <input type="text" name="partner_name" id="partner_name" class="regular-text" 
                                value="<?php echo esc_attr($partner_to_edit['name']); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="partner_url_structure">URL Structure</label></th>
                        <td>
                            <input type="text" name="partner_url_structure" id="partner_url_structure" class="large-text"
                                value="<?php echo esc_attr($partner_to_edit['url_structure']); ?>" required>
                            <p class="description">
                                Use placeholders:<br>
                                Basic: {destination}, {checkin}, {checkout}, {adults}, {children}<br>
                                Advanced: {rooms}, {child_ages}, {has_pets}<br>
                                Example: https://www.booking.com/searchresults.html?ss={destination}&checkin={checkin}&rooms={rooms}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <fieldset>
                                <label for="partner_active">
                                    <input type="checkbox" name="partner_active" id="partner_active" 
                                        <?php checked($partner_to_edit['active'], 1); ?>>
                                    Active
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <?php if ($edit_mode): ?>
                    <p class="submit">
                        <input type="submit" name="pricewise_edit_partner" class="button-primary" value="Update Partner">
                        <a href="<?php echo admin_url('admin.php?page=pricewise-partners'); ?>" class="button">Cancel</a>
                    </p>
                <?php else: ?>
                    <p class="submit">
                        <input type="submit" name="pricewise_add_partner" class="button-primary" value="Add Partner">
                    </p>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Sample Partner Templates -->
        <?php if (empty($partners)): ?>
        <div class="card" style="max-width: 800px; padding: 20px; margin-bottom: 20px; background-color: #f9f9f9;">
            <h3>Quick Start: Add Common Partners</h3>
            <p>Below are URL templates for common booking sites you can use as a starting point:</p>
            
            <div style="margin-bottom: 15px;">
                <strong>Booking.com</strong>
                <pre style="background: #f0f0f0; padding: 10px; overflow: auto;">https://www.booking.com/searchresults.html?ss={destination}&checkin={checkin}&checkout={checkout}&group_adults={adults}&group_children={children}&no_rooms={rooms}&age={child_ages}&pet_friendly={has_pets}</pre>
                <button class="button button-secondary copy-template" data-template="booking">Copy to Form</button>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong>Agoda</strong>
                <pre style="background: #f0f0f0; padding: 10px; overflow: auto;">https://www.agoda.com/search?city={destination}&checkin={checkin}&checkout={checkout}&adults={adults}&children={children}&childages={child_ages}&rooms={rooms}&petsok={has_pets}</pre>
                <button class="button button-secondary copy-template" data-template="agoda">Copy to Form</button>
            </div>
            
            <div style="margin-bottom: 15px;">
                <strong>Expedia</strong>
                <pre style="background: #f0f0f0; padding: 10px; overflow: auto;">https://www.expedia.com/Hotel-Search?destination={destination}&startDate={checkin}&endDate={checkout}&adults={adults}&children={children}&childrenAges={child_ages}&rooms={rooms}&petfriendly={has_pets}</pre>
                <button class="button button-secondary copy-template" data-template="expedia">Copy to Form</button>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('.copy-template').on('click', function(e) {
                    e.preventDefault();
                    var template = $(this).data('template');
                    
                    // Set values based on template
                    if(template === 'booking') {
                        $('#partner_name').val('Booking.com');
                        $('#partner_url_structure').val('https://www.booking.com/searchresults.html?ss={destination}&checkin={checkin}&checkout={checkout}&group_adults={adults}&group_children={children}&no_rooms={rooms}&age={child_ages}&pet_friendly={has_pets}');
                    } else if(template === 'agoda') {
                        $('#partner_name').val('Agoda');
                        $('#partner_url_structure').val('https://www.agoda.com/search?city={destination}&checkin={checkin}&checkout={checkout}&adults={adults}&children={children}&childages={child_ages}&rooms={rooms}&petsok={has_pets}');
                    } else if(template === 'expedia') {
                        $('#partner_name').val('Expedia');
                        $('#partner_url_structure').val('https://www.expedia.com/Hotel-Search?destination={destination}&startDate={checkin}&endDate={checkout}&adults={adults}&children={children}&childrenAges={child_ages}&rooms={rooms}&petfriendly={has_pets}');
                    }
                });
            });
            </script>
        </div>
        <?php endif; ?>
        
        <!-- Partners List -->
        <h2>Partners List</h2>
        <?php if (empty($partners)): ?>
            <p>No partners found. Add your first partner using the form above.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL Structure</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $key => $partner): ?>
                        <tr>
                            <td><?php echo esc_html($partner['name']); ?></td>
                            <td><?php echo esc_html($partner['url_structure']); ?></td>
                            <td><?php echo $partner['active'] ? '<span class="dashicons dashicons-yes" style="color:green;"></span> Active' : '<span class="dashicons dashicons-no" style="color:red;"></span> Inactive'; ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(array('action' => 'edit', 'partner' => $key)); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo add_query_arg(array('action' => 'delete', 'partner' => $key)); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Are you sure you want to delete this partner?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Debugging Info -->
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px; background-color: #f9f9f9;">
            <h3>Troubleshooting "Error fetching data" Messages</h3>
            <p>If you're seeing "Error fetching data" messages in the search results, try these steps:</p>
            <ol>
                <li>Use the <code>partner_url="yes"</code> parameter in your shortcode to see the actual URLs being requested</li>
                <li>Check if the partner URLs are correctly formed with all placeholders properly replaced</li>
                <li>Try accessing the partner URLs directly in your browser to see if they work</li>
                <li>Verify network connectivity and ensure your server can make outbound HTTP requests</li>
                <li>Some booking sites may block automated requests - try adjusting the User-Agent in the plugin code</li>
            </ol>
            <p>Example shortcode for debugging: <code>[pricewise_search partner_url="yes"]</code></p>
        </div>
    </div>
    <?php
    // Add jQuery for admin page
    wp_enqueue_script('jquery');
}

// Save partner data
function save_partner_data() {
    // Verify nonce
    if (!isset($_POST['pricewise_partners_nonce']) || !wp_verify_nonce($_POST['pricewise_partners_nonce'], 'pricewise_partners_nonce')) {
        add_settings_error('pricewise_partners', 'nonce_error', 'Security verification failed.', 'error');
        return;
    }
    
    // Get current partners
    $partners = get_option('pricewise_partners', array());
    
    // Prepare partner data
    $partner_name = isset($_POST['partner_name']) ? sanitize_text_field($_POST['partner_name']) : '';
    $partner_url_structure = isset($_POST['partner_url_structure']) ? sanitize_text_field($_POST['partner_url_structure']) : '';
    $partner_active = isset($_POST['partner_active']) ? 1 : 0;
    
    if (empty($partner_name) || empty($partner_url_structure)) {
        add_settings_error('pricewise_partners', 'empty_fields', 'Partner name and URL structure are required.', 'error');
        return;
    }
    
    // Handle edit mode
    if (isset($_POST['pricewise_edit_partner']) && isset($_POST['partner_key'])) {
        $partner_key = sanitize_key($_POST['partner_key']);
        
        // Update existing partner
        if (isset($partners[$partner_key])) {
            $partners[$partner_key] = array(
                'name' => $partner_name,
                'url_structure' => $partner_url_structure,
                'active' => $partner_active
            );
            
            update_option('pricewise_partners', $partners);
            add_settings_error('pricewise_partners', 'partner_updated', 'Partner updated successfully.', 'success');
        } else {
            add_settings_error('pricewise_partners', 'partner_not_found', 'Partner not found.', 'error');
        }
    } 
    // Handle add mode
    else if (isset($_POST['pricewise_add_partner'])) {
        // Create a key from the name
        $partner_key = sanitize_key($partner_name);
        
        // Make sure the key is unique
        if (isset($partners[$partner_key])) {
            $partner_key = $partner_key . '_' . time();
        }
        
        // Add new partner
        $partners[$partner_key] = array(
            'name' => $partner_name,
            'url_structure' => $partner_url_structure,
            'active' => $partner_active
        );
        
        update_option('pricewise_partners', $partners);
        add_settings_error('pricewise_partners', 'partner_added', 'Partner added successfully.', 'success');
    }
}

// Delete partner
function delete_partner($partner_key) {
    // Get current partners
    $partners = get_option('pricewise_partners', array());
    
    // Check if partner exists
    if (isset($partners[sanitize_key($partner_key)])) {
        // Remove the partner
        unset($partners[sanitize_key($partner_key)]);
        
        // Update the option
        update_option('pricewise_partners', $partners);
        
        // Add success message
        add_settings_error('pricewise_partners', 'partner_deleted', 'Partner deleted successfully.', 'success');
    } else {
        // Add error message
        add_settings_error('pricewise_partners', 'partner_not_found', 'Partner not found.', 'error');
    }
}

// Display settings errors
add_action('admin_notices', function() {
    settings_errors('pricewise_partners');
    settings_errors('pricewise_settings');
});