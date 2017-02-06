<?php
/**
 * The WP eCommerce Base Query Class
 *
 * @package wp-e-commerce
 * @since 3.11.5
 */

abstract class WPSC_Query_Base {

	/**
	 * An array of arrays of cache keys. Allows versioning the cached values,
	 * and busting cache for a group if needed (by incrementing the version).
	 *
	 * Should be structured like:
	 *
	 * protected $group_ids = array(
	 * 	'group_id' => array(
	 * 		'group'   => 'full_group_id_key',
	 * 		'version' => 1,
	 * 	),
	 * 	'group2_id' => array(
	 * 		'group'   => 'full_group2_id_key',
	 * 		'version' => 1,
	 * 	),
	 * );
	 *
	 * And fetched like:
	 *
	 * $value = $this->cache_get( $cache_key, 'group_id' );
	 *
	 * And set like:
	 *
	 * $this->cache_set( $cache_key, $value, 'group_id' );
	 *
	 * @var array
	 */
	protected $group_ids = array();

	/**
	 * Contains the values fetched from the DB
	 *
	 * @access protected
	 * @since 3.11.5
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Data that is not directly stored inside the DB but is inferred. Optional.
	 *
	 * @access protected
	 * @since 3.11.5
	 */
	protected $meta_data = array();

	/**
	 * True if the DB row is fetched into the $data array.
	 *
	 * @access protected
	 * @since 3.11.5
	 *
	 * @var boolean
	 */
	protected $fetched = false;

	/**
	 * True if the row exists in DB
	 *
	 * @access protected
	 * @since 3.11.5
	 *
	 * @var boolean
	 */
	protected $exists = false;

	/**
	 * Fetches the actual $data array.
	 * Should set $this->fetched to true, and $this->exists if row is found.
	 * Should return $this;
	 *
	 * @access protected
	 * @since 3.11.5
	 *
	 * @return WPSC_Query_Base
	 */
	abstract protected function fetch();

	/**
	 * Whether the DB row for this purchase log exists
	 *
	 * @access public
	 * @since 3.11.5
	 *
	 * @return bool True if it exists. Otherwise false.
	 */
	public function exists() {
		$this->fetch();
		return $this->exists;
	}

	/**
	 * Resets properties so any subsequent requests will be refreshed.
	 *
	 * @since  3.11.5
	 *
	 * @return void
	 */
	protected function reset() {
		$this->data = array();
		$this->fetched = false;
		$this->exists = false;
	}

	/**
	 * Returns the value of the specified property of the $data array if it exists.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @param  string $key Name of the property (column)
	 * @return mixed
	 */
	public function get( $key ) {

		// lazy load the row if it's not fetched from the database yet
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) ) {
			$this->fetch();
		}

		if ( isset( $this->data[ $key ] ) ) {
			$value = $this->data[ $key ];
		} else if ( isset( $this->meta_data[ $key ] ) ) {
			$value = $this->meta_data[ $key ];
		} else {
			$value = null;
		}

		return $this->prepare_get( $value, $key );
	}

	/**
	 * Prepares the return value for get() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @param  mixed  $value Value fetched
	 * @param  string $key   Key for $data.
	 *
	 * @return mixed
	 */
	abstract protected function prepare_get( $value, $key );

	/**
	 * Returns the entire $data array.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_data() {
		if ( empty( $this->data ) ) {
			$this->fetch();
		}

		return $this->prepare_get_data();
	}

	/**
	 * Prepares the return value for get_data() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	abstract protected function prepare_get_data();

	/**
	 * Returns the entire $meta_data array.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return array
	 */
	public function get_meta() {

		// lazy load the row if it's not fetched from the database yet
		if ( empty( $this->data ) && empty( $this->meta_data ) ) {
			$this->fetch();
		}

		return $this->prepare_get_meta();
	}

	/**
	 * Prepares the return value for get_meta() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	protected function prepare_get_meta() {
		return $this->meta_data;
	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @param mixed $key             Name of the property (column), or an array containing
	 *                               key value pairs
	 * @param string|int|null $value Optional. Defaults to null. In case $key is a string,
	 *                               this should be specified.
	 * @return WPSC_Query_Base       The current object (for method chaining)
	 */
	abstract public function set( $key, $value = null );

	/**
	 * Saves the object back to the database.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	abstract public function save();

	/**
	 * Sets a meta property to a certain value. This function should accept a key
	 * and a value as arguments, or an associative array containing key value pairs.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @param mixed $key             Name of the property (column), or an array containing
	 *                               key value pairs
	 * @param string|int|null $value Optional. Defaults to null. In case $key is a string,
	 *                               this should be specified.
	 * @return WPSC_Query_Base       The current object (for method chaining)
	 */
	public function set_meta( $key, $value = null ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $index => $value) {
				$this->set_meta( $index, $value );
			}
			return;
		}

		// lazy load the row if it's not fetched from the database yet
		if ( empty( $this->data ) && empty( $this->meta_data ) ) {
			$this->fetch();
		}

		$this->meta_data[ $key ] = $value;

		return $this;
	}

	/**
	 * Saves the meta data back to the database.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return WPSC_Query_Base  The current object (for method chaining)
	 */
	public function save_meta() {
		return $this;
	}

	/**
	 * Wrapper for wp_cache_get.
	 *
	 * @access public
	 * @since 3.11.5
	 *
	 * @see wp_cache_get()
	 *
	 * @param int|string $key      The key under which the cache contents are stored.
	 * @param string     $group_id The key for the group_ids array to compile the group
	 *                             from version/key.
	 *                             Default 0 (no expiration).
	 * @return bool|mixed         False on failure to retrieve contents or the cache
	 *                            contents on success
	 */
	public function cache_get( $key, $group_id ) {
		return wp_cache_get( $key, $this->get_group_id( $group_id ) );
	}

	/**
	 * Wrapper for wp_cache_set.
	 *
	 * @access public
	 * @since 3.11.5
	 *
	 * @see wp_cache_set()
	 *
	 * @param int|string $key      The cache key to use for retrieval later.
	 * @param mixed      $data     The contents to store in the cache.
	 * @param string     $group_id The key for the group_ids array to compile the group
	 *                             from version/key.
	 * @param int        $expire   Optional. When to expire the cache contents, in seconds.
	 *                             Default 0 (no expiration).
	 * @return bool False on failure, true on success
	 */
	public function cache_set( $key, $data, $group_id, $expire = 0 ) {
		return wp_cache_set( $key, $data, $this->get_group_id( $group_id ), $expire );
	}

	/**
	 * Wrapper for wp_cache_delete.
	 *
	 * @access public
	 * @since 3.11.5
	 *
	 * @see wp_cache_delete()
	 *
	 * @param int|string $key      What the contents in the cache are called.
	 * @param string     $group_id The key for the group_ids array to compile the group
	 *                             from version/key.
	 * @return bool True on successful removal, false on failure.
	 */
	public function cache_delete( $key, $group_id ) {
		return wp_cache_delete( $key, $this->get_group_id( $group_id ) );
	}

	/**
	 * Get the versioned group id from the $group_ids array.
	 *
	 * @since  3.11.5
	 *
	 * @param  string $group_id The key for the group_ids array to compile the group
	 *                          from version/key.
	 *
	 * @return string           The full group key.
	 */
	public function get_group_id( $group_id ) {
		$group = $this->group_ids[ $group_id ]['group'];

		if ( isset( $this->group_ids[ $group_id ]['version'] ) && $this->group_ids[ $group_id ]['version'] ) {
			$group .= '_' . $this->group_ids[ $group_id ]['version'];
		}

		return $group;
	}

}
