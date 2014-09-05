(function($) {

	$(window).load(function() {	
		var $checkout_btn = $( '.wpsc-checkout-form-button' ),
		$rd_btn = $( 'INPUT[value="paypal-digital-goods"]' ),
		$inputs = $( 'INPUT[type="radio"]' ),
		$form = $( '#wpsc-checkout-form' );

		$checkout_btn.attr( 'id', 'submitBtn' );


		$inputs.on( 'change', function() {
			if ( $rd_btn.is( ':checked' ) ) {
					var dg = new PAYPAL.apps.DGFlow( {
					trigger: 'submitBtn'
				} );
			} else {
				$form.attr( 'target', '' );
			}
		} );	
	} );

} )( jQuery );
