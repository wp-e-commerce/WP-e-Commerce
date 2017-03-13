<?php

class WPSC_REST_API {

	/**
	 * Core types are controllers that extend core functionality.
	 * This would include custom post types and taxonomies.
	 *
	 * We don't want to load these ourselves, as core handles that for us.
	 * @var array
	 */
	protected static $core_types = array(
		'wpsc-rest-categories-controller.php',
		'wpsc-rest-products-controller.php',
		'wpsc-rest-tags-controller.php',
		'wpsc-rest-variations-controller.php',
	);

	public static function hooks() {
		add_filter( 'wpsc_register_post_types_products_args', array( __CLASS__, 'register_post_type_rest_args' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 999 );
	}

	public static function register_post_type_rest_args( $args ) {

		$args['show_in_rest']          = true;
		$args['rest_base']             = 'products';
		$args['rest_controller_class'] = 'WPSC_REST_Products_Controller';

		return $args;
	}

	public static function register_routes() {
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

			$controller = new $class_name();

			$controller->register_routes();
		}
	}

}

add_action( 'plugins_loaded', 'WPSC_REST_API::hooks' );