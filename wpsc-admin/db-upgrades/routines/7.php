<?php

function _wpsc_db_upgrade_7() {
	_wpsc_migrate_user_meta();
}

function _wpsc_migrate_user_meta() {
	global $wpdb;

	$sql = "
		SELECT *
		FROM {$wpdb->usermeta}
		WHERE
			meta_key LIKE '_wpsc_%customer_profile';
	";

	$results = $wpdb->get_results( $sql );

	foreach ( $results as $row ) {
		preg_match( '/_wpsc_(.*)customer_profile/', $row->meta_key, $matches );
		$blog_prefix = $matches[1];

		$profile = maybe_unserialize( $row->meta_value );

		foreach ( $profile as $key => $value ) {
			$internal_key = "{$blog_prefix}_wpsc_{$key}";
			$current_value = get_user_meta( $row->user_id, $internal_key, true );

			if ( $current_value === '' && $value ) {
				update_user_meta( $row->user_id, $internal_key, $value );
			}
		}

		delete_user_meta( $row->user_id, $row->meta_key );
	}
}