<?php

add_action( 'wp_head', 'wpsc_product_list_rss_feed' );

if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == "rss") ) {
	add_action( 'template_redirect', 'wpsc_product_rss', 80 );
}

function wpsc_product_rss() {
	global $wp_query, $wpsc_query, $_wpsc_is_in_custom_loop;
	list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
	$_wpsc_is_in_custom_loop = true;
	header( "Content-Type: application/xml; charset=UTF-8" );
	header( 'Content-Disposition: inline; filename="E-Commerce_Product_List.rss"' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/rss/rss.php' );
	list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
	$_wpsc_is_in_custom_loop = false;
	exit();
}

function wpsc_product_list_rss_feed() {
	$rss_url = get_option('siteurl');
	$rss_url = add_query_arg( 'wpsc_action', 'rss', $rss_url );
	$rss_url = str_replace('&', '&amp;', $rss_url);
	$rss_url = esc_url( $rss_url ); // URL santization - IMPORTANT!

	echo "<link rel='alternate' type='application/rss+xml' title='" . get_option( 'blogname' ) . " Product List RSS' href='{$rss_url}'/>";
}
