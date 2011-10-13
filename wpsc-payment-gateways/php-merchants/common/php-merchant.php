<?php
require_once( 'exception.php' );

abstract class PHP_Merchant
{
	private $options = array(
		'currency' => 'USD',
	);
	
	public function __construct() {
		
	}
	
	public function purchase( $amt, $options = array() ) {
		
	}
	
	public function authorize() {
		throw new PHP_Merchant_Exception( 1, 'authorize' );
	}
	
	public function capture() {
		throw new PHP_Merchant_Exception( 1, 'capture' );
	}
	
	public function void() {
		throw new PHP_Merchant_Exception( 1, 'void' );
	}
	
	public function credit() {
		throw new PHP_Merchant_Exception( 1, 'credit' );
	}
	
	public function recurring() {
		throw new PHP_Merchant_Exception( 1, 'recurring' );
	}
	
	public function set_options( $options ) {
		$this->options = array_merge( $this->options, $options );
		return $this;
	}
	
	public function get_option( $key ) {
		return array_key_exists( $key, $this->options ) ? $this->options[$key] : null;
	}
	
	public function set_option( $key, $value ) {
		$this->options[$key] = $value;
		return $this;
	}
}