<?php

/**
 * Return the array containing arguments to generate the "Add to cart" form.
 *
 * The returned array can then be used with {@link wpsc_get_form_output()} to
 * generate the HTML output.
 *
 * @since  0.1
 * @uses   apply_filters() Applies 'wpsc_add_to_cart_button_icon' filter. Set the value to an empty value to disable the icon
 * @uses   apply_filters() Applies 'wpsc_add_to_cart_button_title' filter. The return value must be escaped already.
 * @uses   apply_filters() Applies 'wpsc_get_add_to_cart_form_args' filter. Use this filter to add more fields
 * @uses   wpsc_get_product_id() Get current product ID in the loop
 * @uses   wpsc_get_cart_url() Get cart URL for the form action
 * @uses   WPSC_Product::get_instance() get the WPSC_Product object to fetch variation sets and terms
 *
 * @return array     Form argument array
 */
function wpsc_get_add_to_cart_form_args( $id = null ) {
	if ( ! $id ) {
		$id = wpsc_get_product_id();
	}

	$product        = WPSC_Product::get_instance( $id );

	$args = array(
		// attributes of the form
		'class'   => 'wpsc-form wpsc-form-horizontal wpsc-add-to-cart-form',
		'action'  => wpsc_get_cart_url( "add/{$id}" ),
		'id'      => "wpsc-add-to-cart-form-{$id}",
		'data-id' => $id,

		// array containing form fields
		'fields' => array(
			// quantity field
			array(
				'name'  => 'quantity',
				'type'  => 'textfield',
				'title' => __( 'Quantity', 'wp-e-commerce' ),
				'value' => 1,
			),
		),
	);

	// generate the variation dropdown menus
	foreach ( $product->variation_sets as $variation_set_id => $title ) {
		$variation_terms = $product->variation_terms[ $variation_set_id ];
		$args['fields'][] = array(
			'name'    => "wpsc_product_variations[{$variation_set_id}]",
			'type'    => 'select',
			'options' => $variation_terms,
			'title'   => $title,
		);
	}

	// form action section contains the button and hidden values
    $args['form_actions'] = array(
        // Add to Cart button
        array(
            'type'         => 'button',
            'primary'      => true,
            'button_class' => 'wpsc-add-to-cart',
            'icon'         => apply_filters(
                'wpsc_add_to_cart_button_icon',
                array( 'shopping-cart', 'white' )
            ),
            'title'        => apply_filters(
                'wpsc_add_to_cart_button_title',
                __( 'Add to Cart', 'wp-e-commerce' )
			),
        ),

		// set the current page as the referer so that user can be redirected back
		array(
			'type'    => 'hidden',
			'name'    => '_wp_http_referer',
			'value'   => home_url( $_SERVER['REQUEST_URI'] ),
		),

		// nonce
		array(
			'type'    => 'hidden',
			'name'    => '_wp_nonce',
			'value'   => wp_create_nonce( "wpsc-add-to-cart-{$id}" ),
		),
	);

	$args = apply_filters( 'wpsc_get_add_to_cart_form_args', $args, $product, $id );

	return $args;
}

/**
 * Return the HTML output of the "Add to cart" form.
 *
 * @since  0.1
 * @uses apply_filters() Applies 'wpsc_get_add_to_cart_form()'. Use this filter to add or modify the HTML output of this function.
 * @uses wpsc_get_product_id() Get the current product ID in the loop
 * @uses wpsc_get_add_to_cart_form_args() Get the form arguments for this product
 * @param  int $id Optional. Product ID. Defaults to the current product ID in the loop.
 * @return string HTML output
 */
function wpsc_get_add_to_cart_form( $id = null ) {
	if ( ! $id ) {
		$id = wpsc_get_product_id();
	}

	// Enqueue Cart Notifications script.
	wpsc_enqueue_script( 'wpsc-cart-notifications' );

	$args = wpsc_get_add_to_cart_form_args( $id );

	return apply_filters( 'wpsc_get_add_to_cart_form', wpsc_get_form_output( $args ), $id );
}

/**
 * Echo the HTML output for the Add to cart form
 *
 * @since  0.1
 * @uses   wpsc_get_add_to_cart_form() Get the HTML for the form
 * @param  int $id Optional. Product ID. Defaults to the current product in the loop
 */
