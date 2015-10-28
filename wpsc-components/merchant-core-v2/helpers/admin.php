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

/**
 * Filters deprecated gateways out of available gateways list.
 *
 * Only occurs if there is a 3.0 API replacement for the gateway and it is not currently active.
 * Note: Pro Hosted and Pro are not the same thing.
 *
 * @since  3.9.0
 *
 * @param  array $gateways Original list of gateways.
 * @return array           Modified list of gateways.
 */
function wpsc_filter_deprecated_v2_gateways( $gateways ) {

	// Don't remove gateways if 1.0 theme engine is in use.
	$te = get_option( 'wpsc_get_active_theme_engine', '1.0' );

	if ( '1.0' == $te ) {
		return $gateways;
	}

	$deprecated_gateways = array(
		'wpsc_merchant_paypal_express'
	);

	// Loops through available gateways, checks if available gateway is both inactive and deprecated, and removes it.
	foreach ( $gateways as $index => $gateway ) {
		if ( in_array( $gateway['id'], $deprecated_gateways ) && ! in_array( $gateway['id'], get_option( 'custom_gateway_options', array() ) ) ) {
			unset( $gateways[ $index ] );
		}
	}

	return $gateways;
}

add_filter( 'wpsc_settings_get_gateways', 'wpsc_filter_deprecated_v2_gateways', 25 );

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
		if ( array_key_exists( $selected_gateway, $payment_gateway_names ) && $payment_gateway_names[$selected_gateway] !== "") {
			$display_name = $payment_gateway_names[$selected_gateway];
		} elseif ( ! empty( $selected_gateway_data['display_name'] ) && $selected_gateway_data['display_name'] !== "" ) {
			$display_name = $selected_gateway_data['display_name'];
		} else {
			switch($selected_gateway_data['payment_type']) {
				case "paypal";
					$display_name = __( 'PayPal', 'wp-e-commerce' );
					break;

				case "manual_payment":
					$display_name = __( 'Manual Payment', 'wp-e-commerce' );
					break;

				case "google_checkout":
					$display_name = __( 'Google Checkout', 'wp-e-commerce' );
					break;

				case "credit_card":
				default:
					$display_name = __( 'Credit Card', 'wp-e-commerce' );
					break;
			}
		}

		ob_start();
		?>
			<tr>
				<td width="150"><?php esc_html_e( 'Display Name', 'wp-e-commerce' ); ?></td>
				<td>
					<input type="text" name="user_defined_name[<?php echo esc_attr( $selected_gateway ); ?>]" value="<?php echo esc_html( $display_name ); ?>" />
					<p class="description"><?php esc_html_e( 'The text that people see when making a purchase.', 'wp-e-commerce' ); ?></p>
				</td>
			</tr>
		<?php
		$output = ob_get_clean();
		$return = array(
			'name'              => $selected_gateway_data['name'],
			'form_fields'       => $output . call_user_func( $selected_gateway_data['form'] ),
			'has_submit_button' => 0,
		);
	}

	return $return;
}

add_action(
	'wpsc_submit_gateway_options',
	'_wpsc_action_merchant_v2_submit_gateway_options'
);

function _wpsc_action_merchant_v2_submit_gateway_options() {

	if ( isset( $_POST['user_defined_name'] ) && is_array( $_POST['user_defined_name'] ) ) {
		$payment_gateway_names = get_option( 'payment_gateway_names' );

		if ( !is_array( $payment_gateway_names ) ) {
			$payment_gateway_names = array( );
		}
		$payment_gateway_names = array_merge( $payment_gateway_names, (array)$_POST['user_defined_name'] );
		update_option( 'payment_gateway_names', array_map( 'sanitize_text_field', $payment_gateway_names ) );
	}

	$custom_gateways = get_option( 'custom_gateway_options' );

	global $nzshpcrt_gateways;
	foreach ( $nzshpcrt_gateways as $gateway ) {
		if ( in_array( $gateway['internalname'], $custom_gateways ) ) {
			if ( isset( $gateway['submit_function'] ) ) {
				call_user_func_array( $gateway['submit_function'], array() );
				$changes_made = true;
			}
		}
	}
	if ( (isset( $_POST['payment_gw'] ) && $_POST['payment_gw'] != null ) ) {
		update_option( 'payment_gateway', sanitize_text_field( $_POST['payment_gw'] ) );
	}
}
