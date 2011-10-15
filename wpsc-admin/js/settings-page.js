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

(function($){
	// abbreviate WPSC_Settings_Page to 't'
	var t = WPSC_Settings_Page;

	$.extend(t, /** @lends WPSC_Settings_Page */ {
		/**
		 * Event binding for WPSC_Settings_Page
		 * @since [3.8.8]
		 */
		init : function() {
			// make sure the event object contains the 'state' property
			$.event.props.push('state');

			// set the history state of the current page
			if (history.replaceState) {
				(function(){
					history.replaceState({tab_id : t.current_tab});
				})();
			}

			// load the correct settings tab when back/forward browser button is used
			$(window).bind('popstate', t.pop_state_handler);

			// load the settings tab when clicking on navigation links
			$('#wpsc_options a.nav-tab').live('click', function(){
				var tab_id = $(this).data('tab-id');
				t.load_tab(tab_id);
				return false;
			});
		},

		/**
		 * When back/forward browser button is clicked, load the correct tab
		 * @param {Object} e Event object
		 * @since 3.8.8
		 */
		pop_state_handler : function(e) {
			if (e.state) {
				t.load_tab(e.state.tab_id, false);
			}
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
				$('#options_' + t.current_tab).replaceWith(response);
				t.current_tab = tab_id;
				$('.nav-tab-active').removeClass('nav-tab-active');
				$('[data-tab-id="' + tab_id + '"]').addClass('nav-tab-active');
				$('#wpsc_options_page form').attr('action', new_url);
			}

			$.post(ajaxurl, post_data, ajax_callback, 'html');
		}
	});

	t.init();
})(jQuery);