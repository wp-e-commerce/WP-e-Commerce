<?php

/**
 * WP eCommerce product functions and product utility function.
 *
 * This is the wpsc equivalent of post-template.php
 *
 * @package wp-e-commerce
 * @since 3.8
 * @subpackage wpsc-template-functions
 */

/**
 * Get The Product Excerpt
 *
 * WPEC uses the excerpt field in the database to store additional product details.
 * This means that where themes output the excerpt (like in search results) the product's
 * additional details are displayed which is not the expected behaviour.
 *
 * This function filters the excerpt early and returns an empty string which forces the default
 * WordPress behaviour to use a truncated version of the content instead.
 *
 * Always use wpsc_the_product_additional_description() to return the addition product description.
 *
 * @since  3.8.13
 *
 * @param   string  $excerpt  The post excerpt (which for products is the additional description).
 * @return  string            The empty excerpt.
 */
function wpsc_get_the_excerpt( $excerpt ) {
	if ( 'wpsc-product' == get_post_type() )
		return '';
	return $excerpt;
}

add_filter( 'get_the_excerpt', 'wpsc_get_the_excerpt', 2 );

/**
 * WPSC Product Variation Price From
 * Gets the formatted lowest price of a product's variations.
 *
 * @since  3.8.10
 *
 * @param  $product_id  (int)       Product ID
 * @param  $args        (array)     Array of options
 * @return              (string)    Number formatted price
 *
 * @uses   apply_filters()          Calls 'wpsc_do_convert_price' passing price and product ID.
 * @uses   wpsc_currency_display()  Passing price and args.
 */
function wpsc_product_variation_price_from( $product_id, $args = null ) {
	global $wpdb;

	$args = wp_parse_args( $args, array(
		'from_text'         => false,
		'no_decimals'       => false,
		'only_normal_price' => false,
		'only_in_stock'     => false
	) );

	static $price_data = array();

	/* @todo: Rewrite using proper WP_Query */
	if ( isset( $price_data[ $product_id ] ) ) {
		$results = $price_data[ $product_id ];
	} else {

		$stock_sql = '';

		if ( $args['only_in_stock'] ) {
			$stock_sql = "INNER JOIN {$wpdb->postmeta} AS pm3 ON pm3.post_id = p.id AND pm3.meta_key = '_wpsc_stock' AND pm3.meta_value != '0'";
		}

		$sql = $wpdb->prepare( "
			SELECT pm.meta_value AS price, pm2.meta_value AS special_price
			FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_price'
			LEFT JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_special_price'
			$stock_sql
			WHERE p.post_type = 'wpsc-product' AND p.post_parent = %d AND p.post_status IN ( 'publish', 'inherit' )
		", $product_id );

		$results                   = $wpdb->get_results( $sql );
		$price_data[ $product_id ] = $results;
	}

	$prices = array();

	foreach ( $results as $row ) {

		$price = (float) $row->price;

		if ( ! $args['only_normal_price'] ) {

			$special_price = (float) $row->special_price;

			if ( $special_price != 0 && $special_price < $price ) {
				$price = $special_price;
			}
		}

		if ( $args['no_decimals'] ) {
			$price = explode( '.', $price );
			$price = array_shift( $price );
		}

		$prices[] = $price;
	}

	sort( $prices );

	if ( empty( $prices ) ) {
		$prices[] = 0;
	}

	$price = apply_filters( 'wpsc_do_convert_price', $prices[0], $product_id );

	$price_args = array(
		'display_as_html'       => false,
		'display_decimal_point' => ! $args['no_decimals']
	);

	$price = wpsc_currency_display( $price, $price_args );

	if ( isset( $prices[0] ) && $prices[0] == $prices[count( $prices ) - 1] ) {
		$args['from_text'] = false;
	}

	if ( $args['from_text'] ){
		$price = sprintf( $args['from_text'], $price );
	}

	return $price;
}

/**
 * wpsc normal product price function
 * TODO determine why this function is here
 * @return string - returns some form of product price
 */
function wpsc_product_normal_price() {
	return wpsc_the_product_price( false, true );
}

function wpsc_calculate_price( $product_id, $variations = false, $special = true ) {
	global $wpdb;

	$p_id = $product_id;
	if ( ! empty( $variations ) )
		$product_id = wpsc_get_child_object_in_terms( $product_id, $variations, 'wpsc-variation' );
	elseif ( !$product_id )
		$product_id = get_the_ID();

	if( ! $product_id && ! empty( $variations ) ){
		$product_ids = wpsc_get_child_object_in_select_terms( $p_id, $variations, 'wpsc_variation' );
		$sql = "SELECT `post_id` FROM ".$wpdb->postmeta." WHERE `meta_key` = '_wpsc_stock' AND `meta_value` != '0' AND `post_id` IN (".implode(',' , $product_ids).")";
		$stock_available = $wpdb->get_col($sql);
		$sql = "SELECT `post_id` FROM ".$wpdb->postmeta." WHERE `meta_key` = '_wpsc_price' AND `post_id` IN (".implode(',',$stock_available).") ORDER BY `meta_value` ASC LIMIT 1";
		$product_id = $wpdb->get_var($sql);
	}

	if ( $special ) {
		$full_price = get_post_meta( $product_id, '_wpsc_price', true );
		$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );

		$price = $full_price;
		if ( ($full_price > $special_price) && ($special_price > 0) ) {
			$price = $special_price;
		}
	} else {
		$price = get_post_meta( $product_id, '_wpsc_price', true );
	}
	$price = apply_filters( 'wpsc_price', $price, $product_id );

	return $price;
}

