(function($){
	/**
	 * Wrapper for $.post. Takes care of the 'wpsc_action' and 'action' data arguments.
	 *
	 * @since  3.8.9
	 * @param  {object} data      Data to pass to the AJAX destination
	 * @param  {function} handler Response handler
	 */
	$.wpsc_post = function(data, handler) {
		data['wpsc_action'] = data['action'];
		data['action'] = 'wpsc_ajax';

		$.post(ajaxurl, data, handler, 'json');
	};

	/**
	 * Wrapper for $.get. Takes care of the 'wpsc_action' and 'action' data arguments.
	 *
	 * @since  3.8.9
	 * @param  {object}   data    Data to pass to the AJAX destination
	 * @param  {function} handler Response handler
	 */
	$.wpsc_get = function(data, handler) {
		data['wpsc_action'] = data['action'];
		data['action'] = 'wpsc_ajax';

		$.get(ajaxurl, data, handler, 'json');
	};

	if( pagenow == 'edit-wpsc_product_category' ) {
		function category_sort(e, ui){
			var order = $(this).sortable('toArray'),
				data = {
				action: 'category_sort_order',
				sort_order: order,
				parent_id: 0
			};

			jQuery.post(ajaxurl, data);
		}

		var submit_handlers = [];

		var disable_ajax_submit = function() {
			var t = $('#submit');
			console.log(t);
			console.log(t.data('events'));
			console.log(t.data('events').click);
			if (t.data('events'))
				submit_handlers = t.data('events').click;
			t.off('click');
			t.on('click', function() {
				var form = $(this).parents('form');
				if (! validateForm( form ) )
					return false;
			});
		};

		var restore_ajax_submit = function() {
			var t = $('#submit');
			t.off('click');
			$.each(submit_handlers, function(index, obj) {
				t.on('click', obj.handler);
			});
		};

		$(function(){
			var table = $('body.edit-tags-php .wp-list-table');
			table.find('tbody tr').each(function(){
				var t = $(this),
					id = t.attr('id').replace(/[^0-9]+/g, '');
				t.data('level', WPSC_Term_List_Levels[id]);
				t.data('id', id);
			});
			table.wpsc_sortable_table({
				stop : category_sort
			});

			$('.edit-tags-php form').attr('enctype', 'multipart/form-data').attr('encoding', 'multipart/form-data');

			$('[name="image"]').on('change', function() {
				var t = $(this);

				if (t.val())
					disable_ajax_submit();
				else
					restore_ajax_submit();
			});
		});

		$(function() {
			$('.wpsc_select_all').click(function(){
				$('input:checkbox', $(this).parent().siblings('.multiple-select') ).each(function(){ this.checked = true; });
				return false;
			});
			$('.wpsc_select_none').click(function(){
				$('input:checkbox', $(this).parent().siblings('.multiple-select') ).each(function(){ this.checked = false; });
				return false;
			});
		});
	}

	$(document).on( 'click', 'form input.prdfil', function(){
		var t = $(this);
		var post_data = {
			'select_product_file[]' : [],
			product_id : t.parent('form.product_upload').find('input#hidden_id').val(),
			nonce : t.data('nonce'),
			action : 'upload_product_file'
		};
		var products = jQuery(this).parent("form.product_upload").find('input').serializeArray();

		for (var index in products) {
			post_data['select_product_file[]'].push(products[index].value);
		}

		jQuery.wpsc_post(post_data, function(response){
			tb_remove();
			if (! response.is_successful) {
				alert(response.error.messages.join("\n"));
				return;
			}
			jQuery('#wpsc_product_download_forms .select_product_file tbody').append(response.obj.content).
				find('p.no-item').hide().end().
				find('p:even').removeClass('alt').end().
				find('p:odd').addClass('alt');
		});
		return false;
	});

	// delete upload
	$(document).on( 'click', '.file_delete_button', function(){
		var t = $(this),
			post_values = {
				action     : 'delete_file',
				file_name  : t.data('file-name'),
				product_id : t.data('product-id'),
				nonce      : t.data('nonce')
			},
			response_handler = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}

				t.closest('.wpsc_product_download_row').fadeOut('fast', function() {
					$('div.select_product_file p:even').removeClass('alt');
					$('div.select_product_file p:odd').addClass('alt');
					$(this).remove();
				});
			};

		$.wpsc_post( post_values, response_handler);

		return false;
	});

})(jQuery);

