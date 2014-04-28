(function($){
	var resize_iframe = function() {
		if (typeof window.parent.wpsc_resize_iframe != 'undefined') {
			window.parent.wpsc_resize_iframe();
		}
	};

	$(function(){
		resize_iframe();

		$('.wpsc-variation-stock-editor-link').click( function( event ) {
			var parent = $(this).closest('tr'),
				target_row = parent.next('.wpsc-stock-editor-row');

			event.preventDefault();

			target_row.show();
			parent.addClass('active');
			resize_iframe();

			return false;
		});
	});

	var new_variation_set_count = 0;

	$(function(){
		$('.variation_checkboxes').on( 'click', '.variation-set', event_toggle_checkboxes );
		$('.variation_checkboxes').on( 'click', 'a.expand', event_toggle_children );
		$('.variation_checkboxes').on( 'click', '.selectit input:checkbox', event_display_apply_variations );
		$('.variation_checkboxes').on( 'click', '.children input:checkbox', event_toggle_parent );

		$('a.add_variation_set_action').on( 'click', event_add_new_variation_set );
		$('#add-new-variation-set .button').on( 'click', event_variation_set_add );
		$('#add-new-variation-set input[type="text"]').on( 'keypress', event_variation_set_inputs_keypress );
		$('#add-new-variation-set input[type="text"]').on( 'focus', event_variation_set_inputs_focus );
		$('#add-new-variation-set input[type="text"]').on( 'blur', event_variation_set_inputs_blur );

		$('.wpsc-product-variation-thumbnail a').on( 'click', event_variation_thumbnail_click );
		$('.wpsc-bulk-edit').on( 'change', 'input.wpsc-bulk-edit-fields', event_bulk_edit_checkboxes_changed );
      	$('.wpsc-bulk-edit').on( 'keyup', 'input[type="text"]', event_bulk_edit_textboxes_keyup );
	});

	var event_bulk_edit_textboxes_keyup = function() {
		var t = $(this),
		    checkbox = t.siblings('input.wpsc-bulk-edit-fields')[0];
		if ($.trim(t.val()) != '')
			checkbox.checked = true;
	};

	var event_bulk_edit_checkboxes_changed = function() {
		var t = $(this);
		if (t[0].checked)
			t.siblings('input[type="text"]').focus();
	};

	var event_variation_thumbnail_click = function() {
		var t = $( this ), postId = t.data( 'id' ), nonce = t.data( 'nonce' );

		$.wpsc_post(
			{
				action: 'get_variation_gallery',
				nonce: nonce,
				id: postId
			},
			function( response ) {
				if ( ! response.is_successful ) {
					alert( response.error.messages.join( "\n" ) );
					return;
				}

				window.parent.WPSC_Media.open({
					id: postId,
					featuredId: response.obj.featuredId,
					models: response.obj.models,
					galleryUpdateNonce: t.data( 'save-gallery-nonce' ),
					galleryGetNonce: t.data( 'get-gallery-nonce' ),
					featuredNonce: t.data( 'featured-nonce' )
				});
			}
		);

		return false;
	};

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
					action        : 'add_variation_set',
					variation_set : $('#new-variation-set-name').val(),
					variants      : $('#new-variants').val(),
					post_id       : WPSC_Product_Variations.product_id,
					nonce         : WPSC_Product_Variations.add_variation_set_nonce
				},
				ajax_callback = function(response) {
					var checklist, color, set_id, existing_set, content;
					if (response.is_successful) {
						checklist = $('.variation_checkboxes');
						content = $(response.obj.content);
						set_id = content.attr('id');
						existing_set = checklist.find('#' + set_id);
						if (existing_set.size() > 0) {
							existing_set.find('.children').append(content.find('.children .ajax'));
						} else {
							checklist.append(content);
						}

						color = checklist.find('li').css('backgroundColor') || '#FFFFFF';
						checklist.find('.ajax').
							animate({ backgroundColor: '#FFFF33' }, 'fast').
							animate({ backgroundColor: color }, 'fast', function(){
								$(this).css('backgroundColor', 'transparent');
							}).
							removeClass('ajax');
					} else {
						alert(response.error.messages.join("\n"));
					}
					form.hide().find('input:text').val('');
					form.find('label').show().css('opacity', '1');
					spinner.toggleClass('ajax-feedback-active');
				};

			spinner.toggleClass('ajax-feedback-active');
			$.wpsc_post(post_data, ajax_callback);

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
		window.parent.wpsc_resize_iframe();
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
		t.siblings('ul').toggle();
		resize_iframe();
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