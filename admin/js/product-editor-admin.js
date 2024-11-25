(function ($) {
	'use strict';
	let product_editor_object = window.product_editor_object;
	let isRequested = false;
	let isProgressRequested = false;
	let progressIntervalHandle = null;
	let datepicker_options = {
		dateFormat: 'yy-mm-dd',
		showButtonPanel: true,
		// Allow to click mouse at any position on a page no worries about click at a wrong place.
		beforeShow: function () {
			$('.product-editor').prepend('<div id="overlay_datepicker"></div>');
		},
		onClose: function () {
			$('#overlay_datepicker').remove();
		}
	};

	$(function () {
		if (!$('.product-editor').length) {
			return;
		}
		$('.product-editor-loading').hide();

		/** Stick Table header */
		if ($('#wpadminbar').length) {
			$('.product-editor .pe-product-table thead').css('top', $('#wpadminbar').height());
		}

		/** Pulse help button */
		$('#contextual-help-link-wrap').addClass('btn--shockwave');

		/** Date pickers */
		$('.date-picker').datepicker(datepicker_options);

		/** Toggle dynamic prices */
		$('.dynamic_prices__h2').on('click', function () {
			$(this).parent('fieldset').toggleClass('active').children('form').slideToggle();
		});

		/** Tooltips */
		$("[data-tooltip]").tipTip({
			maxWidth: 'auto',
			defaultPosition: 'bottom',
			attribute: 'data-tooltip',
			activation: 'click',
			delay: 0
		});

		/**
		 * Returns taxonomies that are not yet used for searching.
		 * There are 2 types of taxonomies, those that should be present in the products and those that should not be there.
		 * @param type 'include' | 'exclude'
		 */
		let not_selected_taxonomies = (type) =>
			pe_data.search_taxonomies.list.filter((el) => !pe_data.search_taxonomies[type].find(taxName => taxName === el.name));

		/**
		 * Adds a selection item to the search interface for the specified taxonomy.
         * @param name
		 * @param label
		 * @param type 'include' | 'exclude'
		 */
		function addSearchTaxonomy (name, label, type = 'include') {
			let taxonomy = {name, label};
			const searchParams = new URLSearchParams(location.search);
			if ( pe_data.search_taxonomies[type].find((el) => el === taxonomy.name) ) {
				return;
			}

			let $fieldset = $('.search-fieldset.'+type);
			let groupId = 'search-add-tax-' + Date.now();
			let $tmplNode = $(document.getElementById("tmp-add-search-taxonomy").content.cloneNode(true));

			$tmplNode.find('.form-group').attr('id', groupId);
			$tmplNode.find('.button--minus').data('id', groupId).on('click', function () {
				$('#' + $(this).data('id')).remove();
				pe_data.search_taxonomies[type] = pe_data.search_taxonomies[type].filter(el => el !== taxonomy.name);
				$('.selectTaxonomy[data-type="'+type+'"]').selectPageData(not_selected_taxonomies(type));
			});
			$tmplNode.find('.label').html(taxonomy.label);
			$tmplNode.find('.taxonomy_selected_terms').attr('name', 'terms_'+type+'_tax_' + taxonomy.name)

				.attr('value', searchParams.get('terms_'+type+'_tax_' + taxonomy.name));
			$tmplNode.find('.taxonomy_selected_name').attr('name', 'search_'+type+'_taxonomies[]')
			let form_data = new FormData();
			form_data.append('nonce', pe_data.nonce);
			form_data.append('action', 'pe_get_terms');
			form_data.append('taxonomy', taxonomy.name);
			$tmplNode.find('[name="search_'+type+'_taxonomies[]"]').val(taxonomy.name)
			let $node = $fieldset.find('.form-group').last().before($tmplNode);
			pe_data.search_taxonomies[type].push(taxonomy.name);
			$('.selectTaxonomy[data-type="'+type+'"]').selectPageData(not_selected_taxonomies(type));

			let init_select = () => {
				$fieldset.find('.taxonomy_selected_terms[name="'+'terms_'+type+'_tax_' + taxonomy.name+'"]').selectPage({
					multiple : true,
					data: pe_data.search_taxonomies.terms[taxonomy.name],
					showField : 'name',
					keyField : 'slug',
					formatItem : function(data){
						return data.name;
					}
				});
			};

			if (typeof pe_data.search_taxonomies.terms[taxonomy.name] !== 'undefined') {
				init_select();
			} else {
				$('.lds-dual-ring').show();
				fetch(ajaxurl, {
					method: 'POST',
					body: form_data,
				}).then(function (response) {
					if (response.ok) {
						return response.json();
					}
					return Promise.reject(response);
				}).then(function (data) {
					$('.lds-dual-ring').hide();
					console.log(data);
					pe_data.search_taxonomies.terms[taxonomy.name] = data.data;
					init_select();
				}).catch(function (error) {
					$('.lds-dual-ring').hide();
					showInfo('An error occurred while retrieving taxonomy data', 3000);
					$('#' + groupId).remove();
					pe_data.search_taxonomies[type].filter(el => el !== taxonomy.name);
					$('.selectTaxonomy[data-type="' + type + '"]').selectPageData(not_selected_taxonomies(type));
				});
			}
		}

		/**
		 * Initialization of selects lists of additional taxonomies received from the server
		 */
		(function() {
			for(let tax_name of pe_data.search_taxonomies.include_from_server) {
				let tax = pe_data.search_taxonomies.list.find(tax => tax.name === tax_name);
				addSearchTaxonomy(tax.name, tax.label, 'include');
			}
			for(let tax_name of pe_data.search_taxonomies.exclude_from_server) {
				let tax = pe_data.search_taxonomies.list.find(tax => tax.name === tax_name);
				addSearchTaxonomy(tax.name, tax.label, 'exclude');
			}
		})();

		/** Btn search taxonomy add */
		$('.search-fieldset .button--plus').on('click', function (e) {
			e.preventDefault();
			let type = $(this).data('type');
			let taxonomy = $(this).parent().find('[data-type="'+type+'"]').selectPageSelectedData();
			if ( !taxonomy.length ) {
				return;
			}
			taxonomy = taxonomy[0];
			addSearchTaxonomy(taxonomy.name, taxonomy.label, type);
		});

		/** Selects */
		$('.selectTaxonomy[data-type="include"]').selectPage({
			showField: 'label',
			keyField: 'name',
			data: not_selected_taxonomies('include'),
			multiple: false,
			formatItem: function (data) {
				return data.label;
			}
		});
		$('.selectTaxonomy[data-type="exclude"]').selectPage({
			showField: 'label',
			keyField: 'name',
			data: not_selected_taxonomies('exclude'),
			multiple: false,
			formatItem: function (data) {
				return data.label;
			}
		});
		$('.selectTags').selectPage({
			showField : 'name',
			keyField : 'slug',
			data : pe_data.search_taxonomies.terms['product_tag'],
			multiple : true,
			formatItem : function(data){
				return data.name;
			}
		});
		$('.selectCats').selectPage({
			showField : 'name',
			keyField : 'slug',
			data : pe_data.search_taxonomies.terms['product_cat'],
			multiple : true,
			formatItem : function(data){
				return data.name;
			}
		});
		$('.selectTagsEdit').selectPage({
			showField : 'name',
			keyField : 'id',
			data : pe_data.search_taxonomies.terms['product_tag'],
			multiple : true,
			formatItem : function(data){
				return data.name;
			}
		});
		$('.selectStatuses').selectPage({
			data : pe_data.product_statuses,
			keyField: 'key',
			showField : 'key',
			multiple : true,
		});

		/** Reset form button */
		$('.reset_form').on('click', function () {
			let $form = $(this).parent('form');
			$form.trigger('reset');
			$form.find('[type="search"]').val('');
			$form.find('[name="exact_match"]').prop('checked', false);
			$form.find('.selectCats, .selectStatuses, .selectTags, .taxonomy_selected_terms').selectPageClear();
		});

		/** Show round inputs */
		$('.change_regular_price').on('change', function () {
			if (['2','3','4'].includes($(this).val())) {
				$('.round_regular_price').show();
			} else {
				$('.round_regular_price').hide();
				$('.precision_regular_price').hide();
				$('.round_regular_price').val('');
				$('input[name="precision_regular_price"]').val('');
			}
		});
		$('.round_regular_price').on('change', function () {
			if ($(this).val()) {
				$('.precision_regular_price').show();
			} else {
				$('.precision_regular_price').hide();
			}
		});
		$('.change_sale_price').on('change', function () {
			if (['2','3','4'].includes($(this).val())) {
				$('.round_sale_price').show();
			} else {
				$('.round_regular_price').val('');
				$('input[name="precision_sale_price"]').val('');
				$('.round_sale_price').hide();
				$('.precision_sale_price').hide();
			}
		});
		$('.round_sale_price').on('change', function () {
			if ($(this).val()) {
				$('.precision_sale_price').show();
			} else {
				$('.precision_sale_price').hide();
			}
		});

		/** Submit handler for bulk changes form. */
		$('#bulk-changes').submit(function (e) {
			e.preventDefault();
			if (isRequested) return false;
			isRequested = true;
			let form = $(this);
			let data = new FormData(this);
			let process_id = Date.now();
			let ids = [];
			data.append('nonce', pe_data.nonce);
			data.append('process_id', process_id);
			$('input[type="checkbox"][name="ids[]"]:checked').map(function () {
				ids.push($(this).val());
			});
			$('.cb-vr-all-parent.collapse:checked').map(function () {
				$(this).data('children_ids').forEach((el) =>
					ids.push(el)
				)
			});
			data.append("ids", ids.join('|'));
			form.find('input[type="submit"]').prop('disabled', true);
			hideInfo();
			$('.lds-dual-ring').show();
			fetch(form.attr('action'), {
				method: 'POST',
				body: data,
			}).then(function (response) {
				stop_observe_progress_status();
				isRequested = false;
				if (response.ok) {
					return response.json();
				}

				return Promise.reject(response);
			}).then(function (data) {
				console.log(data);
				showInfo(data.message, 3000);
				data.content.forEach((el) => {
					let $tr = $('tr[data-id="' + el.id + '"]');
					$tr.find('.td-price').html(el.price);
					$tr.find('.td-regular-price').html(el.regular_price);
					$tr.find('.td-sale-price').html(el.sale_price);
					$tr.find('.td-date-on-sale-from').html(el.date_on_sale_from);
					$tr.find('.td-date-on-sale-to').html(el.date_on_sale_to);
					$tr.find('.td-tags').html(el.tags);
				});
				form.find('input[type="submit"]').prop('disabled', false);
				$('.lds-dual-ring').hide();
				form[0].reset();
				form.find('.selectTagsEdit').selectPageClear();
				// Reset round inputs
				$('.change_to').trigger('change');
				if (data.reverse) {
					$('.do_reverse').show().data('id', data.reverse['id']).html(product_editor_object['str_undo'] + data.reverse['name']);
				}
			}).catch(function (error) {
				stop_observe_progress_status();
				isRequested = false;
				showInfo('Error occurred!', 3000);
				if (typeof error.json === "function") {
					error.json().then(jsonError => {
						alert(jsonError.message);
						console.warn(jsonError);
					}).catch(genericError => {
						console.warn("Generic error from API");
						alert(error.statusText);
					});
				} else {
					console.warn("Fetch error");
					console.warn(error);
					alert('Error! ' + error);
				}
				form.find('input[type="submit"]').prop('disabled', false);
				$('.lds-dual-ring').hide();
			});
			/** Get progress for process_id */
			observe_progress_status(process_id);
		});

		/** Sends requests to track the progress of the request */
		function observe_progress_status(process_id) {
			if (progressIntervalHandle) {
				return;
			}
			isProgressRequested = false;
			show_progress_bar(0);
			progressIntervalHandle = setInterval(function () {
				if (!isProgressRequested && progressIntervalHandle) {
					isProgressRequested = true;
					$.get(pe_data.admin_post_url, {action: 'pe_get_progress', process_id: process_id})
						.done(function (data) {
							console.log('Progress: ', data, '%');
							data = parseInt(data);
							if (progressIntervalHandle) {
								show_progress_bar(data);
								if (data == 100) {
									stop_observe_progress_status();
								}
							}
						})
						.fail(function (error) {
							console.log(error);
							stop_observe_progress_status();
						})
						.always(function () {
							isProgressRequested = false;
						});
				}
			}, 1500 );
		}

		/** Stops progress tracking */
		function stop_observe_progress_status() {
			clearInterval(progressIntervalHandle);
			progressIntervalHandle = null;
		}

		/** Show progress bar */
		function show_progress_bar(value) {
			if (typeof value === 'number' && value >= 0 && value <= 100 )
				$('.ajax-info').show().children('.inner')
					.html('<progress value="'+value+'" max="100">'+value+' %</progress>'+value+'%')
		}

		/** Apply checkboxes */
		function check_checkboxes(selector) {
			let checked_all = true;
			$(selector).each((ind, el) => {
				if (!$(el).prop('checked')) {
					checked_all = false;
					return false;
				}
			});
			return checked_all;
		}

		$('.cb-pr-all').click(function () {
			if (this.checked) {
				$('.cb-pr').prop('checked', true);
			} else {
				$('.cb-pr').prop('checked', false);
			}
		});
		$('.cb-vr-all').click(function () {
			if (this.checked) {
				$('.cb-vr,.cb-vr-all-parent').prop('checked', true);
			} else {
				$('.cb-vr,.cb-vr-all-parent').prop('checked', false);
			}
		});
		$('table.pe-product-table').on('change', '.cb-vr-all-parent', function () {
			let parent_id = $(this).data('id');
			if (this.checked) {
				$('.cb-vr[data-parent="' + parent_id + '"]').prop('checked', true);
			} else {
				$('.cb-vr[data-parent="' + parent_id + '"]').prop('checked', false);
			}
			$('.cb-vr-all').prop('checked', check_checkboxes('.cb-vr, .cb-vr-all-parent'));
		});
		$('table.pe-product-table').on('click', '.cb-vr', function () {
			let parent_id = $(this).data('parent');
			$('.cb-vr-all-parent[data-id="' + parent_id + '"]').prop('checked', check_checkboxes('.cb-vr[data-parent="' + parent_id + '"]'));
			$('.cb-vr-all').prop('checked', check_checkboxes('.cb-vr, .cb-vr-all-parent'));
		});
		$('table.pe-product-table').on('click', '.cb-pr', function () {
			if (!this.checked) {
				$('.cb-pr-all').prop('checked', false);
			} else {
				$('.cb-pr-all').prop('checked', check_checkboxes('.cb-pr'));
			}
		});
		/** End applying checkboxes */

		/** Handler for clicking on a table cell available for editing. */
		$('table.pe-product-table').on('click', '.editable', function (e) {
			if ($(this).find('form').length)
				return;
			discardEditBoxes();
			let $el = $(this),
				id = $el.parent().data('id'),
				old_value = $el.html(),
				tmplNode = document.getElementById("tmp-edit-single").content.cloneNode(true);
			$(tmplNode).find('input[name="ids"]').val(id);
			$(tmplNode).find('.pe-edit-box').data('old_value', old_value);
			$(tmplNode).find('form').submit(onSubmitSingleValue);
			$(tmplNode).find('.discard').on('click', (e) => {
				e.stopPropagation();
				discardEditBoxes()
			});
			if ($el.hasClass('td-regular-price')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="number" class="focus" name="_regular_price" value="' + old_value + '" autocomplete="off" step="0.01">');
				$(tmplNode).find('input#change_action').prop('name', 'change_regular_price').val(1);
				$el.html(tmplNode);
			} else if ($el.hasClass('td-sale-price')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="number" class="focus" name="_sale_price" value="' + old_value + '" autocomplete="off" step="0.01">');
				$(tmplNode).find('input#change_action').prop('name', 'change_sale_price').val(1);
				$el.html(tmplNode);
			} else if ($el.hasClass('td-date-on-sale-from')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="text" class="date-picker focus" name="_sale_date_from" value="' + old_value + '" placeholder="From&hellip; YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">');
				$(tmplNode).find('input#change_action').prop('name', 'change_date_on_sale_from').val(1);
				$el.html(tmplNode);
				$el.find('.date-picker').datepicker(datepicker_options);
			} else if ($el.hasClass('td-date-on-sale-to')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="text" class="date-picker focus" name="_sale_date_to" value="' + old_value + '" placeholder="To&hellip; YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">');
				$(tmplNode).find('input#change_action').prop('name', 'change_date_on_sale_to').val(1);
				$el.html(tmplNode);
				$el.find('.date-picker').datepicker(datepicker_options);
			}
			$el.find('.focus').focus().select();
		});


		/** Handler for toggle variations of a variable product. */
		$('table.pe-product-table').on('click', '.lbl-toggle', function (e) {
			if (isRequested) return;
			let $sib_input = $(this).siblings('input'),
				id = $sib_input.data('id');
			if ($sib_input.hasClass('collapse')) {
				isRequested = true;
				$('.lds-dual-ring').show();
				$.get(pe_data.admin_post_url, {action: 'expand_product_variable', id: id})
					.done(function (data) {
						$sib_input.parents('tr').after(data);
						$sib_input.addClass('expand').removeClass('collapse');
						if ($sib_input.prop('checked')) {
							$('.cb-vr[data-parent="' + id + '"]').prop('checked', true);
						}
					})
					.fail(function (error) {
						alert($getTextError(error));
					})
					.always(function () {
						$('.lds-dual-ring').hide();
						isRequested = false;
					});
			} else {
				$sib_input.addClass('collapse').removeClass('expand');
				$('tr[data-parent_id="' + id + '"]').remove();
			}
		});

		/** Handler for rollback of the last change. */
		$('.do_reverse').click(function () {
			let reverse_id = $(this).data('id');
			if (
				isRequested
				|| !reverse_id
				|| !confirm(product_editor_object['str_reverse_dialog'])
			) return;
			let process_id = Date.now();
			isRequested = true;
			$('.lds-dual-ring').show();
			$.get(pe_data.admin_post_url, {
				action: 'reverse_products_data',
				nonce: pe_data.nonce,
				reverse_id: reverse_id,
				process_id: process_id
			})
				.done(function (data) {
					document.location.reload();
				})
				.fail(function (error) {
					$('.lds-dual-ring').hide();
					isRequested = false;
					alert($getTextError(error));
				})
				.always(function () {
				});
			/** Get progress for process_id */
			observe_progress_status(process_id);
		});

		/** Show\hide columns visibility list */
		$('.show-cols__link').on('click', function () {
			$(this).toggleClass('active');
		});

		/** Handler for clicking on the column visibility checkbox */
		$('.show-cols__list').on('click', 'input[type="checkbox"]', function() {
			let $this = $(this),
				column_name = $this.data('name'),
				visible = $(this).prop('checked') ? '1' : '',
				error_func = () => {$this.prop('checked', !$this.prop('checked')); alert('Failed to execute a request to the server')};

			$('.show-cols__list input[type="checkbox"]').attr('disabled', true);

			$.post(pe_data.ajax_url, {
				nonce: pe_data.nonce,
				action: 'pe_set_visible_column',
				column_name, visible
			})
				.done(function (data) {
					try {
						let jdata = JSON.parse(data);
						if (jdata['status'] == 'ok')
							$('#table_columns_visibility').html(jdata['style']);
					} catch (e) {
						console.log(data);
						error_func();
					}
				})
				.fail(function (error) {
					error_func();
					console.log(error);
				})
				.always(function () {
					$('.show-cols__list input[type="checkbox"]').attr('disabled', false);
				});

		});


	});

	/** Common function for getting error (ajax jquery) */
	function $getTextError(error) {
		try {
			return (JSON.parse(error.responseText)).message;
		} catch (e) {
			return error.statusText;
		}
	}

	/** When press Escape - close inline edit boxes */
	$(document).keyup(function (e) {
		if (e.key === "Escape") {
			discardEditBoxes();
		}
	});

	/** Close inline edit boxes */
	function discardEditBoxes() {
		$('table .pe-edit-box').each((i, el) => $(el).parents('td').html($(el).data('old_value')))
	}

	/** Show popup message */
	function showInfo(message, delay = 1300) {
		let $box = $('.ajax-info');
		$box.children('.inner').html(message);
		$box.fadeIn(500)
			.delay(delay)
			.fadeOut(1000);
	}

	/** Hide popup message */
	function hideInfo() {
		$('.ajax-info').hide();
	}

	/** Submit handler for inline edit form */
	function onSubmitSingleValue(e) {
		e.preventDefault();
		if (isRequested) return false;
		isRequested = true;
		let form = $(this),
			data = new FormData(this);
		data.append('nonce', pe_data.nonce);
		form.find('input[type="submit"]').prop('disabled', true);
		hideInfo();
		$('.lds-dual-ring').show();

		fetch(form.attr('action'), {
			method: 'POST',
			body: data,
		}).then(function (response) {
			isRequested = false;
			if (response.ok) {
				return response.json();
			}
			return Promise.reject(response);
		}).then(function (data) {
			console.log(data);
			showInfo(data.message);
			data.content.forEach((el) => {
				let $tr = $('tr[data-id="' + el.id + '"]');
				$tr.find('.td-price').html(el.price);
				$tr.find('.td-regular-price').html(el.regular_price);
				$tr.find('.td-sale-price').html(el.sale_price);
				$tr.find('.td-date-on-sale-from').html(el.date_on_sale_from);
				$tr.find('.td-date-on-sale-to').html(el.date_on_sale_to);
				$tr.find('.td-tags').html(el.tags);
			});
			form.find('input[type="submit"]').prop('disabled', false);
			$('.lds-dual-ring').hide();
			if (data.reverse) {
				$('.do_reverse').show().data('id', data.reverse['id']).html(product_editor_object['str_undo'] + data.reverse['name']);
			}
		}).catch(function (error) {
			isRequested = false;
			if (typeof error.json === "function") {
				error.json().then(jsonError => {
					alert(jsonError.message);
					console.warn(jsonError);
				}).catch(genericError => {
					console.warn("Generic error from API");
					alert(error.statusText);
				});
			} else {
				console.warn("Fetch error");
				console.warn(error);
				alert('Error! ' + error);
			}
			form.find('input[type="submit"]').prop('disabled', false);
			$('.lds-dual-ring').hide();
		});
	}
})(jQuery);