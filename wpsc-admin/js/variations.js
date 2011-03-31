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
		is_checked = jQuery(this).attr('checked');
		
		variation_set = jQuery(this).parents("div.variation_set");

		switch(is_checked) {
			case true:
				jQuery('div.variation input:checkbox', variation_set).attr('checked', true);
				jQuery('div.variation', variation_set).show();
			break;
			
			case false:
				jQuery('div.variation input:checkbox', variation_set).attr('checked', false);
				jQuery('div.variation', variation_set).hide();
			break;
		
		}
		
		//jQuery('input:checkbox' ,variation_set);
	
	});
	
	
	
	jQuery("div.variation input:checkbox", this).click(function(event){
		is_checked = jQuery(this).attr('checked');
		variation_set = jQuery(this).parents("div.variation_set");
		variation = jQuery(this).parents("div.variation");
		switch(is_checked) {
			case true:
				jQuery('label.set_label input:checkbox', variation_set).attr('checked', true);
				jQuery('div.variation', variation_set).show();
			break;
			
			case false:
				checked_count = jQuery('div.variation input:checked', variation_set).length;
				if(checked_count < 1) {
					jQuery('div.variation', variation_set).hide();
					jQuery('label.set_label input:checkbox', variation_set).attr('checked', false);
				}
			break;
		
		}
		
	});
	
	
	jQuery("div.variation_set>label input:checkbox", this).livequery(function(event){
	});
	
	jQuery("div.variation input:checkbox", this).livequery(function(event){
		is_checked = jQuery(this).attr('checked');
		variation_set = jQuery(this).parents("div.variation_set");
		checked_count = jQuery('div.variation input:checked', variation_set).length;
		if(checked_count < 1) {
			jQuery('div.variation', variation_set).hide();
			jQuery('label.set_label input:checkbox', variation_set).attr('checked', false);
		}
	});
	
});



