<?php
/* 
** NOTICE: 
** This file was automatically created, strongly suggest that it not be edited directly.
** See the code in the file wpsc-meta-init.php near line 320 for more details.
*/


//
// visitor meta functions
//

/**
 * Add meta data field to a visitor.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function wpsc_add_visitor_meta( $visitor_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'visitor' ,  $visitor_id, $meta_key , $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a visitor.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function wpsc_delete_visitor_meta( $visitor_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'visitor' ,  $visitor_id , $meta_key , $meta_value );
}

/**
 * Retrieve visitor meta field for a visitor.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function wpsc_get_visitor_meta( $visitor_id, $key = '', $single = false ) {
	return get_metadata( 'visitor' , $visitor_id , $key, $single );
}

/**
 *  Determine if a meta key is set for a given visitor.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
* @return boolean true of the key is set, false if not.
 *  is true.
 */
function wpsc_visitor_meta_exists( $visitor_id, $meta_key ) {
	return metadata_exists( 'visitor' , $visitor_id , $meta_key );
}

/**
 * Update visitor meta field based on visitor ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and visitor ID.
 *
 * If the meta field for the visitor does not exist, it will be added.

 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id $visitor ID.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function wpsc_update_visitor_meta( $visitor_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'visitor' , $visitor_id , $meta_key , $meta_value , $prev_value );
}

/**
 * Delete everything from visitor meta matching meta key.
 * This meta data function mirrors a corresponding wordpress post meta function.
 * @since 3.8.12
 *
 * @param string $visitor_meta_key Key to search for when deleting.
 * @return bool Whether the visitor meta key was deleted from the database
 */
function wpsc_delete_visitor_meta_by_key( $visitor_meta_key ) {
	return delete_metadata( 'visitor' , null , $visitor_meta_key , '' , true );
}

/**
 * Retrieve visitor meta fields, based on visitor ID.
 *
 * The visitor meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID.
 * @return array
 */
function wpsc_get_visitor_custom( $visitor_id = 0 ) {
	$visitor_id = absint( $visitor_id );
	return get_visitor_meta( $visitor_id );
}

/**
 * Retrieve meta field names for a visitor.
 *
 * If there are no meta fields, then nothing(null) will be returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param int $visitor_id visitor ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function wpsc_get_visitor_custom_keys( $visitor_id = 0 ) {
	$custom = get_visitor_custom( $visitor_id );

	if ( ! is_array( $custom ) )
		return;

	if ( $keys = array_keys( $custom ) )
		return $keys;
}

/**
 * Retrieve values for a custom visitor field.
 *
 * The parameters must not be considered optional. All of the visitor meta fields
 * will be retrieved and only the meta field key values returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.12
 *
 * @param string $metakey Meta field key.
 * @param int $visitor_id visitor ID
 * @return array Meta field values.
 */
function wpsc_get_visitor_custom_values( $metakey = '', $visitor_id = 0 ) {
	
	if ( ! $key )
		return null;

	$custom = get_visitor_custom( $visitor_id );

	return isset( $custom[$key] ) ? $custom[$key] : null;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.12
 *
 * @param function $callback function to invoke once for each meta matching criteria 
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed, 
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return int count of meta items matching the criteria
 */
function wpsc_get_visitor_meta_by_timestamp( $callback = null, $timestamp = 0, $comparison = '>', $metakey = '' ) {
	return wpsc_get_meta_by_timestamp( 'visitor', $callback , $timestamp , $comparison , $metakey );
}





