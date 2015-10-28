<?php
/**
 * WP e-Commerce Variations class
 *
 * This is the code that handles adding, editing and displaying variations on Products
 */


class wpsc_variations {
	// variation groups: i.e. colour, size
	public $variation_groups;
	public $variation_group_count   = 0;
	public $current_variation_group = -1;
	public $variation_group;

	// for getting the product price
	public $first_variations;

	//variations inside variation groups: i.e. ( red, green, blue ) or ( S, M, L, XL )
	public $variations;
	public $variation_count   = 0;
	public $current_variation = -1;
	public $variation;
	public $all_associated_variations;


	function __construct( $product_id ) {
		global $wpdb;

		$product_terms = wpsc_get_product_terms( $product_id, 'wpsc-variation' );
		$product_terms = wpsc_get_terms_variation_sort_filter( $product_terms );

		$this->variation_groups = array();
		$this->first_variations = array();
		$this->all_associated_variations = array();

		foreach ( $product_terms as $product_term ) {
			if ( $product_term->parent > 0 )
				$this->all_associated_variations[$product_term->parent][] = $product_term;
			else
				$this->variation_groups[] = $product_term;
		}

		// Sort variation orders
		foreach ( $this->all_associated_variations as $variation_set => &$terms ) {
			$terms = wpsc_get_terms_variation_sort_filter( $terms );

			array_unshift(
				$this->all_associated_variations[$variation_set], (object) array(
				'term_id' => 0,
				'name'    => __( '-- Please Select --', 'wp-e-commerce' ),
				)
			);
		}

		// Filters to hook into variations to sort etc.
		$this->variation_groups          = apply_filters( 'wpsc_variation_groups', $this->variation_groups, $product_id );
		$this->all_associated_variations = apply_filters( 'wpsc_all_associated_variations', $this->all_associated_variations, $this->variation_groups, $product_id );

		//the parent_id is the variation group id we need to use this to alter the object ( variants )
		// inside each of these arrays
		$parent_ids = array_keys( $this->all_associated_variations );

		foreach ( (array) $parent_ids as $parent_id ) {
				//sort the variants by their term_order which is the array key
				ksort( $this->all_associated_variations[$parent_id] );
				//once sorted renumber the array keys back from 0
				$this->all_associated_variations[$parent_id] = array_values( $this->all_associated_variations[$parent_id] );
		}

		foreach ( (array) $this->variation_groups as $variation_group ) {
			$variation_id = $variation_group->term_id;
			$this->first_variations[] = $this->all_associated_variations[$variation_id][0]->term_id;
		}

		$this->variation_group_count = count( $this->variation_groups );
	}

	/*
	 * ( Variation Group and Variation ) Loop Code Starts here
	*/
	function get_variation_groups() {
		global $wpdb;
		$this->variation_group_count = count( $this->variation_groups );
		$this->get_first_variations();
	}


	function next_variation_group() {
		$this->current_variation_group++;
		$this->variation_group = $this->variation_groups[$this->current_variation_group];
		return $this->variation_group;
	}


	function the_variation_group() {
		$this->variation_group = $this->next_variation_group();
		$this->get_variations();
	}

	function have_variation_groups() {
		if ( $this->current_variation_group + 1 < $this->variation_group_count ) {
			return true;
		} else if ( $this->current_variation_group + 1 == $this->variation_group_count && $this->variation_group_count > 0 ) {
			$this->rewind_variation_groups();
		}
		return false;
	}

	function rewind_variation_groups() {
		$this->current_variation_group = -1;
		if ( $this->variation_group_count > 0 ) {
			$this->variation_group = $this->variation_groups[0];
		}
	}

	function get_first_variations() {
		global $wpdb;
		return null;
	}


	function get_variations() {
		global $wpdb;
		$this->variations = $this->all_associated_variations[$this->variation_group->term_id];
		$this->variation_count = count( $this->variations );
	}


	function next_variation() {
		$this->current_variation++;
		$this->variation = $this->variations[$this->current_variation];
		return $this->variation;
	}


	function the_variation() {
		$this->variation = $this->next_variation();
	}

	function have_variations() {
		if ( $this->current_variation + 1 < $this->variation_count ) {
			return true;
		} else if ( $this->current_variation + 1 == $this->variation_count && $this->variation_count > 0 ) {
			// Do some cleaning up after the loop,
			$this->rewind_variations();
		}
		return false;
	}

	function rewind_variations() {
		$this->current_variation = -1;
		if ( $this->variation_count > 0 ) {
			$this->variation = $this->variations[0];
		}
	}
}

