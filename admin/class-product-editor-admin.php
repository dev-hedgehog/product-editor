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
	 * Array of data for reverse.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array|null    $reverse_steps    Array of data for reverse.
	 */
	private $reverse_steps;

    /**
     * File handle for progress file.
     *
     * @since 1.0.4
     * @access private
     * @var resource|false|null $progress_tmp_handle    File handle for progress file.
     */
	private $progress_tmp_handle = null;

	/**
	 * An array of mappings of action requests and functions that perform them
	 *
	 * @var string[]
	 */
	public static $change_actions = array(
		'change_regular_price'     => 'change_regular_price',
		'change_sale_price'        => 'change_sale_price',
		'change_date_on_sale_from' => 'change_date_on_sale_from',
		'change_date_on_sale_to'   => 'change_date_on_sale_to',
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$min = defined( SCRIPT_DEBUG ) && SCRIPT_DEBUG ? '' : '.min';
        wp_register_style( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'libs/jquery-ui-1.13.0/jquery-ui' . $min .'.css' );
        wp_register_style( 'tipTip', plugin_dir_url( __FILE__ ) . 'libs/tipTip/tipTip.css' );
        wp_register_style( 'selectPage', plugin_dir_url( __FILE__ ) . 'libs/selectPage/selectpage.css' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/product-editor-admin.css', array( 'jquery-ui', 'tipTip', 'selectPage' ), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        wp_register_script( 'tipTip', plugin_dir_url( __FILE__ ) . 'libs/tipTip/tipTip.min.js', array( 'jquery' ) );
        wp_register_script( 'selectPage', plugin_dir_url( __FILE__ ) . 'libs/selectPage/selectpage.js', array( 'jquery' ) );
        wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/product-editor-admin.js', array( 'jquery', 'tipTip', 'jquery-ui-datepicker', 'selectPage' ), $this->version, false );
        $translation_array = array(
            'str_items_count' => __( 'Items: ', 'product-editor' ),
            'str_undo' => __( 'Undo the change: ', 'product-editor' ),
            'str_reverse_dialog' => __( 'Item data that has been changed in the operation you are about to cancel will be overwritten by the data that preceded it. If any of the products has been edited outside the plugin, its data may be overwritten. Are you sure you want to return values for products?', 'product-editor' ),
        );
        wp_localize_script( $this->plugin_name, 'product_editor_object', $translation_array );
        $selectpage_translation_array = include __DIR__ . "/libs/selectPage/selectpage-js-localize.php";
        wp_localize_script( 'selectPage', 'selectpage_object', $selectpage_translation_array );
        wp_enqueue_script( $this->plugin_name );
	}

	/**
	 * Enqueue stylesheets and scripts.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_assets() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

    /**
     * Returns filename for tmp progress file
     *
     * @return false|string
     * @since   1.0.4
     */
    private function get_progress_filename () {
        $process_id = preg_replace('/[^\d]/', '', General_Helper::get_or_post_var('process_id'));
        if ( ! empty ( $process_id ) ) {
            return get_temp_dir() . $process_id;
        } else {
            return false;
        }
    }

    /**
     * Returns file handle for tmp progress file
     *
     * @return false|resource|null
     * @since   1.0.4
     */
    private function get_progress_file_handle () {
        if ( ! empty( $this->progress_tmp_handle ) || $this->progress_tmp_handle === false ) {
            return $this->progress_tmp_handle;
        } else {
            if ( ( $progress_tmp_file = $this->get_progress_filename() ) ) {
                if ( $this->progress_tmp_handle = $fp = @fopen($progress_tmp_file, 'wr') ) {
                    register_shutdown_function(function () use ($progress_tmp_file, $fp) {
                        @fclose($fp);
                        @unlink($progress_tmp_file);
                    });
                }
            } else {
                $this->progress_tmp_handle = false;
            }
        }
        return $this->progress_tmp_handle;
    }

    /**
     * Write to tmp progress file
     *
     * @param $data
     * @param int $offset
     * @since   1.0.4
     */
    private function write_progress_file ( $data, $offset = 0 ) {
        if ( $fp = $this->get_progress_file_handle() ) {
            @fseek( $fp, $offset );
            @fwrite( $fp, $data );
        }
    }

    /**
     * Returns content of tmp progress file
     *
     * @return false|string
     * @since   1.0.4
     */
    private function read_progress_file () {
        return @file_get_contents( $this->get_progress_filename() );
    }

	/**
	 * Adds menu items
	 *
	 * @since    1.0.0
	 */
	public function admin_menu() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$hookname = add_submenu_page(
			'edit.php?post_type=product',
			__( 'Product Editor', 'product-editor' ),
			__( 'Product Editor', 'product-editor' ),
			'manage_options',
			'product-editor',
			array( $this, 'main_page' )
		);

		add_action( 'load-' . $hookname, array( $this, 'add_screen_help' ) );
		add_action( "admin_print_scripts-$hookname", array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Adds a help tab to the screen
	 *
	 * @since    1.0.0
	 */
	public function add_screen_help() {
		get_current_screen()->add_help_tab(
			array(
				'id'      => 'common-help',
				'title'   => __( 'Common help', 'product-editor' ),
				'content' =>
                    '<p>' . sprintf( wp_kses(
                        __( 'If you are not familiar with the plugin, please watch this <a href="%s" target="_blank">video</a> first.<br/> If you still have questions, you can ask them under the video, on the plugin\'s <a href="%s" target="_blank">forum</a> or email <a href="mailto:%s">%s</a>.', 'product-editor' ),
                        array(  'a' => array( 'href' => array() ), 'br' => array() ) ),
                        // video url
                        PRODUCT_EDITOR_VIDEO_URL,
                        // forum url
                        'https://wordpress.org/support/plugin/product-editor/',
                        // email
                        PRODUCT_EDITOR_SUPPORT_EMAIL, PRODUCT_EDITOR_SUPPORT_EMAIL
                    ) . '</p>'
                    . '<p>' . __( 'Column "Displayed price" is the price as the user sees it.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'A variable product consists of a base product and its child variations.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'Variable product base has no price or sale price.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'To change the price of variable products, change the price of its variations.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'Checkboxes "Base" - are responsible for selecting simple products and the basics of variable products.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'Checkboxes "Variations" - are responsible for selecting variations in variable products.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'The sale price cannot be higher than the regular price, if a higher price is set, the sale is canceled.', 'product-editor' ) . '</p>'
					. '<p>' . __( 'If a "sale date" and / or "sale end date" are set, then the sale price will be active only during this period.', 'product-editor' ) . '</p>',
			)
		);
	}

	/**
	 * Home page handler in admin area
	 *
	 * @since    1.0.0
	 */
	public function main_page() {
		self::security_check( true );
		global $wpdb;
		global $wp_query;

		$this->set_dynamic_prices();
		$this->should_hide_notice();
		$this->add_screen_help();
		// Get products that match the passed parameters.
		$args           = array(
			'paginate' => true,
			'type'     => array( 'simple', 'variable', 'external' ),
		);
		$args['limit']  = (int) General_Helper::get_var( 'limit', 10 );
		$args['offset'] = ( General_Helper::get_var( 'paged', 1 ) - 1 ) * $args['limit'];
		General_Helper::get_var( 'product_cat', false ) && $args['category'] = array( General_Helper::get_var( 'product_cat' ) );
		General_Helper::get_var( 's', false ) && $args['name']               = General_Helper::get_var( 's' );
        $tags_get_value = preg_replace( '|[&<>\'\`\"\\\.]|', '', General_Helper::get_var( 'tags', '' ) );
        if ( $tags_get_value ) {
            $args['tag'] = explode( ',', $tags_get_value );
        }
		$results = wc_get_products( $args );
		// if the search for an exact match of the name did not give any results, we are looking for an inaccurate.
		if ( 0 === $results->total && ! empty( $args['name'] ) ) {
			$args['s'] = $args['name'];
			unset( $args['name'] );
			$results = wc_get_products( $args );
		}
		// Variables for template.
		$total              = $results->total;
		$num_of_pages       = $results->max_num_pages;
		$products           = $results->products;
		$num_on_page        = count( $products );
		$show_variations    = (int) General_Helper::get_var( 'show_variations' );
		$product_categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
        // Get last reverse_step
        $reverse_step = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE . ' ORDER BY id DESC LIMIT 1', ARRAY_A);
        $tags_data = $wpdb->get_results( 'SELECT ' . $wpdb->prefix . 'terms.`slug`, ' . $wpdb->prefix . 'terms.`name`, SUM( tax.`count` ) AS product_count
FROM `' . $wpdb->prefix . 'terms` INNER JOIN ' . $wpdb->prefix . 'term_taxonomy tax ON tax.term_id = ' . $wpdb->prefix . 'terms.term_id AND tax.taxonomy = \'product_tag\'
GROUP BY ' . $wpdb->prefix . 'terms.`term_id`', ARRAY_A );

		include 'partials/product-editor-admin-display.php';
	}

	/**
	 * The handler that implements the rollback of the last change
	 *
	 * @since    1.0.0
	 */
	public function action_reverse_products_data() {
		self::security_check( true, true );
		$reverse_id = sanitize_key( General_Helper::get_or_post_var( 'reverse_id' ) );
		if ( empty( $reverse_id ) ) {
			self::send_response( array( 'message' => __( 'No data to recover', 'product-editor' ) ), 409 );
		}
		global $wpdb;
        $reverse_step = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE . ' WHERE id="' . $reverse_id . '"', ARRAY_A);
        if ( ! $reverse_step || ! ( $reverse_step['data'] = @json_decode( $reverse_step['data'], true ) ) ) {
            self::send_response( array( 'message' => __( 'No data to recover', 'product-editor' ) ), 409 );
        }
		$products = array();
		$wpdb->query( 'START TRANSACTION' );
		$this->write_progress_file( 0 );

        $percentage_for_one_item = 100 / count( $reverse_step['data'] );
        $items_for_one_percentage = ceil( count( $reverse_step['data'] ) / 100 );
        $items_for_one_percentage = $items_for_one_percentage < 3 ? 3 : $items_for_one_percentage;
		// Each record contains information on changing one attribute of the product.
		foreach ( $reverse_step['data'] as $i => $record ) {
			if ( ! empty( $products[ $record['id'] ] ) ) {
				$product = $products[ $record['id'] ];
			} else {
				$product = $products[ $record['id'] ] = wc_get_product( $record['id'] );
				if ( ! $product ) {
					continue;
				}
			}

			switch ( $record['action'] ) {
				case 'change_sale_price':
					$product->set_sale_price( $record['value'] );
					break;
				case 'change_regular_price':
					$product->set_regular_price( $record['value'] );
					break;
				case 'change_date_on_sale_from':
					$product->set_date_on_sale_from( $record['value'] );
					break;
				case 'change_date_on_sale_to':
					$product->set_date_on_sale_to( $record['value'] );
					break;
			}
			$product->save();
            if ( $i % $items_for_one_percentage === 0 ) {
                $progress = floor( $percentage_for_one_item * ( $i + 1 ) );
                $this->write_progress_file($progress);
            }
		}
        $wpdb->delete( $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE, [ 'id' => $reverse_step['id'] ] );
		$wpdb->query( 'COMMIT' );
        WC_Cache_Helper::get_transient_version( 'product', true );
		self::send_response( 'ok', 200, 'raw' );
	}

	/**
	 * The handler that returns (HTML) variations of a variable product. $_GET['id'] - variable product id
	 *
	 * @since    1.0.0
	 */
	public function action_expand_product_variable() {
		self::security_check( true );
		if ( ! ( $id = sanitize_key( General_Helper::get_var( 'id' ) ) ) || ! ( $product = wc_get_product( $id ) ) || ! is_a( $product, 'WC_Product_Variable' ) ) {
			self::send_response( '', 200, 'raw' );
		}

		self::send_response( include 'partials/product-editor-admin-table-variations-rows.php', 200, 'raw' );
	}

	/**
	 * Product Change Request Handler
	 *
	 * @since    1.0.0
	 */
	public function action_bulk_changes() {
		self::security_check( true, true );
		// Check input data.
		$is_empty = true;
		$ids      = (array) General_Helper::post_var( 'ids' );
		foreach ( self::$change_actions as $action_name => $func_name ) {
			if ( General_Helper::post_var( $action_name ) ) {
				$is_empty = false;
			}
		}
		if ( $is_empty || empty( $ids ) ) {
			self::send_response(
				array(
					'message' => __( 'Nothing to change', 'product-editor' ),
					'content' => array(),
				)
			);
		}

		global $wpdb;
		// The request must be applied in full or not at all.
        $this->reverse_steps = [];
		$wpdb->query( 'START TRANSACTION' );
		$this->write_progress_file( 0 );

		// 80% for changes, 20% for reloading
        $percentage_for_one_item = 80 / count( $ids );
		$items_for_one_percentage = ceil( count( $ids ) / 80 );
        $items_for_one_percentage = $items_for_one_percentage < 3 ? 3 : $items_for_one_percentage;
        // Walk through each product and apply the requested operations.
		foreach ( $ids as $i => $id ) {
			$id      = sanitize_key( $id );
			$product = wc_get_product( $id );
			if ( ! $product ) {
				self::send_response(
					/* translators: %s: id of a product */
					array( 'message' => sprintf( __( 'Product with id:%s not found. Operations canceled.', 'product-editor' ), $id ) ),
					500
				);
			}
			$this->process_change_product( $product );
            if ( $i % $items_for_one_percentage === 0 ) {
                $progress = floor( $percentage_for_one_item * ( $i + 1 ) );
                $this->write_progress_file($progress);
            }
		}
		// If changes were made, save the previous values to the database.
		if ( ! empty ( $this->reverse_steps ) ) {
			$table_name = $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE;
			$wpdb->insert(
				$table_name,
				array(
					'time' => current_time( 'mysql' ),
					'name' => current_time( 'mysql' ),
					'data' => wp_json_encode( $this->reverse_steps ),
				)
			);
		}
		$wpdb->query( 'COMMIT' );
        WC_Cache_Helper::get_transient_version( 'product', true );
        if ( ! empty ( $this->reverse_steps ) ) {
            $reverse_step = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . PRODUCT_EDITOR_REVERSE_TABLE . ' ORDER BY id DESC LIMIT 1', ARRAY_A);
        }
		// Response new products data.
		self::send_response(
			array(
				/* translators: %s: count of operations */
				'message' => sprintf( __( 'Operations applied: %s', 'product-editor' ), count( $this->reverse_steps ) ),
				'content' => $this->response_data_for_ids( $ids ),
				'reverse' => ! empty( $reverse_step ) ? array( 'id' => $reverse_step['id'], 'name' => $reverse_step['name'] ) : '',
			)
		);
	}

	/**
	 * Applies the requested change operations to the product
	 *
	 * @param WC_Product $product Object of WC_Product for change.
	 *
	 * @since   1.0.0
	 */
	private function process_change_product( $product ) {
		// self::$change_actions - an array of mappings of action requests and functions that perform them.
		foreach ( self::$change_actions as $action_name => $func_name ) {
			if ( General_Helper::post_var( $action_name ) ) {
				$this->$func_name( $product );
			}
		}
		// Save model after all changes.
		$product->save();
	}

	/**
	 * Creates an array of data for the frontend for the specified product ids
	 *
	 * @param array $ids Array of products id.
	 * @return array
	 *
	 * @since    1.0.0
	 */
	private function response_data_for_ids( $ids ) {
		$response_data = array();
		$extra_ids     = array();
        // 80% for changes, 20% for reloading
        $this->write_progress_file(80);
        $percentage_for_one_item = 20 / count( $ids );
        $items_for_one_percentage = ceil( count( $ids ) / 20 );
        $items_for_one_percentage = $items_for_one_percentage < 3 ? 3 : $items_for_one_percentage;
		foreach ( $ids as $i => $id ) {
			$product = wc_get_product( $id );
			// For variations, we also add their parent product to the output list, if it is not already added or is not in ids list.
			if ( is_a( $product, 'WC_Product_Variation' ) && ! in_array( $product->get_parent_id(), $ids ) && ! in_array( $product->get_parent_id(), $extra_ids ) ) {
				$extra_ids[]     = $product->get_parent_id();
				$response_data[] = self::response_data_for_product( wc_get_product( $product->get_parent_id() ) );
			}

			$response_data[] = self::response_data_for_product( $product );
            if ( $i % $items_for_one_percentage === 0 ) {
                $progress = 80 + floor( $percentage_for_one_item * ( $i + 1 ) );
                $this->write_progress_file($progress);
            }
		}
		return $response_data;
	}

	/**
	 * Creates a frontend dataset for a specific product
	 *
	 * @param WC_Product $product Object of WC_Product for output.
	 * @return array
	 *
	 * @since    1.0.0
	 */
	private static function response_data_for_product( $product ) {
		$date_on_sale_from = $product->get_date_on_sale_from( 'edit' );
		$date_on_sale_from = $date_on_sale_from ? $date_on_sale_from->date( 'Y-m-d' ) : '';
		$date_on_sale_to   = $product->get_date_on_sale_to( 'edit' );
		$date_on_sale_to   = $date_on_sale_to ? $date_on_sale_to->date( 'Y-m-d' ) : '';
		return array(
			'id'                => $product->get_id(),
			'price'             => $product->get_price_html(),
			'regular_price'     => $product->get_regular_price( 'edit' ),
			'sale_price'        => $product->get_sale_price( 'edit' ),
			'date_on_sale_from' => $date_on_sale_from,
			'date_on_sale_to'   => $date_on_sale_to,
		);
	}

    /**
     * Round price value
     *
     * @param float $value
     * @param int $precision
     * @param int|string $round_type
     * @return float|int
     */
    private static function round_price( $value, $precision, $round_type ) {
        $new_value = $value;
        switch ( (int) $round_type ) {
            case 1:
                // Round up
                $new_value = General_Helper::round_up( $value, $precision );
                break;
            case 2:
                // Round down
                $new_value = General_Helper::round_down( $value, $precision );
                break;
        };
        return $new_value;
    }

	/**
	 * Handler function for the action to change a regular price. Data for the operation is taken from POST request
	 * The handler is registered with self::$changeActions
	 *
	 * @param WC_Product $product Object of WC_Product for change.
	 *
	 * @since    1.0.0
	 */
	private function change_regular_price( $product ) {
		$arg_regular_price = wc_clean( General_Helper::post_var( '_regular_price' ) );
		$action            = General_Helper::post_var( 'change_regular_price' );
		$round_type        = General_Helper::post_var( 'round_regular_price' );
		$round_precision   = (int) General_Helper::post_var( 'precision_regular_price' );

		if ( empty( $action ) || is_a( $product, 'WC_Product_Variable' ) ) {
			return;
		}
		// Save the value before the changes, to be able to roll back the changes.
		$this->reverse_steps[] = array(
			'id'     => $product->get_id(),
			'action' => 'change_regular_price',
			'value'  => $product->get_regular_price( 'edit' ),
		);
		$is_percentage         = stripos( $arg_regular_price, '%' ) !== false;
		$arg_regular_price     = str_replace( ',', '.', $arg_regular_price );
		$arg_regular_price     = preg_replace( '/[^\d\.\,\-]/', '', $arg_regular_price );
		$old_regular_price     = (float) $product->get_regular_price( 'edit' );
		$new_regular_price     = $old_regular_price;
		$number                = (float) wc_format_decimal( $arg_regular_price );
		switch ( (int) $action ) {
			case 1:
				// Change to.
				$new_regular_price = $number;
				break;
			case 2:
				// Increase existing price by (fixed amount or %).
				$new_regular_price = $old_regular_price + ( $is_percentage ? $old_regular_price / 100 * $number : $number );
				break;
			case 3:
				// Decrease existing price by (fixed amount or %).
				$new_regular_price = $old_regular_price - ( $is_percentage ? $old_regular_price / 100 * $number : $number );
				break;
            case 4:
                // Multiply existing price by a value
                $new_regular_price = $old_regular_price * $number;
                break;
		};

		$new_regular_price = self::round_price( $new_regular_price, $round_precision, $round_type);

		if ( $new_regular_price <= 0 || '' == $new_regular_price ) {
			self::send_response(
				array(
					'message' =>
						sprintf(
						/* translators: 1: Name of a product 2: New regular price */
							__( 'Invalid price computed for product "%1$s": "%2$s". Operations canceled.', 'product-editor' ),
							$product->get_name(),
							$new_regular_price
						),
				),
				409
			);
		}
		$product->set_regular_price( $new_regular_price );
	}

	/**
	 * Handler function for the action to change a sale price. Data for the operation is taken from POST request
	 * The handler is registered with self::$changeActions
	 *
	 * @param WC_Product $product Object of WC_Product for change.
	 *
	 * @since    1.0.0
	 */
	private function change_sale_price( $product ) {
		$arg_sale_price    = trim( General_Helper::post_var( '_sale_price', 0 ) );
		$action            = General_Helper::post_var( 'change_sale_price' );
        $round_type        = General_Helper::post_var( 'round_sale_price' );
        $round_precision   = (int) General_Helper::post_var( 'precision_sale_price' );
		if ( empty( $action ) || is_a( $product, 'WC_Product_Variable' ) ) {
			return;
		}
		// Save the value before the changes, to be able to roll back the changes.
		$this->reverse_steps[] = array(
			'id'     => $product->get_id(),
			'action' => 'change_sale_price',
			'value'  => $product->get_sale_price( 'edit' ),
		);
		$is_percentage         = stripos( $arg_sale_price, '%' ) !== false;
		$arg_sale_price        = str_replace( ',', '.', $arg_sale_price );
		$arg_sale_price        = preg_replace( '/[^\d\.\,\-]/', '', $arg_sale_price );
		$regular_price         = (float) $product->get_regular_price( 'edit' );
		$old_sale_price        = (float) $product->get_sale_price( 'edit' );
		$new_sale_price        = $old_sale_price;
		$number                = (float) wc_format_decimal( $arg_sale_price );
		switch ( (int) $action ) {
			case 1:
				// Change to.
				$new_sale_price = $number;
				break;
			case 2:
				// Increase existing sale price by (fixed amount or %).
				$new_sale_price = $old_sale_price + ( $is_percentage ? $old_sale_price / 100 * $number : $number );
				break;
			case 3:
				// Decrease existing sale price by (fixed amount or %).
				$new_sale_price = $old_sale_price - ( $is_percentage ? $old_sale_price / 100 * $number : $number );
				break;
			case 4:
				// Set to regular price decreased by (fixed amount or %).
				$new_sale_price = $regular_price - ( $is_percentage ? $regular_price / 100 * $number : $number );
				break;
		}

        $new_sale_price = self::round_price( $new_sale_price, $round_precision, $round_type);

		if ( $new_sale_price <= 0 ) {
			$new_sale_price = '';
		}
		$product->set_sale_price( $new_sale_price );
	}

	/**
	 * Handler function for the action to change sale date. Data for the operation is taken from POST request
	 * The handler is registered with self::$changeActions
	 *
	 * @param WC_Product $product Object of WC_Product for change.
	 *
	 * @since    1.0.0
	 */
	private function change_date_on_sale_from( $product ) {
		 $arg_date = wc_clean( General_Helper::post_var( '_sale_date_from' ) );
		$action    = General_Helper::post_var( 'change_date_on_sale_from' );
		if ( empty( $action ) || is_a( $product, 'WC_Product_Variable' ) ) {
			return;
		}
		// Save the value before the changes, to be able to roll back the changes.
		$old_timestamp         = $product->get_date_on_sale_from( 'edit' );
		$old_timestamp         = $old_timestamp ? $old_timestamp->getTimestamp() : null;
		$this->reverse_steps[] = array(
			'id'     => $product->get_id(),
			'action' => 'change_date_on_sale_from',
			'value'  => $old_timestamp,
		);
		$product->set_date_on_sale_from( $arg_date );
	}

	/**
	 * Handler function for the action to change sale end date. Data for the operation is taken from POST request
	 * The handler is registered with self::$changeActions
	 *
	 * @param WC_Product $product Object of WC_Product for change.
	 *
	 * @since    1.0.0
	 */
	private function change_date_on_sale_to( $product ) {
		$arg_date = wc_clean( General_Helper::post_var( '_sale_date_to' ) );
		$action   = General_Helper::post_var( 'change_date_on_sale_to' );
		if ( empty( $action ) || is_a( $product, 'WC_Product_Variable' ) ) {
			return;
		}
		// Save the value before the changes, to be able to roll back the changes.
		$old_timestamp         = $product->get_date_on_sale_to( 'edit' );
		$old_timestamp         = $old_timestamp ? $old_timestamp->getTimestamp() : null;
		$this->reverse_steps[] = array(
			'id'     => $product->get_id(),
			'action' => 'change_date_on_sale_to',
			'value'  => $old_timestamp,
		);
		$product->set_date_on_sale_to( $arg_date );
	}

	/**
	 * Common function for send response
	 *
	 * @param array|string $body Array or string for output.
	 * @param int          $code  Http code.
	 * @param string       $format json|raw Format for output.
	 *
	 * @since    1.0.0
	 */
	private static function send_response( $body = array(), $code = 200, $format = 'json' ) {
		status_header( $code );
		exit( 'json' === $format ? wp_json_encode( $body ) : $body );
	}

	/**
	 * Guard helper
	 *
	 * @param bool $check_read Check readability.
	 * @param bool $check_change Check the possibility of change.
	 *
	 * @since    1.0.0
	 */
	private static function security_check( $check_read = true, $check_change = false ) {
		if ( $check_read ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				self::send_response( array( 'message' => __( 'You do not have permission to edit products', 'product-editor' ) ), 403 );
			}
		}
		if ( $check_change ) {
			if ( ! wp_verify_nonce( General_Helper::get_or_post_var( 'nonce' ), 'pe_changes' ) ) {
				self::send_response( array( 'message' => __( 'The link you followed has expired.', 'product-editor' ) ), 401 );
			}
		}
	}

    /**
     * Disables welcome notice
     *
     * @since   1.0.2
     */
	public function should_hide_notice() {
	    if ( General_Helper::get_var('action') !== 'hide_notice_welcome'
            || ! wp_verify_nonce( General_Helper::get_var( 'nonce' ), 'pe_hide_notice_welcome' )
        ) return false;

        if ( !get_option('pe_hide_note_welcome') ) {
            add_option('pe_hide_note_welcome', true);
        }
    }

    /**
     * Handler for dynamic price changes form
     *
     * @since   1.0.4
     */
    public function set_dynamic_prices() {
	    if (
	        General_Helper::post_var('action') !== 'pe_change_dynamic_price'
            || ! wp_verify_nonce( General_Helper::post_var( 'nonce' ), 'pe_changes' )
        )
	        return;
        $multiply_value = ! General_Helper::post_var( 'multiply_value' ) || ! (bool) General_Helper::post_var( 'is_multiply' ) || (float) General_Helper::post_var( 'multiply_value' ) < 0 ? '' : (float) General_Helper::post_var( 'multiply_value' );
        $add_value = ! General_Helper::post_var( 'add_value' ) || ! (bool) General_Helper::post_var( 'is_add' ) ? '' : (float) General_Helper::post_var( 'add_value' );
	    update_option( 'pe_dynamic_is_multiply', (bool) General_Helper::post_var( 'is_multiply' ) );
	    update_option( 'pe_dynamic_is_add', (bool) General_Helper::post_var( 'is_add' ) );
	    update_option( 'pe_dynamic_multiply_value', $multiply_value );
	    update_option( 'pe_dynamic_add_value', $add_value );
        WC_Cache_Helper::get_transient_version( 'product', true );
    }

    /**
     * Handler for progress status requests.
     *
     * @since   1.0.4
     */
    public function action_get_progress() {
        self::security_check( true );
        $status = $this->read_progress_file();
        $status = $status !== false ? $status : '100';
        if ( preg_match( '/^\d{0,3}\.?\d*$/', $status ) ) {
            self::send_response($status, 200, 'raw');
        } else {
            self::send_response('error', 520, 'raw');
        }
    }
}
