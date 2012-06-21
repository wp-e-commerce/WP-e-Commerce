<?php

class WPSC_Page_Login extends WPSC_Page_SSL
{
	public function _callback_login() {
		if ( empty( $_COOKIE[TEST_COOKIE] ) )
			$this->message_collection->add( __( "Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to log in to your account.", 'wpsc' ), 'error' );

		$validation_rules = array(
			'username' => array(
				'title' => __( 'username', 'wpsc' ),
				'rules' => 'trim|sanitize_user|required',
			),
			'password' => array(
				'title' => __( 'password', 'wpsc' ),
				'rules' => 'trim|required',
			),
		);

		$validation = wpsc_validate_form( $validation_rules );
		if ( is_wp_error( $validation ) ) {
			$this->set_validation_errors( $validation );
			return;
		}

		$user = wp_signon( array(
			'user_login'    => $_POST['username'],
			'user_password' => $_POST['password'],
			'rememberme'    => ! empty( $_POST['rememberme'] ),
		) );

		if ( is_wp_error( $user ) ) {
			$this->message_collection->add( __( 'We do not recognize the login information you entered. Please try again.', 'wpsc' ), 'error' );
		}
	}

	public function __construct( $callback ) {
		if ( is_user_logged_in() ) {
			$redirect_to = wp_get_referer();
			if ( ! $redirect_to )
				$redirect_to = wpsc_get_catalog_url();

			wp_redirect( $redirect_to );
			exit;
		}

		parent::__construct( $callback, wpsc_get_login_url() );

		//Set a cookie now to see if they are supported by the browser.
		setcookie(TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN);
		if ( SITECOOKIEPATH != COOKIEPATH )
			setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
	}
}