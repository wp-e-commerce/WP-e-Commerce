/*global paypal, wpec_ppic */
/*
* Incontext Checkout for Express Checkout
*/
window.paypalCheckoutReady = function () {
	paypal.checkout.setup( wpec_ppic.mid, {
		environment: wpec_ppic.env,
		condition: function() {
			if( jQuery( 'input[name="wpsc_payment_method"]:checked' ).val() === 'paypal-express-checkout' || jQuery(".express-checkout-cart-button").click ) {
				return true;
			}
			return false;
		},
		button: [ 'wpsc_submit_checkout', 'express-checkout-cart-button-top', 'express-checkout-cart-button-bottom' ]
	});
};
