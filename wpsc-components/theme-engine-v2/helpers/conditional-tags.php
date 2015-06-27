<?php
/**
 * WP eCommerce conditional tags.
 *
 * This file contains conditional tags that theme developers can use in theme templates.
 *
 * @see        template-tags.functions.php
 * @see        theme.functions.php
 * @since 4.0
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
 * @return bool
 */
 function wpsc_is_single() {
 	return is_singular( 'wpsc-product' );
 }

/**
 * Determine whether the current page is the product main store page
 *
 * @since 4.0
 * @uses  is_post_type_archive()
 *
 * @return bool
 */
function wpsc_is_store() {
	return is_post_type_archive( 'wpsc-product' );
}

/**
 * Determine whether the current page is the root page (i.e. 'home_url') and
 * the main store is set to display here.
 *
 * @since  0.1
 * @return bool
 */
function wpsc_is_store_front_page() {
 	global $wp_query;
 	return $wp_query->wpsc_is_store_front_page;
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

function wpsc_is_controller() {
	global $wp_query;
	return $wp_query->wpsc_is_controller;
}

function _wpsc_is_page( $page, $slug ) {
	global $wp_query;
	$prop = 'wpsc_is_' . $page;

	$test = ! empty( $wp_query->$prop );

	if ( $slug !== '' ) {
		$test = $test && $slug == _wpsc_get_current_controller_method();
	}

	return $test;
}

function wpsc_is_cart( $slug = '' ) {
	return _wpsc_is_page( 'cart', $slug );
}

function wpsc_is_checkout( $slug = '' ) {
	return _wpsc_is_page( 'checkout', $slug );
}

function wpsc_is_customer_account( $slug = '' ) {
	return _wpsc_is_page( 'customer_account', $slug );
}

function wpsc_is_login( $slug = '' ) {
	return _wpsc_is_page( 'login', $slug );
}

function wpsc_is_password_reminder( $slug = '' ) {
	return _wpsc_is_page( 'password_reminder', $slug );
}

function wpsc_is_register( $slug = '' ) {
	return _wpsc_is_page( 'register', $slug );
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
	$parent = get_post_field( 'post_parent', $id );

	if ( $parent ) {
		return wpsc_has_product_thumbnail( $parent );
	}

	return has_post_thumbnail( $id );
}

/**
 * Determine whether a product has variations or not.
 *
 * @since 4.0
 * @uses  get_post()
 * @uses  wpsc_get_product_id()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool         True if the product has variations.
 */
function wpsc_has_product_variations( $id = null ) {
	if ( empty( $id ) ) {
		$id = wpsc_get_product_id();
	}

	$product = WPSC_Product::get_instance( $id );
	return $product->has_variations;
}

/**
 * Determine whether a product is on sale or not.
 *
 * @since 4.0
 * @uses  wpsc_get_product_id()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool
 */
function wpsc_is_product_on_sale( $id = null ) {

	if ( empty( $id ) ) {
		$id = wpsc_get_product_id();
	}

	$product = WPSC_Product::get_instance( $id );
	return $product->is_on_sale;
}

/**
 * Determine whether a product is out of stock or not.
 *
 * @since 4.0
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 * @return bool
 */
function wpsc_is_product_out_of_stock( $id = null ) {
	return ! wpsc_product_has_stock( $id );
}

function wpsc_product_has_stock( $id = null ) {
	if ( is_null( $id ) ) {
		$id = wpsc_get_product_id();
	}

	$product = WPSC_Product::get_instance( $id );
	return $product->has_stock;
}

function wpsc_cart_has_items() {
	return wpsc_cart_item_count() >= 1;
}

function wpsc_has_user_messages( $type = 'all', $context = 'main' ) {
	$message_collection = WPSC_Message_Collection::get_instance();
	$messages           = $message_collection->query( $type, $context );

	return ! empty( $messages );
}

function wpsc_is_tax_enabled() {
	$wpec_taxes_controller = new wpec_taxes_controller();
	return $wpec_taxes_controller->wpec_taxes_isenabled();
}

function wpsc_is_tax_included() {
	return wpsc_tax_isincluded();
}