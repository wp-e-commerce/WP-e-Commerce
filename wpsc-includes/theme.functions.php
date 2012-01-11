<?php

define( 'WPSC_THEME_ENGINE_COMPAT_PATH', WPSC_FILE_PATH . '/wpsc-theme-engine' );
define( 'WPSC_THEME_ENGINE_COMPAT_URL' , WPSC_URL . '/wpsc-theme-engine');

/**
 * Locate the path to a certain WPEC theme file.
 *
 * In 4.0, we allow themes and child themes to override a default template, stylesheet, script or
 * image files by providing the same file structure inside the theme (or child theme) folder.
 *
 * This function searches for the file in multiple paths. It willlook for the template in the
 * following order:
 * - wp-content/themes/wpsc-theme-engine/{$current_theme_name}
 * - wp-content/themes/wpsc-theme-engine/{$parent_theme_name}
 * - wp-content/themes/wpsc-theme-engine
 * - current theme's path
 * - parent theme's path
 * - wp-content/plugins/wp-e-commerce/wpsc-theme-engine/{$current_theme_name}
 * - wp-content/plugins/wp-e-commerce/wpsc-theme-engine/{$parent_theme_name}
 * - wp-content/plugins/wp-e-commerce
 *
 * The purpose of the "wp-content/themes/wpsc-theme-engine" path is to provide a way for the users
 * to preserve their custom templates when the current theme is updated. This makes it much more
 * flexible for users who is already using a child theme or a third-party WP e-Commerce theme.
 *
 * Inside wp-content/plugins/wp-e-commerce/wpsc-theme-engine, we provide template files for
 * TwentyTen and TwentyEleven in two separate folders. All the relevant template parts are inside
 * wpsc-theme-engine/wp-e-commerce.
 *
 * For example, on a WordPress installation that uses TwentyTen as the main theme, the main catalog
 * template by default will be located in wp-content/plugins/wp-e-commerce/twentyeleven/archive-wpsc-product.php.
 * If you want to override this template in your TwentyTen theme, simply create an archive-wpsc-product.php
 * file in wp-content/themes/twentyeleven/ and that file will be used.
 *
 * You can essentially override any kind of files inside wp-content/plugins/wp-e-commerce/wpsc-theme-engine/{$theme_name}
 * by creating the same file structure in wp-content/themes/{$theme_name}.
 *
 * @since 4.0
 * @uses  get_stylesheet()
 * @uses  get_template()
 * @uses  get_theme_root()
 *
 * @param  array  $files The file names you want to look for
 * @return string        The path to the matched template file
 */
function wpsc_locate_theme_file( $files ) {
	$located = '';
	$theme_root = get_theme_root();
	$current_theme = get_stylesheet();
	$parent_theme = get_template();

	if ( $current_theme == $parent_theme ) {
		$paths = array(
			"{$theme_root}/wpsc-theme-engine/{$current_theme}",
			"{$theme_root}/wpsc-theme-engine/default",
			STYLESHEETPATH,
			WPSC_THEME_ENGINE_COMPAT_PATH . "/{$current_theme}",
			WPSC_THEME_ENGINE_COMPAT_PATH . "/default"
		);
	} else {
		$paths = array(
			"{$theme_root}/wpsc-theme-engine/{$current_theme}",
			"{$theme_root}/wpsc-theme-engine/{$parent_theme}",
			"{$theme_root}/wpsc-theme-engine/default",
			STYLESHEETPATH,
			TEMPLATEPATH,
			WPSC_THEME_ENGINE_COMPAT_PATH . "/{$current_theme}",
			WPSC_THEME_ENGINE_COMPAT_PATH . "/{$parent_theme}",
			WPSC_THEME_ENGINE_COMPAT_PATH . "/default"
		);
	}

	foreach ( (array) $files as $file ) {
		if ( ! $file )
			continue;

		foreach ( $paths as $path ) {
			if ( file_exists( $path . '/' . $file ) ) {
				$located = $path . '/' . $file;
				break 2;
			}
		}
	}

	return $located;
}

/**
 * Return the URI of a certain WPEC file inside our theme engine folder structure.
 *
 * See {@link wpsc_locate_theme_file()} for more information about how this works.
 *
 * @since 4.0
 * @uses  content_url()
 * @uses  get_site_url()
 * @uses  plugins_url()
 * @uses  wpsc_locate_theme_file()
 *
 * @param  array  $file Files to look for.
 * @return string       The URL of the matched file
 */
