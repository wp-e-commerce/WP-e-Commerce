<?php
add_filter( 'rewrite_rules_array', 'wpsc_taxonomy_rewrite_rules' );

/**
 * wpsc_taxonomy_rewrite_rules function.
 * Adds in new rewrite rules for categories, products, category pages, and ambiguities (either categories or products)
 * Also modifies the rewrite rules for product URLs to add in the post type.
 *
 * @since 3.8
 * @access public
 * @param array $rewrite_rules
 * @return array - the modified rewrite rules
 */
function wpsc_taxonomy_rewrite_rules( $rewrite_rules ) {
	global $wpsc_page_titles;
	$products_page = $wpsc_page_titles['products'];
	$checkout_page = $wpsc_page_titles['checkout'];
	$target_string = "index.php?product";
	$replacement_string = "index.php?post_type=wpsc-product&product";
	$target_rule_set_query_var = 'products';

	$target_rule_set = array( );
	foreach ( $rewrite_rules as $rewrite_key => $rewrite_query ) {
		if ( stristr( $rewrite_query, "index.php?product" ) ) {
			$rewrite_rules[$rewrite_key] = str_replace( $target_string, $replacement_string, $rewrite_query );
		}
		if ( stristr( $rewrite_query, "$target_rule_set_query_var=" ) ) {
			$target_rule_set[] = $rewrite_key;
		}
	}

	$new_rewrite_rules[$products_page . '/(.+?)/product/([^/]+)/comment-page-([0-9]{1,})/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&name=$matches[2]&cpage=$matches[3]';
	$new_rewrite_rules[$products_page . '/(.+?)/product/([^/]+)/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&name=$matches[2]';
	$new_rewrite_rules[$products_page . '/(.+?)/([^/]+)/comment-page-([0-9]{1,})/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&wpsc_item=$matches[2]&cpage=$matches[3]';
	$new_rewrite_rules[$products_page . '/(.+?)/([^/]+)?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&wpsc_item=$matches[2]';

	$last_target_rule = array_pop( $target_rule_set );

	$rebuilt_rewrite_rules = array( );
	foreach ( $rewrite_rules as $rewrite_key => $rewrite_query ) {
		if ( $rewrite_key == $last_target_rule ) {
			$rebuilt_rewrite_rules = array_merge( $rebuilt_rewrite_rules, $new_rewrite_rules );
		}
		$rebuilt_rewrite_rules[$rewrite_key] = $rewrite_query;
	}

	// fix pagination issue with product category hirarchical URL
	if ( get_option( 'product_category_hierarchical_url', false ) ) {
		$rule = $rebuilt_rewrite_rules[$products_page . '/(.+?)/page/?([0-9]{1,})/?$'];
		unset( $rebuilt_rewrite_rules[$products_page . '/(.+?)/page/?([0-9]{1,})/?$'] );
		$rebuilt_rewrite_rules = array_merge(
			array(
				'(' . $products_page . ')/page/([0-9]+)/?' => 'index.php?pagename=$matches[1]&page=$matches[2]',
				$products_page . '/(.+?)(/.+?)?/page/?([0-9]{1,})/?$' => 'index.php?wpsc_product_category=$matches[1]&wpsc-product=$matches[2]&page=$matches[3]',
			),
			$rebuilt_rewrite_rules
		);
	}

	// fix pagination in WordPress 3.4
	if ( version_compare( get_bloginfo( 'version' ), '3.4', '>=' ) ) {
		$rebuilt_rewrite_rules = array_merge(
			array(
				'(' . $products_page . ')/([0-9]+)/?$' => 'index.php?pagename=$matches[1]&page=$matches[2]',
			),
			$rebuilt_rewrite_rules
		);
	}
	return $rebuilt_rewrite_rules;
}

