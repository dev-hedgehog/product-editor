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
 * @subpackage Productesc_html_editor/admin/partials
 */

/** @var int $show_variations Should show variations in variable products. */
/** @var int $total count of base products */
/** @var int $num_on_page count products on page */
/** @var int $num_of_pages count of pages */
/** @var array $tags_data associative array tags data */
/** @var string $tags_get_value tags values from GET request */
/** @var WP_Term[] $product_categories categories */
/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */

?>
<?php
    $nonce = wp_create_nonce( 'pe_changes' );
    // Show welcome notice
    include "product-editor-admin-notice.php";
?>
<template id="tmp-edit-single">
	<form method="post" action="/wp-admin/admin-post.php">
		<input type="hidden" name="action" value="bulk_changes">
		<input type="hidden" id="change_action" name="" value="">
		<input type="hidden" name="ids[]" value="">
		<div class="pe-edit-box" data-old_value="">

			<div class="btn-container">
				<input type="submit" class="button" value="<?php esc_html_e( 'Save', 'product-editor' ); ?>"/>
				<a class="button discard" tabindex="0"><?php esc_html_e( 'Cancel', 'product-editor' ); ?></a>
			</div>
		</div>
	</form>
</template>
<script>
	var pe_nonce = '<?php echo $nonce; ?>';
	var pe_tags_object = <?php echo json_encode($tags_data); ?>
