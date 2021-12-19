<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin/partials
 */

/** @var int $show_variations */
/** @var int $total */
/** @var int $num_on_page */
/** @var int $num_of_pages */
/** @var WP_Term[] $product_categories */
/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */

?>
<template id="tmp-edit-single">
  <form method="post" action="/wp-admin/admin-post.php">
    <input type="hidden" name="action" value="bulk_changes">
    <input type="hidden" id="change_action" name="" value="">
    <input type="hidden" name="ids[]" value="">
    <div class="pe-edit-box" data-old_value="">
      <br/>
      <input type="submit" class="button" value="<?=__('Save', 'product-editor')?>"/>
      <a class="button discard" tabindex="0"><?=__('Cancel', 'product-editor')?></a>
    </div>
  </form>
</template>
<script>
  var pe_nonce = '<?= wp_create_nonce( 'pe_changes' )?>';
</script>
<div class="wrap product-editor">
  <h1 class="wp-heading-inline"><?=__('Product Editor', 'product-editor')?></h1>
  <div class="ajax-info">
    <div class="inner"></div>
  </div>
  <div class="lds-dual-ring"></div>
  <fieldset>
    <h2><?=__('Search options', 'product-editor')?></h2>
  <form method="get">
    <input type="hidden" name="post_type" value="product" />
    <input type="hidden" name="page" value="product-editor" />
    <div class="form-group">
      <label><?=__('Number of items per page:', 'product-editor')?></label>&nbsp;<input type="number" min="1" max="1000" name="limit" value="<?=esc_attr(General_Helper::getVar('limit', 10))?>">
      &nbsp;&nbsp;<label><input type="checkbox" value="1" name="show_variations" <?= $show_variations == 1 ? 'checked' : ''?>><?=__('Show variations', 'product-editor')?></label>
    </div>
    <div class="form-group">

    </div>
    <div class="form-group">
    <label><?=__('Category:', 'product-editor')?>&nbsp;
      <select name="product_cat">
        <option value=""><?=__('All', 'product-editor')?></option>
        <?php
        foreach ($product_categories as $category) {
          echo '<option value="'.esc_attr($category->slug).'" '
            .(General_Helper::getVar('product_cat') == $category->slug ? 'selected' : '')
            .'>'.esc_html($category->name).'</option>';
        }
        ?>
        </select>
    </label>
    &nbsp;&nbsp;
    <label><?=__('Name:', 'product-editor')?>&nbsp;<input type="search" name="s" value="<?= esc_attr(General_Helper::getVar('s', ''))?>"/></label>
    </div>
    <input type="submit" value="<?=__('Search', 'product-editor')?>" class="button">
  </form>

  </fieldset>
  <br>
  <hr/>
  <form method="post" action="/wp-admin/admin-post.php" id="bulk-changes">
    <input type="hidden" name="action" value="bulk_changes">
    <input type="hidden" name="nonce" value="<?= wp_create_nonce( 'pe_bulk_changes' )?>">
    <fieldset>
      <h2><?=__('Bulk change', 'product-editor')?></h2>
      <div class="info-box">
        <?=__('Basic concepts help', 'product-editor')?>
      </div>
      <div class="form-group">
        <label>
          <span class="title"><?=__('Price:', 'product-editor')?></span>&nbsp;
					<select class="change_regular_price change_to" name="change_regular_price">
						<option value=""><?=__('— No change —', 'product-editor')?></option>
            <option value="1"><?=__('Change to:', 'product-editor')?></option>
            <option value="2"><?=__('Increase existing price by (fixed amount or %):', 'product-editor')?></option>
            <option value="3"><?=__('Decrease existing price by (fixed amount or %):', 'product-editor')?></option>
          </select>
        </label>
        <input type="text" name="_regular_price" pattern="^[0-9 ]*%?\w{0,3}\s*$">
      </div>
      <div class="form-group">
        <label>
          <span class="title"><?=__('Sale price:', 'product-editor')?></span>&nbsp;
          <select class="change_sale_price change_to" name="change_sale_price">
            <option value=""><?=__('— No change —', 'product-editor')?></option>
            <option value="1"><?=__('Change to:', 'product-editor')?></option>
            <option value="2"><?=__('Increase existing sale price by (fixed amount or %):', 'product-editor')?></option>
            <option value="3"><?=__('Decrease existing sale price by (fixed amount or %):', 'product-editor')?></option>
            <option value="4"><?=__('Set to regular price decreased by (fixed amount or %):', 'product-editor')?></option>
          </select>
        </label>
        <input type="text" name="_sale_price" pattern="^[0-9 ]*%?\w{0,3}\s*$">
      </div>
      <div class="form-group">
        <label>
          <span class="title">Товар по акции:</span>&nbsp;
          <select class="change_regular_price change_to" name="change_akciya">
            <option value="">— Без изменений —</option>
            <option value="1">Да</option>
            <option value="2">Нет</option>
          </select>
        </label>

      </div>

      <br>
      <div class="form-group">
        <input type="submit" class="button" value="<?=__('Change Selected', 'product-editor')?>">&nbsp;&nbsp;
        <a href="javascript://" class="do_reverse"
          <?= !empty($_SESSION['reverse_steps']) ? '':'style="display: none;"'?>
        ><?=__('Undo the last change', 'product-editor')?></a>

      </div>
    </fieldset>
  </form>
  <br><br>
  <div class="tablenav">
    <?php
    $page_links = paginate_links( array(
      'base' => add_query_arg( 'paged', '%#%' ),
      'format' => '',
      'prev_text' => __( '&laquo;', 'text-domain' ),
      'next_text' => __( '&raquo;', 'text-domain' ),
      'total' => $num_of_pages,
      'current' => sanitize_text_field(General_Helper::getVar('paged', 1))
    ) );

    if ( $page_links ) {
      $page_links = str_replace('<a class="', '<a class="button ', $page_links);
      $page_links = str_replace('<span', '&nbsp;&nbsp;<span', $page_links);
      $page_links = str_replace('span>', 'span>&nbsp;&nbsp;', $page_links);
    }
    ?>
    <ul class="subsubsub">
      <li><b><?=__('Total found:', 'product-editor')?> <?= esc_html($total) ?></b>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</li>
      <li><b><?=__('Items per page:', 'product-editor')?> <?= esc_html($num_on_page) ?></b></li>
    </ul>
    <div class="tablenav-pages"><?= $page_links ?></div>
  </div>

  <table class="wp-list-table widefat fixed striped table-view-list">
    <thead>
    <tr>
      <td class="check-column-t">
        <?=__('Base', 'product-editor')?><br/><input class="cb-pr-all" type="checkbox">
      </td>
      <td class="check-column-t">
        <?=__('Variations', 'product-editor')?><br/><input class="cb-vr-all" type="checkbox">
      </td>
      <th scope="col" class="manage-column col-id">
        <span>ID</span>
      </th>
      <th scope="col" class="manage-column">
        <span><?=__('Name', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column col-status">
        <span><?=__('Status', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column">
        <span><?=__('Type', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column">
        <span><?=__('Displayed price', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column">
        <span><?=__('Regular price', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column">
        <span><?=__('Sale price', 'product-editor')?></span>
      </th>
      <th scope="col" class="manage-column">
        <span>Товар по акции</span>
      </th>

    </tr>
    </thead>
    <tbody>
<?php
  include ('product-editor-admin-table-rows.php');
?>
    </tbody>
  </table>
</div>