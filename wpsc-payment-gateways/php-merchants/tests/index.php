<?php

require_once( 'simpletest/autorun.php' );

class PHP_Merchant_Test_Suite extends TestSuite
{
	function __construct() {
		parent::__construct( 'PHP Merchant Tests' );
		$tests = array(
			'php-merchant',
		);
		
		$dir = dirname( __FILE__ );
		
		foreach ( $tests as $test ) {
			$this->addFile( $dir . '/' . $test . '.php' );
		}
	}
}