<?php
/*
 * One shouldn't need to adjust these cache parameters under normal circumstances.  If an
 * environment or WPEC application needs to change the cache behavior they can be set
 * in a configuration file.  The parameters will change the behavior and performance of
 * the WPSC_Meta cache to fit the capabilities of your environment.
 */

if ( !defined( 'WPSC_META_CACHE_TIMEOUT') )
    define ( 'WPSC_META_CACHE_TIMEOUT' , 3600 );

if ( !defined( 'WPSC_META_CACHE_MAX_SIZE') )
    define ( 'WPSC_META_CACHE_MAX_SIZE' , 16384 );

if ( !defined( 'WPSC_META_CACHE_PREFETCH_SIZE') )
    define ( 'WPSC_META_CACHE_PREFETCH_SIZE' , 512 );

/*
 * Because WordPress cache can come in many flavors and capacities two parameters are provided
 * that influence how the WPSC_Meta class utilizes the wordpress cache.  Adjusting the
 * parameters allow the developer to impact data consistency and performance.
 *
 * ==== CACHE PERSISTENCE ====
 * Is your cache shared across all processes that can service requests? If your cache is
 * not shared, or only shared among some processes, as would be the case for some cluster
 * installations? Set the WPSC_META_CACHE_TIMEOUT very low or even to 0 to avoid data
 * consistency issues.
 *
 * When tuning these parameters consider your application's use of WPSC_Meta and your cache
 * implementation. If your WPEC application doesn't do anything unusual with WPSC_Meta values,
 * the values set are very long lived, and you are not using the meta features to store a state
 * that changes frequently, long cache timeout should work well with a long time out value.
 *
 * Cache implementations can be long-lived persistent, persistent only as long as
 * a process (like fast-cgi) is alive, or not persistent after the request is processed.
 * Based on your platform configuration you may want to adjust the cache timeout and maximum
 * size so avoid the overhead of caching meta that will never be accessed from the cache.
 *
 * ==== META CACHE PREFETCH ====
 * On a first request for a cache_object/cache_id pair, the meta cache will pre-fetch the
 * requested and related cache values that are below the size defined by
 * WPSC_META_CACHE_PREFETCH_SIZE. Meta values that are bigger than WPSC_META_CACHE_PREFETCH_SIZE
 * bytes will be fetched only when requested. Depending on how your cache is
 * accessed (mapped memory, tcp-ip/, file, etc) and the cache capacity, you may want to
 * raise or lower the WPSC_META_CACHE_PREFETCH_SIZE parameter.
 *
 * Adjusting WPSC_META_CACHE_PREFETCH_SIZE could also improve performance when there is
 * are many WPSC_Meta cache items.  Meta for each object_type/object_id pair is stored into
 * and retrieved from the meta cache as a group. Most standard meta values are short strings
 * or numbers.  When the size of all of the meta values for an object_type /object_id pair
 * fit into one request some cache implementations (MEMCACHED is an example) may be
 * significantly faster.
 *
 * ==== CACHING LARGE VALUES ====
 * It is possible for themes or plugins to store very long values as meta.  The meta values
 * could be anything, files, pictures, html.serialized arrays, whatever.  Caching and retrieving
 * very large values can be expensive, and may be more than retrieving the information from
 * a database. Even if the storage or retrieval is not expensive, storing long values into
 * a cache may significantly impact cache performance.
 *
 * The WPSC_META_CACHE_MAX_SIZE defines a threshold over with a value will not be cached.
 *
 */


class WPSC_Meta
{
	/**
	 * Constructor for the meta object. If no arguments are passed, this simply
	 * create a new empty object. Otherwise, this will get the wpsc meta from the
	 * DB by using object type and object id
	 *
	 * Eg:
	 *
	 * // get wpsc download meta collection with ID number 23
	 * $log = new WPSC_Meta( 'wpsc_download', 23 );
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string 	Optional object_type object type of meta
	 * @param int 		Optional object_id object type's id
	 */
	public function WPSC_Meta( $object_type = false, $object_id = false ) {

		$this->object_type = $object_type;
		$this->object_id = $object_id;

		// if the id is specified, try to get from cache
		if ( false !== $object_id  ) {
			$cached_data = wp_cache_get( $this->cache_key(), self::CACHE_GROUP );
			$this->data = ($cached_data!==false) ? $cached_data : array();
			$this->cached = ( false !== $this->data );
		} else {
			$this->data = array();
		}
	}

