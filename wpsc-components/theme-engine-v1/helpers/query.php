<?php
// switch $wp_query and $wpsc_query at the beginning and the end of wp_nav_menu()
add_action( 'pre_get_posts', '_wpsc_pre_get_posts_reset_taxonomy_globals', 1 );
add_action( 'template_redirect', 'wpsc_start_the_query', 8 );
add_action( 'wp', 'wpsc_force_ssl' );

if ( is_ssl() ) {
	add_filter( 'option_product_list_url',  'set_url_scheme' );
	add_filter( 'option_shopping_cart_url', 'set_url_scheme' );
	add_filter( 'option_transact_url',      'set_url_scheme' );
	add_filter( 'option_user_account_url',  'set_url_scheme' );
}

add_filter( 'wp_nav_menu_args', 'wpsc_switch_the_query', 99 );
add_filter( 'request', 'wpsc_filter_query_request' );
add_filter( 'pre_get_posts', 'wpsc_split_the_query', 8 );
add_filter( 'parse_query', 'wpsc_mark_product_query', 12 );
add_filter( 'query_vars', 'wpsc_query_vars' );

if ( get_option( 'product_category_hierarchical_url' ) ) {
	add_filter( 'request', 'wpsc_filter_request' );
}

/**
 * Fixes for some inconsistencies about $wp_query when viewing WPEC pages.
 *
 * Causes the following URLs to work (with pagination enabled):
 *
 * /products-page/ (product listing)
 * /products-page/car-audio/ (existing product category)
 * /products-page/car-audio/page/2/ (existing product category, page 2)
 * /products-page/page/2/  (product listing, page 2)
 * /products-page/checkout/  (existing built-in sub page)
 * /products-page/anotherpage/  (another sub page that may exist)
 *
 * @param string $q Query String
 */
function wpsc_filter_query_request( $args ) {
	global $wpsc_page_titles;
	if ( is_admin() )
		return $args;

	$is_sub_page =    ! empty( $args['wpsc_product_category'] )
				   &&   'page' != $args['wpsc_product_category']
				   && ! term_exists( $args['wpsc_product_category'], 'wpsc_product_category' );

	// Make sure no 404 error is thrown for any sub pages of products-page
	if ( $is_sub_page ) {
		// Probably requesting a page that is a sub page of products page
		$pagename = "{$wpsc_page_titles['products']}/{$args['wpsc_product_category']}";
		if ( isset($args['name']) ) {
			$pagename .= "/{$args['name']}";
		}
		$args = array();
		$args['pagename'] = $pagename;
	}

	// When product page is set to display all products or a category, and pagination is enabled, $wp_query is messed up
	// and is_home() is true. This fixes that.
	$needs_pagination_fix =      isset( $args['post_type'] )
							&& ! empty( $args['wpsc_product_category'] )
							&&   'wpsc-product' == $args['post_type']
							&& ! empty( $args['wpsc-product'] )
							&&   'page' == $args['wpsc_product_category'];
	if ( $needs_pagination_fix ) {
		$default_category = get_option( 'wpsc_default_category' );
		if ( $default_category == 'all' || $default_category != 'list' ) {
			$page = $args['wpsc-product'];
			$args = array();
			$args['pagename'] = "{$wpsc_page_titles['products']}";
			$args['page'] = $page;
		}
	}
	return $args;
}

function _wpsc_menu_exists( $args ) {
	$args = (object) $args;
	// Get the nav menu based on the requested menu
	$menu = wp_get_nav_menu_object( $args->menu );

	// Get the nav menu based on the theme_location
	if ( ! $menu && $args->theme_location && ( $locations = get_nav_menu_locations() ) && isset( $locations[ $args->theme_location ] ) )
		$menu = wp_get_nav_menu_object( $locations[ $args->theme_location ] );

	// get the first menu that has items if we still can't find a menu
	if ( ! $menu && ! $args->theme_location ) {
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu_maybe ) {
			if ( $menu_items = wp_get_nav_menu_items( $menu_maybe->term_id ) ) {
				$menu = $menu_maybe;
				break;
			}
		}
	}

	// If the menu exists, get its items.
	if ( $menu && ! is_wp_error( $menu ) && ! isset( $menu_items ) )
		$menu_items = wp_get_nav_menu_items( $menu->term_id );

	// If no menu was found or if the menu has no items and no location was requested, call the fallback_cb if it exists
	if ( ( ! $menu || is_wp_error( $menu ) || ( isset( $menu_items ) && empty( $menu_items ) && ! $args->theme_location ) ) )
		return false;

	// If no fallback function was specified and the menu doesn't exists, bail.
	if ( ! $menu || is_wp_error( $menu ) || empty( $menu_items ) )
		return false;

	return (bool) $menu;
}