</script>
<div class="wrap product-editor">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Product Editor', 'product-editor' ); ?></h1>
	<div class="ajax-info">
		<div class="inner"></div>
	</div>
	<div class="lds-dual-ring"></div>
    <fieldset class="dynamic_prices">
        <?php
        $is_multiply     = get_option( 'pe_dynamic_is_multiply', false );
        $is_add          = get_option( 'pe_dynamic_is_add', false );
        $multiply_number = get_option( 'pe_dynamic_multiply_value', '' );
        $add_number      = get_option( 'pe_dynamic_add_value', '' );
        $dynamic_tooltip = wp_kses(
                __('Instantly applies change rules to all prices without changing the original price values.<br/>For example, it can be used to change prices relative to the exchange rate.', 'product-editor' ),
                array( 'br' => array() )
        );
        ?>
        <h2 class="dynamic_prices__h2">
            <?php esc_html_e( 'Dynamic price changes (beta)', 'product-editor' ); ?>&nbsp;&nbsp;
            <span class="lbl-toggle"></span>
        </h2>&nbsp;&nbsp;
        <span class="pe-help-tip" data-tooltip="<?php echo $dynamic_tooltip; ?>"></span>
        <form method="post"  class="dynamic_prices__form" style="display: none;">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>"/>
            <input type="hidden" name="action" value="pe_change_dynamic_price"/>
            <div class="form-group">
                <label><input type="checkbox" name="is_multiply" <?php echo ( $is_multiply ? 'checked' : '' ); ?>>
                    <?php esc_html_e( 'Multiply prices by value:', 'product-editor' ); ?>
                </label>&nbsp;
                <input type="number"
                       min="0"
                       step="0.000000001"
                       name="multiply_value"
                       placeholder="<?php esc_html_e( 'Number from 0 to +&#8734;', 'product-editor' ); ?>"
                       value="<?php echo esc_attr( $multiply_number ); ?>"
                >
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_add"  <?php echo ($is_add ? 'checked' : ''); ?>>
                    <?php esc_html_e( 'Add a value to prices:', 'product-editor' ); ?>
                </label>&nbsp;
                <input type="number"
                       step="0.01"
                       name="add_value"
                       placeholder="<?php esc_html_e( 'Number from -&#8734; to +&#8734;', 'product-editor' ); ?>"
                       value="<?php echo esc_attr( $add_number ); ?>"
                >
            </div>
            <br/>
            <input type="submit" value="<?php esc_html_e( 'Save', 'product-editor' ); ?>" class="button">
        </form>
    </fieldset>
	<fieldset>
		<h2><?php esc_html_e( 'Search options', 'product-editor' ); ?></h2>
		<form method="get">
			<input type="hidden" name="post_type" value="product"/>
			<input type="hidden" name="page" value="product-editor"/>
			<div class="form-group">
				<label><?php esc_html_e( 'Number of items per page:', 'product-editor' ); ?></label>&nbsp;
				<input type="number"
							 min="1"
							 max="1000"
							 name="limit"
							 value="<?php echo esc_attr( General_Helper::get_var( 'limit', 10 ) ); ?>"
				>
				&nbsp;&nbsp;<label><input type="checkbox" value="1" name="show_variations" <?php echo 1 == $show_variations ? 'checked' : ''; ?>>
                    <?php esc_html_e( 'Show variations', 'product-editor' ); ?>
				</label>
			</div>
			<div class="form-group">

			</div>
			<div class="form-group">
				<label><?php esc_html_e( 'Category:', 'product-editor' ); ?>&nbsp;
					<select name="product_cat">
						<option value=""><?php esc_html_e( 'All', 'product-editor' ); ?></option>
						<?php
						foreach ( $product_categories as $category ) {
							echo '<option value="' . esc_attr( $category->slug ) . '" '
											. ( General_Helper::get_var( 'product_cat' ) == $category->slug ? 'selected' : '' )
											. '>' . esc_html( $category->name ) . '</option>';
						}
						?>
					</select>
				</label>
				&nbsp;&nbsp;
                <label><?php esc_html_e( 'Tags:', 'product-editor' ); ?>&nbsp;
                    <input type="text" id="selectTags" name="tags" class="form-control" value="<?php echo esc_attr( $tags_get_value ); ?>" >
                </label>
			</div>
            <div class="form-group">
                <label><?php esc_html_e( 'Name:', 'product-editor' ); ?>&nbsp;
                    <input type="search"
                           name="s"
                           value="<?php echo esc_attr( General_Helper::get_var( 's', '' ) ); ?>"
                    />
                </label>
            </div>
            <br/>
			<input type="submit" value="<?php esc_html_e( 'Search', 'product-editor' ); ?>" class="button">
		</form>

	</fieldset>
	<br>
	<hr/>
	<?php
	$round_tooltip_text = wp_kses(
	        __('Examples of rounding up:<br/>precision -2 price 21856.234 = 21900<br/>precision -1 price 21856.234 = 21860<br/>precision 0 price 21856.234 = 21857<br/>precision 1 price 21856.234 = 21856.3<br/>precision 2 price 21856.234 = 21856.24', 'product-editor' ),
            array( 'br' => array() )
    );
	?>
	<form method="post" action="/wp-admin/admin-post.php" id="bulk-changes">
		<input type="hidden" name="action" value="bulk_changes">
		<fieldset>
			<h2><?php esc_html_e( 'Bulk change', 'product-editor' ); ?></h2>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Price:', 'product-editor' ); ?></span>&nbsp;
                </label>
                <select class="change_regular_price change_to" name="change_regular_price">
                    <option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
                    <option value="2"><?php esc_html_e( 'Increase existing price by (fixed amount or %):', 'product-editor' ); ?></option>
                    <option value="3"><?php esc_html_e( 'Decrease existing price by (fixed amount or %):', 'product-editor' ); ?></option>
                    <option value="4"><?php esc_html_e( 'Multiply existing price by a value', 'product-editor' ); ?></option>
                </select>
                <input type="text" name="_regular_price" pattern="^[0-9\., ]*%?\w{0,3}\s*$" autocomplete="off">
                <select class="round_regular_price round_input" name="round_regular_price">
                    <option value=""><?php esc_html_e( '— Without rounding —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Round up, with the number of decimal places:', 'product-editor' ); ?></option>
                    <option value="2"><?php esc_html_e( 'Round down, with the number of decimal places:', 'product-editor' ); ?></option>
                </select>
                <input type="number" name="precision_regular_price" class="precision_regular_price precision_input" min="-9" max="9" placeholder="0" autocomplete="off" >
                <span class="pe-help-tip precision_regular_price" data-tooltip="<?php echo $round_tooltip_text; ?>"></span>

			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale price:', 'product-editor' ); ?></span>&nbsp;
                </label>
                <select class="change_sale_price change_to" name="change_sale_price">
                    <option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
                    <option value="2"><?php esc_html_e( 'Increase existing sale price by (fixed amount or %):', 'product-editor' ); ?></option>
                    <option value="3"><?php esc_html_e( 'Decrease existing sale price by (fixed amount or %):', 'product-editor' ); ?></option>
                    <option value="4"><?php esc_html_e( 'Set to regular price decreased by (fixed amount or %):', 'product-editor' ); ?></option>
                </select>
                <input type="text" name="_sale_price" pattern="^[0-9\., ]*%?\w{0,3}\s*$" autocomplete="off">
                <select class="round_sale_price round_input" name="round_sale_price">
                    <option value=""><?php esc_html_e( '— Without rounding —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Round up, with the number of decimal places:', 'product-editor' ); ?></option>
                    <option value="2"><?php esc_html_e( 'Round down, with the number of decimal places:', 'product-editor' ); ?></option>
                </select>
                <input type="number" name="precision_sale_price" class="precision_sale_price precision_input" min="-9" max="9" placeholder="0" autocomplete="off" >
                <span class="pe-help-tip precision_sale_price" data-tooltip="<?php echo $round_tooltip_text; ?>"></span>
			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale date:', 'product-editor' ); ?></span>&nbsp;
                </label>
                <select class="change_sale_date_from" name="change_date_on_sale_from">
                    <option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
                </select>
				<input type="text" class="date-picker" name="_sale_date_from" value="" placeholder="<?php esc_html_e( 'From&hellip;', 'product-editor' ); ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">
			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale end date:', 'product-editor' ); ?></span>&nbsp;
                </label>
                <select class="change_sale_date_to" name="change_date_on_sale_to">
                    <option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
                    <option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
                </select>
				<input type="text" class="date-picker" name="_sale_date_to" value="" placeholder="<?php esc_html_e( 'To&hellip;', 'product-editor' ); ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">
			</div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="not_processing_zero_price_products">
                    <span class="title"><?php esc_html_e( 'Do not change products with zero price', 'product-editor' ); ?></span>&nbsp;
                </label>
            </div>
			<br>
			<div class="form-group">
				<input type="submit" class="button" value="<?php esc_html_e( 'Change Selected', 'product-editor' ); ?>">&nbsp;&nbsp;
                <?php if ( ! empty( $reverse_step ) ): ?>
                    <a href="javascript://" class="do_reverse"
                       data-id="<?php echo esc_attr( $reverse_step['id'] ) ?>">
                        <?php echo esc_html__( 'Undo the change: ', 'product-editor' ) . esc_html($reverse_step['name']); ?>
                    </a>
                <?php else: ?>
                <a href="javascript://" class="do_reverse" style="display: none;"></a>
                <?php endif; ?>
			</div>
		</fieldset>
	</form>
	<br><br>
	<div class="tablenav">
		<?php
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo;', 'text-domain' ),
				'next_text' => __( '&raquo;', 'text-domain' ),
				'total'     => $num_of_pages,
				'current'   => sanitize_text_field( General_Helper::get_var( 'paged', 1 ) ),
			)
		);

		if ( $page_links ) {
			$page_links = str_replace( '<a class="', '<a class="button ', $page_links );
			$page_links = str_replace( '<span', '&nbsp;&nbsp;<span', $page_links );
			$page_links = str_replace( 'span>', 'span>&nbsp;&nbsp;', $page_links );
		}
		?>
		<ul class="subsubsub">
			<li>
				<b><?php esc_html_e( 'Total found:', 'product-editor' ); ?> <?php echo esc_html( $total ); ?></b>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;
			</li>
			<li><b><?php esc_html_e( 'Items on page:', 'product-editor' ); ?> <?php echo esc_html( $num_on_page ); ?></b></li>
		</ul>
		<div class="tablenav-pages"><?php echo $page_links; ?></div>
	</div>

	<table class="pe-product-table wp-list-table widefat fixed striped table-view-list">
		<thead>
		<tr>
			<th class="check-column-t">
				<label><?php esc_html_e( 'Base', 'product-editor' ); ?><br/><input class="cb-pr-all" type="checkbox"></label>
			</th>
			<th class="check-column-t">
				<label><?php esc_html_e( 'Variations', 'product-editor' ); ?><br/><input class="cb-vr-all" type="checkbox"></label>
			</th>
			<th scope="col" class="manage-column col-id">
				<span>ID</span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Name', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column col-status">
				<span><?php esc_html_e( 'Status', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Type', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Displayed price', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Regular price', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Sale price', 'product-editor' ); ?></span>
			</th>
			<th>
				<span><?php esc_html_e( 'Sale date', 'product-editor' ); ?></span>
			</th>
			<th scope="col" class="manage-column">
				<span><?php esc_html_e( 'Sale end date', 'product-editor' ); ?></span>
			</th>

		</tr>
		</thead>
		<tbody>
		<?php
		require 'product-editor-admin-table-rows.php';
		?>
		</tbody>
	</table>
</div>
