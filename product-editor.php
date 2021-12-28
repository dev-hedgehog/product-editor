<?php
/**
 * @link              https://github.com/dev-hedgehog/product-editor
 * @since             1.0.0
 * @package           Product-Editor
 *
 * @wordpress-plugin
 * Plugin Name:       Product Editor
 * Plugin URI:        https://github.com/dev-hedgehog/product-editor
 * Description:       The free plugin for Woo provides the ability to bulk\individually edit prices, sales prices and sale dates for simple and variable woocommerce products.
 * Version:           1.0.0
 * Author:            dev-hedgehog
 * Author URI:        https://github.com/dev-hedgehog
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-editor
 * Domain Path:       /languages
 * WC requires at least: 4.5
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

define('PRODUCT_EDITOR_VERSION', '1.0.0');
// table for storing old values of changed attributes.
define('REVERSE_TABLE', 'pe_reverse_steps');

require plugin_dir_path(__FILE__) . 'helpers/class-general-helper.php';

function activate_product_editor()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-product-editor-activator.php';
    Product_Editor_Activator::activate();
}

function deactivate_product_editor()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-product-editor-deactivator.php';
    Product_Editor_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_product_editor');
register_deactivation_hook(__FILE__, 'deactivate_product_editor');

// The core plugin class.
require plugin_dir_path(__FILE__) . 'includes/class-product-editor.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_product_editor()
{
    $plugin = new Product_Editor();
    $plugin->run();
}

run_product_editor();
