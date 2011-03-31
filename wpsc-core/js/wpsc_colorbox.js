jQuery(document).ready(function(){
	jQuery('.imagecol').each(function(){
		jQuery('.thickbox', this).colorbox({maxWidth:'90%', maxHeight:'90%'});
	});
});