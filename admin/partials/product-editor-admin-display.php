<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Product_Editor
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
      <input type="submit" class="button" value="Сохранить"/>
      <a class="button discard" tabindex="0">Отменить</a>
    </div>
  </form>
</template>

<div class="wrap product-editor">
  <h1 class="wp-heading-inline">Редактор продуктов</h1>
  <div class="ajax-info">
    <div class="inner"></div>
  </div>
  <div class="lds-dual-ring"></div>
  <fieldset>
    <h2>Параметры поиска</h2>
  <form method="get">
    <input type="hidden" name="post_type" value="product" />
    <input type="hidden" name="page" value="product-editor" />
    <div class="form-group">
      <label>Количество элементов на странице:</label>&nbsp;<input type="number" min="1" max="1000" name="limit" value="<?=General_Helper::getVar('limit', 10)?>">
      &nbsp;&nbsp;<label><input type="checkbox" value="1" name="show_variations" <?= $show_variations == 1 ? 'checked' : ''?>>Показывать вариации</label>
    </div>
    <div class="form-group">

    </div>
    <div class="form-group">
    <label>Категория:&nbsp;
      <select name="product_cat">
        <option value="">Все</option>
        <?php
        foreach ($product_categories as $category) {
          echo '<option value="'.$category->slug.'" '
            .(General_Helper::getVar('product_cat') == $category->slug ? 'selected' : '')
            .'>'.$category->name.'</option>';
        }
        ?>
        </select>
    </label>
    &nbsp;&nbsp;
    <label>Название:&nbsp;<input type="search" name="s" value="<?= General_Helper::getVar('s', '')?>"/></label>
    </div>
    <input type="submit" value="Поиск" class="button">
  </form>

  </fieldset>
  <br>
  <hr/>
  <form method="post" action="/wp-admin/admin-post.php" id="bulk-changes">
    <input type="hidden" name="action" value="bulk_changes">
    <fieldset>
      <h2>Массовое изменение</h2>
      <div class="info-box">
        Вычисляемая цена - цена которою увидит пользователь.<br/>
        Вариативные товары не имеют собственной цены и цены расспродажи.<br/>
        Чтобы изменить цену у вариативных товаров, изменяйте цену у её вариаций.<br/>
        Галочку "Товар по акции" можно поставить только на основной товар.<br/>
        Цена расспродажи не может быть выше обычной цены, если задаётся цена выше,
        то расспродажа отменяется.
      </div>
      <div class="form-group">
        <label>
          <span class="title">Цена:</span>&nbsp;
					<select class="change_regular_price change_to" name="change_regular_price">
						<option value="">— Без изменений —</option>
            <option value="1">Изменить на:</option>
            <option value="2">Увеличить существующие цены на (фиксированную величину или %):</option>
            <option value="3">Уменьшить базовую цену на (фиксированное значение или %):</option>
          </select>
        </label>
        <input type="text" name="_regular_price" pattern="^[0-9 ]*%?₽?$">
      </div>
      <div class="form-group">
        <label>
          <span class="title">Цена расспродажи:</span>&nbsp;
          <select class="change_sale_price change_to" name="change_sale_price">
            <option value="">— Без изменений —</option>
            <option value="1">Изменить на:</option>
            <option value="2">Увеличить текущую цену на (фиксированную сумму или %):</option>
            <option value="3">Уменьшить текущую цену на (фиксированную сумму или %):</option>
            <option value="4">Задать на уровне обычной цены, пониженной на (фиксированную сумму или %):</option>
          </select>
        </label>
        <input type="text" name="_sale_price" pattern="^[0-9 ]*%?₽?$">
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
        <input type="submit" class="button" value="Изменить выбранное">&nbsp;&nbsp;
        <a href="javascript://" class="do_reverse"
          <?= !empty($_SESSION['reverse_steps']) ? '':'style="display: none;"'?>
        >Отменить последнее изменение</a>

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
      'current' => General_Helper::getVar('paged', 1)
    ) );

    if ( $page_links ) {
      $page_links = str_replace('<a class="', '<a class="button ', $page_links);
      $page_links = str_replace('<span', '&nbsp;&nbsp;<span', $page_links);
      $page_links = str_replace('span>', 'span>&nbsp;&nbsp;', $page_links);
    }
    ?>
    <ul class="subsubsub">
      <li><b>Всего найдено: <?= $total?></b>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;</li>
      <li><b>Записей на странице: <?= $num_on_page ?></b></li>
    </ul>
    <div class="tablenav-pages"><?= $page_links ?></div>
  </div>

  <table class="wp-list-table widefat fixed striped table-view-list">
    <thead>
    <tr>
      <td class="check-column-t">
        Основные<input class="cb-pr-all" type="checkbox">
      </td>
      <td class="check-column-t">
        Вариации<input class="cb-vr-all" type="checkbox">
      </td>
      <th scope="col" class="manage-column col-id">
        <span>ID</span>
      </th>
      <th scope="col" class="manage-column">
        <span>Название</span>
      </th>
      <th scope="col" class="manage-column col-status">
        <span>Статус</span>
      </th>
      <th scope="col" class="manage-column">
        <span>Тип</span>
      </th>
      <th scope="col" class="manage-column">
        <span>Вычисляемая цена</span>
      </th>
      <th scope="col" class="manage-column">
        <span>Цена</span>
      </th>
      <th scope="col" class="manage-column">
        <span>Цена расспродажи</span>
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