jQuery(document).ready(function(){
	jQuery('td.hidden_alerts img').each(function(){
		var t = jQuery(this);
		t.appendTo(t.parents('tr').find('td.column-title strong'));
	});

	/* 	Coupon edit functionality */
	jQuery('.modify_coupon').hide();
	jQuery('.wpsc_edit_coupon').click(function(){
		id = jQuery(this).attr('rel');
		id = 'coupon_box_'+id;
		if(jQuery('#'+id).hasClass('displaynone')){
			jQuery('#'+id).show();
			jQuery('#'+id).removeClass('displaynone');
		}else{
			jQuery('#'+id).addClass('displaynone');
			jQuery('#'+id).hide();
		}

	});
	jQuery("form[name='add_coupon']").submit(function() {
		var title = jQuery("form[name='add_coupon'] input[name='add_coupon_code']").val();
		if ( title == '') {
			jQuery('<div id="notice" class="error"><p>' + wpsc_adminL10n.empty_coupon + '</p></div>').insertAfter('div.wrap > h2').delay(2500).hide(350);
			return false;
		}
	});

	//new currency JS in admin product page

	var firstclick = true;

	jQuery('#wpsc_price_control_forms').on( 'click', 'a.wpsc_add_new_currency', function( event ){
			if(firstclick == true){
				jQuery('div.new_layer').show();
				html = jQuery('div.new_layer').html();
				firstclick = false;
			}else{
				jQuery('div.new_layer').after('<div>'+html+'</div>');
			}
			event.preventDefault();
	});

	//delete currency layer in admin product page
	jQuery('#wpsc_price_control_forms').on( 'click', 'a.wpsc_delete_currency_layer', function(event){
			jQuery(this).prev('input').val('');
			jQuery(this).prev('select').val('');
			jQuery(this).parent('div:first').hide();
			event.preventDefault();
	});

	//As far as I can tell, WP provides no good way of unsetting elements in the bulk edit area...tricky jQuery action will do for now....not ideal whatsoever, nor eternally stable.
	 if( pagenow == 'edit-wpsc-product' ) {
		jQuery('.inline-edit-password-input').closest('.inline-edit-group').css('display', 'none');
		var vcl = jQuery('.inline-edit-col input[name="tax_input[wpsc-variation][]"]').css('display', 'none');
		vcl.each(function(){
			jQuery(this).prev().css('display', 'none');
			jQuery(this).next().css('display', 'none');
			jQuery(this).css('display', 'none');
		});
		jQuery('#bulk-edit select[name=post_parent]').closest('fieldset').css('display', 'none');
		jQuery('.inline-edit-col select[name=post_parent]').parent().css('display', 'none');
		jQuery('.inline-edit-status').parent().css('display', 'none');
	}
		if( wpsc_adminL10n.dragndrop_set == "true" && typenow == "wpsc-product" && adminpage == "edit-php" ) {
			// this makes the product list table sortable
			jQuery('table.widefat:not(.tags)').sortable({
		update: function(event, ui) {
			var category_id = jQuery('select#wpsc_product_category option:selected').val(),
				product_order = jQuery('table.widefat').sortable( 'toArray' ),
				post_data = {
					action : 'save_product_order',
					'category_id' : category_id,
					'post[]' : product_order,
					nonce : wpsc_adminL10n.save_product_order_nonce
				};
			jQuery.wpsc_post(post_data, function(response) {
				if (! response.is_successful)
					alert(response.error.messages.join("\n"));
			});
		},
		items: 'tbody tr',
		axis: 'y',
		containment: 'table.widefat tbody',
		placeholder: 'product-placeholder',
				cursor: 'move',
				cancel: 'tr.inline-edit-wpsc-product'
			});
	}

	var limited_stock_checkbox = jQuery('input.limited_stock_checkbox');
	var toggle_stock_fields = function(checked) {
		jQuery('div.edit_stock').toggle(checked);
		jQuery('th.column-stock input, td.stock input').each(function(){
			this.disabled = ! checked;
		});
	}

	if (limited_stock_checkbox.size() > 0) {
		toggle_stock_fields(limited_stock_checkbox.is(':checked'));
	}

	// show or hide the stock input forms
	limited_stock_checkbox.on('click', function ()  {
		toggle_stock_fields(limited_stock_checkbox.is(':checked'));
	});

	jQuery("#table_rate_price").on( 'click', function(){
		if (this.checked) {
			jQuery("#table_rate").show();
		} else {
			jQuery("#table_rate").hide();
		}
	});

	jQuery("#custom_tax_checkbox").on( 'click', function(){
			if (this.checked) {
				jQuery("#custom_tax").show();
			} else {
				jQuery("#custom_tax input").val('');
				jQuery("#custom_tax").hide();
			}
	});

	jQuery( 'div#table_rate' ).on( 'click', '.add_level', function(){
		added = jQuery(this).parent().children('table').append('<tr><td><input type="text" size="10" value="" name="table_rate_price[quantity][]"/> and above</td><td><input type="text" size="10" value="" name="table_rate_price[table_price][]"/></td></tr>');
	});

	jQuery( 'div#table_rate' ).on( 'click', '.remove_line', function(){
		jQuery(this).parent().parent('tr').remove();
	});

	jQuery( '.wpsc_featured_product_toggle' ).on( 'click', function(){
		post_values = {
			product_id : jQuery( this ).parents( 'tr' ).attr( 'id' ).replace( 'post-', '' ),
			action : 'update_featured_product'
		};

		jQuery.post( ajaxurl, post_values, function( response ) {
			jQuery( '.featured_toggle_' + response.product_id ).html( "<img class='" + response.color + "' src='" + response.image + "' alt='" + response.text + "' title='" + response.text + "' />" );
		}, 'json' );

		return false;
	});

	// Fill in values when inline editor appears.
	// This should be done properly so we don't need livequery here - see http://codex.wordpress.org/Plugin_API/Action_Reference/quick_edit_custom_box
	jQuery('.inline-editor').livequery(function() {
		var id = jQuery(this).attr('id');
		id     = id.replace(/^edit-/, '');

		if ( ! id || ! parseInt( id, 10 ) ) {
			return;
		}

		var weight = jQuery('#inline_' + id + '_weight').text(),
			stock = jQuery('#inline_' + id + '_stock').text(),
			price = jQuery('#inline_' + id + '_price').text(),
			sale_price = jQuery('#inline_' + id + '_sale_price').text(),
			sku = jQuery('#inline_' + id + '_sku').text();

		jQuery(this).find('.wpsc_ie_weight').val(weight);
		jQuery(this).find('.wpsc_ie_stock').val(stock);
		jQuery(this).find('.wpsc_ie_price').val(price);
		jQuery(this).find('.wpsc_ie_sale_price').val(sale_price);
		jQuery(this).find('.wpsc_ie_sku').val(sku);
	});

	jQuery( 'div.coupon-condition' ).each( function( index, value ){
		if( jQuery( 'select[name="rules[operator][]"]', jQuery( this ) ).length !== 0 ) {
			margin = jQuery( 'select.ruleprops', jQuery( this ) ).offset().left - jQuery( this ).offset().left;
			margin = parseInt( margin, 10 ) - 1;
			jQuery( 'select[name="rules[operator][]"]', jQuery( this ) ).css( 'margin-left', '-' + margin + 'px' );
		}
	});

	jQuery( '.coupon-conditions' ).on( 'click', '.wpsc-button-plus', function() {
		var parent = jQuery( this ).closest( '.coupon-condition' ),
			conditions_count = jQuery( '.coupon-condition' ).size(),
			prototype = parent.clone();

			var operator_box = jQuery('<select/>',{name:'rules[operator][]'});

			if ( jQuery( 'select[name="rules[operator][]"]', prototype ).length === 0 ) {
				operator_box.append("<option value='and'>" + wpsc_adminL10n.coupons_compare_and +  "</option>");
				operator_box.append("<option value='or'>" + wpsc_adminL10n.coupons_compare_or + "</option>");
				prototype.prepend(operator_box);
			}


		prototype.find('select').val('');
		prototype.find('input').val('');
		prototype.css( { 'opacity' : '0' } );
		prototype.insertAfter(parent);

		margin = jQuery( 'select.ruleprops', prototype ).offset().left - prototype.offset().left;
		margin = parseInt( margin, 10 ) - 1;

		prototype.animate( { opacity: 1, 'margin-left': '-' + margin + 'px', height: 'show' }, 150 );

		return false;
	});

	jQuery('.coupon-conditions').on( 'click', '.wpsc-button-minus', function() {
		var parent = jQuery(this).closest('.coupon-condition'),
			conditions_count = jQuery('.coupon-condition').size(),
			prototype;

		if ( jQuery( this ).index( jQuery( '.wpsc-button-minus' ) ) === 0 )
			return false;

		if (conditions_count == 1) {
			prototype = parent.clone();
			prototype.find('select').val('');
			prototype.find('input').val('');
			prototype.hide();
			jQuery('.coupon-conditions').find('td').prepend(prototype);
			parent.slideUp(150, function(){
				prototype.slideDown(150);
				parent.remove();
			});

			return false;
		}

		parent.slideUp(150, function(){
			parent.remove();
		});

		return false;
	});
});

