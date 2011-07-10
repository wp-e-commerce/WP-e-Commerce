/**
*  This is a nearly exact copy of the corresponding wordpress file, we needed to copy and modify it for our use of swfupoader as the wordpress handler code is specific to posts
*/ 
// define a global variable for swfupload here so that we can later do things to it.
var swfu = null;


jQuery().ajaxComplete(function(event,  XMLHttpRequest, ajaxOptions) {
	// nonces are only regenerated on autosaving when ther product ID is created/changed
	// we only want to edit the swfuploader parameters when that happens
	if(/autosave-generate-nonces/.test(ajaxOptions.data)) {
		window.swfu.removePostParam('product_id');
		window.swfu.addPostParam('product_id', parseInt(jQuery('#post_ID').val()));
	}
	//console.log(jQuery('#post_ID').val());		
});


function wpsc_fileDialogStart() {
	jQuery("#media-upload-error").empty();
}

// progress and success handlers for media multi uploads
function wpsc_fileQueued(fileObj) {
	// Create a progress bar containing the filename
	jQuery('#media-items').append('<div id="media-item-' + fileObj.id + '" ><div class="progress"><div class="bar"></div></div></div>');
	// Display the progress div
	jQuery('#media-item-' + fileObj.id + ' .progress').show();

	// Disable the submit button
	//jQuery('#insert-gallery').attr('disabled', 'disabled');
}

function wpsc_uploadStart(fileObj) {return true; }

function wpsc_uploadProgress(fileObj, bytesDone, bytesTotal) {
	// Lengthen the progress bar
	jQuery('#media-item-' + fileObj.id + ' .bar').width(500*bytesDone/bytesTotal);

	if ( bytesDone == bytesTotal ) {
		jQuery('#media-item-' + fileObj.id + ' .bar').html('<strong class="crunching"></strong>');
	}
}

function wpsc_prepareMediaItem(fileObj, serverData) {
	// Move the progress bar to 100%
	jQuery('#media-item-' + fileObj.id + ' .bar').remove();
	jQuery('#media-item-' + fileObj.id + ' .progress').hide();

	var f = ( typeof shortform == 'undefined' ) ? 1 : 2;
	// Old style: Append the HTML returned by the server -- thumbnail and form inputs
	if ( isNaN(serverData) || !serverData ) {
		jQuery('#media-item-' + fileObj.id).append(serverData);
		prepareMediaItemInit(fileObj);
	}
	// New style: server data is just the attachment ID, fetch the thumbnail and form html from the server
	else {
		jQuery('#media-item-' + fileObj.id).load('async-upload.php', {attachment_id:serverData, fetch:f}, function(){prepareMediaItemInit(fileObj);updateMediaForm()});
	}
}
		
function wpsc_prepareMediaItemInit(fileObj) {

	// Clone the thumbnail as a "pinkynail" -- a tiny image to the left of the filename
	jQuery('#media-item-' + fileObj.id + ' .thumbnail').clone().attr('class', 'pinkynail toggle').prependTo('#media-item-' + fileObj.id);

	// Replace the original filename with the new (unique) one assigned during upload
	jQuery('#media-item-' + fileObj.id + ' .filename.original').replaceWith(jQuery('#media-item-' + fileObj.id + ' .filename.new'));

	// Also bind toggle to the links
	jQuery('#media-item-' + fileObj.id + ' a.toggle').bind('click', function(){jQuery(this).siblings('.slidetoggle').slideToggle(150, function(){var o=jQuery(this).offset();window.scrollTo(0,o.top-36);});jQuery(this).parent().eq(0).children('.toggle').toggle();jQuery(this).siblings('a.toggle').focus();return false;});

	// Bind AJAX to the new Delete button
	jQuery('#media-item-' + fileObj.id + ' a.delete').bind('click',function(){
		// Tell the server to delete it. TODO: handle exceptions
		jQuery.ajax({url:'admin-ajax.php',type:'post',success:deleteSuccess,error:deleteError,id:fileObj.id,data:{
			id : this.id.replace(/[^0-9]/g,''),
			action : 'delete-post',
			_ajax_nonce : this.href.replace(/^.*wpnonce=/,'')}
			});
		return false;
	});

	// Open this item if it says to start open (e.g. to display an error)
	jQuery('#media-item-' + fileObj.id + '.startopen')
		.removeClass('startopen')
		.slideToggle(500)
		.parent().eq(0).children('.toggle').toggle();
}

