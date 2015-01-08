var someresults=function()  {
	document.getElementById('changenotice').innerHTML = "Thank you, your change has been saved";
}

var noresults=function()  {
// see nothing, know nothing, do nothing
}

if(typeof(select_min_height) == undefined) {
	var select_min_height = 0;
	var select_max_height = 200;
}

jQuery(document).ready(
	function() {
		//   jQuery('div.select_product_file').Resizable({
		//     minWidth: 300,
		//     minHeight: select_min_height,
		//     maxWidth: 300,
		//     maxHeight: select_max_height,
		//     handlers: {
		//       s: '.select_product_handle'
		//       }
		//     });

		jQuery("div.admin_product_name a.shorttag_toggle").toggle(
			function () {
				jQuery("div.admin_product_shorttags", jQuery(this).parent("div.admin_product_name")).css('display', 'block');
			},
			function () {
				//jQuery("div#admin_product_name a.shorttag_toggle").toggleClass('toggled');
				jQuery("div.admin_product_shorttags", jQuery(this).parent("div.admin_product_name")).css('display', 'none');
			}
			);
		enablebuttons();

	}
	);

function activate_resizable() {
//   jQuery('div.edit_select_product_file').Resizable({
//     minWidth: 300,
//     minHeight: select_min_height,
//     maxWidth: 300,
//     maxHeight: select_max_height,
//     handlers: {
//       s: '.edit_select_product_handle'
//       }
// 	});
}



jQuery(document).ready(function(){
	// 	  bind_shipping_rate_deletion();
	jQuery(function() {
		// set us up some mighty fine tabs for the options page

		if (typeof jQuery('#wpsc_options > ul#tabs').tabs != "undefined") {
			$tabs = jQuery('#wpsc_options > ul#tabs').tabs();
		}
		// 			current_tab = window.location.href.split('#');

		// this here code handles remembering what tab you were on
		jQuery('#wpsc_options > ul').bind('tabsselect', function(event, ui) {
			form_action = jQuery('#cart_options').attr('action').split('#');  //split at the #
			form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
			jQuery('#cart_options').attr('action', form_action); // stick it all back in the action attribute
		// 				var current_tab = $tabs.data('selected.tabs');
		// 				alert(current_tab);
		// 				if (current_tab == '3') {
		// 					form_action = jQuery('#shipping_options').attr('action').split('#');  //split at the #
		// 					form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
		//
		// 					jQuery('#shipping_options').attr('action', form_action); // stick it all back in the action attribute
		// 				}
		// 				if (current_tab == '4') {
		// 					form_action = jQuery('#gateway_options').attr('action').split('#');  //split at the #
		// 					form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
		// 					jQuery('#gateway_options').attr('action', form_action); // stick it all back in the action attribute
		// 				}
		});
		jQuery('#wpsc_options > ul').bind('tabsload', function(event, ui) {
			bind_shipping_rate_deletion();
			// 				form_action = jQuery('#cart_options').attr('action').split('#');  //split at the #
			// 				form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
			// 				jQuery('#cart_options').attr('action', form_action); // stick it all back in the action attribute
			var current_tab = $tabs.data('selected.tabs');
			if (current_tab == '3') {
				form_action = jQuery('#shipping_options').attr('action').split('#');  //split at the #
				form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
				jQuery('#shipping_options').attr('action', form_action); // stick it all back in the action attribute
			}
			if (current_tab == '4') {
				form_action = jQuery('#gateway_options_tbl').attr('action').split('#');  //split at the #
				form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
				jQuery('#gateway_options_tbl').attr('action', form_action); // stick it all back in the action attribute
			}
			if (current_tab == '5') {
				form_action = jQuery('#chekcout_options_tbl').attr('action').split('#');  //split at the #
				form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
				jQuery('#chekcout_options_tbl').attr('action', form_action); // stick it all back in the action attribute
			}
			if (current_tab == '6') {
				form_action = jQuery('#gold_cart_form').attr('action').split('#');  //split at the #
				form_action = form_action[0]+"#"+ui.panel.id; // get the first item, add the hash then our current tab ID
				jQuery('#gold_cart_form').attr('action', form_action); // stick it all back in the action attribute
			}

		});
	});
});


function categorylist(url) {
	self.location = url;
}

function submit_change_country() {
	document.cart_options.submit();
//document.cart_options.submit();
}

