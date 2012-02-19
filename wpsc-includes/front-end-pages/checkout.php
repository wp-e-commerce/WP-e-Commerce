<?php

class WPSC_Front_End_Page_Checkout extends WPSC_Front_End_Page
{
	protected $template_name = 'wpsc-checkout';
	protected $current_step = '';

	public function __construct( $callback ) {
		global $wp_query;
		parent::__construct( $callback );
		$wp_query->wpsc_is_checkout = true;
	}

	public function get_current_step() {
		return $this->current_step;
	}
	public function main() {
		$this->current_step = 'details';
	}
}
function wpsc_get_current_checkout_step() {
	if (  ! wpsc_is_checkout() )
		return false;

	global $wpsc_page_instance;
	return $wpsc_page_instance->get_current_step();
}