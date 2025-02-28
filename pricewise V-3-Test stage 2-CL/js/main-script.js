jQuery(document).ready(function($) {
    var $destinationInput = $('#destination');

    // Create a container for suggestions
    var $suggestionsBox = $('<div id="destination-suggestions"></div>').css({
        'position': 'absolute',
        'border': '1px solid #ccc',
        'background': '#fff',
        'z-index': '9999',
        'width': $destinationInput.outerWidth()
    }).hide();

    // Place it immediately after the input
    $destinationInput.after($suggestionsBox);

    // When user types in the 'destination' field
    $destinationInput.on('input', function() {
        var query = $(this).val().trim();

        if (query.length < 2) {
            $suggestionsBox.hide();
            return;
        }

        $.ajax({
            url: pricewise_ajax_obj.ajax_url,
            method: 'GET',
            data: {
                action: 'pricewise_search_destination',
                query: query
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    if (data && data.length > 0) {
                        var html = '<ul style="list-style-type:none; margin:0; padding:0;">';
                        data.forEach(function(item) {
                            var placeName = item.place_name || 'Unknown';
                            html += '<li class="suggestion-item" style="padding:5px; cursor:pointer;" data-value="'+ item.place_id +'">'+ placeName +'</li>';
                        });
                        html += '</ul>';
                        $suggestionsBox.html(html).show();
                    } else {
                        $suggestionsBox.html('<div style="padding:5px;">No results found</div>').show();
                    }
                } else {
                    $suggestionsBox.html('<div style="padding:5px;">Error: '+ response.data +'</div>').show();
                }
            },
            error: function() {
                $suggestionsBox.html('<div style="padding:5px;">Request failed</div>').show();
            }
        });
    });

    // When user clicks on a suggestion
    $suggestionsBox.on('click', '.suggestion-item', function() {
        var placeName = $(this).text();
        // We could store the ID in a hidden field as well:
        // var placeId = $(this).data('value');
        $destinationInput.val(placeName);
        $suggestionsBox.hide();
    });

    // Click outside to hide suggestions
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#destination, #destination-suggestions').length) {
            $suggestionsBox.hide();
        }
    });
});
