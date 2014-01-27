<?php
add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
add_action( 'wpsc_hourly_cron_task', '_wpsc_clear_expired_user_profiles' );

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
function _wpsc_clear_expired_user_profiles() {
	$response = wp_remote_post( admin_url( 'admin-ajax.php' ) . '?action=wpsc_clear_expired_profiles'  );
}

add_action( '_wpsc_clear_customer_meta_action' , '_wpsc_clear_expired_user_profiles' );


// If we are doing ajax and the request came from ourselves  we can setup the clear user profiles ajax
if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] ) ) {

	add_action( 'wp_ajax_wpsc_wpsc_clear_expired_profiles', '_wpsc_clear_expired_user_profiles_ajax' );
	add_action( 'wp_ajax_nopriv_wpsc_clear_expired_profiles', '_wpsc_clear_expired_user_profiles_ajax' );

	/**
	 *
	 */
	function _wpsc_clear_expired_user_profiles_ajax() {
		global $wpdb;

		$args = array(
				'meta_query' => array(
						array(
								'key'     => _wpsc_get_customer_meta_key( 'temporary_profile' ),
								'value'   => time(),
								'type'    => 'UNSIGNED',
								'compare' => '<',
						),
				),
				'fields' => 'ID',
		);

		$wp_user_query = new WP_User_Query( $args );


		// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
		// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
		// want to either stop the user from being deleted, or do something with the information in the profile they
		// have that chance.

		if ( ! defined( 'WPSC_MAX_DELETE_PROFILE_TIME' ) ) {
			define( 'WPSC_MAX_DELETE_PROFILE_TIME', 10 );
		}

		if ( ! defined( 'WPSC_MAX_DELETE_MEMORY_USAGE' ) ) {
			define( 'WPSC_MAX_DELETE_MEMORY_USAGE',  20 * 1024 * 1024 ); // allow up to 20 megabytes to be consumed by the delete processing
		}

		$a_little_bit_of_time_after_start = time() + WPSC_MAX_DELETE_PROFILE_TIME;
		$too_much_memory_is_being_used = memory_get_usage( true ) + WPSC_MAX_DELETE_MEMORY_USAGE;

		foreach ( $wp_user_query->results as $id ) {

			// in case we have a lot of users to delete we do some checking to make sure we don't
			// get caught in a loop using server resources for an extended period of time without yielding.
			// Different environments will be able to delete a different number of users in the allowed time,
			// that's the reason for the defined variable
			if ( (time() > $a_little_bit_of_time_after_start) || ( memory_get_usage( true ) > $too_much_memory_is_being_used ) ) {
				// next delete processing will happen no sooner than in a couple minutes, but as the time allowed for
				// delete processing increases the interval between cycles will also extend.
				wp_schedule_single_event( time() + ( 120 + 2 * WPSC_MAX_DELETE_PROFILE_TIME ), '_wpsc_clear_customer_meta_action' );
				break;
			}

			// for extra safety we check to be sure we wouldn't be orphaning data if we deleted a customer profile
			$ok_to_delete_temporary_customer_profile = ( wpsc_customer_purchase_count( $id ) == 0 ) && ( wpsc_customer_post_count( $id ) == 0 ) && ( wpsc_customer_comment_count( $id ) == 0 );
			if ( apply_filters( 'wpsc_before_delete_temp_customer_profile', $ok_to_delete_temporary_customer_profile, $id ) ) {
				wp_delete_user( $id );
				do_action( 'wpsc_after_delete_temp_customer_profile', $id );
			} else {
				// user should not be temporary if it has posts, purchases, comments or anything else.  This is partially a
				// defensive measure against the list of temporary users growing forever should there be logic problems
				// with other plug-ins and their implementation of the wpsc_before_delete_customer_profile filter.
				wpsc_delete_customer_meta( 'temporary_profile' );
				do_action( 'wpsc_customer_profile_not_temporary', $id );
			}
		}
	}
}