/*global paypal, wpec_ppic */
/*
* Incontext Checkout for Express Checkout
*/
window.paypalCheckoutReady = function () {
	paypal.checkout.setup( wpec_ppic.mid, {
		environment: wpec_ppic.env,
		condition: function() {
			
			jQuery( '#express-checkout-cart-button-top' ).click(function(){
			  $(this).data( 'clicked', true );
			});
			
			jQuery( '#express-checkout-cart-button-bottom' ).click(function(){
			  $(this).data( 'clicked', true );
			});			
			
			
			if (
				jQuery( 'input[name="wpsc_payment_method"]:checked' ).val() === 'paypal-express-checkout' || 
				jQuery( 'input[name="custom_gateway"]:checked' ).val() === 'paypal-express-checkout' ||
				jQuery( '#express-checkout-cart-button-top').data('clicked') ||
				jQuery( '#express-checkout-cart-button-bottom').data('clicked') ) {
				
				return true;
			}
			return false;
		},
        buttons: [
            jQuery( '#wpsc_submit_checkout' )[0],
            jQuery( '.wpsc_buy_button' )[0],
            jQuery( '#express-checkout-cart-button-top' )[0],
            jQuery( '#express-checkout-cart-button-bottom' )[0]
        ]
	});
};
