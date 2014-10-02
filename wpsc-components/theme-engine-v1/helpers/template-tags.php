<?php

/**
 * wpsc display products function
 * Used to determine whether to display products on the page
 * @return boolean - true for yes, false for no
 */
function wpsc_display_products() {
	$post = get_queried_object();
	$product_page_id = wpsc_get_the_post_id_by_shortcode('[productspage]');
	//we have to display something, if we are not displaying categories, then we must display products
	$output = true;
	if ( wpsc_display_categories () && isset ( $post->ID ) ) {
		if ( get_option( 'wpsc_default_category' ) == 'list' && $post->ID == $product_page_id )
			$output = false;

		if ( isset( $_GET['range'] ) || isset( $_GET['category'] ) )
			$output = true;
	}
	return $output;
}

/**
 * 	is single product function, determines if we are viewing a single product
 * @return boolean - true, or false...
 */
function wpsc_is_single_product() {
	return is_single() && get_post_type() == 'wpsc-product';
}

/**
 * category class function, categories can have a specific class, this gets that
 * @return string - the class of the selected category
 */
function wpsc_category_class() {
	global $wpdb, $wp_query;

	$category_nice_name = '';
	if ( 'wpsc_product_category' == $wp_query->query_vars['taxonomy']  ) {
		$catid = wpsc_get_the_category_id($wp_query->query_vars['term'],'slug');
	} else {
		$catid = get_option( 'wpsc_default_category' );
		if ( $catid == 'all+list' ) {
			$catid = 'all';
		}
	}

	if ( (int)$catid > 0 ){
		$term = get_term($catid, 'wpsc_product_category');
		$category_nice_name = $term->slug;
	}else if ( $catid == 'all' ){
		$category_nice_name = 'all-categories';
}
	return $category_nice_name;
}

/**
 * category transition function, finds the transition between categories
 * @return string - the class of the selected category
 */
function wpsc_current_category_name() {
	global $wp_query;
	$term_data = get_term( $wp_query->post->term_id, 'wpsc_product_category' );

	return $term_data->name;
}

/**
 * category transition function, finds the transition between categories
 * @return string - the class of the selected category
 */
function wpsc_category_transition() {
	//removed because it was not working in 3.8 RC2 see first changest after
	//http://plugins.trac.wordpress.org/changeset/357529/wp-e-commerce/
	return false;

}
/**
 * wpsc show fb like function, check whether to show facebook like
 * @return boolean true if option is on, otherwise, false
 */

function wpsc_show_fb_like(){
	if('on' == get_option('wpsc_facebook_like'))
		return true;
	else
		return false;
}
/**
 * wpsc have products function, the product loop
 * @return boolean true while we have products, otherwise, false
 */
function wpsc_have_products() {
	return have_posts();
}

/**
 * wpsc the product function, gets the next product,
 * @return nothing
 */
function wpsc_the_product() {
	global $wpsc_custom_meta, $wpsc_variations;
	the_post();
	$wpsc_custom_meta = new wpsc_custom_meta( get_the_ID() );
	$wpsc_variations = new wpsc_variations( get_the_ID() );
}

/**
 * wpsc the product id function,
 * @return integer - the product ID
 */
function wpsc_the_product_id() {
	return get_the_ID();
}

/**
 * wpsc edit the product link function
 * @return string - a link to edit this product
 */
function wpsc_edit_the_product_link( $link = null, $before = '', $after = '', $id = 0 ) {
	global $wpsc_query, $current_user, $table_prefix, $wp_query;
	if ( $link == null )
		$link = __( 'Edit', 'wpsc' );

	$product_id = $wp_query->post->ID;
	if ( $id > 0 )
		$product_id = $id;

	$output = '';
	if(is_user_logged_in()){
		get_currentuserinfo();
		if ( $current_user->{$table_prefix . 'capabilities'}['administrator'] == 1 )
			$output = $before . '<a class="wpsc_edit_product" href="' . esc_attr( get_edit_post_link( $product_id ) ) . '">' . $link . '</a>' . $after;

	}
	return $output;
}

/**
 * wpsc the product title function
 * @return string - the product title
 */
function wpsc_the_product_title( $post = 0 ) {
	return get_the_title( $post );
}

/**
 * wpsc product description function
 * @return string - the product description
 */
function wpsc_the_product_description() {
	$content = get_the_content( __( 'Read the rest of this entry &raquo;', 'wpsc' ) );
	return do_shortcode( wpautop( $content,1 ) );
}

/**
 * wpsc additional product description function
 * TODO make this work with the tabbed multiple product descriptions, may require another loop
 * @return string - the additional description
 */
function wpsc_the_product_additional_description() {
	global $post;

	if ( !empty( $post->post_excerpt ) )
		return $post->post_excerpt;
	else
		return false;
}

/**
 * wpsc product permalink function
 * @return string - the URL to the single product page for this product
 */
function wpsc_the_product_permalink() {
	global $wp_query;
	return get_permalink();
}

/**
 * wpsc external link function
 * @return string - the product price
 */
function wpsc_product_external_link( $id = null ) {
	if ( is_numeric( $id ) && ( $id > 0 ) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset( $product_meta['external_link'] ) ) {
		$external_link = $product_meta['external_link'];
		return esc_url( $external_link );
	}
}

/**
 * wpsc external link text function
 * @return string - the product external link text
 */
function wpsc_product_external_link_text( $id = null, $default = null ) {
	if ( is_numeric( $id ) && ( $id > 0 ) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$external_link_text = __( 'Buy Now', 'wpsc' );
	if ( $default != null ) {
		$external_link_text = $default;
	}

	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset( $product_meta['external_link_text'] ) && !empty( $product_meta['external_link_text'] ) ) {
		$external_link_text = $product_meta['external_link_text'];
	}
	return esc_html( $external_link_text );
}

/**
 * wpsc external link target function
 * @return string - the product external link target
 */
function wpsc_product_external_link_target( $id = null, $external_link_target = '' ) {
	if ( is_numeric( $id ) && ( $id > 0 ) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset( $product_meta['external_link_target'] ) && !empty( $product_meta['external_link_target'] ) ) {
		$external_link_target = $product_meta['external_link_target'];
	}
	return esc_attr( $external_link_target );
}

/**
 * wpsc product sku function
 * @return string - the product price
 */
