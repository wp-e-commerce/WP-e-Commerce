<?php

function wpsc_register_payment_gateway_dir( $dir ) {
	return WPSC_Payment_Gateways::register_dir( $dir );
}

function wpsc_register_payment_gateway_file( $file ) {
	return WPSC_Payment_Gateways::register_file( $file );
}

function wpsc_is_payment_gateway_registered( $gateway ) {
	return WPSC_Payment_Gateways::is_registered( $gateway );
}

function wpsc_get_payment_gateway( $gateway ) {
	return WPSC_Payment_Gateways::get( $gateway );
}

function wpsc_payment_gateway_supports( $gateway, $supports ) {

	$supports = false;
	$gateway  = wpsc_get_payment_gateway( $gateway );

	if ( is_subclass_of( $gateway, 'WPSC_Payment_Gateway' ) ) {
		$supports = $gateway->supports( $supports );
	}

	return $supports;
}
