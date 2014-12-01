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

		return $.post( ajaxurl, data, handler, 'json' );
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

		return $.get( ajaxurl, data, handler, 'json' );
	};

	if( pagenow == 'edit-wpsc_product_category' ) {
		function category_sort(e, ui){
			var order = $(this).sortable('toArray'),
				data = {
				action: 'category_sort_order',
				sort_order: order,
				parent_id: 0
			};

			jQuery.post( ajaxurl, data );
		}

		var submit_handlers = [];

		var disable_ajax_submit = function() {
			var t = $('#submit');

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
			if ( 'undefined' === typeof WPSC_Term_List_Levels ) {
				return;
			}

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

}(jQuery));

jQuery(document).ready(function($){
		
	$('.ui-sortable li .list_gallery_image').mouseover(function(){
		$('.product_gallery_image_delete_button', $(this).parent()).show();
	}).mouseout(function(){
		$('.product_gallery_image_delete_button', $(this).parent()).hide();
	});

	$('.product_gallery_image_delete_button').click(function(){
		var product_gallery_image_data = {
			action: 'product_gallery_image_delete',
			product_gallery_image_id: $(this).parent().parent().find('.product_gallery_image_id').val(),
			product_gallery_post_id: $(this).parent().parent().find('.product_gallery_post_id').val(),
			wpsc_gallery_nonce_check: $('.nonce_class').val()
		};
		$.post(ajaxurl, product_gallery_image_data, function(response){});
		$(this).parent().parent().fadeOut( 'slow' );
	});
		
		
		
	$( '#wpsc_price' ).on( 'change', wpsc_update_price_live_preview );
	$( '#wpsc_sale_price' ).on( 'change', wpsc_update_price_live_preview );

	jQuery('td.hidden_alerts img').each(function(){
		var t = jQuery(this);
		t.appendTo(t.parents('tr').find('td.column-title strong'));
	});


	jQuery( '#stock_limit_quantity' ).change( function(){
		wpsc_push_v2t( '#stock_limit_quantity', '#wpsc_product_stock_metabox_live_title > p > span' );
	});

	jQuery( 'em.wpsc_metabox_live_title' ).each( function( i, v ) {
		var $em = jQuery( this ), $parent = $em.parents( 'div.postbox' ), $h3 = $parent.find( 'h3' );
		$em.appendTo( $h3 );

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
		if ( title === '' ) {
			jQuery('<div id="notice" class="error"><p>' + wpsc_adminL10n.empty_coupon + '</p></div>').insertAfter('div.wrap > h2').delay(2500).hide(350);
			return false;
		}
	});

	/*
	Alternative Currencies
	Trigger and handle UI events for adding and removing currency layers.
	*/

	var currencyRowTemplate = jQuery( '.wpsc-currency-layers tr.template' ).remove().removeClass( 'template hidden' ).removeAttr( 'id' );

	// Hide table if empty
	if ( jQuery( '.wpsc-currency-layers tbody tr' ).length === 0 ) {
		jQuery( '.wpsc-currency-layers table' ).hide();
	}

	// Add new currency layer
	jQuery( '.wpsc-currency-layers' ).on( 'click', 'a.wpsc_add_new_currency', function( e ) {
		jQuery( this ).siblings( 'table' ).show();
		jQuery( '.wpsc-currency-layers tbody' ).append( currencyRowTemplate.clone() );
		e.preventDefault();
	});

	// Delete currency layer in admin product page
	jQuery( '.wpsc-currency-layers' ).on( 'click', 'a.wpsc_delete_currency_layer', function( e ) {
		var currencyRow = jQuery( this ).closest( 'tr' );
		currencyRow.find( 'input' ).val( '' );
		currencyRow.find( 'select' ).val( '' );
		if ( currencyRow.siblings().length === 0 ) {
			currencyRow.closest( 'table' ).hide();
		}
		currencyRow.remove();
		e.preventDefault();
	});

	/*
	Quantity Discounts
	Trigger and handle UI events for adding and removing quantity dicounts.
	*/

	var qtyRowTemplate = jQuery( '.wpsc-quantity-discounts tr.template' ).remove().removeClass( 'template hidden' ).removeAttr( 'id' );

	// Hide table if empty
	if ( jQuery( '.wpsc-quantity-discounts tbody tr' ).length === 0 ) {
		jQuery( '.wpsc-quantity-discounts table' ).hide();
	}

	// Add new row to rate table
	jQuery( '.wpsc-quantity-discounts' ).on( 'click', '.add_level', function( e ) {
		jQuery( this ).siblings( 'table' ).show();
		added = jQuery( '.wpsc-quantity-discounts tbody' ).append( qtyRowTemplate.clone() );
		e.preventDefault();
	});

	// Remove a row from rate table
	jQuery( '.wpsc-quantity-discounts' ).on( 'click', '.remove_line', function( e ) {
		var qtyRow = jQuery( this ).closest( 'tr' );
		qtyRow.find( 'input' ).val( '' );
		if ( qtyRow.siblings().length === 0 ) {
			qtyRow.closest( 'table' ).hide();
		}
		qtyRow.remove();
		e.preventDefault();
	});

	/*
	As far as I can tell, WP provides no good way of unsetting elements in the bulk edit area...
	tricky jQuery action will do for now....not ideal whatsoever, nor eternally stable.
	*/
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
	};

	if (limited_stock_checkbox.size() > 0) {
		toggle_stock_fields(limited_stock_checkbox.is(':checked'));
	}

	// show or hide the stock input forms
	limited_stock_checkbox.on('click', function ()  {
		toggle_stock_fields(limited_stock_checkbox.is(':checked'));
	});

	jQuery("#custom_tax_checkbox").on( 'click', function(){
			if (this.checked) {
				jQuery("#custom_tax").show();
			} else {
				jQuery("#custom_tax input").val('');
				jQuery("#custom_tax").hide();
			}
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
		prototype.find('input').focus();

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

	jQuery( '#wpsc_product_details_forms .category-tabs a, #wpsc_product_delivery_forms .category-tabs a' ).click(function(event){
		var $this = jQuery(this), href = $this.attr('href');

		$this.closest('ul').find('li').removeClass('tabs');
		$this.closest('li').addClass('tabs');
		$this.closest('div').find('.tabs-panel').hide();
		jQuery(href).show();
		event.preventDefault();
	});

	// Meta table
	var meta_inp_tem = jQuery('#wpsc_new_meta_template').remove().removeAttr('id');

	jQuery('#wpsc_add_custom_meta').click(function(){
		if ( jQuery( 'tr.no-meta' ).is( ':visible' ) ) {
			 jQuery( 'tr.no-meta' ).hide();
		}

		jQuery('#wpsc_product_meta_table tbody').append(meta_inp_tem.clone());
		event.preventDefault();
	});

	// Init delivery metabox live title
	if (jQuery('#wpsc_product_delivery_forms').length > 0){
		jQuery('#wpsc_product_delivery_forms input, #wpsc_product_delivery_forms select').change(wpsc_update_delivery_metabox_live_title);
		wpsc_update_delivery_metabox_live_title();
	}

	// Init product details metabox live title
	if (jQuery('#wpsc_product_details_forms').length > 0){
		jQuery('#wpsc_product_details_forms a').click(wpsc_update_product_details_metabox_live_title);
		wpsc_update_product_details_metabox_live_title();
	}

	wpsc_update_price_live_preview();
});


// Remove new/empty custom meta input row
function wpsc_remove_empty_meta(caller){
	jQuery(caller).closest('tr').remove();

	wpsc_update_product_details_metabox_live_title();

	if ( ! jQuery( '#wpsc_product_meta_table tbody tr' ).not( '.no-meta' ).length ) {
		jQuery( 'tr.no-meta' ).show();
	}

	event.preventDefault();
}

// function for removing custom meta
function wpsc_remove_custom_meta(caller, meta_id) {
	var post_data = {
		action    : 'remove_product_meta',
		'meta_id' : meta_id,
		nonce     : jQuery(caller).data('nonce')
	};

	var response_handler = function(response) {
		if (! response.is_successful) {
			alert(response.error.messages.join("\n"));
			return;
		}
		jQuery(caller).closest('tr').remove();
	};

	jQuery.wpsc_post(post_data, response_handler);
	wpsc_update_product_details_metabox_live_title();

	if ( ! jQuery( '#wpsc_product_meta_table tbody tr' ).not( '.no-meta' ).length ) {
		jQuery( 'tr.no-meta' ).show();
	}

	event.preventDefault();
}

// Copy value of caller to target text
function wpsc_push_v2t(caller, target_slt){
	jQuery(target_slt).text(jQuery(caller).val());
}

function wpsc_update_price_live_preview(){
	var price      = jQuery('#wpsc_price').val();
	var sale_price = jQuery('#wpsc_sale_price').val();

	if (sale_price > 0){
		jQuery('#wpsc_product_price_metabox_live_title>p>span').text(sale_price);
		jQuery('#wpsc_product_price_metabox_live_title>del>span').text(price);
		jQuery('#wpsc_product_price_metabox_live_title>del').show();
	} else {
		jQuery('#wpsc_product_price_metabox_live_title>p>span').text(price);
		jQuery('#wpsc_product_price_metabox_live_title>del').hide();
	}
}

// Compose and update live title for shipping metabox
function wpsc_update_delivery_metabox_live_title(){

	if ( ! jQuery('#wpsc_product_delivery_forms').length )  {
		return;
	}

	var weight              = jQuery('#wpsc-product-shipping-weight').val();
	var weight_unit         = jQuery('#wpsc-product-shipping-weight-unit').val();
	var length              = jQuery('#wpsc-product-shipping-length').val();
	var width               = jQuery('#wpsc-product-shipping-width').val();
	var height              = jQuery('#wpsc-product-shipping-height').val();
	var dimensions_unit     = jQuery('#wpsc-product-shipping-dimensions-unit').val();
	var number_of_downloads = jQuery('.wpsc_product_download_row').length;

	var vol = Math.round( ( length * width * height ) * 100) / 100; // Round up to two decimal
	var downloads_name = ( number_of_downloads !== 1 ) ? wpsc_adminL10n.meta_downloads_plural : wpsc_adminL10n.meta_downloads_singular;
	var output = '';

	if ( jQuery( '.wpsc-product-shipping-section' ).length ) {
		output += weight + ' ' + weight_unit + ', ' + vol + ' ' + dimensions_unit + '<sup>3</sup>, ';
	}

	output += number_of_downloads + downloads_name;

	jQuery( '#wpsc_product_delivery_metabox_live_title > p' ).html( output );
}

function wpsc_update_product_details_metabox_live_title(){
	if (jQuery('#wpsc_product_details_forms').length <= 0) return;

	var number_of_photos = jQuery('#wpsc_product_gallery img').length;
	var number_of_meta   = jQuery('#wpsc_product_meta_table tbody tr').not('.no-meta').length;

	var output = number_of_photos + ' images, ';
		output += number_of_meta + ' metadata';

	jQuery('#wpsc_product_details_metabox_live_title>p').html(output);
}

function wpsc_update_product_gallery_tab(obj){
	var output, url;

	output = '<div id="wpsc_product_gallery">';
		output += '<ul>';

		for (var i = 0; i < obj.length; i++) {

			if ( 'undefined' !== typeof obj[i].sizes.thumbnail ) {
				url = obj[i].sizes.thumbnail.url;
			} else {
				url = obj[i].sizes.full.url;
			}

			output += '<li>';
				output += '<img src="' + url + '">';
				output += '<input type="hidden" name="wpsc-product-gallery-imgs[]" value="' + obj[i].id + '">';

			output += '</li>';
		}

		output += '</ul>';
		output += '<div class="clear"></div>';
	output += '</div>';

	jQuery('#wpsc_product_gallery').replaceWith(output);
	wpsc_update_product_details_metabox_live_title();
}

var prevElement = null;
var prevOption = null;

function hideOptionElement(id, option) {
	if (prevOption == option) {
		return;
	}
	if (prevElement !== null) {
		prevElement.style.display = "none";
	}

	if (id === null) {
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