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
 * wpsc_get_terms_category_sort_filter
 *
 * This sorts the categories when a call to get_terms is made
 * @param object array $terms
 * @param array $taxonomies
 * @param array $args
 * @return object array $terms
 */
function wpsc_get_terms_category_sort_filter($terms){
	$new_terms = array();
	$unsorted = array();

	foreach ( $terms as $term ) {
		if ( ! is_object( $term ) )
			return $terms;

		$term_order = ( $term->taxonomy == 'wpsc_product_category' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_category' ) : null;
		$term_order = (int) $term_order;

		// unsorted categories should go to the top of the list
		if ( $term_order == 0 ) {
			$term->sort_order = $term_order;
			$unsorted[] = $term;
			continue;
		}

		while ( isset( $new_terms[$term_order] ) ) {
			$term_order ++;
		}

		$term->sort_order = $term_order;
		$new_terms[$term_order] = $term;
	}

	if ( ! empty( $new_terms ) )
		ksort( $new_terms );

	for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
		array_unshift( $new_terms, $unsorted[$i] );
	}

	return array_values( $new_terms );
}
add_filter('get_terms','wpsc_get_terms_category_sort_filter');


function wpsc_get_terms_variation_sort_filter($terms){
	$new_terms = array();
	$unsorted = array();

	foreach ( $terms as $term ) {
		if ( ! is_object( $term ) )
			return $terms;

		$term_order = ( $term->taxonomy == 'wpsc-variation' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_variation' ) : null;
		$term_order = (int) $term_order;

		// unsorted categories should go to the top of the list
		if ( $term_order == 0 ) {
			$term->sort_order = $term_order;
			$unsorted[] = $term;
			continue;
		}

		while ( isset( $new_terms[$term_order] ) ) {
			$term_order ++;
		}

		$term->sort_order = $term_order;
		$new_terms[$term_order] = $term;
	}

	if ( ! empty( $new_terms ) )
		ksort( $new_terms );

	for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
		array_unshift( $new_terms, $unsorted[$i] );
	}

	return array_values( $new_terms );
}
add_filter('get_terms','wpsc_get_terms_variation_sort_filter');

