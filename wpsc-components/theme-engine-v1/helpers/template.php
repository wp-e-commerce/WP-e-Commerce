<?php
add_action( 'wp', 'wpsc_select_theme_functions', 10, 1 );
add_filter( 'body_class', 'wpsc_body_class' );

/**
 * wpsc_is_product function.
 *
 * @since 3.8
 * @access public
 * @return boolean
 */
function wpsc_is_product() {
	global $wp_query, $rewrite_rules;
	$tmp = false;

	if ( isset( $wp_query->is_product ) )
		$tmp = $wp_query->is_product;

	return $tmp;
}

/**
 * wpsc_is_product function.
 *
 * @since 3.8
 * @access public
 * @return boolean
 */
function wpsc_is_checkout() {
	global $wp_query, $rewrite_rules;
	$tmp = false;

	if ( isset( $wp_query->is_checkout ) )
		$tmp = $wp_query->is_checkout;

	return $tmp;
}

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

/**
 * Body Class Filter
 * @modified:     2009-10-14 by Ben
 * @description:  Adds additional wpsc classes to the body tag.
 * @param:        $classes = Array of body classes
 * @return:       (Array) of classes
 */
function wpsc_body_class( $classes ) {
	global $wp_query, $wpsc_query;
	$post_id = 0;
	if ( isset( $wp_query->post->ID ) )
		$post_id = $wp_query->post->ID;
	$page_url = get_permalink( $post_id );

	// If on a product or category page...
	if ( get_option( 'product_list_url' ) == $page_url ) {

		$classes[] = 'wpsc';

		if ( !is_array( $wpsc_query->query ) )
			$classes[] = 'wpsc-home';

		if ( wpsc_is_single_product ( ) ) {
			$classes[] = 'wpsc-single-product';
			if ( absint( $wpsc_query->products[0]['id'] ) > 0 ) {
				$classes[] = 'wpsc-single-product-' . $wpsc_query->products[0]['id'];
			}
		}

		if ( wpsc_is_in_category() && !wpsc_is_single_product() )
			$classes[] = 'wpsc-category';

		if ( isset( $wpsc_query->query_vars['category_id'] ) && absint( $wpsc_query->query_vars['category_id'] ) > 0 )
			$classes[] = 'wpsc-category-' . $wpsc_query->query_vars['category_id'];

	}

	// If viewing the shopping cart...
	if ( get_option( 'shopping_cart_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-shopping-cart';
	}

	// If viewing the transaction...
	if ( get_option( 'transact_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-transaction-details';
	}

	// If viewing your account...
	if ( get_option( 'user_account_url' ) == $page_url ) {
		$classes[] = 'wpsc';
		$classes[] = 'wpsc-user-account';
	}

	return $classes;
}

/**
 * Checks the active theme folder for the particular file, if it exists then return the active theme directory otherwise
 * return the global wpsc_theme_path
 * @access public
 *
 * @since 3.8
 * @param $file string filename
 * @return PATH to the file
 */
function wpsc_get_template_file_path( $file = '' ){

	// If we're not looking for a file, do not proceed
	if ( empty( $file ) )
		return;

	// No cache, so find one and set it
	if ( false === ( $file_path = get_transient( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file ) ) ) {

		// Plugin may override the template file, get the file name and check to be sure file exists
		$file_path = apply_filters( 'wpsc_get_template_file_path' , false );
		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			$file_path = realpath( $file_path ); // real path just in case plugin doesn't return canonicalized path name

		// Look for file in stylesheet directory
		} elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
			$file_path = get_stylesheet_directory() . '/' . $file;

		// Look for file in template
		} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
			$file_path = get_template_directory() . '/' . $file;

		// Backwards compatibility
		} else {
			// Look in old theme path
			$selected_theme_check = WPSC_OLD_THEMES_PATH . get_option( 'wpsc_selected_theme' ) . '/' . str_ireplace( 'wpsc-', '', $file );

			// Check the selected theme
			if ( file_exists( $selected_theme_check ) ) {
				$file_path = $selected_theme_check;

			// Use the bundled file
			} else {
				$file_path = path_join( WPSC_CORE_THEME_PATH, $file );
			}
		}
		// Save the transient and update it every 12 hours
		if ( !empty( $file_path ) )
			set_transient( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file, $file_path, 60 * 60 * 12 );

	}elseif(!file_exists($file_path)){
		delete_transient(WPEC_TRANSIENT_THEME_PATH_PREFIX . $file);
		wpsc_get_template_file_path($file);
	}

	// Return filtered result
	return apply_filters( WPEC_TRANSIENT_THEME_PATH_PREFIX . $file, $file_path );
}

/**
 * Featured Product
 *
 * Refactoring Featured Product Plugin to utilize Sticky Post Status, available since WP 2.7
 * also utilizes Featured Image functionality, available as post_thumbnail since 2.9, Featured Image since 3.0
 * Main differences - Removed 3.8 conditions, removed meta box from admin, changed meta_values
 * Removes shortcode, as it automatically ties in to top_of_page hook if sticky AND featured product exists.
 *
 * @package wp-e-commerce
 * @since 3.8
 */
function wpsc_the_sticky_image( $product_id ) {
	$attached_images = (array)get_posts( array(
				'post_type' => 'attachment',
				'numberposts' => 1,
				'post_status' => null,
				'post_parent' => $product_id,
				'orderby' => 'menu_order',
				'order' => 'ASC'
			) );
	if ( has_post_thumbnail( $product_id ) ) {
		add_image_size( 'featured-product-thumbnails', 540, 260, TRUE );
		$image = get_the_post_thumbnail( $product_id, 'featured-product-thumbnails' );
		return $image;
	} elseif ( !empty( $attached_images ) ) {
		$attached_image = $attached_images[0];
		$image_link = wpsc_product_image( $attached_image->ID, 540, 260 );
		return '<img src="' . $image_link . '" alt="" />';
	} else {
		return false;
	}
}

/**
 * WPSC canonical URL function
 * Needs a recent version
 * @since 3.7
 * @param int product id
 * @return bool true or false
 */
function wpsc_change_canonical_url( $url = '' ) {
	global $wpdb, $wp_query, $wpsc_page_titles;

	if ( $wp_query->is_single == true && 'wpsc-product' == $wp_query->query_vars['post_type']) {
		$url = get_permalink( $wp_query->get_queried_object()->ID );
	}
	return apply_filters( 'wpsc_change_canonical_url', $url );
}

add_filter( 'aioseop_canonical_url', 'wpsc_change_canonical_url' );

function wpsc_insert_canonical_url() {
	$wpsc_url = wpsc_change_canonical_url( null );
	echo "<link rel='canonical' href='$wpsc_url' />\n";
}

function wpsc_canonical_url() {
	$wpsc_url = wpsc_change_canonical_url( null );
	if ( $wpsc_url != null ) {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', 'wpsc_insert_canonical_url' );
	}
}
add_action( 'template_redirect', 'wpsc_canonical_url' );

