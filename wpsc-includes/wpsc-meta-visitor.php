<?php

require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-visitor.class.php' );

/*
** WPEC Visitor API
*/


function _wpsc_visitor_database_ready() {
	static $visitor_database_checked = false;
	static $visitor_database_ready = false;

	if ( $visitor_database_checked ) {
		return $visitor_database_ready;
	}

	if ( get_option( 'wpsc_db_version', 0 ) >= 10 ) {
		global $wpdb;

		$visitor_database_ready = ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->wpsc_visitors'" )
										&& $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->wpsc_visitormeta'" ) );

	}

	$visitor_database_checked = true;

	return $visitor_database_ready;
}

/**
 * Return the internal visitor meta key for meta values internal to WPEC
 * This helps distinguish private meta added by WPEC from public meta or
 * meta added by third parties
 *
 * @since  3.8.14
 * @access private
 * @param  string $key Meta key
 * @return string      Internal meta key
 */
function _wpsc_get_visitor_meta_key( $key ) {
	return "_wpsc_{$key}";
}


/**
 * Return the internal user meta key, which depends on the blog prefix
 * if this is a multi-site installation.  This helps distinguish meta added
 * by WPEC fromn meta added by third parties
 *
 * @since  3.8.14
 * @access private
 * @param  string $key Meta key
 * @return string      Internal meta key
 */
function _wpsc_get_user_meta_key( $key ) {
	global $wpdb;
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	return "{$blog_prefix}_wpsc_{$key}";
}


/**
 * Creates a WPEC visitor
 *
 * @since 3.8.14
 * @access public
 * @param array (optional) visitor attributes to use when creating new visitor
 * @return int | boolean visitor id or false on failure
 */
function wpsc_create_visitor( $args = null ) {
	global $wpdb;

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$new_visitor_id = false;

	// set user id
	if ( ! is_array( $args ) || empty( $args ) ) {
		$args = array( 'user_id' => null );
	}

	// set last active time
	if ( ! isset( $args['last_active'] ) ) {
		$args['last_active'] = date( 'Y-m-d H:i:s' );
	}

	// set created time
	if ( ! isset( $args['created'] ) ) {
		$args['created'] = date( 'Y-m-d H:i:s' );
	}

	// new visitor profiles expire in two hours
	if ( ! isset( $args['user_id'] ) && ! isset( $args['expires'] ) ) {
		$args['expires'] = $timestamp = date( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS );
	}

	// visitor profiles associated with wordpress user never expire
	if ( isset( $args['user_id'] ) &&  isset( $args['expires'] ) ) {
		unset( $args['expires'] );
	}

	// create a visitor record and get the row id
	$result = $wpdb->insert( $wpdb->wpsc_visitors, $args );
	if ( $result !== false ) {
		$new_visitor_id = $wpdb->insert_id;

		// create a security id, we store this in meta because meta has caching courtesy of wordpress!
		$security_id = '_' . wp_generate_password( 12, false, false );

		wpsc_update_visitor_meta( $new_visitor_id, _wpsc_get_visitor_meta_key( 'key' ), $security_id );
	}

	if ( isset( $args['user_id'] ) && is_numeric( $args['user_id'] ) && ( $args['user_id'] != 0 ) ) {
		$wp_user_id = intval( $args['user_id'] );
		_wpsc_update_wp_user_visitor_id( $wp_user_id, $new_visitor_id );
	}

	do_action( 'wpsc_created_visitor', $new_visitor_id, $args );

	return $new_visitor_id;
}

/**
 * Get the well known visitor information
 * @access private
 * @since 3.8.14
 * @param unknown $visitor_id
 * @return object with visitor properties, false on failure
 */
function _wpsc_get_visitor( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;
	$visitor_row = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->wpsc_visitors . ' WHERE id = ' . $visitor_id, OBJECT );
	if ( $visitor_row === NULL ) {
		$visitor_row = false;
	}

	return $visitor_row;
}

