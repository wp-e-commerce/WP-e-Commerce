<?php

add_shortcode( 'wpsc-cart'       , '_wpsc_shortcode_cart' );
add_shortcode( 'wpsc-products'   , '_wpsc_shortcode_products' );
add_shortcode( 'wpsc-add-to-cart', '_wpsc_shortcode_add_to_cart' );

/**
 * Cart shortcode.
 *
 * Usage example: [wpsc-cart type="widget"]
 *
 * Available attributes:
 * 	- 'type': Can be set to 'form', 'widget', 'table'.
 * 	          'form' would display similarly to the Shopping Cart page
 * 	          'widget' would display similarly to the Cart widget
 * 	          'table' would display a plain table of items without form
 *
 * @param  array $atts    Attributes
 * @return string         Shortcode output
 */
function _wpsc_shortcode_cart( $atts ) {
	global $wpsc_cart;

	ob_start();

	$defaults = array(
		'type' => 'form'
	);

	$atts = shortcode_atts( $defaults, $atts, 'wpsc-cart' );

	require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-widget-form.php' );

	if ( ! count( $wpsc_cart->cart_items ) ) {
		return '<p>' . __( 'No items in cart.', 'wp-e-commerce' ) . '</p>';
	}

	switch ( $atts['type'] ) {
		case 'form' :
			$table = new WPSC_Cart_Item_Table_Form();
			break;
		case 'widget' :
			$table = new WPSC_Cart_Item_Table_Widget_Form();
			break;
		case 'table' :
			$table = new WPSC_Cart_Item_Table();
			break;
	}

	$table->display();
	return ob_get_clean();

}

/**
 * Products shortcode
 *
 * Usage example:
 * 	- [wpsc-products id="12"]
 * 	- [wpsc-products id="12, 15"]
 * 	- [wpsc-products display="sale"]
 *
 * Available attributes:
 *
 * 	- 'id': A single, or a comma separated list of product IDs. Set to '0' will
 * 	        display all products matching remaining criteria. Defaults to '0'.
 * 	- 'display': Defaults to 'all'
 * 		+ display="all" will display all products
 * 		+ display="sale" will only display products on sale
 * 		+ display="not-sale" will only display products not on sale
 * 	- 'per_page': Set to -1 to disable pagination. Defaults to -1.
 * 	- 'paged': Current page number
 * 	- 'offset': Skip how many products at the beginning of the found results
 * 	- 'template_part': Which template part to include to display this shortcode.
 * 	                   Defaults to 'loop-products'
 * 	- 'category_in': Comma separated list of categories whose products you want to display
 * 	- 'category_not_in': Comma separated list of categories you want to exclude
 * 	- 'tag_in': Comma separated list of tags whose products you want to display
 * 	- 'tag_not_in': Comma separated list of tags whose products you want to exclude
 *
 * @param  array $atts Attributes
 * @return string      Shortcode output
 */
function _wpsc_shortcode_products( $atts ) {
	// Default and allowed attributes
	$defaults = array(
		'id'              => 0,
		'display'         => 'all',
		'per_page'        => -1,
		'paged'           => 1,
		'offset'          => 0,
		'template_part'   => 'loop-products',
		'category_in'     => '',
		'category_not_in' => '',
		'tag_in'          => '',
		'tag_not_in'      => '',
	);

	$args = array( 'post_type' => 'wpsc-product' );

	$atts = shortcode_atts( $defaults, $atts, 'wpsc-products' );

	// Query by post ID
	$atts['id'] = str_replace( ' ', '', $atts['id'] );
	$ids =   empty( $atts['id'] )
	       ? array()
	       : array_map( 'absint', explode( ',', $atts['id'] ) );

	if ( ! empty( $ids ) ) {
		$args['post__in'] = $ids;
	}

	// Meta query for whether to select products on sale or not
	switch ( $atts['display'] ) {
		case 'sale':
			$args['meta_query'] = array(
				array(
					'key'     => '_wpsc_special_price',
					'value'   => array( '', '0' ),
					'compare' => 'NOT IN',
				)
			);
			break;

		case 'not-sale':
			$args['meta_query'] = array(
				array(
					'key'     => '_wpsc_special_price',
					'value'   => array( '', '0' ),
					'compare' => 'IN',
				)
			);
			break;
	}

	// Pagination
	if ( $atts['per_page'] !== -1 ) {
		$args['posts_per_page'] = absint( $atts['per_page'] );
		$args['paged']          = absint( $atts['paged'] );
		$args['offset']         = absint( $atts['offset'] );
	} else {
		$args['nopaging'] = true;
	}

	// Taxonomy queries
	$atts['category_in']     = str_replace( ' ', '', $atts['category_in'] );
	$atts['category_not_in'] = str_replace( ' ', '', $atts['category_not_in'] );
	$atts['tag_in']          = str_replace( ' ', '', $atts['tag_in'] );
	$atts['tag_not_in']      = str_replace( ' ', '', $atts['tag_not_in'] );

	$atts['category_in']     =   empty( $atts['category_in'] )
	                           ? array()
	                           : array_map( 'absint', explode( ',', $atts['category_in'] ) );

	$atts['category_not_in'] =   empty( $atts['category_not_in'] )
	                           ? array()
	                           : array_map( 'absint', explode( ',', $atts['category_not_in'] ) );

	$atts['tag_in']          =   empty( $atts['tag_in'] )
	                           ? array()
	                           : array_map( 'absint', explode( ',', $atts['tag_in'] ) );

	$atts['tag_not_in']      =   empty( $atts['tag_not_in'] )
	                           ? array()
	                           : array_map( 'absint', explode( ',', $atts['tag_not_in'] ) );

	$args['tax_query'] = array();

	// Category slug in
	if ( ! empty( $atts['category_in'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'wpsc_product_category',
			'field'    => 'slug',
			'terms'    => $atts['category_in'],
			'operator' => 'IN',
		);
	}

	// Category slug not in
	if ( ! empty( $atts['category_not_in'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'wpsc_product_category',
			'field'    => 'slug',
			'terms'    => $atts['category_not_in'],
			'operator' => 'NOT IN',
		);
	}

	// Product tag in
	if ( ! empty( $atts['tag_in'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => $atts['tag_in'],
			'operator' => 'IN',
		);
	}

	// Product tag not in
	if ( ! empty( $atts['tag_not_in'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => $atts['tag_not_in'],
			'operator' => 'NOT IN',
		);
	}

	// I don't like query posts either but we need to preserve the ability
	// to use the_post() from within the templates, without having to resort
	// to tags like $query->the_post()
	query_posts( $args );

	ob_start();

	wpsc_get_template_part( $atts['template_part'] );

	$output = ob_get_clean();

	wp_reset_query();

	return $output;
}

/**
 * Add to cart shortcode
 *
 * Usage example: [wpsc-add-to-cart id="35"]
 *
 * Available attributes:
 *  - id: Post ID of the product for which you want to display the add to cart form
 * @param  array $atts Attributes
 * @return string      Output
 */
function _wpsc_shortcode_add_to_cart( $atts ) {
	$defaults = array( 'id' => 0 );
	$atts     = array_map( 'absint', shortcode_atts( $defaults, $atts ) );

	if ( empty( $atts['id'] ) ) {
		return '';
	}

	return wpsc_get_add_to_cart_form( $atts['id'] );
}