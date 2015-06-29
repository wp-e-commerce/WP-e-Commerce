<?php

define( 'WPSC_THEME_ENGINE_V2_PATH', dirname( __FILE__ ) );
define( 'WPSC_TE_V2_CLASSES_PATH'  , WPSC_THEME_ENGINE_V2_PATH . '/classes' );
define( 'WPSC_TE_V2_HELPERS_PATH'  , WPSC_THEME_ENGINE_V2_PATH . '/helpers' );
define( 'WPSC_TE_V2_SNIPPETS_PATH' , WPSC_THEME_ENGINE_V2_PATH . '/snippets' );
define( 'WPSC_TE_V2_THEMING_PATH'  , WPSC_THEME_ENGINE_V2_PATH . '/theming' );
define( 'WPSC_TE_V2_URL'           , plugins_url( '', __FILE__ ) );

add_action( 'wpsc_includes', '_wpsc_te_v2_includes' );

add_filter(
	'wpsc_register_post_types_products_args',
	'_wpsc_te_v2_product_post_type_args'
);

add_filter(
	'wpsc_register_taxonomies_product_category_args',
	'_wpsc_te_v2_product_category_args'
);

add_action( 'after_switch_theme', '_wpsc_action_flush_rewrite_rules', 99 );

function _wpsc_te_v2_includes() {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/redirect-canonical.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/template-engine.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/settings.php' );

	require_once( WPSC_TE_V2_HELPERS_PATH . '/redirect-canonical.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/compat.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/mvc.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/settings.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/form.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/form-validation.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/common.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/template-tags/url.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/css.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/js.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/widgets.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/customer.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/shortcodes.php' );
	require_once( WPSC_TE_V2_HELPERS_PATH . '/product.php' );

	if ( is_admin() ) {
		require_once( WPSC_THEME_ENGINE_V2_PATH . '/admin.php' );
	}

	if ( ! is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		_wpsc_te2_mvc_init();
	}

	add_filter( 'rewrite_rules_array', '_wpsc_filter_rewrite_controller_slugs' );
}

/**
 * Modify wpsc-product post type arguments to make store slug works as the
 * post type archive and base slug of everything else related to WPEC
 *
 * Filter hook: wpsc_register_post_types_products_args
 * @since  0.1
 * @access private
 * @param  array $args Post type arguments
 * @return array       Modified post type arguments
 */
function _wpsc_te_v2_product_post_type_args( $args ) {
	// get the base slug
	$archive_slug = $store_slug = wpsc_get_option( 'store_slug' );

	// set the base slug to '/' in case it is set to be displayed as the front
	// page ('has_archive' has to be set to a non-empty value)
	if ( ! $store_slug ) {
		$archive_slug = '/';
	} else {
		$store_slug .= '/';
	}

	// get single product base slug
	$product_slug = $store_slug . wpsc_get_option( 'product_base_slug' );

	// include product category as well if user wants to
	if ( wpsc_get_option( 'prefix_product_slug' ) ) {
		$product_slug .= '/%wpsc_product_category%';
	}

	// modify the args
	$args['has_archive']     = $archive_slug;
	$args['rewrite']['slug'] = $product_slug;

	return $args;
}

/**
 * Modify wpsc_product_category arguments to use the base product category slug
 * option
 *
 * Filter hook: wpsc_register_taxonomies_product_category_args
 * @since  0.1
 * @access private
 * @param  array $args Product category arguments
 * @return array       Modified product category arguments
 */
function _wpsc_te_v2_product_category_args( $args ) {
	$store_slug = wpsc_get_option( 'store_slug' );

	if ( $store_slug ) {
		$store_slug .= '/';
	}

	$category_base_slug            = wpsc_get_option( 'category_base_slug' );
	$hierarchical_product_category = wpsc_get_option( 'hierarchical_product_category_url' );

	$args['rewrite']['slug']         = $store_slug . $category_base_slug;
	$args['rewrite']['hierarchical'] = (bool) $hierarchical_product_category;

	return $args;
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
 * Properly replace permalink tags with product's name and product category.
 *
 * This function also takes into account two settings if $canonical is false: whether to prefix
 * product permalink with product category, and whether hierarchical product category URL is enabled.
 *
 * @access private
 * @since 4.0
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

	$category_slug = '';

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
					if ( in_array( $term->slug, $ancestors ) || is_wp_error( $term ) ) {
						break;
					}
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

	if ( $canonical ) {
		return apply_filters( 'wpsc_product_permalink_canonical', $permalink, $post->ID );
	} else {
		return apply_filters( 'wpsc_product_permalink', $permalink, $post->ID );
	}
}