// function for adding more custom meta
function add_more_meta(e) {
	var current_meta_forms = jQuery(e).parent().children("div.product_custom_meta:last"), // grab the form container
		new_meta_forms = current_meta_forms.clone(); // clone the form container

	new_meta_forms.find('input, textarea').val('');
	current_meta_forms.after(new_meta_forms);  // append it after the container of the clicked element
	return false;
}

// function for removing custom meta
function remove_meta(e, meta_id) {
	var t = jQuery(e),
		current_meta_form = t.parent("div.product_custom_meta"),  // grab the form container
		post_data = {
			action    : 'remove_product_meta',
			'meta_id' : meta_id,
			nonce     : t.data('nonce')
		},
		response_handler = function(response) {
			if (! response.is_successful) {
				alert(response.error.messages.join("\n"));
				return;
			}
			jQuery("div#custom_meta_"+meta_id).remove();
		};

	jQuery.wpsc_post(post_data, response_handler);
	return false;
}

var prevElement = null;
var prevOption = null;

function hideOptionElement(id, option) {
	if (prevOption == option) {
		return;
	}
	if (prevElement != null) {
		prevElement.style.display = "none";
	}

	if (id == null) {
		prevElement = null;
	} else {
		prevElement = document.getElementById(id);
		jQuery('#'+id).css( 'display','block');
	}
	prevOption = option;
}

function hideelement(id) {
	state = document.getElementById(id).style.display;
	//alert(document.getElementById(id).style.display);
	if(state != 'block') {
		document.getElementById(id).style.display = 'block';
	} else {
		document.getElementById(id).style.display = 'none';
	}
}

function getcurrency(id) {
	//ajax.post("index.php",gercurrency,"wpsc_admin_action=change_currency&currencyid="+id);
}

function hideelement1(id, item_value) {
	//alert(value);
	if(item_value == 5) {
		jQuery(document.getElementById(id)).css('display', 'block');
	} else {
		jQuery(document.getElementById(id)).css('display', 'none');
	}
}

function show_status_box(id,image_id) {
	state = document.getElementById(id).style.display;
	if(state != 'block') {
		document.getElementById(id).style.display = 'block';
		document.getElementById(image_id).src =  wpsc_adminL10n.wpsc_core_images_url + '/icon_window_collapse.gif';
	} else {
		document.getElementById(id).style.display = 'none';
		document.getElementById(image_id).src =  wpsc_adminL10n.wpsc_core_images_url + '/icon_window_expand.gif';
	}
	return false;
}
