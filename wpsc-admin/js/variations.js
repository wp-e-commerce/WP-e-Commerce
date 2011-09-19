/* 
* this is the variations javascript file
*/

/**


.variation_checkboxes
	
.variation_box
.variation_checkbox
.variation_checkbox input

.variation_values_box
.variation_checkbox
.variation_checkbox input

*/
//Delete checkout options on settings>checkout page

jQuery('.variation_checkboxes').livequery(function(){

	jQuery('label input:checkbox', this).click(function(){
		 jQuery('a.update_variations_action').show();
	});
	
	jQuery("div.variation_set>label input:checkbox", this).each(function(){
		if (jQuery(this).is(':checked')) {
			jQuery(this).parent().append('<span class="all-none"> (<a href="#" class="all">select all</a> | <a href="#" class="none">select none</a>)</span>');
		}
	});
	
	jQuery("div.variation_set>label input:checkbox", this).click(function(event){
		var variation_set = jQuery(this).parents("div.variation_set");

		jQuery(this).parent().find('.all-none').remove();
		
		if (jQuery(this).is(':checked')) {
			jQuery('div.variation input:checkbox', variation_set).attr('checked', true);
			jQuery('div.variation', variation_set).show();
			jQuery(this).parent().append('<span class="all-none"> (<a href="#" class="all">select all</a> | <a href="#" class="none">select none</a>)</span>');
		} else {
			jQuery('div.variation input:checkbox', variation_set).attr('checked', false);
			jQuery('div.variation', variation_set).hide();
		}
	
	});
	
	jQuery("div.variation_set>label a.all", this).live('click', function(event){
		var variation_set = jQuery(this).parents("div.variation_set");
		jQuery('div.variation input:checkbox', variation_set).attr('checked','checked');
		event.preventDefault();
	});
	
	jQuery("div.variation_set>label a.none", this).live('click', function(event){
		var variation_set = jQuery(this).parents("div.variation_set");
		jQuery('div.variation input:checkbox', variation_set).removeAttr('checked');
		event.preventDefault();
	});
	
	jQuery("div.variation input:checkbox", this).click(function(event){
		var variation_set = jQuery(this).parents("div.variation_set");
		var variation = jQuery(this).parents("div.variation");
		
		if (jQuery(this).is(':checked')) {
			jQuery('label.set_label input:checkbox', variation_set).attr('checked', true);
			jQuery('div.variation', variation_set).show();
		} else {
			var checked_count = jQuery('div.variation input:checked', variation_set).length;
			if(checked_count < 1) {
				jQuery('div.variation', variation_set).hide();
				jQuery('label.set_label input:checkbox', variation_set).attr('checked', false);
			}
		}		
	});
	
});