/**
 * Updates the WPEC visitor id associated with a WordPress user
 * @access private
 * @since 3.8.14
 * @param unknown $wp_user
 * @param unknown $visitor_id
 */
function _wpsc_update_wp_user_visitor_id( $wp_user_id, $visitor_id ) {
	return update_user_meta( $wp_user_id, _wpsc_get_user_meta_key( 'visitor_id' ), $visitor_id );
}



/**
 * Gets a valid WordPress User ID associated weith a WPEC visitor
 * @access private
 * @since 3.8.14
 * @param int $visitor_id
 */
function wpsc_get_visitor_wp_user_id( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;

	$wp_user_id = false;

	if ( ! empty( $visitor_id ) ) {
		$wp_user_id = $wpdb->get_var( 'SELECT user_id FROM ' . $wpdb->wpsc_visitors . ' WHERE id = ' . $visitor_id );
		if ( $wp_user_id === NULL ) {
			$wp_user_id = false;
		}
	}


	return $wp_user_id;
}


/**
 * Gets a valid WPEC visitor id associated with a WordPress user
 * @access private
 * @since 3.8.14
 * @param unknown $wp_user
 */
function _wpsc_get_wp_user_visitor_id( $wp_user_id = null ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$visitor_id = false;

	if ( empty( $wp_user_id ) ) {
		$wp_user_id = get_current_user_id();
	}

	if ( ! empty( $wp_user_id ) ) {

		$visitor_id = get_user_meta( $wp_user_id, _wpsc_get_user_meta_key( 'visitor_id' ), true );

		if ( empty ( $visitor_id ) ) {
			$visitor_id = wpsc_create_visitor( array( 'user_id' => $wp_user_id ) );
		}
	}

	return $visitor_id;
}

/**
 * Gets the last active time for a visitor
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to check
 * @return last active timestamp, or false on failure.
 */
function wpsc_get_visitor_last_active( $visitor_id = null ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;

	$last_active = false;

	if ( ! empty( $visitor_id ) ) {
		$last_active_string = $wpdb->get_var( 'SELECT last_active FROM ' . $wpdb->wpsc_visitors . ' WHERE id = ' . $visitor_id );
		if ( $last_active_string !== NULL ) {
			$last_active = strtotime( $last_active_string );
		}
	}

	return $last_active;
}

/**
 * Sets the last active time for a visitor
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to check
 * @return last active timestamp, or false on failure.
 */
function wpsc_set_visitor_last_active( $visitor_id, $timestamp = null ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;

	// if are explicitly setting the last active to a fixed time that's all we need to do,  if we are setting it to the
	// current time also change the visitor profile expiration
	if ( ! empty( $timestamp ) ) {
		if ( is_numeric( $timestamp ) ) {
			$last_active = date( 'Y-m-d H:i:s' , $timestamp );
		} else {
			$last_active = $timestamp;
		}

		$wpdb->query( 'UPDATE ' . $wpdb->wpsc_visitors . ' SET last_active = "' . $timestamp . '" WHERE id = ' . $visitor_id );

		if ( $wpdb->rows_affected !== 1 ) {
			$last_active = false;
		}
	} else {
		wpsc_set_visitor_expiration( $visitor_id, 2 * DAY_IN_SECONDS );
		$last_active = date( 'Y-m-d H:i:s' , $timestamp );
	}

	return $last_active;
}

/**
 * update visitor profile expiration time
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to update
 * @param int | seconds from now when the user profile should expire, null removes expiration,
 * @return current expiration time, false on no expiration
 */
function wpsc_set_visitor_expiration( $visitor_id, $expires_in_time = null ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	// visitors associated with wordpress users never expire
	if ( ( $expires_in_time === null ) || wpsc_get_visitor_wp_user_id( $visitor_id ) ){
		wpsc_visitor_remove_expiration( $visitor_id );
		$result = false;
	} else {
		global $wpdb;
		$expires_timestamp = $timestamp = date( 'Y-m-d H:i:s', $result = ( time() + $expires_in_time) );
		$wpdb->update( $wpdb->wpsc_visitors, array(	'expires' => $expires_timestamp, 'last_active' => date( 'Y-m-d H:i:s' ), ), array( 'ID' => $visitor_id ) );
	}

	return $result;
}

