<?php

class General_Helper {

  static public function getVar( $key, $default = '' ) {
    $value = filter_input( INPUT_GET, $key );
    return $value ? $value : $default;
  }

  static public function postVar( $key, $default = '' ) {
    //$value = filter_input( INPUT_POST, $key, FILTER_DEFAULT , FILTER_REQUIRE_ARRAY );
    $value = null;
    isset($_POST[$key]) && $value = $_POST[$key];
    return $value ? $value : $default;
  }
}