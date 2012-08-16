<?php
/**
 * WP eCommerce shortcode definitions
 *
 * These are the shortcode definitions for the wp-eCommerce plugin
 *
 * @package wp-e-commerce
 * @since 3.7
*/
/**
 * The WPSC shortcodes
 */

/**
* wpsc products shorttag function
* @return string - html displaying one or more products, derived from wpsc_display_products
*/
function wpsc_products_shorttag($atts) {
	// disable this shortcode on products
	if ( get_post_type() == 'wpsc-product' )
		return '';

	$query = shortcode_atts(array(
		'product_id' => 0,
		'old_product_id' => 0,
		'product_url_name' => null,
		'product_name' => null,
		'category_id' => 0,
		'category_url_name' => null,
		'tag' => null,
		'price' => 0, //if price = 'sale' it shows all sale products
		'limit_of_items' => 0,
		'sort_order' => null, // name,dragndrop,price,ID,author,date,title,modified,parent,rand,comment_count
		'order' => 'ASC', // ASC or DESC
		'number_per_page' => 0,
		'page' => 0,
	), $atts);
	$post_id_array = explode(',',$query['product_id']);
	$cat_id_array = explode(',',$query['category_id']);
	if(!empty($post_id_array) && count($post_id_array) > 1)
		$query['product_id'] = $post_id_array;

	if(!empty($cat_id_array) && count($cat_id_array) > 1)
		$query['category_id'] = $cat_id_array;

	if ( get_option( 'use_pagination', false ) ) {
		$page_number = get_query_var( 'paged' );
		if ( ! $page_number )
			$page_number = get_query_var( 'page' );
		$query['page'] = $page_number;
		$query['number_per_page'] = get_option( 'wpsc_products_per_page' );
	}

	if ( ! empty( $atts['number_per_page'] ) )
		$query['number_per_page'] = $atts['number_per_page'];

	return wpsc_display_products_page($query);
}
add_shortcode('wpsc_products', 'wpsc_products_shorttag');

function wpsc_buy_now_shortcode($atts){
	$output = wpsc_buy_now_button( $atts['product_id'], true );
	return $output;
}

add_shortcode('buy_now_button', 'wpsc_buy_now_shortcode');
?>