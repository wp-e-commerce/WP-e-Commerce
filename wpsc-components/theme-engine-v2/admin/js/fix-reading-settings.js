;(function($){
	$(function() {
		var section = $('#front-static-pages'),
			radios;

		/**
		 * Enable / Disable dropdown based on which "show_on_front" option is
		 * selected
		 *
		 * @since  0.1
		 */
		var event_radio_change = function() {
			// disable all radio boxes first
			section.find('select').prop('disabled', true);

			// enable the dropdown menus associated with the selected radio box
			switch (radios.filter(':checked').attr('value')) {
				case 'page':
					$('#page_on_front, #page_for_posts').prop('disabled', false);
					break;

				case 'wpsc_main_store':
					$('#wpsc_page_for_posts').prop('disabled', false);
					break;

				default:
					break;
			}
		};

		/**
		 * Remove default event bindings for "show_on_front" radio boxes and
		 * use our own
		 *
		 * @since  0.1
		 */
		var bind_radio_events = function() {
			radios.off().on('change', event_radio_change);
			event_radio_change();
		};

		// the HTML for the field is prepared in _wpsc_te2_action_admin_enqueue_scripts()
		section.append(WPSC_Fix_Reading.html);
		radios = section.find('input:radio');

		// default handlers for the radio boxes are binded using inline JavaScript,
		// need to wait a bit in order to unbind them.
		setTimeout(bind_radio_events, 100);
	});
}(jQuery));