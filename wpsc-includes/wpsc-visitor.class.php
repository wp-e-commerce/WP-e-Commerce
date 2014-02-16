<?php

class WPSC_Visitor {
	function __construct( $visitor_id ) {

		$this->_cart = new wpsc_cart();

		$visitor = _wpsc_get_visitor( $visitor_id );

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
					$property_name = substr( $meta_key, strlen( 'cart.' ) );
					$this->_cart->$property_name = $meta_value;
				}
			}
		}
	}

	function get( $attribute = null ) {
		$property_name = '_' . $attribute;
		return $this->$property_name;
	}

	function set( $attribute, $value ) {
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