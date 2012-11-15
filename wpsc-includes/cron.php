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

	$sql = $wpdb->prepare( "SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE '_transient_timeout_wpsc_customer_meta_%%' AND option_value < %d", time() );
	$results = $wpdb->get_col( $sql );

	foreach ( $results as $row ) {
		$customer_id = str_replace( '_transient_timeout_wpsc_customer_meta_', '', $row );
		delete_transient( "wpsc_customer_meta_{$customer_id}" );
	}
}