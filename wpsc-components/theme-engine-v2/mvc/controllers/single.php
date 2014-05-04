<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/query-controller.php' );

class WPSC_Controller_Single extends WPSC_Query_Controller {
	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->title = get_queried_object()->post_title;
		$this->view = 'single';
	}

	public function get_native_template() {
		return locate_template( 'single-wpsc-product.php' );
	}
}