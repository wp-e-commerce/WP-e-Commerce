<?php
/**
 * nzshpcrt_get_gateways()
 *
 * Deprecated function for returning the merchants global
 *
 * @global array $nzshpcrt_gateways
 * @return array
 */
function nzshpcrt_get_gateways() {

	global $nzshpcrt_gateways;

	if ( !is_array( $nzshpcrt_gateways ) )
		wpsc_core_load_gateways();

	return $nzshpcrt_gateways;

}


/**
 * The WPSC Gateway functions
 */

/**
 * @todo Needs desription adding, and return value clarifying.
 * @return bool [description]
 */
function wpsc_have_gateways() {
	global $wpsc_gateway;
	return $wpsc_gateway->have_gateways();
}

/**
 * @todo Needs desription adding, and return value clarifying.
 * @return [type] [description]
 */
function wpsc_the_gateway() {
	global $wpsc_gateway;
	return $wpsc_gateway->the_gateway();
}

/**
 * Indicate if the current gateway has an image provided.
 * @return bool True if the gateway has an image, false if not.
 */
function wpsc_show_gateway_image(){
	global $wpsc_gateway;
	if( isset($wpsc_gateway->gateway['image']) && !empty($wpsc_gateway->gateway['image']) )
		return true;
	else
		return false;
}

/**
 * Return the current gateway's image url if there is one.
 * @return string|bool Returns the URL of the gateway image, or false if none set.
 */
function wpsc_gateway_image_url(){
	global $wpsc_gateway;
	if( wpsc_show_gateway_image() )
		return $wpsc_gateway->gateway['image'];
	else
		return false;
}

/**
 * Return the current gateway's name.
 * 
 * @return  string  The current gateway's name.
 *
 * @uses  $wpsc_gateway              Global array of gateways.
 * @uses  wpsc_show_gateway_image()  Checks if gateway has an image.
 * @uses  apply_filters()            Calls 'wpsc_gateway_name'.
 */
function wpsc_gateway_name() {
	global $wpsc_gateway;
	$display_name = '';

	$payment_gateway_names = get_option( 'payment_gateway_names' );

	// Use gateway internal name if set
	if ( isset( $payment_gateway_names[ $wpsc_gateway->gateway['internalname'] ] ) && ( $payment_gateway_names[ $wpsc_gateway->gateway['internalname'] ] != '' || wpsc_show_gateway_image() ) ) {
		$display_name = $payment_gateway_names[ $wpsc_gateway->gateway['internalname'] ];
	}

	$display_name = apply_filters( 'wpsc_gateway_name', $display_name, $wpsc_gateway->gateway );

	// If no display name or image, use default
	if ( $display_name == '' && ! wpsc_show_gateway_image() ) {
		$display_name = __( 'Credit Card', 'wpsc' );
	}

	return $display_name;
}

/**
 * WPSC Default Gateway Name Filter
 *
 * This filter overrides the display name of a gateway
 *
 * @param   string  $display_name  Gateway display name.
 * @param   array   $gateway       Gateway details.
 * @return  string                 Filtered gateway name.
 *
 * @uses  wpsc_show_gateway_image()  Checks if gateway has an image.
 */
function _wpsc_gateway_name_filter( $display_name, $gateway ) {
	if ( empty( $display_name ) && isset( $gateway['payment_type'] ) && ! wpsc_show_gateway_image() ) {
		switch ( $gateway['payment_type'] ) {
			case 'paypal':
			case 'paypal_pro':
			case 'wpsc_merchant_paypal_pro';
				$display_name = __( 'PayPal', 'wpsc' );
				break;

			case 'manual_payment':
				$display_name =  __( 'Manual Payment', 'wpsc' );
				break;

			case 'credit_card':
			default:
				$display_name = __( 'Credit Card', 'wpsc' );
				break;
		}
	}
	return $display_name;
}

add_filter( 'wpsc_gateway_name', '_wpsc_gateway_name_filter', 10, 2 );

/**
 * Return the current gateway's internal name
 * @return string The current gateway's internal name.
 */
function wpsc_gateway_internal_name() {
	global $wpsc_gateway;
	return $wpsc_gateway->gateway['internalname'];
}

/**
 * Return HTML to check a radio button if the current gateway is the currently selected gateway.
 * @return string HTML checked attribute if the current gateway is selectedd. Empty string otherwise.
 */
function wpsc_gateway_is_checked() {
	global $wpsc_gateway;
	$is_checked = false;
	$selected_gateway = wpsc_get_customer_meta( 'selected_gateway' );

	if ( $selected_gateway ) {
		if ( $wpsc_gateway->gateway['internalname'] == $selected_gateway ) {
			$is_checked = true;
		}
	} else {
		if ( $wpsc_gateway->current_gateway == 0 ) {
			$is_checked = true;
		}
	}
	if ( $is_checked == true ) {
		$output = 'checked="checked"';
	} else {
		$output = '';
	}
	return $output;
}

