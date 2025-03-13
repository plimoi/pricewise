/**
 * Form Integration JavaScript for PriceWise Plugin
 * 
 * Handles client-side form integration functionality.
 *
 * @package PriceWise
 * @since 1.3.0
 */

(function($) {
    'use strict';
    
    // PriceWise Form Integration
    var PricewiseForm = {
        
        /**
         * Initialize the form integration
         */
        init: function() {
            // Initialize form placeholders
            this.initFormPlaceholders();
            
            // Initialize dynamic form fields
            this.initDynamicFields();
            
            // Initialize form validation
            this.initFormValidation();
            
            // Initialize AJAX form submission
            this.initAjaxForms();
        },
        
        /**
         * Initialize form placeholders
         */
        initFormPlaceholders: function() {
            $('.pricewise-api-placeholder').each(function() {
                var $placeholder = $(this);
                var apiId = $placeholder.data('api');
                var endpointId = $placeholder.data('endpoint');
                var fieldPath = $placeholder.data('field');
                var useCache = $placeholder.data('cache') !== false;
                
                // Skip if required data is missing
                if (!apiId || !endpointId) {
                    return;
                }
                
                // Show loading indicator
                $placeholder.html('<span class="pricewise-loading">Loading...</span>');
                
                // Make AJAX request to get API data
                $.ajax({
                    url: pricewise_form.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_get_api_data',
                        nonce: pricewise_form.nonce,
                        api_id: apiId,
                        endpoint_id: endpointId,
                        field_path: fieldPath,
                        use_cache: useCache
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update placeholder with API data
                            if (fieldPath) {
                                $placeholder.html(response.data.field_value);
                            } else {
                                $placeholder.html('<pre>' + JSON.stringify(response.data.data, null, 2) + '</pre>');
                            }
                        } else {
                            // Show error message
                            $placeholder.html('<div class="pricewise-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        // Show error message
                        $placeholder.html('<div class="pricewise-error">Failed to load API data.</div>');
                    }
                });
            });
        },
        
        /**
         * Initialize dynamic form fields
         */
        initDynamicFields: function() {
            // Handle API selection change in forms
            $(document).on('change', '.pricewise-api-select', function() {
                var $select = $(this);
                var apiId = $select.val();
                var $form = $select.closest('form');
                var $endpointSelect = $form.find('.pricewise-endpoint-select');
                
                // Clear endpoint select options
                $endpointSelect.find('option:not([value=""])').remove();
                
                if (!apiId) {
                    return;
                }
                
                // Show loading indicator
                $endpointSelect.after('<span class="pricewise-loading-inline">Loading...</span>');
                
                // Get endpoints for selected API
                $.ajax({
                    url: pricewise_form.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_get_endpoints',
                        nonce: pricewise_form.nonce,
                        api_id: apiId
                    },
                    success: function(response) {
                        // Remove loading indicator
                        $endpointSelect.siblings('.pricewise-loading-inline').remove();
                        
                        if (response.success && response.data) {
                            // Add endpoints to select
                            $.each(response.data, function(endpoint_id, endpoint_name) {
                                $endpointSelect.append(
                                    $('<option></option>').val(endpoint_id).text(endpoint_name)
                                );
                            });
                            
                            // Enable endpoint select
                            $endpointSelect.prop('disabled', false);
                        } else {
                            // Show error
                            $endpointSelect.after('<span class="pricewise-error">Failed to load endpoints.</span>');
                        }
                    },
                    error: function() {
                        // Remove loading indicator
                        $endpointSelect.siblings('.pricewise-loading-inline').remove();
                        
                        // Show error
                        $endpointSelect.after('<span class="pricewise-error">Failed to load endpoints.</span>');
                    }
                });
            });
            
            // Handle endpoint selection change in forms
            $(document).on('change', '.pricewise-endpoint-select', function() {
                var $select = $(this);
                var endpointId = $select.val();
                var $form = $select.closest('form');
                var apiId = $form.find('.pricewise-api-select').val();
                var $paramsContainer = $form.find('.pricewise-params-container');
                
                // Clear parameters container
                $paramsContainer.empty();
                
                if (!apiId || !endpointId) {
                    return;
                }
                
                // Show loading indicator
                $paramsContainer.append('<span class="pricewise-loading-inline">Loading parameters...</span>');
                
                // Get parameters for selected endpoint
                $.ajax({
                    url: pricewise_form.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_get_endpoint_params',
                        nonce: pricewise_form.nonce,
                        api_id: apiId,
                        endpoint_id: endpointId
                    },
                    success: function(response) {
                        // Remove loading indicator
                        $paramsContainer.find('.pricewise-loading-inline').remove();
                        
                        if (response.success && response.data) {
                            // Add parameter fields to container
                            $.each(response.data, function(index, param) {
                                var $field = $('<div class="pricewise-param-field"></div>');
                                
                                $field.append(
                                    $('<label></label>').text(param.name)
                                );
                                
                                // Create input field
                                var $input = $('<input type="text">').attr({
                                    name: 'param_' + param.name,
                                    value: param.value || '',
                                    placeholder: 'Enter value or {field_name} placeholder'
                                });
                                
                                $field.append($input);
                                
                                // Add helper text if needed
                                if (param.description) {
                                    $field.append(
                                        $('<span class="pricewise-param-help"></span>').text(param.description)
                                    );
                                }
                                
                                $paramsContainer.append($field);
                            });
                        } else {
                            // Show message if no parameters
                            $paramsContainer.append('<p>No parameters for this endpoint.</p>');
                        }
                    },
                    error: function() {
                        // Remove loading indicator
                        $paramsContainer.find('.pricewise-loading-inline').remove();
                        
                        // Show error
                        $paramsContainer.append('<span class="pricewise-error">Failed to load parameters.</span>');
                    }
                });
            });
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            $(document).on('submit', '.pricewise-api-form', function(e) {
                var $form = $(this);
                var valid = true;
                
                // Validate required fields
                $form.find('[required]').each(function() {
                    var $field = $(this);
                    
                    if (!$field.val()) {
                        valid = false;
                        $field.addClass('pricewise-error-field');
                        
                        // Add error message if not already present
                        if (!$field.next('.pricewise-field-error').length) {
                            $field.after('<span class="pricewise-field-error">This field is required.</span>');
                        }
                    } else {
                        $field.removeClass('pricewise-error-field');
                        $field.next('.pricewise-field-error').remove();
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    return false;
                }
                
                return true;
            });
            
            // Clear error on field change
            $(document).on('change input', '.pricewise-error-field', function() {
                var $field = $(this);
                
                if ($field.val()) {
                    $field.removeClass('pricewise-error-field');
                    $field.next('.pricewise-field-error').remove();
                }
            });
        },
        
        /**
         * Initialize AJAX form submission
         */
        initAjaxForms: function() {
            $(document).on('submit', '.pricewise-ajax-form', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $resultsContainer = $form.siblings('.pricewise-api-results');
                var $loadingIndicator = $form.find('.pricewise-loading');
                var $submitButton = $form.find('[type="submit"]');
                
                // Disable submit button and show loading indicator
                $submitButton.prop('disabled', true);
                $loadingIndicator.show();
                
                // Prepare form data
                var formData = $form.serializeArray();
                var apiId = $form.data('api');
                var endpointId = $form.data('endpoint');
                
                // Make AJAX request
                $.ajax({
                    url: pricewise_form.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pricewise_process_form',
                        nonce: pricewise_form.nonce,
                        form_data: formData,
                        api_id: apiId,
                        endpoint_id: endpointId
                    },
                    success: function(response) {
                        // Enable submit button and hide loading indicator
                        $submitButton.prop('disabled', false);
                        $loadingIndicator.hide();
                        
                        if (response.success) {
                            // Clear form if configured to do so
                            if ($form.data('clear-on-success')) {
                                $form.find(':input').not(':button, :submit, :reset, :hidden').val('');
                            }
                            
                            // Hide form if configured to do so
                            if ($form.data('hide-on-success')) {
                                $form.hide();
                            }
                            
                            // Show success message
                            $resultsContainer.html('<div class="pricewise-success">' + (response.data.message || 'Form submitted successfully.') + '</div>');
                            
                            // Show API results if available
                            if (response.data.data) {
                                $resultsContainer.append('<div class="pricewise-api-result-data"><pre>' + JSON.stringify(response.data.data, null, 2) + '</pre></div>');
                            }
                            
                            $resultsContainer.show();
                            
                            // Trigger custom event for success
                            $form.trigger('pricewise_form_success', [response.data]);
                        } else {
                            // Show error message
                            $resultsContainer.html('<div class="pricewise-error">' + (response.data.message || 'An error occurred.') + '</div>');
                            $resultsContainer.show();
                            
                            // Trigger custom event for error
                            $form.trigger('pricewise_form_error', [response.data]);
                        }
                    },
                    error: function() {
                        // Enable submit button and hide loading indicator
                        $submitButton.prop('disabled', false);
                        $loadingIndicator.hide();
                        
                        // Show error message
                        $resultsContainer.html('<div class="pricewise-error">Failed to submit form. Please try again later.</div>');
                        $resultsContainer.show();
                        
                        // Trigger custom event for error
                        $form.trigger('pricewise_form_error', [{
                            message: 'Failed to submit form. Please try again later.'
                        }]);
                    }
                });
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        PricewiseForm.init();
    });
    
})(jQuery);