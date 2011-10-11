<?php
	add_action( 'wpsc_hourly_cron_task', 'wpsc_clear_stock_claims' );
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

		$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_CLAIMED_STOCK . " WHERE last_activity < NOW() - INTERVAL %d SECOND", $seconds );
		$wpdb->query( $sql );
	}
?>