<?php

class WPSC_Settings_Tab_Gateway extends WPSC_Settings_Tab
{
	private $active_gateways;
	private $gateway_names;

	public function __construct() {
		if ( isset( $_REQUEST['payment_gateway_id'] ) )
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_payment_gateway', $_REQUEST['payment_gateway_id'] );

		$this->active_gateways = get_option( 'custom_gateway_options' );
		$this->gateway_names = get_option( 'payment_gateway_names' );
	}

	private function get_gateway_form_legacy( $selected_gateway ) {
		global $nzshpcrt_gateways;

		$payment_gateway_names = get_option('payment_gateway_names');
		$return                = false;
		$selected_gateway_data = false;

		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( $gateway['internalname'] == $selected_gateway ) {
				$selected_gateway_data = $gateway;
				break;
			}
		}

		if ( $selected_gateway_data ) {
			if ( array_key_exists( $selected_gateway, $payment_gateway_names ) ) {
				$display_name = $payment_gateway_names[$selected_gateway];
			} elseif ( ! empty( $selected_gateway_data['display_name'] ) ) {
				$display_name = $selected_gateway_data['display_name'];
			} else {
				switch($selected_gateway_data['payment_type']) {
					case "paypal";
						$display_name = "PayPal";
						break;

					case "manual_payment":
						$display_name = "Manual Payment";
						break;

					case "google_checkout":
						$display_name = "Google Checkout";
						break;

					case "credit_card":
					default:
						$display_name = "Credit Card";
						break;
				}
			}
			ob_start();
			?>
				<tr>
					<td><?php esc_html_e( 'Display Name', 'wpsc' ); ?></td>
					<td>
						<input type="text" name="user_defined_name[<?php echo esc_attr( $selected_gateway ); ?>]" value="<?php echo esc_html( $display_name ); ?>" /><br />
						<small><?php esc_html_e( 'The text that people see when making a purchase.', 'wpsc' ); ?></small>
					</td>
				</tr>
			<?php
			$output = ob_get_clean();
			$return = array(
				'name' => $selected_gateway_data['name'],
				'form_fields' => $output . $selected_gateway_data['form'](),
			);
		}

		return $return;
	}

	private function get_gateway_form( $gateway_name ) {
		if ( ! wpsc_is_payment_gateway_registered( $gateway_name ) )
			return _wpsc_get_payment_form_legacy( $gateway_name, $selected_gateway_data );

		$payment_gateway_names = get_option('payment_gateway_names');
		$form                  = array();
		$output                = array( 'name' => '&nbsp;', 'form_fields' => __( 'To configure a payment module select one on the left.', 'wpsc' ), 'has_submit_button' => 0 );
		$gateway               = wpsc_get_payment_gateway( $gateway_name );
		$display_name          = empty( $payment_gateway_names[$gateway_name] ) ? $gateway->get_title() : $payment_gateway_names[$gateway_name];
		ob_start();

		?>
		<tr>
			<td style='border-top: none;'>
				<?php _e( 'Display Name', 'wpsc' ); ?>
			</td>
			<td style='border-top: none;'>
				<input type='text' name='user_defined_name[<?php echo esc_attr( $gateway_name ); ?>]' value='<?php echo esc_attr( $display_name ); ?>' /><br />
				<span class='small description'><?php _e('The text that people see when making a purchase', 'wpsc'); ?></span>
			</td>
		</tr>
		<?php
		$gateway->setup_form();

		$output = array(
			'name'              => $gateway->get_title(),
			'form_fields'       => ob_get_clean(),
			'has_submit_button' => 1,
		);
		return $output;
	}

	public function display_payment_gateway_settings_form() {
		$selected_gateway = (string) get_user_option( 'wpsc_settings_selected_payment_gateway', get_current_user_id() );
		if ( empty( $selected_gateway ) && ! empty( $this->active_gateways ) )
			$selected_gateway = $this->active_gateways[0];

		$payment_data = $this->get_gateway_form( $selected_gateway );

		if ( ! $payment_data ) {
			$payment_data = array(
				'name'        => __( 'Edit Gateway Settings', 'wpsc' ),
				'form_fields' => __( 'Modify a payment gateway settings by clicking "Edit" link on the left.', 'wpsc' ),
			);
		}
		?>

		<td id='wpsc-payment-gateway-settings-panel' class='wpsc-module-settings' rowspan='2'>
			<div class='postbox'>
			<h3 class='hndle'><?php echo $payment_data['name']; ?></h3>
			<div class='inside'>
			<table class='form-table'>
				<?php echo $payment_data['form_fields']; ?>
			</table>
			<div class='submit'>
				<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ) ?>' />
			</div>
		</div>
