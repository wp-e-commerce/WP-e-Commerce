<?php

add_action( 'wpsc_set_cart_item'         , '_wpsc_action_customer_used_cart' );
add_action( 'wpsc_add_item'              , '_wpsc_action_customer_used_cart' );
add_action( 'wpsc_before_submit_checkout', '_wpsc_action_customer_used_cart' );
add_action( 'wp_login'                   , '_wpsc_action_setup_customer'     );


/**
 * Setup current user object and customer ID as well as cart.
 *
 * @uses  do_action() Calls 'wpsc_setup_customer' after customer data is ready
 * @returns int visitor id
 * @access private
 * @since  3.8.13
 */
function _wpsc_action_setup_customer() {

	/////////////////////////////////////////////////////////////////////////
	// Setting up the customer happens after WPEC is initialized AND after
	// WordPress has loaded.  The reason for this is that the conditional
	// query tags are checked to see if the request is a 404 or a feed or
	// some other request that should not create a visitor profile.  The
	// conditional query tags are not available until after the
	// posts_selection hook is processed.  The 'wp' action is fired after
	// the 'posts_selection' hook.
	/////////////////////////////////////////////////////////////////////////
	if ( ! did_action( 'init' ) ) {
		_wpsc_doing_it_wrong( __FUNCTION__, __( 'Customer cannot be reliably setup until at least the "init" hook as been fired during AJAX processing.', 'wpsc' ), '3.8.14' );
	}

	// if the customer cookie is invalid, unset it
	$visitor_id_from_cookie = _wpsc_validate_customer_cookie();

	if ( $visitor_id_from_cookie && is_user_logged_in() ) {
		$id_from_wp_user = get_user_meta( get_current_user_id(), _wpsc_get_visitor_meta_key( 'visitor_id' ), true );
		if ( empty( $id_from_wp_user ) ) {
			_wpsc_update_wp_user_visitor_id( get_current_user_id(), $visitor_id_from_cookie );
		} elseif ( $visitor_id_from_cookie != $id_from_wp_user ) {

			// save the old visitor id so the merge cart function can do its work
			wpsc_update_customer_meta( 'merge_cart_vistor_id', $visitor_id_from_cookie );

			// make the current customer cookie match the cookie that is in the WordPress user meta
			_wpsc_create_customer_id_cookie( $id_from_wp_user );

			// merging cart requires the taxonomies to have been initialized
			if ( did_action( 'wpsc_register_taxonomies_after' ) ) {
				_wpsc_merge_cart();
			} else {
				add_action( 'wpsc_register_taxonomies_after', '_wpsc_merge_cart', 1 );
			}
		}
	} else {
		$id_from_wp_user = '';
	}

	// initialize customer ID if it's not already there
	$visitor_id = wpsc_get_current_customer_id();

	// if there wasn't a visitor id in the cookies we set it now
	if ( $visitor_id && empty( $visitor_id_from_cookie ) && is_user_logged_in() ) {
		_wpsc_create_customer_id_cookie( $visitor_id );
	}

	// setup the cart and restore its items
	wpsc_core_setup_cart();

	do_action( 'wpsc_setup_customer', $visitor_id );
}


function _wpsc_abandon_temporary_customer_profile( $id = false ) {

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	do_action( '_wpsc_abandon_temporary_customer_profile', $id );

	// set the temporary profile keep until time to sometime in the past, the delete
	// processing will take care of the cleanup on the next processing cycle
	wpsc_set_visitor_expiration( $id, -1 );
}

/**
 * Helper function for setting the customer cookie content and expiration
 *
 * @since  3.8.13
 * @access private
 * @param  mixed $cookie  Cookie data
 * @param  int   $expire  Expiration timestamp
 */
function _wpsc_set_customer_cookie( $cookie, $expire ) {

	do_action( '_wpsc_set_customer_cookie' );

	// only set the cookie if headers have not been sent, if headers have been sent
	if ( ! headers_sent() ) {
		setcookie( WPSC_CUSTOMER_COOKIE, $cookie, $expire, WPSC_CUSTOMER_COOKIE_PATH, COOKIE_DOMAIN, false, false );
	}

	if ( $expire < time() ) {
		unset( $_COOKIE[ WPSC_CUSTOMER_COOKIE ] );
	} else {
		$_COOKIE[ WPSC_CUSTOMER_COOKIE ] = $cookie;
	}
}

/**
 * Create a new visitor account for the current visitor and store its ID
 * in a cookie
 *
 * @access public
 * @since 3.8.9
 * @return string Customer ID
 */
function _wpsc_create_customer_id() {

	do_action( '_wpsc_create_customer_id' );

	if ( _wpsc_is_bot_user() ) {

		$visitor_id = WPSC_BOT_VISITOR_ID;
		wpsc_get_current_customer_id( $visitor_id );
		$fake_setting_cookie = true;

	} else {
		$fake_setting_cookie = false;
		$args = array();
		if ( is_user_logged_in() ) {
			$args['user_id'] = get_current_user_id();
		}

		$visitor_id = wpsc_create_visitor( $args );

		if ( $visitor_id === false ) {
			// can't create a new visitor, just use the BOT visitor id
			$visitor_id = WPSC_BOT_VISITOR_ID;
			$fake_setting_cookie = true;
		}

		wpsc_get_current_customer_id( $visitor_id );


		_wpsc_create_customer_id_cookie( $visitor_id, $fake_setting_cookie );

		do_action( 'wpsc_create_customer' , $visitor_id );

	}

	return $visitor_id;
}

