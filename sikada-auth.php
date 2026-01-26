<?php
/**
 * Plugin Name: Sikada Authorization
 * Plugin URI: https://sikadaworks.com/sikada-auth
 * Description: WordPress authentication and authorization plugin
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Sikada Works
 * Author URI: https://sikadaworks.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sikada-auth
 * Domain Path: /languages
 */

namespace SikadaWorks\SikadaAuth;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SIKADA_AUTH_VERSION', '1.0.2');
define('SIKADA_AUTH_PLUGIN_FILE', __FILE__);
define('SIKADA_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIKADA_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Initialize plugin
 *
 * @since 1.0.0
 */
function init_plugin()
{
    if (class_exists('\\SikadaWorks\\SikadaAuth\\Core\\Plugin')) {
        \SikadaWorks\SikadaAuth\Core\Plugin::get_instance();
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\init_plugin');

/**
 * Activation hook
 *
 * @since 1.0.0
 */
register_activation_hook(__FILE__, function () {
    if (class_exists('\\SikadaWorks\\SikadaAuth\\Core\\Plugin')) {
        \SikadaWorks\SikadaAuth\Core\Plugin::activate();
    }
});

/**
 * Deactivation hook
 *
 * @since 1.0.0
 */
register_deactivation_hook(__FILE__, function () {
    if (class_exists('\\SikadaWorks\\SikadaAuth\\Core\\Plugin')) {
        \SikadaWorks\SikadaAuth\Core\Plugin::deactivate();
    }
});
