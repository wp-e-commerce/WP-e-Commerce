<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-digital-goods.php' );

class PHP_Merchant_Paypal_Digital_Goods_Certification_Test_X4 extends UnitTestCase
{
	private $gateway;
	private $token;
	private $default_options;
	private $purchase_options;

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_Digital_Goods test cases' );
		// Generate an invoice number
		$inv = 'E84A90G' . mt_rand( 100, 999);

		// Common Options
		$this->default_options = array(
			// API info
			'return_url'        => 'http://example.com/return',
			'cancel_url'        => 'http://example.com/cancel',
			'address_override'  => 1,

			// Payment info
			'currency'    => 'USD',
			/*
			'amount'      => 15.337,
			'subtotal'    => 13.700,
			'shipping'    => 1.500,
			'tax'         => 0.137,	
			 */
			'description' => 'A sample order',
			'invoice'     => $inv,
			'notify_url'  => 'http://example.com/ipn',

			// Items
			'items' => array(
				array(
					'name'        => 'Gold Cart Plugin',
					'description' => 'Gold Cart extends your WP e-Commerce store by enabling additional features and functionality.',
					'amount'      => 4,
					'quantity'    => 1,
					'tax'         => 0.040,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/',
					'number'      => '7A12343-WHT-XL',
				),
				array(
					'name'        => 'Member Access Plugin',
					'description' => 'Create pay to view subscription sites',
					'amount'      => 5,
					'quantity'    => 1,
					'tax'         => 0.05,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/member-access-plugin/',
					'number'      => '7A12344-WHT-XL',
				),
				array(
					'name'        => 'Amazon S3',
					'description' => 'This Plugin allows downloadable products that you have for sale on your WP e-Commerce site to be hosted within Amazon S3.',
					'amount'      => 4.7,
					'quantity'    => 1,
					'tax'         => 0.047,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/amazon-s3-plugin/',
					'number'      => '7A12345-WHT-XL',
				),
			),
		);
	}

	public function setUp() {
		global $test_accounts;
		$this->gateway = new PHP_Merchant_Paypal_Digital_Goods( $test_accounts['paypal-ec-oa'] );
		$this->purchase_options = $this->default_options;
	}


	public function tearDown() {
		$this->purchase_options = null;
	}

	/**
	 * Test Case Reference 4.1
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref41() {
		/*
		$this->purchase_options['token'] = 'EC-6YT23592N88199745';
		$this->purchase_options['message_id'] = 'abcedfght';
		$this->purchase_options['invoice'] = 'E84A90G811';
		$this->purchase_options['transaction_id'] = '91E227400G099735F';

		$response = $this->gateway->credit( $this->purchase_options );
		$this->assertTrue( $response->is_successful() );
		st_log( $response );
		 */

		// Fully Refunded Transaction
		$response = $this->gateway->get_transaction_details( '91E227400G099735F' );

		$this->assertTrue( $response->is_successful() );
		st_echo( 'Test Case 4.1:' . "\n" );
		st_echo( 'Transaction ID: 91E227400G099735F' . "\n" );
		st_echo( 'Payment Status: ' . $response->get_params()['PAYMENTSTATUS'] . "\n" );
	}

	/**
	 * Test Case Reference 4.2
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref42() {
		/*
		$this->purchase_options['token'] = 'EC-8AU95879WD9646218';
		$this->purchase_options['message_id'] = 'abcedfghkk';
		$this->purchase_options['invoice'] = 'E84A90G555';
		$this->purchase_options['transaction_id'] = '2WH811268W276704P';
		$this->purchase_options['refund_type'] = 'partial';
		$this->purchase_options['amount'] = 6.5;

		// Call DoExpressCheckout 8P404440G06461422
		$response = $this->gateway->credit( $this->purchase_options );


		$this->assertTrue( $response->is_successful() );
		st_log( $response );
 		*/

		// Fully Refunded Transaction
		$response = $this->gateway->get_transaction_details( '2WH811268W276704P' );

		$this->assertTrue( $response->is_successful() );
		st_echo( 'Test Case 4.2:' . "\n" );
		st_echo( 'Transaction ID: 2WH811268W276704P' . "\n" );
		st_echo( 'Payment Status: ' . $response->get_params()['PAYMENTSTATUS'] . "\n" );
	}

	/**
	 * Test Case Reference 4.3
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref43() {
		$this->purchase_options['token'] = 'EC-8AU95879WD9646218';
		$this->purchase_options['message_id'] = 'abcedfghkk';
		$this->purchase_options['invoice'] = 'E84A90G555';
		$this->purchase_options['transaction_id'] = '2WH811268W276704P';	
		$this->purchase_options['amount'] = 100.01;

		// Call DoRefund 
		$response = $this->gateway->credit( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

 		// Display the Transaction Id
		st_echo( 'Test Case 4.3: ' . $response->get( 'correlation_id' ) . "\n" );


	}
	
}
