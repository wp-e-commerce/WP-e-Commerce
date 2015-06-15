jQuery(function($) {

	if ( $( '#pay_with_amazon' ).length > 0 ) {
		var authRequest;
		OffAmazonPayments.Button("pay_with_amazon", amazon_payments_advanced_params.seller_id, {
			type:  "PwA",
			color: "Gold",
			size:  "small",
			useAmazonAddressBook: true,
			authorization: function() {
				var loginOptions = {scope: 'profile payments:widget'};
				authRequest = amazon.Login.authorize(loginOptions, amazon_payments_advanced_params.redirect );
			},
			onError: function(error) {}
		});
	}

	// Addressbook widget
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: amazon_payments_advanced_params.seller_id,
		onOrderReferenceCreate: function(orderReference) {
			$( '.wpsc-checkout-shipping-and-billing input.wpsc-field-wpsc_submit_checkout' ).prop( 'disabled', true );
			$( 'input[name="amazon_reference_id"]' ).val( orderReference.getAmazonOrderReferenceId() )
		},
		design: {
			designMode: 'responsive'
		},
		onError: function(error) {}
	}).bind("amazon_addressbook_widget");

	// Wallet widget
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: amazon_payments_advanced_params.seller_id,
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect : function( orderReference ) {
			$( '.wpsc-checkout-shipping-and-billing input.wpsc-field-wpsc_submit_checkout' ).prop( 'disabled', false );
		},
		onError: function(error) {}
	}).bind("amazon_wallet_widget");
});