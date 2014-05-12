<?php

function _wpsc_enable_theme_engine_v1( $components ) {
	$components['theme-engine']['core-v1'] = array(
		'title'    => __( 'WP e-Commerce Theme Engine v1', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v1/theme-engine-v1.php'
	);

	return $components;
}

function _wpsc_enable_theme_engine_v2( $components ) {
	$components['theme-engine']['core-v2'] = array(
		'title'    => __( 'WP e-Commerce Theme Engine v2', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v2/core.php'
	);

	return $components;
}

function _wpsc_maybe_activate_theme_engine_v2() {

	$activate = false;

	return apply_filters( '_wpsc_maybe_activate_theme_engine_v2', $activate );
}

function _wpsc_theme_engine_router( $components ) {

	if ( _wpsc_maybe_activate_theme_engine_v2() ) {
		return _wpsc_enable_theme_engine_v2( $components );
	} else {
		return _wpsc_enable_theme_engine_v1( $components );
	}
}

add_filter( 'wpsc_components', '_wpsc_theme_engine_router' );