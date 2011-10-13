<?php

define( 'PHPME_FEATURE_NOT_SUPPORTED', 1 );
define( 'PHPME_REQUIRED_OPTION_UNDEFINED', 2 );

final class PHP_Merchant_Exception extends Exception {
	private static $messages = array(
		PHPME_FEATURE_NOT_SUPPORTED => 'This payment gateway does not support "%s" feature.',
		PHPME_MISSING_REQUIRED_PARAM => 'Missing required parameter: %s.',
	);
	
	private $other_args;
	
	public function __construct( $code, $message_args = array(), $other_args = array() ) {
		$this->message_args = (array) $message_args;
		$this->message = vsprintf( self::$messages[$code], $this->message_args );
		$this->other_args = (array) $other_args;
	}
	
	public function getArguments() {
		return $this->other_args;
	}
}