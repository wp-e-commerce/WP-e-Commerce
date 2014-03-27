<?php

/**
 * Twitter OAuth library, based on Abraham Williams'
 *
 * This class extends the original and replaces OAuth* classes with
 * Sputnik_OAuth_* classes, in addition to adapting it for WP_Http
 *
 * @package Sputnik
 * @subpackage Public Utilities
 */
class Sputnik_Library_TwitterOAuth extends Sputnik_Library_TwitterOAuth_Internal {

	/**
	 * construct TwitterOAuth object
	 * @internal Converted OAuth* to Sputnik_OAuth_*
	 */
	function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
		$this->sha1_method = new Sputnik_OAuth_SignatureMethod_HMAC_SHA1();
		$this->consumer = new Sputnik_OAuth_Consumer($consumer_key, $consumer_secret);
		if (!empty($oauth_token) && !empty($oauth_token_secret)) {
			$this->token = new Sputnik_OAuth_Consumer($oauth_token, $oauth_token_secret);
		} else {
			$this->token = NULL;
		}
	}


	/**
	 * Get a request_token from Twitter
	 *
	 * @internal Converted OAuth* to Sputnik_OAuth_*
	 * @returns a key/value array containing oauth_token and oauth_token_secret
	 */
	function getRequestToken($oauth_callback = NULL) {
		$parameters = array();
		if (!empty($oauth_callback)) {
			$parameters['oauth_callback'] = $oauth_callback;
		} 
		$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
		$token = Sputnik_OAuth_Util::parse_parameters($request);
		$this->token = new Sputnik_OAuth_Consumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @internal Converted OAuth* to Sputnik_OAuth_*
	 * @returns array("oauth_token" => "the-access-token",
	 *                "oauth_token_secret" => "the-access-secret",
	 *                "user_id" => "9436992",
	 *                "screen_name" => "abraham")
	 */
	function getAccessToken($oauth_verifier = FALSE) {
		$parameters = array();
		if (!empty($oauth_verifier)) {
			$parameters['oauth_verifier'] = $oauth_verifier;
		}
		$request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
		$token = Sputnik_OAuth_Util::parse_parameters($request);
		$this->token = new Sputnik_OAuth_Consumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * One time exchange of username and password for access token and secret.
	 *
	 * @internal Converted OAuth* to Sputnik_OAuth_*
	 * @returns array("oauth_token" => "the-access-token",
	 *                "oauth_token_secret" => "the-access-secret",
	 *                "user_id" => "9436992",
	 *                "screen_name" => "abraham",
	 *                "x_auth_expires" => "0")
	 */  
	function getXAuthToken($username, $password) {
		$parameters = array();
		$parameters['x_auth_username'] = $username;
		$parameters['x_auth_password'] = $password;
		$parameters['x_auth_mode'] = 'client_auth';
		$request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
		$token = Sputnik_OAuth_Util::parse_parameters($request);
		$this->token = new Sputnik_OAuth_Consumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Format and sign an OAuth / API request
	 *
	 * @internal Converted OAuth* to Sputnik_OAuth_*
	 */
	function oAuthRequest($url, $method, $parameters) {
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = "{$this->host}{$url}.{$this->format}";
		}
		$request = Sputnik_OAuth_Request::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
		case 'GET':
			return $this->http($request->to_url(), 'GET');
		default:
			return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
		}
	}

	/**
	 * Make an HTTP request
	 *
	 * @internal Adapted for WP_Http
	 * @return API results
	 */
	function http($url, $method, $postfields = NULL) {
		$this->http_info = null; // this is never used
		$options = array(
			'method' => $method,
			'timeout' => $this->timeout,
			'user-agent' => $this->useragent,
			'sslverify' => $this->ssl_verifypeer
		);

		switch ($method) {
			case 'POST':
				if (!empty($postfields)) {
					$options['body'] = $postfields;
				}
				break;
			case 'DELETE':
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}

		$response = wp_remote_request($url, $options);

		if (is_wp_error($response)) {
			$this->http_code = null;
			$this->http_header = array();
			return false;
		}

		$this->http_code = $response['response']['code'];
		$this->http_header = $response['headers'];

		return $response['body'];
	}
}