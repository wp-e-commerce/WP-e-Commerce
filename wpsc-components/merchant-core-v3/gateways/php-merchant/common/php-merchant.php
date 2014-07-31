<?php

/**
 *
 *
 * @author Instinct Entertainment
 * @package PHP_Merchant
 **/

require_once( 'exception.php' );
require_once( 'http.php' );
require_once( 'response.php' );
require_once( 'helpers.php' );

abstract class PHP_Merchant {
	/**
	 * These currencies don't have decimal points. Eg: You never see JPY1000.5
	 *
	 * @var array
	 * @access protected
	 */
	protected $currencies_without_fractions = array( 'JPY', 'HUF' );

	/**
	 * Options are passed into the object constructor or payment action methods (purchase,
	 * authorize, capture void, credit, recurring). These options will eventually be used
	 * to generate HTTP requests to payment gateways.
	 *
	 * @var array
	 * @access protected
	 */
	protected $options = array(
		'currency' => 'USD',
	);

	/**
	 * This is the object that handles sending HTTP requests. The default HTTP client is CURL,
	 * but you can always set a custom HTTP client object that inherits from PHP_Merchant_HTTP.
	 *
	 * @var PHP_Merchant_HTTP
	 * @access protected
	 */
	protected $http;

	/**
	 * Constructor of the payment gateway. Accepts an array of options.
	 *
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		if ( ! array_key_exists( 'http_client', $options ) ) {
			require_once( 'http-curl.php' );
			$this->http = new PHP_Merchant_HTTP_CURL();
		} else {
			$this->http =& $options['http_client'];
			unset( $options['http_client'] );
		}

		$this->set_options( $options );
	}

	/**
	 * Format the amount according to the currency being used.
	 *
	 * @param float $amt Amount
	 * @param string $currency Defaults to the currency specified in defined $options
	 * @return string
	 */
	public function format( $amt, $currency = false ) {
		if ( ! $currency )
			$currency = $this->options['currency'];

		$dec = in_array( $currency, $this->currencies_without_fractions ) ? 0 : 2;
		return number_format( $amt, $dec );
	}

	public function purchase( $options = array() ) {
		$this->requires( 'amount' );
	}

	public function authorize() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'authorize' );
	}

	public function capture() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'capture' );
	}

	public function void() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'void' );
	}

	public function credit() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'credit' );
	}

	public function recurring() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'recurring' );
	}

	public function get_options() {
		return $this->options;
	}

	public function set_options( $options ) {
		$this->options = array_merge( $this->options, $options );
		return $this;
	}

	public function get_option( $key ) {
		return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : null;
	}

	public function set_option( $key, $value ) {
		$this->options[ $key ] = $value;
		return $this;
	}

	/**
	 * Specify fields that are required for the API operation, otherwise
	 * throw a PHP_Merchant_Exception exception
	 *
	 * @param array $options Required fields
	 * @return void
	 * @since 3.9
	 */
	protected function requires( $options ) {
		$missing = array();
		foreach ( (array) $options as $option ) {
			if ( ! isset( $this->options[ $option ] ) ) {
				$missing[] = $option;
			}
		}

		if ( ! empty( $missing ) ) {
			throw new PHP_Merchant_Exception( PHPME_REQUIRED_OPTION_UNDEFINED, implode( ', ', $missing ) );
		}
	}

	/**
	 * Specify fields that are required for the API operation, otherwise
	 * throw a PHP_Merchant_Exception exception
	 *
	 * @param array $options Required fields
	 * @return boolean|void Returns True if a specified field is found
	 * @since 3.9
	 */
	protected function conditional_requires( $options ) {
		foreach ( (array) $options as $option ) {
			if ( isset( $this->options[ $option ] ) ) {
				return true;
			}
		}

		throw new PHP_Merchant_Exception( PHPME_REQUIRED_OPTION_UNDEFINED, implode( ', ', $options ) );
	}
}
