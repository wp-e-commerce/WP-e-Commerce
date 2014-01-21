<?php

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

	$success = delete_user_meta( $id, _wpsc_get_customer_meta_key( $key ) );

	// notification after any meta item has been deleted
	if ( $success && has_action( $action = 'wpsc_deleted_customer_meta' ) ) {
		do_action( $action, $key, $id );
	}

	// notification after a specific meta item has been deleted
	if ( $success && has_action( $action = 'wpsc_deleted_customer_meta_' . $key  ) ) {
		do_action( $action, $key, $id );
	}

	return $success;
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

	if ( $result ) {
		return $result;
	}

	$result = update_user_meta( $id, _wpsc_get_customer_meta_key( $key ), $value );

	// notification after any meta item has been updated
	if ( $result && has_action( $action = 'wpsc_updated_customer_meta' ) ) {
		do_action( $action, $value, $key, $id );
	}

	// notification after a specific meta item has been updated
	if ( $result && has_action( $action = 'wpsc_updated_customer_meta_' . $key  ) ) {
		do_action( $action, $value, $key, $id );
	}

	return $result;
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

	if ( $result ) {
		return $result;
	}

	wpsc_delete_all_customer_meta( $id );
	$result = true;

	foreach ( $profile as $key => $value ) {
		$result = $result && wpsc_update_customer_meta( $key, $value, $id );
	}

	return $result;
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

	if ( $result ) {
		return $result;
	}

	$meta_value = get_user_meta( $id, _wpsc_get_customer_meta_key( $key ), true );

	// notification when any meta item is retrieved
	if ( has_filter( $filter = 'wpsc_got_customer_meta' ) ) {
		$meta_value = apply_filters( $filter,  $meta_value, $key, $id );
	}

	// notification when a specific meta item is retrieved
	if ( has_filter( $filter = 'wpsc_got_customer_meta_' . $key  ) ) {
		$meta_value = apply_filters( $filter,  $meta_value, $key, $id );
	}

	return $meta_value;
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

	if ( $result ) {
		return $result;
	}

	$meta        = get_user_meta( $id );
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	$key_pattern = "{$blog_prefix}_wpsc_";

	$return = array();

	foreach ( $meta as $key => $value ) {
		if ( strpos( $key, $key_pattern ) === FALSE )
			continue;

		$short_key = str_replace( $key_pattern, '', $key );
		$return[$short_key] = $value[0];

		// notification when a specific meta item has changed
		if ( has_filter( $filter = 'wpsc_got_customer_meta_' . $short_key  ) ) {
			$return[$short_key] = apply_filters( $filter,  $return[$short_key], $short_key, $id );
		}
	}

	return $return;
}



/**
 * Return an the customer cart
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id Customer ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_get_customer_cart( $id = false  ) {

	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	$cart = maybe_unserialize( base64_decode( wpsc_get_customer_meta( 'cart', $id ) ) );

	if ( ! ($cart instanceof wpsc_cart) ) {
		$cart = new wpsc_cart();
	}

	return $cart;
}


/**
 * Update a customers cart
 * @access public
 * @since 3.8.9
 * @param string $id
 * @param unknown $cart
 * @return boolean
 */
function wpsc_update_customer_cart( $id = false, $cart ) {
	global $wpdb, $wpsc_start_time, $wpsc_cart;

	if ( !is_a( $cart, 'wpsc_cart' ) )
		return false;

	if ( $id == wpsc_get_current_customer_id() ) {
		$wpsc_cart = $cart;
	}

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	wpsc_update_customer_meta( 'cart', base64_encode( serialize( $cart ) ) , $id );

	$wpsc_cart->clear_cache(); // do this to fire off actions that happen when a cart is changed

	return true;
}


/**
 * Update the customer's last active time
 *
 * Last active time is automatically set for certain AJAX transactions (see customer.php) but
 * can be updated manually for specific  customer id as necessary in admin or plugin logic
 *
 * @param string $id     the customer id.
 * @access public
 * @since  3.8.13
 * @return int customer id
 */
function wpsc_update_customer_last_active( $id = false ) {

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	wpsc_update_customer_meta( 'last_active', $last_active = time(), $id );

	// if there is a temporary profile value we update it with a new time
	$temporary_profile = wpsc_get_customer_meta( 'temporary_profile',  $id  );
	if ( ! empty( $temporary_profile ) ) {
		wpsc_update_customer_meta( 'last_active', $last_active + 48 * 60 * 60, $id );
	}

	return $id;
}


