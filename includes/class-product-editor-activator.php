<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 */
class Product_Editor_Activator {

	/**
   * Activate the plugin.
   * crate table for storing old values of changed attributes
	 * @since    1.0.0
	 */
	public static function activate() {
    $version = get_option( 'PRODUCT_EDITOR_VERSION', PRODUCT_EDITOR_VERSION );
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . REVERSE_TABLE;

    $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name varchar(191),
		data longtext,
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    update_option('PRODUCT_EDITOR_VERSION', $version, false);
	}

}
