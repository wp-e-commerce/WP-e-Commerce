<?php

if ( ! defined( 'WPSC_BOT_VISITOR_ID' ) ) {
	define( 'WPSC_BOT_VISITOR_ID', 1 );
}


/**
 * Get current customer ID.
 *
 * If the user is logged in, return the user ID. Otherwise return the ID associated
 * with the customer's cookie.
 *
 * Implement your own system by hooking into 'wpsc_get_current_customer_id' filter.
 *
 * @access public
 * @since 3.8.9
 * @return mixed        User ID (if logged in) or customer cookie ID
 */
function wpsc_get_current_customer_id( $visitor_id_to_set = false ) {
	// once we determine the current customer id it will remain in effect for
	// the remainder of the current request.  This helps performance, but also
	// makes it possible to manipulate the visitor database and cookie without
	// causing code dependent on the valid visitor id to fail.   It's probably
	// also a security benefit to not allow the current user to be changed
	// midway through the HTTP request processing
	static $visitor_id = false;

	if ( $visitor_id_to_set ) {
		$visitor_id = $visitor_id_to_set;
	}

	if ( $visitor_id !== false ) {
		return $visitor_id;
	}

	if ( _wpsc_is_bot_user() ) {
		$visitor_id = WPSC_BOT_VISITOR_ID;
	}

	if ( ! $visitor_id && is_user_logged_in() ) {
		// if the user is logged in we use the user id
		$visitor_id = _wpsc_get_wp_user_visitor_id();
		if ( $visitor_id == WPSC_BOT_VISITOR_ID ) {
			// it is not allowed to have the bot visitor id
			$visitor_id = false;
		}
	}

	if ( ! $visitor_id && isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) ) {
		list( $id, $expire, $hash ) = explode( '|', $_COOKIE[WPSC_CUSTOMER_COOKIE] );
		$visitor_id = $id;
	}

	// get the last active time to validate the visitor exists
	if ( ! ( $visitor_id && wpsc_get_visitor_last_active( $visitor_id ) ) ) {
		$visitor_id = _wpsc_create_customer_id();
	}

	return $visitor_id;
}


/**
 * get the count of posts by the customer
 * @since 3.8.14
 * @access public
 * @return int
 */
function wpsc_customer_post_count( $id = false ) {


	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	return wpsc_visitor_post_count( $id );
}

/**
 * get the count of comments by the customer
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_customer_comment_count( $id = false ) {

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	return wpsc_visitor_comment_count( $id );
}

/**
 * get the count of purchases by the customer
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_customer_purchase_count( $id = false ) {

	$count = 0;

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	return wpsc_visitor_purchase_count( $id );
}

/**
 * does the customer have purchases
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_customer_has_purchases( $id = false ) {

	$has_purchases = false;

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	return wpsc_visitor_has_purchases( $id );
}

// include the internal wpec customer implementation functions and AJAX functions
require_once( WPSC_FILE_PATH . '/wpsc-includes/customer-private.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/customer-ajax.php' );

