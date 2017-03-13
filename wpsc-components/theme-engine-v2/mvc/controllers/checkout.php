<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/checkout-wizard.php' );

class WPSC_Controller_Checkout extends WPSC_Controller {
	protected $current_step  = '';
	protected $wizard;
	protected $shipping_calculator;

	public function __construct() {
		parent::__construct();

		$this->check_cart_items();
		$this->title = wpsc_get_checkout_title();
		$this->init_checkout_wizard();
	}

	private function check_cart_items() {
		global $wpsc_cart;

		if ( count( $wpsc_cart->cart_items ) > 0 ) {
			return;
		}

		wp_redirect( wpsc_get_cart_url() );
		exit;
	}

	public function index() {
		if ( ! is_user_logged_in() ) {

			wpsc_update_customer_meta( 'checkout_after_login', true );
			wpsc_enqueue_script( 'wpsc-checkout' );

			if ( get_option( 'require_register' ) ) {
				$this->view = 'checkout-login-required';
			} else {
				$this->view = 'checkout-login-prompt';
			}

		} else {
			wp_redirect( wpsc_get_checkout_url( 'shipping-and-billing' ) );
			exit;
		}
	}

	/**
	 * Review Order method
	 *
	 * @return void
	 */
	public function review_order() {
		// Initialize Shipping Calculator
		$this->init_shipping_calculator();

		// View Settings
		$this->title .= ' → Review Order';
		$this->view = 'checkout-review-order';

		// If no shipping is available, show an error message.
		if ( wpsc_uses_shipping() && ! $this->shipping_calculator->has_quotes ) {
			$this->message_collection->add(
				__( 'Sorry, but we cannot ship products to your submitted address. Please either provide another shipping address or contact the store administrator about product availability to your location.', 'wp-e-commerce' ),
				'error'
			);
			return;
		}

		// Alert the user that the payment process is not complete.
		$this->message_collection->add(
			__( 'Your payment is not completed, please review your order details, select a Shipping method and press "Place Order" to complete your order', 'wp-e-commerce' ),
			'info'
		);

		// Shipping Selector Scripts and Filters
		$this->enqueue_shipping_price_simulator();

		add_filter( 'wpsc_checkout_shipping_method_form_button_title', array( &$this, 'review_order_button_title' ), 1, 100 );

		// Handle POST request
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_shipping_method' ) {
			$this->submit_review_order();
		}
	}

	/**
	 * Modify the Submit Order button title
	 *
	 * @return string
	 */
	public function review_order_button_title() {
		return __( 'Place Order', 'wp-e-commerce' );
	}

	/**
	 * Validates cart submission.
	 *
	 * @since  4.0
	 * @return bool Whether or not there are validation errors.
	 */
	private function validate_cart() {
		$validation = wpsc_validate_form( wpsc_get_checkout_shipping_form_args() );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return false;
		}

		return true;

	}

	/**
	 * Confirms the existence of the submitted shipping method and option.
	 *
	 * @param  string $submitted_value POSTed value for shipping method.
	 *
	 * @return boolean                 Whether or not shipping method was found.
	 */
	private function check_shipping_method( $submitted_value ) {
		global $wpsc_cart;

		$found = false;

		foreach ( $this->shipping_calculator->quotes as $module_name => $quotes ) {
			foreach ( $quotes as $option => $cost ) {
				$id = $this->shipping_calculator->ids[ $module_name ][ $option ];

				if ( $id == $submitted_value ) {
					$found = true;
					$wpsc_cart->update_shipping( $module_name, $option );
					break 2;
				}
			}
		}

		// Set the Shipping method
		if ( isset( $module_name ) && isset( $option ) ) {
			$this->shipping_calculator->set_active_method( $module_name, $option );
		}

		return $found;
	}

	/**
	 * Handle the Review Order page POST request.
	 *
	 * @return null|void
	 */
	private function submit_review_order() {
		global $wpsc_cart;

		if ( ! $this->verify_nonce( 'wpsc-checkout-form-shipping-method' ) ) {
			return null;
		}

		if ( ! $this->validate_cart() ) {
			return null;
		}

		// Checks shipping method
		if ( ! $this->check_shipping_method( $_POST['wpsc_shipping_option'] ) ) {
			return null;
		}

		// Update the Purchase Log
		$purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );
		$purchase_log    = new WPSC_Purchase_Log( $purchase_log_id );
		$purchase_log->set( 'base_shipping', $wpsc_cart->calculate_base_shipping() );
		$purchase_log->set( 'totalprice', $wpsc_cart->calculate_total_price() );
		$purchase_log->save();

		// Build the Redirection URL
		$url = add_query_arg( array_merge( $_GET, array( 'payment_gateway_callback' => 'confirm_transaction' ) ), wpsc_get_checkout_url( 'results' ) );

		// Redirect to Results Page
		wp_redirect( $url );
		exit;
	}

	public function shipping_and_billing() {
		$this->view = 'checkout-shipping-and-billing';

		wpsc_enqueue_script( 'wpsc-country-region' );
		wpsc_enqueue_script( 'wpsc-copy-billing-info' );
		wpsc_enqueue_script( 'wpsc-checkout' );

		$this->maybe_add_guest_account();

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_checkout_form' ) {
			$this->submit_shipping_and_billing();
		}
	}

	/**
	 * Maybe add UI for creating a guest account.
	 *
	 * By default, it will automatically generate a password and use the billing email as the username.
	 * This way, the customer has no other fields to fill out.
	 *
	 * @since  4.0
	 *
	 * @return bool Whether or not to add a UI for account creation on checkout.
	 */
	private function maybe_add_guest_account() {

		$email = wpsc_get_customer_meta( 'billingemail' );

		return apply_filters( 'wpsc_checkout_maybe_add_guest_account', ( ! is_user_logged_in() ) && get_option( 'users_can_register' ) && ! empty( $email ) && ! username_exists( $email ) );
	}

	private function submit_shipping_and_billing() {
		if ( ! $this->verify_nonce( 'wpsc-checkout-form' ) ) {
			return;
		}

		// wipe out completed steps because we're starting from scratch
		$this->wizard->reset();

		$form_args  = wpsc_get_checkout_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			$this->message_collection->add(
				__( 'Sorry, but it looks like there are some errors with your submitted information.', 'wp-e-commerce' ),
				'error'
			);
			wpsc_set_validation_errors( $validation, 'inline' );
			return;
		}

		if ( ! empty( $_POST['wpsc_copy_billing_details'] ) ) {
			_wpsc_copy_billing_details();
		} else {
			wpsc_update_customer_meta( 'wpsc_copy_billing_details', 'false' );
		}

		$this->save_shipping_and_billing_info();
	}

	public function get_purchase_log() {

		// see if an existing purchase log has been set for this user
		// otherwise create one
		$purchase_log_id = (int) wpsc_get_customer_meta( 'current_purchase_log_id' );

		$create = true;

		if ( $purchase_log_id ) {
			$purchase_log = new WPSC_Purchase_Log( $purchase_log_id );
			$create       = ! $purchase_log->exists();
		}

		if ( $create ) {
			wpsc_delete_customer_meta( 'current_purchase_log_id' );

			$purchase_log = new WPSC_Purchase_Log();

			$purchase_log->set( array(
				'user_ID'        => get_current_user_id(),
				'date'           => time(),
				'plugin_version' => WPSC_VERSION,
				'statusno'       => '0',
			) )->save();
		}

		return $purchase_log;
	}

	public function save_shipping_and_billing_info() {
		global $wpsc_cart;

		$purchase_log = $this->get_purchase_log();

		$sessionid = ( mt_rand( 100, 999 ) . time() );
		wpsc_update_customer_meta( 'checkout_session_id', $sessionid );

		$purchase_log->set( array(
			'sessionid'      => $sessionid,
			'discount_value' => $wpsc_cart->coupons_amount,
			'discount_data'  => $wpsc_cart->coupons_name,
		) );

		$form   = WPSC_Checkout_Form::get();
		$fields = $form->get_fields();

		foreach ( $fields as $field ) {
			if ( ! array_key_exists( $field->id, $_POST['wpsc_checkout_details'] ) ) {
				continue;
			}

			$value = $_POST['wpsc_checkout_details'][ $field->id ];

			switch ( $field->unique_name ) {
				case 'billingstate':
					wpsc_update_customer_meta( 'billing_region', $value );
					$purchase_log->set( 'billing_region', $value );
					break;
				case 'shippingstate':
					wpsc_update_customer_meta( 'shipping_region', $value );
					$purchase_log->set( 'shipping_region', $value );
					break;
				case 'billingcountry':
					wpsc_update_customer_meta( 'billing_country', $value );
					$purchase_log->set( 'billing_country', $value );
					break;
				case 'shippingcountry':
					wpsc_update_customer_meta( 'shipping_country', $value );
					$purchase_log->set( 'shipping_region', $value );
					break;
				case 'shippingpostcode':
					wpsc_update_customer_meta( 'shipping_zip', $value );
					break;
			}
		}

		_wpsc_update_location();

		//keep track of tax if taxes are exclusive
		$wpec_taxes_controller = new wpec_taxes_controller();
		if ( ! $wpec_taxes_controller->wpec_taxes_isincluded() ) {
			$tax = $wpsc_cart->calculate_total_tax();
			$tax_percentage = $wpsc_cart->tax_percentage;
		} else {
			$tax = 0.00;
			$tax_percentage = 0.00;
		}

		$purchase_log->set( array(
			'wpec_taxes_total' => $tax,
			'wpec_taxes_rate'  => $tax_percentage,
		) );

		$purchase_log->save();

		//Check to ensure purchase log row was inserted successfully
		if ( is_null( $purchase_log->get( 'id' ) ) ) {
			$this->message_collection->add(
				__( 'A database error occurred while processing your request.', 'wp-e-commerce' ),
				'error'
			);
			return;
		}

		$wpsc_cart->log_id = $purchase_log->get( 'id' );

		wpsc_update_customer_meta( 'current_purchase_log_id', $purchase_log->get( 'id' ) );

		WPSC_Checkout_Form_Data::save_form( $purchase_log, $fields );

		$this->init_shipping_calculator();

		if ( wpsc_uses_shipping() && ! $this->shipping_calculator->has_quotes ) {
			$this->message_collection->add(
				__( 'Sorry, but we cannot ship products to your submitted address. Please either provide another shipping address or contact the store administrator about product availability to your location.', 'wp-e-commerce' ),
				'error'
			);

			return;
		}

		$this->wizard->completed_step( 'shipping-and-billing' );

		$url = add_query_arg( $_GET, wpsc_get_checkout_url( $this->wizard->pending_step ) );

		wp_redirect( $url );
		exit;
	}

	public function shipping_method() {
		$this->init_shipping_calculator();
		$this->view = 'checkout-shipping-method';

		$this->enqueue_shipping_price_simulator();

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_shipping_method' ) {
			$this->submit_shipping_method();
		}
	}

	private function submit_shipping_method() {
		global $wpsc_cart;

		if ( ! $this->verify_nonce( 'wpsc-checkout-form-shipping-method' ) ) {
			return;
		}

		$form_args  = wpsc_get_checkout_shipping_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		$submitted_value = $_POST['wpsc_shipping_option'];
		$found           = false;
		$module_name     = '';
		$option          = '';

		foreach ( $this->shipping_calculator->quotes as $module_name => $quotes ) {
			foreach ( $quotes as $option => $cost ) {
				$id = $this->shipping_calculator->ids[ $module_name ][ $option ];

				if ( $id == $submitted_value ) {
					$found = true;
					$wpsc_cart->update_shipping( $module_name, $option );
					break 2;
				}
			}
		}

		if ( ! $found ) {
			return;
		}

		$this->wizard->completed_step( 'shipping-method' );

		$this->shipping_calculator->set_active_method( $module_name, $option );

		$url = add_query_arg( $_GET, wpsc_get_checkout_url( $this->wizard->pending_step ) );
		wp_redirect( $url );
		exit;
	}

	public function payment() {
		$this->view = 'checkout-payment';

		wpsc_enqueue_script( 'wpsc-checkout' );
		wpsc_enqueue_script( 'wpsc-checkout-payment' );

		if ( $this->maybe_add_guest_account() ) {
			add_filter( 'wpsc_get_checkout_payment_method_form_args', 'wpsc_create_account_checkbox' );
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_payment_method' ) {
			$this->submit_payment_method();
		}
	}

	private function submit_payment_method() {
		global $wpsc_cart;

		if ( ! $this->verify_nonce( 'wpsc-checkout-form-payment-method' ) ) {
			return;
		}

		if ( empty( $_POST['wpsc_payment_method'] ) && ! wpsc_is_free_cart() ) {
			$this->message_collection->add(
				__( 'Please select a payment method', 'wp-e-commerce' ),
				'validation'
			);
		}

		$valid = apply_filters( '_wpsc_merchant_v2_validate_payment_method', true, $this );

		if ( ! $valid ) {
			return;
		}

		$purchase_log_id   = wpsc_get_customer_meta( 'current_purchase_log_id' );
		$purchase_log      = new WPSC_Purchase_Log( $purchase_log_id );
		$submitted_gateway = $_POST['wpsc_payment_method'];

		$purchase_log->set( array(
			'gateway'       => $submitted_gateway,
			'base_shipping' => $wpsc_cart->calculate_base_shipping(),
			'totalprice'    => $wpsc_cart->calculate_total_price(),
		) );

		if ( $this->maybe_add_guest_account() && isset( $_POST['wpsc_create_account'] ) ) {

			$email   = wpsc_get_customer_meta( 'billingemail' );
			$user_id = wpsc_register_customer( $email, $email, false );
			$purchase_log->set( 'user_ID', $user_id );

			wpsc_update_customer_meta( 'checkout_details', wpsc_get_customer_meta( 'checkout_details' ), $user_id );

			update_user_meta( $user_id, '_wpsc_visitor_id', wpsc_get_current_customer_id() );
		}

		$purchase_log->save();

		$wpsc_cart->empty_db( $purchase_log_id );
		$wpsc_cart->save_to_db( $purchase_log_id );
		$wpsc_cart->submit_stock_claims( $purchase_log_id );
		$wpsc_cart->log_id = $purchase_log_id;

		$this->wizard->completed_step( 'payment' );

		do_action( 'wpsc_submit_checkout', array(
			'purchase_log_id' => $purchase_log_id,
			'our_user_id'     => isset( $user_id ) ? $user_id : get_current_user_id(),
		) );

		do_action( 'wpsc_submit_checkout_gateway', $submitted_gateway, $purchase_log );
	}

	public function results() {
		$this->view = 'checkout-results';
		$this->wizard->completed_step( 'results' );
		require_once( WPSC_TE_V2_HELPERS_PATH . '/checkout-results.php' );

		add_action( 'shutdown', array( $this, '_action_shutdown' ) );
	}

	private function init_checkout_wizard() {
		$this->wizard = WPSC_Checkout_Wizard::get_instance();
		$this->wizard->steps = array(
			'shipping-and-billing' => __( 'Details', 'wp-e-commerce' ),
			'shipping-method'      => __( 'Delivery', 'wp-e-commerce' ),
			'payment'              => __( 'Place Order', 'wp-e-commerce' ),
			'results'              => __( 'Complete', 'wp-e-commerce' ),
		);

		if ( ! wpsc_uses_shipping() ) {
			unset( $this->wizard->steps['shipping-method'] );
		}

		if ( is_user_logged_in() && (
		   		! array_key_exists( $this->wizard->active_step, $this->wizard->steps )
				|| in_array( $this->wizard->active_step, $this->wizard->disabled )
			)
		) {
			wp_redirect( wpsc_get_checkout_url( $this->wizard->pending_step ) );
			exit;
		}
	}

	private function init_shipping_calculator() {

		if ( ! wpsc_uses_shipping() ) {
			return;
		}

		$current_log_id = $this->get_purchase_log();

		require_once( WPSC_TE_V2_CLASSES_PATH . '/shipping-calculator.php' );
		$this->shipping_calculator = new WPSC_Shipping_Calculator( $current_log_id );
	}

	private function get_shipping_method_js_vars() {
		global $wpsc_cart;
		$js_var = array(
			'subtotal' => (float) $wpsc_cart->calculate_subtotal(),
			'shipping' => array(),
			'tax' =>
				  ( wpsc_is_tax_enabled() && ! wpsc_is_tax_included() )
				? (float) wpsc_cart_tax( false )
				: 0,
			'discount' => wpsc_coupon_amount( false ) > 0 ? wpsc_coupon_amount( false ) : 0
		);

		foreach ( $this->shipping_calculator->sorted_quotes as $module_name => $quotes ) {
			foreach ( $quotes as $option => $cost ) {
				$id = $this->shipping_calculator->ids[ $module_name ][ $option ];
				$js_var['shipping'][ $id ] = $cost;
			}
		}

		$currency          = new WPSC_Country( get_option( 'currency_type' ) );
		$currency_code     = $currency->get_currency_code();
		$isocode           = $currency->get_isocode();
		$without_fractions = in_array( $currency_code, WPSC_Payment_Gateways::currencies_without_fractions() );

		$decimals = $without_fractions ? 0 : 2;
		$decimals            = apply_filters( 'wpsc_modify_decimals'                 , $decimals, $isocode );
		$decimal_separator   = apply_filters( 'wpsc_format_currency_decimal_separator'  , wpsc_get_option( 'decimal_separator' ), $isocode );
		$thousands_separator = apply_filters( 'wpsc_format_currency_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );
		$symbol = apply_filters( 'wpsc_format_currency_currency_symbol', $currency->get_currency_symbol() );
		$sign_location = get_option( 'currency_sign_location' );

		$js_var['formatter'] = array(
			'currency_code'       => $currency_code,
			'without_fractions'   => $without_fractions,
			'decimals'            => $decimals,
			'decimal_separator'   => $decimal_separator,
			'thousands_separator' => $thousands_separator,
			'symbol'              => $symbol,
			'sign_location'       => $sign_location,
		);

		return $js_var;
	}

	private function enqueue_shipping_price_simulator() {
		wpsc_enqueue_script( 'wpsc-shipping-price-simulator', array(
			'property_name' => 'priceTable',
			'data' => $this->get_shipping_method_js_vars(),
		) );
	}

	public function _action_shutdown() {
		$this->wizard->reset();
		wpsc_delete_customer_meta( 'current_purchase_log_id' );
	}
}
