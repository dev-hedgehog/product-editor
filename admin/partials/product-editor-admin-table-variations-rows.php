<?php
/**
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
  $at = wc_get_product_variation_attributes($variation_id);
  $var_atts = '';
  array_walk($at, function($val, $ind) use (&$var_atts) {
    $var_atts .= str_replace('attribute_pa_', '', $ind).':'.$val.' ';
  });
  ?>
  <tr class="variation-product" data-id="<?=$variation_id?>" data-parent_id="<?=$product->get_id()?>">
    <td></td>
    <td><input class="cb-vr" name="ids[]" data-parent="<?=$product->get_id()?>" value="<?=$variation_id?>" type="checkbox"></td>
    <td><?=$variation_id?></td>
    <td><?=$var->get_name()?></td>
    <td></td>
    <td>Вариация: <?=$var_atts?></td>
    <td class="td-price"><?=$var->get_price_html()?></td>
    <td class="td-regular-price editable"><?=$var->get_regular_price()?></td>
    <td class="td-sale-price editable"><?=$var->get_sale_price()?></td>
    <td class="td-akciya"></td>
  </tr>

  <?php
}