<?php

class WPSC_Front_End_Page_Lost_Password extends WPSC_Front_End_Page_SSL
{
	protected $template_name = 'wpsc-lost-password';

	public function __construct( $callback ) {
		global $wp_query;
		parent::__construct( $callback, wpsc_get_lost_password_url() );
		$wp_query->wpsc_is_lost_password = true;
	}

	public function process_new_password() {
		global $wpdb;

		$validation_rules = array(
			'username' => array(
				'title' => __( 'username', 'wpsc' ),
				'rules' => 'trim|required|valid_username_or_email|allow_password_reset',
			),
		);

		$validation = wpsc_validate_form( $validation_rules );

		do_action('lostpassword_post');

		if ( is_wp_error( $validation ) ) {
			$this->set_validation_errors( $validation );
			return;
		}

		extract( $_POST, EXTR_SKIP );

		$field = strpos( $username, '@' ) ? $field = 'email' : 'login';
		$user_data = get_user_by( $field, $username );
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action('retrieve_password', $user_login);

		$allow = apply_filters('allow_password_reset', true, $user_data->ID);
		if ( ! $allow ) {
			$this->set_validation_errors( new WP_Error( 'username', __( 'Password reset is not allowed for this user', 'wpsc' ) ) );
		} else if ( is_wp_error( $allow ) ) {
			$this->set_validation_errors( $allow );
		}

		$key = $wpdb->get_var( $wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login ) );
		if ( empty( $key ) ) {
			// Generate something random for a key...
			$key = wp_generate_password( 20, false );
			do_action( 'retrieve_password_key', $user_login, $key );
			// Now insert the new md5 key into the db
			$wpdb->update ($wpdb->users, array('user_activation_key' => $key ), array( 'user_login' => $user_login ) );
		}
		$message = __( 'Someone requested that the password be reset for the following account:', 'wpsc' ) . "\r\n\r\n";
		$message .= home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s', 'wpsc' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'wpsc' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'wpsc' ) . "\r\n\r\n";
		$message .= '<' . wpsc_get_lost_password_url( "reset/{$user_login}/{$key}" ) . ">\r\n";

		if ( is_multisite() )
			$blogname = $GLOBALS['current_site']->site_name;
		else
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$title = sprintf( __( '[%s] Password Reset', 'wpsc' ), $blogname );

		$title = apply_filters( 'wpsc_retrieve_password_title', $title );
		$message = apply_filters( 'wpsc_retrieve_password_message', $message, $key );

		if ( $message && ! wp_mail( $user_email, $title, $message ) )
			$this->set_message( __( "Sorry, but due to an unexpected technical issue, we couldn't send you the e-mail containing password reset directions. Most likely the web host we're using have disabled e-mail features. Please contact us and we'll help you fix this. Or you can simply try again later.", 'wpsc' ), 'error', 'sending password reset email' ); // by "us", we mean the site owner.

		$this->set_message( __( "We just sent you an e-mail containing directions to reset your password. If you don't receive it in a few minutes, check your Spam folder or simply try again.", 'wpsc' ), 'success', 'sending password reset email' );
	}

	private function invalid_key_error() {
		return new WP_Error( 'invalid_key', __( 'The username and reset key combination in the URL are incorrect. Please make sure that you are using the correct URL specified in your Password Reset confirmation email.', 'wpsc' ) );
	}

	private function check_password_reset_key( $key, $login ) {
		global $wpdb;

		$key = preg_replace('/[^a-z0-9]/i', '', $key);

		if ( empty( $key ) || ! is_string( $key ) || empty( $login ) || ! is_string( $login ) )
			return $this->invalid_key_error();

		$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login ) );

		if ( empty( $user ) )
			return $this->invalid_key_error();

		return $user;
	}

	public function process_reset_password( $username = null, $key = null ) {
		$this->reset( $username, $key );

		if ( $this->has_validation_errors( 'check password reset key' ) )
			return;

		$validation_rules = array(
			'pass1' => array(
				'title' => __( 'new password', 'wpsc' ),
				'rules' => 'required',
			),
			'pass2' => array(
				'title' => __( 'confirm new password', 'wpsc' ),
				'rules' => 'required|matches[pass1]',
			),
		);

		$validation = wpsc_validate_form( $validation_rules );

		if ( is_wp_error( $validation ) ) {
			$this->set_validation_errors( $validation );
			return;
		}
	}

	public function reset( $username = null, $key = null ) {
		if ( empty( $username ) || empty( $key ) ) {
			wp_redirect( wpsc_get_lost_password_url() );
			exit;
		}
		$user = $this->check_password_reset_key( $key, $username );

		if ( is_wp_error( $user ) ) {
			$this->set_validation_errors( $user, 'check password reset key' );
			return;
		}
	}
}