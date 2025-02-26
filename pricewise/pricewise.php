<?php
/*
Plugin Name: Pricewise
Plugin URI: http://example.com/pricewise
Description: A hotel price comparison plugin that fetches and compares hotel prices from multiple booking sites.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PRICEWISE_VERSION', '1.0');
define('PRICEWISE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRICEWISE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include admin functions
require_once PRICEWISE_PLUGIN_DIR . 'admin/admin-page.php';

// Register the shortcode to display the search form
add_shortcode('pricewise_search', 'pricewise_search_shortcode');

function pricewise_search_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(
        array(
            'partner_url' => 'no', // Default is not to show partner URLs
        ),
        $atts,
        'pricewise_search'
    );
    
    // Convert attribute to boolean for easier use
    $show_partner_urls = ($atts['partner_url'] === 'yes' || $atts['partner_url'] === 'true' || $atts['partner_url'] === '1');
    
    ob_start();
    ?>
    <div class="pricewise-search-form">
        <form method="post" id="pricewise-form">
            <div class="form-row">
                <label>Destination: <input type="text" name="destination" required></label>
            </div>
            <div class="form-row">
                <label>Check-in Date: <input type="date" name="checkin" required></label>
            </div>
            <div class="form-row">
                <label>Check-out Date: <input type="date" name="checkout" required></label>
            </div>
            <div class="form-row">
                <label>Adults: <input type="number" name="adults" value="2" min="1" required></label>
            </div>
            <div class="form-row">
                <label>Children: <input type="number" name="children" value="0" min="0" id="children-count" required></label>
            </div>
            
            <!-- Child ages container - will be populated dynamically -->
            <div id="child-ages-container" style="display: none;">
                <h4>Child Ages</h4>
                <div id="child-ages-fields"></div>
            </div>
            
            <div class="form-row">
                <label>Rooms: <input type="number" name="rooms" value="1" min="1" required></label>
            </div>
            <div class="form-row">
                <label>
                    <input type="checkbox" name="pets" value="1"> Pets allowed
                </label>
            </div>
            
            <!-- Hidden field to pass the show_partner_urls parameter -->
            <input type="hidden" name="show_partner_urls" value="<?php echo $show_partner_urls ? '1' : '0'; ?>">
            
            <input type="submit" name="pricewise_search_submit" value="Search">
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const childrenInput = document.getElementById('children-count');
        const childAgesContainer = document.getElementById('child-ages-container');
        const childAgesFields = document.getElementById('child-ages-fields');
        
        // Handle change in children count
        childrenInput.addEventListener('change', function() {
            const childCount = parseInt(this.value);
            
            // Clear previous fields
            childAgesFields.innerHTML = '';
            
            if (childCount > 0) {
                childAgesContainer.style.display = 'block';
                
                // Create fields for each child
                for (let i = 0; i < childCount; i++) {
                    const ageField = document.createElement('div');
                    ageField.className = 'child-age-field';
                    ageField.innerHTML = `
                        <label>Child ${i+1} Age: 
                            <select name="child_age[]" required>
                                <option value="">Select Age</option>
                                ${Array.from({length: 18}, (_, j) => `<option value="${j}">${j} ${j == 1 ? 'year' : 'years'}</option>`).join('')}
                            </select>
                        </label>
                    `;
                    childAgesFields.appendChild(ageField);
                }
            } else {
                childAgesContainer.style.display = 'none';
            }
        });
    });
    </script>
    
    <?php
    if (isset($_POST['pricewise_search_submit'])) {
        // Sanitize and retrieve user inputs
        $destination = sanitize_text_field($_POST['destination']);
        $checkin = sanitize_text_field($_POST['checkin']);
        $checkout = sanitize_text_field($_POST['checkout']);
        $adults = intval($_POST['adults']);
        $children = intval($_POST['children']);
        $rooms = intval($_POST['rooms']);
        $pets = isset($_POST['pets']) ? 1 : 0;
        $show_partner_urls = isset($_POST['show_partner_urls']) ? intval($_POST['show_partner_urls']) : 0;
        
        // Process child ages if children are specified
        $child_ages = array();
        if ($children > 0 && isset($_POST['child_age']) && is_array($_POST['child_age'])) {
            foreach ($_POST['child_age'] as $age) {
                $child_ages[] = intval($age);
            }
        }

        echo '<h3>Search Results for ' . esc_html($destination) . '</h3>';
        
        // Display search parameters summary
        echo '<div class="pricewise-search-summary">';
        echo '<p><strong>Check-in:</strong> ' . esc_html($checkin) . ' | <strong>Check-out:</strong> ' . esc_html($checkout) . '</p>';
        echo '<p><strong>Adults:</strong> ' . esc_html($adults) . ' | <strong>Children:</strong> ' . esc_html($children) . '</p>';
        
        if (!empty($child_ages)) {
            echo '<p><strong>Child Ages:</strong> ' . esc_html(implode(', ', $child_ages)) . '</p>';
        }
        
        echo '<p><strong>Rooms:</strong> ' . esc_html($rooms) . ' | <strong>Pets:</strong> ' . ($pets ? 'Yes' : 'No') . '</p>';
        echo '</div>';

        // Get active partners
        $partners = get_option('pricewise_partners', array());
        $results = array();
        $partner_urls = array(); // Store partner URLs for debugging
        
        // Check if we have any partners
        if (empty($partners)) {
            echo '<div class="pricewise-notice pricewise-error">';
            echo '<p>No booking partners are configured. Please add partners via the Pricewise admin page.</p>';
            if (current_user_can('manage_options')) {
                echo '<p><a href="' . admin_url('admin.php?page=pricewise-partners') . '" class="button">Add Partners</a></p>';
            }
            echo '</div>';
            return ob_get_clean();
        }
        
        // Process each active partner
        foreach ($partners as $key => $partner) {
            if (!empty($partner['active'])) {
                // Generate URL based on the partner's URL structure
                $url = generate_partner_url($partner['url_structure'], $destination, $checkin, $checkout, $adults, $children, $child_ages, $rooms, $pets);
                
                // Store URL for debugging
                $partner_urls[$partner['name']] = $url;
                
                // Fetch and parse results
                $partner_results = fetch_and_parse_results($url, $key, $partner['name']);
                
                // Add to combined results
                $results[$partner['name']] = $partner_results;
            }
        }

        // Display partner URLs if enabled
        if ($show_partner_urls) {
            echo '<div class="pricewise-debug-info">';
            echo '<h4>Debug Information - Partner URLs</h4>';
            echo '<ul>';
            foreach ($partner_urls as $partner_name => $url) {
                echo '<li><strong>' . esc_html($partner_name) . ':</strong> <a href="' . esc_url($url) . '" target="_blank">' . esc_url($url) . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        // Merge the results from all sources
        $combined_results = merge_results($results);

        // Display the results in a comparison table
        display_results_table($combined_results);
    }
    return ob_get_clean();
}

// Generate URL for a partner based on the URL structure and search parameters
function generate_partner_url($url_structure, $destination, $checkin, $checkout, $adults, $children, $child_ages = array(), $rooms = 1, $pets = 0) {
    // Create child ages string format
    $child_ages_str = implode(',', $child_ages);
    
    $replacements = array(
        '{destination}' => urlencode($destination),
        '{checkin}' => $checkin,
        '{checkout}' => $checkout,
        '{adults}' => $adults,
        '{children}' => $children,
        '{child_ages}' => urlencode($child_ages_str),
        '{rooms}' => $rooms,
        '{pets}' => $pets
    );
    
    return str_replace(array_keys($replacements), array_values($replacements), $url_structure);
}

// Function to fetch HTML content using cURL
function fetch_html($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Set a browser-like user agent to reduce risk of blocking
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PricewiseBot/1.0; +http://example.com)');
    
    // Add additional cURL options to help with debugging
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout in seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout in seconds for the whole operation
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum number of redirects to follow
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Don't verify SSL certificates (not recommended for production)
    
    $html = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        error_log("Pricewise cURL error for URL {$url}: " . $error);
        return false;
    }
    
    if ($http_code != 200) {
        error_log("Pricewise HTTP error for URL {$url}: HTTP code {$http_code}");
        return false;
    }
    
    return $html;
}

// Wrapper function to fetch HTML and then parse results based on the source
function fetch_and_parse_results($url, $partner_key, $partner_name) {
    $html = fetch_html($url);
    if (!$html) {
        // Return empty result set with error message
        return array(
            array(
                'name' => 'Error fetching data',
                'price' => 'N/A',
                'image' => '',
                'rating' => 'N/A',
                'source' => $partner_name,
                'url' => $url // Include the URL that failed
            )
        );
    }

    // Default to generic parser
    $results = parse_generic_results($html, $partner_name, $url);
    
    // Check if we have a specific parser for this partner
    $parser_function = 'parse_' . $partner_key . '_results';
    if (function_exists($parser_function)) {
        $specific_results = call_user_func($parser_function, $html, $partner_name, $url);
        if (!empty($specific_results)) {
            $results = $specific_results;
        }
    }
    
    return $results;
}

// Generic parser for all partners
function parse_generic_results($html, $source_name, $url = '') {
    // This is a simple placeholder implementation
    // In a real scenario, you'd need to implement proper HTML parsing
    
    // Create some dummy results for demonstration
    $results = array(
        array(
            'name' => 'Sample Hotel 1',
            'price' => '$120',
            'image' => '',
            'rating' => '4.5',
            'source' => $source_name,
            'url' => $url // Include the URL for reference
        ),
        array(
            'name' => 'Sample Hotel 2',
            'price' => '$145',
            'image' => '',
            'rating' => '4.2',
            'source' => $source_name,
            'url' => $url
        ),
        array(
            'name' => 'Sample Hotel 3',
            'price' => '$99',
            'image' => '',
            'rating' => '3.8',
            'source' => $source_name,
            'url' => $url
        )
    );
    
    return $results;
}

// You can define specific parsers for common sites
// These functions will be used if available, otherwise generic parser is used

// Example specific parser for Booking.com - this is just a placeholder
function parse_bookingcom_results($html, $source_name, $url = '') {
    $results = array();
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);
    
    // Example XPath query – would need to be updated based on actual Booking.com HTML structure
    $nodes = $xpath->query("//div[contains(@class,'sr_item')]//span[contains(@class,'sr-hotel__name')]");
    
    // If no nodes found, return empty array (will fall back to generic parser)
    if ($nodes->length === 0) {
        return array();
    }
    
    foreach ($nodes as $node) {
        $hotel_name = trim($node->nodeValue);
        // In a full implementation, also extract price, image, rating, etc.
        $results[] = array(
            'name'   => $hotel_name,
            'price'  => 'N/A', // Placeholder
            'image'  => '',
            'rating' => 'N/A',
            'source' => $source_name,
            'url'    => $url // Include the URL for reference
        );
    }
    
    return $results;
}

// Merge results from various sources into a single array
function merge_results($sources_results) {
    $merged = array();
    // For simplicity, we just append all results.
    // A more advanced implementation might merge by hotel name or unique identifier.
    foreach ($sources_results as $source => $results) {
        foreach ($results as $hotel) {
            $merged[] = $hotel;
        }
    }
    return $merged;
}

// Display the aggregated results in a table
function display_results_table($results) {
    if (empty($results)) {
        echo "<p>No results found.</p>";
        return;
    }
    
    // Determine if we have URL data in the results
    $has_url_data = false;
    foreach ($results as $hotel) {
        if (!empty($hotel['url'])) {
            $has_url_data = true;
            break;
        }
    }
    
    echo "<table class='pricewise-results' border='1' cellpadding='5' cellspacing='0'>";
    
    // Table header
    echo "<thead><tr><th>Hotel Name</th><th>Price</th><th>Rating</th><th>Source</th>";
    
    // Add URL column header if we have URL data
    if ($has_url_data) {
        echo "<th>Error Details</th>";
    }
    
    echo "</tr></thead>";
    
    // Table body
    echo "<tbody>";
    foreach ($results as $hotel) {
        echo "<tr>";
        echo "<td>" . esc_html($hotel['name']) . "</td>";
        echo "<td>" . esc_html($hotel['price']) . "</td>";
        echo "<td>" . esc_html($hotel['rating']) . "</td>";
        echo "<td>" . esc_html($hotel['source']) . "</td>";
        
        // Add URL as a column for error details if present
        if ($has_url_data && !empty($hotel['url']) && $hotel['name'] === 'Error fetching data') {
            echo "<td>";
            echo "<details>";
            echo "<summary>View URL</summary>";
            echo "<a href='" . esc_url($hotel['url']) . "' target='_blank'>" . esc_url($hotel['url']) . "</a>";
            echo "</details>";
            echo "</td>";
        } elseif ($has_url_data) {
            echo "<td>N/A</td>";
        }
        
        echo "</tr>";
    }
    echo "</tbody></table>";
}

// Add some basic CSS for the frontend
add_action('wp_head', 'pricewise_add_css');

function pricewise_add_css() {
		wp_enqueue_style('pricewise-style', plugin_dir_url(__FILE__) . 'style/main-style.css');
    ?>
    <?php
}

// Plugin activation
register_activation_hook(__FILE__, 'pricewise_activate');

function pricewise_activate() {
    // Call the function to initialize empty partners
    if (function_exists('pricewise_initialize_partners')) {
        pricewise_initialize_partners();
    }
    
    // Create necessary database tables or options (future implementation)
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'pricewise_deactivate');

function pricewise_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}