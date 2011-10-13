<?php

class PHP_Merchant_Paypal_Response extends PHP_Merchant_Response
{
	public function __construct( $response_str ) {
		parse_str( $response_str, $params );
		
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
		
		foreach ( $params as $key => $param ) {
			$this->options[strtolower($key)] = $param;
		}
	}
}