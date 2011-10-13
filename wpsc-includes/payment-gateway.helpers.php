<?php

function wpsc_register_payment_gateway_dir( $dir ) {
	return WPSC_Payment_Gateway::register_dir( $dir );
}

/**
 * Register a payment gateway class with WP e-Commerce payment gateway API v3.0.
 * Helper function for WPSC_Payment_Gateway::register();
 *
 * @since 3.9
 */
function wpsc_register_payment_gateway( $class_name, $params = array(), $file = '' ) {
	return WPSC_Payment_Gateway::register( $class_name, $params, $file );
}