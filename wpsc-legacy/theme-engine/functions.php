<?php
/**
 * This file contains functions used in 3.8 theme engine. These functions previously
 * resided in wpsc-functions.php.
 */

/**
 * wpsc_core_load_page_titles()
 *
 * Load the WPEC page titles
 *
 * @global array $wpsc_page_titles
 */
function wpsc_core_load_page_titles() {
	global $wpsc_page_titles;
	$wpsc_page_titles = wpsc_get_page_post_names();
}

/**
 * wpsc_get_page_post_names function.
 * Seems that using just one SQL query and then processing the results is probably going to be around as efficient as just doing three separate queries
 * But using three queries is a hell of a lot simpler to write and easier to read.
 * @since 3.8
 * @access public
 * @return void
 */
function wpsc_get_page_post_names() {
	global $wpdb;
	$wpsc_page['products']            = $wpdb->get_var( "SELECT post_name FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%[productspage]%'  AND `post_type` = 'page' LIMIT 1" );
	$wpsc_page['checkout']            = $wpdb->get_var( "SELECT post_name FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%[shoppingcart]%'  AND `post_type` = 'page' LIMIT 1" );
	$wpsc_page['transaction_results'] = $wpdb->get_var( "SELECT post_name FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%[transactionresults]%'  AND `post_type` = 'page' LIMIT 1" );
	$wpsc_page['userlog']             = $wpdb->get_var( "SELECT post_name FROM `" . $wpdb->posts . "` WHERE `post_content` LIKE '%[userlog]%'  AND `post_type` = 'page' LIMIT 1" );
	return $wpsc_page;
}

/**
 * wpsc_template_loader function.
 *
 * @since 3.8
 * @access public
 * @return void
 */
function wpsc_template_loader() {
	global $wp_query;

	if ( wpsc_is_product() && $template = wpsc_get_template( 'products' ) ) {
		include( $template );
		exit();
	}

	if ( wpsc_is_checkout() && $template = wpsc_get_template( 'checkout' ) ) {
		include( $template );
		exit();
	}
}

/**
 * select_wpsc_theme_functions function, provides a place to override the e-commece theme path
 * add to switch "theme's functions file
 * © with xiligroup dev
 */
function wpsc_select_theme_functions() {
	$selected_theme = get_option( 'wpsc_selected_theme' );
	if ( !empty( $selected_theme ) && file_exists( WPSC_CORE_THEME_PATH . '/' . WPSC_THEME_DIR . '.php' ) )
		include_once( WPSC_CORE_THEME_PATH . '/' . WPSC_THEME_DIR . '.php' );
}
add_action( 'wp', 'wpsc_select_theme_functions', 10, 1 );

function wpsc_filter_request( $q ) {
	if ( empty( $q['wpsc-product'] ) )
		return $q;

	$components = explode( '/', $q['wpsc-product'] );
	if ( count( $components ) == 1 )
		return $q;
	$end_node = array_pop( $components );
	$parent_node = array_pop( $components );

	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'name' => $end_node,
	) );

	if ( ! empty( $posts ) ) {
		$q['wpsc-product'] = $q['name'] = $end_node;
		$q['wpsc_product_category'] = $parent_node;
	} else {
		$q['wpsc_product_category'] = $end_node;
		unset( $q['name'] );
		unset( $q['wpsc-product'] );
	}
	return $q;
}

if ( get_option( 'product_category_hierarchical_url' ) )
	add_filter( 'request', 'wpsc_filter_request' );

add_filter( 'query_string', 'wpsc_filter_query_string' );

/**
 * Fixes for some inconsistencies about $wp_query when viewing WPEC pages
 *
 * @param string $q Query String
 */
