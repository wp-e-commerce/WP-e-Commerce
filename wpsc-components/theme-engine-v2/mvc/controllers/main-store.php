<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/query-controller.php' );

class WPSC_Controller_Main_Store extends WPSC_Query_Controller {
	public function __construct() {
		parent::__construct();
		$this->title = wpsc_get_store_title();
	}

	public function index() {
		$this->title = wpsc_get_store_title();
		$this->view = 'main-store';
		wpsc_enqueue_script( 'wpsc-products' );
	}

	protected function get_native_template() {
		return locate_template( 'archive-wpsc-product.php' );
	}
}
