/*global jQuery, pro_loc */
(function($) {
	$(window).load(function() {
		// Declare variables/DOM elements
		var $checkout_btn = $( '.wpsc-checkout-form-button, .wpsc_buy_button' ),
		$rd_btn           = $( 'input[value="paypal-pro"]' ),
		$inputs           = $( 'input[type="radio"]' ),
		$form             = $( '#wpsc-checkout-form, .wpsc_checkout_forms' ),
		$container        = $( '.wpsc-payment-method, .wpsc_gateway_container' ),
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
				$container.html( '<iframe id="pro-iframe" name="hss_iframe" width="570px" height="540px"></iframe>' );

				// Insert the Spinner
				$container.parents( '.wpsc-checkout-review' ).find( 'strong.wpsc-large' ).eq( -1 ).after( '<img src="' + pro_loc.spinner_url + '" class="pro-spinner" alt="spinner" />' );
				var $spinner = $( '.pro-spinner' );

				// Call the PayPal Pro API
				$.ajax( {
					type: "POST",
					url: window.location,
					data: $form.serialize() + '&custom_gateway=paypal-pro',

					success: function(data) {
						data = data.replace(/\\/g, '');
						$container.append( data );
						$hss_form = $( 'FORM', $container );
						$hss_form.attr( 'target', 'hss_iframe' );
						$hss_form.find( 'input[type="image"]' ).click();

						// Remove the Spinner

						// Show the IFRAME
						$( '#pro-iframe' ).show();

						document.getElementById( 'pro-iframe' ).onload = function() {
							$spinner.hide();
						}
					}
				} );
			}
		} );
	} );

} )( jQuery );
