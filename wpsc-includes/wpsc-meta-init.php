<?php

require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-cart-item.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-purchase.php' );

/**
 * The name of the meta table for a specific meta object type.
 *
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta table
 */
function wpsc_meta_table_name( $meta_object_type ) {
	global $wpdb;
	return $wpdb->prefix . $meta_object_type . '_meta';
}

/**
 * Create the meta table for Cart Item
 *
 * @since 3.8.12
 * @access private
 *
 */
function _wpsc_create_cart_item_meta_table() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;

	$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_cart_item_meta .' ('
				.'meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, '
				.'wpsc_cart_item_id bigint(20) unsigned NOT NULL DEFAULT 0 , '
				.'meta_key varchar(255) DEFAULT NULL, '
				.'meta_value longtext, '
				.'meta_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
				.'PRIMARY KEY  (meta_id), '
				.'KEY wpsc_cart_item_id (wpsc_cart_item_id), '
				.'KEY meta_key (meta_key(191)), '
				.'KEY meta_value (meta_value(20)), '
				.'KEY meta_key_and_value (meta_key(191),meta_value(32)), '
				.'KEY meta_timestamp_index (meta_timestamp) '
				.') '. $charset_collate;

	dbDelta( $sql );
}

/**
 * Create the meta table for Purchases
 *
 * @since 3.8.12
 * @access private
 *
 */
function _wpsc_create_purchase_meta_table() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;

	$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_purchase_meta .' ('
				.'meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, '
				.'wpsc_purchase_id bigint(20) unsigned NOT NULL DEFAULT 0 , '
				.'meta_key varchar(255) DEFAULT NULL, '
				.'meta_value longtext, '
				.'meta_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
				.'PRIMARY KEY  (meta_id), '
				.'KEY wpsc_purchase_id (wpsc_purchase_id), '
				.'KEY meta_key (meta_key(191)), '
				.'KEY meta_value (meta_value(20)), '
				.'KEY meta_key_and_value (meta_key(191),meta_value(32)), '
				.'KEY meta_timestamp_index (meta_timestamp) '
				.') '. $charset_collate;

	dbDelta( $sql );
}

/**
 * Get meta timestamp of the by object type, meta id and key, if multiple records exist
 * the timestamp of the newest record is returned
 * @since 3.8.12
 *
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @param int $meta_id ID for a specific meta row
 * @return object Meta object or false.
 */
function wpsc_get_metadata_timestamp( $meta_object_type, $meta_id, $meta_key ) {
	global $wpdb;

	$meta_id = intval( $meta_id );

	if ( ! empty($meta_object_type) && !empty($meta_id)  && !empty($meta_key) ) {
		$wpdb_property = $meta_object_type.'meta';

		if ( ! empty( $wpdb->$wpdb_property ) ) {
			$sql = 'SELECT meta_timestamp FROM '.wpsc_meta_table_name( $meta_object_type ).' WHERE meta_id = %d ORDER BY meta_timestamp DESC LIMIT 1';
			$timestamp = $wpdb->get_row( $wpdb->prepare( $sql , $meta_id ) );
		}
	}

	if ( empty( $timestamp ) )
		$timestamp = false;

	return $timestamp;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.12
 *
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return array metadata matching the query
 */
function wpsc_get_meta_by_timestamp( $meta_object_type, $timestamp = 0, $comparison = '>', $meta_key = '' ) {
	global $wpdb;

	$meta_table = wpsc_meta_table_name( $meta_object_type );
	if ( ($timestamp == 0) || empty( $timestamp ) ) {
		$sql = "SELECT * FROM `{$meta_table}` WHERE 1=1 ";
	} else {
		// validate the comparison operator
		if ( ! in_array( $comparison, array(
				'=', '>=', '>', '<=', '<', '<>', '!='
		) ) )
			return false;

		if ( is_int( $timestamp ) )
			$timestamp = date( 'Y-m-d H:i:s', $timestamp );

		$sql = 'SELECT * FROM {$meta_table} where meta_timestamp {$comparison} %s';
	}

	if ( ! empty ($meta_key ) )
		$sql .= ' AND meta_key = %s';

	$sql = $wpdb->prepare( $sql, $timestamp, $meta_key );
	$meta_rows = $wpdb->get_results( $sql, OBJECT  );

	return $meta_rows;
}

function wpsc_meta_migrate( $meta_object_type ) {
	global $wpdb;
	$legacy_meta_table = $wpdb->prefix.'wpsc_meta';
	$sql = "SELECT meta_id, object_id, meta_key, meta_value FROM `{$legacy_meta_table}` WHERE object_type ='%s'";

	$old_meta_rows = $wpdb->get_results( $wpdb->prepare( $sql , 'wpsc_'.$meta_object_type ) );

	foreach ( $old_meta_rows as $old_meta_row ) {
		$meta_data = maybe_unserialize( $old_meta_row->meta_value );
		add_metadata( 'wpsc_' . $meta_object_type, $old_meta_row->object_id, $old_meta_row->meta_key, $meta_data, false );
	}
}

function _wpsc_meta_migrate_wpsc_cart_item() {
	wpsc_meta_migrate( 'cart_item' );
}

function _wpsc_meta_migrate_wpsc_purchase() {
	wpsc_meta_migrate( 'purchase' );
}