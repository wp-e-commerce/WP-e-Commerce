<?php

class WPSC_Settings_Tab_Gateway extends WPSC_Settings_Tab {
	private $active_gateways;
	private $gateway_names;

	public function __construct() {
		if ( isset( $_REQUEST['payment_gateway_id'] ) )
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_payment_gateway', $_REQUEST['payment_gateway_id'] );

		$this->active_gateways = get_option( 'custom_gateway_options' );
		$this->gateway_names = get_option( 'payment_gateway_names' );

		$this->hide_submit_button();
	}

	private function get_gateway_form( $selected_gateway ) {
		return apply_filters( 'wpsc_settings_gateway_form', array(), $selected_gateway );
	}

	private function get_gateway_settings_url( $gateway ) {
		$location = isset( $_REQUEST['current_url'] ) ? $_REQUEST['current_url'] : $_SERVER['REQUEST_URI'];
		$location = add_query_arg( array(
			'tab'                => 'gateway',
			'page'               => 'wpsc-settings',
			'payment_gateway_id' => $gateway || "",
		), $location );
		return $location;
	}

	public function display_payment_gateway_settings_form( $selected_gateway = null ) {
		if ( ! $selected_gateway ) {
			$selected_gateway = (string) get_user_option( 'wpsc_settings_selected_payment_gateway', get_current_user_id() );
			if ( empty( $selected_gateway ) && ! empty( $this->active_gateways ) )
				$selected_gateway = $this->active_gateways[0];
		}
		$payment_data = $this->get_gateway_form( $selected_gateway );
		if ( ! $payment_data ) {
			$payment_data = array(
				'name'              => __( 'Edit Gateway Settings', 'wpsc' ),
				'form_fields'       => __( 'Modify a payment gateway settings by clicking "Edit" link on the left.', 'wpsc' ),
				'has_submit_button' => 1,
			);
		}

		?>
		<div id="gateway_settings_<?php echo esc_attr( $selected_gateway ); ?>_form" class='gateway_settings_form'>
			<table class='form-table'>
				<?php echo $payment_data['form_fields']; ?>
			</table>
			<?php // hidden because most gateways provide their own update button. ?>
			<?php if ($payment_data['has_submit_button'] !== 1) { ?>
				<!--
				<div class='submit'>
					<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ) ?>' />
				</div>
				-->
				<?php submit_button( __( 'Update &raquo;', 'wpsc' ) ); ?>
			<?php } ?>
		</div>
		<?php
	}

	public function display() {
		global $wpdb, $nzshpcrt_gateways;
		if ( empty( $nzshpcrt_gateways ) )
			$nzshpcrt_gateways     = nzshpcrt_get_gateways();
	?>

		<!-- <div class='metabox-holder'> -->
			<table id='wpsc-payment-gateway-settings' class='wpsc-edit-module-options'>
				<tr>
					<td>
						<!-- <div class='postbox'> -->
							<h3 class='hndle'><?php _e( 'Select Payment Gateways', 'wpsc' ); ?></h3>
							<div class='inside'>
								<p><?php _e( 'Activate the payment gateways that you want to make available to your customers by selecting them below.', 'wpsc' ); ?></p>
								<br />
								<?php $this->gateway_list(); ?>
								<?php submit_button( __( 'Save Changes' ) ); ?>
							</div>
						<!-- </div> -->

						<h4><?php _e( 'We Recommend', 'wpsc' ); ?></h4>
						<a style="border-bottom:none;" href="https://www.paypal.com/nz/mrb/pal=LENKCHY6CU2VY" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/paypal-referal.gif" border="0" alt="<?php esc_attr_e( 'Sign up for PayPal and start accepting credit card payments instantly.', 'wpsc' ); ?>" /></a> <br /><br />
						<a style="border-bottom:none;" href="http://checkout.google.com/sell/?promo=seinstinct" target="_blank"><img src="https://checkout.google.com/buyer/images/google_checkout.gif" border="0" alt="<?php esc_attr_e( 'Sign up for Google Checkout', 'wpsc' ); ?>" /></a>

					</td>
				</tr>
			</table>
		<!-- </div> -->

	<?php
	}

	private function gateway_list_item( $gateway, $force ) {
		$checked = in_array( $gateway['id'], $this->active_gateways );
		$hidden = ( $checked || $force ? "" : "style='display: none;'" );
		$edithidden = ( $checked || $force ? "style='display: none;'" : "");
		?>
			<div class="wpsc-select-gateway" id="gateway_list_item_<?php echo $gateway['id'];?>">
				<div class='wpsc-gateway-actions'>
					<span class="edit">
						<a class='edit-payment-module' data-gateway-id="<?php echo esc_attr( $gateway['id'] ); ?>" title="<?php esc_attr_e( "Edit this Payment Gateway's Settings", 'wpsc' ) ?>" href='<?php echo esc_url( $this->get_gateway_settings_url( $gateway['id'] ) ); ?>' <?php echo $edithidden; ?>><?php esc_html_e( 'Edit', 'wpsc' ); ?></a>
						<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-feedback" title="" alt="" />
					</span>
				</div>
				<p>
					<input name='wpsc_options[custom_gateway_options][]' <?php checked( $checked ); ?> type='checkbox' value='<?php echo esc_attr( $gateway['id'] ); ?>' id='<?php echo esc_attr( $gateway['id'] ); ?>_id' />
					<label for='<?php echo esc_attr( $gateway['id'] ); ?>_id'><?php echo esc_html( $gateway['name'] ); ?></label>
				</p>
				<div id="wpsc_gateway_settings_<?php echo esc_attr( $gateway['id'] ); ?>" class='gateway_settings' <?php echo $hidden; ?> >
					<a class="edit-payment-module-cancel" data-gateway-id="<?php echo esc_attr( $gateway['id'] ); ?>" title="<?php esc_attr_e( "Cancel editing this Payment Gateway's settings", 'wpsc' ) ?>" href='<?php echo esc_url( $this->get_gateway_settings_url( '' ) ); ?>'><?php esc_html_e( 'Cancel', 'wpsc' ); ?></a>
					<?php if ( $checked || $force ) {
						$this->display_payment_gateway_settings_form( $gateway['id'] );
					} ?>
				</div>
			</div>
		<?php
	}

	private function gateway_list() {
		$gateways = apply_filters( 'wpsc_settings_get_gateways', array() );

		$selected_gateway = (string) get_user_option( 'wpsc_settings_selected_payment_gateway', get_current_user_id() );
		if ( empty( $selected_gateway ) && ! empty( $this->active_gateways ) ) {
			$selected_gateway = $this->active_gateways[0];
		}

		foreach ( $gateways as $gateway ) {
			$this->gateway_list_item( $gateway, $selected_gateway === $gateway['id'] );
		}
	}

	public function callback_submit_options() {
		do_action( 'wpsc_submit_gateway_options' );
	}
}