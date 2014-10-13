<?php
// Check for Remote Tests
global $remote;
global $argv;
$remote = false;
if ( in_array( '--enable-remote', $argv ) || ! empty( $_GET['remote'] ) ) {
	$remote = true;
}
if ( $remote ) {
	require_once( 'simpletest/web_tester.php' );
	require_once( 'common/test-accounts.php' );
}

require_once( 'simpletest/varlog.php' );
require_once( 'simpletest/autorun.php' );

define( 'PHP_MERCHANT_PATH', realpath( '../' ) );
define( 'PHP_MERCHANT_TEST_PATH', dirname( __FILE__ ) );

class PHP_Merchant_Test_Suite extends TestSuite
{
	function __construct() {
		global $remote;
		parent::__construct( 'PHP Merchant Test Suite' );
		$tests = array(
			'common/php-merchant',
			'common/http-curl',
			'gateways/paypal',
			'gateways/paypal-express-checkout',
			'gateways/paypal-ipn',
		);

		// Since we are running the SimpleTest Tests from the command-line,
		// we are adding a command-line key for remote tests
		if ( $remote ) {
			$tests = array_merge( $tests, array(
				'remote/http-curl',
				'remote/paypal-express-checkout',
				'remote/paypal-ec-cert-x1',
				'remote/paypal-ec-cert-x2',
				'remote/paypal-ec-cert-x3',
				'remote/paypal-ec-cert-x4',
				'remote/paypal-ec-cert-x5',
				'remote/paypal-ec-cert-x6',
				'remote/paypal-dg-cert-x1',
				'remote/paypal-dg-cert-x2',
				'remote/paypal-dg-cert-x3',
				'remote/paypal-dg-cert-x4',
				'remote/paypal-pro-cert-x1',
				'remote/paypal-pro-cert-x2',
			) );
		}

		$dir = dirname( __FILE__ );

		foreach ( $tests as $test ) {
			$this->addFile( $dir . '/' . $test . '.php' );
		}
	}
}