/**
 * Create a cookie for a specific customer ID.
 *
 * You can also fake it by just assigning the cookie to $_COOKIE superglobal.
 *
 * @since  3.8.13
 * @access private
 * @param  int  $id      Customer ID
 * @param  boolean $fake_it Defaults to false
 */
function _wpsc_create_customer_id_cookie( $id, $fake_it = false ) {

	do_action( '_wpsc_create_customer_id_cookie', $id, $fake_it );

	$expire = time() + WPSC_CUSTOMER_DATA_EXPIRATION; // valid for 48 hours
	$data   = $id . $expire;

	$key = wp_hash( _wpsc_visitor_security_key( $id ) . '|' . $expire );

	$hash   = hash_hmac( 'md5', $data, $key );
	$cookie = $id . '|' . $expire . '|' . $hash;

	// store ID, expire and hash to validate later
	if ( headers_sent() || $fake_it ) {
		$_COOKIE[ WPSC_CUSTOMER_COOKIE ] = $cookie;
	} else {
		_wpsc_set_customer_cookie( $cookie, $expire );
	}
}

/**
 * Make sure the customer cookie is not compromised.
 *
 * @access public
 * @since 3.8.9
 * @return mixed Return the customer ID if the cookie is valid, false if otherwise.
 */
function _wpsc_validate_customer_cookie() {

	do_action( '_wpsc_validate_customer_cookie' );

	if ( ! isset( $_COOKIE[ WPSC_CUSTOMER_COOKIE ] ) ) {
		return false;
	}

	$cookie = $_COOKIE[ WPSC_CUSTOMER_COOKIE ];
	list( $id, $expire, $hash ) = $x = explode( '|', $cookie );
	$data = $id . $expire;

	// check to see if the ID is valid, it must be an integer, empty test is because old versions of php
	// can return true on empty string
	if ( ! empty( $id ) &&  ctype_digit( $id ) ) {
		$id = intval( $id );
		$security_key = _wpsc_visitor_security_key( $id );

		// if a user is found keep checking, user not found clear the cookie and return invalid
		if ( ! empty( $security_key ) ) {
			$key = wp_hash( $security_key . '|' . $expire );
			$hmac = hash_hmac( 'md5', $data, $key );

			// integrity check
			if ( $hmac == $hash ) {
				return $id;
			}
		}
	}

	// if we get to here the cookie or user is not valid
	return _wpsc_unset_customer_cookie();
}

/**
 * Unsets the customer cookie
 *
 * @access private
 * @since  3.8.14
 */
function _wpsc_unset_customer_cookie() {
	_wpsc_set_customer_cookie( '', time() - 3600 );
}

add_action( 'clear_auth_cookie', '_wpsc_unset_customer_cookie' );

/**
 * Attach a purchase log to our customer profile
 *
 * @access private
 * @since  3.8.14
 */
function _wpsc_set_purchase_log_customer_id( $wpsc_purchase_log ) {

	do_action( '_wpsc_set_purchase_log_customer_id', $wpsc_purchase_log );

	// if there is a purchase log for this user we don't want to delete the
	// user id, even if the transaction isn't successful.  there may be useful
	// information in the customer profile related to the transaction
	wpsc_delete_customer_meta( 'temporary_profile' );

	// connect the purchase to the visitor id
	wpsc_update_purchase_meta( $wpsc_purchase_log->id, 'visitor_id', wpsc_get_current_customer_id(), true );

	// connect the visitor to purchase
	wpsc_add_visitor_meta( wpsc_get_current_customer_id(), 'purchase_id',  $wpsc_purchase_log->id, false );
}

add_action( 'wpsc_purchase_log_insert', '_wpsc_set_purchase_log_customer_id', 10, 1 );

/**
 * Return the internal customer meta key, which depends on the blog prefix
 * if this is a multi-site installation.
 *
 * @since  3.8.13
 * @access private
 * @param  string $key Meta key
 * @return string      Internal meta key
 */
function _wpsc_get_customer_meta_key( $key ) {
	return _wpsc_get_visitor_meta_key( $key );
}

/**
 * Update the current customer's last active time
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_action_customer_used_cart() {

	do_action( '_wpsc_action_customer_used_cart' );

	// get the current users id
	$id = wpsc_get_current_customer_id();

	// go through the common update routine that allows any users last active time to be changed
	wpsc_set_visitor_expiration( $id , DAY_IN_SECONDS * 2 );

	// also extend cookie expiration
	_wpsc_create_customer_id_cookie( $id );
}

/**
 * Merge cart from anonymous user with cart from logged in user
 *
 * @since 3.8.13
 * @access private
 */
