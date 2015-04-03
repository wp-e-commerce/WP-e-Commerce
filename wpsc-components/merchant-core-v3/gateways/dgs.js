/*globals jQuery, dg_loc */

/*
* PayPal for Digital Goods script
*/
(function($) {
	var utils = {
		isValidPPURL: function( url ) {
			return url.substring( 0, 4 ) === 'http';
		}
	};
	$( window ).load( function() {
		// Declare DOM elements
		var $checkout_btn = $( '#pp-ecs-dg' ), $spinner = $( '' ); // Avoids exceptions if the dg_loc is not loaded;

		// Inserts the Spinner
		if ( window.dg_loc && window.dg_loc.spinner_url ) {
			$checkout_btn.after( '<img src="' + dg_loc.spinner_url + '" class="dg-spinner" alt="spinner" />' );
			$spinner = $( '.dg-spinner' );
			// Add some basic styling
			$spinner.css( {
				'vertical-align': 'middle',
				'padding-left': '15px'
			} );
			$checkout_btn.css( 'vertical-align', 'middle' );
			// Hide the Spinner
			$spinner.hide();
		}

		// Submit button Click handler
		$checkout_btn.on( 'click', function() {
				// Disable Submit button
				$checkout_btn.val( window.dg_loc.loading ).prop( 'disabled', true );

				// Show the Spinner
				$spinner.show();

				// Submit the FORM with AJAX
				$.ajax({
					url: $checkout_btn.attr( 'href' ),
					type: 'get',
					success: function( url ) {
						// Start the DG flow
						var dg = new window.PAYPAL.apps.DGFlow({
							trigger: null
						});

						if ( utils.isValidPPURL( url ) ) {
							dg.startFlow( url );
						} else {
							if ( window.console ) {
								window.console.info( url );
							}
						}
						// Hide the Spinner
						$spinner.hide();
					}
				});
				return false;
		} );
	} );
} )( jQuery );