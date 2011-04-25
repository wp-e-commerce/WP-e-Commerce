jQuery(document).ready(function(){
	jQuery('.imagecol').each(function(){
		var t = jQuery(this).find('.wpcart_gallery .thickbox');
		t.colorbox({
			maxWidth :'90%',
			maxHeight :'90%',
			returnFocus : false
		});
		
		jQuery(this).children('.thickbox').click(function(e){
			var that = jQuery(this);
			e.preventDefault();
			t.each(function(){
				if (jQuery(this).attr('href') == that.attr('href')) {
					jQuery(this).click();
					return;
				}
			});
		});
	});
});