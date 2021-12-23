<?php
/**
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin/partials
 */
/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */
/** @var int $show_variations Should show variations in variable products */

foreach ($products as $product) {
  $isVariable = is_a($product, 'WC_Product_Variable');
  $isSimple = is_a($product, 'WC_Product_Simple');
  ?>
  <tr class="<?= $isVariable? 'variable-product': 'simple-product'?>" data-id="<?=esc_attr($product->get_id())?>">
    <td><input class="cb-pr" name="ids[]" value="<?=esc_attr($product->get_id())?>" type="checkbox"></td>
    <td><?= $isVariable
        ? '<input class="cb-vr-all-parent '.($show_variations?'expand':'collapse').'" data-id="'.esc_attr($product->get_id()).'" data-children_ids="'.esc_attr(json_encode($product->get_children())).'" type="checkbox">'
          .'<label class="lbl-toggle"></label>'
        : ''?></td>
    <td><?=esc_html($product->get_id())?></td>
    <td><?=esc_html($product->get_name())?></td>
    <td><?=esc_html($product->get_status())?></td>
    <td><?= $isVariable ? __('Variable', 'product-editor') : __('Simple', 'product-editor')?></td>
    <td class="td-price"><?= $product->get_price_html() ?></td>
    <td class="td-regular-price <?= $isVariable ? '' : 'editable'?>"><?=esc_html($product->get_regular_price('edit'))?></td>
    <td class="td-sale-price <?= $isVariable ? '' : 'editable'?>"><?=esc_html($product->get_sale_price('edit'))?></td>
    <td class="td-akciya editable"><?= !$product->get_meta('sale')? 'Нет': 'Да'?></td>
  </tr>
  <?php
  if ($isVariable && $show_variations) {
    include ('product-editor-admin-table-variations-rows.php');
  }
}
?>