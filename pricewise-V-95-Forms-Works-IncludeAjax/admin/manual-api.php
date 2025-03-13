<?php
/**
 * Manual API loader file
 * This file now serves as a loader for the restructured manual API functionality
 * 
 * @package PriceWise
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include the main manual API file
require_once plugin_dir_path( __FILE__ ) . 'manual-api/main-manual-api.php';