function wpsc_locate_theme_file_uri( $file ) {
	$path = wpsc_locate_theme_file( $file );
	if ( strpos( $path, WP_CONTENT_DIR ) !== false )
		return content_url( substr( $path, strlen( WP_CONTENT_DIR ) ) );
	elseif ( strpos( $path, WP_PLUGIN_DIR ) !== false )
		return plugins_url( substr( $path, strlen( WP_PLUGIN_DIR ) ) );
	elseif ( strpos( $path, WPMU_PLUGIN_DIR ) !== false )
		return plugins_url( substr( $path, strlen( WP_PLUGIN_DIR ) ) );
	elseif ( strpos( $path, ABSPATH ) !== false )
		return get_site_url( null, substr( $path, strlen( ABSPATH ) ) );

	return '';
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * See {@link wpsc_locate_theme_file()} for more information about how this works.
 *
 * @see   wpsc_locate_theme_file()
 * @since 4.0
 * @uses  load_template()
 * @uses  wpsc_locate_theme_file()
 *
 * @param  string|array $template_names Template files to search for, in order
 * @param  bool         $load           If true the template will be loaded if found
 * @param  bool         $require_once   Whether to use require_once or require. Default true. No effect if $load is false
 * @return string                       The template file name is located
 */
function wpsc_locate_template( $template_names, $load = false, $require_once = true ) {
	$located = wpsc_locate_theme_file( $template_names );

	if ( $load && '' != $located )
		load_template( $located, $require_once );

	return $located;
}

/**
 * This works just like get_template_part(), except that it uses wpsc_locate_template()
 * to search for the template part in 2 extra WP e-Commerce specific paths.
 *
 * @since 4.0
 * @see   get_template()
 * @see   wpsc_locate_theme_file()
 * @uses  apply_filters() Applies 'wpsc_get_template_part_paths_for_{$slug}' filter.
 * @uses  do_action()     Calls   'wpsc_get_template_part_{$slug}'           action.
 * @uses  do_action()     Calls   'wpsc_template_before_{$slug}-{$name}'     action.
 * @uses  do_action()     Calls   'wpsc_template_after_{$slug}-{$name}'      action.
 * @uses  wpsc_locate_template()
 *
 * @param  string $slug The slug name for the generic template.
 * @param  string $name The name of the specialised template. Optional. Default null.
 */
function wpsc_get_template_part( $slug, $name = null ) {
	do_action( "wpsc_get_template_part_{$slug}", $slug, $name );

	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = "wp-e-commerce/{$slug}-{$name}.php";
	}

	$templates[] = "wp-e-commerce/{$slug}.php";

	$templates = apply_filters( "wpsc_get_template_part_paths_for_{$slug}", $templates, $slug, $name );

	do_action( "wpsc_template_before_{$slug}-{$name}" );
	wpsc_locate_template( $templates, true, false );
	do_action( "wpsc_template_after_{$slug}-{$name}" );
}

/**
 * This function is hooked into 'archive_template' filter.
 *
 * It searches for archive-wpsc-product.php and archive.php using {@link wpsc_locate_template()}
 * instead of {@link locate_template()}, which means it looks for those templates in two additional
 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
 *
 * @since 4.0
 * @uses  get_post_type()
 * @uses  wpsc_locate_template()
 *
 * @param  string $template The template file that get_query_template() found
 * @return string           The template file located by WP e-Commerce
 */
function wpsc_filter_get_archive_template( $template ) {
	if ( is_post_type_archive( 'wpsc-product' ) ) {
		if ( $located = apply_filters( 'wpsc_get_archive_template', false ) )
			return $located;

		$templates = array(
			"archive-wpsc-product.php",
			'archive.php',
		);

		if ( $located = wpsc_locate_template( $templates ) )
			$template = $located;
	}

	return $template;
}
add_filter( 'archive_template', 'wpsc_filter_get_archive_template' );

/**
 * This function is hooked into 'taxonomy_template' filter.
 *
 * It searches for WPEC related taxonomy templates using {@link wpsc_locate_template()}
 * instead of {@link locate_template()}, which means it looks for those templates in two additional
 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
 *
 * @since 4.0
 * @uses  get_post_type()
 * @uses  wpsc_locate_template()
 *
 * @param  string $template The template file that get_query_template() found
 * @return string           The template file located by WP e-Commerce
 */
