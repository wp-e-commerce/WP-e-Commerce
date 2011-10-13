<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-ipn.php' );

class PHP_Merchant_Paypal_IPN_Test extends UnitTestCase
{
	private $http;

	public $ipn_request = array(
		'mc_gross'               => '-27.50',
		'invoice'                => '1411316760920',
		'settle_amount'          => '19.02',
		'protection_eligibility' => 'Eligible',
		'item_number1'           => '',
		'payer_id'               => 'BC798KQ2QU22W',
		'address_street'         => '1 Main St',
		'payment_date'           => '00:06:09 Sep 23, 2011 PDT',
		'payment_status'         => 'Pending',
		'charset'                => 'windows-1252',
		'address_zip'            => '95131',
		'mc_shipping'            => '0.00',
		'mc_handling'            => '0.00',
		'first_name'             => 'Test',
		'mc_fee'                 => '-1.25',
		'address_country_code'   => 'US',
		'exchange_rate'          => '0.724571',
		'address_name'           => 'Test User',
		'notify_version'         => '3.4',
		'reason_code'            => 'refund',
		'settle_currency'        => 'USD',
		'custom'                 => '',
		'address_country'        => 'United States',
		'mc_handling1'           => '0.00',
		'address_city'           => 'San Jose',
		'verify_sign'            => 'An5ns1Kso7MWUdW4ErQKJJJ4qi4-AVcXYpICHDtcWk34bsCJQf7rc93o',
		'payer_email'            => 'visa_1304648966_per@garyc40.com',
		'mc_shipping1'           => '0.00',
		'parent_txn_id'          => '3BC81385RB1253259',
		'txn_id'                 => '64B91482UD035471X',
		'payment_type'           => 'echeck',
		'last_name'              => 'User',
		'address_state'          => 'CA',
		'item_name1'             => 'Test Product',
		'receiver_email'         => 'pro_1304085877_biz@garyc40.com',
		'payment_fee'            => '',
		'quantity1'              => '1',
		'receiver_id'            => 'S2FHLPD5HHGMJ',
		'pending_reason'         => 'echeck',
		'mc_gross_1'             => '25.00',
		'mc_currency'            => 'NZD',
		'residence_country'      => 'US',
		'test_ipn'               => '1',
		'transaction_subject'    => '',
		'payment_gross'          => '',
		'ipn_track_id'           => 'qXFPBOM1pjBuIydRsesfyQ',
	);

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_IPN test cases' );
		require_once( PHP_MERCHANT_PATH . '/common/http-curl.php' );
		Mock::generate( 'PHP_Merchant_HTTP_CURL' );
	}

	public function test_correct_request_is_returned_to_IPN() {
		$live_url = 'https://www.paypal.com/cgi-bin/webscr';
		$sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

		$verify_request = array( 'cmd' => '_notify-validate' );
		$verify_request += $this->ipn_request;

		// expects to send to Live URL and receive "VERIFIED" message from Paypal
		$http = new MockPHP_Merchant_HTTP_CURL();
		$http->returns( 'post', 'VERIFIED' );
		$ipn = new PHP_Merchant_Paypal_IPN( $this->ipn_request, false, $http );
		$http->expectOnce( 'post', array( $live_url, $verify_request ) ); // make sure $verify_request is constructed correctly
		$this->assertTrue( $ipn->is_verified() );

		// expects to send to Sandbox URL and receive "INVALID" message from Paypal
		$http = new MockPHP_Merchant_HTTP_CURL();
		$http->returns( 'post', 'INVALID' );
		$ipn = new PHP_Merchant_Paypal_IPN( $this->ipn_request, true, $http );
		$http->expectOnce( 'post', array( $sandbox_url, $verify_request ) ); // make sure $verify_request is constructed correctly
		$this->assertFalse( $ipn->is_verified() );
	}
}

class PHP_Merchant_Paypal_IPN_Bogus extends PHP_Merchant_Paypal_IPN
{
	public $http;
}