jQuery(function($) {

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
		onError: function(error) {
			console.log(error);
		}
	});

	// Addressbook widget
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: amazon_payments_advanced_params.seller_id,
		amazonOrderReferenceId: amazon_payments_advanced_params.reference_id,
		onOrderReferenceCreate: function(orderReference) {
			$( 'input[name="amazon_reference_id"]' ).val( orderReference.getAmazonOrderReferenceId() )
			console.log( orderReference.getAmazonOrderReferenceId() );
		},
		design: {
			designMode: 'responsive'
		},
		onError: function(error) {}
	}).bind("amazon_addressbook_widget");

	// Wallet widget
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: amazon_payments_advanced_params.seller_id,
		onOrderReferenceCreate: function(orderReference) {
			$( 'input[name="amazon_reference_id"]' ).val( orderReference.getAmazonOrderReferenceId() );
			console.log( orderReference.getAmazonOrderReferenceId() );
		},
		design: {
			designMode: 'responsive'
		},
		onError: function(error) { console.log(error);}
	}).bind("amazon_wallet_widget");
});