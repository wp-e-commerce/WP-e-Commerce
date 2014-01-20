/* globals jQuery, WPSC_Settings_Page */
;(function($) {
	'use strict';
	var phantom = false;
	var event_change_size = function(e) {
		var t = $(this);
		var text = '';
		if (! phantom) {
			phantom = $('<span></span>');
			phantom.css({
				'position'       : 'absolute',
				'top'            : '-9999em',
				'left'           : '-9999em',
				'font-size'      : t.css('font-size'),
				'font-family'    : t.css('font-family'),
				'letter-spacing' : t.css('letter-spacing'),
				'word-spacing'   : t.css('word-spacing')
			});
			$('body').append(phantom);
		}
		text = t.val();
		if (e) {
			text += String.fromCharCode(e.which);
		}
		text = text.replace(/\s/g, '&nbsp;');
		text += '&nbsp;';

		phantom.html(text);
		t.width(phantom.width());
	};

	var event_sanitize = function() {
		var t = $(this),
			val = t.val().toLowerCase(),
			orig_val = val;

		val = val.replace(/\s+/g, '-');
		val = val.replace(/[^A-Za-z0-9\-_]+/g, '');
		val = val.replace(/([^A-Za-z0-9])[^A-Za-z0-9]+/g, '$1');
		val = encodeURIComponent(val);
		t.val(val);

		if (orig_val.length != val.length)
			event_change_size.call(this);
	};

	var event_blur = function() {
		var t = $(this);

		if (! $.trim(t.val())) {
			t.val(t.data('original_value'));
			event_change_size.call(this);
		}
	};

	$(WPSC_Settings_Page).on( 'wpsc_settings_tab_loaded_pages', function() {
		var selector = '#wpsc-settings-form input[id$="-slug"]';
		$('body').on('keypress', selector, event_change_size);
		$('body').on('keyup', selector, event_sanitize).
			on('blur', selector, event_blur);
		$(selector).each(function() {
			var t = $(this);
			t.data('original_value', t.val());
			event_change_size.call(this);
		});
	});
}(jQuery));