function _wpsc_switch_the_query( $stuff = '' ) {
	global $wp_query, $wpsc_query;
	list( $wp_query, $wpsc_query ) = array( $wpsc_query, $wp_query );
	return $stuff;
}

/**
 * Switch $wp_query and $wpsc_query when outputting the navigation menu, but only if we're on a product
 * category page.
 *
 * We need to do this because the function _wp_menu_item_classes_by_context(), which generates classes
 * for menu items, depends on $wp_query. As a result, without this fix, when viewing a product category
 * page, the corresponding product category menu item will not be highlighted.
 *
 * Because there are no action hooks in wp_nav_menu(), we have to use two filters that are applied at
 * the beginning and the end of the function.
 *
 * @param mixed $stuff
 * @return mixed
 */
function wpsc_switch_the_query( $args ) {
	global $wp_query, $wpsc_query;
	$qv = $wpsc_query->query_vars;
	if ( ! empty( $qv['wpsc_product_category'] ) && ! empty( $qv['taxonomy'] ) && ! empty( $qv['term'] ) && ! is_single() && _wpsc_menu_exists( $args ) ) {
		_wpsc_switch_the_query();
		add_filter( 'wp_nav_menu', '_wpsc_switch_the_query', 99 );
	}
	return $args;
}

function _wpsc_pre_get_posts_reset_taxonomy_globals( $query ) {
	global $wp_the_query;

	if ( is_admin() || $query !== $wp_the_query )
		return;

	if ( ! $query->get( 'page' ) && ! $query->get( 'paged' ) )
		return;

	if ( ! get_option( 'use_pagination' ) )
		return;

	if ( ! is_page( wpsc_get_the_post_id_by_shortcode( '[productspage]' ) ) && ! $query->get( 'wpsc_product_category' ) )
		return;

	$query->set( 'posts_per_page', get_option( 'wpsc_products_per_page' ) );

	$post_type_object = get_post_type_object( 'wpsc-product' );

	if ( current_user_can( $post_type_object->cap->edit_posts ) )
		$query->set( 'post_status', apply_filters( 'wpsc_product_display_status', array( 'publish' ) ) );
	else
		$query->set( 'post_status', 'publish' );
}

/**
 * wpsc_start_the_query
 */
