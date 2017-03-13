<?php
require_once( WPSC_TE_V2_CLASSES_PATH . '/query-controller.php' );

class WPSC_Controller_Category extends WPSC_Query_Controller {
	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->view = 'category';
		wpsc_enqueue_script( 'wpsc-products' );
		$this->title = get_queried_object()->name;
	}

	public function get_native_template() {
		$term = get_queried_object();
		return locate_template( array(
			"taxonomy-{$term->taxonomy}-{$term->slug}.php",
			"taxonomy-{$term->taxonomy}.php"
		) );
	}
}
