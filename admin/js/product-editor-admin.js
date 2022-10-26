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

		/** Selects */
		if (typeof window.pe_taxonomies_object !== 'undefined' && window.pe_taxonomies_object['product_tag'])
			$('.selectTags').selectPage({
				showField : 'name',
				keyField : 'slug',
				data : window.pe_taxonomies_object['product_tag']['terms'],
				multiple : true,
				formatItem : function(data){
					return data.name;
				}
			});
		if (typeof window.pe_taxonomies_object !== 'undefined' && window.pe_taxonomies_object['product_cat'])
			$('.selectCats').selectPage({
				showField : 'name',
				keyField : 'slug',
				data : window.pe_taxonomies_object['product_cat']['terms'],
				multiple : true,
				formatItem : function(data){
					return data.name;
				}
			});
		if (typeof window.pe_statuses_object !== 'undefined')
			$('.selectStatuses').selectPage({
				data : window.pe_statuses_object,
				keyField: 'key',
				showField : 'key',
				multiple : true,
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
			data.append('nonce', window.pe_nonce);
			data.append('process_id', process_id);
			$('input[type="checkbox"][name="ids[]"]:checked').map(function () {
				data.append("ids[]", $(this).val());
			});
			$('.cb-vr-all-parent.collapse:checked').map(function () {
				$(this).data('children_ids').forEach((el) =>
					data.append("ids[]", el)
				)
			});

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
				});
				form.find('input[type="submit"]').prop('disabled', false);
				$('.lds-dual-ring').hide();
				form[0].reset();
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
					$.get('/wp-admin/admin-post.php', {action: 'pe_get_progress', process_id: process_id})
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
			$(tmplNode).find('input[name="ids[]"]').val(id);
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
					.prepend('<input type="text" class="date-picker" name="_sale_date_from" value="' + old_value + '" placeholder="From&hellip; YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">');
				$(tmplNode).find('input#change_action').prop('name', 'change_date_on_sale_from').val(1);
				$el.html(tmplNode);
				$el.find('.date-picker').datepicker(datepicker_options);
			} else if ($el.hasClass('td-date-on-sale-to')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="text" class="date-picker" name="_sale_date_to" value="' + old_value + '" placeholder="To&hellip; YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" autocomplete="off">');
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
				$.get('/wp-admin/admin-post.php', {action: 'expand_product_variable', id: id})
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
			$.get('/wp-admin/admin-post.php', {
				action: 'reverse_products_data',
				nonce: window.pe_nonce,
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
		data.append('nonce', window.pe_nonce);
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