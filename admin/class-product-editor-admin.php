<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin
 
 */
class Product_Editor_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      array|null    $reverse_steps    The current version of this plugin.
   */
	private $reverse_steps;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/product-editor-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/product-editor-admin.js', array( 'jquery' ), $this->version, false );

	}

  /**
   * Create session if doesn't exists
   *
   * @since    1.0.0
   */
	public function start_session() {
    if(!session_id()) {
      session_start();
    }
  }

  /**
   * Adds menu items
   *
   * @since    1.0.0
   */
	public function admin_menu() {
    if (!current_user_can('manage_woocommerce')) {
      return;
    }
    add_submenu_page('edit.php?post_type=product', __('Product Editor', 'product-editor'), __('Product Editor', 'product-editor'),
      'manage_options', 'product-editor', [$this, 'main_page']);
    add_submenu_page('edit.php?post_type=product', 'Fix attrs', 'Fix attributes',
      'manage_options', 'product-editor-fix-variations', [$this, 'sub_page']);
  }

  public function sub_page() {
    self::securityCheck(true);
    $args = [
      'type' => ['variable'],
      //'limit' => 2
    ];
    $products = wc_get_products($args);
    $bad_products = [];
    $bad_terms = [];
    $all_terms = [];
    foreach ($products as $product) {
      $vars = $product->get_available_variations('object');
      $prod_terms = [];
      foreach ($vars as $var) {
        $at = wc_get_product_variation_attributes($var->get_id());
        foreach ($at as $att_name => $value) {
          if (!$value) continue;
          $term_name = str_replace('attribute_', '', $att_name);
          if (!isset($prod_terms[$term_name])) {
            $prod_terms[$term_name] = wp_get_post_terms($product->get_id(), $term_name, array('fields' => 'slugs'));
          }
          if (!in_array($value, $prod_terms[$term_name])) {
            $bad_products[$product->get_id()][$var->get_id()][] = $term_name.':'.$value;
            if (!isset($all_terms[$term_name])) {
              $all_terms[$term_name] = get_terms(['taxonomy' => $term_name, 'hide_empty' => false, 'fields' => 'slugs']);
            }
            if (!in_array($value, $all_terms[$term_name])) {
              $bad_terms[$term_name][$value] = 1;
            } elseif (!empty($_GET['doitg'])) {
              wp_set_object_terms($product->get_id(), $value, $term_name, true);
            }
          }
        }

      }
      //break;
    }
    echo '<pre>';
    print_r($bad_products);
    echo "\n====================\n";
    print_r($bad_terms);
  }

  /**
   * Home page handler in admin area
   *
   * @since    1.0.0
   */
  public function main_page() {
    self::securityCheck(true);
    global $wpdb;
    global $wp_query;

    // We get products that match the passed parameters
    $args = [
      'paginate' => true,
       'type' => ['simple','variable']
    ];
    $args['limit'] = (int) General_Helper::getVar('limit', 10);
    $args['offset'] = (General_Helper::getVar('paged', 1)-1) * $args['limit'];
    General_Helper::getVar('product_cat', false) && $args['category'] = [sanitize_title_for_query(General_Helper::getVar('product_cat'))];
    General_Helper::getVar('s', false) && $args['name'] = sanitize_title_for_query(General_Helper::getVar('s'));
    $results = wc_get_products($args);
    // if the search for an exact match of the name did not give any results, we are looking for an inaccurate
    if ($results->total === 0 && $args['name']) {
      $args['s'] = $args['name'];
      unset($args['name']);
      $results = wc_get_products($args);
    }
    // variables for template
    $total = $results->total;
    $num_of_pages = $results->max_num_pages;
    $products = $results->products;
    $num_on_page = sizeof($products);
    $show_variations = (int)General_Helper::getVar('show_variations');
    $product_categories = get_terms(['taxonomy' => 'product_cat',]);

    include ('partials/product-editor-admin-display.php');
  }

  /**
   * An array of mappings of action requests and functions that perform them
   *
   * @var string[]
   */
  public static $changeActions = [
    'change_regular_price' => 'change_regular_price',
    'change_sale_price' => 'change_sale_price',
    'change_akciya'  => 'change_akciya',
  ];

  /**
   * The handler that implements the rollback of the last change
   *
   * @since    1.0.0
   */
	public function action_reverse_products_data() {
    self::securityCheck(true, true);
	  if (empty($_SESSION['reverse_steps'])) {
      self::sendResponse(['message' => __('No data to recover', 'product-editor')], 409);
    }
    global $wpdb;
	  $products = [];
    $wpdb->query("START TRANSACTION");
    // each record contains information on changing one attribute of the product
	  foreach ($_SESSION['reverse_steps'] as $record) {
	    if (!empty($products[$record['id']])) {
        $product = $products[$record['id']];
      } else {
        $product = $products[$record['id']] = wc_get_product($record['id']);
        if (!$product) continue;
      }

	    switch ($record['action']) {
        case 'change_akciya': $product->update_meta_data('sale', $record['value']);
        break;
        case 'change_sale_price': $product->set_sale_price($record['value']);
        break;
        case 'change_regular_price': $product->set_regular_price($record['value']);
        break;
      }
	    $product->save();
    }
    $wpdb->query("COMMIT");
    $_SESSION['reverse_steps'] = null;
    self::sendResponse('ok', 200, 'raw');
  }

  /**
   * The handler that returns (HTML) variations of a variable product. $_GET['id'] - variable product id
   *
   * @since    1.0.0
   */
	public function action_expand_product_variable() {
    self::securityCheck(true);
    if (!($id = sanitize_key(General_Helper::getVar('id'))) || !($product = wc_get_product($id)) || !is_a($product, 'WC_Product_Variable')) {
      self::sendResponse('', 200, 'raw');
    }

    self::sendResponse(include ('partials/product-editor-admin-table-variations-rows.php'), 200, 'raw');
	}

  /**
   * Product Change Request Handler
   *
   * @since    1.0.0
   */
  public function action_bulk_changes() {
    self::securityCheck(true, true);
    // check input data
    $isEmpty = true;
    $ids = (array)General_Helper::postVar('ids');
    foreach (self::$changeActions as $action_name => $func_name) {
      if (General_Helper::postVar($action_name)) {
        $isEmpty = false;
      }
    }
    if ($isEmpty || empty($ids)) {
      self::sendResponse(['message' => __('Nothing to change', 'product-editor'), 'content'=>[]]);
    }

    global $wpdb;
    // the request must be applied in full or not at all
    $wpdb->query("START TRANSACTION");

    // walk through each product and apply the requested operations
    foreach ($ids as $id) {
      $id = sanitize_key($id);
      $product = wc_get_product($id);
      if (!$product) {
        self::sendResponse(
          ['message' => sprintf(__('Product with id:%s not found. Operations canceled.', 'product-editor'), $id)],
          500
        );
      }
      $this->process_change_product($product);
    }
    // if changes were made, save the previous values to the database
    if ($this->reverse_steps) {
      $table_name = $wpdb->prefix . REVERSE_TABLE;
      $wpdb->insert(
        $table_name,
        array(
          'time' => current_time('mysql'),
          'name' => current_time('mysql'),
          'data' => json_encode($this->reverse_steps),
        )
      );
    }
    $wpdb->query("COMMIT");
    if (!$this->reverse_steps) {
      $this->reverse_steps = [];
    }
    $_SESSION['reverse_steps'] = $this->reverse_steps;

    // response new products data
    self::sendResponse([
      'message' => sprintf(__('Operations applied: %s', 'product-editor'), sizeof($this->reverse_steps)),
      'content' => self::response_data_for_ids($ids),
      'reverse' => !empty($this->reverse_steps)
    ]);
  }

  /**
   * @param $product
   *
   * Applies the requested change operations to the product
   *
   * @since   1.0.0
   */
  private function process_change_product($product) {
    // self::$changeActions - an array of mappings of action requests and functions that perform them
    foreach (self::$changeActions as $action_name => $func_name) {
      if (General_Helper::postVar($action_name)) {
        $this->$func_name($product);
      }
    }
    // save model after all changes
    $product->save();
  }

  /**
   * Creates an array of data for the frontend for the specified product ids
   *
   * @param $ids
   * @return array
   *
   * @since    1.0.0
   */
  private static function response_data_for_ids($ids) {
    $response_data = [];
    $extra_ids = [];
    foreach ($ids as $id) {
      $product = wc_get_product($id);
      // for variations, we also add their parent product to the output list, if it is not already added or is not in ids list
      if (is_a($product, 'WC_Product_Variation') && !in_array($product->get_parent_id(), $ids) && !in_array($product->get_parent_id(), $extra_ids)) {
        $extra_ids[] = $product->get_parent_id();
        $response_data[] = self::response_data_for_product(wc_get_product($product->get_parent_id()));
      }

      $response_data[] = self::response_data_for_product($product);
    }
    return $response_data;
  }

  /**
   * Creates a frontend dataset for a specific product
   *
   * @param $product
   * @return array
   *
   * @since    1.0.0
   */
  private static function response_data_for_product($product) {
    return [
      'id' => $product->get_id(),
      'price' => $product->get_price_html(),
      'regular_price' => $product->get_regular_price(),
      'sale_price' => $product->get_sale_price(),
      'akciya' => is_a($product, 'WC_Product_Variation') ? '' : (!$product->get_meta('sale')? 'Нет': 'Да'),
    ];
  }


  private function change_akciya($product) {
    $action = General_Helper::postVar('change_akciya');
    if (empty($action) || is_a($product, 'WC_Product_Variation')) {
      return;
    }
    $this->reverse_steps[] = [
      'id' => $product->get_id(),
      'action' => 'change_akciya',
      'value' => $product->get_meta('sale')
    ];
    switch ((int)$action) {
      case 1: $product->update_meta_data('sale', ['Товар по акции']);
      break;
      case 2: $product->update_meta_data('sale', '');
    }
  }

  /**
   * Handler function for the action to change a sale price. Data for the operation is taken from POST request
   * The handler is registered with self::$changeActions
   *
   * @param $product
   *
   * @since    1.0.0
   */
  private function change_sale_price($product) {
    $arg_sale_price = trim(General_Helper::postVar('_sale_price', 0));
    $action = General_Helper::postVar('change_sale_price');
    if (empty($action)) {
      return;
    }
    // Save the value before the changes, to be able to roll back the changes
    $this->reverse_steps[] = [
      'id' => $product->get_id(),
      'action' => 'change_sale_price',
      'value' => $product->get_sale_price()
    ];
    $isPercentage = stripos($arg_sale_price, '%') !== false;
    $arg_sale_price = preg_replace('/[^\d\.\-]/', '', $arg_sale_price);
    $regular_price = (float)$product->get_regular_price();
    $old_sale_price = (float)$product->get_sale_price();
    $new_sale_price = $old_sale_price;
    $number = (float) wc_format_decimal($arg_sale_price);
    switch ((int)$action) {
      case 1:
        // Change to
        $new_sale_price = $number;
        break;
      case 2:
        // Increase existing sale price by (fixed amount or %)
        $new_sale_price = $old_sale_price + ($isPercentage ? $old_sale_price/100*$number : $number);
        break;
      case 3:
        // Decrease existing sale price by (fixed amount or %)
        $new_sale_price = $old_sale_price - ($isPercentage ? $old_sale_price/100*$number : $number);
        break;
      case 4:
        // Set to regular price decreased by (fixed amount or %)
        $new_sale_price = $regular_price - ($isPercentage ? $regular_price/100*$number : $number);
        break;
    }
    if ($new_sale_price <= 0) {
      $new_sale_price = '';
    }
    $product->set_sale_price($new_sale_price);
  }

  /**
   * Handler function for the action to change a regular price. Data for the operation is taken from POST request
   * The handler is registered with self::$changeActions
   *
   * @param $product
   *
   * @since    1.0.0
   */
  private function change_regular_price($product) {
    $arg_regular_price = trim(General_Helper::postVar('_regular_price'));
    $action = General_Helper::postVar('change_regular_price');
    if (empty($action)) {
      return;
    }
    // Save the value before the changes, to be able to roll back the changes
    $this->reverse_steps[] = [
      'id' => $product->get_id(),
      'action' => 'change_regular_price',
      'value' => $product->get_regular_price()
    ];
    $isPercentage = stripos($arg_regular_price, '%') !== false;
    $arg_regular_price = preg_replace('/[^\d\.\-]/', '', $arg_regular_price);
    $old_regular_price = $product->get_regular_price();
    $new_regular_price = $old_regular_price;
    $number = (float) wc_format_decimal($arg_regular_price);
    switch ((int)$action) {
      case 1:
        // Change to
        $new_regular_price = $number;
        break;
      case 2:
        // Increase existing price by (fixed amount or %)
        $new_regular_price = $old_regular_price + ($isPercentage ? $old_regular_price/100*$number : $number);
        break;
      case 3:
        // Decrease existing price by (fixed amount or %)
        $new_regular_price = $old_regular_price - ($isPercentage ? $old_regular_price/100*$number : $number);
        break;
    }
    if ($new_regular_price <= 0 || $new_regular_price == '') {
      self::sendResponse(
        ['message' =>
          sprintf(__('Invalid price computed for product "%1$s": "%2$s". Operations canceled.', 'product-editor'),
            $product->get_name(), $new_regular_price)
        ],409);
    }
    $product->set_regular_price($new_regular_price);
  }

  /**
   * Common function for send response
   *
   * @param array $body
   * @param int $code
   * @param string $format
   *
   * @since    1.0.0
   */
  private static function sendResponse($body = [], $code = 200, $format='json') {
    status_header($code);
    exit($format=='json'? json_encode($body) : $body);
  }

  /**
   * Guard helper
   *
   * @param bool $check_read
   * @param false $check_change
   *
   * @since    1.0.0
   */
  private static function securityCheck($check_read = true, $check_change = false) {
    if ($check_read) {
      if (!current_user_can('manage_woocommerce')) {
        self::sendResponse(['message' => __('You do not have permission to edit products', 'product-editor')], 403);
      }
    }
    if ($check_change) {
      if (!wp_verify_nonce(General_Helper::postVar('nonce'), 'pe_changes' ) ) {
        self::sendResponse(['message' => __('Incorrect authorization key. Refresh the page.', 'product-editor')], 401);
      }
    }
  }
}
