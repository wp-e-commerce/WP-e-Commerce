;(function($) {
	'use strict';
	var toggle_extra_form = function() {
		var value = this.value;
		var form = $('.wpsc-payment-gateway-extra-form-' + value + ', #' + value + '-cc-form' );

		if ( 0 === form.size() ) {
			return;
		}

		if (this.checked) {
			form[0].style.display = 'block';
		} else {
			form[0].style.display = 'none';
		}
	};
	$(function() {
		var inputs = $('input[name="wpsc_payment_method"]');
		var toggle_forms = function() {
			inputs.each(toggle_extra_form);
		};
		inputs.on('change', toggle_forms);
		toggle_forms();
		$( '.wpsc-credit-card-form-card-number' ).payment('formatCardNumber');
		$( '.wpsc-credit-card-form-card-expiry' ).payment('formatCardExpiry');
		$( '.wpsc-credit-card-form-card-cvc' ).payment('formatCardCVC');
	});
})(jQuery);
