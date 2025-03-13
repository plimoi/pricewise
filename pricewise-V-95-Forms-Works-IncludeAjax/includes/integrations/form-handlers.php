<?php
/**
 * Form Handlers for PriceWise Plugin
 * 
 * Provides specific handlers for popular form plugins.
 * This file extends the form integration with plugin-specific adapters.
 *
 * @package PriceWise
 * @subpackage Integrations
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class to handle specific form plugin integrations
 */
class Pricewise_Form_Handlers {
    
    /**
     * Instance of this class.
     *
     * @var Pricewise_Form_Handlers
     */
    protected static $_instance = null;
    
    /**
     * Main Form Handlers Instance.
     *
     * Ensures only one instance of Form Handlers is loaded or can be loaded.
     *
     * @return Pricewise_Form_Handlers
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the handlers
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the handlers
     */
    public function init() {
        // Initialize Contact Form 7 integration
        if (class_exists('WPCF7')) {
            $this->init_cf7_integration();
        }
        
        // Initialize Gravity Forms integration
        if (class_exists('GFForms')) {
            $this->init_gravity_forms_integration();
        }
        
        // Initialize WPForms integration
        if (class_exists('WPForms')) {
            $this->init_wpforms_integration();
        }
        
        // Initialize Formidable Forms integration
        if (class_exists('FrmForm')) {
            $this->init_formidable_forms_integration();
        }
        
        // Initialize Ninja Forms integration
        if (class_exists('Ninja_Forms')) {
            $this->init_ninja_forms_integration();
        }
        
        // Initialize generic form handling (search forms, etc.)
        $this->init_generic_form_integration();
    }
    
    /**
     * Initialize Contact Form 7 integration
     */
    private function init_cf7_integration() {
        // Add admin interface for CF7 forms
        add_action('wpcf7_admin_after_additional_settings', array($this, 'cf7_admin_interface'), 10, 1);
        add_action('wpcf7_save_contact_form', array($this, 'cf7_save_form_settings'), 10, 3);
        
        // Add data tag for CF7 to display API data
        add_action('wpcf7_init', array($this, 'cf7_add_api_data_tag'));
        
        // Process form submission and add results to form data
        add_filter('wpcf7_posted_data', array($this, 'cf7_process_posted_data'));
        
        // Display API results after form submission
        add_filter('wpcf7_ajax_json_echo', array($this, 'cf7_modify_ajax_response'), 10, 2);
    }
    
