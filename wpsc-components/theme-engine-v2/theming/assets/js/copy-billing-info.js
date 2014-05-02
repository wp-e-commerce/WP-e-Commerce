;(function($){
	var event_copy = function(e) {
		var fields_to_copy = [
			'firstname',
			'lastname',
			'address',
			'city',
			'country',
			'postcode',
			'state'
		];
		var index, shipping, billing, field, instance;
		e.preventDefault();

		for (index in fields_to_copy) {
			field = fields_to_copy[index];
			shipping = $('#wpsc-checkout-field-shipping' + field + '-control');
			billing = $('#wpsc-checkout-field-billing' + field + '-control');
			value = billing.val();
			shipping.val(value);
			if (field == 'country' || field == 'state') {
				shipping.
					siblings('input.ui-autocomplete-input').
					val(shipping.find('option[value="' + value + '"]').text());

				if (field == 'state')
					continue;

				instance = shipping.data('wpsc_country_field');
				instance.refresh_state_control({'real-value' : value});
			}
		}
	};
	$(function() {
		$('input[name="wpsc_copy_billing_details"]').on('click', event_copy);
	});
})(jQuery);