<?php

add_filter(	'wpsc_settings_get_gateways', '_wpsc_filter_merchant_v2_get_gateways' );

function _wpsc_filter_merchant_v2_get_gateways( $gateways ) {
	global $nzshpcrt_gateways;

	foreach ( $nzshpcrt_gateways as $gateway ) {
		$name =   empty( $gateway['admin_name'] )
		        ? $gateway['name']
		        : $gateway['admin_name'];

		$gateways[] = array(
			'id' => $gateway['internalname'],
			'name' => $name,
		);
	}

	return $gateways;
}

add_filter(
	'wpsc_settings_gateway_form',
	'_wpsc_filter_merchant_v2_gateway_form',
	10,
	2
);

function _wpsc_filter_merchant_v2_gateway_form( $form, $selected_gateway ) {
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
			'name'              => $selected_gateway_data['name'],
			'form_fields'       => $output . $selected_gateway_data['form'](),
			'has_submit_button' => 0,
		);
	}

	return $return;
}