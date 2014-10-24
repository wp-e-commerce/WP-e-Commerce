<?php
add_action( 'save_post'        , 'wpsc_refresh_page_urls', 10, 2 );
add_action( 'wpsc_theme_footer', 'wpsc_fancy_notifications' );

if ( get_option( 'wpsc_replace_page_title' ) == 1 ) {
	add_filter( 'wp_title', 'wpsc_replace_wp_title', 10, 2 );
}

add_filter( 'post_type_link', 'wpsc_product_link', 10, 3 );

/**
 * wpsc_product_link function.
 * Gets the product link, hooks into post_link
 * Uses the currently selected, only associated or first listed category for the term URL
 * If the category slug is the same as the product slug, it prefixes the product slug with "product/" to counteract conflicts
 *
 * @access public
 * @return void
 */
function wpsc_product_link( $permalink, $post, $leavename ) {
	global $wp_query, $wpsc_page_titles, $wpsc_query, $wp_current_filter;

	$rewritecode = array(
		'%wpsc_product_category%',
		$leavename ? '' : '%postname%',
	);
	if ( is_object( $post ) ) {
		// In wordpress 2.9 we got a post object
		$post_id = $post->ID;
	} else {
		// In wordpress 3.0 we get a post ID
		$post_id = $post;
		$post = get_post( $post_id );
	}

	// Only applies to WPSC products, don't stop on permalinks of other CPTs
	// Fixes http://code.google.com/p/wp-e-commerce/issues/detail?id=271
	if ( 'wpsc-product' !== $post->post_type ) {
		return $permalink;
	}

	if ( 'inherit' === $post->post_status && 0 !== $post->post_parent ) {
		$post_id = $post->post_parent;
		$post    = get_post( $post_id );
	}

	global $wp_rewrite;

	$our_permalink_structure = $wp_rewrite->root;

	// This may become customiseable later
	$our_permalink_structure .= str_replace( basename( home_url() ), '', $wpsc_page_titles['products'] ) . "/%wpsc_product_category%/%postname%/";

	// Mostly the same conditions used for posts, but restricted to items with a post type of "wpsc-product "
	if ( $wp_rewrite->using_permalinks() && ! in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {

		$product_categories = wpsc_get_product_terms( $post_id, 'wpsc_product_category' );
		$product_category_slugs = array( );
		foreach ( $product_categories as $product_category ) {
			$product_category_slugs[] = $product_category->slug;
		}
		// If the product is associated with multiple categories, determine which one to pick
		if ( count( $product_categories ) == 0 ) {
			$category_slug = apply_filters( 'wpsc_uncategorized_product_category', 'uncategorized' );
		} elseif ( count( $product_categories ) > 1 ) {
			if ( (isset( $wp_query->query_vars['products'] ) && $wp_query->query_vars['products'] != null) && in_array( $wp_query->query_vars['products'], $product_category_slugs ) ) {
				$product_category = $wp_query->query_vars['products'];
			} else {
				$link = $product_categories[0]->slug;
				if ( ! in_array( 'wp_head', $wp_current_filter) && isset( $wpsc_query->query_vars['wpsc_product_category'] ) ) {
					$current_cat = $wpsc_query->query_vars['wpsc_product_category'];
					if ( in_array( $current_cat, $product_category_slugs ) )
						$link = $current_cat;
				}

				$product_category = $link;
			}
			$category_slug = $product_category;
		} else {
			// If the product is associated with only one category, we only have one choice
			if ( !isset( $product_categories[0] ) )
				$product_categories[0] = '';

			$product_category = $product_categories[0];

			if ( !is_object( $product_category ) )
				$product_category = new stdClass();

			if ( !isset( $product_category->slug ) )
				$product_category->slug = null;

			$category_slug = $product_category->slug;
		}

		$post_name = $post->post_name;

		if ( get_option( 'product_category_hierarchical_url', 0 ) ) {
			$selected_term = get_term_by( 'slug', $category_slug, 'wpsc_product_category' );
			if ( is_object( $selected_term ) ) {
				$term_chain = array( $selected_term->slug );
				while ( $selected_term->parent ) {
					$selected_term = get_term( $selected_term->parent, 'wpsc_product_category' );
					array_unshift( $term_chain, $selected_term->slug );
				}
				$category_slug = implode( '/', $term_chain );
			}
		}

		if( isset( $category_slug ) && empty( $category_slug ) )
			$category_slug = 'product';

		$category_slug = apply_filters( 'wpsc_product_permalink_cat_slug', $category_slug, $post_id );

		$rewritereplace = array(
			$category_slug,
			$post_name
		);

		$permalink = str_replace( $rewritecode, $rewritereplace, $our_permalink_structure );
		$permalink = user_trailingslashit( $permalink, 'single' );

		$permalink = home_url( $permalink );
	}

	return apply_filters( 'wpsc_product_permalink', $permalink, $post->ID );
}

/**
 * wpsc_list_categories function.
 *
 * @access public
 * @param string $callback_function - The function name you want to use for displaying the data
 * @param mixed $parameters (default: null) - the additional parameters to the callback function
 * @param int $category_id. (default: 0) - The category id defaults to zero, for displaying all categories
 * @param int $level. (default: 0)
 */
function wpsc_list_categories($callback_function, $parameters = null, $category_id = 0, $level = 0) {
	global $wpdb,$category_data;
	$output = '';
	$category_list = get_terms('wpsc_product_category','hide_empty=0&parent='.$category_id);
	if($category_list != null) {
		foreach((array)$category_list as $category) {
			$callback_output = $callback_function($category, $level, $parameters);
			if(is_array($callback_output)) {
				$output .= array_shift($callback_output);
			} else {
				$output .= $callback_output;
			}
			$output .= wpsc_list_categories($callback_function, $parameters , $category->term_id, ($level+1));
			if(is_array($callback_output) && (isset($callback_output[1]))) {
				$output .= $callback_output[1];
			}
		}
	}
	return $output;
}

/**
* Gets the Function Parent Image link and checks whether Image should be displayed or not
*
*/
function wpsc_parent_category_image($show_thumbnails , $category_image , $width, $height, $grid=false, $show_name){

	if(!$show_thumbnails) return;

	if($category_image == WPSC_CATEGORY_URL){
		if(!$show_name) return;
	?>
	<span class='wpsc_category_image item_no_image ' style='width:<?php echo $width; ?>px; height: <?php echo $height; ?>px;'>
		<span class='link_substitute' >
			<span><?php _e('N/A', 'wpsc'); ?></span>
		</span>
	</span>
	<?php
	}else{
	?><img src='<?php echo $category_image; ?>' width='<?php echo $width; ?>' height='<?php echo $height; ?>' /><?php
	}
}
/// category template tags start here

/**
 * Returns true if you're on a tag that is a WPeC tag
 *
 * @since 3.9
 *
 * @uses is_tax()           Returns true/false given taxonomy and takes second parameter of term
 * @param string|array|int  $term   optional    The term you could be checking for
 * @return bool             True if you are on a product_tag false if not
 */
function wpsc_is_in_tag( $term = '' ) {

	return is_tax( 'product_tag', $term );

}

/**
* wpsc starts category query function
* gets passed the query and makes it into a global variable, then starts capturing the html for the category loop
*/
function wpsc_start_category_query($arguments = array()) {
  global $wpdb, $wpsc_category_query;
  $wpsc_category_query = $arguments;
  ob_start();
}

/**
* wpsc print category name function
* places the shortcode for the category name
*/
function wpsc_print_category_name() {
	echo "[wpsc_category_name]";
}

/**
* wpsc print category description function
* places the shortcode for the category description, accepts parameters for the description container
* @param string starting HTML element
* @param string ending HTML element
*/
function wpsc_print_category_description($start_element = '', $end_element = '') {
  global $wpsc_category_query;
  $wpsc_category_query['description_container'] = array('start_element' => $start_element, 'end_element' =>  $end_element);
	echo "[wpsc_category_description]";
}

/**
* wpsc print category url function
* places the shortcode for the category URL
*/
function wpsc_print_category_url() {
	echo "[wpsc_category_url]";
}

/**
* wpsc print category id function
* places the shortcode for the category URL
*/
function wpsc_print_category_id() {
	echo "[wpsc_category_id]";
}

/**
* wpsc print category classes function
* places classes for the category including selected state
*
* please note that "current category" means the category that we are in now,
* and not the category that we are printing for
*
* @param $category_to_print - the category for which we should print classes
* @param $echo - whether to echo the result (true) or return (false)
*/
function wpsc_print_category_classes($category_to_print = false, $echo = true) {
	global $wp_query, $wpdb;
	$result = '';

	//if we are in wpsc category page then get the current category
	$curr_cat = false;
	$term = get_query_var( 'wpsc_product_category' );
	if ( ! $term && get_query_var( 'taxonomy' ) == 'wpsc_product_category' )
		$term = get_query_var( 'term' );
	if ( $term )
		$curr_cat = get_term_by( 'slug', $term, 'wpsc_product_category' );

	//check if we are in wpsc category page and that we have a term_id of the category to print
	//this is done here because none of the following matters if we don't have one of those and we can
	//safely return
	if(isset($category_to_print['term_id']) && $curr_cat){

		//we will need a list of current category parents for the following if statement
		$curr_cat_parents = wpsc_get_term_parents($curr_cat->term_id, 'wpsc_product_category');

		//if current category is the same as the one we are printing - then add wpsc-current-cat class
		if( $category_to_print['term_id'] == $curr_cat->term_id )
			$result = ' wpsc-current-cat ';
		//else check if the category that we are printing is parent of current category
		elseif ( in_array($category_to_print['term_id'], $curr_cat_parents) )
			$result = ' wpsc-cat-ancestor ';
	}

	$result = apply_filters( 'wpsc_print_category_classes', $result, $category_to_print );

	if ( ! empty ( $result ) ) {
		if ( $echo ) {
			echo $result;
		} else {
			return $result;
		}
	}
}

/**
* wpsc print subcategory function
* places the shortcode for the subcategories, accepts parameters for the subcategories container, have this as <ul> and </ul> if using a list
* @param string starting HTML element
* @param string ending HTML element
*/
function wpsc_print_subcategory($start_element = '', $end_element = '') {
  global $wpsc_category_query;
  $wpsc_category_query['subcategory_container'] = array('start_element' => $start_element, 'end_element' =>  $end_element);
  echo "[wpsc_subcategory]";
}
function wpsc_print_category_classes_section(){
	echo "[wpsc_category_classes]";
}

/**
* wpsc print category image function
* places the shortcode for the category image, accepts parameters for width and height
* @param integer width
* @param integer height
*/
function wpsc_print_category_image($width = null, $height = null) {
  global $wpsc_category_query;
  $wpsc_category_query['image_size'] = array('width' => $width, 'height' =>  $height);
	echo "[wpsc_category_image]";
}

/**
* wpsc print category products count function
* places the shortcode for the category product count, accepts parameters for the container element
* @param string starting HTML element
* @param string ending HTML element
*/
function wpsc_print_category_products_count($start_element = '', $end_element = '') {
  global $wpsc_category_query;
  $wpsc_category_query['products_count'] = array('start_element' => $start_element, 'end_element' =>  $end_element);
	echo "[wpsc_category_products_count]";
}

/**
* wpsc end category query function
*/
function wpsc_end_category_query() {
	global $wpdb, $wpsc_category_query;
  $category_html = ob_get_clean();
  echo wpsc_display_category_loop($wpsc_category_query, $category_html);
  unset($GLOBALS['wpsc_category_query']);
}

/**
* wpsc category loop function
* This function recursively loops through the categories to display the category tree.
* This function also generates a tree of categories at the same time
* WARNING: as this function is recursive, be careful what you do with it.
* @param array the category query
* @param string the category html
* @param array the category array branch, is an internal value, leave it alone.
* @return string - the finished category html
*/
function wpsc_display_category_loop($query, $category_html, &$category_branch = null){
	static $category_count_data = array(); // the array tree is stored in this

	if( isset($query['parent_category_id']) )
		$category_id = absint($query['parent_category_id']);
	else
		$category_id = 0;
	$category_data = get_terms('wpsc_product_category','hide_empty=0&parent='.$category_id, OBJECT, 'display');
	$output ='';

	// if the category branch is identical to null, make it a reference to $category_count_data
	if($category_branch === null) {
		$category_branch =& $category_count_data;
	}
	$allowed_tags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array(), 'b'=> array());

	$allowedtags = apply_filters('wpsc_category_description_allowed_tags' , $allowed_tags);

	foreach((array)$category_data as $category_row) {

		// modifys the query for the next round
		$modified_query = $query;
		$modified_query['parent_category_id'] = $category_row->term_id;

		// gets the count of products associated with this category
		$category_count = $category_row->count;


		// Sticks the category description in
		$category_description = '';
		if($category_row->description != '' && ! empty( $query['description_container'] ) ) {
			$start_element = $query['description_container']['start_element'];
			$end_element = $query['description_container']['end_element'];
			$category_description =  $start_element.wpautop(wptexturize( wp_kses( $category_row->description, $allowedtags ))).$end_element;
		}


		// Creates the list of classes on the category item
		$category_classes = wpsc_print_category_classes((array)$category_row, false);

		// Set the variables for this category
		$category_branch[$category_row->term_id]['children'] = array();
		$category_branch[$category_row->term_id]['count'] = (int)$category_count;


		// Recurse into the next level of categories
		$sub_categories = wpsc_display_category_loop($modified_query, $category_html, $category_branch[$category_row->term_id]['children']);

		// grab the product count from the subcategories
		foreach((array)$category_branch[$category_row->term_id]['children'] as $child_category) {
			$category_branch[$category_row->term_id]['count'] += (int)$child_category['count'];
		}

		// stick the category count array together here
		// this must run after the subcategories and the count of products belonging to them has been obtained

		$category_count = $category_branch[$category_row->term_id]['count'];

		$start_element = '';
		$end_element = '';

		if (isset($query['products_count']['start_element'])) {
			$start_element = $query['products_count']['start_element'];
		}

		if (isset($query['products_count']['end_element'])) {
			$end_element = $query['products_count']['end_element'];
		}

		$category_count_html =  $start_element.$category_count.$end_element;


		if ( isset( $query['subcategory_container'] ) && ! empty( $sub_categories ) ) {
			$start_element = $query['subcategory_container']['start_element'];
			$end_element = $query['subcategory_container']['end_element'];
			$sub_categories = $start_element.$sub_categories.$end_element;
		}

		// get the category images
		$category_image = wpsc_place_category_image($category_row->term_id, $modified_query);

		if ( empty( $query['image_size']['width'] ) ) {
			if ( ! wpsc_category_grid_view() )
				$width = wpsc_get_categorymeta( $category_row->term_id, 'image_width' );
			if ( empty( $width ) )
				$width = get_option( 'category_image_width' );
		} else {
			$width = $query['image_size']['width'];
		}

		if ( empty( $query['image_size']['height'] ) ) {
			if ( ! wpsc_category_grid_view() )
				$height = wpsc_get_categorymeta( $category_row->term_id, 'image_height' );
			if ( empty( $height ) )
				$height = get_option( 'category_image_height' );
		} else {
			$height = $query['image_size']['height'];
		}

		$category_image = wpsc_get_categorymeta($category_row->term_id, 'image');
		$category_image_html = '';
		if(($query['show_thumbnails'] == 1)) {
			if((!empty($category_image)) && is_file(WPSC_CATEGORY_DIR.$category_image)) {
				$category_image_html = "<img src='".WPSC_CATEGORY_URL."$category_image' alt='{$category_row->name}' title='{$category_row->name}' style='width: {$width}px; height: {$height}px;' class='wpsc_category_image' />";
			} elseif( isset( $query['show_name'] ) && 1 == $query['show_name']) {
				$category_image_html .= "<span class='wpsc_category_image item_no_image ' style='width: {$width}px; height: {$height}px;'>\n\r";
				$category_image_html .= "	<span class='link_substitute' >\n\r";
				$category_image_html .= "		<span>".__('N/A','wpsc')."</span>\n\r";
				$category_image_html .= "	</span>\n\r";
				$category_image_html .= "</span>\n\r";
			}

		}


		// get the list of products associated with this category.
		$tags_to_replace = array('[wpsc_category_name]',
		'[wpsc_category_description]',
		'[wpsc_category_url]',
		'[wpsc_category_id]',
		'[wpsc_category_classes]',
		'[wpsc_category_image]',
		'[wpsc_subcategory]',
		'[wpsc_category_products_count]');

		$content_to_place = array(
		esc_html($category_row->name),
		$category_description,
		esc_url( get_term_link( $category_row->slug, 'wpsc_product_category' ) ),
		$category_row->term_id,
		$category_classes,
		$category_image_html,
		$sub_categories,
		$category_count_html);

		// Stick all the category html together and concatenate it to the previously generated HTML
		$output .= str_replace($tags_to_replace, $content_to_place ,$category_html);
	}
	return $output;
}

