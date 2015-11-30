<?php

final class WPSC_Payment_Gateways {

	/**
	 * Contain a key-value array of gateway names and gateway class names
	 *
	 * @access private
	 * @static
	 * @var array
	 * @since 3.9
	 */
	private static $gateways = array();

	/**
	 * Contain an array of payment gateway objects
	 *
	 * @access private
	 * @static
	 * @var array
	 * @since 3.9
	 */
	private static $instances = array();

	/**
	 * Contains the cached metadata of the registered payment gateways, so that the
	 * plugin doesn't have to load the gateway's files to determine its metadata
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.9
	 *
	 * @var array
	 */
	private static $payment_gateway_cache = array();

	/**
	 * Contains the names of active gateways that use this API
	 *
	 * @access private
	 * @static
	 * @since 3.9
	 *
	 * @var array
	 */
	private static $active_gateways = array();

	/**
	 * Return a particular payment gateway object
	 *
	 * @access public
	 * @param string $gateway Name of the payment gateway you want to get
	 * @return object
	 * @since 3.9
	 */
	public static function &get( $gateway, $meta = false ) {

		if ( empty( self::$instances[ $gateway ] ) ) {

			if ( ! $meta ) {
				$meta = self::$gateways[ $gateway ];
			}

			if ( ! file_exists( $meta['path'] ) ) {
				WPSC_Payment_Gateways::flush_cache();
			}

			require_once( $meta['path'] );

			$class_name = $meta['class'];

			$options = array(
				'http_client' => new WPSC_Payment_Gateway_HTTP(),
			);

			if ( ! class_exists( $class_name ) ) {
				$error = new WP_Error( 'wpsc_invalid_payment_gateway', sprintf( __( 'Invalid payment gateway: Class %s does not exist.', 'wp-e-commerce' ), $class_name ) );
				return $error;
			}

			self::$instances[ $gateway ] = new $class_name( $options );
		}

		return self::$instances[ $gateway ];
	}

	public static function init() {

		add_action( 'wpsc_submit_gateway_options', array( 'WPSC_Payment_Gateway_Setting', 'action_update_payment_gateway_settings' ) );

		if ( ! defined( 'WPSC_PAYMENT_GATEWAY_DEBUG' ) || WPSC_PAYMENT_GATEWAY_DEBUG == false ) {
			add_action( 'init', array( 'WPSC_Payment_Gateways', 'action_save_payment_gateway_cache' ), 99 );
		 } else {
			WPSC_Payment_Gateways::flush_cache();
		 }

		WPSC_Payment_Gateways::register_dir( WPSC_MERCHANT_V3_PATH . '/gateways' );

		// Call the Active Gateways init function
		self::initialize_gateways();

		if ( isset( $_REQUEST['payment_gateway'] ) && isset( $_REQUEST['payment_gateway_callback'] ) ) {
			add_action( 'init', array( 'WPSC_Payment_Gateways', 'action_process_callbacks' ) );
		}
	}

	public static function action_process_callbacks() {
		$gateway = self::get( $_REQUEST['payment_gateway'] );
		$function_name = "callback_{$_REQUEST['payment_gateway_callback']}";
		$callback = array( $gateway, $function_name );

		if ( is_callable( $callback ) ) {
			$gateway->$function_name();
		}
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
		return ! empty( self::$gateways[ $gateway ] );
	}

