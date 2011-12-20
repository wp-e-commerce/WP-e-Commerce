<?php

function wpsc_have_products() {
	global $wpsc_query;
	return $wpsc_query->have_posts();
}

function wpsc_the_product() {
	global $wpsc_query;
	$wpsc_query->the_post();
}

function wpsc_get_the_product_id() {
	return get_the_ID();
}

function wpsc_the_product_id() {
	the_ID();
}

function wpsc_filter_product_class( $classes, $class, $post_id ) {
	global $wpsc_query;

	$post = get_post( $post_id );
	if ( $post->post_type == 'wpsc-product' ) {
		$count     = isset( $wpsc_query->current_post ) ? (int) $wpsc_query->current_post : 1;
		$classes[] = $count % 2 ? 'even' : 'odd';
		if ( wpsc_is_the_product_on_sale( $post_id ) )
			$classes[] = 'wpsc-product-on-sale';

		return apply_filters( 'wpsc_product_class', $classes, $class, $post_id );
	}
}

add_filter( 'post_class', 'wpsc_filter_product_class', 10, 3 );

function wpsc_product_class() {
	post_class();
}

function wpsc_get_product_permalink( $id = 0, $leavename = false ) {
	return apply_filters( 'wpsc_get_product_permalink', get_permalink() );
}

function wpsc_the_product_permalink() {
	echo wpsc_get_product_permalink();
}

function wpsc_the_product_title_attribute( $args = '' ) {
	$title = wpsc_get_product_title();

	if ( strlen($title) == 0 )
		return;

	$defaults = array('before' => '', 'after' =>  '', 'echo' => true);
	$r = wp_parse_args($args, $defaults);
	extract( $r, EXTR_SKIP );

	$title = $before . $title . $after;
	$title = esc_attr(strip_tags($title));

	if ( $echo )
		echo $title;
	else
		return $title;
}

function wpsc_get_product_title( $id = 0 ) {
	return apply_filters( 'wpsc_get_product_title', get_the_title( $id ), $id );
}

function wpsc_the_product_title( $before = '', $after = '', $echo = true ) {
	$title = wpsc_get_product_title();

	if ( strlen( $title ) == 0 )
		return;

	$title = $before . $title . $after;

	if ( $echo )
		echo $title;
	else
		return $title;
}

