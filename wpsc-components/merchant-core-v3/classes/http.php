<?php

require_once( WPSC_MERCHANT_V3_PATH . '/gateways/php-merchant/common/http.php' );

class WPSC_Payment_Gateway_HTTP extends PHP_Merchant_HTTP {
	protected function request( $url, $fields = '', $args = array() ) {
		$defaults = array(
			'follow' => true,
			'method' => 'GET',
			'ssl_verify' => false,
			'body' => '',
			'httpversion' => '1.1',
			'timeout' => 60,
		);

		$args = array_merge( $defaults, $args );
		$args['body'] = $fields;

		$response = wp_safe_remote_request( $url, $args );

		if ( is_wp_error( $response ) )
			throw new PHP_Merchant_Exception( PHPME_HTTP_REQUEST_FAILED, $response->get_error_message() );

		return $response['body'];
	}

	public function post( $url, $fields = '', $args = array() ) {
		$args['method'] = 'POST';
		return $this->request( esc_url_raw( $url ), $fields, $args );
	}

	public function get( $url, $fields = '', $args = array() ) {
		return $this->request( esc_url_raw( $url ), $fields, $args );
	}
}
