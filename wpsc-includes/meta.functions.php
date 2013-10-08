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
 * @param mixed $product_id
 * @param mixed $key
 * @param mixed $value
 * @param bool  $unique - obsolete
 * @param bool  $custom - obsolete
 */
function add_product_meta( $product_id, $key, $value, $unique = false, $custom = false ) {
	$key = WPSC_META_PREFIX.$key;
	return add_post_meta($product_id, $key, $value);
}

/**
 * delete_product_meta function.
 *
 * @access public
 * @param mixed $product_id
 * @param mixed $key
 * @param bool  $value. (default: '')
 */
function delete_product_meta($product_id, $key, $value = '') {
	$key = WPSC_META_PREFIX.$key;
	return delete_post_meta($product_id, $key, $value = '');
}


/**
 * get_product_meta function.
 *
 * @access public
 * @param mixed $product_id
 * @param mixed $key
 * @param bool  $single. (default: false)
 * @return void
 */
function get_product_meta($product_id, $key, $single = false) {
	$key = WPSC_META_PREFIX.$key;
	return get_post_meta($product_id, $key, $single);
}

/**
 * update_product_meta function.
 *
 * @access public
 * @param  mixed  $product_id
 * @param  mixed  $key
 * @param  mixed  $value
 * @param  string $prev_value. (default: '')
 * @return void
 */
function update_product_meta($product_id, $key, $value, $prev_value = '') {
	$key = WPSC_META_PREFIX.$key;
	return update_post_meta($product_id, $key, $value, $prev_value = '');
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

	function wpsc_custom_meta($postid) {
		global $wpdb;

		$this->custom_meta = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value, meta_id, post_id
			FROM $wpdb->postmeta
			WHERE post_id = %d
			AND `meta_key` NOT REGEXP '^_'
			ORDER BY meta_key,meta_id", $postid), ARRAY_A );

		$this->custom_meta_count = count($this->custom_meta);
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