function wpsc_get_product_category_list( $args = '' ) {
	$defaults = array(
		'id'        => 0,
		'before'    => '',
		'after'     => '',
		'separator' => __( ', ', 'category list separator', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	return get_the_term_list( $id, 'wpsc_product_category', $before, $separator, $after );
}

function wpsc_the_product_category_list( $args = '' ) {
	echo wpsc_get_product_category_list( $args );
}

function wpsc_get_product_tag_list( $args = '' ) {
	$defaults = array(
		'id'        => 0,
		'before'    => '',
		'after'     => '',
		'separator' => __( ', ', 'tag list separator', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	return get_the_term_list( $id, 'product_tag', $before, $separator, $after );
}

function wpsc_get_product_category_count( $id = 0 ) {
	$cats = get_the_terms( $id, 'wpsc_product_category' );

	if ( $cats === false )
		return 0;

	return count( $cats );
}

function wpsc_get_product_tag_count( $id = 0 ) {
	$tags = get_the_terms( $id, 'product_tag' );

	if ( $tags === false )
		return 0;

	return count( $tags );
}

function wpsc_edit_product_link( $args = '' ) {
	$defaults = array(
		'id'     => 0,
		'before' => '',
		'after'  => '',
		'title'  => _x( 'Edit This Product', 'product edit link template tag', 'wpsc' ),
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	ob_start();
	edit_post_link( $title, $before, $after, $id );
	$link = ob_get_clean();
	echo apply_filters( 'wpsc_edit_product_link', $link, $id );
}

function wpsc_has_product_thumbnail( $id = null ) {
	return has_post_thumbnail( $id );
}

function wpsc_get_product_thumbnail_id( $product_id = null ) {
	return get_post_thumbnail_id( $product_id );
}

function wpsc_get_the_product_thumbnail( $id = null, $size = 'single', $attr = '' ) {
	global $_wp_additional_image_sizes;
	$wp_size = 'wpsc_product_' . $size . '_thumbnail';

	if ( wpsc_has_product_thumbnail() ) {
		$thumb_id = wpsc_get_product_thumbnail_id( $id );
		$size_metadata = $_wp_additional_image_sizes[$wp_size];
		$current_size_metadata = get_post_meta( $thumb_id, '_wpsc_current_size_metadata', true );

		// Regenerate if this image was generated for a different size
		if ( $current_size_metadata != $size_metadata ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$metadata = wp_get_attachment_metadata( $thumb_id );
			if ( ! is_array( $metadata ) )
				$metadata = array();
			if ( empty( $metadata['sizes'] ) )
				$metadata['sizes'] = array();
			$file = get_attached_file( $thumb_id );
			$generated = wp_generate_attachment_metadata( $thumb_id, $file );
			if ( ! empty( $generated ) && ! empty( $generated['sizes'] ) )
				$metadata['sizes'] = array_merge( $metadata['sizes'], $generated['sizes'] );
			wp_update_attachment_metadata( $thumb_id, $metadata );
			update_post_meta( $thumb_id, '_wpsc_current_size_metadata', $size_metadata );
		}
	}

	return get_the_post_thumbnail( $id, $wp_size, $attr );
}

function wpsc_the_product_thumbnail( $size = 'single', $attr = '' ) {
	echo wpsc_get_the_product_thumbnail( null, $size, $attr );
}

function wpsc_product_no_thumbnail_image( $size = 'single', $attr = '' ) {
	global $_wp_additional_image_sizes;

	$wp_size = 'wpsc_product_' . $size . '_thumbnail';
	$dimensions = $_wp_additional_image_sizes[$wp_size];
	$title = wpsc_the_product_title_attribute( array( 'echo' => false ) );
	$src = WPSC_THEME_ENGINE_COMPAT_URL . '/default/images/no-thumbnails.png';
	$html = '<img src="' . $src . '" title="' . $title . '" width="' . $dimensions['width'] . '" height="' . $dimensions['height'] . '" />';

	$html = apply_filters( 'wpsc_product_no_thumbnail_html', $html, $size, $attr );

	echo $html;
}

function wpsc_the_product_description( $more_link_text = null, $mode = 'with-teaser' ) {
	$content = wpsc_get_the_product_description( $more_link_text, $mode );
	$content = apply_filters( 'the_content', $content );
	$content = apply_filters( 'wpsc_product_description', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );
	echo $content;
}

function wpsc_filter_remove_the_content_more_link( $link ) {
	return '';
}

function wpsc_get_the_product_description( $more_link_text = null, $mode = 'with-teaser' ) {
	$stripteaser = $mode == 'no-teaser';

	if ( $mode == 'only-teaser' )
		add_filter( 'the_content_more_link', 'wpsc_filter_remove_the_content_more_link', 99 );

	$content = get_the_content( $more_link_text, $stripteaser );

	if ( $mode == 'only-teaser' ) {
		remove_filter( 'the_content_more_link', 'wpsc_filter_remove_the_content_more_link', 99 );
		$sub = '<span id="more-' . get_the_ID() . '"></span>';
		$pos = strpos( $content, $sub );
		if ( $pos !== false )
			$content = substr( $content, 0, $pos );
	}

	return $content;
}

function wpsc_get_product_variation_sets( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_the_product_id();

	$variations = WPSC_Product_Variations::get_instance( $id );
	return $variations->get_variation_sets();
}

function wpsc_the_product_variation_set_dropdown( $variation_set_id, $product_id = null ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_the_product_id();

	$variations = WPSC_Product_Variations::get_instance( $product_id );
	$variations->variation_set_dropdown( $variation_set_id );
}

function wpsc_has_product_variations( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_the_product_id();

	$product = get_post( $id );
	if ( $product->post_parent )
		return false;

	$variations = WPSC_Product_Variations::get_instance( $id );
	return $variations->has_variations();
}

function wpsc_the_product_original_price() {
	$original_price = apply_filters( 'wpsc_the_product_original_price', wpsc_get_the_product_original_price() );
	echo $original_price;
}

function wpsc_get_the_product_original_price( $product_id = null, $return_type = 'string' ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_the_product_id();

	if ( wpsc_has_product_variations( $product_id ) ) {
		$variations = WPSC_Product_Variations::get_instance( $product_id );
		$original_price = $variations->get_original_from_price( $return_type );
	} else {
		$original_price = get_post_meta( $product_id, '_wpsc_price', true );
		if ( $return_type === 'string' )
			$original_price = wpsc_format_price( $original_price );
		else
			$original_price = (float) $original_price;
	}

	return apply_filters( 'wpsc_get_the_product_original_price', $original_price, $product_id, $return_type );
}

function wpsc_the_product_sale_price() {
	$sale_price = apply_filters( 'wpsc_the_product_sale_price', wpsc_get_the_product_sale_price() );
	echo $sale_price;
}

function wpsc_get_the_product_sale_price( $product_id = null, $return_type = 'string' ) {
	if ( empty( $product_id ) )
		$product_id = wpsc_get_the_product_id();

	if ( wpsc_has_product_variations( $product_id ) ) {
		$variations = WPSC_Product_Variations::get_instance( $product_id );
		$sale_price = $variations->get_sale_from_price( $return_type );
	} else {
		$sale_price = get_post_meta( $product_id, '_wpsc_special_price', true );
		if ( $return_type === 'string' )
			$sale_price = wpsc_format_price( $sale_price );
		else
			$sale_price = (float) $sale_price;
	}

	return apply_filters( 'wpsc_get_the_product_sale_price', $sale_price, $product_id, $return_type );
}

function wpsc_is_the_product_on_sale( $id = null ) {
	if ( empty( $id ) )
		$id = wpsc_get_the_product_id();

	if ( wpsc_has_product_variations( $id ) ) {
		$variations = WPSC_Product_Variations::get_instance( $id );
		return $variations->is_on_sale();
	}

	$sale_price = wpsc_get_the_product_sale_price( null, 'number' );
	$original_price = wpsc_get_the_product_original_price( null, 'number' );

	if ( $sale_price > 0 && $sale_price < $original_price )
		return true;

	return false;
}

function wpsc_format_price( $amt, $args = '' ) {
	$defaults = array(
		'display_currency_symbol' => true,
		'display_decimal_point'   => true,
		'display_currency_code'   => false,
		'isocode'                 => false,
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$currencies_without_fractions = array( 'JPY', 'HUF' );
	if ( $isocode )
		$currency = new WPSC_Country( $isocode, 'isocode' );
	else
		$currency = new WPSC_Country( get_option( 'currency_type' ) );
	$currency_code = $currency->get( 'code' );

	// No decimal point, no decimals
	if ( ! $display_decimal_point || in_array( $currency_code, $currencies_without_fractions ) )
		$decimals = 0;
	else
		$decimals = 2; // default is 2

	$decimals = apply_filters( 'wpsc_modify_decimals' , $decimals, $isocode );
	$decimal_separator = apply_filters( 'wpsc_format_price_decimal_separator', wpsc_get_option( 'decimal_separator' ), $isocode );
	$thousands_separator = apply_filters( 'wpsc_format_price_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );

	// Format the price for output
	$formatted = number_format( $amt, $decimals, $decimal_separator, $thousands_separator );

	if ( ! $display_currency_code )
		$currency_code = '';

	$symbol = $display_currency_symbol ? $currency->get('symbol' ) : '';
	$symbol = esc_html( $symbol );
	$symbol = apply_filters( 'wpsc_format_price_currency_symbol', $symbol, $isocode );

	$currency_sign_location = get_option( 'currency_sign_location' );

	// Rejig the currency sign location
	switch ( $currency_sign_location ) {
		case 1:
			$format_string = '%3$s%1$s%2$s';
			break;

		case 2:
			$format_string = '%3$s %1$s%2$s';
			break;

		case 4:
			$format_string = '%1$s%2$s  %3$s';
			break;

		case 3:
		default:
			$format_string = '%1$s %2$s%3$s';
			break;
	}
	$currency_code = apply_filters( 'wpsc_format_price_currency_code', $currency_code, $isocode );

	// Compile the output
	$output = trim( sprintf( $format_string, $currency_code, $symbol, $formatted ) );
	$output = apply_filters( 'wpsc_format_price', $output, $isocode );
	return $output;
}

function wpsc_the_product_add_to_cart_button( $title = null, $id = null, $echo = true ) {
	if ( ! $id )
		$id = wpsc_get_the_product_id();

	if ( ! $title )
		$title = _x( 'Add to Cart', 'product add to cart button', 'wpsc' );

	$title = apply_filters( 'wpsc_the_product_add_to_cart_button_title', $title, $id );
	$output = '<input class="wpsc-product-add-to-cart-button" id="wpsc-product-add-to-cart-button-' . $id . '" type="submit" value="' . esc_attr( $title ) . '" />';
	$output = apply_filters( 'wpsc_the_product_add_to_cart_button', $output, $id );
	if ( $echo )
		echo $output;
	else
		return $output;
}

function wpsc_the_product_id_hidden_field( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_the_product_id();

	echo '<input type="hidden" name="wpsc_product_id" value="' . esc_attr( $id ) . '" />';
}
add_action( 'wpsc_theme_product_add_to_cart_actions_after', 'wpsc_the_product_id_hidden_field', 10, 1 );

function wpsc_filter_content_more_link( $link ) {
	if ( get_query_var( 'post_type' ) == 'wpsc-product' )
		$link = '<p class="wpsc-more-link">' . $link . '</p>';
	return $link;
}
add_filter( 'the_content_more_link', 'wpsc_filter_content_more_link' );

function wpsc_is_pagination_enabled( $position = 'bottom' ) {
	$pagination_enabled = get_option( 'use_pagination' );
	if ( ! $pagination_enabled )
		return false;

	$pagination_position = wpsc_get_option( 'page_number_position' );
	if ( $pagination_position == WPSC_PAGE_NUMBER_POSITION_BOTH )
		return true;

	$id = WPSC_PAGE_NUMBER_POSITION_BOTTOM;
	if ( $position == 'top' )
		$id = WPSC_PAGE_NUMBER_POSITION_TOP;

	return ( $pagination_position == $id );
}

function wpsc_product_pagination( $position = 'bottom' ) {
	if ( ! wpsc_is_pagination_enabled( $position ) )
		return;

	echo '<div class="wpsc-pagination wpsc-pagination-' . esc_attr( $position ) . '">';
	wpsc_get_template_part( 'pagination-product-archive', $position );
	echo '</div>';
}

function wpsc_product_pagination_page_count() {
	global $wpsc_query;
	return $wpsc_query->max_num_pages;
}

function wpsc_product_pagination_count() {
	global $wpsc_query;

	$total = empty( $wpsc_query->found_posts ) ? $wpsc_query->post_count : $wpsc_query->found_posts;
	$total_pages = $wpsc_query->max_num_pages;
	$per_page = get_query_var( 'posts_per_page' );
	$current_page = wpsc_get_current_page_number();
	$from = ( $current_page - 1 ) * $per_page + 1;
	$to = $from + $per_page - 1;
	$post_count = $wpsc_query->post_count;
	if ( $to > $total )
		$to = $total;

	if ( $total > 1 ) {
		if ( $from == $to )
			$output = sprintf( __( 'Viewing product %1$s (of %2$s total)', 'wpsc' ), $from, $total );
		elseif ( $total_pages === 1 )
			$output = sprintf( __( 'Viewing %1$s products', 'wpsc' ), $total );
		else
			$output = sprintf( __( 'Viewing %1$s products - %2$s through %3$s (of %4$s total)', 'wpsc' ), $post_count, $from, $to, $total );
	} else {
		$output = sprintf( __( 'Viewing %1$s product', 'wpsc' ), $total );
	}

	// Filter and return
	echo apply_filters( 'wpsc_product_pagination_count', $output );
}

function wpsc_get_current_page_number() {
	$current = get_query_var( 'paged' );
	if ( $current )
		return $current;

	return 1;
}

function wpsc_product_pagination_links() {
	global $wp_rewrite, $wpsc_query;

	$base = home_url( wpsc_get_option( 'catalog_slug' ) );

	if ( $wp_rewrite->using_permalinks() )
		$format = 'page/%#%';
	else
		$format = '?page=%#%';

	$args = array(
		'base'      => $base . '/%_%',
		'format'    => $format,
		'total'     => $wpsc_query->max_num_pages,
		'current'   => wpsc_get_current_page_number(),
		'prev_text' => __( '&larr;', 'wpsc' ),
		'next_text' => __( '&rarr;', 'wpsc' ),
	);

	$args = apply_filters( 'wpsc_product_pagination_links_args', $args );
	$links = apply_filters( 'wpsc_product_pagination_links', paginate_links( $args ) );
	echo $links;
}

function wpsc_is_product_out_of_stock( $id = null ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_the_product_id();

	$stock = get_post_meta( $id, '_wpsc_stock', true );

	if ( $stock === '' )
		return false;

	if ( wpsc_has_product_variations() ) {
		$variations = WPSC_Product_Variations::get_instance( $id );
		return $variations->is_out_of_stock();
	}

	if ( $stock > 0 ) {
		$sql = $wpdb->prepare( 'SELECT SUM(stock_claimed) FROM '.WPSC_TABLE_CLAIMED_STOCK.' WHERE product_id=%d', $id );
		$claimed_stock = $wpdb->get_var( $sql );
		$stock -= $claimed_stock;
	}

	if ( $stock < 0 )
		return true;

	return false;
}