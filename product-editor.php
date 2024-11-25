<?php
/**
 * @link              https://github.com/dev-hedgehog/product-editor
 * @since             1.0.0
 * @package           Product-Editor
 * @author            dev-hedgehog <dev.hedgehog.core@gmail.com>
 *
 * @wordpress-plugin
 * Plugin Name:       Product Editor
 * Plugin URI:        https://github.com/dev-hedgehog/product-editor
 * Description:       Bulk\individual editing of prices, sale prices and sale dates of woocommerce variable, simple and external products.
 * Version:           1.0.16
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

define('PRODUCT_EDITOR_VERSION', '1.0.17');
// table for storing old values of changed attributes.
define('PRODUCT_EDITOR_REVERSE_TABLE', 'pe_reverse_steps');

define('PRODUCT_EDITOR_SUPPORT_EMAIL', 'dev.hedgehog.core@gmail.com');
define('PRODUCT_EDITOR_VIDEO_URL', 'https://youtu.be/mSM_ndk2z7A');

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

add_filter( 'plugin_action_links', 'action_links_product_editor', 10, 2 );

function action_links_product_editor( $links_array, $plugin_file_name )
{
	if( strpos( $plugin_file_name, basename(__FILE__) ) ) {
		array_unshift($links_array,
			'<a href="' . esc_url( admin_url( '/edit.php?post_type=product&page=product-editor' ) ) . '">' . __( 'Product Editor', 'product-editor' ) . '</a>'
		);
	}
	return $links_array;
}

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
