<?php

// Left Overs
$wpsc_currency_data = array();
$wpsc_title_data    = array();

/**
 * _wpsc_is_session_started()
 *
 * Check if PHP session is started using method suggested on php.net
 *
 * @since 3.8.14
 * @return boolean
 */
function _wpsc_is_session_started() {

	if ( version_compare( phpversion(), '5.4.0', '>=' ) ) {
		return session_status() === PHP_SESSION_ACTIVE;
	} else {
		if ( ! isset( $_SESSION ) ) {
			$_SESSION = null;
		}

		return session_id() !== '';
	}

	return false;
}

/**
 * wpsc_core_load_session()
 *
 * Load up the WPEC session
 *
 * @return boolean
 */
function wpsc_core_load_session() {

	if ( ! _wpsc_is_session_started() ) {
		@ session_start();
	}

	return _wpsc_is_session_started();
}

/**
 * wpsc_core_constants()
 *
 * The core WPEC constants necessary to start loading
 */
function wpsc_core_constants() {
	if ( ! defined( 'WPSC_URL' ) ) {
		define( 'WPSC_URL', plugins_url( '', __FILE__ ) );
	}

	// Define Plugin version
	if ( ! defined( 'WPSC_VERSION' ) ) {
		define( 'WPSC_VERSION'            , '3.11.2' );
	}

	if ( ! defined( 'WPSC_MINOR_VERSION' ) ) {
		define( 'WPSC_MINOR_VERSION'      , 'ffbc44e' );
	}

	if ( ! defined( 'WPSC_PRESENTABLE_VERSION' ) ) {
		define( 'WPSC_PRESENTABLE_VERSION', '3.11.2' );
	}

	// Define a salt to use when we hash, WPSC_SALT may be defined for us in our config file, so check first
	if ( ! defined( 'WPSC_SALT' ) ) {
		if ( defined( 'AUTH_SALT' ) ) {
			define( 'WPSC_SALT', AUTH_SALT );
		} else {
			define( 'WPSC_SALT', hash_hmac( 'md5', __FUNCTION__, __FILE__ ) );
		}
	}

	// Define the current database version
	define( 'WPSC_DB_VERSION', 14 );

	// Define Debug Variables for developers, if they haven't already been defined
	if ( ! defined( 'WPSC_DEBUG' ) ) {
		define( 'WPSC_DEBUG', false );
	}

	if ( ! defined( 'WPSC_GATEWAY_DEBUG' ) ) {
		define( 'WPSC_GATEWAY_DEBUG', false );
	}

	// Images URL
	define( 'WPSC_CORE_IMAGES_URL',  WPSC_URL . '/wpsc-core/images' );
	define( 'WPSC_CORE_IMAGES_PATH', WPSC_FILE_PATH . '/wpsc-core/images' );

	// JS URL
	define( 'WPSC_CORE_JS_URL' , WPSC_URL . '/wpsc-core/js' );
	define( 'WPSC_CORE_JS_PATH', WPSC_FILE_PATH . '/wpsc-core/js' );


	// Require loading of deprecated functions for now. We will ween WPEC off
	// of this in future versions.
	if ( ! defined( 'WPEC_LOAD_DEPRECATED' ) ) {
		// use a filter so that themes can turn this off without editing config or code,
		$load_deprecated = apply_filters( 'wpsc_load_deprecated', true );
		define( 'WPEC_LOAD_DEPRECATED', $load_deprecated );
	}

	// Do not require loading of deprecated JS of this in future versions.
	if ( ! defined( 'WPEC_LOAD_DEPRECATED_JS' ) ) {
		define( 'WPEC_LOAD_DEPRECATED_JS', false );
	}

	define( 'WPSC_CUSTOMER_COOKIE', 'wpsc_customer_cookie_' . COOKIEHASH );

	if ( ! defined( 'WPSC_CUSTOMER_COOKIE_PATH' ) )
		define( 'WPSC_CUSTOMER_COOKIE_PATH', COOKIEPATH );

	if ( ! defined( 'WPSC_CUSTOMER_DATA_EXPIRATION' ) ) {
		define( 'WPSC_CUSTOMER_DATA_EXPIRATION', 48 * 3600 );
	}

	/*
	 * When caching is true, the cart needs to be loaded using AJAX.
	 * Caching is false then the cart can be generated in-line with the page.content.
	 *
	 * In the case of the cart widget, true would always load the widget using AJAX
	 * That would mean that one user would not see another users cart because the
	 * other user's request filled the page cache.
	 */
	if ( ! defined( 'WPSC_PAGE_CACHE_IN_USE' ) ) {
		// if the do not cache constant is set behave as if there was a page cache in place and
		// don't cache generated results
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			define( 'WPSC_PAGE_CACHE_IN_USE', true );
		} elseif ( defined( 'WP_CACHE' ) ) {
			define( 'WPSC_PAGE_CACHE_IN_USE', WP_CACHE );
		} else {
			// default to assuming a cache is there if we don't know otherwise,
			// this should prevent one user's data from being used to generate pages
			// that other user may see, for example cat contents.
			define( 'WPSC_PAGE_CACHE_IN_USE', true );
		}
	}
}

