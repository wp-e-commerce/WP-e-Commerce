<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-pro.php' );

class PHP_Merchant_Paypal_Pro_Certification_Test_X2 extends UnitTestCase
{
	private $gateway;
	private $token;
	private $default_options;
	private $purchase_options;

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_Pro test cases' );
		$this->default_options = array();
	}

	public function setUp() {
		global $test_accounts;
		$this->gateway = new PHP_Merchant_Paypal_Pro( $test_accounts['paypal-pro-oa'] );
		$this->purchase_options = $this->default_options;
	}


	public function tearDown() {
		$this->purchase_options = null;
	}

	/**
	 * Test Case Reference A.1.1
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_paypalpro_refA11() {
		// Fully Refunded Transaction
		$response = $this->gateway->get_transaction_details( '57T574999A4979400' );

		$this->assertTrue( $response->is_successful() );
		st_echo( 'Test Case A1.1:' . "\n" );
		st_echo( 'Transaction ID: 57T574999A4979400' . "\n" );
		st_echo( 'Payment Status: ' . $response->get_params()['PAYMENTSTATUS'] . "\n" );
	}

	/**
	 * Test Case Reference A.1.2
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_paypalpro_refA12() {
		// Partially Refunded Transaction
		$response = $this->gateway->get_transaction_details( '6XD74563HX9353102' );

		$this->assertTrue( $response->is_successful() );
		st_echo( 'Test Case A1.2:' . "\n" );
		st_echo( 'Transaction ID: 6XD74563HX9353102' . "\n" );
		st_echo( 'Payment Status: ' . $response->get_params()['PAYMENTSTATUS'] . "\n" );	
	}

	/**
	 * Test Case Reference A.1.3
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_paypalpro_refA13() {
		// Negative Test
		$this->purchase_options['message_id'] = '';
		$this->purchase_options['transaction_id'] = '1MC14541JJ0533214';
		$this->purchase_options['invoice'] = '2';
		$this->purchase_options['refund_type'] = 'partial';
		$this->purchase_options['amount'] = '100.01';

		$response = $this->gateway->credit( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		st_echo( 'Test Case A1.3:' . "\n" );	
		st_echo( 'Correlation ID: ' . $response->get_params()['CORRELATIONID'] . "\n" );	
	}
}
