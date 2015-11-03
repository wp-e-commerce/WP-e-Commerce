<?php
class Sputnik_Upgrader extends Plugin_Upgrader {
	public function download_package($package) {
		if (!preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package)) {
			return $package; //must be a local file..
		}

		if (empty($package)) {
			return new WP_Error('no_package', $this->strings['no_package']);
		}

		$this->skin->feedback('downloading_package', $package);

		$download_file = self::download($package);

		if (is_wp_error($download_file)) {
			return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());
		}

		return $download_file;
	}

	protected static function download( $url, $timeout = 300 ) {
		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url )
			return new WP_Error('http_no_url', __('Invalid URL Provided.', 'wp-e-commerce' ));

		$tmpfname = wp_tempnam($url);
		if ( ! $tmpfname )
			return new WP_Error('http_no_file', __('Could not create Temporary file.', 'wp-e-commerce' ));

		$args = array(
			'timeout'    => $timeout,
			'stream'     => true,
			'filename'   => $tmpfname,
			'headers'    => array( 'X-WP-Domain' => Sputnik_API::domain() ),
			'user-agent' => 'WP eCommerce Marketplace: ' . WPSC_VERSION
		);

		Sputnik_API::sign_download($url, $args);

		$response = wp_safe_remote_get($url, $args);

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ){
			unlink( $tmpfname );
			return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		return $tmpfname;
	}
}