	/**
	 * Deletes wpsc meta
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param $metas_to_delete 	string|array|int identifier(s) for meta to delete,
	 * 							empty string deletes all meta for current object,
	 * 							non empty string deletes single meta value, array
	 * 							of strings deletes meta values matching strings,
	 * 							single int deletes single meta value by meta_id
	 *
	 * @return WPSC_Meta $meta  The meta object after the delete has happened,
	 * 							will return false if there is a severe error
	 *                          to stop object chaining
	 */
	public function delete( $metas_to_delete = false ) {
		global $wpdb;

		// case where a meta id is passed
		if ( is_int($metas_to_delete) ) {
			return $this->delete_meta_key_by_id( $meta_to_delete );
		}

		/*
		 *  A little safety check here to be sure we have both an object type and object id.
		 *  If we don't have them both we certainly don't want to iterate over the entire
		 *  meta table and delete everything!
		 */
		if ( empty( $this->object_type ) ||  empty( $this->object_id) )
			return false;

		if ( empty ($metas_to_delete) ) {
			// iterate over wach element so that the delete actions are triggered for each meta key
			$metas_to_delete = array_keys($this->data);
		} elseif ( !is_array($metas_to_delete) ) {
			$metas_to_delete = array( $metas_to_delete );
		}

		foreach ( $metas_to_delete as $meta_to_delete ) {
			do_action( 'wpsc_meta_before_delete',  $this->object_type, $this->object_id, $meta_to_delete );

			if ( ( $meta_id !== false )  || ( $object_type !== false && $object_id !== false) ) {
				$sql = $wpdb->prepare(
						'DELETE FROM ' . WPSC_TABLE_META . ' WHERE object_type = %s AND object_id = %d AND meta_key = %s ',
						$this->object_type, $this->object_id, $meta_to_delete
						);

				$result = $wpdb->query( $sql );
				do_action( 'wpsc_meta_delete', $this->object_type, $this->object_id, $meta_to_delete );
			}

			if ( isset( $this->data[$meta_to_delete] ) ) {
				unset( $this->data[$meta_to_delete] );
			}
		}

		$this->update_cache();

		return $this;
	}
	/**
	 * Whether the meta key for this object exits
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return bool True if it exists. Otherwise false.
	 */
	public function exists($meta_key='') {
		$this->fetch();

		if ( empty ($meta_key) )
			$exists = !empty( $this->data );
		else
			$exists = isset( $this->data[$meta_key] );

		return $exists;
	}

