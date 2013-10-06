<?php

add_action( 'wpsc_set_cart_item'         , '_wpsc_update_customer_last_active' );
add_action( 'wpsc_add_item'              , '_wpsc_update_customer_last_active' );
add_action( 'wpsc_before_submit_checkout', '_wpsc_update_customer_last_active' );

/**
 * Helper function for setting the customer cookie content and expiration
 *
 * @since  3.8.13
 * @access private
 * @param  mixed $cookie  Cookie data
 * @param  int   $expire  Expiration timestamp
 */
function _wpsc_set_customer_cookie( $cookie, $expire ) {
	$secure = is_ssl();
	setcookie( WPSC_CUSTOMER_COOKIE, $cookie, $expire, WPSC_CUSTOMER_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );

	if ( $expire < time() )
		unset( $_COOKIE[WPSC_CUSTOMER_COOKIE] );
	else
		$_COOKIE[WPSC_CUSTOMER_COOKIE] = $cookie;
}

/**
 * In case the user is not logged in, create a new user account and store its ID
 * in a cookie
 *
 * @access public
 * @since 3.8.9
 * @return string Customer ID
 */
function wpsc_create_customer_id() {
	static $cached_current_customer_id = false;
	global $wp_roles;

	if ( $cached_current_customer_id !== false ) {
		return $cached_current_customer_id;
	}

	if ( $is_a_bot_user = wpsc_is_bot_user() ) {
		$username = '_wpsc_bot';
		$wp_user = get_user_by( 'login', $username );
		if ( $wp_user === false ) {
			$password = wp_generate_password( 12, false );
			$id = wp_create_user( $username, $password );
		} else {
			$id = $wp_user->ID;
		}
	} else {
		if ( !($id =_wpsc_recently_created_user()) ) {
			$username = '_' . wp_generate_password( 8, false, false );
			$password = wp_generate_password( 12, false );

			$role = $wp_roles->get_role( 'wpsc_anonymous' );

			if ( ! $role )
				$wp_roles->add_role( 'wpsc_anonymous', __( 'Anonymous', 'wpsc' ) );

			$id = wp_create_user( $username, $password );
			$user = new WP_User( $id );
			$user->set_role( 'wpsc_anonymous' );

			update_user_meta( $id, '_wpsc_last_active', time() );
			update_user_meta( $id, '_wpsc_temporary_profile', 48 ); // 48 hours, cron job to delete will tick once per hour
			update_user_meta( $id, _wpsc_user_hash_meta_key(), microtime( true ) );
		}
	}


	// set cookie for all live users
	if ( !wpsc_is_bot_user() ) {
		$expire = time() + WPSC_CUSTOMER_DATA_EXPIRATION; // valid for 48 hours
		$data = $id . $expire;
		$hash = hash_hmac( 'md5', $data, wp_hash( $data ) );
		$cookie = $id . '|' . $expire . '|' . $hash;

		// store ID, expire and hash to validate later
		_wpsc_set_customer_cookie( $cookie, $expire );
	}

	$cached_current_customer_id = $id;

	return $id;
}

/**
 * Make sure the customer cookie is not compromised.
 *
 * @access public
 * @since 3.8.9
 * @return mixed Return the customer ID if the cookie is valid, false if otherwise.
 */
function wpsc_validate_customer_cookie() {
	static $validated_user_id = false;

	// we hold on to the validated user id once we have it becuase this function might
	// be called many times per url request.
	if ( $validated_user_id !== false )
		return $validated_user_id;

	$cookie = $_COOKIE[WPSC_CUSTOMER_COOKIE];
	list( $id, $expire, $hash ) = $x = explode( '|', $cookie );
	$data = $id . $expire;
	$hmac = hash_hmac( 'md5', $data, wp_hash( $data ) );

	if ( ($hmac != $hash) || empty( $id ) || !is_numeric($id)) {
		return false;
	} else {
		// check to be sure the user still exists, could have been purged
		$id = intval( $id );
		$wp_user = get_user_by( 'id', $id );
		if ( $wp_user === false ) {
			return false;
		}
	}

	$validated_user_id = $id;
	return $id;
}