function wpsc_product_sku( $id = null ) {
	if ( is_numeric( $id ) && ( $id > 0 ) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$product_sku = get_post_meta( $id, '_wpsc_sku', true );

	return esc_attr( $product_sku );
}

function wpsc_has_pages_top(){if(wpsc_has_pages() &&  ((get_option('wpsc_page_number_position') == 1) || (get_option('wpsc_page_number_position') == 3)))
		return true;
	else
		return false;
}


function wpsc_has_pages_bottom(){
	if(wpsc_has_pages() &&  ((get_option('wpsc_page_number_position') == 2) || (get_option('wpsc_page_number_position') == 3)))
		return true;
	else
		return false;
}

/**
 * Used in wpsc_pagination to generate the links in pagination
 * @access public
 *
 * @since 3.8
 * @param $page int
 * @return $output string, url
 */
function wpsc_a_page_url($page=null) {
	global $wp_query;
	$output = '';
	$curpage = $wp_query->query_vars['paged'];
	if($page != '')
		$wp_query->query_vars['paged'] = $page;
	if($wp_query->is_single === true) {
		$wp_query->query_vars['paged'] = $curpage;
		return get_permalink($wp_query->post->ID);
	} else {
		if( 1 < $wp_query->query_vars['paged']) {
			if(get_option('permalink_structure'))
				$output .= "paged/{$wp_query->query_vars['paged']}/";
			else
				$output = add_query_arg('paged', '', $output);

		}
	return $output;
	}
}
/**
 * wpsc_pagination generates and returns the urls for pagination
 * @access public
 *
 * @since 3.8
 * @param $totalpages (INT) Number of pages,
 * @param $per_page (INT) Number of products per page
 * @param $current_page (INT) Current Product page number
 * @param $page_link (STRING) URL of Page
 *
 * @return
 */
function wpsc_pagination( $totalpages = '', $per_page = '', $current_page = '', $page_link = '' ) {
	global $wp_query, $wpsc_query, $wp_the_query;

	$num_paged_links = 4; //amount of links to show on either side of current page

	$additional_links = '';

	//additional links, items per page and products order
	if( get_option('permalink_structure') != '' ){
		$additional_links_separator = '?';
	}else{
		$additional_links_separator = '&';
	}
	if( !empty( $_GET['items_per_page'] ) ){
			$additional_links = $additional_links_separator . 'items_per_page=' . $_GET['items_per_page'];
			$additional_links_separator = '&';
	}
	if( !empty( $_GET['product_order'] ) )
		$additional_links .= $additional_links_separator . 'product_order=' . $_GET['product_order'];

	if ( ! empty( $_GET['range'] ) )
		$additional_links .= $additional_links_separator . 'range=' . $_GET['range'];

	$additional_links = apply_filters('wpsc_pagination_additional_links', $additional_links);
	//end of additional links

	if(empty($totalpages)){
			$totalpages = $wp_query->max_num_pages;
	}
	if(empty($per_page))
		$per_page = (int)get_option('wpsc_products_per_page');

	$current_page = absint( get_query_var('paged') );
	if($current_page == 0)
		$current_page = 1;

	if(empty($page_link))
		$page_link = wpsc_a_page_url();

	//if there is no pagination
	if(!get_option('permalink_structure')) {
		$category = '?';

		if( ! empty( $wp_query->query_vars['wpsc_product_category'] ) )
			$category = '?wpsc_product_category='.$wp_query->query_vars['wpsc_product_category'];
		if(isset($wp_query->query_vars['wpsc_product_category']) && is_string($wp_query->query_vars['wpsc_product_category'])){
			$page_link = get_option('blogurl').$category.'&amp;paged';
		}else{
			$page_link = get_option('product_list_url').$category.'&amp;paged';
		}

		$separator = '=';
	}else{
		global $wpsc_query;

		$separator = 'page/';

		if ( isset( $wp_query->query_vars['wpsc_product_category'] ) ) {
			$category_id = get_term_by( 'slug', $wp_query->query_vars['wpsc_product_category'], 'wpsc_product_category' );
			$page_link = trailingslashit( get_term_link( $category_id, 'wpsc_product_category' ) );
			// in case we're displaying a category using shortcode, need to use the page's URL instead of the taxonomy URL
			if ( $wp_the_query->is_page() ) {
				$page = $wp_the_query->get_queried_object();
				if ( preg_match( '/\[wpsc\_products[^\]]*category_id=/', $page->post_content ) ) {
					$page_link = trailingslashit( get_permalink( $page->ID ) );
					$separator = '';
				}
			}
		} elseif ( is_tax( 'product_tag' ) ) {
			$tag = get_queried_object();
			$page_link = trailingslashit( get_term_link( (int) $tag->term_id, 'product_tag' ) );
		} elseif ( $wpsc_query->is_front_page() ) {
			$page_link = trailingslashit( home_url() );
		} else {
			$page_link = trailingslashit( get_option( 'product_list_url' ) );
			$separator = '';
		}
	}

	// If there's only one page, return now and don't bother
	if($totalpages == 1)
		return;
	// Pagination Prefix
	$output = __('Pages: ','wpsc');
	if(get_option('permalink_structure')){
		// Should we show the FIRST PAGE link?
		if($current_page > 1)
			$output .= "<a href=\"". esc_url( $page_link . $additional_links ) . "\" title=\"" . __('First Page', 'wpsc') . "\">" . __('&laquo; First', 'wpsc') . "</a>";

		// Should we show the PREVIOUS PAGE link?
		if($current_page > 1) {
			$previous_page = $current_page - 1;
			if( $previous_page == 1 )
				$output .= " <a href=\"". esc_url( $page_link . $additional_links ) . "\" title=\"" . __('Previous Page', 'wpsc') . "\">" . __('&lt; Previous', 'wpsc') . "</a>";
			else
				$output .= " <a href=\"". esc_url( $page_link .$separator. $previous_page . $additional_links ) . "\" title=\"" . __('Previous Page', 'wpsc') . "\">" . __('&lt; Previous', 'wpsc') . "</a>";
		}
		$i =$current_page - $num_paged_links;
		$count = 1;
		if($i <= 0) $i =1;
		while($i < $current_page){
			if($count <= $num_paged_links){
				if($count == 1)
					$output .= " <a href=\"". esc_url( $page_link . $additional_links ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), $i ) . " \">".$i."</a>";
				else
					$output .= " <a href=\"". esc_url( $page_link .$separator. $i . '/' . $additional_links ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), $i ) . " \">".$i."</a>";
			}
			$i++;
			$count++;
		}
		// Current Page Number
		if($current_page > 0)
			$output .= "<span class='current'>$current_page</span>";

		//Links after Current Page
		$i = $current_page + $num_paged_links;
		$count = 1;

		if($current_page < $totalpages){
			while(($i) > $current_page){

				if ( $count < ( $num_paged_links + 1 ) && ( $count + $current_page ) <= $totalpages ) {
						$output .= " <a href=\"". esc_url( $page_link . $separator . ( $count + $current_page ) . '/' . $additional_links ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), ($count+$current_page) ) . "\">".($count+$current_page)."</a>";
				$i++;
				}else{
				break;
				}
				$count ++;
			}
		}

		if($current_page < $totalpages) {
			$next_page = $current_page + 1;
			$output .= "<a href=\"". esc_url( $page_link  .$separator. $next_page . '/' . $additional_links ) . "\" title=\"" . __('Next Page', 'wpsc') . "\">" . __('Next &gt;', 'wpsc') . "</a>";
		}
		// Should we show the LAST PAGE link?
		if($current_page < $totalpages) {
			$output .= "<a href=\"". esc_url( $page_link  .$separator. $totalpages . '/' . $additional_links ) . "\" title=\"" . __('Last Page', 'wpsc') . "\">" . __('Last &raquo;', 'wpsc') . "</a>";
		}
	} else {
		// Should we show the FIRST PAGE link?
		if($current_page > 1)
			$output .= "<a href=\"". remove_query_arg('paged' ) . "\" title=\"" . __('First Page', 'wpsc') . "\">" . __('&laquo; First', 'wpsc') . "</a>";

		// Should we show the PREVIOUS PAGE link?
		if($current_page > 1) {
			$previous_page = $current_page - 1;
			if( $previous_page == 1 )
				$output .= " <a href=\"". remove_query_arg( 'paged' ) . $additional_links . "\" title=\"" . __('Previous Page', 'wpsc') . "\">" . __('&lt; Previous', 'wpsc') . "</a>";
			else
				$output .= " <a href=\"". add_query_arg( 'paged', ($current_page - 1) ) . $additional_links . "\" title=\"" . __('Previous Page', 'wpsc') . "\">" . __('&lt; Previous', 'wpsc') . "</a>";
		}
		$i =$current_page - $num_paged_links;
		$count = 1;
		if($i <= 0) $i =1;
		while($i < $current_page){
			if($count <= $num_paged_links){
				if($i == 1)
					$output .= " <a href=\"". remove_query_arg('paged' ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), $i ) . " \">".$i."</a>";
				else
					$output .= " <a href=\"". add_query_arg('paged', $i ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), $i ) . " \">".$i."</a>";
			}
			$i++;
			$count++;
		}
		// Current Page Number
		if($current_page > 0)
			$output .= "<span class='current'>$current_page</span>";

		//Links after Current Page
		$i = $current_page + $num_paged_links;
		$count = 1;

		if($current_page < $totalpages){
			while(($i) > $current_page){

				if($count < $num_paged_links && ($count+$current_page) <= $totalpages){
						$output .= " <a href=\"". add_query_arg( 'paged', ($count+$current_page) ) . "\" title=\"" . sprintf( __('Page %s', 'wpsc'), ($count+$current_page) ) . "\">".($count+$current_page)."</a>";
				$i++;
				}else{
				break;
				}
				$count ++;
			}
		}

		if($current_page < $totalpages) {
			$next_page = $current_page + 1;
			$output .= "<a href=\"". add_query_arg( 'paged', $next_page ) . "\" title=\"" . __('Next Page', 'wpsc') . "\">" . __('Next &gt;', 'wpsc') . "</a>";
		}
		// Should we show the LAST PAGE link?
		if($current_page < $totalpages) {
			$output .= "<a href=\"". add_query_arg( 'paged', $totalpages ) . "\" title=\"" . __('Last Page', 'wpsc') . "\">" . __('Last &raquo;', 'wpsc') . "</a>";
		}
	}
	// Return the output.
	echo apply_filters( 'wpsc_pagination', $output, $totalpages, $per_page, $current_page, $page_link );
}

