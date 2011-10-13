<?php

class WPSC_Payment_Gateway_Paypal_Express_Checkout extends WPSC_Payment_Gateway
{	
	private $gateway;
	
	public function __construct() {
		parent::__construct();
		$this->title = __( 'Paypal Express Checkout 3.0', 'wpsc' );
		require_once( 'php-merchants/paypal-express-checkout.php' );
		$this->gateway = new PHP_Merchant_Paypal_Express_Checkout();
		$this->gateway->set_options( array( 
			'currency' => $this->get_currency_code(),
			'test'     => (bool) $this->setting->get( 'sandbox_mode' ),
		) );
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
		$result = $this->gateway->setup_purchase( $total );
	}
}