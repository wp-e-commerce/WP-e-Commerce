jQuery(function() {

	var authRequest;
	OffAmazonPayments.Button("pay_with_amazon", amazon_payments_advanced_params.seller_id, {
		type:  "PwA",
		color: "Gold",
		size:  "medium",
		useAmazonAddressBook: true,
		authorization: function() {
		  var loginOptions = {scope: 'profile payments:widget'};
		  authRequest = amazon.Login.authorize(loginOptions, amazon_payments_advanced_params.redirect );
		  console.log( authRequest );
		},
		onError: function(error) {
		  console.log(error);
		}
	});

	// Addressbook widget
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: amazon_payments_advanced_params.seller_id,
		amazonOrderReferenceId: amazon_payments_advanced_params.reference_id,
		onAddressSelect: function( orderReference ) {},
		design: {
            designMode: 'responsive'
        },
		onError: function(error) {}
	}).bind("amazon_addressbook_widget");

	// Wallet widget
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: amazon_payments_advanced_params.seller_id,
		amazonOrderReferenceId: amazon_payments_advanced_params.reference_id,
		design: {
            designMode: 'responsive'
        },
		onError: function(error) { console.log(error);}
	}).bind("amazon_wallet_widget");

});