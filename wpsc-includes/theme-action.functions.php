<?php

function wpsc_theme_product_header_before( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_header_before', $id );
}

function wpsc_theme_product_header_after( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_header_after', $id );
}

function wpsc_theme_add_to_cart_fields_before( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_add_product_to_cart_form_before', $id );
}

function wpsc_theme_add_to_cart_fields_after( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_add_product_to_cart_form_after', $id );
}

function wpsc_theme_product_add_to_cart_actions_before( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_add_to_cart_actions_before', $id );
}

function wpsc_theme_product_add_to_cart_actions_after( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_add_to_cart_actions_after', $id );
}

function wpsc_theme_product_breadcrumb_before( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_breadcrumb_before' );
}

function wpsc_theme_product_breadcrumb_after( $id = 0 ) {
	if ( ! $id )
		$id = wpsc_get_product_id();
	do_action( 'wpsc_theme_product_breadcrumb_after' );
}