<?php

class PHP_Merchant_HTTP_CURL extends PHP_Merchant_HTTP
{
	protected function request( $url, $fields = '', $args = array() ) {
		$defaults = array(
			'follow' => true,
			'method' => 'GET',
			'ssl_verify' => false,
			'body' => '',
		);

		$args = array_merge( $defaults, $args );
		extract( $args, EXTR_SKIP );

		$body = http_build_query( $fields );

		$handle = curl_init();
		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, $ssl_verify ? 2 : false );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, $ssl_verify );
		curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, $follow );

		switch ( $method ) {
			case 'POST':
				curl_setopt( $handle, CURLOPT_POST, true );
				curl_setopt( $handle, CURLOPT_POSTFIELDS, $body );
				break;
		}

		$response = curl_exec( $handle );

		if ( ! $response ) {
			throw new PHP_Merchant_Exception( PHPME_HTTP_REQUEST_FAILED, curl_error( $handle ) );
		}

		return $response;
	}

	public function post( $url, $fields = '', $args = array() ) {
		$args['method'] = 'POST';
		return $this->request( $url, $fields, $args );
	}

	public function get( $url, $fields = '', $args = array() ) {
		return $this->request( $url, $fields, $args );
	}
}