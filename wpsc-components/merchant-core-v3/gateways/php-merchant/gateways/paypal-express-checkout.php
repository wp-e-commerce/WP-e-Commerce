<?php
require_once( 'paypal.php' );
require_once( 'paypal-express-checkout-response.php' );

class PHP_Merchant_Paypal_Express_Checkout extends PHP_Merchant_Paypal {
	public function __construct( $options = array() ) {
		parent::__construct( $options );
	}

	/**
	 * Add the payment details to the PayPal request
	 *
	 * @param array $action
	 * @return array
	 * @since 3.9
	 */
	protected function add_payment( $action ) {

		// Total Payment details
		$request = array(
			'PAYMENTREQUEST_0_AMT'           => $this->format( $this->options['amount'] ),
			'PAYMENTREQUEST_0_CURRENCYCODE'  => $this->options['currency'],
			'PAYMENTREQUEST_0_PAYMENTACTION' => $action,
		);

		if ( $action === 'Sale' ) {
			$request['PAYMENTREQUEST_0_ALLOWEDPAYMENTMETHOD'] = 'InstantPaymentOnly';
		}

		foreach ( array( 'subtotal', 'shipping', 'handling', 'tax', 'amount', 'discount' ) as $key ) {
			if ( isset( $this->options[$key] ) ) {
				$this->options[$key] = $this->format( $this->options[$key] );
			}
		}
		
		if ( isset( $this->options[ 'discount' ] ) ) {
			$this->options['subtotal'] = $this->format( $this->options['subtotal'] - $this->options['discount'] );
		}

		$request += phpme_map( $this->options, array(
			'PAYMENTREQUEST_0_ITEMAMT'     => 'subtotal',
			'PAYMENTREQUEST_0_SHIPPINGAMT' => 'shipping',
			'PAYMENTREQUEST_0_HANDLINGAMT' => 'handling',
			'PAYMENTREQUEST_0_TAXAMT'      => 'tax',
			'PAYMENTREQUEST_0_DESC'        => 'description',
			'PAYMENTREQUEST_0_INVNUM'      => 'invoice',
			'PAYMENTREQUEST_0_NOTIFYURL'   => 'notify_url',
			'L_BILLINGTYPE0' 			   => 'billing_type',
			'L_BILLINGAGREEMENTDESCRIPTION0' => 'billing_description',
		) );

		// Apply a Discount if available
		$this->add_discount();

		// Shopping Cart details
		$i = 0;
		if ( is_array( $this->options['items'] ) ) {
			foreach ( $this->options['items'] as $item ) {
				// Options Fields
				$item_optionals = array(
					'description' => "L_PAYMENTREQUEST_0_DESC{$i}",
					'tax'         => "L_PAYMENTREQUEST_0_TAXAMT{$i}",
					'url'         => "L_PAYMENTREQUEST_0_ITEMURL{$i}",
					'number'	  => "L_PAYMENTREQUEST_0_NUMBER{$i}",
				);

				// Format Amount Field
				$item['amount'] = $this->format( $item['amount'] );

				// Required Fields
				$request += phpme_map( $item, array(
					"L_PAYMENTREQUEST_0_NAME{$i}" => 'name',
					"L_PAYMENTREQUEST_0_AMT{$i}"  => 'amount',
					"L_PAYMENTREQUEST_0_QTY{$i}"  => 'quantity',
				) );

				// No Shipping Field
				if ( isset( $this->options['no_shipping'] ) ) {
					$request["L_PAYMENTREQUEST_0_ITEMCATEGORY{$i}"] = 'DIGITAL';
				}

				foreach ( $item_optionals as $key => $param ) {
					if ( ! empty( $this->options['items'][$i][$key] ) )
						if ( $key == 'tax' ) {
							$request[$param] = $this->format( $this->options['items'][$i][$key] );
						} else {
							$request[$param] = $this->options['items'][$i][$key];
						}
				}

				$i ++;
			}
		}

		return $request;
	}

	/**
 	 * Add Discount for the Shopping Cart.
	 *
	 * Since PayPal doesn't have distinct support for discounts, we have to add the discount
	 * as a separate item with a negative value.
	 *
	 * @return void
 	 */
	protected function add_discount() {
		// Verify if a discount is set
		if ( isset( $this->options['discount'] ) && (float) $this->options['discount'] != 0 ) {
			$discount = (float) $this->options['discount'];
			$sub_total = (float) $this->options['subtotal'];

			// If discount amount is larger than or equal to the item total, we need to set item total to 0.01
			// because PayPal does not accept 0 item total.
			if ( $discount >= $sub_total ) {
				$discount = $sub_total - 0.01;
			}

			// if there's shipping, we'll take 0.01 from there
			if ( ! empty( $this->options['shipping'] ) ) {
				$this->options['shipping'] -= 0.01;
			} else {
				$this->options['amount'] = 0.01;
			}

			// Add the Discount as an Item
			$this->options['items'][] = array(
				'name' => __( 'Discount', 'wp-e-commerce' ),
				'amount' => - $discount,
				'quantity' => '1',
			);
		}
	}

