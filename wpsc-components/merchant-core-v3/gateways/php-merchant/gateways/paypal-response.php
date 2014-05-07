<?php

class PHP_Merchant_Paypal_Response extends PHP_Merchant_Response
{
	protected $params = array();
	
	public function __construct( $response_str ) {
		parent::__construct( $response_str );
		
		parse_str( $response_str, $params );
		$this->params = $params = array_map( 'urldecode', $params );
		
		if ( empty( $params ) || ! isset( $params['ACK'] ) )
			throw new PHP_Merchant_Exception( PHPME_INVALID_RESPONSE, array(), $response_str );
		
		$this->options['datetime'] = $params['TIMESTAMP'];
		
		$time = rtrim( $params['TIMESTAMP'], 'Z' ) . '+0000';
		if ( ! $time = strptime( $time, '%FT%T%z' ) )
			$time = strptime( $time, '%FT%T%Z' );
		
		switch ( $params['ACK'] ) {
			case 'Success':
			case 'SuccessWithWarning':
				$this->is_successful = true;
				break;
			
			case 'Failure':
			case 'FailureWithWarning':
			case 'Warning':
				$this->is_successful = false;
				break;
		}
		
		if ( isset( $params['TOKEN'] ) )
			$this->options['token']          = $params['TOKEN'];
		
		$this->options['correlation_id'] = $params['CORRELATIONID'];
		$this->options['version']        = $params['VERSION'];
		$this->options['build']          = $params['BUILD'];
		
		$i = 0;
		while ( array_key_exists( "L_ERRORCODE{$i}", $params ) ) {
			$error = array(
				'code'    => $params["L_ERRORCODE{$i}"],
				'message' => $params["L_SHORTMESSAGE{$i}"],
				'details' => $params["L_LONGMESSAGE{$i}"],
			);
			
			$this->errors[] = $error;
			$i++;
		}
			
		if ( is_array( $time ) ) {
			extract( $time, EXTR_SKIP );
			$this->options['timestamp'] = mktime( $tm_hour, $tm_min, $tm_sec, 1 + $tm_mon, $tm_mday, 1900 + $tm_year );
		} else {
			$this->options['timestamp'] = time();
		}
	}
	
	public function get_params() {
		return $this->params;
	}
}