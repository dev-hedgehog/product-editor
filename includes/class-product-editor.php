<?php

/**
 * The file that defines the core plugin class
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Product-Editor
 * @subpackage Product_Editor/includes
 * @author     dev-hedgehog <dev.hedgehog.core@gmail.com>
 */
class Product_Editor {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Product_Editor_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PRODUCT_EDITOR_VERSION' ) ) {
			$this->version = PRODUCT_EDITOR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'product-editor';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_common_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Product_Editor_Loader. Orchestrates the hooks of the plugin.
	 * - Product_Editor_i18n. Defines internationalization functionality.
	 * - Product_Editor_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-product-editor-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-product-editor-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-product-editor-admin.php';

		$this->loader = new Product_Editor_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Product_Editor_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Product_Editor_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Product_Editor_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );

        $this->loader->add_action( 'admin_post_bulk_changes', $plugin_admin, 'action_bulk_changes' );
        $this->loader->add_action( 'admin_post_expand_product_variable', $plugin_admin, 'action_expand_product_variable' );
        $this->loader->add_action( 'admin_post_reverse_products_data', $plugin_admin, 'action_reverse_products_data' );
        $this->loader->add_action( 'admin_post_pe_get_progress', $plugin_admin, 'action_get_progress' );
        $this->loader->add_action( 'wp_ajax_pe_get_terms', $plugin_admin, 'action_get_terms' );


	}

    /**
     * Register all of the hooks related to the admin area and frontend area
     *
     * @since   1.0.4
     * @access   private
     */
	private function define_common_hooks() {
        // Simple, grouped and external products
        $this->loader->add_filter( 'woocommerce_product_get_price', $this, 'dynamic_price', 99, 2 );
        $this->loader->add_filter( 'woocommerce_product_get_regular_price', $this, 'dynamic_price', 99, 2 );
        $this->loader->add_filter( 'woocommerce_product_get_sale_price', $this, 'dynamic_price', 99, 2 );
        // Variations
        $this->loader->add_filter( 'woocommerce_product_variation_get_regular_price', $this, 'dynamic_price', 99, 2 );
        $this->loader->add_filter( 'woocommerce_product_variation_get_price', $this, 'dynamic_price', 99, 2 );
        $this->loader->add_filter( 'woocommerce_product_variation_get_sale_price', $this, 'dynamic_price', 99, 2 );
        // Variable (price range)
        $this->loader->add_filter( 'woocommerce_variation_prices_price', $this, 'dynamic_price', 99, 3 );
        $this->loader->add_filter( 'woocommerce_variation_prices_regular_price', $this, 'dynamic_price', 99, 3 );
        $this->loader->add_filter( 'woocommerce_variation_prices_sale_price', $this, 'dynamic_price', 99, 2 );
        // Handling price caching
        $this->loader->add_filter( 'woocommerce_get_variation_prices_hash', $this, 'dynamic_variation_prices_hash', 99, 3 );
    }

    /**
     * Decorator for prices
     * @param $price
     * @param $v
     * @param null $p
     * @return float|mixed
     * @since   1.0.4
     */
    public function dynamic_price( $price, $v, $p = null ) {
	    if (
	        !$price
            || ( ! get_option( 'pe_dynamic_is_multiply' ) && ! get_option( 'pe_dynamic_is_add' ) )
            || ( ! get_option( 'pe_dynamic_multiply_value' ) && ! get_option( 'pe_dynamic_add_value' ) )
        ) {
            return $price;
        }
	    $new_price = (float) $price;
        if ( get_option( 'pe_dynamic_is_multiply' ) && (float) get_option( 'pe_dynamic_multiply_value' ) > 0) {
            $new_price = $new_price * (float) get_option( 'pe_dynamic_multiply_value' );
        }
        if ( get_option( 'pe_dynamic_is_add' ) ) {
            $new_price = $new_price + (float) get_option( 'pe_dynamic_add_value' );
        }
        return $new_price >= 0 ? (float) $new_price : $price;
    }

    /**
     * Hash for dynamic prices
     *
     * @param $price_hash
     * @param $product
     * @param $for_display
     * @return mixed
     * @since   1.0.4
     */
    public function dynamic_variation_prices_hash( $price_hash, $product, $for_display ) {
        $price_hash[] = array ( get_option( 'pe_dynamic_is_multiply' ), get_option( 'pe_dynamic_is_add' ), get_option( 'pe_dynamic_multiply_value' ), get_option( 'pe_dynamic_add_value' ) );
        return $price_hash;
    }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
      $this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Product_Editor_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