function _wpsc_merge_cart() {

	$id_from_wp_user = get_user_meta( get_current_user_id(), _wpsc_get_visitor_meta_key( 'visitor_id' ), true );

	if ( empty( $id_from_wp_user ) ) {
		return;
	}

	do_action( '_wpsc_merge_cart' );


	$id_from_customer_meta = wpsc_get_customer_meta( 'merge_cart_vistor_id' );
	wpsc_delete_customer_meta( 'merge_cart_vistor_id' );


	$old_cart = wpsc_get_customer_cart( $id_from_customer_meta );
	$items    = $old_cart->get_items();

	$new_cart = wpsc_get_customer_cart( $id_from_wp_user );

	// first of all empty the old cart so that the claimed stock and related
	// hooks are released
	$old_cart->empty_cart();

	// add each item to the new cart
	foreach ( $items as $item ) {
		$new_cart->set_item(
				$item->product_id, array(
						'quantity'         => $item->quantity,
						'variation_values' => $item->variation_values,
						'custom_message'   => $item->custom_message,
						'provided_price'   => $item->provided_price,
						'time_requested'   => $item->time_requested,
						'custom_file'      => $item->custom_file,
						'is_customisable'  => $item->is_customisable,
						'meta'             => $item->meta,
				)
		);
	}

	wpsc_update_customer_cart( $new_cart );

	// The old profile is no longer needed
	_wpsc_abandon_temporary_customer_profile( $id_from_customer_meta );

}


/**
 * Are we currently processing a non WPEC ajax request
 * @return boolean
 */
function _wpsc_doing_wpsc_ajax_request() {

	$doing_wpsc_ajax_request = false;

	// if the wpsc_ajax_action is set, it's a WPEC AJAX request
	if ( isset( $_REQUEST['wpsc_ajax_action'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['wpsc_update_quantity'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['update_shipping_price'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['get_cart'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['change_tax'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['change_profile_country'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['update_location'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( isset( $_REQUEST['shipping_same_as_billing_update'] ) ) {
		$doing_wpsc_ajax_request = true;
	}

	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

		// if the wpsc_ajax_action is set, it's a WPEC AJAX request
		if ( isset( $_REQUEST['action'] ) && ( strpos( $_REQUEST['action'], 'wpsc_' ) !== false ) ) {
			$doing_wpsc_ajax_request = true;
		}

		// this AJAX request is old and doesn't start with wpsc_
		if ( isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'update_product_price' ) ) {
			$doing_wpsc_ajax_request = true;
		}
	}

	return $doing_wpsc_ajax_request;
}

/**
 * Is the user an automata not worthy of a WPEC profile to hold shopping cart and other info
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_is_bot_user() {

	$is_bot = false;

	// if the customer cookie is invalid, unset it
	$visitor_id_from_cookie = _wpsc_validate_customer_cookie();
	if ( $visitor_id_from_cookie ) {
		return $visitor_id_from_cookie === WPSC_BOT_VISITOR_ID;
	}

	if ( ! is_user_logged_in() ) {

		// check for WordPress detected 404 or feed request
		if ( did_action( 'posts_selection' ) ) {
			if ( is_feed() ) {
				$is_bot = true;
			}

			if ( is_404() ) {
				$is_bot = true;
			}
		}

		// check for non WPEC ajax request, no reason to create a visitor profile if this is the case
		if ( ! $is_bot && ! _wpsc_doing_wpsc_ajax_request() ) {
			$is_bot = true;
		}

		if ( ! $is_bot && ( strpos( $_SERVER['REQUEST_URI'], '?wpsc_action=rss' ) ) ) {
			$is_bot = true;
		}

		// Cron jobs are not flesh originated
		if ( ! $is_bot && ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			$is_bot = true;
		}

		// XML RPC requests are probably from cybernetic beasts
		if ( ! $is_bot && ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			$is_bot = true;
		}

		// coming to login first, after the user logs in we know they are a live being, until then they are something else
		if ( ! $is_bot && ( strpos( $_SERVER['PHP_SELF'], 'wp-login' ) || strpos( $_SERVER['PHP_SELF'], 'wp-register' ) ) ) {
			$is_bot = true;
		}

		if ( ! $is_bot && ( ! empty( $_SERVER['HTTP_USER_AGENT']) ) ) {

			// the user agent could be google bot, bing bot or some other bot,  one would hope real user agents do not have the
			// string 'bot|spider|crawler|preview' in them, there are bots that don't do us the kindness of identifying themselves as such,
			// check for the user being logged in in a real user is using a bot to access content from our site
			$bot_agent_strings = array( 'robot', 'bot', 'crawler', 'spider', 'preview', 'WordPress', );
			$bot_agent_strings = apply_filters( 'wpsc_bot_user_agents', $bot_agent_strings );

			foreach ( $bot_agent_strings as $bot_agent_string ) {
				if ( stripos( $_SERVER['HTTP_USER_AGENT'], $bot_agent_string ) !== false ) {
					$is_bot = true;
					break;
				}
			}
		}
	}

	$is_bot = apply_filters( 'wpsc_is_bot_user', $is_bot );

	return $is_bot;
}