function wpsc_filter_get_taxonomy_template( $template ) {
	$term = get_queried_object();
	$taxonomy = $term->taxonomy;

	if ( in_array( $taxonomy, array( 'wpsc_product_category', 'product_tag' ) ) ) {
		if ( $located = apply_filters( 'wpsc_get_taxonomy_template', false ) )
			return $located;

		$templates = array(
			"taxonomy-$taxonomy-{$term->slug}.php",
			"taxonomy-$taxonomy.php",
			'taxonomy.php',
		);

		if ( $located = wpsc_locate_template( $templates ) )
			$template = $located;
	}

	return $template;
}
add_filter( 'taxonomy_template', 'wpsc_filter_get_taxonomy_template' );

/**
 * This function is hooked into 'single_template' filter.
 *
 * It searches for single-wpsc-product.php and single.php using {@link wpsc_locate_template()}
 * instead of {@link locate_template()}, which means it looks for those templates in two additional
 * paths that WP e-Commerce defines in {@link wpsc_locate_template()}.
 *
 * @since 4.0
 * @uses  get_post_type()
 * @uses  wpsc_locate_template()
 *
 * @param  string $template
 * @return string
 */
function wpsc_filter_get_single_template( $template ) {
	if ( get_post_type() == 'wpsc-product' ) {
		$templates = array(
			'single-wpsc-product.php',
			'single.php',
		);

		if ( $located = wpsc_locate_template( $templates ) )
			$template = $located;
	}

	return $template;
}
add_filter( 'single_template', 'wpsc_filter_get_single_template' );

/**
 * Create a separate $wpsc_query global object for convenience and consistency
 *
 * This function hooks into 'wp' action hook.
 *
 * @since 4.0
 * @uses  $wp_query
 */
