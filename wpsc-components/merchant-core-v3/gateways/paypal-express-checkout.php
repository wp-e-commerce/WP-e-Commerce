<?php

class WPSC_Payment_Gateway_Paypal_Express_Checkout extends WPSC_Payment_Gateway
{
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
	const LIVE_URL    = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
	private $gateway;

	public function __construct( $options ) {
		parent::__construct();
		$this->title = __( 'Paypal Express Checkout 3.0', 'wpsc' );
		require_once( 'php-merchant/gateways/paypal-express-checkout.php' );
		$this->gateway = new PHP_Merchant_Paypal_Express_Checkout( $options );
		$this->gateway->set_options( array(
			'api_username'     => $this->setting->get( 'api_username' ),
			'api_password'     => $this->setting->get( 'api_password' ),
			'api_signature'    => $this->setting->get( 'api_signature' ),
			'cancel_url'       => get_option('shopping_cart_url'),
			'currency'         => $this->get_currency_code(),
			'test'             => (bool) $this->setting->get( 'sandbox_mode' ),
			'address_override' => true,
		) );

		add_filter( 'wpsc_purchase_log_gateway_data', array( $this, 'filter_purchase_log_gateway_data' ), 10, 2 );
	}

	public function filter_purchase_log_gateway_data( $gateway_data, $data ) {
		// Because paypal express checkout API doesn't have full support for discount, we have to manually add an item here
		if ( isset( $gateway_data['discount'] ) && (float) $gateway_data['discount'] != 0 ) {
			$i =& $gateway_data['items'];
			$d =& $gateway_data['discount'];
			$s =& $gateway_data['subtotal'];

			// If discount amount is larger than or equal to the item total, we need to set item total to 0.01
			// because Paypal does not accept 0 item total.
			if ( $d >= $gateway_data['subtotal'] ) {
				$d = $s - 0.01;

				// if there's shipping, we'll take 0.01 from there
				if ( ! empty( $gateway_data['shipping'] ) )
					$gateway_data['shipping'] -= 0.01;
				else
					$gateway_data['amount'] = 0.01;
			}
			$s -= $d;

			$i[] = array(
				'name' => __( 'Discount', 'wpsc' ),
				'amount' => - $d,
				'quantity' => 1,
			);
		}
		return $gateway_data;
	}

	protected function get_return_url() {
		$location = add_query_arg( array(
				'sessionid'                => $this->purchase_log->get( 'sessionid' ),
				'payment_gateway'          => 'paypal-express-checkout',
				'payment_gateway_callback' => 'confirm_transaction',
			),
			get_option( 'transact_url' )
		);
		return apply_filters( 'wpsc_paypal_express_checkout_return_url', $location );
	}

	protected function get_notify_url() {
		$location = add_query_arg( array(
			'payment_gateway'          => 'paypal-express-checkout',
			'payment_gateway_callback' => 'ipn',
		), home_url( 'index.php' ) );

		return apply_filters( 'wpsc_paypal_express_checkout_notify_url', $location );
	}

	protected function set_purchase_log_for_callbacks( $sessionid = false ) {
		if ( $sessionid === false )
			$sessionid = $_REQUEST['sessionid'];
		$purchase_log = new WPSC_Purchase_Log( $sessionid, 'sessionid' );

		if ( ! $purchase_log->exists() )
			return;

		$this->set_purchase_log( $purchase_log );
	}

