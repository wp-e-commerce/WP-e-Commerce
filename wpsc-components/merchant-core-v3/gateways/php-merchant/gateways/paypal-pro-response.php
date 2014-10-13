<?php

class PHP_Merchant_Paypal_Pro_Response extends PHP_Merchant_Paypal_Response
{
	public function __construct( $response_str ) {
		parent::__construct( $response_str );
		$p =& $this->params;

		if ( isset( $p['PAYMENTSTATUS'] ) )
			$this->options['payment_status'] = $p['PAYMENTSTATUS'];

		if ( isset( $p['TRANSACTIONID'] ) )
			$this->options['transaction_id'] = $p['TRANSACTIONID'];

		$this->options += phpme_map( $p, array(
			'currency'          => 'CURRENCYCODE',
			'total'             => 'AMT', // alias for "amount"
			'amount'            => 'AMT',
			'subtotal'          => 'ITEMAMT',
			'shipping'          => 'SHIPPINGAMT',
			'handling'          => 'HANDLINGAMT',
			'tax'               => 'TAXAMT',
			'description'       => 'DESC',
			'invoice'           => 'INVNUM',
			'notify_url'        => 'NOTIFYURL',
			'shipping_discount' => 'SHIPDISCAMT',
		) );

		if ( isset( $p['PAYERID'] ) )
			$this->options['payer'] = phpme_map( $p, array(
				'email'      => 'EMAIL',
				'id'         => 'PAYERID',
				'status'     => 'PAYERSTATUS',
				'shipping_status' => 'ADDRESSSTATUS',
				'first_name' => 'FIRSTNAME',
				'last_name'  => 'LASTNAME',
				'country'    => 'COUNTRYCODE',
			), 'Object' );

		if ( isset( $p['SHIPTONAME'] ) )
			$this->options['shipping_address'] = phpme_map( $p, array(
				'name'         => 'SHIPTONAME',
				'street'       => 'SHIPTOSTREET',
				'street2'      => 'SHIPTOSTREET2',
				'city'         => 'SHIPTOCITY',
				'state'        => 'SHIPTOSTATE',
				'zip'          => 'SHIPTOZIP',
				'country_code' => 'SHIPTOCOUNTRYCODE',
				'country'      => 'SHIPTOCOUNTRYNAME',
				'phone'        => 'SHIPTOPHONENUM',
			) );
	}

	public function is_payment_completed() {
		return in_array( $this->get( 'payment_status' ), array( 'Completed', 'Processed' ) );
	}

	public function is_payment_pending() {
		return $this->get( 'payment_status' ) == 'Pending';
	}

	public function is_payment_refunded() {
		return $this->get( 'payment_status' ) == 'Refunded';
	}

	public function is_payment_denied() {
		return $this->get( 'payment_status' ) == 'Denied';
	}}
