<?php

if ( ! function_exists( 'wpsc_the_product_thumbnail' ) ) {
	function wpsc_the_product_thumbnail( $width = null, $height = null, $product_id = 0, $page = false ) {
		$thumbnail = '';

		// Get the product ID if none was passed
		if ( empty( $product_id ) )
			$product_id = get_the_ID();

		// Load the product
		$product = get_post( $product_id );

		$thumbnail_id = wpsc_the_product_thumbnail_id( $product_id );

		// If no thumbnail found for item, get it's parent image (props. TJM)
		if ( ! $thumbnail_id && $product->post_parent ) {
			$thumbnail_id = wpsc_the_product_thumbnail_id( $product->post_parent );
		}

		if ( $page == 'manage-products' && isset( $thumbnail_id ) ) {
			$current_size = image_get_intermediate_size( $thumbnail_id, 'wpsc_product_admin_thumbnail' );

			if ( ! $current_size )
				_wpsc_regenerate_thumbnail_size( $thumbnail_id, 'admin-product-thumbnails' );

			$src = wp_get_attachment_image_src( $thumbnail_id, 'wpsc_product_admin_thumbnail' );

			if ( ! empty( $src ) && is_string( $src[0] ) )
				$thumbnail = $src[0];
		}

		if ( ! $thumbnail && isset( $thumbnail_id ) )
			$thumbnail = wpsc_product_image( $thumbnail_id, $width, $height );

		if ( ! empty( $thumbnail ) && is_ssl() )
			$thumbnail = str_replace( 'http://', 'https://', $thumbnail );

		return $thumbnail;
	}
}

if ( ! function_exists( 'wpsc_product_image' ) ) {
	function wpsc_product_image( $attachment_id = 0, $width = null, $height = null ) {

		$uploads = wp_upload_dir();

		// Do some dancing around the image size
		if ( ( ( $width >= 10 ) && ( $height >= 10 ) ) && ( ( $width <= 1024 ) && ( $height <= 1024 ) ) )
			$intermediate_size = "wpsc-{$width}x{$height}";

		// Get image url if we have enough info
		if ( ( $attachment_id > 0 ) && ( !empty( $intermediate_size ) ) ) {

			// Get all the required information about the attachment
			$image_meta = get_post_meta( $attachment_id, '' );
			$file_path  = get_attached_file( $attachment_id );

			// Clean up the meta array
			foreach ( $image_meta as $meta_name => $meta_value )
				$image_meta[$meta_name] = maybe_unserialize( array_pop( $meta_value ) );


			$attachment_metadata = $image_meta['_wp_attachment_metadata'];
			// Determine if we already have an image of this size
			if ( isset( $attachment_metadata['sizes'] ) && (count( $attachment_metadata['sizes'] ) > 0) && ( isset( $attachment_metadata['sizes'][$intermediate_size] ) ) ) {
				$intermediate_image_data = image_get_intermediate_size( $attachment_id, $intermediate_size );
				$image_url = $intermediate_image_data['url'];
			} else {
				$image_url = home_url( "index.php?wpsc_action=scale_image&attachment_id={$attachment_id}&width=$width&height=$height" );
			}
		// Not enough info so attempt to fallback
		} else {

			if ( !empty( $attachment_id ) ) {
				$image_url = home_url( "index.php?wpsc_action=scale_image&attachment_id={$attachment_id}&width=$width&height=$height" );
			} else {
				$image_url = false;
			}

		}
		if(empty($image_url) && !empty($file_path)){
			$image_meta = get_post_meta( $attachment_id, '_wp_attached_file' );
			if ( ! empty( $image_meta ) )
				$image_url = $uploads['baseurl'].'/'.$image_meta[0];
		}
	        if( is_ssl() ) str_replace('http://', 'https://', $image_url);

		return apply_filters( 'wpsc_product_image', $image_url );
	}
}