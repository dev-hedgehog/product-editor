(function( $ ) {
	'use strict';

	let isRequested = false;

	$(function() {
		$('#bulk-changes').submit(function (e) {
			e.preventDefault();

			let form = $(this);
			//e.originalEvent.target
			let data = new FormData(this);
			data.append('nonce', window.pe_nonce);
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
				if (response.ok) {
					return response.json();
				}
				//response.text().then(text => { throw new Error(text) })
				return Promise.reject(response);
			}).then(function (data) {
				console.log(data);
				showInfo(data.message);
				data.content.forEach((el) => {
					let $tr = $('tr[data-id="'+el.id+'"]');
					$tr.find('.td-price').html(el.price);
					$tr.find('.td-regular-price').html(el.regular_price);
					$tr.find('.td-sale-price').html(el.sale_price);
					$tr.find('.td-akciya').html(el.akciya);
				});
				form.find('input[type="submit"]').prop('disabled', false);
				$('.lds-dual-ring').hide();
				form[0].reset();
				if (data.reverse) {
					$('.do_reverse').show();
				}
			}).catch(function (error) {
				if (error.json) {
					error.json().then( error => {
						alert(error.message);
						console.warn(error.message);
					})
				} else {
					alert(error);
					console.warn(error);
				}
				form.find('input[type="submit"]').prop('disabled', false);
				$('.lds-dual-ring').hide();
			});
		});

		/** Apply checkboxes */
		function check_checkboxes(selector) {
			let checked_all = true;
			$(selector).each((ind,el) => {
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
		$('table.widefat').on('change', '.cb-vr-all-parent', function () {
			let parent_id = $(this).data('id');
			if (this.checked) {
				$('.cb-vr[data-parent="'+parent_id+'"]').prop('checked', true);
			} else {
				$('.cb-vr[data-parent="'+parent_id+'"]').prop('checked', false);
			}
			$('.cb-vr-all').prop('checked',check_checkboxes('.cb-vr, .cb-vr-all-parent'));
		});
		$('table.widefat').on('click','.cb-vr', function () {
			let parent_id = $(this).data('parent');
			$('.cb-vr-all-parent[data-id="'+parent_id+'"]').prop('checked', check_checkboxes('.cb-vr[data-parent="'+parent_id+'"]'));
			$('.cb-vr-all').prop('checked', check_checkboxes('.cb-vr, .cb-vr-all-parent'));
		});
		$('table.widefat').on('click','.cb-pr', function () {
			if (!this.checked) {
				$('.cb-pr-all').prop('checked',false);
			} else {
				$('.cb-pr-all').prop('checked', check_checkboxes('.cb-pr'));
			}
		});
		/** End applying checkboxes */

		$('table.widefat').on('click', '.editable', function (e) {
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
			$(tmplNode).find('.discard').on('click', (e) => {e.stopPropagation();discardEditBoxes()});
			if ($el.hasClass('td-regular-price')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="number" class="focus" name="_regular_price" value="'+old_value+'">');
				$(tmplNode).find('input#change_action').prop('name', 'change_regular_price').val(1);
				$el.html(tmplNode);
			} else if ($el.hasClass('td-sale-price')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<input type="number" class="focus" name="_sale_price" value="'+old_value+'">');
				$(tmplNode).find('input#change_action').prop('name', 'change_sale_price').val(1);
				$el.html(tmplNode);
			} else if ($el.hasClass('td-akciya')) {
				$(tmplNode).find('.pe-edit-box')
					.prepend('<label>Товар по акции<select name="change_akciya" class="focus"><option value="1">Да</option><option value="2" '+(old_value=='Нет'? 'selected':'')+'>Нет</option></select></label>');
				$(tmplNode).find('input#change_action').prop('name', 'change_akciya').val(1);
				$el.html(tmplNode);
			}
			$el.find('.focus').focus();
		});


		$('table.widefat').on('click', '.lbl-toggle', function (e) {
			if (isRequested) return;
			let $sib_input = $(this).siblings('input'),
				id = $sib_input.data('id');
			if ($sib_input.hasClass('collapse')) {
				isRequested = true;
				$('.lds-dual-ring').show();
				$.get('/wp-admin/admin-post.php', {action: 'expand_product_variable', id: id})
					.done(function(data) {
						$sib_input.parents('tr').after(data);
						$sib_input.addClass('expand').removeClass('collapse');
						if ($sib_input.prop('checked')) {
							$('.cb-vr[data-parent="'+id+'"]').prop('checked', true);
						}
					})
					.fail(function(error) {
						alert(error);
					})
					.always(function() {
						$('.lds-dual-ring').hide();
						isRequested = false;
					});
			} else {
				$sib_input.addClass('collapse').removeClass('expand');
				$('tr[data-parent_id="'+id+'"]').remove();
			}
		});

		$('.do_reverse').click(function () {
			if (isRequested) return;
			isRequested = true;
			$('.lds-dual-ring').show();
			$.get('/wp-admin/admin-post.php', {action: 'reverse_products_data', nonce: window.pe_nonce})
				.done(function(data) {
					document.location.reload();
				})
				.fail(function(error) {
					$('.lds-dual-ring').hide();
					isRequested = false;
					alert(error.responseText);
				})
				.always(function() {
				});
		});



	});

	$(document).keyup(function(e) {
		if (e.key === "Escape") {
			discardEditBoxes();
		}
	});


	function discardEditBoxes() {
		$('table .pe-edit-box').each((i, el)=> $(el).parents('td').html($(el).data('old_value')))
	}

	function showInfo(message) {
		let $box = $('.ajax-info');
		$box.children('.inner').html(message);
		$box.fadeIn(500)
			.delay(1300)
			.fadeOut(1000);
	}
	function hideInfo() {
		$('.ajax-info').hide();
	}

	function onSubmitSingleValue(e) {
		e.preventDefault();

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
			if (response.ok) {
				return response.json();
			}
			return Promise.reject(response);
		}).then(function (data) {
			console.log(data);
			showInfo(data.message);
			data.content.forEach((el) => {
				let $tr = $('tr[data-id="'+el.id+'"]');
				$tr.find('.td-price').html(el.price);
				$tr.find('.td-regular-price').html(el.regular_price);
				$tr.find('.td-sale-price').html(el.sale_price);
				$tr.find('.td-akciya').html(el.akciya);
			});
			form.find('input[type="submit"]').prop('disabled', false);
			$('.lds-dual-ring').hide();
			if (data.reverse) {
				$('.do_reverse').show();
			}
		}).catch(function (error) {
			if (error.json) {
				error.json().then( error => {
					alert(error.message);
					console.warn(error.message);
				})
			} else {
				alert(error);
				console.warn(error);
			}
			form.find('input[type="submit"]').prop('disabled', false);
			$('.lds-dual-ring').hide();
		});
	}
})( jQuery );