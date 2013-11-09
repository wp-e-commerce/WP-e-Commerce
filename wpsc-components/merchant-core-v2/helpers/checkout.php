<?php

add_filter( 'wpsc_get_gateway_list', '_wpsc_filter_merchant_v2_get_gateway_list' );

function _wpsc_filter_merchant_v2_get_gateway_list() {
	ob_start();
	while ( wpsc_have_gateways() ) : wpsc_the_gateway(); ?>
		<div class="custom_gateway">
			<label><input type="radio" value="<?php echo wpsc_gateway_internal_name();?>" <?php echo wpsc_gateway_is_checked(); ?> name="custom_gateway" class="custom_gateway"/><?php echo wpsc_gateway_name(); ?>
				<?php if ( wpsc_show_gateway_image() ):
					$gateway_image = '<img src="' . esc_url( wpsc_gateway_image_url() ) . '" alt="' . esc_attr( wpsc_gateway_name() ) . '" style="position:relative; top:5px;" />';
					echo apply_filters( 'wpsc_gateway_image', $gateway_image, wpsc_gateway_internal_name() );
				endif; ?>
			</label>

			<?php if ( wpsc_gateway_form_fields() ) : ?>
				<table class='wpsc_checkout_table <?php echo wpsc_gateway_form_field_style();?>'>
					<?php echo wpsc_gateway_form_fields();?>
				</table>
			<?php endif; ?>
		</div>
	<?php endwhile;
	return ob_get_clean();
}

function _wpsc_filter_merchant_v2_payment_method_form_fields( $fields ) {
	$selected_value =   isset( $_POST['wpsc_payment_method'] )
	                   ? $_POST['wpsc_payment_method']
	                   : '';

	if ( empty( $selected_value ) ) {
		$current_purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );
		$purchase_log = new WPSC_Purchase_Log( $current_purchase_log_id );
		$selected_value = $purchase_log->get( 'gateway' );
	}

	foreach ( _wpsc_merchant_v2_get_active_gateways() as $gateway ) {
		$gateway = (object) $gateway;
		$title = $gateway->name;
		if ( ! empty( $gateway->image ) )
			$title .= ' <img src="' . $gateway->image . '" alt="' . $gateway->name . '" />';

		$field = array(
			'title'   => $title,
			'type'    => 'radio',
			'value'   => $gateway->internalname,
			'name'    => 'wpsc_payment_method',
			'checked' => $selected_value == $gateway->internalname,
		);

		$fields[] = $field;
	}

	// check the first payment gateway by default
	if ( empty( $selected_value ) )
		$fields[0]['checked'] = true;

	return $fields;
}

add_filter(
	'wpsc_payment_method_form_fields',
	'_wpsc_filter_merchant_v2_payment_method_form_fields'
);

function _wpsc_filter_merchant_v2_field_after( $output, $field, $r ) {
	if ( $field['name'] != 'wpsc_payment_method' )
		return $output;

	foreach ( _wpsc_merchant_v2_get_active_gateways() as $gateway ) {
		if ( $gateway['internalname'] == $field['value'] ) {
			$extra_form = _wpsc_merchant_v2_get_gateway_form( $gateway );
			$extra_form = _wpsc_merchant_v2_hack_gateway_field_names( $extra_form, $gateway );
			if ( ! empty( $extra_form ) ) {
				$output .= '<table class="wpsc-payment-gateway-extra-form wpsc-payment-gateway-extra-form-' . $gateway['internalname'] . '"><tbody>';
				$output .= $extra_form;
				$output .= '</tbody></table>';
			}
			break;
		}
	}

	return $output;
}
add_filter( 'wpsc_field_after', '_wpsc_filter_merchant_v2_field_after', 10, 3 );

function _wpsc_merchant_v2_hack_gateway_field_names( $extra_form, $gateway ) {
	$fields = array(
		'card_number',
		'card_number1',
		'card_number2',
		'card_number3',
		'card_number4',
		'expiry',
		'card_code',
		'cctype',
	);

	$regexp = '/(name\s*=\s*[\'"])(' . implode( '|', $fields ) . ')(["\'\[])/';
	$replace = '/$1extra_form[' . $gateway['internalname'] . '][$2]$3';
	$extra_form = preg_replace( $regexp, $replace, $extra_form );
	return $extra_form;
}

function _wpsc_merchant_v2_get_active_gateways() {
	global $nzshpcrt_gateways;
	static $gateways = null;

	if ( is_null( $gateways ) ) {
		$active = get_option( 'custom_gateway_options' );
		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( in_array( $gateway['internalname'], (array) $active ) )
				$gateways[] = $gateway;
		}
	}

	return $gateways;
}

