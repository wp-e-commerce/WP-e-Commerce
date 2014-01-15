<?php

add_action( 'wpsc_set_cart_item'         , '_wpsc_action_update_customer_last_active'     );
add_action( 'wpsc_add_item'              , '_wpsc_action_update_customer_last_active'     );
add_action( 'wpsc_before_submit_checkout', '_wpsc_action_update_customer_last_active'     );
add_action( 'wp_login'                   , '_wpsc_action_setup_customer'                  );
add_action( 'load-users.php'             , '_wpsc_action_load_users'                      );
add_filter( 'views_users'                , '_wpsc_filter_views_users'                     );
add_filter( 'editable_roles'             , '_wpsc_filter_editable_roles'                  );

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
function _wpsc_create_customer_id() {

	$role = get_role( 'wpsc_anonymous' );

	if ( ! $role ) {
		add_role( 'wpsc_anonymous', __( 'Anonymous', 'wpsc' ) );
	}

	$username = '_' . wp_generate_password( 8, false, false );
	$password = wp_generate_password( 12, false );

	$id   = wp_create_user( $username, $password );
	$user = new WP_User( $id );
	$user->set_role( 'wpsc_anonymous' );

	update_user_meta( $id, '_wpsc_last_active', time() );

	_wpsc_create_customer_id_cookie( $id );

	return $id;
}

/**
 * Set up a dummy user account for bots.
 *
 * This is not an ideal solution but it prevents third party plugins from failing
 * because they rely on the customer meta being there no matter whether this request
 * is by a bot or not.
 *
 * @since 3.8.13
 * @access private
 */
function _wpsc_maybe_setup_bot_user() {
	if ( ! _wpsc_is_bot_user() )
		return;

	$username = '_wpsc_bot';
	$wp_user  = get_user_by( 'login', $username );

	if ( $wp_user === false ) {
		$password = wp_generate_password( 12, false );
		$id       = wp_create_user( $username, $password );
		$user     = new WP_User( $id );
		$user->set_role( 'wpsc_anonymous' );
	} else {
		$id = $wp_user->ID;
	}

	// pretend that the cookie exists but don't actually need to use setcookie()
	_wpsc_create_customer_id_cookie( $id, true );

	return $id;
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

	$expire = time() + WPSC_CUSTOMER_DATA_EXPIRATION; // valid for 48 hours
	$data   = $id . $expire;

	$user      = get_user_by( 'id', $id );
	$pass_frag = substr( $user->user_pass, 8, 4 );

	$key = wp_hash( $user->user_login . $pass_frag . '|' . $expire );

	$hash   = hash_hmac( 'md5', $data, $key );
	$cookie = $id . '|' . $expire . '|' . $hash;

	// store ID, expire and hash to validate later
	if ( $fake_it )
		$_COOKIE[ WPSC_CUSTOMER_COOKIE ] = $cookie;
	else
		_wpsc_set_customer_cookie( $cookie, $expire );
}

/**
 * Make sure the customer cookie is not compromised.
 *
 * @access public
 * @since 3.8.9
 * @return mixed Return the customer ID if the cookie is valid, false if otherwise.
 */
function _wpsc_validate_customer_cookie() {

	if ( is_admin() || ! isset( $_COOKIE[ WPSC_CUSTOMER_COOKIE ] ) ) {
		return false;
	}

	$cookie = $_COOKIE[ WPSC_CUSTOMER_COOKIE ];
	list( $id, $expire, $hash ) = $x = explode( '|', $cookie );
	$data = $id . $expire;

	$id = intval( $id );

	// invalid ID
	if ( ! $id ) {
		return false;
	}

	$user = get_user_by( 'id', $id );

	// no user found
	if ( $user === false ) {
		return false;
	}

	$pass_frag = substr( $user->user_pass, 8, 4 );
	$key       = wp_hash( $user->user_login . $pass_frag . '|' . $expire );
	$hmac      = hash_hmac( 'md5', $data, $key );

	// integrity check
	if ( $hmac == $hash ) {
		return $id;
	}

	_wpsc_set_customer_cookie( '', time() - 3600 );
	return false;
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
function wpsc_get_current_customer_id() {
	$id = apply_filters( 'wpsc_get_current_customer_id', null );

	if ( ! empty( $id ) )
		return $id;

	// if the user is logged in we use the user id
	if ( is_user_logged_in() ) {
		return get_current_user_id();
	} elseif ( isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) ) {
		list( $id, $expire, $hash ) = explode( '|', $_COOKIE[WPSC_CUSTOMER_COOKIE] );
		return $id;
	}

	return _wpsc_create_customer_id();
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
	// if the customer cookie is invalid, unset it
	$id = _wpsc_validate_customer_cookie();

	// if a valid ID is present in the cookie, and the user is logged in,
	// it's time to merge the carts
	if ( isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) && is_user_logged_in() ) {
		// merging cart requires the taxonomies to have been initialized
		if ( did_action( 'wpsc_register_taxonomies_after' ) ) {
			_wpsc_merge_cart();
		}
		else {
			add_action( 'wpsc_register_taxonomies_after', '_wpsc_merge_cart', 1 );
		}
	}

	// if this request is by a bot, prevent multiple account creation
	_wpsc_maybe_setup_bot_user();

	// initialize customer ID if it's not already there
	wpsc_get_current_customer_id();

	// setup the cart and restore its items
	wpsc_core_setup_cart();

	do_action( 'wpsc_setup_customer' );
}