	/**
	 * Add a shipping address to the PayPal request
	 *
	 * @return array
	 * @since 3.9
	 */
	protected function add_address() {
		$map = array(
			'name'     => 'PAYMENTREQUEST_0_SHIPTONAME',
			'street'   => 'PAYMENTREQUEST_0_SHIPTOSTREET',
			'street2'  => 'PAYMENTREQUEST_0_SHIPTOSTREET2',
			'city'     => 'PAYMENTREQUEST_0_SHIPTOCITY',
			'state'    => 'PAYMENTREQUEST_0_SHIPTOSTATE',
			'zip'      => 'PAYMENTREQUEST_0_SHIPTOZIP',
			'country'  => 'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE',
			'phone'    => 'PAYMENTREQUEST_0_SHIPTOPHONENUM',
		);

		$request = array();

		foreach ( $map as $key => $param ) {
			if ( ! empty( $this->options['shipping_address'][$key] ) ) {
				$request[$param] = $this->options['shipping_address'][$key];
			}
		}

		return $request;
	}

	/**
	 * Build the request array
	 *
	 * @param string $action
	 * @param array $options
	 * @return array
	 * @since 3.9
	 */
	protected function build_checkout_request( $action, $options = array() ) {
		$request = array();

		if ( isset( $this->options['return_url'] ) ) {
			$request['RETURNURL'] = $this->options['return_url'];
		}

		if ( isset( $this->options['cancel_url'] ) ) {
			$request['CANCELURL'] = $this->options['cancel_url'];
		}

		// Common Fields
		$request += phpme_map( $this->options, array(
			'AMT'          => 'amount',
			'MAXAMT'       => 'max_amount',
			'SOLUTIONTYPE' => 'solution_type',
			'ALLOWNOTE'    => 'allow_note',
			'ADDROVERRIDE' => 'address_override',
			'TOKEN'        => 'token',
			'PAYERID'      => 'payer_id',
			'TRANSACTIONID'=> 'transaction_id',
			'AUTHORIZATIONID'=> 'authorization_id',
			'MSGSUBID'	   => 'message_id',
			'INVOICEID'	   => 'invoice',
			'NOTE'         => 'note',
			'USERSELECTEDFUNDINGSOURCE' => 'user_funding_source',
		) );

		// Cart Customization Fields
		$request += phpme_map( $this->options, array(
			'LOGOIMG'			=> 'cart_logo',
			'CARTBORDERCOLOR'	=> 'cart_border',
		) );

		// RefundTransaction Fields
		$request += phpme_map( $this->options, array(
			'REFUNDTYPE'   => 'refund_type',
			'REFUNDSOURCE' => 'refund_source',
			'REFUNDADVICE' => 'refund_advice',
		) );

		// DoCapture Fields
		$request += phpme_map( $this->options, array(
			'COMPLETETYPE' => 'complete_type',
		) );

		if ( ! empty( $this->options['shipping_address'] ) && ! isset( $this->options['no_shipping'] ) ) {
			$request += $this->add_address();
		}

		if ( isset( $this->options['no_shipping'] ) ) {
			$request['NOSHIPPING'] = '1';
		}

		if ( $action != false ) {
			$request += $this->add_payment( $action );
		}

		// BN Code
		$request['BUTTONSOURCE'] = 'WPeC_Cart_EC';

		return $request;
	}

	/**
	 * Gateway implementation for SetExpressCheckout
	 *
	 * @param array $options
	 * @param string $action
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function setup_purchase( $options = array(), $action = 'Sale' ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( array( 'amount', 'return_url', 'cancel_url' ) );
		$request = $this->build_checkout_request( $action, $options );
		$response_str = $this->commit( 'SetExpressCheckout', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway impelementation for GetExpressCheckoutDetails
	 *
	 * @param string $token Authentication token returned by the SetExpressCheckout operation
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function get_details_for( $token ) {
		$request =  array( 'TOKEN' => $token );
		$response_str = $this->commit( 'GetExpressCheckoutDetails', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway impelementation for GetTransactionDetails
	 *
	 * @param string $transaction_id Unique identifier of a transaction.
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function get_transaction_details( $transaction_id ) {
		$request =  array( 'TRANSACTIONID' => $transaction_id );
		$response_str = $this->commit( 'GetTransactionDetails', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway implementation for DoExpressCheckout
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function purchase( $options = array(), $action = 'Sale' ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( array( 'amount', 'token', 'payer_id' ) );
		$request = $this->build_checkout_request( $action, $options );

		$response_str = $this->commit( 'DoExpressCheckoutPayment', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway implementation for DoAuthorize
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function authorize( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( array( 'amount', 'token', 'transaction_id' ) );
		$request = $this->build_checkout_request( false, $options );

		$response_str = $this->commit( 'DoAuthorization', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway implementation for DoCapture
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function capture( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( array( 'amount', 'complete_type', 'authorization_id' ) );
		$request = $this->build_checkout_request( false, $options );

		$response_str = $this->commit( 'DoCapture', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway implementation for DoVoid
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function void( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
		$this->requires( array( 'authorization_id' ) );
		$request = $this->build_checkout_request( false, $options );

		$response_str = $this->commit( 'DoVoid', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}

	/**
	 * Gateway implementation for RefundTransaction
	 *
	 * @param array $options
	 * @return PHP_Merchant_Paypal_Express_Checkout_Response
	 * @since 3.9
	 */
	public function credit( $options = array() ) {
		$this->options = array_merge( $this->options, $options );

		// Required Fields
		$this->requires( array( 'transaction_id' ) );

		// Conditionally required fields (one field at least is set)
		$this->conditional_requires( array( 'invoice' ) );

		// Amount is required if the refund is partial
		if ( strtolower( $this->options['refund_type'] ) === 'partial' ) {
			$this->requires( array( 'amount' ) );
		}

		$request = $this->build_checkout_request( false, $options );

		$response_str = $this->commit( 'RefundTransaction', $request );
		return new PHP_Merchant_Paypal_Express_Checkout_Response( $response_str );
	}
}
