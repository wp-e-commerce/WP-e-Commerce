<?php

class WPSC_Payment_Gateway_Manual extends WPSC_Payment_Gateway
{
	public static function get_title() {
		return __( 'Manual Payment Gateway 3.0', 'wpsc' );
	}
	
	public static function setup_form() {
		
	}
	
	public function __construct() {
		parent::__construct();
	}
}