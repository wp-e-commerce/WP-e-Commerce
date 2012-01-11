<?php

class WPSC_Front_End_Page_Checkout extends WPSC_Front_End_Page
{
	protected $template_name = 'wpsc-checkout';

	public function __construct( $callback ) {
		global $wp_query;
		parent::__construct( $callback );
		$wp_query->wpsc_is_checkout = true;
	}
}