/**
 * wpsc_core_version_processing()
 */
function wpsc_core_constants_version_processing() {
	global $wp_version;

	$version_processing = str_replace( array( '_', '-', '+' ), '.', strtolower( $wp_version ) );
	$version_processing = str_replace( array( 'alpha', 'beta', 'gamma' ), array( 'a', 'b', 'g' ), $version_processing );
	$version_processing = preg_split( "/([a-z]+)/i", $version_processing, -1, PREG_SPLIT_DELIM_CAPTURE );

	array_walk( $version_processing, create_function( '&$v', '$v = trim($v,". ");' ) );

	define( 'IS_WP25', version_compare( $version_processing[0], '2.5', '>=' ) );
	define( 'IS_WP27', version_compare( $version_processing[0], '2.7', '>=' ) );
	define( 'IS_WP29', version_compare( $version_processing[0], '2.9', '>=' ) );
	define( 'IS_WP30', version_compare( $version_processing[0], '3.0', '>=' ) );
}

/**
 * wpsc_core_is_multisite()
 *
 * Checks if this is a multisite installation of WordPress
 *
 * @global object $wpdb
 * @return bool
 */
function wpsc_core_is_multisite() {
	global $wpdb;

	if ( defined( 'IS_WPMU' ) )
		return IS_WPMU;

	if ( isset( $wpdb->blogid ) )
		$is_multisite = 1;
	else
		$is_multisite = 0;

	define( 'IS_WPMU', $is_multisite );

	return (bool) $is_multisite;
}

/**
 * wpsc_core_constants_table_names()
 *
 * List globals here for proper assignment
 *
 * @global string $table_prefix
 * @global object $wpdb
 */
