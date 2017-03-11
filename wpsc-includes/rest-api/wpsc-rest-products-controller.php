<?php

class WPSC_REST_Products_Controller extends WP_REST_Posts_Controller {

	public function __construct( $post_type = 'wpsc-product' ) {
		parent::__construct( $post_type );
		$this->namespace = 'wpsc/v1';
	}
}
