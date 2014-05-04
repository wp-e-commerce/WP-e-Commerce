<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/checkout-wizard.php' );

class WPSC_Controller_Checkout extends WPSC_Controller {
	protected $current_step  = '';
	private $wizard;

	public function __construct() {
		parent::__construct();

		$this->check_cart_items();
		$this->title = wpsc_get_checkout_title();
		$this->init_checkout_wizard();
	}

	private function check_cart_items() {
		global $wpsc_cart;
		if ( count( $wpsc_cart->cart_items ) > 0 )
			return;

		wp_redirect( wpsc_get_cart_url() );
		exit;
	}

	public function index() {
		if ( ! is_user_logged_in() ) {
			wpsc_update_customer_meta( 'checkout_after_login', true );
			if ( get_option( 'require_register' ) )
				$this->view = 'checkout-login-required';
			else
				$this->view = 'checkout-login-prompt';
		} else {
			wp_redirect( wpsc_get_checkout_url( 'shipping-and-billing' ) );
			exit;
		}
	}

	public function shipping_and_billing() {
		$this->view = 'checkout-shipping-and-billing';
		_wpsc_enqueue_shipping_billing_scripts();

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_checkout_form' ) {
			$this->submit_shipping_and_billing();
		}
	}

	private function submit_shipping_and_billing() {
		if ( ! $this->verify_nonce( 'wpsc-checkout-form' ) )
			return;

		// wipe out completed steps because we're starting from scratch
		$this->wizard->reset();

		$form_args = wpsc_get_checkout_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			$this->message_collection->add(
				__( 'Sorry but it looks like there are some errors with your submitted information.', 'wpsc' ),
				'error'
			);
			wpsc_set_validation_errors( $validation, $context = 'inline' );
			return;
		}

		if ( ! empty( $_POST['wpsc_copy_billing_details'] ) )
			_wpsc_copy_billing_details();

		$this->save_shipping_and_billing_info();
	}

	private function save_shipping_and_billing_info() {
		global $wpsc_cart;

		// see if an existing purchase log has been set for this user
		// otherwise create one
		$purchase_log_id = (int) wpsc_get_customer_meta( 'current_purchase_log_id' );
		if ( $purchase_log_id )
			$purchase_log = new WPSC_Purchase_Log( $purchase_log_id );
		else
			$purchase_log = new WPSC_Purchase_Log();

		$sessionid = ( mt_rand( 100, 999 ) . time() );
		wpsc_update_customer_meta( 'checkout_session_id', $sessionid );

		$purchase_log->set( array(
			'user_ID' => wpsc_get_current_customer_id(),
			'date' => time(),
			'plugin_version' => WPSC_VERSION,
			'statusno' => '0',
			'sessionid' => $sessionid,
		) );

		$form = WPSC_Checkout_Form::get();
		$fields = $form->get_fields();

		foreach ( $fields as $field ) {
			if ( ! array_key_exists( $field->id, $_POST['wpsc_checkout_details'] ) )
				continue;

			$value = $_POST['wpsc_checkout_details'][$field->id];
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
		if ( wpsc_is_tax_included() ) {
			$tax = $wpsc_cart->calculate_total_tax();
			$tax_percentage = $wpsc_cart->tax_percentage;
		} else {
			$tax = 0;
			$tax_percentage = 0;
		}
		$purchase_log->set( array(
			'wpec_taxes_total' => $tax,
			'wpec_taxes_rate' => $tax_percentage,
		) );
		$purchase_log->save();
		$wpsc_cart->log_id = $purchase_log->get( 'id' );
		wpsc_update_customer_meta( 'current_purchase_log_id', $purchase_log->get( 'id' ) );
		$this->save_form( $purchase_log, $fields );

		$this->init_shipping_calculator();
		if ( wpsc_uses_shipping() && ! $this->shipping_calculator->has_quotes ) {
			$this->message_collection->add(
				__( 'Sorry but we cannot ship products to your submitted address. Please either provide another shipping address or contact the store administrator about product availability to your location.', 'wpsc' ),
				'error'
			);

			return;
		}

		$this->wizard->completed_step( 'shipping-and-billing' );
		wp_redirect( wpsc_get_checkout_url( $this->wizard->pending_step ) );
		exit;
	}

	public function shipping_method() {
		$this->init_shipping_calculator();
		$this->view = 'checkout-shipping-method';
		add_action( 'wp_enqueue_scripts',
			array( $this, '_action_shipping_method_scripts' )
		);

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_shipping_method' ) {
			$this->submit_shipping_method();
		}
	}

	private function submit_shipping_method() {
		global $wpsc_cart;

		if ( ! $this->verify_nonce( 'wpsc-checkout-form-shipping-method' ) )
			return;

		$form_args = wpsc_get_checkout_shipping_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		$submitted_value = $_POST['wpsc_shipping_option'];
		$found = false;

		foreach ( $this->shipping_calculator->quotes as $module_name => $quotes ) {
			foreach ( $quotes as $option => $cost ) {
				$id = $this->shipping_calculator->ids[$module_name][$option];

				if ( $id == $submitted_value ) {
					$found = true;
					$wpsc_cart->update_shipping( $module_name, $option );
					break 2;
				}
			}
		}

		if ( ! $found )
			return;

		$this->wizard->completed_step( 'shipping-method' );

		/* @todo: I _think_ this will be fine, as $module_name should still be defined at this execution path from the loop, but we need to confirm. */
		$this->shipping_calculator->set_active_method( $module_name, $option );
		wp_redirect( wpsc_get_checkout_url( $this->wizard->pending_step ) );
		exit;
	}

	public function payment() {
		$this->view = 'checkout-payment';
		add_action(
			'wp_enqueue_scripts',
			array( $this, '_action_payment_scripts' )
		);

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'submit_payment_method' )
			$this->submit_payment_method();
	}

	private function submit_payment_method() {
		global $wpsc_cart;

		if ( ! $this->verify_nonce( 'wpsc-checkout-form-payment-method' ) )
			return;

		if ( empty( $_POST['wpsc_payment_method'] ) ) {
			$this->message_collection->add(
				__( 'Please select a payment method', 'wpsc' ),
				'validation'
			);
		}

		$valid = apply_filters(
			'_wpsc_merchant_v2_validate_payment_method',
			true,
			$this
		);

		if ( ! $valid )
			return;

		$submitted_gateway = $_POST['wpsc_payment_method'];

		$purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );
		$purchase_log = new WPSC_Purchase_Log( $purchase_log_id );
		$purchase_log->set( 'gateway', $submitted_gateway );
		$purchase_log->set( array(
			'gateway' => $submitted_gateway,
			'base_shipping' => $wpsc_cart->calculate_base_shipping(),
			'totalprice' => $wpsc_cart->calculate_total_price(),
		) );
		$purchase_log->save();

		$wpsc_cart->empty_db( $purchase_log_id );
		$wpsc_cart->save_to_db( $purchase_log_id );
		$wpsc_cart->submit_stock_claims( $purchase_log_id );
		$wpsc_cart->log_id = $purchase_log_id;

		$this->wizard->completed_step( 'payment' );

		do_action( 'wpsc_submit_checkout', array(
			"purchase_log_id" => $purchase_log_id,
			"our_user_id" => get_current_user_id(),
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
			'shipping-and-billing' => __( 'Details', 'wpsc' ),
			'shipping-method' => __( 'Delivery', 'wpsc' ),
			'payment' => __( 'Place Order', 'wpsc' ),
			'results' => __( 'Complete', 'wpsc' ),
		);

		if ( ! wpsc_uses_shipping() )
			unset( $this->wizard->steps['shipping-method'] );

		if (
			   is_user_logged_in()
			&& (
				   ! array_key_exists( $this->wizard->active_step, $this->wizard->steps )
				|| in_array( $this->wizard->active_step, $this->wizard->disabled )
			)
		){
			wp_redirect( wpsc_get_checkout_url( $this->wizard->pending_step ) );
			exit;
		}
	}

	private function init_shipping_calculator() {
		if ( ! wpsc_uses_shipping() )
			return;

		$current_log_id = wpsc_get_customer_meta( 'current_purchase_log_id', '' );

		if ( ! $current_log_id )
			return;

		require_once( WPSC_TE_V2_CLASSES_PATH . '/shipping-calculator.php' );
		$this->shipping_calculator = new WPSC_Shipping_Calculator( $current_log_id );
	}

	private function save_form( $purchase_log, $fields ) {
		global $wpdb;
		$log_id = $purchase_log->get( 'id' );

		// delete previous field values
		$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id = %d", $log_id );
		$wpdb->query( $sql );
		$customer_details = array();

		foreach ( $fields as $field ) {
			if ( $field->type == 'heading' )
				continue;

			$value = '';
			if ( isset( $_POST['wpsc_checkout_details'][$field->id] ) )
				$value = $_POST['wpsc_checkout_details'][$field->id];

			$customer_details[$field->id] = $value;

			$wpdb->insert(
				WPSC_TABLE_SUBMITTED_FORM_DATA,
				array(
					'log_id' => $log_id,
					'form_id' => $field->id,
					'value' => $value,
				),
				array(
					'%d',
					'%d',
					'%s',
				)
			);
		}

		wpsc_save_customer_details( $customer_details );
	}

	private function get_shipping_method_js_vars() {
		global $wpsc_cart;
		$js_var = array(
			'subtotal' => (float) $wpsc_cart->calculate_subtotal(),
			'shipping' => array(),
			'tax' =>
				  ( wpsc_is_tax_enabled() && ! wpsc_is_tax_included() )
				? (float) wpsc_cart_tax( false )
				: 0
		);

		foreach ( $this->shipping_calculator->sorted_quotes as $module_name => $quotes ) {
			foreach ( $quotes as $option => $cost ) {
				$id = $this->shipping_calculator->ids[$module_name][$option];
				$js_var['shipping'][$id] = $cost;
			}
		}

		$currency = new WPSC_Country( get_option( 'currency_type' ) );
		$currency_code = $currency->get( 'code' );
		$isocode = $currency->get( 'isocode' );
		$without_fractions = in_array( $currency_code, array( 'JPY', 'HUF', 'VND' ) );

		$decimals = $without_fractions ? 0 : 2;
		$decimals            = apply_filters( 'wpsc_modify_decimals'                 , $decimals, $isocode );
		$decimal_separator   = apply_filters( 'wpsc_format_currency_decimal_separator'  , wpsc_get_option( 'decimal_separator' ), $isocode );
		$thousands_separator = apply_filters( 'wpsc_format_currency_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );
		$symbol = apply_filters( 'wpsc_format_currency_currency_symbol', $currency->get( 'symbol' ), $isocode );
		$sign_location = get_option( 'currency_sign_location' );

		$js_var['formatter'] = array(
			'currency_code' => $currency_code,
			'without_fractions' => $without_fractions,
			'decimals' => $decimals,
			'decimal_separator' => $decimal_separator,
			'thousands_separator' => $thousands_separator,
			'symbol' => $symbol,
			'sign_location' => $sign_location,
		);

		return $js_var;
	}

	public function _action_shipping_method_scripts() {
		wp_enqueue_script( 'wpsc-shipping-price-simulator' );
		wp_localize_script(
			'wpsc-shipping-price-simulator',
			'WPSC_Price_Table',
			$this->get_shipping_method_js_vars()
		);
	}

	public function _action_payment_scripts() {
		wp_enqueue_script( 'wpsc-checkout-payment' );
	}

	public function _action_shutdown() {
		$this->wizard->reset();
		wpsc_delete_customer_meta( 'current_purchase_log_id' );
	}
}