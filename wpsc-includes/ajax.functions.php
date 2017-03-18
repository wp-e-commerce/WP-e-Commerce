<?php

/**
 * The AJAX functions for WP-e-Commerce
 *
 * @package wp-e-commerce
 * @since 3.8
 */

function wpsc_gateway_notification() {
	global $wpsc_gateways;
	$gateway_name = $_GET['gateway'];
	// work out what gateway we are getting the request from, run the appropriate code.
	if ( ($gateway_name != null) && isset( $wpsc_gateways[$gateway_name]['class_name'] ) ) {
		$merchant_class = $wpsc_gateways[$gateway_name]['class_name'];
		$merchant_instance = new $merchant_class( null, true );
		$merchant_instance->process_gateway_notification();
	}
	exit();
}

if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == 'gateway_notification') )
	add_action( 'init', 'wpsc_gateway_notification' );

/**
 * wpsc scale image function, dynamically resizes an image oif no image already exists of that size.
 */
function wpsc_scale_image() {
	global $wpdb;

	if ( !isset( $_REQUEST['wpsc_action'] ) || !isset( $_REQUEST['attachment_id'] ) || ( 'scale_image' != $_REQUEST['wpsc_action'] ) || !is_numeric( $_REQUEST['attachment_id'] ) )
		return false;

	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attachment_id = absint( $_REQUEST['attachment_id'] );
	$width = absint( $_REQUEST['width'] );
	$height = absint( $_REQUEST['height'] );
	$intermediate_size = '';

	if ( (($width >= 10) && ($height >= 10)) && (($width <= 1024) && ($height <= 1024)) ) {
		$intermediate_size = "wpsc-{$width}x{$height}";
		$generate_thumbnail = true;
	} else {
		if ( isset( $_REQUEST['intermediate_size'] ) )
		$intermediate_size = esc_attr( $_REQUEST['intermediate_size'] );
		$generate_thumbnail = false;
	}

	// If the attachment ID is greater than 0, and the width and height is greater than or equal to 10, and less than or equal to 1024
	if ( ($attachment_id > 0) && ($intermediate_size != '') ) {
		// Get all the required information about the attachment
		$uploads = wp_upload_dir();

		$image_meta = get_post_meta( $attachment_id, '' );
		$file_path = get_attached_file( $attachment_id );
		foreach ( $image_meta as $meta_name => $meta_value ) { // clean up the meta array
			$image_meta[$meta_name] = maybe_unserialize( array_pop( $meta_value ) );
		}
		if ( !isset( $image_meta['_wp_attachment_metadata'] ) )
			$image_meta['_wp_attachment_metadata'] = '';
		$attachment_metadata = $image_meta['_wp_attachment_metadata'];

		if ( !isset( $attachment_metadata['sizes'] ) )
			$attachment_metadata['sizes'] = '';
		if ( !isset( $attachment_metadata['sizes'][$intermediate_size] ) )
			$attachment_metadata['sizes'][$intermediate_size] = '';

		// determine if we already have an image of this size
		if ( (count( $attachment_metadata['sizes'] ) > 0) && ($attachment_metadata['sizes'][$intermediate_size]) ) {
			$intermediate_image_data = image_get_intermediate_size( $attachment_id, $intermediate_size );
			if ( file_exists( $file_path ) ) {
				$original_modification_time = filemtime( $file_path );
				$cache_modification_time = filemtime( $uploads['basedir'] . "/" . $intermediate_image_data['path'] );
				if ( $original_modification_time < $cache_modification_time ) {
					$generate_thumbnail = false;
				}
			}
		}

		if ( $generate_thumbnail == true ) {
			//JS - 7.1.2010 - Added true parameter to function to not crop - causing issues on WPShop
			$crop = apply_filters( 'wpsc_scale_image_cropped', true );
			$intermediate_size_data = image_make_intermediate_size( $file_path, $width, $height, $crop );
			$attachment_metadata['sizes'][$intermediate_size] = $intermediate_size_data;
			wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
			$intermediate_image_data = image_get_intermediate_size( $attachment_id, $intermediate_size );
		}

		wp_redirect( set_url_scheme( $intermediate_image_data['url'] ) );
	} else {
		_e( 'Invalid Image parameters', 'wp-e-commerce' );
	}
	exit();
}
add_action( 'init', 'wpsc_scale_image' );

