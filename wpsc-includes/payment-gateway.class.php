<?php

abstract class WPSC_Payment_Gateway
{
	protected static $gateways = array();
	
	/**
	 * Automatically scan a directory for payment gateways and load the classes
	 *
	 * @param string $dir Path to the directory
	 * @param string $main_file File name of the class to load
	 * @return mixed Return true if successfully loaded all the payment gateway in the directory. Otherwise return a WP_Error object.
	 */
	public static function register_dir( $dir, $main_file = '' ) {
		// scan files in dir
		$files = scandir( $dir );
		
		foreach ( $files as $file ) {
			if ( in_array( $file, array( '.', '..' ) ) )
				continue;
				
			if ( ! $main_file )
				$main_file = $dir . '.php';
			
			$dir = trailingslashit( $dir );
			var_dump( $dir ); exit;
			if ( is_dir( $file ) )
				$return = self::register_dir( $dir . $file, $main_file );
			else
				$return = self::register_file( $dir . $main_file );
				
			return $return;
		}
	}
	
	public static function register_file( $file ) {
		echo '<pre>'; var_dump( $file ); echo '</pre>';
	}
	
	protected $params;
	
	public function __construct( $params = array() ) {
		if ( ! empty( $params ) )
			$this->set_params( $params );
	}
	
	abstract protected function set_params( $params = array() );
}