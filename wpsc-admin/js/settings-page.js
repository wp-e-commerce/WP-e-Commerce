(function($){
	if (history.replaceState) {
		(function(){
			history.replaceState({tab_id : WPSC_Settings_Page.current_tab});
		})();
	}

	$.event.props.push('state');

	var pop_state_handler = function(e) {
		if (e.state) {
			load_tab(e.state.tab_id, false);
		}
	}

	$(window).bind('popstate', pop_state_handler);

	var load_tab = function(tab_id, push_state) {
		if (typeof push_state == 'undefined') {
			push_state = true;
		}

		var post_data = {
			'action' : 'wpsc_navigate_settings_tab',
			'tab_id' : tab_id,
			'nonce'  : WPSC_Settings_Page.nonce,
		};

		var ajax_callback = function(response) {
			var new_url = $.query.set('tab', tab_id).toString();
			if (push_state && history.pushState) {
				history.pushState({'tab_id' : tab_id}, '', new_url);
			}

			$('#options_' + WPSC_Settings_Page.current_tab).replaceWith(response);
			WPSC_Settings_Page.current_tab = tab_id;
			$('.nav-tab-active').removeClass('nav-tab-active');
			$('[data-tab-id="' + tab_id + '"]').addClass('nav-tab-active');
			$('#wpsc_options_page form').attr('action', new_url);
		};

		$.post(ajaxurl, post_data, ajax_callback, 'html');
	};

	$('#wpsc_options a.nav-tab').live('click', function(){
		var tab_id = $(this).data('tab-id');
		load_tab(tab_id);
		return false;
	});
})(jQuery);