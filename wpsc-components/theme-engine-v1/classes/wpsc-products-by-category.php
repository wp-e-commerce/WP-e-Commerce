<?php
/**
 * wpsc_products_by_category class.
 *
 */
class wpsc_products_by_category {

	var $sql_components = array( );

	/**
	 * wpsc_products_by_category function.
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function wpsc_products_by_category( $query ) {

		_wpsc_doing_it_wrong( 'wpsc_products_by_category', __( 'This class is deprecated. There is no direct replacement. Hiding subcategory products in parent categories is now handled by the private wpsc_hide_subcatsprods_in_cat_query() function.', 'wp-e-commerce' ), '4.0' );

	}

	function join_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'join_sql' );

		return $sql;
	}

	function where_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'where_sql' );

		return $sql;
	}

	function order_by_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'order_by_sql' );

		return $sql;
	}

	function fields_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'fields_sql' );

		return $sql;
	}

	function group_by_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'group_by_sql' );

		return $sql;
	}

	function request_sql( $sql ) {

		$this->_wpsc_doing_it_wrong( 'request_sql' );

		return $sql;
	}

	/**
	 * Doing it Wrong
	 *
	 * @since   4.0
	 * @access  private
	 */
	function _wpsc_doing_it_wrong( $method ) {

		_wpsc_doing_it_wrong( 'wpsc_products_by_category->' . $method . '()', __( 'This class is deprecated. There is no direct replacement. Hiding subcategory products in parent categories is now handled by the private wpsc_hide_subcatsprods_in_cat_query() function.', 'wp-e-commerce' ), '4.0' );

	}

}

