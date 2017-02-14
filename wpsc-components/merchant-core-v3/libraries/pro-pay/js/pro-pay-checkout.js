/*global hpp_Load, signalR_SubmitForm */
window.WPSC_Pro_Pay_Checkout = window.WPSC_Pro_Pay_Checkout || {};

( function( window, document, $, wpsc, undefined ) {
	'use strict';

	var $c = {};

	var pro_pay = {
		$ : $c
	};

	pro_pay.cache = function() {
		$c.body             = $( document.body );
		$c.tev1_wrapper     = $( 'form.wpsc_checkout_forms' );
		$c.tev2_wrapper     = $( '#wpsc-checkout-form' );
		$c.iframe           = $( '#pro_pay_iframe' );
		$c.v1               = $c.tev1_wrapper.length;
		$c.purchase_spinner = $( '.wpsc-purchase-loader-container' );

		if ( $c.v1 ) {
			$c.wrapper              = $c.tev1_wrapper;
			$c.buy_button_container = $( '.wpsc_make_purchase' );
			$c.buy_button           = $( '.wpsc_buy_button' );
		} else {
			$c.wrapper              = $c.tev2_wrapper;
			$c.buy_button_container = $( '.wpsc-form-actions' );
			$c.buy_button           = $( '.wpsc-field-wpsc_submit_checkout' );
		}

		pro_pay.purchase_spinner();
		pro_pay.payment_method_spinner();
		pro_pay.iframe();
	};

	pro_pay.iframe = function() {
		$c.iframe.insertBefore( $c.buy_button_container ).hide();
	};

	pro_pay.purchase_spinner = function() {

		if ( $c.v1 ) {
			$c.purchase_spinner.css( { 'position' : 'relative' } );
		} else {
			$c.buy_button_container.css( 'position', 'relative' );
		}

		$c.purchase_spinner.prependTo( $c.buy_button_container );
		$c.purchase_spinner.css( {
			'height'           : $c.buy_button.css( 'height' ),
			'width'            : $c.buy_button.css( 'width' ),
			'background-color' : $c.buy_button.css( 'background-color' ),
			'top'              : $c.buy_button_container.css( 'padding-top' )
		} );
	};

	pro_pay.payment_method_spinner = function() {
		$c.spinner = $c.wrapper.find( '.spinner' );
	};

	/**
	 * This works great in initial tev2 testing, but it's off in tev1.
	 * Needs some tweaking.
	 *
	 * @return {[type]} [description]
	 */
	pro_pay.toggle_purchase_spinner = function() {
		$c.buy_button.fadeToggle( 150 );
		$c.purchase_spinner.fadeToggle( 150 );
	};

	pro_pay.init = function() {

		pro_pay.cache();

		$c.wrapper.on( 'change', 'input[name="custom_gateway"], .wpsc-field-wpsc_payment_method input', pro_pay.create_payer_id );
		$( document ).on( 'ready'                                                                     , pro_pay.create_payer_id );
		$c.body.on( 'pro-pay-submission-success'                                                      , pro_pay.hosted_results );
		$c.body.on( 'pro-pay-connected'                                                               , pro_pay.toggle_iframe_class );
		$c.body.on( 'pro-pay-submission-error'                                                        , pro_pay.toggle_purchase_spinner );

	};

	pro_pay.toggle_iframe_class = function() {
		$c.iframe.toggleClass( 'loaded' );
	};

	pro_pay.get_customer_data = function() {

		var first_name,
		last_name,
		address,
		address1,
		address2,
		city,
		state,
		zip,
		country,
		email;

		if ( $c.v1 ) {
			email      = $( 'input[data-wpsc-meta-key="billingemail"].text' ).val();
			first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			address    = $( 'textarea[data-wpsc-meta-key="billingaddress"].text' ).val();
			address1   = address.split( "\n" )[0];
			address2   = address.split( "\n" )[1] || '';
			city       = $( 'input[data-wpsc-meta-key="billingcity"].text' ).val();
			state      = $( 'select[data-wpsc-meta-key="billingregion"]' ).val();
			zip        = $( 'input[data-wpsc-meta-key="billingpostcode"].text' ).val();
			country    = $( 'select[data-wpsc-meta-key="billingcountry"]' ).val();
		} else {
			email      = wpsc.checkout_data.billingemail;
			first_name = wpsc.checkout_data.billingfirstname;
			last_name  = wpsc.checkout_data.billinglastname;
			address    = wpsc.checkout_data.billingaddress;
			address1   = address.split( "\n" )[0];
			address2   = address.split( "\n" )[1] || '';
			city       = wpsc.checkout_data.billingcity;
			state      = wpsc.checkout_data.billingregion;
			zip        = wpsc.checkout_data.billingpostcode;
			country    = wpsc.checkout_data.billingcountry;
		}

		return {
			email : email,
			first_name : first_name,
			last_name : last_name,
			address1 : address1 ,
			address2 : address2,
			city : city,
			state : state,
			zip : zip,
			country : country
		};
	};

	pro_pay.create_payer_id = function() {
		var val = $( 'input[name="custom_gateway"]:checked, .wpsc-field-wpsc_payment_method input:checked' ).val();

		if ( $c.v1 && val == null ) {
			val = $( 'input[name="custom_gateway"]' ).val();
		}

		if ( 'pro-pay' !== val ) {
			$c.wrapper.off( 'submit', pro_pay.generate_hosted_id );
			$c.buy_button.prop( 'disabled', false );
			return;
		} else {
			$c.wrapper.on( 'submit', function() {
				pro_pay.toggle_purchase_spinner();
				pro_pay.generate_hosted_id();
				$c.buy_button.prop( 'disabled', true );
				return false;
			} );
		}


		if ( '' === pro_pay.get_customer_data().first_name || '' === pro_pay.get_customer_data().last_name || '' === pro_pay.get_customer_data().email ) {
			return;
		}

		$c.spinner.fadeIn().css( 'display', 'inline-block' );

		var data = {
			action : 'create_payer_id',
			nonce  : wpsc.checkout_nonce,
			name   : pro_pay.get_customer_data().first_name + ' ' + pro_pay.get_customer_data().last_name,
			email  : pro_pay.get_customer_data().email
		};

		var success = function(response) {
			if ( response.success ) {
				$c.spinner.fadeOut( 350 );
			} else {
				window.console.log( response );
			}
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );
	};

	pro_pay.generate_hosted_id = function() {

		var data = {
			action    : 'create_hosted_transaction_id',
			nonce     : wpsc.checkout_nonce,
			name      : pro_pay.get_customer_data().first_name + ' ' + pro_pay.get_customer_data().last_name,
			address1  : pro_pay.get_customer_data().address1,
			address2  : pro_pay.get_customer_data().address2,
			city      : pro_pay.get_customer_data().city,
			state     : pro_pay.get_customer_data().state,
			zip       : pro_pay.get_customer_data().zip,
			country   : pro_pay.get_customer_data().country
		};

		var success = function(response) {
			if ( response.success ) {
				pro_pay.toggle_purchase_spinner();
				$c.hosted_id = response.data.token;

				hpp_Load( response.data.token, wpsc.debug );

				$c.iframe.slideDown();

				$c.wrapper.off( 'submit' );

				$c.wrapper.on( 'submit', function( e ) {
					e.preventDefault();
					pro_pay.toggle_purchase_spinner();
					signalR_SubmitForm();
					return false;
				} );

			} else {
				window.console.log( response );
			}
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );

		return false;
	};

	pro_pay.hosted_results = function() {

		$c.iframe.slideUp();

		var data = {
			action    : 'create_hosted_results',
			nonce     : wpsc.checkout_nonce,
			hosted_id : $c.hosted_id
		};

		var success = function(response) {
			if ( response.success && 'SUCCESS' === response.data.results.response.Result.ResultValue ) {
				var transaction = response.data.results.response.HostedTransaction;

				$c.wrapper.off( 'submit' );
				$c.wrapper.append( '<input id="pro-pay-payment-method-token" type="hidden" name="pro_pay_payment_method_token" />' );
				$c.wrapper.append( '<input id="pro-pay-transaction-id" type="hidden" name="pro_pay_transaction_id" />' );
				$c.wrapper.append( '<input id="pro-pay-acct-number" type="hidden" name="pro_pay_obfs_acct_number" />' );
				$c.wrapper.append( '<input id="pro-pay-type" type="hidden" name="pro_pay_card_type" />' );
				$( '#pro-pay-payment-method-token' ).val( transaction.PaymentMethodInfo.PaymentMethodID );
				$( '#pro-pay-transaction-id' ).val( transaction.TransactionHistoryId );
				$( '#pro-pay-acct-number' ).val( transaction.PaymentMethodInfo.ObfuscatedAccountNumber );
				$( '#pro-pay-type' ).val( transaction.PaymentMethodInfo.PaymentMethodType );

				$c.wrapper.submit();
			} else {
				pro_pay.toggle_purchase_spinner();
				window.console.log( response );
			}
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );

		return false;
	};

	wpsc.enable_buy_buttons = function() {
		$c.buy_button.prop( 'disabled', false );
	};

	wpsc.toggle_purchase_spinner = pro_pay.toggle_purchase_spinner;

	$( pro_pay.init );

} )( window, document, jQuery, window.WPSC_Pro_Pay_Checkout );

function formIsReadyToSubmit() {
	window.WPSC_Pro_Pay_Checkout.enable_buy_buttons();
}
