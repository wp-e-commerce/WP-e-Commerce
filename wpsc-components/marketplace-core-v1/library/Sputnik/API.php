<?php

class Sputnik_API {
	/**
	 * @var Sputnik_API_Auth
	 */
	protected static $auth = null;

	/**
	 * For sites like WPeCommerce.org that will distribute Sputnik with a plugin to sell WP plugins,
	 * we override the domain. This is because we need to ensure Baikonur receives the Saas domain,
	 * not the end-user domain. Only relevant where Sputnik is available to an end-user, not a Saas.
	 */
	protected static $domain_override = 'https://wpecommerce.org';

	public static function get_all($page = 1, $params = null) {
		$url = '/';
		if ($page !== 1) {
			$url = sprintf('/page/%d', $page);
		}

		return self::request($url, $params);
	}

    public static function search( $query, $params = null, $page = 1 ) {
		$url = '/';

		if ( $page !== 1 ) {
			$url = sprintf( '/page/%d', $page );
		}

		$extra = array(
	    	'query'     => $query
		);

		$params = array_merge( $params, $extra );

		return self::request( $url, $params );
    }


	public static function get_single($name, $user = 0) {
		$params = array(
			'name' => $name
		);
		if ($user !== 0) {
			$params['user'] = $user;
		}

		$uri = "/info/{$name}/";

		return self::request( $uri );
	}

	public static function rate_product($name, $rating) {
		self::authenticate();

		$url = sprintf('/info/%s/rate', $name);
		$parameters = array('rating' => (int) $rating);
		$auth_header = self::$auth->get_auth_header($url, 'POST', $parameters);
		$options = array(
			'method' => 'POST',
			'headers' => array(
				'Authorization' => $auth_header
			)
		);
		return self::request($url, $parameters, $options);
	}

	public static function get_tags() {
		return self::request('/tags');
	}

	protected static function authenticate() {
		$token = get_option('sputnik_oauth_access', false);

		if ($token == false) {
			throw new Exception('Need to authenticate first', 1);
		}
		self::$auth = new Sputnik_API_Auth(Sputnik::OAUTH_KEY, Sputnik::OAUTH_SECRET, $token['oauth_token'], $token['oauth_token_secret']);
	}

	public static function auth_request( $callback = '', $redirect = true ) {
		self::$auth   = new Sputnik_API_Auth( Sputnik::OAUTH_KEY, Sputnik::OAUTH_SECRET);
		$callback_url = empty( $callback ) ? Sputnik_Admin::build_url( array( 'oauth' => 'callback' ) ) : $callback;
		$token        = self::$auth->get_request_token( $callback_url );

		update_option( 'sputnik_oauth_request', $token );

		$auth_url = self::$auth->get_authorize_url( $token );

		//Modifying to add marketplace and user email to query string.
		if ( $redirect ) {
			wp_redirect( esc_url( add_query_arg( array( 'domain' => self::domain(), 'user' => rawurlencode( wp_get_current_user()->user_email ) ), $auth_url ) ) );
			exit;
		} else {
			return esc_url( $auth_url );
		}
	}

	public static function auth_access() {

		if( isset( $_REQUEST['denied'] ) ) {

			$return_url = Sputnik_Admin::build_url( array( 'auth' => 'denied' ) );

		} else {
			$request = get_option('sputnik_oauth_request', false);

			self::$auth = new Sputnik_API_Auth(Sputnik::OAUTH_KEY, Sputnik::OAUTH_SECRET, $request['oauth_token'], $request['oauth_token_secret']);
			$access = self::$auth->get_access_token($_REQUEST['oauth_verifier']);

			update_option('sputnik_oauth_access', $access);

			$args = array();
			if ( ! empty( $_REQUEST['oauth_buy']  ) )
				$args['oauth_buy'] = $_REQUEST['oauth_buy'];
			$return_url = Sputnik_Admin::build_url( $args );
		}

		// Close the authentication popup ?>
<!DOCTYPE html><html>
	<head>
		<title><?php _e( 'Redirecting...', 'wp-e-commerce' ); ?></title>
		<script type="text/javascript">
			parent.location = '<?php echo wp_validate_redirect( $return_url ); ?>';
			window.close();
		</script>
	</head>
	<body>&nbsp;</body>
</html><?php
		die();
	}

	public static function get_account() {
		self::authenticate();

		$url = '/account';
		$request = self::$auth->sign($url);
		return self::request($request->to_url());
	}

	public static function get_purchased() {
		self::authenticate();

		$url = '/account/purchased';
		$request = self::$auth->sign($url);
		return self::request($request->to_url());
	}

	public static function get_own() {
		self::authenticate();

		$url = '/account/myplugins';
		$request = self::$auth->sign($url);
		return self::request($request->to_url());
	}

	public static function sign_download(&$url, &$args) {
		self::authenticate();

		if (!isset($args['headers'])) {
			$args['headers'] = array();
		}

		$oauth = self::$auth->sign($url);

		$url = $oauth->to_url();
	}

	public static function get_auth_for_download($url) {
		self::authenticate();

		return self::$auth->get_auth_header($url);
	}

	/* Purchase Methods */

	public static function get_checkout_token( $product ) {
		self::authenticate();

		$url = '/purchase/get_checkout_token/' . $product->client_product_id;

		$request = self::$auth->sign( $url, 'GET', array(
			'redirect_uri' => Sputnik_Admin::build_url( array( '_wpnonce' => wp_create_nonce( 'sputnik_install-plugin_' . $product->slug ) ) )
			)
		);

		$response = self::request( $request->to_url(), array(), array( 'timeout' => 25 ) );

		return $response;
	}

	/* Helper Methods */
	public static function request($url, $params = null, $args = array()) {

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$defaults = array( 'method' => 'GET' );

		$args = wp_parse_args( $args, $defaults );

		if ( strpos( $url, 'http' ) !== 0 ) {
			$url = Sputnik::API_BASE . $url;
		}

		$args['timeout']                = 25;
		$args['headers']['user-agent']  = 'WP eCommerce Marketplace: ' . WPSC_VERSION;
		$args['headers']['X-WP-Domain'] = self::domain();

		$request = wp_safe_remote_request( esc_url_raw( $url ), $args );

		if ( is_wp_error( $request ) ) {
			throw new Exception( $request->get_error_message() );
		}

		if ( $request['response']['code'] != 200 ) {
			throw new Exception($request['body'], $request['response']['code']);
		}

		$result = json_decode($request['body']);

		if ($result === null) {
			throw new Exception($request['body'], $request['response']['code']);
		}

		$request['body'] = $result;


		return $request;
	}

	public static function domain() {
		$wp_install = home_url( '/' );

		if ( is_multisite() )
			$wp_install = network_site_url( '/' );

		if ( ! empty( self::$domain_override ) )
			$wp_install = self::$domain_override;

		return $wp_install;
	}
}
