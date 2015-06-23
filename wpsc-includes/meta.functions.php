<?php

/**
 * Some parts of this code were copied from functions.bb-meta.php in bbpress
 */

function wpsc_sanitize_meta_key( $key ) {
	return preg_replace( '|[^a-z0-9_]|i', '', $key );
}

/**
 * Get meta data from the database
 *
 * Gets and caches an object's meta using the WordPress Object Cache API
 * and returns meta for a specific key.
 *
 * @internal
 *
 * @param   integer  $object_id    Object ID.
 * @param   string   $meta_key     Meta key.
 * @param   string   $object_type  Object type.
 * @return  mixed                  Meta value.
 */
function wpsc_get_meta( $object_id = 0, $meta_key, $object_type ) {

	global $wpdb;

	$cache_object_id = $object_id = (int) $object_id;
	$meta_key = wpsc_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key' );
	$meta_tuple = apply_filters( 'wpsc_get_meta', $meta_tuple );

	// Get cached meta
	$meta_value = wp_cache_get( $cache_object_id, $meta_tuple['object_type'] );

	// If not cached, get and cache all object meta
	if ( $meta_value === false ) {
		$meta_values = wpsc_update_meta_cache( $meta_tuple['object_type'], $meta_tuple['object_id'] );
		$meta_value = $meta_values[ $meta_tuple['object_id'] ];
	}

	if ( isset( $meta_value[ $meta_tuple['meta_key'] ] ) ) {
		return maybe_unserialize( $meta_value[ $meta_tuple['meta_key'] ] );
	}

	return '';

}

/**
 * Adds and updates meta data in the database
 *
 * @internal
 *
 * @param   integer  $object_id    Object ID.
 * @param   string   $meta_key     Meta key.
 * @param   mixed    $meta_value   Meta value.
 * @param   string   $object_type  Object type.
 * @param   boolean  $global       ?
 * @return  boolean
 */
function wpsc_update_meta( $object_id = 0, $meta_key, $meta_value, $object_type, $global = false ) {

	global $wpdb;

	if ( ! is_numeric( $object_id ) || empty( $object_id ) && ! $global ) {
		return false;
	}

	$cache_object_id = $object_id = (int) $object_id;
	$meta_key = wpsc_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value' );
	$meta_tuple = apply_filters( 'wpsc_update_meta', $meta_tuple );

	$meta_value = $_meta_value = maybe_serialize( $meta_tuple['meta_value'] );
	$meta_value = maybe_unserialize( $meta_value );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_META . "` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $meta_tuple['object_type'], $meta_tuple['object_id'], $meta_tuple['meta_key'] ) );

	if ( ! $cur ) {
		$wpdb->insert( WPSC_TABLE_META, array( 'object_type' => $meta_tuple['object_type'], 'object_id' => $meta_tuple['object_id'], 'meta_key' => $meta_tuple['meta_key'], 'meta_value' => $_meta_value ) );
	} elseif ( $cur->meta_value != $meta_value ) {
		$wpdb->update( WPSC_TABLE_META, array( 'meta_value' => $_meta_value ), array( 'object_type' => $meta_tuple['object_type'], 'object_id' => $meta_tuple['object_id'], 'meta_key' => $meta_tuple['meta_key'] ) );
	}

	wp_cache_delete( $cache_object_id, $meta_tuple['object_type'] );

	if ( ! $cur ) {
		return true;
	}

}

/**
 * Deletes meta data from the database
 *
 * @internal
 *
 * @param   integer  $object_id    Object ID.
 * @param   string   $meta_key     Meta key.
 * @param   mixed    $meta_value   Meta value.
 * @param   string   $object_type  Object type.
 * @param   boolean  $global       ?
 * @return  boolean
 */
