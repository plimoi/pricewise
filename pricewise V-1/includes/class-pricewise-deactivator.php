<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Pricewise
 * @subpackage Pricewise/includes
 */

class Pricewise_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
        
        // We don't remove plugin data on deactivation
        // Data cleanup happens in uninstall.php if the plugin is deleted
    }
}