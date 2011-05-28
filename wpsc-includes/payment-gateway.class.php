<?php

abstract class WPSC_Payment_Gateway
{
	/**
	 * Contain a key-value array of gateway names and gateway class names
	 *
	 * @access protected
	 * @static
	 * @var array
	 * @since 3.9
	 */
	protected static $gateways = array();
	
	/**
	 * Contain an array of payment gateway objects
	 *
	 * @access protected
	 * @static
	 * @var array
	 * @since 3.9
	 */
	protected static $instances = array();
	
	/**
	 * Object that allows manipulation of payment gateway settings in a consistent
	 * manner
	 *
	 * @access public
	 * @var string
	 */
	public $setting;
	
	/**
	 * Return a particular payment gateway object
	 *
	 * @access public
	 * @param string $gateway Name of the payment gateway you want to get
	 * @return object
	 * @since 3.9
	 */
	public static function &get( $gateway ) {
		if ( empty( self::$instances[$gateway] ) ) {
			$class_name = self::$gateways[$gateway];
			self::$instances[$gateway] = new $class_name();
			self::$instances[$gateway]->setting = new WPSC_Payment_Gateway_Setting( $gateway );
		}
		
		return self::$instances[$gateway];
	}
	
	/**
	 * Check to see whether a gateway is registered using this new API
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string $gateway Gateway name (derived from the filename without .php extension)
	 * @return bool True if it's already registered.
	 */
	public static function is_registered( $gateway ) {
		return ! empty( self::$gateways[$gateway] );
	}
	
	/**
	 * Automatically scan a directory for payment gateways and load the classes.
	 *
	 * The structure of this directory should follow the same rules of the wp-content/plugins
	 * structure.
	 *
	 * All of the files inside the directory will be assumed as payment gateway modules.
	 * If the directory has sub-folders, these sub-folders will be scanned as well, and
	 * files with the same
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
	 * The following files will be loaded as payment gateway modules: test-gateway-1.php,
	 * test-gateway-2.php, test-gateway-3/test-gateway-3.php
	 * See WPSC_Payment_Gateway::register_file() for file and class naming convention
	 *
	 * @access public
	 * @since 3.9
	 * @uses WPSC_Payment_Gateway::register_file()
	 *
	 * @param string $dir Path to the directory
	 * @param string $main_file File name of the class to load
	 * @return mixed Return true if successfully loaded all the payment gateway in
	 * the directory.
	 * Otherwise return a WP_Error object.
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
	 * The file name should be lowercase, using hyphens or underscores between words
	 * instead of spaces. The class name must have "WPSC_Payment_Gateway_" as the
	 * prefix, followed by the file name, in which words are capitalized and connected
	 * by underscore.
	 *
	 * For example, if the file name is "paypal-pro.php", then the class name inside
	 * the file must be WPSC_Payment_Gateway_Paypal_Pro.
	 *
	 * @access public
	 * @since 3.9
	 * @see WPSC_Payment_Gateway::register_dir()
	 *
	 * @param string $file Absolute path to the file containing the payment gateway
	 * class
	 * @return mixed Return true if the file is successfully included and contains
	 * a valid class. Otherwise, a WP_Error object is returned.
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
	 *
	 * Return an array containing registered gateway names.
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return array
	 */
	public static function get_gateways() {
		return array_keys( self::$gateways );
	}
	
	/**
	 * Return the title of the payment gateway. This method must be overridden by subclasses.
	 * It is recommended that the payment gateway title be properly localized using __()
	 *
	 * @abstract
	 * @access public
	 * @since 3.9
	 * @see __()
	 * 
	 * @return string
	 */
	abstract public function get_title();
	
	/**
	 * Display the payment gateway settings form as seen in WP e-Commerce Settings area.
	 * This method must be overridden by subclasses.
	 *
	 * @abstract
	 * @access public
	 * @since 3.9
	 * 
	 * @return void
	 */
	abstract public function setup_form();
	
	/**
	 * Returns the URL to the logo of the payment gateway (or any representative image).
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return mixed False if there's no image defined.
	 */
	public function get_image_url() {
		return false;
	}
	
