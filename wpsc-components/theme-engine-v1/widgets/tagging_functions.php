<?php

/**
 * This entire file is shenanigans.  No need to copy WP core code when we can use these as wrappers for that code.
 * @param type $args
 * @return type
 */

function product_tag_cloud( $args = '' ) {

	$defaults = array(
		'smallest' => 8,
		'largest'  => 22,
		'unit'     => 'pt',
		'number'   => 45,
		'format'   => 'flat',
		'orderby'  => 'name',
		'order'    => 'ASC',
		'exclude'  => '',
		'include'  => ''
	);

	$args = wp_parse_args( $args, $defaults );

	// Always query top tags
	$tags = get_product_tags( array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) );
	if ( empty( $tags ) )
		return;

	// Here's where those top tags get sorted according to $args
	$return = wp_generate_product_tag_cloud( $tags, $args );

	if ( is_wp_error( $return ) )
		return false;
	else
		echo apply_filters( 'product_tag_cloud', $return, $args );
}

function wp_generate_product_tag_cloud( $tags, $args = '' ) {
	global $wp_rewrite;

	$defaults = array(
		'smallest' => 8,
		'largest'  => 22,
		'unit'     => 'pt',
		'number'   => 45,
		'format'   => 'flat',
		'orderby'  => 'name',
		'order'    => 'ASC'
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	if ( !$tags )
		return;

	$counts = $tag_links = array();

	foreach ( (array)$tags as $tag ) {
		$counts[$tag->name] = $tag->count;
		$tag_links[$tag->name] = get_term_link( $tag->slug, $tag->taxonomy ); //get_product_tag_link( $tag->term_id );

		if ( is_wp_error( $tag_links[$tag->name] ) )
			return $tag_links[$tag->name];

		$tag_ids[$tag->name] = $tag->term_id;
	}

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;

	if ( $spread <= 0 )
		$spread = 1;

	$font_spread = $largest - $smallest;

	if ( $font_spread <= 0 )
		$font_spread = 1;

	$font_step = $font_spread / $spread;

	// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
	if ( 'name' == $orderby )
		uksort( $counts, 'strnatcasecmp' );
	else
		asort( $counts );

	if ( 'DESC' == $order )
		$counts = array_reverse( $counts, true );

	$a = array( );

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

	foreach ( $counts as $tag => $count ) {
		$tag_id = $tag_ids[$tag];
		$tag_link = esc_url( $tag_links[$tag] );
		$tag = str_replace( ' ', '&nbsp;', esc_html( $tag ) );
		$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( sprintf( _n( '%d topic', '%d topics', $count, 'wpsc' ), $count ) ) . "'$rel style='font-size: " .
				( $smallest + ( ( $count - $min_count ) * $font_step ) )
				. "$unit;'>$tag</a>";
	}

	switch ( $format ) :
		case 'array' :
			$return = & $a;
			break;

		case 'list' :
			$return = "<ul class='product_tag_cloud'>\n\t<li>";
			$return .= join( "</li>\n\t<li>", $a );
			$return .= "</li>\n</ul>\n";
			break;

		default :
			$return = "<div id='product_tag_wrap'>".join( "\n", $a )."</div>";
			break;

	endswitch;

	return apply_filters( 'wp_generate_product_tag_cloud', $return, $tags, $args );
}

function &get_product_tags( $args = '' ) {
	$tags = get_terms( 'product_tag', $args );
	$tags = apply_filters( 'get_product_tags', $tags, $args );
	return $tags;
}

function &get_product_tag( $tag, $output = OBJECT, $filter = 'raw' ) {
	return get_term( $tag, 'product_tag', $output, $filter );
}

//
// Tags
//
function wpsc_get_product_tag_link( $product_tag ) {
	$taglink = get_term_link( $product_tag, 'product_tag' );
	return apply_filters( 'product_tag_link', $taglink, $product_tag );
}

function wpsc_get_the_product_tags( $id = 0 ) {
	$tags = get_the_terms( $id, 'product_tag' );
	return apply_filters( 'get_the_product_tags', $tags, $id );
}

function wpsc_get_the_product_tag_list( $before = '', $sep = '', $after = '' ) {
	global $post;

	if ( ! $post->ID )
		return false;

	$tags = get_the_term_list( $post->ID, 'product_tag', $before, $sep, $after );

	if ( empty( $tags ) )
		return false;

	return apply_filters( 'the_product_tag_list', $tags );
}

function wpsc_the_product_tags( $before = null, $sep = ', ', $after = '' ) {
	if ( is_null( $before ) )
		$before = __( 'Tags', 'wpsc' );
	echo wpsc_get_the_product_tag_list( $before, $sep, $after );
}
