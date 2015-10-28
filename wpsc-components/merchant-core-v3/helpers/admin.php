<?php

add_filter(
	'wpsc_settings_get_gateways',
	'_wpsc_merchant_v3_settings_get_gateways'
);

function _wpsc_merchant_v3_settings_get_gateways( $gateways ) {
	foreach ( WPSC_Payment_Gateways::get_gateways() as $gateway ) {
		$meta = WPSC_Payment_Gateways::get_meta( $gateway );
		$gateways[] = array(
			'id' => $gateway,
			'name' => $meta['name'],
		);
	}

	return $gateways;
}

add_filter(
	'wpsc_settings_gateway_form',
	'_wpsc_filter_merchant_v3_gateway_form',
	13,
	2
);

function _wpsc_filter_merchant_v3_gateway_form( $form, $gateway_id ) {
	if ( ! WPSC_Payment_Gateways::is_registered( $gateway_id ) )
		return $form;

	$payment_gateway_names = get_option('payment_gateway_names');
	$form                  = array();
	$output                = array( 'name' => '&nbsp;', 'form_fields' => __( 'To configure a payment module select one on the left.', 'wp-e-commerce' ), 'has_submit_button' => 0 );
	$gateway               = wpsc_get_payment_gateway( $gateway_id );
	$display_name          = empty( $payment_gateway_names[$gateway_id] ) ? $gateway->get_title() : $payment_gateway_names[$gateway_id];
	ob_start();

	?>
	<tr>
		<td style='border-top: none;'>
			<?php _e( 'Display Name', 'wp-e-commerce' ); ?>
		</td>
		<td style='border-top: none;'>
			<input type='text' name='user_defined_name[<?php echo esc_attr( $gateway_id ); ?>]' value='<?php echo esc_attr( $display_name ); ?>' /><br />
			<span class='small description'><?php _e('The text that people see when making a purchase.', 'wp-e-commerce'); ?></span>
		</td>
	</tr>
	<?php
	$gateway->setup_form();

	$output = array(
		'name'              => $gateway->get_title(),
		'form_fields'       => ob_get_clean(),
		'has_submit_button' => 0,
	);
	return $output;
}
