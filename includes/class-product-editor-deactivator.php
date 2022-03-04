<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 
 */
class Product_Editor_Deactivator {

	/**
	 * Drop table REVERSE_TABLE
   *
	 * @since    1.0.0
	 */
	public static function deactivate() {
    global $wpdb;
    $table_name = $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE;
    $sql = "DROP TABLE IF EXISTS $table_name";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    delete_option('PRODUCT_EDITOR_VERSION');
	}

}
