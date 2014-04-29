<?php
/**
 * Control database upgrade to version 11
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_db_upgrade_12() {
	_wpsc_fix_billing_country_meta();
	_wpsc_fix_shipping_country_meta();
}

/**
 * make sure the billing country meta is stored as individual values not as an array
 *
 * @access private
 * @since 3.8.14
 */
function _wpsc_fix_billing_country_meta() {
	global $wpdb;

	$sql   = 'SELECT * from ' . $wpdb->wpsc_visitormeta . ' WHERE meta_key = "billingcountry"';
	$metas = $wpdb->get_results( $sql, OBJECT );

	foreach ( $metas as $meta ) {
		$meta_value = maybe_unserialize( $meta->meta_value );
		if ( is_array( $meta_value ) ) {
			wpsc_update_visitor_meta( $meta->wpsc_visitor_id, 'billingregion', $meta_value[1] );
			wpsc_update_visitor_meta( $meta->wpsc_visitor_id, 'billingcountry', $meta_value[0] );
		}
	}
}

/**
 * make sure the shipping country meta is stored as individual values not as an array
 *
 * @access private
 * @since 3.8.14
 */
function _wpsc_fix_shipping_country_meta() {
	global $wpdb;

	$sql   = 'SELECT * from ' . $wpdb->wpsc_visitormeta . ' WHERE meta_key = "shippingcountry"';
	$metas = $wpdb->get_results( $sql, OBJECT );

	foreach ( $metas as $meta ) {
		$meta_value = maybe_unserialize( $meta->meta_value );
		if ( is_array( $meta_value ) ) {
			wpsc_update_visitor_meta( $meta->wpsc_visitor_id, 'shippingregion', $meta_value[1] );
			wpsc_update_visitor_meta( $meta->wpsc_visitor_id, 'shippingcountry', $meta_value[0] );
		}
	}
}

