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
 */
function _wpsc_delete_expired_visitors() {

	if ( ! defined( 'WPSC_MAX_DELETE_PROFILE_TIME' ) ) {
		define( 'WPSC_MAX_DELETE_PROFILE_TIME', 20 );
	}

	if ( ! defined( 'WPSC_MAX_DELETE_MEMORY_USAGE' ) ) {
		define( 'WPSC_MAX_DELETE_MEMORY_USAGE',  20 * 1024 * 1024 ); // allow up to 20 megabytes to be consumed by the delete processing
	}

	$expired_visitor_ids = wpsc_get_expired_visitor_ids();

	$a_little_bit_of_time_after_start = time() + WPSC_MAX_DELETE_PROFILE_TIME;
	$too_much_memory_is_being_used = memory_get_usage( true ) + WPSC_MAX_DELETE_MEMORY_USAGE;

	// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
	// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
	// want to either stop the user from being deleted, or do something with the information in the profile they
	// have that chance.
	foreach ( $expired_visitor_ids as $expired_visitor_id ) {
		wpsc_delete_visitor( $expired_visitor_id );

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
}

if ( is_admin() ) {
	// add admin action for convenience
	add_action( 'wpsc_delete_expired_visitors_action' , '_wpsc_delete_expired_visitors' );
}

