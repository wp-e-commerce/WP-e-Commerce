<?php

function wpsc_locate_asset( $file ) {
	$engine = WPSC_Template_Engine::get_instance();

	return _wpsc_locate_stuff( $engine->get_asset_paths(), $file, false, false );
}

function wpsc_locate_asset_uri( $file ) {
	$path = wpsc_locate_asset( $file );

	if ( strpos( $path, wp_normalize_path( WP_CONTENT_DIR ) ) !== false ) {
		return content_url( substr( $path, strlen( wp_normalize_path( WP_CONTENT_DIR ) ) ) );
	} elseif ( strpos( $path, wp_normalize_path( WP_PLUGIN_DIR ) !== false ) ) {
		return plugins_url( substr( $path, strlen( wp_normalize_path( WP_PLUGIN_DIR ) ) ) );
	} elseif ( strpos( $path, wp_normalize_path( WPMU_PLUGIN_DIR ) !== false ) ) {
		return plugins_url( substr( $path, strlen( wp_normalize_path( WP_PLUGIN_DIR ) ) ) );
	} elseif ( strpos( $path, wp_normalize_path( ABSPATH ) !== false ) ) {
		return get_site_url( null, substr( $path, strlen( wp_normalize_path( ABSPATH ) ) ) );
	}

	return '';
}

