<?php
/**
 * Template part for displaying the hotel search results
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
$checkin_date = new DateTime($search->checkin);
$checkout_date = new DateTime($search->checkout);
$date_diff = $checkout_date->diff($checkin_date);
$nights = $date_diff->days;

// Pagination variables
$items_per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 10;
$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$total_items = count($hotels);
$total_pages = ceil($total_items / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;
$paged_hotels = array_slice($hotels, $offset, $items_per_page);
?>

<div class="pricewise-search-results">
    <div class="pricewise-search-summary">
        <h2><?php 
            /* translators: %1$d: number of hotels, %2$s: destination name */
            printf(__('Found %1$d hotels in %2$s', 'pricewise'), $total_items, esc_html($search->destination)); 
        ?></h2>
        <p class="pricewise-search-details">
            <?php 
                /* translators: %1$s: check-in date, %2$s: check-out date, %3$d: number of nights */
                printf(
                    __('Stay: %1$s to %2$s (%3$d night%4$s)', 'pricewise'), 
                    esc_html($checkin_date->format('M j, Y')), 
                    esc_html($checkout_date->format('M j, Y')), 
                    $nights,
                    $nights > 1 ? 's' : ''
                ); 
            ?> | 
            <?php 
                /* translators: %1$d: number of adults, %2$d: number of children, %3$d: number of rooms */
                printf(
                    __('Guests: %1$d adult%2$s%3$s in %4$d room%5$s', 'pricewise'), 
                    $search->adults,
                    $search->adults > 1 ? 's' : '',
                    $search->children > 0 ? ', ' . $search->children . ' ' . ($search->children > 1 ? __('children', 'pricewise') : __('child', 'pricewise')) : '',
                    $search->rooms,
                    $search->rooms > 1 ? 's' : ''
                ); 
            ?>
        </p>
    </div>
    
    <div class="pricewise-sorting">
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
            <?php 
            // Add all existing query parameters
            foreach ($_GET as $key => $value) {
                if ($key != 'sort' && $key != 'paged') {
                    echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
            ?>
            <label for="pricewise-sort"><?php _e('Sort by:', 'pricewise'); ?></label>
            <select id="pricewise-sort" name="sort" onchange="this.form.submit()">
                <option value="price_asc" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'price_asc'); ?>>
                    <?php _e('Price (low to high)', 'pricewise'); ?>
                </option>
                <option value="price_desc" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'price_desc'); ?>>
                    <?php _e('Price (high to low)', 'pricewise'); ?>
                </option>
                <option value="rating_desc" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'rating_desc'); ?>>
                    <?php _e('Rating (high to low)', 'pricewise'); ?>
                </option>
                <option value="stars_desc" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'stars_desc'); ?>>
                    <?php _e('Stars (high to low)', 'pricewise'); ?>
                </option>
                <option value="name_asc" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'name_asc'); ?>>
                    <?php _e('Name (A to Z)', 'pricewise'); ?>
                </option>
            </select>
        </form>
    </div>
    
    <div class="pricewise-results-list">
        <?php foreach ($paged_hotels as $hotel) : ?>
            <div class="pricewise-hotel-item">
                <div class="pricewise-hotel-image">
                    <?php if (!empty($hotel->image_url)) : ?>
                        <img src="<?php echo esc_url($hotel->image_url); ?>" alt="<?php echo esc_attr($hotel->name); ?>" />
                    <?php else : ?>
                        <div class="pricewise-no-image"><?php _e('No image available', 'pricewise'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="pricewise-hotel-details">
                    <h3 class="pricewise-hotel-name">
                        <?php if (isset($hotel->id)) : ?>
                            <a href="<?php 
                                echo esc_url(add_query_arg(array(
                                    'hotel_id' => $hotel->id,
                                    'checkin' => $search->checkin,
                                    'checkout' => $search->checkout,
                                    'adults' => $search->adults,
                                    'children' => $search->children,
                                    'children_ages' => implode(',', $search->children_ages),
                                    'rooms' => $search->rooms,
                                ), get_permalink()));
                            ?>">
                                <?php echo esc_html($hotel->name); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($hotel->name); ?>
                        <?php endif; ?>
                    </h3>
                    
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
                
                <div class="pricewise-hotel-pricing">
                    <?php if (isset($hotel->prices) && !empty($hotel->prices['lowest'])) : ?>
                        <div class="pricewise-price-container">
                            <div class="pricewise-price-for">
                                <?php
                                /* translators: %d: number of nights */
                                printf(_n('Price for %d night', 'Price for %d nights', $nights, 'pricewise'), 
                                    $nights); 
                                ?>
                            </div>
                            
                            <div class="pricewise-lowest-price">
                                <span class="pricewise-price"><?php echo esc_html($hotel->prices['lowest']['price']); ?></span>
                                <span class="pricewise-provider"><?php 
                                    /* translators: %s: partner/provider name */
                                    printf(__('via %s', 'pricewise'), esc_html($hotel->prices['lowest']['partner'])); 
                                ?></span>
                            </div>
                            
                            <a href="<?php echo esc_url($hotel->prices['lowest']['deeplink']); ?>" class="pricewise-view-deal" target="_blank" rel="nofollow">
                                <?php _e('View Deal', 'pricewise'); ?>
                            </a>
                        </div>
                        
                        <?php if (count($hotel->prices) > 1) : ?>
                            <div class="pricewise-other-providers">
                                <?php
                                $other_prices = array_filter($hotel->prices, function($key) {
                                    return $key !== 'lowest';
                                }, ARRAY_FILTER_USE_KEY);
                                
                                // Only show up to 3 other providers
                                $other_prices = array_slice($other_prices, 0, 3);
                                
                                if (!empty($other_prices)) :
                                ?>
                                    <div class="pricewise-more-prices">
                                        <button class="pricewise-show-more"><?php _e('More prices', 'pricewise'); ?></button>
                                        <div class="pricewise-other-prices-list" style="display: none;">
                                            <?php foreach ($other_prices as $key => $price) : ?>
                                                <div class="pricewise-other-price-item">
                                                    <span class="pricewise-other-provider"><?php echo esc_html($price['partner']); ?></span>
                                                    <span class="pricewise-other-price"><?php echo esc_html($price['price']); ?></span>
                                                    <a href="<?php echo esc_url($price['deeplink']); ?>" class="pricewise-other-deal" target="_blank" rel="nofollow">
                                                        <?php _e('View', 'pricewise'); ?>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="pricewise-no-price">
                            <p><?php _e('Price information unavailable', 'pricewise'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($total_pages > 1) : ?>
        <div class="pricewise-pagination">
            <?php 
            // Build the pagination links
            $pagination_args = array();
            foreach ($_GET as $key => $value) {
                if ($key != 'paged') {
                    $pagination_args[$key] = $value;
                }
            }
            
            $current_url = get_permalink();
            ?>
            
            <ul class="pricewise-pagination-list">
                <?php if ($current_page > 1) : ?>
                    <li class="pricewise-pagination-prev">
                        <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_args, array('paged' => $current_page - 1)), $current_url)); ?>">
                            <?php _e('Previous', 'pricewise'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php 
                // Determine pagination range
                $range = 2; // How many pages to show on each side of current page
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);
                
                // Always show first page
                if ($start_page > 1) : ?>
                    <li class="pricewise-pagination-item">
                        <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_args, array('paged' => 1)), $current_url)); ?>">
                            1
                        </a>
                    </li>
                    <?php if ($start_page > 2) : ?>
                        <li class="pricewise-pagination-ellipsis">...</li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                    <li class="pricewise-pagination-item<?php echo $i == $current_page ? ' pricewise-pagination-current' : ''; ?>">
                        <?php if ($i == $current_page) : ?>
                            <span><?php echo $i; ?></span>
                        <?php else : ?>
                            <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_args, array('paged' => $i)), $current_url)); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>
                
                <?php 
                // Always show last page
                if ($end_page < $total_pages) : ?>
                    <?php if ($end_page < $total_pages - 1) : ?>
                        <li class="pricewise-pagination-ellipsis">...</li>
                    <?php endif; ?>
                    <li class="pricewise-pagination-item">
                        <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_args, array('paged' => $total_pages)), $current_url)); ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li class="pricewise-pagination-next">
                        <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_args, array('paged' => $current_page + 1)), $current_url)); ?>">
                            <?php _e('Next', 'pricewise'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>