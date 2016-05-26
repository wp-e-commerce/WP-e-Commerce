<?php

add_action( 'wp_enqueue_scripts'        , '_wpsc_te2_enqueue_styles', 1 );

function _wpsc_te2_enqueue_styles() {
	wp_register_style( 'wpsc-common', wpsc_locate_asset_uri( 'css/common.css' ), array(), WPSC_VERSION );

	do_action( 'wpsc_register_styles' );

	wp_enqueue_style( 'wpsc-common' );

	if ( apply_filters( 'wpsc_add_inline_style', true ) ) {
		wp_add_inline_style( 'wpsc-common', _wpsc_get_inline_style() );
	}

function _wpsc_te2_enqueue_styles() {
	wp_register_style( 'wpsc-common', wpsc_locate_asset_uri( 'css/common.css' ), array(), WPSC_VERSION );
	wp_register_style( 'wpsc-responsive', wpsc_locate_asset_uri( 'css/wpsc-responsive.css' ), array(), WPSC_VERSION );
	do_action( 'wpsc_register_styles' );

	wpsc_enqueue_style( 'wpsc-common' );
	wp_enqueue_style( 'wpsc-responsive' );
	wpsc_add_inline_style( 'wpsc-common', _wpsc_get_inline_style() );

	do_action( 'wpsc_enqueue_styles' );
}

