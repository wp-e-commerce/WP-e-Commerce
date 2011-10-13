<?php

class PHP_Merchant_Paypal_IPN
{
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	const LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';

	private $verified = false;
	private $data = array();

	public function __construct( $data = false, $test = false ) {
		if ( $data === false )
			$data = $_POST;

		$verifying_data = array( 'cmd' => '_notify-validate' );
		$verifying_data += $data;
		$http = new PHP_Merchant_HTTP_CURL();
		$url = $test ? self::SANDBOX_URL : self::LIVE_URL;
		$verifying_response = $http->post( $url, $verifying_data );
		var_dump( $verifying_data );
var_dump( $verifying_response );
$verifying_response = true;
		if ( $verifying_response == 'VERIFIED' ) {
			$this->verified = true;

			/*

			There's some confusing stuff regarding payment information:
			mc_gross = total amount = total tax + item subtotal + total shipping
			mc_gross_$i = item amount * quantity + item shipping

			See: https://www.x.com/developers/paypal/forums/ipn/pdt/mcgross-does-not-include-tax-right

			IPN only returns the item gross amount, so the single item amount will need to be
			calculated so that the $this->data array is consistent with other Paypal Response classes.

			*/
			$this->data = phpme_map( $data, array(
				'transaction_id'   => 'txn_id',
				'transaction_type' => 'txn_type',
				'handling'         => 'mc_handling',
				'shipping'         => 'mc_shipping',
				'exchange_rate'    => 'exchange_rate',
				'invoice'          => 'invoice',
				'currency'         => 'mc_currency',
				'fee'              => 'mc_fee',
				'total'            => 'mc_gross',
				'amount'           => 'mc_gross',
				'payment_status'   => 'payment_status',
				'tax'              => 'tax',
			) );

			// Strangely, Canceled_Reversal in IPN response is actually Canceled-Reversal in normal Paypal Express responses.
			// Need to change the underscore to hyphen to maintain consistency
			$this->data['payment_status'] = str_replace( '_', '-', $this->data['payment_status'] );

			$i = 1;
			$this->data['items'] = array();
			while ( isset( $data["item_name{$i}"] ) ) {
				$item = phpme_map( $data, array(
					'name' => "item_name{$i}",
					'quantity' => "quantity{$i}",
				) );

				$item['shipping'] = $shipping = isset( $data["mc_shipping{$i}"] ) ? $data["mc_shipping{$i}"] : 0;
				$item['handling'] = $handling = isset( $data["mc_handling{$i}"] ) ? $data["mc_handling{$i}"] : 0;
				$item['amount'] = ( $data["mc_gross_{$i}"] - $shipping - $handling ) / $item['quantity'];
				$this->data['items'][] = $item;
				$i++;
			}

			$this->data['payer'] = phpme_map( $data, array(
				'first_name'    => 'first_name',
				'last_name'     => 'last_name',
				'business_name' => 'payer_business_name',
				'status'        => 'payer_status',
				'id'            => 'payer_id',
				'email'         => 'payer_email',
			) );

			$this->data['address'] = phpme_map( $data, array(
				'street'       => 'address_street',
				'zip'          => 'address_zip',
				'city'         => 'address_city',
				'state'        => 'address_state',
				'country'      => 'address_country',
				'country_code' => 'address_country_code',
				'name'         => 'address_name',
				'status'       => 'address_status',
				'phone'        => 'contact_phone',
			) );
		}
	}

	public function is_verified() {
		return $this->verified;
	}

	public function get( $item ) {
		return isset( $this->data[$item] ) ? $this->data[$item] : null;
	}

	public function get_data() {
		return $this->data;
	}

	public function is_payment_completed() {
		return $this->data['payment_status'] == 'Completed' || $this->data['payment_status'] == 'Processed';
	}

	public function is_payment_pending() {
		return $this->data['payment_status'] == 'Pending';
	}

	public function is_payment_refunded() {
		return $this->data['payment_status'] == 'Refunded';
	}

	public function is_payment_denied() {
		return $this->data['payment_status'] == 'Denied';
	}
}