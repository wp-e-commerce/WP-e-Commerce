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
	 * @param  {object}   data      Data to pass to the AJAX destination
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
			t.unbind('click');
			t.bind('click', function() {
				var form = $(this).parents('form');
				if (! validateForm( form ) )
					return false;
			});
		};

		var restore_ajax_submit = function() {
			var t = $('#submit');
			t.unbind('click');
			$.each(submit_handlers, function(index, obj) {
				t.bind('click', obj.handler);
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

			$('[name="image"]').bind('change', function() {
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

	$(document).delegate('form input.prdfil', 'click', function(){
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
		event.preventDefault();
	});

	// delete upload
	$(document).delegate('.file_delete_button', 'click', function(){
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
	//Animateedit products columns
	jQuery('.wpsc-separator').livequery(function(){
		jQuery(this).click(function(){
			if(jQuery('#wpsc-col-left').css('width') == '20px'){
				left_col_width = '50%';
				right_col_width = '48%';

			}else{
				left_col_width = '20px';
				right_col_width = '95%';
			}
			if(left_col_width == '50%'){
				jQuery('.tablenav').show();
				jQuery('#posts-filter').show();
			}else{
				jQuery('.tablenav').hide();
				jQuery('#posts-filter').hide();

			}
			//jQuery(this).css('background-position','0');
			jQuery('#wpsc-col-left').animate(
			{
				width : left_col_width
			},
			50,
			function(){
				//On complete

				}
				);
			jQuery('#wpsc-col-right').animate(
			{
				width : right_col_width
			},
			50,
			function(){
				//On complete

				}
				);
		});

	});

	//new currency JS in admin product page
	jQuery('div.new_layer').livequery(function(){
		jQuery(this).hide();

	});
	var firstclick = true
	jQuery('a.wpsc_add_new_currency').livequery(function(){
		jQuery(this).click(function(event){
			if(firstclick == true){
				jQuery('div.new_layer').show();
				html = jQuery('div.new_layer').html();
				firstclick = false;
			}else{
				jQuery('div.new_layer').after('<div>'+html+'</div>');
			}
			event.preventDefault();
		});
	});
	//delete currency layer in admin product page
	jQuery('a.wpsc_delete_currency_layer').livequery(function(){
		jQuery(this).click(function(event){
			jQuery(this).prev('input').val('');
			jQuery(this).prev('select').val('');
			jQuery(this).parent('div:first').hide();
			event.preventDefault();
		});
	});

	//delete currency layer in admin product page
	jQuery('a.wpsc_mass_resize').livequery(function(){
		jQuery(this).click(function(event){
			this_href = jQuery(this).attr('href');
			parent_element = jQuery(this).parent();
			extra_parameters = jQuery("input[type='text']", parent_element).serialize();
			window.location = this_href+"&"+extra_parameters;
			return false;
		});
	});

	jQuery('#wpsc_product_list .wpsc_ie_save').live('click', function(){
		jQuery(this).parents('tr:first').find('.loading_indicator').css('visibility', 'visible');
		var id =jQuery(this).parents('tr:first').find('.wpsc_ie_id').val();
		var title = jQuery(this).parents('tr:first').find('.wpsc_ie_title').val();
		var weight = jQuery(this).parents('tr:first').find('.wpsc_ie_weight').val();
		var stock = jQuery(this).parents('tr:first').find('.wpsc_ie_stock').val();
		var price = jQuery(this).parents('tr:first').find('.wpsc_ie_price').val();
		var special_price = jQuery(this).parents('tr:first').find('.wpsc_ie_special_price').val();
		var sku = jQuery(this).parents('tr:first').find('.wpsc_ie_sku').val();
		//post stuff
		var data = {
			action: 'wpsc_ie_save',
			id: id,
			title: title,
			weight: weight,
			stock: stock,
			price: price,
			special_price: special_price,
			sku: sku
		};

		jQuery.post(ajaxurl, data, function(response) {
			response = eval(response);
			if(response.error){
				alert(response.error);
				jQuery('#post-' + response.id + ' a.row-title, #post-' + response.id + ' td > span').show();
				jQuery('#post-' + response.id + ' td input.wpsc_ie_field, #post-' + response.id + ' td .wpsc_inline_actions').show();
				jQuery('#post-' + response.id + ' .loading_indicator').css('visibility', 'hidden');
			}
			else{
				jQuery('#post-' + response.id + ' .post-title a.row-title').text(response.title);
				jQuery('#post-' + response.id + ' .column-weight span').text(response.weight);
				jQuery('#post-' + response.id + ' .column-stock span').text(response.stock);
				jQuery('#post-' + response.id + ' .column-SKU span').text(response.sku);

				jQuery('#post-' + response.id + ' .column-price .pricedisplay').html(jQuery(response.price).text());
				jQuery('#post-' + response.id + ' .column-sale_price .pricedisplay').html(jQuery(response.special_price).text());

				jQuery('#post-' + response.id + ' a.row-title, #post-' + response.id + ' td > span').hide();
				jQuery('#post-' + response.id + ' td input.wpsc_ie_field, #post-' + response.id + ' td .wpsc_inline_actions').show();
				jQuery('#post-' + response.id + ' .loading_indicator').css('visibility', 'hidden');
			}
		});
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

	// this helps show the links in the product list table, it is partially done using CSS, but that breaks in IE6
	jQuery("tr.product-edit").hover(
		function() {
			jQuery(".wpsc-row-actions", this).css("visibility", "visible");
		},
		function() {
			jQuery(".wpsc-row-actions", this).css("visibility", "hidden");
		}
		);

	jQuery("div.admin_product_name a.shorttag_toggle").livequery(function(){
		jQuery(this).toggle(
			function () {
				jQuery("div.admin_product_shorttags", jQuery(this).parents("table.product_editform")).css('display', 'block');
				return false;
			},
			function () {
				//jQuery("div#admin_product_name a.shorttag_toggle").toggleClass('toggled');
				jQuery("div.admin_product_shorttags", jQuery(this).parents("table.product_editform")).css('display', 'none');
				return false;
			}
			);
	});
	jQuery('.editinline').live('click', function(){
		setTimeout('editinline_get_id()',200);

	});

	jQuery('a.add_variation_item_form').livequery(function(){
		jQuery(this).click(function() {
			form_field_container = jQuery(this).siblings('#variation_values');
			form_field = jQuery("div.variation_value", form_field_container).eq(0).clone();

			jQuery('input.text',form_field).attr('name','new_variation_values[]');
			jQuery('input.text',form_field).val('');
			jQuery('input.variation_values_id',form_field).remove();

			jQuery(form_field_container).append(form_field);
			return false;
		});
	});


	jQuery('div.variation_value a.delete_variation_value').livequery(function(){
		jQuery(this).click( function() {
			element_count = jQuery("#variation_values div").size();

			if(element_count > 1) {

				parent_element = jQuery(this).parent("div.variation_value");
				variation_value_id = jQuery("input.variation_values_id", parent_element).val();

				delete_url = jQuery(this).attr('href');
				post_values = "remove_variation_value=true&variation_value_id="+variation_value_id;
				jQuery.post( delete_url, "ajax=true", function(returned_data) {
					jQuery("#variation_row_"+returned_data).fadeOut('fast', function() {
						jQuery(this).remove();
					});
				});
			}
			return false;
		});
	});


	jQuery("#add-product-image").click(function(){
		swfu.selectFiles();
	});


	jQuery('a.closeimagesettings').livequery(function(){
		jQuery(this).click( function() {
			jQuery('.image_settings_box').hide();
		});
	});

	setTimeout(bulkedit_edit_tags_hack, 1000);


	jQuery("#gallery_list").livequery(function(){
		jQuery(this).sortable({
			revert: false,
			placeholder: "ui-selected",
			start: function(e,ui) {
				jQuery('#image_settings_box').hide();
				jQuery('a.editButton').hide();
				jQuery('img.deleteButton').hide();
				jQuery('ul#gallery_list').children('li').removeClass('first');
			},
			stop:function (e,ui) {
				jQuery('ul#gallery_list').children('li:first').addClass('first');
			},
			update: function (e,ui){
				input_set = jQuery.makeArray(jQuery("#gallery_list li:not(.ui-sortable-helper) input.image-id"));
				//console.log(input_set);
				set = new Array();
				for( var i in input_set) {
					set[i] = jQuery(input_set[i]).val();
				}

				order = set.join(',');
				product_id = jQuery('#product_id').val();

				postVars = "product_id="+product_id+"&order="+order;
				jQuery.post( 'index.php?wpsc_admin_action=rearrange_images', postVars, function(returned_data) {
					eval(returned_data);
					jQuery('#gallery_image_'+image_id).children('a.editButton').remove();
					jQuery('#gallery_image_'+image_id).children('div.image_settings_box').remove();
					jQuery('#gallery_image_'+image_id).append(image_menu);
				});

			},
			'opacity':0.5
		});
	});

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
	limited_stock_checkbox.bind('click', function ()  {
		toggle_stock_fields(limited_stock_checkbox.is(':checked'));
	});


	jQuery("#table_rate_price").livequery(function(){
		if (!this.checked) {
			jQuery("#table_rate").hide();
		}
		jQuery(this).click( function() {
			if (this.checked) {
				jQuery("#table_rate").show();
			} else {
				jQuery("#table_rate").hide();
			}
		});
	});

	jQuery("#custom_tax_checkbox").livequery(function(){
		jQuery(this).click( function() {
			if (this.checked) {
				jQuery("#custom_tax").show();
			} else {
				jQuery("#custom_tax input").val('');
				jQuery("#custom_tax").hide();
			}
		});
	});

	jQuery(".add_level").livequery(function(){
		jQuery(this).click(function() {
			added = jQuery(this).parent().children('table').append('<tr><td><input type="text" size="10" value="" name="table_rate_price[quantity][]"/> and above</td><td><input type="text" size="10" value="" name="table_rate_price[table_price][]"/></td></tr>');
		});
	});


	jQuery(".remove_line").livequery(function(){
		jQuery(this).click(function() {
			jQuery(this).parent().parent('tr').remove();
		});
	});

	// hover for gallery view
	jQuery("div.previewimage").livequery(function(){
		jQuery(this).hover(
			function () {
				jQuery(this).children('img.deleteButton').show();
				if(jQuery('div.image_settings_box').css('display')!='block')
					jQuery(this).children('a.editButton').show();
			},
			function () {
				jQuery(this).children('img.deleteButton').hide();
				jQuery(this).children('a.editButton').hide();
			}
			);
	});


	// display image editing menu
	jQuery("a.editButton").livequery(function(){
		jQuery(this).click( function(){
			jQuery(this).hide();
			jQuery('div.image_settings_box').show('fast');
		});
	});
	// hide image editing menu
	jQuery("a.closeimagesettings").livequery(function(){
		jQuery(this).click(function (e) {
			jQuery("div#image_settings_box").hide();
		});
	});

	jQuery('.wpsc_featured_product_toggle').livequery(function(){
		jQuery(this).click(function(event){
			target_url = jQuery(this).attr('href');
			post_values = "ajax=true";
			jQuery.post(target_url, post_values, function(returned_data){
				eval(returned_data);
			});
			return false;
		});
	});

	// Fill in values when inline editor appears.
	jQuery('.inline-editor').livequery(function() {
		var id = jQuery(this).attr('id');
		id = id.replace(/^edit-/, '');

		if (!id || !parseInt(id)) {
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


// function for switching the state of the image upload forms
function wpsc_upload_switcher(target_state) {
	switch(target_state) {
		case 'flash':
			jQuery("div.browser-image-uploader").css("display","none");
			jQuery("div.flash-image-uploader").css("display","block");
			jQuery.post( 'index.php?admin=true', "admin=true&ajax=true&save_image_upload_state=true&image_upload_state=1", function(returned_data) { });
			break;

		case 'browser':
			jQuery("div.flash-image-uploader").css("display","none");
			jQuery("div.browser-image-uploader").css("display","block");
			jQuery.post( 'index.php?admin=true', "admin=true&ajax=true&save_image_upload_state=true&image_upload_state=0", function(returned_data) { });
			break;
	}
}

// function for switching the state of the extra resize forms
function image_resize_extra_forms(option) {
	container = jQuery(option).parent();
	jQuery("div.image_resize_extra_forms").css('display', 'none');
	jQuery("div.image_resize_extra_forms",container).css('display', 'block');
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

/*
 * Modified copy of the wordpress edToolbar function that does the same job, it uses document.write, we cannot.
*/
function wpsc_edToolbar() {
	//document.write('<div id="ed_toolbar">');
	output = '';
	for (i = 0; i < edButtons.length; i++) {
		output += 	wpsc_edShowButton(edButtons[i], i);
	}
	output += '<input type="button" id="ed_spell" class="ed_button" onclick="edSpell(edCanvas);" title="' + quicktagsL10n.dictionaryLookup + '" value="' + quicktagsL10n.lookup + '" />';
	output += '<input type="button" id="ed_close" class="ed_button" onclick="edCloseAllTags();" title="' + quicktagsL10n.closeAllOpenTags + '" value="' + quicktagsL10n.closeTags + '" />';
	//	edShowLinks(); // disabled by default
	//document.write('</div>');
	jQuery('div#ed_toolbar').html(output);
}


/*
 * Modified copy of the wordpress edShowButton function that does the same job, it uses document.write, we cannot.
*/

function wpsc_edShowButton(button, i) {
	if (button.id == 'ed_img') {
		output = '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertImage(edCanvas);" value="' + button.display + '" />';
	}
	else if (button.id == 'ed_link') {
		output = '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertLink(edCanvas, ' + i + ');" value="' + button.display + '" />';
	}
	else {
		output = '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" class="ed_button" onclick="edInsertTag(edCanvas, ' + i + ');" value="' + button.display + '"  />';
	}
	return output;
}



function fillcategoryform(catid) {
	post_values = 'ajax=true&admin=true&catid='+catid;
	jQuery.post( 'index.php', post_values, function(returned_data) {
		jQuery('#formcontent').html( returned_data );
		jQuery('form.edititem').css('display', 'block');
		jQuery('#additem').css('display', 'none');
		jQuery('#blank_item').css('display', 'none');
		jQuery('#productform').css('display', 'block');
		jQuery("#loadingindicator_span").css('visibility','hidden');
	});
}

function submit_status_form(id) {
	document.getElementById(id).submit();
}

function getcurrency(id) {
//ajax.post("index.php",gercurrency,"wpsc_admin_action=change_currency&currencyid="+id);
}

function showadd_categorisation_form() {
	if(jQuery('div_categorisation').css('display') != 'block') {
		jQuery('div#add_categorisation').css('display', 'block');
		jQuery('div#edit_categorisation').css('display', 'none');
	} else {
		jQuery('div#add_categorisation').css('display', 'none');
	}
	return false;
}


function showedit_categorisation_form() {
	if(jQuery('div#edit_categorisation').css('display') != 'block') {
		jQuery('div#edit_categorisation').css('display', 'block');
		jQuery('div#add_categorisation').css('display', 'none');
	} else {
		jQuery('div#edit_categorisation').css('display', 'none');
	}
	return false;
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

function editinline_get_id(){
	id = jQuery('.inline-edit-row').attr('id');
	id = id.replace('edit-','');
	has_var = jQuery('#inline_'+id+'_has_var').val();
	if( has_var == '1'){
   		jQuery(".wpsc-cols").css('display', 'none');
		jQuery(".wpsc-cols:first").text(wpsc_adminL10n.bulk_edit_no_vars)
		.addClass('wpsc_var_description')
		.css('display','block');
	}else{
  		jQuery(".wpsc-cols").css('display', 'block');
	}


}

// inline-edit-post.dev.js prepend tag edit textarea into the last fieldset. We need to undo that
function bulkedit_edit_tags_hack() {
	jQuery('<fieldset class="inline-edit-col-right"><div class="inline-edit-col"></div></fieldset>').insertBefore('#bulk-edit .wpsc-cols:first').find('.inline-edit-col').append(jQuery('#bulk-edit .inline-edit-tags'));
}