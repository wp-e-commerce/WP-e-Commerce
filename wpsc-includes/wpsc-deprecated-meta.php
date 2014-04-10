<?php
/*
 * This file contains all the logic the deprecation and upgrade of all different kinds
 * of WPEC customer, purchase, visitor, cart_item and other meta
 *
 * Usage:
 *
 * When you deprecate a meta value you should typically create put three functions into this file.
 *
 * 1) A function to be called when the database upgrade happens to bring it up to
 *    the correct version for the release.  This may mean going finding all of the
 *    deprecated meta values and changing them into a different value or jsut deleting
 *    the value all together.
 *
 * 2) A hook that will run when any function/theme/plugin 'gets' the deprecated meta value.
 *    The hook should return an appropriate value, and can use available logging methods
 *    to try to inform the developer of the change.
 *
 * 3) A hook that will run when a function/theme/plugin 'sets' the deprecated meta value.
 *    The hook should store an appropriate value, and can use available logging methods
 *    to try to inform the developer of the change.
 *
 * For each deprecated meta value there is a 'define' near the top of the file that controls if the
 * deprecation routines for any specific value should be enabled. create a control value for any
 * meta value you are deprecating using this format:
 *     define ( 'WPSC_DEPRECATE_[META TYPE]_[META_KEY_NAME]', true|false );
 *
 * If you want to deprecate meta conditionally based on the current WPEC version number replace
 * the true or false with an appropriate expression.
 *
 * By way of an example look at the the three functions that manage the deprecation of the
 * 'checkout_details' meta item below:
 *		_wpsc_cleanup_customer_meta_checkout_details
 *      _wpsc_get_deprecated_customer_meta_checkout_details
 *      _wpsc_update_deprecated_customer_meta_checkout_details
 *
 */

//////////////////// Deprecation Handling Control //////////////////////////

// meta deprecations for 3.8.14

// enable deprecation handling for customer meta with key 'checkout_details'
if ( ! defined( 'WPSC_DEPRECATE_CUSTOMER_CHECKOUT_DETAILS') )
	define( 'WPSC_DEPRECATE_CUSTOMER_CHECKOUT_DETAILS', true );

//
//////////////////// End deprecation Handling Control //////////////////////////


