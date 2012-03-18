<?php
/**
 * WP e-Commerce conditional tags.
 *
 * This file contains conditional tags that theme developers can use in theme templates.
 *
 * @see        template-tags.functions.php
 * @see        theme.functions.php
 * @since      4.0
 * @package    wp-e-commerce
 * @subpackage conditional-tags
 */

/**
 * Determine whether the current page is a single product page.
 *
 * @see is_single()
 * @since 4.0
 * @uses  get_post_type()
 * @uses  is_single()
 *
 * @param  mixed $product Optional. Product ID, title, slug, or an array of such.
 * @return bool
 */
 function wpsc_is_single_product( $product = '' ) {
 	return is_singular( 'wpsc-product' );
 }

/**
 * Determine whether the current page is the product catalog page
 *
 * @since 4.0
 * @uses  is_post_type_archive()
 *
 * @return bool
 */
 function wpsc_is_product_catalog() {
 	return is_post_type_archive( 'wpsc-product' );
 }

/**
 * Determine whether the current page is a product category archive page.
 *
 * @since 4.0
 * @uses  is_tax()
 *
 * @param  mixed $cat Optional. Category ID, name, slug or an array of such.
 * @return bool
 */
 function wpsc_is_product_category( $cat = '' ) {
 	return is_tax( 'wpsc_product_category', $cat );
 }

/**
 * Determine whether the current page is a product tag archive page.
 *
 * @since 4.0
 * @uses  is_tax()
 *
 * @param  mixed $tag Optional. Tag ID, name, slug or an array of such.
 * @return bool
 */
function wpsc_is_product_tag( $tag = '' ) {
	return is_tax( 'product_tag', $tag );
}

function wpsc_is_page() {
	global $wpsc_query;
	return $wpsc_query->wpsc_is_page;
}

function wpsc_is_cart() {
	global $wpsc_query;
	return $wpsc_query->wpsc_is_cart;
}

function wpsc_is_checkout( $slug = '' ) {
	global $wpsc_query, $wpsc_page_instance;
	$test = $wpsc_query->wpsc_is_checkout;
	if ( $slug !== '' )
		$test = $test && $slug == $wpsc_page_instance->get_slug();

	return $test;
}

function wpsc_is_login( $slug = '' ) {
	global $wpsc_query, $wpsc_page_instance;
	$test = $wpsc_query->wpsc_is_login;
	if ( $slug !== '' )
		$test = $test && $slug == $wpsc_page_instance->get_slug();

	return $test;
}

function wpsc_is_password_reminder( $slug = '' ) {
	global $wpsc_query, $wpsc_page_instance;
	$test = $wpsc_query->wpsc_is_password_reminder;
	if ( $slug !== '' )
		$test = $test && $slug == $wpsc_page_instance->get_slug();

	return $test;
}

function wpsc_is_register( $slug = '' ) {
	global $wpsc_query, $wpsc_page_instance;
	$test = $wpsc_query->wpsc_is_register;

	if ( $slug !== '' )
		$test = $test && $slug == $wpsc_page_instance->get_slug();

	return $test;
}

/**
 * Determine whether a product has an associated featured thumbnail or not.
 *
 * @since 4.0
 * @uses  has_post_thumbnail()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product ID in the loop.
 * @return bool
 */
function wpsc_has_product_thumbnail( $id = null ) {
	return has_post_thumbnail( $id );
}

/**
 * Determine whether a product has variations or not.
 *
 * @since 4.0
 * @uses  get_post()
 * @uses  wpsc_get_product_id()
 * @uses  WPSC_Product_Variation::get_instance()
 * @uses  WPSC_Product_Variation::has_variations()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool         True if the product has variations.
 */
function wpsc_has_product_variations( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_product_id();

	$product = get_post( $id );
	if ( $product->post_parent )
		return false;

	$variations = WPSC_Product_Variations::get_instance( $id );
	return $variations->has_variations();
}

/**
 * Determine whether a product is on sale or not.
 *
 * @since 4.0
 * @uses  wpsc_get_product_id()
 * @uses  wpsc_get_product_sale_price()
 * @uses  wpsc_get_product_original_price()
 * @uses  wpsc_has_product_variations()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::is_on_sale()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool
 */
function wpsc_is_product_on_sale( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_product_id();

	if ( wpsc_has_product_variations( $id ) ) {
		$variations = WPSC_Product_Variations::get_instance( $id );
		return $variations->is_on_sale();
	}

	$sale_price = wpsc_get_product_sale_price( null, 'number' );
	$original_price = wpsc_get_product_original_price( null, 'number' );

	if ( $sale_price > 0 && $sale_price < $original_price )
		return true;

	return false;
}

/**
 * Determine whether a product is out of stock or not.
 *
 * @since 4.0
 * @uses  get_post_meta()
 * @uses  wpdb::get_var()
 * @uses  wpdb::prepare()
 * @uses  wpsc_get_product_id()
 * @uses  wpsc_has_product_variations()
 * @uses  WPSC_Product_Variations::get_instance()
 * @uses  WPSC_Product_Variations::is_out_of_stock()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool
 */
function wpsc_is_product_out_of_stock( $id = null ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_product_id();

	$stock = get_post_meta( $id, '_wpsc_stock', true );

	// An empty string means the product does not have limited stock
	if ( $stock === '' )
		return false;

	if ( wpsc_has_product_variations() ) {
		$variations = WPSC_Product_Variations::get_instance( $id );
		return $variations->is_out_of_stock();
	}

	if ( $stock > 0 ) {
		$sql = $wpdb->prepare( 'SELECT SUM(stock_claimed) FROM '.WPSC_TABLE_CLAIMED_STOCK.' WHERE product_id=%d', $id );
		$claimed_stock = $wpdb->get_var( $sql );
		$stock -= $claimed_stock;
	}

	if ( $stock < 0 )
		return true;

	return false;
}

function wpsc_cart_has_items() {
	return wpsc_cart_item_count() >= 1;
}

function wpsc_has_user_messages( $type = 'all', $context = 'main' ) {
	global $wpsc_page_instance;
	if ( empty( $wpsc_page_instance ) )
		return false;

	return $wpsc_page_instance->has_messages( $type, $context );
}