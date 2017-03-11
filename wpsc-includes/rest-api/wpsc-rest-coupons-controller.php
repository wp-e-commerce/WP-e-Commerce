<?php
class WPSC_REST_Coupons_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'wpsc/v1';
		$this->rest_base = 'coupons';
	}

	public function register_routes() {

	}
}
