<?php

add_action( 'wp_enqueue_scripts', '_wpsc_te2_register_scripts', 1 );

function _wpsc_te2_register_scripts() {
	wp_register_script(
		'wpsc-select-autocomplete',
		wpsc_locate_asset_uri( 'js/jquery.select-to-autocomplete.js' ),
		array( 'jquery-ui-autocomplete' ),
		'1.0.5'
	);
	wp_register_script(
		'wpsc-country-region',
		wpsc_locate_asset_uri( 'js/country-region.js' ),
		array( 'wpsc-select-autocomplete', 'jquery' ),
		WPSC_VERSION
	);
	wp_register_script(
		'wpsc-copy-billing-info',
		wpsc_locate_asset_uri( 'js/copy-billing-info.js' ),
		array( 'jquery' ),
		WPSC_VERSION
	);
	wp_register_script(
		'wpsc-shipping-price-simulator',
		wpsc_locate_asset_uri( 'js/shipping-price-simulator.js' ),
		array( 'jquery' ),
		WPSC_VERSION
	);
	wp_register_script(
		'wpsc-checkout-payment',
		wpsc_locate_asset_uri( 'js/checkout-payment.js' ),
		array( 'jquery' ),
		WPSC_VERSION
	);

	do_action( 'wpsc_register_scripts' );
	do_action( 'wpsc_enqueue_scripts' );
}

function _wpsc_enqueue_shipping_billing_scripts() {
	add_action(
		'wp_enqueue_scripts',
		'_wpsc_action_enqueue_shipping_billing_scripts'
	);
}

function _wpsc_action_enqueue_shipping_billing_scripts() {
	wp_enqueue_script( 'wpsc-country-region' );
	wp_enqueue_script( 'wpsc-copy-billing-info' );

	wp_localize_script( 'wpsc-copy-billing-info', 'wpsc_checkout_labels', array(
		'billing_and_shipping' => apply_filters( 'wpsc_checkout_billing_header_label'     , __( '<h2>Billing &amp; Shipping Details</h2>', 'wp-e-commerce' ) ),
		'shipping'             => apply_filters( 'wpsc_checkout_shipping_header_label'    , __( '<h2>Shipping Details</h2>', 'wp-e-commerce' ) ),
		'billing'              => apply_filters( 'wpsc_checkout_billing_only_header_label', __( '<h2>Billing Details</h2>', 'wp-e-commerce' ) ),
	) );
}