<?php


/**
 * WPEC Visitor Class
 * @since 3.8.14
 */
class WPSC_Visitor {

	public $valid = true;

	/**
	 * Create visitor class from visitor id
	 * @param  $visitor_id int unique visitor id
	 * @since 3.8.14
	 */
	function __construct( $visitor_id ) {

		$this->_cart = new wpsc_cart();

		$visitor = _wpsc_get_visitor( $visitor_id );
		if ( $visitor == false ) {
			$valid = false;
			return;
		}

		if ( $visitor ) {
			foreach ( $visitor as $key => $value ) {
				$property_name = '_' . $key;
				$this->$property_name = $value;
			}
		}

		$visitor_meta = wpsc_get_visitor_meta( $visitor_id );

		if ( ! empty( $visitor_meta ) ) {
			foreach ( $visitor_meta as $meta_key => $meta_value ) {
				if ( ( $i = strpos( $meta_key, 'cart.' ) ) === false ) {
					$property_name = '_' . $meta_key;
					$this->$property_name = $meta_value[0];
				} else {
					$property_name = substr( $meta_key, strlen( '_wpsc_cart.' ) );
					$this->_cart->$property_name = $meta_value[0];
				}
			}
		}

		$this->_cart = wpsc_get_visitor_cart( $visitor_id );
	}

	/**
	 * Get visitor expiration
	 * @param  $unix_time boolean  true rerutn time as unix time, false return time as string
	 * @return string expiration time
	 * @since 3.8.14
	 */
	function expiration( $unix_time = true ) {
		if ( ! ($unix_time = strtotime( $this->_expires ) ) ) {
			return false;
		}

		if ( $unix_time ) {
			return $unix_time;
		}

		return  $this->_expires;
	}

	/**
	 * Get visitor attribute
	 * @param  $attribute attribute name
	 * @return varies, attribute value
	 * @since 3.8.14
	 */
	function get( $attribute = null ) {
		if ( empty( $attribute ) ) {
			return $this;
		}

		$property_name = '_' . $attribute;
		return $this->$property_name;
	}

	/**
	 * Get visitor attribute
	 * @param  $attribute attribute name
	 * @param  $value attribute value
	 * @return this
	 * @since 3.8.14
	 */
	function set( $attribute, $value ) {

		$property_name = '_' . $attribute;
		$this->$property_name = $value;

		if ( in_array( $attribute, $visitor_table_attribute_list ) ) {
			// test if change of the attribute is permitted
			if ( $visitor_table_attribute_list( $attribute ) ) {
				wpsc_update_visitor( $this->_id, array( $attribute => $value ) );
			}
		} else {
			wpsc_update_visitor_meta( $this->id, $attribute, $value );
			return $this;
		}
	}

	/**
	 * Delete visitor attribute
	 * @param  $attribute attribute name
	 * @return this
	 * @since 3.8.14
	 */
	function delete( $attribute ) {
		$property_name = '_' . $attribute;
		if ( isset( $this->$property_name ) ) {
			unset($a->$property_name ) ;
		}

		wpsc_delete_visitor_meta( $this->id, $attribute );

		return $this;

	}

	private $visitor_table_attribute_list = array(
													// well known attributes from the 'wpsc_visitors table', true false if change allowed
													'id'          => false,
													'user_id'     => true,
													'last_active' => false,
													'expires'     => false,
													'created'     => false,
											);


	// helper function for well known variables
	function id() {
		return $this->_id;
	}

	function user_id() {
		return $this->_user_id;
	}

	function last_active() {
		return $this->_last_active;
	}

	function created() {
		return $this->_created;
	}

	function cart() {
		return $this->_cart;
	}


	//////////////////////////////////////////////////////////////////////////////////////////
	// Here are the well known attributes, functionality outside of WPEC should not
	// access these attributes directly, as they are subject to change as the implementation
	// evolves.  Instead use the get and set methods.
	public $_id          = false;
	public $_user_id     = false;
	public $_last_active = false;
	public $_expires     = false;
	public $_created     = false;
	public $_cart        = false;

}