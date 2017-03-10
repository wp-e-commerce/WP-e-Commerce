<?php

class WPSC_REST_API {

	public static function hooks() {
		add_filter( 'wpsc_register_post_types_products_args', array( __CLASS__, 'register_post_type_rest_args' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'includes' ), 999 );
	}

	public static function register_post_type_rest_args( $args ) {

		$args['show_in_rest']          = true;
		$args['rest_base']             = 'products';
		$args['rest_controller_class'] = 'WPSC_REST_Products_Controller';

		return $args;
	}

	public static function includes() {
		$dir = WPSC_FILE_PATH . '/wpsc-includes/rest-api/';

		// scan files in dir
		$files = scandir( $dir );

		foreach ( $files as $file ) {
			$path = $dir . $file;

			if ( pathinfo( $path, PATHINFO_EXTENSION ) != 'php' || in_array( $file, array( '.', '..' ) ) || is_dir( $path ) ) {
				continue;
			}


			require_once $path;

			$class_name = str_replace( array( '-', '.php' ), array( '_', '' ), $file );
			new $class_name();
		}
	}

}

add_action( 'plugins_loaded', 'WPSC_REST_API::hooks' );