// manage deprecation of customer meta with key 'checkout_details'
if ( WPSC_DEPRECATE_CUSTOMER_CHECKOUT_DETAILS ) {

	/**
	 * Function to call to convert/delete/translate the customer meta value checkout_details
	 *
	 * @since  3.8.14
	 * @param  string|int $id Customer ID. Optional. Defaults to current customer
	 * @return array        checkout details array
	 */
	function _wpsc_cleanup_visitor_meta_checkout_details() {
		remove_filter( 'wpsc_got_visitor_meta_checkout_details', '_wpsc_get_deprecated_visitor_meta_checkout_details', 1, 3 );

		$meta_item_ids = wpsc_get_meta_ids_by_meta_key( 'visitor', 'checkout_details' );

		foreach ( $meta_item_ids as $meta_item_id ) {
			if ( $meta_item = _wpsc_get_meta_by_meta_id( 'visitor', $meta_item_id ) ) {
				$old_meta_timestamp = strtotime( $meta_item->meta_timestamp );

				// we use the  timestamp from the billing first name field as a proxy for the freshness of customer meta data set
				// the billing first name is required in all of the data collection cases
				$new_meta_visitor_ids = _wpsc_get_meta_ids( 'visitor', $meta_item->wpsc_visitor_id, 'billingfirstname' );

				if ( ! empty ( $new_meta_visitor_ids ) ) {
					$new_meta_timestamp = strtotime( wpsc_get_meta_id_timestamp( 'visitor', $new_meta_visitor_ids[0] ) );
				} else {
					$new_meta_timestamp = 0;
				}

				// if the old data is newer than any new data we can replace it
				if ( $old_meta_timestamp > $new_meta_timestamp ) {
					_wpsc_update_deprecated_visitor_meta_checkout_details( $meta_item->meta_value, 'checkout_details', $meta_item->wpsc_visitor_id );
				}
			}
		}


		add_filter( 'wpsc_got_visitor_meta_checkout_details', '_wpsc_get_deprecated_visitor_meta_checkout_details', 1, 3 );
	}

	/**
	 * Update the meta values from the contents of a meta value that mirrors what was once "checkout_details".
	 *
	 * @since  3.8.14
	 * @param  string|int $id Customer ID. Optional. Defaults to current customer
	 * @return array        checkout details array
	 */
	function _wpsc_get_deprecated_visitor_meta_checkout_details(  $meta_value, $key = 'checkout_details', $id = null ) {

		if ( ! $id ) {
			$id = wpsc_get_current_customer_id();
		}

		remove_filter( 'wpsc_got_visitor_meta_checkout_details', '_wpsc_get_deprecated_visitor_meta_checkout_details', 1, 3 );

		global $wpdb;

		$form_sql = 'SELECT * FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE `active` = "1" ORDER BY `checkout_set`, `checkout_order`;';
		$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

		$meta_data_in_old_format = array();

		foreach ( $form_data as $index => $form_field ) {
			if ( ! empty ( $form_field['unique_name'] ) ) {
				$meta_key   = $form_field['unique_name'];
				$meta_value = wpsc_get_visitor_meta( $id , $meta_key, true );

				switch ( $form_field['type'] ) {
					case 'delivery_country':
						if ( wpsc_has_regions( $meta_value ) ) {
							$meta_value = array( $meta_value, wpsc_get_customer_meta( 'shippingregion' ) );
						}

						$meta_data_in_old_format[$form_field['id']] = $meta_value;
						break;

					case 'country':
						if ( wpsc_has_regions( $meta_value ) ) {
							$meta_value = array( 0 => $meta_value, wpsc_get_customer_meta( 'billingregion' )  );
						}

						$meta_data_in_old_format[$form_field['id']] = $meta_value;
						break;

					default:
						$meta_data_in_old_format[$form_field['id']] = $meta_value;
						break;
				}
			}
		}

		$deprecated_meta_value = wpsc_get_visitor_meta( $id, $key, true );
		if ( ! empty( $deprecated_meta_value ) ) {
			wpsc_delete_visitor_meta( $id, $key );
		}

		add_filter( 'wpsc_got_visitor_meta_checkout_details', '_wpsc_get_deprecated_visitor_meta_checkout_details', 1, 3 );

		return $meta_data_in_old_format;
	}

	add_filter( 'wpsc_got_visitor_meta_checkout_details', '_wpsc_get_deprecated_visitor_meta_checkout_details', 1, 3 );

	/**
	 * Get a deprecated customer meta value that mirrors what was once "checkout_details".
	 *
	 * @since  3.8.14
	 * @param  string|int $id Customer ID. Optional. Defaults to current customer
	 * @return array        checkout details array
	 */
	function _wpsc_update_deprecated_visitor_meta_checkout_details(  $meta_data_in_old_format, $key = 'checkout_details', $id = null ) {
		global $wpdb;

		if ( ! $id ) {
			$id = wpsc_get_current_customer_id();
		}

		$form_sql = 'SELECT * FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE `active` = "1" ORDER BY `checkout_set`, `checkout_order`;';
		$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

		foreach ( $form_data as $index => $form_field ) {
			if (  isset( $meta_data_in_old_format[$form_field['id']] ) ) {

				$meta_key = $form_field['unique_name'];
				$meta_value = $meta_data_in_old_format[$form_field['id']];

				switch ( $form_field['type'] ) {
					case 'delivery_country':
						if ( is_array( $meta_value ) && count( $meta_value ) == 2 ) {
							wpsc_update_visitor_meta( 'shippingcountry', $meta_value[0], $id );
							wpsc_update_visitor_meta( 'shippingregion', $meta_value[1], $id );
						} else {
							if ( is_array( $meta_value ) ) {
								$meta_value = $meta_value[0];
							}
							wpsc_update_visitor_meta( 'shippingcountry', $meta_value, $id );
							wpsc_update_visitor_meta( 'shippingregion', $id );
						}

						break;

					case 'country':
						if ( is_array( $meta_value ) && count( $meta_value ) == 2 ) {
							wpsc_update_visitor_meta( 'billingcountry', $meta_value[0], $id );
							wpsc_update_visitor_meta( 'billingregion', $meta_value[1], $id );
						} else {
							if ( is_array( $meta_value ) ) {
								$meta_value = $meta_value[0];
							}

							wpsc_update_visitor_meta( 'billingcountry', $meta_value, $id );
							wpsc_update_visitor_meta( 'billingregion', $id );
						}

						break;

					default:
						wpsc_update_visitor_meta( $meta_key, $meta_value, $id );
						break;
				}
			}
		}

		$deprecated_meta_value = wpsc_get_visitor_meta( $id, $key, true );
		if ( ! empty( $deprecated_meta_value ) ) {
			wpsc_delete_visitor_meta( $id, $key );
		}

		return $meta_data_in_old_format;

	}

	add_filter( 'wpsc_updated_visitor_meta_checkout_details', '_wpsc_update_deprecated_visitor_meta_checkout_details', 10, 3 );

}

