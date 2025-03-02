<?php
/**
 * Template part for displaying the hotel search form
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

// Determine the form action (search results page)
$results_page = !empty($atts['results_page']) ? esc_url($atts['results_page']) : get_permalink();
?>

<div class="pricewise-search-form">
    <form id="pricewise-hotel-search" method="get" action="<?php echo esc_url($results_page); ?>">
        
        <div class="pricewise-form-row">
            <div class="pricewise-form-field pricewise-destination-field">
                <label for="pricewise-destination"><?php _e('Destination', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <input 
                        type="text" 
                        id="pricewise-destination" 
                        name="destination" 
                        value="<?php echo esc_attr($destination); ?>" 
                        placeholder="<?php _e('Enter a city or hotel name', 'pricewise'); ?>" 
                        required
                    />
                    <input type="hidden" id="pricewise-entity-id" name="entity_id" value="<?php echo esc_attr($entity_id); ?>" />
                </div>
            </div>
        </div>
        
        <div class="pricewise-form-row">
            <div class="pricewise-form-field pricewise-date-field">
                <label for="pricewise-checkin"><?php _e('Check-in', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <input 
                        type="text" 
                        id="pricewise-checkin" 
                        name="checkin" 
                        value="<?php echo esc_attr($checkin); ?>" 
                        class="pricewise-datepicker" 
                        placeholder="<?php _e('Select date', 'pricewise'); ?>" 
                        required
                        autocomplete="off"
                    />
                </div>
            </div>
            
            <div class="pricewise-form-field pricewise-date-field">
                <label for="pricewise-checkout"><?php _e('Check-out', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <input 
                        type="text" 
                        id="pricewise-checkout" 
                        name="checkout" 
                        value="<?php echo esc_attr($checkout); ?>" 
                        class="pricewise-datepicker" 
                        placeholder="<?php _e('Select date', 'pricewise'); ?>" 
                        required
                        autocomplete="off"
                    />
                </div>
            </div>
        </div>
        
        <div class="pricewise-form-row">
            <div class="pricewise-form-field pricewise-guests-field">
                <label for="pricewise-adults"><?php _e('Adults', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <select id="pricewise-adults" name="adults">
                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php selected($adults, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="pricewise-form-field pricewise-guests-field">
                <label for="pricewise-children"><?php _e('Children', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <select id="pricewise-children" name="children">
                        <?php for ($i = 0; $i <= 6; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php selected($children, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="pricewise-form-field pricewise-guests-field">
                <label for="pricewise-rooms"><?php _e('Rooms', 'pricewise'); ?></label>
                <div class="pricewise-input-wrapper">
                    <select id="pricewise-rooms" name="rooms">
                        <?php for ($i = 1; $i <= 8; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php selected($rooms, $i); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <?php if ($children > 0) : ?>
            <div class="pricewise-form-row pricewise-children-ages" id="pricewise-children-ages-container">
                <h4><?php _e('Children Ages', 'pricewise'); ?></h4>
                <div class="pricewise-ages-fields">
                    <?php for ($i = 0; $i < $children; $i++) : ?>
                        <div class="pricewise-form-field pricewise-age-field">
                            <label for="pricewise-child-age-<?php echo $i; ?>"><?php 
                                /* translators: %d: child number */
                                printf(__('Child %d', 'pricewise'), $i + 1); 
                            ?></label>
                            <div class="pricewise-input-wrapper">
                                <select 
                                    id="pricewise-child-age-<?php echo $i; ?>" 
                                    name="child_age[]" 
                                    class="pricewise-child-age"
                                    data-index="<?php echo $i; ?>"
                                >
                                    <?php for ($age = 1; $age <= 17; $age++) : ?>
                                        <option value="<?php echo $age; ?>" <?php 
                                            selected(isset($children_ages[$i]) ? $children_ages[$i] : 5, $age); 
                                        ?>><?php echo $age; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="pricewise-children-ages" name="children_ages" value="<?php echo esc_attr(implode(',', $children_ages)); ?>" />
            </div>
        <?php endif; ?>
        
        <div class="pricewise-form-row pricewise-submit-row">
            <button type="submit" class="pricewise-search-button">
                <?php _e('Search Hotels', 'pricewise'); ?>
            </button>
        </div>
    </form>
    
    <div id="pricewise-search-error" class="pricewise-error" style="display: none;"></div>
</div>