/**
 * Get current customer ID.
 *
 * If the user is logged in, return the user ID. Otherwise return the ID associated
 * with the customer's cookie.
 *
 * If $mode is set to 'create', WPEC will create the customer ID if it hasn't
 * already been created yet.
 *
 * @access public
 * @since 3.8.9
 * @return mixed        User ID (if logged in) or customer cookie ID
 */
function wpsc_get_current_customer_id() {
	// if the user is logged in and the cookie is still there, delete the cookie
	if ( is_user_logged_in() && isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) )
		_wpsc_set_customer_cookie( '', time() - 3600 );

	// if the user is logged in we use the user id
	if ( is_user_logged_in() ) {
		return get_current_user_id();
	} elseif ( isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) ) {
		// check the customer cookie, get the id, or if that doesn't work move on and create the user
		$id = wpsc_validate_customer_cookie();
		if ( $id != false )
			return $id;
	}

	return wpsc_create_customer_id();
}

/**
 * Setup current user object and customer ID as well as cart.
 *
 * @uses  do_action() Calls 'wpsc_setup_customer' after customer data is ready
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_action_setup_customer() {
	wpsc_get_current_customer_id();
	wpsc_core_setup_cart();
	do_action( 'wpsc_setup_customer' );
}

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
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	return "{$blog_prefix}_wpsc_{$key}";
}

/**
 * Delete all customer meta for a certain customer ID
 *
 * @since  3.8.9.4
 * @param  string|int $id Customer ID. Optional. Defaults to current customer
 * @return boolean        True if successful, False if otherwise
 */
function wpsc_delete_all_customer_meta( $id = false ) {
	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	$meta = get_user_meta( $id );
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	$key_pattern = "{$blog_prefix}_wpsc_";
	$success = true;

	foreach ( $meta as $key => $value ) {
		if ( strpos( $key, $key_pattern ) === 0 )
			$success = $success && delete_user_meta( $id, $key );
	}

	return $success;
}

/**
 * Delete customer meta.
 *
 * @access public
 * @since  3.8.9
 * @param  string     $key  Meta key
 * @param  string|int $id   Customer ID. Optional. Defaults to current customer.
 * @return boolean|WP_Error True if successful. False if not successful. WP_Error
 *                          if there are any errors.
 */
function wpsc_delete_customer_meta( $key, $id = false ) {
	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	return delete_user_meta( $id, _wpsc_get_customer_meta_key( $key ) );
}

/**
 * Update a customer meta.
 *
 * @access public
 * @since  3.8.9
 * @param  string     $key   Meta key
 * @param  mixed      $value Meta value
 * @param  string|int $id    Customer ID. Optional. Defaults to current customer.
 * @return boolean|WP_Error  True if successful, false if not successful, WP_Error
 *                           if there are any errors.
 */
function wpsc_update_customer_meta( $key, $value, $id = false ) {
	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	return update_user_meta( $id, _wpsc_get_customer_meta_key( $key ), $value );
}

/**
 * Overwrite customer meta with an array of meta_key => meta_value.
 *
 * @access public
 * @since  3.8.9
 * @param  array      $profile Customer meta array
 * @param  int|string $id      Customer ID. Optional. Defaults to current customer.
 * @return boolean             True if meta values are updated successfully. False
 *                             if otherwise.
 */
function wpsc_update_all_customer_meta( $profile, $id = false ) {
	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	wpsc_delete_all_customer_meta( $id );
	$success = true;

	foreach ( $profile as $key => $value ) {
		$success = $success && wpsc_update_customer_meta( $key, $value, $id );
	}

	return $success;
}

