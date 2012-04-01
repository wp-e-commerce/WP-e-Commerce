(function($){
	var new_variation_set_count = 0;

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
		$('a.add_variation_set_action').bind('click', event_add_new_variation_set);
		$('#add-new-variation-set .button').bind('click', event_variation_set_add);
		$('#add-new-variation-set input[type="text"]').bind('keypress', event_variation_set_inputs_keypress).
		                                               bind('focus', event_variation_set_inputs_focus).
		                                               bind('blur', event_variation_set_inputs_blur);
	});

	/**
	 * Save variation sort order when user has finished dragging & dropping
	 * @param  {Object} e  Event Object
	 * @param  {Object} ui UI Object
	 * @since 3.8.8
	 */
	var variation_sort = function(e, ui){
		var order = $(this).sortable('toArray'),
			data = {
			action: 'variation_sort_order',
			sort_order: order,
			parent_id: 0
		};
		jQuery.post(ajaxurl, data);
	}

	/**
	 * Save new variation set using AJAX
	 * @since 3.8.8
	 */
	var event_variation_set_add = function() {
		var form = $('#add-new-variation-set');

		form.find('.error').removeClass('error');

		form.find('input[type="text"]').each(function(){
			var t = $(this);
			if (t.val() == '') {
				t.parent().addClass('error');
			}
		});

		if (form.find('.error').size() === 0) {
			var spinner = $(this).siblings('.ajax-feedback'),
				post_data = {
					action : 'wpsc_add_variation_set',
					variation_set : $('#new-variation-set-name').val(),
					variants : $('#new-variants').val(),
					post_id : $('input[name="post_ID"]').val()
				},
				ajax_callback = function(response) {
					var checklist, color, set_id, existing_set;
					if (response != '-1') {
						checklist = $('.variation_checkboxes');
						response = $(response);
						set_id = response.attr('id');
						existing_set = checklist.find('#' + set_id);
						if (existing_set.size() > 0) {
							existing_set.find('.children').append(response.find('.children .ajax'));
						} else {
							checklist.append(response);
						}

						color = checklist.find('li').css('backgroundColor') || '#FFFFFF';
						checklist.find('.ajax').
							animate({ backgroundColor: '#FFFF33' }, 'fast').
							animate({ backgroundColor: color }, 'fast', function(){
								$(this).css('backgroundColor', 'transparent');
							}).
							removeClass('ajax');
					}
					form.hide().find('input:text').val('');
					form.find('label').show().css('opacity', '1');
					spinner.toggleClass('ajax-feedback-active');
				};

			spinner.toggleClass('ajax-feedback-active');
			$.post(ajaxurl, post_data, ajax_callback);

		}

		return false;
	};

	/**
	 * Dim the new variation set inputs' labels when focused.
	 * @since 3.8.8
	 */
	var event_variation_set_inputs_focus = function() {
		$(this).siblings('label').animate({opacity:0.5}, 150);
	};

	/**
	 * Restore opacity to the "new variation set" inputs' labels when blurred.
	 * @since 3.8.8
	 */
	var event_variation_set_inputs_blur = function() {
		var t = $(this);
		if (t.val() == '') {
			t.siblings('label').show().animate({opacity:1}, 150);
		}
	};

	/**
	 * Remove class "error" when something is typed into the new variation set textboxes
	 * @since 3.8.8
	 */
	var event_variation_set_inputs_keypress = function(e) {
		var code = e.keyCode ? e.keyCode : e.which;
		if (code == 13) {
			$('#add-new-variation-set .button').trigger('click');
			e.preventDefault();
		} else {
			$(this).siblings('label').hide().removeClass('error');
		}
	};

	/**
	 * Show the Add Variation Set form and focus on the first text field
	 * @since 3.8.8
	 */
	var event_add_new_variation_set = function() {
		var t = $(this);
		$('#add-new-variation-set').show().find('#new-variation-set-name').focus();
	};

	/**
	 * Save variation combinations via AJAX
	 * @since 3.8.8
	 */
	var event_apply_variations = function() {
		var t = $(this),
			spinner = t.siblings('.ajax-feedback'),
			boxes = $('.variation_checkboxes input:checked'),
			values = [],
			post_data = {
				action : 'wpsc_update_variations',
				description : $('#content_ifr').contents().find('body').html(),
				additional_description : $('textarea#additional_description').text(),
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

	/**
	 * Deselect or Select all children variations when variation set is ticked.
	 * @since 3.8.8
	 */
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

		if (checked !== t.closest('li').hasClass('expanded'))
			t.parent().siblings('.expand').trigger('click');
	};

	/**
	 * Show children variant checkboxes when the triangle is clicked.
	 * @since 3.8.8
	 */
	var event_toggle_children = function() {
		var t = $(this);
		t.siblings('ul').slideToggle(150);
		t.closest('li').toggleClass('expanded');
		return false;
	};

	/**
	 * Show the update variation button.
	 * @since 3.8.8
	 */
	var event_display_apply_variations = function() {
		$('.update-variations').fadeIn(150);
	};

	/**
	 * Deselect the variation set if none of its variants are selected.
	 * Or select the variation set when at least one of its variants is selected.
	 * @since 3.8.8
	 */
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