    /**
     * Add admin interface for Contact Form 7
     *
     * @param WPCF7_ContactForm $contact_form The contact form instance
     */
    public function cf7_admin_interface($contact_form) {
        $form_id = $contact_form->id();
        
        // Get the API configuration for this form
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('cf7', $form_id);
        
        // Get all APIs
        $apis = Pricewise_API_Settings::get_apis();
        ?>
        <h2><?php _e('PriceWise API Integration', 'pricewise'); ?></h2>
        <fieldset>
            <legend><?php _e('Configure API integration for this form', 'pricewise'); ?></legend>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="pricewise-api-id"><?php _e('API', 'pricewise'); ?></label>
                    </th>
                    <td>
                        <select name="pricewise-api-id" id="pricewise-api-id">
                            <option value=""><?php _e('None', 'pricewise'); ?></option>
                            <?php foreach ($apis as $api_id => $api) : ?>
                                <option value="<?php echo esc_attr($api_id); ?>" <?php selected($api_config && $api_config['api_id'] === $api_id); ?>>
                                    <?php echo esc_html($api['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pricewise-endpoint-id"><?php _e('Endpoint', 'pricewise'); ?></label>
                    </th>
                    <td>
                        <select name="pricewise-endpoint-id" id="pricewise-endpoint-id">
                            <option value=""><?php _e('None', 'pricewise'); ?></option>
                            <?php
                            if ($api_config && !empty($api_config['api_id'])) {
                                $endpoints = Pricewise_API_Settings::get_endpoints($api_config['api_id']);
                                foreach ($endpoints as $endpoint_id => $endpoint) :
                                    ?>
                                    <option value="<?php echo esc_attr($endpoint_id); ?>" <?php selected($api_config && $api_config['endpoint_id'] === $endpoint_id); ?>>
                                        <?php echo esc_html($endpoint['name']); ?>
                                    </option>
                                    <?php
                                endforeach;
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pricewise-api-trigger"><?php _e('API Trigger', 'pricewise'); ?></label>
                    </th>
                    <td>
                        <select name="pricewise-api-trigger" id="pricewise-api-trigger">
                            <option value="submit" <?php selected($api_config && isset($api_config['trigger']) && $api_config['trigger'] === 'submit'); ?>>
                                <?php _e('On form submission', 'pricewise'); ?>
                            </option>
                            <option value="ajax" <?php selected($api_config && isset($api_config['trigger']) && $api_config['trigger'] === 'ajax'); ?>>
                                <?php _e('Via AJAX (during form interaction)', 'pricewise'); ?>
                            </option>
                        </select>
                        <p class="description"><?php _e('When should the API request be triggered?', 'pricewise'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pricewise-display-results"><?php _e('Display Results', 'pricewise'); ?></label>
                    </th>
                    <td>
                        <select name="pricewise-display-results" id="pricewise-display-results">
                            <option value="none" <?php selected($api_config && isset($api_config['display_results']) && $api_config['display_results'] === 'none'); ?>>
                                <?php _e('Do not display results', 'pricewise'); ?>
                            </option>
                            <option value="after_submit" <?php selected($api_config && isset($api_config['display_results']) && $api_config['display_results'] === 'after_submit'); ?>>
                                <?php _e('After form submission', 'pricewise'); ?>
                            </option>
                            <option value="replace_form" <?php selected($api_config && isset($api_config['display_results']) && $api_config['display_results'] === 'replace_form'); ?>>
                                <?php _e('Replace form with results', 'pricewise'); ?>
                            </option>
                        </select>
                        <p class="description"><?php _e('How to display API results?', 'pricewise'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="pricewise-results-template"><?php _e('Results Template', 'pricewise'); ?></label>
                    </th>
                    <td>
                        <textarea name="pricewise-results-template" id="pricewise-results-template" rows="5" class="large-text code"><?php echo esc_textarea($api_config && isset($api_config['results_template']) ? $api_config['results_template'] : ''); ?></textarea>
                        <p class="description"><?php _e('HTML template for displaying results. Use {{data.field_name}} as placeholders for API response data.', 'pricewise'); ?></p>
                    </td>
                </tr>
            </table>
            
            <h3><?php _e('Field Mapping', 'pricewise'); ?></h3>
            <p><?php _e('Map form fields to API parameters by using the field name as a placeholder in API parameter values.', 'pricewise'); ?></p>
            <p><?php _e('For example, if your form has a field named "email", you can use {email} as a placeholder in API parameters.', 'pricewise'); ?></p>
            
            <p><strong><?php _e('Available Form Fields:', 'pricewise'); ?></strong></p>
            <?php
            $tags = $contact_form->scan_form_tags();
            if (!empty($tags)) {
                echo '<ul>';
                foreach ($tags as $tag) {
                    if (!empty($tag['name'])) {
                        echo '<li><code>{' . esc_html($tag['name']) . '}</code></li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('No fields found in this form.', 'pricewise') . '</p>';
            }
            ?>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Handle API selection change
                $('#pricewise-api-id').on('change', function() {
                    var apiId = $(this).val();
                    var endpointSelect = $('#pricewise-endpoint-id');
                    
                    // Clear current options
                    endpointSelect.find('option').not(':first').remove();
                    
                    if (apiId) {
                        // Show loading indicator
                        endpointSelect.after('<span class="spinner is-active" style="float:none;"></span>');
                        
                        // Fetch endpoints for the selected API
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'pricewise_get_endpoints',
                                api_id: apiId,
                                nonce: '<?php echo wp_create_nonce('pricewise_get_endpoints'); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data) {
                                    // Add endpoints to select
                                    $.each(response.data, function(id, name) {
                                        endpointSelect.append(
                                            $('<option></option>').val(id).text(name)
                                        );
                                    });
                                }
                                
                                // Remove loading indicator
                                endpointSelect.next('.spinner').remove();
                            }
                        });
                    }
                });
            });
            </script>
        </fieldset>
        <?php
    }
    
    /**
     * Save Contact Form 7 form settings
     *
     * @param WPCF7_ContactForm $contact_form The contact form instance
     * @param array $args The arguments
     * @param string $context The context
     */
    public function cf7_save_form_settings($contact_form, $args, $context) {
        $form_id = $contact_form->id();
        
        // Check if our fields are set
        $api_id = isset($_POST['pricewise-api-id']) ? sanitize_text_field($_POST['pricewise-api-id']) : '';
        $endpoint_id = isset($_POST['pricewise-endpoint-id']) ? sanitize_text_field($_POST['pricewise-endpoint-id']) : '';
        $trigger = isset($_POST['pricewise-api-trigger']) ? sanitize_text_field($_POST['pricewise-api-trigger']) : 'submit';
        $display_results = isset($_POST['pricewise-display-results']) ? sanitize_text_field($_POST['pricewise-display-results']) : 'none';
        $results_template = isset($_POST['pricewise-results-template']) ? wp_kses_post($_POST['pricewise-results-template']) : '';
        
        // Get form integration
        $form_integration = pricewise_form_integration();
        
        if (empty($api_id) || empty($endpoint_id)) {
            // If no API or endpoint selected, delete the configuration
            $form_integration->delete_form_api_config('cf7', $form_id);
            return;
        }
        
        // Save the configuration
        $config = array(
            'api_id' => $api_id,
            'endpoint_id' => $endpoint_id,
            'trigger' => $trigger,
            'display_results' => $display_results,
            'results_template' => $results_template
        );
        
        $form_integration->save_form_api_config('cf7', $form_id, $config);
    }
    
    /**
     * Add API data tag for Contact Form 7
     */
    public function cf7_add_api_data_tag() {
        // Add the apidata tag for displaying API data in forms
        if (function_exists('wpcf7_add_form_tag')) {
            wpcf7_add_form_tag('apidata', array($this, 'cf7_api_data_tag_handler'));
        }
    }
    
    /**
     * Handle the apidata tag for Contact Form 7
     *
     * @param WPCF7_FormTag $tag The form tag
     * @return string The tag output
     */
    public function cf7_api_data_tag_handler($tag) {
        // Get tag attributes
        $atts = $tag->get_option('api', 'DEFAULT');
        $api_id = isset($atts[0]) ? $atts[0] : '';
        
        $atts = $tag->get_option('endpoint', 'DEFAULT');
        $endpoint_id = isset($atts[0]) ? $atts[0] : '';
        
        $atts = $tag->get_option('field', 'DEFAULT');
        $field_path = isset($atts[0]) ? $atts[0] : '';
        
        // Get API data
        $api = Pricewise_API_Settings::get_api($api_id);
        $endpoint = Pricewise_API_Settings::get_endpoint($api_id, $endpoint_id);
        
        if (!$api || !$endpoint) {
            return '';
        }
        
        // Create placeholder div for AJAX loading
        $output = '<div class="pricewise-api-data" data-api-id="' . esc_attr($api_id) . '" data-endpoint-id="' . esc_attr($endpoint_id) . '" data-field-path="' . esc_attr($field_path) . '"></div>';
        
        return $output;
    }
    
    /**
     * Process posted data for Contact Form 7
     *
     * @param array $posted_data The posted data
     * @return array The processed posted data
     */
    public function cf7_process_posted_data($posted_data) {
        return $posted_data;
    }
    
    /**
     * Modify AJAX response for Contact Form 7
     *
     * @param array $response The AJAX response
     * @param WPCF7_ContactForm $contact_form The contact form instance
     * @return array The modified AJAX response
     */
    public function cf7_modify_ajax_response($response, $contact_form) {
        // Get form ID
        $form_id = $contact_form->id();
        
        // Get form integration
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('cf7', $form_id);
        
        if (!$api_config || $api_config['display_results'] === 'none') {
            return $response;
        }
        
        // Get the API response from the submission if available
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return $response;
        }
        
        $api_response = $submission->get_response();
        
        if (empty($api_response)) {
            return $response;
        }
        
        // Format the results using the template
        $results_html = $this->format_api_results($api_response, $api_config['results_template']);
        
        // Add the results to the response
        if ($api_config['display_results'] === 'after_submit') {
            $response['message'] .= $results_html;
        } elseif ($api_config['display_results'] === 'replace_form') {
            $response['message'] = $results_html;
        }
        
        return $response;
    }
    