/**
 * wpsc_show_stock_availability
 *
 * Checks to see whether stock symbols need to be shown
 * @return boolean - true is the option has been checked false otherwise
 */
function wpsc_show_stock_availability(){
	if( get_option('list_view_quantity') == 1 )
		return true;
	else
		return false;
}

/**
 * wpsc product image function
 *
 * if no parameters are passed, the image is not resized, otherwise it is resized to the specified dimensions
 *
 * @param integer attachment_ID
 * @param integer width
 * @param integer height
 * @return string - the product image URL, or the URL of the resized version
 */
function wpsc_product_image( $attachment_id = 0, $width = null, $height = null ) {

	// Do some dancing around the image size
	if ( ( ( $width >= 10 ) && ( $height >= 10 ) ) && ( ( $width <= 1024 ) && ( $height <= 1024 ) ) ) {
		$intermediate_size = "wpsc-{$width}x{$height}";
	}

	// Get image url if we have enough info
	if ( $attachment_id > 0 && ! empty( $intermediate_size ) ) {

		// Get all the required information about the attachment
		$uploads    = wp_upload_dir();
		$image_meta = get_post_meta( $attachment_id, '' );
		$file_path  = get_attached_file( $attachment_id );

		// Clean up the meta array
		foreach ( $image_meta as $meta_name => $meta_value ) {
			$image_meta[ $meta_name ] = maybe_unserialize( array_pop( $meta_value ) );
		}

		$attachment_metadata = isset( $image_meta['_wp_attachment_metadata'] ) ? $image_meta['_wp_attachment_metadata'] : null;

		// Determine if we already have an image of this size
		if ( isset( $attachment_metadata['sizes'] ) && count( $attachment_metadata['sizes'] ) && ( isset( $attachment_metadata['sizes'][ $intermediate_size ] ) ) ) {
			$intermediate_image_data = image_get_intermediate_size( $attachment_id, $intermediate_size );
			$image_url               = $intermediate_image_data['url'];
		} else {
			$image_url = home_url( "index.php?wpsc_action=scale_image&attachment_id={$attachment_id}&width=$width&height=$height" );
		}
	// Not enough info so attempt to fallback
	} else {

		if ( ! empty( $attachment_id ) ) {
			$image_url = home_url( "index.php?wpsc_action=scale_image&attachment_id={$attachment_id}&width=$width&height=$height" );
		} else {
			$image_url = false;
		}

	}

	if ( empty( $image_url ) && ! empty( $file_path ) ) {

		$image_meta = get_post_meta( $attachment_id, '_wp_attached_file' );

		if ( ! empty( $image_meta ) ) {
			$image_url = $uploads['baseurl'] . '/' . $image_meta[0];
		}
	}

	/**
	 * The filter here and in wpsc_the_product_image() were unfortunately named, as were the functions.
	 * They do very different things but are named as if they do not.  The onus will be on the
	 * plugin developer to check if the second parameter is an attachment ID or a product ID.
	 *
	 * @since 3.8
	 */
	return apply_filters( 'wpsc_product_image', set_url_scheme( $image_url ), $attachment_id );
}

function wpsc_product_no_image_fallback( $image_url = '' ) {
	if ( ! empty( $image_url ) ) {
		return $image_url;
	} else {
		return apply_filters( 'wpsc_product_noimage', WPSC_CORE_THEME_URL . 'wpsc-images/noimage.png' );
	}
}

add_filter( 'wpsc_product_image', 'wpsc_product_no_image_fallback' );

/**
 * wpsc show pnp function
 * @return boolean - true if display_pnp is 1 false otherwise
 */
function wpsc_show_pnp(){
	global $post;
	if(1 == get_option('display_pnp'))
		return true;
	return false;
}

/**
 * wpsc product price function
 * @return string - the product price
 */
function wpsc_the_product_price( $no_decimals = false, $only_normal_price = false, $product_id = 0 ) {
	global $wpsc_query, $wpsc_variations, $wpdb;

	if ( ! $product_id )
		$product_id = get_the_ID();

	if ( wpsc_product_has_variations( $product_id ) ) {
		$from_text = __( ' from %s', 'wpsc' );
		$from_text = apply_filters( 'wpsc_product_variation_text', $from_text );
		$output = wpsc_product_variation_price_from( $product_id, array(
			'from_text'         => $from_text,
			'only_normal_price' => $only_normal_price,
		) );
	} else {
		$price = $full_price = get_post_meta( $product_id, '_wpsc_price', true );

		if ( ! $only_normal_price ) {
			$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );

			if ( ( $full_price > $special_price ) && ( $special_price > 0 ) )
				$price = $special_price;
		}

		if ( true == $no_decimals ) {
			$price = explode( ".", $price );
			$price = array_shift( $price );
		}

		$price = apply_filters( 'wpsc_do_convert_price', $price, $product_id );
		$args = array(
			'display_as_html' => false,
			'display_decimal_point' => ! $no_decimals
		);
		$output = wpsc_currency_display( $price, $args );
	}
	return $output;
}

/**
 * wpsc display categories function
 * Used to determine whether to display products on the page
 * @return boolean - true for yes, false for no
 */
function wpsc_display_categories() {
	global $wp_query;
	$output = false;
	if ( !is_numeric( get_option( 'wpsc_default_category' ) ) && ! get_query_var( 'product_tag' ) ) {

		if ( isset( $wp_query->query_vars['products'] ) )
			$category_id = $wp_query->query_vars['products'];
		else if ( isset( $_GET['products'] ) )
			$category_id = $_GET['products'];

		// if we have no categories, and no search, show the group list
		if ( is_numeric( get_option( 'wpsc_default_category' ) ) || (isset( $product_id ) && is_numeric( $product_id )) )
			$output = true;
		if ( (get_option( 'wpsc_default_category' ) == 'all+list'))
			$output = true;

		if (get_option( 'wpsc_default_category' ) == 'list' && (!isset($wp_query->query_vars['wpsc_product_category']) || !isset($wp_query->query_vars['product_tag']) && get_option('wpsc_display_categories')))
			$output = true;

	}

	if ( isset( $category_id ) && $category_id > 0 )
		$output = false;
	if ( get_option( 'wpsc_display_categories' ))
		$output = true;

	return $output;
}