/**
 * Remove
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to update
 * @param int | seconds from now when the user profile should expire, null removes expiration,
 * @return current expiration time, false on no expiration
 */
function wpsc_visitor_remove_expiration( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;
	$wpdb->query( 'UPDATE ' . $wpdb->wpsc_visitors . ' SET expires = NULL, last_active = "' .  date( 'Y-m-d H:i:s' ) . '" WHERE id = ' . $visitor_id );
	return true;
}

/**
 * Is the visitor profile going to expire
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to check
 * @return boolean true if visitor profile will expire, false if it is permanent
 */
function wpsc_visitor_profile_expires( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$expiration = $wpdb->get_var( ' SELECT expires FROM ' . $wpdb->wpsc_visitors . ' WHERE id = ' . $visitor_id );
	return ! empty ( $expiration );
}

/**
 * Current visitor expiration time
 *
 * @since 3.8.14
 * @param $visitor_id int visitor id to check
 * @return int unix timestamp of expiration
 */
function wpsc_get_visitor_expiration( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$expiration = $wpdb->get_var( ' SELECT expires FROM ' . $wpdb->wpsc_visitors . ' WHERE id = ' . $visitor_id );

	if ( ! empty( $expiration ) ) {
		$expiration = strtotime( $expiration );
	} else {
		$expiration = false;
	}

	return $expiration;
}


/**
 * Gets the security key associated with a WPEC visitor
 *
 * @access private
 * @param int $visitor_id
 * @return string security key created when the visitor was created
 */
function _wpsc_visitor_security_key( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return wpsc_get_visitor_meta( $visitor_id, _wpsc_get_visitor_meta_key( 'key' ), true );
}

/**
 * Creates a WPEC visitor
 *
 * @since 3.8.14
 *
 * @param int (optional) WPEC visitor id to update
 * @param $updates_array array of attributes to update
 * @return boolean true if successful
 */
function wpsc_update_visitor(  $visitor_id, $args ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$result = false;

	if ( ! empty( $args ) ) {

		$result = $wpdb->update( $wpdb->wpsc_visitors, $args,	array( 'id' => $visitor_id ) );

		if ( isset( $args['user_id'] ) && is_numeric( $args['user_id'] ) && ( $args['user_id'] != 0 ) ) {
			$wp_user_id = intval( $args['user_id'] );
			update_user_meta( $wp_user_id, '_wpsc_visitor_id', $visitor_id );
		}
	}

	// one row should be updated on success
	return $result === 1;
}

/**
 * Deletes a WPEC visitor
 *
 * @since 3.8.14
 *
 * @param int (optional) WPEC visitor id to update
 * @param $updates_array array of attributes to update
 * @return boolean true if successful
 */
