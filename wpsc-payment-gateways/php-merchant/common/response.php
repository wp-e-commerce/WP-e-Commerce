<?php

abstract class PHP_Merchant_Response
{
	protected $is_successful = false;
	protected $options = array();
	protected $errors = array();
	protected $response = '';
	
	public function __construct( $response_str ) {
		$this->response = $response_str;
	}
	
	public function is_successful() {
		return $this->is_successful;
	}
	
	public function get( $name ) {
		if ( ! isset( $this->options[$name] ) )
			return null;
		return $this->options[$name];
	}
	
	public function get_response_string() {
		return $this->response;
	}
	
	public function get_errors() {
		return $this->errors;
	}
	
	public function has_errors() {
		return ! empty( $this->errors );
	}
	
	public function get_error() {
		return empty( $this->errors ) ? false : $this->errors[0];
	}
}