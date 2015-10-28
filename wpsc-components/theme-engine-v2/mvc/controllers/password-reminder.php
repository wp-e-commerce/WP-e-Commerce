<?php

class WPSC_Controller_Password_Reminder extends WPSC_Controller{
	public function __construct() {
		if ( is_user_logged_in() ) {
			wp_redirect( wpsc_get_store_url() );
			exit;
		}

		$this->title = wpsc_get_password_reminder_title();

		parent::__construct();
	}

	public function index() {
		$this->view = 'password-reminder';

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'new_password' ) {
			$this->callback_new_password();
		}
	}

	private function callback_new_password() {
		global $wpdb;

		$form       = wpsc_get_password_reminder_form_args();
		$validation = wpsc_validate_form( $form );

		do_action( 'lostpassword_post' );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		extract( $_POST, EXTR_SKIP );

		$username = $_POST['username'];

		$field     = is_email( $username ) ? $field = 'email' : 'login';
		$user_data = get_user_by( $field, $username );

		if ( ! $user_data ) {
			$this->message_collection->add( __( 'Invalid username or email.', 'wp-e-commerce' ), 'error' );
			return;
		}

		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {
			wpsc_set_validation_errors( new WP_Error( 'username', __( 'Password reset is not allowed for this user', 'wp-e-commerce' ) ) );
		} else if ( is_wp_error( $allow ) ) {
			wpsc_set_validation_errors( $allow );
		}

		$key = $wpdb->get_var( $wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login ) );

		if ( empty( $key ) ) {
			// Generate something random for a key...
			$key = wp_generate_password( 20, false );
			do_action( 'retrieve_password_key', $user_login, $key );
			// Now insert the new md5 key into the db
			$wpdb->update( $wpdb->users, array('user_activation_key' => $key ), array( 'user_login' => $user_login ) );
		}
		$message  = __( 'Someone requested that the password be reset for the following account:', 'wp-e-commerce' ) . "\r\n\r\n";
		$message .= home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s', 'wp-e-commerce' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'wp-e-commerce' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'wp-e-commerce' ) . "\r\n\r\n";
		$message .= '<' . wpsc_get_password_reminder_url( "reset/{$user_login}/{$key}" ) . ">\r\n";

		if ( is_multisite() ) {
			$blogname = $GLOBALS['current_site']->site_name;
		} else {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		$title = sprintf( __( '[%s] Password Reset', 'wp-e-commerce' ), $blogname );

		$title   = apply_filters( 'wpsc_retrieve_password_title', $title );
		$message = apply_filters( 'wpsc_retrieve_password_message', $message, $key );

		if ( $message && ! wp_mail( $user_email, $title, $message ) ) {
			$this->message_collection->add( __( "Sorry, but due to an unexpected technical issue, we couldn't send you the e-mail containing password reset directions. Most likely the web host we're using has disabled e-mail features. Please contact us and we'll help you fix this. Or you can simply try again later.", 'wp-e-commerce' ), 'error' ); // by "us", we mean the site owner.
		}

		$this->message_collection->add( __( "We just sent you an e-mail containing directions to reset your password. If you don't receive it in a few minutes, check your Spam folder or simply try again.", 'wp-e-commerce' ), 'success' );
	}

	private function invalid_key_error() {
		return new WP_Error( 'invalid_key', __( 'The username and reset key combination in the URL are incorrect. Please make sure that you are using the correct URL specified in your Password Reset confirmation email.', 'wp-e-commerce' ) );
	}

	private function check_password_reset_key( $key, $login ) {
		global $wpdb;

		$key = preg_replace('/[^a-z0-9]/i', '', $key);

		if ( empty( $key ) || ! is_string( $key ) || empty( $login ) || ! is_string( $login ) ) {
			return $this->invalid_key_error();
		}

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login ) );

		if ( empty( $user ) ) {
			return $this->invalid_key_error();
		}

		return $user;
	}

	public function filter_fields_dont_match_message() {
		return __( 'The password fields do not match.', 'wp-e-commerce' );
	}

	public function reset_password( $user, $new_pass ) {
		do_action('password_reset', $user, $new_pass);
		wp_set_password( $new_pass, $user->ID );
		wp_password_change_notification( $user );
	}

	private function callback_reset_password( $user ) {
		$form = wpsc_get_password_reset_form_args();

		add_filter( 'wpsc_validation_rule_fields_dont_match_message', array( $this, 'filter_fields_dont_match_message' ) );
		$validation = wpsc_validate_form( $form );
		remove_filter( 'wpsc_validation_rule_fields_dont_match_message', array( $this, 'filter_fields_dont_match_message' ) );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		$this->reset_password( $user, $_POST['pass1'] );
		$message = apply_filters( 'wpsc_reset_password_success_message', __( 'Your password has been reset successfully. Please log in with the new password.', 'wp-e-commerce' ), $user );
		$this->message_collection->add( $message, 'success', 'main', 'flash' );

		wp_redirect( wpsc_get_login_url() );
		exit;
	}

	public function reset( $username = null, $key = null ) {
		if ( empty( $username ) || empty( $key ) ) {
			wp_redirect( wpsc_get_password_reminder_url() );
			exit;
		}
		$user = $this->check_password_reset_key( $key, $username );

		if ( is_wp_error( $user ) ) {
			wpsc_set_validation_errors( $user, 'check password reset key' );
			return $user;
		}

		$this->view = 'password-reminder-reset';

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'reset_password' ) {
			$this->callback_reset_password( $user );
		}

		return $user;
	}
}
