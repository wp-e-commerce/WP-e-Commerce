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
var WPSC_Settings_Tab_General;

(function($){
	// abbreviate WPSC_Settings_Page to 't'
	var t = WPSC_Settings_Page;

	$.extend(t, /** @lends WPSC_Settings_Page */ {
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
					history.replaceState({tab_id : t.current_tab}, '', location.search);
				})();
			}

			// load the correct settings tab when back/forward browser button is used
			$(window).bind('popstate', t.event_pop_state);

			$(function(){
				$('#wpsc_options').delegate('a.nav-tab', 'click', t.event_tab_button_clicked);
				$(t).trigger('wpsc_settings_tab_loaded');
				$(t).trigger('wpsc_settings_tab_loaded_' + t.current_tab);
			});
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
		 * Use AJAX to load a tab to the settings page
		 * @param  {String} tab_id The ID string of the tab
		 * @param  {Boolean} push_state True (Default) if we need to history.pushState.
		 *                           False if this is a result of back/forward browser button being pushed.
		 * @since 3.8.8
		 */
		load_tab : function(tab_id, push_state) {
			if (typeof push_state == 'undefined') {
				push_state = true;
			}

			var new_url = $.query.set('tab', tab_id).toString();
			var post_data = {
				'action' : 'wpsc_navigate_settings_tab',
				'tab_id' : tab_id,
				'nonce'  : t.nonce,
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
	 */
	var tg = WPSC_Settings_Tab_General = {
		init : function() {
			$('#options_general').delegate('#wpsc-base-country-drop-down', 'change', tg.event_base_country_changed);
		},

		event_base_country_changed : function() {
			var span = $('#wpsc-base-region-drop-down');
			span.find('select').remove();
			span.find('img').toggleClass('ajax-feedback');

			var postdata = {
				action  : 'wpsc_display_region_list',
				country : $('#wpsc-base-country-drop-down').val(),
				nonce   : t.nonce
			};

			var ajax_callback = function(response) {
				span.find('img').toggleClass('ajax-feedback');
				if (response !== '') {
					span.prepend(response);
				}
			};
			$.post(ajaxurl, postdata, ajax_callback, 'html');
		}
	};
	$(t).bind('wpsc_settings_tab_loaded_general', tg.init);
})(jQuery);

WPSC_Settings_Page.init();