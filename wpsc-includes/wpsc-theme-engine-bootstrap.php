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
	return apply_filters( '_wpsc_maybe_activate_theme_engine_v2', false );
}

function _wpsc_theme_engine_router() {

	if ( _wpsc_maybe_activate_theme_engine_v2() ) {
		add_filter( 'wpsc_components', '_wpsc_enable_theme_engine_v2' );
	} else {
		add_filter( 'wpsc_components', '_wpsc_enable_theme_engine_v1' );
	}
}

add_action( 'wpsc_started', '_wpsc_theme_engine_router' );