function wpsc_delete_visitor( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	if ( empty( $visitor_id ) || ( $visitor_id == WPSC_BOT_VISITOR_ID ) ) {
		return false;
	}

	$ok_to_delete_visitor = ! ( wpsc_visitor_has_purchases( $visitor_id )
									&& wpsc_visitor_post_count( $visitor_id )
										&& wpsc_visitor_comment_count( $visitor_id ) );

	if ( ! $ok_to_delete_visitor ) {
		wpsc_visitor_remove_expiration( $visitor_id );
	} else {

		global $wpdb;

		$ok_to_delete_visitor = apply_filters( 'wpsc_before_delete_visitor', $ok_to_delete_visitor, $visitor_id );

		// we explicitly empty the cart to allow WPEC hooks to run
		$cart = wpsc_get_visitor_cart( $visitor_id );
		$cart->empty_cart();

		// Delete all of the visitor meta
		$visitor_meta = wpsc_get_visitor_meta( $visitor_id );
		foreach ( $visitor_meta as $visitor_meta_key => $visitor_meta_value ) {
			wpsc_delete_visitor_meta( $visitor_id, $visitor_meta_key );
		}

		// Delete the visitor record
		$result = $wpdb->delete( $wpdb->wpsc_visitors, array( 'id' => $visitor_id ) );

		// if a WordPress user references the visitor being deleted we need to remove the reference
		$sql = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = "_wpsc_visitor_id" AND meta_value = ' .  $visitor_id;
		$user_ids = $wpdb->get_col( $sql, 0 );

		foreach ( $user_ids as $user_id ) {
			delete_user_meta( $user_id, '_wpsc_visitor_id' );
		}

		do_action( 'wpsc_after_delete_visitor', $visitor_id );
	}

	// one row should be updated on success
	return $result === 1;
}

/**
 *  Get list of visitor ids that have expired
 *  			list will be ordered by expired date, eldest expiration first
 *
 * @since 3.8.14
 * @return array of integers, each integer corresponds to a visitor id that is expired
 */
function wpsc_get_expired_visitor_ids() {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;
	$sql = 'SELECT id FROM ' . $wpdb->wpsc_visitors . ' WHERE expires IS NOT NULL AND expires <  NOW() AND id <> ' . WPSC_BOT_VISITOR_ID . ' ORDER BY expires ASC';
	$visitor_ids = $wpdb->get_col( $sql, 0 );
	$visitor_ids = array_map( 'intval', $visitor_ids );
	return $visitor_ids;
}

/**
 *  Get list of visitor ids, list will be ordered by created date, most recent first
 *
 * @since 3.8.14
 * @return array of integers, each integer corresponds to a visitor id
 */
function wpsc_get_visitor_ids() {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;
	$sql = 'SELECT id FROM ' . $wpdb->wpsc_visitors . ' ORDER BY created DESC';
	$visitor_ids = $wpdb->get_col( $sql, 0 );
	$visitor_ids = array_map( 'intval', $visitor_ids );
	return $visitor_ids;
}

/**
 *  Get list of visitor ids
 * @param boolean 	when true, include expired visitors in the list,
 * 					list will be ordered by created date, most recent first
 * @since 3.8.14
 * @return array of objects, the index is the visitor id
 */
function wpsc_get_visitor_list( $include_expired_visitors ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	global $wpdb;

	if ( $include_expired_visitors ) {
		$sql = 'SELECT * FROM ' . $wpdb->wpsc_visitors . ' ORDER BY created DESC';
	} else {
		$sql = 'SELECT id FROM ' . $wpdb->wpsc_visitors . ' WHERE expires IS NOT NULL AND expires >  NOW() ORDER BY created DESC';
	}

	$visitors = $wpdb->get_results( $sql, OBJECT_K );
	return $visitors;
}