function wpsc_set_query_object() {
	global $wp_query;
	if ( ! isset( $wp_query->wpsc_is_page ) )
		$wp_query->wpsc_is_page = false;

	if ( ! isset( $wp_query->wpsc_is_cart ) )
		$wp_query->wpsc_is_cart = false;

	$GLOBALS['wpsc_query'] =& $wp_query;
}
add_action( 'wp', 'wpsc_set_query_object', 1 );

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
function wpsc_action_before_setup_theme() {
	$theme_root = get_theme_root();
	$current_theme = get_stylesheet();
	$parent_theme = get_template();

	$paths = array(
		WPSC_THEME_ENGINE_COMPAT_PATH . "/default",
		WPSC_THEME_ENGINE_COMPAT_PATH . "/{$parent_theme}",
		WPSC_THEME_ENGINE_COMPAT_PATH . "/{$current_theme}",
	);

	foreach ( $paths as $path ) {
		$filename = $path . '/wpsc-functions.php';
		if ( file_exists( $filename ) ) {
			require_once( $filename );
		}
	}
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
function wpsc_action_after_setup_theme() {
	$theme_root = get_theme_root();
	$current_theme = get_stylesheet();
	$parent_theme = get_template();

	$paths = array(
		TEMPLATEPATH,
		STYLESHEETPATH,
		"{$theme_root}/wpsc-theme-engine/default",
		"{$theme_root}/wpsc-theme-engine/{$parent_theme}",
		"{$theme_root}/wpsc-theme-engine/{$current_theme}",
	);

	foreach ( $paths as $path ) {
		$filename = $path . '/wpsc-functions.php';
		if ( file_exists( $filename ) ) {
			require_once( $filename );
		}
	}
}
add_action( 'setup_theme', 'wpsc_action_before_setup_theme' );
add_action( 'after_setup_theme', 'wpsc_action_after_setup_theme' );

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

/**
 * Override the per page parameter to use WPEC own "products per page" option.
 *
 * @since 4.0
 * @uses  WP_Query::is_main_query()
 * @uses  wpsc_get_option()            Gets WPEC 'products_per_page' option.
 * @uses  wpsc_is_pagination_enabled()
 * @uses  wpsc_is_product_catalog()
 * @uses  wpsc_is_product_category()
 * @uses  wpsc_is_product_tag()
 *
 * @param  object $query
 */
function wpsc_action_set_product_per_page_query_var( $query ) {
	if ( is_single() )
		return;

	if ( wpsc_is_pagination_enabled() && $query->is_main_query() && ( wpsc_is_product_catalog() || wpsc_is_product_category() || wpsc_is_product_tag() ) )
		$query->query_vars['posts_per_archive_page'] = wpsc_get_option( 'products_per_page' );
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
	global $wpsc_query;

	$post = get_post( $post_id );
	if ( $post->post_type == 'wpsc-product' ) {
		$count     = isset( $wpsc_query->current_post ) ? (int) $wpsc_query->current_post : 1;
		$classes[] = $count % 2 ? 'even' : 'odd';
		if ( wpsc_is_product_on_sale( $post_id ) )
			$classes[] = 'wpsc-product-on-sale';

		return apply_filters( 'wpsc_product_class', $classes, $class, $post_id );
	}

	return $classes;
}
add_filter( 'post_class', 'wpsc_filter_product_class', 10, 3 );

/**
 * Properly replace permalink tags with product's name and product category.
 *
 * This function also takes into account two settings if $canonical is false: whether to prefix
 * product permalink with product category, and whether hierarchical product category URL is enabled.
 *
 * @access private
 * @since  4.0
 * @uses   apply_filters()        Applies 'wpsc_product_permalink_canonical' filter if $canonical is true.
 * @uses   apply_filters()        Applies 'wpsc_product_permalink' filter if $canonical is false.
 * @uses   get_option()           Gets 'permalink_structure' option.
 * @uses   get_query_var()        Gets the current "wpsc_product_category" context of the product.
 * @uses   get_term()             Gets the ancestor terms.
 * @uses   get_term_by()          Gets parent term so that we can recursively get the ancestors.
 * @uses   is_wp_error()
 * @uses   user_trailingslashit()
 * @uses   wp_get_object_terms()  Gets the product categories associated with the product.
 * @uses   wp_list_pluck()        Plucks only the "slug" of the categories array.
 * @uses   wpsc_get_option()      Gets 'hierarchical_product_category_url' option.
 *
 * @param  string $permalink
 * @param  object $post
 * @param  bool   $leavename
 * @param  bool   $sample
 * @param  bool   $canonical Whether to return a canonical URL or not
 * @return string
 */
function _wpsc_filter_product_permalink( $permalink, $post, $leavename, $sample, $canonical = false ) {
	// Define what to replace in the permalink
	$rewritecode = array(
		'%wpsc_product_category%',
		$leavename ? '' : '%wpsc-product%',
	);

	// only need to do this if a permalink structure is used
	$permalink_structure = get_option( 'permalink_structure' );
	if ( empty( $permalink_structure ) || $post->post_type != 'wpsc-product' || in_array( $post->post_status, array( 'draft', 'pending' ) ) )
		return $permalink;

	if ( strpos( $permalink, '%wpsc_product_category%' ) !== false ) {
		$category_slug = 'uncategorized';
		$categories    = wp_list_pluck( wp_get_object_terms( $post->ID, 'wpsc_product_category' ), 'slug' );

		// if there are multiple product categories, choose an appropriate one based on the current
		// product category being viewed
		if ( ! empty( $categories ) ) {
			$category_slug = $categories[0];
			$context       = get_query_var( 'wpsc_product_category' );
			if ( ! $canonical && $context && in_array( $context, $categories ) )
				$category_slug = $context;
		}

		// if hierarchical product category URL is enabled, we need to get the ancestors
		if ( ! $canonical && wpsc_get_option( 'hierarchical_product_category_url' ) ) {
			$term = get_term_by( 'slug', $category_slug, 'wpsc_product_category' );
			if ( is_object( $term ) ) {
				$ancestors = array( $category_slug );
				while ( $term->parent ) {
					$term = get_term( $term->parent, 'wpsc_product_category' );
					if ( in_array( $term->slug, $ancestors ) || is_wp_error( $term ) )
						break;
					$ancestors[] = $term->slug;
				}

				$category_slug = implode( '/', array_reverse( $ancestors ) );
			}
		}
	}

	$rewritereplace = array(
		$category_slug,
		$post->post_name,
	);

	$permalink = str_replace( $rewritecode, $rewritereplace, $permalink );
	$permalink = user_trailingslashit( $permalink, 'single' );

	if ( $canonical )
		return apply_filters( 'wpsc_product_permalink_canonical', $permalink, $post->ID );
	else
		return apply_filters( 'wpsc_product_permalink', $permalink, $post->ID );
}

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
 * Return the permalink of a product.
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
function wpsc_filter_product_permalink( $permalink, $post, $leavename, $sample ) {
	return _wpsc_filter_product_permalink( $permalink, $post, $leavename, $sample, false );
}
add_filter( 'post_type_link', 'wpsc_filter_product_permalink', 10, 4 );

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
	if ( empty( $q['wpsc-product'] ) )
		return $q;

	// break down the 'wpsc-product' query var to get the current and parent node
	$components = explode( '/', $q['wpsc-product'] );
	if ( count( $components ) == 1 )
		return $q;
	$end_node    = array_pop( $components );
	$parent_node = array_pop( $components );

	// check to see if a post with the slug exists
	// if it doesn't then we're viewing a product category
	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'name'      => $end_node,
	) );

	if ( ! empty( $posts ) ) {
		$q['wpsc-product'] = $q['name'] = $end_node;
		$q['wpsc_product_category'] = $parent_node;
	} else {
		$q['wpsc_product_category'] = $end_node;
		unset( $q['name'        ] );
		unset( $q['wpsc-product'] );
		unset( $q['post_type'   ] );
	}
	return $q;
}
if ( wpsc_get_option( 'hierarchical_product_category_url' ) )
	add_filter( 'request', 'wpsc_filter_hierarchical_category_request' );

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
function wpsc_canonical_url() {
	if ( is_singular( 'wpsc-product' ) ) {
		remove_action( 'wp_head', 'rel_canonical'             );
		add_action   ( 'wp_head', 'wpsc_action_rel_canonical' );
	}
}
add_action( 'wp', 'wpsc_canonical_url' );