function wpsc_delete_meta( $object_id = 0, $meta_key, $meta_value, $object_type, $global = false ) {

	global $wpdb;

	if ( ! is_numeric( $object_id ) || empty( $object_id ) && ! $global ) {
		return false;
	}

	$cache_object_id = $object_id = (int) $object_id;
	$meta_key = wpsc_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value' );
	$meta_tuple = apply_filters( 'wpsc_delete_meta', $meta_tuple );

	$meta_value = maybe_serialize( $meta_tuple['meta_value'] );

	if ( empty( $meta_value ) ) {
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `" . WPSC_TABLE_META . "` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $meta_tuple['object_type'], $meta_tuple['object_id'], $meta_tuple['meta_key'] );
	} else {
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `" . WPSC_TABLE_META . "` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s AND `meta_value` = %s", $meta_tuple['object_type'], $meta_tuple['object_id'], $meta_tuple['meta_key'], $meta_value );
	}

	if ( ! $meta_id = $wpdb->get_var( $meta_sql ) ) {
		return false;
	}
	$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_META . "` WHERE `meta_id` = %d", $meta_id ) );

	wp_cache_delete( $cache_object_id, $meta_tuple['object_type'] );

	return true;

}

/**
 * Update Meta Cache
 *
 * Query database to get meta for objects, update the cache and return the object meta.
 *
 * @param   string     $object_type  Object type.
 * @param   int|array  $object_ids   Object ID or IDs.
 * @return  array                    Array of objects and cached values.
 */
function wpsc_update_meta_cache( $object_type, $object_ids ) {

	global $wpdb;

	if ( ! $object_type || ! $object_ids ) {
		return false;
	}

	// If $object_ids is a string, convert to array
	if ( ! is_array( $object_ids ) ) {
		$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
		$object_ids = explode( ',', $object_ids );
	}

	$object_ids = array_map( 'intval', $object_ids );

	$ids = array();
	$cache = array();

	// Only need to retrieve objects that aren't already cached
	foreach ( $object_ids as $id ) {
		$cached_object = wp_cache_get( $id, $object_type );
		if ( false === $cached_object ) {
			$ids[] = $id;
		} else {
			$cache[ $id ] = $cached_object;
		}
	}

	if ( empty( $ids ) ) {
		return $cache;
	}

	$id_list = join( ',', $ids );
	$meta_list = $wpdb->get_results( $wpdb->prepare( "SELECT object_id, meta_key, meta_value FROM " . WPSC_TABLE_META . " WHERE `object_type` = '%s' AND `object_id` IN ( " . $id_list . " )", $object_type ), ARRAY_A );

	// Add results to cache array
	if ( ! empty( $meta_list ) ) {
		foreach ( $meta_list as $metarow ) {
			$mpid = intval( $metarow[ 'object_id' ] );
			$mkey = $metarow['meta_key'];
			$mval = $metarow['meta_value'];

			// Add a value to the current pid/key:
			$cache[ $mpid ][ $mkey ] = $mval;
		}
	}

	// Update cache
	foreach ( $ids as $id ) {
		if ( ! isset( $cache[ $id ] ) ) {
			$cache[ $id ] = array();
		}
		wp_cache_add( $id, $cache[ $id ], $object_type );
	}

	return $cache;
}

/**
 * category meta functions are as follows:
 */

/**
 * Retrieve meta field for a category
 *
 * @param  int    $cat_id   Category ID.
 * @param  string $meta_key The meta key to retrieve.
 * @return mixed            Will be value of meta data field
 */
function wpsc_get_categorymeta( $cat_id, $meta_key ) {
	return wpsc_get_meta( $cat_id, $meta_key, 'wpsc_category' );
}

/**
 * Update meta field for a category
 *
 * @param  int    $cat_id     Category ID.
 * @param  string $meta_key   The meta key to retrieve.
 * @param  string $meta_value The value to be stored.
 * @return mixed              True if updated
 */
function wpsc_update_categorymeta( $cat_id, $meta_key, $meta_value ) {
	return wpsc_update_meta( $cat_id, $meta_key, $meta_value, 'wpsc_category' );
}

/**
 * Delete meta field for a category
 *
 * @param  int    $cat_id     Category ID.
 * @param  string $meta_key   The meta key to retrieve.
 * @param  string $meta_value Value to be compared before deleting.
 * @return mixed              True if updated
 */
