<?php

require_once( PHP_MERCHANT_PATH . '/common/php-merchant.php' );

class PHP_Merchant_Test extends UnitTestCase
{
	public function __construct() {
		parent::__construct( 'PHP_Merchant test cases' );
	}
	
	public function test_options_are_initialized_correctly() {
		$options = array(
			'option_1' => 'option 1',
			'option_2' => 'option 2',
		);
		
		$bogus = new PHP_Merchant_Bogus( $options );
		foreach ( $options as $key => $value ) {
			$this->assertEqual( $value, $bogus->get_option( $key ) );
		}
	}
	
	public function test_multiple_options_are_set_correctly() {
		$options = array(
			'option_1' => 'option 1',
			'option_2' => 'option 2',
		);
		
		$bogus = new PHP_Merchant_Bogus();
		$bogus->set_options( $options );
		foreach ( $options as $key => $value ) {
			$this->assertEqual( $value, $bogus->get_option( $key ) );
		}
	}
	
	public function test_option_is_set_correctly() {
		$bogus = new PHP_Merchant_Bogus();
		$bogus->set_option( 'test_option', 'test_value' );
		$this->assertEqual( 'test_value', $bogus->get_option( 'test_option' ) );
	}
	
	public function test_default_currency_option_is_usd() {
		$bogus = new PHP_Merchant_Bogus();
		$this->assertEqual( 'USD', $bogus->get_option( 'currency' ) );
	}
	
	public function test_option_is_overrided_correctly() {
		$bogus = new PHP_Merchant_Bogus();
		$bogus->set_option( 'currency', 'JPY' );
		$this->assertEqual( 'JPY', $bogus->get_option( 'currency' ) );
	}
	
	public function test_multiple_options_are_overrided_correctly() {
		$bogus = new PHP_Merchant_Bogus();
		$bogus->set_option( 'option_1', 'option 1' );
		$bogus->set_option( 'option_2', 'option 2' );
		
		$bogus->set_options( array(
			'currency' => 'JPY',
			'option_1' => 'updated value',
		) );
		
		$this->assertEqual( 'JPY', $bogus->get_option( 'currency' ) );
		$this->assertEqual( 'updated value', $bogus->get_option( 'option_1' ) );
		$this->assertEqual( 'option 2', $bogus->get_option( 'option_2' ) );
	}
	
	public function test_price_is_formatted_correctly() {
		$bogus = new PHP_Merchant_Bogus();
		$this->assertEqual( $bogus->format( 22.59378 ), '22.59' );
		$this->assertEqual( $bogus->format( 22.495 ), '22.5' );
	}
	
	public function test_price_should_not_be_fractional_in_certain_currencies() {
		$bogus = new PHP_Merchant_Bogus();
		$bogus->set_option( 'currency', 'JPY' );
		$this->assertEqual( $bogus->format( 22.59378 ), '23' );
		$this->assertEqual( $bogus->format( 22.495 ), '22' );
		$this->assertEqual( $bogus->format( 22.333 ), '22' );
		
		$bogus->set_option( 'currency', 'HUF' );
		$this->assertEqual( $bogus->format( 22.59378 ), '23' );
		$this->assertEqual( $bogus->format( 22.495 ), '22' );
		$this->assertEqual( $bogus->format( 22.333 ), '22' );
	}
	
	public function test_no_exception_is_thrown_when_feature_is_supported() {
		$bogus = new PHP_Merchant_Bogus_Full_Features();
		$bogus->authorize();
		$bogus->capture();
		$bogus->void();
		$bogus->credit();
		$bogus->recurring();
	}
	
	public function test_exception_is_thrown_when_authorize_is_not_supported() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'authorize' ) );
		$bogus->authorize();
	}
	
	public function test_exception_is_thrown_when_capture_is_not_supported() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'capture' ) );
		$bogus->capture();
	}
	
	public function test_exception_is_thrown_when_void_is_not_supported() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'void' ) );
		$bogus->void();
	}
	
	public function test_exception_is_thrown_when_credit_is_not_supported() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'credit' ) );
		$bogus->credit();
	}
	
	public function test_exception_is_thrown_when_recurring_is_not_supported() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_FEATURE_NOT_SUPPORTED, 'recurring' ) );
		$bogus->recurring();
	}
	
	public function test_exception_is_thrown_when_a_required_option_is_missing() {
		$bogus = new PHP_Merchant_Bogus();
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_REQUIRED_OPTION_UNDEFINED, array( 'missing_option' ) ) );
		$bogus->requires( 'missing_option' );
	}
	
	public function test_exception_is_thrown_when_some_required_options_are_missing() {
		$bogus = new PHP_Merchant_Bogus();
		
		$bogus->set_option( 'option_1', 'option 1' );
		$bogus->set_option( 'option_2', 'option 2' );
		
		$this->expectException( new PHP_Merchant_Exception( PHPME_REQUIRED_OPTION_UNDEFINED, array( 'missing_option' ) ) );
		$bogus->requires( array( 'currency', 'option_1', 'option_2', 'missing_option' ) );
	}

	public function test_exception_is_thrown_when_some_cond_required_options_are_missing() {
		$bogus = new PHP_Merchant_Bogus();
			
		$this->expectException( new PHP_Merchant_Exception( PHPME_REQUIRED_OPTION_UNDEFINED, 'option_1, option_2, option_3' ) );
		$bogus->conditional_requires( array( 'option_1', 'option_2', 'option_3' ) );
	}
}

class PHP_Merchant_Bogus extends PHP_Merchant {
	public function requires( $options ) {
		parent::requires( $options );
	}
	public function conditional_requires( $options ) {
		parent::conditional_requires( $options );
	}
}

class PHP_Merchant_Bogus_Full_Features extends PHP_Merchant {	
	public function authorize() {
	}
	
	public function capture() {
	}
	
	public function void() {
	}
	
	public function credit() {
	}
	
	public function recurring() {
	}
}
