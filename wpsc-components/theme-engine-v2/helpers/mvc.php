<?php

define( 'WPSC_TE_V2_MVC_PATH'           , WPSC_THEME_ENGINE_V2_PATH . '/mvc' );
define( 'WPSC_TE_V2_CONTROLLERS_PATH'   , WPSC_TE_V2_MVC_PATH . '/controllers' );
define( 'WPSC_TE_V2_TEMPLATE_PARTS_PATH', WPSC_TE_V2_THEMING_PATH . '/template-parts' );
define( 'WPSC_TE_V2_ASSETS_PATH'        , WPSC_TE_V2_THEMING_PATH . '/assets' );

function _wpsc_te2_mvc_init() {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/router.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-engine.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/conditional-tags.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/message-collection.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/product.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/url.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/general.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/form.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/taxonomy.php' );

	WPSC_Router::get_instance();

	add_filter( 'get_edit_post_link', '_wpsc_filter_edit_post_link', 10, 2 );

	do_action( 'wpsc_mvc_init' );
}

function _wpsc_filter_edit_post_link( $link, $id ) {
	if ( ! $id ) {
		return false;
	}

	return $link;
}

function _wpsc_filter_rewrite_controller_slugs( $rules ) {
	$slugs     = wpsc_get_page_slugs();
	$new_rules = array();

	foreach ( $slugs as $page_name => $slug ) {
		$controller_name                  = sanitize_title_with_dashes( $page_name );
		$new_rules[ "($slug)(/.+?)?/?$" ] = 'index.php?wpsc_controller=' . $controller_name . '&wpsc_controller_args=$matches[2]';
	}

	$rules = array_merge( $new_rules, $rules );

	return $rules;
}

function _wpsc_load_controller( $controller, $method ) {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/controller.php' );

	$controller_class = str_replace( '-', ' ', $controller );
	$controller_class = ucwords( $controller_class );

	$controller_class = apply_filters( 'wpsc_load_controller_class_' . $method, 'WPSC_Controller_' . str_replace( ' ', '_', $controller_class ), $controller );

	$controller_path = apply_filters( 'wpsc_load_controller_path_' . $method, WPSC_TE_V2_CONTROLLERS_PATH . "/{$controller}.php", $controller, $controller_class );

	if ( file_exists( $controller_path ) ) {
		require_once( $controller_path );
		return new $controller_class();
	} else {
		trigger_error( 'Undefined controller: ' . $controller_class, E_USER_ERROR );
	}
}

function _wpsc_get_current_controller_name() {
	$router = WPSC_Router::get_instance();
	return $router->controller_name;
}

function _wpsc_get_current_controller() {
	$router = WPSC_Router::get_instance();
	return $router->controller;
}

function _wpsc_get_current_controller_method() {
	$router = WPSC_Router::get_instance();
	return $router->controller_method;
}

function _wpsc_get_current_controller_args() {
	$router = WPSC_Router::get_instance();
	return $router->controller_args;
}

function _wpsc_get_current_controller_slug() {
	$router = WPSC_Router::get_instance();
	return $router->controller_slug;
}