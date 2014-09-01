(function($) {
	$(window).load(function() {
		$( '.wpsc-checkout-form-button' ).attr( 'id', 'submitBtn' );
		var dg = new PAYPAL.apps.DGFlow({
			// the HTML ID of the form submit button which calls setEC
			trigger: 'submitBtn',
			// the experience type: instant or mini
			expType: 'instant'
		});
	});
})(jQuery);