function wpsc_itemAjaxError(id, html) {
	var error = jQuery('#media-item-error' + id);

	error.html('<div class="file-error"><button type="button" id="dismiss-'+id+'" class="button dismiss">'+swfuploadL10n.dismiss+'</button>'+html+'</div>');
	jQuery('#dismiss-'+id).click(function(){jQuery(this).parents('.file-error').slideUp(200, function(){jQuery(this).empty();})});
}

function wpsc_deleteSuccess(data, textStatus) {
	if ( data == '-1' )
		return itemAjaxError(this.id, 'You do not have permission. Has your session expired?');
	if ( data == '0' )
		return itemAjaxError(this.id, 'Could not be deleted. Has it been deleted already?');

	var item = jQuery('#media-item-' + this.id);

	// Decrement the counters.
	if ( type = jQuery('#type-of-' + this.id).val() )
		jQuery('#' + type + '-counter').text(jQuery('#' + type + '-counter').text()-1);
	if ( jQuery('.type-form #media-items>*').length == 1 && jQuery('#media-items .hidden').length > 0 ) {
		jQuery('.toggle').toggle();
		jQuery('.slidetoggle').slideUp(200).siblings().removeClass('hidden');
	}

	// Vanish it.
	jQuery('#media-item-' + this.id + ' .filename:empty').remove();
	jQuery('#media-item-' + this.id + ' .filename').append(' <span class="file-error">'+swfuploadL10n.deleted+'</span>').siblings('a.toggle').remove();
	jQuery('#media-item-' + this.id).children('.describe').css({backgroundColor:'#fff'}).end()
			.animate({backgroundColor:'#ffc0c0'}, {queue:false,duration:50})
			.animate({minHeight:0,height:36}, 400, null, function(){jQuery(this).children('.describe').remove()})
			.animate({backgroundColor:'#fff'}, 400)
			.animate({height:0}, 800, null, function(){jQuery(this).remove();updateMediaForm();});

	return;
}

function wpsc_deleteError(X, textStatus, errorThrown) {
	// TODO
}

function wpsc_updateMediaForm() {
	storeState();
	// Just one file, no need for collapsible part
	if ( jQuery('.type-form #media-items>*').length == 1 ) {
		jQuery('#media-items .slidetoggle').slideDown(500).parent().eq(0).children('.toggle').toggle();
		jQuery('.type-form .slidetoggle').siblings().addClass('hidden');
	}

	// Only show Save buttons when there is at least one file.
	if ( jQuery('#media-items>*').not('.media-blank').length > 0 )
		jQuery('.savebutton').show();
	else
		jQuery('.savebutton').hide();

	// Only show Gallery button when there are at least two files.
	if ( jQuery('#media-items>*').length > 1 )
		jQuery('.insert-gallery').show();
	else
		jQuery('.insert-gallery').hide();
}