/**
 * Get The Product Thumbnail ID
 *
 * If no post thumbnail is set, this will return the ID of the first image
 * associated with a product.
 *
 * @param  int  $product_id  Product ID
 * @return int               Product thumbnail ID
 */
/**
 * Get The Product Thumbnail ID
 *
 * If no post thumbnail is set, this will return the ID of the first image
 * associated with a product. If no image is found and the product is a variation it will
 * then try getting the parent product's image instead.
 *
 * @param  int  $product_id  Product ID
 * @return int               Product thumbnail ID
 */
function wpsc_the_product_thumbnail_id( $product_id ) {
	$thumbnail_id = null;

	// Use product thumbnail...
	if ( has_post_thumbnail( $product_id ) ) {
		$thumbnail_id = get_post_thumbnail_id( $product_id  );
	} else {
		// ... or get first image in the product gallery
		$attached_images = wpsc_get_product_gallery( $product_id );
		if ( ! empty( $attached_images ) )
			$thumbnail_id = $attached_images[0]->ID;
	}
	return apply_filters( 'wpsc_the_product_thumbnail_id', $thumbnail_id, $product_id );
}

/**
* Regenerate size metadata of a thumbnail in case it's missing.
*
* @since  3.8.9
* @access private
*/
function _wpsc_regenerate_thumbnail_size( $thumbnail_id, $size ) {
	// regenerate size metadata in case it's missing
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	if ( ! $metadata = wp_get_attachment_metadata( $thumbnail_id ) ) {
		$metadata = array();
	}

	if ( empty( $metadata['sizes'] ) ) {
		$metadata['sizes'] = array();
	}

	$file      = get_attached_file( $thumbnail_id );
	$generated = wp_generate_attachment_metadata( $thumbnail_id, $file );

	if ( empty( $generated ) ) {
		return false;
	}

	if ( empty( $generated['sizes'] ) ) {
		$generated['sizes'] = array();
	}

	$metadata['sizes'] = array_merge( $metadata['sizes'], $generated['sizes'] );
	wp_update_attachment_metadata( $thumbnail_id, $metadata );

	return true;
}

function wpsc_get_downloadable_file( $file_id ) {
	return get_post( $file_id );
}

/**
* wpsc_product_has_children function
* Checks whether a product has variations or not
*
* @return boolean true if product does have variations, false otherwise
*/
function wpsc_product_has_children( $id, $exclude_unpublished = true ){
	return wpsc_product_has_variations( $id );
}

/**
 * Check whether a product has variations or not.
 *
 * @since  3.8.9
 * @access public
 * @param  int  $id Product ID. Defaults to 0 for current post in the loop.
 * @return bool     true if product has variations.
 */
function wpsc_product_has_variations( $id = 0 ) {
	static $has_variations = array();

	if ( ! $id )
		$id = get_the_ID();

	if ( ! isset( $has_variations[ $id ] ) ) {
		$args = array(
			'post_parent' => $id,
			'post_type'   => 'wpsc-product',
			'post_status' => array( 'inherit', 'publish' ),
		);
		$children = get_children( $args );

		$has_variations[$id] = ! empty( $children );
	}

	return $has_variations[$id];
}

/**
 * Maybe Get The Parent Product Thumbnail ID
 *
 * If no thumbnail is found and the product is a variation it will
 * then try getting the parent product's image instead.
 *
 * @param  int  $thumbnail_id  Thumbnail ID
 * @param  int  $product_id    Product ID
 * @return int                 Product thumbnail ID
 *
 * @uses   wpsc_the_product_thumbnail_id()  Get the product thumbnail ID
 */
function wpsc_maybe_get_the_parent_product_thumbnail_id( $thumbnail_id, $product_id ) {

	if ( ! $thumbnail_id ) {
		$product = get_post( $product_id );

		if ( is_a( $product, 'WP_Post' ) && $product->post_parent > 0 ) {
			$thumbnail_id = wpsc_the_product_thumbnail_id( $product->post_parent );
		}
	}

	return $thumbnail_id;
}

add_filter( 'wpsc_the_product_thumbnail_id', 'wpsc_maybe_get_the_parent_product_thumbnail_id', 10, 2 );

function wpsc_get_product_gallery( $id ) {
	$ids = get_post_meta( $id, '_wpsc_product_gallery', true );

	$args = array(
		'nopaging' => true,
		'post_status' => 'all',
		'post_type' => 'attachment'
	);

	// By default, when the user took no action to select product gallery, all the
	// images attached to a product are treated as gallery images. If $ids is not
	// empty, however, it means the user has made some selection for the product
	// gallery, we should respect that selection.
	if ( empty( $ids ) ) {
		$args['post_parent'] = $id;
		$args['orderby'] = 'menu_order';
		$args['order'] = 'ASC';
	} else {
		if ( ! is_array( $ids ) )
			$ids = array();

		if ( has_post_thumbnail( $id ) ) {
			$thumb_id = get_post_thumbnail_id( $id );
			if ( ! in_array( $thumb_id, $ids ) )
				array_unshift( $ids, $thumb_id );
		}

		if ( ! is_array( $ids ) || empty( $ids ) )
			return array();

		$args['post__in'] = $ids;
		$args['orderby'] = 'post__in';
	}

	return get_posts( $args );
}

function wpsc_set_product_gallery( $id, $attachments ) {
	$attachment_ids = array();
	foreach ( $attachments as $attachment ) {
		if ( is_object( $attachment ) )
			$attachment_ids[] = $attachment->ID;
		elseif ( is_numeric( $attachment ) )
			$attachment_ids[] = absint( $attachment );
	}

	return update_post_meta( $id, '_wpsc_product_gallery', $attachment_ids );
}
