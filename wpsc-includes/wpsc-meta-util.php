<?php
/**
 * Get all meta ids that have the meta value
 *
 * @since 3.8.14
 *
 * @param string $meta_object_type the WordPress meta object type
 * @param string $meta_key ids with the specified meta key
 * @return array of int 	meta object type object ids that match have the meta key
 */
function wpsc_get_meta_ids_by_meta_key( $meta_object_type, $meta_key = '' ) {
	global $wpdb;

	$meta_table    = _wpsc_meta_table_name( 'visitor' );
	$id_field_name = _wpsc_meta_key_name( 'visitor' );

	$sql = 'SELECT meta_id FROM `' . $meta_table . '` where meta_key = "%s"';
	$sql = $wpdb->prepare( $sql , $meta_key );

	$meta_item_ids = $wpdb->get_col( $sql, 0  );
	$meta_item_ids = array_map( 'intval', $meta_item_ids );

	$ids = apply_filters( 'wpsc_get_ids_by_meta_key', $meta_item_ids, $meta_object_type, $meta_key );

	return $meta_item_ids;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type the WordPress meta object type
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return array metadata matching the query
 */
function wpsc_get_meta_by_timestamp( $meta_object_type, $timestamp = 0, $comparison = '>', $meta_key = '' ) {
	global $wpdb;

	$meta_table    = _wpsc_meta_table_name( $meta_object_type );
	$id_field_name = _wpsc_meta_key_name( 'visitor' );

	if ( ($timestamp == 0) || empty( $timestamp ) ) {
		$sql = 'SELECT ' . $id_field_name . ' AS id FROM ` ' . $meta_table . '` ';
	} else {
		// validate the comparison operator
		if ( ! in_array( $comparison, array( '=', '>=', '>', '<=', '<', '<>', '!='	) ) ) {
			return false;
		}

		if ( is_int( $timestamp ) ) {
			$timestamp = date( 'Y-m-d H:i:s', $timestamp );
		}

		$sql = 'SELECT ' . $id_field_name . ' as id FROM `' . $meta_table. '` where meta_timestamp ' . $comparison . ' "%s"';
		$sql = $wpdb->prepare( $sql , $timestamp );
	}

	if ( ! empty ($meta_key ) ) {
		$sql .= ' AND meta_key = %s';
		$sql = $wpdb->prepare( $sql , $meta_key );
	}

	$meta_item_ids = $wpdb->get_col( $sql, 0  );
	$meta_item_ids = array_map( 'intval', $meta_item_ids );

	$ids = apply_filters( 'wpsc_get_meta_by_timestamp', $meta_item_ids, $meta_object_type, $meta_key, $timestamp, $comparison );

	$metas = array();

	foreach ( $meta_item_ids as $id ) {
		$metas[$id] = get_metadata( $meta_object_type , $id , $meta_key );
	}

	return $metas;
}


/**
 * Get meta timestamp of the by object type, meta id and key, if multiple records exist
 * the timestamp of the most recently updated record is returned
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $object_id ID for a specific meta item
 * @return object Meta object timestamp or false.
 */
function wpsc_get_metadata_timestamp( $meta_object_type, $object_id, $meta_key = '' ) {
	global $wpdb;

	$object_id = intval( $object_id );

	if ( ! empty($meta_object_type) && ! empty( $meta_id )  && ! empty( $meta_key ) ) {
		$meta_table_name = _wpsc_meta_table_name( $meta_object_type );
		$id_field_name = _wpsc_meta_key_name( 'visitor' );
		if ( ! empty( $meta_table_name ) ) {
			if ( ! empty ( $meta_key ) ) {
				$sql = 'SELECT meta_timestamp '
						. ' FROM '. $meta_table_name
						. ' WHERE meta_key = %s AND `' . $id_field_name . '` = %d '
						. ' ORDER BY meta_timestamp DESC LIMIT 1';

				$wpdb->prepare( $sql , $meta_key, $object_id );
			} else {
				$sql = 'SELECT meta_timestamp '
						. ' FROM '. $meta_table_name
						. ' WHERE ' . $id_field_name . '` = %d '
						. ' ORDER BY meta_timestamp DESC LIMIT 1';

				$wpdb->prepare( $sql , $meta_key, $object_id );
			}

			$timestamp = $wpdb->get_row( $sql );
		}
	}

	if ( empty( $timestamp ) ) {
		$timestamp = false;
	}

	return $timestamp;
}


/**
 * Get meta timestamp of the by meta object id
 *
 * @since 3.8.14
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $meta_id ID for a specific meta row
 * @return object Meta object timestamp or false.
 */
function wpsc_get_meta_id_timestamp( $meta_object_type, $meta_id ) {
	global $wpdb;

	$meta_id = intval( $meta_id );

	if ( ! empty($meta_object_type) && ! empty( $meta_id ) ) {
		$meta_table_name = _wpsc_meta_table_name( $meta_object_type );
		if ( ! empty( $meta_table_name ) ) {
			$sql = 'SELECT meta_timestamp FROM '. $meta_table_name .' WHERE meta_id = %d';
			$timestamp = $wpdb->get_var( $wpdb->prepare( $sql , $meta_id ), 0 );
		}
	}

	if ( empty( $timestamp ) ) {
		$timestamp = false;
	}

	return $timestamp;
}


/**
 * Get the meta associated with a specific meta type and meta id
 * the timestamp of the newest record is returned
 * @since 3.8.14
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 	* @param int $meta_id ID for a specific meta row
 * @return object Meta object or false.
 */
function _wpsc_get_meta_by_meta_id( $meta_object_type, $meta_id  ) {
	global $wpdb;
	$meta_item = false;

	$meta_id = intval( $meta_id );

	if ( ! empty($meta_object_type) && ! empty( $meta_id ) ) {
		$meta_table_name = _wpsc_meta_table_name( $meta_object_type );

		if ( ! empty( $meta_table_name ) ) {
			$sql = 'SELECT * FROM ' . $meta_table_name . ' WHERE meta_id = %d';
			$sql = $wpdb->prepare( $sql , $meta_id );

			$meta_item = $wpdb->get_row( $sql, OBJECT );

			$meta_item->meta_value = maybe_unserialize( $meta_item->meta_value );

			if ( $meta_item === null ) {
				$meta_item = false;
			}
		}
	}

	return $meta_item;
}


/**
 * Get the meta ids associated with a specific meta type, object id and meta key
 * the timestamp of the newest record is returned
 *
 * @acess private
 * @since 3.8.14
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $meta_id ID for a specific meta row
 * @return array array of meta ids
 */
function _wpsc_get_meta_ids( $meta_object_type, $object_id, $meta_key  ) {
	global $wpdb;
	$meta_item_ids = array();

	$object_id = intval( $object_id );

	if ( ! empty($meta_object_type) && ! empty( $object_id )  && ! empty( $meta_key ) ) {
		$meta_table_name = _wpsc_meta_table_name( $meta_object_type );

		if ( ! empty( $meta_table_name ) ) {
			$sql = 'SELECT meta_id '
					. ' FROM '. $meta_table_name
					. ' WHERE `' . _wpsc_meta_key_name( $meta_object_type )  . '` = %d '
					. ' AND meta_key = %s';

			$sql = $wpdb->prepare( $sql , $object_id, $meta_key );

			$meta_item_ids = $wpdb->get_col( $sql, 0 );

			if ( ! empty( $meta_item_ids ) ) {
				$meta_item_ids = array_map( 'intval', $meta_item_ids );
			}
		}
	}

	return $meta_item_ids;
}


/**
 * Validate the custom meta object type
 *
 * @since 3.8.14
 * @access private
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return validated string, or empty string if it isn't a valid object type
 */
function _wpsc_validate_meta_object_type( $meta_object_type ) {

	// This is a name translation that should stay very small and only be added to
	// in the case where we change a meta object name, or are storing multiple meta
	// types in the same table.
	//
	// TODO: post 3.8.14 enhance by adding the general meta table to this array and validate the the
	// WPEC built in meta infrastructure, with its nifty caching capabilities can be used to access
	// the legacy catch-all meta table
	//
	$valid_meta_object_types = array(
			'visitor'   => 'visitor',    // valid customer meta table
			'purchase'  => 'purchase',   // valid customer meta table
			'cart_item' => 'cart_item',  // valid customer meta table
			'customer'  => 'visitor',    // customer changed to visitor in release 3.8.14
	);


	if ( in_array( $meta_object_type, $valid_meta_object_types ) ) {
		$object_type = $valid_meta_object_types[$meta_object_type];
	} else {
		$object_type = '';
	}

	return $object_type;
}

/**
 * The name of the meta table for a specific meta object type.
 *
 *  if it hasn't been defined in $wpdb the name as it would be defined is returned. The
 *  likely cases where it would not be defined would be during an initialization or
 *  upgrade process. Because it is possible that the meta table name has been overridden
 *  we will check to see if it exists in the $wpdb object before trying to crate it anew.
 *  Note: that function call does not check if the table exits, it only give back the name,
 *
 * @since 3.8.12
 * @access private
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta table defined in $wpdb, or the name as it would be defined
 */
function _wpsc_meta_table_name( $meta_object_type ) {
	global $wpdb;

	$meta_table_name_property = _wpsc_wpdb_meta_table( $meta_object_type );

	if ( property_exists( $wpdb, $meta_table_name_property ) ) {
		return $wpdb->$meta_table_name_property;
	} else {
		return $wpdb->prefix . $meta_object_type . '_meta';
	}
}


/**
 * The name of the meta table property for a specific meta object type, this name should be the name
 * found in the $wpdb class for the specified meta type
 *
 * @since 3.8.14
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the applicable WPEC custom meta table, empty string if the meta type is not valid
 */
function _wpsc_wpdb_meta_table( $meta_object_type ) {
	global $wpdb;

	if ( $meta_object_type = _wpsc_validate_meta_object_type( $meta_object_type ) ) {
		$table_name_property = 'wpsc_'. $meta_object_type . 'meta';
	} else {
		$table_name_property = '';
	}

	return $table_name_property;
}

/**
 * The name of the column in the meta table that contains the key of the target object
 *
 * @since 3.8.14
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string column name of the applicable WPEC custom meta table index field, empty string if the meta type is not valid
 */
function _wpsc_meta_key_name( $meta_object_type ) {
	global $wpdb;

	if ( $meta_object_type = _wpsc_validate_meta_object_type( $meta_object_type ) ) {
		$id_field_name = 'wpsc_' . $meta_object_type . '_id';
	} else {
		$id_field_name = '';
	}

	return $id_field_name;
}

/**
 * Check the visitor meta key to see if it has been aliased to another visitor meta key
 *
 * @since 3.8.14
 *
 * @param string $visitor_meta_key
 * @return string valid unchanged key if original is valid, or replacement visitor meta key
 */
function _wpsc_validate_visitor_meta_key( $visitor_meta_key ) {

	// WPEC internal visitor meta keys are not allowed to be aliased, internal visitor meta keys
	if ( ! ( strpos( $visitor_meta_key, _wpsc_get_visitor_meta_key( '' ) ) === 0 ) ) {

		$build_in_checkout_names = wpsc_checkout_unique_names();

		// the built in checkout names cannot be aliased to something else
		if ( ! in_array( $visitor_meta_key, $build_in_checkout_names ) ) {

			/**
			 * Filter wpsc_visitor_meta_key_replacements
			 *
			 * Get an array of key/value pairs that are used to alias visitor meta keys. The
			 * key is the old name, the value is the new name
			 *
			 * @since 3.8.14
			 *
			 * @param array of key value pairs
			 *
			 */
			$aliased_meta_keys = apply_filters( 'wpsc_visitor_meta_key_replacements', array() );

			if ( isset( $aliased_meta_keys[$visitor_meta_key] ) ) {
				$visitor_meta_key = $aliased_meta_keys[$visitor_meta_key];
			}
		}
	}

	return $visitor_meta_key;
}



/**
 * Replace all of the specified meta keys in the database during an upgrade
 *
 * @since 3.8.14
 *
 * @param  array  array of string parirs old key is the index key, new key is the value
 * @return int    count of values updated
 */
function _wpsc_replace_visitor_meta_keys( $replacements ) {

	$build_in_checkout_names = wpsc_checkout_unique_names();

	$total_count_updated = 0;

	foreach ( $replacements as $old_meta_key => $new_meta_key ) {

		// the built in checkout names cannot be replaced to something else
		if ( ! isset( $build_in_checkout_names[$visitor_meta_key] ) ) {

			$sql = 'UPDATE ' . $wpdb->wpsc_visitormeta . ' SET meta_key = "' . $new_meta_key .

			$rows_updated = $wpdb->update(
					$wpdb->wpsc_visitormeta,                // table
					array( 'meta_key' => $new_meta_key,	),	// data to set
					array( 'meta_key' => $old_meta_key,	),  // where
					array( '%s', ),                         // format
					array( '%s', )                          // where format
			);

			$total_count_updated += $rows_updated;
		}
	}

	if ( $total_count_updated > 0 ) {
		wp_cache_flush();
	}

	return $total_count_updated;
}


/** Create visitors that we expect to be in the table
 *
 */
function _wpsc_create_well_known_visitors() {

	global $wpdb;

	// user id 1 will be used for a well known bot user
	$wpdb->insert( $wpdb->wpsc_visitors, array( 'id' => 1 ) );
}


if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

	add_action( 'wp_ajax_wpsc_migrate_anonymous_user', '_wpsc_meta_migrate_anonymous_user_worker' );
	add_action( 'wp_ajax_nopriv_wpsc_migrate_anonymous_user', '_wpsc_meta_migrate_anonymous_user_worker' );

	function _wpsc_meta_migrate_anonymous_user_worker() {

		global $wpdb;

		$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
		$key_pattern = "{$blog_prefix}_wpsc_";

		wp_suspend_cache_addition( true );

		$sql = 'SELECT ID FROM '. $wpdb->users . ' WHERE user_login LIKE "\_%" AND user_email = "" AND user_login = user_nicename AND user_login = display_name LIMIT 100';
		$user_ids = $wpdb->get_col( $sql, 0 );

		// Create an array to store users to be removed.
		$bin = array();

		foreach ( $user_ids as $user_id ) {

			$wpdb->query( 'INSERT INTO ' . $wpdb->wpsc_visitors . '(`id`) VALUES ( ' . $user_id . ' )' );

			wpsc_set_visitor_expiration( $user_id,  DAY_IN_SECONDS );

			$meta = get_user_meta( $user_id );
			foreach ( $meta as $key => $value ) {

				if ( strpos( $key, $key_pattern ) === FALSE )
					continue;

				$short_key = str_replace( $key_pattern, '', $key );
				if ( $short_key !== 'cart' ) {
					wpsc_add_visitor_meta( $user_id , $short_key, $value[0] );
				} else {
					$wpsc_user_cart = maybe_unserialize( base64_decode( $value[0] ) );

					if ( ! ($wpsc_user_cart instanceof wpsc_cart) ) {
						$wpsc_user_cart = new wpsc_cart();
					} else {
						continue;
					}
				}
			}

			$comment_count = $wpdb->get_var( 'SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' WHERE user_id = ' . $user_id );
			if ( ! count_user_posts( $user_id ) && ! $comment_count ) {
				//wp_delete_user( $user_id );
				// Add user to bin.
				$bin[] = $user_id;
			}
		}

		// Remove users.
		if ( ! empty( $bin ) ) {
			// Convert $bin to string.
			$bin = implode( ',', $bin );
			$wpdb->query( 'DELETE FROM ' . $wpdb->users . ' WHERE ID IN (' . $bin . ')' );
			$wpdb->query( 'DELETE FROM ' . $wpdb->usermeta . ' WHERE user_id IN (' . $bin . ')' );
		}

		wp_suspend_cache_addition( false );
		exit( 0 );
	}

}

add_action( 'wpsc_migrate_anonymous_user_cron', '_wpsc_meta_migrate_anonymous_user_cron' );


function _wpsc_meta_migrate_anonymous_user_cron() {

	global $wpdb;

	set_time_limit( 10 * 60 ); // 10 minutes maximum for the cron

	// WPEC created user records with a funky format,  no email is a dead giveaway, as is login, user name and display name being idnentical with the '_'
	$sql = 'SELECT count( ID ) FROM '. $wpdb->users . ' WHERE user_login LIKE "\_%" AND user_email = "" AND user_login = user_nicename AND user_login = display_name LIMIT 1';
	$ids_to_migrate = $user_ids = $wpdb->get_var( $sql );

	if ( $ids_to_migrate ) {
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ) . '?action=wpsc_migrate_anonymous_user' , array(  'blocking' => true, ) );
		wp_schedule_single_event( time() + 30 , 'wpsc_migrate_anonymous_user_cron' );
	} else {
		wp_cache_flush();
	}
}