function wpsc_core_constants_table_names() {
	global $table_prefix, $wpdb;

	// Use the DB method if it's around
	if ( !empty( $wpdb->prefix ) )
		$wp_table_prefix = $wpdb->prefix;

	// Fallback on the wp_config.php global
	else if ( !empty( $table_prefix ) )
		$wp_table_prefix = $table_prefix;

	// the WPSC meta prefix, used for the product meta functions.
	define( 'WPSC_META_PREFIX', '_wpsc_' );

	// These tables are required, either for speed, or because there are no
	// existing WordPress tables suitable for the data stored in them.
	define( 'WPSC_TABLE_PURCHASE_LOGS',          "{$wp_table_prefix}wpsc_purchase_logs" );
	define( 'WPSC_TABLE_CART_CONTENTS',          "{$wp_table_prefix}wpsc_cart_contents" );
	define( 'WPSC_TABLE_SUBMITED_FORM_DATA',     "{$wp_table_prefix}wpsc_submited_form_data" );
	define( 'WPSC_TABLE_SUBMITTED_FORM_DATA',    "{$wp_table_prefix}wpsc_submited_form_data" );
	define( 'WPSC_TABLE_CURRENCY_LIST',          "{$wp_table_prefix}wpsc_currency_list" );

	// These tables may be needed in some situations, but are not vital to
	// the core functionality of the plugin
	define( 'WPSC_TABLE_CLAIMED_STOCK',          "{$wp_table_prefix}wpsc_claimed_stock" );
	define( 'WPSC_TABLE_ALSO_BOUGHT',            "{$wp_table_prefix}wpsc_also_bought" );

	// This could be done using the posts table and the post meta table
	// but its a bit of a kludge.
	define( 'WPSC_TABLE_META',                   "{$wp_table_prefix}wpsc_meta" ); // only as long as wordpress doesn't ship with one.

	// This could be made to use the posts and post meta table.
	define( 'WPSC_TABLE_CHECKOUT_FORMS',         "{$wp_table_prefix}wpsc_checkout_forms" ); // dubious
	define( 'WPSC_TABLE_COUPON_CODES',           "{$wp_table_prefix}wpsc_coupon_codes" ); // ought to be fine

	// The tables below are marked for removal, the data in them is to be placed into other tables.
	define( 'WPSC_TABLE_CATEGORISATION_GROUPS',  "{$wp_table_prefix}wpsc_categorisation_groups" );
	define( 'WPSC_TABLE_DOWNLOAD_STATUS',        "{$wp_table_prefix}wpsc_download_status" );
	define( 'WPSC_TABLE_ITEM_CATEGORY_ASSOC',    "{$wp_table_prefix}wpsc_item_category_assoc" );
	define( 'WPSC_TABLE_PRODUCT_CATEGORIES',     "{$wp_table_prefix}wpsc_product_categories" );
	define( 'WPSC_TABLE_PRODUCT_FILES',          "{$wp_table_prefix}wpsc_product_files" );
	define( 'WPSC_TABLE_PRODUCT_IMAGES',         "{$wp_table_prefix}wpsc_product_images" );
	define( 'WPSC_TABLE_PRODUCT_LIST',           "{$wp_table_prefix}wpsc_product_list" );
	define( 'WPSC_TABLE_PRODUCT_ORDER',          "{$wp_table_prefix}wpsc_product_order" );
	define( 'WPSC_TABLE_PRODUCT_RATING',         "{$wp_table_prefix}wpsc_product_rating" );
	define( 'WPSC_TABLE_PRODUCT_VARIATIONS',     "{$wp_table_prefix}wpsc_product_variations" );
	define( 'WPSC_TABLE_PRODUCTMETA',            "{$wp_table_prefix}wpsc_productmeta" );
	define( 'WPSC_TABLE_VARIATION_ASSOC',        "{$wp_table_prefix}wpsc_variation_assoc" );
	define( 'WPSC_TABLE_VARIATION_PROPERTIES',   "{$wp_table_prefix}wpsc_variation_properties" );
	define( 'WPSC_TABLE_VARIATION_VALUES',       "{$wp_table_prefix}wpsc_variation_values" );
	define( 'WPSC_TABLE_VARIATION_VALUES_ASSOC', "{$wp_table_prefix}wpsc_variation_values_assoc" );
	define( 'WPSC_TABLE_VARIATION_COMBINATIONS', "{$wp_table_prefix}wpsc_variation_combinations" );
	define( 'WPSC_TABLE_REGION_TAX',             "{$wp_table_prefix}wpsc_region_tax" );

	define( 'WPSC_TABLE_CART_ITEM_META',         "{$wp_table_prefix}wpsc_cart_item_meta" );
	define( 'WPSC_TABLE_PURCHASE_META',          "{$wp_table_prefix}wpsc_purchase_meta" );

	define( 'WPSC_TABLE_VISITORS',         		 "{$wp_table_prefix}wpsc_visitors" );
	define( 'WPSC_TABLE_VISITOR_META',           "{$wp_table_prefix}wpsc_visitor_meta" );

}

/**
 * wpsc_core_constants_uploads()
 *
 * Set the Upload related constants
 */
