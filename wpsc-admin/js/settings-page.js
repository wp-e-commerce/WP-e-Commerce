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
var WPSC_Settings_Tab_General, WPSC_Settings_Tab_Presentation, WPSC_Settings_Tab_Checkout, WPSC_Settings_Tab_Taxes,
    WPSC_Settings_Tab_Shipping;

(function($){

	var t = WPSC_Settings_Page;

	$.extend(t, /** @lends WPSC_Settings_Page */ {
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
					history.replaceState({tab_id : t.current_tab}, '', location.search + location.hash);
				})();
			}

			// load the correct settings tab when back/forward browser button is used
			$(window).bind('popstate', t.event_pop_state);

			$(function(){
				$('#wpsc_options').delegate('a.nav-tab', 'click', t.event_tab_button_clicked).
				                   delegate('input, textarea, select', 'change', t.event_settings_changed);
				$(window).bind('beforeunload', t.event_before_unload);
				$(t).trigger('wpsc_settings_tab_loaded');
				$(t).trigger('wpsc_settings_tab_loaded_' + t.current_tab);
			});
		},

		/**
		 * Mark the page as "unsaved" when a field is modified
		 * @since 3.8.8
		 */
		event_settings_changed : function() {
			t.unsaved_settings = true;
		},

		/**
		 * Display a confirm dialog when the user is trying to navigate
		 * away with unsaved settings
		 * @since 3.8.8
		 */
		event_before_unload : function() {
			if (t.unsaved_settings) {
				return t.before_unload_dialog;
			}
		},

		/**
		 * Load the settings tab when tab buttons are clicked
		 * @since 3.8.8
		 */
		event_tab_button_clicked : function() {
			var tab_id = $(this).data('tab-id');
			if (tab_id != t.current_tab) {
				t.load_tab(tab_id);
			}
			return false;
		},

		/**
		 * When back/forward browser button is clicked, load the correct tab
		 * @param {Object} e Event object
		 * @since 3.8.8
		 */
		event_pop_state : function(e) {
			if (e.state) {
				t.load_tab(e.state.tab_id, false);
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
		load_tab : function(tab_id, push_state) {
			if (t.unsaved_settings && ! confirm(t.ajax_navigate_confirm_dialog)) {
				return;
			}

			if (typeof push_state == 'undefined') {
				push_state = true;
			}

			var new_url = '?page=wpsc-settings&tab=' + tab_id;
			var post_data = {
				'action' : 'wpsc_navigate_settings_tab',
				'tab_id' : tab_id,
				'nonce'  : t.nonce,
				'current_url' : location.href
			};

			t.toggle_ajax_state(tab_id);

			// pushState to save this page load into history, and alter the address field of the browser
			if (push_state && history.pushState) {
				history.pushState({'tab_id' : tab_id}, '', new_url);
			}

			/**
			 * Replace the option tab content with the AJAX response, also change
			 * the action URL of the form and switch the active tab.
			 * @param  {String} response HTML response string
			 * @since 3.8.8
			 */
			var ajax_callback = function(response) {
				t.unsaved_settings = false;
				t.toggle_ajax_state(tab_id);
				$('#options_' + t.current_tab).replaceWith(response);
				t.current_tab = tab_id;
				$('.nav-tab-active').removeClass('nav-tab-active');
				$('[data-tab-id="' + tab_id + '"]').addClass('nav-tab-active');
				$('#wpsc_options_page form').attr('action', new_url);
				$(t).trigger('wpsc_settings_tab_loaded');
				$(t).trigger('wpsc_settings_tab_loaded_' + tab_id);
			}

			$.post(ajaxurl, post_data, ajax_callback, 'html');
		}
	});

	/**
	 * General tab
	 * @namespace
	 * @since 3.8.8
	 */
	var tg = WPSC_Settings_Tab_General = {
		/**
		 * Event binding for base country drop down
		 * @return {[type]}
		 * @since 3.8.8
		 */
		init : function() {
			var wrapper = $('#options_general');
			wrapper.delegate('#wpsc-base-country-drop-down', 'change', tg.event_base_country_changed).
			        delegate('.wpsc-select-all', 'click', tg.event_select_all).
			        delegate('.wpsc-select-none', 'click', tg.event_select_none);
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
				action  : 'wpsc_display_region_list',
				country : $('#wpsc-base-country-drop-down').val(),
				nonce   : t.nonce
			};

			var ajax_callback = function(response) {
				span.find('img').toggleClass('ajax-feedback-active');
				if (response !== '') {
					span.prepend(response);
				}
			};
			$.post(ajaxurl, postdata, ajax_callback, 'html');
		}
	};
	$(t).bind('wpsc_settings_tab_loaded_general', tg.init);

	/**
	 * Presentation tab
	 * @namespace
	 * @since 3.8.8
	 */
	var tpr = WPSC_Settings_Tab_Presentation = {
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
		init : function() {
			var wrapper = $('#options_presentation'), i;
			wrapper.delegate('#wpsc-show-images-only', 'click', tpr.event_show_images_only_clicked);
			wrapper.delegate('#' + tpr.grid_view_boxes.join(',#'), 'click', tpr.event_grid_view_boxes_clicked);
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
			if ($(this).is(':checked')) {
				for (i in tpr.grid_view_boxes) {
					document.getElementById(tpr.grid_view_boxes[i]).checked = false;
				}
			}
		}
	};
	$(t).bind('wpsc_settings_tab_loaded_presentation', tpr.init);

	/**
	 * Checkout Tab
	 * @namespace
	 * @since 3.8.8
	 */
	var tco = WPSC_Settings_Tab_Checkout = {
		/**
		 * Event binding for Checkout tab
		 * @since 3.8.8
		 */
		init : function() {
			var wrapper = $('#options_checkout');
			wrapper.delegate('.add_new_form_set', 'click', tco.event_add_new_form_set);
		},

		/**
		 * Toggle "Add New Form Set" field
		 * @since 3.8.8
		 */
		event_add_new_form_set : function() {
			jQuery(".add_new_form_set_forms").toggle();
				return false;
		}
	};
	$(t).bind('wpsc_settings_tab_loaded_checkout', tco.init);

	/**
	 * Taxes tab
	 * @namespace
	 * @since 3.8.8
	 */
	var tt = WPSC_Settings_Tab_Taxes = {
		/**
		 * Event binding for Taxes tab
		 * @since 3.8.8
		 */
		init : function() {
			var wrapper = $('#options_taxes');
			wrapper.delegate('#wpsc-add-tax-rates a', 'click', tt.event_add_tax_rate).
			        delegate('.wpsc-taxes-rates-delete', 'click', tt.event_delete_tax_rate).
			        delegate('#wpsc-add-tax-bands a', 'click', tt.event_add_tax_band).
			        delegate('.wpsc-taxes-bands-delete', 'click', tt.event_delete_tax_band).
			        delegate('.wpsc-taxes-country-drop-down', 'change', tt.event_country_drop_down_changed);
		},

		/**
		 * Load the region drop down via AJAX if the country has regions
		 * @since 3.8.8
		 */
		event_country_drop_down_changed : function() {
			var c = $(this),
			    post_data = {
					action            : 'wpec_taxes_ajax',
					wpec_taxes_action : 'wpec_taxes_get_regions',
					current_key       : c.data('key'),
					taxes_type        : c.data('type'),
					country_code      : c.val(),
					nonce             : t.nonce
				},
				spinner = c.siblings('.ajax-feedback'),
				ajax_callback = function(response) {
					spinner.toggleClass('ajax-feedback-active');
					if (response != '') {
						c.after(response);
					}
				};
			spinner.toggleClass('ajax-feedback-active');
			c.siblings('.wpsc-taxes-region-drop-down').remove();

			$.post(ajaxurl, post_data, ajax_callback, 'html');
		},

		/**
		 * Add new tax rate field when "Add Tax Rate" is clicked
		 * @since 3.8.8
		 * TODO: rewrote the horrible code in class wpec_taxes_controller. There's really no need for AJAX here.
		 */
		event_add_tax_rate : function() {
			tt.add_field('rates');
			return false;
		},

		/**
		 * Remove a tax rate row when "Delete" on that row is clicked.
		 * @since 3.8.8
		 */
		event_delete_tax_rate : function() {
			$(this).parents('.wpsc-tax-rates-row').remove();
			return false;
		},

		/**
		 * Add new tax band field when "Add Tax Band" is clicked
		 * @since 3.8.8
		 */
		event_add_tax_band : function() {
			tt.add_field('bands');
			return false;
		},

		event_delete_tax_band : function() {
			$(this).parents('.wpsc-tax-bands-row').remove();
			return false;
		},

		/**
		 * Add a field to the Tax Rate / Tax Band form, depending on the supplied type
		 * @param {String} Either "bands" or "rates" to specify the type of field
		 * @since 3.8.8
		 */
		add_field : function(type) {
			var button_wrapper = $('#wpsc-add-tax-' + type);
			    count = $('.wpsc-tax-' + type + '-row').size(),
			    post_data = {
			    	action            : 'wpec_taxes_ajax',
			    	wpec_taxes_action : 'wpec_taxes_build_' + type + '_form',
			    	current_key       : count,
			    	nonce             : t.nonce,
			    },
			    ajax_callback = function(response) {
			    	button_wrapper.before(response).find('img').toggleClass('ajax-feedback-active');
			    };

			button_wrapper.find('img').toggleClass('ajax-feedback-active');
			$.post(ajaxurl, post_data, ajax_callback, 'html');
		}
	}
	$(t).bind('wpsc_settings_tab_loaded_taxes', tt.init);

	var ts = WPSC_Settings_Tab_Shipping = {
		init : function() {
			var wrapper = $('#options_shipping');
			wrapper.delegate('.edit-shipping-module', 'click', ts.event_edit_shipping_module);
		},

		event_edit_shipping_module : function() {
			var element = $(this),
			    spinner = element.siblings('.ajax-feedback'),
			    post_data = {
			    	action : 'wpsc_shipping_module_settings_form',
			    	shipping_module_id : element.data('module-id'),
			    	nonce  : t.nonce
			    },
			    ajax_callback = function(response) {
			    	console.log(response);
			    	spinner.toggleClass('ajax-feedback-active');
			    	$('#wpsc-shipping-module-settings').replaceWith(response);
			    };

			spinner.toggleClass('ajax-feedback-active');
			$.post(ajaxurl, post_data, ajax_callback, 'html');
			return false;
		}
	};
	$(t).bind('wpsc_settings_tab_loaded_shipping', ts.init);
})(jQuery);

WPSC_Settings_Page.init();