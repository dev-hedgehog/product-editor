<?php
/**
 * This file is a chunk that render rows of variations of a variable product
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin/partials
 */

/** @var WC_Product_Variable $product */

$variation_ids = $product->get_children();
foreach ( $variation_ids as $variation_id ) {
	$var = wc_get_product( $variation_id );
	// Create a string with attributes for the variation.
	$at       = wc_get_product_variation_attributes( $variation_id );
	$var_atts = '';
	array_walk(
		$at,
		function ( $val, $ind ) use ( &$var_atts ) {
			$var_atts .= str_replace( 'attribute_pa_', '', $ind ) . ':' . $val . ' ';
		}
	);
	// Get on sale dates.
	$date_on_sale_from = $var->get_date_on_sale_from( 'edit' );
	$date_on_sale_from = $date_on_sale_from ? $date_on_sale_from->date( 'Y-m-d' ) : '';
	$date_on_sale_to   = $var->get_date_on_sale_to( 'edit' );
	$date_on_sale_to   = $date_on_sale_to ? $date_on_sale_to->date( 'Y-m-d' ) : '';
	?>
	<tr class="variation-product"
			data-id="<?php echo esc_attr( $variation_id ); ?>"
			data-parent_id="<?php echo esc_attr( $product->get_id() ); ?>">
		<td></td>
		<td><input class="cb-vr"
							 name="ids[]"
							 data-parent="<?php echo esc_attr( $product->get_id() ); ?>"
							 value="<?php echo esc_attr( $variation_id ); ?>"
							 type="checkbox"></td>
		<td class="td-id"><?php echo esc_html( $variation_id ); ?></td>
		<td class="td-name"><?php echo esc_html( $var->get_name() ); ?></td>
		<td class="td-status"></td>
		<td class="td-type"><?php esc_html_e( 'Variation:', 'product-editor' ); ?> <?php echo esc_html( $var_atts ); ?></td>
		<td class="td-price"><?php echo $var->get_price_html(); ?></td>
		<td class="td-regular-price editable"><?php echo esc_html( $var->get_regular_price( 'edit' ) ); ?></td>
		<td class="td-sale-price editable"><?php echo esc_html( $var->get_sale_price( 'edit' ) ); ?></td>
		<td class="td-date-on-sale-from editable"><?php echo esc_html( $date_on_sale_from ); ?></td>
		<td class="td-date-on-sale-to editable"><?php echo esc_html( $date_on_sale_to ); ?></td>
        <td class="td-tags"></td>
	</tr>

	<?php
}
