<?php

function wpsc_validate_form( $form_args, &$validated_array = false ) {
	if ( ! is_array( $validated_array ) ) {
		$validated_array = &$_POST;
	}

	$error = new WP_Error();
	$a     =& $error;

	if ( ! isset( $form_args['fields'] ) ) {
		$valid = null;
	} else {
		$valid = true;
	}

	$form = $form_args['fields'];

	foreach ( $form as $props ) {

		// Handle custom fields.
		if ( ! isset( $props['fields'] ) ) {
			$props['fields'] = $props;
		}

		foreach ( $props['fields'] as $prop ) {
			if ( empty( $prop['rules'] ) ) {
				continue;
			}

			$prop = _wpsc_populate_field_default_args( $prop );
			$field = $prop['name'];
			$rules = $prop['rules'];

			if ( is_string( $rules ) ) {
				$rules = explode( '|', $rules );
			}


			$value = wpsc_submitted_value( $field, '', $validated_array );

			foreach ( $rules as $rule ) {
				if ( function_exists( $rule ) ) {
					$value = call_user_func( $rule, $value );
					continue;
				}

				if ( preg_match( '/([^\[]+)\[([^\]]+)\]/', $rule, $matches ) ) {
					$rule          = $matches[1];
					$matched_field = $matches[2];
					$matched_value = wpsc_submitted_value( $matched_field, null, $validated_array );
					$matched_props = isset( $form[$matched_field] ) ? $form[$matched_field] : array();

					$error = apply_filters( "wpsc_validation_rule_{$rule}", $error, $value, $field, $prop, $matched_field, $matched_value, $matched_props );
				} else {
					$error = apply_filters( "wpsc_validation_rule_{$rule}", $error, $value, $field, $prop );
				}

				if ( count( $error->get_error_codes() ) ) {
					break;
				}
			}
		}

		_wpsc_set_submitted_value( $field, $value, $validated_array );
	}

	if ( count( $error->get_error_messages() ) ) {
		$valid = $error;
	}

	return apply_filters( 'wpsc_validate_form', $valid );
}

/**
 * This is messy.
 *
 * @param  [type] $name  [description]
 * @param  [type] $value [description]
 * @param  [type] $from  [description]
 * @return [type]        [description]
 */
function _wpsc_set_submitted_value( $name, $value, &$from = null ) {

	if ( ! is_array ( $from ) ) {
		$from =& $_REQUEST;
	}

	$i = strpos( $name, '[' );

	if ( $i !== false ) {
		$head = substr( $name, 0, $i );
		preg_match_all( '/\[([^\]]+)\]/', $name, $matches );
		$matches = $matches[1];
		array_unshift( $matches, $head );

		$val = &$from;

		foreach ( $matches as $token ) {
			if ( array_key_exists( $token, $val ) )
				$val = &$val[ $token ];
			else
				return;
		}
		return;
	}

	$from[ $name ] = $value;
}

