<?php

function _wpsc_gateway_item( $gateway, $title = '', $checked = false ) {
	?>
		<div class="wpsc_shipping_options">
			<div class='wpsc-shipping-actions wpsc-payment-actions'>
				<span class="edit">
					<a class='edit-payment-module' rel="<?php echo $gateway; ?>" onclick="event.preventDefault();" title="Edit this Payment Module" href='<?php echo esc_url( add_query_arg( 'payment_module', $gateway ) ); ?>' style="cursor:pointer;">Edit</a>
				</span>
			</div>
			<p>
				<input name='wpsc_options[custom_gateway_options][]' <?php checked( $checked ); ?> type='checkbox' value='<?php echo esc_attr( $gateway ); ?>' id='<?php echo esc_attr( $gateway ); ?>_id' />
				<label for='<?php echo esc_attr( $gateway ); ?>_id'><?php echo esc_html( $title ); ?></label>
			</p>
		</div>
	<?php
}

function _wpsc_gateway_list() {
	$selected_gateways = get_option( 'custom_gateway_options', array() );
	
	foreach ( WPSC_Payment_Gateways::get_gateways() as $gateway ) {
		$gateway_meta = WPSC_Payment_Gateways::get_meta( $gateway );
		_wpsc_gateway_item( $gateway, $gateway_meta['name'], in_array( $gateway, $selected_gateways ) );
	}
	
	global $nzshpcrt_gateways;
	foreach ( $nzshpcrt_gateways as $gateway ) {
		if ( isset( $gateway['admin_name'] ) )
			$gateway['name'] = $gateway['admin_name'];
		_wpsc_gateway_item( $gateway['internalname'], $gateway['name'], in_array( $gateway['internalname'], $selected_gateways ) );
	}
}

function wpsc_options_gateway() {
	global $wpdb, $nzshpcrt_gateways;

	$curgateway = get_option( 'payment_gateway' );

	$payment_gateway_names = get_option( 'payment_gateway_names' );
	
	if ( empty( $nzshpcrt_gateways ) )
		$nzshpcrt_gateways     = nzshpcrt_get_gateways();

	if ( is_array( $nzshpcrt_gateways ) ) {
		$selected_gateways = get_option( 'custom_gateway_options' );
		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( $gateway['internalname'] == $curgateway ) {
				$selected = "selected='selected'";
				$form = $gateway['form']();
				$selected_gateway_data = $gateway;
			} else {
				$selected = '';
			}

			if ( isset( $gateway['admin_name'] ) )
				$gateway['name'] = $gateway['admin_name'];

			$disabled = '';

			if ( !in_array( $gateway['internalname'], (array)$selected_gateways ) )
				$disabled = "disabled='disabled'";

			if ( !isset( $gateway['internalname'] ) )
				$gateway['internalname'] = '';

			$gatewaylist = '';
			$gatewaylist .= "<option $disabled value='" . esc_attr( $gateway['internalname'] ) . "' " . $selected . " >" . esc_attr( $gateway['name'] )  . "</option>";
		}
	}
	$nogw = '';
	$gatewaylist = "<option value='" . $nogw . "'>" . __( 'Please Select A Payment Gateway', 'wpsc' ) . "</option>" . $gatewaylist;
?>

	<script language='javascript' type='text/javascript'>
		function selectgateway() {
			document.forms.gateway_opt.submit();
		}
	</script>

	<div class="wrap">
	<div class='metabox-holder'>
		<form name='gatewayopt' method='post' id='gateway_opt' action='' class='wpsc_form_track'>
			<input type='hidden' name='gateway_submits' value='true' />
			<input type='hidden' name='wpsc_gateway_settings' value='gateway_settings' />
			<?php
			/* wpsc_setting_page_update_notification displays the wordpress styled notifications */
			wpsc_settings_page_update_notification(); ?>
			<table id='gateway_options' >
				<tr>
					<td class='select_gateway'>
						<div class='postbox'>
							<h3 class='hndle'><?php _e( 'General Settings', 'wpsc' ); ?></h3>
							<div class='inside'>
								<p><?php _e( 'Activate the payment gateways that you want to make available to your customers by selecting them below.', 'wpsc' ); ?></p>
								<br />
								<?php
								_wpsc_gateway_list();
								?>
								<div class='submit gateway_settings'>
									<input type='hidden' value='true' name='update_gateways' />
									<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ) ?>' name='updateoption' />
								</div>
								</div>
							</div>

								<h4><?php _e( 'We Recommend', 'wpsc' ); ?></h4>
								<a style="border-bottom:none;" href="https://www.paypal.com/nz/mrb/pal=LENKCHY6CU2VY" target="_blank"><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/paypal-referal.gif" border="0" alt="Sign up for PayPal and start accepting credit card payments instantly." /></a> <br /><br />
								<a style="border-bottom:none;" href="http://checkout.google.com/sell/?promo=seinstinct" target="_blank"><img src="https://checkout.google.com/buyer/images/google_checkout.gif" border="0" alt="Sign up for Google Checkout" /></a>

							</td>

							<td class='gateway_settings' rowspan='2'>
								<div class='postbox'>
							<?php
							
								if ( !isset( $_SESSION['previous_payment_name'] ) )
									$_SESSION['previous_payment_name'] = "";
								if ( !isset( $selected_gateway_data ) )
									$selected_gateway_data = "";
								$payment_data = wpsc_get_payment_form( $_SESSION['previous_payment_name'], $selected_gateway_data );
							?>
								<h3 class='hndle'><?php echo $payment_data['name']; ?></h3>
								<div class='inside'>
								<table class='form-table'>
									<?php echo $payment_data['form_fields']; ?>
								</table>
								<?php
								if ( $payment_data['has_submit_button'] == 0 ) 
									$update_button_css = 'style= "display: none;"';
								else
									$update_button_css = '';
								?>
								<div class='submit' <?php echo $update_button_css; ?>>
<?php wp_nonce_field( 'update-options', 'wpsc-update-options' ); ?>
									<input type='submit' value='<?php _e( 'Update &raquo;', 'wpsc' ) ?>' name='updateoption' />
								</div>
							</div>
					</td>
				</tr>
			</table>
		</form>
	</div>
</div>

<?php
							}
?>