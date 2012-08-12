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

	private function get_gateway_form( $selected_gateway ) {
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
						$display_name = __( 'PayPal', 'wpsc' );
						break;

					case "manual_payment":
						$display_name = __( 'Manual Payment', 'wpsc' );
						break;

					case "google_checkout":
						$display_name = __( 'Google Checkout', 'wpsc' );
						break;

					case "credit_card":
					default:
						$display_name = __( 'Credit Card', 'wpsc' );
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
				'name'        => $selected_gateway_data['name'],
				'form_fields' => $output . $selected_gateway_data['form'](),
			);
		}

		return $return;
	}

	private function get_gateway_settings_url( $gateway ) {
		$location = isset( $_REQUEST['current_url'] ) ? $_REQUEST['current_url'] : $_SERVER['REQUEST_URI'];
		$location = add_query_arg( array(
			'tab'                => 'gateway',
			'page'               => 'wpsc-settings',
			'payment_gateway_id' => $gateway,
		), $location );
		return $location;
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

	public function display() {
		global $wpdb, $nzshpcrt_gateways;
		$payment_gateway_names = get_option( 'payment_gateway_names' );
		if ( empty( $nzshpcrt_gateways ) )
			$nzshpcrt_gateways     = nzshpcrt_get_gateways();
	?>

		<div class='metabox-holder'>
			<input type='hidden' name='gateway_submits' value='true' />
			<input type='hidden' name='wpsc_gateway_settings' value='gateway_settings' />
			<?php
			if ( get_option( 'custom_gateway' ) == 1 ) {
				$custom_gateway_hide = "style='display:block;'";
				$custom_gateway1 = 'checked="checked"';
			} else {
				$custom_gateway_hide = "style='display:none;'";
				$custom_gateway2 = 'checked="checked"';
			}
			 ?>
			<table id='wpsc-payment-gateway-settings' class='wpsc-edit-module-options'>
				<tr>
					<td>
						<div class='postbox'>
							<h3 class='hndle'><?php _e( 'Select Payment Gateways', 'wpsc' ); ?></h3>
							<div class='inside'>
								<p><?php _e( 'Activate the payment gateways that you want to make available to your customers by selecting them below.', 'wpsc' ); ?></p>
								<br />
								<?php
								$selected_gateways = get_option( 'custom_gateway_options' );
								foreach ( $nzshpcrt_gateways as $gateway ) {
									if ( isset( $gateway['admin_name'] ) )
										$gateway['name'] = $gateway['admin_name'];
								?>

									<div class="wpsc-select-gateway">
										<div class='wpsc-gateway-actions'>
											<span class="edit">
													<a class='edit-payment-module' data-gateway-id="<?php echo esc_attr( $gateway['internalname'] ); ?>" title="<?php esc_attr_e( "Edit this Payment Gateway's Settings", 'wpsc' ) ?>" href='<?php echo esc_url( $this->get_gateway_settings_url( $gateway['internalname'] ) ); ?>'><?php esc_html_e( 'Edit', 'wpsc' ); ?></a>
													<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
											</span>
									</div>
									<p>
										<input name='wpsc_options[custom_gateway_options][]' <?php checked( in_array( $gateway['internalname'], (array) $selected_gateways ) ); ?> type='checkbox' value='<?php echo esc_attr( $gateway['internalname'] ); ?>' id='<?php echo esc_attr( $gateway['internalname'] ); ?>_id' />
										<label for='<?php echo esc_attr( $gateway['internalname'] ); ?>_id'><?php echo esc_attr( $gateway['name'] ); ?></label>
									</p>
								</div>
					<?php }
								?>
								<div class='submit gateway_settings'>
									<input type='submit' value='<?php esc_attr_e( 'Update &raquo;', 'wpsc' ) ?>' name='updateoption' />
								</div>
								</div>
							</div>

								<h4><?php _e( 'We Recommend', 'wpsc' ); ?></h4>
								<a style="border-bottom:none;" href="https://www.paypal.com/nz/mrb/pal=LENKCHY6CU2VY" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/paypal-referal.gif" border="0" alt="<?php esc_attr_e( 'Sign up for PayPal and start accepting credit card payments instantly.', 'wpsc' ); ?>" /></a> <br /><br />
								<a style="border-bottom:none;" href="http://checkout.google.com/sell/?promo=seinstinct" target="_blank"><img src="https://checkout.google.com/buyer/images/google_checkout.gif" border="0" alt="<?php esc_attr_e( 'Sign up for Google Checkout', 'wpsc' ); ?>" /></a>

							</td>

							<?php $this->display_payment_gateway_settings_form(); ?>
				</tr>
			</table>
		</div>

	<?php
	}
}