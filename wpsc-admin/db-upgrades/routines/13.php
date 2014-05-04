<?php
/**
 * Control database upgrade to version 11
*
* @access private
* @since 3.8.14
*
*/
function _wpsc_db_upgrade_13() {
	_wpsc_add_region_label_to_uk();
	_wpsc_fix_bad_checkout_form_rows();
}

/**
 * add the county region label to the uk
 *
 * @access private
 * @since 3.8.14.1
 */
function _wpsc_add_region_label_to_uk() {
	$wpsc_country = new WPSC_Country( 'GB' );
	$wpsc_country->set( 'region_label', __( 'County', 'wpsc' ) );
}

function _wpsc_fix_bad_checkout_form_rows() {
	global $wpdb;

	$rows = $wpdb->get_results( 'SELECT * from '. WPSC_TABLE_CHECKOUT_FORMS . ' WHERE `unique_name` = ""' );

	foreach ( $rows as $index => $row ) {
		// look for the unique name to be sure it doesn't already-exist, if it does, make it unique
		$new_unique_name = sanitize_title( $row->name );

		$count = $wpdb->get_var( 'SELECT count(*) FROM ' . WPSC_TABLE_CHECKOUT_FORMS . ' WHERE `unique_name` = "' . $new_unique_name .'"' );
		$count = intval( $count );
		if ( $count ) {
			$new_unique_name = sanitize_title( $row->name ) . '-' . ( $count + 1 );
		}

		$result = $wpdb->query(
								'UPDATE ' . WPSC_TABLE_CHECKOUT_FORMS
									. ' SET `unique_name` = "' . $new_unique_name
									. '" WHERE id=' . $row->id
							);
	}


	$index = $wpdb->get_results( 'SHOW INDEX FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE KEY_NAME = "unique_name"' );

	if ( ! count( $index ) ) {
		// Add the new index
		$wpdb->query( 'ALTER TABLE `' . WPSC_TABLE_CHECKOUT_FORMS . '` ADD INDEX ( `unique_name` )' );
	}
}
