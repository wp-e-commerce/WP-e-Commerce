<?php

abstract class PHP_Merchant_HTTP
{
	public function __construct() {
		
	}
	
	abstract public function get( $url, $args = array() );
	abstract public function post( $url, $args = array() );
}