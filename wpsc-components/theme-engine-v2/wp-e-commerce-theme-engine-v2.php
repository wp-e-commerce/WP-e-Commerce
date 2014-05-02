<?php
/**
  * Plugin Name: WP e-Commerce Theme Engine v2
  * Plugin URI: http://getshopped.org/
  * Description: Them engine v2 to use with WP e-Commerce 3.8.11+
  * Version: 0.1
  * Author: Instinct Entertainment
  * Author URI: http://getshopped.org/
  **/

add_filter( 'wpsc_components', '_wpsc_te2_register_component', 20 );

function _wpsc_te2_register_component( $components ) {
	$components['theme-engine'] = array(
		'core-v2' => array(
			'title' => __( 'WP e-Commerce Theme Engine v2', 'wpsc' ),
				'includes' =>
					dirname( __FILE__ ) . '/core.php'
		)
	);
	return $components;
}

function _wpsc_te_v2_activate() {
	$path = dirname( __FILE__ );
	require_once( $path . '/core.php' );
	_wpsc_te_v2_includes();
	wpsc_register_post_types();
	flush_rewrite_rules( true );
	update_option( 'transact_url', wpsc_get_checkout_url( 'results' ) );
	WPSC_Settings::get_instance();
	do_action( 'wpsc_te2_activate' );
}
register_activation_hook( __FILE__, '_wpsc_te_v2_activate' );