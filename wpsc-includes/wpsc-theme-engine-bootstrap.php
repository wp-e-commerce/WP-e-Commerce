<?php

/**
 * Checks 1.0 theme engine template actions for hooks.
 * This ensures we don't load the 2.0 engine if there are active plugins
 * that expect the 1.0 hooks to be available.
 *
 * @since  3.9
 * @uses   has_action()
 * @uses   remove_action()
 *
 * @return array $has_actions If not empty, then we don't load 2.0
 */
function _wpsc_theme_engine_v1_has_actions() {

	/**
	 * A list of all actions used in the 1.0 theme engine templates.
	 * If any of these are hooked into by plugins or the active theme, we load 1.0.
	 *
	 * @var array
	 */
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

	/**
	 * A list of all actions that are hooked into in core.
	 * We don't actually want to load 1.0 if the only actions hooking into 1.0 templates are from core.
	 *
	 * @var array
	 */
	$core_exceptions = array(
		'wpsc_start_display_user_log_form_fields'    => 'wpsc_deprecated_filter_user_log_get',
		'wpsc_theme_footer'                          => 'wpsc_fancy_notifications',
		'wpsc_before_shipping_of_shopping_cart'      => array( '_wpsc_calculate_shipping_quotes_before_product_page', '_wpsc_action_init_shipping_method' ),
		'wpsc_before_form_of_shopping_cart'          => '_wpsc_shipping_error_messages',
		'wpsc_user_profile_section_purchase_history' => '_wpsc_action_purchase_history_section',
		'wpsc_user_profile_section_edit_profile'     => '_wpsc_action_edit_profile_section',
		'wpsc_user_profile_section_downloads'        => '_wpsc_action_downloads_section'
	);

	$has_actions = array();

	foreach ( $actions as $action ) {

		if ( isset( $core_exceptions[ $action ] ) ) {

			if ( is_array( $core_exceptions[ $action ] ) ) {

				foreach ( $core_exceptions[ $action ] as $core_hook ) {
					remove_action( $action, $core_hook  );
				}

			} else {

				remove_action( $action, $core_exceptions[ $action ] );

			}
		}

		if ( has_action( $action ) ) {
			$has_actions[] = $action;
		}
	}

	return $has_actions;
}

/**
 * A simple check of the active theme directory for wpsc template files.
 * Minimally redundant, though the comparable functions are only loaded in the 1.0 engine.
 *
 * @since  3.9
 * @return [type] [description]
 */
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

/**
 * Enables the 1.0 theme engine.
 *
 * @since  3.9
 * @param  array $components Array of components loaded via the component API.
 * @return array $components Merged array of components.
 */
function _wpsc_enable_theme_engine_v1( $components ) {
	$components['theme-engine']['core-v1'] = array(
		'title'    => __( 'WP eCommerce Theme Engine v1', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v1/theme-engine-v1.php'
	);

	return $components;
}
/**
 * Enables the 2.0 theme engine.
 *
 * @since  3.9
 * @param  array $components Array of components loaded via the component API.
 * @return array $components Merged array of components.
 */
function _wpsc_enable_theme_engine_v2( $components ) {
	$components['theme-engine']['core-v2'] = array(
		'title'    => __( 'WP eCommerce Theme Engine v2', 'wpsc' ),
		'includes' =>
			WPSC_FILE_PATH . '/wpsc-components/theme-engine-v2/core.php'
	);

	return $components;
}

/**
 * Load either 1.0 or 2.0 theme engine, based on several critical and evolving criteron.
 * Theme and plugin developers are advised to check and filter this function as needed.
 *
 * @since  3.9
 * @return bool $activate Whether or not to activate the 2.0 theme engine.
 */
function _wpsc_maybe_activate_theme_engine_v2() {

	$activate = false;

	global $wp_rewrite;

	if ( ! is_a( $wp_rewrite, 'WP_Rewrite' ) ) {
		$wp_rewrite = new WP_Rewrite();
	}

	// The theme engine routing mechanism (WPSC_Router) is not currently equipped to work without pretty permalinks.
	if ( ! $wp_rewrite->using_permalinks() ) {
		$activate = false;
	}

	// We're also not currently able to work as expected when using "Almost Pretty" permalinks, where PATHINFO is not enabled.
	if ( $wp_rewrite->using_index_permalinks() ) {
		$activate = false;
	}

	// In the future, this check will be more refined.  As of 3.9, the current Gold Cart release is incompatible with the 2.0 theme engine.
	if ( defined( 'WPSC_GOLD_VERSION' ) ) {
		$activate = false;
	}

	// If an active theme is dependent on 1.0 theme template files from 3.8.
	if ( _wpsc_theme_engine_v2_has_old_templates() ) {
		$activate = false;
	}

	// If any plugins or theme functionality are hooking into 1.0 template hooks, we don't activate 2.0.
	if ( _wpsc_theme_engine_v1_has_actions() ) {
		$activate = false;
	}

	return apply_filters( '_wpsc_maybe_activate_theme_engine_v2', $activate );
}

/**
 * Simple router for using either the 1.0 or 2.0 theme engine, based on result of _wpsc_maybe_activate_theme_engine_v2().
 *
 * @since  3.9
 * @param  array $components Array of components loaded via the component API.
 * @return array $components Merged array of components loaded via the component API.
 */
function _wpsc_theme_engine_router( $components ) {

	if ( _wpsc_maybe_activate_theme_engine_v2() ) {
		return _wpsc_enable_theme_engine_v2( $components );
	} else {
		return _wpsc_enable_theme_engine_v1( $components );
	}
}

add_filter( 'wpsc_components', '_wpsc_theme_engine_router' );


/**
 * Deactivates the former feature-as-a-plugin plugin for the 2.0 theme engine, if it is active.
 *
 * @since  3.9.0
 * @return void
 */
function _wpsc_deactivate_theme_engine_plugin() {
	if ( defined( 'WPSC_TE_V2_PATH' ) ) {
		deactivate_plugins( plugin_basename( WPSC_TE_V2_PATH ) . '/wp-e-commerce-theme-engine-v2.php' );
	}
}

add_action( 'admin_init' , '_wpsc_deactivate_theme_engine_plugin' );