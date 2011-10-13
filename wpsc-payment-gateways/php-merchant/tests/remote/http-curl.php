<?php

require_once( PHP_MERCHANT_PATH . '/common/http-curl.php' );

class PHP_Merchant_HTTP_CURL_Remote_Test extends WebTestCase
{
	public function __construct() {
		parent::__construct( 'PHP_Merchant_HTTP_CURL Remote Unit Tests' );
	}
	
	public function test_http_curl_get_request_returns_correct_response() {
		$expected_content = 'c7194f7e74fedaf84525235d3b37c203';
		
		$http = new PHP_Merchant_HTTP_CURL();
		$actual_content = $http->get( 'http://garyc40.com/test-get.php' );
		$this->assertEqual( $expected_content, $actual_content );
	}
	
	public function test_http_curl_post_request_returns_correct_response() {
		$expected_content = "key_1 => value 1\ntest_another_key => value 2\n";
		
		$http = new PHP_Merchant_HTTP_CURL();
		$actual_content = $http->post( 'http://garyc40.com/test-post.php', array(
			'key 1' => 'value 1',
			'test another key' => 'value 2',
		) );
		$this->assertEqual( $expected_content, $actual_content );
	}
}