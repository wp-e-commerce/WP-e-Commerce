<?php
require_once( 'paypal.php' );

class PHP_Merchant_Paypal_Express_Checkout extends PHP_Merchant_Paypal
{
	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}
	
	protected function add_payment( $action, $amt ) {
		$optionals = array(
			'subtotal'         => 'PAYMENTREQUEST_0_ITEMAMT',
			'shipping'         => 'PAYMENTREQUEST_0_SHIPPINGAMT',
			'handling'         => 'PAYMENTREQUEST_0_HANDLINGAMT',
			'tax'              => 'PAYMENTREQUEST_0_TAXAMT',
			'description'      => 'PAYMENTREQUEST_0_DESC',
			'invoice'          => 'PAYMENTREQUEST_0_INVNUM',
			'notify_url'       => 'PAYMENTREQUEST_0_NOTIFYURL',
		);
		
		$request = array(
			'PAYMENTREQUEST_0_AMT' => $this->format( $amt ),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $this->options['currency'],
			'PAYMENTREQUEST_0_PAYMENTACTION' => $action,
		);
		
		foreach ( $optionals as $key => $param ) {
			if ( ! empty( $this->options[$key] ) )
				if ( in_array( $key, array( 'subtotal', 'shipping', 'handling', 'tax' ) ) )
					$request[$param] = $this->format( $this->options[$key] );
				else
					$request[$param] = $this->options[$key];
		}
		
		$subtotal = 0;
		
		$i = 0;
		foreach ( $this->options['items'] as $item ) {
			$item_optionals = array(
				'description' => "L_PAYMENTREQUEST_0_DESC{$i}",
				'tax'         => "L_PAYMENTREQUEST_0_TAXAMT{$i}",
				'url'         => "L_PAYMENTREQUEST_0_ITEMURL{$i}",
			);
			
			$request["L_PAYMENTREQUEST_0_NAME{$i}"] = $item['name'];
			$request["L_PAYMENTREQUEST_0_AMT{$i}"] = $this->format( $item['amount'] );
			$request["L_PAYMENTREQUEST_0_QTY{$i}"] = $item['quantity'];
			
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
	
	protected function build_setup_request( $action, $amt, $options = array() ) {
		$this->set_options( $options );
		
		$optionals = array(
			'token'            => 'TOKEN',
			'max_amount'       => 'MAXAMT',
			'allow_note'       => 'ALLOWNOTE',
			'address_override' => 'ADDROVERRIDE',
		);
		
		$request = array(
			'METHOD' => 'SetExpressCheckout',
			'RETURNURL' => $this->options['return_url'],
			'CANCELURL' => $this->options['cancel_url'],
		);
		
		foreach ( $optionals as $key => $param ) {
			if ( ! empty( $this->options[$key] ) )
				if ( is_bool( $this->options[$key] ) )
					$request[$param] = (int) $this->options[$key];
				else
					$request[$param] = $this->options[$key];
		}
		
		if ( ! empty( $this->options['shipping'] ) && ! empty( $this->options['address_override'] ) )
			$request += $this->add_address();
		
		$request += $this->add_payment( $action, $amt );
		
		return $request;
	}
	
	public function setup_purchase( $amt, $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( 'return_url', 'cancel_url' );
		$request = $this->build_setup_request( 'Sale', $amt, $options );

		$response_str = $this->commit( 'SetExpressCheckout', $request );
		return new PHP_Merchant_Paypal_Response( $response_str );
	}
	
	public function purchase() {
		
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