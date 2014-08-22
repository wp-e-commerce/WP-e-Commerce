<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-express-checkout.php' );

class PHP_Merchant_Paypal_Express_Checkout_Certification_Test_X1 extends UnitTestCase
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
	 * Test Case Reference 1.01
	 *
	 * Standard Fields Test
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref101() {
		// Call SetExpressCheckout
		$options  = is_array( $this->purchase_options ) ? $this->purchase_options : array();
		$response = $this->gateway->setup_purchase( $options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.01: ' . $response->get( 'token' ) . "\n" );
	}

	/**
	 * Test Case Reference 1.02
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref102() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '1 Infinite Loop',
			'street2' => 'Apple Headquarter ext',
			'city'    => 'Cupertino',
			'state'   => 'CA',
			'country' => 'US',
			'zip'     => '95014',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.02: ' . $response->get( 'token' ) . "\n" );
	}

	/**
	 * Test Case Reference 1.03
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref103() {
		// Max Amount
		$this->purchase_options['max_amount'] = 15.9;
		$this->purchase_options['solution_type'] = 'Mark';

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.03: ' . $response->get( 'token' ) . "\n" );

	}

	/**
	 * Test Case Reference 1.04
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref104() {
		// Max Amount
		$this->purchase_options['max_amount'] = 15.9;
		$this->purchase_options['solution_type'] = 'Sole';

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.04: ' . $response->get( 'token' ) . "\n" );
	}

	/**
	 * Test Case Reference 1.05
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref105() {
		// Max Amount
		$this->purchase_options['no_shipping'] = true;

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.05: ' . $response->get( 'token' ) . "\n" );
	}

	/**
	 * Test Case Reference 1.06
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref106() {
		// Billing Agreement description
		$this->purchase_options['billing_type'] = 'MerchantInitiatedBillingSingleAgreement';
		$this->purchase_options['billing_description'] = 'One Time Payment';

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertTrue( $response->is_successful() );

		// Display the transaction Id
		st_echo('Test Case 1.06: ' . $response->get( 'token' ) . "\n" );
	}

	/**
	 * Test Case Reference 1.08
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref108() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '',
			'street2' => '',
			'city'    => 'Cupertino',
			'state'   => 'CA',
			'country' => 'US',
			'zip'     => '95014',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.08: ' . $response->get( 'correlation_id' ) . ' - ' . $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.09
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref109() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '1 Infinite Loop',
			'street2' => 'Apple Headquarter',
			'city'    => '',
			'state'   => 'CA',
			'country' => 'US',
			'zip'     => '95014',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.09: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.10
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref110() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '1 Infinite Loop',
			'street2' => 'Apple Headquarter',
			'city'    => 'Cupertino',
			'state'   => '',
			'country' => 'US',
			'zip'     => '95014',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.10: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.11
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref111() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '1 Infinite Loop',
			'street2' => 'Apple Headquarter',
			'city'    => 'Cupertino',
			'state'   => 'CA',
			'country' => 'US',
			'zip'     => '',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.11: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.12
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref112() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '1 Infinite Loop',
			'street2' => 'Apple Headquarter',
			'city'    => 'Cupertino',
			'state'   => 'CA',
			'country' => '',
			'zip'     => '95014',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.12: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.13
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref113() {
		// Shipping Details
		$this->purchase_options['shipping_address'] = array(
			'name'    => 'Abid Omar',
			'street'  => '123 Any Street',
			'street2' => '',
			'city'    => 'New York',
			'state'   => 'NY',
			'country' => 'US',
			'zip'     => '90210',
			'phone'   => '(877) 412-7753',
		);

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.13: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}

	/**
	 * Test Case Reference 1.14
	 *
	 * @return void
	 * @since 3.9
	 */
	public function test_setexpresscheckout_standard_ref114() {
		// Negative Testing
		$this->purchase_options['subtotal'] = 100.01;
		$this->purchase_options['shipping'] = 0;
		$this->purchase_options['tax'] = 0;
		$this->purchase_options['amount'] = 100.01;
		$this->purchase_options['max_amount'] = 100.01;
		$this->purchase_options['items'] = array();

		// Call SetExpressCheckout
		$response = $this->gateway->setup_purchase( $this->purchase_options );

		$this->assertFalse( $response->is_successful() );

		// Display the transaction Id
		$error = $response->get_error();
		st_echo( 'Test Case 1.14: '  . $response->get( 'correlation_id' ) . ' - '. $error['details']  . "\n" );
	}
}
