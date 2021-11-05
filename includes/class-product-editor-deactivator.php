<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Product_Editor
 * @subpackage Product_Editor/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Product_Editor
 * @subpackage Product_Editor/includes
 * @author     Your Name <email@example.com>
 */
class Product_Editor_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
    global $wpdb;
    $table_name = $wpdb->prefix . REVERSE_TABLE;
    $sql = "DROP TABLE IF EXISTS $table_name";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    delete_option('PRODUCT_EDITOR_VERSION');
	}

}