function wpsc_filter_query_string( $q ) {
	global $wpsc_page_titles;
	parse_str( $q, $args );

	// Make sure no 404 error is thrown for products-page's sub pages
	if ( ! empty( $args['wpsc_product_category'] ) && in_array( $args['wpsc_product_category'], $wpsc_page_titles ) ) {
		$q = "pagename={$wpsc_page_titles['products']}/{$args['wpsc_product_category']}";
	}

	// When product page is set to display all products or a category, and pagination is enabled, $wp_query is messed up
	// and is_home() is true. This fixes that.
	if ( ! is_admin() && isset( $args['post_type'] ) && $args['post_type'] == 'wpsc-product' && ! empty( $args['paged'] ) && empty( $args['wpsc_product_category'] ) ) {
		$default_category = get_option( 'wpsc_default_category' );
		if ( $default_category == 'all' || $default_category != 'list' )
			$q = "pagename={$wpsc_page_titles['products']}&page={$args['paged']}";
	}
	return $q;
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
function wpsc_switch_the_query( $stuff ) {
	global $wp_query, $wpsc_query;
	$qv = $wpsc_query->query_vars;
	if ( ! empty( $qv['wpsc_product_category'] ) && ! empty( $qv['taxonomy'] ) && ! empty( $qv['term'] ) && ! is_single() )
		list( $wp_query, $wpsc_query ) = array( $wpsc_query, $wp_query );
	return $stuff;
}

// switch $wp_query and $wpsc_query at the beginning and the end of wp_nav_menu()
add_filter( 'wp_nav_menu_args', 'wpsc_switch_the_query' );
add_filter( 'wp_nav_menu', 'wpsc_switch_the_query' );

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
			if( !isset( $wp_query->query_vars['wpsc_product_category'] ) )
				$wp_query = new WP_Query('post_type=wpsc-product&name='.$wp_query->query_vars['name']);

			if(isset($wp_query->post->ID))
				$post = $wp_query->post;
			else
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['name'];
		}
		if ( count( $wpsc_query_vars ) <= 1 ) {
			$wpsc_query_vars = array(
				'post_status' => 'publish, locked, private',
				'post_parent' => 0,
				'order'       => apply_filters('wpsc_product_order','ASC')
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
				$wpsc_query_vars['pagename'] = 'products-page';
			}
			if(1 == get_option('use_pagination')){
				$wpsc_query_vars['nopaging'] = false;

				$wpsc_query_vars['posts_per_page'] = get_option('wpsc_products_per_page');
				$wpsc_query_vars['paged'] = get_query_var('paged');
				if(isset($wpsc_query_vars['paged']) && empty($wpsc_query_vars['paged'])){
					$wpsc_query_vars['paged'] = get_query_var('page');

				}

			}
			$orderby = get_option( 'wpsc_sort_by' );
			if( isset( $_GET['product_order'] ) )
				$orderby = 'title';

			switch ( $orderby ) {

				case "dragndrop":
					$wpsc_query_vars["orderby"] = 'menu_order';
					break;

				case "name":
					$wpsc_query_vars["orderby"] = 'title';
					break;

				//This only works in WP 3.0.
				case "price":
					add_filter( 'posts_join', 'wpsc_add_meta_table' );
					add_filter( 'posts_where', 'wpsc_add_meta_table_where' );
					$wpsc_query_vars["meta_key"] = '_wpsc_price';
					$wpsc_query_vars["orderby"] = 'meta_value_num';
					break;

				case "id":
					$wpsc_query_vars["orderby"] = 'ID';
					break;
			}

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
			$product_page_id = wpec_get_the_post_id_by_shortcode('[productspage]');
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
add_action( 'template_redirect', 'wpsc_start_the_query', 8 );

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

	return $rebuilt_rewrite_rules;
}

add_filter( 'rewrite_rules_array', 'wpsc_taxonomy_rewrite_rules' );

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

