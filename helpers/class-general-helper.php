<?php
/**
 * The file that defines the helper plugin class
 *
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/helpers
 */

class General_Helper {

  /**
   * Returns the GET value by a key
   * @param $key
   * @param string $default
   * @return mixed|string
   */
  static public function getVar( $key, $default = '' ) {
    $value = filter_input( INPUT_GET, $key );
    return $value ? $value : $default;
  }

  /**
   * Returns the POST value by a key
   * @param $key
   * @param string $default
   * @return mixed|string
   */
  static public function postVar( $key, $default = '' ) {
    $value = null;
    isset($_POST[$key]) && $value = $_POST[$key];
    return $value ? $value : $default;
  }
}