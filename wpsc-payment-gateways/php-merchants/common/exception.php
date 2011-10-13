<?php

final class PHP_Merchant_Exception extends Exception {
	const C_FEATURE_NOT_SUPPORTED = 1;
	
	private static $messages = array(
		C_FEATURE_NOT_SUPPORTED => 'This payment gateway does not support "%s" feature.',
	);
	
	private $other_args;
	
	public function __construct( $code, $message_args = array(), $other_args = array() ) {
		$this->message_args = $message_args;
		$this->message = vsprintf( self::$messages[$code], $this->message_args );
		$this->other_args = $other_args;
	}
	
	public function getArguments() {
		return $this->other_args;
	}
}