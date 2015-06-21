<?php

/**
 * Sputnik OAuth class
 */
class Sputnik_API_Auth {
	public function __construct($key, $secret, $token = null, $token_secret = null) {
		$this->sha1_method = new Sputnik_OAuth_SignatureMethod_HMAC_SHA1();
		$this->consumer = new Sputnik_OAuth_Consumer($key, $secret);
		if (!empty($token) && !empty($token_secret)) {
			$this->token = new Sputnik_OAuth_Consumer($token, $token_secret);
		} else {
			$this->token = NULL;
		}
	}

	/**
	 * Get a request_token from Twitter
	 *
	 * @return array A key/value array containing oauth_token and oauth_token_secret
	 */
	public function get_request_token($callback = null) {
		$parameters = array();
		if (!empty($callback)) {
			$parameters['oauth_callback'] = $callback;
		}
		$request = $this->request('/auth/request_token', 'GET', $parameters);

		$token = Sputnik_OAuth_Util::parse_parameters($request);
		$this->token = new Sputnik_OAuth_Consumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Get the authorize URL
	 *
	 * @return string
	 */
	public function get_authorize_url($token) {
		if (is_array($token)) {
			$token = $token['oauth_token'];
		}
		return Sputnik::SITE_BASE . "/oauth/authorize?oauth_token={$token}";
	}

	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @return  array("oauth_token" => "the-access-token",
	 *                "oauth_token_secret" => "the-access-secret",
	 *                "user_id" => "9436992",
	 *                "screen_name" => "abraham")
	 */
	public function get_access_token($verifier = false) {
		$parameters = array();
		if (!empty($verifier)) {
			$parameters['oauth_verifier'] = $verifier;
		}

		$request = $this->request('/auth/access_token', 'GET', $parameters);

		$token = Sputnik_OAuth_Util::parse_parameters($request);
		$this->token = new Sputnik_OAuth_Consumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Format and sign an OAuth / API request
	 */
	public function sign($url, $method = 'GET', $parameters = array()) {
		if (strpos($url, 'http') !== 0) {
			$url = Sputnik::API_BASE . $url;
		}

		$request = Sputnik_OAuth_Request::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		return $request;
	}

	/**
	 * Format and sign an OAuth / API request
	 */
	public function get_auth_header($url, $method = 'GET', $parameters = array()) {
		if (strpos($url, 'http') !== 0) {
			$url = Sputnik::API_BASE . $url;
		}
		$request = Sputnik_OAuth_Request::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		$header = $request->to_header($this->sha1_method, $this->consumer, $this->token);

		// We want to remove the 'Authorization' bit from the start
		return substr($header, 15);
	}

	/**
	 * Format and sign an OAuth / API request, and execute it
	 */
	public function request($url, $method, $parameters) {
		$request = $this->sign($url, $method, $parameters);

		switch ($method) {
			case 'GET':
				return $this->http($request->to_url(), 'GET');
			default:
				return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
		}
	}

	protected function http($url, $method, $postfields = NULL) {
		$args = array(
			'method'     => $method,
			'user-agent' => 'WP eCommerce Marketplace: ' . WPSC_VERSION
		);

		switch ($method) {
			case 'POST':
				if (!empty($postfields)) {
					$args['body'] = $postfields;
				}
				break;
		}

		$args['headers'] = array( 'X-WP-Domain' => Sputnik_API::domain() );

		$response = wp_safe_remote_request($url, $args);

		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		}

		if ($response['response']['code'] != 200) {
			throw new Exception($response['body']);
		}
		return $response['body'];
	}
}