/**
* wpsc category image function
* if no parameters are passed, the category is not resized, otherwise it is resized to the specified dimensions
* @param integer category id
* @param array category query array
* @return string - the category image URL, or the URL of the resized version
*/
function wpsc_place_category_image($category_id, $query) {
	// show the full sized image for the product, if supplied with dimensions, will resize image to those.
		$width = (isset($query['image_size']['width'])) ? ($query['image_size']['width']) : get_option('category_image_width');
		$height = (isset($query['image_size']['height'])) ? ($query['image_size']['height']) : get_option('category_image_height');
		$image_url = "index.php?wpsc_request_image=true&category_id=".$category_id."&width=".$width."&height=".$height;
		return htmlspecialchars($image_url);
}

/// category template tags end here

/**
* wpsc_category_url  function, makes permalink to the category or
* @param integer category ID, can be 0
* @param boolean permalink compatibility, adds a prefix to prevent permalink namespace conflicts
*/
function wpsc_category_url($category_id, $permalink_compatibility = false) {
  return get_term_link( $category_id, 'wpsc_product_category');
}


/**
 * Returns true if you're on a category that is a WPeC category
 *
 * @uses is_tax()           Returns true/false given taxonomy and takes second parameter of term
 * @param string|array|int  $term   optional    The term you could be checking for
 * @return bool             True if you are on a wpsc_product_category false if not
 */