function wpsc_delete_categorymeta( $cat_id, $meta_key, $meta_value = '' ) {
	return wpsc_delete_meta( $cat_id, $meta_key, $meta_value, 'wpsc_category' );
}
/**
 * category meta functions end here.
 */


/**
 * product meta functions start here
 * all these functions just prefix the key with the meta prefix, and pass the values through to the equivalent post meta function.
 */

/**
 * add_product_meta function.
 *
 * @access public
 * @param int       $product_id Unique product identifier
 * @param string    $key  Metadata name.
 * @param mixed     $value Metadata value. Must be serializable if non-scalar.
 * @param bool      $unique - obsolete
 * @param bool      $custom - obsolete
 * @return int|bool WordPress Meta ID on success, false on failure.
 */
function add_product_meta( $product_id, $key, $value, $unique = false, $custom = false ) {
	$key = WPSC_META_PREFIX.$key;
	return add_post_meta($product_id, $key, $value);
}

/**
 * delete_product_meta function.
 *
 * @access public
 * @param  int    $product_id
 * @param  string $key
 * @param  mixed  $value.  Optional. Metadata value. Must be serializable if non-scalar. Default empty
 * @return bool   True on success, false on failure.
 */
function delete_product_meta($product_id, $key, $value = '') {
	$key = WPSC_META_PREFIX.$key;
	return delete_post_meta($product_id, $key, $value);
}


/**
 * get_product_meta function.
 *
 * @access public
 * @param  int    $product_id
 * @param  string $key
 * @param  bool   $single  Optional. Whether to return a single value. Default false.
 * @return mixed  Will be an array if $single is false. Will be value of meta data
 *                field if $single is true.
 */
function get_product_meta($product_id, $key, $single = false) {
	$key = WPSC_META_PREFIX.$key;
	return get_post_meta($product_id, $key, $single);
}

/**
 * update_product_meta function.
 *
 * @access public
 * @param  int      $product_id
 * @param  string   $key
 * @param  mixed    Metadata value. Must be serializable if non-scalar.
 * @param  string   Optional. Previous value to check before removing.
 *                  Defaults to empty. (default: '')
 * @return int|bool Meta ID if the key didn't exist, true on successful update,
 *                  false on failure.
 */
function update_product_meta($product_id, $key, $value, $prev_value = '') {
	$key = WPSC_META_PREFIX.$key;
	return update_post_meta($product_id, $key, $value, $prev_value);
}


/**
 * product meta functions end here
 */

class wpsc_custom_meta {
	// Custom meta values
	var $custom_meta;
	var $custom_meta_count = 0;
	var $current_custom_meta = -1;
	var $custom_meta_values;

	function __construct( $post_id ) {

		$cleaned_metas = array();

		if ( ! empty( $post_id ) ) {
			$meta_values = get_post_meta( $post_id );

			foreach ( $meta_values as $key => $values ) {
				if ( ! is_protected_meta( $key,  'wpsc-product' ) ) {
					if ( is_array( $values ) ) {
						foreach ( $values as $value ) {
							$cleaned_metas[] = array( 'meta_key' => $key, 'meta_value' => $value );
						}
					}
				}
			}
		}

		$this->custom_meta = $cleaned_metas;
		$this->custom_meta_count = count( $this->custom_meta );
	}

	function have_custom_meta() {
		if (($this->current_custom_meta + 1) < $this->custom_meta_count) {
			return true;
		} else if ($this->current_custom_meta + 1 == $this->custom_meta_count && $this->custom_meta_count > 0) {
			$this->rewind_custom_meta();
		}
		return false;
	}

	/*
	 * Custom Meta Loop Code Starts here
	*/
	function next_custom_meta() {
		$this->current_custom_meta++;
		$this->custom_meta_values = $this->custom_meta[$this->current_custom_meta];
		return $this->custom_meta_values;
	}


	function the_custom_meta() {
		$this->custom_meta_values = $this->next_custom_meta();
		return $this->custom_meta_values;
	}

	function rewind_custom_meta() {
		if ($this->custom_meta_count > 0) {
			$this->custom_meta_values = $this->custom_meta[0];
		}
	}
}