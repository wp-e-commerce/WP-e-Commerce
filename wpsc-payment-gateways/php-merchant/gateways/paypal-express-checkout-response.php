<?php
class PHP_Merchant_Paypal_Express_Checkout_Response extends PHP_Merchant_Paypal_Response
{	
	public function __construct( $response_str ) {
		parent::__construct( $response_str );
		$p =& $this->params;
		
		// more readable checkout status
		switch ( $p['CHECKOUTSTATUS'] ) {
			case 'PaymentActionNotInitiated':
				$this->options['checkout_status'] = 'NotInitiated';
				break;
				
			case 'PaymentActionFailed':
				$this->options['checkout_status'] = 'Failed';
				break;
				
			case 'PaymentActionInProgress':
				$this->options['checkout_status'] = 'InProgress';
				break;
				
			case 'PaymentCompleted':
				$this->options['checkout_status'] = 'Completed';
				break;
			
			default:
				$this->options['checkout_status'] = $p['CHECKOUT_STATUS'];
				break;
		}
		
		$this->options += phpme_map( $p, array(
			'currency'          => 'PAYMENTREQUEST_0_CURRENCYCODE',
			'total'             => 'PAYMENTREQUEST_0_AMT', // alias for "amount"
			'amount'            => 'PAYMENTREQUEST_0_AMT',
			'subtotal'          => 'PAYMENTREQUEST_0_ITEMAMT',
			'shipping'          => 'PAYMENTREQUEST_0_SHIPPINGAMT',
			'handling'          => 'PAYMENTREQUEST_0_HANDLINGAMT',
			'tax'               => 'PAYMENTREQUEST_0_TAXAMT',
			'description'       => 'PAYMENTREQUEST_0_DESC',
			'invoice'           => 'PAYMENTREQUEST_0_INVNUM',
			'notify_url'        => 'PAYMENTREQUEST_0_NOTIFYURL',
			'shipping_discount' => 'PAYMENTREQUEST_0_SHIPDISCAMT',
		) );
		
		$items = array();
		$i = 0;
		while ( isset( $p["L_PAYMENTREQUEST_0_NAME{$i}"] ) ) {
			$items[] = phpme_map( $p, array(
				'name'        => "L_PAYMENTREQUEST_0_NAME{$i}",
				'description' => "L_PAYMENTREQUEST_0_DESC{$i}",
				'amount'      => "L_PAYMENTREQUEST_0_AMT{$i}",
				'quantity'    => "L_PAYMENTREQUEST_0_QTY{$i}",
				'tax'         => "L_PAYMENTREQUEST_0_TAXAMT{$i}",
			), 'Object' );
			
			$i ++;
		}
		
		$this->options['items'] = $items;
		
		if ( isset( $p['PAYERID'] ) )
			$this->options['payer'] = phpme_map( $p, array(
				'email'      => 'EMAIL',
				'id'         => 'PAYERID',
				'status'     => 'PAYERSTATUS',
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
	
	public function is_checkout_not_initiated() {
		return $this->options['checkout_status'] == 'NotInitiated';
	}
	
	public function is_checkout_failed() {
		return $this->options['checkout_status'] == 'Failed';
	}
	
	public function is_checkout_in_progress() {
		return $this->options['checkout_status'] == 'InProgress';
	}
	
	public function is_checkout_completed() {
		return $this->options['checkout_status'] == 'Completed';
	}
}