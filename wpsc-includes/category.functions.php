<?php
/**
 * WP eCommerce category display functions
 *
 * These are functions for the wp-eCommerce categories
 * I would like to use an object and the theme engine for this, but it uses a recursive function, and I cannot think of a way to make that work with an object like the rest of the theme engine.
 *
 * @package wp-e-commerce
 * @since 3.7
 */

/**
* wpsc_get_term_parents - get all parents of the term
*
* @param int $id - id of the term
* @return array of term objects or empty array if anything went wrong or there were no parents
*/
function wpsc_get_term_parents( $term_id, $taxonomy ) {
	$term = get_term( $term_id, $taxonomy );

	if( empty( $term->parent ) )
		return array();

	$parent = get_term( $term->parent, $taxonomy );
	if ( is_wp_error( $parent ) )
		return array();

 	$parents = array( $parent->term_id );

	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $parents ) ) {
		$parents = array_merge( $parents, wpsc_get_term_parents( $parent->term_id, $taxonomy ) );
	}

	return $parents;
}

/**
 * Get Terms Category Sort Filter
 *
 * This sorts the categories when a call to get_terms is made.
 *
 * If $args requests that term objects (default) are returned then
 * we are able to pre-fetch the term meta in bulk.
 *
 * @param   object|array  $terms       Array of term objects or field values.
 * @param   array         $taxonomies  Taxonomies.
 * @param   array         $args        Args passed to get_terms().
 * @return  object|array  $terms
 */

function wpsc_get_terms_category_sort_filter( $terms, $taxonomies, $args ) {

	if ( in_array( 'wpsc_product_category', $taxonomies ) ) {

		$new_terms = array();
		$unsorted  = array();
		$term_ids  = array();

		// If something other than term objects are requested, don't sort.
		if ( isset( $args['fields'] ) && 'all' != $args['fields'] ) {
			return $terms;
		} else {
			$term_ids = wp_list_pluck( $terms, 'term_id' );
		}

		// Pre-fetch category meta in bulk if we've been able to grab the IDs.
		if ( ! empty( $term_ids ) ) {
			wpsc_update_meta_cache( 'wpsc_category', $term_ids );
		}

		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) {
				return $terms;
			}

			$term_order = ( $term->taxonomy == 'wpsc_product_category' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_category' ) : null;
			$term_order = (int) $term_order;

			// unsorted categories should go to the top of the list
			if ( $term_order == 0 ) {
				$term->sort_order = $term_order;
				$unsorted[]       = $term;
				continue;
			}

			while ( isset( $new_terms[ $term_order ] ) ) {
				$term_order++;
			}

			$term->sort_order         = $term_order;
			$new_terms[ $term_order ] = $term;
		}

		if ( ! empty( $new_terms ) ) {
			ksort( $new_terms );
		}

		for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
			array_unshift( $new_terms, $unsorted[ $i ] );
		}

		return array_values( $new_terms );

	}

	return $terms;
}

add_filter( 'get_terms', 'wpsc_get_terms_category_sort_filter', 10, 3 );

function wpsc_get_terms_variation_sort_filter( $terms ) {
	$new_terms = array();
	$unsorted = array();

	foreach ( $terms as $term ) {
		if ( ! is_object( $term ) ) {
			return $terms;
		}

		$term_order = ( $term->taxonomy == 'wpsc-variation' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_variation' ) : null;
		$term_order = (int) $term_order;

		// unsorted categories should go to the top of the list
		if ( $term_order == 0 ) {
			$term->sort_order = $term_order;
			$unsorted[]       = $term;
			continue;
		}

		while ( isset( $new_terms[ $term_order ] ) ) {
			$term_order++;
		}

		$term->sort_order = $term_order;
		$new_terms[ $term_order ] = $term;
	}

	if ( ! empty( $new_terms ) ) {
		ksort( $new_terms );
	}

	for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
		array_unshift( $new_terms, $unsorted[ $i ] );
	}

	return array_values( $new_terms );
}

add_filter( 'get_terms','wpsc_get_terms_variation_sort_filter' );

/**
 * Hide Subcategory Products in Parent Category
 *
 * By default, taxonomy queries include posts assigned to child categories.
 * To disable this the taxonomy query needs to set `include_children` to false.
 *
 * @since   4.0
 * @access  private
 *
 * @param  WP_Query  $query  Query object.
 */
function wpsc_hide_subcatsprods_in_cat_query( $query ) {

	$show_subcatsprods_in_cat = get_option( 'show_subcatsprods_in_cat' );

	if ( ! is_admin() && ! $show_subcatsprods_in_cat && isset( $query->query_vars['wpsc_product_category'] ) && ! empty( $query->tax_query->queries ) ) {
		foreach ( $query->tax_query->queries as &$tq ) {
			if ( 'wpsc_product_category' === $tq['taxonomy'] ) {
				$tq['include_children'] = false;
			}
		}
	}

}

add_action( 'parse_tax_query', 'wpsc_hide_subcatsprods_in_cat_query' );

/**
* wpsc_category_image function, Gets the category image or returns false
*
* @param integer category ID, can be 0
* @return string url to the category image
*/
function wpsc_category_image($category_id = null) {
	if($category_id < 1)
		$category_id = wpsc_category_id();
	$category_image = wpsc_get_categorymeta($category_id, 'image');
	$category_path = WPSC_CATEGORY_DIR.basename($category_image);
	$category_url = WPSC_CATEGORY_URL.basename($category_image);
	if(file_exists($category_path) && is_file($category_path))
		return $category_url;
	return false;
}