	/**
	 * Automatically scan a directory for payment gateways and load the classes.
	 *
	 * The structure of this directory should follow the same rules of the wp-content/plugins
	 * structure.
	 *
	 * All of the files inside the directory will be assumed as payment gateway modules.
	 * Files with the same name as those sub-folders will be included as payment
	 * gateway modules.
	 *
	 * For example, if we have the following directory structure:
	 * payment-gateways/
	 * |-- test-gateway-1.php
	 * |-- test-gateway-2.php
	 * |-- some-folder/
	 *     |-- class.php
	 *     |-- functions.php
	 *
	 * The following files will be loaded as payment gateway modules: test-gateway-1.php,
	 * test-gateway-2.php
	 * See WPSC_Payment_Gateways::register_file() for file and class naming convention
	 *
	 * @access public
	 * @since 3.9
	 * @uses WPSC_Payment_Gateways::register_file()
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

		// scan files in dir
		$files = scandir( $dir );

		if ( in_array( $main_file, $files ) ) {
			return self::register_file( $dir . $main_file );
		}

		foreach ( $files as $file ) {
			$path = $dir . $file;

			if ( pathinfo( $path, PATHINFO_EXTENSION ) != 'php' || in_array( $file, array( '.', '..' ) ) || is_dir( $path ) ) {
				continue;
			}

			$return = self::register_file( $path );

			if ( is_wp_error( $return ) ) {
				return $return;
			}
		}
	}

	/**
	 * Register a file as a payment gateway module.
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
	 * @see WPSC_Payment_Gateways::register_dir()
	 *
	 * @param string $file Absolute path to the file containing the payment gateway
	 * class
	 * @return mixed Return true if the file is successfully included and contains
	 * a valid class. Otherwise, a WP_Error object is returned.
	 */
	public static function register_file( $file ) {

		if ( empty( self::$payment_gateway_cache ) ) {
			self::$payment_gateway_cache = get_option( 'wpsc_payment_gateway_cache', array() );
		}

		$filename = basename( $file, '.php' );

		// payment gateway already exists in cache
		if ( isset( self::$payment_gateway_cache[ $filename ] ) ) {
			self::$gateways[ $filename ] = self::$payment_gateway_cache[ $filename ];
		}

		// if payment gateway is not in cache, load metadata
		$classname = ucwords( str_replace( '-', ' ', $filename ) );
		$classname = 'WPSC_Payment_Gateway_' . str_replace( ' ', '_', $classname );

		if ( file_exists( $file ) ) {
			require_once $file;
		}

		if ( is_callable( array( $classname, 'load' ) ) && ! call_user_func( array( $classname, 'load' ) ) ) {

			self::unregister_file( $filename );

			$error = new WP_Error( 'wpsc-payment', __( 'Error', 'wp-e-commerce' ) );

			return $error;
		}

		$meta = array(
			'class'        => $classname,
			'path'         => $file,
			'internalname' => $filename, // compat with older API
		);

		$gateway = self::get( $filename, $meta );

		if ( is_wp_error( $gateway ) ) {
			return $gateway;
		}

		$meta['name']  = $gateway->get_title();
		$meta['image'] = $gateway->get_image_url();
		$meta['mark']  = $gateway->get_mark_html();

		self::$gateways[ $filename ] = $meta;

		return true;
	}

	public static function unregister_file( $filename ) {
		if ( isset( self::$gateways[ $filename ] ) ) {
			unset( self::$gateways[ $filename ] );
		}
	}

	/**
	 * Updates the payment gateway cache when it's changed.
	 *
	 * This function is hooked into WordPress' wp_loaded action
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @return void
	 */
	public static function action_save_payment_gateway_cache() {
		if ( self::$payment_gateway_cache != self::$gateways ) {
			update_option( 'wpsc_payment_gateway_cache', self::$gateways );
		}
	}

	/**
	 * Flush the payment gateways cache.
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 * @return void
	 */
	public static function flush_cache() {
		delete_option( 'wpsc_payment_gateway_cache' );
	}