function wpsc_core_constants_uploads() {
	$upload_path = '';
	$upload_url = '';
	$wp_upload_dir_data = wp_upload_dir();

	// Error Message
	if ( isset( $wp_upload_dir_data['error'] ) )
		$error_msg = $wp_upload_dir_data['error'];

	// Upload Path
	if ( isset( $wp_upload_dir_data['basedir'] ) )
		$upload_path = $wp_upload_dir_data['basedir'];

	// Upload DIR
	if ( isset( $wp_upload_dir_data['baseurl'] ) )
		$upload_url = set_url_scheme( $wp_upload_dir_data['baseurl'] );

	// Set DIR and URL strings
	$wpsc_upload_sub_dir = '/wpsc/';
	$wpsc_upload_dir     = $upload_path . $wpsc_upload_sub_dir;
	$wpsc_upload_url     = $upload_url  . $wpsc_upload_sub_dir;

	// Sub directories inside the WPEC folder
	$sub_dirs = array(
		'downloadables',
		'previews',
		'product_images',
		'product_images/thumbnails',
		'category_images',
		'user_uploads',
		'cache',
		'upgrades',
		'theme_backup',
		'themes'
	);

	// Upload DIR constants
	define( 'WPSC_UPLOAD_ERR', $error_msg );
	define( 'WPSC_UPLOAD_DIR', $wpsc_upload_dir );
	define( 'WPSC_UPLOAD_URL', $wpsc_upload_url );

	// Loop through sub directories
	foreach ( $sub_dirs as $sub_directory ) {
		$wpsc_paths[] = trailingslashit( $wpsc_upload_dir . $sub_directory );
		$wpsc_urls[]  = trailingslashit( $wpsc_upload_url . $sub_directory );
	}

	// Define paths
	define( 'WPSC_FILE_DIR',         $wpsc_paths[0] );
	define( 'WPSC_PREVIEW_DIR',      $wpsc_paths[1] );
	define( 'WPSC_IMAGE_DIR',        $wpsc_paths[2] );
	define( 'WPSC_THUMBNAIL_DIR',    $wpsc_paths[3] );
	define( 'WPSC_CATEGORY_DIR',     $wpsc_paths[4] );
	define( 'WPSC_USER_UPLOADS_DIR', $wpsc_paths[5] );
	define( 'WPSC_CACHE_DIR',        $wpsc_paths[6] );
	define( 'WPSC_UPGRADES_DIR',     $wpsc_paths[7] );
	define( 'WPSC_THEME_BACKUP_DIR', $wpsc_paths[8] );
	define( 'WPSC_OLD_THEMES_PATH',  $wpsc_paths[9] );

	// Define urls
	define( 'WPSC_FILE_URL',         $wpsc_urls[0] );
	define( 'WPSC_PREVIEW_URL',      $wpsc_urls[1] );
	define( 'WPSC_IMAGE_URL',        $wpsc_urls[2] );
	define( 'WPSC_THUMBNAIL_URL',    $wpsc_urls[3] );
	define( 'WPSC_CATEGORY_URL',     $wpsc_urls[4] );
	define( 'WPSC_USER_UPLOADS_URL', $wpsc_urls[5] );
	define( 'WPSC_CACHE_URL',        $wpsc_urls[6] );
	define( 'WPSC_UPGRADES_URL',     $wpsc_urls[7] );
	define( 'WPSC_THEME_BACKUP_URL', $wpsc_urls[8] );
	define( 'WPSC_OLD_THEMES_URL',   $wpsc_urls[9] );

}

/**
 * wpsc_core_setup_cart()
 *
 * Setup the cart
 */
function wpsc_core_setup_cart() {
	if ( 2 == get_option( 'cart_location' ) )
		add_filter( 'the_content', 'wpsc_shopping_cart', 14 );

	$GLOBALS['wpsc_cart'] = wpsc_get_customer_cart();
}

/**
 * _wpsc_action_init_shipping_method()
 *
 * The cart was setup at the beginning of the init sequence, and that's
 * too early to do shipping calculations because custom taxonomies, types
 * and other plugins may not have been initialized.  So we save the shipping
 * method initialization for the end of the init sequence.
 */
function _wpsc_action_init_shipping_method() {
	global $wpsc_cart;

	if ( ! is_object( $wpsc_cart ) ) {
		wpsc_core_setup_cart();
	}

	if ( empty( $wpsc_cart->selected_shipping_method ) ) {
		$wpsc_cart->get_shipping_method();
	}
}

// make sure that when we display the shopping cart page shipping quotes have been calculated
add_action( 'wpsc_before_shipping_of_shopping_cart', '_wpsc_action_init_shipping_method' );

/***
 * wpsc_core_setup_globals()
 *
 * Initialize the wpsc query vars, must be a global variable as we
 * cannot start it off from within the wp query object.
 * Starting it in wp_query results in intractable infinite loops in 3.0
 */
function wpsc_core_setup_globals() {
	global $wpsc_query_vars, $wpsc_cart, $wpec_ash;

	// Setup some globals
	$wpsc_query_vars = array();
    	require_once( WPSC_FILE_PATH . '/wpsc-includes/shipping.helper.php');
    	$wpec_ash = new ASH();
}
