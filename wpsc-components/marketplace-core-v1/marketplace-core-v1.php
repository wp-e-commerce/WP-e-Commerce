<?php

if ( ! empty( $_GET['wpsc_enable_marketplace'] ) )
	update_option( '_wpsc_enable_marketplace', true );

if ( ! empty( $_GET['wpsc_disable_marketplace'] ) )
	update_option( '_wpsc_enable_marketplace', false );

add_action( 'wpsc_includes', 'wpec_beta_marketplace_bootstrap', 12 );

function wpec_beta_marketplace_bootstrap() {
	if ( ! get_option( '_wpsc_enable_marketplace', false ) )
		return;

	if ( ! class_exists( 'Sputnik' ) ) {
		require_once( dirname( __FILE__ ) . '/library/Sputnik.php' );
		Sputnik::$path = dirname( __FILE__ );
		Sputnik::bootstrap();
	}
}