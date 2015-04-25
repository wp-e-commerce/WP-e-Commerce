<?php

class WPSC_Payment_Gateway_Amazon_Payments extends WPSC_Payment_Gateway {

	public function __construct() {

		parent::__construct();
		$this->title = __( 'Amazon Payments', 'wpsc' );

	}

	public function load() {
		return version_compare( phpversion(), '5.3', '>=' );
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
		$this->purchase_log->set( 'processed', WPSC_PAYMENT_STATUS_RECEIVED )->save();
		$this->go_to_transaction_results();
	}
}