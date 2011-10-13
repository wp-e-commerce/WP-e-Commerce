<?php

class PHP_Merchant_Paypal_Response extends PHP_Merchant_Response
{
	public function __construct( $response_str ) {
		parse_str( $response_str, $params );
		$params = array_map( 'urldecode', $params );
		
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
				$this->is_successful = false;
				break;
		}
		
		$this->options['token']          = $params['TOKEN'];
		$this->options['correlation_id'] = $params['CORRELATIONID'];
		$this->options['version']        = $params['VERSION'];
		$this->options['build']          = $params['BUILD'];
			
		if ( is_array( $time ) ) {
			extract( $time, EXTR_SKIP );
			$this->options['timestamp'] = mktime( $tm_hour, $tm_min, $tm_sec, 1 + $tm_mon, $tm_mday, 1900 + $tm_year );
		} else {
			$this->options['timestamp'] = time();
		}
	}
}