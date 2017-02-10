<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * Support for:
 *
 * 1. `WP_DEVELOP_DIR` and `WP_TESTS_DIR` environment variables
 * 2. Plugin installed inside of WordPress.org developer checkout
 * 3. Tests checked out to /tmp
 *
 * @package wp-e-commerce
 */

$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

if ( false !== getenv( 'WP_DEVELOP_DIR' ) && file_exists( getenv( 'WP_DEVELOP_DIR' ) . '/includes/bootstrap.php' ) ) {
	$GLOBALS['test_root'] = getenv( 'WP_DEVELOP_DIR' );
} elseif ( false !== getenv( 'WP_TESTS_DIR' ) && file_exists( getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php' )  ) {
	$GLOBALS['test_root'] = getenv( 'WP_TESTS_DIR' );
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$GLOBALS['test_root'] = '../../../../tests/phpunit';
} elseif ( file_exists( '../../../../../wordpress-develop/tests/phpunit/includes/bootstrap.php' ) ) {
	$GLOBALS['test_root'] = '../../../../../wordpress-develop/tests/phpunit';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$GLOBALS['test_root'] = '/tmp/wordpress-tests-lib';
}

require_once $GLOBALS['test_root'] . '/includes/functions.php';

/**
 * Activates the WP eCommerce plugin in WordPress so it can be tested.
 */
function _wpsc_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-shopping-cart.php';
}
tests_add_filter( 'muplugins_loaded', '_wpsc_manually_load_plugin' );

ob_start();
require $GLOBALS['test_root'] . '/includes/bootstrap.php';
ob_end_clean();
