<?php

/**
 * Add meta data field to a cart_item.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function wpsc_add_cart_item_meta( $cart_item_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'wpsc_cart_item' , $cart_item_id, $meta_key , $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a cart_item.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function wpsc_delete_cart_item_meta( $cart_item_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'wpsc_cart_item', $cart_item_id , $meta_key , $meta_value );
}

/**
 * Retrieve cart_item meta field for a cart_item.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function wpsc_get_cart_item_meta( $cart_item_id, $key = '', $single = false ) {
	return get_metadata( 'wpsc_cart_item' , $cart_item_id , $key, $single );
}

/**
 *  Determine if a meta key is set for a given cart_item.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
* @return boolean true of the key is set, false if not.
 *  is true.
 */
function wpsc_cart_item_meta_exists( $cart_item_id, $meta_key ) {
	return metadata_exists( 'wpsc_cart_item' , $cart_item_id , $meta_key );
}

/**
 * Update cart_item meta field based on cart_item ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and cart_item ID.
 *
 * If the meta field for the cart_item does not exist, it will be added.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id $cart_item ID.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function wpsc_update_cart_item_meta( $cart_item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'wpsc_cart_item' , $cart_item_id , $meta_key , $meta_value , $prev_value );
}

/**
 * Delete everything from cart_item meta matching meta key.
 * This meta data function mirrors a corresponding wordpress post meta function.
 * @since 3.8.12
 *
 * @param string $cart_item_meta_key Key to search for when deleting.
 * @return bool Whether the cart_item meta key was deleted from the database
 */
function wpsc_delete_cart_item_meta_by_key( $cart_item_meta_key ) {
	return delete_metadata( 'wpsc_cart_item' , null , $cart_item_meta_key , '' , true );
}

/**
 * Retrieve cart_item meta fields, based on cart_item ID.
 *
 * The cart_item meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID.
 * @return array
 */
function wpsc_get_cart_item_custom( $cart_item_id = 0 ) {
	$cart_item_id = absint( $cart_item_id );
	return get_cart_item_meta( $cart_item_id );
}

/**
 * Retrieve meta field names for a cart_item.
 *
 * If there are no meta fields, then nothing(null) will be returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $cart_item_id cart_item ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function wpsc_get_cart_item_custom_keys( $cart_item_id = 0 ) {
	$custom = get_cart_item_custom( $cart_item_id );

	if ( ! is_array( $custom ) )
		return;

	if ( $keys = array_keys( $custom ) )
		return $keys;
}

/**
 * Retrieve values for a custom cart_item field.
 *
 * The parameters must not be considered optional. All of the cart_item meta fields
 * will be retrieved and only the meta field key values returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param string $key Meta field key.
 * @param int $cart_item_id cart_item ID
 * @return array Meta field values.
 */
function wpsc_get_cart_item_custom_values( $key = '', $cart_item_id = 0 ) {

	if ( ! $key )
		return null;

	$custom = get_cart_item_custom( $cart_item_id );

	return isset( $custom[$key] ) ? $custom[$key] : null;
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
function wpsc_get_cart_item_meta_by_timestamp( $timestamp = 0, $comparison = '>', $metakey = '' ) {
	return wpsc_get_meta_by_timestamp( 'wpsc_cart_item', $timestamp , $comparison , $metakey );
}

