<?php

class WPSC_Checkout_Form
{
	private static $instance;
	
	public static function &get_instance() {
		if ( ! self::$instance )
			self::$instance = new WPSC_Checkout_Form();
			
		return self::$instance;
	}
	
	private function __construct() {
		
	}
}