function wpsc_get_child_object_in_select_terms( $parent_id, $terms, $taxonomy ) {
	global $wpdb;
	$sql = $wpdb->prepare(
			'SELECT tr.`object_id`
			FROM `'.$wpdb->term_relationships.'` AS tr
			LEFT JOIN `'.$wpdb->posts.'` AS posts
			ON posts.`ID` = tr.`object_id`
			WHERE tr.`term_taxonomy_id` IN ( '.implode( ',', esc_sql( $terms ) ).' ) and posts.`post_parent` = %d', $parent_id
			);
	$products = $wpdb->get_col( $sql );
	return $products;

}

/**
 * wpsc_get_child_objects_in_term function.
 * gets the
 *
 * @access public
 * @param int $parent_id
 * @param int|array of int $terms
 * @param int|array taxonomiy id(s) ti look for
 * @param array $args  additional arguments to query against
 * @return boolean|int|array result false if product not found, a single product id when
 *                           one id found, or an array of product ids if more than one found
 */
function wpsc_get_child_object_in_terms( $parent_id, $terms, $taxonomies = 'wpsc-variation', $args = array() ) {

	$parent_id = absint( $parent_id );

	if ( ! is_array( $terms ) )
		$terms = array( $terms );

	if ( ! is_array( $taxonomies ) )
		$taxonomies = array( $taxonomies );

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) )
			return new WP_Error( 'invalid_taxonomy', __( 'Invalid Taxonomy', 'wp-e-commerce' ) );
	}

	$defaults = array(
			'post_type'      => 'wpsc-product',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'post_parent'    => $parent_id,
	);

	$args = wp_parse_args( $args, $defaults );

	$terms     = array_map( 'intval', $terms );
	$tax_query = array( 'relation' => 'AND' );

	foreach ( $terms as $term_index => $term_id ) {
		$taxonomy    = isset( $taxonomies[ $term_index ] ) ? $taxonomies[ $term_index ] : 'wpsc-variation';
		$tax_query[] = array( 'taxonomy' => $taxonomy, 'field' => 'id', 'terms' => array( $term_id ), 'operator' => 'IN' );
	}

	$args['tax_query'] = $tax_query;

	$children = get_posts( $args );
	$child_id = ! empty( $children ) ? $children[0]->ID : false;

	// return false if product not found, a single product id if one id found, or an array of ids if many found
	if ( empty( $children ) ) {
		$result = false;
	} elseif ( count( $children ) == 1 ) {
		$result = $children[0]->ID;
	} else {
		$result = array();
		foreach ( $children as $child ) {
			$result[] = $child->ID;
		}
	}

	return $result;
}


/**
 * wpsc_get_child_objects_in_term function.
 * gets the
 *
 * @access public
 * @param mixed $parent_id
 * @param mixed $terms
 * @param mixed $taxonomies
 * @param array $args. ( default: array() )
 * @return void
 */
function wpsc_get_child_object_in_terms_var( $parent_id, $terms, $taxonomies, $args = array() ) {
	global $wpdb, $current_version_number;
	$wpdb->show_errors = true;
	$parent_id = absint( $parent_id );

	if ( !is_array( $terms ) )
		$terms = array( $terms );

	if ( !is_array( $taxonomies ) )
		$taxonomies = array( $taxonomies );

	foreach ( ( array ) $taxonomies as $taxonomy ) {
		if ( $current_version_number < 3.8 ) {
			if ( ! taxonomy_exists( $taxonomy ) )
				return new WP_Error( 'invalid_taxonomy', __( 'Invalid Taxonomy', 'wp-e-commerce' ) );
		} else {
			if ( !taxonomy_exists( $taxonomy ) )
				return new WP_Error( 'invalid_taxonomy', __( 'Invalid Taxonomy', 'wp-e-commerce' ) );
		}
	}

	$defaults = array( 'order' => 'ASC' );
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$order = ( 'desc' == strtolower( $order ) ) ? 'DESC' : 'ASC';

	$terms = array_map( 'intval', $terms );

	$taxonomies = "'" . implode( "', '", $taxonomies ) . "'";
	$terms = "'" . implode( "', '", $terms ) . "'";

	// This SQL statement finds the item associated with all variations in the selected combination that is a child of the target product
	$object_sql = "SELECT tr.object_id
	FROM {$wpdb->term_relationships} AS tr
	INNER JOIN {$wpdb->posts} AS posts
		ON posts.ID = tr.object_id
	INNER JOIN {$wpdb->term_taxonomy} AS tt
		ON tr.term_taxonomy_id = tt.term_taxonomy_id
	WHERE posts.post_parent = {$parent_id}
		AND tt.taxonomy IN ( {$taxonomies} )
		AND tt.term_id IN ( {$terms} )
		AND tt.parent > 0
	GROUP BY tr.object_id";
	$object_ids = $wpdb->get_results( $object_sql, ARRAY_A );
	if ( count( $object_ids ) > 0 ) {
		return $object_ids;
	} else {
		return false;
	}
}