var getresults=function(results) {
	document.getElementById('formcontent').innerHTML = results;
	jQuery('form.edititem').css('display', 'block');
	jQuery('form.edititem').css('display', 'block');
	jQuery('#additem').css('display', 'none');
	jQuery('#productform').css('display', 'block');
	jQuery("#loadingindicator_span").css('visibility','hidden');
	enablebuttons();

	jQuery("#gallery_list").sortable({
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
			set = jQuery("#gallery_list").sortable('toArray');
			img_id = jQuery('#gallery_image_'+set[0]).parent('li').attr('id');

			jQuery('#gallery_image_'+set[0]).children('img.deleteButton').remove();
			jQuery('#gallery_image_'+set[0]).append("<a class='editButton'>Edit   <img src='" + WPSC_CORE_IMAGES_URL + "/pencil.png' alt='' /></a>");
			jQuery('#gallery_image_'+set[0]).parent('li').attr('id', 0);
			//for(i=1;i<set.length;i++) {
			//	jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
			//	jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='" + WPSC_CORE_IMAGES_URL + "cross.png'/>");
			//}

			for(i=1;i<set.length;i++) {
				jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
				jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='" + WPSC_CORE_IMAGES_URL + "/cross.png'/>");

				element_id = jQuery('#gallery_image_'+set[i]).parent('li').attr('id');
				if(element_id == 0) {
					jQuery('#gallery_image_'+set[i]).parent('li').attr('id', img_id);
				}
			}

			order = set.join(',');
			prodid = jQuery('#prodid').val();
			ajax.post("index.php",imageorderresults,"admin=true&ajax=true&prodid="+prodid+"&imageorder=true&order="+order);
		},
		'opacity':0.5
	});

	function imageorderresults(results) {
		eval(results);

		jQuery('#gallery_image_'+ser).append(output);

		enablebuttons();
	}

	jQuery("div.previewimage").hover(
		function () {
			jQuery(this).children('img.deleteButton').show();
			if(jQuery('#image_settings_box').css('display')!='block')
				jQuery(this).children('a.editButton').show();
		},
		function () {
			jQuery(this).children('img.deleteButton').hide();
			jQuery(this).children('a.editButton').hide();
		}
		);

	jQuery("a.closeimagesettings").click(
		function (e) {
			jQuery("div#image_settings_box").hide();
		}
		);

	jQuery("#table_rate_price").click(
		function() {
			if (this.checked) {
				jQuery("#table_rate").slideDown("fast");
			} else {
				jQuery("#table_rate").slideUp("fast");
			}
		}
		);

	jQuery(".add_level").click(
		function() {
			jQuery(this).parent().children('table').append('<tr><td><input type="text" size="10" value="" name="productmeta_values[table_rate_price][quantity][]"/> and above</td><td><input type="text" size="10" value="" name="productmeta_values[table_rate_price][table_price][]"/></td><td><img src="' + WPSC_CORE_IMAGES_URL + '/cross.png" class="remove_line"></td></tr>');
		}
		);


	jQuery("#add_label").click(
		function(){
			jQuery("#labels").append("<br><table><tr><td>"+TXT_WPSC_LABEL+" :</td><td><input type='text' name='productmeta_values[labels][]'></td></tr><tr><td>"+TXT_WPSC_LIFE_NUMBER+" :</td><td><input type='text' name='productmeta_values[life_number][]'></td></tr><tr><td>"+TXT_WPSC_ITEM_NUMBER+" :</td><td><input type='text' name='productmeta_values[item_number][]'></td></tr><tr><td>"+TXT_WPSC_PRODUCT_CODE+" :</td><td><input type='text' name='productmeta_values[product_code][]'></td></tr><tr><td>"+TXT_WPSC_PDF+" :</td><td><input type='file' name='productmeta_values[product_pdf][]'></td></tr></table>");
		}
		);

	jQuery(".remove_line").click(
		function() {
			jQuery(this).parent().parent('tr').remove();
		}
		);

	jQuery("div.admin_product_name a.shorttag_toggle").toggle(
		function () {
			jQuery("div.admin_product_shorttags", jQuery(this).parent("div.admin_product_name")).css('display', 'block');
		},
		function () {
			//jQuery("div#admin_product_name a.shorttag_toggle").toggleClass('toggled');
			jQuery("div.admin_product_shorttags", jQuery(this).parent("div.admin_product_name")).css('display', 'none');
		}
		);
	jQuery(".file_delete_button").click(
		function() {
			jQuery(this).parent().remove();
			file_hash = jQuery(this).siblings("input").val();
			ajax.post("index.php",noresults,"admin=true&ajax=true&del_file=true&del_file_hash="+file_hash);
		}
		);
	boxes = ["price_and_stock", "shipping", "variation", "advanced", "product_image", "product_download"];
	for (i=0;i<boxes.length;i++) {
		if ( ! jQuery('#'+boxes[i]+'-hide').is(':checked')){
			if (IS_WP27) {
				jQuery('#'+boxes[i]).hide();
			}
		}
	}
}