/**
 * Return the HTML output for the current gateway's checkout fields.
 * @return string The HTML of the current gateway's checkout fields.
 * @uses apply_filters() Filters output through wpsc_gateway_checkout_form_{gateway_internal_name}
 */
function wpsc_gateway_form_fields() {
	global $wpsc_gateway, $gateway_checkout_form_fields, $wpsc_gateway_error_messages;

	$messages = is_array( $wpsc_gateway_error_messages ) ? $wpsc_gateway_error_messages : array();

	$error = array(
		'card_number' => empty( $messages['card_number'] ) ? '' : $messages['card_number'],
		'expdate' => empty( $messages['expdate'] ) ? '' : $messages['expdate'],
		'card_code' => empty( $messages['card_code'] ) ? '' : $messages['card_code'],
		'cctype' => empty( $messages['cctype'] ) ? '' : $messages['cctype'],
	);

	$output = '';

	// Match fields to gateway
	switch ( $wpsc_gateway->gateway['internalname'] ) {

		case 'paypal_pro' : // legacy
		case 'wpsc_merchant_paypal_pro' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate'],
				wpsc_the_checkout_CCcvv_validation_class(), $error['card_code'],
				wpsc_the_checkout_CCtype_validation_class(), $error['cctype']
			);
			break;

		case 'authorize' :
		case 'paypal_payflow' :
			$output = @sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate'],
				wpsc_the_checkout_CCcvv_validation_class(), $error['card_code']
			);
			break;

		case 'eway' :
		case 'bluepay' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate']
			);
			break;
		case 'linkpoint' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate']
			);
			break;

	}

	if ( empty( $output ) && isset( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']] ) ) {
		$output = $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']];
	}

	return apply_filters ( 'wpsc_gateway_checkout_form_' . $wpsc_gateway->gateway['internalname'], $output );

}

/**
 * Return HTML class to be added to a checkout field to indicate if thi field belongs to the
 * currently selected gateway.
 * @return string HTML class
 */
function wpsc_gateway_form_field_style() {
	global $wpsc_gateway;
	$is_checked = false;
	$selected_gateway = wpsc_get_customer_meta( 'selected_gateway' );
	if ( $selected_gateway ) {
		if ( $wpsc_gateway->gateway['internalname'] == $selected_gateway ) {
			$is_checked = true;
		}
	} else {
		if ( $wpsc_gateway->current_gateway == 0 ) {
			$is_checked = true;
		}
	}
	if ( $is_checked == true ) {
		$output = 'checkout_forms';
	} else {
		$output = 'checkout_forms_hidden';
	}
	return $output;
}

add_action(
	'wpsc_before_shopping_cart_page',
	'_wpsc_merchant_v2_before_shopping_cart'
);

/**
 * @todo Clarify what this is doing
 */
function _wpsc_merchant_v2_before_shopping_cart() {
	$GLOBALS['wpsc_gateway'] = new wpsc_gateways();
}

add_filter(
	'_wpsc_merchant_v2_validate_payment_method',
	'_wpsc_action_merchant_v2_validate_payment_method',
	10,
	2
);

function _wpsc_action_merchant_v2_validate_payment_method( $valid, $controller ) {
	$fields = array(
		'card_number',
		'card_number1',
		'card_number2',
		'card_number3',
		'card_number4',
		'card_code',
		'cctype',
	);

	$selected_gateway = $_POST['wpsc_payment_method'];
	if (
		   ! isset( $_POST['extra_form'] )
		|| ! isset( $_POST['extra_form'][$selected_gateway] )
	)
		return $valid;

	$extra = $_POST['extra_form'][$selected_gateway];
	$card_number_error = false;
	$messages = array();
	foreach ( $fields as $field ) {
		if ( isset( $extra[$field] ) && trim( $extra[$field] ) == '' ) {
			switch ( $field ) {
				case 'card_number':
				case 'card_number1':
				case 'card_number2':
				case 'card_number3':
				case 'card_number4':
					if ( $card_number_error )
						continue;

					$messages['card_number'] = __( 'Please enter a valid credit card number', 'wpsc' );
					$card_number_error = true;
					break;
				case 'card_code':
					$messages[$field] = __( 'Please enter a valid CVV', 'wpsc' );
					break;
				case 'cctype':
					$messages[$field] = __( 'Please select a valid credit card type', 'wpsc' );
					break;
			}
		}
	}

	if ( ! empty( $extra['expiry'] ) )
		foreach ( array( 'month', 'year' ) as $element ) {
			if (
				   empty( $extra['expiry'][$element] )
				|| ! is_numeric( $extra['expiry'][$element] )
			) {
				$messages['expdate'] = __( 'Please specify a valid expiration date.', 'wpsc' );
				break;
			}
		}

	if ( ! empty( $messages ) ) {
		foreach ( $messages as $field => $message ) {
			$controller->message_collection->add( $message, 'validation' );
		}
		$GLOBALS['wpsc_gateway_error_messages'] = $messages;
		return false;
	}

	foreach ( $extra as $key => $value ) {
		$_POST[$key] = $value;
	}
	return true;
}