<?php

define( 'PHPME_FEATURE_NOT_SUPPORTED', 1 );
define( 'PHPME_REQUIRED_OPTION_UNDEFINED', 2 );
define( 'PHPME_HTTP_REQUEST_FAILED', 3 );
define( 'PHPME_INVALID_RESPONSE', 4 );

final class PHP_Merchant_Exception extends Exception {
	private static $messages = array(
		PHPME_FEATURE_NOT_SUPPORTED     => 'This payment gateway does not support "%s" feature',
		PHPME_REQUIRED_OPTION_UNDEFINED => 'Missing required parameter: %s',
		PHPME_HTTP_REQUEST_FAILED       => 'HTTP request failed: %s',
		PHPME_INVALID_RESPONSE          => 'Invalid response from payment gateway',
	);
	
	public static function addCodes( $codes ) {
		if ( ! is_array( $codes ) )
			return;
		
		self::$message += $codes;
	}
	
	private $other_args;
	
	public function __construct( $code, $message_args = array(), $other_args = array() ) {
		$this->message_args = (array) $message_args;
		$this->message = vsprintf( self::$messages[$code], $this->message_args );
		$this->other_args = (array) $other_args;
		$this->code = $code;
		
		parent::__construct( $this->message, $code );
	}
	
	public function getArguments() {
		return $this->other_args;
	}
}