<?php

function _wpsc_theme_engine_v1_has_actions() {
	$actions = array(
		'wpsc_start_display_user_log_form_fields',
		'wpsc_pre_purchase_logs',
		'wpsc_user_log_after_order_status',
		'wpsc_before_cart_widget_item_name',
		'wpsc_after_cart_widget_item_name',
		'wpsc_top_of_products_page',
		'wpsc_product_form_fields_begin',
		'wpsc_product_form_fields_end',
		'wpsc_theme_footer',
		'wpsc_product_before_description',
		'wpsc_product_addons',
		'wpsc_before_checkout_cart_row',
		'wpsc_before_checkout_cart_item_image',
		'wpsc_after_checkout_cart_item_image',
		'wpsc_before_checkout_cart_item_name',
		'wpsc_after_checkout_cart_item_name',
		'wpsc_after_checkout_cart_row',
		'wpsc_after_checkout_cart_rows',
		'wpsc_before_shipping_of_shopping_cart',
		'wpsc_before_form_of_shopping_cart',
		'wpsc_inside_shopping_cart',
		'wpsc_bottom_of_shopping_cart',
		'wpsc_additional_user_profile_links',
		'wpsc_user_profile_section_purchase_history',
		'wpsc_user_profile_section_edit_profile',
		'wpsc_user_profile_section_downloads',
	);

	$has_actions = false;

	foreach ( $actions as $action ) {
		if ( has_action( $action ) ) {
			$has_actions = true;
		}
	}

	return $has_actions;
}

function _wpsc_theme_engine_v2_has_old_templates() {
	$current_theme  = trailingslashit( get_stylesheet_directory() );
	$theme_files    = scandir( $current_theme );
	$wpsc_files     = array();

	foreach ( $theme_files as $file ) {
		if ( 'wpsc-' === substr( $file, 0, 5 ) && is_file( $current_theme . $file ) ) {
			$wpsc_files[] = $file;
		}
	}

	return ! empty( $wpsc_files );
}

function _wpsc_enable_theme_engine_v1( $components ) {
	$components['theme-engine']['core-v1'] = array(
		'title'    => __( 'WP e-Commerce Theme Engine v1', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v1/theme-engine-v1.php'
	);

	return $components;
}

function _wpsc_enable_theme_engine_v2( $components ) {
	$components['theme-engine']['core-v2'] = array(
		'title'    => __( 'WP e-Commerce Theme Engine v2', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v2/core.php'
	);

	return $components;
}

function _wpsc_maybe_activate_theme_engine_v2() {

	$activate = true;

	global $wp_rewrite;

	if ( ! is_a( $wp_rewrite, 'WP_Rewrite' ) ) {
		$wp_rewrite = new WP_Rewrite();
	}

	if ( ! $wp_rewrite->using_permalinks() ) {
		$activate = false;
	}

	if ( $wp_rewrite->using_index_permalinks() ) {
		$activate = false;
	}

	if ( defined( 'WPSC_GOLD_VERSION' ) ) {
		$activate = false;
	}

	if ( _wpsc_theme_engine_v2_has_old_templates() ) {
		$activate = false;
	}

	if ( _wpsc_theme_engine_v1_has_actions() ) {
		$activate = false;
	}

	return apply_filters( '_wpsc_maybe_activate_theme_engine_v2', $activate );
}

function _wpsc_theme_engine_router( $components ) {

	if ( _wpsc_maybe_activate_theme_engine_v2() ) {
		return _wpsc_enable_theme_engine_v2( $components );
	} else {
		return _wpsc_enable_theme_engine_v1( $components );
	}
}

add_filter( 'wpsc_components', '_wpsc_theme_engine_router' );