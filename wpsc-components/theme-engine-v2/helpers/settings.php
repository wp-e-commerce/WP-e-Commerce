<?php

add_action( 'wpsc_theme_engine_v2_activate', '_wpsc_te2_action_setup_settings');

add_action(
	'sanitize_option_show_on_front',
	'_wpsc_te2_action_sanitize_show_on_front'
);

add_filter(
	'pre_option_wpsc_store_slug',
	'_wpsc_te2_filter_store_slug'
);

add_filter(
	'pre_option_transact_url',
	'_wpsc_te2_filter_option_transact_url'
);

add_filter(
	'pre_option_shopping_cart_url',
	'_wpsc_te2_filter_option_shopping_cart_url'
);

add_filter(
	'option_wpsc_category_base_slug',
	'_wpsc_te2_filter_category_base_slug'
);

/**
 * Retrieve WP eCommerce option value based on name of the option.
 *
 * Works just like get_option(), except that it automatically prefixes
 * the option name with 'wpsc_' and assign a default value as defined in
 * WPSC_Settings class in case the option has no value.
 *
 * @since  0.1
 *
 * @uses   WPSC_Settings::get()
 * @param  string $option_name Name of the option, not escaped.
 * @return mixed               Value of the option
 */
function wpsc_get_option( $option_name ) {
	$wpsc_settings = WPSC_Settings::get_instance();
	return $wpsc_settings->get( $option_name );
}

/**
 * Update the value of an option that was already added.
 *
 * Works just like update_option(), except that it automatically prefixes the
 * option name with 'wpsc_'.
 *
 * @since  0.1
 *
 * @param  string $option_name Option name
 * @param  mixed  $value       New value
 * @return bool                True if updated successfully
 */
function wpsc_update_option( $option_name, $value ) {
	$wpsc_settings = WPSC_Settings::get_instance();
	return $wpsc_settings->set( $option_name, $value );
}

/**
 * The 'transact_url' option is still used by other components outside of theme
 * engine (such as payment gateways). To ensure compatibility, we need to point
 * this option to the last step of the checkout process.
 *
 * Action hook: 'pre_option_transact_url'.
 *
 * @access private
 *
 * @since  0.1
 * @return string The new transaction result URL
 */
function _wpsc_te2_filter_option_transact_url() {
	return wpsc_get_checkout_url( 'results' );
}

/**
 * The 'shopping_cart_url' option is still used by other components outside of theme
 * engine (such as payment gateways). To ensure compatibility, we need to point
 * this option to the last step of the checkout process.
 *
 * Action hook: 'pre_option_shopping_cart_url'.
 *
 * @access private
 *
 * @since  0.1
 * @return string The new shopping cart URL
 */
function _wpsc_te2_filter_option_shopping_cart_url() {
	return wpsc_get_checkout_url( 'payment' );
}

/**
 * When the theme engine is activated, setup the options.
 *
 * Action hooks: 'wpsc_theme_engine_v2_activate', 'add_option_rewrite_rules'
 *
 * @since  0.1
 * @access private
 * @uses   WPSC_Settings::_action_setup()
 */
function _wpsc_te2_action_setup_settings() {
	$wpsc_settings = WPSC_Settings::get_instance();
	$wpsc_settings->_action_setup();
}

/**
 * Provide compatibility between 'show_on_front' and 'store_as_front_page' options.
 *
 * WordPress currently doesn't allow any values for 'show_on_front' other than
 * 'page' and 'posts'.
 *
 * {@link _wpsc_te2_action_admin_enqueue_script()} is used to dynamically inject
 * a radio box in Settings->Reading so that it's more user friendly to select
 * 'Main store as front page' as an option.
 *
 * Behind the scene, it doesn't really matter what the value of 'show_on_front'
 * is. What really matters is the 'wpsc_store_as_front_page' option.
 *
 * Filter hook: sanitize_option_show_on_front
 *
 * @access private
 *
 * @since  0.1
 * @param  mixed $value
 * @return mixed
 */
function _wpsc_te2_action_sanitize_show_on_front( $value ) {
	// if the value is 'wpsc_main_store', just reset it back to 'posts' or
	// 'page'.
	if ( $value == 'wpsc_main_store' ) {
		$value = 'page';
		wpsc_update_option( 'store_as_front_page', true );
	} else {
		// if the user selected something other than main store as front page,
		// reset 'wpsc_store_as_front_page' to false
		wpsc_update_option( 'store_as_front_page', false );
	}

	// regenerate rewrite rules again because wpsc-product post type archive
	// slug has possibly changed
	wpsc_register_post_types();
	flush_rewrite_rules();
	return $value;
}

/**
 * In case store is set to display on front page, force the 'store_slug' option
 * to always return empty value.
 *
 * @since  0.1
 * @access private
 *
 * @param  string $value Current value of 'store_slug' option
 * @return string        New value
 */
function _wpsc_te2_filter_store_slug( $value ) {

	if ( wpsc_get_option( 'store_as_front_page' ) ) {
		return '';
	}

	return false;
}

/**
 * Prevent conflict with category base slug in case front page is set to
 * store. In that case, the category base slug.
 *
 * Filter hook: option_wpsc_store_slug
 *
 * @since  0.1
 * @access private
 *
 * @param  string $value Current value
 * @return string        New value
 */
function _wpsc_te2_filter_category_base_slug( $value ) {

	if ( ! wpsc_get_option( 'store_as_front_page') ) {
		return $value;
	}

	$category_base = get_option( 'category_base' );

	if ( ! $category_base ) {
		$category_base = 'category';
	}

	if ( $value == $category_base ) {
		$value = 'store-' . $value;
	}

	return $value;
}


function _wpsc_action_check_thumbnail_support() {
	if ( ! current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails' );
		add_action( 'init', '_wpsc_action_remove_post_type_thumbnail_support' );
	}

	$crop = wpsc_get_option( 'crop_thumbnails' );

	add_image_size(
		'wpsc_product_single_thumbnail',
		get_option( 'single_view_image_width' ),
		get_option( 'single_view_image_height' ),
		$crop
	);

	add_image_size(
		'wpsc_product_archive_thumbnail',
		get_option( 'product_image_width' ),
		get_option( 'product_image_height' ),
		$crop
	);

	add_image_size(
		'wpsc_product_taxonomy_thumbnail',
		get_option( 'category_image_width' ),
		get_option( 'category_image_height' ),
		$crop
	);

	add_image_size(
		'wpsc_product_admin_thumbnail',
		50,
		50,
		$crop
	);

	add_image_size(
		'wpsc_product_cart_thumbnail',
		64,
		64,
		$crop
	);
}

add_action( 'after_setup_theme', '_wpsc_action_check_thumbnail_support', 99 );
