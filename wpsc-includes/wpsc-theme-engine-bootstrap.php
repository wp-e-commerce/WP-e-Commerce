<?php

function _wpsc_theme_engine_v2_has_old_templates() {
	$current_theme  = trailingslashit( get_stylesheet_directory() );
	$theme_files    = scandir( $current_theme );
	$wpsc_files     = array();

	foreach ( $theme_files as $file ) {
		if ( 'wpsc-' === substr( $file, 0, 5 ) && is_file( $current_theme . $file ) ) {
			$wpsc_files[] = $file;
		}
	}

	return ! empty( $wpsc_files );
}

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

	global $wp_rewrite;

	if ( ! is_a( $wp_rewrite, 'WP_Rewrite' ) ) {
		$wp_rewrite = new WP_Rewrite();
	}

	if ( ! $wp_rewrite->using_permalinks() ) {
		$activate = false;
	}

	if ( $wp_rewrite->using_index_permalinks() ) {
		$activate = false;
	}

	if ( function_exists( '_wpsc_gc_init' ) ) {
		$activate = false;
	}

	if ( _wpsc_theme_engine_v2_has_old_templates() ) {
		$activate = false;
	}

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