/**
 * custmer/visitor/user meta has been known by different identifiers. we are trying to standardize on using
 * the uniquename value in the form definition for well known shopper meta.  this function allows
 * old meta keys to return the proper meta value from the database
 *
 * @since 3.8.14
 * @access private
 * @param unknown $meta_keys
 * @return string
 */
function _wpsc_visitor_meta_key_replacements( $meta_keys ) {

	$meta_keys['billing_region']           = 'billingregion';
	$meta_keys['billing_country']          = 'billingcountry';
	$meta_keys['shipping_region']          = 'shippingregion';
	$meta_keys['shipping_country']         = 'shippingcountry';
	$meta_keys['shipping_zip']             = 'shippingpostcode';
	$meta_keys['shipping_zipcode']         = 'shippingpostcode';
	$meta_keys['billing_zip']              = 'billingpostcode';
	$meta_keys['billing_zipcode']          = 'billingpostcode';
	$meta_keys['shippingzip']              = 'shippingpostcode';
	$meta_keys['billingzip']               = 'billingpostcode';
	$meta_keys['shipping_same_as_billing'] = 'shippingSameBilling';
	$meta_keys['delivertoafriend']         = 'shippingSameBilling';
	return $meta_keys;
}

add_filter( 'wpsc_visitor_meta_key_replacements', '_wpsc_visitor_meta_key_replacements' );