function _wpsc_locate_stuff( $paths, $files, $load = false, $require_once = true ) {
	$located = '';

	foreach ( (array) $files as $file ) {
		if ( ! $file ) {
			continue;
		}

		foreach ( $paths as $path ) {
			if ( file_exists( $path . '/' . $file ) ) {
				$located = wp_normalize_path( $path . '/' . $file );
				break 2;
			}
		}
	}

	if ( $load && '' != $located ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * See {@link wpsc_locate_theme_file()} for more information about how this works.
 *
 * @see   _wpsc_locate_stuff()
 * @since 4.0
 * @uses  load_template()
 * @uses  _wpsc_locate_stuff()
 *
 * @param  string|array $template_names Template files to search for, in order
 * @param  bool         $load           If true the template will be loaded if found
 * @param  bool         $require_once   Whether to use require_once or require. Default true. No effect if $load is false
 * @return string                       The template file name is located
 */
function wpsc_locate_template_part( $files, $load = false, $require_once = true ) {
	$engine = WPSC_Template_Engine::get_instance();

	return _wpsc_locate_stuff( $engine->get_template_part_paths(), $files, $load, $require_once );
}

function wpsc_locate_view_wrappers( $files, $load = false, $require_once = true ) {
	$engine = WPSC_Template_Engine::get_instance();

	return _wpsc_locate_stuff( $engine->get_view_wrapper_paths(), $files, $load, $require_once );
}

/**
 * This works just like get_template_part(), except that it uses wpsc_locate_template_path()
 * to search for the template part in 2 extra WP eCommerce specific paths.
 *
 * @since 4.0
 * @see   get_template()
 * @see   wpsc_locate_theme_file()
 * @uses  apply_filters() Applies 'wpsc_get_template_part_paths_for_{$slug}' filter.
 * @uses  do_action()     Calls   'wpsc_get_template_part_{$slug}'           action.
 * @uses  do_action()     Calls   'wpsc_template_before_{$slug}-{$name}'     action.
 * @uses  do_action()     Calls   'wpsc_template_after_{$slug}-{$name}'      action.
 * @uses  wpsc_locate_template_path()
 *
 * @param  string $slug The slug name for the generic template.
 * @param  string $name The name of the specialised template. Optional. Default null.
 */
function wpsc_get_template_part( $slug = false, $name = null ) {

	if ( ! $slug ) {
		$controller = _wpsc_get_current_controller();
		$slug       = $controller->view;
	}

	do_action( "wpsc_get_template_part_{$slug}", $slug, $name );

	$templates = array();

	if ( isset( $name ) ) {
		$templates[] =  "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	$templates = apply_filters( "wpsc_get_template_part_paths_for_{$slug}", $templates, $slug, $name );

	do_action( trim( "wpsc_template_before_{$slug}-{$name}", '-' ) );
	wpsc_locate_template_part( $templates, true, false );
	do_action( trim( "wpsc_template_after_{$slug}-{$name}", '-' ) );
}

/**
 * WPEC provides a way to separate all WPEC-related theme functions into a file called 'wpsc-functions.php'.
 * By providing a file named 'wpsc-functions.php', you can override the same function file of the parent
 * theme or that of the default theme engine that comes with WPEC.
 *
 * @since 4.0
 * @uses  get_stylesheet()
 * @uses  get_template()
 * @uses  get_theme_root()
 */
function _wpsc_action_after_setup_theme() {
	$current_theme = get_stylesheet();
	$parent_theme  = get_template();

	$paths = array(
		STYLESHEETPATH . '/wp-e-commerce',
	);

	if ( $current_theme != $parent_theme ) {
		$paths[] = TEMPLATEPATH . '/wp-e-commerce';
	}

	foreach ( $paths as $path ) {
		$filename = $path . '/functions.php';
		if ( file_exists( $filename ) ) {
			require_once( $filename );
		}
	}
}

add_action( 'after_setup_theme', '_wpsc_action_after_setup_theme' );

/**
 * Determine whether pagination is enabled for a certain position of the page.
 *
 * @since 4.0
 * @uses get_option() Gets 'use_pagination' option.
 * @uses wpsc_get_option() Gets WPEC 'page_number_postion' option.
 *
 * @param  string $position 'bottom', 'top', or 'both'
 * @return bool
 */
function wpsc_is_pagination_enabled( $position = 'bottom' ) {
	$pagination_enabled = wpsc_get_option( 'display_pagination' );

	if ( ! $pagination_enabled ) {
		return false;
	}

	$pagination_position = wpsc_get_option( 'page_number_position' );

	if ( $pagination_position == WPSC_PAGE_NUMBER_POSITION_BOTH ) {
		return true;
	}

	$id = WPSC_PAGE_NUMBER_POSITION_BOTTOM;

	if ( $position == 'top' ) {
		$id = WPSC_PAGE_NUMBER_POSITION_TOP;
	}

	return ( $pagination_position == $id );
}

/**
 * Override the per page parameter to use WPEC own "products per page" option.
 *
 * @since 4.0
 * @uses  WP_Query::is_main_query()
 * @uses  wpsc_get_option()            Gets WPEC 'products_per_page' option.
 * @uses  wpsc_is_pagination_enabled()
 * @uses  wpsc_is_store()
 * @uses  wpsc_is_product_category()
 * @uses  wpsc_is_product_tag()
 *
 * @param  object $query
 */
function wpsc_action_set_product_per_page_query_var( $query ) {
	if ( is_single() ) {
		return;
	}

	if ( wpsc_is_pagination_enabled() && $query->is_main_query() && ( wpsc_is_store() || wpsc_is_product_category() || wpsc_is_product_tag() ) ) {
		$query->set( 'posts_per_archive_page', wpsc_get_option( 'products_per_page' ) );
	}
}

add_action( 'pre_get_posts', 'wpsc_action_set_product_per_page_query_var', 10, 1 );

/**
 * Hook into 'post_class' filter to add custom classes to the current product in the loop.
 *
 * @since 4.0
 * @uses apply_filters() Applies 'wpsc_product_class' filter
 * @uses get_post() Gets the current post object
 * @uses wpsc_is_product_on_sale() Checks to see whether the current product is on sale
 * @uses $wpsc_query Global WPEC query object
 *
 * @param  array  $classes
 * @param  string $class
 * @param  int    $post_id
 * @return array  The filtered class array
 */
function wpsc_filter_product_class( $classes, $class, $post_id ) {

	if ( is_main_query() && ! $post_id ) {
		return $classes;
	}

	$post = get_post( $post_id );

	if ( $post->post_type == 'wpsc-product' ) {
		global $wp_query;

		$count     = isset( $wp_query->current_post ) ? (int) $wp_query->current_post : 1;
		$classes[] = $count % 2 ? 'even' : 'odd';

		if ( wpsc_is_product_on_sale( $post_id ) ) {
			$classes[] = 'wpsc-product-on-sale';
		}

		return apply_filters( 'wpsc_product_class', $classes, $class, $post_id );
	}

	return $classes;
}

add_filter( 'post_class', 'wpsc_filter_product_class', 10, 3 );

/**
 * Return the canonical permalink of a product.
 *
 * This function is usually used inside a hook action.
 *
 * @since 4.0
 * @uses  _wpsc_filter_product_permalink()
 *
 * @param  string $permalink
 * @param  object $post
 * @param  bool   $leavename
 * @param  bool   $sample
 * @return string
 */
function wpsc_filter_product_permalink_canonical( $permalink, $post, $leavename, $sample ) {
	return _wpsc_filter_product_permalink( $permalink, $post, $leavename, $sample, true );
}

/**
 * When hierarchical category url is enabled and wpsc_filter_product_permalink is attached to
 * 'post_type_link' filter hook, this function will make sure the resulting permalink scheme won't
 * return 404 errors.
 *
 * @since 4.0
 *
 * @param  array $q Query variable array
 * @return array
 */
function wpsc_filter_hierarchical_category_request( $q ) {
	if ( empty( $q['wpsc-product'] ) ) {
		return $q;
	}

	// break down the 'wpsc-product' query var to get the current and parent node
	$components = explode( '/', $q['wpsc-product'] );

	if ( count( $components ) == 1 ) {
		return $q;
	}

	$end_node    = array_pop( $components );
	$parent_node = array_pop( $components );

	// check to see if a post with the slug exists
	// if it doesn't then we're viewing a product category
	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'name'      => $end_node,
	) );

	if ( ! empty( $posts ) ) {
		$q['wpsc-product']          = $q['name'] = $end_node;
		$q['wpsc_product_category'] = $parent_node;
	} else {
		$q['wpsc_product_category'] = $end_node;
		unset( $q['name'        ] );
		unset( $q['wpsc-product'] );
		unset( $q['post_type'   ] );
	}
	return $q;
}

if ( wpsc_get_option( 'hierarchical_product_category_url' ) ) {
	add_filter( 'request', 'wpsc_filter_hierarchical_category_request' );
}

/**
 * Make sure the canonical URL of a single product page is correct.
 *
 * When wpsc_filter_product_permalink() is attached to 'post_type_link', the side effect is that
 * canonical URL is not canonical any more because 'wpsc_product_category' query var is taken into
 * account.
 *
 * This function temporarily removes the original wpsc_filter_product_permalink() function from 'post_type_link'
 * hook, and replaces it with wpsc_filter_product_permalink_canonical().
 *
 * @since 4.0
 * @uses  add_filter() Restores wpsc_filter_product_permalink() to 'post_type_link' filter.
 * @uses  add_filter() Temporarily attaches wpsc_filter_product_permalink_canonical() to 'post_type_link' filter.
 * @uses  remove_filter() Removes wpsc_filter_product_permalink_canonical() from 'post_type_link' filter.
 * @uses  remove_filter() Temporarily removes wpsc_filter_product_permalink() from 'post_type_link' filter.
 */
function wpsc_action_rel_canonical() {
	remove_filter( 'post_type_link' , 'wpsc_filter_product_permalink'          , 10, 4 );
	add_filter   ( 'post_type_link' , 'wpsc_filter_product_permalink_canonical', 10, 4 );
	rel_canonical();
	remove_filter( 'post_type_link' , 'wpsc_filter_product_permalink_canonical', 10, 4 );
	add_filter   ( 'post_type_link' , 'wpsc_filter_product_permalink'          , 10, 4 );
}

/**
 * Make sure we fix the canonical URL of the single product. The canonical URL is broken when
 * single product permalink is prefixed by product category.
 *
 * @since 4.0
 * @uses  add_action()    Adds wpsc_action_rel_canonical() to 'wp_head' action hook.
 * @uses  is_singular()
 * @uses  remove_action() Removes rel_canonical() from 'wp_head' action hook.
 */
function _wpsc_action_canonical_url() {
	if ( is_singular( 'wpsc-product' ) ) {
		remove_action( 'wp_head', 'rel_canonical'             );
		add_action   ( 'wp_head', 'wpsc_action_rel_canonical' );
	}
}
add_action( 'wp', '_wpsc_action_canonical_url' );

/**
 * In case the display mode is set to "Show list of product categories", this function is hooked into
 * the filter inside wpsc_get_template_part() and returns paths to category list template instead of
 * the usual one.
 *
 * @since 4.0
 *
 * @param  array  $templates
 * @param  string $slug
 * @param  string $name
 * @return array
 */
function wpsc_get_category_list_template_paths( $templates, $slug, $name ) {
	$templates = array(
		'wp-e-commerce/archive-category-list.php',
		'wp-e-commerce/archive.php',
	);
	return $templates;
}

function _wpsc_filter_body_class( $classes ) {
	if ( ! wpsc_is_controller() ) {
		return $classes;
	}

	$classes[] = 'wpsc-controller';
	$classes[] = 'wpsc-' . _wpsc_get_current_controller_name();
	$classes[] = 'wpsc-controller-' . _wpsc_get_current_controller_slug();

	return $classes;
}

add_filter( 'body_class', '_wpsc_filter_body_class' );

/**
 * Filters the title tag in WordPress to reflect the controller title.
 *
 * @since  4.0
 *
 * @param  string $title    Page title.
 * @param  string $sep      Elements for separating the site title and page title.
 * @param  string $location Where the title shoud be in relation to the separator.
 *
 * @return string           Modified page title.
 */
function _wpsc_filter_wp_title( $title, $sep = '&raquo;', $location = 'right' ) {

	if ( wpsc_is_controller() ) {

		$controller = _wpsc_get_current_controller();

		if ( empty( $title ) ) {
			$title = $controller->title;
		}

		$parts  = explode( $sep, $title );
		$prefix = " $sep ";

		if ( 'right' == $location ) { // sep on right, so reverse the order
			$parts = array_reverse( $parts );
			$title = ltrim( implode( " $sep ", $parts ) . $prefix, $prefix );
		} else {
			$title = rtrim( $prefix . implode( " $sep ", $parts ), $prefix );
		}
	}

	return $title;
}

add_filter( 'wp_title', '_wpsc_filter_wp_title', 10, 3 );

function _wpsc_filter_title( $title ) {
	if ( wpsc_is_controller() ) {
		$controller = _wpsc_get_current_controller();
		if (   wpsc_is_store()
			 || get_post_type() == 'page'
		)
			return $controller->title;
	}

	return $title;
}

add_filter( 'post_type_archive_title', '_wpsc_filter_title', 1 );
add_filter( 'single_post_title', '_wpsc_filter_title', 1 );

add_action( 'update_option_users_can_register', '_wpsc_action_flush_rewrite_rules' );

function _wpsc_action_remove_post_type_thumbnail_support() {
	remove_post_type_support( 'post', 'thumbnail' );
	remove_post_type_support( 'page', 'thumbnail' );
}

/**
 * Keep track of generated sizes for a particular thumbnail.
 *
 * This is important because by keeping track of the size settings used when generating
 * a thumbnail, we can lazily re-genarate thumbnails whenever these settings are
 * changed (see {@link wpsc_get_product_thumbnail()}).
 *
 * Filter hook: wpsc_generate_attachment_metadata
 *
 * @since  0.1
 * @access private
 * @see  wpsc_get_product_thumbnail()
 * @uses update_post_meta() Save the generated sizes into 'wpsc_generated_size'
 *
 * @param  array $metadata Thumbnail metadata
 * @param  int   $id       Attachment ID
 * @return array
 */
function _wpsc_filter_generate_attachment_metadata( $metadata, $id ) {
	global $_wp_additional_image_sizes;

	// Built-in sizes supported by WPEC
	$sizes = array(
		'cart',
		'taxonomy',
		'archive',
		'single',
	);

	$meta = array();

	// generate an array containing width, height, crop arguments for sizes
	// generated for this particular attachment
	foreach ( $sizes as $size ) {
		$key = "wpsc_product_{$size}_thumbnail";

		// if this size is not generated for this attachment, skip it
		if ( ! array_key_exists( $key, $metadata['sizes'] ) ) {
			continue;
		}

		// save the generated size settings for this image
		$meta[ $size ] = $_wp_additional_image_sizes[ $key ];
	}

	// store the copy in a meta so that later we can pull it out and compare
	update_post_meta( $id, '_wpsc_generated_sizes', $meta );

	return $metadata;
}

add_filter(
	'wp_generate_attachment_metadata',
	'_wpsc_filter_generate_attachment_metadata',
	10,
	2
);