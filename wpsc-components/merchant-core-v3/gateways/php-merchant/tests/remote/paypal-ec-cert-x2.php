<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-express-checkout.php' );

class PHP_Merchant_Paypal_Express_Checkout_Certification_Test_X2 extends UnitTestCase
{
	private $gateway;
	private $token;
	private $default_options;
	private $purchase_options;

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_Express_Checkout test cases' );
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
			'amount'      => 15.337,
			'subtotal'    => 13.700,
			'shipping'    => 1.500,
			'tax'         => 0.137,
			'description' => 'A sample order',
			'invoice'     => $inv,
			'notify_url'  => 'http://example.com/ipn',

			// Items
			'items' => array(
				array(
					'name'        => 'Gold Cart Plugin',
					'description' => 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality.',
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
					'description' => 'This Plugin allows downloadable products that you have for sale on your WP eCommerce site to be hosted within Amazon S3.',
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
		$this->gateway = new PHP_Merchant_Paypal_Express_Checkout( $test_accounts['paypal-ec-oa'] );
		$this->purchase_options = $this->default_options;
	}


	public function tearDown() {
		$this->purchase_options = null;
	}

	/**
	 * Test Case Reference 2.1
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref21() {
		// Using the Token and Payer Id for another transaction 
		$this->purchase_options['token'] = 'EC-2LX41209SH738634B';
		$this->purchase_options['payer_id'] = 'FQQ7Q9EVPAB86';

		// Call DoExpressCheckout
		$response = $this->gateway->purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the Transaction Id
		st_echo( 'Test Case 2.1: ' . $response->get( 'transaction_id' ) . "\n" );
	}

	/**
	 * Test Case Reference 2.2
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref22() { 
		// Using the Token and Payer Id for another transaction 
		$this->purchase_options['token'] = 'EC-5T412084S27270248';
		$this->purchase_options['payer_id'] = 'FQQ7Q9EVPAB86';

		// Call DoExpressCheckout
		$response = $this->gateway->purchase( $this->purchase_options, 'Authorization' );

		$this->assertTrue( $response->is_successful() ); 

		// Display the Transaction Id
		st_echo( 'Test Case 2.2: ' . $response->get( 'transaction_id' ) . "\n" );
	}

	/**
	 * Test Case Reference 2.3
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref23() {
		// Using the Token and Payer Id for another transaction 
		$this->purchase_options['token'] = 'EC-94S08582T02646945';
		$this->purchase_options['payer_id'] = 'FQQ7Q9EVPAB86';

		// Call DoExpressCheckout
		$response = $this->gateway->purchase( $this->purchase_options, 'Order' );

		$this->assertTrue( $response->is_successful() );

		// Display the Transaction Id
		st_echo( 'Test Case 2.3: ' . $response->get( 'transaction_id' ) . "\n" );
	}

	/**
	 * Test Case Reference 2.4
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref24() {
		// Using the Token and Payer Id for another transaction 
		$this->purchase_options['token'] = 'EC-2A077123HD2136914';
		$this->purchase_options['payer_id'] = 'FQQ7Q9EVPAB86';

		// Call DoExpressCheckout
		$this->purchase_options['no_shipping'] = 1;
		$response = $this->gateway->purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the Transaction Id
		st_echo( 'Test Case 2.4: ' . $response->get( 'transaction_id' ) . "\n" );
	}

	/**
	 * Test Case Reference 2.5
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_doexpresscheckout_ref25() {
		// Negative Testing
		$this->purchase_options['subtotal'] = 100.01;
		$this->purchase_options['shipping'] = 0;
		$this->purchase_options['tax'] = 0;
		$this->purchase_options['amount'] = 100.01;
		$this->purchase_options['max_amount'] = 100.01;
		$this->purchase_options['items'] = array();

		// Using the Token and Payer Id for another transaction 
		$this->purchase_options['token'] = 'EC-04C181866P316870M';
		$this->purchase_options['payer_id'] = 'FQQ7Q9EVPAB86';

		// Call DoExpressCheckout
		$response = $this->gateway->purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the Transaction Id
		st_echo( 'Test Case 2.5: ' . $response->get( 'correlation_id' ) . "\n" );
	}
}