    /**
     * Initialize Gravity Forms integration
     */
    private function init_gravity_forms_integration() {
        // Add settings to Gravity Forms
        add_filter('gform_form_settings', array($this, 'gf_add_form_settings'), 10, 2);
        add_filter('gform_pre_form_settings_save', array($this, 'gf_save_form_settings'));
        
        // Add custom merge tags
        add_filter('gform_custom_merge_tags', array($this, 'gf_add_api_merge_tags'), 10, 2);
        add_filter('gform_replace_merge_tags', array($this, 'gf_replace_api_merge_tags'), 10, 7);
    }
    
    /**
     * Add form settings to Gravity Forms
     *
     * @param array $settings The form settings
     * @param array $form The form object
     * @return array The modified form settings
     */
    public function gf_add_form_settings($settings, $form) {
        $form_id = $form['id'];
        
        // Get the API configuration for this form
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('gravity_forms', $form_id);
        
        // Get all APIs
        $apis = Pricewise_API_Settings::get_apis();
        
        $settings['PriceWise API'] = array(
            'api_integration' => '
                <tr>
                    <th>' . __('API Integration', 'pricewise') . '</th>
                    <td>
                        <input type="checkbox" name="pricewise-enable-api" id="pricewise-enable-api" value="1" ' . checked(is_array($api_config), true, false) . ' />
                        <label for="pricewise-enable-api">' . __('Enable API integration for this form', 'pricewise') . '</label>
                    </td>
                </tr>
            ',
            'api_selection' => '
                <tr>
                    <th>' . __('Select API', 'pricewise') . '</th>
                    <td>
                        <select name="pricewise-api-id" id="pricewise-api-id">
                            <option value="">' . __('None', 'pricewise') . '</option>
                            ' . $this->get_api_options_html($apis, $api_config) . '
                        </select>
                    </td>
                </tr>
            ',
            'endpoint_selection' => '
                <tr>
                    <th>' . __('Select Endpoint', 'pricewise') . '</th>
                    <td>
                        <select name="pricewise-endpoint-id" id="pricewise-endpoint-id">
                            <option value="">' . __('None', 'pricewise') . '</option>
                            ' . $this->get_endpoint_options_html($api_config) . '
                        </select>
                    </td>
                </tr>
            ',
            'api_trigger' => '
                <tr>
                    <th>' . __('API Trigger', 'pricewise') . '</th>
                    <td>
                        <select name="pricewise-api-trigger" id="pricewise-api-trigger">
                            <option value="submit" ' . selected($api_config && isset($api_config['trigger']) && $api_config['trigger'] === 'submit', true, false) . '>' . __('On form submission', 'pricewise') . '</option>
                            <option value="ajax" ' . selected($api_config && isset($api_config['trigger']) && $api_config['trigger'] === 'ajax', true, false) . '>' . __('Via AJAX (during form interaction)', 'pricewise') . '</option>
                        </select>
                        <p class="description">' . __('When should the API request be triggered?', 'pricewise') . '</p>
                    </td>
                </tr>
            ',
            'api_field_mapping' => '
                <tr>
                    <th>' . __('Field Mapping', 'pricewise') . '</th>
                    <td>
                        <p>' . __('Map form fields to API parameters by using the field name as a placeholder in API parameter values.', 'pricewise') . '</p>
                        <p>' . __('For example, if your form has a field with ID 1, you can use {1} as a placeholder in API parameters.', 'pricewise') . '</p>
                        
                        <a href="#" id="pricewise-show-fields" class="button">' . __('Show Available Fields', 'pricewise') . '</a>
                        
                        <div id="pricewise-available-fields" style="display: none; margin-top: 10px; padding: 10px; background: #f8f8f8; border: 1px solid #ddd;">
                            <h4>' . __('Available Form Fields:', 'pricewise') . '</h4>
                            <ul>
                                ' . $this->get_gf_fields_html($form) . '
                            </ul>
                        </div>
                        
                        <script type="text/javascript">
                            jQuery(document).ready(function($) {
                                $("#pricewise-show-fields").on("click", function(e) {
                                    e.preventDefault();
                                    $("#pricewise-available-fields").toggle();
                                });
                                
                                // Handle API selection change
                                $("#pricewise-api-id").on("change", function() {
                                    var apiId = $(this).val();
                                    var endpointSelect = $("#pricewise-endpoint-id");
                                    
                                    // Clear current options
                                    endpointSelect.find("option").not(":first").remove();
                                    
                                    if (apiId) {
                                        // Show loading indicator
                                        endpointSelect.after("<span class=\"spinner is-active\" style=\"float:none;\"></span>");
                                        
                                        // Fetch endpoints for the selected API
                                        $.ajax({
                                            url: ajaxurl,
                                            type: "POST",
                                            data: {
                                                action: "pricewise_get_endpoints",
                                                api_id: apiId,
                                                nonce: "' . wp_create_nonce('pricewise_get_endpoints') . '"
                                            },
                                            success: function(response) {
                                                if (response.success && response.data) {
                                                    // Add endpoints to select
                                                    $.each(response.data, function(id, name) {
                                                        endpointSelect.append(
                                                            $("<option></option>").val(id).text(name)
                                                        );
                                                    });
                                                }
                                                
                                                // Remove loading indicator
                                                endpointSelect.next(".spinner").remove();
                                            }
                                        });
                                    }
                                });
                            });
                        </script>
                    </td>
                </tr>
            '
        );
        
        return $settings;
    }
    
