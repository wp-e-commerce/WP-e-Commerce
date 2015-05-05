jQuery(function() {

	// Login Widget
	new OffAmazonPayments.Widgets.Button ({
		sellerId: amazon_payments_advanced_params.seller_id,
		useAmazonAddressBook: amazon_payments_advanced_params.is_checkout_pay_page ? false : true,
		onSignIn: function( orderReference ) {
			amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
			window.location = amazon_payments_advanced_params.redirect + '&amazon_reference_id=' + amazonOrderReferenceId;
		},
		onError: function(error) {}
	}).bind("pay_with_amazon");

	// Addressbook widget
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: amazon_payments_advanced_params.seller_id,
		amazonOrderReferenceId: amazon_payments_advanced_params.reference_id,
		onAddressSelect: function( orderReference ) {
			jQuery('body').trigger('update_checkout');
		},
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
		onError: function(error) {}
	}).bind("amazon_wallet_widget");

});