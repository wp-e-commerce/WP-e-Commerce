<?php
class WPSC_REST_Customers_Controller extends WP_REST_Users_Controller {
	public function __construct() {
		$this->namespace = 'wpsc/v1';
		$this->rest_base = 'customers';
	}

	public function register_routes() {

	}
}
