<?php
require_once( 'paypal.php' );
require_once( 'paypal-express-checkout-response.php' );

class PHP_Merchant_Paypal_Express_Checkout extends PHP_Merchant_Paypal
{
	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	protected function add_payment( $action ) {
		$request = array(
			'PAYMENTREQUEST_0_AMT'           => $this->format( $this->options['amount'] ),
			'PAYMENTREQUEST_0_CURRENCYCODE'  => $this->options['currency'],
			'PAYMENTREQUEST_0_PAYMENTACTION' => $action,
		);

		foreach ( array( 'subtotal', 'shipping', 'handling', 'tax' ) as $key ) {
			if ( isset( $this->options[$key] ) )
				$this->options[$key] = $this->format( $this->options[$key] );
		}

		$request += phpme_map( $this->options, array(
			'PAYMENTREQUEST_0_ITEMAMT'     => 'subtotal',
			'PAYMENTREQUEST_0_SHIPPINGAMT' => 'shipping',
			'PAYMENTREQUEST_0_HANDLINGAMT' => 'handling',
			'PAYMENTREQUEST_0_TAXAMT'      => 'tax',
			'PAYMENTREQUEST_0_DESC'        => 'description',
			'PAYMENTREQUEST_0_INVNUM'      => 'invoice',
			'PAYMENTREQUEST_0_NOTIFYURL'   => 'notify_url',
		) );

		$subtotal = 0;

		$i = 0;
		foreach ( $this->options['items'] as $item ) {
			$item_optionals = array(
				'description' => "L_PAYMENTREQUEST_0_DESC{$i}",
				'tax'         => "L_PAYMENTREQUEST_0_TAXAMT{$i}",
				'url'         => "L_PAYMENTREQUEST_0_ITEMURL{$i}",
			);

			$item['amount'] = $this->format( $item['amount'] );
			$request += phpme_map( $item, array(
				"L_PAYMENTREQUEST_0_NAME{$i}" => 'name',
				"L_PAYMENTREQUEST_0_AMT{$i}"  => 'amount',
				"L_PAYMENTREQUEST_0_QTY{$i}"  => 'quantity',
			) );

			foreach ( $item_optionals as $key => $param ) {
				if ( ! empty( $this->options['items'][$i][$key] ) )
					if ( $key == 'tax' )
						$request[$param] = $this->format( $this->options['items'][$i][$key] );
					else
						$request[$param] = $this->options['items'][$i][$key];
			}

			$i ++;
		}

		return $request;
	}

	protected function add_address() {
		$map = array(
			'name'     => 'PAYMENTREQUEST_0_SHIPTONAME',
			'street'   => 'PAYMENTREQUEST_0_SHIPTOSTREET',
			'street2'  => 'PAYMENTREQUEST_0_SHIPTOSTREET2',
			'city'     => 'PAYMENTREQUEST_0_SHIPTOCITY',
			'state'    => 'PAYMENTREQUEST_0_SHIPTOSTATE',
			'zip'      => 'PAYMENTREQUEST_0_SHIPTOZIP',
			'country'  => 'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE',
			'phone'    => 'PAYMENTREQUEST_0_SHIPTOPHONENUM',
		);

		$request = array();

		foreach ( $map as $key => $param ) {
			if ( ! empty( $this->options['shipping_address'][$key] ) )
				$request[$param] = $this->options['shipping_address'][$key];
		}

		return $request;
	}

	protected function build_checkout_request( $action, $options = array() ) {
		$request = array();

		if ( isset( $this->options['return_url'] ) )
			$request['RETURNURL'] = $this->options['return_url'];

		if ( isset( $this->options['cancel_url'] ) )
			$request['CANCELURL'] = $this->options['cancel_url'];

		$request += phpme_map( $this->options, array(
			'MAXAMT'       => 'max_amount',
			'ALLOWNOTE'    => 'allow_note',
			'ADDROVERRIDE' => 'address_override',
			'TOKEN'        => 'token',
			'PAYERID'      => 'payer_id',
		) );

		if ( ! empty( $this->options['shipping'] ) && ! empty( $this->options['address_override'] ) )
			$request += $this->add_address();

		$request += $this->add_payment( $action );
		return $request;
	}

	public function setup_purchase( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( 'amount', 'return_url', 'cancel_url' );
		$request = $this->build_checkout_request( 'Sale', $options );

		$response_str = $this->commit( 'SetExpressCheckout', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	public function build_get_details_request( $token ) {
		return array(
			'TOKEN' => $token,
		);
	}

	public function get_details_for( $token ) {
		$request = $this->build_get_details_request( $token );
		$response_str = $this->commit( 'GetExpressCheckoutDetails', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	public function purchase( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( 'amount', 'token', 'payer_id' );
		$request = $this->build_checkout_request( 'Sale', $options );

		$response_str = $this->commit( 'DoExpressCheckoutPayment', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	public function authorize() {

	}

	public function capture() {

	}

	public function void() {

	}

	public function credit() {

	}
}