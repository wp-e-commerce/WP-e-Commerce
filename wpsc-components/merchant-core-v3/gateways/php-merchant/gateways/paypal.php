<?php
require_once( dirname( __FILE__ ) . '/../common/php-merchant.php' );
require_once( 'paypal-response.php' );
require_once( 'paypal-ipn.php' );

abstract class PHP_Merchant_Paypal extends PHP_Merchant
{
	private static $supported_currencies = array(
		'AUD',
		'BRL',
		'CAD',
		'CHF',
		'CZK',
		'DKK',
		'EUR',
		'GBP',
		'HKD',
		'HUF',
		'ILS',
		'JPY',
		'MXN',
		'MYR',
		'NOK',
		'NZD',
		'PHP',
		'PLN',
		'SEK',
		'SGD',
		'THB',
		'TWD',
		'USD',
	);

	const API_VERSION = '114.0';
	const SANDBOX_URL = 'https://api-3t.sandbox.paypal.com/nvp';
	const LIVE_URL = 'https://api-3t.paypal.com/nvp';

	protected $request = array();
	protected $url;

	protected function add_credentials() {
		$this->requires( array( 'api_username', 'api_password', 'api_signature' ) );
		$credentials = array(
			'USER' => $this->options['api_username'],
			'PWD'  => $this->options['api_password'],
			'VERSION' => self::API_VERSION,
			'SIGNATURE' => $this->options['api_signature'],
		);

		return $credentials;
	}

	protected function build_request( $action = '', $request = array() ) {
		$this->request += $this->add_credentials();
		if ( ! empty( $action ) )
			$this->request['METHOD'] = $action;
		$this->request = array_merge( $this->request, $request );
		foreach( $this->request as $key => $value ) {
			if ( is_bool( $value ) )
				$this->request[$key] = (int) $value;
		}

		return $this->request;
	}

	public static function get_supported_currencies() {
		return self::$supported_currencies;
	}

	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	public function get_url() {
		return empty( $this->options['test'] ) ? self::LIVE_URL : self::SANDBOX_URL;
	}

	public static function is_currency_supported( $currency ) {
		return in_array( $currency, self::$supported_currencies );
	}

	protected function commit( $action, $request = array() ) {
		$this->build_request( $action, $request );
		return $this->http->post( $this->get_url(), $this->request );
	}
}
