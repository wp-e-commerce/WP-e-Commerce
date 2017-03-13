<?php
class WPSC_REST_Reports_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'wpsc/v1';
		$this->rest_base = 'reports';
	}

	public function register_routes() {

	}
}
