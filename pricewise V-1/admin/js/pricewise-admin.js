(function($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     */

    $(document).ready(function() {

        // Initialize color picker
        $('.pricewise-color-picker').wpColorPicker();

        // Cache expiry presets
        $('.pricewise-preset').on('click', function(e) {
            e.preventDefault();
            const value = $(this).data('value');
            $('#pricewise_cache_expiry').val(value);
        });

        // Clear cache button
        $('#pricewise-clear-cache').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const resultContainer = $('#pricewise-cache-clear-result');

            // Disable button during request
            button.prop('disabled', true);
            resultContainer.html('<span class="spinner is-active"></span>');

            // Make AJAX request
            $.ajax({
                url: pricewise_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pricewise_clear_cache',
                    nonce: pricewise_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.html('<span class="pricewise-success">' + response.data.message + '</span>');
                    } else {
                        resultContainer.html('<span class="pricewise-error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    resultContainer.html('<span class="pricewise-error">' + pricewise_admin.strings.cache_error + '</span>');
                },
                complete: function() {
                    button.prop('disabled', false);
                    
                    // Clear message after 3 seconds
                    setTimeout(function() {
                        resultContainer.html('');
                    }, 3000);
                }
            });
        });

        // Test API connection button
        $('#pricewise-test-api').on('click', function(e) {
            e.preventDefault();
            const button = $(this);
            const resultContainer = $('#pricewise-api-test-result');

            // Check if API key field is empty
            const apiKey = $('#pricewise_rapidapi_key').val();
            if (!apiKey) {
                resultContainer.html('<span class="pricewise-error">' + pricewise_admin.strings.api_error + '</span>');
                return;
            }

            // Disable button during request
            button.prop('disabled', true);
            resultContainer.html('<span class="spinner is-active"></span>');

            // Make AJAX request
            $.ajax({
                url: pricewise_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pricewise_test_api',
                    nonce: pricewise_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.html('<span class="pricewise-success">' + response.data.message + '</span>');
                    } else {
                        resultContainer.html('<span class="pricewise-error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    resultContainer.html('<span class="pricewise-error">' + pricewise_admin.strings.api_error + '</span>');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });

    });

})(jQuery);