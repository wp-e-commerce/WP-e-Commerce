(function($) {

	$(window).load(function() {	
		var $checkout_btn = $( '.wpsc-checkout-form-button' ),
		$rd_btn = $( 'INPUT[value="paypal-digital-goods"]' );

		$checkout_btn.attr( 'id', 'submitBtn' );

		$checkout_btn.on( 'click', function() {
			if ( $rd_btn.is(':checked') ) {
				var dg = new PAYPAL.apps.DGFlow({
					trigger: 'submitBtn',	
					expType: ''
				});
			}
		});
	});

})(jQuery);
