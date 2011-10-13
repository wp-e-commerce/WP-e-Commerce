<?php

abstract class PHP_Merchant_HTTP
{
	public function __construct() {
		
	}
	
	abstract public function get();
	abstract public function post();
}