function wpsc_add_to_cart_form( $id = null ) {
	echo wpsc_get_add_to_cart_form( $id );
}

function wpsc_cart_item_table() {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-form.php' );
	$cart_item_table = WPSC_Cart_Item_Table_Form::get_instance();
	$cart_item_table->display();
}

function wpsc_get_login_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-login-form',
		'action' => wpsc_get_login_url(),
		'id'     => "wpsc-login-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-login-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username or Email', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'required',
			),
			array(
				'id'    => 'wpsc-login-password',
				'name'  => 'password',
				'type'  => 'password',
				'title' => __( 'Password', 'wp-e-commerce' ),
				'value' => '',
				'rules' => 'required',
				'description' => sprintf(
					'<a class="wpsc-lost-password-link" href="%1$s">%2$s</a>',
					esc_url( wpsc_get_password_reminder_url() ),
					__( 'Lost your password?', 'wp-e-commerce' )
				),
			),
			array(
				'id' => 'wpsc-login-remember',
				'name' => 'remember',
				'type' => 'checkbox',
				'title' => __( 'Remember Me', 'wp-e-commerce' ),
			),
		),
		'form_actions' => array(
			array(
				'type' => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_login_button_title', __( 'Log in', 'wp-e-commerce' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'login',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-log-in" ),
			),
		),
	);

	$args = apply_filters( 'wpsc_get_login_form_args', $args );

	return $args;
}

function wpsc_get_login_form() {
	$args = wpsc_get_login_form_args();
	return apply_filters( 'wpsc_get_login_form', wpsc_get_form_output( $args ) );
}

function wpsc_login_form() {
	echo wpsc_get_login_form();
}

function wpsc_get_register_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-register-form',
		'action' => wpsc_get_register_url(),
		'id'     => "wpsc-register-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-register-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'trim|required|username|sanitize_username',
			),
			array(
				'id'    => 'wpsc-register-email',
				'name'  => 'email',
				'description' => __( 'A password will be emailed to you', 'wp-e-commerce' ),
				'type'  => 'textfield',
				'title' => __( 'Email', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'email' ),
				'rules' => 'trim|required|account_email',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_register_button_title', __( 'Register', 'wp-e-commerce' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'register',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( 'wpsc-register' ),
			),
		),
	);

	$args = apply_filters( 'wpsc_get_register_form_args', $args );

	return $args;
}

function wpsc_get_register_form() {
	$args = wpsc_get_register_form_args();
	return apply_filters( 'wpsc_get_register_form', wpsc_get_form_output( $args ) );
}

function wpsc_register_form() {
	echo wpsc_get_register_form();
}

function wpsc_get_password_reminder_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-password-reminder-form',
		'action' => wpsc_get_password_reminder_url(),
		'id'     => "wpsc-password-reminder-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-password-reminder-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username or Email', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'trim|required|valid_username_or_email',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_password_reminder_button_title', __( 'Get New Password', 'wp-e-commerce' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'new_password',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( 'wpsc-password-reminder' ),
			),
		),
	);

	return apply_filters( 'wpsc_get_password_reminder_form_args', $args );
}

function wpsc_get_password_reminder_form() {
	$args = wpsc_get_password_reminder_form_args();
	return apply_filters( 'wpsc_get_password_reminder_form', wpsc_get_form_output( $args ) );
}

function wpsc_password_reminder_form() {
	echo wpsc_get_password_reminder_form();
}

function wpsc_get_password_reset_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-password-reminder-form',
		'action' => '',
		'id'     => "wpsc-password-reminder-form",
		'fields' => array(
			array(
				'name'  => 'pass1',
				'type'  => 'password',
				'title' => __( 'New password', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'pass1' ),
				'rules' => 'trim|required',
			),
			array(
				'name'  => 'pass2',
				'type'  => 'password',
				'title' => __( 'Confirm new password', 'wp-e-commerce' ),
				'value' => wpsc_submitted_value( 'pass2' ),
				'rules' => 'trim|required|matches[pass1]',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_password_reset_button_title', __( 'Reset Password', 'wp-e-commerce' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'reset_password',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-password-reset" ),
			),
		),
	);

	return $args;
}

function wpsc_get_password_reset_form() {
	$args = wpsc_get_password_reset_form_args();
	return apply_filters( 'wpsc_get_password_reset_form', wpsc_get_form_output( $args ) );
}

