/*global ajaxurl*/
window.WPSC_Pro_Pay_Checkout = window.WPSC_Pro_Pay_Checkout || {};

( function( window, document, $, wpsc, ajaxurl, undefined ) {
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

		if ( $c.tev1_wrapper.length ) {
			$c.wrapper = $c.tev1_wrapper;
			$c.v1      = true;
		} else {
			$c.wrapper = $c.tev2_wrapper;
		}

		$c.spinner = $c.wrapper.find( '.spinner' );

	};

	pro_pay.init = function() {

		pro_pay.cache();

		$c.wrapper.on( 'change', '.custom_gateway', pro_pay.create_payer_id );

	};

	pro_pay.create_payer_id = function() {

		var val = $( this ).val(), $first_name, $last_name, $email;

		if ( 'pro-pay' !== val ) {
			return;
		}

		if ( $c.v1 ) {
			$first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			$last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			$email      = $( 'input[data-wpsc-meta-key="billingemail"].text' ).val();
		} else {
			$first_name = $( 'input[data-wpsc-meta-key="billingfirstname"].text' ).val();
			$last_name  = $( 'input[data-wpsc-meta-key="billinglastname"].text' ).val();
			$email      = $( 'input[data-wpsc-meta-key="billingemail"].text' ).val();
		}

		if ( '' === $first_name || '' === $last_name || '' === $email ) {
			return;
		}

		$c.spinner.fadeIn().css( 'display', 'inline-block' );

		var data = {
			action : 'create_payer_id',
			nonce  : wpsc.checkout_nonce,
			name   : $first_name + ' ' + $last_name,
			email  : $email
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

	$( pro_pay.init );

} )( window, document, jQuery, window.WPSC_Pro_Pay_Checkout, ajaxurl );
