<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           Pricewise
 *
 * @wordpress-plugin
 * Plugin Name:       Pricewise
 * Plugin URI:        https://example.com/pricewise
 * Description:       A plugin for hotel price comparison using SkyScanner API.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pricewise
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('PRICEWISE_VERSION', '1.0.0');
define('PRICEWISE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PRICEWISE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRICEWISE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_pricewise() {
    require_once PRICEWISE_PLUGIN_DIR . 'includes/class-pricewise-activator.php';
    Pricewise_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_pricewise() {
    require_once PRICEWISE_PLUGIN_DIR . 'includes/class-pricewise-deactivator.php';
    Pricewise_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_pricewise');
register_deactivation_hook(__FILE__, 'deactivate_pricewise');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require PRICEWISE_PLUGIN_DIR . 'includes/class-pricewise.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_pricewise() {
    $plugin = new Pricewise();
    $plugin->run();
}

run_pricewise();