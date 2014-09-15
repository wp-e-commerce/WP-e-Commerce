<?php

require_once( PHP_MERCHANT_PATH . '/gateways/paypal-express-checkout.php' );

class PHP_Merchant_Paypal_Express_Checkout_Test extends UnitTestCase
{
	private $bogus;
	private $options;
	private $amount;
	private $token;
	private $setup_purchase_options;
	private $purchase_options;

	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal_Express_Checkout test cases' );
		$this->token = 'EC-6L77249383950130E';
		// options to pass to the merchant class
		$this->setup_purchase_options = $this->purchase_options = array(
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
				'country' => 'USA',
				'zip'     => '95014',
				'phone'   => '(877) 412-7753',
			),

			// Payment info
			'currency'    => 'JPY',
			'amount'      => 15337,
			'subtotal'    => 13700,
			'shipping'    => 1500,
			'tax'         => 137,
			'description' => 'Order for example.com',
			'invoice'     => 'E84A90G94',
			'notify_url'  => 'http://example.com/ipn',

			// Items
			'items' => array(
				array(
					'name'        => 'Gold Cart Plugin',
					'description' => 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality, including views, galleries, store search and payment gateways.',
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
					'description' => 'This Plugin allows downloadable products that you have for sale on your WP eCommerce site to be hosted within Amazon S3.',
					'amount'      => 4700,
					'quantity'    => 1,
					'tax'         => 47,
					'url'         => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/amazon-s3-plugin/',
				),
			),
		);

		$this->purchase_options += array(
			'token'    => 'EC-2JJ0893331633543K',
			'payer_id' => 'BC798KQ2QU22W',
		);
	}

	public function setUp() {
		$this->bogus = new PHP_Merchant_Paypal_Express_Checkout_Bogus( array(
			'api_username'      => 'sdk-three_api1.sdk.com',
			'api_password'      => 'QFZCWN5HZM8VBG7Q',
			'api_signature'     => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
		) );
	}

	public function tearDown() {

	}

	public function test_correct_parameters_are_sent_to_paypal_when_set_express_checkout() {
		// set up expectations for mock objects
		$url = 'https://api-3t.paypal.com/nvp';

		// how the request parameters should look like
		$args = array(
			// API info
			'USER'         => 'sdk-three_api1.sdk.com',
			'PWD'          => 'QFZCWN5HZM8VBG7Q',
			'VERSION'      => '114.0',
			'SIGNATURE'    => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
			'METHOD'       => 'SetExpressCheckout',
 			'RETURNURL'    => 'http://example.com/return',
			'CANCELURL'    => 'http://example.com/cancel',
			'AMT'		   => 15337,
			'ADDROVERRIDE' => 1,
			'INVOICEID'	   => 'E84A90G94',
			

			// Shipping details
			'PAYMENTREQUEST_0_SHIPTONAME'        => 'Gary Cao',
			'PAYMENTREQUEST_0_SHIPTOSTREET'      => '1 Infinite Loop',
			'PAYMENTREQUEST_0_SHIPTOSTREET2'     => 'Apple Headquarter',
			'PAYMENTREQUEST_0_SHIPTOCITY'        => 'Cupertino',
			'PAYMENTREQUEST_0_SHIPTOSTATE'       => 'CA',
			'PAYMENTREQUEST_0_SHIPTOZIP'         => '95014',
			'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'USA',
			'PAYMENTREQUEST_0_SHIPTOPHONENUM'    => '(877) 412-7753',

			// Payment info
			'PAYMENTREQUEST_0_AMT'           => '15,337',
			'PAYMENTREQUEST_0_CURRENCYCODE'  => 'JPY',
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
			'PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD' => 'InstantPaymentOnly',
			'PAYMENTREQUEST_0_ITEMAMT'       => '13,700',
			'PAYMENTREQUEST_0_SHIPPINGAMT'   => '1,500',
			'PAYMENTREQUEST_0_TAXAMT'        => '137',
			'PAYMENTREQUEST_0_DESC'          => 'Order for example.com',
			'PAYMENTREQUEST_0_INVNUM'        => 'E84A90G94',
			'PAYMENTREQUEST_0_NOTIFYURL'     => 'http://example.com/ipn',

			// Items
			'L_PAYMENTREQUEST_0_NAME0'    => 'Gold Cart Plugin',
			'L_PAYMENTREQUEST_0_AMT0'     => '4,000',
			'L_PAYMENTREQUEST_0_QTY0'     => 1,
			'L_PAYMENTREQUEST_0_DESC0'    => 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality, including views, galleries, store search and payment gateways.',
			'L_PAYMENTREQUEST_0_TAXAMT0'  => '40',
			'L_PAYMENTREQUEST_0_ITEMURL0' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/',

			'L_PAYMENTREQUEST_0_NAME1'    => 'Member Access Plugin',
			'L_PAYMENTREQUEST_0_AMT1'     => '5,000',
			'L_PAYMENTREQUEST_0_QTY1'     => 1,
			'L_PAYMENTREQUEST_0_DESC1'    => 'Create pay to view subscription sites',
			'L_PAYMENTREQUEST_0_TAXAMT1'  => '50',
			'L_PAYMENTREQUEST_0_ITEMURL1' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/member-access-plugin/',

			'L_PAYMENTREQUEST_0_NAME2'    => 'Amazon S3',
			'L_PAYMENTREQUEST_0_AMT2'     => '4,700',
			'L_PAYMENTREQUEST_0_QTY2'     => 1,
			'L_PAYMENTREQUEST_0_DESC2'    => 'This Plugin allows downloadable products that you have for sale on your WP eCommerce site to be hosted within Amazon S3.',
			'L_PAYMENTREQUEST_0_TAXAMT2'  => '47',
			'L_PAYMENTREQUEST_0_ITEMURL2' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/amazon-s3-plugin/',
		);

		$this->bogus->http->expectOnce( 'post', array( $url, $args ) );
		try {
			$this->bogus->setup_purchase( $this->setup_purchase_options );
		} catch ( PHP_Merchant_Exception $e ) {

		}
	}

	public function test_correct_parameters_are_sent_when_do_express_checkout_payment() {
		// set up expectations for mock objects
		$url = 'https://api-3t.paypal.com/nvp';

		// how the request parameters should look like
		$args = array(
			// API info
			'USER'         => 'sdk-three_api1.sdk.com',
			'PWD'          => 'QFZCWN5HZM8VBG7Q',
			'VERSION'      => '114.0',
			'SIGNATURE'    => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
			'METHOD'       => 'DoExpressCheckoutPayment',
 			'RETURNURL'    => 'http://example.com/return',
			'CANCELURL'    => 'http://example.com/cancel',
			'AMT'		   => 15337,
			'ADDROVERRIDE' => 1,

			// Payer ID
			'TOKEN'   => 'EC-2JJ0893331633543K',
			'PAYERID' => 'BC798KQ2QU22W',

			'INVOICEID'	   => 'E84A90G94',

			// Shipping details
			'PAYMENTREQUEST_0_SHIPTONAME'        => 'Gary Cao',
			'PAYMENTREQUEST_0_SHIPTOSTREET'      => '1 Infinite Loop',
			'PAYMENTREQUEST_0_SHIPTOSTREET2'     => 'Apple Headquarter',
			'PAYMENTREQUEST_0_SHIPTOCITY'        => 'Cupertino',
			'PAYMENTREQUEST_0_SHIPTOSTATE'       => 'CA',
			'PAYMENTREQUEST_0_SHIPTOZIP'         => '95014',
			'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'USA',
			'PAYMENTREQUEST_0_SHIPTOPHONENUM'    => '(877) 412-7753',

			// Payment info
			'PAYMENTREQUEST_0_AMT'           => '15,337',
			'PAYMENTREQUEST_0_CURRENCYCODE'  => 'JPY',
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
			'PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD' => 'InstantPaymentOnly',
			'PAYMENTREQUEST_0_ITEMAMT'       => '13,700',
			'PAYMENTREQUEST_0_SHIPPINGAMT'   => '1,500',
			'PAYMENTREQUEST_0_TAXAMT'        => '137',
			'PAYMENTREQUEST_0_DESC'          => 'Order for example.com',
			'PAYMENTREQUEST_0_INVNUM'        => 'E84A90G94',
			'PAYMENTREQUEST_0_NOTIFYURL'     => 'http://example.com/ipn',

			// Items
			'L_PAYMENTREQUEST_0_NAME0'    => 'Gold Cart Plugin',
			'L_PAYMENTREQUEST_0_AMT0'     => '4,000',
			'L_PAYMENTREQUEST_0_QTY0'     => 1,
			'L_PAYMENTREQUEST_0_DESC0'    => 'Gold Cart extends your WP eCommerce store by enabling additional features and functionality, including views, galleries, store search and payment gateways.',
			'L_PAYMENTREQUEST_0_TAXAMT0'  => '40',
			'L_PAYMENTREQUEST_0_ITEMURL0' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/gold-cart-plugin/',

			'L_PAYMENTREQUEST_0_NAME1'    => 'Member Access Plugin',
			'L_PAYMENTREQUEST_0_AMT1'     => '5,000',
			'L_PAYMENTREQUEST_0_QTY1'     => 1,
			'L_PAYMENTREQUEST_0_DESC1'    => 'Create pay to view subscription sites',
			'L_PAYMENTREQUEST_0_TAXAMT1'  => '50',
			'L_PAYMENTREQUEST_0_ITEMURL1' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/member-access-plugin/',

			'L_PAYMENTREQUEST_0_NAME2'    => 'Amazon S3',
			'L_PAYMENTREQUEST_0_AMT2'     => '4,700',
			'L_PAYMENTREQUEST_0_QTY2'     => 1,
			'L_PAYMENTREQUEST_0_DESC2'    => 'This Plugin allows downloadable products that you have for sale on your WP eCommerce site to be hosted within Amazon S3.',
			'L_PAYMENTREQUEST_0_TAXAMT2'  => '47',
			'L_PAYMENTREQUEST_0_ITEMURL2' => 'http://getshopped.org/extend/premium-upgrades/premium-upgrades/amazon-s3-plugin/',
		);

		$this->bogus->http->expectOnce( 'post', array( $url, $args ) );
		try {
			$this->bogus->purchase( $this->purchase_options );
		} catch ( PHP_Merchant_Exception $e ) {

		}
	}

	public function test_correct_parameters_are_sent_when_get_express_checkout_details() {
		$url = 'https://api-3t.paypal.com/nvp';
		$args = array(
			// API info
			'USER'      => 'sdk-three_api1.sdk.com',
			'PWD'       => 'QFZCWN5HZM8VBG7Q',
			'VERSION'   => '114.0',
			'SIGNATURE' => 'A-IzJhZZjhg29XQ2qnhapuwxIDzyAZQ92FRP5dqBzVesOkzbdUONzmOU',
			'METHOD'    => 'GetExpressCheckoutDetails',
			'TOKEN'     => $this->token,
		);

		$this->bogus->http->expectOnce( 'post', array( $url, $args ) );
		try {
			$this->bogus->get_details_for( $this->token );
		} catch ( PHP_Merchant_Exception $e ) {

		}
	}

	public function test_correct_response_is_returned_when_set_express_checkout_is_successful() {
		$mock_response = 'ACK=Success&CORRELATIONID=224f0e4a32d14&TIMESTAMP=2011%2d07%2d05T13%253A23%253A52Z&VERSION=2%2e30000&BUILD=1%2e0006&TOKEN=EC%2d1OIN4UJGFOK54YFV';
		$this->bogus->http->returnsByValue( 'post', $mock_response );
		$response = $this->bogus->setup_purchase( $this->setup_purchase_options );

		$this->assertTrue( $response->is_successful() );
		$this->assertFalse( $response->has_errors() );
		$this->assertEqual( $response->get( 'token'          ), 'EC-1OIN4UJGFOK54YFV'  );
		$this->assertEqual( $response->get( 'timestamp'      ), 1309872232             );
		$this->assertEqual( $response->get( 'datetime'       ), '2011-07-05T13:23:52Z' );
		$this->assertEqual( $response->get( 'correlation_id' ), '224f0e4a32d14'        );
		$this->assertEqual( $response->get( 'version'        ), '2.30000'              );
		$this->assertEqual( $response->get( 'build'          ), '1.0006'               );
	}

	public function test_correct_response_is_returned_when_get_express_checkout_details_is_successful() {
		$mock_response = 'TOKEN=EC%2d6EC97401PF4449255'.

		                 // API Info
		                 '&CHECKOUTSTATUS=PaymentActionNotInitiated'.
		                 '&TIMESTAMP=2011%2d08%2d25T08%3a04%3a26Z'.
		                 '&CORRELATIONID=b5ae9bd5c735f'.
		                 '&ACK=Success'.
		                 '&VERSION=114%2e0'.
		                 '&BUILD=2085867'.

		                 // Payer info
						 '&EMAIL=visa_1304648966_per%40garyc40%2ecom'.
		                 '&PAYERID=BC798KQ2QU22W'.
		                 '&PAYERSTATUS=verified'.
		                 '&FIRSTNAME=Test'.
		                 '&LASTNAME=User'.
		                 '&COUNTRYCODE=US'.
		                 '&SHIPTONAME=Gary%20Cao'.
		                 '&SHIPTOSTREET=1%20Infinite%20Loop'.
		                 '&SHIPTOSTREET2=Apple%20Headquarter'.
		                 '&SHIPTOCITY=Cupertino'.
		                 '&SHIPTOSTATE=CA'.
		                 '&SHIPTOZIP=95014'.
		                 '&SHIPTOCOUNTRYCODE=US'.
		                 '&SHIPTOPHONENUM=%28877%29%20412%2d7753'.
		                 '&SHIPTOCOUNTRYNAME=United%20States'.
		                 '&ADDRESSSTATUS=Unconfirmed'.

		                 // Legacy parameters (old API)
		                 '&CURRENCYCODE=JPY'.
		                 '&AMT=15337'.
		                 '&ITEMAMT=13700'.
		                 '&SHIPPINGAMT=1500'.
		                 '&HANDLINGAMT=0'.
		                 '&TAXAMT=137'.
		                 '&DESC=Order%20for%20example%2ecom'.
		                 '&INVNUM=E84A90G94'.
		                 '&NOTIFYURL=http%3a%2f%2fexample%2ecom%2fipn'.
		                 '&INSURANCEAMT=0'.
		                 '&SHIPDISCAMT=0'.

		                 // Legacy parameters (old API)
		                 '&L_NAME0=Gold%20Cart%20Plugin'.
		                 '&L_NAME1=Member%20Access%20Plugin'.
		                 '&L_NAME2=Amazon%20S3'.
		                 '&L_QTY0=1'.
		                 '&L_QTY1=1'.
		                 '&L_QTY2=1'.
		                 '&L_TAXAMT0=40'.
		                 '&L_TAXAMT1=50'.
		                 '&L_TAXAMT2=47'.
		                 '&L_AMT0=4000'.
		                 '&L_AMT1=5000'.
		                 '&L_AMT2=4700'.
		                 '&L_DESC0=Gold%20Cart%20extends%20your%20WP%20e%2dCommerce%20store%20by%20enabling%20additional%20features%20and%20functionality%2e'.
		                 '&L_DESC1=Create%20pay%20to%20view%20subscription%20sites'.
		                 '&L_DESC2=This%20Plugin%20allows%20downloadable%20products%20on%20your%20WP%20e%2dCommerce%20site%20to%20be%20hosted%20on%20Amazon%20S3%2e'.
		                 '&L_ITEMWEIGHTVALUE0=%20%20%200%2e00000'.
		                 '&L_ITEMWEIGHTVALUE1=%20%20%200%2e00000'.
		                 '&L_ITEMWEIGHTVALUE2=%20%20%200%2e00000'.
		                 '&L_ITEMLENGTHVALUE0=%20%20%200%2e00000'.
		                 '&L_ITEMLENGTHVALUE1=%20%20%200%2e00000'.
		                 '&L_ITEMLENGTHVALUE2=%20%20%200%2e00000'.
		                 '&L_ITEMWIDTHVALUE0=%20%20%200%2e00000'.
		                 '&L_ITEMWIDTHVALUE1=%20%20%200%2e00000'.
		                 '&L_ITEMWIDTHVALUE2=%20%20%200%2e00000'.
		                 '&L_ITEMHEIGHTVALUE0=%20%20%200%2e00000'.
		                 '&L_ITEMHEIGHTVALUE1=%20%20%200%2e00000'.
		                 '&L_ITEMHEIGHTVALUE2=%20%20%200%2e00000'.

		                 // Payment Information
		                 '&PAYMENTREQUEST_0_CURRENCYCODE=JPY'.
		                 '&PAYMENTREQUEST_0_AMT=15337'.
		                 '&PAYMENTREQUEST_0_ITEMAMT=13700'.
		                 '&PAYMENTREQUEST_0_SHIPPINGAMT=1500'.
		                 '&PAYMENTREQUEST_0_HANDLINGAMT=0'.
		                 '&PAYMENTREQUEST_0_TAXAMT=137'.
		                 '&PAYMENTREQUEST_0_DESC=Order%20for%20example%2ecom'.
		                 '&PAYMENTREQUEST_0_INVNUM=E84A90G94'.
		                 '&PAYMENTREQUEST_0_NOTIFYURL=http%3a%2f%2fexample%2ecom%2fipn'.
		                 '&PAYMENTREQUEST_0_INSURANCEAMT=0'.
		                 '&PAYMENTREQUEST_0_SHIPDISCAMT=0'.
		                 '&PAYMENTREQUEST_0_INSURANCEOPTIONOFFERED=false'.

		                  // Item Information
		                 '&L_PAYMENTREQUEST_0_NAME0=Gold%20Cart%20Plugin'.
		                 '&L_PAYMENTREQUEST_0_NAME1=Member%20Access%20Plugin'.
		                 '&L_PAYMENTREQUEST_0_NAME2=Amazon%20S3'.
		                 '&L_PAYMENTREQUEST_0_QTY0=1'.
		                 '&L_PAYMENTREQUEST_0_QTY1=1'.
		                 '&L_PAYMENTREQUEST_0_QTY2=1'.
		                 '&L_PAYMENTREQUEST_0_TAXAMT0=40'.
		                 '&L_PAYMENTREQUEST_0_TAXAMT1=50'.
		                 '&L_PAYMENTREQUEST_0_TAXAMT2=47'.
		                 '&L_PAYMENTREQUEST_0_AMT0=4000'.
		                 '&L_PAYMENTREQUEST_0_AMT1=5000'.
		                 '&L_PAYMENTREQUEST_0_AMT2=4700'.
		                 '&L_PAYMENTREQUEST_0_DESC0=Gold%20Cart%20extends%20your%20WP%20e%2dCommerce%20store%20by%20enabling%20additional%20features%20and%20functionality%2e'.
		                 '&L_PAYMENTREQUEST_0_DESC1=Create%20pay%20to%20view%20subscription%20sites'.
		                 '&L_PAYMENTREQUEST_0_DESC2=This%20Plugin%20allows%20downloadable%20products%20on%20your%20WP%20e%2dCommerce%20site%20to%20be%20hosted%20on%20Amazon%20S3%2e'.
		                 '&L_PAYMENTREQUEST_0_ITEMWEIGHTVALUE0=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMWEIGHTVALUE1=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMWEIGHTVALUE2=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMLENGTHVALUE0=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMLENGTHVALUE1=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMLENGTHVALUE2=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMWIDTHVALUE0=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMWIDTHVALUE1=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMWIDTHVALUE2=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMHEIGHTVALUE0=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMHEIGHTVALUE1=%20%20%200%2e00000'.
		                 '&L_PAYMENTREQUEST_0_ITEMHEIGHTVALUE2=%20%20%200%2e00000'.

		                 // Errors
		                 '&PAYMENTREQUESTINFO_0_ERRORCODE=0';

		$this->bogus->http->returnsByValue( 'post', $mock_response );
		$response = $this->bogus->get_details_for( $this->token );

		$this->assertTrue( $response->is_successful() );
		$this->assertFalse( $response->has_errors() );

		// API Info
		$this->assertTrue( $response->is_checkout_not_initiated() );
		$this->assertFalse( $response->is_checkout_failed() );
		$this->assertFalse( $response->is_checkout_in_progress() );
		$this->assertFalse( $response->is_checkout_completed() );
		$this->assertEqual( $response->get( 'checkout_status' ), 'Not-Initiated'         );
		$this->assertEqual( $response->get( 'token'           ), 'EC-6EC97401PF4449255' );
		$this->assertEqual( $response->get( 'timestamp'       ), 1314259466             );
		$this->assertEqual( $response->get( 'datetime'        ), '2011-08-25T08:04:26Z' );
		$this->assertEqual( $response->get( 'correlation_id'  ), 'b5ae9bd5c735f'        );
		$this->assertEqual( $response->get( 'version'         ), '114.0'                 );
		$this->assertEqual( $response->get( 'build'           ), '2085867'              );

		// Payer Information
		$mock_payer = (Object) array(
			'email'        => 'visa_1304648966_per@garyc40.com',
			'id'           => 'BC798KQ2QU22W',
			'status'       => 'verified',
			'shipping_status' => 'Unconfirmed',
			'first_name'   => 'Test',
			'last_name'    => 'User',
			'country'      => 'US',
		);
		$this->assertEqual( $response->get( 'payer' ), $mock_payer );

		// Shipping Address
		$mock_shipping_address = array(
			'name'         => 'Gary Cao',
			'street'       => '1 Infinite Loop',
			'street2'      => 'Apple Headquarter',
			'city'         => 'Cupertino',
			'state'        => 'CA',
			'zip'          => '95014',
			'country_code'  => 'US',
			'country'      => 'United States',
			'phone'        => '(877) 412-7753',
		);
		$this->assertEqual( $response->get( 'shipping_address' ), $mock_shipping_address );

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

	public function test_correct_response_is_returned_when_set_express_checkout_fails() {
		$mock_response = 'ACK=Failure&CORRELATIONID=224f0e4a32d14&TIMESTAMP=2011%2d07%2d05T13%253A23%253A52Z&VERSION=2%2e30000&BUILD=1%2e0006&TOKEN=EC%2d1OIN4UJGFOK54YFV&L_ERRORCODE0=10412&L_SHORTMESSAGE0=Duplicate%20invoice&L_LONGMESSAGE0=Payment%20has%20already%20been%20made%20for%20this%20InvoiceID.&L_SEVERITYCODE0=3&L_ERRORCODE1=10010&L_SHORTMESSAGE1=Invalid%20Invoice&L_LONGMESSAGE1=Non-ASCII%20invoice%20id%20is%20not%20supported.&L_SEVERITYCODE1=3';
		$this->bogus->http->returnsByValue( 'post', $mock_response );
		$response = $this->bogus->setup_purchase( $this->setup_purchase_options );

		$this->assertFalse( $response->is_successful() );
		$this->assertTrue( $response->has_errors() );
		$this->assertEqual( $response->get( 'timestamp'      ), 1309872232             );
		$this->assertEqual( $response->get( 'datetime'       ), '2011-07-05T13:23:52Z' );
		$this->assertEqual( $response->get( 'correlation_id' ), '224f0e4a32d14'        );
		$this->assertEqual( $response->get( 'version'        ), '2.30000'              );
		$this->assertEqual( $response->get( 'build'          ), '1.0006'               );

		$expected_errors = array(
			array(
				'code'    => 10412,
				'message' => 'Duplicate invoice',
				'details' => 'Payment has already been made for this InvoiceID.',
			),

			array(
				'code'    => 10010,
				'message' => 'Invalid Invoice',
				'details' => 'Non-ASCII invoice id is not supported.',
			),
		);
		$actual_errors = $response->get_errors();
		$this->assertEqual( $actual_errors, $expected_errors );
	}

	public function test_correct_response_is_returned_when_set_express_checkout_is_successful_with_warning() {
		$mock_response = 'ACK=SuccessWithWarning&CORRELATIONID=224f0e4a32d14&TIMESTAMP=2011%2d07%2d05T13%253A23%253A52Z&VERSION=2%2e30000&BUILD=1%2e0006&TOKEN=EC%2d1OIN4UJGFOK54YFV&L_ERRORCODE0=10412&L_SHORTMESSAGE0=Duplicate%20invoice&L_LONGMESSAGE0=Payment%20has%20already%20been%20made%20for%20this%20InvoiceID.&L_SEVERITYCODE0=3&L_ERRORCODE1=10010&L_SHORTMESSAGE1=Invalid%20Invoice&L_LONGMESSAGE1=Non-ASCII%20invoice%20id%20is%20not%20supported.&L_SEVERITYCODE1=3';

		$this->bogus->http->returnsByValue( 'post', $mock_response );
		$response = $this->bogus->setup_purchase( $this->setup_purchase_options );

		$this->assertTrue( $response->is_successful() );
		$this->assertTrue( $response->has_errors() );
		$this->assertEqual( $response->get( 'token'          ), 'EC-1OIN4UJGFOK54YFV'  );
		$this->assertEqual( $response->get( 'timestamp'      ), 1309872232             );
		$this->assertEqual( $response->get( 'datetime'       ), '2011-07-05T13:23:52Z' );
		$this->assertEqual( $response->get( 'correlation_id' ), '224f0e4a32d14'        );
		$this->assertEqual( $response->get( 'version'        ), '2.30000'              );
		$this->assertEqual( $response->get( 'build'          ), '1.0006'               );

		$expected_errors = array(
			array(
				'code'    => 10412,
				'message' => 'Duplicate invoice',
				'details' => 'Payment has already been made for this InvoiceID.',
			),

			array(
				'code'    => 10010,
				'message' => 'Invalid Invoice',
				'details' => 'Non-ASCII invoice id is not supported.',
			),
		);
		$actual_errors = $response->get_errors();
		$this->assertEqual( $actual_errors, $expected_errors );

	}
}

require_once( PHP_MERCHANT_PATH . '/common/http-curl.php' );
Mock::generate( 'PHP_Merchant_HTTP_CURL' );

class PHP_Merchant_Paypal_Express_Checkout_Bogus extends PHP_Merchant_Paypal_Express_Checkout
{
	public $http;

	public function __construct( $options = array() ) {
		$options['http_client'] = new MockPHP_Merchant_HTTP_CURL();
		parent::__construct( $options );
	}
}
