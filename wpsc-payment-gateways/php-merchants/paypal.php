<?php
require_once( 'common/php-merchant.php' );
abstract class PHP_Merchant_Paypal extends PHP_Merchant
{
	const VERSION = '72.0';
	
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
	
	private $request;
	
	private function build_request( $request ) {
		$this->request = array(
			
		);
	}
	
	public static function get_supported_currencies() {
		return self::$supported_currencies;
	}
	
	public function __construct() {
		parent::__construct();
	}
	
	public function is_currency_supported( $currency ) {
		return in_array( $currency, self::$supported_currencies );
	}
	
	protected function commit( $action, $request ) {
		$request['METHOD'] = $action;
		$this->build_request( $request );
	}
}