function _wpsc_merchant_v2_get_gateway_form( $gateway ) {
	global $gateway_checkout_form_fields, $wpsc_gateway_error_messages;

	$submitted_gateway = isset( $_POST['wpsc_payment_method'] )
	                     ? $_POST['wpsc_payment_method']
	                     : '';

	$error = array(
		'card_number' => '',
		'expdate' => '',
		'card_code' => '',
		'cctype' => '',
	);

	if (
		   ! empty( $submitted_gateway )
		&& $submitted_gateway == $gateway['internalname']
		&& is_array( $wpsc_gateway_error_messages )
	)
		$error = array_merge( $error, $wpsc_gateway_error_messages );

	$classes = array();
	foreach ( array( 'card_number', 'expdate', 'card_code', 'cctype' ) as $field ) {
		if ( empty( $error[$field] ) )
			$classes[$field] = '';
		else
			$classes[$field] = 'class="validation-error"';
	}

	// Match fields to gateway
	switch ( $gateway['internalname'] ) {
		case 'paypal_pro' : // legacy
		case 'wpsc_merchant_paypal_pro' :
			$output = sprintf(
				$gateway_checkout_form_fields[$gateway['internalname']],
				$classes['card_number'], $error['card_number'],
				$classes['expdate'], $error['expdate'],
				$classes['card_code'], $error['card_code'],
				$classes['cctype'], $error['cctype']
			);
			break;

		case 'authorize' :
		case 'paypal_payflow' :
			$output = @sprintf( $gateway_checkout_form_fields[$gateway['internalname']], $classes['card_number'], $error['card_number'],
				$classes['expdate'], $error['expdate'],
				$classes['card_code'], $error['card_code']
			);
			break;

		case 'eway' :
		case 'bluepay' :
			$output = sprintf( $gateway_checkout_form_fields[$gateway['internalname']], $classes['card_number'], $error['card_number'],
				$classes['expdate'], $error['expdate']
			);
			break;
		case 'linkpoint' :
			$output = sprintf( $gateway_checkout_form_fields[$gateway['internalname']], $classes['card_number'], $error['card_number'],
				$classes['expdate'], $error['expdate']
			);
			break;

	}

	if ( isset( $output ) && ! empty( $output ) )
		return $output;
	elseif ( isset( $gateway_checkout_form_fields[$gateway['internalname']] ) )
		return $gateway_checkout_form_fields[$gateway['internalname']];
	return '';
}


add_filter( 'wpsc_gateway_count', '_wpsc_filter_merchant_v2_gateway_count' );

function _wpsc_filter_merchant_v2_gateway_count( $count ) {
	global $wpsc_gateway;
	$count += $wpsc_gateway->gateway_count;
	return $count;
}

add_filter(
	'wpsc_gateway_hidden_field_value',
	'_wpsc_filter_merchant_v2_gateway_hidden_field_value'
);

function _wpsc_filter_merchant_v2_gateway_hidden_field_value( $value ) {
	global $wpsc_gateway;
	if ( wpsc_have_gateways() ) {
		wpsc_the_gateway();
		$value = $wpsc_gateway->gateway['internalname'];
	}
	$wpsc_gateway->rewind_gateways();

	return $value;
}

add_action(
	'wpsc_after_gateway_hidden_field',
	'_wpsc_filter_merchant_v2_after_gateway_hidden_field'
);

function _wpsc_filter_merchant_v2_after_gateway_hidden_field() {
	if ( wpsc_have_gateways() ) {
		wpsc_the_gateway();
		if ( wpsc_gateway_form_fields() ) : ?>
			<table class='wpsc_checkout_table <?php echo wpsc_gateway_form_field_style();?>'>
				<?php echo wpsc_gateway_form_fields(); ?>
			</table>
		<?php endif;
	}
}

add_action(
	'wpsc_submit_checkout_gateway',
	'_wpsc_action_merchant_v2_submit_checkout',
	10,
	2
);

function _wpsc_action_merchant_v2_submit_checkout( $gateway, $purchase_log ) {
	global $wpsc_gateways;

	if ( empty( $wpsc_gateways[$gateway] ) )
		return;

	// submit to gateway
	$current_gateway_data = &$wpsc_gateways[$gateway];
	if ( isset( $current_gateway_data['api_version'] ) && $current_gateway_data['api_version'] >= 2.0 ) {
		$merchant_instance = new $current_gateway_data['class_name']( $purchase_log->get( 'id' ) );
		$merchant_instance->construct_value_array();
		do_action_ref_array( 'wpsc_pre_submit_gateway', array( &$merchant_instance ) );
		$merchant_instance->submit();
	} elseif ( ($current_gateway_data['internalname'] == $gateway) && ($current_gateway_data['internalname'] != 'google') ) {
		if ( get_option( 'permalink_structure' ) != '' )
			$separator = "?";
		else
			$separator = "&";
		$gateway_used = $current_gateway_data['internalname'];
		$purchase_log->set( 'gateway', $gateway_used );
		$purchase_log->save();
		$current_gateway_data['function']( $separator, $purchase_log->get( 'sessionid' ) );
	} elseif ( $current_gateway_data['internalname'] == 'google' ) {
		$gateway_used = $current_gateway_data['internalname'];
		$purchase_log->set( 'gateway', $gateway_used );
		wpsc_update_customer_meta( 'google_checkout', 'google' );
		wp_redirect(get_option( 'shopping_cart_url' ));
		exit;
	}
}
