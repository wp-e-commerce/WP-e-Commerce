<?php

class PHP_Merchant_Paypal_Pro_Response extends PHP_Merchant_Paypal_Response
{
	public function __construct( $response_str ) {
		parent::__construct( $response_str );
	}

	public function is_checkout_not_initiated() {

	}

	public function is_checkout_failed() {

	}

	public function is_checkout_in_progress() {

	}

	public function is_checkout_completed() {

	}

	public function is_payment_completed() {

	}

	public function is_payment_pending() {

	}

	public function is_payment_refunded() {

	}

	public function is_payment_denied() {

	}
}
