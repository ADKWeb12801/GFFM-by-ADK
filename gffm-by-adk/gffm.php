<?php
/**
 * Plugin Name:       GFFM by ADK
 * Plugin URI:        https://gffm.org
 * Description:       A complete vendor and market management system for the Glens Falls Farmers' Market.
 * Version:           3.0.0
 * Author:            ADK Web Solutions
 * Author URI:        https://adkwebsolutions.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gffm-plugin
 * Domain Path:       /languages
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('GFFM_VERSION', '3.0.0');
define('GFFM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GFFM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GFFM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Composer Autoload
if (file_exists(GFFM_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once GFFM_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('GFFM Plugin Error: Composer dependencies not found. Please run "composer install" in the plugin directory.', 'gffm-plugin');
        echo '</p></div>';
    });
    return;
}

/**
 * The code that runs during plugin activation.
 */
function gffm_activate_plugin() {
    GFFM\Includes\Setup\Installer::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function gffm_deactivate_plugin() {
    GFFM\Includes\Setup\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'gffm_activate_plugin');
register_deactivation_hook(__FILE__, 'gffm_deactivate_plugin');

/**
 * Begins execution of the plugin.
 */
function gffm_run_plugin() {
    try {
        $plugin = new GFFM\Includes\Core();
        $plugin->run();
    } catch (Exception $e) {
        error_log('GFFM Plugin failed to initialize: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>GFFM Plugin Error: ' . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}

gffm_run_plugin();
