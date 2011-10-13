<?php

require_once( PHP_MERCHANT_PATH . '/paypal.php' );

class PHP_Merchant_Paypal_Test extends UnitTestCase
{
	public function __construct() {
		parent::__construct( 'PHP_Merchant_Paypal test cases' );
	}
}

class PHP_Merchant_Paypal_Bogus extends PHP_Merchant_Paypal
{
	public $request;
}