    /**
     * Save Gravity Forms form settings
     *
     * @param array $form The form object
     * @return array The modified form object
     */
    public function gf_save_form_settings($form) {
        $form_id = $form['id'];
        
        // Check if API integration is enabled
        $enable_api = isset($_POST['pricewise-enable-api']) ? (bool)$_POST['pricewise-enable-api'] : false;
        
        // Get form integration
        $form_integration = pricewise_form_integration();
        
        if (!$enable_api) {
            // If API integration is disabled, delete the configuration
            $form_integration->delete_form_api_config('gravity_forms', $form_id);
            return $form;
        }
        
        // Get API and endpoint
        $api_id = isset($_POST['pricewise-api-id']) ? sanitize_text_field($_POST['pricewise-api-id']) : '';
        $endpoint_id = isset($_POST['pricewise-endpoint-id']) ? sanitize_text_field($_POST['pricewise-endpoint-id']) : '';
        $trigger = isset($_POST['pricewise-api-trigger']) ? sanitize_text_field($_POST['pricewise-api-trigger']) : 'submit';
        
        if (empty($api_id) || empty($endpoint_id)) {
            // If no API or endpoint selected, delete the configuration
            $form_integration->delete_form_api_config('gravity_forms', $form_id);
            return $form;
        }
        
        // Save the configuration
        $config = array(
            'api_id' => $api_id,
            'endpoint_id' => $endpoint_id,
            'trigger' => $trigger
        );
        
        $form_integration->save_form_api_config('gravity_forms', $form_id, $config);
        
        return $form;
    }
    
