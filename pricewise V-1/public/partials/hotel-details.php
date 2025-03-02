<?php
/**
 * Template part for displaying the hotel details
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Pricewise
 * @subpackage Pricewise/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Calculate date differences for stay length
$checkin_date = new DateTime($checkin);
$checkout_date = new DateTime($checkout);
$date_diff = $checkout_date->diff($checkin_date);
$nights = $date_diff->days;
?>

<div class="pricewise-hotel-details-container">
    <div class="pricewise-hotel-header">
        <h1 class="pricewise-hotel-title"><?php echo esc_html($hotel->name); ?></h1>
        
        <div class="pricewise-hotel-rating">
            <div class="pricewise-stars">
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <?php if ($i <= $hotel->stars) : ?>
                        <span class="pricewise-star pricewise-star-filled">★</span>
                    <?php else : ?>
                        <span class="pricewise-star pricewise-star-empty">☆</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <?php if (isset($hotel->reviews) && isset($hotel->reviews['score'])) : ?>
                <div class="pricewise-review-score">
                    <span class="pricewise-score"><?php echo esc_html(number_format($hotel->reviews['score'], 1)); ?></span>
                    <span class="pricewise-score-description"><?php echo esc_html($hotel->reviews['description']); ?></span>
                    <span class="pricewise-review-count">
                        <?php
                        /* translators: %d: number of reviews */
                        printf(_n('Based on %d review', 'Based on %d reviews', $hotel->reviews['total'], 'pricewise'), 
                            $hotel->reviews['total']); 
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pricewise-hotel-location">
            <span class="pricewise-location-icon">📍</span>
            <span class="pricewise-location-text"><?php echo esc_html($hotel->location); ?></span>
        </div>
    </div>
    
    <div class="pricewise-hotel-content">
        <div class="pricewise-hotel-main">
            <div class="pricewise-hotel-images">
                <?php if (!empty($hotel->image_url)) : ?>
                    <div class="pricewise-hotel-main-image">
                        <img src="<?php echo esc_url($hotel->image_url); ?>" alt="<?php echo esc_attr($hotel->name); ?>" />
                    </div>
                <?php else : ?>
                    <div class="pricewise-no-image"><?php _e('No image available', 'pricewise'); ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($hotel->description)) : ?>
                <div class="pricewise-hotel-description">
                    <h2><?php _e('About this hotel', 'pricewise'); ?></h2>
                    <div class="pricewise-description-content">
                        <?php echo wp_kses_post($hotel->description); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pricewise-hotel-sidebar">
            <div class="pricewise-booking-details">
                <h3><?php _e('Your stay', 'pricewise'); ?></h3>
                
                <div class="pricewise-booking-dates">
                    <div class="pricewise-checkin">
                        <span class="pricewise-date-label"><?php _e('Check-in', 'pricewise'); ?></span>
                        <span class="pricewise-date-value"><?php echo esc_html($checkin_date->format('D, M j, Y')); ?></span>
                    </div>
                    <div class="pricewise-checkout">
                        <span class="pricewise-date-label"><?php _e('Check-out', 'pricewise'); ?></span>
                        <span class="pricewise-date-value"><?php echo esc_html($checkout_date->format('D, M j, Y')); ?></span>
                    </div>
                    <div class="pricewise-stay-length">
                        <span class="pricewise-length-value">
                            <?php
                            /* translators: %d: number of nights */
                            printf(_n('%d night', '%d nights', $nights, 'pricewise'), $nights); 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="pricewise-booking-guests">
                    <div class="pricewise-guests-label"><?php _e('Guests:', 'pricewise'); ?></div>
                    <div class="pricewise-guests-value">
                        <?php 
                            /* translators: %1$d: number of adults, %2$d: number of children, %3$d: number of rooms */
                            printf(
                                __('%1$d adult%2$s%3$s in %4$d room%5$s', 'pricewise'), 
                                $adults,
                                $adults > 1 ? 's' : '',
                                $children > 0 ? ', ' . $children . ' ' . ($children > 1 ? __('children', 'pricewise') : __('child', 'pricewise')) : '',
                                $rooms,
                                $rooms > 1 ? 's' : ''
                            ); 
                        ?>
                    </div>
                </div>
                
                <div class="pricewise-modify-search">
                    <a href="<?php 
                        echo esc_url(add_query_arg(array(
                            'destination' => urlencode($hotel->location),
                            'checkin' => $checkin,
                            'checkout' => $checkout,
                            'adults' => $adults,
                            'children' => $children,
                            'children_ages' => implode(',', $children_ages),
                            'rooms' => $rooms,
                        ), get_permalink())); 
                    ?>" class="pricewise-modify-button">
                        <?php _e('Modify Search', 'pricewise'); ?>
                    </a>
                </div>
            </div>
            
            <div class="pricewise-price-comparison">
                <h3><?php _e('Price Comparison', 'pricewise'); ?></h3>
                
                <?php if (!empty($hotel->prices)) : ?>
                    <div class="pricewise-provider-list">
                        <?php 
                        // Sort prices by lowest price first
                        $sorted_prices = $hotel->prices;
                        usort($sorted_prices, function($a, $b) {
                            return $a['raw_price'] - $b['raw_price'];
                        });
                        
                        foreach ($sorted_prices as $key => $price) : 
                        ?>
                            <div class="pricewise-provider-item">
                                <div class="pricewise-provider-info">
                                    <div class="pricewise-provider-name">
                                        <?php if (!empty($price['partner_logo'])) : ?>
                                            <img src="<?php echo esc_url($price['partner_logo']); ?>" alt="<?php echo esc_attr($price['partner']); ?>" class="pricewise-provider-logo" />
                                        <?php else : ?>
                                            <span class="pricewise-provider-text"><?php echo esc_html($price['partner']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (isset($price['room_type'])) : ?>
                                        <div class="pricewise-room-type"><?php echo esc_html($price['room_type']); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($price['room_policies'])) : ?>
                                        <div class="pricewise-room-policies"><?php echo esc_html($price['room_policies']); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="pricewise-provider-price">
                                    <div class="pricewise-price-tag"><?php echo esc_html($price['price']); ?></div>
                                    <div class="pricewise-price-for">
                                        <?php
                                        /* translators: %d: number of nights */
                                        printf(_n('for %d night', 'for %d nights', $nights, 'pricewise'), $nights); 
                                        ?>
                                    </div>
                                    
                                    <?php if (!empty($price['deeplink'])) : ?>
                                        <a href="<?php echo esc_url($price['deeplink']); ?>" class="pricewise-book-button" target="_blank" rel="nofollow">
                                            <?php _e('Book Now', 'pricewise'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="pricewise-no-prices">
                        <p><?php _e('No price information available for the selected dates.', 'pricewise'); ?></p>
                        <p><?php _e('Please try different dates or check back later.', 'pricewise'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pricewise-back-to-results">
                <a href="javascript:history.back()" class="pricewise-back-button">
                    <?php _e('Back to search results', 'pricewise'); ?>
                </a>
            </div>
        </div>
    </div>
</div>