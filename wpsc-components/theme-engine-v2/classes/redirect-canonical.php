<?php

/**
 * Handles canonical redirects for all single product pages, shortcode pages, archive pages, taxonomy pages.
 */
class WPSC_Redirect_Canonical {
	private static $instance;

	public static function get_instance(){
		if ( is_null( self::$instance ) ) {
			self::$instance = new WPSC_Redirect_Canonical();
			self::$instance->init();
		}
	}

	private function init() {

		add_action( 'redirect_canonical', array( self::$instance, 'set_redirects' ), 20, 2 );
	}

	public static function register_redirect() {

	}

	public static function deregister_redirect() {

	}

	public function set_redirects( $redirect_url, $requested_url ) {

	}
}

add_action( 'wpsc_init', 'WPSC_Redirect_Canonical::get_instance' );