function wpsc_password_reset_form() {
	echo wpsc_get_password_reset_form();
}

function wpsc_checkout_form() {
	echo wpsc_get_checkout_form();
}

function wpsc_get_checkout_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-horizontal wpsc-checkout-form',
		'action' => '',
		'id'     => "wpsc-checkout-form",
		'inline_validation_errors' => true,
		'fields' => array(),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_checkout_form_button_title', __( 'Continue', 'wp-e-commerce' ) ),
				'name'    => 'wpsc_submit_checkout',
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'submit_checkout_form',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-checkout-form" ),
			),
		),
	);

	$args['fields'] = _wpsc_convert_checkout_form_fields();
	return apply_filters( 'wpsc_get_checkout_form_args', $args );
}

function _wpsc_convert_checkout_form_fields( $customer_settings = false, $purchase_log_id = 0 ) {
	$form   = WPSC_Checkout_Form::get();
	$fields = $form->get_fields();

	if ( empty( $fields ) ) {
		return array();
	}

	$args   = array();

	$purchase_log_exists = false;

	$fieldsets = array(
		'billing'  => array(
			'type'   => 'fieldset',
			'title'  => apply_filters( 'wpsc_checkout_billing_header_label', __( '<h2>Billing &amp; Shipping Details</h2>', 'wp-e-commerce' ) ),
			'id'     => 'wpsc-checkout-form-billing',
			'fields' => array()
		),
		'shipping' => array(
			'type'   => 'fieldset',
			'title'  => apply_filters( 'wpsc_checkout_shipping_header_label', __( '<h2>Shipping Details</h2>', 'wp-e-commerce' ) ),
			'id'     => 'wpsc-checkout-form-shipping',
			'fields' => array()
		),
	);

	if ( ! $customer_settings ) {

		$form_data_obj = null;

		if ( $purchase_log_id ) {

			if ( $purchase_log_id instanceof WPSC_Checkout_Form_Data ) {
				$id = $purchase_log_id->get_log_id();
				$form_data_obj = $purchase_log_id;
				$purchase_log_id = $id;
			} elseif ( is_numeric( $purchase_log_id ) ) {
				$purchase_log_id = absint( $purchase_log_id );
			}

		}

		if ( ! $purchase_log_id ) {
			$purchase_log_id = wpsc_get_customer_meta( 'current_purchase_log_id' );
		}

		$purchase_log_exists = (bool) $purchase_log_id;

		if ( $purchase_log_exists ) {
			$form_data       = array();

			if ( ! $form_data_obj ) {
				$form_data_obj = new WPSC_Checkout_Form_Data( $purchase_log_id );
			}

			$purchase_log_exists = $form_data_obj->exists();

			if ( $purchase_log_exists ) {
				$form_data = $form_data_obj->get_indexed_raw_data();
			}
		}
	}

	$i = 0;

	$state_country_pairs = array(
		'billing_state'  => array(),
		'shipping_state' => array(),
	);

	$customer_details = wpsc_get_customer_meta( 'checkout_details' );

	if ( ! is_array( $customer_details ) ) {
		$customer_details = array();
	}

	foreach ( $fields as $field ) {
		$id = empty( $field->unique_name ) ? $field->id : $field->unique_name;

		$is_shipping = false !== strpos( $field->unique_name, 'shipping' );
		$is_billing  = false !== strpos( $field->unique_name, 'billing' );

		$default_value = array_key_exists( $field->id, $customer_details )
			? $customer_details[ $field->id ]
			: '';

		/* Doing our college-best to check for one of the two original headings */
		if ( 'heading' == $field->type && ( 'delivertoafriend' == $field->unique_name || '1' === $field->id ) ) {
			continue;
		}

		if ( $purchase_log_exists && isset( $form_data[ $field->id ] ) ) {
			$default_value = $form_data[ $field->id ]->value;
		}

		if ( isset( $_POST['wpsc_checkout_details'] ) ) {
			$_POST['wpsc_checkout_details'] = wp_unslash( $_POST['wpsc_checkout_details'] );
		}

		$field_arr = array(
			'type'  => $field->type,
			'id'    => "wpsc-checkout-field-{$id}",
			'title' => esc_html( $field->name ),
			'name'  => 'wpsc_checkout_details[' . $field->id . ']',
			'value' => wpsc_submitted_value( $field->id, wp_unslash( $default_value ), $_POST['wpsc_checkout_details'] ),
		);

		$validation_rules = array( 'trim' );

		if ( $field->mandatory ) {
			$validation_rules[] = 'required';
		}

		$optional_state_field = false;

		if ( in_array( $field->unique_name, array( 'billingstate', 'shippingstate' ) ) ) {
			$field_arr['type'] = 'select_region';
			/* output states for all countries just in case Javascript doesn't work */
			$field_arr['country'] = 'all';

			if ( $field->unique_name == 'billingstate' ) {
				$state_country_pairs['billing_state']['key']  = $i;
			} else {
				$state_country_pairs['shipping_state']['key'] = $i;
			}

			// optional text field in case the country they select do not have states
			// and JS is disabled either by preferences or on error
			$optional_state_field = true;

			// convert state values in text into proper ID
			$validation_rules[] = '_wpsc_convert_state';
		} elseif ( in_array( $field->unique_name, array( 'billingcountry', 'shippingcountry' ) ) || $field->type == 'delivery_country' ) {

			$field_arr['type']  = 'select_country';
			$validation_rules[] = 'country';

			if ( $field->unique_name == 'billingcountry' ) {
				$state_country_pairs['billing_state']['country_field_id'] = $field->id;
			} else {
				$state_country_pairs['shipping_state']['country_field_id'] = $field->id;
			}

		} elseif ( $field->type == 'text' ) {
			$field_arr['type'] = 'textfield';
		} elseif ( $field->type == 'select' ) {
			$field_arr['options'] = array_flip( unserialize( $field->options ) );
		} elseif ( $field->type == 'radio' ) {
			$field_arr['type'] = 'radios';
			$field_arr['options'] = array_flip( unserialize( $field->options ) );
		} elseif ( $field->type == 'checkbox' ) {
			$field_arr['type'] = 'checkboxes';
			$field_arr['options'] = array_flip( unserialize( $field->options ) );
		} elseif ( in_array( $field->type, array( 'address', 'city', 'email' ) ) ) {
			$field_arr['type'] = 'textfield';
			if ( $field->type == 'email' ) {
				$validation_rules[] = 'email';
			}
		} elseif ( $field->type == 'heading' && $field->unique_name == 'delivertoafriend') {
			$field_arr['shipping_heading'] = true;
		}

		$field_arr['rules'] = implode( '|', $validation_rules );

		if ( $is_shipping ) {
			$fieldsets['shipping']['fields'][ $i ] = $field_arr;
		} else if ( $is_billing ) {
			$fieldsets['billing']['fields'][ $i ]  = $field_arr;
		} else {
			$args[ $i ] = $field_arr;
		}

		$i++;

		if ( $optional_state_field && $is_billing ) {
			$fieldsets['billing']['fields'][ $i ]         = $fieldsets['billing']['fields'][ $i - 1 ];
			$fieldsets['billing']['fields'][ $i ]['type'] = 'textfield';
			$fieldsets['billing']['fields'][ $i ]['id']   = 'wpsc-checkout-field-' . $id . '-text';
			$i++;
		} else if ( $optional_state_field && $is_shipping ) {
			$fieldsets['shipping']['fields'][ $i ]         = $fieldsets['shipping']['fields'][ $i - 1 ];
			$fieldsets['shipping']['fields'][ $i ]['type'] = 'textfield';
			$fieldsets['shipping']['fields'][ $i ]['id']   = 'wpsc-checkout-field-' . $id . '-text';
		}
	}

	if ( wpsc_has_tnc() && ! $customer_settings ) {
		$args[] = array(
			'type'  => 'checkbox',
			'id'    => 'wpsc-terms-and-conditions',
			'title' => sprintf(
				__( "I agree to the <a class='thickbox' target='_blank' href='%s' class='termsandconds'>Terms and Conditions</a>", 'wp-e-commerce'),
				esc_url( add_query_arg( array( 'termsandconds' => 'true', 'width' => 360, 'height' => 400 ) ) )
			),
			'value'   => 1,
			'name'    => 'wpsc_terms_conditions',
			'rules'   => 'required',
			'checked' => ( wpsc_submitted_value( 'wpsc_terms_conditions', 0 ) == 1 )
		);
	}

	foreach ( $state_country_pairs as $state => $field ) {
		$is_shipping = 'shipping_state' == $state;

		if ( isset( $field['key'] ) && $is_shipping ) {
			$fieldsets['shipping']['fields'][ $field['key'] ]['rules'] .= '|state_of[' . $field['country_field_id'] . ']';
			$fieldsets['shipping']['fields'][ $field['key'] ]['rules']  = ltrim( $fieldsets['shipping']['fields'][ $field['key'] ]['rules'], '|' );
		} else if ( isset( $field['key'] ) ) {
			$fieldsets['billing']['fields'][ $field['key'] ]['rules'] .= '|state_of[' . $field['country_field_id'] . ']';
			$fieldsets['billing']['fields'][ $field['key'] ]['rules']  = ltrim( $fieldsets['billing']['fields'][ $field['key'] ]['rules'], '|' );
		}
	}

	/* Add 'shipping same as billing' box to end of billing, rather than shipping header. */
	if ( ! empty( $fieldsets['billing']['fields'] ) && ! empty( $fieldsets['shipping']['fields'] ) ) {

		$checked = wpsc_get_customer_meta( 'wpsc_copy_billing_details' );
		$checked = empty( $checked ) || '1' == $checked;

		if ( $purchase_log_exists ) {
			// If we have a purchase log object, we need to compare the
			// shipping and billing values to see if they match.
			// If not, checked should be false.
			$checked = $form_data_obj->shipping_matches_billing();
		}

		$fieldsets['billing']['fields'][ $i++ ] = array(
			'type'  => 'checkbox',
			'id'    => 'wpsc-terms-and-conditions',
			'title' => apply_filters( 'wpsc_shipping_same_as_billing', __( 'Shipping address is same as billing', 'wp-e-commerce' ) ),
			'value'   => 1,
			'name'    => 'wpsc_copy_billing_details',
			'checked' => $checked,
		);
	}

	if ( empty( $fieldsets['billing']['fields'] ) ) {
		unset( $fieldsets['billing'] );
	}

	if ( empty( $fieldsets['shipping']['fields'] ) ) {
		unset( $fieldsets['shipping'] );
	}

	return $fieldsets + $args;
}

