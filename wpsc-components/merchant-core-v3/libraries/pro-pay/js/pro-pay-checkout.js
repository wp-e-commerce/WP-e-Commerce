/*global ajaxurl*/
window.WPSC_Pro_Pay = window.WPSC_Pro_Pay || {};

( function( window, document, $, wpsc, ajaxurl, undefined ) {
	'use strict';

	var $c = {};

	var pro_pay = {
		$ : $c
	};

	pro_pay.cache = function() {
		$c.body           = $( document.body );
		$c.tev1_wrapper   = $( '#gateway_settings_pro-pay_form' );
		$c.tev2_wrapper   = $( '#gateway_settings_pro-pay_form' );
		$c.spinner        = $c.wrapper.find( '.spinner' );
	};

	pro_pay.init = function() {

		pro_pay.cache();

		if ( $c.tev1_wrapper.length ) {
			$c.wrapper.on( 'click', '.custom-gateway', pro_pay.create_payer_id );
		}

		if ( $c.tev2_wrapper.length ) {
			$c.wrapper.on( 'click', '.payment-method', pro_pay.create_payer_id );
		}

	};

	pro_pay.create_merchant_profile = function( e ) {
		e.preventDefault();

		var data = {
			action : 'create_payer_id',
			nonce  : wpsc.checkout_nonce
		};

		$c.spinner.css( 'visibility', 'visible' );

		var success = function(response) {
			if ( response.success ) {
				$( '#wpsc-pro-pay-merchant-profile-id' ).val( response.data.profile_id );
				$( '#wpsc-propay-merchant-profile-create' ).html( '<p>' + wpsc.profile_id_success_text + '</p>' );
			} else {
				$( '#wpsc-propay-merchant-profile-create' ).html( '<p>' + wpsc.profile_id_error_text + '</p>' );
			}
			$c.spinner.fadeOut( 350 );
		};

		$.post( wpsc.ajaxurl, data, success, 'json' );

		return false;
	};

	$( pro_pay.init );

} )( window, document, jQuery, window.WPSC_Pro_Pay, ajaxurl );