    /**
     * Add API merge tags to Gravity Forms
     *
     * @param array $merge_tags The merge tags
     * @param array $form The form object
     * @return array The modified merge tags
     */
    public function gf_add_api_merge_tags($merge_tags, $form) {
        $form_id = $form['id'];
        
        // Get the API configuration for this form
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('gravity_forms', $form_id);
        
        if (!$api_config) {
            return $merge_tags;
        }
        
        // Add API data merge tag
        $merge_tags[] = array(
            'label' => __('API Data', 'pricewise'),
            'tag' => '{pricewise_api_data}'
        );
        
        return $merge_tags;
    }
    
    /**
     * Replace API merge tags in Gravity Forms
     *
     * @param string $text The text to replace merge tags in
     * @param array $form The form object
     * @param array $entry The entry object
     * @param bool $url_encode Whether to URL encode the replacement
     * @param bool $esc_html Whether to escape HTML in the replacement
     * @param bool $nl2br Whether to convert newlines to <br> tags
     * @param string $format The format to use (html or text)
     * @return string The text with merge tags replaced
     */
    public function gf_replace_api_merge_tags($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
        if (strpos($text, '{pricewise_api_data}') === false) {
            return $text;
        }
        
        // Get form ID
        $form_id = $form['id'];
        
        // Get the API configuration for this form
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('gravity_forms', $form_id);
        
        if (!$api_config) {
            return str_replace('{pricewise_api_data}', '', $text);
        }
        
        // Get API response from entry meta
        $api_response = gform_get_meta($entry['id'], 'pricewise_api_result');
        
        if (empty($api_response)) {
            return str_replace('{pricewise_api_data}', '', $text);
        }
        
        // Format API response as HTML
        $replacement = '<div class="pricewise-api-data">';
        $replacement .= '<pre>' . print_r($api_response, true) . '</pre>';
        $replacement .= '</div>';
        
        return str_replace('{pricewise_api_data}', $replacement, $text);
    }
    
    /**
     * Initialize WPForms integration
     */
    private function init_wpforms_integration() {
        // We'll implement this if needed
    }
    
    /**
     * Initialize Formidable Forms integration
     */
    private function init_formidable_forms_integration() {
        // We'll implement this if needed
    }
    
    /**
     * Initialize Ninja Forms integration
     */
    private function init_ninja_forms_integration() {
        // We'll implement this if needed
    }
    
    /**
     * Initialize generic form integration
     */
    private function init_generic_form_integration() {
        // Add search form integration (example)
        add_action('pre_get_posts', array($this, 'process_search_form'));
        
        // Add comment form integration 
        add_filter('preprocess_comment', array($this, 'process_comment_form'));
    }
    