function wpsc_get_customer_settings_form_args( $purchase_log_id = 0 ) {
	$args = array(
		'inline_validation_errors' => true,
		'class'  => 'wpsc-form wpsc-form-horizontal wpsc-customer-settings-form',
		'action' => '',
		'id'     => 'wpsc-customer-settings-form',
		'fields' => array(),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_customer_settings_form_button_title', __( 'Save settings', 'wp-e-commerce' ) ),
				'name'    => 'wpsc_submit_customer_settings',
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'submit_customer_settings_form',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-customer-settings-form" ),
			),
		),
	);

	$args['fields'] = _wpsc_convert_checkout_form_fields( $purchase_log_id ? false : true, $purchase_log_id );
	return $args;
}

function wpsc_get_customer_settings_form( $purchase_log_id = 0 ) {
	$args = wpsc_get_customer_settings_form_args( $purchase_log_id );
	return apply_filters( 'wpsc_get_checkout_form', wpsc_get_form_output( $args ) );
}

function wpsc_customer_settings_form( $purchase_log_id = 0 ) {
	echo wpsc_get_customer_settings_form( $purchase_log_id );
}

function wpsc_get_checkout_form() {
	$args = wpsc_get_checkout_form_args();
	return apply_filters( 'wpsc_get_checkout_form', wpsc_get_form_output( $args ) );
}

