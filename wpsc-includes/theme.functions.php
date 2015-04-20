<?php
/**
 * WP eCommerce theme functions
 *
 * These are the functions for the wp-eCommerce theme engine
 *
 * @package wp-e-commerce
 * @since 3.7
 */


if ( isset( $_REQUEST['wpsc_notices'] ) && $_REQUEST['wpsc_notices'] == 'theme_ignore' ) {
	update_option( 'wpsc_ignore_theme', true );
	wp_redirect( esc_url_raw( remove_query_arg( 'wpsc_notices' ) ) );
}

/**
 * Get the Product Category ID by either slug or name
 * @access public
 *
 * @since 3.8
 * @param $slug (string) to be searched
 * @param $type (string) column to search, i.e name or slug
 * @return $category_id (int) Category ID
 */
function wpsc_get_the_category_id($slug, $type = 'name'){
	global $wpdb,$wp_query;
	if(isset($wp_query->query_vars['taxonomy']))
		$taxonomy = $wp_query->query_vars['taxonomy'];
	else
		$taxonomy = 'wpsc_product_category';

	$category = get_term_by($type,$slug,$taxonomy);
	return empty( $category ) ? false : $category->term_id;
}