/**
 * This function makes preparation in case the main catalog page is going to be displayed.
 *
 * Because the catalog page can display all products, product category list, or products of a certain
 * category, this functions takes care of that.
 *
 * @since 4.0
 * @uses  add_filter()      Attaches wpsc_get_category_list_template_paths() to 'wpsc_get_template_part_paths_for_archive'.
 * @uses  add_action()      Attaches wpsc_display_category_as_catalog()      to 'pre_get_posts'.
 * @uses  wpsc_get_option() Gets 'default_category' option.
 *
 */
function wpsc_determine_main_catalog_display_mode() {
	$mode = wpsc_get_option( 'default_category' );

	if ( $mode == 'all' )
		return;

	if ( $mode == 'list' ) {
		add_filter( 'wpsc_get_template_part_paths_for_archive', 'wpsc_get_category_list_template_paths', 10, 3 );
		return;
	}

	if ( ! is_numeric( $mode ) )
		return;

	add_action( 'pre_get_posts', 'wpsc_display_category_as_catalog' );
}

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

/**
 * In case a particular category is selected to be displayed on the main catalog page, this function
 * is hooked into 'pre_get_posts' and append a taxonomy query to make sure only products of that
 * category is fetched from the database and displayed.
 *
 * @since 4.0
 * @uses  WP_Query::is_main_query()
 * @uses  WP_Query::is_post_type_archive()
 * @uses  wpsc_get_option() Gets 'default_category' option.
 *
 * @param  object $query
 */
function wpsc_display_category_as_catalog( $query ) {
	if ( $query->is_main_query() && $query->is_post_type_archive( 'wpsc-product' ) ) {
		$q =& $query->query_vars;
		if ( empty( $q['tax_query'] ) )
			$q['tax_query'] = array();

		$q['tax_query'][] = array(
			'taxonomy'         => 'wpsc_product_category',
			'field'            => 'id',
			'terms'            => wpsc_get_option( 'default_category' ),
			'include_children' => get_option( 'show_subcatsprods_in_cat' ) ? true : false,
		);
	}
}

/**
 * When "Show Subcategory Products in Parent Category" option is disabled, this function is hooked
 * up with 'pre_get_posts'.
 *
 * This function will try to generate 'tax_query' and temporarily unset the 'wpsc_product_category'
 * query var, so that "include_children" parameter of the generated tax_query remains false.
 *
 * Later in wpsc_restore_product_category_query_var(), the 'wpsc_product_category' query var will
 * be restored to the original value.
 *
 * @since 4.0
 * @uses  WP_Query::is_main_query()
 * @uses  WP_Query::is_tax()
 * @uses  wp_basename()
 *
 * @param  object $query
 */
function wpsc_hide_child_cat_products( $query ) {
	if ( $query->is_main_query() && ( $query->is_tax( 'wpsc_product_category' ) ) ) {
		$q =& $query->query_vars;
		$tax_query_defaults = array(
			'taxonomy'         => 'wpsc_product_category',
			'field'            => 'slug',
			'include_children' => false,
		);

		$tax_query = array_key_exists( 'tax_query', $q ) ? $q['tax_query'] : array();
		$term_var  = $q['wpsc_product_category_temp'] = wp_basename( $q['wpsc_product_category'] );
		unset( $q['wpsc_product_category'] );

		if ( strpos($term_var, '+') !== false ) {
			$terms = preg_split( '/[+]+/', $term_var );
			foreach ( $terms as $term ) {
				$tax_query[] = array_merge( $tax_query_defaults, array(
					'terms' => array( $term )
				) );
			}
		} else {
			$tax_query[] = array_merge( $tax_query_defaults, array(
				'terms' => preg_split( '/[,]+/', $term_var )
			) );
		}

		$q['tax_query'] = $tax_query;
	}
}