/**
 * Get a customer meta value.
 *
 * @access public
 * @since  3.8.9
 * @param  string  $key Meta key
 * @param  int|string $id  Customer ID. Optional, defaults to current customer
 * @return mixed           Meta value, or null if it doesn't exist or if the
 *                         customer ID is invalid.
 */
function wpsc_get_customer_meta( $key = '', $id = false ) {
	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	return get_user_meta( $id, _wpsc_get_customer_meta_key( $key ), true );
}

/**
 * Return an array containing all metadata of a customer
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id Customer ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_get_all_customer_meta( $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	$meta = get_user_meta( $id );
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	$key_pattern = "{$blog_prefix}_wpsc_";

	$return = array();

	foreach ( $meta as $key => $value ) {
		if ( strpos( $key, $key_pattern ) === FALSE )
			continue;

		$short_key = str_replace( $key_pattern, '', $key );
		$return[$short_key] = $value[0];
	}

	return $return;
}

/**
 * Update the customer's last active time
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_update_customer_last_active() {
	$id = wpsc_get_current_customer_id();
	update_user_meta( $id, '_wpsc_last_active', time() );
	$meta_value = get_user_meta($id, '_wpsc_temporary_profile', true);
	if ( !empty( $meta_value ) )
		update_user_meta( $id, '_wpsc_temporary_profile', 48 );
}


/**
 * Is the user an automata not worthy of a WPEC profile to hold shopping cart and other info
 *
 * @access public
 * @since  3.8.13
 */
function wpsc_is_bot_user() {

	static $is_a_bot_user = null;

	if ( $is_a_bot_user !== null )
		return $is_a_bot_user;

	if ( is_user_logged_in() ) {
		$is_a_bot_user = false;
		return false;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], '?wpsc_action=rss' ) ) {
		$is_a_bot_user = true;
		return true;
	}

	// Cron jobs are not flesh originated
	if ( defined('DOING_CRON') && DOING_CRON ) {
		$is_a_bot_user = true;
		return true;
	}

	// XML RPC requests are probably from cybernetic beasts
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
		$is_a_bot_user = true;
		return true;
	}

	// coming to login first, after the user logs in we know they are a live being, until then they are something else
	if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login' ) || strpos( $_SERVER['REQUEST_URI'], 'wp-register' ) ) {
		$is_a_bot_user = true;
		return true;
	}

	// even web servers talk to themselves when they think no one is listening
	if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wordpress' ) !== false ) {
		$is_a_bot_user = true;
		return true;
	}

	// the user agent could be google bot, bing bot or some other bot,  one would hope real user agents do not have the
	// string 'bot|spider|crawler|preview' in them, there are bots that don't do us the kindness of identifying themselves as such,
	// check for the user being logged in in a real user is using a bot to access content from our site
	if ( !is_user_logged_in() && (
			( stripos( $_SERVER['HTTP_USER_AGENT'], 'bot' ) !== false )
				|| ( stripos( $_SERVER['HTTP_USER_AGENT'], 'crawler' ) !== false )
					|| ( stripos( $_SERVER['HTTP_USER_AGENT'], 'spider' ) !== false )
						|| ( stripos( $_SERVER['HTTP_USER_AGENT'], 'Preview' ) !== false )
		) ) {
		$is_a_bot_user = true;
		return true;
	}

	// Are we feeding the masses?
	if ( is_feed() ) {
		$is_a_bot_user = true;
		return true;
	}

	$is_a_bot_user = false;

	// at this point we have eliminated all but the most obvious choice, a human (or cylon?)
	return false;
}


