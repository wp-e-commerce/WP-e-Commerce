<?php
/**
 * Todos, in a future phase:
 *
 * - Integrate with recurring payments
 * - Integrate Refunds
 * - Integrate with tev1
 * - Add WP Layer to SDK and handle
 * - Add JP (Japan) to endpoints
 * - Remove $_SESSION use
 * - Get Platform ID from Amazon
 */
class WPSC_Payment_Gateway_Amazon_Payments extends WPSC_Payment_Gateway {

	private $endpoints = array(
		'sandbox' => array(
			'US' => 'https://mws.amazonservices.com/OffAmazonPayments_Sandbox/2013-01-01/',
			'JP' => 'https://mws.amazonservices.jp/OffAmazonPayments_Sandbox/2013-01-01/',
			'GB' => 'https://mws-eu.amazonservices.com/OffAmazonPayments_Sandbox/2013-01-01/',
			'DE' => 'https://mws-eu.amazonservices.com/OffAmazonPayments_Sandbox/2013-01-01/',
		),
		'production' => array(
			'US' => 'https://mws.amazonservices.com/OffAmazonPayments/2013-01-01/',
			'JP' => 'https://mws.amazonservices.jp/OffAmazonPayments/2013-01-01/',
			'GB' => 'https://mws-eu.amazonservices.com/OffAmazonPayments/2013-01-01/',
			'DE' => 'https://mws-eu.amazonservices.com/OffAmazonPayments/2013-01-01/',
		)
	);

	private $order_handler;
	private $reference_id;

	public function __construct() {

		parent::__construct();

		$this->title = __( 'Amazon Payments', 'wpsc' );

		$this->reference_id = ! empty( $_REQUEST['amazon_reference_id'] ) ? $_REQUEST['amazon_reference_id'] : '';

		$this->order_handler = WPSC_Amazon_Payments_Order_Handler::get_instance( $this );

		add_action( 'wpsc_loaded', array( $this, 'init_handlers' ), 11 );
		add_action( 'wp_footer'  , array( $this, 'maybe_hide_standard_checkout_button' ) );

		// Define user set variables
		$this->seller_id       = $this->setting->get( 'seller_id' );
		$this->mws_access_key  = $this->setting->get( 'mws_access_key' );
		$this->secret_key      = $this->setting->get( 'secret_key' );
		$this->sandbox         = $this->setting->get( 'sandbox_mode' ) == '1' ? true : false;
		$this->payment_capture = $this->setting->get( 'payment_capture' ) !== null ? $this->setting->get( 'payment_capture' ) : '';

		$base_country = new WPSC_Country( wpsc_get_base_country() );

		// Get endpoint
		$location             = in_array( $base_country->get_isocode(), array( 'US', 'GB', 'DE', 'JP' ) ) ? $base_country->get_isocode() : 'US';
		$this->endpoint       = $this->sandbox ? $this->endpoints['sandbox'][ $location ] : $this->endpoints['production'][ $location ];

		$this->define_widget_constants();

		// Get refererence ID
		$this->reference_id   = ! empty( $_REQUEST['amazon_reference_id'] ) ? $_REQUEST['amazon_reference_id'] : '';

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );

