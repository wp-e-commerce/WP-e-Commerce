<?php
require_once( 'exception.php' );

abstract class PHP_Merchant
{
	protected $currencies_without_fractions = array( 'JPY', 'HUF' );
	
	protected $options = array(
		'currency' => 'USD',
	);
	
	public function __construct( $options = array() ) {
		$this->options = array_merge( $this->options, $options );
	}
	
	public function format( $amt, $currency = false ) {
		if ( ! $currency )
			$currency = $this->options['currency'];
			
		$dec = in_array( $currency, $this->currencies_without_fractions ) ? 0 : 2;
		return number_format( $amt, $dec );
	}
	
	public function purchase( $amt, $options = array() ) {
		
	}
	
	public function authorize() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'authorize' );
	}
	
	public function capture() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'capture' );
	}
	
	public function void() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'void' );
	}
	
	public function credit() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'credit' );
	}
	
	public function recurring() {
		throw new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'recurring' );
	}
	
	public function get_options() {
		return $this->options;
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
	
	protected function requires( $options ) {
		$missing = array();
		foreach ( (array) $options as $option ) {
			if ( ! isset( $this->options[$option] ) )
				$missing[] = $option;
		}
		
		if ( ! empty( $missing ) )
			throw new PHP_Merchant_Exception( PHPME_MISSING_REQUIRED_PARAM, implode( ', ', $missing ) );
	}
}