/**
 * Restore the 'wpsc_product_category' to the original value, because in wpsc_hide_child_cat_products()
 * it is unset.
 *
 * If "Show Subcategory Products in Parent Category" is disabled, this function is hooked into the
 * 'wp' action hook.
 *
 * @since 4.0
 * @uses  $wp_query The global WP_Query object.
 */
function wpsc_restore_product_category_query_var() {
	global $wp_query;
	if ( is_tax( 'wpsc_product_category' ) ) {
		$q =& $wp_query->query_vars;
		$q['wpsc_product_category'] = $q['wpsc_product_category_temp'];
		unset( $q['wpsc_product_category_temp'] );
	}
}

/**
 * Prepare the main query object's 'tax_query' to exclude products of children category when
 * "Show Subcategory Products in Parent Category" option is disabled.
 *
 * @since 4.0
 * @uses  add_action() Attaches wpsc_hide_child_cat_products() to 'pre_get_posts' action.
 * @uses  add_action() Attaches wpsc_restore_product_category_query_var() to 'wp' action.
 * @uses  get_option() Gets 'show_subcatsprods_in_cat' option.
 */
function wpsc_determine_product_category_display_mode() {
	if ( ! get_option( 'show_subcatsprods_in_cat' ) ) {
		add_action( 'pre_get_posts', 'wpsc_hide_child_cat_products' );
		add_action( 'wp', 'wpsc_restore_product_category_query_var', 1 );
	}
}

function wpsc_register_custom_page_rewrites() {
	$cart_slug               = wpsc_get_option( 'cart_page_slug'               );
	$checkout_slug           = wpsc_get_option( 'checkout_page_slug'           );
	$transaction_result_slug = wpsc_get_option( 'transaction_result_page_slug' );
	$customer_account_slug   = wpsc_get_option( 'customer_account_page_slug'   );

	$regexp = "({$cart_slug}|{$transaction_result_slug}|{$customer_account_slug}|{$checkout_slug})(/.+?)?/?$";
	$rewrite = 'index.php?wpsc_page=$matches[1]&wpsc_callback=$matches[2]';

	add_rewrite_rule( $regexp, $rewrite, 'top' );
}
add_action( 'init', 'wpsc_register_custom_page_rewrites', 1 );

function wpsc_register_query_vars( $qv ) {
	$qv[] = 'wpsc_page';
	$qv[] = 'wpsc_callback';
	return $qv;
}
add_filter( 'query_vars', 'wpsc_register_query_vars' );

function wpsc_prepare_pages( $query ) {
	if ( ! $query->is_main_query() )
		return;

	if ( $page = $query->get( 'wpsc_page' ) ) {
		$callback = $query->get( 'wpsc_callback' );
		$GLOBALS['wpsc_page_instance'] = wpsc_get_front_end_page( $page, $callback );
	}
}
add_action( 'pre_get_posts', 'wpsc_prepare_pages', 10 );

function wpsc_body_class( $classes ) {
	if ( wpsc_is_cart() )
		$classes[] = 'wpsc-cart';
	elseif ( wpsc_is_checkout() )
		$classes[] = 'wpsc-checkout';
	return $classes;
}
add_filter( 'body_class', 'wpsc_body_class' );

function wpsc_title( $title, $sep, $sep_location ) {
	if ( wpsc_is_page() ) {
		$prefix = " {$sep} ";

		if ( wpsc_is_cart() )
			$title = apply_filters( 'wpsc_cart_title', __( 'Shopping Cart', 'wpsc' ), $sep, $sep_location );
		elseif ( wpsc_is_checkout() )
			$title = apply_filters( 'wpsc_checkout_title', __( 'Checkout', 'wpsc' ), $sep, $sep_location );

		if ( $sep_location == 'right' )
			$title .= $prefix;
		else
			$title = $prefix . $title;
	}

	return $title;
}
add_filter( 'wp_title', 'wpsc_title', 10, 3 );