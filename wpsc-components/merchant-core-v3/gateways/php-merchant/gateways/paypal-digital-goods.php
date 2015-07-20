<?php

require_once( 'paypal-express-checkout.php' );
require_once( 'paypal-express-checkout-response.php' );

class PHP_Merchant_Paypal_Digital_Goods extends PHP_Merchant_Paypal_Express_Checkout {
	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	/**
	 * Creates and returns the payment component of a PayPal Digital Goods NVP API request.
	 *
	 * PayPal requires the category component for all items in a digital goods purchase to be set as Digital.
	 * This function specifies that all goods are digital, then calls @see parent::add_payment() to create
	 * the rest of the API request (which is the same as a vanilla Express Checkout request).
	 *
	 * @uses parent::add_payment() to create non digital goods components of the request.
	 * @return Array An array of name value pairs for each element representing a payment in a PayPal Digital Goods NVP API request.
	 */
	protected function add_payment( $action ) {
		$request = parent::add_payment( $action );

		// Make sure PayPal knows all goods are digital
		for( $i = 0; $i < count( $this->options['items'] ); $i++ ) {
			$request += array( "L_PAYMENTREQUEST_0_ITEMCATEGORY{$i}" => 'Digital' );
		}

		$request['BUTTONSOURCE'] = 'WPeC_Cart_DG';

		return $request;
	}

	/**
	 * For Digital Goods purchases, PayPal requires the PAYMENTREQUEST_n_ITEMAMT. This function sets the 'items' flag to required
	 * then calls @see parent::setup_purchase() to initiate an Express Checkout payment.
	 *
	 * @uses self::requires() to flag 'items' as required
	 * @uses parent::setup_purchase() to create and make the request.
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response An object containing the details of PayPal's response to the request.
	 */
	public function setup_purchase( $options = array(), $action = 'Sale' ) {
		$options['no_shipping'] = true;
		return parent::setup_purchase( $options, $action );
	}

	/**
	 * For Digital Goods purchases, PayPal requires the PAYMENTREQUEST_n_ITEMAMT. This function sets the 'items' flag to required
	 * then calls @see parent::setup_purchase() to complete the payment.
	 *
	 * @uses self::requires() to flag 'items' as required
	 * @uses parent::setup_purchase() to create and make the request.
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response An object containing the details of PayPal's response to the request.
	 */
	public function purchase( $options = array(), $action = 'Sale' ) {
		$options['no_shipping'] = true;
		return parent::purchase( $options, $action );
	}


	/**
	 * The Javascript to invoke the digital goods in context checkout process.
	 *
	 * No need to call this function manually, required scripts are automatically printed with @see print_buy_buttion().
	 * If you do print this script manually, print it after the button in the DOM to ensure the
	 * click event is properly hooked.
	 */
	public function get_script( $args = array() ){

		if( empty( $args['element_id'] ) )
			$args['element_id'] = 'paypal-submit';

		$dg_script  = '<script src ="https://www.paypalobjects.com/js/external/dg.js" type="text/javascript"></script>'
			. '<script>'
			. 'var dg = new PAYPAL.apps.DGFlow({'
			. 'trigger: "' . $args['element_id'] . '"' // the ID of the HTML element which calls setExpressCheckout
			. '}); </script>';

		return $dg_script;
	}


}