function wpsc_start_the_query() {
	global $wpsc_page_titles, $wp_query, $wpsc_query, $wpsc_query_vars;

	$is_404 = false;
	if ( null == $wpsc_query ) {
		if( ( $wp_query->is_404 && !empty($wp_query->query_vars['paged']) ) || (isset( $wp_query->query['pagename']) && strpos( $wp_query->query['pagename'] , $wpsc_page_titles['products'] ) !== false ) && !isset($wp_query->post)){
			global $post;
			$is_404 = true;
			if( !isset( $wp_query->query_vars['wpsc_product_category'] ) && ! isset( $wp_query->query_vars['product_tag'] ) )
				$wp_query = new WP_Query('post_type=wpsc-product&name='.$wp_query->query_vars['name']);

			if(isset($wp_query->post->ID))
				$post = $wp_query->post;
			else
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['name'];
		}
		if ( count( $wpsc_query_vars ) <= 1 ) {
			$wpsc_query_vars = array(
				'post_status' => apply_filters( 'wpsc_product_display_status', array( 'publish' ) ),
				'post_parent' => 0,
				'order'       => apply_filters( 'wpsc_product_order', get_option( 'wpsc_product_order', 'ASC' ) ),
				'post_type'   => apply_filters( 'wpsc_product_post_type', array( 'wpsc-product' ) ),
			);
			if($wp_query->query_vars['preview'])
				$wpsc_query_vars['post_status'] = 'any';

			if( isset( $_GET['product_order'] ) )
				$wpsc_query_vars['order'] = $_GET['product_order'];

			if(isset($wp_query->query_vars['product_tag'])){
				$wpsc_query_vars['product_tag'] = $wp_query->query_vars['product_tag'];
				$wpsc_query_vars['taxonomy'] = get_query_var( 'taxonomy' );
				$wpsc_query_vars['term'] = get_query_var( 'term' );
			}elseif( isset($wp_query->query_vars['wpsc_product_category']) ){
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
				$wpsc_query_vars['taxonomy'] = get_query_var( 'taxonomy' );
				$wpsc_query_vars['term'] = get_query_var( 'term' );
			}else{
				$wpsc_query_vars['post_type'] = 'wpsc-product';
				$wpsc_query_vars['pagename']  = wpsc_get_page_slug( '[productspage]' );
			}
			if(1 == get_option('use_pagination')){
				$wpsc_query_vars['nopaging'] = false;

				$wpsc_query_vars['posts_per_page'] = get_option('wpsc_products_per_page');

				$wpsc_query_vars['paged'] = get_query_var('paged');
				if(isset($wpsc_query_vars['paged']) && empty($wpsc_query_vars['paged'])){
					$wpsc_query_vars['paged'] = get_query_var('page');

				}

			}

			$orderby = ( isset( $_GET['product_order'] ) ) ? 'title' : null;
			$wpsc_query_vars = array_merge( $wpsc_query_vars, wpsc_product_sort_order_query_vars($orderby) );

			add_filter( 'pre_get_posts', 'wpsc_generate_product_query', 11 );

			$wpsc_query = new WP_Query( $wpsc_query_vars );

			//for 3.1 :|
			if(empty($wpsc_query->posts) && isset($wpsc_query->tax_query) && isset($wp_query->query_vars['wpsc_product_category'])){
				$wpsc_query_vars = array();
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
				if(1 == get_option('use_pagination')){
					$wpsc_query_vars['posts_per_page'] = get_option('wpsc_products_per_page');
					$wpsc_query_vars['paged'] = get_query_var('paged');
					if(empty($wpsc_query_vars['paged']))
						$wpsc_query_vars['paged'] = get_query_var('page');
				}
				$wpsc_query = new WP_Query( $wpsc_query_vars );

			}
		}
	}

	if(  $is_404 || ( ( isset($wpsc_query->post_count) && $wpsc_query->post_count == 0 ) && isset($wpsc_query_vars['wpsc_product_category'] )  )){

		$args = array_merge($wp_query->query, array('posts_per_page' => get_option('wpsc_products_per_page')));
		$wp_query = new WP_Query($args);

		if( empty( $wp_query->posts ) ){
			$product_page_id = wpsc_get_the_post_id_by_shortcode('[productspage]');
			$wp_query = new WP_Query( 'page_id='.$product_page_id);
		}
	}
	if ( isset( $wp_query->post->ID ) )
		$post_id = $wp_query->post->ID;
	else
		$post_id = 0;

	if ( get_permalink( $post_id ) == get_option( 'shopping_cart_url' ) )
		$_SESSION['wpsc_has_been_to_checkout'] = true;
}

/**
* Returns page slug that corresponds to a given WPEC-specific shortcode.
*
* @since 3.8.10
*
* @uses wpsc_get_the_post_id_by_shortcode() Gets page ID of shortcode.
* @uses get_post_field() Returns post name of page ID.
*
* @param string $shortcode Shortcode of WPEC-specific page, e.g. '[productspage]''
* @return string Post slug
*/
function wpsc_get_page_slug( $shortcode ) {
	$id = wpsc_get_the_post_id_by_shortcode( $shortcode );
	return get_post_field( 'post_name', $id );
}

