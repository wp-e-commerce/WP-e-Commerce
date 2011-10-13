<?php

class WPSC_Payment_Gateway_Paypal_Express_Checkout extends WPSC_Payment_Gateway
{
	const SANDBOX_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
	const LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
	private $gateway;

	public function __construct() {
		parent::__construct();
		$this->title = __( 'Paypal Express Checkout 3.0', 'wpsc' );
		require_once( 'php-merchant/gateways/paypal-express-checkout.php' );
		$this->gateway = new PHP_Merchant_Paypal_Express_Checkout();
		$this->gateway->set_options( array(
			'api_username'     => $this->setting->get( 'api_username' ),
			'api_password'    => $this->setting->get( 'api_password' ),
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
		if ( ! empty( $gateway_data['discount'] ) ) {
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
		var_dump( $gateway_data );
		return $gateway_data;
	}

	private function get_return_url() {
		$sep = '?';
		if ( get_option('permalink_structure') != '' )
			$sep = '&';
		return get_option( 'transact_url' ) . $sep . 'session_id=' . $this->purchase_log->get( 'sessionid' ) . '&gateway=paypal-express-checkout';
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
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ) ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="1" /> <?php _e( 'Enabled', 'wpsc' ); ?></label>&nbsp;&nbsp;&nbsp;
				<label><input <?php checked( $this->setting->get( 'sandbox_mode' ), false ); ?> type="radio" name="<?php echo esc_attr( $this->setting->get_field_name( 'sandbox_mode' ) ); ?>" value="0" /> <?php _e( 'Disabled', 'wpsc' ); ?></label>
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

	private function is_currency_supported() {
		$code = parent::get_currency_code();
		return in_array( $code, $this->gateway->get_supported_currencies() );
	}

	public function get_currency_code() {
		$code = parent::get_currency_code();
		if ( ! in_array( $code, $this->gateway->get_supported_currencies() ) )
			$code = $this->setting->get( 'currency', 'USD' );
		return $code;
	}

	private function convert( $amt ) {
		if ( $this->is_currency_supported() )
			return $amt;

		return wpsc_convert_currency( $amt, parent::get_currency_code(), $this->get_currency_code() );
	}

	public function process() {
		$total = $this->convert( $this->purchase_log->get( 'totalprice' ) );
		$options = array(
			'return_url' => $this->get_return_url(),
		);
		$options += $this->checkout_data->get_gateway_data();
		$options += $this->purchase_log->get_gateway_data( parent::get_currency_code(), $this->get_currency_code() );

		$response = $this->gateway->setup_purchase( $options );
		if ( $response->is_successful() ) {
			$url = ( $this->setting->get( 'sandbox_mode' ) ? self::SANDBOX_URL : self::LIVE_URL ) . $response->get( 'token' );
			wp_redirect( $url );
		} else {
			echo "SetExpressCheckout API call failed. ";
			$errors = $response->get_errors();
			for ( $i=0; $i < count( $errors ); $i++ ) {
				echo "<p>Error #" . ( $i + 1 ) . ": {$errors[$i]['details']} ({$errors[$i]['code']})</p>";
			}
		}
		exit;
	}
}