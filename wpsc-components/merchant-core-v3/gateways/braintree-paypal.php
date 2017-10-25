<?php
class WPSC_Payment_Gateway_Braintree_PayPal extends WPSC_Payment_Gateway {

	public function __construct() {
		require_once( WPSC_MERCHANT_V3_SDKS_PATH . '/pp-braintree/pp-braintree.php' );
		parent::__construct();

		$this->helpers          = WPEC_Braintree_Helpers::get_instance();
		$this->title            = __( 'PayPal powered by Braintree - PayPal', 'wp-e-commerce' );
		$this->supports         = array( 'default_credit_card_form', 'tokenization', 'tev1' );
		$this->sandbox          = $this->setting->get( 'sandbox' ) == '1' ? true : false;
		$this->but_size         = $this->setting->get( 'but_size' ) !== null ? $this->setting->get( 'but_size' ) : $this->setting->set( 'but_size', 'responsive' );
		$this->but_colour       = $this->setting->get( 'but_colour' ) !== null ? $this->setting->get( 'but_colour' ) : $this->setting->set( 'but_colour', 'gold' );
		$this->but_shape        = $this->setting->get( 'but_shape' ) !== null ? $this->setting->get( 'but_shape' ) : $this->setting->set( 'but_shape', 'pill' );
	}
	
	public function load() {
		return version_compare( PHP_VERSION, '5.4.0', '>=' );
	}

	public function init() {
		parent::init();

		// Disable if not setup using BT Auth
		if ( ! $this->helpers->is_gateway_setup( 'braintree-paypal' ) ) {
			// Remove gateway if its not setup properly
			add_filter( 'wpsc_get_active_gateways', array( $this, 'remove_gateways' ) );
			add_filter( 'wpsc_payment_method_form_fields', array( $this, 'remove_gateways_v2' ), 999 );
		}

		// Tev1 fields
		add_filter( 'wpsc_tev1_default_credit_card_form_fields_braintree-paypal', array( $this, 'tev1_checkout_fields'), 10, 2 );
		// Tev2 fields
		add_filter( 'wpsc_default_credit_card_form_fields_braintree-paypal', array( $this, 'tev2_checkout_fields' ), 10, 2 );
	}

	public function tev2_checkout_fields( $fields, $name ) {
		$fields = array();

		$fields = array(
			'bt-pp-button' => '<p class="wpsc-form-row wpsc-form-row-wide wpsc-bt-pp-but-field">
				<label for="' . esc_attr( $name ) . '-bt-pp-but">' . __( 'Click below to continue to PayPal', 'wp-e-commerce' ) . '</label>
				<div id="pp_braintree_pp_button"></div>
			</p>'
		);

		return $fields;
	}

	public function tev1_checkout_fields( $fields, $name ) {
		$fields = array();

		$fields = array(
			'bt-pp-button' => '<tr><td><p class="wpsc-form-row wpsc-form-row-wide wpsc-bt-pp-but-field">
				<label for="' . esc_attr( $name ) . '-bt-pp-but">' . __( 'Click below to continue to PayPal', 'wp-e-commerce' ) . '</label></td></tr>
				<tr><td><div id="pp_braintree_pp_button"></div></td></tr>'
		);

		return $fields;

	}

