<?php
require_once( 'paypal.php' );
require_once( 'paypal-pro-response.php' );

class PHP_Merchant_Paypal_Pro extends PHP_Merchant_Paypal
{
	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	/**
	 * Gateway implementation for DoExpressCheckout
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function purchase( $options = array(), $action = 'Sale' ) {

	}

	/**
	 * Gateway implementation for DoAuthorize
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function authorize( $options = array() ) {

	}

	/**
	 * Gateway implementation for DoCapture
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function capture( $options = array() ) {
		
	}

	/**
	 * Gateway implementation for DoVoid
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function void( $options = array() ) {
		
	}

	/**
	 * Gateway implementation for RefundTransaction
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function credit( $options = array() ) {
		
	}
}
