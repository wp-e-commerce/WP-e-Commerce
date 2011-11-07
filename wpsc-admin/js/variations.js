(function($){
	function variation_sort(e, ui){
		var order = $(this).sortable('toArray'),
			data = {
			action: 'variation_sort_order',
			sort_order: order,
			parent_id: 0
		};
		jQuery.post(ajaxurl, data);
	}

	$(function(){
		var table = $('body.edit-tags-php .wp-list-table');
		table.find('tbody tr').each(function(){
			var t = $(this),
				id = t.attr('id').replace(/[^0-9]+/g, '');
			t.data('level', WPSC_Term_List_Levels[id]);
			t.data('id', id);
		});
		table.wpsc_sortable_table({
			stop : variation_sort
		});
	});
})(jQuery);

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



