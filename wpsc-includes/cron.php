<?php
add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
add_action( 'wpsc_hourly_cron_task', '_wpsc_clear_customer_meta' );

/**
 * wpsc_clear_stock_claims, clears the stock claims, runs using wp-cron and when editing purchase log statuses via the dashboard
 */
function wpsc_clear_stock_claims() {
	WPSC_Claimed_Stock::clear_claimed_stock();
}

function _wpsc_clear_customer_meta() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/user.php' );

	$sql = "
		SELECT user_id
		FROM {$wpdb->usermeta}
		WHERE
		meta_key = '_wpsc_last_active'
		AND meta_value < UNIX_TIMESTAMP() - " . WPSC_CUSTOMER_DATA_EXPIRATION . "
	";

	$ids = $wpdb->get_col( $sql );
	foreach ( $ids as $id ) {
		wp_delete_user( $id );
	}
}