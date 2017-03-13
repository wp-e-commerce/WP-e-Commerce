<?php
class WPSC_REST_Product_Files_Controller extends WP_REST_Posts_Controller {
	public function __construct( $post_type = 'wpsc-product-file' ) {
		parent::__construct( $post_type );
	}
}
