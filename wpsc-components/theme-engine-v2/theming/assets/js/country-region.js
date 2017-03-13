(function ($) {

	var CountryField = function (elem, opt) {
		var defaults = {}, t = this;
		t.settings = $.extend(defaults, opt);
		t.element = $(elem);
		t.previous_cached_states = {};
		t.event_autocompletefocus = function (e, ui) {
			t.refresh_state_control(ui.item);
		};

		t.event_state_changed = function () {
			t.previous_cached_states[t.country_code] = $(this).val();
		};

		t.init();
	};

	CountryField.prototype.init = function () {
		var t = this;
		t.state_field = $(t.settings.state_field_selector);
		t.states = t.state_field.find('option').clone();
		t.state_field_prototype = t.state_field.clone().empty();
		t.state_field_parent = t.state_field.parent();
		t.element.selectToAutocomplete();
		t.text_input = t.element.siblings('input.ui-autocomplete-input');
		t.text_input.on('autocompletefocus', t.event_autocompletefocus);
		t.refresh_state_control();
	};

	CountryField.prototype.refresh_state_control = function (item) {
		var t = this, matched_states, state_text;
		if (item) {
			t.country_code = item['real-value'];
		} else {
			t.country_code = t.element.val();
			t.previous_cached_states[t.country_code] = t.state_field.val();
		}

		t.state_field_parent.empty();
		matched_states = t.states.filter('[data-country-isocode="' + t.country_code + '"]').clone();
		if (matched_states.size() === 0) {
			state_text = $('<input type="text">').attr({
				'id': t.state_field_prototype.attr('id'),
				'name': t.state_field_prototype.attr('name')
			});
			state_text.appendTo(t.state_field_parent);
			state_text.on('change', t.event_state_changed);
			if (typeof t.previous_cached_states[t.country_code] !== 'undefined') {
				state_text.val(t.previous_cached_states[t.country_code]);
			}
		} else {
			t.state_field = t.state_field_prototype.clone().append(matched_states);
			if (typeof t.previous_cached_states[t.country_code] !== 'undefined') {
				t.state_field.val(t.previous_cached_states[t.country_code]);
			}
			t.state_field.appendTo(t.state_field_parent).selectToAutocomplete();
			t.state_field.on('change', t.event_state_changed);
		}
	};

	$.fn.wpsc_country_field = function (opt) {
		return this.each(function () {
			$(this).data('wpsc_country_field', new CountryField(this, opt));
		});
	};

	$(function () {
		$('#wpsc-checkout-field-shippingstate-text, #wpsc-checkout-field-billingstate-text').remove();
		$('#wpsc-checkout-field-shippingstate, #wpsc-checkout-field-billingstate').show();
		$('#wpsc-checkout-field-billingcountry-control').wpsc_country_field({
			'state_field_selector': '#wpsc-checkout-field-billingstate-control'
		});

		$('#wpsc-checkout-field-shippingcountry-control').wpsc_country_field({
			'state_field_selector': '#wpsc-checkout-field-shippingstate-control'
		});

		if (window.chrome) {
			$('.ui-autocomplete-input').prop('autocomplete', 'false');
		}
	});
}(jQuery));
