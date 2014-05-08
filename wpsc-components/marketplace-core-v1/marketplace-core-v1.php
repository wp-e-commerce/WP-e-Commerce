<?php

add_action( 'wpsc_includes', 'wpsc_beta_marketplace_bootstrap', 12 );

function wpsc_beta_marketplace_bootstrap() {
	if ( ! class_exists( 'Sputnik' ) ) {
		require_once( dirname( __FILE__ ) . '/library/Sputnik.php' );
		Sputnik::$path = dirname( __FILE__ );
		Sputnik::bootstrap();
	}
}