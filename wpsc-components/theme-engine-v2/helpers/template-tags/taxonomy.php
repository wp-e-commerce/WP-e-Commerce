<?php

/**
 * Output the permalink of a product category.
 *
 * @since 4.0
 * @uses  wpsc_get_product_category_permalink()
 *
 * @param  int|string|object $cat Optional. Either a term ID, term object or term slug.  Defaults to the main product category.
 */
function wpsc_product_category_permalink( $cat = '' ) {
	echo wpsc_get_product_category_permalink( $cat );
}

/**
 * Return the permalink of a product category.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_category_url' filter.
 * @uses  get_query_var()
 * @uses  get_term_link()
 *
 * @param  int|string|object $cat Optional. Either a term ID, term object or term slug. Defaults to the main product category.
 * @return string
 */
function wpsc_get_product_category_permalink( $cat = '' ) {
	if ( ! $cat ) {
		$cat = get_query_var( 'term' );
	}

	if ( is_numeric( $cat ) ) {
		$cat = absint( $cat );
	}

	$link = get_term_link( $cat, 'wpsc_product_category' );

	return apply_filters( 'wpsc_get_product_category_permalink', $link, $cat );
}

/**
 * Output a product tag's permalink.
 *
 * @since 4.0
 * @uses  wpsc_get_product_tag_permalink()
 *
 * @param  int|string|object $tag Optional. Either a term ID, term object or term slug. Defaults to the main product tag.
 */
function wpsc_product_tag_permalink( $tag = '' ) {
	echo wpsc_get_product_tag_permalink( $tag );
}

/**
 * Return a product tag's permalink.
 *
 * @since 4.0
 * @uses  get_query_var()
 * @uses  get_term_link()
 *
 * @param  int|string|object $tag Optional. Either a term ID, term object or term slug.  Defaults to the main product tag.
 * @return string
 */
function wpsc_get_product_tag_permalink( $tag = '' ) {
	if ( ! $tag ) {
		$tag = get_query_var( 'term' );
	}

	if ( is_numeric( $tag ) ) {
		$tag = absint( $tag );
	}

	return apply_filters( 'wpsc_get_product_tag_permalink', get_term_link( $tag, 'product_tag' ), $tag );
}

/**
 * Return the name of a product category.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_category_name' filter.
 * @uses  get_query_var()
 * @uses  get_term()
 * @uses  get_term_by()
 * @uses  is_wp_error()
 *
 * @param  int|string|object $term Optional. Either a term ID, term object or term slug.
 * @return string
 */
function wpsc_get_product_category_name( $term = '' ) {

	if ( empty( $term ) ) {
		$term = get_query_var( 'term' );
	}

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, 'wpsc_product_category' );
		} else {
			$term = get_term_by( 'slug', $term, 'wpsc_product_category' );
		}
	}

	if ( ! is_object( $term ) || is_wp_error( $term ) ) {
		return '';
	}

	return apply_filters( 'wpsc_get_product_category_name', $term->name, $term );
}

/**
 * Return the name of a product tag.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_get_product_tag_name' filter.
 * @uses  get_query_var()
 * @uses  get_term()
 * @uses  get_term_by()
 * @uses  is_wp_error()
 *
 * @param  int|string|object $term Optional. Either a term ID, term object or term slug.
 * @return string
 */
function wpsc_get_product_tag_name( $term = '' ) {

	if ( empty( $term ) ) {
		$term = get_query_var( 'term' );
	}

	if ( ! is_object( $term ) ) {
		if ( is_int( $term ) ) {
			$term = get_term( $term, 'product_tag' );
		} else {
			$term = get_term_by( 'slug', $term, 'product_tag' );
		}
	}

	if ( ! is_object( $term ) || is_wp_error( $term ) ) {
		return '';
	}

	return apply_filters( 'wpsc_get_product_tag_name', $term->name, $term );
}

/**
 * Output a list of product categories.
 *
 * The options you can use to customize the output is similar to that of {@link wp_list_categories()}.
 * However, there are many new options available to make it much more flexible to customize:
 *     'show_description'   - Whether to show category description. Defaults to false.
 *     'show_thumbnail'     - Whether to show category thumbnail. Defaults to false.
 *     'before'             - The HTML before the list. Defaults to <ul class="%s">.
 *     'after'              - The HTML after the list. Defaults to </ul>.
 *     'before_description' - The HTML before the category description. Defaults to '<div class="%s">'.
 *     'after_description'  - The HTML after the category description. Defaults to '</div>'.
 *     'before_item'        - The HTML before each individual category. Defaults to '<li class="%s">'.
 *     'after_item'         - The HTML after each individual category. Defaults to '</li>'.
 *     'before_thumbnail'   - The HTML before the category thumbnail. Defaults to '<div class="%s">'.
 *     'after_thumbnail'    - The HTML after the category thumbnail. Defaults to '</div>'.
 *     'before_nested_list' - The HTML before the list of children categories. Defaults to '<ul class="%s">'.
 *     'after_nested_list'  - The HTML after the list of children categories. Defaults to '</ul>'.
 *
 * The placeholder %s will be replaced with the class attribute of the corresonding element.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_product_category_list_class' filter.
 * @uses  wp_list_categories()
 * @uses  wp_parse_args()
 * @param  string $args [description]
 * @return [type]
 */
function wpsc_list_product_categories( $args = '' ) {
	$defaults = array(
		'before'             => '<ul class="%s">',
		'before_description' => '<div class="%s">',
		'before_item'        => '<li class="%s">',
		'before_nested_list' => '<ul class="%s">',
		'before_thumbnail'   => '<div class="%s">',
		'after'              => '</ul>',
		'after_description'  =>  '</div>',
		'after_item'         => '</li>',
		'after_nested_list'  => '</ul>',
		'after_thumbnail'    => '</div>',
		'walker'             => new WPSC_Walker_Product_Category(),
		'show_description'   => false,
		'show_option_none'   => __( 'No product categories', 'wp-e-commerce' ),
		'show_thumbnail'     => false,
		'echo'               => 1,
	);

	$r = wp_parse_args( $args, $defaults );

	$r['taxonomy'] = 'wpsc_product_category';

	extract( $r, EXTR_SKIP );

	$r['echo'] = false;
	$class     = apply_filters( 'wpsc_product_category_list_class', 'wpsc-product-category-list' );
	$output    = sprintf( $before, $class ) . "\n" . wp_list_categories( $r ) . "\n" . $after;

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}