function wpsc_download_file() {
	global $wpdb;

	if ( isset( $_GET['downloadid'] ) ) {
		// strip out anything that isnt 'a' to 'z' or '0' to '9'
		ini_set('max_execution_time',10800);
		$downloadid = preg_replace( "/[^a-z0-9]+/i", '', strtolower( $_GET['downloadid'] ) );
		$download_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `uniqueid` = '%s' AND `downloads` > '0' AND `active`='1' LIMIT 1", $downloadid ), ARRAY_A );

		if ( is_null( $download_data ) && is_numeric( $downloadid ) )
			$download_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `id` = %d AND `downloads` > '0' AND `active`='1' AND `uniqueid` IS NULL LIMIT 1", $downloadid ), ARRAY_A );


		if ( (get_option( 'wpsc_ip_lock_downloads' ) == 1) && ($_SERVER['REMOTE_ADDR'] != null) ) {
			$ip_number = $_SERVER['REMOTE_ADDR'];
			if ( $download_data['ip_number'] == '' ) {
				// if the IP number is not set, set it
				$wpdb->update( WPSC_TABLE_DOWNLOAD_STATUS, array(
				'ip_number' => $ip_number
				), array( 'id' => $download_data['id'] ) );
			} else if ( $ip_number != $download_data['ip_number'] ) {
				// if the IP number is set but does not match, fail here.
				exit( _e( 'This download is no longer valid, Please contact the site administrator for more information.', 'wp-e-commerce' ) );
			}
		}

		$file_id = $download_data['fileid'];
		$file_data = wpsc_get_downloadable_file($file_id);

		if ( $file_data == null ) {
			exit( _e( 'This download is no longer valid, Please contact the site administrator for more information.', 'wp-e-commerce' ) );
		}

		if ( $download_data != null ) {

			if ( (int)$download_data['downloads'] >= 1 ) {
				$download_count = (int)$download_data['downloads'] - 1;
			} else {
				$download_count = 0;
			}


			$wpdb->update( WPSC_TABLE_DOWNLOAD_STATUS, array(
			'downloads' => $download_count
			), array( 'id' => $download_data['id'] ) );

			$cart_contents = $wpdb->get_results( $wpdb->prepare( "SELECT `" . WPSC_TABLE_CART_CONTENTS . "`.*, $wpdb->posts.`guid` FROM `" . WPSC_TABLE_CART_CONTENTS . "` LEFT JOIN $wpdb->posts ON `" . WPSC_TABLE_CART_CONTENTS . "`.`prodid`= $wpdb->posts.`post_parent` WHERE $wpdb->posts.`post_type` = 'wpsc-product-file' AND `purchaseid` = %d", $download_data['purchid'] ), ARRAY_A );
			$dl = 0;

			foreach ( $cart_contents as $cart_content ) {
				if ( $cart_content['guid'] == 1 ) {
					$dl++;
				}
			}
			if ( count( $cart_contents ) == $dl ) {
				wpsc_update_purchase_log_status( $download_data['purchid'], 4 );
			}

			_wpsc_force_download_file( $file_id );
		} else {
			exit( _e( 'This download is no longer valid, Please contact the site administrator for more information.', 'wp-e-commerce' ) );
		}
	}
}
add_action( 'init', 'wpsc_download_file' );

function _wpsc_force_download_file( $file_id ) {
	do_action( 'wpsc_alter_download_action', $file_id );
	$file_data = get_post( $file_id );
	if ( ! $file_data )
		wp_die( __( 'Invalid file ID.', 'wp-e-commerce' ) );

	$file_name = basename( $file_data->post_title );
	$file_path = WPSC_FILE_DIR . $file_name;

	if ( is_file( $file_path ) ) {
		header( 'Content-Type: ' . $file_data->post_mime_type );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Disposition: attachment; filename="' . stripslashes( $file_name ) . '"' );
		if ( isset( $_SERVER["HTTPS"] ) && ($_SERVER["HTTPS"] != '') ) {
			/*
			  There is a bug in how IE handles downloads from servers using HTTPS, this is part of the fix, you may also need:
			  session_cache_limiter('public');
			  session_cache_expire(30);
			  At the start of your index.php file or before the session is started
			 */
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: public" );
		} else {
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		}
		header( "Pragma: public" );
						header( "Expires: 0" );

		// destroy the session to allow the file to be downloaded on some buggy browsers and webservers
		session_destroy();
		wpsc_readfile_chunked( $file_path );
		exit();
	}else{
		wp_die(__('Sorry, something has gone wrong with your download!', 'wp-e-commerce'));
	}
}