/**
 * Return a visitor's cart
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id visitor ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_get_visitor_cart( $visitor_id ) {

	$wpsc_cart = new wpsc_cart();

	if ( _wpsc_visitor_database_ready() ) {

		foreach ( $wpsc_cart as $key => $value ) {
			$cart_property_meta_key = _wpsc_get_visitor_meta_key( 'cart.' . $key );
			$meta_value = wpsc_get_visitor_meta( $visitor_id, $cart_property_meta_key, true );
			if ( ! empty( $meta_value ) ) {

				switch ( $key ) {
					case 'shipping_methods':
					case 'shipping_quotes':
					case 'cart_items':
						/////////////////////////////////////////////////////////////////////////////
						// The type of the decoded value must be an array, we are going to check here
						// just in case something went wrong during a data storage or perhaps the
						// verion upgrade. If the datatype is not an array we will throw away the
						// data to stop later functions from abending.
						/////////////////////////////////////////////////////////////////////////////
						$meta_value = _wpsc_decode_meta_value( $meta_value );
						if ( ! is_array( $meta_value ) ) {
							$meta_value = array();
						}

						break;


					case 'cart_item':
						/////////////////////////////////////////////////////////////////////////////
						// The type of the decoded value must be an wpsc_cart_item, we are going to
						// check here just in case something went wrong during a data storage or
						// perhaps the verion upgrade. If the datatype is not an array we will
						// throw away the data to stop later functions from abending.
						/////////////////////////////////////////////////////////////////////////////
						$meta_value = _wpsc_decode_meta_value( $meta_value );
						if ( ! is_a( $meta_value, 'wpsc_cart_item' ) ) {
							$meta_value = null;
						}

						break;

					default:
						break;
				}

				$wpsc_cart->$key = $meta_value;
			}
		}
	}

	$wpsc_cart = apply_filters( 'wpsc_got_visitor_cart', $wpsc_cart, $visitor_id );

	return $wpsc_cart;
}

/**
 * Update a visitor's cart
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id visitor ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_update_visitor_cart( $visitor_id, $wpsc_cart ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return $wpsc_cart;
	}

	foreach ( $wpsc_cart as $key => $value ) {
		$cart_property_meta_key = _wpsc_get_visitor_meta_key( 'cart.' . $key );

		// we don't store empty cart properties, this keeps meta table and caches neater
		if ( ! empty( $value ) ) {
			switch ( $key ) {
				case 'shipping_methods':
				case 'shipping_quotes':
				case 'cart_items':
				case 'cart_item':
					$value = _wpsc_encode_meta_value( $value );
					break;

				default:
					break;
			}

			wpsc_update_visitor_meta( $visitor_id, $cart_property_meta_key, $value );

		} else {
			wpsc_delete_visitor_meta( $visitor_id, $cart_property_meta_key );
		}
	}

	return $wpsc_cart;
}


/**
 *  If a value is an object or an array encode it so it can be stored as WordPress meta
 * @param unknown $value
 * @return encoded value
 */
function _wpsc_encode_meta_value( $value  ) {
	$value = base64_encode( serialize( $value ) );
	return $value;
}

/**
 *  If a value was enocoded prior to being stored, decode it
 * @param unknown $value
 * @return encoded value
 */
function _wpsc_decode_meta_value( $value ) {

	if ( is_string( $value ) ) {
		$decoded = base64_decode( $value, true );

		if ( $decoded !== false ) {
			$value = maybe_unserialize( $decoded );
		}
	}

	return $value;
}


/**
 * get the count of comments by the customer
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_visitor_comment_count( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$count = 0;

	if ( $wp_user_id = wpsc_get_visitor_wp_user_id( $visitor_id ) ) {

		global $wpdb;
		$count = $wpdb->get_var( 'SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' WHERE user_id = "' . $wp_user_id . '"' );

		if ( empty($count) || ! is_numeric( $count ) ) {
			$count = 0;
		}
	}

	return $count;
}

/**
 * get the count of purchases by the customer
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_visitor_purchase_count( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$count = 0;

	// Check the purchases in the visitor meta
	$purchase_ids = wpsc_get_visitor_meta( $visitor_id, 'purchase_id', false );
	if ( count( $purchase_ids ) ) {
		$has_purchases = true;
	}

	return count( $purchase_ids );
}

/**
 * does the customer have purchases
 * @since 3.8.14
 * @access public
 * @param string $id
 * @return int
 */
function wpsc_visitor_has_purchases( $visitor_id ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$has_purchases = false;

	// If there is one, check the WordPress user id in the purchase logs
	if ( $wp_user_id = wpsc_get_visitor_wp_user_id( $visitor_id ) ) {

		global $wpdb;
		$count = $wpdb->get_var( 'SELECT COUNT(user_ID) FROM ' . WPSC_TABLE_PURCHASE_LOGS. ' WHERE user_ID = "' . $wp_user_id . '"' );

		if ( ! empty( $count ) && is_numeric( $count ) && intval( $count ) > 0 ) {
			$has_purchases = true;
		}
	}

	// Check the purchases in the visitor meta
	$purchase_ids = wpsc_get_visitor_meta( $visitor_id, 'purchase_id', false );
	if ( count( $purchase_ids ) ) {
		$has_purchases = true;
	}

	return $has_purchases;
}



