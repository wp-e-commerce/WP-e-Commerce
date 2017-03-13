<?php

class WPSC_Controller_Register extends WPSC_Controller {
	public function __construct() {
		if ( is_user_logged_in() ) {
			wp_redirect( wpsc_get_store_url() );
			exit;
		}

		parent::__construct();
		$this->title = wpsc_get_register_title();
	}

	public function index() {
		if ( isset( $_POST['action'] ) && 'register' === $_POST['action'] ) {
			$this->callback_register();
		}

		$this->view = 'register';
		wpsc_enqueue_script( 'wpsc-checkout' );
	}

	public function filter_fields_dont_match_message() {
		return __( 'The password fields do not match.', 'wp-e-commerce' );
	}

	private function callback_register() {
		$form_args  = wpsc_get_register_form_args();
		$validation = wpsc_validate_form( $form_args );

		if ( is_wp_error( $validation ) ) {
			wpsc_set_validation_errors( $validation );
			return;
		}

		return wpsc_register_customer( $_POST['username'], $_POST['password'], true );
	}

	private function send_registration_notification( $user_id, $username, $email, $password ) {
		return wpsc_send_registration_notifiction( $user_id, $username, $email, $password );
	}
}
