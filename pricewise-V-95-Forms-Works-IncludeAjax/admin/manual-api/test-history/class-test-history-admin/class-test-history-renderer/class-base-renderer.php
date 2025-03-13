<?php
/**
 * Test History Base Renderer Class
 * Provides base functionality for all renderer classes
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Pricewise_Test_History_Base_Renderer {
    /**
     * Database handler
     *
     * @var Pricewise_Test_History_DB
     */
    protected $db;

    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Enqueue scripts and styles
     *
     * @param string $hook Current admin page
     */
    public function enqueue_scripts($hook) {
        if ($hook != 'pricewise_page_pricewise-test-history') {
            return;
        }

        // Add datepicker for date filtering
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // Add custom styles
        wp_add_inline_style('jquery-ui', $this->get_custom_css());
        
        // Localize script for AJAX
        wp_localize_script('jquery', 'pricewise_test_history', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pricewise_manual_data_fields')
        ));
        
        // Add inline script for handling field visibility and Data Screen Options
        wp_add_inline_script('jquery', $this->get_custom_js());
    }

    /**
     * Get custom CSS for the test history page
     * 
     * @return string CSS rules
     */
    protected function get_custom_css() {
        return '
            .pricewise-history-filters {
                margin: 10px 0;
                padding: 10px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
            }
            .pricewise-history-filters .form-row {
                margin-bottom: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                align-items: center;
            }
            .pricewise-history-filters .form-row label {
                min-width: 100px;
            }
            .status-success {
                color: green;
            }
            .status-error {
                color: red;
            }
            .status-warning {
                color: orange;
            }
            .pricewise-history-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
            }
            .pricewise-history-stats .stat-box {
                background: #fff;
                padding: 10px;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                flex: 1;
                min-width: 150px;
                text-align: center;
            }
            .pricewise-history-stats .stat-box h3 {
                margin-top: 0;
            }
            .pricewise-history-stats .stat-box .stat-value {
                font-size: 24px;
                font-weight: bold;
            }
            .pricewise-history-detail {
                margin-top: 20px;
            }
            .pricewise-history-detail .section {
                margin-bottom: 20px;
            }
            .pricewise-history-detail h3 {
                margin-top: 0;
                padding-bottom: 5px;
                border-bottom: 1px solid #e5e5e5;
            }
            .pricewise-history-detail pre {
                background: #f9f9f9;
                padding: 10px;
                overflow: auto;
                max-height: 300px;
                border: 1px solid #e5e5e5;
            }
            .pricewise-history-detail .response-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 10px;
            }
            .pricewise-history-detail .meta-item {
                min-width: 150px;
            }
            .pricewise-history-detail .meta-item .label {
                font-weight: bold;
                margin-bottom: 5px;
            }
            /* Data Screen Options style UI */
            .pricewise-data-screen-options {
                float: right;
                display: inline-block;
                position: relative;
                margin-top: -38px;
            }
            .pricewise-data-screen-options .button {
                padding: 4px 8px;
                position: relative;
                top: -3px;
                text-decoration: none;
                border: 1px solid #ddd;
                border-radius: 2px;
                background: #f7f7f7;
                text-shadow: none;
                font-weight: 600;
                font-size: 13px;
                line-height: normal;
                color: #0073aa;
                cursor: pointer;
                outline: 0;
            }
            .pricewise-data-screen-options .button:hover {
                border-color: #999;
                background: #f5f5f5;
            }
            .pricewise-data-screen-options-panel {
                display: none;
                position: absolute;
                top: 36px;
                right: 0;
                padding: 15px;
                background: #fff;
                box-shadow: 0 3px 5px rgba(0,0,0,.2);
                border: 1px solid #ccd0d4;
                border-radius: 2px;
                z-index: 10;
                min-width: 270px;
                width: max-content;
                max-width: 400px;
            }
            .pricewise-manual-data-list {
                margin: 0;
                padding: 0;
            }
            .pricewise-manual-data-list h3 {
                margin-top: 0;
                margin-bottom: 10px;
                display: block;
                font-size: 14px;
                font-weight: 600;
                padding-bottom: 5px;
                border-bottom: 1px solid #eee;
            }
            .pricewise-manual-data-content {
                padding-top: 0;
            }
            .pricewise-manual-data-list .manual-data-section {
                margin-bottom: 15px;
            }
            .pricewise-manual-data-list .manual-data-section h4 {
                margin: 8px 0;
                font-size: 13px;
                font-weight: 600;
            }
            .pricewise-manual-data-list .checkbox-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
                margin-top: 5px;
            }
            .pricewise-manual-data-list .checkbox-group label {
                display: flex;
                align-items: center;
                font-size: 13px;
            }
            .pricewise-manual-data-list .checkbox-group input[type="checkbox"] {
                margin-right: 8px;
            }
            
            /* Dropdown selector styles */
            .param-selector-container,
            .header-selector-container {
                margin-top: 10px;
            }
            
            .param-selector-wrapper,
            .header-selector-wrapper {
                position: relative;
                display: inline-block;
                width: 100%;
                max-width: 350px;
            }
            
            .param-search,
            .header-search {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            
            .param-dropdown-content,
            .header-dropdown-content {
                display: none;
                position: absolute;
                background-color: #f9f9f9;
                min-width: 350px;
                max-height: 250px;
                overflow-y: auto;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
                border-radius: 4px;
                margin-top: 2px;
            }
            
            .param-dropdown-content.show,
            .header-dropdown-content.show {
                display: block;
            }
            
            .param-dropdown-item,
            .header-dropdown-item {
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
            }
            
            .param-dropdown-item:hover,
            .header-dropdown-item:hover {
                background-color: #f1f1f1;
            }
            
            .param-dropdown-item label,
            .header-dropdown-item label {
                display: block;
                cursor: pointer;
            }
            
            .selected-params,
            .selected-headers {
                margin-top: 10px;
            }
            
            .selected-params-header,
            .selected-headers-header {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .selected-params-list,
            .selected-headers-list {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .selected-param-tag,
            .selected-header-tag {
                display: inline-block;
                padding: 4px 8px;
                margin: 2px;
                background-color: #e1f5fe;
                border-radius: 4px;
                border: 1px solid #b3e5fc;
                font-size: 13px;
                position: relative;
            }
            
            .remove-param,
            .remove-header {
                margin-left: 5px;
                cursor: pointer;
                font-weight: bold;
                color: #555;
            }
            
            .remove-param:hover,
            .remove-header:hover {
                color: #f00;
            }
            
            .no-params-selected,
            .no-headers-selected {
                color: #888;
                font-style: italic;
            }
            
            #manual-data-message {
                margin-top: 10px;
                margin-bottom: 10px;
            }
        ';
    }

    /**
     * Get custom JavaScript for the test history page
     * 
     * @return string JavaScript code
     */
    protected function get_custom_js() {
        return '
            jQuery(document).ready(function($) {
                // Initialize datepicker
                $(".datepicker").datepicker({
                    dateFormat: "yy-mm-dd",
                    changeMonth: true,
                    changeYear: true
                });
                
                // Data Screen Options toggle
                $("#pricewise-data-screen-options-toggle").on("click", function(e) {
                    e.preventDefault();
                    $(".pricewise-data-screen-options-panel").slideToggle(300);
                });
                
                // Close Data Screen Options when clicking outside
                $(document).on("click", function(e) {
                    if (!$(e.target).closest(".pricewise-data-screen-options").length) {
                        $(".pricewise-data-screen-options-panel").slideUp(300);
                    }
                });
                
                // Parameter dropdown functionality
                var paramSearch = $("#param-search");
                var paramDropdown = $("#param-dropdown-content");
                var paramItems = $(".param-dropdown-item");
                var selectedParamsList = $("#selected-params-list");
                
                // Show dropdown when clicking on search input
                paramSearch.on("click focus", function() {
                    paramDropdown.addClass("show");
                });
                
                // Filter parameters on search input
                paramSearch.on("input", function() {
                    var searchText = $(this).val().toLowerCase();
                    
                    // Only start filtering after 2 characters
                    if (searchText.length >= 2) {
                        paramItems.each(function() {
                            var paramText = $(this).text().toLowerCase();
                            if (paramText.indexOf(searchText) > -1) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    } else {
                        paramItems.show();
                    }
                });
                
                // Close dropdown when clicking outside
                $(document).on("click", function(e) {
                    if (!$(e.target).closest(".param-selector-wrapper").length) {
                        paramDropdown.removeClass("show");
                    }
                });
                
                // Handle parameter checkbox changes
                $(document).on("change", ".param-dropdown-item input[type=checkbox]", function() {
                    var paramName = $(this).val();
                    
                    if ($(this).is(":checked")) {
                        // Add tag if it doesn\'t exist
                        if (selectedParamsList.find(\'[data-param="\' + paramName + \'"]\').length === 0) {
                            $(".no-params-selected").remove();
                            selectedParamsList.append(
                                \'<span class="selected-param-tag" data-param="\' + paramName + \'">\' + 
                                paramName + 
                                \'<span class="remove-param">×</span></span>\'
                            );
                        }
                    } else {
                        // Remove tag
                        selectedParamsList.find(\'[data-param="\' + paramName + \'"]\').remove();
                        
                        // Show "No parameters selected" if none are selected
                        if (selectedParamsList.children().length === 0) {
                            selectedParamsList.html(\'<span class="no-params-selected">No parameters selected</span>\');
                        }
                    }
                });
                
                // Remove parameter when clicking X
                $(document).on("click", ".remove-param", function() {
                    var tag = $(this).parent();
                    var paramName = tag.data("param");
                    
                    // Uncheck the corresponding checkbox
                    $(\'input[name="param_field[\' + paramName + \']"]\').prop("checked", false);
                    
                    // Remove the tag
                    tag.remove();
                    
                    // Show "No parameters selected" if none are selected
                    if (selectedParamsList.children().length === 0) {
                        selectedParamsList.html(\'<span class="no-params-selected">No parameters selected</span>\');
                    }
                });
                
                // Header dropdown functionality
                var headerSearch = $("#header-search");
                var headerDropdown = $("#header-dropdown-content");
                var headerItems = $(".header-dropdown-item");
                var selectedHeadersList = $("#selected-headers-list");
                
                // Show dropdown when clicking on search input
                headerSearch.on("click focus", function() {
                    headerDropdown.addClass("show");
                });
                
                // Filter headers on search input
                headerSearch.on("input", function() {
                    var searchText = $(this).val().toLowerCase();
                    
                    // Only start filtering after 2 characters
                    if (searchText.length >= 2) {
                        headerItems.each(function() {
                            var headerText = $(this).text().toLowerCase();
                            if (headerText.indexOf(searchText) > -1) {
                                $(this).show();
                            } else {
                                $(this).hide();
                            }
                        });
                    } else {
                        headerItems.show();
                    }
                });
                
                // Close dropdown when clicking outside
                $(document).on("click", function(e) {
                    if (!$(e.target).closest(".header-selector-wrapper").length) {
                        headerDropdown.removeClass("show");
                    }
                });
                
                // Handle header checkbox changes
                $(document).on("change", ".header-dropdown-item input[type=checkbox]", function() {
                    var headerName = $(this).val();
                    
                    if ($(this).is(":checked")) {
                        // Add tag if it doesn\'t exist
                        if (selectedHeadersList.find(\'[data-header="\' + headerName + \'"]\').length === 0) {
                            $(".no-headers-selected").remove();
                            selectedHeadersList.append(
                                \'<span class="selected-header-tag" data-header="\' + headerName + \'">\' + 
                                headerName + 
                                \'<span class="remove-header">×</span></span>\'
                            );
                        }
                    } else {
                        // Remove tag
                        selectedHeadersList.find(\'[data-header="\' + headerName + \'"]\').remove();
                        
                        // Show "No headers selected" if none are selected
                        if (selectedHeadersList.children().length === 0) {
                            selectedHeadersList.html(\'<span class="no-headers-selected">No headers selected</span>\');
                        }
                    }
                });
                
                // Remove header when clicking X
                $(document).on("click", ".remove-header", function() {
                    var tag = $(this).parent();
                    var headerName = tag.data("header");
                    
                    // Uncheck the corresponding checkbox
                    $(\'input[name="header_field[\' + headerName + \']"]\').prop("checked", false);
                    
                    // Remove the tag
                    tag.remove();
                    
                    // Show "No headers selected" if none are selected
                    if (selectedHeadersList.children().length === 0) {
                        selectedHeadersList.html(\'<span class="no-headers-selected">No headers selected</span>\');
                    }
                });
                
                // Handle saving manual data fields
                $("#save-manual-data-fields").on("click", function() {
                    // Enable the actual AJAX functionality instead of "Coming soon" message
                    var defaultFields = {};
                    $("input[name^=\'default_field\']:checked").each(function() {
                        defaultFields[$(this).val()] = 1;
                    });
                    
                    var paramFields = {};
                    $("input[name^=\'param_field\']:checked").each(function() {
                        paramFields[$(this).val()] = 1;
                    });
                    
                    var headerFields = {};
                    $("input[name^=\'header_field\']:checked").each(function() {
                        headerFields[$(this).val()] = 1;
                    });
                    
                    // Show loading message
                    $("#manual-data-message").html("<div class=\'notice notice-info inline\'><p>Saving preferences...</p></div>").show();
                    
                    // Send AJAX request
                    $.ajax({
                        url: pricewise_test_history.ajax_url,
                        type: "POST",
                        data: {
                            action: "pricewise_save_manual_data_fields",
                            nonce: pricewise_test_history.nonce,
                            default_fields: defaultFields,
                            param_fields: paramFields,
                            header_fields: headerFields
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#manual-data-message").html("<div class=\'notice notice-success inline\'><p>" + response.data.message + "</p></div>").show().delay(3000).fadeOut();
                                
                                // Apply column visibility immediately
                                location.reload();
                            } else {
                                $("#manual-data-message").html("<div class=\'notice notice-error inline\'><p>Error: " + response.data.message + "</p></div>").show();
                            }
                        },
                        error: function() {
                            $("#manual-data-message").html("<div class=\'notice notice-error inline\'><p>An error occurred while saving your preferences.</p></div>").show();
                        }
                    });
                });
            });
        ';
    }

    /**
     * Get user column preferences
     * 
     * @return array User preferences for columns
     */
    protected function get_user_column_preferences() {
        $user_id = get_current_user_id();
        $saved_default_fields = get_user_meta($user_id, 'pricewise_manual_data_default_fields', true);
        $saved_param_fields = get_user_meta($user_id, 'pricewise_manual_data_param_fields', true);
        $saved_header_fields = get_user_meta($user_id, 'pricewise_manual_data_header_fields', true);
        
        if (!is_array($saved_default_fields)) {
            $saved_default_fields = array();
        }
        
        if (!is_array($saved_param_fields)) {
            $saved_param_fields = array();
        }
        
        if (!is_array($saved_header_fields)) {
            $saved_header_fields = array();
        }
        
        // Default all columns to visible if no preferences have been saved
        $show_all_columns = empty($saved_default_fields) && empty($saved_param_fields) && empty($saved_header_fields);
        
        return array(
            'default_fields' => $saved_default_fields,
            'param_fields' => $saved_param_fields,
            'header_fields' => $saved_header_fields,
            'show_all_columns' => $show_all_columns
        );
    }
}