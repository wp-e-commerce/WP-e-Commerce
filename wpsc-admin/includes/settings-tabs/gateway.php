<?php

class WPSC_Settings_Tab_Gateway extends WPSC_Settings_Tab {
	private $active_gateways;
	private $gateway_names;

	public function __construct() {
		if ( isset( $_REQUEST['payment_gateway_id'] ) ) {
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_payment_gateway', $_REQUEST['payment_gateway_id'] );
		} else {
			update_user_option( get_current_user_id(), 'wpsc_settings_selected_payment_gateway', '' );
		}
		$this->active_gateways = get_option( 'custom_gateway_options' );
		$this->gateway_names = get_option( 'payment_gateway_names' );

		$this->hide_submit_button();
	}

	private function get_gateway_form( $selected_gateway ) {
		return apply_filters( 'wpsc_settings_gateway_form', array(), $selected_gateway );
	}

	private function get_gateway_settings_url( $gateway ) {
		$location = isset( $_REQUEST['current_url'] ) ? $_REQUEST['current_url'] : $_SERVER['REQUEST_URI'];
		$gateway  = ! empty( $gateway ) ? $gateway : '';

		return add_query_arg( array(
			'tab'                => 'gateway',
			'page'               => 'wpsc-settings',
			'payment_gateway_id' => $gateway
		), $location );
	}

	public function display_payment_gateway_settings_form( $selected_gateway = null ) {
		if ( ! $selected_gateway ) {
			$selected_gateway = (string) get_user_option( 'wpsc_settings_selected_payment_gateway', get_current_user_id() );
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
				<tbody>
					<?php echo $payment_data['form_fields']; ?>
					<tr><td colspan="2">
						<?php // hidden because most gateways provide their own update button. ?>
						<?php if ( $payment_data['has_submit_button'] !== 1 ) { ?>
							<p class="submit inline-edit-save">
								<a class="button edit-payment-module-cancel" title="<?php esc_attr_e( "Cancel editing this Payment Gateway's settings", 'wpsc' ) ?>"><?php esc_html_e( "Cancel", 'wpsc' ); ?></a>
								<input type="submit" name="submit" class="button button-primary edit-payment-module-update" value='<?php _e( "Update &raquo;", 'wpsc' ); ?>'>
							</p>
						<?php } ?>
					</td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function display() {
		global $wpdb, $nzshpcrt_gateways;
	?>

		<h3><?php _e( 'Select Payment Gateways', 'wpsc' ); ?></h3>
		<p><?php _e( 'Activate the payment gateways that you want to make available to your customers by selecting them below.', 'wpsc' ); ?></p>

		<table id='wpsc-payment-gateway-settings' class='wpsc-edit-module-options wp-list-table widefat plugins'>
			<thead>
				<tr>
					<th scope="col" id="wpsc-gateway-active" class="manage-column"></th>
					<th scope="col" id="wpsc-gateway-name" class="manage-column column-name"><?php _e( 'Payment Gateway', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-gateway-display-name" class="manage-column column-description"><?php _e( 'Display Name', 'wpsc' ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" id="wpsc-gateway-active" class="manage-column"></th>
					<th scope="col" id="wpsc-gateway-name" class="manage-column column-name"><?php _e( 'Payment Gateway', 'wpsc' ); ?></th>
					<th scope="col" id="wpsc-gateway-display-name" class="manage-column column-description"><?php _e( 'Display Name', 'wpsc' ); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php $this->gateway_list(); ?>
			</tbody>
		</table>
		<?php submit_button( __( 'Save Changes' ) ); ?>
		<h4><?php _e( 'WP e-Commerce Recommends', 'wpsc' ); ?></h4>
		<a style="border-bottom:none;" href="https://www.paypal.com/nz/mrb/pal=LENKCHY6CU2VY" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/paypal-referal.gif" border="0" alt="<?php esc_attr_e( 'Sign up for PayPal and start accepting credit card payments instantly.', 'wpsc' ); ?>" /></a>
	<?php
	}

	private function gateway_list_item( $gateway, $force ) {
		$checked = in_array( $gateway['id'], $this->active_gateways );

		$active = $checked ? 'active' : 'inactive';
		$hidden = $force   ? '' : "style='display: none;'";

		$edithidden = $hidden;

		$payment_gateway_names = get_option( 'payment_gateway_names' );
		$display_name = isset( $payment_gateway_names[ $gateway['id'] ] ) ? $payment_gateway_names[ $gateway['id'] ] : '' ;
		$gateway_data = false;
		?>
			<tr class="wpsc-select-gateway <?php echo $active; ?>" data-gateway-id="<?php echo esc_attr( $gateway['id'] ); ?>" id="gateway_list_item_<?php echo $gateway['id'];?>">
				<th scope="row" class="check-column">
					<label class="screen-reader-text" for="<?php echo esc_attr( $gateway['id'] ); ?>_id"><?php _e( "Select", "wpsc" ); ?> <?php echo esc_html( $gateway['name'] ); ?></label>
					<input name='wpsc_options[custom_gateway_options][]' <?php checked( $checked ); ?> type='checkbox' value='<?php echo esc_attr( $gateway['id'] ); ?>' id='<?php echo esc_attr( $gateway['id'] ); ?>_id' />
				</th>
				<td class="plugin-title">
					<label for='<?php echo esc_attr( $gateway['id'] ); ?>_id'><strong><?php echo esc_html( $gateway['name'] ); ?></strong></label>
					<div class="row-actions-visible">
						<span class="edit">
							<a class='edit-payment-module' title="<?php esc_attr_e( "Edit this Payment Gateway's Settings", 'wpsc' ) ?>" href='<?php echo esc_url( $this->get_gateway_settings_url( $gateway['id'] ) ); ?>'><?php esc_html_e( 'Settings', 'wpsc' ); ?></a>
							<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
						</span>
					</div>
				</td>
				<td class="plugin-description">
					<?php echo esc_html( $display_name ); ?>
				</td>
			</tr>
			<tr id="wpsc_gateway_settings_<?php echo esc_attr( $gateway['id'] ); ?>" data-gateway-id="<?php echo esc_attr( $gateway['id'] ); ?>" class='gateway_settings <?php echo $active; ?>' <?php echo $hidden; ?> >
				<td colspan="3" id="wpsc_gateway_settings_<?php echo esc_attr( $gateway['id'] ); ?>_container">
					<?php if ( $force ) {
						$this->display_payment_gateway_settings_form( $gateway['id'] );
					} ?>
				</td>
			</tr>

		<?php
	}

	private function gateway_list() {
		$gateways = apply_filters( 'wpsc_settings_get_gateways', array() );

		$selected_gateway = (string) get_user_option( 'wpsc_settings_selected_payment_gateway', get_current_user_id() );

		foreach ( $gateways as $gateway ) {
			$this->gateway_list_item( $gateway, $selected_gateway === $gateway['id'] );
		}
	}

	public function callback_submit_options() {
		do_action( 'wpsc_submit_gateway_options' );
	}
}