</td>
		<?php
	}

	private function gateway_item( $gateway, $title = '', $checked = false ) {
		?>
			<div class="wpsc-select-gateway">
				<div class='wpsc-gateway-actions'>
					<span class="edit">
						<a class='edit-payment-module' data-gateway-id="<?php echo esc_attr( $gateway ); ?>" title="<?php _e( "Edit this Payment Gateway's Settings", 'wpsc' ) ?>" href='<?php echo esc_attr( add_query_arg( 'payment_gateway_id', $gateway ) ); ?>'><?php esc_html_e( 'Edit', 'wpsc' ); ?></a>
					</span>
				</div>
				<p>
					<input name='wpsc_options[custom_gateway_options][]' <?php checked( $checked ); ?> type='checkbox' value='<?php echo esc_attr( $gateway ); ?>' id='<?php echo esc_attr( $gateway ); ?>_id' />
					<label for='<?php echo esc_attr( $gateway ); ?>_id'><?php echo esc_html( $title ); ?></label>
				</p>
			</div>
		<?php
	}

	private function gateway_list() {
		$selected_gateways = get_option( 'custom_gateway_options', array() );

		foreach ( WPSC_Payment_Gateways::get_gateways() as $gateway ) {
			$gateway_meta = WPSC_Payment_Gateways::get_meta( $gateway );
			$this->gateway_item( $gateway, $gateway_meta['name'], in_array( $gateway, $selected_gateways ) );
		}

		// compat with older API
		global $nzshpcrt_gateways;
		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( isset( $gateway['admin_name'] ) )
				$gateway['name'] = $gateway['admin_name'];
			$this->gateway_item( $gateway['internalname'], $gateway['name'], in_array( $gateway['internalname'], $selected_gateways ) );
		}
	}

	public function display() {
		global $wpdb, $nzshpcrt_gateways;
	?>

		<div class='metabox-holder'>
			<input type='hidden' name='gateway_submits' value='true' />
			<input type='hidden' name='wpsc_gateway_settings' value='gateway_settings' />
			<table id='wpsc-payment-gateway-settings' class='wpsc-edit-module-options'>
				<tr>
					<td>
						<div class='postbox'>
							<h3 class='hndle'><?php _e( 'Select Payment Gateways', 'wpsc' ); ?></h3>
							<div class='inside'>
								<p><?php _e( 'Activate the payment gateways that you want to make available to your customers by selecting them below.', 'wpsc' ); ?></p>
								<br />
								<?php
									$this->gateway_list();
								?>
								<div class='submit gateway_settings'>
									<input type='hidden' value='true' name='update_gateways' />
									<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ) ?>' name='updateoption' />
								</div>

								<h4><?php _e( 'We Recommend', 'wpsc' ); ?></h4>
								<a style="border-bottom:none;" href="https://www.paypal.com/nz/mrb/pal=LENKCHY6CU2VY" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/paypal-referal.gif" border="0" alt="Sign up for PayPal and start accepting credit card payments instantly." /></a> <br /><br />
								<a style="border-bottom:none;" href="http://checkout.google.com/sell/?promo=seinstinct" target="_blank"><img src="https://checkout.google.com/buyer/images/google_checkout.gif" border="0" alt="Sign up for Google Checkout" /></a>
							</div>
						</div>
					</td>
					<?php $this->display_payment_gateway_settings_form(); ?>
				</tr>
			</table>
		</div>

	<?php
	}
}