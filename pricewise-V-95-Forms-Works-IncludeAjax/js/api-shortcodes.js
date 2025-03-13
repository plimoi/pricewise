/**
 * API Shortcodes JavaScript for PriceWise Plugin
 * 
 * Handles client-side functionality for API shortcodes.
 *
 * @package PriceWise
 * @since 1.3.0
 */

(function($) {
    'use strict';
    
    // PriceWise API Shortcodes
    var PricewiseAPI = {
        
        /**
         * Initialize the API shortcodes
         */
        init: function() {
            // Initialize API data displays
            this.initApiDataDisplays();
            
            // Initialize API forms
            this.initApiForms();
            
            // Initialize AJAX refreshing
            this.initAjaxRefresh();
            
            // Initialize dynamic data loading
            this.initDynamicLoading();
        },
        
        /**
         * Initialize API data displays
         */
        initApiDataDisplays: function() {
            $('.pricewise-api-display[data-loading="true"]').each(function() {
                var $display = $(this);
                
                // Get API data via AJAX
                PricewiseAPI.loadApiData($display);
            });
        },
        
        /**
         * Initialize API forms
         */
        initApiForms: function() {
            // Handle form submission
            $(document).on('submit', '.pricewise-api-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $resultsContainer = $form.siblings('.pricewise-api-results');
                var $loadingElement = $form.find('.pricewise-loading');
                var $submitButton = $form.find('button[type="submit"]');
                
                // Disable submit button and show loading indicator
                $submitButton.prop('disabled', true);
                $loadingElement.show();
                
                // Serialize form data
                var formData = $form.serializeArray();
                var apiId = $form.data('api');
                var endpointId = $form.data('endpoint');
                var redirectUrl = $form.data('redirect');
                
                // Make AJAX request
                $.ajax({
                    url: pricewise_api.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_process_form',
                        nonce: pricewise_api.nonce,
                        form_data: formData,
                        api_id: apiId,
                        endpoint_id: endpointId
                    },
                    success: function(response) {
                        // Re-enable submit button and hide loading indicator
                        $submitButton.prop('disabled', false);
                        $loadingElement.hide();
                        
                        if (response.success) {
                            // Handle successful response
                            if (redirectUrl) {
                                // Redirect if URL is provided
                                window.location.href = redirectUrl;
                            } else {
                                // Display results
                                var resultTemplate = $resultsContainer.data('template');
                                
                                if (resultTemplate) {
                                    // Use custom template
                                    var resultHtml = resultTemplate;
                                    
                                    // Replace data placeholders
                                    if (response.data && response.data.data) {
                                        resultHtml = PricewiseAPI.replaceDataPlaceholders(resultHtml, response.data.data);
                                    }
                                    
                                    $resultsContainer.html(resultHtml);
                                } else {
                                    // Use default template
                                    var resultHtml = '<div class="pricewise-api-success">';
                                    resultHtml += '<h3>API Response:</h3>';
                                    resultHtml += '<pre>' + JSON.stringify(response.data.data, null, 2) + '</pre>';
                                    resultHtml += '</div>';
                                    
                                    $resultsContainer.html(resultHtml);
                                }
                                
                                $resultsContainer.show();
                                
                                // Trigger custom event
                                $form.trigger('pricewise_api_success', [response.data]);
                            }
                        } else {
                            // Handle error response
                            var errorHtml = '<div class="pricewise-api-error">';
                            errorHtml += '<p>Error: ' + (response.data ? response.data.message : 'Unknown error') + '</p>';
                            errorHtml += '</div>';
                            
                            $resultsContainer.html(errorHtml).show();
                            
                            // Trigger custom event
                            $form.trigger('pricewise_api_error', [response.data]);
                        }
                    },
                    error: function() {
                        // Re-enable submit button and hide loading indicator
                        $submitButton.prop('disabled', false);
                        $loadingElement.hide();
                        
                        // Display error message
                        var errorHtml = '<div class="pricewise-api-error">';
                        errorHtml += '<p>Error: Could not connect to the server. Please try again later.</p>';
                        errorHtml += '</div>';
                        
                        $resultsContainer.html(errorHtml).show();
                        
                        // Trigger custom event
                        $form.trigger('pricewise_api_error', [{
                            message: 'Could not connect to the server. Please try again later.'
                        }]);
                    }
                });
            });
        },
        
        /**
         * Initialize AJAX refreshing
         */
        initAjaxRefresh: function() {
            // Set up interval refreshing for elements with a refresh interval
            $('.pricewise-api-display[data-interval]').each(function() {
                var $display = $(this);
                var interval = parseInt($display.data('interval'), 10);
                
                if (interval > 0) {
                    // Set interval for refreshing
                    setInterval(function() {
                        PricewiseAPI.loadApiData($display);
                    }, interval * 1000);
                }
            });
        },
        
        /**
         * Initialize dynamic data loading
         */
        initDynamicLoading: function() {
            // Handle dynamic API data loading triggered by events
            $(document).on('pricewise_load_api_data', function(e, options) {
                var defaults = {
                    selector: null,
                    api: null,
                    endpoint: null,
                    params: {},
                    template: null,
                    callback: null
                };
                
                var settings = $.extend({}, defaults, options);
                
                if (!settings.selector) {
                    console.error('No selector provided for API data loading');
                    return;
                }
                
                var $target = $(settings.selector);
                
                if (!$target.length) {
                    console.error('Target element not found for API data loading');
                    return;
                }
                
                if (!settings.api || !settings.endpoint) {
                    console.error('API ID and Endpoint ID are required for API data loading');
                    return;
                }
                
                // Show loading indicator
                $target.html('<div class="pricewise-loading">Loading...</div>');
                
                // Make AJAX request
                $.ajax({
                    url: pricewise_api.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_get_api_data',
                        nonce: pricewise_api.nonce,
                        api_id: settings.api,
                        endpoint_id: settings.endpoint,
                        params: settings.params
                    },
                    success: function(response) {
                        if (response.success) {
                            var content = '';
                            
                            if (settings.template) {
                                // Use custom template
                                content = PricewiseAPI.replaceDataPlaceholders(settings.template, response.data.data);
                            } else {
                                // Use default template
                                content = '<pre>' + JSON.stringify(response.data.data, null, 2) + '</pre>';
                            }
                            
                            $target.html(content);
                            
                            // Call callback if provided
                            if (typeof settings.callback === 'function') {
                                settings.callback(response.data.data, $target);
                            }
                        } else {
                            // Show error message
                            $target.html('<div class="pricewise-api-error">' + (response.data ? response.data.message : 'Unknown error') + '</div>');
                        }
                    },
                    error: function() {
                        // Show error message
                        $target.html('<div class="pricewise-api-error">Could not connect to the server. Please try again later.</div>');
                    }
                });
            });
        },
        
        /**
         * Load API data for a display element
         *
         * @param {jQuery} $display The display element
         */
        loadApiData: function($display) {
            var apiId = $display.data('api');
            var endpointId = $display.data('endpoint');
            var useCache = $display.data('cache') === 'yes';
            var paramsStr = $display.data('params');
            var template = $display.data('template');
            
            // Parse parameters
            var params = {};
            if (paramsStr) {
                var paramPairs = paramsStr.split(',');
                paramPairs.forEach(function(pair) {
                    pair = pair.trim();
                    if (pair.indexOf('=') !== -1) {
                        var keyValue = pair.split('=');
                        params[keyValue[0].trim()] = keyValue[1].trim();
                    }
                });
            }
            
            // Make AJAX request
            $.ajax({
                url: pricewise_api.ajax_url,
                type: 'POST',
                data: {
                    action: 'pricewise_get_api_data',
                    nonce: pricewise_api.nonce,
                    api_id: apiId,
                    endpoint_id: endpointId,
                    use_cache: useCache,
                    params: params
                },
                success: function(response) {
                    if (response.success) {
                        // Update display element
                        $display.data('loading', false);
                        
                        if (template) {
                            // Use custom template
                            var html = template;
                            
                            // Replace data placeholders
                            if (response.data && response.data.data) {
                                html = PricewiseAPI.replaceDataPlaceholders(html, response.data.data);
                            }
                            
                            $display.html(html);
                        } else {
                            // Use default template
                            var html = '<pre>' + JSON.stringify(response.data.data, null, 2) + '</pre>';
                            $display.html(html);
                        }
                        
                        // Trigger custom event
                        $display.trigger('pricewise_api_data_loaded', [response.data.data]);
                    } else {
                        // Display error message
                        var errorHtml = '<div class="pricewise-api-error">';
                        errorHtml += '<p>Error: ' + (response.data ? response.data.message : 'Unknown error') + '</p>';
                        errorHtml += '</div>';
                        
                        $display.html(errorHtml);
                    }
                },
                error: function() {
                    // Display error message
                    var errorHtml = '<div class="pricewise-api-error">';
                    errorHtml += '<p>Error: Could not connect to the server. Please try again later.</p>';
                    errorHtml += '</div>';
                    
                    $display.html(errorHtml);
                }
            });
        },
        
        /**
         * Replace data placeholders in a template
         *
         * @param {string} template The template
         * @param {object} data The data
         * @param {string} prefix The prefix for nested data
         * @return {string} The template with placeholders replaced
         */
        replaceDataPlaceholders: function(template, data, prefix) {
            prefix = prefix || 'data';
            
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var placeholder = '{{' + prefix + '.' + key + '}}';
                    var value = data[key];
                    
                    if (typeof value === 'object' && value !== null) {
                        // Recursively process nested objects
                        template = PricewiseAPI.replaceDataPlaceholders(template, value, prefix + '.' + key);
                        
                        // Also replace the object itself (as JSON)
                        template = template.replace(new RegExp(placeholder, 'g'), JSON.stringify(value));
                    } else {
                        // Replace simple value
                        template = template.replace(new RegExp(placeholder, 'g'), value);
                    }
                }
            }
            
            return template;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        PricewiseAPI.init();
    });
    
})(jQuery);