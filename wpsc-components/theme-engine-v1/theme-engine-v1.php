<?php

add_action( 'wpsc_includes', '_wpsc_action_theme_engine_v1_includes' );

_wpsc_action_theme_engine_v1_constants();
function _wpsc_action_theme_engine_v1_constants() {
	define( 'WPSC_THEME_ENGINE_V1_PATH', dirname( __FILE__ ) );
	define( 'WPSC_THEME_ENGINE_V1_URL', plugins_url(  basename( dirname ( __FILE__  ) ) , dirname( __FILE__ ) ) );

	// Themes folder locations
	define( 'WPSC_CORE_THEME_PATH', WPSC_THEME_ENGINE_V1_PATH . '/templates/' );
	define( 'WPSC_CORE_THEME_URL' , WPSC_THEME_ENGINE_V1_URL  . '/templates/' );

	// No transient so look for the themes directory
	if ( false === ( $theme_path = get_transient( 'wpsc_theme_path' ) ) ) {

		// Use the old path if it exists
		if ( file_exists( WPSC_OLD_THEMES_PATH.get_option('wpsc_selected_theme') ) )
			define( 'WPSC_THEMES_PATH', WPSC_OLD_THEMES_PATH );

		// Use the built in theme files
		else
			define( 'WPSC_THEMES_PATH', WPSC_CORE_THEME_PATH );

		// Store the theme directory in a transient for safe keeping
		set_transient( 'wpsc_theme_path', WPSC_THEMES_PATH, 60 * 60 * 12 );

	// Transient exists, so use that
	} else {
		define( 'WPSC_THEMES_PATH', $theme_path );
	}

	$selected_theme  = get_option( 'wpsc_selected_theme' );

	// Pick selected theme or fallback to default
	if ( empty( $selected_theme ) || !file_exists( WPSC_THEMES_PATH ) )
		define( 'WPSC_THEME_DIR', 'default' );
	else
		define( 'WPSC_THEME_DIR', $selected_theme );

	// Include a file named after the current theme, if one exists
	if ( !empty( $selected_theme ) && file_exists( WPSC_THEMES_PATH . $selected_theme . '/' . $selected_theme . '.php' ) )
		include_once( WPSC_THEMES_PATH . $selected_theme . '/' . $selected_theme . '.php' );

	define( 'WPEC_TRANSIENT_THEME_PATH_PREFIX', 'wpsc_path_' );
	define( 'WPEC_TRANSIENT_THEME_URL_PREFIX', 'wpsc_url_' );
}

function _wpsc_action_theme_engine_v1_includes() {
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/classes/breadcrumbs.php' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/classes/wpsc-products-by-category.php' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/classes/hide-subcatsprods-in-cat.php' );

	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/thumbnails.php' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/page.php'       );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/rewrite.php'    );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/query.php'      );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/template.php'   );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/product.php'    );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/ajax.php'       );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/checkout.php'   );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/cart.php'       );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/template-tags.php' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/shortcodes.php' );
	require_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/form.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/admin.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/shipping.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/helpers/rss.php' );

	require_once( WPSC_CORE_THEME_PATH . 'functions/wpsc-transaction_results_functions.php' );
	require_once( WPSC_CORE_THEME_PATH . 'functions/wpsc-user_log_functions.php' );

	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/category_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/cart.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/product_tag_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/shopping_cart_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/donations_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/specials_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/latest_product_widget.php' );
	include_once( WPSC_THEME_ENGINE_V1_PATH . '/widgets/price_range_widget.php' );
	wpsc_enable_page_filters();
}

function wpsc_enable_page_filters( $excerpt = '' ) {
	add_filter( 'the_content', 'add_to_cart_shortcode', 12 ); //Used for add_to_cart_button shortcode
	add_filter( 'the_content', 'wpsc_products_page', 1 );
	add_filter( 'the_content', 'wpsc_single_template',12 );
	add_filter( 'archive_template','wpsc_the_category_template');
	add_filter( 'the_title', 'wpsc_the_category_title',10 );
	add_filter( 'the_content', 'wpsc_place_shopping_cart', 12 );
	add_filter( 'the_content', 'wpsc_transaction_results', 12 );
	add_filter( 'the_content', 'wpsc_user_log', 12 );
	return $excerpt;
}

