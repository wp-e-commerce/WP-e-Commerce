<?php

abstract class PHP_Merchant_Response
{
	protected $is_successful = false;
	protected $options = array();
	protected $errors = array();
	
	abstract public function __construct( $params );
	
	public function is_successful() {
		return $this->is_successful;
	}
	
	public function get( $name ) {
		return $this->options[$name];
	}
	
	public function get_errors() {
		return $this->errors;
	}
	
	public function get_error() {
		return empty( $this->errors ) ? false : $this->errors[0];
	}
}