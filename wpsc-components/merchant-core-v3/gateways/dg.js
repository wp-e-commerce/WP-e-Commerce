/*
* PayPal for Digital Goods script
*/
(function($) {
	$(window).load(function() {	
		// Declare variables/doom elements
		var $checkout_btn = $( '.wpsc-checkout-form-button' ),
		$rd_btn = $( 'INPUT[value="paypal-digital-goods"]' ),
		$inputs = $( 'INPUT[type="radio"]' ),
		$form = $( '#wpsc-checkout-form' );

		// Change the id of the checkout button
		$checkout_btn.attr( 'id', 'submitBtn' );

		// Gateway selection changed
		$inputs.on( 'change', function() {
			if ( $rd_btn.is( ':checked' ) ) {
				// Starts the DG Flow
				var dg = new PAYPAL.apps.DGFlow( {
					trigger: 'submitBtn'
				} );
			} else {
				$form.attr( 'target', '' );
			}
		} );	
	} );

} )( jQuery );
