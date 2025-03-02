<?php
/**
 * The hotel data model.
 *
 * @package    Pricewise
 * @subpackage Pricewise/includes/models
 */

class Pricewise_Hotel {

    /**
     * The hotel ID.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $id    The hotel ID.
     */
    public $id;

    /**
     * The entity ID (location ID).
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $entity_id    The entity ID.
     */
    public $entity_id;

    /**
     * The hotel name.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $name    The hotel name.
     */
    public $name;

    /**
     * The hotel location.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $location    The hotel location.
     */
    public $location;

    /**
     * The hotel stars rating.
     *
     * @since    1.0.0
     * @access   public
     * @var      int    $stars    The hotel stars rating.
     */
    public $stars;

    /**
     * The hotel description.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $description    The hotel description.
     */
    public $description;

    /**
     * The hotel image URL.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $image_url    The hotel image URL.
     */
    public $image_url;

    /**
     * The hotel price details.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $prices    The hotel price details.
     */
    public $prices = array();

    /**
     * The hotel review details.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $reviews    The hotel review details.
     */
    public $reviews = array();

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    array    $data    Optional. The hotel data.
     */
    public function __construct($data = array()) {
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Populate hotel data from API response.
     *
     * @since    1.0.0
     * @param    array    $data    The hotel data from API.
     * @return   void
     */
    public function populate($data) {
        // The API can return different data structures for different endpoints
        // Handle hotels/search endpoint response
        if (isset($data['id']) && isset($data['hotelId'])) {
            $this->id = sanitize_text_field($data['hotelId']);
            
            // Extract entity ID from combined ID
            if (preg_match('/eyJlbnRpdHlJZCI6IiguKj8pIiwiaWQiOi/', $data['id'], $matches)) {
                $this->entity_id = sanitize_text_field($matches[1]);
            }
            
            $this->name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
            $this->stars = isset($data['stars']) ? intval($data['stars']) : 0;
            
            // Handle location
            if (isset($data['distance'])) {
                $this->location = sanitize_text_field($data['distance']);
            }
            
            // Handle images
            if (isset($data['images']) && is_array($data['images']) && !empty($data['images'])) {
                $this->image_url = esc_url_raw($data['images'][0]);
            }
            
            // Handle reviews
            if (isset($data['reviewsSummary'])) {
                $this->reviews = array(
                    'score' => isset($data['reviewsSummary']['score']) ? floatval($data['reviewsSummary']['score']) : 0,
                    'total' => isset($data['reviewsSummary']['total']) ? intval($data['reviewsSummary']['total']) : 0,
                    'description' => isset($data['reviewsSummary']['scoreDesc']) ? sanitize_text_field($data['reviewsSummary']['scoreDesc']) : '',
                );
            }
            
            // Handle prices
            if (isset($data['lowestPrice'])) {
                $this->prices = array(
                    'lowest' => array(
                        'price' => isset($data['lowestPrice']['price']) ? sanitize_text_field($data['lowestPrice']['price']) : '',
                        'raw_price' => isset($data['lowestPrice']['rawPrice']) ? floatval($data['lowestPrice']['rawPrice']) : 0,
                        'partner' => isset($data['lowestPrice']['partnerName']) ? sanitize_text_field($data['lowestPrice']['partnerName']) : '',
                        'partner_logo' => isset($data['lowestPrice']['partnerLogo']) ? esc_url_raw($data['lowestPrice']['partnerLogo']) : '',
                        'deeplink' => isset($data['lowestPrice']['deeplink']) ? esc_url_raw($data['lowestPrice']['deeplink']) : '',
                    )
                );
                
                // Add other prices if available
                if (isset($data['otherPrices']) && is_array($data['otherPrices'])) {
                    foreach ($data['otherPrices'] as $price_data) {
                        if (isset($price_data['partnerName']) && isset($price_data['price'])) {
                            $partner_key = sanitize_title($price_data['partnerName']);
                            $this->prices[$partner_key] = array(
                                'price' => sanitize_text_field($price_data['price']),
                                'raw_price' => isset($price_data['rawPrice']) ? floatval($price_data['rawPrice']) : 0,
                                'partner' => sanitize_text_field($price_data['partnerName']),
                                'partner_logo' => isset($price_data['partnerLogo']) ? esc_url_raw($price_data['partnerLogo']) : '',
                                'deeplink' => isset($price_data['deeplink']) ? esc_url_raw($price_data['deeplink']) : '',
                            );
                        }
                    }
                }
            }
        }
        // Handle hotels/detail endpoint response
        else if (isset($data['general']) && isset($data['general']['name'])) {
            $this->name = sanitize_text_field($data['general']['name']);
            $this->stars = isset($data['general']['stars']) ? intval($data['general']['stars']) : 0;
            
            // Handle description
            if (isset($data['goodToKnow']['description']['content'])) {
                $this->description = wp_kses_post($data['goodToKnow']['description']['content']);
            }
            
            // Handle location
            if (isset($data['location']['address'])) {
                $this->location = sanitize_text_field($data['location']['address']);
            }
            
            // Handle images
            if (isset($data['gallery']['images']) && is_array($data['gallery']['images']) && !empty($data['gallery']['images'])) {
                foreach ($data['gallery']['images'] as $image) {
                    if (isset($image['dynamic'])) {
                        $this->image_url = esc_url_raw($image['dynamic']);
                        break;
                    }
                }
            }
            
            // Handle reviews
            if (isset($data['reviewRatingSummary'])) {
                $this->reviews = array(
                    'score' => isset($data['reviewRatingSummary']['score']) ? floatval($data['reviewRatingSummary']['score']) : 0,
                    'total' => isset($data['reviewRatingSummary']['countNumber']) ? intval($data['reviewRatingSummary']['countNumber']) : 0,
                    'description' => isset($data['reviewRatingSummary']['scoreDesc']) ? sanitize_text_field($data['reviewRatingSummary']['scoreDesc']) : '',
                );
            }
        }
    }

    /**
     * Get hotel details from API.
     *
     * @since    1.0.0
     * @param    Pricewise_Hotels_API    $api    The hotels API instance.
     * @return   boolean                        True on success, false on failure.
     */
    public function fetch_details($api) {
        if (empty($this->id)) {
            return false;
        }
        
        $details = $api->get_details($this->id);
        
        if (is_wp_error($details)) {
            return false;
        }
        
        if (isset($details['data'])) {
            $this->populate($details['data']);
            return true;
        }
        
        return false;
    }

    /**
     * Get hotel prices from API.
     *
     * @since    1.0.0
     * @param    Pricewise_Hotels_API    $api         The hotels API instance.
     * @param    string                 $checkin     The check-in date.
     * @param    string                 $checkout    The check-out date.
     * @param    array                  $options     Optional. Additional options.
     * @return   boolean                            True on success, false on failure.
     */
    public function fetch_prices($api, $checkin, $checkout, $options = array()) {
        if (empty($this->id)) {
            return false;
        }
        
        $prices_data = $api->get_prices($this->id, $checkin, $checkout, $options);
        
        if (is_wp_error($prices_data)) {
            return false;
        }
        
        if (isset($prices_data['data']) && isset($prices_data['data']['metaInfo']['rates'])) {
            $rates = $prices_data['data']['metaInfo']['rates'];
            
            // Reset prices array
            $this->prices = array();
            
            // Add lowest price if available
            if (isset($prices_data['data']['cheapestPrice'])) {
                $cheapest = $prices_data['data']['cheapestPrice'];
                $this->prices['lowest'] = array(
                    'price' => isset($cheapest['price']) ? sanitize_text_field($cheapest['price']) : '',
                    'raw_price' => isset($cheapest['rawPrice']) ? floatval($cheapest['rawPrice']) : 0,
                    'partner' => isset($cheapest['partnerId']) ? sanitize_text_field($cheapest['partnerId']) : '',
                    'deeplink' => '',
                );
            }
            
            // Process all rates
            foreach ($rates as $rate) {
                if (isset($rate['partnerName']) && isset($rate['price'])) {
                    $partner_key = sanitize_title($rate['partnerName']);
                    $this->prices[$partner_key] = array(
                        'price' => sanitize_text_field($rate['price']),
                        'raw_price' => isset($rate['rawPrice']) ? floatval($rate['rawPrice']) : 0,
                        'partner' => sanitize_text_field($rate['partnerName']),
                        'partner_logo' => isset($rate['partnerLogo']) ? esc_url_raw($rate['partnerLogo']) : '',
                        'deeplink' => isset($rate['deeplink']) ? esc_url_raw($rate['deeplink']) : '',
                        'room_type' => isset($rate['roomType']) ? sanitize_text_field($rate['roomType']) : '',
                        'room_policies' => isset($rate['roomPolicies']) ? sanitize_text_field($rate['roomPolicies']) : '',
                    );
                }
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Save hotel to database.
     *
     * @since    1.0.0
     * @return   boolean    True on success, false on failure.
     */
    public function save() {
        global $wpdb;
        
        if (empty($this->id) || empty($this->name)) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'pricewise_hotels';
        
        $data = array(
            'hotel_id' => $this->id,
            'entity_id' => $this->entity_id,
            'name' => $this->name,
            'location' => $this->location,
            'stars' => $this->stars,
            'image_url' => $this->image_url,
            'last_updated' => current_time('mysql'),
        );
        
        $formats = array('%s', '%s', '%s', '%s', '%d', '%s', '%s');
        
        // Check if hotel already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE hotel_id = %s",
            $this->id
        ));
        
        if ($exists) {
            // Update existing hotel
            $result = $wpdb->update(
                $table_name,
                $data,
                array('hotel_id' => $this->id),
                $formats,
                array('%s')
            );
        } else {
            // Insert new hotel
            $result = $wpdb->insert(
                $table_name,
                $data,
                $formats
            );
        }
        
        return $result !== false;
    }
}