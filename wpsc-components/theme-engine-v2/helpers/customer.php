<?php

function wpsc_save_customer_details( $customer_details ) {
	$customer_details = apply_filters( 'wpsc_update_customer_checkout_details', $customer_details );

	// legacy filter
	if ( is_user_logged_in() ) {
		$customer_details = apply_filters( 'wpsc_checkout_user_profile_update', $customer_details, get_current_user_id() );
	}

	wpsc_update_customer_meta( 'checkout_details', wp_unslash( $customer_details ) );
}

function _wpsc_copy_billing_details() {
	$form   = WPSC_Checkout_Form::get();
	$fields = $form->get_fields();
	$fields_to_copy = array(
		'firstname',
		'lastname',
		'address',
		'city',
		'state',
		'country',
		'postcode',
	);

	$field_ids = array(
		'shipping' => array(),
		'billing'  => array()
	);

	foreach ( $fields as $field ) {
		if ( ! empty( $field->unique_name )
			&& preg_match( '/^(billing|shipping)(.+)/', $field->unique_name, $matches )
			&& in_array( $matches[2], $fields_to_copy )
		) {
			$field_ids[ $matches[1] ][ $matches[2] ] = $field->id;
		}
	}

	$post_data =& $_POST['wpsc_checkout_details'];

	foreach ( $field_ids['shipping'] as $name => $id ) {
		$billing_field_id = $field_ids['billing'][ $name ];
		$post_data[ $id ] = $post_data[ $billing_field_id ];
	}

	wpsc_update_customer_meta( 'wpsc_copy_billing_details', '1' );
}

function _wpsc_update_location() {
	global $wpsc_cart;

	$wpsc_cart->update_location();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->get_shipping_option();
}

/**
 * Register a customer to the store website.
 *
 * @since  4.0
 *
 * @param  string  $username Username
 * @param  string  $email    Email address
 * @param  boolean $redirect Whether or not to redirect to the login page.
 *
 * @return mixed Null if redirected or errors are present, User ID if created successfully.
 */
function wpsc_register_customer( $username = '', $email = '', $redirect = true ) {

	$errors = new WP_Error();

	do_action( 'register_post', $username, $email, $errors );

	$errors = apply_filters( 'registration_errors', $errors, $username, $email );

	if ( $errors->get_error_code() ) {
		wpsc_set_validation_error( $errors );
		return;
	}

	$password = wp_generate_password( 12, false );

	$user_id  = wp_insert_user(
		apply_filters( 'wpsc_register_customer_args',
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email
			)
		)
	);

	$message_collection = WPSC_Message_Collection::get_instance();

	if ( is_wp_error( $user_id ) ) {
		foreach ( $user_id->get_error_messages() as $message ) {
			$message_collection->add( $message, 'error' );
		}
		return;
	}

	if ( ! $user_id ) {
		$message = apply_filters( 'wpsc_register_unknown_error_message', __( 'Sorry, but we could not process your registration information. Please <a href="mailto:%s">contact us</a>, or try again later.', 'wp-e-commerce' ) );
		$message_collection->add( sprintf( $message, get_option( 'admin_email' ), 'error' ) );
		return;
	}

	update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.

	$notification = wpsc_send_registration_notification( $user_id, $username, $email, $password );

	if ( ! $notification ) {
		$message = apply_filters( 'wpsc_register_email_did_not_send', __( 'We were able to create your account, but our server was unable to send an email to you. Please <a href="mailto:%s">contact us</a>.', 'wp-e-commerce' ) );
		$message_collection->add( sprintf( $message, get_option( 'admin_email' ), 'error' ) );
		return;
	} else {
		$message_collection->add( __( 'We just sent you an email containing your generated password. Just follow the directions in that email to complete your registration.', 'wp-e-commerce' ), 'success', 'main', 'flash' );
	}

	if ( $redirect ) {
		wp_redirect( wpsc_get_login_url() );
		exit;
	} else {
		return $user_id;
	}
}

/**
 * Sends customer registration notification email.
 *
 * @since  4.0
 *
 * @param  int    $user_id  User ID
 * @param  string $username Username
 * @param  string $email    Email
 * @param  string $password Password
 *
 * @return bool             Whether the notification was sent successfully.
 */
function wpsc_send_registration_notification( $user_id, $username, $email, $password ) {
	wp_new_user_notification( $user_id );

	$username = stripslashes( $username );
	$password = stripslashes( $password );
	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	$title = apply_filters( 'wpsc_registration_notification_title', __( '[%s] Thank you for registering', 'wp-e-commerce' ) );
	$title = sprintf( $title, $blogname );
	$message = sprintf( __( 'Welcome, %s.', 'wp-e-commerce' ), $username ) . "\r\n\r\n";
	$message .= __( "Thank you for registering with us. Your account has been created:", 'wp-e-commerce' ) . "\r\n\r\n";
	$message .= sprintf( __( 'Username: %s', 'wp-e-commerce' ), $username ) . "\r\n\r\n";
	$message .= sprintf( __( 'Password: %s', 'wp-e-commerce' ), $password ) . "\r\n\r\n";
	$message .= __( "Here's a list of things you can do to get started:", 'wp-e-commerce' ) . "\r\n\r\n";
	$message .= sprintf( __( '1. Log in with your new account details <%s>', 'wp-e-commerce' ), wpsc_get_login_url() ) . "\r\n\r\n";
	$message .= sprintf( __( '2. Build your customer profile, and probably change your password to something easier to remember <%s>', 'wp-e-commerce' ), wpsc_get_customer_account_url() ) . "\r\n\r\n";
	$message .= sprintf( __( '3. Explore our shop! <%s>', 'wp-e-commerce' ), wpsc_get_store_url() ) . "\r\n\r\n";
	$message = apply_filters( 'wpsc_registration_notification_body', $message );

	return wp_mail( $email, $title, $message );
}