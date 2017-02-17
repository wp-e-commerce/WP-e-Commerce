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
		$c.wrapper        = $( '#wpsc-payment-gateway-settings' );
		$c.spinner        = $c.wrapper.find( '.spinner' );
	};

	pro_pay.init = function() {

		pro_pay.cache();

		$c.wrapper.on( 'click', '.create-merchant-profile', pro_pay.create_merchant_profile );
		$c.body.on( 'wpsc-payment-gateway-settings-form-loaded',  pro_pay.init );
	};

	pro_pay.create_merchant_profile = function( e ) {
		e.preventDefault();

		var data = {
			action : 'propay_create_merchant_profile_id',
			nonce  : wpsc.merchant_profile_nonce
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

		$.post( ajaxurl, data, success, 'json' );
	};

	$( pro_pay.init );

} )( window, document, jQuery, window.WPSC_Pro_Pay, ajaxurl );