	/**
	 * Returns the value of the specified meta value
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param String | array | int 	$meta_key Name of the meta proprty, if empty will return all
	 * 								properties as an associative array,  if an array of meta keys is passes
	 * 								will return	all properties identified by the elements of the array, if
	 * 								a single integer is passed it will be treated as a meta_id and the
	 * 								meta_value associated with the meta_id will returned
	 * @return mixed
	 */
	public function get( $meta_key = '' ) {

		/*
		 *  A little safety check here to be sure we have both an object type and object id.
		 *  If we don't have them both we certainly don't want to iterate over the entire
		 *  meta table and return everything!
		 */
		if ( empty( $this->object_type ) ||  empty( $this->object_id) )
			return false;

		// lazy load the purchase log row if it's not fetched from the database yet
		if ( empty( $this->data ) || ! $this->fetched )
			$this->fetch();

		// case where a meta id is passed
		if ( is_int($meta_key) ) {
			return $this->get_meta_value_by_id( $meta_key );
		} elseif ( empty ($meta_key) ) {
			// iterate over wach element so that the delete actions are triggered for each meta key
			$meta_keys_to_get = array_keys($this->data);
		} elseif ( !is_array($meta_key) ) {
			$meta_keys_to_get = array( $meta_key );
		} else {
			$meta_keys_to_get = $meta_key;
		}

		$meta_values = array();

		foreach ( $meta_keys_to_get as $meta_key_to_get ) {
			$value = '';

			if ( isset( $this->data[$meta_key_to_get] ) ) {
				if ( !empty( $this->data[$meta_key_to_get]->meta_value ) ) {
					$value = $this->data[$meta_key_to_get]->meta_value;
				} else {
					$meta_row = self::get_row( $this->data[$meta_key_to_get]->meta_id );
					if ( isset($meta_row->meta_value) ) {
						$value = $meta_row->meta_value;
						$this->data[$meta_key_to_get]->meta_value = $value;
					}
				}
			}

			if ( !empty ( $value ) )
				$value = maybe_unserialize( $value );

			$value = apply_filters( 'wpsc_meta_get', $this->object_type, $this->object_id, $meta_key_to_get, $value, $this );

			$meta_values[$meta_key_to_get] = $value;

		}

		if ( !empty($meta_key) && !is_array($meta_key) ) {
			$result = $meta_values[$meta_key];
		} else {
			$result = $meta_values;
		}

		return $result;
	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param mixed $key_value_pairs_or_key Name of the meta value, or an array containing key
	 *                   value pairs
	 * @param string|int $value Optional. Defaults to false. In case $key is a string,
	 *                          this should be specified.
	 * @return WPSC_Purchase_Log The current object (for method chaining)
	 */
	public function set( $key_value_pairs_or_key, $value = null ) {
		global $wpdb;

		if ( is_int($key_value_pairs_or_key) && ($value != null)) {
			return $this->set_meta_value_by_id( $key_value_pairs_or_key, $value );
		}

		if ( !is_array($key_value_pairs_or_key) ) {
			$key_value_pairs = array( $key_value_pairs_or_key=>$value );
		} else {
			$key_value_pairs = $key_value_pairs_or_key;
		}

		/*
		 * Do a (hopefully) quick pass through the values being set to validate
		 * them.  Don't allow empty meta keys to be stored.  Function will return
		 * false if there is an error, this will break method chaining and make the
		 * error visible to the caller.  We need to do this before the insert/update
		 * loop so the database doesn't get partial updates.
		 */
		foreach ( $key_value_pairs as $key =>$value ) {
			$cleaned_key = trim($key);
			if ( empty( $cleaned_key ) )
				return false;
		}

		foreach ( $key_value_pairs as $key =>$value ) {

			if(!is_serialized( $value )) {
				$value = maybe_serialize($value);
			}

			$sql = $wpdb->prepare(
							'SELECT meta_id FROM '.WPSC_TABLE_META
								.' WHERE `object_type`= %s AND `object_id` = %s AND `meta_key` = %s',
							$this->object_type, $this->object_id, $key );

			$meta_id = $wpdb->get_var( $sql);

			if ( null != $meta_id ) {
				// updating an exiting meta row
				$wpdb->update( WPSC_TABLE_META,	array( 'meta_value' => $value ), array( 'meta_id' => $meta_id ), array( '%s' ),	array( '%d' ) );
				$this->data[$key]->meta_value = $value;
			} else {
				// inserting a new meta row
				$result  =	$wpdb->insert(	WPSC_TABLE_META,
											array(
													'object_type' => $this->object_type,
													'object_id' => $this->object_id,
													'meta_key' => $key,
													'meta_value' => $value
											),
											array(
													'%s',
													'%s',
													'%s',
													'%s',
											)
									);

				$meta_id = $wpdb->insert_id;

				$this->data[$key] = (object) array(
															'object_type' => $this->object_type,
															'meta_id' => $meta_id ,
															'object_id' => $this->object_id,
															'meta_value' => $value
												);


			}

			do_action( 'wpsc_meta_set', $this->object_type, $this->object_id, $key, $this );

		}


		return $this;
	}


	/****************************************************************************************
	 * Here starts the private section of this class
	 ***************************************************************************************/
	const CACHE_GROUP = 'wpsc_meta';
	const CACHE_EXPIRE  = WPSC_META_CACHE_TIMEOUT;


	/**
	 * Contains the values fetched from the DB
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $object_type;
	private $object_id;

	/**
	 * True if this object was found in the cache
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var string
	 */
	private $cached = false;


	/**
	 * True if the DB row is fetched into $this->data.
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var string
	 */
	private $fetched = false;

	/**
	 * Array of meta data for the current object id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Get the key used to store wpsc meta in the cache
	 *
	 * @access private
	 * @static
	 * @since 3.9
	 *
	 * @param WPSC_Meta $meta The meta object that you want to store into cache
	 * @return void
	 */
	private function cache_key( ) {

		if ( empty( $this->object_type ) || empty( $this->object_id) )
			return false;

		return $this->object_type.':'.$this->object_id;
	}

	/**
	 * Update cache of the passed meta object
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param WPSC_Meta $meta The meta object that you want to store into cache
	 * @return void
	 */
	private function update_cache( ) {

		if ( WPSC_META_CACHE_TIMEOUT == 0 )
			return;

		$data_to_cache = array();

		foreach ($this->data as $meta_key => $meta_data ) {
			$data_to_cache[$meta_key] = $this->data[$meta_key];
			if ( $meta_data->meta_length > WPSC_META_CACHE_MAX_SIZE ) {
				/*
				 * If the meta value is too big, null it out,
				* it will be refetched if the cached record is used and the values is accessed
				*/
				$data_to_cache[$meta_key]->meta_value = null;
			}
		}

		if ( empty ( $data_to_cache ) ) {
			wp_cache_delete( $this->cache_key() , self::CACHE_GROUP );
			$this->cached = false; // this is for testing only, don't rely on it, cache can clear at any time
		} else {
			$result = wp_cache_set( $this->cache_key() , $data_to_cache, self::CACHE_GROUP, self::CACHE_EXPIRE );
			$this->cached = true;  // this is for testing only, don't rely on it, cache can clear at any time
		}

	}

	/**
	 * Deletes cache of a meta (either by using the meta ID or sessionid)
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param string $value The value to query
	 * @param string $col Optional. Defaults to 'id'. Whether to delete cache by using
	 *                    a purchase log ID or sessionid
	 * @return void
	 */
	private function delete_cache( ) {

		if ( WPSC_META_CACHE_TIMEOUT == 0 )
			return;

		wp_cache_delete( $this->cache_key(), self::CACHE_GROUP );
		$this->cached = false; // this is for testing only, don't rely on it, cache can clear at any time
	}

	/**
	 * Gets a meta key from an meta id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param int $meta_id meta_id
	 * @return String The meta_key
	 */
	private static function get_meta_key_by_id( $meta_id ) {
		$sql = $wpdb->prepare( 'SELECT meta_key FROM '.WPSC_TABLE_META.' WHERE `meta_id` = %d',$meta_id );
		$meta_id = $wpdb->get_var( $sql);
		return $meta_id;
	}

	/**
	 * Gets a meta key from an meta id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param int $meta_id meta_id
	 * @return String The meta_key
	 */
	private function get_meta_value_by_id( $meta_id ) {

		$wpsc_meta = meta_by_id($meta_id);
		if ($wpsc_meta) {
			self::copy($wpsc_meta,$this);
			$meta_key = self::get_meta_key_by_id( $meta_id );
			$value = $this->get($meta_key);
		} else {
			$value = '';
		}

		return $value;
	}

	/**
	 * Sets a meta value from an meta id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param int $meta_id meta_id
	 * @param $string $value value to set
	 * @return String The meta_key
	 */
	private function set_meta_value_by_id( $meta_id, $value ) {
		$value = '';

		$wpsc_meta = meta_by_id($meta_id);
		if ($wpsc_meta) {
			self::copy($wpsc_meta,$this);
			$meta_key = self::get_meta_key_by_id( $meta_id );
			$this->set($meta_key,$value);
		}

		return $this;
	}

	/**
	 * Deletes a meta key from an meta id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param int $meta_id meta_id
	 * @return String The meta_key
	 */
	private function delete_meta_key_by_id( $meta_id ) {
		$wpsc_meta = meta_by_id($meta_id);
		if ($wpsc_meta) {
			self::copy($wpsc_meta,$this);
			$meta_key = self::get_meta_key_by_id( $meta_id );
			$this->delete($meta_key);
		}

		return $this;

	}

	/**
	 * Deletes a meta value by meta id
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param string $meta_id id of the meta row
	 * @return meta object, false when meta_id does nto exist
	 */

	private static function meta_by_id ( $meta_id ) {

		$meta_row = self::get_row( $meta_id );
		if ( !empty ($meta_row) ) {
			$wpsc_meta = new self($meta_row->object_type,$meta_row->object_id);
		} else {
			$wpsc_meta = new self();
		}

		return $wpsc_meta;
	}

	/**
	 * copy one objects properties to another object
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param string $meta_id id of the meta row
	 * @return meta object, false when meta_id does nto exist
	 */

	private function copy( $old_object , $new_object ) {
		foreach(get_object_vars($old_object) as $prop => $value)
		{
			$new_object->$prop = $value;
		}
	}

	/**
	 * Returns the value of the specified meta row
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param string $meta_id id of the meta row
	 * @return mixed, Returns OBJECT holding NULL if no result is found
	 */

	private static function get_row ( $meta_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( 'SELECT * FROM '.WPSC_TABLE_META.' WHERE `meta_id`= %s', $meta_id  );
		$meta_row = $wpdb->get_row($sql);
		return $meta_row;
	}

	/**
	 * Fetches the meta records from the database
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @return void
	 */
	private function fetch() {
		global $wpdb;

		if ( $this->fetched )
			return;

		// If we don't have a type and id, it means the object contains a new unsaved
		// row so we don't need to fetch from DB
		if ( empty( $this->object_type ) || empty( $this->object_id ) )
			return;

		$sql = 'SELECT m.meta_key, m.meta_id,m.object_type,m.object_id,length(m.meta_value) as meta_length, d.meta_value'
		. ' FROM '.WPSC_TABLE_META.' m '
		. ' LEFT JOIN ( '
		. ' SELECT d.meta_id, d.meta_value FROM '. WPSC_TABLE_META .' d '
		. ' WHERE d.object_type = \'' . $this->object_type . '\''
		. ' AND d.object_id = ' . $this->object_id
		. ' AND length(d.meta_value) < '. WPSC_META_CACHE_PREFETCH_SIZE . ' ) as d '
		. ' ON (d.meta_id = m.meta_id) '
		. ' WHERE m.object_type =\''. $this->object_type . '\' AND ' . 'm.object_id = ' . $this->object_id;

		$data_from_db = $wpdb->get_results( $sql, OBJECT_K );

		if ( !empty( $data_from_db ) ) {
			$this->data = $data_from_db;
			$this->update_cache();
		}

		do_action( 'wpsc_meta_fetched', $this );

		$this->fetched = true;
	}
}
