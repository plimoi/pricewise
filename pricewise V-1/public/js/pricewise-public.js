(function($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     */

    $(document).ready(function() {

        // Initialize datepickers
        const dateFormat = pricewise_data.strings.date_format || 'yy-mm-dd';
        
        // Get today's date
        const today = new Date();

        // Initialize check-in datepicker
        $('#pricewise-checkin').datepicker({
            dateFormat: dateFormat,
            minDate: 0, // Today
            onSelect: function(selectedDate) {
                // When check-in date is selected, set checkout min date to day after
                const checkInDate = $(this).datepicker('getDate');
                const checkOutMinDate = new Date(checkInDate);
                checkOutMinDate.setDate(checkOutMinDate.getDate() + 1);
                
                // Set minimum date on checkout calendar
                $('#pricewise-checkout').datepicker('option', 'minDate', checkOutMinDate);
                
                // If checkout date is less than or equal to checkin date, set it to checkin + 1
                const checkoutDate = $('#pricewise-checkout').datepicker('getDate');
                if (!checkoutDate || checkoutDate <= checkInDate) {
                    $('#pricewise-checkout').datepicker('setDate', checkOutMinDate);
                }
            }
        });

        // Initialize check-out datepicker
        $('#pricewise-checkout').datepicker({
            dateFormat: dateFormat,
            minDate: 1, // Tomorrow
        });

        // Initialize destination autocomplete
        $('#pricewise-destination').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: pricewise_data.ajax_url,
                    dataType: 'json',
                    data: {
                        action: 'pricewise_autocomplete',
                        term: request.term,
                        nonce: pricewise_data.nonce
                    },
                    success: function(data) {
                        if (data.success) {
                            response(data.data);
                        } else {
                            response([]);
                        }
                    },
                    error: function() {
                        response([]);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                // When an item is selected from autocomplete dropdown
                // Set the entity_id for API calls
                $('#pricewise-entity-id').val(ui.item.entity_id);
            }
        });

        // Handle children field change to show/hide age inputs
        $('#pricewise-children').on('change', function() {
            const childrenCount = parseInt($(this).val(), 10);
            
            // Clear any existing age fields
            $('#pricewise-children-ages-container').remove();
            
            if (childrenCount > 0) {
                // Add children ages fields
                let ageFields = `
                    <div class="pricewise-form-row pricewise-children-ages" id="pricewise-children-ages-container">
                        <h4>${pricewise_data.strings.children_ages || 'Children Ages'}</h4>
                        <div class="pricewise-ages-fields">
                `;
                
                for (let i = 0; i < childrenCount; i++) {
                    ageFields += `
                        <div class="pricewise-form-field pricewise-age-field">
                            <label for="pricewise-child-age-${i}">${pricewise_data.strings.child_label || 'Child'} ${i + 1}</label>
                            <div class="pricewise-input-wrapper">
                                <select id="pricewise-child-age-${i}" name="child_age[]" class="pricewise-child-age" data-index="${i}">
                    `;
                    
                    for (let age = 1; age <= 17; age++) {
                        // Default age of 5 for new child fields
                        ageFields += `<option value="${age}" ${age === 5 ? 'selected' : ''}>${age}</option>`;
                    }
                    
                    ageFields += `
                                </select>
                            </div>
                        </div>
                    `;
                }
                
                ageFields += `
                        </div>
                        <input type="hidden" id="pricewise-children-ages" name="children_ages" value="">
                    </div>
                `;
                
                // Insert after the row with children dropdown
                $(this).closest('.pricewise-form-row').after(ageFields);
                
                // Set up event handler for child age changes
                $('.pricewise-child-age').on('change', updateChildrenAges);
                
                // Initialize the hidden field
                updateChildrenAges();
            }
        });

        // Update children ages hidden field when ages change
        function updateChildrenAges() {
            const ages = [];
            $('.pricewise-child-age').each(function() {
                const index = $(this).data('index');
                ages[index] = $(this).val();
            });
            $('#pricewise-children-ages').val(ages.join(','));
        }

        // Handle form submission
        $('#pricewise-hotel-search').on('submit', function(e) {
            // Form is submitting normally to create the URL with query parameters
            // We just want to ensure the children ages are properly set
            if ($('#pricewise-children').val() > 0) {
                updateChildrenAges();
            }
        });

        // Handle AJAX search if needed (alternative to normal form submission)
        // This would be used if you want to load results without page refresh
        $('.pricewise-ajax-search-button').on('click', function(e) {
            e.preventDefault();
            
            const form = $('#pricewise-hotel-search');
            const errorContainer = $('#pricewise-search-error');
            const resultsContainer = $('#pricewise-search-results');
            
            // Show loading indicator
            resultsContainer.html(`<div class="pricewise-loading">${pricewise_data.strings.loading}</div>`);
            errorContainer.hide();
            
            // Get form data
            const formData = new FormData(form[0]);
            formData.append('action', 'pricewise_search');
            formData.append('nonce', pricewise_data.nonce);
            
            // Make AJAX request
            $.ajax({
                url: pricewise_data.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Handle successful response
                        resultsContainer.html(''); // Clear loading
                        
                        // Process and display results here
                        // This would typically be handled by rendering a template
                        // For simplicity, we might redirect to the search results page
                        
                        // Alternatively, update URL with search parameters without reload
                        const searchParams = new URLSearchParams();
                        for (const [key, value] of formData.entries()) {
                            if (key !== 'action' && key !== 'nonce') {
                                searchParams.append(key, value);
                            }
                        }
                        
                        const newUrl = window.location.pathname + '?' + searchParams.toString();
                        window.history.pushState({}, '', newUrl);
                        
                        // Now handle displaying results...
                    } else {
                        // Show error message
                        errorContainer.html(response.data.message).show();
                        resultsContainer.html(''); // Clear loading
                    }
                },
                error: function() {
                    // Show generic error message
                    errorContainer.html(pricewise_data.strings.search_error).show();
                    resultsContainer.html(''); // Clear loading
                }
            });
        });

        // Handle "More prices" toggle
        $('.pricewise-show-more').on('click', function() {
            const pricesList = $(this).siblings('.pricewise-other-prices-list');
            pricesList.toggle();
            
            // Update button text
            if (pricesList.is(':visible')) {
                $(this).text(pricewise_data.strings.hide_prices || 'Hide prices');
            } else {
                $(this).text(pricewise_data.strings.more_prices || 'More prices');
            }
        });

    });

})(jQuery);