<?php
/**
 * The file that defines the helper plugin class
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/helpers
 */

class General_Helper {

	/**
	 * Returns a GET value by a key
	 *
	 * @param string $key Key in $_GET.
	 * @param string $default Default value if key isn't set.
	 * @return mixed|string
	 */
	public static function get_var( $key, $default = '' ) {
		$value = filter_input( INPUT_GET, $key );
		return $value ? $value : $default;
	}

	/**
	 * Returns a POST value by a key
	 *
	 * @param string $key Key in $_POST.
	 * @param string $default Default value if key isn't set.
	 * @return mixed|string
	 */
	public static function post_var( $key, $default = '' ) {
		$value                            = null;
		isset( $_POST[ $key ] ) && $value = wp_unslash( $_POST[ $key ] );
		return $value ? $value : $default;
	}

	/**
	 * Returns a GET value by a key if isset else a POST value
	 *
	 * @param string $key Key in $_GET or $_POST.
	 * @param string $default Default value if key isn't set.
	 * @return mixed|string
	 */
	public static function get_or_post_var( $key, $default = '' ) {
		if ( self::get_var( $key ) ) {
			return self::get_var( $key );
		} elseif ( self::post_var( $key ) ) {
			return self::post_var( $key );
		}
		return $default;
	}

    /**
     * Round up the value
     *
     * @param float $value
     * @param int $places
     * @return float|int
     */
    public static function round_up($value, $places)
    {
        $mult = pow(10, abs($places));
        return $places < 0 ?
            ceil($value / $mult) * $mult :
            ceil($value * $mult) / $mult;
    }

    /**
     * Round down the value
     *
     * @param float $value
     * @param int $places
     * @return float|int
     */
    public static function round_down($value, $places)
    {
        $mult = pow(10, abs($places));
        return $places < 0 ?
            floor($value / $mult) * $mult :
            floor($value * $mult) / $mult;
    }

    /**
     * Get existing product statuses
     *
     * @return array
     */
    public static function get_product_statuses()
    {
        global $wpdb;
        $result = array();
        $data = $wpdb->get_results( 'select distinct post_status from ' . $wpdb->prefix . 'posts where post_type = "product"', ARRAY_A );
        if ( !empty( $data ) ) {
            foreach ($data as $row)
                $result[] = ['key' => $row['post_status']];
        }

        return $result;
    }

    /**
     * Return array of taxonomies and their terms of product post type
     *
     * return []
     */
    public static function getTaxAndTerms( $taxonomy_names = [] )
    {
        $result = array();
        $taxonomies = get_object_taxonomies( 'product', 'objects' );
        foreach ( $taxonomies as $taxonomy ) {
            if ( !empty( $taxonomy_names ) && !in_array($taxonomy->name, $taxonomy_names) ) {
                continue;
            }
            $terms = get_terms( array( 'taxonomy' => $taxonomy->name ) );
            if ( $terms && !is_wp_error( $terms ) ) {
                $terms = array_map( function ( $data ) {
                    return [
                        'name' => $data->name,
                        'slug' => $data->slug,
                        'product_count' => $data->count
                    ];
                }, $terms);
                $result[$taxonomy->name] = [
                    'name' =>  $taxonomy->name,
                    'label' =>  $taxonomy->label,
                    'terms' => $terms,
                ];
            }
        }
        return $result;
    }
}