/**
 * Attach a purchase log to our customer profile
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_set_purchase_log_customer_id( $data ) {

	// if there is a purchase log for this user we don't want to delete the
	// user id, even if the transaction isn't successful.  there may be useful
	// information in the customer profile related to the transaction
	wpsc_delete_customer_meta('_wpsc_temporary_profile');

	// if there isn't already user id we set the user id of the current customer id
	if ( empty ( $data['user_ID'] ) ) {
		$id = wpsc_get_current_customer_id();
		$data['user_ID'] = $id;
	}

	return $data;
}

if ( !is_user_logged_in() ) {
	add_filter( 'wpsc_purchase_log_update_data', '_wpsc_set_purchase_log_customer_id', 1, 1 );
	add_filter( 'wpsc_purchase_log_insert_data', '_wpsc_set_purchase_log_customer_id', 1, 1 );
}


/**
 * Create a hash from what we know about a user's connection to try to determine if it is unique
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_user_hash_meta_key() {
	$user_hash_meta_key = '_wpsc_' . md5( $_SERVER['REMOTE_ADDR'] .  $_SERVER['HTTP_USER_AGENT'] );
	return $user_hash_meta_key;
}

/**
 * Check users with similar information to see if they were created in the last
 * milliseconds so that we don't create two users when two requests come to the server
 * in parallel.
 *
 * Why do we do this?
 * WPEC creates a user profile for each visitor at the start of each visit. The user profile is used
 * to hold information like the cart contents, shipping data, checkout errors, or anything that a WPEC
 * aware plug-in may wish to save with the user.
 *
 * Creating the profile as soon as the user starts a visit has some advantages over waiting
 * until there is data to save before creating the profile. Mostly it allows code to be written
 * knowing that the user visit information can be saved to the profile without worrying about if
 * any special initialization has taken place.
 *
 * It also has some disadvantages that need to be addressed. In addition to detecting if a visit is
 * some type of bot, handled in _wpsc_is_bot, we need to make sure multiple profiles are not
 * inadvertently created.  How can this happen?
 *
 * Consider this common scenario.  WPEC based site is built and used page caching, a page that is cached is
 * is delivered to a real user.  When that page is delivered Wordpress/WPEC typically is unaware that anything
 * has taken place because the cache software/hardware has done all of the communication with the user's browser.
 *
 * The browser parses and processes the cached page HTML and java script.  When the page is processed there are
 * embedded AJAX calls, or other HTTP requests that are serviced by WPEC/Wordpress.  Modern browsers make the requests
 * to the server in parallel.  THat means that a web server might have as many as 4-8 requests working at the same time,
 * none of which has the WPEC customer cookie set.
 *
 * Without some means of detecting that each of these requests is coming from the same live user, a new user profile would
 * be created for each request, and a unique customer cookie would be set in each request.  That's
 * kind of messy.  It also could cause a problem if one of the HTTP requests coming to the server was an add to cart
 * operation.  An item could be added to the cart, show on the users screen as in the cart, but not be there when the
 * user goes to checkout because the cookie from a different request was what was ultimately set in the user's
 * web browser. Keep in mind that hte JAX requests that create a user profile don't have to be WPEC requests. They
 * could be requests from any plugin, doing anything that the plug-in intended.
 *
 * We are limited in what we can do to detect a common source for multiple requests. We look at the originating
 * IP address, the user agent string and the time.  If the user agent and the ip address are the same, and the time
 * is within half a second of a previous create profile request we treat the requests as coming from the same user.
 *
 *  When does this fail? Two users both behind the same caching proxy, or NAT firewall, who both go to the same website,
 *  and the pages they go to are cached, and they do it at almost exactly the same time.
 *
 * @access private
 * @since  3.8.13
 */
function _wpsc_recently_created_user() {
	global $wpdb;

	$recently_created_user_id = false;

	$sql = 'SELECT user_id, meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = "' . _wpsc_user_hash_meta_key() . '"';

	$similiar_users = $wpdb->get_results( $sql );

	$now = microtime( true );
	foreach ( $similiar_users as $similiar_user ) {
		$then = floatval( $similiar_user->meta_value );
		$howlong = $now - $then;
		if ( $howlong < 0.5 ) { // one half second
			$recently_created_user_id = $similiar_user->user_id;
			break;
		}
	}

	return $recently_created_user_id;
}