add_filter( 'query_vars', 'wpsc_query_vars' );

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

		$query->queried_object = & get_page_by_path( $query->query['pagename'] );

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
	$prod_page = wpec_get_the_post_id_by_shortcode('[productspage]');
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
			if ( is_numeric( $_GET['items_per_page'] ) )
				$query->query_vars['posts_per_page'] = (int) $_GET['items_per_page'];
			elseif ( $_GET['items_per_page'] == 'all' )
				$query->query_vars['posts_per_page'] = -1;
		}
	} else {
		$query->query_vars['posts_per_page'] = '-1';
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
add_filter( 'pre_get_posts', 'wpsc_split_the_query', 8 );
add_filter( 'parse_query', 'wpsc_mark_product_query', 12 );

/**
 * wpsc_products_by_category class.
 *
 */
class wpsc_products_by_category {

	var $sql_components = array( );

	/**
	 * wpsc_products_by_category function.
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function wpsc_products_by_category( $query ) {
		global $wpdb;
		$q = $query->query_vars;


		// Category stuff for nice URLs
		if ( !empty( $q['wpsc_product_category'] ) && !$query->is_singular ) {
			$q['taxonomy'] = 'wpsc_product_category';
			$q['term'] = $q['wpsc_product_category'];
			$in_cats = '';
			$join = " INNER JOIN $wpdb->term_relationships
				ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
			INNER JOIN $wpdb->term_taxonomy
				ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
			";
			if(isset($q['meta_key']))
				$join .= " INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";

			$whichcat = " AND $wpdb->term_taxonomy.taxonomy = '{$q['taxonomy']}' ";

			$term_data = get_term_by( 'slug', $q['term'], $q['taxonomy'] );

			if( is_object( $term_data ) )
				$in_cats = array( $term_data->term_id );

			if('0' != get_option('show_subcatsprods_in_cat') && is_object($term_data)){
				$term_children_data = get_term_children( $term_data->term_id, $q['taxonomy'] );
				$in_cats = array_reverse( array_merge( $in_cats, $term_children_data ) );
			}
			if( is_array( $in_cats ) ){
				$in_cats = "'" . implode( "', '", $in_cats ) . "'";
				$whichcat .= "AND $wpdb->term_taxonomy.term_id IN ($in_cats)";
			}
			$whichcat .= " AND $wpdb->posts.post_status IN ('publish', 'locked', 'private') ";

			$groupby = "{$wpdb->posts}.ID";

			$this->sql_components['join']     = $join;
			$this->sql_components['fields']   = "{$wpdb->posts}.*, {$wpdb->term_taxonomy}.term_id";
			$this->sql_components['group_by'] = $groupby;

			//what about ordering by price
			if(isset($q['meta_key']) && '_wpsc_price' == $q['meta_key']){
				$whichcat .= " AND $wpdb->postmeta.meta_key = '_wpsc_price'";
			}else{

				$this->sql_components['order_by'] = "{$wpdb->term_taxonomy}.term_id";
			}
			$this->sql_components['where']    = $whichcat;
			add_filter( 'posts_join', array( &$this, 'join_sql' ) );
			add_filter( 'posts_where', array( &$this, 'where_sql' ) );
			add_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
			add_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
			add_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		}
	}

	function join_sql( $sql ) {
		if ( isset( $this->sql_components['join'] ) )
			$sql = $this->sql_components['join'];

		remove_filter( 'posts_join', array( &$this, 'join_sql' ) );
		return $sql;
	}

	function where_sql( $sql ) {
		if ( isset( $this->sql_components['where'] ) )
			$sql = $this->sql_components['where'];

		remove_filter( 'posts_where', array( &$this, 'where_sql' ) );
		return $sql;
	}

	function order_by_sql( $sql ) {
		$order_by_parts   = array( );
		$order_by_parts[] = $sql;

		if ( isset( $this->sql_components['order_by'] ) )
			$order_by_parts[] = $this->sql_components['order_by'];

		$order_by_parts = array_reverse( $order_by_parts );
		$sql = implode( ',', $order_by_parts );

		remove_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
		return $sql;
	}

	function fields_sql( $sql ) {
		if ( isset( $this->sql_components['fields'] ) )
			$sql = $this->sql_components['fields'];

		remove_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
		return $sql;
	}

	function group_by_sql( $sql ) {
		if ( isset( $this->sql_components['group_by'] ) )
			$sql = $this->sql_components['group_by'];

		remove_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		return $sql;
	}

	function request_sql( $sql ) {
		echo $sql . "<br />";
		remove_filter( 'posts_request', array( &$this, 'request_sql' ) );
		return $sql;
	}
}

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
	global $wp_query, $wpsc_page_titles;
	$term_url = '';
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
	if ($post->post_type != 'wpsc-product')
		return $permalink;

	$permalink_structure = get_option( 'permalink_structure' );
	// This may become customiseable later

	$our_permalink_structure = $wpsc_page_titles['products'] . "/%wpsc_product_category%/%postname%/";
	// Mostly the same conditions used for posts, but restricted to items with a post type of "wpsc-product "

	if ( '' != $permalink_structure && !in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
		$product_categories = wp_get_object_terms( $post_id, 'wpsc_product_category' );
		$product_category_slugs = array( );
		foreach ( $product_categories as $product_category ) {
			$product_category_slugs[] = $product_category->slug;
		}
		// If the product is associated with multiple categories, determine which one to pick
		if ( count( $product_categories ) == 0 ) {
			$category_slug = 'uncategorized';
		} elseif ( count( $product_categories ) > 1 ) {
			if ( (isset( $wp_query->query_vars['products'] ) && $wp_query->query_vars['products'] != null) && in_array( $wp_query->query_vars['products'], $product_category_slugs ) ) {
				$product_category = $wp_query->query_vars['products'];
			} else {
				if ( ( $current_cat = get_query_var( 'wpsc_product_category' ) ) && in_array( $current_cat, $product_category_slugs ) )
					$link = $current_cat;
				else
					$link = $product_categories[0]->slug;

				$product_category = $link;
			}
			$category_slug = $product_category;
			$term_url = get_term_link( $category_slug, 'wpsc_product_category' );
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

			$term_url = get_term_link( $category_slug, 'wpsc_product_category' );
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

		if(isset($category_slug) && empty($category_slug)) $category_slug = 'product';

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
add_filter( 'post_type_link', 'wpsc_product_link', 10, 3 );

/**
 * wpsc_get_product_template function.
 *
 * @since 3.8
 * @access public
 * @return void
 */
function wpsc_get_template( $template ) {
	return get_query_template( $template );
}

/**
 * wpsc_product_template_fallback function.
 *
 * @since 3.8
 * @access public
 * @param mixed $template_path
 * @return string - the corrected template path
 */
function wpsc_template_fallback( $template_path ) {

	$prospective_file_name = basename( "{$template_path}.php" );
	$prospective_file_path = trailingslashit( WPSC_CORE_THEME_PATH ) . $prospective_file_name;

	if ( !file_exists( $prospective_file_path ) )
		exit( $prospective_file_path );

	return $prospective_file_path;
}


function wpsc_products_template_fallback() {
	return wpsc_template_fallback( 'products' );
}

function wpsc_checkout_template_fallback() {
	return wpsc_template_fallback( 'checkout' );
}

function wpsc_product_rss() {
	global $wp_query, $wpsc_query;
	list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
	header( "Content-Type: application/xml; charset=UTF-8" );
	header( 'Content-Disposition: inline; filename="E-Commerce_Product_List.rss"' );
	require_once(WPSC_FILE_PATH . '/wpsc-includes/rss_template.php');
	list($wp_query, $wpsc_query) = array( $wpsc_query, $wp_query ); // swap the wpsc_query object
	exit();
}

if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == "rss") ) {
	add_action( 'template_redirect', 'wpsc_product_rss', 80 );
}

