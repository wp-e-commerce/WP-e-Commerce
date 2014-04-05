<?php

/**
 * A class that will maintain a map of keys to values, and persist it across page sessions.
 * Users of this class should treat it like a transient, it can vaporize and not be
 * available until reconstructed.  The contents of the map can be manually cleared using the
 * clear method.
 *
 * This class has these advantages over using an array in the implementation of business logic:
 *  - caching is completely transparent you don't need to
 *
 * @access public
 *
 * @since 3.8.14
 *
 */
class WPSC_Data_Map {

	/**
	 * Create the map
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param string  		a map name to uniquely identify this map so it can be saved and restored
	 * @param string|array  a callback function to re-generate the map if it can't be reloaded when it is neaded
	 *
	 */
	public function __construct( $map_name = '', $map_callback = null ) {

		$this->_map_name 		= $map_name;
		$this->_map_callback 	= $map_callback;

		// if our map is names it means we want to save the map for use some time in the future
		if ( ! empty( $this->_map_name ) ) {
			add_action( 'shutdown', array( &$this, '_save_map' ) );
		}
	}



	/**
	 * Count of items in the map
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return int
	 */
	public function data() {
		if ( is_array( $this->_map_data ) ) {
			return array_values( $this->_map_data );
		} else {
			return array();
		}
	}

	/**
	 * Count of items in the map
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return int
	 */
	public function count() {
		$count = 0;

		if ( is_array( $this->_map_data ) ) {
			$count = count( $this->_map_data );
		}

		return $count;
	}

	/**
	 * Clear the cached map
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string  a map name to uniquely identify this map so it can be saved and restored
	 */
	public function clear() {
		if ( ! empty( $this->_map_name ) ) {
			delete_transient( $this->_map_name );
		}

		$this->_map_data = null;
	}

	/**
	 * Get the value associated wit ha key from the map, or null on failure
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param string|int  $key 			for which the value will be retrieved
	 * @param any (including callable)  $default 	what to return if the key is not found
	 *
	 * @return string  the value from the data map if it is there, otherwise the value of the default parameter, or null
	 */
	public function value( $key, $default = null ) {
		if ( $this->_confirm_data_ready() ) {
			if ( isset( $this->_map_data[$key] ) ) {
				$value = $this->_map_data[$key];
			} else {
				if ( $default === null ) {
					$value = null;
				} elseif ( is_callable( $default ) ) {
					$value = call_user_func( $default );
				} else {
					$value = $default;
				}
			}
		}

		return $value;
	}

	/**
	 * Get the value associated with a key from the map, or null on failure
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param string  	$key	the key value for the map
	 * @param varied	$value 	to store in the map
	 *
	 * @return boolean true if the map data has been modified byt this or previous operations, false otherwise
	 */
	public function map( $key_or_array_of_key_values, $value = null ) {

		if ( $this->_confirm_data_ready() ) {
			// if we got a single value add it to the map
			if ( ! is_array( $key_or_array_of_key_values ) ) {
				$key = $key_or_array_of_key_values;
				if ( ! (isset( $this->_map_data[$key] )  && ( $this->_map_data[$key] == $value ) ) ) {
					$this->_map_data[$key] = $value;
					$this->_dirty = true;
				}
			} else {
				// add map entry for each element
				foreach ( $key_or_array_of_key_values as $key => $value ) {
					$this->map( $key, $value );
				}
			}
		}

		return $this->_dirty;
	}


	/**
	 * Save the map- if this map has been given a name it means we will save it as a transient when
	 *               requested or when we shutdown
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return string  a map name to uniquely identify this map so it can be saved and restored
	 */
	public function _save_map() {
		if ( $this->_dirty ) {

			// we sort the data before storing it, just to be neat
			ksort( $this->_map_data );

			// if the map is named we will save it for next time, unless it is empty, we give an
			// expiration so that transient storage mechanisms can destroy the map if space is needed
			if ( ! empty ( $this->_map_name ) ) {
				if ( ! empty( $this->_map_data ) ) {
					set_transient( $this->_map_name, $this->_map_data, 13 * WEEK_IN_SECONDS );
				} else {
					delete_transient( $this->_map_name );
				}
			}

			$this->_dirty = false;
		}

	}

	/**
	 * Make sure the data is available
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string  a map name to uniquely identify this map so it can be saved and restored
	 */
	private function _confirm_data_ready() {
		if ( ! is_array( $this->_map_data ) ) {

			// if this is a named map we can try to restore it from the transient store
			if ( ! empty ( $this->_map_name ) ) {
				$this->_map_data = get_transient( $this->_map_name );
			}

			// if we still don't have a valid map and there is a constructor callback use it
			if ( ! is_array( $this->_map_data ) && ! empty( $this->_map_callback ) && is_callable( $this->_map_callback ) ) {

				$this->_map_data = array();
				call_user_func( $this->_map_callback , $this );
				if ( ! is_array( $this->_map_data ) ) {
					$this->_map_data = array();
					$this->_dirty = true;
				}
			}

			// if we still don't have valid map data create an empty array
			if ( ! is_array( $this->_map_data ) ) {
				$this->_map_data = array();
			}

			// we have not soiled our data, note that
			$this->_dirty = false;
		}

		return (  is_array( $this->_map_data ) );

	}

	public function dirty() {
		return $this->_dirty;
	}


	/**
	 * Private properties for this class, they are declared as public so that objects of this class
	 * can be serialized, not to provide access to the outside world.
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 */
	public $_map_name 		= null;
	public $_map_callback 	= null;
	public $_map_data 		= null;
	private $_dirty    		= false;
}