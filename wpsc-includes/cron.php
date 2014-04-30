<?php
add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
add_action( 'wpsc_hourly_cron_task', '_wpsc_delete_expired_visitors' );

/**
 * wpsc_clear_stock_claims, clears the stock claims, runs using wp-cron and when editing purchase log statuses via the dashboard
 */
function wpsc_clear_stock_claims() {
	global $wpdb;

	$time = (float) get_option( 'wpsc_stock_keeping_time', 1 );
	$interval = get_option( 'wpsc_stock_keeping_interval', 'day' );

	// we need to convert into seconds because we're allowing decimal intervals like 1.5 days
	$convert = array(
		'hour' => 3600,
		'day'  => 86400,
		'week' => 604800,
	);

	$seconds = floor( $time * $convert[ $interval ] );

	$sql = $wpdb->prepare( 'DELETE FROM ' . WPSC_TABLE_CLAIMED_STOCK . ' WHERE last_activity < UTC_TIMESTAMP() - INTERVAL %d SECOND', $seconds );
	$wpdb->query( $sql );
}


/** Start the process that cleans up user profiles
 *
 * Request is made through AJAX to ensure all of the WordPress admin functionality  is loaded.  Necessary because there is
 * delete logic that is admin only.  It is also possible that a plugin may have filters that need to run when users are deleted.
 * @access private
 * @since 3.8.14
 *
 * @return how many expired visitors remain to be deleted, 0 if all done
 */
function _wpsc_delete_expired_visitors() {

	if ( ! defined( 'WPSC_MAX_DELETE_PROFILE_TIME' ) ) {
		define( 'WPSC_MAX_DELETE_PROFILE_TIME', 20 );
	}

	if ( ! defined( 'WPSC_MAX_DELETE_MEMORY_USAGE' ) ) {
		define( 'WPSC_MAX_DELETE_MEMORY_USAGE',  20 * 1024 * 1024 ); // allow up to 20 megabytes to be consumed by the delete processing
	}

	// We are going to record a little option so that support can confirm that the delete users cron is running
	add_option( '_wpsc_last_delete_expired_visitors_cron', date( 'Y-m-d H:i:s' ), null, 'no' );

	$expired_visitor_ids = wpsc_get_expired_visitor_ids();

	$a_little_bit_of_time_after_start = time() + WPSC_MAX_DELETE_PROFILE_TIME;
	$too_much_memory_is_being_used = memory_get_usage( true ) + WPSC_MAX_DELETE_MEMORY_USAGE;

	// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
	// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
	// want to either stop the user from being deleted, or do something with the information in the profile they
	// have that chance.
	foreach ( $expired_visitor_ids as $index => $expired_visitor_id ) {
		wpsc_do_delete_visitor_ajax( $expired_visitor_id );

		unset( $expired_visitor_ids[$index] );

		// in case we have a lot of users to delete we do some checking to make sure we don't
		// get caught in a loop using server resources for an extended period of time without yielding.
		// Different environments will be able to delete a different number of users in the allowed time,
		// that's the reason for the defined variable
		if ( (time() > $a_little_bit_of_time_after_start) || ( memory_get_usage( true ) > $too_much_memory_is_being_used ) ) {
			// next delete processing will happen no sooner than in a couple minutes, but as the time allowed for
			// delete processing increases the interval between cycles will also extend.
			wp_schedule_single_event( time() + ( 120 + 2 * WPSC_MAX_DELETE_PROFILE_TIME ), '_wpsc_delete_expired_visitors_action' );
			break;
		}
	}

	// Since there are no visitors to delete this is a good time to cleanup the visitors meta table
	// eliminating any orphaned meta data, asingle SQL query will do it!
	if ( ! count( $expired_visitor_ids ) ) {
		global $wpdb;
		$sql = 'DELETE vm FROM ' . $wpdb->wpsc_visitormeta . ' vm LEFT JOIN ' . $wpdb->wpsc_visitors . ' v  on v.id = vm.wpsc_visitor_id WHERE v.id IS NULL';
		$wpdb->query( $sql );
	}

	return count( $expired_visitor_ids );
}


/**
 * Request a visitor be deleted via the WordPRess admin ajax path
 *
 * @access private

 * @since 3.8.14
 *
 * @param int $visitor_id
 *
 * @return boolean, true on success, false on failure
 *
 */
function wpsc_do_delete_visitor_ajax( $visitor_id ) {

	$delete_visitor_nonce_action = 'wpsc_delete_visitor_id_' .  $visitor_id;

	$wpsc_security = wp_create_nonce( $delete_visitor_nonce_action );

	$response = wp_remote_post(
									admin_url( 'admin-ajax.php' ),
									array(
										'method'      => 'POST',
										'timeout'     => 15,
										'redirection' => 5,
										'httpversion' => '1.0',
										'blocking'    => true,
										'headers'     => array(),
										'body'        => array( 'action' => 'wpsc_delete_visitor', 'wpsc_visitor_id' => $visitor_id, 'wpsc_security' => $wpsc_security, ),
										'cookies'     => array(),
									)
								);

	if ( is_wp_error( $response ) ) {
		$result = false;
	} else {
		$result = true;
	}

	return $result;
}


// add admin action for convenience
add_action( '_wpsc_delete_expired_visitors_action' , '_wpsc_delete_expired_visitors' );

