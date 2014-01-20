<?php

function _wpsc_get_filtered_categories() {
	$filtered = wpsc_get_option( 'categories_to_filter' );

	$args = array( 'hide_empty' => 0 );

	if ( $filtered == 'first_level' ) {
		$args['parent'] = 0;
	} elseif ( $filtered == 'custom' ) {
		$ids = array_map( 'absint', wpsc_get_option( 'categories_to_filter_custom' ) );
		$args['include'] = $ids;
	}

	return get_terms( 'wpsc_product_category', $args );
}