//
// visitor meta functions
//

/**
 * Add meta data field to a visitor.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function wpsc_add_visitor_meta( $visitor_id, $meta_key, $meta_value, $unique = false ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return add_metadata( 'wpsc_visitor' , $visitor_id, $meta_key , $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a visitor.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function wpsc_delete_visitor_meta( $visitor_id, $meta_key, $meta_value = '' ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return delete_metadata( 'wpsc_visitor', $visitor_id , $meta_key , $meta_value );
}

/**
 * Retrieve visitor meta field for a visitor.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function wpsc_get_visitor_meta( $visitor_id, $key = '', $single = false ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return get_metadata( 'wpsc_visitor' , $visitor_id , $key, $single );
}

/**
 *  Determine if a meta key is set for a given visitor.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
* @return boolean true of the key is set, false if not.
 *  is true.
 */
function wpsc_visitor_meta_exists( $visitor_id, $meta_key ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return metadata_exists( 'wpsc_visitor' , $visitor_id , $meta_key );
}

/**
 * Update visitor meta field based on visitor ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and visitor ID.
 *
 * If the meta field for the visitor does not exist, it will be added.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id $visitor ID.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function wpsc_update_visitor_meta( $visitor_id, $meta_key, $meta_value, $prev_value = '' ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return update_metadata( 'wpsc_visitor' , $visitor_id , $meta_key , $meta_value , $prev_value );
}

/**
 * Delete everything from visitor meta matching meta key.
 * This meta data function mirrors a corresponding wordpress post meta function.
 * @since 3.8.14
 *
 * @param string $visitor_meta_key Key to search for when deleting.
 * @return bool Whether the visitor meta key was deleted from the database
 */
function wpsc_delete_visitor_meta_by_key( $visitor_meta_key ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return delete_metadata( 'wpsc_visitor' , null , $visitor_meta_key , '' , true );
}

/**
 * Retrieve visitor meta fields, based on visitor ID.
 *
 * The visitor meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID.
 * @return array
 */
function wpsc_get_visitor_custom( $visitor_id = 0 ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$visitor_id = absint( $visitor_id );
	return get_visitor_meta( $visitor_id );
}

/**
 * Retrieve meta field names for a visitor.
 *
 * If there are no meta fields, then nothing(null) will be returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $visitor_id visitor ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function wpsc_get_visitor_custom_keys( $visitor_id = 0 ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	$custom = wpsc_get_visitor_custom( $visitor_id );

	if ( ! is_array( $custom ) )
		return;

	if ( $keys = array_keys( $custom ) )
		return $keys;
}

/**
 * Retrieve values for a custom visitor field.
 *
 * The parameters must not be considered optional. All of the visitor meta fields
 * will be retrieved and only the meta field key values returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param string $metakey Meta field key.
 * @param int $visitor_id visitor ID
 * @return array Meta field values.
 */
function wpsc_get_visitor_custom_values( $metakey = '', $visitor_id = 0 ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	if ( ! $key )
		return null;

	$custom = wpsc_get_visitor_custom( $visitor_id );

	return isset( $custom[$key] ) ? $custom[$key] : null;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.14
 *
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return array metadata matching the query
 */
function wpsc_get_visitor_meta_by_timestamp( $timestamp = 0, $comparison = '>', $metakey = '' ) {

	if ( ! _wpsc_visitor_database_ready() ) {
		return false;
	}

	return wpsc_get_meta_by_timestamp( 'wpsc_visitor', $timestamp , $comparison , $metakey );
}