    /**
     * Process search form
     *
     * @param WP_Query $query The query object
     */
    public function process_search_form($query) {
        if (!$query->is_search() || !$query->is_main_query()) {
            return;
        }
        
        // Get search query
        $search_query = get_search_query();
        
        if (empty($search_query)) {
            return;
        }
        
        // Get API configuration for search
        $form_integration = pricewise_form_integration();
        $api_config = $form_integration->get_form_api_config('search', 'default');
        
        if (!$api_config) {
            return;
        }
        
        // Form data for API request
        $form_data = array(
            'search_query' => $search_query
        );
        
        // Process API request
        $result = $form_integration->process_form_submission($form_data, $api_config['api_id'], $api_config['endpoint_id']);
        
        // Store the result for later use
        set_query_var('pricewise_api_result', $result);
    }
    
    /**
     * Process comment form
     *
     * @param array $commentdata The comment data
     * @return array The processed comment data
     */
    public function process_comment_form($commentdata) {
        return $commentdata;
    }
    
    /**
     * Get HTML for API options
     *
     * @param array $apis The APIs
     * @param array $api_config The API configuration
     * @return string The HTML for API options
     */
    private function get_api_options_html($apis, $api_config) {
        $html = '';
        
        foreach ($apis as $api_id => $api) {
            $selected = ($api_config && $api_config['api_id'] === $api_id) ? 'selected' : '';
            $html .= '<option value="' . esc_attr($api_id) . '" ' . $selected . '>' . esc_html($api['name']) . '</option>';
        }
        
        return $html;
    }
    
    /**
     * Get HTML for endpoint options
     *
     * @param array $api_config The API configuration
     * @return string The HTML for endpoint options
     */
    private function get_endpoint_options_html($api_config) {
        $html = '';
        
        if ($api_config && !empty($api_config['api_id'])) {
            $endpoints = Pricewise_API_Settings::get_endpoints($api_config['api_id']);
            
            foreach ($endpoints as $endpoint_id => $endpoint) {
                $selected = ($api_config['endpoint_id'] === $endpoint_id) ? 'selected' : '';
                $html .= '<option value="' . esc_attr($endpoint_id) . '" ' . $selected . '>' . esc_html($endpoint['name']) . '</option>';
            }
        }
        
        return $html;
    }
    
    /**
     * Get HTML for Gravity Forms fields
     *
     * @param array $form The form object
     * @return string The HTML for form fields
     */
    private function get_gf_fields_html($form) {
        $html = '';
        
        if (isset($form['fields']) && is_array($form['fields'])) {
            foreach ($form['fields'] as $field) {
                $html .= '<li><code>{' . esc_html($field->id) . '}</code> - ' . esc_html($field->label) . '</li>';
            }
        }
        
        return $html;
    }
    
    /**
     * Format API results
     *
     * @param mixed $api_response The API response
     * @param string $template The template to use
     * @return string The formatted results
     */
    private function format_api_results($api_response, $template) {
        if (empty($template)) {
            // Default template if none provided
            $template = '<div class="pricewise-api-results">';
            $template .= '<pre>{{json}}</pre>';
            $template .= '</div>';
        }
        
        // Convert response to array if it's not already
        if (is_string($api_response)) {
            $api_response = json_decode($api_response, true);
        }
        
        // Replace JSON placeholder with entire response
        $template = str_replace('{{json}}', json_encode($api_response, JSON_PRETTY_PRINT), $template);
        
        // Replace field placeholders
        if (is_array($api_response)) {
            $template = $this->replace_data_placeholders($template, $api_response);
        }
        
        return $template;
    }
    
    /**
     * Replace data placeholders in a template
     *
     * @param string $template The template
     * @param array $data The data
     * @param string $prefix The prefix for nested data
     * @return string The template with placeholders replaced
     */
    private function replace_data_placeholders($template, $data, $prefix = 'data') {
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $prefix . '.' . $key . '}}';
            
            if (is_array($value)) {
                // Recursively process nested arrays
                $template = $this->replace_data_placeholders($template, $value, $prefix . '.' . $key);
                
                // Also replace the array itself (as JSON)
                $template = str_replace($placeholder, json_encode($value), $template);
            } else {
                // Replace simple value
                $template = str_replace($placeholder, $value, $template);
            }
        }
        
        return $template;
    }
}

// Initialize the form handlers
function pricewise_form_handlers() {
    return Pricewise_Form_Handlers::instance();
}

// Start the handlers
pricewise_form_handlers();