function wpsc_get_checkout_shipping_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-horizontal wpsc-checkout-form-shipping-method',
		'action' => '',
		'id'     => "wpsc-checkout-form",
		'fields' => array(
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_checkout_shipping_method_form_button_title', __( 'Continue', 'wp-e-commerce' ) ),
				'name'    => 'wpsc_submit_checkout',
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'submit_shipping_method',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-checkout-form-shipping-method" ),
			),
		),
	);

	return apply_filters( 'wpsc_get_checkout_shipping_method_form_args', _wpsc_convert_checkout_shipping_form_args( $args ) );
}

function _wpsc_convert_checkout_shipping_form_args( $args ) {
	global $wpsc_shipping_modules;

	$calculator = WPSC_Shipping_Calculator::get_instance();
	$submitted_value = wpsc_submitted_value( 'wpsc_shipping_option' );
	$active_shipping_id = $calculator->active_shipping_id;

	foreach ( $calculator->sorted_quotes as $module_name => $quotes ) {
		$radios = array(
			'type'    => 'radios',
			'title'   => $wpsc_shipping_modules[$module_name]->name,
			'options' => array(),
			'name'    => 'wpsc_shipping_option',
		);

		foreach ( $quotes as $option => $cost ) {
			$id = $calculator->ids[ $module_name ][ $option ];
			$checked = empty( $submitted_value )
			           ? $active_shipping_id == $id
			           : $submitted_value == $id;
			$radios['options'][] = array(
				'title'       => $option,
				'value'       => $id,
				'description' => wpsc_format_currency( $cost ),
				'checked'     => $checked,
			);
		}

		$args['fields'][] = $radios;
	}

	// automatically select the cheapest option by default
	if ( empty( $active_shipping_id ) && empty( $submitted_value ) ) {
		$args['fields'][0]['options'][0]['checked'] = true;
	}

	return $args;
}