/**
 * Obtain the necessary product sort order query variables based on the specified product sort order.
 * If no sort order is specified, the sort order configured in Dashboard -> Settings -> Store -> Presentation -> 'Sort Product By' is used.
 *
 * @param string $orderby optional product sort order
 * @return array Array of query variables
 */
function wpsc_product_sort_order_query_vars( $orderby = null ) {
	if ( is_null($orderby) )
		$orderby = get_option( 'wpsc_sort_by' );

	$query_vars = array();

	switch ( $orderby ) {

		case "dragndrop":
			$query_vars["orderby"] = 'menu_order';
			break;

		case "name":
			$query_vars["orderby"] = 'title';
			break;

		//This only works in WP 3.0.
		case "price":
			add_filter( 'posts_join', 'wpsc_add_meta_table' );
			add_filter( 'posts_where', 'wpsc_add_meta_table_where' );
			$query_vars["meta_key"] = '_wpsc_price';
			$query_vars["orderby"] = 'meta_value_num';
			break;

		case "id":
			$query_vars["orderby"] = 'ID';
			break;
		default:
			// Allow other WordPress 'ordery' values as defined in http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
			$query_vars["orderby"] = $orderby;
			break;
	}
	return $query_vars;
}

/**
 * wpsc_query_vars function.
 * adds in the post_type and wpsc_item query vars
 *
 * @since 3.8
 * @access public
 * @param mixed $vars
 * @return void
 */
function wpsc_query_vars( $vars ) {
	// post_type is used to specify that we are looking for products
	$vars[] = "post_type";
	// wpsc_item is used to find items that could be either a product or a product category, it defaults to category, then tries products
	$vars[] = "wpsc_item";
	return $vars;
}

/**
 * wpsc_query_modifier function.
 *
 * @since 3.8
 * @access public
 * @param object - reference to $wp_query
 * @return $query
 */
function wpsc_split_the_query( $query ) {
	global $wpsc_page_titles, $wpsc_query, $wpsc_query_vars;
	// These values are to be dynamically defined
	$products_page = $wpsc_page_titles['products'];
	$checkout_page = $wpsc_page_titles['checkout'];
	$userlog_page = $wpsc_page_titles['userlog'];
	$transaction_results_page = $wpsc_page_titles['transaction_results'];

	// otherwise, check if we are looking at a product, if so, duplicate the query and swap the old one out for a products page request
	// JS - 6.4.1020 - Added is_admin condition, as the products condition broke categories in backend
	if ( !empty($query->query_vars['pagename']) && ($query->query_vars['pagename'] == $products_page) || isset( $query->query_vars['products'] ) && !is_admin() ) {
		// store a copy of the wordpress query
		$wpsc_query_data = $query->query;

		// wipe and replace the query vars
		$query->query                   = array();
		$query->query['pagename']       = "$products_page";
		$query->query_vars['pagename']  = "$products_page";
		$query->query_vars['name']      = '';
		$query->query_vars['post_type'] = '';

		$query->queried_object = get_page_by_path( $query->query['pagename'] );

		if ( !empty( $query->queried_object ) )
			$query->queried_object_id = (int)$query->queried_object->ID;
		else
			unset( $query->queried_object );

		unset( $query->query_vars['products'] );
		unset( $query->query_vars['name'] );
		unset( $query->query_vars['taxonomy'] );
		unset( $query->query_vars['term'] );
		unset( $query->query_vars['wpsc_item'] );

		$query->is_singular = true;
		$query->is_page     = true;
		$query->is_tax      = false;
		$query->is_archive  = false;
		$query->is_single   = false;

		if ( ($wpsc_query_vars == null ) ) {
			unset( $wpsc_query_data['pagename'] );
			$wpsc_query_vars = $wpsc_query_data;
		}
	}

	add_filter( 'redirect_canonical', 'wpsc_break_canonical_redirects', 10, 2 );
	remove_filter( 'pre_get_posts', 'wpsc_split_the_query', 8 );
}

/**
 * wpsc_generate_product_query function.
 *
 * @access public
 * @param mixed $query
 * @return void
 */
