// This is the wp-e-commerce front end javascript "library"

jQuery(document).ready(function(){	
	jQuery('td.hidden_alerts img').each(function(){
		var t = jQuery(this);
		t.appendTo(t.parents('tr').find('td.column-title strong'));
	});

	jQuery('a.update_variations_action').click(function(){
		jQuery("<img class='loading' src='images/loading.gif' height='15' width='15' />").insertAfter(this);
		var edit_var_val = jQuery('div.variation_checkboxes input:checked').serialize();
		var description = jQuery('#content_ifr').contents().find('body').html();
		var additional_description = jQuery('textarea#additional_description').text();
		var name = jQuery('input#title').val();
		var product_id = jQuery('input#product_id').val();
		var post_values = edit_var_val + '&description=' + description + '&additional_description=' + additional_description + '&name=' + name + '&product_id=' + product_id;
		jQuery('div#wpsc_product_variation_forms table.widefat tbody').fadeTo(200, 0, function(){
			jQuery.post(ajaxurl + '?action=wpsc_update_variations',post_values, function(returned_data){
				jQuery('div#wpsc_product_variation_forms table.widefat tbody').html(returned_data).fadeTo(200, 1);
				jQuery('img.loading').hide();
			});
		});
		return false;

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
	jQuery("form[name='add_coupon'] input[name='submit_coupon']").click(function() {
		var title = jQuery("form[name='add_coupon'] input[name='add_coupon_code']").val();
		if ( title == '') {
			alert('Please enter a coupon code.');
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

	jQuery('.wpsc_prod_thumb_option').livequery(function(){
		jQuery(this).focus(function(){
			jQuery('.wpsc_mass_resize').css('visibility', 'visible');
		});
	});

	jQuery('.wpsc_prod_thumb_option').livequery(function(){
		jQuery(this).blur(function(){
			jQuery('.wpsc_mass_resize').css('visibility', 'hidden');
		});
	});


	//Delete checkout options on settings>checkout page
	jQuery('.wpsc_delete_option').livequery(function(){
		jQuery(this).click(function(event){
			jQuery(this).parent().parent('tr').remove();
			event.preventDefault();
		});

	});
	//Changing the checkout fields page
	jQuery('#wpsc_checkout_sets').livequery(function(){
		jQuery(this).change(function(){

			});

	});
	//checkboxes on checkout page

	jQuery('.wpsc_add_new_checkout_option').livequery(function(){
		jQuery(this).click(function(event){
			form_id = jQuery(this).attr('title');
			id = form_id.replace('form_options', '');
			output = "<tr class='wpsc_grey'><td></td><td><input type='text' value='' name='wpsc_checkout_option_label"+id+"[]' /></td><td colspan='4'><input type='text' value='' name='wpsc_checkout_option_value"+id+"[]' />&nbsp;<a class='wpsc_delete_option' href='' ><img src='" + WPSC_CORE_IMAGES_URL + "/trash.gif' alt='"+TXT_WPSC_DELETE+"' title='"+TXT_WPSC_DELETE+"' /></a></td></tr>";
			jQuery(this).parent().parent('tr').after(output);
			event.preventDefault();
		});

	});


	jQuery('.wpsc_edit_checkout_options').livequery(function(){
		jQuery(this).click(function(event){
			if(!jQuery(this).hasClass('triggered')){
				jQuery(this).addClass('triggered');
				id = jQuery(this).attr('rel');
				id = id.replace('form_options[', '');
				id = id.replace(']', '');
				post_values = "form_id="+id;
				jQuery.post('index.php?wpsc_admin_action=check_form_options',post_values, function(returned_data){
					if(returned_data != ''){
						jQuery('#checkout_'+id).after(returned_data);
					}else{
						output =  "<tr class='wpsc_grey'><td></td><td colspan='5'>Please Save your changes before trying to Order your Checkout Forms again.</td></tr>\r\n<tr  class='wpsc_grey'><td></td><th>Label</th><th >Value</th><td colspan='3'><a href=''  class='wpsc_add_new_checkout_option'  title='form_options["+id+"]'>+ New Option</a></td></tr>";
						output += "<tr class='wpsc_grey'><td></td><td><input type='text' value='' name='wpsc_checkout_option_label["+id+"][]' /></td><td colspan='4'><input type='text' value='' name='wpsc_checkout_option_value["+id+"][]' /><a class='wpsc_delete_option' href='' ><img src='" + WPSC_CORE_IMAGES_URL + "/trash.gif' alt='Delete' title='delete' /></a></td></tr>";
						jQuery('#checkout_'+id).after(output);

					}

				});
				jQuery('table#wpsc_checkout_list').sortable('disable');
			}
			event.preventDefault();
		});


	});

	//grid view checkbox ajax to deselect show images only when other checkboxes are selected
	jQuery('#show_images_only').livequery(function(){
		jQuery(this).click(function(){
			imagesonly = jQuery(this).is(':checked');
			if(imagesonly){
				jQuery('#display_variations').attr('checked', false);
				jQuery('#display_description').attr('checked', false);
				jQuery('#display_addtocart').attr('checked', false);
				jQuery('#display_moredetails').attr('checked', false);

			}
		});
	});
	jQuery('#display_variations, #display_description, #display_addtocart, #display_moredetails').livequery(function(){
		jQuery(this).click(function(){
			imagesonly = jQuery(this).is(':checked');

			if(imagesonly){
				jQuery('#show_images_only').attr('checked', false);

			}
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

	jQuery('form input.prdfil').livequery(function(){
		jQuery(this).click(function(event){
			var products = jQuery(this).parent("form.product_upload").find('input').serialize();
			var product_id = jQuery(this).parent("form.product_upload").find('input#hidden_id').val();
			post_values = products + '&product_id=' + product_id;
			jQuery.post('admin.php?wpsc_admin_action=product_files_upload',post_values, function(returned_data){
				tb_remove();
				if (returned_data.length > 0) {
					jQuery('#wpsc_product_download_forms .select_product_file').append(returned_data).
						find('p.no-item').hide().end().
						find('p:even').removeClass('alt').end().
				   		find('p:odd').addClass('alt');
				}
			});
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

	//select all target markets in general settings page
	jQuery('a.wpsc_select_all').livequery(function(){
		jQuery(this).click(function(event){
			jQuery('div#resizeable input:checkbox').attr('checked', true);
			event.preventDefault();

		});

	});
	//select all target markets in general settings page
	jQuery('a.wpsc_select_none').livequery(function(){
		jQuery(this).click(function(event){
			jQuery('div#resizeable input:checkbox').attr('checked', false);
			event.preventDefault();

		});

	});
         if( pagenow == 'edit-wpsc_product_category' ) {
          jQuery('table.tags').sortable({
            axis: 'y',
            items : 'tr',
            containment: 'table.tags tbody',
            placeholder: 'product-placeholder',
            cursor: 'move',
            tolerance: 'pointer',
            update: function(event, ui){
              categorySort(jQuery('table.tags').sortable('toArray'), 0);
            }
          });

          function categorySort(order, parent){
            var data = {
                    action: 'category_sort_order',
                    sort_order: order,
                    parent_id: parent
            };

            var id = '#debugData_';

            jQuery.post(ajaxurl, data, function(response) {
                    jQuery(id).append(response);
            });
            return false;
       }

          jQuery('.edit-tags-php form').attr('enctype', 'multipart/form-data').attr('encoding', 'multipart/form-data');

      }
	//Added for inline editing capabilities
	jQuery('#wpsc_product_list a.wpsc_editinline').live('click', function() {
		var t = jQuery(this),
			tr = t.parents('tr');
		tr.find('td input[type="text"].wpsc_ie_field').each(function(){
			var ti = jQuery(this), p = ti.parents('td');
			if (! p.hasClass('column-stock') || p.css('display') != 'none') {
				ti.innerWidth(p.width());
			}
		}).show();
		tr.find('td .wpsc_inline_actions').show().end().find('a.row-title, td > span').hide();
		return false;
	});

	jQuery('#wpsc_product_list .wpsc_ie_cancel').live('click', function(){
		jQuery(this).parents('tr:first').find('a.row-title, td > span').show();
		jQuery(this).parents('tr:first').find('td input.wpsc_ie_field, td .wpsc_inline_actions').hide();
		jQuery(this).parents('tr:first').find('.loading_indicator').css('visibility', 'hidden');
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
				jQuery('#post-' + response.id + ' td input.wpsc_ie_field, #post-' + response.id + ' td .wpsc_inline_actions').hide();
				jQuery('#post-' + response.id + ' .loading_indicator').css('visibility', 'hidden');
			}
			else{
				jQuery('#post-' + response.id + ' .post-title a.row-title').text(response.title);
				jQuery('#post-' + response.id + ' .column-weight span').text(response.weight);
				jQuery('#post-' + response.id + ' .column-stock span').text(response.stock);
				jQuery('#post-' + response.id + ' .column-SKU span').text(response.sku);

				jQuery('#post-' + response.id + ' .column-price .pricedisplay').html(jQuery(response.price).text());
				jQuery('#post-' + response.id + ' .column-sale_price .pricedisplay').html(jQuery(response.special_price).text());

				jQuery('#post-' + response.id + ' a.row-title, #post-' + response.id + ' td > span').show();
				jQuery('#post-' + response.id + ' td input.wpsc_ie_field, #post-' + response.id + ' td .wpsc_inline_actions').hide();
				jQuery('#post-' + response.id + ' .loading_indicator').css('visibility', 'hidden');
			}
		});
	});

    //As far as I can tell, WP provides no good way of unsetting elements in the bulk edit area...tricky jQuery action will do for now....not ideal whatsoever, nor eternally stable.
     if( pagenow == 'edit-wpsc-product' ) {
        //jQuery('fieldset.inline-edit-col-left .inline-edit-date').css('display','none');
        //jQuery('fieldset.inline-edit-col-center span.title:eq(1), ul.cat-checklist:eq(1)').css('display','none');
        jQuery("label:contains('Date')").css('display', 'none');
        jQuery(".inline-edit-group:contains('Password')").css('display', 'none');
        jQuery('fieldset.inline-edit-col-left.wpsc-cols').css({'float': 'right', 'clear' : 'right'});
        jQuery("label:contains('Parent')").css('display', 'none');
        jQuery("label:contains('Status')").css('display', 'none');
        jQuery("span:contains('Variations')").css('display', 'none');
        jQuery("ul.wpsc-variation-checklist").css('display', 'none');
        jQuery("div.inline-edit-date").css('display', 'none');
    }
        if( wpsc_adminL10n.dragndrop_set == "true" && typenow == "wpsc-product" && adminpage == "edit-php" ) {
            // this makes the product list table sortable
            jQuery('table.widefat:not(.tags)').sortable({
		update: function(event, ui) {
			category_id = jQuery('select#wpsc_product_category option:selected').val();
			product_order = jQuery('table.widefat').sortable( 'serialize' );
			post_values = "category_id="+category_id+"&"+product_order;
			jQuery.post( 'index.php?wpsc_admin_action=save_product_order', post_values, function(returned_data) { });
		},
		items: 'tbody tr',
		axis: 'y',
		containment: 'table.widefat tbody',
		placeholder: 'product-placeholder',
                cursor: 'move',
                cancel: 'tr.inline-edit-wpsc-product'
            });
	}

	jQuery('table#wpsc_checkout_list').livequery(function(event){
		//this makes the checkout form fields sortable
		jQuery(this).sortable({

			items: 'tr.checkout_form_field',
			axis: 'y',
			containment: 'table#wpsc_checkout_list',
			placeholder: 'checkout-placeholder',
			handle: '.drag'

		});
		jQuery(this).bind('sortupdate', function(event, ui) {

			//post_values = jQuery(this).sortable();
			//post_values = "category_id="+category_id+"&"+checkout_order;
			post_values = jQuery( 'table#wpsc_checkout_list').sortable( 'serialize');
			jQuery.post( 'index.php?wpsc_admin_action=save_checkout_order', post_values, function(returned_data) { });
		});

	});

	// this helps show the links in the product list table, it is partially done using CSS, but that breaks in IE6
	jQuery("tr.product-edit").hover(
		function() {
			jQuery(".wpsc-row-actions", this).css("visibility", "visible");
		},
		function() {
			jQuery(".wpsc-row-actions", this).css("visibility", "hidden");
		}
		);

	jQuery('tr.wpsc_trackingid_row').hide();

	jQuery('.wpsc_show_trackingid').click(function(event){
		purchlog_id = jQuery(this).attr('title');
		if(jQuery('tr.log'+purchlog_id).hasClass('wpsc_hastracking')){
			jQuery('tr.log'+purchlog_id).removeClass('wpsc_hastracking');
			jQuery('tr.log'+purchlog_id).hide();
		}else{
			jQuery('tr.log'+purchlog_id).addClass('wpsc_hastracking');
			jQuery('tr.log'+purchlog_id).show();
		}
		event.preventDefault();
	});
	// this changes the purchase log item status
	jQuery('.selector').change(function(){
		purchlog_id = jQuery(this).attr('title');
		purchlog_status = jQuery(this).val();
		post_values = "action=purchlog_edit_status&purchlog_id="+purchlog_id+"&purchlog_status="+purchlog_status;
		var ajax_loading = jQuery(this).prev('.ajax-loading');
		ajax_loading.css('visibility', 'visible');
		jQuery.post( ajaxurl, post_values, function(returned_data) {
			ajax_loading.css('visibility', 'hidden');
		});
		
		if(purchlog_status == 4){
			jQuery('tr.log'+purchlog_id).show();

		}
	});

	jQuery('.sendTrackingEmail').click(function(event){
		purchlog_id = jQuery(this).attr('title');
		post_values = "purchlog_id="+purchlog_id;
		var ajax_loader = jQuery(this).prev('.ajax-loading');
		ajax_loader.css('visibility', 'visible');
		jQuery.post( 'index.php?wpsc_admin_action=purchlog_email_trackid', post_values, function(returned_data) { ajax_loader.css('visibility', 'hidden'); });
		event.preventDefault();
	});

	jQuery("a.thickbox").livequery(function(){
		tb_init(this);
	});

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

	jQuery('a.add_new_form_set').livequery(function(){
		jQuery(this).click( function() {
			jQuery(".add_new_form_set_forms").toggle();
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
				//console.log(set);
				/*
						img_id = jQuery('#gallery_image_'+set[0]).parent('li').attr('id');

						jQuery('#gallery_image_'+set[0]).children('img.deleteButton').remove();
						jQuery('#gallery_image_'+set[0]).append("<a class='editButton'>Edit   <img src='" + WPSC_CORE_IMAGES_URL + "/pencil.png' alt ='' /></a>");
// 						jQuery('#gallery_image_'+set[0]).parent('li').attr('id',  "product_image_"+img_id);
						//for(i=1;i<set.length;i++) {
						//	jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
						//	jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='" + WPSC_CORE_IMAGES_URL + "/cross.png'/>");
						//}

						for(i=1;i<set.length;i++) {
							jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
							jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='" + WPSC_CORE_IMAGES_URL + "/cross.png'/>");

							element_id = jQuery('#gallery_image_'+set[i]).parent('li').attr('id');
							if(element_id == 0) {
// 								jQuery('#gallery_image_'+set[i]).parent('li').attr('id', "product_image_"+img_id);
							}
						}
			*/
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




	// show or hide the stock input forms
	jQuery('input.limited_stock_checkbox').live('click', function ()  {
		parent_form = jQuery(this).parents('form');
		if(jQuery(this).is(':checked')) {
			jQuery("div.edit_stock",parent_form).show();
			jQuery("th.column-stock, td.stock", parent_form).show();
		} else {
			jQuery("div.edit_stock", parent_form).hide();
			jQuery("th.column-stock, td.stock", parent_form).hide();
		}
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
	/* shipping options start */
	// gets shipping form for admin page
	// show or hide the stock input forms

	jQuery(".wpsc-payment-actions a").livequery(function(){
		jQuery(this).click( function ()  {
			var module = jQuery(this).attr('rel');
			//console.log(module);
			jQuery.ajax({
				method: "post",
				url: "index.php",
				data: "wpsc_admin_action=get_payment_form&paymentname="+module,
				success: function(returned_data){
					//	console.log(returned_data);
					eval(returned_data);
					//jQuery(".gateway_settings").children(".form-table").html(html)
					jQuery('.gateway_settings h3.hndle').html(payment_name_html);
					jQuery("td.gateway_settings table.form-table").html('<tr><td><input type="hidden" name="paymentname" value="'+module+'" /></td></tr>'+payment_form_html);
					if(has_submit_button != '') {
						jQuery('.gateway_settings div.submit').css('display', 'block');
					} else {
						jQuery('.gateway_settings div.submit').css('display', 'none');
					}
				}
			});
			return false;

		});
	});

	jQuery('#addweightlayer').livequery(function(){
		jQuery(this).click(function(){
			jQuery(this).parent().append("<div class='wpsc_newlayer'><tr class='rate_row'><td><i style='color:grey'>"+TXT_WPSC_IF_WEIGHT_IS+"</i><input type='text' name='weight_layer[]' size='10'> <i style='color:grey'>"+TXT_WPSC_AND_ABOVE+"</i></td><td><input type='text' name='weight_shipping[]' size='10'>&nbsp;&nbsp;<a href='' class='delete_button nosubmit' >"+TXT_WPSC_DELETE+"</a></td></tr></div>");
		});

	});

	jQuery('#addlayer').livequery(function(){
		jQuery(this).click(function(){
			jQuery(this).parent().append("<div class='wpsc_newlayer'><tr class='rate_row'><td><i style='color:grey'>"+TXT_WPSC_IF_PRICE_IS+"</i><input type='text' name='layer[]' size='10'> <i style='color:grey'>"+TXT_WPSC_AND_ABOVE+"</i></td><td><input type='text' name='shipping[]' size='10'>&nbsp;&nbsp;<a href='' class='delete_button nosubmit' >"+TXT_WPSC_DELETE+"</a></td></tr></div>");
			//bind_shipping_rate_deletion();
			return false;
		});

	});

	jQuery('table#gateway_options a.delete_button').livequery(function(){
		jQuery(this).click(function () {
			this_row = jQuery(this).parent().parent('tr .rate_row');
			// alert(this_row);
			//jQuery(this_row).hide();
			if(jQuery(this).hasClass('nosubmit')) {
				// if the row was added using JS, just scrap it
				this_row = jQuery(this).parent('div .wpsc_newlayer');
				jQuery(this_row).remove();
			} else {
				// otherwise, empty it and submit it
				jQuery('input', this_row).val('');
				jQuery(this).parents('form').submit();
			}
			return false;
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

	// delete upload
	jQuery(".file_delete_button").livequery(function(){
		jQuery(this).click(function() {
			url = jQuery(this).attr('href');
			post_values = "ajax=true";
			jQuery.post( url, post_values, function(returned_data) {
				eval(returned_data);
			});
			return false;
		});
	});

	// Options page ajax tab display
	jQuery('#sidemenu li').click(function(){
		page_title = jQuery(this).attr('id');

		wpnonce = jQuery('a',this).attr('href').match(/_wpnonce=(\w{1,})/);
		post_values = "wpsc_admin_action=settings_page_ajax&page_title="+page_title+"&_wpnonce="+wpnonce[1];
		jQuery.post('admin.php?', post_values, function(html){
			//console.log(html);
			jQuery('a.current').removeClass('current');
			jQuery('#'+page_title+' a' ).addClass('current');
			jQuery('#wpsc_options_page').html('');
			jQuery('#wpsc_options_page').html(html);

		});
		return false;

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

	// Form change tracking code.
	jQuery('form.wpsc_form_track input, form.wpsc_form_track textarea').live('change', function() {
		jQuery(this).parents('form.wpsc_form_track:first').addClass('wpsc_form_changed');
	});

	jQuery('form.wpsc_form_track').live('submit', function() {
		jQuery(this).removeClass('wpsc_form_changed');

	});

	// We'd ideally want to be using window.onbeforeonload to toss in a prompt, but that event is too
	// unreliable from a browser to the next. Hooking onto anchors is the next best thing.
	jQuery('form.wpsc_form_track a').live('click', function() {
		if (jQuery(this).attr('href') && jQuery(this).attr('href') != '#' && !jQuery(this).hasClass('wpsc_select_all') && !jQuery(this).hasClass('wpsc_select_none')) {
			if (jQuery('form.wpsc_form_changed').size()) {
				if (!confirm(wpsc_adminL10n.unsaved_changes_detected)) {
					return false;
				}
			}
		}
	});
});

// function for adding more custom meta
function add_more_meta(e) {
	current_meta_forms = jQuery(e).parent().children("div.product_custom_meta:last");  // grab the form container
	new_meta_forms = current_meta_forms.clone(true); // clone the form container
	jQuery("label input", new_meta_forms).val(''); // reset all contained forms to empty
	current_meta_forms.after(new_meta_forms);  // append it after the container of the clicked element
	return false;
}

// function for removing custom meta
function remove_meta(e, meta_id) {
	current_meta_form = jQuery(e).parent("div.product_custom_meta");  // grab the form container
	//meta_name = jQuery("input#custom_meta_name_"+meta_id, current_meta_form).val();
	//meta_value = jQuery("input#custom_meta_value_"+meta_id, current_meta_form).val();
	returned_value = jQuery.ajax({
		type: "POST",
		url: "admin.php?ajax=true",
		data: "admin=true&remove_meta=true&meta_id="+meta_id+"",
		success: function(results) {
			if(results > 0) {
				jQuery("div#custom_meta_"+meta_id).remove();
			}
		}
	});
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
function showaddform() {
	jQuery('#blank_item').css('display', 'none');
	jQuery('#productform').css('display', 'none');
	jQuery('#additem').css('display', 'block');
	return false;
}
//used to add new form fields in the checkout setting page
function add_form_field(e) {
	var time = new Date(),
		new_element_number = time.getTime(),
		new_element_id = "form_id_"+new_element_number,
		new_element_contents = '<tr class="checkout_form_field" id="'+new_element_id+'">';
		
	new_element_contents += "<td class='drag'></td>";
	new_element_contents += "<td class='namecol'><input type='text' name='new_form_name["+new_element_number+"]' value='' /></td>\n\r";
	new_element_contents += "<td class='typecol'><select class='wpsc_checkout_selectboxes' name='new_form_type["+new_element_number+"]'>"+HTML_FORM_FIELD_TYPES+"</select></td>\n\r";
	new_element_contents += "<td class='typecol'><select name='new_form_unique_name["+new_element_number+"]'>"+HTML_FORM_FIELD_UNIQUE_NAMES+"</select></td>\n\r";
	new_element_contents += "<td class='mandatorycol' style='text-align: center;'><input type='checkbox' name='new_form_mandatory["+new_element_number+"]' value='1' /></td>\n\r";
	new_element_contents += "<td><a class='image_link' href='#' onclick='return remove_new_form_field(\""+new_element_id+"\");'><img src='" + WPSC_CORE_IMAGES_URL + "/trash.gif' alt='"+TXT_WPSC_DELETE+"' title='"+TXT_WPSC_DELETE+"' /></a></td>\n\r";
	new_element_contents += '</tr>';
	jQuery('#checkout_message').css('display', 'block');
	jQuery("#wpsc_checkout_list_body").append(new_element_contents);

	return false;
}



function remove_new_form_field(id) {
	element_count = document.getElementById("wpsc_checkout_list_body").childNodes.length;
	if(element_count > 1) {
		target_element = document.getElementById(id);
		document.getElementById("wpsc_checkout_list_body").removeChild(target_element);
	}
	return false;
}


function submit_change_country() {
	document.cart_options.submit();
//document.cart_options.submit();
}

function getcurrency(id) {
//ajax.post("index.php",gercurrency,"wpsc_admin_action=change_currency&currencyid="+id);
}
//delete checkout fields from checkout settings page
function remove_form_field(id,form_id) {
	var delete_variation_value=function(results) { }
	element_count = document.getElementById("wpsc_checkout_list_body").childNodes.length;
	if(element_count > 1) {
		ajax.post("index.php",delete_variation_value,"admin=true&ajax=true&remove_form_field=true&form_id="+form_id);
		target_element = document.getElementById(id);
		document.getElementById("wpsc_checkout_list_body").removeChild(target_element);
	}
	return false;
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
		document.getElementById(image_id).src =  WPSC_CORE_IMAGES_URL + '/icon_window_collapse.gif';
	} else {
		document.getElementById(id).style.display = 'none';
		document.getElementById(image_id).src =  WPSC_CORE_IMAGES_URL + '/icon_window_expand.gif';
	}
	return false;
}

function editinline_get_id(){
	id = jQuery('.inline-edit-row').attr('id');
	id = id.replace('edit-','');
	has_var = jQuery('#inline_'+id+'_has_var').val();
	if( has_var == '1'){
   		jQuery(".wpsc-cols").css('display', 'none');
		jQuery(".wpsc-cols:first").text('Quick Edit options are limited when editing products that have variations. You will need to edit the variations themselves.')
		.addClass('wpsc_var_description')
		.css('display','block');
	}else{
  		jQuery(".wpsc-cols").css('display', 'block');
	}
		

}