function _wpsc_merge_cart() {
	$old_id = _wpsc_validate_customer_cookie();

	if ( ! $old_id ) {
		return;
	}

	$new_id = get_current_user_id();

	$old_cart = wpsc_get_customer_cart( $old_id );
	$items    = $old_cart->get_items();

	$new_cart = wpsc_get_customer_cart( $new_id );

	// first of all empty the old cart so that the claimed stock and related
	// hooks are released
	$old_cart->empty_cart();

	// add each item to the new cart
	foreach ( $items as $item ) {
		$new_cart->set_item( $item->product_id, array(
			'quantity'         => $item->quantity,
			'variation_values' => $item->variation_values,
			'custom_message'   => $item->custom_message,
			'provided_price'   => $item->provided_price,
			'time_requested'   => $item->time_requested,
			'custom_file'      => $item->custom_file,
			'is_customisable'  => $item->is_customisable,
			'meta'             => $item->meta
		) );
	}

	require_once( ABSPATH . 'wp-admin/includes/user.php' );
	wp_delete_user( $old_id );

	_wpsc_set_customer_cookie( '', time() - 3600 );
}

function wpsc_get_customer_cart( $id = false ) {
	global $wpsc_cart;

	if ( ! empty( $wpsc_cart ) && ( ! $id || $id == wpsc_get_current_customer_id() ) )
		return $wpsc_cart;

	$cart = maybe_unserialize( base64_decode( wpsc_get_customer_meta( 'cart', $id ) ) );
	if ( empty( $cart ) || ! $cart instanceof wpsc_cart )
		$cart = new wpsc_cart();

	return $cart;
}

function wpsc_update_customer_cart( $cart, $id = false ) {
	if ( ! $id || $id == wpsc_get_current_customer_id() )
		return wpsc_serialize_shopping_cart();

	return wpsc_update_customer_meta( 'cart', base64_encode( serialize( $wpsc_cart ) ), $id );
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
	global $wpdb;

	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	return "{$blog_prefix}_wpsc_{$key}";
}

/**
 * Delete all customer meta for a certain customer ID.
 *
 * Implement your own system by hooking into 'wpsc_delete_all_customer_meta'.
 *
 * @since  3.8.9.4
 * @param  string|int $id Customer ID. Optional. Defaults to current customer
 * @return boolean        True if successful, False if otherwise
 */
function wpsc_delete_all_customer_meta( $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	$result = apply_filters( 'wpsc_delete_all_customer_meta', null, $id );

	if ( $result )
		return $result;

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
 * Implement your own system by hooking into 'wpsc_delete_customer_meta'.
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

	$result = apply_filters( 'wpsc_delete_customer_meta', null, $key, $id );

	if ( $result )
		return $result;

	return delete_user_meta( $id, _wpsc_get_customer_meta_key( $key ) );
}

/**
 * Update a customer meta.
 *
 * Implement your own system by hooking into 'wpsc_update_customer_meta'.
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

	$result = apply_filters( 'wpsc_update_customer_meta', null, $key, $value, $id );

	if ( $result )
		return $result;

	return update_user_meta( $id, _wpsc_get_customer_meta_key( $key ), $value );
}

/**
 * Overwrite customer meta with an array of meta_key => meta_value.
 *
 * Implement your own system by hooking into 'wpsc_update_all_customer_meta'.
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

	$result = apply_filters( 'wpsc_update_all_customer_meta', null, $profile, $id );

	if ( $result )
		return $result;

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
 * Implement your own system by hooking into 'wpsc_get_customer_meta'.
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

	$result = apply_filters( 'wpsc_get_customer_meta', null, $key, $id );

	if ( $result )
		return $result;

	return get_user_meta( $id, _wpsc_get_customer_meta_key( $key ), true );
}

/**
 * Return an array containing all metadata of a customer
 *
 * Implement your own system by hooking into 'wpsc_get_all_customer_meta'.
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

	$result = apply_filters( 'wpsc_get_all_customer_meta', null, $id );

	if ( $result )
		return $result;

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
function _wpsc_action_update_customer_last_active() {
	$id = wpsc_get_current_customer_id();

	$user = get_user_by( 'id', $id );
	if ( $user->role != 'wpsc_anonymous' )
		return;

	update_user_meta( $id, '_wpsc_last_active', time() );

	// also extend cookie expiration
	_wpsc_create_customer_id_cookie( $id );
}


/**
 * Is the user an automata not worthy of a WPEC profile to hold shopping cart and other info
 *
 * @access public
 * @since  3.8.13
 */
