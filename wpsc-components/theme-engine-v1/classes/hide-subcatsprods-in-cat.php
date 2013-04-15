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
		$this->q =& $q;
		if ( ( !isset($q->query_vars['taxonomy']) || ( "wpsc_product_category" != $q->query_vars['taxonomy'] )) )
			return false;

		add_action( 'posts_where', array( &$this, 'where' ) );
		add_action( 'posts_join', array( &$this, 'join' ) );
	}

	function where( $where ) {
		global $wpdb;

		remove_action( 'posts_where', array( &$this, 'where' ) );

		$term_id=$wpdb->get_var($wpdb->prepare('SELECT term_id FROM '.$wpdb->terms.' WHERE slug = %s ', $this->q->query_vars['term']));

		if ( !is_numeric( $term_id ) || $term_id < 1 )
			return $where;

		$term_taxonomy_id = $wpdb->get_var($wpdb->prepare('SELECT term_taxonomy_id FROM '.$wpdb->term_taxonomy.' WHERE term_id = %d and taxonomy = %s', $term_id, $this->q->query_vars['taxonomy']));

		if ( !is_numeric($term_taxonomy_id) || $term_taxonomy_id < 1)
			return $where;

		$field = preg_quote( "$wpdb->term_relationships.term_taxonomy_id", '#' );

		$just_one = $wpdb->prepare( " AND $wpdb->term_relationships.term_taxonomy_id = %d ", $term_taxonomy_id );
		if ( preg_match( "#AND\s+$field\s+IN\s*\(\s*(?:['\"]?\d+['\"]?\s*,\s*)*['\"]?\d+['\"]?\s*\)#", $where, $matches ) )
			$where = str_replace( $matches[0], $just_one, $where );
		else
			$where .= $just_one;

		return $where;
	}

	function join($join){
		global $wpdb;
		remove_action( 'posts_where', array( &$this, 'where' ) );
		remove_action( 'posts_join', array( &$this, 'join' ) );
		if( strpos($join, "JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)" ) ){
			return $join;
		}
		$join .= " JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)";
		return $join;
	}
}

