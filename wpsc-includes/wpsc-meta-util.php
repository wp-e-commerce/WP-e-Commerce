<?php
/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.12
 *
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return array metadata matching the query
 */
function wpsc_get_meta_by_timestamp( $meta_object_type, $timestamp = 0, $comparison = '>', $meta_key = '' ) {
	global $wpdb;

	$meta_table = wpsc_meta_table_name( $meta_object_type );
	if ( ($timestamp == 0) || empty( $timestamp ) ) {
		$sql = "SELECT * FROM `{$meta_table}` WHERE 1=1 ";
	} else {
		// validate the comparison operator
		if ( ! in_array( $comparison, array( '=', '>=', '>', '<=', '<', '<>', '!='	) ) )
			return false;

		if ( is_int( $timestamp ) )
			$timestamp = date( 'Y-m-d H:i:s', $timestamp );

		$sql = 'SELECT * FROM {$meta_table} where meta_timestamp {$comparison} %s';
	}

	if ( ! empty ($meta_key ) )
		$sql .= ' AND meta_key = %s';

	$sql = $wpdb->prepare( $sql, $timestamp, $meta_key );
	$meta_rows = $wpdb->get_results( $sql, OBJECT  );

	return $meta_rows;
}


/**
 * Get meta timestamp of the by object type, meta id and key, if multiple records exist
 * the timestamp of the newest record is returned
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 	* @param int $meta_id ID for a specific meta row
 * @return object Meta object or false.
 */
function wpsc_get_metadata_timestamp( $meta_object_type, $meta_id, $meta_key ) {
	global $wpdb;

	$meta_id = intval( $meta_id );

	if ( ! empty($meta_object_type) && ! empty( $meta_id )  && ! empty( $meta_key ) ) {
		$wpdb_property = $meta_object_type.'meta';

		if ( ! empty( $wpdb->$wpdb_property ) ) {
			$sql = 'SELECT meta_timestamp FROM '.wpsc_meta_table_name( $meta_object_type ).' WHERE meta_id = %d ORDER BY meta_timestamp DESC LIMIT 1';
			$timestamp = $wpdb->get_row( $wpdb->prepare( $sql , $meta_id ) );
		}
	}

	if ( empty( $timestamp ) )
		$timestamp = false;

	return $timestamp;
}

/**
 * The name of the meta table for a specific meta object type.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 	* @return string Name of the custom meta table
 */
function wpsc_meta_table_name( $meta_object_type ) {
	global $wpdb;
	return $wpdb->prefix . $meta_object_type . '_meta';
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
		if ( ! isset( $build_in_checkout_names[$visitor_meta_key] ) ) {

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

			if ( in_array( $visitor_meta_key, $aliased_meta_keys ) ) {
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
			$bin = implode(',', $bin);
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

