<?php
/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */
/** @var int $show_variations */

foreach ($products as $product) {
  $isVariable = is_a($product, 'WC_Product_Variable');
  $isSimple = is_a($product, 'WC_Product_Simple');
  ?>
  <tr class="<?= $isVariable? 'variable-product': 'simple-product'?>" data-id="<?=$product->get_id()?>">
    <td><input class="cb-pr" name="ids[]" value="<?=$product->get_id()?>" type="checkbox"></td>
    <td><?= $isVariable
        ? '<input class="cb-vr-all-parent '.($show_variations?'expand':'collapse').'" data-id="'.$product->get_id().'" data-children_ids="'.json_encode($product->get_children()).'" type="checkbox">'
          .'<label class="lbl-toggle"></label>'
        : ''?></td>
    <td><?=$product->get_id()?></td>
    <td><?=$product->get_name()?></td>
    <td><?=$product->get_status()?></td>
    <td><?= $isVariable ? 'Вариативный' : 'Простой'?></td>
    <td class="td-price"><?= $product->get_price_html()?></td>
    <td class="td-regular-price <?= $isVariable ? '' : 'editable'?>"><?=$product->get_regular_price('edit')?></td>
    <td class="td-sale-price <?= $isVariable ? '' : 'editable'?>"><?=$product->get_sale_price('edit')?></td>
    <td class="td-akciya editable"><?= !$product->get_meta('sale')? 'Нет': 'Да'?></td>
  </tr>
  <?php
  if ($isVariable && $show_variations) {
    include ('product-editor-admin-table-variations-rows.php');
  }
}
?>