function wpsc_legacy_theme_load_css() {
	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
	wp_enqueue_style( 'wpsc-theme-css', wpsc_get_template_file_url( 'wpsc-' . get_option( 'wpsc_selected_theme' ) . '.css' ), false, $version_identifier, 'all' );
	wp_enqueue_style( 'wpsc-theme-css-compatibility', WPSC_CORE_THEME_URL . 'compatibility.css',                                    false, $version_identifier, 'all' );
}

add_action( 'init', 'wpsc_legacy_theme_load_css' );

/**
 * wpsc_refresh_page_urls( $content )
 *
 * Refresh page urls when permalinks are turned on or altered
 *
 * @global object $wpdb
 * @param string $content
 * @return string
 */
function wpsc_refresh_page_urls( $content ) {
	global $wpdb;

	$wpsc_pageurl_option['product_list_url'] = '[productspage]';
	$wpsc_pageurl_option['shopping_cart_url'] = '[shoppingcart]';
	$check_chekout = $wpdb->get_var( "SELECT `guid` FROM `{$wpdb->posts}` WHERE `post_content` LIKE '%[checkout]%' AND `post_type` NOT IN('revision') LIMIT 1" );

	if ( $check_chekout != null )
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';
	else
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';

	$wpsc_pageurl_option['transact_url'] = '[transactionresults]';
	$wpsc_pageurl_option['user_account_url'] = '[userlog]';
	$changes_made = false;
	foreach ( $wpsc_pageurl_option as $option_key => $page_string ) {
		$post_id = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` IN('page','post') AND `post_content` LIKE '%$page_string%' AND `post_type` NOT IN('revision') LIMIT 1" );
		$the_new_link = _get_page_link( $post_id );

		if ( stristr( get_option( $option_key ), "https://" ) )
			$the_new_link = str_replace( 'http://', "https://", $the_new_link );

		update_option( $option_key, $the_new_link );
	}
	return $content;
}

add_filter( 'mod_rewrite_rules', 'wpsc_refresh_page_urls' );