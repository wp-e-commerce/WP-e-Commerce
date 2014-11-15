<?php

class WPSC_Controller_Register extends WPSC_Controller
{
	public function __construct() {
		if ( is_user_logged_in() ) {
			wp_redirect( wpsc_get_store_url() );
			exit;
		}

		parent::__construct();
		$this->title = wpsc_get_register_title();
	}

	public function index() {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'register' ) {
			$this->callback_register();
		}

		$this->view = 'register';
	}

	public function filter_fields_dont_match_message() {
		return __( 'The password fields do not match.', 'wpsc' );
	}

	private function callback_register() {
		$form_args  = wpsc_get_register_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		extract( $_POST, EXTR_SKIP );
		$errors = new WP_Error();

		do_action( 'register_post', $username, $email, $errors );
		$errors = apply_filters( 'registration_errors', $errors, $username, $email );

		if ( $errors->get_error_code() ) {
			wpsc_set_validation_error( $errors );
			return;
		}

		$password = wp_generate_password( 12, false );
		$user_id  = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			foreach ( $user_id->get_error_messages() as $message ) {
				$this->message_collection->add( $message, 'error' );
			}
			return;
		}

		if ( ! $user_id ) {
			$message = apply_filters( 'wpsc_register_unknown_error_message', __( 'Sorry, but we could not process your registration information. Please <a href="mailto:%s">contact us</a>, or try again later.', 'wpsc' ) );
			$this->message_collection->add( sprintf( $message, get_option( 'admin_email' ), 'error' ) );
			return;
		}

		update_user_option( $user_id, 'default_password_nag', true, true ); //Set up the Password change nag.
		$this->send_registration_notification( $user_id, $username, $email, $password );
		$this->message_collection->add( __( 'We just sent you an e-mail containing your generated password. Just follow the directions in that e-mail to complete your registration.', 'wpsc' ), 'success', 'main', 'flash' );

		wp_redirect( wpsc_get_login_url() );
		exit;
	}

	private function send_registration_notification( $user_id, $username, $email, $password ) {
		wp_new_user_notification( $user_id );

		$username = stripslashes( $username );
		$password = stripslashes( $password );
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$title = apply_filters( 'wpsc_registration_notification_title', __( '[%s] Thank you for registering', 'wpsc' ) );
		$title = sprintf( $title, $blogname );
		$message = sprintf( __( 'Welcome, %s.', 'wpsc' ), $username ) . "\r\n\r\n";
		$message .= __( "Thank you for registering with us. Your account has been created:", 'wpsc' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s', 'wpsc' ), $username ) . "\r\n\r\n";
		$message .= sprintf( __( 'Password: %s', 'wpsc' ), $password ) . "\r\n\r\n";
		$message .= __( "Here's a list of things you can do to get started:", 'wpsc' ) . "\r\n\r\n";
		$message .= sprintf( __( '1. Log in with your new account details <%s>', 'wpsc' ), wpsc_get_login_url() ) . "\r\n\r\n";
		$message .= sprintf( __( '2. Build your customer profile, and probably change your password to something easier to remember <%s>', 'wpsc' ), wpsc_get_customer_account_url() ) . "\r\n\r\n";
		$message .= sprintf( __( '3. Explore our shop! <%s>', 'wpsc' ), wpsc_get_store_url() ) . "\r\n\r\n";
		$message = apply_filters( 'wpsc_registration_notification_body', $message );

		wp_mail( $email, $title, $message );
	}
}