function wpsc_is_in_category( $term = '' ) {

	return is_tax( 'wpsc_product_category', $term );

}


/**
 * Uses a category's, (in the wpsc_product_category taxonomy), slug to find its
 * ID, then returns it.
 *
 * @param string $category_slug The slug of the category who's ID we want.
 * @return (int | bool) Returns the integer ID of the category if found, or a
 * boolean false if the category is not found.
 *
 * @todo Cache the results of this somewhere.  It could save quite a few trips
 * to the MySQL server.
 *
 */
function wpsc_category_id($category_slug = '') {
	if(empty($category_slug))
		$category_slug = get_query_var( 'wpsc_product_category' );
	elseif(array_key_exists('wpsc_product_category', $_GET))
		$category_slug = $_GET['wpsc_product_category'];

	if(!empty($category_slug)) {
		$category = get_term_by('slug', $category_slug, 'wpsc_product_category');
		if(!empty($category->term_id)){
			return $category->term_id;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

/**
* wpsc_category_description function, Gets the category description
* @param integer category ID, can be 0
* @return string category description
*/
function wpsc_category_description($category_id = null) {
  if($category_id < 1)
	$category_id = wpsc_category_id();
  $category = get_term_by( 'id', $category_id, 'wpsc_product_category' );
  return $category ? $category->description : '';
}

function wpsc_category_name($category_id = null) {
	if ( $category_id < 1 )
		$category_id = wpsc_category_id();

	$category = get_term_by( 'id', $category_id, 'wpsc_product_category' );
	return $category ? $category->name : '';
}

function nzshpcrt_display_categories_groups() {
	return '';
}

/** wpsc list subcategories function
		used to get an array of all the subcategories of a category.
*/
function wpsc_list_subcategories($category_id = null) {
  global $wpdb,$category_data;

    $category_list = $wpdb->get_col( $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PRODUCT_CATEGORIES."` WHERE `category_parent` = %d", $category_id ) );

    if($category_list != null) {
	foreach($category_list as $subcategory_id) {
			$category_list = array_merge((array)$category_list, (array)wpsc_list_subcategories($subcategory_id));
		}
	}
    return $category_list;
}

/**
 * wpsc_show_category_thumbnails function
 * @return bool - whether to show category thumbnails or not
 */
function wpsc_show_category_thumbnails(){
	if(get_option('show_category_thumbnails') && wpsc_category_image())
		return true;
	else
		return false;
}

/**
 * wpsc_show_category_description function
 * @return bool - whether to show category description or not
 */
function wpsc_show_category_description(){
	return get_option( 'wpsc_category_description' );
}

/**
 * wpsc buy now button code products function
 * Sorry about the ugly code, this is just to get the functionality back, buy now will soon be overhauled, and this function will then be completely different
 * @return string - html displaying one or more products
 */
function wpsc_buy_now_button( $product_id, $replaced_shortcode = false ) {

	$product_id = absint( $product_id );

	$product            = get_post( $product_id );
	$supported_gateways = array( 'wpsc_merchant_paypal_standard', 'paypal_multiple' );
	$selected_gateways  = get_option( 'custom_gateway_options' );

	if ( $replaced_shortcode ) {
		ob_start();
	}

	if ( in_array( 'wpsc_merchant_paypal_standard', (array) $selected_gateways ) ) {
		if ( $product_id > 0 ) {

			$post_meta     = get_post_meta( $product_id, '_wpsc_product_metadata', true );
			$shipping      = isset( $post_meta['shipping'] ) ? $post_meta['shipping']['local'] : '';
			$price         = get_post_meta( $product_id, '_wpsc_price', true );
			$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );

			if ( $special_price )
				$price = $special_price;

			if ( wpsc_uses_shipping ( ) ) {
				$handling = get_option( 'base_local_shipping' );
			} else {
				$handling = $shipping;
			}

			$has_variants = wpsc_product_has_variations( $product_id ) || ! wpsc_product_has_stock( $product_id );

			$src     = apply_filters( 'wpsc_buy_now_button_src', _x( 'https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif', 'PayPal Buy Now Button', 'wpsc' ) );
			$classes = apply_filters( 'wpsc_buy_now_button_class', "wpsc-buy-now-form wpsc-buy-now-form-{$product_id}" );

            $classes_array = array_map( 'sanitize_html_class', explode( ' ', $classes ) );

            $classes = implode( ' ', $classes_array );

			$button_html = sprintf( '<input%1$s class="wpsc-buy-now-button wpsc-buy-now-button-%2$s" type="image" name="submit" border="0" src="%3$s" alt="%4$s" />',
				disabled( $has_variants, true, false ),
				esc_attr( $product_id ),
				esc_url( $src ),
				esc_attr__( 'PayPal - The safer, easier way to pay online', 'wpsc' )
			);

			$button_html = apply_filters( 'wpsc_buy_now_button_html', $button_html, $product_id );
?>
			<form class="<?php echo( $classes ); ?>" id="buy-now-product_<?php echo $product_id; ?>" target="paypal" action="<?php echo esc_url( home_url() ); ?>" method="post">
				<input type="hidden" name="wpsc_buy_now_callback" value="1" />
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
<?php
				if ( $has_variants ) :
					// grab the variation form fields here
					$wpsc_variations = new wpsc_variations( $product_id );
					while ( wpsc_have_variation_groups() ) : wpsc_the_variation_group();
						printf('<input type="hidden" class="variation-value" name="variation[%1$d]" id="%2$s" value="0"/>', wpsc_vargrp_id(), wpsc_vargrp_form_id() );
					endwhile;
				endif; /* END wpsc_product_has_variations */
?>
				<?php if ( get_option( 'multi_add' ) ) : ?>
					<label for="quantity"><?php esc_html_e( 'Quantity', 'wpsc' ); ?></label>
					<input type="text" size="4" id="quantity" class="wpsc-buy-now-quantity" name="quantity" value="" /><br />
				<?php else: ?>
					<input type="hidden" name="quantity" class="wpsc-buy-now-quantity" value="1" />
				<?php endif ?>
				<?php echo $button_html; ?>
				<img alt='' border='0' width='1' height='1' src='<?php echo esc_url( _x( 'https://www.paypal.com/en_US/i/scr/pixel.gif', 'PayPal Pixel', 'wpsc' ) ); ?>' />
			</form>
			<?php
		}
	}
	if ( $replaced_shortcode ) {
		return ob_get_clean();
	}
}

/**
 * Displays products that were bought along with the product defined by $product_id.
 * This functionality will be deprecated and be provided by a plugin in a future version.
 */
function wpsc_also_bought( $product_id ) {
	global $wpdb;

	if ( get_option( 'wpsc_also_bought' ) == 0 ) {
		return '';
	}

	// To be made customiseable in a future release
	$also_bought_limit = 3;
	$element_widths = 96;
	$image_display_height = 96;
	$image_display_width = 96;

	// Filter will be used by a plugin to provide 'Also Bought' functionality when this is deprecated from core.
	// Filter is currently private and should not be used by plugin/theme devs as it may only be temporary.
	$output = apply_filters( '_wpsc_also_bought', '', $product_id );
	if ( ! empty( $output ) ) {
		return $output;
	}

	// If above filter returns output then the following is ignore and can be deprecated in future.
	$also_bought = $wpdb->get_results( $wpdb->prepare( "SELECT `" . $wpdb->posts . "`.* FROM `" . WPSC_TABLE_ALSO_BOUGHT . "`, `" . $wpdb->posts . "` WHERE `selected_product`= %d AND `" . WPSC_TABLE_ALSO_BOUGHT . "`.`associated_product` = `" . $wpdb->posts . "`.`id` AND `" . $wpdb->posts . "`.`post_status` IN('publish','protected') ORDER BY `" . WPSC_TABLE_ALSO_BOUGHT . "`.`quantity` DESC LIMIT $also_bought_limit", $product_id ), ARRAY_A );
	if ( is_array( $also_bought ) && count( $also_bought ) > 0 ) {
		$output .= '<h2 class="prodtitles wpsc_also_bought">' . __( 'People who bought this item also bought', 'wpsc' ) . '</h2>';
		$output .= '<div class="wpsc_also_bought">';
		foreach ( $also_bought as $also_bought_data ) {
			$output .= '<div class="wpsc_also_bought_item" style="width: ' . $element_widths . 'px;">';
			if ( get_option( 'show_thumbnails' ) == 1 ) {
				$image_path = wpsc_the_product_thumbnail( $image_display_width, $image_display_height, $also_bought_data['ID'] );
				if ( $image_path ) {
					$output .= '<a href="' . esc_attr( get_permalink( $also_bought_data['ID'] ) ) . '" class="preview_link" rel="' . esc_attr( sanitize_html_class( get_the_title( $also_bought_data['ID'] ) ) ) . '">';
					$output .= '<img src="' . esc_attr( $image_path ) . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" />';
					$output .= '</a>';
				} else {
					if ( get_option( 'product_image_width' ) != '' ) {
						$width_and_height = 'width="' . $image_display_height . '" height="' . $image_display_height . '" ';
					} else {
						$width_and_height = '';
					}
					$output .= '<img src="' . WPSC_CORE_THEME_URL . '/wpsc-images/noimage.png" title="' . esc_attr( get_the_title( $also_bought_data['ID'] ) ) . '" alt="' . esc_attr( get_the_title( $also_bought_data['ID'] ) ) . '" id="product_image_' . $also_bought_data['ID'] . '" class="product_image" ' . $width_and_height . '/>';
				}
			}

			$output .= '<a class="wpsc_product_name" href="' . get_permalink( $also_bought_data['ID'] ) . '">' . get_the_title( $also_bought_data['ID'] ) . '</a>';
			if ( ! wpsc_product_is_donation( $also_bought_data['ID'] ) ) {
				// Ideally use the wpsc_the_product_price_display() function here but needs some tweaking
				$price = get_product_meta( $also_bought_data['ID'], 'price', true );
				$special_price = get_product_meta( $also_bought_data['ID'], 'special_price', true );
				if ( ! empty( $special_price ) ) {
					$output .= '<span style="text-decoration: line-through;">' . wpsc_currency_display( $price ) . '</span>';
					$output .= wpsc_currency_display( $special_price );
				} else {
					$output .= wpsc_currency_display( $price );
				}
			}
			$output .= '</div>';
		}
		$output .= '</div>';
		$output .= '<br clear="all" />';
	}
	return $output;
}

/**
 * Get the URL of the loading animation image.
 * Can be filtered using the wpsc_loading_animation_url filter.
 */
function wpsc_loading_animation_url() {
	return apply_filters( 'wpsc_loading_animation_url', WPSC_CORE_THEME_URL . 'wpsc-images/indicator.gif' );
}

function fancy_notifications() {
	return wpsc_fancy_notifications( true );
}
function wpsc_fancy_notifications( $return = false ) {
	static $already_output = false;

	if ( $already_output )
		return '';

	$output = "";
	if ( get_option( 'fancy_notifications' ) == 1 ) {
		$output = "";
		$output .= "<div id='fancy_notification'>\n\r";
		$output .= "  <div id='loading_animation'>\n\r";
		$output .= '<img id="fancy_notificationimage" title="' . esc_attr__( 'Loading', 'wpsc' ) . '" alt="' . esc_attr__( 'Loading', 'wpsc' ) . '" src="' . wpsc_loading_animation_url() . '" />' . __( 'Updating', 'wpsc' ) . "...\n\r";
		$output .= "  </div>\n\r";
		$output .= "  <div id='fancy_notification_content'>\n\r";
		$output .= "  </div>\n\r";
		$output .= "</div>\n\r";
	}

	$already_output = true;

	if ( $return )
		return $output;
	else
		echo $output;
}

function fancy_notification_content( $cart_messages ) {
	$siteurl = get_option( 'siteurl' );
	$output = '';
	foreach ( (array)$cart_messages as $cart_message ) {
		$output .= "<span>" . $cart_message . "</span><br />";
	}
	$output .= "<a href='" . get_option( 'shopping_cart_url' ) . "' class='go_to_checkout'>" . __( 'Go to Checkout', 'wpsc' ) . "</a>";
	$output .= "<a href='#' onclick='jQuery(\"#fancy_notification\").css(\"display\", \"none\"); return false;' class='continue_shopping'>" . __( 'Continue Shopping', 'wpsc' ) . "</a>";
	return $output;
}

/*
 * wpsc product url function, gets the URL of a product,
 * Deprecated, all parameters past the first unused. use get_permalink
 */

function wpsc_product_url( $product_id, $category_id = null, $escape = true ) {
	$post = get_post($product_id);
	if ( isset($post->post_parent) && $post->post_parent > 0) {
		return get_permalink($post->post_parent);
	} else {
		return get_permalink($product_id);
	}
}

function external_link( $product_id ) {
	$link = get_product_meta( $product_id, 'external_link', true );
	if ( !stristr( $link, 'http://' ) ) {
		$link = 'http://' . $link;
	}
	$target = wpsc_product_external_link_target( $product_id );
	$output .= "<input class='wpsc_buy_button' type='button' value='" . wpsc_product_external_link_text( $product_id, __( 'Buy Now', 'wpsc' ) ) . "' onclick='return gotoexternallink(\"$link\", \"$target\")'>";
	return $output;
}

/**
 * wpsc_refresh_page_urls
 *
 * Refresh page urls when pages are updated
 *
 * @param  int    $post_id
 * @param  object $post
 * @uses   wpsc_update_permalink_slugs()
 * @return int    $post_id
 */
function wpsc_refresh_page_urls( $post_id, $post ) {

	if ( ! current_user_can( 'manage_options' ) )
		return;

	if ( 'page' != $post->post_type )
		return;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( ! in_array( $post->post_status, array( 'publish', 'private' ) ) )
		return;

	wpsc_update_permalink_slugs();

	return $post_id;
}

function wpsc_replace_wp_title( $input ) {
	global $wpdb, $wp_query;
	$output = wpsc_obtain_the_title();
	if ( $output != null ) {
		return $output;
	}
	return $input;
}

function wpsc_replace_bloginfo_title( $input, $show ) {
	global $wpdb, $wp_query;
	if ( $show == 'description' ) {
		$output = wpsc_obtain_the_title();
		if ( $output != null ) {
			return $output;
		}
	}
	return $input;
}

