<?php

class WPSC_Controller_Login extends WPSC_Controller
{
	public function __construct() {
		if ( is_user_logged_in() ) {
			wp_redirect( wpsc_get_store_url() );
			exit;
		}

		parent::__construct();

		//Set a cookie now to see if they are supported by the browser.
		setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN );
		if ( SITECOOKIEPATH != COOKIEPATH ) {
			setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN );
		}

		$this->title = wpsc_get_login_title();
	}

	public function index() {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'login' ) {
			$this->callback_login();
		}

		$this->view = 'login';
	}

	private function callback_login() {
		if ( empty( $_COOKIE[ TEST_COOKIE ] ) ) {
			$this->message_collection->add( __( "Cookies are blocked or not supported by your browser. You must <a href='http://www.google.com/cookies.html'>enable cookies</a> to log in to your account.", 'wp-e-commerce' ), 'error' );
		}

		$form_args  = wpsc_get_login_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		$user = wp_signon( array(
			'user_login'    => $_POST['username'],
			'user_password' => $_POST['password'],
			'rememberme'    => ! empty( $_POST['rememberme'] ),
		) );

		if ( is_wp_error( $user ) ) {
			$this->message_collection->add( __( 'We do not recognize the login information you entered. Please try again.', 'wp-e-commerce' ), 'error' );
			return;
		}

		$redirect_to = wp_get_referer();

		if ( wpsc_get_customer_meta( 'checkout_after_login' ) ) {
			$redirect_to = wpsc_get_checkout_url();
			wpsc_delete_customer_meta( 'checkout_after_login' );
		}

		if ( ! $redirect_to || trim( str_replace( home_url(), '', $redirect_to ), '/' ) == trim( $_SERVER['REQUEST_URI'], '/' ) ) {
			$redirect_to = wpsc_get_store_url();
		}

		wp_redirect( $redirect_to );
		exit;
	}
}