function _wpsc_is_bot_user() {
	if ( is_user_logged_in() )
		return false;

	if ( strpos( $_SERVER['REQUEST_URI'], '?wpsc_action=rss' ) )
		return true;

	// Cron jobs are not flesh originated
	if ( defined('DOING_CRON') && DOING_CRON )
		return true;

	// XML RPC requests are probably from cybernetic beasts
	if ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST )
		return true;

	// coming to login first, after the user logs in we know they are a live being, until then they are something else
	if ( strpos( $_SERVER['PHP_SELF'], 'wp-login' ) || strpos( $_SERVER['PHP_SELF'], 'wp-register' ) )
		return true;

	// even web servers talk to themselves when they think no one is listening
	if ( stripos( $_SERVER['HTTP_USER_AGENT'], 'wordpress' ) !== false )
		return true;

	// the user agent could be google bot, bing bot or some other bot,  one would hope real user agents do not have the
	// string 'bot|spider|crawler|preview' in them, there are bots that don't do us the kindness of identifying themselves as such,
	// check for the user being logged in in a real user is using a bot to access content from our site
	$bot_agents_patterns = apply_filters( 'wpsc_bot_user_agents', array(
		'robot',
		'bot',
		'crawler',
		'spider',
		'preview',
	) );

	$pattern = '/(' . implode( '|', $bot_agents_patterns ) . ')/i';

	if ( preg_match( $pattern, $_SERVER['HTTP_USER_AGENT'] ) )
		return true;

	// Are we feeding the masses?
	if ( is_feed() )
		return true;

	// at this point we have eliminated all but the most obvious choice, a human (or cylon?)
	return false;
}

/**
 * Given a users.php view's HTML code, this function returns the user count displayed
 * in the view.
 *
 * If `count_users()` had implented caching, we could have just called that function again
 * instead of using this hack.
 *
 * @access private
 * @since  3.8.13.2
 * @param  string $view
 * @return int
 */
function _wpsc_extract_user_count( $view ) {
	global $wp_locale;
	if ( preg_match( '/class="count">\((.+)\)/', $view, $matches ) ) {
		return absint( str_replace( $wp_locale->number_format['thousands_sep'], '', $matches[1] ) );
	}

	return 0;
}

/**
 * Filter the user views so that Anonymous role is not displayed
 *
 * @since  3.8.13.2
 * @access private
 * @param  array $views
 * @return array
 */
function _wpsc_filter_views_users( $views ) {
	if ( isset( $views['wpsc_anonymous'] ) ) {
		// ugly hack to make the anonymous users not count towards "All"
		// really wish WordPress had a filter in count_users(), but in the mean time
		// this will do
		$anon_count = _wpsc_extract_user_count( $views['wpsc_anonymous'] );
		$all_count = _wpsc_extract_user_count( $views['all'] );
		$new_count = $all_count - $anon_count;
		$views['all'] = preg_replace( '/class="count">\(.+\)/', 'class="count">(' . number_format_i18n( $new_count ) . ')', $views['all'] );
	}

	unset( $views['wpsc_anonymous'] );
	return $views;
}

/**
 * Add the action necessary to filter out anonymous users
 *
 * @since 3.8.13.2
 * @access private
 */
function _wpsc_action_load_users() {
	add_action( 'pre_user_query', '_wpsc_action_pre_user_query', 10, 1 );
}

/**
 * Filter out anonymous users in "All" view
 *
 * @since 3.8.13.2
 * @access private
 * @param  WP_User_Query $query
 */
function _wpsc_action_pre_user_query( $query ) {
	global $wpdb;

	// only do this when we're viewing all users
	if ( ! empty( $query->query_vars['role'] ) )
		return;

	// if the site is multisite, we need to do things a bit differently
	if ( is_multisite() ) {
		// on Network Admin, a JOIN with usermeta is not possible (some users don't have capabilities set, so we fall back to matching user_login, although this is not ideal)
		if ( empty( $query->query_vars['blog_id'] ) ) {
			$query->query_where .= " AND $wpdb->users.user_login NOT LIKE '\_________'";
		} else {
			$query->query_where .= " AND CAST($wpdb->usermeta.meta_value AS CHAR) NOT LIKE '%" . like_escape( '"wpsc_anonymous"' ) . "%'";
		}
		return;
	}

	$cap_meta_query = array(
		array(
			'key'     => $wpdb->get_blog_prefix( $query->query_vars['blog_id'] ) . 'capabilities',
			'value'   => '"wpsc_anonymous"',
			'compare' => 'not like',
		)
	);

	$meta_query = new WP_Meta_Query( $cap_meta_query );
	$clauses = $meta_query->get_sql( 'user', $wpdb->users, 'ID', $query );

	$query->query_from .= $clauses['join'];
	$query->query_where .= $clauses['where'];
}

/**
 * Make sure Anonymous role not editable
 *
 * @since 3.8.13.2
 * @param  array $editable_roles
 * @return array
 */
function _wpsc_filter_editable_roles( $editable_roles ) {
	unset( $editable_roles['wpsc_anonymous'] );
	return $editable_roles;
}