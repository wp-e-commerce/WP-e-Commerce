(function($){
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

		$('.variation_checkboxes').delegate('.variation-set', 'click', event_toggle_checkboxes).
		                           delegate('a.expand', 'click', event_toggle_children).
		                           delegate('.selectit input:checkbox', 'click', event_display_apply_variations).
		                           delegate('.children input:checkbox', 'click', event_toggle_parent);

		$('a.update_variations_action').bind('click', event_apply_variations);
	});

	function variation_sort(e, ui){
		var order = $(this).sortable('toArray'),
			data = {
			action: 'variation_sort_order',
			sort_order: order,
			parent_id: 0
		};
		jQuery.post(ajaxurl, data);
	}

	var event_apply_variations = function() {
		var t = $(this),
			spinner = t.siblings('.ajax-feedback'),
			boxes = $('.variation_checkboxes input:checked'),
			values = [],
			post_data = {
				action : 'wpsc_update_variations',
				description : $('#content_ifr').contents().find('body').html(),
				additional_description : $('textareaa#additional_description').text(),
				name : $('input#title').val(),
				product_id : $('input#product_id').val()
			},
			ajax_callback = function(response){
				$('div#wpsc_product_variation_forms table.widefat tbody').html(response);
				spinner.toggleClass('ajax-feedback-active');
			};

		boxes.each(function(){
			var t = $(this);
			post_data[t.attr('name')] = t.val();
		});

		post_data.edit_var_val = values;
		spinner.toggleClass('ajax-feedback-active');

		$.post(ajaxurl, post_data, ajax_callback);

		return false;
	};

	var event_toggle_checkboxes = function() {
		var t = $(this), checked;

		if (t.is(':checked')) {
			checked = true;
		} else {
			checked = false;
		}

		t.closest('li').find('.children input:checkbox').each(function(){
			this.checked = checked;
		});

		t.parent().siblings('.expand').trigger('click');
	};

	var event_toggle_children = function() {
		var t = $(this);
		t.siblings('ul').slideToggle(150);
		t.closest('li').toggleClass('expanded');
		return false;
	};

	var event_display_apply_variations = function() {
		$('.update-variations').fadeIn(150);
	};

	var event_toggle_parent = function() {
		var t = $(this),
			parent = t.closest('.children').parent();
			parent_checkbox = parent.find('.variation-set'),
			checked = this.checked;

		if (this.checked) {
			parent_checkbox[0].checked = true;
		} else if (parent.find('.children input:checked').size() == 0) {
			parent_checkbox[0].checked = false;
			parent.find('.expand').trigger('click');
		}
	};
})(jQuery);