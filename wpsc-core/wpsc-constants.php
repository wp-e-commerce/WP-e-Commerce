<?php
// Left Overs
$wpsc_currency_data = array();
$wpsc_title_data    = array();

/**
 * wpsc_core_load_session()
 *
 * Load up the WPEC session
 */
function wpsc_core_load_session() {

	if ( ! isset( $_SESSION ) )
		$_SESSION = null;

	if ( ( !is_array( $_SESSION ) ) xor ( ! isset( $_SESSION['nzshpcrt_cart'] ) ) xor ( !$_SESSION ) )
		session_start();

	return;
}

/**
 * wpsc_core_constants()
 *
 * The core WPEC constants necessary to start loading
 */
function wpsc_core_constants() {
	if(!defined('WPSC_URL'))
		define( 'WPSC_URL',       plugins_url( '', __FILE__ ) );
	// Define Plugin version
	define( 'WPSC_VERSION', '3.8.12' );
	define( 'WPSC_MINOR_VERSION', '74e9456712' );
	define( 'WPSC_PRESENTABLE_VERSION', '3.8.12' );
	define( 'WPSC_DB_VERSION', 4 );

	// Define Debug Variables for developers
	define( 'WPSC_DEBUG', false );
	define( 'WPSC_GATEWAY_DEBUG', false );

	// Images URL
	define( 'WPSC_CORE_IMAGES_URL',  WPSC_URL . '/wpsc-core/images' );
	define( 'WPSC_CORE_IMAGES_PATH', WPSC_FILE_PATH . '/wpsc-core/images' );

	// JS URL
	define( 'WPSC_CORE_JS_URL',  WPSC_URL . '/wpsc-core/js' );
	define( 'WPSC_CORE_JS_PATH', WPSC_FILE_PATH . '/wpsc-core/js' );

	// Require loading of deprecated functions for now. We will ween WPEC off
	// of this in future versions.
	define( 'WPEC_LOAD_DEPRECATED', true );

	define( 'WPSC_CUSTOMER_COOKIE', 'wpsc_customer_cookie_' . COOKIEHASH );
	if ( ! defined( 'WPSC_CUSTOMER_COOKIE_PATH' ) )
		define( 'WPSC_CUSTOMER_COOKIE_PATH', COOKIEPATH );

	if ( ! defined( 'WPSC_CUSTOMER_DATA_EXPIRATION' ) )
    	define( 'WPSC_CUSTOMER_DATA_EXPIRATION', 48 * 3600 );
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

	return (bool)$is_multisite;
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
	define( 'WPEC_TRANSIENT_THEME_PATH_PREFIX', 'wpsc_path_' );
	define( 'WPEC_TRANSIENT_THEME_URL_PREFIX', 'wpsc_url_' );

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
		$upload_url = $wp_upload_dir_data['baseurl'];

	// SSL Check for URL
	if ( is_ssl() )
		$upload_url = str_replace( 'http://', 'https://', $upload_url );

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

	// Themes folder locations
	define( 'WPSC_CORE_THEME_PATH', WPSC_FILE_PATH . '/wpsc-theme/' );
	define( 'WPSC_CORE_THEME_URL' , WPSC_URL       . '/wpsc-theme/' );

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
}

/**
 * wpsc_core_setup_cart()
 *
 * Setup the cart
 */
function wpsc_core_setup_cart() {
	if ( 2 == get_option( 'cart_location' ) )
		add_filter( 'the_content', 'wpsc_shopping_cart', 14 );

	$cart = maybe_unserialize( wpsc_get_customer_meta( 'cart' ) );

	if ( is_object( $cart ) && ! is_wp_error( $cart ) )
		$GLOBALS['wpsc_cart'] = $cart;
	else
		$GLOBALS['wpsc_cart'] = new wpsc_cart();
}

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
	$selected_theme  = get_option( 'wpsc_selected_theme' );

	// Pick selected theme or fallback to default
	if ( empty( $selected_theme ) || !file_exists( WPSC_THEMES_PATH ) )
		define( 'WPSC_THEME_DIR', 'default' );
	else
		define( 'WPSC_THEME_DIR', $selected_theme );

	// Include a file named after the current theme, if one exists
	if ( !empty( $selected_theme ) && file_exists( WPSC_THEMES_PATH . $selected_theme . '/' . $selected_theme . '.php' ) )
		include_once( WPSC_THEMES_PATH . $selected_theme . '/' . $selected_theme . '.php' );
    require_once( WPSC_FILE_PATH . '/wpsc-includes/shipping.helper.php');
    $wpec_ash = new ASH();
}
