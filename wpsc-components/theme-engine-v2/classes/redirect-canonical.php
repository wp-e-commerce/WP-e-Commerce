<?php

/**
 * Handles canonical redirects for all single product pages, shortcode pages, archive pages, taxonomy pages.
 */
class WPSC_Redirect_Canonical {
	private static $instance;

	public static function get_instance(){
		if ( is_null( $instance ) ) {
			self::$instance = new WPSC_Redirect_Canonical();
			self::$instance->init();
		}
	}

	private function init() {

	}

	public static register_redirect() {

	}

	public static deregister_redirect() {

	}
}

add_action( 'wpsc_init', 'WPSC_Redirect_Canonical::get_instance' );