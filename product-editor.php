<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Product_Editor
 *
 * @wordpress-plugin
 * Plugin Name:       Product Editor
 * Plugin URI:        http://example.com/product-editor-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Your Name or Your Company
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-editor
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRODUCT_EDITOR_VERSION', '1.0.0' );
define( 'REVERSE_TABLE', 'pe_reverse_steps');

require plugin_dir_path( __FILE__ ) . 'helpers/class-general-helper.php';
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-product-editor-activator.php
 */
function activate_product_editor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-editor-activator.php';
	Product_Editor_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-product-editor-deactivator.php
 */
function deactivate_product_editor() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-editor-deactivator.php';
	Product_Editor_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_product_editor' );
register_deactivation_hook( __FILE__, 'deactivate_product_editor' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-product-editor.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_product_editor() {

	$plugin = new Product_Editor();
	$plugin->run();

}
run_product_editor();