	public function callback_ipn() {
		$ipn = new PHP_Merchant_Paypal_IPN( false, (bool) $this->setting->get( 'sandbox_mode', false ) );

		if ( $ipn->is_verified() ) {
			$sessionid = $ipn->get( 'invoice' );
			$this->set_purchase_log_for_callbacks( $sessionid );

			if ( $ipn->is_payment_denied() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::PAYMENT_DECLINED );
			} elseif ( $ipn->is_payment_refunded() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::REFUNDED );
			} elseif ( $ipn->is_payment_completed() ) {
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
			} elseif ( $ipn->is_payment_pending() ) {
				if ( $ipn->is_payment_refund_pending() )
					$this->purchase_log->set( 'processed', WPSC_Purchase_Log::REFUND_PENDING );
				else
					$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ORDER_RECEIVED );
			}

			$this->purchase_log->save();
			transaction_results( $sessionid, false );
		}

		exit;
	}

	public function callback_confirm_transaction() {
		if ( ! isset( $_REQUEST['sessionid'] ) || ! isset( $_REQUEST['token'] ) || ! isset( $_REQUEST['PayerID'] ) )
			return;

		$this->set_purchase_log_for_callbacks();
		add_filter( 'wpsc_get_transaction_html_output', array( $this, 'filter_confirm_transaction_page' ) );
	}

	public function callback_display_paypal_error() {
		add_filter( 'wpsc_get_transaction_html_output', array( $this, 'filter_paypal_error_page' ) );
	}

	public function callback_display_generic_error() {
		add_filter( 'wpsc_get_transaction_html_output', array( $this, 'filter_generic_error_page' ) );
	}

	public function callback_process_confirmed_payment() {
		$args = array_map( 'urldecode', $_GET );
		extract( $args, EXTR_SKIP );
		if ( ! isset( $sessionid ) || ! isset( $token ) || ! isset( $PayerID ) )
			return;

		$this->set_purchase_log_for_callbacks();

		$total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
		$options = array(
			'token'    => $token,
			'payer_id' => $PayerID,
			'invoice'  => $this->purchase_log->get( 'sessionid' ),
		);
		$options += $this->checkout_data->get_gateway_data();
		$options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

		if ( $this->setting->get( 'ipn', false ) )
			$options['notify_url'] = $this->get_notify_url();

		$response = $this->gateway->purchase( $options );
		$location = remove_query_arg( 'payment_gateway_callback' );

		if ( $response->has_errors() ) {
			wpsc_update_customer_meta( 'paypal_express_checkout_errors', $response->get_errors() );
			$location = add_query_arg( array( 'payment_gateway_callback' => 'display_paypal_error' ) );
		} elseif ( $response->is_payment_completed() || $response->is_payment_pending() ) {
			$location = remove_query_arg( 'payment_gateway' );

			if ( $response->is_payment_completed() )
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ACCEPTED_PAYMENT );
			else
				$this->purchase_log->set( 'processed', WPSC_Purchase_Log::ORDER_RECEIVED );

			$this->purchase_log->set( 'transactid', $response->get( 'transaction_id' ) )
			                   ->set( 'date', time() )
			                   ->save();
		} else {
			$location = add_query_arg( array( 'payment_gateway_callback' => 'display_generic_error' ) );
		}

		wp_redirect( $location );
		exit;
	}

	public function filter_paypal_error_page() {
		$errors = wpsc_get_customer_meta( 'paypal_express_checkout_errors' );
		ob_start();
		?>
		<p>
			<?php _e( 'Sorry, your transaction could not be processed by Paypal. Please contact the site administrator. The following errors are returned:' ); ?>
		</p>
		<ul>
			<?php foreach ( $errors as $error ): ?>
				<li><?php echo esc_html( $error['details'] ) ?> (<?php echo esc_html( $error['code'] ); ?>)</li>
			<?php endforeach; ?>
		</ul>
		<p><a href="<?php echo esc_attr( get_option( 'shopping_cart_url' ) ); ?>"><?php _e( 'Click here to go back to the checkout page.') ?></a></p>
		<?php
		$output = apply_filters( 'wpsc_paypal_express_checkout_gateway_error_message', ob_get_clean(), $errors );
		return $output;
	}

	public function filter_generic_error_page() {
		ob_start();
		?>
			<p><?php _e( 'Sorry, but your transaction could not be processed by Paypal for some reason. Please contact the site administrator.' ); ?></p>
			<p><a href="<?php echo esc_attr( get_option( 'shopping_cart_url' ) ); ?>"><?php _e( 'Click here to go back to the checkout page.') ?></a></p>
		<?php
		$output = apply_filters( 'wpsc_paypal_express_checkout_generic_error_message', ob_get_clean() );
		return $output;
	}

	public function filter_confirm_transaction_page() {
		ob_start();
		?>
		<table width='400' class='paypal_express_form'>
	        <tr>
	            <td align='left' class='firstcol'><strong><?php _e( 'Order Total:', 'wpsc' ); ?></strong></td>
	            <td align='left'><?php echo wpsc_currency_display( $this->purchase_log->get( 'totalprice' ) ); ?></td>
	        </tr>
			<tr>
			    <td align='left' colspan='2'><strong><?php _e( 'Shipping Details:', 'wpsc' ); ?></strong></td>
			</tr>
	        <tr>
	            <td align='left' class='firstcol'>
	                <?php echo __('Address:', 'wpsc' ); ?>
				</td>
	            <td align='left'>
					<?php echo esc_html( $this->checkout_data->get( 'shippingaddress' ) ); ?>
	            </td>
	        </tr>
	        <tr>
	            <td align='left' class='firstcol'>
	                <?php echo __('City:', 'wpsc' ); ?>
				</td>
	            <td align='left'><?php echo esc_html( $this->checkout_data->get( 'shippingcity' ) ); ?></td>
	        </tr>
	        <tr>
	            <td align='left' class='firstcol'>
	                <?php echo __('State:', 'wpsc' ); ?>
				</td>
	            <td align='left'>
					<?php echo esc_html( wpsc_get_region( $this->checkout_data->get( 'shippingstate' ) ) ); ?>
				</td>
	        </tr>
	        <tr>
	            <td align='left' class='firstcol'>
	                <?php echo __('Postal code:', 'wpsc' ); ?>
				</td>
	            <td align='left'><?php echo esc_html( $this->checkout_data->get( 'shippingpostcode' ) ); ?></td>
	        </tr>
	        <tr>
	            <td align='left' class='firstcol'>
	                <?php echo __('Country:', 'wpsc' ); ?></td>
	            <td align='left'><?php echo esc_html( wpsc_get_country( $this->checkout_data->get( 'shippingcountry' ) ) ); ?></td>
	        </tr>
	        <tr>
	            <td colspan='2'>
					<form action="<?php echo remove_query_arg( array( 'payment_gateway', 'payment_gateway_callback' ) ); ?>" method='post'>
						<input type='hidden' name='payment_gateway' value='paypal-express-checkout' />
						<input type='hidden' name='payment_gateway_callback' value='process_confirmed_payment' />
						<p><input name='action' type='submit' value='<?php _e( 'Confirm Payment', 'wpsc' ); ?>' /></p>
					</form>
				</td>
	        </tr>
	    </table>
		<?php
		$output = apply_filters( 'wpsc_confirm_payment_message', ob_get_clean(), $this->purchase_log );
		return $output;
	}

	public function setup_form() {
		$paypal_currency = $this->get_currency_code();
		?>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-username"><?php _e( 'API Username', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_username' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_username' ) ); ?>" id="wpsc-paypal-express-api-username" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-password"><?php _e( 'API Password', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_password' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_password' ) ); ?>" id="wpsc-paypal-express-api-password" />
			</td>
		</tr>
		<tr>
			<td>
				<label for="wpsc-paypal-express-api-signature"><?php _e( 'API Signature', 'wpsc' ); ?></label>
			</td>
			<td>
				<input type="text" name="<?php echo esc_attr( $this->setting->get_field_name( 'api_signature' ) ); ?>" value="<?php echo esc_attr( $this->setting->get( 'api_signature' ) ); ?>" id="wpsc-paypal-express-api-signature" />
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'Sandbox Mode', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'sandbox_mode' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<tr>
			<td>
				<label><?php _e( 'IPN', 'wpsc' ); ?></label>
			</td>
			<td>
				<label><input <?php checked( $this->setting->get( 'ipn' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="1" /> <?php _e( 'Yes', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( (bool) $this->setting->get( 'ipn' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'ipn' ) ); ?>" value="0" /> <?php _e( 'No', 'wpsc' ); ?></label>
			</td>
		</tr>
		<?php if ( ! $this->is_currency_supported() ): ?>
			<tr>
				<td colspan="2">
					<h4><?php _e( 'Currency Conversion', 'wpsc' ); ?></h4>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<p><?php _e( 'Your base currency is currently not accepted by PayPal. As a result, before a payment request is sent to Paypal, WP e-Commerce has to convert the amounts into one of Paypal supported currencies. Please select your preferred currency below.', 'wpsc' ); ?></p>
				</td>
			</tr>
			<tr>
				<td>
					<label for "wpsc-paypal-express-currency"><?php _e( 'Paypal Currency', 'wpsc' ); ?></label>
				</td>
				<td>
					<select name="<?php echo esc_attr( $this->setting->get_field_name( 'currency' ) ); ?>" id="wpsc-paypal-express-currency">
						<?php foreach ($this->gateway->get_supported_currencies() as $currency): ?>
							<option <?php selected( $currency, $paypal_currency ); ?> value="<?php echo esc_attr( $currency ); ?>"><?php echo esc_html( $currency ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
		<?php endif ?>

		<?php
	}

	protected function is_currency_supported() {
		$code = parent::get_currency_code();
		return in_array( $code, $this->gateway->get_supported_currencies() );
	}

	public function get_currency_code() {
		$code = parent::get_currency_code();
		if ( ! in_array( $code, $this->gateway->get_supported_currencies() ) )
			$code = $this->setting->get( 'currency', 'USD' );
		return $code;
	}

	protected function convert( $amt ) {
		if ( $this->is_currency_supported() )
			return $amt;

		return wpsc_convert_currency( $amt, parent::get_currency_code(), $this->get_currency_code() );
	}

	public function process() {
		$total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
		$options = array(
			'return_url' => $this->get_return_url(),
			'invoice'    => $this->purchase_log->get( 'sessionid' ),
		);
		$options += $this->checkout_data->get_gateway_data();
		$options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

		if ( $this->setting->get( 'ipn', false ) )
			$options['notify_url'] = $this->get_notify_url();

		$response = $this->gateway->setup_purchase( $options );
		if ( $response->is_successful() ) {
			$url = ( $this->setting->get( 'sandbox_mode' ) ? self::SANDBOX_URL : self::LIVE_URL ) . $response->get( 'token' );
			wp_redirect( $url );
		} else {
			wpsc_update_customer_meta( 'paypal_express_checkout_errors', $response->get_errors() );
			$url = add_query_arg( array(
				'payment_gateway'          => 'paypal-express-checkout',
				'payment_gateway_callback' => 'display_paypal_error',
			), $this->get_return_url() );
		}

		wp_redirect( $url );
		exit;
	}
}