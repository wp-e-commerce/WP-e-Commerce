jQuery(function($) {

	if ( $( '#pay_with_amazon' ).length > 0 ) {
		var authRequest;
		OffAmazonPayments.Button("pay_with_amazon", amazon_payments_advanced_params.seller_id, {
			type:  amazon_payments_advanced_params.pwa_type,
			color: amazon_payments_advanced_params.pwa_color,
			size:  amazon_payments_advanced_params.pwa_size,
			useAmazonAddressBook: true,
			authorization: function() {
				var loginOptions = { scope: 'profile payments:widget' };
				authRequest = amazon.Login.authorize(loginOptions, amazon_payments_advanced_params.redirect );
			},
			design: {
				designMode: 'responsive'
			},
			onError: function(error) {}
		});
	}

	var addressBookArgs, walletArgs;

	addressBookArgs = {
		sellerId: amazon_payments_advanced_params.seller_id,
		design: {
			designMode: 'responsive'
		},
		onOrderReferenceCreate : function( orderReference ) {
			$( '.wpsc-checkout-shipping-and-billing input.wpsc-field-wpsc_submit_checkout' ).prop( 'disabled', true );
			$( 'input[name="amazon_reference_id"]' ).val( orderReference.getAmazonOrderReferenceId() );
		},
		onError: function(error) {
			jQuery( '.wpsc-checkout-form-button' ).prepend( '<div class="errors"><p class="wpsc-alert-error" id="wpsc-alert-error-"' + error.getErrorCode() + '>' + error.getErrorMessage() + '</p></div>' );
 		}
	};

	walletArgs = {
		sellerId: amazon_payments_advanced_params.seller_id,
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect : function( orderReference ) {
			$( '.wpsc-checkout-shipping-and-billing input.wpsc-field-wpsc_submit_checkout' ).prop( 'disabled', false );
		},
		onError: function(error) {
			jQuery( '.wpsc-checkout-form-button' ).prepend( '<div class="errors"><p class="wpsc-alert-error" id="wpsc-alert-error-"' + error.getErrorCode() + '>' + error.getErrorMessage() + '</p></div>' );
 		}
	}

	if ( $( 'body' ).hasClass( 'wpsc-controller-payment' ) ) {
		addressBookArgs.displayMode = walletArgs.displayMode = "Read";
		$( '.wpsc-order-preview' ).before( '<div id="amazon_addressbook_widget"></div><div id="amazon_wallet_widget"></div>' )
	}

	// Addressbook widget
	new OffAmazonPayments.Widgets.AddressBook( addressBookArgs ).bind( 'amazon_addressbook_widget' );

	// Wallet widget
	new OffAmazonPayments.Widgets.Wallet( walletArgs ).bind( 'amazon_wallet_widget' );
});