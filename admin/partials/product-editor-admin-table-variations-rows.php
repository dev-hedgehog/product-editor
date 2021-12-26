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
		<td><?php echo esc_html( $variation_id ); ?></td>
		<td class="td-name"><?php echo esc_html( $var->get_name() ); ?></td>
		<td></td>
		<td><?php esc_html_e( 'Variation:', 'product-editor' ); ?> <?php echo esc_html( $var_atts ); ?></td>
		<td class="td-price"><?php echo $var->get_price_html(); ?></td>
		<td class="td-regular-price editable"><?php echo esc_html( $var->get_regular_price() ); ?></td>
		<td class="td-sale-price editable"><?php echo esc_html( $var->get_sale_price() ); ?></td>
		<td class="td-akciya"></td>
	</tr>

	<?php
}
