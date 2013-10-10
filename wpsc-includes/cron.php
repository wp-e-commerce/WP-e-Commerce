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

	$sql = 'UPDATE ' . $wpdb->usermeta . '
		SET
			meta_value = meta_value - 1,
			meta_key = IF (meta_value < 0, "_wpsc_temporary_profile_to_delete", meta_key )
		WHERE
			meta_key = "_wpsc_temporary_profile"';

	$wpdb->get_results( $sql );

	$sql = "
		SELECT user_id
		FROM {$wpdb->usermeta}
		WHERE
			meta_key = '_wpsc_temporary_profile_to_delete'
	";

	$ids = $wpdb->get_col( $sql );
	foreach ( $ids as $id ) {
		wp_delete_user( $id );
	}
}