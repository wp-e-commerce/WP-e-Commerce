<?php
class WPSC_REST_Tags_Controller extends WP_REST_Terms_Controller {
	public function __construct() {
		parent::__construct( 'product_tag' );
		$this->namespace = 'wpsc/v1';
	}
}