function wpsc_generate_product_query( $query ) {
	global $wp_query;
	$prod_page = wpsc_get_the_post_id_by_shortcode('[productspage]');
	$prod_page = get_post($prod_page);
	remove_filter( 'pre_get_posts', 'wpsc_generate_product_query', 11 );
	$query->query_vars['taxonomy'] = null;
	$query->query_vars['term'] = null;

	// default product selection
	if ( $query->query_vars['pagename'] != '' ) {
		$query->query_vars['post_type'] = 'wpsc-product';
		$query->query_vars['pagename']  = '';
		$query->is_page     = false;
		$query->is_tax      = false;
		$query->is_archive  = true;
		$query->is_singular = false;
		$query->is_single   = false;
	}

	// If wpsc_item is not null, we are looking for a product or a product category, check for category
	if ( isset( $query->query_vars['wpsc_item'] ) && ($query->query_vars['wpsc_item'] != '') ) {
		$test_term = get_term_by( 'slug', $query->query_vars['wpsc_item'], 'wpsc_product_category' );
		if ( $test_term->slug == $query->query_vars['wpsc_item'] ) {
			// if category exists (slug matches slug), set products to value of wpsc_item
			$query->query_vars['products'] = $query->query_vars['wpsc_item'];
		} else {
			// otherwise set name to value of wpsc_item
			$query->query_vars['name'] = $query->query_vars['wpsc_item'];
		}
	}

	if ( isset( $query->query_vars['products'] ) && ($query->query_vars['products'] != null) && ($query->query_vars['name'] != null) ) {
		unset( $query->query_vars['taxonomy'] );
		unset( $query->query_vars['term'] );
		$query->query_vars['post_type'] = 'wpsc-product';
		$query->is_tax      = false;
		$query->is_archive  = true;
		$query->is_singular = false;
		$query->is_single   = false;
	}
	if( isset($wp_query->query_vars['wpsc_product_category']) && !isset($wp_query->query_vars['wpsc-product'])){
		$query->query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
		$query->query_vars['taxonomy'] = $wp_query->query_vars['taxonomy'];
		$query->query_vars['term'] = $wp_query->query_vars['term'];
	}elseif( '' != ($default_category = get_option('wpsc_default_category')) && !isset($wp_query->query_vars['wpsc-product'])){
		$default_term = get_term($default_category,'wpsc_product_category');
		if(!empty($default_term) && empty($wp_query->query_vars['category_name'])){
			$query->query_vars['taxonomy'] = 'wpsc_product_category';
			$query->query_vars['term'] = $default_term->slug;
			$query->is_tax = true;
		}elseif(isset($wp_query->query_vars['name']) && $wp_query->is_404 && $wp_query->query_vars['category_name'] != $prod_page->post_name){
			unset( $query->query_vars['taxonomy'] );
			unset( $query->query_vars['term'] );
			$query->query_vars['wpsc-product'] = $wp_query->query_vars['name'];
			$query->query_vars['name'] = $wp_query->query_vars['name'];

		}else{
			$query->is_tax = true;
			$term =	get_term_by('slug',$wp_query->query_vars['name'], 'wpsc_product_category' );
			if(!empty($term)){
				$query->query_vars['taxonomy'] = 'wpsc_product_category';
				$query->query_vars['wpsc_product_category__in'] = array($term->term_taxonomy_id);
				$query->query_vars['wpsc_product_category'] = $wp_query->query_vars['name'];
				$query->query_vars['term'] = $wp_query->query_vars['name'];
			}elseif(is_numeric($default_category)){
				$query->query_vars['taxonomy'] = 'wpsc_product_category';
			}else{
				$query->is_tax = false;
			}
		}
	}
	//If Product Tag Taxonomy
	if (isset($wp_query->query_vars['product_tag']) && $wp_query->query_vars['product_tag']){
		$query->query_vars['product_tag'] = $wp_query->query_vars['product_tag'];
		$query->query_vars['term'] = $wp_query->query_vars['term'];
		$query->query_vars['taxonomy'] = 'product_tag';
		$query->is_tax      = true;
	}
	if(1 == get_option('use_pagination')){
		$query->query_vars['posts_per_page'] = get_option('wpsc_products_per_page');
		if( isset( $_GET['items_per_page'] ) ) {
			if ( is_numeric( $_GET['items_per_page'] ) ) {
				$query->query_vars['posts_per_page'] = (int) $_GET['items_per_page'];
			} elseif ( $_GET['items_per_page'] == 'all' ) {
				$query->query_vars['posts_per_page'] = -1;
				$query->query_vars['nopaging'] = 1;
			}
		}
	} else {
		$query->query_vars['posts_per_page'] = -1;
		$query->query_vars['nopaging'] = 1;
	}
	if ( $query->is_tax == true )
		new wpsc_products_by_category( $query );
	return $query;
}