function wpsc_uploadSuccess(fileObj, serverData) {
	// if async-upload returned an error message, place it in the media item div and return
	if ( serverData.match('media-upload-error') ) {
		jQuery('#media-item-' + fileObj.id).html(serverData);
		return;
	}
	//console.log(fileObj);
	//console.log(serverData);
  eval(serverData);
	if(upload_status == 1 ) {
		output_html = "";
		output_html +="<li class='gallery_image' id='product_image_"+image_id+"'>\n";
		output_html += "	<input type='hidden' value='"+image_id+"' name='gallery_image_id[]' class='image-id'/>\n";
		output_html += "	<div id='gallery_image_"+image_id+"' class='previewimage'>\n";
		output_html += "		<a class='thickbox' rel='product_extra_image_"+image_id+"' href='admin.php?wpsc_admin_action=crop_image&amp;imagename="+image_src+"&amp;imgheight=480&amp;imgwidth=600&amp;product_id=103&amp;width=640&amp;height=342' id='extra_preview_link_"+image_id+"'>\n";
		output_html += "		<img title='Preview' alt='Preview' src='"+image_src+"' class='previewimage'/>\n";
		output_html += "		</a>\n";
		output_html += "	<img src='" + WPSC_CORE_IMAGES_URL + "/cross.png' class='deleteButton' alt='-' style='display: none;'/>\n";
		output_html += "	</div>\n";
		output_html += "</li>\n";

		image_count = jQuery("ul#gallery_list li.gallery_image div a img.previewimage").size();
		if(image_count < 1) {
		  replace_existing = 1;
		}
		//console.log(jQuery("ul#gallery_list li.gallery_image div a img.previewimage"));
		//console.log(image_count);
		
 		if(replace_existing == 1) {
			jQuery("ul#gallery_list").html(output_html);
			
			input_set = jQuery.makeArray(jQuery("#gallery_list li:not(.ui-sortable-helper) input.image-id"));
			set = new Array();
			for( var i in input_set) {
				set[i] = jQuery(input_set[i]).val();
			}
			//console.log(set);

			img_id = jQuery('#gallery_image_'+set[0]).parent('li').attr('id');

			jQuery('#gallery_image_'+set[0]).children('img.deleteButton').remove();
			jQuery('#gallery_image_'+set[0]).append("<a class='editButton'>Edit   <img src='" + WPSC_CORE_IMAGES_URL + "/pencil.png' alt ='' /></a>");
// 			jQuery('#gallery_image_'+set[0]).parent('li').attr('id', 0);

			for(i=1;i<set.length;i++) {
				jQuery('#gallery_image_'+set[i]).children('a.editButton').remove();
				jQuery('#gallery_image_'+set[i]).append("<img alt='-' class='deleteButton' src='" + WPSC_CORE_IMAGES_URL + "/cross.png'/>");

				element_id = jQuery('#gallery_image_'+set[i]).parent('li').attr('id');
				if(element_id == 0) {
// 					jQuery('#gallery_image_'+set[i]).parent('li').attr('id', img_id);
				}
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
 		} else {
 			jQuery("ul#gallery_list").append(output_html);
 			///jQuery("#gallery_list").trigger( 'update' );
 		}
	}

		//jQuery('#media-item-' + fileObj.id + ' .progress').show();
 		//window.setInterval(function() {
		jQuery("#media-item-" + fileObj.id + "").fadeOut("normal");
 		//}, 5000);

	//prepareMediaItem(fileObj, serverData);
	//updateMediaForm();

}

function wpsc_uploadComplete(fileObj) {
	// If no more uploads queued, enable the submit button
	if ( swfu.getStats().files_queued == 0 )
		jQuery('#insert-gallery').attr('disabled', '');
}


// wp-specific error handlers

// generic message
function wpsc_wpQueueError(message) {
	jQuery('#media-upload-error').show().text(message);
}

// file-specific message
function wpsc_wpFileError(fileObj, message) {
	jQuery('#media-item-' + fileObj.id + ' .filename').after('<div class="file-error"><button type="button" id="dismiss-' + fileObj.id + '" class="button dismiss">'+swfuploadL10n.dismiss+'</button>'+message+'</div>').siblings('.toggle').remove();
	jQuery('#dismiss-' + fileObj.id).click(function(){jQuery(this).parents('.media-item').slideUp(200, function(){jQuery(this).remove();})});
}

function wpsc_fileQueueError(fileObj, error_code, message)  {
	// Handle this error separately because we don't want to create a FileProgress element for it.
	if ( error_code == SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED ) {
		wpQueueError(swfuploadL10n.queue_limit_exceeded);
	}
	else if ( error_code == SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT ) {
		fileQueued(fileObj);
		wpFileError(fileObj, swfuploadL10n.file_exceeds_size_limit);
	}
	else if ( error_code == SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE ) {
		fileQueued(fileObj);
		wpFileError(fileObj, swfuploadL10n.zero_byte_file);
	}
	else if ( error_code == SWFUpload.QUEUE_ERROR.INVALID_FILETYPE ) {
		fileQueued(fileObj);
		wpFileError(fileObj, swfuploadL10n.invalid_filetype);
	}
	else {
		wpQueueError(swfuploadL10n.default_error);
	}
}

function wpsc_fileDialogComplete(num_files_queued) {
	try {
		if (num_files_queued > 0) {
			this.startUpload();
		}
	} catch (ex) {
		this.debug(ex);
	}
}

function wpsc_swfuploadPreLoad() {
	var swfupload_element = jQuery('#'+swfu.customSettings.swfupload_element_id).get(0);
	jQuery('#' + swfu.customSettings.degraded_element_id).hide();
	// Doing this directly because jQuery().show() seems to have timing problems
	if ( swfupload_element && ! swfupload_element.style.display )
			swfupload_element.style.display = 'block';
}

function wpsc_swfuploadLoadFailed() {
	jQuery('#' + swfu.customSettings.swfupload_element_id).hide();
	jQuery('#' + swfu.customSettings.degraded_element_id).show();
}

function wpsc_uploadError(fileObj, error_code, message) {
	// first the file specific error
	if ( error_code == SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL ) {
		wpFileError(fileObj, swfuploadL10n.missing_upload_url);
	}
	else if ( error_code == SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED ) {
		wpFileError(fileObj, swfuploadL10n.upload_limit_exceeded);
	}
	else {
		wpFileError(fileObj, swfuploadL10n.default_error);
	}

	// now the general upload status
	if ( error_code == SWFUpload.UPLOAD_ERROR.HTTP_ERROR ) {
		wpQueueError(swfuploadL10n.http_error);
	}
	else if ( error_code == SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED ) {
		wpQueueError(swfuploadL10n.upload_failed);
	}
	else if ( error_code == SWFUpload.UPLOAD_ERROR.IO_ERROR ) {
		wpQueueError(swfuploadL10n.io_error);
	}
	else if ( error_code == SWFUpload.UPLOAD_ERROR.SECURITY_ERROR ) {
		wpQueueError(swfuploadL10n.security_error);
	}
	else if ( error_code == SWFUpload.UPLOAD_ERROR.FILE_CANCELLED ) {
		wpQueueError(swfuploadL10n.security_error);
	}
}

// remember the last used image size, alignment and url
var storeState;
(function($){

storeState = function(){
	var align = getUserSetting('align') || '', imgsize = getUserSetting('imgsize') || '';

	$('tr.align input[type="radio"]').click(function(){
		setUserSetting('align', $(this).val());
	}).filter(function(){
		if ( $(this).val() == align )
			return true;
		return false;
	}).attr('checked','checked');

	$('tr.image-size input[type="radio"]').click(function(){
		setUserSetting('imgsize', $(this).val());
	}).filter(function(){
		if ( $(this).attr('disabled') || $(this).val() != imgsize )
			return false;
		return true;
	}).attr('checked','checked');

	$('tr.url button').click(function(){
		var c = this.className || '';
		c = c.replace(/.*?(url[^ '"]+).*/, '$1');
		if (c) setUserSetting('urlbutton', c);
		$(this).siblings('.urlfield').val( $(this).attr('title') );
	});

	$('tr.url .urlfield').each(function(){
		var b = getUserSetting('urlbutton');
		$(this).val( $(this).siblings('button.'+b).attr('title') );
	});
}
})(jQuery);
