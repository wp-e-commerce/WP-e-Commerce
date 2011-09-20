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

	jQuery("div.variation_set>label input:checkbox", this).click(function(event){
		var variation_set = jQuery(this).parents("div.variation_set");

		if (jQuery(this).is(':checked')) {
			jQuery('div.variation input:checkbox', variation_set).attr('checked', true);
			jQuery('div.variation', variation_set).show();
		} else {
			jQuery('div.variation input:checkbox', variation_set).attr('checked', false);
			jQuery('div.variation', variation_set).hide();
		}
	
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



