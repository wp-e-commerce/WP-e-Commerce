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