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
	$already_cleaned_up_anonymous_profiles = get_option( 'wpsc_cleaned_up_anonymous_profiles', false );

	if ( $already_cleaned_up_anonymous_profiles ) {
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ) . '?action=wpsc_clear_expired_profiles' , array(  'blocking' => false, ) );
	} else {
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ) . '?action=wpsc_clear_expired_anonymous_profiles' , array(  'blocking' => false, ) );
	}
}

add_action( '_wpsc_clear_customer_meta_action' , '_wpsc_clear_expired_user_profiles' );


// If we are doing ajax and the request came from ourselves  we can setup the clear user profiles ajax
if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] ) ) {

	add_action( 'wp_ajax_wpsc_wpsc_clear_expired_profiles', '_wpsc_clear_expired_user_profiles_ajax' );
	add_action( 'wp_ajax_nopriv_wpsc_clear_expired_profiles', '_wpsc_clear_expired_user_profiles_ajax' );

	$already_cleaned_up_anonymous_profiles = get_option( 'wpsc_cleaned_up_anonymous_profiles', false );
	if ( ! $already_cleaned_up_anonymous_profiles ) {
		add_action( 'wp_ajax_wpsc_wpsc_clear_expired_anonymous_profiles', '_wpsc_clear_expired_anonymous_profiles_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_clear_expired_anonymous_profiles', '_wpsc_clear_expired_anonymous_profiles_ajax' );
	}

	/**
	 * Action processing to cleanup WPEC user profiles that haven't been used for a while
	 * @since 3.8.14
	 */
	function _wpsc_clear_expired_user_profiles_ajax() {

		if ( ! defined( 'WPSC_MAX_DELETE_PROFILE_TIME' ) ) {
			define( 'WPSC_MAX_DELETE_PROFILE_TIME', 10 );
		}

		if ( ! defined( 'WPSC_MAX_DELETE_MEMORY_USAGE' ) ) {
			define( 'WPSC_MAX_DELETE_MEMORY_USAGE',  20 * 1024 * 1024 ); // allow up to 20 megabytes to be consumed by the delete processing
		}

		if ( ! defined( 'WPSC_MAX_PROFILES_TO_QUERY' ) ) {
			define( 'WPSC_MAX_PROFILES_TO_QUERY',  500 ); // allow up to 500 profiles to be retrieved in a query
		}

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
				'number' => WPSC_MAX_PROFILES_TO_QUERY, // an arbitrary limit to avoid retrieving many thousands of users if they exist
		);

		$wp_user_query = new WP_User_Query( $args );

		$a_little_bit_of_time_after_start = time() + WPSC_MAX_DELETE_PROFILE_TIME;
		$too_much_memory_is_being_used = memory_get_usage( true ) + WPSC_MAX_DELETE_MEMORY_USAGE;

		// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
		// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
		// want to either stop the user from being deleted, or do something with the information in the profile they
		// have that chance.
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

	/**
	 * Action processing to cleanup pre 3.8.14 WPEC user profiles that haven't been used for a while
	 * Note: This is essentially an upgrade routine but cannot be run in the normal WPEC upgrade path becuase
	 * it is possible that total processing could take minutes to a small number of hours on slower sites.
	 * Processing time is dependent on how many of the old style temporary profiles have accumulated
	 * in the database.  Tests with 50,000 expired profiles on a fast highly tuned system show that
	 * cleaning the profiles can take about half an hour.
	 *
	 * The processing in this routine works by deleting (in batches) already expired profiles.  THen for
	 * the profiles that remain they are converted to new style profiles by adding
	 * the temporary_profile meta and an expire time in the future.  The normal clean up routine
	 * will take care of the clean up when the profile ages a little bit.
	 *
	 * @since 3.8.14
	 */
	function _wpsc_clear_expired_anonymous_profiles_ajax() {

		if ( ! defined( 'WPSC_MAX_DELETE_PROFILE_TIME' ) ) {
			define( 'WPSC_MAX_DELETE_PROFILE_TIME', 10 );
		}

		if ( ! defined( 'WPSC_MAX_DELETE_MEMORY_USAGE' ) ) {
			define( 'WPSC_MAX_DELETE_MEMORY_USAGE',  20 * 1024 * 1024 ); // allow up to 20 megabytes to be consumed by the delete processing
		}

		if ( ! defined( 'WPSC_MAX_PROFILES_TO_QUERY' ) ) {
			define( 'WPSC_MAX_PROFILES_TO_QUERY',  500 ); // allow up to 500 profiles to be retrieved in a query
		}

		global $wpdb;

		$args = array(
				'meta_query' => array(
						array(
								'key'     => _wpsc_get_customer_meta_key( 'last_active' ),
								'value'   => time(),
								'type'    => 'UNSIGNED',
								'compare' => '<',
						),
				),
				'fields' => 'ID',
				'number' => WPSC_MAX_PROFILES_TO_QUERY, // an arbitrary limit to avoid retrieving many thousands of users if they exist
				'role'   => 'wpsc_anonymous',
		);

		$wp_user_query = new WP_User_Query( $args );

		$a_little_bit_of_time_after_start = time() + WPSC_MAX_DELETE_PROFILE_TIME;
		$too_much_memory_is_being_used = memory_get_usage( true ) + WPSC_MAX_DELETE_MEMORY_USAGE;

		// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
		// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
		// want to either stop the user from being deleted, or do something with the information in the profile they
		// have that chance.
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

		// if the query results are empty last thing to do is check the database for non-expired
		// profiles that do not have the temporary_profile meta set
		if ( empty( $wp_user_query->results ) ) {

			// this is where we check to be sure that every anonymous user with last active has the temporary profile flag
			// after this the profile database should be clean and compatible with this version of WPEC.  We will set expire time to
			// last active time plus 48 hours, or two hours from now, whichever is the longer time interval
			$last_actives = $wpdb->get_results( 'SELECT user_id, meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = "' . _wpsc_get_customer_meta_key( 'last_active' ) . '"', OBJECT_K );
			$temporary_profiles = $wpdb->get_results( 'SELECT user_id, meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = "' . _wpsc_get_customer_meta_key( 'temporary_profile' ) . '"', OBJECT_K );

			$two_hours_from_now = time() + ( 2 * 60 * 60 );
			foreach ( $last_actives as $id => $data ) {
				if ( empty( $temporary_profiles[$id] ) ) {
					$temporary_profile_meta_count++;
					$profile_expire_time = max( intval( $data->meta_value ) + ( 60 * 60 * 48 ) , $two_hours_from_now );
					update_user_meta( $id, _wpsc_get_customer_meta_key( 'temporary_profile' ), $profile_expire_time );
				}
			}

			update_option( 'wpsc_cleaned_up_anonymous_profiles', true );
		}
	}

}