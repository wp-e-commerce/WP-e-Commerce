/*
* PayPal Pro script
*/
(function($) {
	$(window).load(function() {
		// Declare variables/doom elements
		var $checkout_btn = $( '.wpsc-checkout-form-button' ),
		$rd_btn = $( 'input[value="paypal-pro"]' ),
		$inputs = $( 'input[type="radio"]' ),
		$form = $( '#wpsc-checkout-form' ),
		$container = $( '.wpsc-payment-method' ),
		$hss_form;

		$checkout_btn.on( 'click', function() {
			if ( $rd_btn.is( ':checked' ) ) {
				// Empty the container
				$container.html( '' );
				// Disable FORM
				$form.on( 'submit', function() {
					return false;
				} );

				// Insert iFrame
				$container.html( '<iframe name="hss_iframe" width="570px" height="540px"></iframe>' );

				// Call the PayPal Pro API
				$.ajax({
					type: "POST",
					url: window.location,
					data: $form.serialize(),

					success: function(data) {
						$container.append( data );
						$hss_form = $( 'FORM', $container );
						$hss_form.attr( 'target', 'hss_iframe' );
						$hss_form.submit();
					}

				});
			}
		} );
	} );

} )( jQuery );
