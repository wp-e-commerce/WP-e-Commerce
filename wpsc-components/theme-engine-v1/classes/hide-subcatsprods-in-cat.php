<?php

/**
 * wpsc_display_products_page class
 *
 * Shows only products from current category, but not from subcategories.
 *
 * @access public
 * @return void
 */

class WPSC_Hide_subcatsprods_in_cat {
	var $q;

	function get_posts( &$q ) {

		$this->_wpsc_doing_it_wrong( 'get_posts' );

		return false;

	}

	function where( $where ) {

		$this->_wpsc_doing_it_wrong( 'where' );

		return $where;
	}

	function join( $join ){

		$this->_wpsc_doing_it_wrong( 'join' );

		return $join;
	}

	/**
	 * Doing it Wrong
	 *
	 * @since   4.0
	 * @access  private
	 */
	function _wpsc_doing_it_wrong( $method ) {

		_wpsc_doing_it_wrong( 'WPSC_Hide_subcatsprods_in_cat->' . $method . '()', __( 'This class is deprecated. There is no direct replacement. Hiding subcategory products in parent categories is now handled by the private wpsc_hide_subcatsprods_in_cat_query() function.', 'wp-e-commerce' ), '4.0' );

	}

}