/**
 * wpsc product creation time function
 * @return string - the product price
 */
function wpsc_product_creation_time( $format = null ) {
	global $wpsc_query;

	if ( $format == null )
		$format = "Y-m-d H:i:s";

	return mysql2date( $format, $wpsc_query->product['date_added'] );
}

/**
 * wpsc check variation stock availability function
 * @return string - the product price
 */
function wpsc_check_variation_stock_availability( $product_id, $variations ) {
	global $wpdb;
	$selected_post = get_posts( array(
				'post_parent'      => $product_id,
				'post_type'        => "wpsc-product",
				'post_status'      => 'any',
				'suppress_filters' => true,
				'numberposts'      => -1,
			) );

	$selected_variation = false;

	foreach ( $selected_post as $variation ) {
		$matches = 0;
		$terms = wpsc_get_product_terms( $variation->ID, 'wpsc-variation' );
		foreach ( $terms as $term ) {
			if ( in_array( $term->term_id, $variations ) )
				$matches++;
		}

		if ( $matches == count( $variations ) ) {
			$selected_variation = $variation->ID;
		}
	}

	if ( ! $selected_variation )
		return false;

	if ( wpsc_product_has_stock( $selected_variation ) ) {
		$stock = get_product_meta( $selected_variation, 'stock', true );
		if ( $stock === '' )
			return true;

		return (int) $stock;
	}

	return 0;
}

/**
 * wpsc product has stock function
 * @return boolean - true if the product has stock or does not use stock, false if it does not
 */
