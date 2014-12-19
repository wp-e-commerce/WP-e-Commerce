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

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	$result = apply_filters( 'wpsc_delete_all_customer_meta', null, $id );

	if ( $result ) {
		return $result;
	}

	$meta = wpsc_get_all_customer_meta( $id );

	$success = false;

	foreach ( $meta as $key => $value ) {
		$success = wpsc_delete_visitor_meta( $id, $key );
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
	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	$result = apply_filters( 'wpsc_delete_customer_meta', null, $key, $id );

	if ( $result ) {
		return $result;
	}

	return wpsc_delete_visitor_meta( $id, $key );
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

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	$result = apply_filters( 'wpsc_update_customer_meta', null, $key, $value, $id );

	if ( $result ) {
		return $result;
	}

	return wpsc_update_visitor_meta( $id, $key, $value );
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

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

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

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	// a filter to override meta get prior to retrieving the value
	$meta_value = apply_filters( 'wpsc_get_customer_meta', null, $key, $id );
	if ( $meta_value ) {
		return $meta_value;
	}

	return wpsc_get_visitor_meta( $id, $key, true );
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

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	$result = apply_filters( 'wpsc_get_all_customer_meta', null, $id );

	if ( $result ) {
		return $result;
	}

	$meta        = wpsc_get_visitor_meta( $id );
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
 * Return an the customer cart
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id Customer ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_get_customer_cart( $id = false  ) {
	global $wpsc_cart;

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	// if we are using the current visitors cart then we have a global to use
	if ( $id == wpsc_get_current_customer_id() ) {
		if ( empty( $wpsc_cart ) ) {
			$wpsc_cart = wpsc_get_visitor_cart( $id );
		}

		return $wpsc_cart;
	} else {
		return wpsc_get_visitor_cart( $id );
	}
}


/**
 * Update a customers cart
 *
 * @access public
 * @since 3.8.14
 * @param unknown $cart
 * @param int $id
 *
 * @return boolean
 */
function wpsc_update_customer_cart( $cart, $id = false ) {
	global $wpsc_cart;

	if ( ! is_a( $cart, 'wpsc_cart' ) ) {
		return false;
	}

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	if ( $id == wpsc_get_current_customer_id() ) {
		$wpsc_cart = $cart;
	}

	wpsc_update_visitor_cart( $id , $cart );

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
 * @return int
 */
function wpsc_update_customer_last_active( $id = false ) {

	if ( ! $id ) {
		$id = wpsc_get_current_customer_id();
	}

	wpsc_set_visitor_last_active( $id );

	return $id;
}