	/**
	 * Payment gateway constructor. Cannot be called publicly. Must use WPSC_Payment_Gateway::get( $gateway_name ) instead.
	 *
	 * @access protected
	 * @return WPSC_Payment_Gateway
	 */
	protected function __construct() {
	}
}

class WPSC_Payment_Gateway_Setting
{
	/**
	 * Contain settings of the payment gateway
	 *
	 * @access private
	 * @var array
	 */
	private $settings;
	
	/**
	 * Contain unsaved settings of the payment gateway. This is useful when the saving of the settings
	 * are deferred.
	 *
	 * @access private
	 * @var array
	 */
	private $unsaved_settings = array();
	
	/**
	 * Name of the gateway
	 *
	 * @access private
	 * @var string
	 */
	private $gateway_name = '';
	
	/**
	 * Name of the option containing all the settings in WP DB
	 *
	 * @access private
	 * @var string
	 */
	private $option_name = '';
	
	/**
	 * Save settings when the payment gateway setup form is updated
	 *
	 * @access public
	 * @static
	 * @return void
	 * 
	 * @since 3.9
	 */
	public static function action_update_payment_gateway_settings() {
		if ( ! empty( $_POST['wpsc_payment_gateway_settings'] ) )
			foreach ( $_POST['wpsc_payment_gateway_settings'] as $gateway_name => $new_settings ) {
				$settings = new WPSC_Payment_Gateway_Setting( $gateway_name );
				$settings->merge( $new_settings );
			}
	}
	
	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 * @param string $gateway_name Name of the gateway
	 * @return WPSC_Payment_Gateway
	 */
	public function __construct( $gateway_name ) {
		$this->gateway_name = str_replace( array( ' ', '-' ), '_', $gateway_name );
		$this->option_name = 'wpsc_payment_gateway_' . $this->gateway_name;
	}
	
	/**
	 * Lazy load the settings from the DB when necessary
	 *
	 * @access private
	 * @return void
	 */
	private function lazy_load() {
		if ( is_null( $this->settings ) )
			$this->settings = get_option( $this->option_name, array() );
	}
	
	/**
	 * Get the value of a setting
	 *
	 * @param string $setting 
	 * @return mixed
	 * @since 3.9
	 */
	public function get( $setting, $default = false ) {
		$this->lazy_load();		
		return isset( $this->settings[$setting] ) ? $this->settings[$setting] : $default;
	}
	
	/**
	 * Set the value of a setting
	 *
	 * @param string $setting 
	 * @param mixed $value 
	 * @param bool $defer True if you want to defer saving the settings array to the database
	 * @return void
	 * @since 3.9
	 */
	public function set( $setting, $value, $defer = false ) {
		$this->lazy_load();
		$this->unsaved_settings[$setting] = $value;
		if ( ! $defer )
			$this->save();
	}
	
	/**
	 * Overwrite current settings with an array of settings
	 *
	 * @access public
	 * @param string $settings Settings that you want to overwrite upon current settings
	 * @param string $defer Optional. Defaults to false. True if you want to defer
	 *                      saving the settings array to the database.
	 * @return void
	 * @since 3.9
	 */
	public function merge( $settings, $defer = false ) {
		$this->lazy_load();
		$this->unsaved_settings = array_merge( $this->unsaved_settings, $settings );
		if ( ! $defer )
			$this->save();
	}
	
	/**
	 * Returns the field name of the setting on payment gateway setup form
	 *
	 * @access public
	 * @param string $setting Setting name
	 * @return string
	 * @since 3.9
	 */
	public function get_field_name( $setting ) {
		return "wpsc_payment_gateway_settings[{$this->gateway_name}][{$setting}]";
	}
	
	/**
	 * Save the settings into the database
	 *
	 * @return void
	 * @since 3.9
	 */
	public function save() {
		$this->settings = array_merge( $this->settings, $this->unsaved_settings );
		$this->unsaved_settings = array();
		update_option( $this->option_name, $this->settings );
	}
}

WPSC_Payment_Gateway::register_dir( WPSC_FILE_PATH . '/wpsc-payment-gateways' );
add_action( 'wpsc_update_payment_gateway_settings', array( 'WPSC_Payment_Gateway_Setting', 'action_update_payment_gateway_settings' ) );