function wpsc_mark_product_query( $query ) {

	if ( isset( $query->query_vars['post_type'] ) && ($query->query_vars['post_type'] == 'wpsc-product') )
		$query->is_product = true;

	return $query;
}

/**
 * add meta table where section for ordering by price
 *
 */
function wpsc_add_meta_table_where($where){
	global $wpdb;

	remove_filter( 'posts_where', 'wpsc_add_meta_table_where' );

	return $where . ' AND ' . $wpdb->postmeta . '.meta_key = "_wpsc_price"';
}

/**
 * add meta table join section for ordering by price
 *
 */
function wpsc_add_meta_table($join){
	global $wpdb;
	remove_filter( 'posts_join', 'wpsc_add_meta_table' );
	if(strpos($join, "INNER JOIN ON (".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id)") !== false){
		return  ' JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts. '.ID = ' . $wpdb->postmeta . '.post_id';
	}else{
		return $join;
	}
}

function wpsc_break_canonical_redirects( $redirect_url, $requested_url ) {
	global $wp_query;

	if ( ( isset( $wp_query->query_vars['products'] ) && ($wp_query->query_vars['products'] != '') ) || ( isset( $wp_query->query_vars['products'] ) && $wp_query->query_vars['products'] != 'wpsc_item') )
		return false;

	if ( stristr( $requested_url, $redirect_url ) )
		return false;

	return $redirect_url;
}

function wpsc_filter_request( $q ) {
	if ( empty( $q['wpsc-product'] ) )
		return $q;

	$components = explode( '/', $q['wpsc-product'] );
	$end_node = array_pop( $components );
	$parent_node = array_pop( $components );

	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'name' => $end_node,
	) );

	if ( ! empty( $posts ) ) {
		$q['wpsc-product'] = $q['name'] = $end_node;
		if ( !empty( $parent_node ) )
			$q['wpsc_product_category'] = $parent_node;
	} else {
		$q['wpsc_product_category'] = $end_node;
		unset( $q['name'] );
		unset( $q['wpsc-product'] );
	}
	return $q;
}

/**
 * if the user is on a checkout page, force SSL if that option is so set
 */
function wpsc_force_ssl() {
	global $wp_query;
	if ( '1' == get_option( 'wpsc_force_ssl' ) &&
		! is_ssl() &&
		! empty ( $wp_query->post->post_content ) &&
		false !== strpos( $wp_query->post->post_content, '[shoppingcart]' ) ) {
		$sslurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		wp_redirect( $sslurl );
		exit;
	}
}

/**
 * wpsc_add_https_to_page_url_options( $url )
 *
 * Forces SSL onto option URLs
 *
 * @param string $url
 * @deprecated 3.8.14
 * @return string
 */
function wpsc_add_https_to_page_url_options( $url ) {
	return str_replace( 'http://', 'https://', $url );
}

/**
 * Checks if current page is shopping cart, and it should be SSL, but is not.
 * Used primarily for str_replacing links or content for https
 *
 * @since 3.8.8.1
 * @deprecated 3.8.8.2
 * @return boolean true if we're on the shopping cart page and should be ssl, false if not
 */
function wpsc_is_ssl() {
	global $wp_query;

	return '1' == get_option( 'wpsc_force_ssl' ) && ! is_ssl() && false !== strpos( $wp_query->post->post_content, '[shoppingcart]' );
}

