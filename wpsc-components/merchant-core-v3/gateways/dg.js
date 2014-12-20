/*
* PayPal for Digital Goods script
*/
;
(function($) {
	$(window).load(function() {	
		// Declare variables/doom elements
		var $checkout_btn = $( '.wpsc-checkout-form-button' ),
		$rd_btn = $( 'input[value="paypal-digital-goods"]' ),
		$form = $( '#wpsc-checkout-form' );

		// Change the id of the checkout button
		$checkout_btn.attr( 'id', 'submitBtn' );

		// Disable Form submission if DG is selected
		$form.on ('submit', function() {
			if ( $rd_btn.is( ':checked' ) ) {
				return false;			
			}
		} );

		// Submit button Click handler
		$checkout_btn.on( 'click', function() {
			// If DG is selected
			if ( $rd_btn.is( ':checked' ) ) {
				// Disable Submit button
				$checkout_btn.val( 'loading...' ).attr( 'disabled', 'on' );
				// Submit the FORM with AJAX
				$.ajax({
					url: '',
					type: 'post',
					data: $form.serialize(),
					success: function( url ) {
						// Start the DG flow
						var dg = new PAYPAL.apps.DGFlow({
							trigger: null
						});
						dg.startFlow( url );
					}
				});
				return false;
			}
		} );
	} );
} )( jQuery );
