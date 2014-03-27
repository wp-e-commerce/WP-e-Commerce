<?php

add_action( 'wpsc_loaded', 'wpsc_core_load_thumbnail_sizes' );
add_action( 'after_setup_theme', 'wpsc_check_thumbnail_support', 99 );
add_filter( 'intermediate_image_sizes_advanced', 'wpsc_intermediate_image_sizes_advanced', 10, 1 );

function wpsc_check_thumbnail_support() {
	if ( !current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails' );
		add_action( 'init', 'wpsc_remove_post_type_thumbnail_support' );
	}
}

function wpsc_remove_post_type_thumbnail_support() {
	remove_post_type_support( 'post', 'thumbnail' );
	remove_post_type_support( 'page', 'thumbnail' );
}

function wpsc_intermediate_image_sizes_advanced($sizes){
	$sizes['small-product-thumbnail']=array(
		"width" => get_option( 'product_image_width' ),
		"height" => get_option( 'product_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['medium-single-product']=array(
		"width" => get_option( 'single_view_image_width' ),
		"height" => get_option( 'single_view_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['featured-product-thumbnails']=array(
		"width" => 425,
		"height" => 215,
		"crop" => get_option( 'wpsc_crop_thumbnails', true )
	);
	$sizes['admin-product-thumbnails']=array(
		"width" => 38,
		"height" => 38,
		"crop" => get_option( 'wpsc_crop_thumbnails', true )
	);
	$sizes['product-thumbnails']=array(
		"width" => get_option( 'product_image_width' ),
		"height" => get_option( 'product_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['gold-thumbnails']=array(
		"width" => get_option( 'wpsc_gallery_image_width' ),
		"height" => get_option( 'wpsc_gallery_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	return $sizes;
}

/**
 *
 * wpsc_core_load_thumbnail_sizes()
 *
 * Load up the WPEC core thumbnail sizes
 * @todo Remove hardcoded sizes
 */
function wpsc_core_load_thumbnail_sizes() {
	// Add image sizes for products
	add_image_size( 'product-thumbnails', get_option( 'product_image_width' ), get_option( 'product_image_height' ), get_option( 'wpsc_crop_thumbnails', false )  );
	add_image_size( 'gold-thumbnails',  get_option( 'wpsc_gallery_image_width' ), get_option( 'wpsc_gallery_image_height' ), get_option( 'wpsc_crop_thumbnails', false ) );
	add_image_size( 'admin-product-thumbnails', 38, 38, get_option( 'wpsc_crop_thumbnails', true )  );
	add_image_size( 'featured-product-thumbnails', 425, 215, get_option( 'wpsc_crop_thumbnails', true )  );
	add_image_size( 'small-product-thumbnail', get_option( 'product_image_width' ), get_option( 'product_image_height' ), get_option( 'wpsc_crop_thumbnails', false ) );
	add_image_size( 'medium-single-product', get_option( 'single_view_image_width' ), get_option( 'single_view_image_height' ), get_option( 'wpsc_crop_thumbnails', false) );
}
