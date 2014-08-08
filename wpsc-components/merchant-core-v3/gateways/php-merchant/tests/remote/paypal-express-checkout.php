<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-express-checkout.php' );

class PHP_Merchant_Paypal_Express_Checkout_Remote_Test extends WebTestCase
{
	private $response;

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_Express_Checkout Remote Unit Tests' );
	}

	public function test_successful_set_express_checkout_request() {
		global $test_accounts;
		$gateway = new PHP_Merchant_Paypal_Express_Checkout( $test_accounts['paypal-express-checkout'] );

		$purchase_options = array(
			// API info
			'return_url'        => 'http://example.com/return',
			'cancel_url'        => 'http://example.com/cancel',
			'address_override'  => true,

			// Shipping details
			'shipping_address' => array(
				'name'    => 'Gary Cao',
				'street'  => '1 Infinite Loop',
				'street2' => 'Apple Headquarter',
				'city'    => 'Cupertino',
				'state'   => 'CA',
				'country' => 'US',
				'zip'     => '95014',
				'phone'   => '(877) 412-7753',
			),

			// Payment info
			'currency'    => 'JPY',
			'subtotal'    => 13700,
			'shipping'    => 1500,
			'tax'         => 137,
			'amount'	  => 15337,
			'description' => 'Order for example.com',
			'invoice'     => 'E84A90G94',
			'notify_url'  => 'http://example.com/ipn',

			// Items
			'items' => array(
				array(
					'name'        => 'Gold Cart Plugin',
					'description' => 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality.',
					'amount'      => 4000,
					'quantity'    => 1,
					'tax'         => 40,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/',
				),
				array(
					'name'        => 'Member Access Plugin',
					'description' => 'Create pay to view subscription sites',
					'amount'      => 5000,
					'quantity'    => 1,
					'tax'         => 50,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/member-access-plugin/',
				),
				array(
					'name'        => 'Amazon S3',
					'description' => 'This Plugin allows downloadable products on your WP eCommerce site to be hosted on Amazon S3.',
					'amount'      => 4700,
					'quantity'    => 1,
					'tax'         => 47,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/amazon-s3-plugin/',
				),
			),
		);

		$this->response = $response = $gateway->setup_purchase( $purchase_options );
		$this->token = $response->get( 'token' );
		$this->timestamp = $response->get( 'timestamp' );
		$this->datetime = $response->get( 'datetime' );
		$this->correlation_id = $response->get( 'correlation_id' );
		$this->version = $response->get( 'version' );
		$this->build = $response->get( 'build' );
		$this->assertTrue( $response->is_successful() );
		$this->assertFalse( $response->has_errors() );
	}

	public function test_successful_get_express_checkout_request() {
		global $test_accounts;

		// This is required to fill the response property
		$this->test_successful_set_express_checkout_request();

		$gateway = new PHP_Merchant_Paypal_Express_Checkout( $test_accounts['paypal-express-checkout'] );

		$response = $gateway->get_details_for( $this->response->get( 'token' ) );

		$this->assertTrue( $response->is_successful() );
		$this->assertFalse( $response->has_errors() );

		// API Info
		$this->assertTrue( $response->is_checkout_not_initiated() );
		$this->assertFalse( $response->is_checkout_failed() );
		$this->assertFalse( $response->is_checkout_in_progress() );
		$this->assertFalse( $response->is_checkout_completed() );
		$this->assertEqual( $response->get( 'checkout_status' ), 'Not-Initiated' );
		$this->assertEqual( $response->get( 'token'           ), $this->response->get( 'token'   ) );
		$this->assertEqual( $response->get( 'version'         ), $this->response->get( 'version' ) );
		$this->assertEqual( $response->get( 'build'           ), $this->response->get( 'build'   ) );

		// Payment Information
		$this->assertEqual( $response->get( 'currency' ), 'JPY' );
		$this->assertEqual( $response->get( 'amount'   ), 15337 );
		$this->assertEqual( $response->get( 'subtotal' ), 13700 );
		$this->assertEqual( $response->get( 'shipping' ), 1500  );
		$this->assertEqual( $response->get( 'handling' ), 0     );
		$this->assertEqual( $response->get( 'tax'      ), 137   );
		$this->assertEqual( $response->get( 'invoice'  ), 'E84A90G94' );
		$this->assertEqual( $response->get( 'notify_url' ), 'http://example.com/ipn' );
		$this->assertEqual( $response->get( 'shipping_discount' ), 0 );

		// Item Information
		$items = $response->get( 'items' );
		$mock_items = array();

		$mock_items[0] = new stdClass();
		$mock_items[0]->name = 'Gold Cart Plugin';
		$mock_items[0]->description = 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality.';
		$mock_items[0]->amount = 4000;
		$mock_items[0]->quantity = 1;
		$mock_items[0]->tax = 40;

		$mock_items[1] = new stdClass();
		$mock_items[1]->name = 'Member Access Plugin';
		$mock_items[1]->description = 'Create pay to view subscription sites';
		$mock_items[1]->amount = 5000;
		$mock_items[1]->quantity = 1;
		$mock_items[1]->tax = 50;

		$mock_items[2] = new stdClass();
		$mock_items[2]->name = 'Amazon S3';
		$mock_items[2]->description = 'This Plugin allows downloadable products on your WP eCommerce site to be hosted on Amazon S3.';
		$mock_items[2]->amount = 4700;
		$mock_items[2]->quantity = 1;
		$mock_items[2]->tax = 47;

		$this->assertEqual( $items, $mock_items );
	}
}