function wpsc_get_checkout_shipping_form() {
	if ( ! class_exists( 'WPSC_Shipping_Calculator' ) ) {
		return '';
	}

	$args = wpsc_get_checkout_shipping_form_args();
	return apply_filters( 'wpsc_get_checkout_shipping_form', wpsc_get_form_output( $args ) );
}

function wpsc_checkout_shipping_form() {
	echo wpsc_get_checkout_shipping_form();
}

function _wpsc_convert_checkout_payment_method_form_args( $args ) {
	$args['fields'] = apply_filters(
		'wpsc_payment_method_form_fields',
		$args['fields'],
		$args
	);
	return $args;
}

function wpsc_get_checkout_payment_method_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-checkout-form-payment-method',
		'action' => '',
		'id'     => "wpsc-checkout-form",
		'fields' => array(
		),
		'form_actions' => array(
			array(
				'id'	  => 'wpsc_submit_checkout',
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_checkout_payment_method_form_button_title', __( 'Place Your Order', 'wp-e-commerce' ) ),
				'name'    => 'wpsc_submit_checkout',
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'submit_payment_method',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-checkout-form-payment-method" ),
			),
		),
	);

	$args = apply_filters( 'wpsc_get_checkout_payment_method_form_args', _wpsc_convert_checkout_payment_method_form_args( $args ) );
	return $args;
}

function wpsc_get_checkout_payment_method_form() {
	$args = wpsc_get_checkout_payment_method_form_args();
	return apply_filters( 'wpsc_get_checkout_payment_method_form', wpsc_get_form_output( $args ) );
}

function wpsc_checkout_payment_method_form() {
	echo wpsc_get_checkout_payment_method_form();
}

/**
 * Return the HTML for "Begin Checkout" button
 *
 * @since  4.0
 *
 * @uses   apply_filters() Applies 'wpsc_begin_checkout_button_title'
 * @uses   apply_filters() Applies 'wpsc_begin_checkout_button_icon'
 * @uses   apply_filters() Applies 'wpsc_begin_checkout_button_args'
 * @uses   apply_filters() Applies 'wpsc_begin_checkout_button'
 *
 * @return string HTML Output
 */
function wpsc_get_begin_checkout_button() {
	$title = apply_filters(
		'wpsc_begin_checkout_button_title',
		__( 'Begin Checkout', 'wp-e-commerce' )
	);

	$icon = apply_filters(
		'wpsc_begin_checkout_button_icon',
		array( 'white', 'ok-sign' )
	);

	$args = apply_filters(
		'wpsc_begin_checkout_button_args',
		array(
			'class' => 'wpsc-button wpsc-button-primary wpsc-begin-checkout',
			'icon'  => $icon,
		)
	);

	$button = apply_filters(
		'wpsc_begin_checkout_button',
		wpsc_form_button(
			'',
			$title,
			$args,
			false
		)
	);

	return $button;
}

/**
 * Display the "Begin Checkout" button
 *
 * @since  0.1
 * @uses wpsc_get_begin_checkout_button()
 */
function wpsc_begin_checkout_button() {
	echo wpsc_get_begin_checkout_button();
}