	/**
	 * Gets metadata of a certain payment gateway. This is better than calling WPSC_Payment_Gateways->get( $gateway_name )->get_title()
	 * and the likes of it, since it doesn't require the gateway itself to be loaded.
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param string $gateway
	 * @return mixed Array containing the metadata. If the gateway is not registered,
	 *               returns false.
	 */
	public static function get_meta( $gateway ) {
		return isset( self::$gateways[$gateway] ) ? self::$gateways[$gateway] : false;
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
	 *
	 * Return an array containing active gateway names.
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return array
	 */
	public static function get_active_gateways() {
		if ( empty( self::$active_gateways ) ) {
			$selected_gateways = get_option( 'custom_gateway_options', array() );
			$registered_gateways = self::get_gateways();
			self::$active_gateways = array_intersect( $selected_gateways, $registered_gateways );
		}

		return apply_filters( 'wpsc_get_active_gateways', array_values( self::$active_gateways ) );
	}

	/**
	 * Initialize the Active Gateways
	 *
	 * @access public
	 * @since 4.0
	 *
	 * @return void
	 */
	public static function initialize_gateways() {
		$active_gateways = self::get_active_gateways();

		foreach( $active_gateways as $gateway_id ) {
			$gateway = self::get( $gateway_id );
			$gateway->init();
		}
	}

	/**
	 * Returns all known currencies without fractions.
	 *
	 * Our internal list has not been updated in some time, so returning a filterable list
	 * for ever-changing economies and currencies should prove helpful.
	 *
	 * @link http://www.currency-iso.org/dam/downloads/table_a1.xml
	 *
	 * @since  4.0
	 *
	 * @return array Currency ISO codes that do not use fractions.
	 */
	public static function currencies_without_fractions() {

		$currencies = array(
			'JPY',
			'HUF',
			'VND',
			'BYR',
			'XOF',
			'BIF',
			'XAF',
			'CLP',
			'KMF',
			'DJF',
			'XPF',
			'GNF',
			'ISK',
			'GNF',
			'KRW',
			'PYG',
			'RWF',
			'UGX',
			'UYI',
			'VUV',
		);

		return (array) apply_filters( 'wpsc_currencies_without_fractions', $currencies );
	}

	/**
	 * Gets an array of countries in the EU.
	 *
	 * MC (monaco) and IM (Isle of Man, part of UK) also use VAT.
	 *
	 * @since  4.0
	 * @param  $type Type of countries to retrieve. Blank for EU member countries. eu_vat for EU VAT countries.
	 * @return string[]
	 */
	public function get_european_union_countries( $type = '' ) {
		$countries = array( 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK' );

		if ( 'eu_vat' === $type ) {
			$countries[] = 'MC';
			$countries[] = 'IM';
		}

		return $countries;
	}

	/**
	 * No instantiation for this class
	 *
	 * @access private
	 * @since 3.9
	 *
	 */
	private function __construct() {}
}

abstract class WPSC_Payment_Gateway {

	/**
	 * Object that allows manipulation of payment gateway settings in a consistent
	 * manner
	 *
	 * @access public
	 * @var WPSC_Payment_Gateway_Setting
	 */
	public $setting;

	public $purchase_log;

	public $checkout_data;

	public $currency_code;

	public $title;

	/**
	 * Return the title of the payment gateway. For this to work, $this->title must
	 * be set already.
	 *
	 * It is recommended that the payment gateway title be properly localized using __()
	 *
	 * @access public
	 * @since 3.9
	 * @see __()
	 *
	 * @return string
	 */
	public function get_title() {
		$title = empty( $this->title ) ? '' : $this->title;
		return apply_filters( 'wpsc_payment_gateway_title', $title );
	}

	/**
	 * Display the payment gateway settings form as seen in WP eCommerce Settings area.
	 * This method must be overridden by subclasses.
	 *
	 * @abstract
	 * @access public
	 * @since 3.9
	 *
	 * @return void
	 */
	public function setup_form() {
		$checkout_field_types = array(
			'billing'  => __( 'Billing Fields' , 'wp-e-commerce' ),
			'shipping' => __( 'Shipping Fields', 'wp-e-commerce' ),
		);

		$fields = array(
			'firstname' => __( 'First Name' , 'wp-e-commerce' ),
			'lastname'  => __( 'Last Name'  , 'wp-e-commerce' ),
			'address'   => __( 'Address'    , 'wp-e-commerce' ),
			'city'      => __( 'City'       , 'wp-e-commerce' ),
			'state'     => __( 'State'      , 'wp-e-commerce' ),
			'country'   => __( 'Country'    , 'wp-e-commerce' ),
			'postcode'  => __( 'Postal Code', 'wp-e-commerce' ),
		);

		$checkout_form = WPSC_Checkout_Form::get();

		foreach ( $checkout_field_types as $field_type => $title ): ?>
			<tr>
				<td colspan="2">
					<h4><?php echo esc_html( $title ); ?></h4>
				</td>
			</tr>
			<?php foreach ( $fields as $field_name => $field_title ):
				$unique_name = $field_type . $field_name;
				$selected_id = $this->setting->get( "checkout_field_{$unique_name}", $checkout_form->get_field_id_by_unique_name( $unique_name ) );
			?>
				<tr>
					<td>
						<label for="manual-form-<?php echo esc_attr( $unique_name ); ?>"><?php echo esc_html( $field_title ); ?></label>
					</td>
					<td>
						<select name="<?php echo $this->setting->get_field_name( "checkout_field_{$unique_name}" ); ?>" id="manual-form-<?php echo esc_attr( $unique_name ); ?>">
							<?php $checkout_form->field_drop_down_options( $selected_id ); ?>
						</select>
					</td>
				</tr>
			<?php endforeach;
		endforeach;
	}

	/**
	 * Process and send payment details to payment gateways
	 *
	 * @abstract
	 * @access public
	 * @since 3.9
	 *
	 * @return void
	 */
	abstract public function process();

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
	 * Returns the HTML of the logo of the payment gateway.
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return mixed False if there's no html defined.
	 */
	public function get_mark_html() {
		return false;
	}

	public function set_purchase_log( &$purchase_log ) {
		$this->purchase_log = &$purchase_log;
		$this->checkout_data = new WPSC_Checkout_Form_Data( $purchase_log->get( 'id' ) );
	}

	public function get_currency_code() {
		if ( ! $this->currency_code ) {
			$country = new WPSC_Country( get_option( 'currency_type' ) );
			$currency = $country->get( 'currency_code' );
		} else {
			$currency = $this->currency_code;
		}

		return $currency;
	}

	public function get_notification_url() {
		return add_query_arg( 'wpsc_action', 'gateway_notification', (get_option( 'siteurl' ) . "/index.php" ) );
	}

	public function get_transaction_results_url() {
		return get_option( 'transact_url' );
	}

	public function get_shopping_cart_url() {
		return get_option( 'shopping_cart_url' );
	}

	public function get_shopping_cart_payment_url() {

		$te = get_option( 'wpsc_get_active_theme_engine', '1.0' );

		return '1.0' !== $te ? wpsc_get_checkout_url( 'shipping-and-billing' ) : get_option( 'shopping_cart_url' );
	}

	public function get_products_page_url() {
		return get_option( 'product_list_url' );
	}

	public function go_to_transaction_results() {
		//Now to do actions once the payment has been attempted
		switch ( $this->purchase_log->get( 'processed' ) ) {
			case 3:
				// payment worked
				do_action('wpsc_payment_successful');
				break;
			case 1:
				// payment declined
				do_action('wpsc_payment_failed');
				break;
			case 2:
				// something happened with the payment
				do_action('wpsc_payment_incomplete');
				break;
		}

		$transaction_url_with_sessionid = add_query_arg( 'sessionid', $this->purchase_log->get( 'sessionid' ), get_option( 'transact_url' ) );
		wp_redirect( $transaction_url_with_sessionid );

		exit();
	}

	/**
	 * Payment gateway constructor.
	 *
	 * Use WPSC_Payment_Gateways::get( $gateway_name ) instead.
	 *
	 * @access public
	 * @return WPSC_Payment_Gateway
	 */
	public function __construct() {

		$this->setting = new WPSC_Payment_Gateway_Setting( get_class( $this ) );
	}

	/**
	 * Gateway initialization function.
	 *
	 * You should use this function for hooks with actions and filters that are required by the gateway.
	 *
	 * @access public
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {}
}

class WPSC_Payment_Gateway_Setting {
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
	public function __construct( $gateway_name_or_class ) {
		$name = str_replace( 'wpsc_payment_gateway_', '', strtolower( $gateway_name_or_class ) );
		$name = str_replace( array( ' ', '-' ), '_', $name );
		$this->gateway_name = $name;
		$this->option_name = 'wpsc_payment_gateway_' . $this->gateway_name;
	}

	/**
	 * Lazy load the settings from the DB when necessary
	 *
	 * @access private
	 * @return void
	 */
	private function lazy_load() {
		if ( is_null( $this->settings ) ) {
			$this->settings = get_option( $this->option_name, array() );
		}
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
		return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : $default;
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
		$this->unsaved_settings[ $setting ] = $value;
		if ( ! $defer ) {
			$this->save();
		}
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
		if ( ! $defer ) {
			$this->save();
		}
	}

	/**
	 * Returns the field name of the setting on payment gateway setup form
	 *
	 * @access public
	 * @param string $setting Setting names
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

WPSC_Payment_Gateways::init();