function filleditform(prodid)	{
	jQuery(document).ready(function(){
		ajax.post("index.php",getresults,"ajax=true&admin=true&prodid="+prodid);
		jQuery('.loadingimage').attr('src', jQuery(".loadingimage").attr('src'));
		jQuery('#loadingindicator_span').css('visibility','visible');
	});
}

function fillvariationform(variation_id) {
	ajax.post("index.php",getresults,"ajax=true&admin=true&variation_id="+variation_id);
	jQuery('.loadingimage').attr('src',  WPSC_CORE_IMAGES_URL + 'indicator.gif');
	jQuery('#loadingindicator_span').css('visibility','visible');
}

function showaddform() {
	document.getElementById('productform').style.display = 'none';
	document.getElementById('additem').style.display = 'block';
	return false;
}

function showadd_categorisation_form() {
	if(jQuery('div#add_categorisation').css('display') != 'block') {
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

function fillcategoryform(catid) {
	ajax.post("index.php",getresults,"ajax=true&admin=true&catid="+catid);
}

function fillbrandform(catid) {
	ajax.post("index.php",getresults,"ajax=true&admin=true&brandid="+catid);
}

var gercurrency=function(results) {
	document.getElementById('cslchar1').innerHTML = results;
	document.getElementById('cslchar2').innerHTML = results;
	document.getElementById('cslchar3').innerHTML = results;
	document.getElementById('cslchar4').innerHTML = results;
}



function country_list(id) {
	var country_list=function(results) {
		document.getElementById('options_region').innerHTML = results;
	}
	ajax.post("index.php",country_list,"ajax=true&get_country_tax=true&country_id="+id);
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

function update_preview_url(prodid) {
	image_height = document.getElementById("image_height").value;
	image_width = document.getElementById("image_width").value;
	if(((image_height > 0) && (image_height <= 1024)) && ((image_width > 0) && (image_width <= 1024))) {
		new_url = "index.php?productid="+prodid+"&height="+image_height+"&width="+image_width+"";
		document.getElementById("preview_link").setAttribute('href',new_url);
	} else {
		new_url = "index.php?productid="+prodid+"";
		document.getElementById("preview_link").setAttribute('href',new_url);
	}
	return false;
}


function add_variation_value(value_type) {
	container_id = value_type+"_variation_values";
	//alert(container_id);
	last_element_id = document.getElementById(container_id).lastChild.id;
	//   last_element_id = last_element_id.split("_");
	//   last_element_id = last_element_id.reverse();
	date = new Date;
	new_element_id = "variation_value_"+date.getTime();


	old_elements = document.getElementById(container_id).innerHTML;
	new_element_contents = "";
	if(value_type == "edit") {
		new_element_contents += "<input type='text' class='text' name='new_variation_values[]' value='' />";
	} else {
		new_element_contents += "<input type='text' class='text' name='variation_values[]' value='' />";
	}
	new_element_contents += " <a class='image_link' href='#' onclick='remove_variation_value_field(\""+new_element_id+"\")'><img src='" + WPSC_CORE_IMAGES_URL + "trash.gif' alt='"+TXT_WPSC_DELETE+"' title='"+TXT_WPSC_DELETE+"' /></a><br />";
	//new_element_contents += "</span>";

	new_element = document.createElement('span');
	new_element.id = new_element_id;

	document.getElementById(container_id).appendChild(new_element);
	document.getElementById(new_element_id).innerHTML = new_element_contents;
	return false;
}

function remove_variation_value(element,variation_value) {
	var delete_variation_value=function(results)
	{
	}

	element_count = jQuery("div#edit_variation_values span").size();
	if(element_count > 1) {
		ajax.post("index.php",delete_variation_value,"admin=true&ajax=true&remove_variation_value=true&variation_value_id="+variation_value);
		jQuery(element).parent("span.variation_value").remove();
	}
	return false;
}




function checkimageresize() {
	document.getElementById('image_resize2').checked = true;
}

function submit_status_form(id) {
	document.getElementById(id).submit();
}

// pe.{
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


// }.pe

function toggle_display_options(state) {
	switch(state) {
		case 'list':
			document.getElementById('grid_view_options').style.display = 'none';
			document.getElementById('list_view_options').style.display = 'block';
			break;

		case 'grid':
			document.getElementById('list_view_options').style.display = 'none';
			document.getElementById('grid_view_options').style.display = 'block';
			break;

		default:
			document.getElementById('list_view_options').style.display = 'none';
			document.getElementById('grid_view_options').style.display = 'none';
			break;
	}
}

function log_submitform(id) {
	value1 = document.getElementById(id);
	if (ajax.serialize(value1).search(/value=3/)!=-1) {
		document.getElementById("track_id_"+id).style.display="block";
	} else {
		document.getElementById("track_id_"+id).style.display="none";
	}
	var get_log_results=function(results) {
		eval(results);
	}
	frm = document.getElementById(id);
	ajax.post("index.php?admin=true&ajax=true&log_state=true",get_log_results,ajax.serialize(frm));
	return false;
}

function save_tracking_id(id) {
	value1 = document.getElementById('tracking_id_'+id).value;
	value1 ="id="+id +"&value="+value1;
	ajax.post("index.php?admin=true&ajax=true&save_tracking_id=true",noresults,value1);
	return false;
}

var select_min_height = 75;
var select_max_height = 50;
/*
//ToolTip JavaScript
jQuery('img').Tooltip(
	{
		className: 'inputsTooltip',
		position: 'mouse',
		delay: 200
	}
);
*/
jQuery(window).load( function () {

	jQuery('a.closeEl').bind('click', toggleContent);
	/*
 	jQuery('div.groupWrapper').sortable( {
			accept: 'groupItem',
 			helperclass: 'sortHelper',
 			activeclass : 	'sortableactive',
 			hoverclass : 	'sortablehover',
 			handle: 'div.itemHeader',
 			tolerance: 'pointer',
 			onStart : function() {
 				jQuery.iAutoscroller.start(this, document.getElementsByTagName('body'));
 			},
 			onStop : function() {
				jQuery.iAutoscroller.stop();
 			},
 			update : function(e,ui) {
 				serial = jQuery('div.groupWrapper').sortable('toArray');
 				category_id = jQuery("input#item_list_category_id").val();

 				ajax.post("index.php", noresults, "admin=true&ajax=true&changeorder=true&category_id="+category_id+"&sort1="+serial);
 			}
 		}
 	);
*/

	jQuery('a#close_news_box').click( function () {
		jQuery('div.wpsc_news').css( 'display', 'none' );
		ajax.post("index.php", noresults, "ajax=true&admin=true&hide_ecom_dashboard=true");
		return false;
	});
});
var toggleContent = function(e)
{
	var targetContent = jQuery('div.itemContent', this.parentNode.parentNode);
	if (targetContent.css('display') == 'none') {
		targetContent.slideDown(300);
		jQuery(this).html('[-]');
	} else {
		targetContent.slideUp(300);
		jQuery(this).html('[+]');
	}
	return false;
};


function hideelement1(id, item_value)
{
	//alert(value);
	if(item_value == 5) {
		jQuery(document.getElementById(id)).css('display', 'block');
	} else {
		jQuery(document.getElementById(id)).css('display', 'none');
	}
}


function suspendsubs(user_id)
{
	var comm =jQuery("#suspend_subs"+user_id).attr("checked");
	//alert(comm);
	if (comm == true){
		ajax.post("index.php",noresults,"admin=true&ajax=true&log_state=true&suspend=true&value=1&id="+user_id);
	} else {
		ajax.post("index.php",noresults,"admin=true&ajax=true&log_state=true&suspend=true&value=2&id="+user_id);
	}
	return false;
}

function delete_extra_preview(preview_name, prodid) {
	var preview_name_results=function(results) {
		filleditform(prodid);
	}
	ajax.post("index.php",preview_name_results,"ajax=true&admin=true&prodid="+prodid+"&preview_name="+preview_name);
}

function shipwire_sync() {
	ajax.post("index.php",noresults,"ajax=true&shipwire_sync=ture");
}

function shipwire_tracking() {
	ajax.post("index.php",noresults,"ajax=true&shipwire_tracking=ture");
}

function display_settings_button() {
	jQuery("#settings_button").slideToggle(200);
//document.getElementById("settings_button").style.display='block';
}

function submittogoogle(id){
	value1=document.getElementById("google_command_list_"+id).value;
	value2=document.getElementById("partial_amount_"+id).value;
	reason=document.getElementById("cancel_reason_"+id).value;
	comment=document.getElementById("cancel_comment_"+id).value;
	message=document.getElementById("message_to_buyer_message_"+id).value;
	document.getElementById("google_command_indicator").style.display='inline';
	ajax.post("index.php",submittogoogleresults,"ajax=true&submittogoogle=true&message="+message+"&value="+value1+"&amount="+value2+"&comment="+comment+"&reason="+reason+"&id="+id);
	return true;
}

var submittogoogleresults=function (results) {
	window.location.reload(true);
}

function display_partial_box(id){
	value1=document.getElementById("google_command_list_"+id).value;
	if ((value1=='Refund') || (value1=='Charge')){
		document.getElementById("google_partial_radio_"+id).style.display='inline';
		if (value1=='Refund'){
			document.getElementById("google_cancel_"+id).style.display='block';
			document.getElementById("cancel_reason_"+id).style.display='inline';
			document.getElementById("cancel_div_comment_"+id).style.display='none';
		}
	}else if ((value1=='Cancel')||(value1=='Refund')) {
		document.getElementById("google_cancel_"+id).style.display='block';
		document.getElementById("cancel_reason_"+id).style.display='inline';
	}else if (value1=='Send Message') {
		document.getElementById("message_to_buyer_"+id).style.display='block';
	} else {
		document.getElementById("cancel_div_comment_"+id).style.display='none';
		document.getElementById("google_cancel_"+id).style.display='none';
		document.getElementById("cancel_reason_"+id).style.display='none';
		document.getElementById("message_to_buyer_"+id).style.display='none';
		document.getElementById("google_partial_radio_"+id).style.display='none';
		document.getElementById("partial_amount_"+id).style.display='none';
	}
}

function add_more_meta(e) {
	current_meta_forms = jQuery(e).parent().children("div.product_custom_meta:last");  // grab the form container
	new_meta_forms = current_meta_forms.clone(true); // clone the form container
	jQuery("label input", new_meta_forms).val(''); // reset all contained forms to empty
	current_meta_forms.after(new_meta_forms);  // append it after the container of the clicked element
	return false;
}

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


function wpsc_save_postboxes_state(page, container) {
	var closed = jQuery(container+' .postbox').filter('.closed').map(function() {
		return this.id;
	}).get().join(',');
	jQuery.post(postboxL10n.requestFile, {
		action: 'closed-postboxes',
		closed: closed,
		closedpostboxesnonce: jQuery('#closedpostboxesnonce').val(),
		page: page
	});
}

jQuery(document).ready(function(){

	jQuery('.deleteproducts > button').click(
		function () {
			var ids='0';
			jQuery('.deletecheckbox:checked').each(
				function () {
					ids += ","+jQuery(this).val();
				}
				);
			var r=confirm("Please confirm deletion");
			if (r==true) {
				ajax.post("index.php",reloadresults,"admin=true&ajax=true&del_prod=true&del_prod_id="+ids);
			}
		}
		);
	jQuery('#selectall').click(
		function () {
			if (this.checked) {
				jQuery('.deletecheckbox').each(function(){
					this.checked = true;
				});
			} else {
				jQuery('.deletecheckbox').each(function(){
					this.checked = false;
				});
			}
		}
		);

	if (typeof jQuery('.pickdate').datepicker != "undefined") {
		jQuery('.pickdate').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	}
	filesizeLimit = 5120000;

	// 	alert('test 1');
	if (typeof SWFUpload != "undefined") {
		var swfu = new SWFUpload({
			flash_url : WPSC_CORE_JS_URL + '/swfupload.swf',
			upload_url: base_url+'/?action=wpsc_add_image',
			button_placeholder_id : "spanButtonPlaceholder",
			button_width: 103,
			button_height: 24,
			button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
			button_cursor: SWFUpload.CURSOR.HAND,
			post_params: {
				"prodid" : 0
			},
			file_queue_limit : 1,
			file_size_limit : filesizeLimit+'b',
			file_types : "*.jpg;*.jpeg;*.png;*.gif;*.JPG;*.JPEG;*.PNG;*.GIF",
			file_types_description : "Web-compatible Image Files",
			file_upload_limit : filesizeLimit,
			custom_settings : {
				targetHolder : false,
				progressBar : false,
				sorting : false
			},
			debug: false,

			file_queued_handler : imageFileQueued,
			file_queue_error_handler : imageFileQueueError,
			file_dialog_complete_handler : imageFileDialogComplete,
			upload_start_handler : startImageUpload,
			upload_progress_handler : imageUploadProgress,
			upload_error_handler : imageUploadError,
			upload_success_handler : imageUploadSuccess,
			upload_complete_handler : imageUploadComplete,
			queue_complete_handler : imageQueueComplete
		});
	}
	jQuery("#add-product-image").click(function(){
		swfu.selectFiles();
	});
});

function addlayer(){
	jQuery("tr.addlayer").before("<tr class='rate_row'><td><i style='color:grey'>"+TXT_WPSC_IF_PRICE_IS+"</i><input type='text' name='layer[]' size='10'> <i style='color:grey'>"+TXT_WPSC_AND_ABOVE+"</i></td><td><input type='text' name='shipping[]' size='10'>&nbsp;&nbsp;<a href='#' class='delete_button nosubmit' >"+TXT_WPSC_DELETE+"</a></td></tr>");
	bind_shipping_rate_deletion();
}

function addweightlayer(){
	jQuery("tr.addlayer").before("<tr class='rate_row'><td><i style='color:grey'>"+TXT_WPSC_IF_WEIGHT_IS+"</i><input type='text' name='weight_layer[]' size='10'> <i style='color:grey'>"+TXT_WPSC_AND_ABOVE+"</i></td><td><input type='text' name='weight_shipping[]' size='10'>&nbsp;&nbsp;<a href='#' class='delete_button nosubmit' >"+TXT_WPSC_DELETE+"</a></td></tr>");
	bind_shipping_rate_deletion();
}

function removelayer() {
	this.parent.parent.innerHTML='';
}

/**
 * SWFUpload Image Uploading events
 **/

function imageFileQueued (file) {

}

function imageFileQueueError (file, error, message) {
	if (error == SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
		alert("You selected too many files to upload at one time. " + (message === 0 ? "You have reached the upload limit." : "You may upload " + (message > 1 ? "up to " + message + " files." : "only one file.")));
		return;
	}

}

function imageFileDialogComplete (selected, queued) {
	try {
		this.startUpload();
	} catch (ex) {
		this.debug(ex);
	}
}

function startImageUpload (file) {
	//   alert('start '+jQuery("div#swfupload_img_indicator").css('display'));
	jQuery("div#swfupload_img_indicator").css('display', 'block');
	var cell = jQuery('<li></li>').appendTo(jQuery('#gallery_list'));
	var sorting = jQuery('<input type="hidden" name="images[]" value="" />').appendTo(cell);
	var progress = jQuery('<div class="progress"></div>').appendTo(cell);
	var bar = jQuery('<div class="bar"></div>').appendTo(progress);
	var art = jQuery('<div class="gloss"></div>').appendTo(progress);
	this.targetHolder = cell;
	this.progressBar = bar;
	this.sorting = sorting;
	return true;
}

function imageUploadProgress (file, loaded, total) {
	var progress = Math.ceil((loaded/total)*76);
	jQuery(this.progressBar).animate({
		'width':progress+'px'
		},100);
}

function imageUploadError (file, error, message) {
	console.log(error+": "+message);
}

function imageUploadSuccess (file, results) {
	//Don't delete, initiate id is neccesary.
	var id = null;
	var pid = null;

	jQuery("span.swfupload_loadingindicator").css('visibility', 'hidden');
	eval(results);
	// 		jQuery(this).css('border', '1px solid red');
	if(pid >= 1) {
		context = jQuery("div#productform");
	} else {
		context = jQuery("div#additem");
	}

	if (id == null ) {
		if(replacement_src != null) {
			jQuery("li.first div.previewimage a.thickbox", context).attr('href', replacement_src);
			jQuery("li.first div.previewimage a.thickbox img.previewimage", context).attr('src', replacement_src);
		} else {
			if (jQuery('#gold_present', context).val() != '1') {
				jQuery('#add-product-image', context).remove();
			}
			jQuery(this.sorting).attr({
				'value':src
			});
			var img = jQuery('<div class="previewimage" id="'+id+'"><a href="'+WPSC_IMAGE_URL+src+'" rel="product_extra_image_'+id+'" class="thickbox"><img src="'+WPSC_IMAGE_URL+src+'" width="60" height="60" class="previewimage" /></a></div>').appendTo(this.targetHolder).hide();
			set = jQuery("#gallery_list", context).sortable('toArray');

			jQuery('#gallery_image_0', context).append("<a class='editButton'>Edit   <img src='" + WPSC_IMAGE_URL + "pencil.png'/></a>");
			jQuery('#gallery_image_0', context).parent('li').addClass('first');
			jQuery('#gallery_image_0', context).parent('li').attr('id', 0);
			jQuery('#gallery_image_0 img.deleteButton', context).remove();
			enablebuttons();
		}
	} else {
		//jQuery(this.targetHolder).attr({'id':'image-'+src});
		jQuery(this.targetHolder).attr({
			'id':id
		});
		div_id = 'gallery_image_'+id;
		jQuery(this.targetHolder).html('');
		var img = jQuery('<div class="previewimage" id="'+div_id+'"><input type="hidden" name="images[]" value="'+src+'"><a href="'+WPSC_IMAGE_URL+src+'" rel="product_extra_image_'+id+'" class="thickbox"><img src="'+WPSC_IMAGE_URL+src+'" width="60" height="60" class="previewimage" /></a></div>').appendTo(this.targetHolder).hide();

		jQuery('#gallery_image_0', context).append("<a class='editButton'>Edit   <img src='"+WPSC_CORE_IMAGES_URL+"/pencil.png'/></a>");
		jQuery('#gallery_image_0', context).parent('li').addClass('first');
		jQuery('#gallery_image_0', context).parent('li').attr('id', 0);
		jQuery('#gallery_image_0 img.deleteButton', context).remove();


		if (jQuery('#gallery_list li', context).size() > 1) {
			jQuery('#gallery_list', context).sortable('refresh');
		} else {
			jQuery('#gallery_list', context).sortable();
		}
		set = jQuery("#gallery_list", context).sortable('toArray');
		order = set.join(',');
		prodid = jQuery('#prodid', context).val();

		if(prodid == null) {
			prodid = 0;
		}

		function imageorderresults(results) {
			eval(results);
			jQuery('#gallery_image_'+ser).append(output);
			enablebuttons();
		}

		ajax.post("index.php",imageorderresults,"admin=true&ajax=true&prodid="+prodid+"&imageorder=true&order="+order+"");


		enablebuttons();

	}
	jQuery(this.progressBar).animate({
		'width':'76px'
	},250,function () {
		jQuery(this).parent().fadeOut(500,function() {
			jQuery(this).remove();
			jQuery(img).fadeIn('500');
			jQuery(img).append('<img class="deleteButton" src="'+WPSC_CORE_IMAGES_URL+'/cross.png" alt="-" style="display: none;"/>');
			enablebuttons()
		//enableDeleteButton(deleteButton);
		});
	});
}

function imageUploadComplete (file) {

	jQuery("div#swfupload_img_indicator").css('display', 'none');
	if (jQuery('#gallery_list li').size() > 1)
		jQuery('#gallery_list').sortable('refresh');
	else
		jQuery('#gallery_list').sortable();
}

function imageQueueComplete (uploads) {

}

function enablebuttons(){
	jQuery("img.deleteButton").click(
		function(){
			var r=confirm("Please confirm deletion");
			if (r==true) {
				img_id = jQuery(this).parent().parent('li').attr('id');
				jQuery(this).parent().parent('li').remove();
				ajax.post("index.php",noresults,"admin=true&ajax=true&del_img=true&del_img_id="+img_id);
			}
		}
		);

	jQuery("a.delete_primary_image").click(
		function(){
			var r=confirm("Please confirm deletion");
			if (r==true) {
				img_id = jQuery(this).parents('li.first').attr('id');
				//ajax.post("index.php",noresults,"ajax=true&del_img=true&del_img_id="+img_id);
				jQuery(this).parents('li.first').remove();



				set = jQuery("#gallery_list").sortable('toArray');
				jQuery('#gallery_image_'+set[0]).children('img.deleteButton').remove();
				jQuery('#gallery_image_'+set[0]).append("<a class='editButton'>Edit   <img src='"+WPSC_CORE_IMAGES_URL+"/pencil.png'/></a>");
				jQuery('#gallery_image_'+set[0]).parent('li').addClass('first');
				jQuery('#gallery_image_'+set[0]).parent('li').attr('id', 0);
				for(i=1;i<set.length;i++) {
					jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
					jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='"+WPSC_CORE_IMAGES_URL+"/cross.png'/>");

					if(element_id == 0) {
						jQuery('#gallery_image_'+set[i]).parent('li').attr('id', img_id);
					}
				}
				order = set.join(',');
				prodid = jQuery('#prodid').val();
				ajax.post("index.php",imageorderresults,"admin=true&ajax=true&prodid="+prodid+"&imageorder=true&order="+order+"&delete_primary=true");

				jQuery(this).parents('li.first').attr('id', '0');
			}
			return false;
		}
		);

	jQuery("div.previewimage").hover(
		function () {
			jQuery(this).children('img.deleteButton').show();
			if(jQuery('#image_settings_box').css('display')!='block')
				jQuery(this).children('a.editButton').show();
		},
		function () {
			jQuery(this).children('img.deleteButton').hide();
			jQuery(this).children('a.editButton').hide();
		}
		);

	jQuery("a.editButton").click(
		function(){
			jQuery(this).hide();
			jQuery('#image_settings_box').show('fast');
		}
		);

	jQuery("a.closeimagesettings").click(
		function (e) {
			jQuery("div#image_settings_box").hide();
		}
		);

	function imageorderresults(results) {
		eval(results);
		jQuery('#gallery_image_'+ser).append(output);
		enablebuttons();
	}

	jQuery("input.limited_stock_checkbox").click( function ()  {
		parent_form = jQuery(this).parents('form');
		if(jQuery(this).is(':checked')) {
			jQuery("div.edit_stock",parent_form).show();
			jQuery("th.stock, td.stock", parent_form).show();
			jQuery(".stock_limit_quantity", parent_form).show();
		} else {
			jQuery("div.edit_stock", parent_form).hide();
			jQuery("th.stock, td.stock", parent_form).hide();
			jQuery(".stock_limit_quantity", parent_form).hide();
		}
	});
}

function reloadresults(){
	window.location = window.location.href;
}

jQuery(document).ready(function(){
	jQuery(".wpsc-row-actions").parent().parent("tr").mouseover(
		function() {
			jQuery(this).children("td").children(".wpsc-row-actions").css("visibility", "visible");
		}
		).mouseout(
		function() {
			jQuery(this).children("td").children(".wpsc-row-actions").css("visibility", "hidden");
		}
		);
	/*
	jQuery(".wpsc-shipping-actions").hide();
	jQuery("#wpsc_shipping_options").hover(
		function() {
			alert('hovering');
			jQuery(this).children(".wpsc-shipping-actions").css("visibility", "visible");
		}
	);
*//*
.mouseout(
		function() {
			jQuery(this).children(".wpsc-shipping-actions").css("visibility", "hidden");
		}
	);
*/

	jQuery("#table_rate_price").click(
		function() {
			if (this.checked) {
				jQuery("#table_rate").slideDown("fast");
			} else {
				jQuery("#table_rate").slideUp("fast");
			}
		}
		);
	jQuery("#add_label").click(
		function(){
			jQuery("#labels").append("<br><table><tr><td>"+TXT_WPSC_LABEL+" :</td><td><input type='text' name='productmeta_values[labels][]'></td></tr><tr><td>"+TXT_WPSC_LABEL_DESC+" :</td><td><textarea name='productmeta_values[labels_desc][]'></textarea></td></tr><tr><td>"+TXT_WPSC_LIFE_NUMBER+" :</td><td><input type='text' name='productmeta_values[life_number][]'></td></tr><tr><td>"+TXT_WPSC_ITEM_NUMBER+" :</td><td><input type='text' name='productmeta_values[item_number][]'></td></tr><tr><td>"+TXT_WPSC_PRODUCT_CODE+" :</td><td><input type='text' name='productmeta_values[product_code][]'></td></tr><tr><td>"+TXT_WPSC_PDF+" :</td><td><input type='file' name='productmeta_values[product_pdf][]'></td></tr></table>");
		}
		);
	jQuery(".add_level").click(
		function() {
			added = jQuery(this).parent().children('table').append('<tr><td><input type="text" size="10" value="" name="productmeta_values[table_rate_price][quantity][]"/> and above</td><td><input type="text" size="10" value="" name="productmeta_values[table_rate_price][table_price][]"/></td></tr>');
		}
		);

	jQuery(".file_delete_button").click(
		function() {
			jQuery(this).parent().remove();
			file_hash = jQuery(this).siblings("input").val();
			ajax.post("index.php",noresults,"admin=true&ajax=true&del_file=true&del_file_hash="+file_hash);
		}
		);

	jQuery("table#itemlist .pricedisplay").each(
		function () {
			jQuery(this).attr("id",jQuery(this).parent().attr('id'));
		}
		);

	jQuery("#submit_category_select").click(
		function() {
			new_url = jQuery("#category_select").children("option:selected").val();
			window.location = new_url;
		}
		);
});



function wpsc_upload_switcher(target_state) {
	switch(target_state) {
		case 'flash':
			jQuery("table.browser-image-uploader").css("display","none");
			jQuery("table.flash-image-uploader").css("display","block");
			ajax.post("index.php",noresults,"admin=true&ajax=true&save_image_upload_state=true&image_upload_state=1");
			break;

		case 'browser':
			jQuery("table.flash-image-uploader").css("display","none");
			jQuery("table.browser-image-uploader").css("display","block");
			ajax.post("index.php",noresults,"admin=true&ajax=true&save_image_upload_state=true&image_upload_state=0");
			break;
	}
}



function open_variation_settings(element_id) {
	jQuery("tr#"+element_id+" td div.variation_settings").toggle();
	return false;
}
