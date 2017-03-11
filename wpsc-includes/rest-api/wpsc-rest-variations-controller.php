<?php
class WPSC_REST_Variations_Controller extends WP_REST_Terms_Controller {

	public function __construct() {
		parent::__construct( 'wpsc-variation' );
		$this->namespace = 'wpsc/v1';
	}

	
}
