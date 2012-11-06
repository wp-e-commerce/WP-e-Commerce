<?php
/**
 * WP eCommerce category display functions
 *
 * These are functions for the wp-eCommerce categories
 * I would like to use an object and the theme engine for this, but it uses a recursive function, and I cannot think of a way to make that work with an object like the rest of the theme engine.
 *
 * @package wp-e-commerce
 * @since 3.7
 */

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
	if( isset($result) )
		if($echo)
			echo $result;
		else
			return $result;
}


/**
* wpsc_get_term_parents - get all parents of the term
*
* @param int $id - id of the term
* @return array of term objects or empty array if anything went wrong or there were no parrents
*/
function wpsc_get_term_parents( $term_id, $taxonomy ) {
	$term = &get_term( $term_id, $taxonomy );

	if(empty($term->parent))
		return array();
	$parent = &get_term( $term->parent, $taxonomy );
	if ( is_wp_error( $parent ) )
		return array();

 	$parents = array( $parent->term_id );

	if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $parents ) ) {
		$parents = array_merge($parents, wpsc_get_term_parents( $parent->term_id, $taxonomy ));
	}

	return $parents;
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


		if($sub_categories != '') {
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


function wpsc_is_in_category() {
  global $wpdb, $wp_query;
  $is_in_category = false;
  if(isset($wp_query->query_vars['wpsc_product_category'] ) && !empty($wp_query->query_vars['wpsc_product_category'])) {
    $is_in_category = true;
  } else if(isset($_GET['wpsc_product_category']) && !empty($_GET['wpsc_product_category'])) {
    $is_in_category = true;
  }

  return $is_in_category;
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
 * @author John Beales ( johnbeales.com )
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
* wpsc_category_image function, Gets the category image or returns false
* @param integer category ID, can be 0
* @return string url to the category image
*/
function wpsc_category_image($category_id = null) {
	if($category_id < 1)
		$category_id = wpsc_category_id();
	$category_image = wpsc_get_categorymeta($category_id, 'image');
	$category_path = WPSC_CATEGORY_DIR.basename($category_image);
	$category_url = WPSC_CATEGORY_URL.basename($category_image);
	if(file_exists($category_path) && is_file($category_path))
		return $category_url;
	return false;
}


/**
* wpsc_category_description function, Gets the category description
* @param integer category ID, can be 0
* @return string category description
*/
function wpsc_category_description($category_id = null) {
  if($category_id < 1)
	$category_id = wpsc_category_id();
  $category = get_term_by('id', $category_id, 'wpsc_product_category');
  return  $category->description;
}

function wpsc_category_name($category_id = null) {
	if($category_id < 1)
		$category_id = wpsc_category_id();
	$category = get_term_by('id', $category_id, 'wpsc_product_category');
	return $category->name;
}

function nzshpcrt_display_categories_groups() {
    global $wpdb;

    return $output;
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
 * wpsc_get_terms_category_sort_filter
 *
 * This sorts the categories when a call to get_terms is made
 * @param object array $terms
 * @param array $taxonomies
 * @param array $args
 * @return object array $terms
 */
function wpsc_get_terms_category_sort_filter($terms){
	$new_terms = array();
	$unsorted = array();

	foreach ( $terms as $term ) {
		if ( ! is_object( $term ) )
			return $terms;

		$term_order = ( $term->taxonomy == 'wpsc_product_category' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_category' ) : null;
		$term_order = (int) $term_order;

		// unsorted categories should go to the top of the list
		if ( $term_order == 0 ) {
			$term->sort_order = $term_order;
			$unsorted[] = $term;
			continue;
		}

		while ( isset( $new_terms[$term_order] ) ) {
			$term_order ++;
		}

		$term->sort_order = $term_order;
		$new_terms[$term_order] = $term;
	}

	if ( ! empty( $new_terms ) )
		ksort( $new_terms );

	for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
		array_unshift( $new_terms, $unsorted[$i] );
	}

	return array_values( $new_terms );
}
add_filter('get_terms','wpsc_get_terms_category_sort_filter');


function wpsc_get_terms_variation_sort_filter($terms){
	$new_terms = array();
	$unsorted = array();

	foreach ( $terms as $term ) {
		if ( ! is_object( $term ) )
			return $terms;

		$term_order = ( $term->taxonomy == 'wpsc-variation' ) ? wpsc_get_meta( $term->term_id, 'sort_order', 'wpsc_variation' ) : null;
		$term_order = (int) $term_order;

		// unsorted categories should go to the top of the list
		if ( $term_order == 0 ) {
			$term->sort_order = $term_order;
			$unsorted[] = $term;
			continue;
		}

		while ( isset( $new_terms[$term_order] ) ) {
			$term_order ++;
		}

		$term->sort_order = $term_order;
		$new_terms[$term_order] = $term;
	}

	if ( ! empty( $new_terms ) )
		ksort( $new_terms );

	for ( $i = count( $unsorted ) - 1; $i >= 0; $i-- ) {
		array_unshift( $new_terms, $unsorted[$i] );
	}

	return array_values( $new_terms );
}
add_filter('get_terms','wpsc_get_terms_variation_sort_filter');

/**
 * Abstracts Suhosin check into a function.  Used primarily in relation to target markets.
 * May be deprecated or never publicly launched if we change how the target market variables work.
 *
 * @since 3.8.9
 * @return boolean
 */
function wpsc_is_suhosin_enabled() {
	return @ extension_loaded( 'suhosin' ) && @ ini_get( 'suhosin.post.max_vars' ) > 0 && @ ini_get( 'suhosin.post.max_vars' ) < 500;
}

?>
