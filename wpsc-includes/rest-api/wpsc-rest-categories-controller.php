<?php
class WPSC_REST_Categories_Controller extends WP_REST_Terms_Controller {
	public function __construct() {
		parent::__construct( 'wpsc_product_category' );
		$this->namespace = 'wpsc/v1';
	}
}
