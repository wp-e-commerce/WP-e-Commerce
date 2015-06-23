/**
 * WPSC_Settings_Page object and functions.
 *
 * Dependencies: jQuery, jQuery.query
 *
 * The following properties of WPSC_Settings_Page have been set by wp_localize_script():
 * - current_tab: The ID of the currently active tab
 * - nonce      : The nonce used to verify request to load tab content via AJAX
 */

/**
 * @requires jQuery
 * @requires jQuery.query
 */

/*global jQuery, WPSC_Settings_Page, confirm, ajaxurl, alert */
(function($){

	$.extend(WPSC_Settings_Page, /** @lends WPSC_Settings_Page */ {
		/**
		 * Set to true if there are modified settings.
		 * @type {Boolean}
		 * @since 3.8.8
		 */
		unsaved_settings : false,

		/**
		 * Event binding for WPSC_Settings_Page
		 * @since 3.8.8
		 */
		init : function() {
			// make sure the event object contains the 'state' property
			$.event.props.push('state');

			// set the history state of the current page
			if (history.replaceState) {
				(function(){
					history.replaceState({url : location.search + location.hash}, '', location.search + location.hash);
				})();
			}

			// load the correct settings tab when back/forward browser button is used

			$(window).on('popstate', WPSC_Settings_Page.event_pop_state);
			$(function(){
				var wpsc_options = $('#wpsc_options');
				wpsc_options.on( 'click' , 'a.nav-tab'              , WPSC_Settings_Page.event_tab_button_clicked);
				wpsc_options.on( 'change', 'input, textarea, select', WPSC_Settings_Page.event_settings_changed);
				wpsc_options.on( 'submit', '#wpsc-settings-form'    , WPSC_Settings_Page.event_settings_form_submitted);
				$(window).on('beforeunload', WPSC_Settings_Page.event_before_unload);
				$(WPSC_Settings_Page).trigger('wpsc_settings_tab_loaded');
				$(WPSC_Settings_Page).trigger('wpsc_settings_tab_loaded_' + WPSC_Settings_Page.current_tab);
				$('.settings-error').insertAfter('.nav-tab-wrapper');
			});
		},

		/**
		 * This prevents the confirm dialog triggered by event_before_unload from being displayed.
		 * @since 3.8.8
		 */
		event_settings_form_submitted : function() {
			WPSC_Settings_Page.unsaved_settings = false;
		},

		/**
		 * Mark the page as "unsaved" when a field is modified
		 * @since 3.8.8
		 */
		event_settings_changed : function() {
			WPSC_Settings_Page.unsaved_settings = true;
		},

		/**
		 * Display a confirm dialog when the user is trying to navigate
		 * away with unsaved settings
		 * @since 3.8.8
		 */
		event_before_unload : function() {
			if (WPSC_Settings_Page.unsaved_settings) {
				return WPSC_Settings_Page.before_unload_dialog;
			}
		},

		/**
		 * Load the settings tab when tab buttons are clicked
		 * @since 3.8.8
		 */
		event_tab_button_clicked : function() {
			var href = $(this).attr('href');
			WPSC_Settings_Page.load_tab(href);
			return false;
		},

		/**
		 * When back/forward browser button is clicked, load the correct tab
		 * @param {Object} e Event object
		 * @since 3.8.8
		 */
		event_pop_state : function(e) {
			if (e.state) {
				WPSC_Settings_Page.load_tab(e.state.url, false);
			}
		},

		/**
		 * Display a small spinning wheel when loading a tab via AJAX
		 * @param  {String} tab_id Tab ID
		 * @since 3.8.8
		 */
		toggle_ajax_state : function(tab_id) {
			var tab_button = $('a[data-tab-id="' + tab_id + '"]');
			tab_button.toggleClass('nav-tab-loading');
		},

		/**
		 * Use AJAX to load a tab to the settings page. If there are unsaved settings in the
		 * current tab, a confirm dialog will be displayed.
		 *
		 * @param  {String}  tab_id The ID string of the tab
		 * @param  {Boolean} push_state True (Default) if we need to history.pushState.
		 *                              False if this is a result of back/forward browser button being pushed.
		 * @since 3.8.8
		 */
		load_tab : function(url, push_state) {
			if (WPSC_Settings_Page.unsaved_settings && ! confirm(WPSC_Settings_Page.ajax_navigate_confirm_dialog)) {
				return;
			}

			if (typeof push_state == 'undefined') {
				push_state = true;
			}

			var query = $.query.load(url);
			var tab_id = query.get('tab');
			var post_data = $.extend({}, query.get(), {
				'action'      : 'navigate_settings_tab',
				'nonce'       : WPSC_Settings_Page.navigate_settings_tab_nonce,
				'current_url' : location.href,
				'tab'         : tab_id
			});
			var spinner = $('#wpsc-settings-page-title .ajax-feedback');

			spinner.addClass('ajax-feedback-active');
			WPSC_Settings_Page.toggle_ajax_state(tab_id);

			// pushState to save this page load into history, and alter the address field of the browser
			if (push_state && history.pushState) {
				history.pushState({'url' : url}, '', url);
			}

			/**
			 * Replace the option tab content with the AJAX response, also change
			 * the action URL of the form and switch the active tab.
			 * @param  {String} response HTML response string
			 * @since 3.8.8
			 */
			var ajax_callback = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
				var t = WPSC_Settings_Page;
				t.unsaved_settings = false;
				t.toggle_ajax_state(tab_id);
				$('#options_' + WPSC_Settings_Page.current_tab).replaceWith(response.obj.content);
				WPSC_Settings_Page.current_tab = tab_id;
				$('.settings-error').remove();
				$('.nav-tab-active').removeClass('nav-tab-active');
				$('[data-tab-id="' + tab_id + '"]').addClass('nav-tab-active');
				$('#wpsc_options_page form').attr('action', url);
				$(t).trigger('wpsc_settings_tab_loaded');
				$(t).trigger('wpsc_settings_tab_loaded_' + tab_id);
				spinner.removeClass('ajax-feedback-active');
			};

			$.wpsc_post(post_data, ajax_callback);
		}
	});

	/**
	 * General tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.General = {
		/**
		 * Event binding for base country drop down
		 * @since 3.8.8
		 */
		event_init : function() {
			var wrapper = $('#options_general');
			wrapper.on( 'change', '#wpsc-base-country-drop-down', WPSC_Settings_Page.General.event_base_country_changed);
			wrapper.on( 'click' , '.wpsc-select-all'            , WPSC_Settings_Page.General.event_select_all);
			wrapper.on( 'click' ,'.wpsc-select-none'            , WPSC_Settings_Page.General.event_select_none);
		},

		/**
		 * Select all countries for Target Markets
		 * @since 3.8.8
		 */
		event_select_all : function() {
			$('#wpsc-target-markets input:checkbox').each(function(){ this.checked = true; });
			return false;
		},

		/**
		 * Deselect all countries for Target Markets
		 * @since 3.8.8
		 */
		event_select_none : function() {
			$('#wpsc-target-markets input:checkbox').each(function(){ this.checked = false; });
			return false;
		},

		/**
		 * When country is changed, load the region / state drop down using AJAX
		 * @since 3.8.8
		 */
		event_base_country_changed : function() {
			var span = $('#wpsc-base-region-drop-down');
			span.find('select').remove();
			span.find('img').toggleClass('ajax-feedback-active');

			var postdata = {
				action  : 'display_region_list',
				country : $('#wpsc-base-country-drop-down').val(),
				nonce   : WPSC_Settings_Page.display_region_list_nonce
			};

			var ajax_callback = function(response) {
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
				span.find('img').toggleClass('ajax-feedback-active');
				span.prepend(response.obj.content);
			};
			$.wpsc_post(postdata, ajax_callback);
		}
	};
	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_general', WPSC_Settings_Page.General.event_init);

	/**
	 * Presentation tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.Presentation = {
		/**
		 * IDs of checkboxes for Grid View (excluding the Show Images Only checkbox)
		 * @type {Array}
		 * @since 3.8.8
		 */
		grid_view_boxes : ['wpsc-display-variations', 'wpsc-display-description', 'wpsc-display-add-to-cart', 'wpsc-display-more-details'],

		/**
		 * Event binding for Grid View checkboxes
		 * @since 3.8.8
		 */
		event_init : function() {
			var wrapper = $('#options_presentation'),
				checkbox_selector = '#' + WPSC_Settings_Page.Presentation.grid_view_boxes.join(',#');
			wrapper.on( 'click', '#wpsc-show-images-only', WPSC_Settings_Page.Presentation.event_show_images_only_clicked);
			wrapper.on( 'click', checkbox_selector       , WPSC_Settings_Page.Presentation.event_grid_view_boxes_clicked);
		},

		/**
		 * Deselect "Show Images Only" checkbox when any other Grid View checkboxes are selected
		 * @since 3.8.8
		 */
		event_grid_view_boxes_clicked : function() {
			document.getElementById('wpsc-show-images-only').checked = false;
		},

		/**
		 * Deselect all other Grid View checkboxes when "Show Images Only" is selected
		 * @since 3.8.8
		 */
		event_show_images_only_clicked : function() {
			var i;

			if ( $( this ).is( ':checked' ) ) {
				for ( i in WPSC_Settings_Page.Presentation.grid_view_boxes ) {
					if ( WPSC_Settings_Page.Presentation.grid_view_boxes.hasOwnProperty( i ) ) {
						document.getElementById( WPSC_Settings_Page.Presentation.grid_view_boxes[ i ] ).checked = false;
					}
				}
			}
		}
	};

	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_presentation', WPSC_Settings_Page.Presentation.event_init);

	/**
	 * Checkout Tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.Checkout = {
		new_field_count : 0,

		/**
		 * Event binding for Checkout tab
		 * @since 3.8.8
		 */
		event_init : function() {
			var wrapper = $('#options_checkout');
			wrapper.on( 'click', '.add_new_form_set', WPSC_Settings_Page.Checkout.event_add_new_form_set);
			wrapper.on( 'click', '.actionscol a.wpsc-button-plus', WPSC_Settings_Page.Checkout.event_add_new_field);
			wrapper.on( 'click', '.actionscol a.wpsc-button-minus', WPSC_Settings_Page.Checkout.event_delete_field);
			wrapper.on( 'click', 'a.edit-options', WPSC_Settings_Page.Checkout.event_edit_field_options);
			wrapper.on( 'change', 'select[name^="form_type"], select[name^="new_field_type"]', WPSC_Settings_Page.Checkout.event_form_type_changed);
			wrapper.on( 'click', '.field-option-cell-wrapper .wpsc-button-plus', WPSC_Settings_Page.Checkout.event_add_field_option);
			wrapper.on( 'click', '.field-option-cell-wrapper .wpsc-button-minus', WPSC_Settings_Page.Checkout.event_delete_field_option);
			wrapper.on( 'click', '#wpsc-delete-checkout-set', WPSC_Settings_Page.Checkout.event_delete_checkout_set);
			wrapper.on( 'change', '#wpsc_form_set', WPSC_Settings_Page.Checkout.event_select_form_set);
            wrapper.on( 'click', '.mandatorycol input[type="checkbox"]', WPSC_Settings_Page.Checkout.event_disabled_toggled);
			$('#wpsc-settings-form').on( 'submit', WPSC_Settings_Page.Checkout.event_form_submit);

			wrapper.find('#wpsc_checkout_list').
				sortable({
					items       : 'tr.checkout_form_field',
					axis        : 'y',
					containment : 'parent',
					placeholder : 'checkout-placeholder',
					handle      : '.drag',
					sort        : WPSC_Settings_Page.Checkout.event_sort,
					helper      : WPSC_Settings_Page.Checkout.fix_sortable_helper,
					start       : WPSC_Settings_Page.Checkout.event_sort_start,
					stop        : WPSC_Settings_Page.Checkout.event_sort_stop,
					update      : WPSC_Settings_Page.Checkout.event_sort_update
				});

			WPSC_Settings_Page.Checkout.new_field_count = $('.new-field').length;

			/**
			 * Finding checkboxes that are mandatory and disabling the display option.
			 * If it's mandatory you no have choice for display.
			 */
			wrapper.find( '.mandatorycol input[type="checkbox"]').each( function(){
				var displaycol = $( this ).parents( '.mandatorycol' ).siblings( '.displaycol' );

				if ( $( this ).is( ':checked' ) ) {
					$( displaycol ).find( 'input[type="checkbox"]' ).prop( 'checked', true ).prop( 'readonly', true );
				} else {
					$( displaycol ).find( 'input[type="checkbox"]' ).prop( 'readonly', this.checked );
				}
			});

		},

		event_add_field_option : function() {
			var target_row = $(this).closest('tr'),
				prototype = target_row.siblings('.new-option').clone(),
				options_row = $(this).closest('.form-field-options'),
				id = options_row.data('field-id'),
				options_field_name;

			if (! id) {
				id = options_row.data('new-field-id');
				options_field_name = 'new_field_options[' + id + ']';
			} else {
				options_field_name = 'form_options[' + id + ']';
			}

			prototype.removeClass('new-option');
			prototype.find('.field-option-cell-wrapper').hide();
			prototype.find('.column-labels input').attr('name', options_field_name + '[label][]');
			prototype.find('.column-values input').attr('name', options_field_name + '[value][]');
			prototype.insertAfter(target_row).show().find('.field-option-cell-wrapper').slideDown(150);
			prototype.find('input[type="text"]').eq(0).focus();

			WPSC_Settings_Page.unsaved_settings = true;
			return false;
		},

		event_delete_field_option : function() {
			var target_row = $(this).closest('tr'),
				prototype = target_row.siblings('.new-option');

			target_row.find('.field-option-cell-wrapper').slideUp(150, function(){
				var clone;
				if (prototype.siblings().size() == 1) {
					clone = prototype.clone().removeClass('new-option');
					clone.find('.field-option-cell-wrapper').hide();
					clone.show().insertAfter(target_row);
					clone.find('.field-option-cell-wrapper').slideDown(150);
				}
				target_row.remove();
			});

			WPSC_Settings_Page.unsaved_settings = true;

			return false;
		},

		event_form_type_changed : function() {
			var t = $(this),
				target_row = t.closest('tr'),
				id = target_row.data('field-id'),
				type = target_row.data('field-type'),
				link = target_row.find('.edit-options'),
				options_row_id = 'wpsc-field-edit-options-' + id;

			if (! id) {
				id = target_row.data('new-field-id');
				options_row_id = 'wpsc-new-field-edit-options-' + id;
			}
			target_row
				.removeClass('field_type_'+type)
				.addClass('field_type_'+t.val())
				.data('field-type', t.val());
			type = t.val();
			if ($.inArray(t.val(), ['select', 'radio', 'checkbox']) !== -1) {
				link.show();
			} else {
				link.hide().text(WPSC_Settings_Page.edit_field_options).removeClass('expanded');
				$('#wpsc-field-edit-options-' + id).find('.cell-wrapper').slideUp(150, function(){
					$(this).closest('tr').remove();
					target_row.removeClass('editing-options');
				});
			}
		},

		event_edit_field_options : function() {
			var t = $(this), target = t.closest('tr'),
				id, options_row, label_inputs, options_field_name,
				prototype_option, options_row_id, data_name;

			id = target.data('field-id');

			if (id) {
				options_field_name = 'form_options[' + id + ']';
				options_row_id = 'wpsc-field-edit-options-' + id;
				data_name = 'field-id';
			} else {
				id = target.data('new-field-id');
				options_field_name = 'new_field_options[' + id + ']';
				options_row_id = 'wpsc-new-field-edit-options-' + id;
				data_name = 'new-field-id';
			}

			options_row = $('#' + options_row_id);

			if (t.hasClass('expanded')) {
				options_row.find('.cell-wrapper').slideUp(150, function(){
					$(this).closest('tr').hide();
					target.removeClass('editing-options');
				});
				t.removeClass('expanded');
				t.text(WPSC_Settings_Page.edit_field_options);
				return false;
			}

			t.addClass('expanded');
			t.text(WPSC_Settings_Page.hide_edit_field_options);
			target.addClass('editing-options');

			if (options_row.size() > 0) {
				options_row.show().find('.cell-wrapper').slideDown(150);
				return false;
			}

			options_row = $('#field-options-prototype').clone();
			prototype_option = options_row.find('.new-option');

			options_row.
				attr('id', options_row_id).
				data(data_name, id);

			if (target.hasClass('new-field')) {
				options_row.addClass('new-field-options');
			}


			prototype_option.find('.column-labels input').attr('name', options_field_name + '[label][]');
			prototype_option.find('.column-values input').attr('name', options_field_name + '[value][]');

			label_inputs = target.find('input[name^="' + options_field_name + '[label]"]');

			label_inputs.each(function(){
				var prototype = options_row.find('.new-option'),
					appended_row = prototype.clone().removeClass('new-option'),
					input_label = $(this),
					input_value = $(this).next(),
					new_label_field = $('<input type="text" />').attr('name', input_label.attr('name')).val(input_label.val()),
					new_value_field = $('<input type="text" />').attr('name', input_value.attr('name')).val(input_value.val());

				appended_row.find('.column-labels input').replaceWith(new_label_field);
				appended_row.find('.column-values input').replaceWith(new_value_field);
				options_row.find('tbody').append(appended_row);
				input_value.remove();
				input_label.remove();
			});

			prototype_option.hide();
			if (label_inputs.size() === 0) {
				prototype_option.clone().removeClass('new-option').show().appendTo(options_row.find('tbody'));
			}

			options_row.find('.cell-wrapper').hide();
			options_row.insertAfter(target).show().find('.cell-wrapper').slideDown(150);
			return false;
		},

		event_form_submit : function() {
			var sort_order = $('#wpsc_checkout_list').sortable('toArray');
			for (var index in sort_order) {
				if ( sort_order.hasOwnProperty( index ) ) {
					$(this).append('<input type="hidden" name="sort_order[]" value="' + sort_order[index] + '" />');
				}
			}
			return true;
		},

		event_add_new_field : function() {
			var target_row = $(this).closest('tr'),
				new_row = $('#field-prototype').clone(),
				id,
				next_row = target_row.next();

			WPSC_Settings_Page.Checkout.new_field_count ++;
			id = WPSC_Settings_Page.Checkout.new_field_count;
			new_row.
				attr('id', 'new-field-' + id).
				addClass('checkout_form_field').
				data('new-field-id', id);
			new_row.find('.cell-wrapper').hide();
			new_row.find('input, select').each(function(){
				var t = $(this),
					name = t.attr('name'),
					new_name = name.replace('[0]', '[' + id + ']');

				t.attr('name', new_name);
			});

			if (next_row && next_row.hasClass('form-field-options'))
				target_row = next_row;

			new_row.insertAfter(target_row).show().find('.cell-wrapper').slideDown(150);

			WPSC_Settings_Page.unsaved_settings = true;
			return false;
		},

		event_delete_field : function() {
			var target_row = $(this).closest('tr'), next_row;

			if ( $('.checkout_form_field').length == 1 ) {
				next_row = target_row.next();
				next_row.hide();
				target_row.removeClass('editing-options');
				target_row.find('input[type="text"]').val('');
				target_row.find('select').val('');
				target_row.find('.edit-options').removeClass('expanded').text(WPSC_Settings_Page.edit_field_options).hide();

				next_row.find('input[type="text"]').val('');
				next_row.find('.wpsc-field-options-table tbody tr:gt(1)').remove();

				target_row.find('.cell-wrapper').slideUp(150, function(){
					$(this).slideDown(150);
				});
				return false;
			}

			target_row.find('.cell-wrapper').slideUp(150, function(){
				var id = target_row.data('field-id');

				if (id) {
					$('#wpsc-field-edit-options-' + id).remove();
				} else {
					id = target_row.data('new-field-id');
					$('#wpsc-new-field-edit-options-' + id).remove();
				}
				target_row.remove();
			});

			WPSC_Settings_Page.unsaved_settings = true;
			return false;
		},

		/**
		 * Disables and checks the display option if you make a field mandatory. If you uncheck
		 * mandatory then it just enables you to uncheck the display box.
		 */
		event_disabled_toggled : function() {

			var displaycol = $( this ).parents( '.mandatorycol' ).siblings( '.displaycol' );

			if ( $( this ).is( ':checked' ) ) {
				$( displaycol ).find( 'input[type="checkbox"]' ).prop( 'checked', true ).prop( 'readonly', true );
			} else {
				 $( displaycol ).find( 'input[type="checkbox"]' ).prop( 'readonly', this.checked );
			}
		},

		/**
		 * This hack is to make sure the dragged row has 100% width
		 *
		 * @param  {Object} e Event object
		 * @param  {Object} tr The row being dragged
		 * @return {Object} helper The helper object (which is a clone of the row)
		 */
		fix_sortable_helper : function(e, tr) {
			var row = tr.clone().width(tr.width());
			row.find('td').each(function(){
				var td_class = $(this).attr('class'), original = tr.find('.' + td_class);
				$(this).width(original.width());
			});
			return row;
		},

		/**
		 * The placeholder in this case will be an empty <tr> element. Need to add
		 * a <td> inside for styling purpose.
		 * @param  {Object} e Event Object
		 * @param  {Object} ui UI Object
		 */
		event_sort_start : function(e, ui) {
			var t = $(this);

			$('.form-field-options').find('.cell-wrapper').slideUp(150, function(){
				var options_row = $(this).closest('tr'),
					id = options_row.data('field-id'),
					row_id = '#checkout_' + id;

				if (! id) {
					id = options_row.data('new-field-id');
					row_id = '#new-field-' + id;
				}
				options_row.hide();
				t.sortable('refreshPositions');
				$(row_id).removeClass('editing-options');
			});

			ui.placeholder.html('<td colspan="7">&nbsp;</td>');
		},

		event_sort_stop : function() {
			$('.form-field-options').each(function(){
				var options_row = $(this),
					id = $(this).data('field-id'),
					target_row_id = '#checkout_' + id,
					target_row;

				if (! id) {
					id = $(this).data('new-field-id');
					target_row_id = '#new-field-' + id;
				}
				target_row = $(target_row_id);
				options_row.insertAfter(target_row).show().find('.cell-wrapper').slideDown(150, function(){
					target_row.addClass('editing-options');
				});
			});
		},

		/**
		 * Update sort order via AJAX.
		 * @param  {Object} e Event Object
		 * @param  {Object} ui UI Object
		 */
		event_sort_update : function(e, ui) {
			if (ui.item.hasClass('new-field')) {
				return;
			}

			var spinner = $(ui.item).find('.ajax-feedback');
			var post_data = {
				action     : 'update_checkout_fields_order',
				nonce      : WPSC_Settings_Page.update_checkout_fields_order_nonce,
				sort_order : $('table#wpsc_checkout_list').sortable('toArray')
			};
			var ajax_callback = function(response) {
				spinner.toggleClass('ajax-feedback-active');
				ui.item.find('.drag a').show();
				if (! response.is_successful) {
					alert(response.error.messages.join("\n"));
					return;
				}
			};
			ui.item.find('.drag a').hide();
			spinner.toggleClass('ajax-feedback-active');
			$.wpsc_post(post_data, ajax_callback);
		},

		/**
		 * Toggle "Add New Form Set" field
		 * @since 3.8.8
		 */
		event_add_new_form_set : function() {
			$(".add_new_form_set_forms").toggle();
			return false;
		},

		/**
		 * Display confirm dialog before deleting a form set
		 * @since 3.8.9
		 */
		event_delete_checkout_set : function() {
			var dropdown = $('#wpsc_form_set'),
				form_id = dropdown.val(),
				form_name = dropdown.find('option[value="' + form_id + '"]').text(),
				message = WPSC_Settings_Page.delete_form_set_confirm.replace('%s', form_name);

			if (! confirm(message)) {
				return false;
			}

			return true;
		},

		/**
		 * Hide delete button if default form set is being selected
		 * @since 3.8.9
		 */
		event_select_form_set : function() {
			var t = $(this), form_id = t.val() * 1;

			if (form_id === 0)
				$('#wpsc-delete-checkout-set').hide();
			else
				$('#wpsc-delete-checkout-set').show();
		}
	};
	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_checkout', WPSC_Settings_Page.Checkout.event_init);

	/**
	 * Taxes tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.Taxes = {
		/**
		 * Event binding for Taxes tab
		 * @since 3.8.8
		 */
		event_init : function() {
			var wrapper = $('#options_taxes');

			wrapper.on( 'click' , '.wpsc-button-minus'           , function () { return false; } );
			wrapper.on( 'click' , '.wpsc-taxes-rates-add'        , WPSC_Settings_Page.Taxes.event_add_tax_rate);
			wrapper.on( 'click' , '.wpsc-taxes-rates-delete'     , WPSC_Settings_Page.Taxes.event_delete_tax_rate);
			wrapper.on( 'click' , '.wpsc-taxes-bands-add'        , WPSC_Settings_Page.Taxes.event_add_tax_band);
			wrapper.on( 'click' , '.wpsc-taxes-bands-delete'     , WPSC_Settings_Page.Taxes.event_delete_tax_band);
			wrapper.on( 'change', '.wpsc-taxes-country-drop-down', WPSC_Settings_Page.Taxes.event_country_drop_down_changed);
		},

		/**
		 * Load the region drop down via AJAX if the country has regions
		 * @since 3.8.8
		 */
		event_country_drop_down_changed : function() {
			var c = $(this),
				post_data = {
					action            : 'add_tax_rate',
					wpec_taxes_action : 'wpec_taxes_get_regions',
					current_key       : c.data('row-key'),
					taxes_type        : c.data('row-mode'),
					country_code      : c.val(),
					nonce             : WPSC_Settings_Page.add_tax_rate_nonce
				},
				spinner = c.siblings('.ajax-feedback'),
				ajax_callback = function(response) {
					spinner.toggleClass('ajax-feedback-active');
					if (response.is_successful) {
						c.after(response.obj.content);
					}
				};
			spinner.toggleClass('ajax-feedback-active');
			c.siblings('.wpsc-taxes-region-drop-down').remove();

			$.wpsc_post(post_data, ajax_callback);
		},

		/**
		 * Add new tax rate field when "Add Tax Rate" is clicked
		 * @since 3.8.8
		 * TODO: rewrite the horrible code in class wpec_taxes_controller. There's really no need for AJAX here.
		 */
		event_add_tax_rate : function() {
			WPSC_Settings_Page.Taxes.add_field('rates');
			return false;
		},

		/**
		 * Remove a tax rate row when "Delete" on that row is clicked.
		 * @since 3.8.8
		 */
		event_delete_tax_rate : function() {
			$(this).parents('.wpsc-tax-rates-row').remove();
			if ($('.wpsc-tax-rates-row').size() === 1) {
				WPSC_Settings_Page.Taxes.add_field('rates');
			}
			return false;
		},

		/**
		 * Add new tax band field when "Add Tax Band" is clicked.
		 * @since 3.8.8
		 */
		event_add_tax_band : function() {
			WPSC_Settings_Page.Taxes.add_field('bands');
			return false;
		},

		/**
		 * Delete a tax band field when "Delete" is clicked.
		 * @return {[type]}
		 */
		event_delete_tax_band : function() {
			$(this).parents('.wpsc-tax-bands-row').remove();
			if ($('.wpsc-tax-bands-row').size() === 1) {
				WPSC_Settings_Page.Taxes.add_field('bands');
			}
			return false;
		},

		/**
		 * Add a field to the Tax Rate / Tax Band form, depending on the supplied type
		 * @param {String} Either "bands" or "rates" to specify the type of field
		 * @since 3.8.8
		 */
		add_field : function(type) {
			var tbody = $('#wpec-taxes-' + type + ' tbody'),
				count = $('.wpsc-tax-' + type + '-row').size(),
				new_prototype_row = $('#wpsc-taxes-' + type + '-row-prototype').clone();
			new_prototype_row.removeClass('prototype');
			new_prototype_row.attr('id', new_prototype_row.attr('id').replace(/prototype/g, count));
			new_prototype_row.attr('data-row-key', new_prototype_row.attr('data-row-key').replace(/prototype/g, count));
			new_prototype_row.html( new_prototype_row.html().replace(/prototype/g, count) );
			tbody.append(new_prototype_row);
		}
	};
	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_taxes', WPSC_Settings_Page.Taxes.event_init);

	/**
	 * Shipping Tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.Shipping = {
		/**
		 * Event binding for Shipping tab.
		 * @since 3.8.8
		 */
		event_init : function() {
			WPSC_Settings_Page.Shipping.wrapper = $('#options_shipping');
			WPSC_Settings_Page.Shipping.table_rate = WPSC_Settings_Page.Shipping.wrapper.find('.table-rate');

			WPSC_Settings_Page.Shipping.wrapper.on( 'click'   , '.edit-shipping-module'         , WPSC_Settings_Page.Shipping.event_edit_shipping_module);
			WPSC_Settings_Page.Shipping.wrapper.on( 'click'   , '.table-rate .wpsc-button-plus' , WPSC_Settings_Page.Shipping.event_add_table_rate_layer);
			WPSC_Settings_Page.Shipping.wrapper.on( 'click'   , '.table-rate .wpsc-button-minus', WPSC_Settings_Page.Shipping.event_delete_table_rate_layer);
			WPSC_Settings_Page.Shipping.wrapper.on( 'keypress', '.table-rate input[type="text"]', WPSC_Settings_Page.Shipping.event_enter_key_pressed);
			WPSC_Settings_Page.Shipping.wrapper.on( 'click'   , 'a.shipwire_sync'               , WPSC_Settings_Page.Shipping.event_sync_shipwire);
			WPSC_Settings_Page.Shipping.wrapper.on( 'click'   , '.edit-shipping-module-cancel'  , WPSC_Settings_Page.Shipping.event_edit_shipping_module_cancel);
		},

		/**
		 * When Enter key is pressed inside the table rate fields, it should either move
		 * focus to the next input field (just like tab), or create a new row and do that.
		 *
		 * This is to prevent accidental form submission.
		 *
		 * @param  {Object} e Event object
		 * @since 3.8.8
		 */
		event_enter_key_pressed : function(e) {
			var code = e.keyCode ? e.keyCode : e.which;
			if (code == 13) {
				var add_button = $(this).siblings('.actions').find('.add');
				if (add_button.size() > 0) {
					add_button.trigger('click', [true]);
				} else {
					$(this).closest('td').siblings('td').find('input').focus();
				}
				e.preventDefault();
			}
		},

		/**
		 * Add a layer row to the table rate form
		 * @param  {Object} e Event object
		 * @param  {Boolean} focus_on_new_row Defaults to false. Whether to automatically put focus on the first input of the new row.
		 * @since 3.8.8
		 */
		event_add_table_rate_layer : function(e, focus_on_new_row) {
			if (typeof focus_on_new_row === 'undefined') {
				focus_on_new_row = false;
			}

			var this_row = $(this).closest('tr'),
				clone = this_row.clone();

			clone.find('input').val('');
			clone.find('.cell-wrapper').hide();
			clone.insertAfter(this_row).find('.cell-wrapper').slideDown(150, function() {
				if (focus_on_new_row) {
					clone.find('input').eq(0).focus();
				}
			});
			WPSC_Settings_Page.Shipping.refresh_alt_row();
			return false;
		},

		/**
		 * Delete a table rate layer row.
		 * @since 3.8.8
		 */
		event_delete_table_rate_layer : function() {
			var this_row = $(this).closest('tr');
			if (WPSC_Settings_Page.Shipping.wrapper.find('.table-rate tr:not(.js-warning)').size() == 1) {
				this_row.find('input').val('');
				this_row.fadeOut(150, function(){ $(this).fadeIn(150); } );
			} else {
				this_row.find('.cell-wrapper').slideUp(150, function(){
					this_row.remove();
					WPSC_Settings_Page.Shipping.refresh_alt_row();
				});
			}
			return false;
		},

		/**
		 * Load Shipping Module settings form via AJAX when "Edit" is clicked.
		 * @since 3.8.8
		 */
		event_edit_shipping_module : function() {
			var element = $(this),
				shipping_module_id = element.closest('.wpsc-select-shipping').data('shipping-id'),
				spinner = element.children('.ajax-feedback'),
				post_data = {
					action : 'shipping_module_settings_form',
					'shipping_module_id' : shipping_module_id,
					nonce  : WPSC_Settings_Page.shipping_module_settings_form_nonce
				},
				ajax_callback = function(response) {
					if (! response.is_successful) {
						alert(response.error.messages.join("\n"));
						return false;
					}

					if (history.pushState) {
						var new_url = '?page=wpsc-settings&tab=' + WPSC_Settings_Page.current_tab + '&shipping_module_id=' + shipping_module_id;
						history.pushState({url : new_url}, '', new_url);
					}
					spinner.toggleClass('ajax-feedback-active');

					$('#wpsc_shipping_settings_' + shipping_module_id + '_form').remove();
					$('#wpsc_shipping_settings_'+ shipping_module_id).show( 400 );
					$('#wpsc_shipping_settings_'+ shipping_module_id + '_container').append(response.obj.content);

				};

			if ( $( '#wpsc_shipping_settings_' + shipping_module_id + '_form' ).is( ':visible' ) ) {
				return false;
			}

			spinner.toggleClass('ajax-feedback-active');
			$.wpsc_post(post_data, ajax_callback);

			return false;
		},

		/**
		 * Remove Shipping Module settings from from page when "Cancel" is clicked.
		 * @since 3.8.11
		 */
		event_edit_shipping_module_cancel : function() {
			var element = $(this),
				shipping_module_id = element.closest('.wpsc-select-shipping').data('shipping-id');

			if (history.pushState) {
				var new_url = '?page=wpsc-settings&tab=' + WPSC_Settings_Page.current_tab;
				history.pushState({'url' : new_url}, '', new_url);
			}
			$('#wpsc_shipping_settings_' + shipping_module_id + '_form').remove();
			$('#wpsc_shipping_settings_' + shipping_module_id).hide( 400 );

			return false;
		},

		/**
		 * Syncs with Shipwire's Inventory and Tracking APIs
		 * @since 3.8.9
		 */
		event_sync_shipwire : function() {
			var element = $(this),
				spinner = element.siblings('.ajax-feedback'),
				post_data = {
					action    : 'sync_shipwire_products',
					email     : $('input[name="wpsc_options[shipwireemail]"]').val(),
					password  : $('input[name="wpsc_options[shipwirepassword]"]').val(),
					server    : $('input[name="wpsc_options[shipwire_test_server]"]').val(),
					nonce     : WPSC_Settings_Page.shipping_module_settings_form_nonce
				},
				ajax_callback = function(response) {
					$('<div class="updated shipwire-update"><p><strong>' + response.tracking + '<br />' + response.inventory + '</strong></p></div>').
					insertBefore(element).
					fadeIn('slow').
					delay(5500).
					fadeOut('slow');
					spinner.toggleClass('ajax-feedback-active');
				};
			spinner.toggleClass('ajax-feedback-active');
			$.post( ajaxurl, post_data, ajax_callback, 'json' );
			return false;
		},

		/**
		 * Refresh the zebra rows of the table
		 * @since 3.8.8
		 */
		refresh_alt_row : function() {
			WPSC_Settings_Page.Shipping.wrapper.find('.alternate').removeClass('alternate');
			WPSC_Settings_Page.Shipping.wrapper.find('#wpsc-shipping-module-settings tr:odd').addClass('alternate');
		}
	};
	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_shipping', WPSC_Settings_Page.Shipping.event_init);

	/**
	 * Payments Tab
	 * @namespace
	 * @since 3.8.8
	 */
	WPSC_Settings_Page.Gateway = {
		event_init : function() {
			var wrapper = $('#options_gateway');
			wrapper.on( 'click', '.edit-payment-module'       , WPSC_Settings_Page.Gateway.event_edit_payment_gateway);
			wrapper.on( 'click', '.edit-payment-module-cancel', WPSC_Settings_Page.Gateway.event_edit_payment_gateway_cancel);
		},
		event_edit_payment_gateway : function() {
			var element = $(this), // edit link
				payment_gateway_id = element.closest('.wpsc-select-gateway').data('gateway-id'),
				spinner = element.siblings('.ajax-feedback'),
				post_data = {
					action               : 'payment_gateway_settings_form',
					'payment_gateway_id' : payment_gateway_id,
					nonce                : WPSC_Settings_Page.payment_gateway_settings_form_nonce
				},
				ajax_callback = function(response) {
					if ( ! response.is_successful ) {
						alert(response.error.messages.join("\n"));
						return false;
					}

					if (history.pushState) {
						var new_url = '?page=wpsc-settings&tab=' + WPSC_Settings_Page.current_tab + '&payment_gateway_id=' + payment_gateway_id;
						history.pushState({'url' : new_url}, '', new_url);
					}
					spinner.toggleClass('ajax-feedback-active');
					$( '#gateway_settings_' + payment_gateway_id + '_form' ).remove();
					$( '#wpsc_gateway_settings_'+ payment_gateway_id ).show( 400 );
					$( '#wpsc_gateway_settings_'+ payment_gateway_id + '_container' ).append(response.obj.content);
				};

			if ( $( '#gateway_settings_' + payment_gateway_id + '_form' ).is( ':visible' ) ) {
				return false;
			}

			spinner.toggleClass('ajax-feedback-active');
			$.wpsc_post(post_data, ajax_callback);
			return false;
		},
		event_edit_payment_gateway_cancel : function() {
			var element = $(this),
				payment_gateway_id = element.closest('.gateway_settings').data('gateway-id');
			if (history.pushState) {
				var new_url = '?page=wpsc-settings&tab=' + WPSC_Settings_Page.current_tab;
				history.pushState({'url' : new_url}, '', new_url);
			}
			$('#gateway_settings_' + payment_gateway_id + '_form').remove();
			$('#wpsc_gateway_settings_' + payment_gateway_id).hide( 400 );
			return false;
		}
	};
	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_gateway', WPSC_Settings_Page.Gateway.event_init);

	/**
	 * Marketing Tab
	 * @namespace
	 * @since 3.8.9
	 */
	WPSC_Settings_Page.Marketing = {
		event_init : function() {

			var wrapper = $('div#wpsc_google_analytics_integration');

			wrapper.on( 'click', 'input[type="checkbox"]', WPSC_Settings_Page.Marketing.event_show_hide_dependencies);

			var checkbox = $( 'div#wpsc_google_analytics_integration input[type="checkbox"]' );

			$.each( checkbox, function() {
				if ( $(this).is( ':checked' ) )
					$(this).parent('p').nextAll('p').hide();
			});
		},

		event_show_hide_dependencies : function () {
			var e = $(this);

			if ( e.is( ':checked' ) )
				e.parent('p').nextAll('p').hide();
			else
				e.parent('p').nextAll('p').show();
		}

	};

	$(WPSC_Settings_Page).on('wpsc_settings_tab_loaded_marketing', WPSC_Settings_Page.Marketing.event_init);

})(jQuery);

WPSC_Settings_Page.init();
