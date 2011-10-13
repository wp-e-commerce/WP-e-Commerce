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
			'invoice_id'       => 'PAYMENTREQUEST_0_INVNUM',
		);
		
		$request = array(
			'PAYMENTREQUEST_0_AMT' => $this->format( $amt ),
			'PAYMENTREQUEST_0_CURRENCYCODE' => $this->options['currency'],
			'PAYMENTREQUEST_0_PAYMENTACTION' => $action,
		);
		
		$i = 0;
		foreach ( $this->options['items'] as $item ) {
			$item_optionals = array(
				'description' => "L_PAYMENTREQUEST_0_DESC{$i}",
				'tax'         => "L_PAYMENTREQUEST_0_TAXAMT{$i}",
				'url'         => "L_PAYMENTREQUEST_0_ITEMURL{$i}",
			);
			
			$request["L_PAYMENTREQUEST_0_NAME{$i}"] = $item['name'];
			$request["L_PAYMENTREQUEST_0_AMT{$i}"] = $item['amount'];
			$request["L_PAYMENTREQUEST_0_QTY{$i}"] = $item['quantity'];
			
			foreach ( $item_optionals as $key => $param ) {
				if ( ! empty( $this->options[$key] ) )
					$request[$param] = $this->options[$key];
			}
		}
	}
	
	protected function add_address( $type = 'both' ) {
		$map = array(
			'shipping' => array(
				'name'     => 'PAYMENTREQUEST_0_SHIPTONAME',
				'address1' => 'PAYMENTREQUEST_0_SHIPTOSTREET',
				'address2' => 'PAYMENTREQUEST_0_SHIPTOSTREET2',
				'city'     => 'PAYMENTREQUEST_0_SHIPTOCITY',
				'state'    => 'PAYMENTREQUEST_0_SHIPTOSTATE',
				'zip'      => 'PAYMENTREQUEST_0_SHIPTOZIP',
				'country'  => 'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE',
				'phone'    => 'PAYMENTREQUEST_0_SHIPTOPHONENUM',
			),
		);
		$request = array();
		
		if ( $type != 'billing' ) {
			foreach ( $map[$type] as $key => $param ) {
				if ( )
			}
		}
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
			'CANCEL_URL' => $this->options['cancel_url'],
		);
		
		foreach ( $optionals as $key => $param ) {
			if ( ! empty( $this->options[$key] ) )
				$request[$param] = $this->options[$key];
		}
		
		$this->add_payment( $action, $amt );
		
		if ( ! empty( $this->options['shipping'] ) && ! empty( $this->options['address_override'] ) )
			$this->add_address( 'shipping' );
		
		$this->request = array_merge( $this->request, $request );
	}
	
	public function setup_purchase( $amt, $options = array() ) {
		$this->requires( 'return_url', 'cancel_url' );
		$this->commit( 'SetExpressCheckout', $this->build_setup_request( 'Sale', $amt, $options ) );
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