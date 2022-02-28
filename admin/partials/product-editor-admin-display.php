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
/** @var WP_Term[] $product_categories categories */
/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */

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
	var pe_nonce = '<?php echo wp_create_nonce( 'pe_changes' ); ?>';
</script>
<div class="wrap product-editor">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Product Editor', 'product-editor' ); ?></h1>
	<div class="ajax-info">
		<div class="inner"></div>
	</div>
	<div class="lds-dual-ring"></div>
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
				&nbsp;&nbsp;<label><input type="checkbox" value="1"
																	name="show_variations"
									<?php echo 1 == $show_variations ? 'checked' : ''; ?>><?php esc_html_e( 'Show variations', 'product-editor' ); ?>
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
				<label><?php esc_html_e( 'Name:', 'product-editor' ); ?>&nbsp;
					<input type="search"
								 name="s"
								 value="<?php echo esc_attr( General_Helper::get_var( 's', '' ) ); ?>"
					/>
				</label>
			</div>
			<input type="submit" value="<?php esc_html_e( 'Search', 'product-editor' ); ?>" class="button">
		</form>

	</fieldset>
	<br>
	<hr/>
	<form method="post" action="/wp-admin/admin-post.php" id="bulk-changes">
		<input type="hidden" name="action" value="bulk_changes">
		<fieldset>
			<h2><?php esc_html_e( 'Bulk change', 'product-editor' ); ?></h2>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Price:', 'product-editor' ); ?></span>&nbsp;
					<select class="change_regular_price change_to" name="change_regular_price">
						<option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
						<option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
						<option value="2"><?php esc_html_e( 'Increase existing price by (fixed amount or %):', 'product-editor' ); ?></option>
						<option value="3"><?php esc_html_e( 'Decrease existing price by (fixed amount or %):', 'product-editor' ); ?></option>
						<option value="4"><?php esc_html_e( 'Multiply existing price by a value', 'product-editor' ); ?></option>
					</select>
				</label>
				<input type="text" name="_regular_price" pattern="^[0-9\., ]*%?\w{0,3}\s*$" autocomplete="off">
			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale price:', 'product-editor' ); ?></span>&nbsp;
					<select class="change_sale_price change_to" name="change_sale_price">
						<option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
						<option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
						<option value="2"><?php esc_html_e( 'Increase existing sale price by (fixed amount or %):', 'product-editor' ); ?></option>
						<option value="3"><?php esc_html_e( 'Decrease existing sale price by (fixed amount or %):', 'product-editor' ); ?></option>
						<option value="4"><?php esc_html_e( 'Set to regular price decreased by (fixed amount or %):', 'product-editor' ); ?></option>
					</select>
				</label>
				<input type="text" name="_sale_price" pattern="^[0-9\., ]*%?\w{0,3}\s*$" autocomplete="off">
			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale date:', 'product-editor' ); ?></span>&nbsp;
					<select class="change_sale_date_from" name="change_date_on_sale_from">
						<option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
						<option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
					</select>
				</label>
				<input type="text" class="date-picker" name="_sale_date_from" value="" placeholder="<?php esc_html_e( 'From&hellip;', 'product-editor' ); ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">
			</div>
			<div class="form-group">
				<label>
					<span class="title"><?php esc_html_e( 'Sale end date:', 'product-editor' ); ?></span>&nbsp;
					<select class="change_sale_date_to" name="change_date_on_sale_to">
						<option value=""><?php esc_html_e( '— No change —', 'product-editor' ); ?></option>
						<option value="1"><?php esc_html_e( 'Change to:', 'product-editor' ); ?></option>
					</select>
				</label>
				<input type="text" class="date-picker" name="_sale_date_to" value="" placeholder="<?php esc_html_e( 'To&hellip;', 'product-editor' ); ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">
			</div>

			<br>
			<div class="form-group">
				<input type="submit" class="button" value="<?php esc_html_e( 'Change Selected', 'product-editor' ); ?>">&nbsp;&nbsp;
				<a href="javascript://" class="do_reverse"
								<?php echo ! empty( $_SESSION['reverse_steps'] ) ? '' : 'style="display: none;"'; ?>
				><?php esc_html_e( 'Undo the last change', 'product-editor' ); ?></a>

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
			<li><b><?php esc_html_e( 'Items per page:', 'product-editor' ); ?> <?php echo esc_html( $num_on_page ); ?></b></li>
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
