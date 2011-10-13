<?php

require_once( 'simpletest/autorun.php' );

define( 'PHP_MERCHANT_PATH', realpath( '../' ) );

class PHP_Merchant_Test_Suite extends TestSuite
{
	function __construct() {
		parent::__construct( 'PHP Merchant Test Suite' );
		$tests = array(
			'php-merchant',
			'paypal',
			'paypal-express-checkout',
		);
		
		$dir = dirname( __FILE__ );
		
		foreach ( $tests as $test ) {
			$this->addFile( $dir . '/' . $test . '.php' );
		}
	}
}