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
		global $wpdb;
		$q = $query->query_vars;


		// Category stuff for nice URLs
		if ( !empty( $q['wpsc_product_category'] ) && !$query->is_singular ) {
			$q['taxonomy'] = 'wpsc_product_category';
			$q['term'] = $q['wpsc_product_category'];
			$in_cats = '';
			$join = " INNER JOIN $wpdb->term_relationships
				ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
			INNER JOIN $wpdb->term_taxonomy
				ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
			";
			if(isset($q['meta_key']))
				$join .= " INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";

			$whichcat = " AND $wpdb->term_taxonomy.taxonomy = '{$q['taxonomy']}' ";

			$term_data = get_term_by( 'slug', $q['term'], $q['taxonomy'] );

			if( is_object( $term_data ) )
				$in_cats = array( $term_data->term_id );

			if('0' != get_option('show_subcatsprods_in_cat') && is_object($term_data)){
				$term_children_data = get_term_children( $term_data->term_id, $q['taxonomy'] );
				$in_cats = array_reverse( array_merge( $in_cats, $term_children_data ) );
			}
			if( is_array( $in_cats ) ){
				$in_cats = "'" . implode( "', '", $in_cats ) . "'";
				$whichcat .= "AND $wpdb->term_taxonomy.term_id IN ($in_cats)";
			}

			$post_type_object = get_post_type_object( 'wpsc-product' );
			$permitted_post_statuses = current_user_can( $post_type_object->cap->edit_posts ) ? "'" . implode( "', '", apply_filters( 'wpsc_product_display_status', array( 'publish' ) ) ) . "'" : "'publish'";

			$whichcat .= " AND $wpdb->posts.post_status IN ($permitted_post_statuses) ";
			$groupby = "{$wpdb->posts}.ID";

			$this->sql_components['join']     = $join;
			$this->sql_components['fields']   = "{$wpdb->posts}.*, {$wpdb->term_taxonomy}.term_id, {$wpdb->term_relationships}.term_order";
			$this->sql_components['group_by'] = $groupby;

			//what about ordering by price
			if(isset($q['meta_key']) && '_wpsc_price' == $q['meta_key']){
				$whichcat .= " AND $wpdb->postmeta.meta_key = '_wpsc_price'";
			}else{
				$this->sql_components['order_by'] = "{$wpdb->term_taxonomy}.term_id";

				// Term Taxonomy ID Ordering
				if ( $q['orderby'] == 'menu_order' ) {
					if ( $term_data ) {
						$this->sql_components['order_by'] = "{$wpdb->term_relationships}.term_order ASC";
					}
				}
			}
			$this->sql_components['where']    = $whichcat;
			add_filter( 'posts_join', array( &$this, 'join_sql' ) );
			add_filter( 'posts_where', array( &$this, 'where_sql' ) );
			add_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
			add_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
			add_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		}
	}

	function join_sql( $sql ) {
		if ( isset( $this->sql_components['join'] ) )
			$sql = $this->sql_components['join'];

		remove_filter( 'posts_join', array( &$this, 'join_sql' ) );
		return $sql;
	}

	function where_sql( $sql ) {
		if ( isset( $this->sql_components['where'] ) )
			$sql = $this->sql_components['where'];

		remove_filter( 'posts_where', array( &$this, 'where_sql' ) );
		return $sql;
	}

	function order_by_sql( $sql ) {
		$order_by_parts   = array( );
		$order_by_parts[] = $sql;

		if ( isset( $this->sql_components['order_by'] ) )
			$order_by_parts[] = $this->sql_components['order_by'];

		$order_by_parts = array_reverse( $order_by_parts );
		$sql = implode( ',', $order_by_parts );

		remove_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
		return $sql;
	}

	function fields_sql( $sql ) {
		if ( isset( $this->sql_components['fields'] ) )
			$sql = $this->sql_components['fields'];

		remove_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
		return $sql;
	}

	function group_by_sql( $sql ) {
		if ( isset( $this->sql_components['group_by'] ) )
			$sql = $this->sql_components['group_by'];

		remove_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		return $sql;
	}

	function request_sql( $sql ) {
		echo $sql . "<br />";
		remove_filter( 'posts_request', array( &$this, 'request_sql' ) );
		return $sql;
	}
}

