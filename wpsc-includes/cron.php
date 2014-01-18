<?php
add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
add_action( 'wpsc_hourly_cron_task', '_wpsc_clear_customer_meta' );

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

	$seconds = floor( $time * $convert[$interval] );

	$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_CLAIMED_STOCK . " WHERE last_activity < UTC_TIMESTAMP() - INTERVAL %d SECOND", $seconds );
	$wpdb->query( $sql );
}

function _wpsc_clear_customer_meta() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/user.php' );


	$args = array(
			'meta_query' => array(
					array(
							'key'     => _wpsc_get_customer_meta_key( 'temporary_profile' ),
							'value'   => time(),
							'type'    => 'numeric',
							'compare' => '<'
					)
			),
			'fields' => 'ID'
	);

	 $wp_user_query = new WP_User_Query( $args );


	// For each of the ids double check to be sure there isn't any important data associated with the temporary user.
	// If important data is found the user is no longer temporary. We also use a filter so that if other plug-ins
	// want to either stop the user from being deleted, or do something with the information in the profile they
	// have that chance.
	foreach ( $wp_user_query->results as $id ) {
		// for extra safety
		$ok_to_delete_temporary_customer_profile = ( wpsc_customer_purchase_count( $id ) == 0 ) && ( wpsc_customer_post_count( $id ) == 0 ) && ( wpsc_customer_comment_count( $id ) == 0 );
		if ( apply_filters( 'wpsc_before_delete_customer_profile', $ok_to_delete_temporary_customer_profile, $id ) ) {
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

add_action( 'testclearmeta' , _wpsc_clear_customer_meta );

