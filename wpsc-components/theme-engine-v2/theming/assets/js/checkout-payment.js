;(function($) {
	"use strict";
	var toggle_extra_form = function() {
		var value = this.value;
		var form = $('.wpsc-payment-gateway-extra-form-' + value);
		if (form.size() === 0)
			return;

		if (this.checked)
			form[0].style.display = '';
		else
			form[0].style.display = 'none';
	};
	$(function() {
		var inputs = $('input[name="wpsc_payment_method"]');
		var toggle_forms = function() {
			inputs.each(toggle_extra_form);
		};
		inputs.on('change', toggle_forms);
		toggle_forms();
	});
})(jQuery);