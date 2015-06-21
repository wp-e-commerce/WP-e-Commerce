<?php

class Sputnik_Updater {
	public static function bootstrap() {
		add_filter('pre_set_site_transient_update_plugins', array(get_class(), 'mangle_update_plugins'));
		add_filter('pre_set_site_transient_update_themes', array(get_class(), 'mangle_update_themes'));
		add_action('admin_action_update-selected', array(get_class(), 'prepare_bulk_mangle'));
	}

	public static function mangle_update_plugins($plugins) {
		// WP saves once before checking, in case it fails
		static $tried = false;
		if (!$tried) {
			$tried = true;
			return $plugins;
		}

		$ours = Sputnik::get_installed(true);

		if (empty($ours)) {
			return $plugins;
		}

		$data = array();
		$files = array();
		foreach ($ours as $file => $plugin) {
			// If something accidentally slipped in...
			if (empty($plugin['Sputnik ID'])) {
				// ...ignore it.
				continue;
			}

			$name = $plugin['Sputnik ID'];

			$files[$name] = $file;
			$data[$name] = $plugin['Version'];
		}

		$url = Sputnik::API_BASE . '/version';

		$options = array(
			'headers' => array(
				'X-WP-Domain' => self::domain(),
			),
			'user-agent' => 'WP eCommerce Marketplace: ' . WPSC_VERSION
		);
		$url = esc_url_raw( add_query_arg('plugins', urlencode(json_encode($data)), $url) );
		$req = wp_safe_remote_get($url, $options);
		if (is_wp_error($req) || $req['response']['code'] !== 200) {
			return $plugins;
		}

		$response = json_decode($req['body']);

		if (empty($response)) {
			return $plugins;
		}

		foreach ($response as $name => $result) {
			$file = $files[$name];

			if ($result->status === 410) {
				self::$suspended[$name] = $result;
				Sputnik::suspend_plugin($name, $file, $result);
				continue;
			}
			if ($result->status !== 200) {
				continue;
			}

			$info = (object) array(
				'package' => $result->location,
				'url' => $result->url,
				'new_version' => $result->version,
				'slug' => 'sputnik-' . $name,
				'sputnik_id' => $name
			);
			$plugins->response[$file] = $info;
		}

		return $plugins;
	}

	public static function mangle_update_themes( $themes ) {
		// WP saves once before checking, in case it fails
		static $tried = false;
		if ( ! $tried) {
			$tried = true;
			return $themes;
		}

		$ours = Sputnik::get_installed( true );

		if ( empty( $ours ) ) {
			return $themes;
		}

		$data = array();
		$files = array();
		foreach ( $ours as $file => $theme ) {
			// If something accidentally slipped in...
			if ( empty( $theme['Sputnik ID'] ) ) {
				// ...ignore it.
				continue;
			}

			$name = $theme['Sputnik ID'];

			$files[$name] = $file;
			$data[$name] = $theme['Version'];
		}

		$url = Sputnik::API_BASE . '/version';

		$options = array(
			'headers' => array(
				'X-WP-Domain' => self::domain(),
			),
			'user-agent' => 'WP eCommerce Marketplace: ' . WPSC_VERSION
		);
		$url = esc_url_raw( add_query_arg( 'themes', urlencode( json_encode( $data ) ), $url ) );
		$req = wp_safe_remote_get( $url, $options );
		if (is_wp_error($req) || $req['response']['code'] !== 200) {
			return $themes;
		}

		$response = json_decode( $req['body'] );

		if ( empty( $response ) ) {
			return $themes;
		}

		foreach ( $response as $name => $result ) {
			$file = $files[$name];

			if ( $result->status === 410 ) {
				self::$suspended[$name] = $result;
				Sputnik::suspend_plugin( $name, $file, $result );
				continue;
			}
			if ( $result->status !== 200 ) {
				continue;
			}

			$info = (object) array(
				'package'     => $result->location,
				'url'         => $result->url,
				'new_version' => $result->version,
				'slug'        => 'sputnik-' . $name,
				'sputnik_id'  => $name
			);
			$themes->response[$file] = $info;
		}

		return $themes;
	}

	/**
	 * Callback for {@see Sputnik} to confirm suspension
	 *
	 * The use of this confirmation system is to ensure that only the updater
	 * can disable other plugins.
	 */
	public static function confirm_suspend($plugin, $data) {
		return (isset(self::$suspended[$plugin]) && self::$suspended[$plugin] === $data);
	}

	public static function mangle_bulk($current) {
		return $current;
	}

	public static function prepare_bulk_mangle() {
		add_filter('http_request_args', array(get_class(), 'mangle_bulk_http'), 10, 2);
	}

	public static function mangle_bulk_http($r, $url) {

		if (strpos($url, Sputnik::API_BASE) === false) {
			return $r;
		}

		$auth_header = Sputnik_API::get_auth_for_download($url);
		list($key, $auth_header) = explode(':', $auth_header);

		$r['headers']['X-WP-Domain'] = Sputnik_API::domain();
		$r['headers']['Authorization'] = $auth_header;

		return $r;
	}

	protected static function domain() {
		$wp_install = home_url( '/' );

		if ( is_multisite() )
			$wp_install = network_site_url();

		$wp_install = parse_url( $wp_install );
		return $wp_install['host'];
	}
}