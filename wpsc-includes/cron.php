<?php
add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
add_action( 'wpsc_hourly_cron_task', '_wpsc_clear_customer_meta' );

/**
 * Clears the stock claims, runs on hourly WP_Cron event and when editing purchase log statuses.
 *
 * @since 3.8.9
 * @access public
 *
 * @return void
 */
function wpsc_clear_stock_claims() {
	global $wpdb;

	$time     = (float) get_option( 'wpsc_stock_keeping_time', 1 );
	$interval = get_option( 'wpsc_stock_keeping_interval', 'day' );

	// we need to convert into seconds because we're allowing decimal intervals like 1.5 days
	$convert = array(
		'hour' => 3600,
		'day'  => 86400,
		'week' => 604800,
	);

	$seconds = floor( $time * $convert[ $interval ] );

	$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_CLAIMED_STOCK . " WHERE last_activity < UTC_TIMESTAMP() - INTERVAL %d SECOND", $seconds );
	$wpdb->query( $sql );
}

/**
 * Purges customer meta that is older than WPSC_CUSTOMER_DATA_EXPIRATION on an hourly WP_Cron event.
 *
 * @since 3.8.9.2
 * @access public
 *
 * @return void
 */
function _wpsc_clear_customer_meta() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/user.php' );

	$purge_count = 200;

	$sql = "
		SELECT user_id
		FROM {$wpdb->usermeta}
		WHERE
		meta_key = '_wpsc_last_active'
		AND meta_value < UNIX_TIMESTAMP() - " . WPSC_CUSTOMER_DATA_EXPIRATION . "
		LIMIT {$purge_count}
	";

	/* Do this in batches of 200 to avoid memory issues when there are too many anonymous users */
	@set_time_limit( 0 ); // no time limit

	do {
		$ids = $wpdb->get_col( $sql );
		foreach ( $ids as $id ) {
			wp_delete_user( $id );
		}
	} while ( count( $ids ) == $purge_count );
}