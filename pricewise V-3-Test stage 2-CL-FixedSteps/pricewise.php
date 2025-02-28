<?php
/*
Plugin Name: PriceWise
Description: A basic hotel price comparison plugin.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'PRICEWISE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRICEWISE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Enqueue external CSS + JS
function pricewise_enqueue_scripts() {
    // CSS
    wp_enqueue_style( 'pricewise-main-style', PRICEWISE_PLUGIN_URL . 'style/main-style.css' );

    // JS
    wp_enqueue_script( 'pricewise-main-js', PRICEWISE_PLUGIN_URL . 'js/main-script.js', array('jquery'), '1.0', true );
    wp_localize_script( 'pricewise-main-js', 'pricewise_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ) );
}
add_action( 'wp_enqueue_scripts', 'pricewise_enqueue_scripts' );

// Include API and admin settings file
require_once PRICEWISE_PLUGIN_DIR . 'includes/main-api.php';

// Shortcode function to display the search form
function pricewise_form_shortcode() {
    ob_start();
    include PRICEWISE_PLUGIN_DIR . 'includes/search-form.php';
    return ob_get_clean();
}
add_shortcode( 'pricewise_form', 'pricewise_form_shortcode' );