			if ( isset( $post_data['amazon_reference_id'] ) ) {
				$this->reference_id = $post_data['amazon_reference_id'];
			}
		}

		// Handling for the review page of the German Market Plugin
		if ( empty( $this->reference_id ) ) {
			if ( isset( $_SESSION['first_checkout_post_array']['amazon_reference_id'] ) ) {
				$this->reference_id = $_SESSION['first_checkout_post_array']['amazon_reference_id'];
			}
		}

	}

	public function get_image_url() {
		return apply_filters( 'wpsc_amazon_pa_logo', WPSC_MERCHANT_V3_SDKS_URL . '/amazon-payments/assets/images/amazon-payments.gif' );
	}

	public function define_widget_constants() {
		$base_country = new WPSC_Country( wpsc_get_base_country() );

		switch ( $base_country->get_isocode() ) {
			case 'GB' :
				define( 'WPSC_AMAZON_PA_WIDGETS_URL', 'https://static-eu.payments-amazon.com/OffAmazonPayments/uk/' . ( $this->sandbox ? 'sandbox/' : '' ) . 'js/Widgets.js?sellerId=' . $this->setting->get( 'seller_id' ) );
				define( 'WPSC_AMAZON_WIDGET_ENDPOINT', 'https://payments' . ( $this->sandbox ? '-sandbox' : '' ) . '.amazon.co.uk' );
				define( 'WPSC_AMAZON_REGISTER_URL', 'https://sellercentral-europe.amazon.com/gp/on-board/workflow/Registration/login.html?passthrough%2Fsource=internal-landing-select&passthrough%2F*entries*=0&passthrough%2FmarketplaceID=A2WQPBGJ59HSXT&passthrough%2FsuperSource=OAR&passthrough%2F*Version*=1&passthrough%2Fld=APRPWPECOMMERCE&passthrough%2Faccount=cba&passthrough%2FwaiveFee=1' );
			break;
			case 'DE' :
				define( 'WPSC_AMAZON_PA_WIDGETS_URL', 'https://static-eu.payments-amazon.com/OffAmazonPayments/de/' . ( $this->sandbox ? 'sandbox/' : '' ) . 'js/Widgets.js?sellerId=' . $this->setting->get( 'seller_id' ) );
				define( 'WPSC_AMAZON_WIDGET_ENDPOINT', 'https://payments' . ( $this->sandbox ? '-sandbox' : '' ) . '.amazon.de' );
				define( 'WPSC_AMAZON_REGISTER_URL', 'https://sellercentral-europe.amazon.com/gp/on-board/workflow/Registration/login.html?passthrough%2Fsource=internal-landing-select&passthrough%2F*entries*=0&passthrough%2FmarketplaceID=A1OCY9REWJOCW5&passthrough%2FsuperSource=OAR&passthrough%2F*Version*=1&passthrough%2Fld=APRPWPECOMMERCE&passthrough%2Faccount=cba&passthrough%2FwaiveFee=1' );
			break;
			default :
				define( 'WPSC_AMAZON_PA_WIDGETS_URL', 'https://static-na.payments-amazon.com/OffAmazonPayments/us/' . ( $this->sandbox ? 'sandbox/' : '' ) . 'js/Widgets.js?sellerId=' . $this->setting->get( 'seller_id' ) );
				define( 'WPSC_AMAZON_WIDGET_ENDPOINT', 'https://payments' . ( $this->sandbox ? '-sandbox' : '' ) . '.amazon.com' );
				define( 'WPSC_AMAZON_REGISTER_URL', 'https://sellercentral.amazon.com/hz/me/sp/signup?solutionProviderOptions=mws-acc%3B&marketplaceId=AGWSWK15IEJJ7&solutionProviderToken=AAAAAQAAAAEAAAAQ1XU19m0BwtKDkfLZx%2B03RwAAAHBZVsoAgz2yhE7DemKr0y26Mce%2F9Q64kptY6CRih871XhB7neN0zoPX6c1wsW3QThdY6g1Re7CwxJkhvczwVfvZ9BvjG1V%2F%2FHrRgbIf47cTrdo5nNT8jmYSIEJvFbSm85nWxpvHjSC4CMsVL9s%2FPsZt&solutionProviderId=A1BVJDFFHQ7US4' );
			break;
		}
	}

	/**
	 * Load gateway only if curl is enabled (SDK requirement), PHP 5.3+ (same) and TEv2.
	 *
	 * @return bool Whether or not to load gateway.
	 */
	public function load() {
		return version_compare( phpversion(), '5.3', '>=' ) && function_exists( 'curl_init' ) && _wpsc_maybe_activate_theme_engine_v2();
	}

	/**
	 * Displays the setup form
	 *
	 * @access public
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function setup_form() {
		?>
		<!-- Account Credentials -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Account Credentials', 'wpsc' ); ?></h4>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-seller-id"><?php _e( 'Seller ID', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'seller_id' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'seller_id' ) ); ?>" id="wpsc-amazon-payments-seller-id" /><br />
				<small><?php _e( 'Obtained from your Amazon account. Also known as the "Merchant ID". Usually found under Settings > Integrations after logging into your merchant account.', 'wpsc' ); ?></small>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-mws-access-key"><?php _e( 'MWS Access Key', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'mws_access_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'mws_access_key' ) ); ?>" id="wpsc-amazon-payments-mws-access-key" /><br />
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-secret-key"><?php _e( 'Secret Key', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'secret_key' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'secret_key' ) ); ?>" id="wpsc-amazon-payments-secret-key" /><br />
				<small><?php _e( 'Obtained from your Amazon account. You can get these keys by logging into Seller Central and viewing the MWS Access Key section under the Integration tab.', 'wpsc' ); ?></small>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-sandbox-mode"><?php _e( 'Sandbox Mode', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'sandbox_mode' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-payment-capture"><?php _e( 'Payment Capture', 'wpsc' ); ?></label>
			</td>
			<td>
				<select id="wpsc-amazon-payments-payment-capture" name="<?php echo esc_attr( $this->setting->get_field_name( 'payment_capture' ) ); ?>">
					<option value='' <?php selected( '', $this->setting->get( 'payment_capture' ) ); ?>><?php _e( 'Authorize and capture the payment when the order is placed.', 'wpsc' )?></option>
					<option value='authorize' <?php selected( 'authorize', $this->setting->get( 'payment_capture' ) ); ?>><?php _e( 'Authorize the payment when the order is placed.', 'wpsc' )?></option>
					<option value='manual' <?php selected( 'manual', $this->setting->get( 'payment_capture' ) ); ?>><?php _e( 'Don’t authorize the payment when the order is placed (i.e. for pre-orders).', 'wpsc' )?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-cart-button-display"><?php _e( 'Cart login button display', 'wpsc' ); ?></label>
			</td>
			<td>
				<select id="wpsc-amazon-payments-cart-button-display" name="<?php echo esc_attr( $this->setting->get_field_name( 'cart_button_display' ) ); ?>">
					<option value='button' <?php selected( 'button', $this->setting->get( 'cart_button_display' ) ); ?>><?php _e( 'Button', 'wpsc' )?></option>
					<option value='banner' <?php selected( 'banner', $this->setting->get( 'cart_button_display' ) ); ?>><?php _e( 'Banner', 'wpsc' )?></option>
					<option value='disabled' <?php selected( 'disabled', $this->setting->get( 'cart_button_display' ) ); ?>><?php _e( 'Disabled', 'wpsc' )?></option>
				</select><br />
				<small><?php _e( 'How the Login with Amazon button gets displayed on the cart page.' ); ?></small>
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-amazon-payments-hide-button-display"><?php _e( 'Hide Standard Checkout Button', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'hide_button_display' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'hide_button_display' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'hide_button_display' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'hide_button_display' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<!-- Error Logging -->
		<tr>
			<td colspan="2">
				<h4><?php _e( 'Error Logging', 'wpsc' ); ?></h4>
			</td>
		</tr>

		<tr>
			<td>
				<label><?php _e( 'Enable Debugging', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'debugging' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'debugging' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'debugging' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<?php
	}

	public function process() {

		$order = $this->purchase_log;

		$order->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT )->save();

		$amazon_reference_id = isset( $_REQUEST['amazon_reference_id'] ) ? sanitize_text_field( $_REQUEST['amazon_reference_id'] ) : '';

		try {

			if ( ! $amazon_reference_id ) {
				throw new Exception( __( 'An Amazon payment method was not chosen.', 'wpsc' ) );
			}

			// Update order reference with amounts
			$response = $this->api_request( array(
				'Action'                                                       => 'SetOrderReferenceDetails',
				'AmazonOrderReferenceId'                                       => $amazon_reference_id,
				'OrderReferenceAttributes.OrderTotal.Amount'                   => $order->get( 'totalprice' ),
				'OrderReferenceAttributes.OrderTotal.CurrencyCode'             => strtoupper( $this->get_currency_code() ),
				'OrderReferenceAttributes.SellerNote'                          => sprintf( __( 'Order %s from %s.', 'wpsc' ), $order->get( 'id' ), urlencode( remove_accents( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) ) ),
				'OrderReferenceAttributes.SellerOrderAttributes.SellerOrderId' => $order->get( 'id' ),
				'OrderReferenceAttributes.SellerOrderAttributes.StoreName'     => remove_accents( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
				'OrderReferenceAttributes.PlatformId'                          => 'A2Z8DY3R4G08IM'
			) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( isset( $response['Error']['Message'] ) ) {
				throw new Exception( $response['Error']['Message'] );
			}

			// Confirm order reference
			$response = $this->api_request( array(
				'Action'                 => 'ConfirmOrderReference',
				'AmazonOrderReferenceId' => $amazon_reference_id
			) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( isset( $response['Error']['Message'] ) ) {
				throw new Exception( $response['Error']['Message'] );
			}

			// Get address details and save them to the order
			$response = $this->api_request( array(
				'Action'                 => 'GetOrderReferenceDetails',
				'AmazonOrderReferenceId' => $amazon_reference_id
			) );

			if ( ! is_wp_error( $response ) && isset( $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'] ) ) {

				$buyer   = $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Buyer'];
				$address = $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'];

				$this->set_customer_address( $buyer, $address );
			}

			// Store reference ID in the order
			$order->set( 'amazon_reference_id', $amazon_reference_id )->save();
			$this->order_handler->set_purchase_log( $order->get( 'id' ) );

			switch ( $this->payment_capture ) {
				case 'manual' :

					// Mark as on-hold
					$order->set( 'amazon-status', __( 'Amazon order opened. Authorize and/or capture payment below. Authorized payments must be captured within 7 days.', 'wpsc' ) );

				break;
				case 'authorize' :

					// Authorize only
					$result = $this->order_handler->authorize_payment( $order_id, $amazon_reference_id, false );

					if ( $result ) {
						// Mark as on-hold
						$order->set( 'amazon-status', __( 'Amazon order opened. Authorize and/or capture payment below. Authorized payments must be captured within 7 days.', 'wpsc' ) );

					} else {
						$order->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED );
						$order->set( 'amazon-status', __( 'Could not authorize Amazon payment.', 'wpsc' ) );
					}

				break;
				default :

					// Capture
					$result = $this->order_handler->authorize_payment( $order_id, $amazon_reference_id, true );

					if ( $result ) {
						// Payment complete
						$order->set( 'amazon-status', __( 'Amazon order completed.  Funds have been authorized and captured.', 'wpsc' ) );
					} else {
						$order->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED );
						$order->set( 'amazon-status', __( 'Could not authorize Amazon payment.', 'wpsc' ) );
					}

				break;
			}

			$order->save();
			$this->go_to_transaction_results();

		} catch( Exception $e ) {
			WPSC_Message_Collection::get_instance()->add( $e->getMessage(), 'error', 'main', 'flash' );
			return;
		}

	}

	/**
	 * Sets customer billing and shipping information.
	 *
	 * Pulls data directly from Amazon API, populating the submitted form data table.
	 *
	 * @param array $buyer   Buyer information
	 * @param array $address Shipping information
	 *
	 * @since  4.0
	 */
	private function set_customer_address( $buyer, $address ) {

		remove_action( 'wpsc_checkout_get_fields', '__return_empty_array' );

		$billing_name   = explode( ' ' , $buyer['Name'] );
		$shipping_name  = explode( ' ' , $address['Name'] );

		// Get first and last names
		$billing_last   = array_pop( $billing_name );
		$shipping_last  = array_pop( $shipping_name );
		$billing_first  = implode( ' ', $billing_name );
		$shipping_first = implode( ' ', $shipping_name );

		$this->checkout_data->set( 'billingfirstname', $billing_first );
		$this->checkout_data->set( 'billinglastname' , $billing_last );
		$this->checkout_data->set( 'billingemail'    , $buyer['Email'] );

		if ( isset( $buyer['Phone'] ) ) {
			$this->checkout_data->set( 'billingphone', $buyer['Phone'] );
		} else if ( isset( $address['Phone'] ) ) {
			$this->checkout_data->set( 'billingphone', $address['Phone'] );
		}

		$this->checkout_data->set( 'shippingfirstname', $shipping_first );
		$this->checkout_data->set( 'shippinglastname' , $shipping_last );

		// Format address
		$address_lines = array();

		if ( ! empty( $address['AddressLine1'] ) ) {
			$address_lines[] = $address['AddressLine1'];
		}
		if ( ! empty( $address['AddressLine2'] ) ) {
			$address_lines[] = $address['AddressLine2'];
		}
		if ( ! empty( $address['AddressLine3'] ) ) {
			$address_lines[] = $address['AddressLine3'];
		}

		$street_address = implode( "\n", $address_lines );
		$this->checkout_data->set( 'shippingaddress', $street_address );

		if ( isset( $address['City'] ) ) {
			$this->checkout_data->set( 'shippingcity', $address['City'] );
		}

		if ( isset( $address['PostalCode'] ) ) {
			$this->checkout_data->set( 'shippingpostcode', $address['PostalCode'] );
		}

		if ( isset( $address['StateOrRegion'] ) ) {
			$this->checkout_data->set( 'shippingstate', $address['StateOrRegion'] );
		}

		if ( isset( $address['CountryCode'] ) ) {
			$this->checkout_data->set( 'shippingcountry', $address['CountryCode'] );
		}

		$this->checkout_data->save();
	}

	/**
	 * Maybe hide standard checkout button on the cart, if enabled
	 *
	 * @since 4.0
	 */
	public function maybe_hide_standard_checkout_button() {
		if ( $this->setting->get( 'hide_button_display' ) ) {
			?>
				<style type="text/css">
					.wpsc-cart-form .wpsc-button-primary {
						display: none ! important;
					}
				</style>
			<?php
		}
	}

	/**
	 * Load handlers for cart and orders after cart is loaded.
	 */
	public function init_handlers() {
		// Disable if no seller ID
		if ( empty( $this->setting->get( 'seller_id' ) ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		if ( $this->setting->get( 'cart_button_display' ) == 'button' ) {
			add_action( 'wpsc_template_before_cart', array( $this, 'checkout_button' ), 12 );
			add_action( 'wpsc_template_after_cart' , array( $this, 'checkout_button' ), 12 );
		} elseif ( $this->setting->get( 'cart_button_display' ) == 'banner' ) {
			add_action( 'wpsc_template_before_cart', array( $this, 'checkout_message' ), 5 );
		}

		add_action( 'wpsc_template_before_checkout-shipping-and-billing', array( $this, 'checkout_message' ), 5 );
		add_action( 'wpsc_template_before_checkout-payment'             , array( $this, 'checkout_message' ), 5 );

		if ( empty( $this->reference_id ) ) {
			return;
		}

		add_filter( 'wpsc_get_checkout_payment_method_form_args', array( $this, 'add_widgets_to_method_form' ) );

		add_action( 'wpsc_checkout_get_fields', '__return_empty_array' );

		add_filter( 'wpsc_get_active_gateways', array( $this, 'remove_gateways' ) );
		add_filter( 'wpsc_get_gateway_list'   , array( $this, 'remove_gateways' ) );

		add_filter( 'wpsc_payment_method_form_fields', array( $this, 'remove_gateways_v2' ) );
	}

	/**
	 *  Checkout Button
	 */
	public function checkout_button() {
		?><div id="pay_with_amazon" class="checkout_button"></div><?php
	}

	public function add_widgets_to_method_form( $args ) {

		ob_start();

		$this->address_widget();
		$this->payment_widget();

		$widgets = ob_get_clean();

		if ( isset( $args['before_form_actions'] ) ) {
			$args['before_form_actions'] .= $widgets;
		} else {
			$args['before_form_actions']  = $widgets;
		}

		return $args;
	}

	/**
	 *  Checkout Message
	 */
	public function checkout_message() {
		if ( empty( $this->reference_id ) ) {
			echo '<div class="wpsc-alert wpsc-alert-block wpsc-alert-success"><div id="pay_with_amazon"></div><p>' . apply_filters( 'wpsc_amazon_pa_checkout_message', __( 'Have an Amazon account?', 'wpsc' ) ) . '</p></div>';
		}
	}

	/**
	 * Add scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'amazon_payments_advanced', WPSC_MERCHANT_V3_SDKS_URL . '/amazon-payments/assets/css/style.css' );

		wp_enqueue_script( 'amazon_payments_advanced_widgets', WPSC_AMAZON_PA_WIDGETS_URL, '', '1.0', true );

		wp_enqueue_script( 'amazon_payments_advanced', WPSC_MERCHANT_V3_SDKS_URL . '/amazon-payments/assets/js/amazon-checkout.js', array( 'amazon_payments_advanced_widgets' ), '1.0', true	);

		$is_pay_page   =  _wpsc_get_current_controller_name() == 'checkout' || _wpsc_get_current_controller_name() == 'cart';

		$redirect_page = $is_pay_page ? add_query_arg( 'amazon_payments_advanced', 'true', wpsc_get_checkout_url( 'payment' ) ) : esc_url_raw( add_query_arg( 'amazon_payments_advanced', 'true' ) );

		wp_localize_script( 'amazon_payments_advanced', 'amazon_payments_advanced_params', array(
			'seller_id'            => $this->setting->get( 'seller_id' ),
			'reference_id'         => $this->reference_id,
			'redirect'             => $redirect_page,
			'is_checkout_pay_page' => $is_pay_page,
		) );
	}

	/**
	 * Output the address widget HTML
	 */
	public function address_widget() {
		?>
		<div class="col2-set">
			<div class="col-1">
				<?php
				if ( wpsc_uses_shipping() ) {
					?><h3><?php _e( 'Shipping Address', 'wpsc' ); ?></h3><?php
				} else {
					?><h3><?php _e( 'Your Address', 'wpsc' ); ?></h3><?php
				}
				?>
				<div id="amazon_addressbook_widget"></div>
				<input type="hidden" name="amazon_reference_id" value="<?php echo $this->reference_id; ?>" />
				<style type="text/css">
					#amazon_addressbook_widget,
					#amazon_wallet_widget {
						width: 400px;
						height: 228px;
						margin: 10px 0 100px;
						position: relative;
						z-index: 2
					}
					.wpsc-checkout-review p,
					.wpsc-field-wpsc_payment_method {
						display: none
					}
				</style>
			</div>
		<?php
	}

	/**
	 * Output the payment method widget HTML
	 */
	public function payment_widget() {
		?>
			<div class="col-2">
				<h3><?php _e( 'Payment Method', 'wpsc' ); ?></h3>
				<div id="amazon_wallet_widget"></div>
				<input type="hidden" name="amazon_reference_id" value="<?php echo $this->reference_id; ?>" />
			</div>
		</div>
		<?php
	}

	/**
	 * Remove all gateways except Amazon Payments.
	 *
	 * This function primarily effects TEv1
	 *
	 * @since  4.0
	 */
	public function remove_gateways( $gateways ) {

		return array( 'amazon-payments' );
	}

	/**
	 * Remove all gateways except Amazon Payments.
	 *
	 * This function effects TEv2.
	 *
	 * @since  4.0
	 */
	public function remove_gateways_v2( $fields ) {
		foreach ( $fields as $i => $field ) {
			if ( 'amazon-payments' == $field['value'] ) {
				$fields[ $i ][ 'checked' ] = true;
			} else {
				unset( $fields[ $i ] );
			}
		}

		return $fields;
	}

	/**
	 * Get customer details from amazon
	 */
	public function get_customer_details() {
		try {

			// Update order reference with amounts
			$amazon = new WPSC_Gateway_Amazon_Payments_Advanced();

			$response = $amazon->api_request( array(
				'Action'                 => 'GetOrderReferenceDetails',
				'AmazonOrderReferenceId' => $this->reference_id,
			) );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			if ( ! isset( $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'] ) ) {
				return;
			}

			$address = $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']['PhysicalDestination'];

			if ( ! empty( $address['CountryCode'] ) ) {
				WC()->customer->set_country( $address['CountryCode'] );
				WC()->customer->set_shipping_country( $address['CountryCode'] );
			}

			if ( ! empty( $address['StateOrRegion'] ) ) {
				WC()->customer->set_state( $address['StateOrRegion'] );
				WC()->customer->set_shipping_state( $address['StateOrRegion'] );
			}

			if ( ! empty( $address['PostalCode'] ) ) {
				WC()->customer->set_postcode( $address['PostalCode'] );
				WC()->customer->set_shipping_postcode( $address['PostalCode'] );
			}

			if ( ! empty( $address['City'] ) ) {
				WC()->customer->set_city( $address['City'] );
				WC()->customer->set_shipping_city( $address['City'] );
			}

		} catch( Exception $e ) {
			wc_add_notice( __( 'Error:', 'wpsc' ) . ' ' . $e->getMessage(), 'error' );
			return;
		}
	}

	/**
	 * Make an API request to Amazon.
	 *
	 * @param  args $args
	 * @return WP_Error or parsed response array
	 */
	public function api_request( $args ) {

		require_once WPSC_MERCHANT_V3_SDKS_PATH . '/amazon-payments/sdk/ResponseParser.php';

		$defaults = array(
			'AWSAccessKeyId' => $this->mws_access_key,
			'SellerId'       => $this->seller_id
		);

		$args = wp_parse_args( $args, $defaults );

		$response = wp_remote_get(
			$this->get_signed_amazon_url( $this->endpoint . '?' . http_build_query( $args, '', '&' ), $this->secret_key ),
			array(
				'timeout' => 12
			)
		);

		$this->log( $args, $response );

		if ( ! is_wp_error( $response ) ) {
			$response_object = array();
			$response_object['ResponseBody'] = $response['body'];
			$response_object['Status']       = wp_remote_retrieve_response_code( $response );
			$response_parser = new PayWithAmazon\ResponseParser( $response_object );
			$response = $response_parser->toArray();
		}

		return $response;
	}

	/**
	 * If debugging is enabled on the gateway, this will log API requests/responses.
	 *
	 * @param  array  $args     Arguments passed to API
	 * @param  mixed  $response Response from API.
	 *
	 * @return void
	 */
	private function log( $args, $response ) {
		if ( $this->setting->get( 'debugging' ) ) {

			add_filter( 'wpsc_logging_post_type_args', 'WPSC_Logging::force_ui' );
			add_filter( 'wpsc_logging_taxonomy_args ', 'WPSC_Logging::force_ui' );

			$log_data = array(
				'post_title'    => 'Amazon API Operation Failure',
				'post_content'  =>  'There was an error processing the payment. Find details in the log entry meta fields.',
				'log_type'      => 'error'
			);

			$log_meta = $args;

			WPSC_Logging::insert_log( $log_data, $log_meta );
		}
	}

	/**
     * Payment form on checkout page
     */
	public function payment_fields() {
		if ( $this->has_fields ) {
			?>
			<div id="amazon_wallet_widget"></div>
			<input type="hidden" name="amazon_reference_id" value="<?php echo $this->reference_id; ?>" />
			<?php
		}
	}

	/**
	 * Sign a URL for Amazon
	 *
	 * @param  string $url
	 * @return string
	 */
	public function get_signed_amazon_url( $url, $secret_key ) {

		$urlparts = parse_url( $url );

		// Build $params with each name/value pair
	    foreach ( explode( '&', $urlparts['query'] ) as $part ) {
	        if ( strpos( $part, '=' ) ) {
	            list( $name, $value ) = explode( '=', $part, 2 );
	        } else {
	            $name  = $part;
	            $value = '';
	        }
	        $params[ $name ] = $value;
	    }

	    // Include a timestamp if none was provided
	    if ( empty( $params['Timestamp'] ) ) {
	        $params['Timestamp'] = gmdate( 'Y-m-d\TH:i:s\Z' );
	    }

	    $params['SignatureVersion'] = '2';
	    $params['SignatureMethod'] = 'HmacSHA256';

	    // Sort the array by key
	    ksort( $params );

	    // Build the canonical query string
	    $canonical       = '';

	    // Don't encode here - http_build_query already did it.
	    foreach ( $params as $key => $val ) {
	        $canonical  .= $key . "=" . rawurlencode( utf8_decode( urldecode( $val ) ) ) . "&";
	    }

		// Remove the trailing ampersand
		$canonical      = preg_replace( "/&$/", '', $canonical );

		// Some common replacements and ones that Amazon specifically mentions
		$canonical      = str_replace( array( ' ', '+', ',', ';' ), array( '%20', '%20', urlencode(','), urlencode(':') ), $canonical );

		// Build the sign
		$string_to_sign = "GET\n{$urlparts['host']}\n{$urlparts['path']}\n$canonical";

		// Calculate our actual signature and base64 encode it
		$signature      = base64_encode( hash_hmac( 'sha256', $string_to_sign, $secret_key, true ) );

		// Finally re-build the URL with the proper string and include the Signature
		$url            = "{$urlparts['scheme']}://{$urlparts['host']}{$urlparts['path']}?$canonical&Signature=" . rawurlencode( $signature );

	    return $url;
	}
}

class WPSC_Amazon_Payments_Order_Handler {

	private static $instance;

	/**
	 * Constructor
	 */
	public function init() {
		add_action( 'add_meta_boxes'             , array( $this, 'meta_box' ) );
		add_action( 'wp_ajax_amazon_order_action', array( $this, 'order_actions' ) );
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Perform order actions for amazon
	 */
	public function order_actions() {
		check_ajax_referer( 'amazon_order_action', 'security' );

		$order_id = absint( $_POST['order_id'] );
		$id       = isset( $_POST['amazon_id'] ) ? sanitize_text_field( $_POST['amazon_id'] ) : '';
		$action   = sanitize_title( $_POST['amazon_action'] );

		switch ( $action ) {
			case 'refresh' :
				$this->clear_stored_states( $order_id );
			break;
			case 'authorize' :
				// Delete old
				delete_post_meta( $order_id, 'amazon_authorization_id' );
				delete_post_meta( $order_id, 'amazon_capture_id' );

				$this->authorize_payment( $order_id, $id, false );
				$this->clear_stored_states( $order_id );
			break;
			case 'authorize_capture' :
				// Delete old
				delete_post_meta( $order_id, 'amazon_authorization_id' );
				delete_post_meta( $order_id, 'amazon_capture_id' );

				$this->authorize_payment( $order_id, $id, true );
				$this->clear_stored_states( $order_id );
			break;
			case 'close_authorization' :
				$this->close_authorization( $order_id, $id );
				$this->clear_stored_states( $order_id );
			break;
			case 'capture' :
				$this->capture_payment( $order_id, $id );
				$this->clear_stored_states( $order_id );
			break;
			case 'refund' :
				$amazon_refund_amount = floatval( sanitize_text_field( $_POST['amazon_refund_amount'] ) );
				$amazon_refund_note   = sanitize_text_field( $_POST['amazon_refund_note'] );

				$this->refund_payment( $order_id, $id, $amazon_refund_amount, $amazon_refund_note );
				$this->clear_stored_states( $order_id );
			break;
		}

		die();
	}

	/**
	 * Wipe states so the value is refreshed
	 */
	public function clear_stored_states( $order_id ) {
		delete_post_meta( $order_id, 'amazon_reference_state' );
		delete_post_meta( $order_id, 'amazon_capture_state' );
		delete_post_meta( $order_id, 'amazon_authorization_state' );
	}

	/**
	 * Get auth state from amazon API
	 * @param  string $id
	 * @return string or false on failure
	 */
	public function get_reference_state( $order_id, $id ) {
		if ( $state = get_post_meta( $order_id, 'amazon_reference_state', true ) )
			return $state;

		$amazon = new WC_Gateway_Amazon_Payments_Advanced();

		$response = $amazon->api_request( array(
			'Action'                 => 'GetOrderReferenceDetails',
			'AmazonOrderReferenceId' => $id,
		) );

		if ( is_wp_error( $response ) || isset( $response['Error']['Message'] ) )
			return false;

		$state = $response['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State'];

		update_post_meta( $order_id, 'amazon_reference_state', $state );

		return $state;
	}

	/**
	 * Get auth state from amazon API
	 * @param  string $id
	 * @return string or false on failure
	 */
	public function get_authorization_state( $order_id, $id ) {
		if ( $state = get_post_meta( $order_id, 'amazon_authorization_state', true ) ) {
			return $state;
		}

		$amazon = new WC_Gateway_Amazon_Payments_Advanced();

		$response = $amazon->api_request( array(
			'Action'                => 'GetAuthorizationDetails',
			'AmazonAuthorizationId' => $id,
		) );

		if ( is_wp_error( $response ) || isset( $response['Error']['Message'] ) )
			return false;

		$state = $response['GetAuthorizationDetailsResult']['AuthorizationDetails']['AuthorizationStatus']['State'];

		update_post_meta( $order_id, 'amazon_authorization_state', $state );

		$this->maybe_update_billing_details( $order_id, $response['GetAuthorizationDetailsResult']['AuthorizationDetails'] );

		return $state;
	}

	/**
	 * VAT registered sellers - Obtaining the Billing Address
	 * http://docs.developer.amazonservices.com/en_UK/apa_guide/APAGuide_GetAuthorizationStatus.html
	 * @param  int $order_id
	 * @param array $result
	 */
	public function maybe_update_billing_details( $order_id, $result ) {
		if ( ! empty( $result['AuthorizationBillingAddress'] ) ) {
			$address = $result['AuthorizationBillingAddress'];

			// Format address and map to WC fields
			$address_lines = array();

			if ( ! empty( $address['AddressLine1'] ) ) {
				$address_lines[] = $address['AddressLine1'];
			}
			if ( ! empty( $address['AddressLine2'] ) ) {
				$address_lines[] = $address['AddressLine2'];
			}
			if ( ! empty( $address['AddressLine3'] ) ) {
				$address_lines[] = $address['AddressLine3'];
			}

			if ( 3 === sizeof( $address_lines ) ) {
				update_post_meta( $order_id, '_billing_company', $address_lines[0] );
				update_post_meta( $order_id, '_billing_address_1', $address_lines[1] );
				update_post_meta( $order_id, '_billing_address_2', $address_lines[2] );
			} elseif ( 2 === sizeof( $address_lines ) ) {
				update_post_meta( $order_id, '_billing_address_1', $address_lines[0] );
				update_post_meta( $order_id, '_billing_address_2', $address_lines[1] );
			} elseif ( sizeof( $address_lines ) ) {
				update_post_meta( $order_id, '_billing_address_1', $address_lines[0] );
			}

			if ( isset( $address['City'] ) ) {
				update_post_meta( $order_id, '_billing_city', $address['City'] );
			}

			if ( isset( $address['PostalCode'] ) ) {
				update_post_meta( $order_id, '_billing_postcode', $address['PostalCode'] );
			}

			if ( isset( $address['StateOrRegion'] ) ) {
				update_post_meta( $order_id, '_billing_state', $address['StateOrRegion'] );
			}

			if ( isset( $address['CountryCode'] ) ) {
				update_post_meta( $order_id, '_billing_country', $address['CountryCode'] );
			}
		}
	}

	/**
	 * Get capture state from amazon API
	 * @param  string $id
	 * @return string or false on failure
	 */
	public function get_capture_state( $order_id, $id ) {
		if ( $state = get_post_meta( $order_id, 'amazon_capture_state', true ) )
			return $state;

		$amazon = new WC_Gateway_Amazon_Payments_Advanced();

		$response = $amazon->api_request( array(
			'Action'          => 'GetCaptureDetails',
			'AmazonCaptureId' => $id,
		) );

		if ( is_wp_error( $response ) || isset( $response['Error']['Message'] ) )
			return false;

		$state = $response['GetCaptureDetailsResult']['CaptureDetails']['CaptureStatus']['State'];

		update_post_meta( $order_id, 'amazon_capture_state', $state );

		return $state;
	}

	/**
	 * meta_box function.
	 *
	 * @access public
	 * @return void
	 */
	function meta_box() {
		global $post, $wpdb;

		$order_id = absint( $post->ID );
		$order    = new WC_Order( $order_id );

		if ( $order->payment_method == 'amazon_payments_advanced' ) {
			add_meta_box( 'woocommerce-amazon-payments-advanced', __( 'Amazon Payments Advanced', 'wpsc' ), array( $this, 'authorization_box' ), 'shop_order', 'side' );
		}
	}

	/**
	 * pre_auth_box function.
	 *
	 * @access public
	 * @return void
	 */
	function authorization_box() {
		global $post, $wpdb, $theorder;

		$actions  = array();
		$order_id = absint( $post->ID );

		if ( ! is_object( $theorder ) )
			$theorder = new WC_Order( $order_id );

		// Get ids
		$amazon_authorization_id = get_post_meta( $order_id, 'amazon_authorization_id', true );
		$amazon_reference_id     = get_post_meta( $order_id, 'amazon_reference_id', true );
		$amazon_capture_id       = get_post_meta( $order_id, 'amazon_capture_id', true );
		$amazon_refund_ids       = get_post_meta( $order_id, 'amazon_refund_id', false );

		if ( $amazon_capture_id ) {

			$amazon_capture_state = $this->get_capture_state( $order_id, $amazon_capture_id );

			switch ( $amazon_capture_state ) {
				case 'Pending' :

					echo wpautop( sprintf( __( 'Capture Reference %s is <strong>%s</strong>.', 'wpsc' ), $amazon_capture_id, $amazon_capture_state ) . ' <a href="#" data-action="refresh" class="refresh">' . __( 'Refresh', 'wpsc' ) . '</a>' );

					// Admin will need to re-check this, so clear the stored value
					$this->clear_stored_states( $order_id );
				break;
				case 'Declined' :

					echo wpautop( __( 'The capture was declined.', 'wpsc' ) );

					$actions['authorize'] = array(
						'id' => $amazon_reference_id,
						'button' => __( 'Re-authorize?', 'wpsc' )
					);

				break;
				case 'Completed' :

					echo wpautop( sprintf( __( 'Capture Reference %s is <strong>%s</strong>.', 'wpsc' ), $amazon_capture_id, $amazon_capture_state ) . ' <a href="#" class="toggle_refund">' . __( 'Make a refund?', 'wpsc' ) . '</a>' );

					// Refund form
					?>
					<p class="refund_form" style="display:none">
						<input type="number" step="any" style="width:100%" class="amazon_refund_amount" value="<?php echo $theorder->get_total(); ?>" />
						<input type="text" style="width:100%" class="amazon_refund_note" placeholder="<?php _e( 'Add a note about this refund', 'wpsc' ); ?>" /><br/>
						<a href="#" class="button" data-action="refund" data-id="<?php echo $amazon_capture_id; ?>"><?php _e( 'Refund', 'wpsc' ); ?></a>
					</form>
					<?php

				break;
				case 'Closed' :

					echo wpautop( sprintf( __( 'Capture Reference %s is <strong>%s</strong>.', 'wpsc' ), $amazon_capture_id, $amazon_capture_state ) );

				break;
			}

			// Display refunds
			if ( $amazon_refund_ids ) {
				$amazon = new WC_Gateway_Amazon_Payments_Advanced();

				$refunds = (array) get_post_meta( $order_id, 'amazon_refunds', true );

				foreach ( $amazon_refund_ids as $amazon_refund_id ) {

					if ( isset( $refunds[ $amazon_refund_id ] ) ) {
						echo wpautop( sprintf( __( 'Refund %s of %s is <strong>%s</strong> (%s).', 'wpsc' ), $amazon_refund_id, woocommerce_price( $refunds[ $amazon_refund_id ]['amount'] ), $refunds[ $amazon_refund_id ]['state'], $refunds[ $amazon_refund_id ]['note'] ) );
					} else {

						$response = $amazon->api_request( array(
							'Action'         => 'GetRefundDetails',
							'AmazonRefundId' => $amazon_refund_id,
						) );

						if ( ! is_wp_error( $response ) && ! isset( $response['Error']['Message'] ) ) {

							$note   = $response['GetRefundDetailsResult']['RefundDetails']['SellerRefundNote'];
							$state  = $response['GetRefundDetailsResult']['RefundDetails']['RefundStatus']['State'];
							$amount = $response['GetRefundDetailsResult']['RefundDetails']['RefundAmount']['Amount'];

							echo wpautop( sprintf( __( 'Refund %s of %s is <strong>%s</strong> (%s).', 'wpsc' ), $amazon_refund_id, woocommerce_price( $amount ), $state, $note ) );

							if ( $state == 'Completed' ) {
								$refunds[ $amazon_refund_id ] = array(
									'state'  => $state,
									'amount' => $amount,
									'note'   => $note
								);
							}
						}

					}
				}

				update_post_meta( $order_id, 'amazon_refunds', $refunds );
			}
		}

		elseif ( $amazon_authorization_id ) {

			$amazon_authorization_state = $this->get_authorization_state( $order_id, $amazon_authorization_id );

			echo wpautop( sprintf( __( 'Auth Reference %s is <strong>%s</strong>.', 'wpsc' ), $amazon_reference_id, $amazon_authorization_state ) . ' <a href="#" data-action="refresh" class="refresh">' . __( 'Refresh', 'wpsc' ) . '</a>' );

			switch ( $amazon_authorization_state ) {
				case 'Open' :

					$actions['capture'] = array(
						'id' => $amazon_authorization_id,
						'button' => __( 'Capture funds', 'wpsc' )
					);

					$actions['close_authorization'] = array(
						'id' => $amazon_authorization_id,
						'button' => __( 'Close Authorization', 'wpsc' )
					);

				break;
				case 'Pending' :

					echo wpautop( __( 'You cannot capture funds whilst the authorization is pending. Try again later.', 'wpsc' ) );

					// Admin will need to re-check this, so clear the stored value
					$this->clear_stored_states( $order_id );

				break;
				case 'Closed' :
				case 'Declined' :
					$actions['authorize'] = array(
						'id' => $amazon_reference_id,
						'button' => __( 'Authorize again', 'wpsc' )
					);
				break;
			}
		}

		elseif ( $amazon_reference_id ) {

			$amazon_reference_state = $this->get_reference_state( $order_id, $amazon_reference_id );

			echo wpautop( sprintf( __( 'Order Reference %s is <strong>%s</strong>.', 'wpsc' ), $amazon_reference_id, $amazon_reference_state ) . ' <a href="#" data-action="refresh" class="refresh">' . __( 'Refresh', 'wpsc' ) . '</a>' );

			switch ( $amazon_reference_state ) {
				case 'Open' :

					$actions['authorize'] = array(
						'id' => $amazon_reference_id,
						'button' => __( 'Authorize', 'wpsc' )
					);

					$actions['authorize_capture'] = array(
						'id' => $amazon_reference_id,
						'button' => __( 'Authorize &amp; Capture', 'wpsc' )
					);

				break;
				case 'Suspended' :

					echo wpautop( __( 'The reference has been suspended. Another form of payment is required.', 'wpsc' ) );

				break;
				case 'Canceled' :
				case 'Suspended' :

					echo wpautop( __( 'The reference has been cancelled/closed. No authorizations can be made.', 'wpsc' ) );

				break;
			}
		}

		if ( ! empty( $actions ) ) {

			echo '<p class="buttons">';

			foreach ( $actions as $action_name => $action ) {
				echo '<a href="#" class="button" data-action="' . $action_name . '" data-id="' . $action['id'] . '">' . $action['button'] . '</a> ';
			}

			echo '</p>';

		}
?>
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$('#wpsc-amazon-payments').on( 'click', 'a.button, a.refresh', function( e ) {
					var $this = $( this );
					e.preventDefault();

					var data = {
						action: 		'amazon_order_action',
						security: 		'<?php echo wp_create_nonce( "amazon_order_action" ); ?>',
						order_id: 		'<?php echo $order_id; ?>',
						amazon_action: 	$this.data('action'),
						amazon_id: 		$this.data('id'),
						amazon_refund_amount: jQuery('.amazon_refund_amount').val(),
						amazon_refund_note: jQuery('.amazon_refund_note').val(),
					};

					// Ajax action
					$.post( ajaxurl, data, function( result ) {
							location.reload();
						}, 'json' );

					return false;
				});

				$('#wpsc-amazon-payments').on( 'click', 'a.toggle_refund', function(){
					jQuery('.refund_form').slideToggle();
					return false;
				});
			} );


			</script>
			</div>
			</div>
			</div>
		<?php
	}

    /**
     * Authorize payment
     */
    public function authorize_payment( $order_id, $amazon_reference_id, $capture_now = false ) {

		if ( $this->log->get( 'gateway' ) == 'amazon-payments' ) {

			$response = $this->gateway->api_request( array(
				'Action'                           => 'Authorize',
				'AmazonOrderReferenceId'           => $amazon_reference_id,
				'AuthorizationReferenceId'         => $order_id . '-' . current_time( 'timestamp', true ),
				'AuthorizationAmount.Amount'       => $this->log->get( 'totalprice' ),
				'AuthorizationAmount.CurrencyCode' => strtoupper( $this->gateway->get_currency_code() ),
				'CaptureNow'                       => $capture_now,
				'TransactionTimeout'               => 0,
			) );

			if ( is_wp_error( $response ) ) {

				$this->log->set( 'amazon-status', __( 'Unable to authorize funds with amazon:', 'wpsc' ) . ' ' . $response->get_error_message() )->save();

				return false;

			} elseif ( isset( $response['Error']['Message'] ) ) {

				$this->log->set( 'amazon-status', $response['Error']['Message'] )->save();

				return false;

			} else {

				if ( isset( $response['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'] ) ) {
					$auth_id = $response['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
				} else {
					return false;
				}

				if ( isset( $response['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] ) ) {
					$state = strtolower( $response['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] );
				} else {
					$state = 'pending';
				}

				$this->log->set( 'amazon_authorization_id', $auth_id )->save();

				$this->maybe_update_billing_details( $order_id, $response['AuthorizeResult']['AuthorizationDetails'] );

				if ( 'declined' == $state ) {
					$this->log->set( 'amazon-status', sprintf( __( 'Order Declined with reason code: %s', 'wpsc' ), $response['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'] ) )->save();
					// Payment was not authorized
					return false;
				}

				if ( $capture_now ) {
					$this->log->set( 'amazon_capture_id', str_replace( '-A', '-C', $auth_id ) )->save();

					$this->log->set( 'amazon-status', sprintf( __( 'Captured (Auth ID: %s)', 'wpsc' ), str_replace( '-A', '-C', $auth_id ) ) )->save();
				} else {
					$this->log->set( 'amazon-status', sprintf( __( 'Authorized (Auth ID: %s)', 'wpsc' ), $auth_id ) )->save();
				}

				return true;
			}
		}

		return false;
    }

    /**
     * Close auth
     * @param  int $order_id
     * @param  string $amazon_authorization_id
     */
    public function close_authorization( $order_id, $amazon_authorization_id ) {
		$order = new WC_Order( $order_id );

		if ( $order->payment_method == 'amazon_payments_advanced' ) {

			$amazon = new WC_Gateway_Amazon_Payments_Advanced();

			$response = $amazon->api_request( array(
				'Action'                => 'CloseAuthorization',
				'AmazonAuthorizationId' => $amazon_authorization_id
			) );

			if ( is_wp_error( $response ) ) {

				// Don't add a note
				//
			} elseif ( isset( $response['Error']['Message'] ) ) {

				$this->log->set( 'amazon-status', $response['Error']['Message'] )->save();

			} else {

				wpsc_delete_purchase_meta( $order_id, 'amazon_authorization_id' );

				$this->log->set( 'amazon-status', sprintf( __( 'Authorization closed (Auth ID: %s)', 'wpsc' ), $amazon_authorization_id ) )->save();

			}
		}
    }

    /**
     * Capture payment
     *
     * @param  int $order_id
     */
    public function capture_payment( $order_id, $amazon_authorization_id ) {

		if ( $this->log->get( 'gateway' ) == 'amazon-payments' ) {

			$response = $this->gateway->api_request( array(
				'Action'                     => 'Capture',
				'AmazonAuthorizationId'      => $amazon_authorization_id,
				'CaptureReferenceId'         => $this->log->get( 'id' ) . '-' . current_time( 'timestamp', true ),
				'CaptureAmount.Amount'       => $this->log->get( 'totalprice' ),
				'CaptureAmount.CurrencyCode' => strtoupper( $this->gateway->get_currency_code() )
			) );

			if ( is_wp_error( $response ) ) {

				$this->log->set( 'amazon-status', __( 'Unable to authorize funds with amazon:', 'wpsc' ) . ' ' . $response->get_error_message() )->save();

			} elseif ( isset( $response['Error']['Message'] ) ) {

				$this->log->set( 'amazon-status', $response['Error']['Message'] )->save();

			} else {
				$capture_id = $response['CaptureResult']['CaptureDetails']['AmazonCaptureId'];

				$this->log->set( 'amazon-status', sprintf( __( 'Capture Attempted (Capture ID: %s)', 'wpsc' ), $capture_id ) )->save();

				$this->log->set( 'amazon_capture_id', $capture_id )->save();
			}
		}
    }

    /**
     * Refund a payment
     * @param  int $order_id
     * @param  string $capture_id
     * @param  float $amount
     */
    public function refund_payment( $order_id, $capture_id, $amount, $note ) {
		if ( $this->log->get( 'gateway' ) == 'amazon-payments' ) {

			$base_country = new WPSC_Country( wpsc_get_base_country() );

			if ( 'US' == $base_country->get_isocode() && $amount > $this->log->get( 'totalprice' ) ) {
				$this->log->set( 'amazon-status', __( 'Unable to refund funds via amazon:', 'wpsc' ) . ' ' . __( 'Refund amount is greater than order total.', 'wpsc' ) )->save();

				return;
			} elseif ( $amount > min( ( $this->log->get( 'totalprice' ) * 1.15 ), ( $this->log->get( 'totalprice' ) + 75 ) ) ) {
				$this->log->set( 'amazon-status', __( 'Unable to refund funds via amazon:', 'wpsc' ) . ' ' . __( 'Refund amount is greater than the max refund amount.', 'wpsc' ) )->save();

				return;
			}

			$response = $this->gateway->api_request( array(
				'Action'                    => 'Refund',
				'AmazonCaptureId'           => $capture_id,
				'RefundReferenceId'         => $order_id . '-' . current_time( 'timestamp', true ),
				'RefundAmount.Amount'       => $amount,
				'RefundAmount.CurrencyCode' => strtoupper( $this->gateway->get_currency_code() ),
				'SellerRefundNote'          => $note
			) );

			if ( is_wp_error( $response ) ) {

				$this->log->set( 'amazon-status', __( 'Unable to refund funds via amazon:', 'wpsc' ) . ' ' . $response->get_error_message() )->save();

			} elseif ( isset( $response['Error']['Message'] ) ) {

				$this->log->set( 'amazon-status', $response['Error']['Message'] )->save();

			} else {
				$refund_id = $response['RefundResult']['RefundDetails']['AmazonRefundId'];

				$this->log->set( 'amazon-status', sprintf( __( 'Refunded %s (%s)', 'wpsc' ), wpsc_currency_display( $amount ), $note ) )->save();

				wpsc_add_purchase_meta( $order_id, 'amazon_refund_id', $refund_id );
			}
		}
    }
}