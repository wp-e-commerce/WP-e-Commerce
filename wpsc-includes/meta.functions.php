<?php
/**
 * Some parts of this code were copied from functions.bb-meta.php in bbpress
 */

function wpsc_sanitize_meta_key( $key ) {
	return preg_replace( '|[^a-z0-9_]|i', '', $key );
}

/**
 * Gets meta data from the database
 * This needs caching implemented for it, but I have not yet figured out how to make this work for it
 * @internal
 */
function wpsc_get_meta( $object_id = 0, $meta_key, $type ) {
	global $wpdb;
	$cache_object_id = $object_id = (int)$object_id;
	$object_type = $type;
	$value = wp_cache_get( $cache_object_id, $object_type );
	$meta_key = wpsc_sanitize_meta_key( $meta_key );
	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value', 'type' );
	$meta_tuple = apply_filters( 'wpsc_get_meta', $meta_tuple );
	extract( $meta_tuple, EXTR_OVERWRITE );
	$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT `meta_value` FROM `".WPSC_TABLE_META."` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key ) );
	$meta_value = maybe_unserialize( $meta_value );
	return $meta_value;
}

/**
 * Adds and updates meta data in the database
 *
 * @internal
 */
function wpsc_update_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false ) {
	global $wpdb;
	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global ) {
		return false;
	}
	$cache_object_id = $object_id = (int) $object_id;

	$object_type = $type;

	$meta_key = wpsc_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value', 'type' );
	$meta_tuple = apply_filters( 'wpsc_update_meta', $meta_tuple );
	extract( $meta_tuple, EXTR_OVERWRITE );

	$meta_value = $_meta_value = maybe_serialize( $meta_value );
	$meta_value = maybe_unserialize( $meta_value );

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_META."` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key ) );
	if ( !$cur ) {
		$wpdb->insert( WPSC_TABLE_META, array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key, 'meta_value' => $_meta_value ) );
	} elseif ( $cur->meta_value != $meta_value ) {
		$wpdb->update( WPSC_TABLE_META, array( 'meta_value' => $_meta_value), array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key ) );
	}
	wp_cache_delete( $cache_object_id, $object_type );

	if ( !$cur ) {
		return true;
	}
}

/**
 * Deletes meta data from the database
 *
 * @internal
 */
function wpsc_delete_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false ) {
	global $wpdb;
	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global )
		return false;

	$cache_object_id = $object_id = (int) $object_id;

	$object_type = $type;

	$meta_key = wpsc_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value', 'type' );
	$meta_tuple = apply_filters( 'wpsc_delete_meta', $meta_tuple );
	extract( $meta_tuple, EXTR_OVERWRITE );

	$meta_value = maybe_serialize( $meta_value );

	if ( empty( $meta_value ) )
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `".WPSC_TABLE_META."` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key );
	else
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `".WPSC_TABLE_META."` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s AND `meta_value` = %s", $object_type, $object_id, $meta_key, $meta_value );

	if ( !$meta_id = $wpdb->get_var( $meta_sql ) )
		return false;

	$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_META."` WHERE `meta_id` = %d", $meta_id ) );
	wp_cache_delete( $cache_object_id, $object_type );
	return true;
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