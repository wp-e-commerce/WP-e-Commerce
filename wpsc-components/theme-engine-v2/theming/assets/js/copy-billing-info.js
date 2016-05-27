;(function($){

	'use strict';

	var strings = window.WPSC.copyBilling.strings;

	var event_copy = function() {
		var fields_to_copy = [
			'firstname',
			'lastname',
			'address',
			'city',
			'country',
			'postcode',
			'state'
		];

		var index, shipping, billing, field, instance, value;

		for (index in fields_to_copy) {
			field = fields_to_copy[index];
			shipping = $('#wpsc-checkout-field-shipping' + field + '-control');
			billing = $('#wpsc-checkout-field-billing' + field + '-control');
			value = billing.val();
			shipping.val(value);
			if ( 'country' === field || 'state' === field ) {
				shipping.
					siblings('input.ui-autocomplete-input').
					val(shipping.find('option[value="' + value + '"]').text());

				if ( 'state' === field ) {
					continue;
				}

				instance = shipping.data('wpsc_country_field');
				instance.refresh_state_control({'real-value' : value});
			}
		}
	};

	var set_visibility = function() {

		if ( $( 'input[name="wpsc_copy_billing_details"]' ).is( ':checked' ) ) {
			$( '#wpsc-checkout-form-billing h2' ).html( strings.billing_and_shipping );
			$( '#wpsc-checkout-form-shipping' ).addClass( 'ui-helper-hidden' );
		} else {
			$( '#wpsc-checkout-form-billing h2' ).html( strings.billing );
			$( '#wpsc-checkout-form-shipping' ).removeClass( 'ui-helper-hidden' );
		}
	};

	$(function() {
		$( 'input[name="wpsc_copy_billing_details"]' ).on( 'click', event_copy );
		$( 'input[name="wpsc_copy_billing_details"]' ).on( 'click', set_visibility );
		$( document ).on( 'ready', set_visibility );
	});
})(jQuery);