/**
 * Given array of variation selections this works through the terms and returns the product_id for the matching variation
 *
 * For the `$variations` parameter we expect to get two 'term_ids' which correspond to the selections in the variation.
 * So if we have a blue large shirt and the blue term_id is 8 and the large term_id is 12 we would get an array that looks like.
 * They keys in the array ( 2 and 9 below ) don't actually matter and are a result of the array items on the frontend in a typical
 * 'add to cart' action from the frontend of a theme.
 *
 * array(
 *      '2' => '8',     // this is the blue term_id
 *      '9' => '12',    // this is the large term_id
 * );
 *
 * Here the keys are captured when someone clicks the 'add to cart' button and correspond with ... whatever. They don't really matter for our function.
 * Really you could pass an array of 2 term_ids here and a $product_id that has variations to match those terms and you'd get back the expected array.
 *
 * @since 4.0
 *
 * @param array     $variations     required            The array of variation selections
 * @param int       $product_id     required            The default product_id
 * @uses wpsc_get_child_objects_in_terms()              Given $product_id and product params this returns the variation product id
 * @return array    $args {
 *      @param  int     product_id                      The variation product_id
 *      @param  array   variation_values                The array of variation_values that wpsc_add_to_cart needs to populate $provided_parameters['variation_values']
 * }
 */
function wpsc_get_product_data_from_variations( $variations, $product_id ) {

	$variation_values = array();
	$original_product_id = $product_id;

	foreach ( (array) $variations as $key => $variation ) {
		$variation_values[ (int) $key ] = (int) $variation;
	}

	if ( count( $variation_values ) > 0 ) {
		$variation_product_id = wpsc_get_child_object_in_terms( $product_id, $variation_values, 'wpsc-variation' );
		if ( $variation_product_id > 0 ) {
			$product_id = $variation_product_id;
		}
	}

	/** This filter is documented in wpsc_get_product_id_from_variations */
	$product_id = apply_filters( 'wpsc_variation_product_id', absint( $product_id ), $variations, absint( $original_product_id ) );

	$return_args = array(
		'product_id'       => absint( $product_id ),
		'variation_values' => $variation_values,
	);

	return $return_args;

}

/**
 * Wrapper for wpsc_get_product_data_from_variations that returns the variation product_id
 *
 * See wpsc_get_product_data_from_variations for a full description on what needs to be passed in the $variations params
 *
 * @since 4.0
 *
 * @uses wpsc_get_product_data_from_variations()                        Returns array of data pertaining to product variations
 * @return int      $product_id                                         The product_id corresponding to the selected variation
 */
function wpsc_get_product_id_from_variations( $variations, $product_id ){

	$values = wpsc_get_product_data_from_variations( $variations, $product_id );

	/**
	 * Allows users to filter the product_id based on the variation selections
	 *
	 * @since 4.0
	 *
	 * @param int   $$values['product_id']  The variation product_id
	 * @param array $variations             The variation selections passed to the core function
	 * @param int   $product_id             The originally passed $product_id
	 * @param array $values{
	 *      @param  int     product_id         The variation product_id
	 *      @param  array   variation_values   The array of variation term_ids based on the selections sent through
	 * }
	 */
	$product_id = apply_filters( 'wpsc_variation_product_id', absint( $values['product_id'] ), $variations, $product_id, $values );

	return absint( $product_id );

}

/**
 * Wrapper for wpsc_get_product_data_from_variations that returns the variation values corresponding to the variation selections
 *
 * See wpsc_get_product_data_from_variations for a full description on what needs to be passed in the $variations params
 *
 * @since 4.0
 *
 * @uses wpsc_get_product_data_from_variations()                        Returns array of data pertaining to product variations
 * @return array      $variation_values                                 The variation values for the selected variation terms, so the term_ids as the array $values
 */
function wpsc_get_variation_values_from_variations( $variations, $product_id ){

	$values = wpsc_get_product_data_from_variations( $variations, $product_id );

	/**
	 * Allows users to filter the variation values based on the variation selections
	 *
	 * @since 4.0
	 *
	 * @param array $variations             The variation selections passed to the core function
	 * @param int   $product_id             The default passed product_id
	 * @param array $values{
	 *      @param  int     product_id         The variation product_id
	 *      @param  array   variation_values   The array of variation term_ids based on the selections sent through
	 * }
	 */
	$variation_values = apply_filters( 'wpsc_variation_values', $values['variation_values'], $product_id, $values );

	return (array) $variation_values;

}
