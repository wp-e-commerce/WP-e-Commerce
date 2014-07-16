<?php

require_once( PHP_MERCHANT_PATH . '/common/http.php' );
require_once( PHP_MERCHANT_PATH . '/common/http-curl.php' );

class PHP_Merchant_HTTP_CURL_Test extends UnitTestCase
{
	public function __construct() {
		parent::__construct( 'PHP_Merchant_HTTP_CURL unit tests' );
	}
	
	public function test_array_is_parsed_correctly_into_query_string() {
		$http = new PHP_Merchant_HTTP_CURL_Bogus();
		
		$args = array(
			'key_1' => 'key 1 value',
			'key_2' => 'key&2%value='
		);
		
		$query_string = 'key_1=key+1+value&key_2=key%262%25value%3D';
		
		$this->assertEqual( $http->parse_args( $args ), $query_string );
	}
}

class PHP_Merchant_HTTP_CURL_Bogus extends PHP_Merchant_HTTP_CURL
{
	public function parse_args( $args ) {
		return parent::parse_args( $args );
	}
}
