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
		var $checkout_btn = $( '#pp-ecs-dg' );

		// Inserts the Spinner
		if ( dg_loc && dg_loc.spinner_url ) {
			$checkout_btn.after( '<img src="' + dg_loc.spinner_url + '" class="dg-spinner" alt="spinner" />' );
			var $spinner = $( '.dg-spinner' );
			// Add some basic styling
			$spinner.css( {
				'vertical-align': 'middle',
				'padding-left': '15px'
			} );
			$checkout_btn.css( 'vertical-align', 'middle' );
			// Hide the Spinner
			$spinner.hide();
		} else {
			$spinner = $( '' ); // Avoids exceptions if the dg_loc is not loaded
		}

		// Submit button Click handler
		$checkout_btn.on( 'click', function() {	
				// Disable Submit button
				$checkout_btn.val( dg_loc.loading ).prop( 'disabled', true );

				// Show the Spinner
				$spinner.show();

				// Submit the FORM with AJAX
				$.ajax({
					url: $checkout_btn.attr( 'href' ),
					type: 'get',	
					success: function( url ) {
						// Start the DG flow
						var dg = new PAYPAL.apps.DGFlow({
							trigger: null
						});

						if ( utils.isValidPPURL( url ) ) {
							dg.startFlow( url );
						} else {
							if ( window.console ) {
								console.info( url );
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