function wpsc_validation_rule_required( $error, $value, $field, $props ) {
	if ( $value === '' ) {
		$error_message = apply_filters( 'wpsc_validation_rule_required_message', __( 'The %s field is empty.', 'wp-e-commerce' ), $value, $field, $props );
		$title = isset( $props['title_validation'] ) ? $props['title_validation'] : $field;
		$error->add( $field, sprintf( $error_message, $title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}

add_filter( 'wpsc_validation_rule_required', 'wpsc_validation_rule_required', 10, 4 );

function _wpsc_filter_terms_conditions_required_message( $message, $value, $field, $props ) {
	if ( $props['name'] == 'wpsc_terms_conditions' )
		$message = __( 'You are required to agree to our <a class="thickbox" target="_blank" href="%s" class="termsandconds">Terms and Conditions</a> in order to proceed with checkout.', 'wp-e-commerce' );

	return $message;
}

add_filter( 'wpsc_validation_rule_required_message', '_wpsc_filter_terms_conditions_required_message', 10, 4 );

function wpsc_validation_rule_email( $error, $value, $field, $props ) {
	$field_title = isset( $props['title_validation'] ) ? $props['title_validation'] : $field;

	if ( empty( $value ) ) {
		return $error;
	}

	if ( ! is_email( $value ) ) {
		$message = apply_filters( 'wpsc_validation_rule_invalid_email_message', __( 'The %s field contains an invalid email address.', 'wp-e-commerce' ) );
		$error->add( $field, sprintf( $message, $field_title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_email', 'wpsc_validation_rule_email', 10, 4 );

function wpsc_validation_rule_valid_username_or_email( $error, $value, $field, $props ) {
	if ( strpos( $value, '@' ) ) {
		$user = get_user_by( 'email', $value );
		if ( empty( $user ) ) {
			$message = apply_filters( 'wpsc_validation_rule_account_email_not_found_message', __( 'There is no user registered with that email address.', 'wp-e-commerce' ), $value, $field, $props );
			$error->add( $field, $message, array( 'value' => $value, 'props' => $props) );
		}
	} else {
		$user = get_user_by( 'login', $value );
		if ( empty( $user ) ) {
			$message = apply_filters( 'wpsc_validation_rule_username_not_found_message', __( 'There is no user registered with that username.', 'wp-e-commerce' ), $value, $field, $props );
			$error->add( $field, $message, array( 'value' => $value, 'props' => $props ) );
		}
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_valid_username_or_email', 'wpsc_validation_rule_valid_username_or_email', 10, 4 );

function wpsc_validation_rule_matches( $error, $value, $field, $props, $matched_field, $matched_value, $matched_props ) {
	if ( is_null( $matched_value ) || $value != $matched_value ) {
		$message = apply_filters( 'wpsc_validation_rule_fields_dont_match_message', __( 'The %s and %s fields do not match.', 'wp-e-commerce' ), $value, $field, $props, $matched_field, $matched_value, $matched_props );
		$title = isset( $props['title_validation'] ) ? $props['title_validation'] : $field;
		$matched_title = isset( $matched_props['title_validation'] ) ? $matched_props['title_validation'] : $field;
		$error->add( $field, sprintf( $message, $title, $matched_title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_matches', 'wpsc_validation_rule_matches', 10, 7 );

function wpsc_validation_rule_username( $error, $value, $field, $props ) {
	$field_title = isset( $props['title_validation'] ) ? $props['title_validation'] : $field;

	if ( ! validate_username( $value ) ) {
		$message = apply_filters( 'wpsc_validation_rule_invalid_username_message', __( 'This %s contains invalid characters. Username may contain letters (a-z), numbers (0-9), dashes (-), underscores (_) and periods (.).', 'wp-e-commerce' ) );
		$error->add( $field, sprintf( $message, $field_title ), array( 'value' => $value, 'props' => $props ) );
	} elseif ( username_exists( $value ) ) {
		$message = apply_filters( 'wpsc_validation_rule_username_not_available_message', _x( 'This %s is already used by another account. Please choose another one.', 'username not available', 'wp-e-commerce' ) );
		$error->add( $field, sprintf( $message, $field_title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_username', 'wpsc_validation_rule_username', 10, 4 );

function wpsc_validation_rule_account_email( $error, $value, $field, $props ) {
	$field_title = isset( $props['title_validation'] ) ? $props['title_validation'] : $field;

	if ( ! is_email( $value ) ) {
		$message = apply_filters( 'wpsc_validation_rule_invalid_account_email_message', __( 'The %s is not valid.', 'wp-e-commerce' ) );
		$error->add( $field, sprintf( $message, $field_title ), array( 'value' => $value, 'props' => $props ) );
	} elseif ( email_exists( $value ) ) {
		$message = apply_filters( 'wpsc_validation_rule_account_email_not_available_message', _x( 'This %s is already used by another account. Please choose another one.', 'email not available', 'wp-e-commerce' ) );
		$error->add( $field, sprintf( $message, $field_title ), array( 'value' => $value, 'props' => $props ) );
	}

	return $error;
}
add_filter( 'wpsc_validation_rule_account_email', 'wpsc_validation_rule_account_email', 10, 4 );

function _wpsc_filter_validation_rule_state_of( $error, $value, $field, $props, $matched_field, $matched_value, $matched_props ) {
	global $wpdb;

	if ( $value == '' ) {
		return $error;
	}

	$country_code = $_POST['wpsc_checkout_details'][ $matched_field ];
	$country      = new WPSC_Country( $country_code );

	if ( ! $country->has_regions() ) {
		return $error;
	}

	// state should have been converted into a numeric value already
	// if not, it's an invalid state
	if ( ! is_numeric( $value ) ) {
		$message = apply_filters(
			'wpsc_validation_rule_invalid_state_message',
			/* translators: %1$s is state, %2$s is country */
			__( '%1$s is not a valid state or province in %2$s', 'wp-e-commerce' )
		);
		$message = sprintf( $message, $value, $country->get_name() );
		$error->add(
			$field,
			$message,
			array(
				'value' => $value,
				'props' => $props,
			)
		);

		return $error;
	}

	$sql   = $wpdb->prepare('SELECT COUNT(id) FROM ' . WPSC_TABLE_REGION_TAX . ' WHERE id = %d', $value );
	$count = $wpdb->get_var( $sql );

	if ( $count == 0 ) {
		$message = apply_filters(
			'wpsc_validation_rule_invalid_state_id_message',
			__( 'You specified or were assigned an invalid state or province. Please contact administrator for assistance', 'wp-e-commerce' )
		);
		$error->add(
			$field,
			$message,
			array(
				'value' => $value,
				'props' => $props,
			)
		);
	}

	return $error;
}

add_filter( 'wpsc_validation_rule_state_of', '_wpsc_filter_validation_rule_state_of', 10, 7 );

function _wpsc_convert_state( $state ) {
	global $wpdb;

	if ( is_numeric( $state ) ) {
		return (int) $state;
	}

	if ( strlen( $state ) == 2 ) {
		$where = 'code = %s';
	} else {
		$where = 'name = %s';
	}

	$sql = $wpdb->prepare( 'SELECT id FROM ' . WPSC_TABLE_REGION_TAX . ' WHERE ' . $where, $state );
	$val = $wpdb->get_var( $sql );

	if ( $val ) {
		$state = (int) $val;
	}

	return $state;
}
