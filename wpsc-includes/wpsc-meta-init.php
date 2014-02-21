<?php
if ( ! function_exists( '_wpsc_create_cart_item_meta_table' ) ) {

	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-cart-item.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-includes/wpsc-meta-purchase.php' );

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

		$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_cart_itemmeta .' ('
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

		$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_purchasemeta .' ('
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

}
