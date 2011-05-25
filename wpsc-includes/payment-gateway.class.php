<?php

abstract class WPSC_Payment_Gateway
{
	protected static $gateways = array();
	protected static $instances = array();
	
	/**
	 * Return a particular payment gateway object
	 *
	 * @param string $gateway Name of the payment gateway you want to get
	 * @return object
	 * @since 3.9
	 */
	public static function &get( $gateway ) {
		if ( empty( self::$instances[$gateway] ) ) {
			$class_name = self::$gateways[$gateway];
			self::$instances[$gateway] = new $class_name();
		}
		
		return self::$instances[$gateway];
	}
	
	/**
	 * Check to see whether a gateway is registered using this new API
	 *
	 * @param string $gateway Gateway name (derived from the filename without .php extension)
	 * @return bool True if it's already registered.
	 * @since 3.9
	 */
	public static function is_registered( $gateway ) {
		return ! empty( self::$gateways[$gateway] );
	}
	
	/**
	 * Automatically scan a directory for payment gateways and load the classes.
	 *
	 * The structure of this directory should follow the same rules of the wp-content/plugins structure.
	 *
	 * All of the files inside the directory will be assumed as payment gateway modules.
	 * If the directory has sub-folders, these sub-folders will be scanned as well, and files with the same
	 * name as those sub-folders will be included as payment gateway modules.
	 *
	 * For example, if we have the following directory structure:
	 * payment-gateways/
	 * |-- test-gateway-1.php
	 * |-- test-gateway-2.php
	 * |-- test-gateway-3/
	 *     |-- test-gateway-3.php
	 *     |-- functions.php
	 *
	 * The following files will be loaded as payment gateway modules: test-gateway-1.php, test-gateway-2.php, test-gateway-3/test-gateway-3.php
	 * See WPSC_Payment_Gateway::register_file() for file and class naming convention
	 *
	 * @param string $dir Path to the directory
	 * @param string $main_file File name of the class to load
	 * @return mixed Return true if successfully loaded all the payment gateway in the directory. Otherwise return a WP_Error object.
	 * @since 3.9
	 * @uses WPSC_Payment_Gateway::register_file()
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
	
	/**
	 * Include a file as a payment gateway module.
	 *
	 * The payment gateway inside the file must be defined as a subclass of WPSC_Payment_Gateway. 
	 * 
	 * The file name should be lowercase, using hyphens or underscores between words instead of spaces. The class name must
	 * have "WPSC_Payment_Gateway_" as the prefix, followed by the file name, in which words are capitalized and connected by underscore.
	 *
	 * For example, if the file name is "paypal-pro.php", then the class name inside the file must be WPSC_Payment_Gateway_Paypal_Pro.
	 *
	 * @param string $file Absolute path to the file containing the payment gateway class
	 * @return mixed Return true if the file is successfully included and contains a valid class. Otherwise, a WP_Error object is returned.
	 * @since 3.9
	 * @see WPSC_Payment_Gateway::register_dir()
	 */
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
	
	/**
	 * Return an array containing registered gateway names.
	 *
	 * @return array
	 * @since 3.9
	 */
	public static function get_gateways() {
		return array_keys( self::$gateways );
	}
	
	/**
	 * Return the title of the payment gateway. This method must be overridden by subclasses.
	 * It is recommended that the payment gateway title be properly localized using __()
	 *
	 * @return string
	 * @since 3.9
	 * @see __()
	 */
	abstract public function get_title();
	
	/**
	 * Display the payment gateway settings form as seen in WP e-Commerce Settings area.
	 * This method must be overridden by subclasses.
	 *
	 * @return void
	 * @since 3.9
	 */
	abstract public function setup_form();
		
	public function __construct() {
	}
}

WPSC_Payment_Gateway::register_dir( WPSC_FILE_PATH . '/wpsc-payment-gateways' );