function wpsc_product_has_stock( $id = null ) {
	global $wpdb;
	// maybe do wpsc_clear_stock_claims first?
	if ( is_numeric( $id ) && ( $id > 0 ) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$stock = get_post_meta( $id, '_wpsc_stock', true );
	if ( $stock === '' )
		return true;

	$variations = get_children( array( "post_type" => "wpsc-product", "post_parent" => $id ) );
	$filter_name = empty( $variations ) ? 'wpsc_product_stock' : 'wpsc_product_variation_stock';
	$stock = apply_filters( $filter_name, (int) $stock, $id );

	if ( ! empty( $variations ) ) {
		foreach ( $variations as $variation ) {
			if ( wpsc_product_has_stock( $variation->ID ) )
				return true;
		}
	} elseif ( $stock > 0 ) {
		$claimed_query = new WPSC_Claimed_Stock( array( 'product_id' => $id ) );
		$claimed_stock = $claimed_query->get_claimed_stock_count();
		if( $stock - $claimed_stock > 0 )
			return true;
	}

	return false;
}

/**
 * wpsc_is_product_external( $product_id = 0 )
 *
 * Checks if current product is external.
 *
 * @param int $product_id
 */
function wpsc_is_product_external( $product_id = 0 ) {

	// Get product ID if incorrect value was passed
	if ( empty( $product_id ) || !is_numeric( $product_id ) )
		$product_id = wpsc_the_product_id();

	// Get external link
	$external_link = wpsc_product_external_link( $product_id );

	// Use external if set
	if ( !empty( $external_link ) )
		return true;
	else
		return false;
}

/**
 * wpsc product remaining stock function
 * @return integer - the amount of remaining stock, or null if product is stockless
 */
function wpsc_product_remaining_stock( $id = null ) {
	if ( is_numeric( $id ) && ($id > 0) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$is_limited_stock = get_post_meta( $id, '_wpsc_stock', true );

	if ( is_numeric( $is_limited_stock ) ) {
		$product_stock = get_post_meta( $id, '_wpsc_stock', true );
		return absint( $product_stock );
	} else {
		return null;
	}
}

/**
 * wpsc is donation function
 * @return boolean - true if it is a donation, otherwise false
 */
function wpsc_product_is_donation( $id = null ) {
	if ( is_numeric( $id ) && ($id > 0) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$is_donation = get_post_meta( $id, '_wpsc_is_donation', true );
	if ( $is_donation == 1 )
		return true;
	else
		return false;

}

/**
 * wpsc product on special function
 * @return boolean - true if the product is on special, otherwise false
 */
function wpsc_product_on_special( $id = 0 ) {
	static $on_special = array();

	global $wpsc_query, $wpdb;

	if ( ! $id )
		$id = get_the_ID();

	if ( ! isset( $on_special[ $id ] ) ) {
		// don't rely on product sales price if it has variations
		if ( wpsc_product_has_variations( $id ) ) {
			$sql = $wpdb->prepare("
				SELECT COUNT(*)
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS pm ON pm.post_id = p.id AND pm.meta_key = '_wpsc_special_price' AND pm.meta_value != '0' AND pm.meta_value != ''
				INNER JOIN {$wpdb->postmeta} AS pm2 ON pm2.post_id = p.id AND pm2.meta_key = '_wpsc_stock' AND pm2.meta_value != '0'
				INNER JOIN {$wpdb->postmeta} AS pm3 ON pm3.post_id = p.id AND pm3.meta_key = '_wpsc_price' AND CAST(pm3.meta_value AS DECIMAL(10,2)) > CAST(pm.meta_value AS DECIMAL(10,2))
				WHERE
					p.post_type = 'wpsc-product'
					AND
					p.post_parent = %d
			", $id );

			$results = $wpdb->get_var( $sql );

			$on_special[ $id ] = ( $results > 0 );
		} else {
			$price             = get_product_meta( $id, 'price', true );
			$special_price     = get_product_meta( $id, 'special_price', true );
			$on_special[ $id ] = $special_price > 0 && $price > $special_price;
		}
	}

	return apply_filters( 'wpsc_product_on_special', $on_special[ $id ], $id );
}

/**
 * wpsc product is modifiable function
 * @return boolean - true if the product has a file
 */
function wpsc_product_is_customisable() {
	global $wpsc_query, $wpdb;
	$id = get_the_ID();
	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( (isset($product_meta['engraved']) && $product_meta['engraved'] == true) || (isset($product_meta['can_have_uploaded_image']) && $product_meta['can_have_uploaded_image'] == true) )
		return true;

	return false;
}

/**
 * wpsc product has personal text function
 * @return boolean - true if the product has a file
 */
function wpsc_product_has_personal_text() {
	global $wpsc_query, $wpdb;
	$id = get_the_ID();
	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset($product_meta['engraved']) && $product_meta['engraved'] == true )
		return true;

	return false;
}

/**
 * wpsc product has personal file function
 * @return boolean - true if the product has a file
 */
function wpsc_product_has_supplied_file() {
	global $wpsc_query, $wpdb;
	$id = get_the_ID();
	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset($product_meta['can_have_uploaded_image']) && $product_meta['can_have_uploaded_image'] == true )
		return true;

	return false;
}

/**
 * wpsc product postage and packaging function
 * @return string - currently only valid for flat rate
 */
function wpsc_product_postage_and_packaging( $id = null ) {
	if ( isset( $id ) && is_numeric( $id ) && ($id > 0) )
		$id = absint( $id );
	else
		$id = get_the_ID();

	$product_meta = get_post_meta( $id, '_wpsc_product_metadata', true );
	if ( isset(  $product_meta['shipping'] ) && is_array( $product_meta['shipping'] ) &&  1 != $product_meta['no_shipping'])
		return wpsc_currency_display( apply_filters( 'wpsc_product_postage_and_packaging', $product_meta['shipping']['local'] ) );
	else
		return wpsc_currency_display( 0 );


}

/**
 * WPSC Product Image
 *
 * Gets the product image URL. Uses the post thumbnail if set.
 *
 * @param  int     $width                   Image width
 * @param  int     $height                  Image height
 * @param  int     $product_id              Product ID
 * @return string                           The URL to the thumbnail image
 *
 * @uses   wpsc_the_product_thumbnail_id()  Get the product thumbnail ID
 */
function wpsc_the_product_image( $width = '', $height = '', $product_id = '' ) {
	if ( empty( $product_id ) )
		$product_id = get_the_ID();

	// If variation, get parent product
	$product = get_post( $product_id );
	if ( $product->post_parent > 0 )
		$product_id = $product->post_parent;

	$src = false;

	$product_thumbnail_id = wpsc_the_product_thumbnail_id( $product_id );
	$src = wp_get_attachment_image_src( $product_thumbnail_id, 'large' );
	$src = is_array( $src ) ? $src[0] : $src;

	// WordPress's esc_url() function strips out spaces, so encode them here to ensure they don't get stripped out
	// Ref: http://core.trac.wordpress.org/ticket/23605
	$src = str_replace( ' ', '%20', $src );

	return apply_filters( 'wpsc_product_image', set_url_scheme( $src ), $product_id, $product );
}

/**
 * wpsc check display type
 *
 * Check the display view for the selected category
 *
 * @return string - display type
 */
function wpsc_check_display_type(){
	global $wpsc_query, $post;
	if(isset($wpsc_query->query_vars['taxonomy']) && 'wpsc_product_category' == $wpsc_query->query_vars['taxonomy'] && is_string($wpsc_query->query_vars['term']) && 1 < $wpsc_query->post_count)
		$display_type =	wpsc_get_the_category_display($wpsc_query->query_vars['term']);
	else
		$display_type = get_option('product_view');
	return $display_type;
}

/**
 * wpsc product thumbnail function
 *
 * Show the thumbnail image for the product
 *
 * @return string - the URL to the thumbnail image
 */
function wpsc_the_product_thumbnail( $width = null, $height = null, $product_id = 0, $page = false ) {
	$thumbnail = false;
	$display = wpsc_check_display_type();

	// Get the product ID if none was passed
	if ( empty( $product_id ) )
		$product_id = get_the_ID();

	// Load the product
	$product = get_post( $product_id );

	$thumbnail_id = wpsc_the_product_thumbnail_id( $product_id );

	// If no thumbnail found for item, get its parent image
	if ( ! $thumbnail_id && ( is_a( $product, 'WP_Post' ) && $product->post_parent ) ) {
		$thumbnail_id = wpsc_the_product_thumbnail_id( $product->post_parent );
	}

	// if still no thumbnail ID is found, return our fallback function
	if ( ! $thumbnail_id ) {
		return wpsc_product_image();
	}

	if ( ! $page ) {
		$page = is_single() ? 'single' : 'products-page';
	}

	if ( ! $width && ! $height ) {
		$width  = get_option( 'product_image_width' );
		$height = get_option( 'product_image_height' );

		// Overwrite height & width if custom dimensions exist for thumbnail_id
		if ( 'grid' != $display && 'products-page' == $page && isset( $thumbnail_id ) ) {
			$custom_width  = get_post_meta( $thumbnail_id, '_wpsc_custom_thumb_w', true );
			$custom_height = get_post_meta( $thumbnail_id, '_wpsc_custom_thumb_h', true );

			if ( ! empty( $custom_width ) && ! empty( $custom_height ) ) {
				$width  = $custom_width;
				$height = $custom_height;
			}
		} elseif ( $page == 'single' && isset( $thumbnail_id ) ) {
			$custom_thumbnail = get_post_meta( $thumbnail_id, '_wpsc_selected_image_size', true );

			if ( ! $custom_thumbnail ) {
				$custom_thumbnail = 'medium-single-product';
				$current_size = image_get_intermediate_size( $thumbnail_id, $custom_thumbnail );
				$settings_width  = get_option( 'single_view_image_width' );
				$settings_height = get_option( 'single_view_image_height' );

				if ( ! $current_size || ( $current_size['width'] != $settings_width && $current_size['height'] != $settings_height ) )
					_wpsc_regenerate_thumbnail_size( $thumbnail_id, $custom_thumbnail );
			}
			$src = wp_get_attachment_image_src( $thumbnail_id, $custom_thumbnail );

			if ( ! empty( $src ) && is_string( $src[0] ) )
				$thumbnail = $src[0];
		} elseif ( $page == 'manage-products' && isset( $thumbnail_id ) ) {
			$current_size = image_get_intermediate_size( $thumbnail_id, 'admin-product-thumbnails' );

			if ( ! $current_size )
				_wpsc_regenerate_thumbnail_size( $thumbnail_id, 'admin-product-thumbnails' );

			$src = wp_get_attachment_image_src( $thumbnail_id, 'admin-product-thumbnails' );

			if ( ! empty( $src ) && is_string( $src[0] ) )
				$thumbnail = $src[0];
		}
	}

	// Calculate the height based on the ratio of the original dimensions.
	if ( $height == 0 || $width == 0 ) {
		$attachment_meta = get_post_meta( $thumbnail_id, '_wp_attachment_metadata', true );

		$original_width  = isset( $attachment_meta['width'] )	? absint( $attachment_meta['width'] )	: 0;
		$original_height = isset( $attachment_meta['height'] )	? absint( $attachment_meta['height'] )	: 0;

		// bail if either original value is zero. can't divide by zero.
		if ( $original_width == 0 || $original_height == 0 )
			return;

		if ( $width != 0 ) {
			$height = ( $original_height / $original_width ) * $width;
			$height = round( $height, 0 );
		} elseif ( $height != 0 ) {
			$width = ( $original_width / $original_height ) * $height;
			$width = round( $width, 0 );
		}
	}

	if ( ! $thumbnail && isset( $thumbnail_id ) )
		$thumbnail = wpsc_product_image( $thumbnail_id, $width, $height );

	// WordPress's esc_url() function strips out spaces, so encode them here to ensure they don't get stripped out
	// Ref: http://core.trac.wordpress.org/ticket/23605
	$thumbnail = str_replace( ' ', '%20', $thumbnail );

	return apply_filters( 'wpsc_the_product_thumbnail', set_url_scheme( $thumbnail ), $product_id, $product );
}

/**
 * Return the class(es) that should be applied to a product image's <a> tag.
 *
 * If the thickbox effect is enabled for product images (presentation setting), the thickbox class name is included
 *
 * This function is called from theme files when outputting product img tags
 *
 * @since 3.8
 * @return string space-separated list of class names (for use in a class="") attribute
 */
function wpsc_the_product_image_link_classes() {
	$classes = array( );
	if ( get_option( 'show_thumbnails_thickbox' ) )
		$classes[] = 'thickbox';

	$classes[] = 'preview_link';

	$classes = apply_filters( 'wpsc_the_product_image_link_classes', $classes );
	return implode( ' ', $classes );
}

/**
 * wpsc product comment link function
 * @return string - javascript required to make the intense debate link work
 */
function wpsc_product_comment_link() {
	// add the product comment link
	global $wpsc_query;

	if ( get_option( 'wpsc_enable_comments' ) == 1 ) {
		$enable_for_product = get_product_meta( get_the_ID(), 'enable_comments' );

		if ( (get_option( 'wpsc_comments_which_products' ) == 1 && $enable_for_product == '') || $enable_for_product == 'yes' ) {
			$original = array( "&", "'", ":", "/", "@", "?", "=" );
			$entities = array( "%26", "%27", "%3A", "%2F", "%40", "%3F", "%3D" );

			$output = "<div class=\"clear comments\">
						<script src='https://www.intensedebate.com/js/getCommentLink.php?acct=" . get_option( "wpsc_intense_debate_account_id" ) . "&postid=product_" . $wpsc_query->product['id'] . "&posttitle=" . urlencode( get_the_title() ) . "&posturl=" . str_replace( $original, $entities, get_permalink( get_the_ID() ) ) . "&posttime=" . urlencode( date( 'Y-m-d h:i:s', time() ) ) . "&postauthor=author_" . get_the_ID() . "' type='text/javascript' defer='defer'></script>
					</div>";
		}
	}
	return $output;
}

/**
 * wpsc product comments function
 * @return string - javascript for the intensedebate comments
 */
function wpsc_product_comments() {
	global $wpsc_query;
	$output = '';
	// add the product comments
	if ( get_option( 'wpsc_enable_comments' ) == 1 ) {
		$enable_for_product = get_product_meta( $wpsc_query->product['id'], 'enable_comments' );

		if ( (get_option( 'wpsc_comments_which_products' ) == 1 && $enable_for_product == '') || $enable_for_product == 'yes' ) {
			$output = "<script>
				var idcomments_acct = '" . esc_js( get_option( 'wpsc_intense_debate_account_id' ) ) . "';
				var idcomments_post_id = 'product_" . $wpsc_query->product['id'] . "';
				var idcomments_post_url = encodeURIComponent('" . get_permalink( $wpsc_query->product['id'] ) . "');
				</script>
				<span id=\"IDCommentsPostTitle\" style=\"display:none\"></span>
				<script type='text/javascript' src='https://www.intensedebate.com/js/genericCommentWrapperV2.js'></script>
				";
		}
	}
	return $output;
}

/**
 * wpsc have custom meta function
 * @return boolean - true while we have custom meta to display
 */
function wpsc_have_custom_meta() {
	global $wpsc_custom_meta;
	return esc_html( $wpsc_custom_meta->have_custom_meta() );
}

/**
 * wpsc the custom meta function
 * @return nothing - iterate through the custom meta vallues
 */
function wpsc_the_custom_meta() {
	global $wpsc_custom_meta;
	return $wpsc_custom_meta->the_custom_meta();
}

/**
 * wpsc custom meta name function
 * @return string - the custom metal name
 */
function wpsc_custom_meta_name() {
	global $wpsc_custom_meta;
	return esc_html( $wpsc_custom_meta->custom_meta_values['meta_key'] );
}

/**
 * wpsc custom meta value function
 * @return string - the custom meta value
 */
function wpsc_custom_meta_value() {
	global $wpsc_custom_meta;
	return esc_html( $wpsc_custom_meta->custom_meta_values['meta_value'] );
}

/**
 * wpsc have variation groups function
 * @return boolean - true while we have variation groups
 */
function wpsc_have_variation_groups() {
	global $wpsc_variations;
	return $wpsc_variations->have_variation_groups();
}

/**
 * wpsc the variation group function
 * @return nothing - iterate through the variation groups
 */
function wpsc_the_variation_group() {
	global $wpsc_variations;
	$wpsc_variations->the_variation_group();
}

/**
 * wpsc have variations function
 * @return boolean - true while we have variations
 */
function wpsc_have_variations() {
	global $wpsc_variations;
	return $wpsc_variations->have_variations();
}

/**
 * wpsc the variation function
 * @return nothing - iterate through the variations
 */
function wpsc_the_variation() {
	global $wpsc_variations;
	$wpsc_variations->the_variation();
}


function wpsc_product_has_multicurrency() {
	global $wpdb, $wpsc_query;

	$currency = get_product_meta(get_the_ID(),'currency',true);
	if ( ! empty( $currency ) )
		return true;
	else
		return false;
}

function wpsc_display_product_multicurrency() {
	global $wpdb, $wpsc_query;

	$results = get_product_meta(get_the_ID(),'currency',true);
	if ( count( $results ) > 0 ) {
		foreach ( (array)$results as $isocode => $curr ) {
			echo apply_filters( 'wpsc_display_product_multicurrency', '<span class="wpscsmall pricefloatright pricedisplay">' . $isocode . ': ' . wpsc_currency_display( $curr, array( 'isocode' => $isocode ) ) . '</span><br />', $isocode, $curr );
		}
	}

	return false;
}

/**
 * wpsc variation group name function
 * @return string - the variation group name
 */
function wpsc_the_vargrp_name() {
	global $wpsc_variations;
	return apply_filters( 'wpsc_vargrp_name', $wpsc_variations->variation_group->name, $wpsc_variations->variation_group );
}

/**
 * wpsc variation group form ID function
 * @return string - the variation group form id, for labels and the like
 */
function wpsc_vargrp_form_id() {
	// generate the variation group form ID;
	global $wpsc_variations, $wpsc_variations;
	$product_id = get_the_ID();
	$form_id = "variation_select_{$product_id}_{$wpsc_variations->variation_group->term_id}";
	return $form_id;
}

/**
 * wpsc variation group ID function
 * @return integer - the variation group ID
 */
function wpsc_vargrp_id() {
	global $wpsc_variations;
	return $wpsc_variations->variation_group->term_id;
}

/**
 * wpsc the variation name function
 * @return string - the variation name
 */
function wpsc_the_variation_name() {
	global $wpsc_variations;
	return esc_html( apply_filters( 'wpsc_variation_name', $wpsc_variations->variation->name ) );
}

/**
 * wpsc the variation ID function
 * @return integer - the variation ID
 */
function wpsc_the_variation_id() {
	global $wpsc_variations;
	return $wpsc_variations->variation->term_id;
}

/**
 * wpsc the variation out_of_stock function
 * @return string - HTML attribute to disable select options and radio buttons
 */
function wpsc_the_variation_out_of_stock() {
	global $wpsc_query, $wpdb, $wpsc_variations;
	$out_of_stock = false;

	// If there is more than one variation group we cannot determine a stock status for individual variations
	// Also, if the item is not stock limited, there is no need to check variation stock status
	$product_id = get_the_ID();

	$stock = get_product_meta( $product_id, 'stock', true );
	if ( ($wpsc_variations->variation_group_count == 1) && is_numeric( $stock ) && isset( $wpsc_variations->variation->slug ) ) {

		$product_id = get_the_ID();
		$variation_group_id = $wpsc_variations->variation_group->term_id;
		$variation_id = $wpsc_variations->variation->term_id;

		$wpq = array( 'variations' => $wpsc_variations->variation->slug,
			'post_status' => 'inherit',
			'post_type' => 'wpsc-product',
			'post_parent' => $product_id );
		$query = new WP_Query( $wpq );

		if ( $query->post_count != 1 ) {
			// Should never happen
			return FALSE;
		}

		$variation_product_id = $query->posts[0]->ID;

		$stock = get_product_meta( $variation_product_id, "stock" );
		$stock = $stock[0];
		if ( $stock < 1 ) {
			$out_of_stock = true;
		}
	}

	if ( $out_of_stock == true )
		return "disabled='disabled'";
	else
		return '';

}

/**
 * wpsc product rater function
 * @return string - HTML to display the product rater
 */
function wpsc_product_rater() {
	global $wpsc_query;
	$product_id = get_the_ID();
	$output = '';
	if ( get_option( 'product_ratings' ) == 1 ) {
		$output .= "<div class='product_footer'>";

		$output .= "<div class='product_average_vote'>";
		$output .= "<strong>" . __( 'Avg. Customer Rating', 'wpsc' ) . ":</strong>";
		$output .= wpsc_product_existing_rating( $product_id );
		$output .= "</div>";

		$output .= "<div class='product_user_vote'>";

		$output .= "<strong><span id='rating_" . $product_id . "_text'>" . __( 'Your Rating', 'wpsc' ) . ":</span>";
		$output .= "<span class='rating_saved' id='saved_" . $product_id . "_text'> " . __( 'Saved', 'wpsc' ) . "</span>";
		$output .= "</strong>";

		$output .= wpsc_product_new_rating( $product_id );
		$output .= "</div>";
		$output .= "</div>";
	}
	return $output;
}

function wpsc_product_existing_rating( $product_id ) {
	global $wpdb;
	$get_average = $wpdb->get_results( $wpdb->prepare( "SELECT AVG(`rated`) AS `average`, COUNT(*) AS `count` FROM `" . WPSC_TABLE_PRODUCT_RATING . "` WHERE `productid`= %d ", $product_id ), ARRAY_A );
	$average = floor( $get_average[0]['average'] );
	$count = $get_average[0]['count'];
	$output  = "  <span class='votetext'>";
	for ( $l = 1; $l <= $average; ++$l ) {
		$output .= "<img class='goldstar' src='" . WPSC_CORE_IMAGES_URL . "/gold-star.png' alt='$l' title='$l' />";
	}
	$remainder = 5 - $average;
	for ( $l = 1; $l <= $remainder; ++$l ) {
		$output .= "<img class='goldstar' src='" . WPSC_CORE_IMAGES_URL . "/grey-star.png' alt='$l' title='$l' />";
	}
	$output .= "<span class='vote_total'>&nbsp;(<span id='vote_total_{$product_id}'>" . $count . "</span>)</span> \r\n";
	$output .= "</span> \r\n";
	return $output;
}

function wpsc_product_new_rating( $product_id ) {
	global $wpdb;

	$cookie_data = '';
	if (isset($_COOKIE['voting_cookie'][$product_id])) {
		$cookie_data = explode( ",", $_COOKIE['voting_cookie'][$product_id] );
	}

	$vote_id = 0;

	if ( isset($cookie_data[0]) &&  is_numeric( $cookie_data[0] ) )
		$vote_id = absint( $cookie_data[0] );

	$previous_vote = 1;
	if ( $vote_id > 0 )
		$previous_vote = $wpdb->get_var( "SELECT `rated` FROM `" . WPSC_TABLE_PRODUCT_RATING . "` WHERE `id`='" . $vote_id . "' LIMIT 1" );

	$output = "<form class='wpsc_product_rating' method='post'>\n";
	$output .= "			<input type='hidden' name='wpsc_ajax_action' value='rate_product' />\n";
	$output .= "			<input type='hidden' class='wpsc_rating_product_id' name='product_id' value='{$product_id}' />\n";
	$output .= "			<select class='wpsc_select_product_rating' name='product_rating'>\n";
	$output .= "					<option " . (($previous_vote == '1') ? "selected='selected'" : '') . " value='1'>1</option>\n";
	$output .= "					<option " . (($previous_vote == '2') ? "selected='selected'" : '') . " value='2'>2</option>\n";
	$output .= "					<option " . (($previous_vote == '3') ? "selected='selected'" : '') . " value='3'>3</option>\n";
	$output .= "					<option " . (($previous_vote == '4') ? "selected='selected'" : '') . " value='4'>4</option>\n";
	$output .= "					<option " . (($previous_vote == '5') ? "selected='selected'" : '') . " value='5'>5</option>\n";
	$output .= "			</select>\n";
	$output .= "			<input type='submit' value='" . __( 'Save', 'wpsc' ) . "'>";
	$output .= "	</form>";
	return $output;
}

/**
 * wpsc has pages function
 * @return boolean - true if we have pages
 */
function wpsc_has_pages() {
	if(1 == get_option('use_pagination'))
		return true;
	else
		return false;

}

/**
 * this is for the multi adding property, it checks to see whether multi adding is enabled;
 *
 */
function wpsc_has_multi_adding() {
	if ( get_option( 'multi_add' ) == 1 && (get_option( 'addtocart_or_buynow' ) != 1) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * wpsc product count function
 * @return string - the page URL
 */
function wpsc_product_count() {
	global $wp_query;
	return count($wp_query->posts);
}

//The following code was removed from WP 3.8, present in 3.7 - Not sure why it was removed and not refactored. (JS)

/**
 * wpsc the variation price function
 * @return string - the variation price
 */
function wpsc_the_variation_price( $return_as_numeric = false ) {
	global $wpdb, $wpsc_variations;
	if ( $wpsc_variations->variation_count > 0 ) {

		$product_id = get_the_ID();
		$wpq = array(
			'variations'  => $wpsc_variations->variation->slug,
			'post_status' => array( 'inherit', 'publish' ),
			'post_type'   => 'wpsc-product',
			'post_parent' => $product_id
		);

		$query = new WP_Query( $wpq );
		// Should never happen
		if ( $query->post_count != 1 )
			return false;

		$variation_product_id = $query->posts[0]->ID;

		$price         = get_product_meta( $variation_product_id, 'price',true );
		$special_price = get_product_meta( $variation_product_id, 'special_price', true );

		if ( $special_price < $price && $special_price > 0 )
			$price = $special_price;

		$price  = apply_filters( 'wpsc_do_convert_price', $price, $product_id, $variation_product_id );
		$output = $return_as_numeric ? $price :  wpsc_currency_display( $price, array( 'display_as_html' => false ) );
	} else {
		$output = false;
	}

	return $output;
}

/**
 * wpsc the variation stock function
 * @return mixed - Stock level for the variation or FALSE if it can't be calculated
 */
function wpsc_the_variation_stock() {
	global $wpdb, $wpsc_variations;

	if ( $wpsc_variations->variation_count > 0 ) {

		$product_id = get_the_ID();

		$wpq = array( 'variations' => $wpsc_variations->variation->slug,
			'post_status' => 'inherit',
			'post_type'   => 'wpsc-product',
			'post_parent' => $product_id );

		$query = new WP_Query( $wpq );

		// Should never happen
		if ( $query->post_count != 1 )
			return false;

		// Get the stock count
		$vp_id     = $query->posts[0]->ID;
		$stock     = get_product_meta( $vp_id, "stock" );
		$stock[0]  = apply_filters( 'wpsc_product_variation_stock', $stock[0], $id );
		$output    = $stock[0];
	} else {
		return false;
	}

	return $output;
}

/**
 * wpsc_category_grid_view function
 * @return bool - whether the category is in grid view or not
 */
function wpsc_category_grid_view(){
	if(get_option('wpsc_category_grid_view') == 1)
		return true;
	else
		return false;
}

/**
 * wpsc_show_thumbnails function
 * @return bool - whether to show thumbnails or not
 */
function wpsc_show_thumbnails(){
	return get_option('show_thumbnails');
}

/**
 * gold_cart_display_gallery function
 * @return bool - whether to show gold cart gallery or not
 */
function gold_cart_display_gallery(){
	return function_exists('gold_shpcrt_display_gallery');
}

function wpsc_remove_currency_code( $args ) {

	$args['display_currency_symbol'] = false;
	$args['display_currency_code']  = false;

	return $args;
}

function wpsc_you_save( $args = null ){

	$defaults = array(
		'product_id' => false,
		'type'       => 'percentage',
		'variations' => false
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	global $wpdb;

	if ( ! $product_id )
		if(function_exists('wpsc_the_product_id')){
			//select the variation ID with lowest price
			$product_id = $wpdb->get_var('SELECT `posts`.`id` FROM ' . $wpdb->posts . ' `posts` JOIN ' . $wpdb->postmeta . ' `postmeta` ON `posts`.`id` = `postmeta`.`post_id` WHERE `posts`.`post_parent` = ' . wpsc_the_product_id() . ' AND `posts`.`post_type` = "wpsc-product" AND `posts`.`post_status` = "inherit" AND `postmeta`.`meta_key`="_wpsc_price" ORDER BY (`postmeta`.`meta_value`)+0 ASC LIMIT 1');
			if(!$product_id)
				$product_id=wpsc_the_product_id();
		}

	if ( ! $product_id )
		return 0;

	$regular_price = wpsc_calculate_price( $product_id, $variations, false );
	$sale_price = wpsc_calculate_price( $product_id, $variations, true );

	switch( $type ){
		case "amount":
			return $regular_price - $sale_price;
			break;

		default:
			if ( $regular_price == 0 )
				return 0;
			else
				return number_format( ( $regular_price - $sale_price ) / $regular_price * 100, 2 );
	}
}

function wpsc_the_product_price_display( $args = array() ) {
	if ( empty( $args['id'] ) )
		$id = get_the_ID();
	else
		$id = (int) $args['id'];

	$defaults = array(
		'id' => $id,
		'old_price_text'   => __( 'Old Price: %s', 'wpsc' ),
		'price_text'       => __( 'Price: %s', 'wpsc' ),
		/* translators     : %1$s is the saved amount text, %2$s is the saved percentage text, %% is the percentage sign */
		'you_save_text'    => __( 'You save: %s', 'wpsc' ),
		'old_price_class'  => 'pricedisplay wpsc-product-old-price ' . $id,
		'old_price_before' => '<p %s>',
		'old_price_after'  => '</p>',
		'old_price_amount_id'     => 'old_product_price_' . $id,
		'old_price_amount_class' => 'oldprice',
		'old_price_amount_before' => '<span class="%1$s" id="%2$s">',
		'old_price_amount_after' => '</span>',
		'price_amount_id'     => 'product_price_' . $id,
		'price_class'  => 'pricedisplay wpsc-product-price ' . $id,
		'price_before' => '<p %s>',
		'price_after' => '</p>',
		'price_amount_class' => 'currentprice pricedisplay ' . $id,
		'price_amount_before' => '<span class="%1$s" id="%2$s">',
		'price_amount_after' => '</span>',
		'you_save_class' => 'pricedisplay wpsc-product-you-save product_' . $id,
		'you_save_before' => '<p %s>',
		'you_save_after' => '</p>',
		'you_save_amount_id'     => 'yousave_' . $id,
		'you_save_amount_class' => 'yousave',
		'you_save_amount_before' => '<span class="%1$s" id="%2$s">',
		'you_save_amount_after'  => '</span>',
		'output_price'     => true,
		'output_old_price' => true,
		'output_you_save'  => true,
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	// if the product has no variations, these amounts are straight forward...
	$old_price           = wpsc_product_normal_price( $id );
	$current_price       = wpsc_the_product_price( false, false, $id );
	$you_save            = wpsc_you_save( 'type=amount' ) . '! (' . wpsc_you_save() . '%)';
	$you_save_percentage = wpsc_you_save();

	$show_old_price = $show_you_save = wpsc_product_on_special( $id );

	// but if the product has variations and at least one of the variations is on special, we have
	// a few edge cases...
	if ( wpsc_product_has_variations( $id ) && wpsc_product_on_special( $id ) ) {
		// generally it doesn't make sense to display "you save" amount unless the user has selected
		// a specific variation
		$show_you_save = false;

		$old_price_number = wpsc_product_variation_price_from( $id, array( 'only_normal_price' => true ) );
		$current_price_number = wpsc_product_variation_price_from( $id );

		// if coincidentally, one of the variations are not on special, but its price is equal to
		// or lower than the lowest variation sale price, old price should be hidden, and current
		// price should reflect the "normal" price, not the sales price, to avoid confusion
		if ( $old_price_number == $current_price_number ) {
			$show_old_price = false;
			$current_price = wpsc_product_normal_price( $id );
		}
	}

	// replace placeholders in arguments with correct values
	$old_price_class = apply_filters( 'wpsc_the_product_price_display_old_price_class', $old_price_class, $id );
	$old_price_amount_class = apply_filters( 'wpsc_the_product_price_display_old_price_amount_class', $old_price_amount_class, $id );
	$attributes = 'class="' . esc_attr( $old_price_class ) . '"';
	if ( ! $show_old_price )
		$attributes .= ' style="display:none;"';
	$old_price_before = sprintf( $old_price_before, $attributes );
	$old_price_amount_before = sprintf( $old_price_amount_before, esc_attr( $old_price_amount_class ), esc_attr( $old_price_amount_id ) );

	$price_class = 'class="' . esc_attr( apply_filters( 'wpsc_the_product_price_display_price_class', esc_attr( $price_class ), $id )  ) . '"';
	$price_amount_class = apply_filters( 'wpsc_the_product_price_display_price_amount_class', esc_attr( $price_amount_class ), $id );
	$price_before = sprintf( $price_before, $price_class );
	$price_amount_before = sprintf( $price_amount_before, esc_attr( $price_amount_class ), esc_attr( $price_amount_id ) );

	$you_save_class = apply_filters( 'wpsc_the_product_price_display_you_save_class', $you_save_class, $id );
	$you_save_amount_class = apply_filters( 'wpsc_the_product_price_display_you_save_amount_class', $you_save_amount_class, $id );
	$attributes = 'class="' . esc_attr( $you_save_class ) . '"';
	if ( ! $show_you_save )
		$attributes .= ' style="display:none;"';
	$you_save_before = sprintf( $you_save_before, $attributes );
	$you_save_amount_before = sprintf( $you_save_amount_before, esc_attr( $you_save_amount_class ), esc_attr( $you_save_amount_id ) );
	$you_save = wpsc_currency_display ( $you_save );

	$old_price     = $old_price_amount_before . $old_price . $old_price_amount_after;
	$current_price = $price_amount_before . $current_price . $price_amount_after;
	$you_save      = $you_save_amount_before . $you_save . $you_save_amount_after;

	$old_price_text = sprintf( $old_price_text, $old_price );
	$price_text     = sprintf( $price_text, $current_price );
	$you_save_text  = sprintf( $you_save_text, $you_save );

	if ( $output_old_price )
		echo $old_price_before . $old_price_text . $old_price_after . "\n";

	if ( $output_price )
		echo $price_before . $price_text . $price_after . "\n";

	if ( $output_you_save )
		echo $you_save_before . $you_save_text . $you_save_after . "\n";
}

/**
 * wpsc_form_action
 *
 * Echo the form action for use in the template files
 *
 * @global <type> $wpec_form_action
 * @return <type>
 */
function wpsc_form_action() {
	echo wpsc_get_form_action();
}
	/**
	 * wpsc_get_form_action
	 *
	 * Return the form action for use in the template files
	 *
	 * @global <type> $wpec_form_action
	 * @return <type>
	 */
	function wpsc_get_form_action() {
		global $wpec_form_action;

		$product_id = wpsc_the_product_id();

		// Function has already ran in this page load
		if ( isset( $wpec_form_action ) ) {
			$action =  $wpec_form_action;

		// No global so figure it out
		} else {

			// Use external if set
			if ( wpsc_is_product_external() ) {
				$action = wpsc_product_external_link( $product_id );

			// Otherwise use this page
			} else {
				$action = wpsc_this_page_url();
			}
		}

		// Return form action
		return $action;
	}