	/**
	 * submit method, sends the received data to the payment gateway
	 * @access public
	 */
	public function process() {
		global $braintree_settings;

		$this->helpers->setBraintreeConfiguration('braintree-paypal');

		$order = $this->purchase_log;
		$payment_method_nonce = $_POST['pp_btree_method_nonce'];

		$country = new WPSC_Country( $this->checkout_data->get('shippingcountry') );
		if ( $country->has_regions() ) {
			$shipping_state = wpsc_get_state_by_id( wpsc_get_customer_meta( '_wpsc_cart.delivery_region' ), 'code' );
		} else {
			$shipping_state = $this->checkout_data->get('shippingstate');
		}

		$phone_field = $this->checkout_data->get('billingphone');

		$params = array(
			'amount' => $order->get('totalprice'),
			'channel' => 'WPec_Cart_PPpbBT',
			'orderId' => $order->get('id'),
			'paymentMethodNonce' => $payment_method_nonce,
			'customer' => array(
				'firstName' => $this->checkout_data->get('billingfirstname'),
				'lastName' => $this->checkout_data->get('billinglastname'),
				'phone' => isset( $phone_field ) ? $phone_field : '',
				'email' => $this->checkout_data->get('billingemail'),
			),
			'billing' => array(
				'firstName' => $this->checkout_data->get('billingfirstname'),
				'lastName' => $this->checkout_data->get('billinglastname'),
				'streetAddress' => $this->checkout_data->get('billingaddress'),
				'locality' => $this->checkout_data->get('billingcity'),
				'region' => wpsc_get_state_by_id( wpsc_get_customer_meta( '_wpsc_cart.billing_region' ), 'code' ),
				'postalCode' => $this->checkout_data->get('billingpostcode'),
				'countryCodeAlpha2' => $this->checkout_data->get('billingcountry'),
			),
			'shipping' => array(
				'firstName' => $this->checkout_data->get('shippingfirstname'),
				'lastName' => $this->checkout_data->get('shippinglastname'),
				'streetAddress' => $this->checkout_data->get('shippingaddress'),
				'locality' => $this->checkout_data->get('shippingcity'),
				'region' => $shipping_state,
				'postalCode' => $this->checkout_data->get('shippingpostcode'),
				'countryCodeAlpha2' => $this->checkout_data->get('shippingcountry'),
			),
			'options' => array(
				'submitForSettlement' => true,
			),
		);

		if ( $this->helpers->bt_auth_is_connected() ) {
			$acc_token = get_option( 'wpec_braintree_auth_access_token' );
			$gateway = new Braintree_Gateway( array(
				'accessToken' => $acc_token,
			));

			$result = $gateway->transaction()->sale( $params );
		} else {
			$this->helpers->setBraintreeConfiguration('braintree-paypal');
			$result = Braintree_Transaction::sale( $params );
		}

		// In theory all error handling should be done on the client side...?
		if ( $result->success ) {
			// Payment complete
			$order->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT )->save();
			$order->set( 'transactid', $result->transaction->id )->save();
			$this->go_to_transaction_results();
		} else {
			if ( $result->transaction ) {
				$order->set( 'processed', WPSC_Purchase_Log::INCOMPLETE_SALE )->save();
				$error = $this->helpers->get_failure_status_info( $result, 'message' );
				$this->helpers->set_payment_error_message( $error );
				wp_safe_redirect( $this->get_shopping_cart_payment_url() );
			} else {
				$error = "Payment Error: " . $result->message;

				$this->helpers->set_payment_error_message( $error );
				wp_safe_redirect( $this->get_shopping_cart_payment_url() );
			}
		}
	 	exit();
	}

	public function manual_credentials( $hide = false ) {
		$hidden = $hide ? ' style="display:none;"' : '';
	?>
		<!-- Account Credentials -->
		<tr id="bt-pp-manual-header">
			<td colspan="2">
				<h4><?php _e( 'Account Credentials', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<tr id="bt-pp-manual-public-key">
			<td>
				<label for="wpsc-worldpay-secure-net-id"><?php _e( 'Public Key', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'public_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'public_key' ) ); ?>" id="wpsc-anet-api-id" />
			</td>
		</tr>
		<tr id="bt-pp-manual-private-key">
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Private Key', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'private_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'private_key' ) ); ?>" id="wpsc-anet-trans-key" />
			</td>
		</tr>
		<tr id="bt-pp-manual-merchant-id">
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Merchant ID', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'merchant_id' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'merchant_id' ) ); ?>" id="wpsc-anet-trans-key" />
			</td>
		</tr>
		<tr id="bt-pp-manual-sandbox">
			<td>
				<label><?php _e( 'Sandbox Mode', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'sandbox' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'sandbox' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
			</td>
		</tr>
	<?php
	}

	/**
	 * Creates the Braintree PayPal configuration form in the admin section
	 * @return string
	 */
	public function setup_form() {
		if ( $this->helpers->bt_auth_can_connect() ) {
			echo $this->helpers->show_connect_button();
		} else {
			$this->manual_credentials(true);
		}
	?>
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Gateway Settings', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Button Size', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<select id="wpsc-worldpay-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'but_size' ) ); ?>">
					<option value='small' <?php selected( 'small', $this->setting->get( 'but_size' ) ); ?>><?php _e( 'Small', 'wp-e-commerce' )?></option>
					<option value='medium' <?php selected( 'medium', $this->setting->get( 'but_size' ) ); ?>><?php _e( 'Medium', 'wp-e-commerce' )?></option>
					<option value='responsive' <?php selected( 'responsive', $this->setting->get( 'but_size' ) ); ?>><?php _e( 'Responsive', 'wp-e-commerce' )?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Button Colour', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<select id="wpsc-worldpay-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'but_colour' ) ); ?>">
					<option value='gold' <?php selected( 'gold', $this->setting->get( 'but_colour' ) ); ?>><?php _e( 'Gold', 'wp-e-commerce' )?></option>
					<option value='blue' <?php selected( 'blue', $this->setting->get( 'but_colour' ) ); ?>><?php _e( 'Blue', 'wp-e-commerce' )?></option>
					<option value='silver' <?php selected( 'silver', $this->setting->get( 'but_colour' ) ); ?>><?php _e( 'Silver', 'wp-e-commerce' )?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-worldpay-secure-key"><?php _e( 'Button Shape', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<select id="wpsc-worldpay-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'but_shape' ) ); ?>">
					<option value='pill' <?php selected( 'pill', $this->setting->get( 'but_shape' ) ); ?>><?php _e( 'Pill', 'wp-e-commerce' )?></option>
					<option value='rect' <?php selected( 'rect', $this->setting->get( 'but_shape' ) ); ?>><?php _e( 'Rect', 'wp-e-commerce' )?></option>
				</select>
			</td>
		</tr>
		<!-- Error Logging -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Error Logging', 'wp-e-commerce' ); ?></h4>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'Enable Debugging', 'wp-e-commerce' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'debugging' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wp-e-commerce' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'debugging' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="0" /> <?php _e( 'No', 'wp-e-commerce' ); ?></label>
			</td>
		</tr>
	<?php
	}

	/**
	 * Returns the HTML of the logo of the payment gateway.
	 *
	 * @access public
	 * @return string
	 *
	 * @since 3.9.0
	 */
	public function get_image_url() {
		return apply_filters( 'wpsc_braintree-paypal_mark_html', 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_200x51.png' );
	}

	public function remove_gateways( $gateways ) {
		foreach ( $gateways as $i => $gateway ) {
			if ( 'braintree-paypal' == $gateway ) {
				unset( $gateways[ $i ] );
			}
		}
		return $gateways;
	}

	public function remove_gateways_v2( $fields ) {
		foreach ( $fields as $i => $field ) {
			if ( 'braintree-paypal' == $field['value'] ) {
				unset( $fields[ $i ] );
			}
		}
		return $fields;
	}
}
