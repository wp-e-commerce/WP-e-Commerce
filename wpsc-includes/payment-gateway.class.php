<?php

abstract class WPSC_Payment_Gateway
{
	protected static $gateways = array();
	
	public static function is_registered( $gateway ) {
		return ! empty( self::$gateways[$gateway] );
	}
	
	/**
	 * Automatically scan a directory for payment gateways and load the classes
	 *
	 * @param string $dir Path to the directory
	 * @param string $main_file File name of the class to load
	 * @return mixed Return true if successfully loaded all the payment gateway in the directory. Otherwise return a WP_Error object.
	 */
	public static function register_dir( $dir, $main_file = '' ) {
		$dir = trailingslashit( $dir );

		$main_file = basename( $dir ) . '.php';
		if ( file_exists( $dir . $main_file ) )
			return self::register_file( $dir . $main_file );
		
		// scan files in dir
		$files = scandir( $dir );
		
		foreach ( $files as $file ) {
			if ( in_array( $file, array( '.', '..' ) ) )
				continue;

			$path = $dir . $file;
			$return = is_dir( $path ) ? self::register_dir( $path ) : self::register_file( $path );
			if ( $return instanceof WP_Error )
				return $return;
		}
		
		return true;
	}
	
	public static function register_file( $file ) {
		require_once( $file );
		$filename = basename( $file, '.php' );
		$classname = ucwords( str_replace( '-', ' ', $filename ) );
		$classname = 'WPSC_Payment_Gateway_' . str_replace( ' ', '_', $classname );
		
		if ( ! class_exists( $classname ) )
			return new WP_Error( 'wpsc_invalid_payment_gateway', __( 'Invalid payment gateway file.' ) );
		
		self::$gateways[$filename] = $classname;
		
		return true;
	}
	
	public static function get_gateways() {
		return self::$gateways;
	}
	
	abstract public static function get_title();
	abstract public static function setup_form();
	
	protected $params;
	
	public function __construct( $params = array() ) {
		if ( ! empty( $params ) )
			$this->set_params( $params );
	}
}

WPSC_Payment_Gateway::register_dir( WPSC_FILE_PATH . '/wpsc-payment-gateways' );