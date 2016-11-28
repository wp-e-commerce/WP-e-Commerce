/*global paypal, wpec_ppic */
/*
* Incontext Checkout for Express Checkout
*/
window.paypalCheckoutReady = function () {
	paypal.checkout.setup( wpec_ppic.mid, {
		environment: wpec_ppic.env,
		condition: function() {
			return jQuery( 'input[name="wpsc_payment_method"]:checked' ).val() === 'paypal-express-checkout';
		},
		button: 'wpsc_submit_checkout'
	});
};