<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal.php' );

class PHP_Merchant_Paypal_Test extends UnitTestCase
{
	private $bogus;
	
	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal test cases' );
	}
	
	public function setUp() {
		$options = array(
			'api_username'  => 'sdk-three_api1.sdk.com',
			'api_password'  => 'QFZCWN5HZM8VBG7Q',
			'api_signature' => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
		);
		$this->bogus = new PHP_Merchant_Paypal_Bogus( $options );
	}
	
	public function test_api_credentials_are_properly_generated() {
		$params = array(
			'USER'      => 'sdk-three_api1.sdk.com',
			'PWD'       => 'QFZCWN5HZM8VBG7Q',
			'VERSION'   => '114.0',
			'SIGNATURE' => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
		);
		$this->assertEqual( $this->bogus->add_credentials(), $params );
	}
	
	public function test_check_whether_currency_is_supported_by_paypal() {
		$supported = array(
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
		
		$this->assertEqual( $supported, PHP_Merchant_Paypal_Bogus::get_supported_currencies() );
		
		foreach ( $supported as $currency ) {
			$this->assertTrue( PHP_Merchant_Paypal_Bogus::is_currency_supported( $currency ) );
		}
		
		$this->assertFalse( PHP_Merchant_Paypal_Bogus::is_currency_supported( 'ZAR' ) );
	}
	
	public function test_build_request_function_correctly_handles_custom_request_array() {
		$additional_params = array(
			'PAYMENTREQUEST_0_SHIPTONAME'        => 'Gary Cao',
			'PAYMENTREQUEST_0_SHIPTOSTREET'      => '1 Infinite Loop',
			'PAYMENTREQUEST_0_SHIPTOSTREET2'     => 'Apple Headquarter',
			'PAYMENTREQUEST_0_SHIPTOCITY'        => 'Cupertino',
			'PAYMENTREQUEST_0_SHIPTOSTATE'       => 'CA',
			'PAYMENTREQUEST_0_SHIPTOZIP'         => '95014',
			'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'USA',
			'PAYMENTREQUEST_0_SHIPTOPHONENUM'    => '(877) 412-7753',
		);
		
		$full_param_list = array(
			'USER'      => 'sdk-three_api1.sdk.com',
			'PWD'       => 'QFZCWN5HZM8VBG7Q',
			'VERSION'   => '114.0',
			'SIGNATURE' => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
			'METHOD'    => 'SetExpressCheckout',
			
			'PAYMENTREQUEST_0_SHIPTONAME'        => 'Gary Cao',
			'PAYMENTREQUEST_0_SHIPTOSTREET'      => '1 Infinite Loop',
			'PAYMENTREQUEST_0_SHIPTOSTREET2'     => 'Apple Headquarter',
			'PAYMENTREQUEST_0_SHIPTOCITY'        => 'Cupertino',
			'PAYMENTREQUEST_0_SHIPTOSTATE'       => 'CA',
			'PAYMENTREQUEST_0_SHIPTOZIP'         => '95014',
			'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'USA',
			'PAYMENTREQUEST_0_SHIPTOPHONENUM'    => '(877) 412-7753',
		);
		
		$request = $this->bogus->build_request( 'SetExpressCheckout', $additional_params );
		$this->assertEqual( $request, $full_param_list );
	}
	
	public function test_get_url_returns_sandbox_url_when_in_test_mode() {
		$this->bogus->set_option( 'test', true );
		$this->assertEqual( $this->bogus->get_url(), 'https://api-3t.sandbox.paypal.com/nvp' );
	}
	
	public function test_get_url_returns_live_url_when_in_live_mode() {
		$this->assertEqual( $this->bogus->get_url(), 'https://api-3t.paypal.com/nvp' );
		
		$this->bogus->set_option( 'test', false );
		$this->assertEqual( $this->bogus->get_url(), 'https://api-3t.paypal.com/nvp' );
	}
}

class PHP_Merchant_Paypal_Bogus extends PHP_Merchant_Paypal
{
	public $request = array();
	
	public function add_credentials() {
		return parent::add_credentials();
	}
		
	public function build_request( $action = '', $request = array() ) {
		return parent::build_request( $action, $request );
	}
	 
}
