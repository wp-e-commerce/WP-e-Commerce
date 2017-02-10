/*global hpp_Load, signalR_SubmitForm */
window.WPSC_Pro_Pay_Checkout = window.WPSC_Pro_Pay_Checkout || {};

( function( window, document, $, wpsc, undefined ) {
	'use strict';

	var $c = {};

	var pro_pay = {
		$ : $c
	};

	pro_pay.cache = function() {
		$c.body           = $( document.body );

		$c.tev1_wrapper   = $( 'form.wpsc_checkout_forms' );
		$c.tev2_wrapper   = $( '#gateway_settings_pro-pay_form' );
		$c.v1             = false;
		$c.iframe         = $( '#pro_pay_iframe' );

		if ( $c.tev1_wrapper.length ) {
			$c.wrapper = $c.tev1_wrapper;
			$c.v1      = true;
			$c.iframe.insertBefore( $( '.wpsc_make_purchase' ) ).hide();
			$c.buy_button = $( '.wpsc_buy_button' );
		} else {
			$c.wrapper = $c.tev2_wrapper;
		}

		$c.spinner = $c.wrapper.find( '.spinner' );

	};

	pro_pay.init = function() {

		pro_pay.cache();

		$c.wrapper.on( 'change', '.custom_gateway', pro_pay.create_payer_id );
		$c.body.on( 'pro-pay-submission-success'  , pro_pay.hosted_results );

	};

	pro_pay.create_payer_id = function() {

		var val = $( this ).val(),
		first_name,
		last_name,
		email;

		if ( 'pro-pay' !== val ) {
			$c.wrapper.off( 'submit', pro_pay.generate_hosted_id );
			$c.buy_button.prop( 'disabled', false );
			return;
		} else {
			$c.wrapper.on( 'submit', function() {
				pro_pay.generate_hosted_id();
				$c.buy_button.prop( 'disabled', true );
				return false;
			} );
		}

		if ( $c.v1 ) {
			first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			email      = $( 'input[data-wpsc-meta-key="billingemail"].text' ).val();
		} else {
			first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			email      = $( 'input[data-wpsc-meta-key="billingemail"].text' ).val();
		}

		if ( '' === first_name || '' === last_name || '' === email ) {
			return;
		}

		$c.spinner.fadeIn().css( 'display', 'inline-block' );

		var data = {
			action : 'create_payer_id',
			nonce  : wpsc.checkout_nonce,
			name   : first_name + ' ' + last_name,
			email  : email
		};

		var success = function(response) {
			if ( response.success ) {
				window.console.log( response );
				$c.spinner.fadeOut( 350 );
			} else {
				window.console.log( response );
			}
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );
	};

	pro_pay.generate_hosted_id = function() {
		var first_name,
		last_name,
		address,
		address1,
		address2,
		city,
		state,
		zip,
		country;

		if ( $c.v1 ) {
			first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			address   = $( 'textarea[data-wpsc-meta-key="billingaddress"].text' ).val();
			address1   = address.split( "\n" )[0];
			address2   = address.split( "\n" )[1] || '';
			city       = $( 'input[data-wpsc-meta-key="billingcity"].text' ).val();
			state      = $( 'select[data-wpsc-meta-key="billingregion"]' ).val();
			zip        = $( 'input[data-wpsc-meta-key="billingpostcode"].text' ).val();
			country    = $( 'select[data-wpsc-meta-key="billingcountry"]' ).val();
		} else {
			first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			address   = $( 'textarea[data-wpsc-meta-key="billingaddress"].text' ).val();
			address1   = address.split( "\n" )[0];
			address2   = address.split( "\n" )[1] || '';
			city       = $( 'input[data-wpsc-meta-key="billingcity"].text' ).val();
			state      = $( 'select[data-wpsc-meta-key="billingregion"]' ).val();
			zip        = $( 'input[data-wpsc-meta-key="billingpostcode"].text' ).val();
			country    = $( 'select[data-wpsc-meta-key="billingcountry"]' ).val();
		}

		$c.spinner.fadeIn().css( 'display', 'inline-block' );

		var data = {
			action    : 'create_hosted_transaction_id',
			nonce     : wpsc.checkout_nonce,
			name      : first_name + ' ' + last_name,
			address1  : address1,
			address2  : address2,
			city      : city,
			state     : state,
			zip       : zip,
			country   : country
		};

		var success = function(response) {
			if ( response.success ) {
				window.console.log( response );
				$c.spinner.fadeOut( 350 );
				$c.hosted_id = response.data.token;

				hpp_Load( response.data.token, wpsc.debug );

				$c.iframe.slideDown();

				$c.wrapper.off( 'submit' );

				$c.wrapper.on( 'submit', function( e ) {
					e.preventDefault();
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
		window.console.log( 'hosted results' );

		$c.iframe.slideUp();

		$c.spinner.fadeIn().css( 'display', 'inline-block' );

		var data = {
			action    : 'create_hosted_results',
			nonce     : wpsc.checkout_nonce,
			hosted_id : $c.hosted_id
		};

		var success = function(response) {
			if ( response.success && 'SUCCESS' === response.data.results.response.Result.ResultValue ) {
				var transaction = response.data.results.response.HostedTransaction;

				$c.spinner.fadeOut( 350 );
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
				window.console.log( response );
			}
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );

		return false;
	};

	$( pro_pay.init );

} )( window, document, jQuery, window.WPSC_Pro_Pay_Checkout );

function formIsReadyToSubmit() {
	jQuery( '.wpsc_buy_button' ).prop( 'disabled', false );
}
