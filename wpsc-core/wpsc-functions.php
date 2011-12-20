<?php

/**
 * WP eCommerce core functions
 *
 * These are core functions for wp-eCommerce
 * Things like registering custom post types and taxonomies, rewrite rules, wp_query modifications, link generation and some basic theme finding code is located here
 *
 * @package wp-e-commerce
 * @since 3.8
 */

add_filter( 'intermediate_image_sizes_advanced', 'wpsc_intermediate_image_sizes_advanced', 10, 1 );

function wpsc_intermediate_image_sizes_advanced($sizes){
	/* Legacy thumbnail sizes begin */
	$sizes['small-product-thumbnail']=array(
		"width" => get_option( 'product_image_width' ),
		"height" => get_option( 'product_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['medium-single-product']=array(
		"width" => get_option( 'single_view_image_width' ),
		"height" => get_option( 'single_view_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['featured-product-thumbnails']=array(
		"width" => 425,
		"height" => 215,
		"crop" => get_option( 'wpsc_crop_thumbnails', true )
	);
	$sizes['admin-product-thumbnails']=array(
		"width" => 38,
		"height" => 38,
		"crop" => get_option( 'wpsc_crop_thumbnails', true )
	);
	$sizes['product-thumbnails']=array(
		"width" => get_option( 'product_image_width' ),
		"height" => get_option( 'product_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	$sizes['gold-thumbnails']=array(
		"width" => get_option( 'wpsc_gallery_image_width' ),
		"height" => get_option( 'wpsc_gallery_image_height' ),
		"crop" => get_option( 'wpsc_crop_thumbnails', false )
	);
	/* Legacy thumbnail sizes end */
	return $sizes;
}

/**
 *
 * wpsc_core_load_thumbnail_sizes()
 *
 * Load up the WPEC core thumbnail sizes
 * @todo Remove hardcoded sizes
 */
function wpsc_core_load_thumbnail_sizes() {
	// Add image sizes for products
	add_image_size( 'product-thumbnails', get_option( 'product_image_width' ), get_option( 'product_image_height' ), get_option( 'wpsc_crop_thumbnails', false )  );
	add_image_size( 'gold-thumbnails',  get_option( 'wpsc_gallery_image_width' ), get_option( 'wpsc_gallery_image_height' ), get_option( 'wpsc_crop_thumbnails', false ) );
	add_image_size( 'admin-product-thumbnails', 38, 38, get_option( 'wpsc_crop_thumbnails', true )  );
	add_image_size( 'featured-product-thumbnails', 425, 215, get_option( 'wpsc_crop_thumbnails', true )  );
	add_image_size( 'small-product-thumbnail', get_option( 'product_image_width' ), get_option( 'product_image_height' ), get_option( 'wpsc_crop_thumbnails', false ) );
	add_image_size( 'medium-single-product', get_option( 'single_view_image_width' ), get_option( 'single_view_image_height' ), get_option( 'wpsc_crop_thumbnails', false) );
}
/**
 * wpsc_core_load_checkout_data()
 *
 *
 */

function wpsc_core_load_checkout_data() {
	$form_types = Array(
		"Text" => "text",
		"Email Address" => "email",
		"Street Address" => "address",
		"City" => "city",
		"Country" => "country",
		"Delivery Address" => "delivery_address",
		"Delivery City" => "delivery_city",
		"Delivery Country" => "delivery_country",
		"Text Area" => "textarea",
		"Heading" => "heading",
		"Select" => "select",
		"Radio Button" => "radio",
		"Checkbox" => "checkbox"
	);

	$form_types = apply_filters('wpsc_add_form_types' , $form_types);
	update_option('wpsc_checkout_form_fields', $form_types);

	$unique_names = Array(
		'billingfirstname',
		'billinglastname',
		'billingaddress',
		'billingcity',
		'billingstate',
		'billingcountry',
		'billingemail',
		'billingphone',
		'billingpostcode',
		'delivertoafriend' ,
		'shippingfirstname' ,
		'shippinglastname' ,
		'shippingaddress' ,
		'shippingcity' ,
		'shippingstate' ,
		'shippingcountry' ,
		'shippingpostcode'
	);

	$unique_names = apply_filters('wpsc_add_unique_names' , $unique_names);
	update_option('wpsc_checkout_unique_names', $unique_names);

}
/**
 * wpsc_core_load_purchase_log_statuses()
 *
 * @global array $wpsc_purchlog_statuses
 */
function wpsc_core_load_purchase_log_statuses() {
	global $wpsc_purchlog_statuses;

	$wpsc_purchlog_statuses = array(
		array(
			'internalname' => 'incomplete_sale',
			'label'        => __( 'Incomplete Sale', 'wpsc' ),
			'order'        => 1,
		),
		array(
			'internalname' => 'order_received',
			'label'        => __( 'Order Received', 'wpsc' ),
			'order'        => 2,
		),
		array(
			'internalname'   => 'accepted_payment',
			'label'          => __( 'Accepted Payment', 'wpsc' ),
			'is_transaction' => true,
			'order'          => 3,
		),
		array(
			'internalname'   => 'job_dispatched',
			'label'          => __( 'Job Dispatched', 'wpsc' ),
			'is_transaction' => true,
			'order'          => 4,
		),
		array(
			'internalname'   => 'closed_order',
			'label'          => __( 'Closed Order', 'wpsc' ),
			'is_transaction' => true,
			'order'          => 5,
		),
		array(
			'internalname'   => 'declined_payment',
			'label'          => __( 'Payment Declined', 'wpsc' ),
			'order'          => 6,
		),
		array(
			'internalname'   => 'refunded',
			'label'          => __( 'Refunded', 'wpsc' ),
			'order'          => 7,
		),
		array(
			'internalname'   => 'refund_pending',
			'label'          => __( 'Refund Pending', 'wpsc' ),
			'order'          => 8,
		),
	);
	$wpsc_purchlog_statuses = apply_filters('wpsc_set_purchlog_statuses',$wpsc_purchlog_statuses);
}

/***
 * wpsc_core_load_gateways()
 *
 * Gets the merchants from the merchants directory and eeds to search the
 * merchants directory for merchants, the code to do this starts here.
 *
 * @todo Come up with a better way to do this than a global $num value
 */
function wpsc_core_load_gateways() {
	global $nzshpcrt_gateways, $num, $wpsc_gateways,$gateway_checkout_form_fields;

	$gateway_directory      = WPSC_FILE_PATH . '/wpsc-merchants';
	$nzshpcrt_merchant_list = wpsc_list_dir( $gateway_directory );

	$num = 0;
	foreach ( $nzshpcrt_merchant_list as $nzshpcrt_merchant ) {
		if ( stristr( $nzshpcrt_merchant, '.php' ) ) {
			require( WPSC_FILE_PATH . '/wpsc-merchants/' . $nzshpcrt_merchant );
		}
		$num++;
	}
	unset( $nzshpcrt_merchant );

	$nzshpcrt_gateways = apply_filters( 'wpsc_merchants_modules', $nzshpcrt_gateways );
	uasort( $nzshpcrt_gateways, 'wpsc_merchant_sort' );

	// make an associative array of references to gateway data.
	$wpsc_gateways = array();
	foreach ( (array)$nzshpcrt_gateways as $key => $gateway )
		$wpsc_gateways[$gateway['internalname']] = &$nzshpcrt_gateways[$key];

	unset( $key, $gateway );

}

/***
 * wpsc_core_load_shipping_modules()
 *
 * Gets the shipping modules from the shipping directory and needs to search
 * the shipping directory for modules.
 */
function wpsc_core_load_shipping_modules() {
	global $wpsc_shipping_modules;

	$shipping_directory     = WPSC_FILE_PATH . '/wpsc-shipping';
	$nzshpcrt_shipping_list = wpsc_list_dir( $shipping_directory );

	foreach ( $nzshpcrt_shipping_list as $nzshpcrt_shipping ) {
		if ( stristr( $nzshpcrt_shipping, '.php' ) ) {
			require( WPSC_FILE_PATH . '/wpsc-shipping/' . $nzshpcrt_shipping );
		}
	}

	$wpsc_shipping_modules = apply_filters( 'wpsc_shipping_modules', $wpsc_shipping_modules );
}

/**
 * Update Notice
 *
 * Displays an update message below the auto-upgrade link in the WordPress admin
 * to notify users that they should check the upgrade information and changelog
 * before upgrading in case they need to may updates to their theme files.
 *
 * @package wp-e-commerce
 * @since 3.7.6.1
 */
function wpsc_update_notice() {
	$info_title = __( 'Please backup your website before updating!', 'wpsc' );
	$info_text =  __( 'Before updating please backup your database and files in case anything goes wrong.', 'wpsc' );
	echo '<div style="border-top:1px solid #CCC; margin-top:3px; padding-top:3px; font-weight:normal;"><strong style="color:#CC0000">' . strip_tags( $info_title ) . '</strong> ' . strip_tags( $info_text, '<br><a><strong><em><span>' ) . '</div>';
}

function wpsc_in_plugin_update_message() {
	add_action( 'in_plugin_update_message-' . WPSC_DIR_NAME . '/wp-shopping-cart.php', 'wpsc_update_notice' );
}

if ( is_admin() )
	add_action( 'init', 'wpsc_in_plugin_update_message' );


function wpsc_add_product_price_to_rss() {
	global $post;
	$price = get_post_meta( $post->ID, '_wpsc_price', true );
	// Only output a price tag if we have a price
	if ( $price )
		echo '<price>' . $price . '</price>';
}
add_action( 'rss2_item', 'wpsc_add_product_price_to_rss' );
add_action( 'rss_item', 'wpsc_add_product_price_to_rss' );
add_action( 'rdf_item', 'wpsc_add_product_price_to_rss' );

/**
 * wpsc_register_post_types()
 *
 * The meat of this whole operation, this is where we register our post types
 *
 * @global array $wpsc_page_titles
 */
function wpsc_register_post_types() {
	global $wpsc_page_titles;

	$catalog_slug                  = wpsc_get_option( 'catalog_slug' );
	$category_base_slug            = wpsc_get_option( 'category_base_slug' );
	$hierarchical_product_category = wpsc_get_option( 'hierarchical_product_category_url' );

	$labels = array(
		'name' => _x( 'Products', 'post type name', 'wpsc' ),
		'singular_name' => _x( 'Product', 'post type singular name', 'wpsc' ),
		'add_new' => _x( 'Add New', 'admin menu: add new product', 'wpsc' ),
		'add_new_item' => __('Add New Product', 'wpsc' ),
		'edit_item' => __('Edit Product', 'wpsc' ),
		'new_item' => __('New Product', 'wpsc' ),
		'view_item' => __('View Product', 'wpsc' ),
		'search_items' => __('Search Products', 'wpsc' ),
		'not_found' =>  __('No products found', 'wpsc' ),
		'not_found_in_trash' => __( 'No products found in Trash', 'wpsc' ),
		'parent_item_colon' => '',
		'menu_name' => __( 'Products', 'wpsc' )
	  );

	// Products
	$product_slug = $catalog_slug . '/' . wpsc_get_option( 'product_base_slug' );
	if ( wpsc_get_option( 'prefix_product_slug' ) )
		$product_slug .= '/%wpsc_product_category%';
	register_post_type( 'wpsc-product', array(
		'capability_type' => 'post',
		'has_archive' => $catalog_slug,
		'hierarchical' => true,
		'exclude_from_search' => false,
		'public' => true,
		'show_ui' => true,
		'show_in_nav_menus' => true,
		'menu_icon' => WPSC_CORE_IMAGES_URL . "/credit_cards.png",
		'labels' => $labels,
		'query_var' => true,
		'register_meta_box_cb' => 'wpsc_meta_boxes',
		'rewrite' => array(
			'slug' => $product_slug,
			'with_front' => false
		)
	) );

	// Purchasable product files
	register_post_type( 'wpsc-product-file', array(
		'capability_type' => 'post',
		'hierarchical' => false,
		'exclude_from_search' => true,
		'rewrite' => false
	) );

	// Product tags
	$labels = array( 'name' => _x( 'Product Tags', 'taxonomy general name', 'wpsc' ),
		'singular_name' => _x( 'Product Tag', 'taxonomy singular name', 'wpsc' ),
		'search_items' => __( 'Product Search Tags', 'wpsc' ),
		'all_items' => __( 'All Product Tags' , 'wpsc'),
		'edit_item' => __( 'Edit Tag', 'wpsc' ),
		'update_item' => __( 'Update Tag', 'wpsc' ),
		'add_new_item' => __( 'Add new Product Tag', 'wpsc' ),
		'new_item_name' => __( 'New Product Tag Name', 'wpsc' ) );

	register_taxonomy( 'product_tag', 'wpsc-product', array(
		'hierarchical' => false,
		'labels' => $labels,
		'rewrite' => array(
			'slug' => '/' . sanitize_title_with_dashes( _x( 'tagged', 'slug, part of url', 'wpsc' ) ),
			'with_front' => false )
	) );

	// Product categories, is heirarchical and can use permalinks
	$labels = array(
		'name' => _x( 'Product Categories', 'taxonomy general name', 'wpsc' ),
		'singular_name' => _x( 'Product Category', 'taxonomy singular name', 'wpsc' ),
		'search_items' => __( 'Search Product Categories', 'wpsc' ),
		'all_items' => __( 'All Product Categories', 'wpsc' ),
		'parent_item' => __( 'Parent Product Category', 'wpsc' ),
		'parent_item_colon' => __( 'Parent Product Category:', 'wpsc' ),
		'edit_item' => __( 'Edit Product Category', 'wpsc' ),
		'update_item' => __( 'Update Product Category', 'wpsc' ),
		'add_new_item' => __( 'Add New Product Category', 'wpsc' ),
		'new_item_name' => __( 'New Product Category Name', 'wpsc' ),
		'menu_name' => _x( 'Categories', 'taxonomy general name', 'wpsc' )
	);

	register_taxonomy( 'wpsc_product_category', 'wpsc-product', array(
		'hierarchical' => true,
		'rewrite' => array(
			'slug' => $catalog_slug . '/' . $category_base_slug,
			'with_front' => false,
			'hierarchical' => (bool) $hierarchical_product_category,
		),
		'labels' => $labels,
	) );
	$labels = array(
		'name' => _x( 'Variations', 'taxonomy general name', 'wpsc' ),
		'singular_name' => _x( 'Variation', 'taxonomy singular name', 'wpsc' ),
		'search_items' => __( 'Search Variations', 'wpsc' ),
		'all_items' => __( 'All Variations', 'wpsc' ),
		'parent_item' => __( 'Parent Variation', 'wpsc' ),
		'parent_item_colon' => __( 'Parent Variations:', 'wpsc' ),
		'edit_item' => __( 'Edit Variation', 'wpsc' ),
		'update_item' => __( 'Update Variation', 'wpsc' ),
		'add_new_item' => __( 'Add New Variation', 'wpsc' ),
		'new_item_name' => __( 'New Variation Name', 'wpsc' ),
	);

	// Product Variations, is internally heirarchical, externally, two separate types of items, one containing the other
	register_taxonomy( 'wpsc-variation', 'wpsc-product', array(
		'hierarchical' => true,
		'query_var' => 'variations',
		'rewrite' => false,
		'public' => true,
		'labels' => $labels
	) );
	$role = get_role( 'administrator' );
	$role->add_cap( 'read_wpsc-product' );
	$role->add_cap( 'read_wpsc-product-file' );
}
add_action( 'init', 'wpsc_register_post_types', 8 );

function wpsc_register_custom_page_rewrites() {
	$cart_slug               = wpsc_get_option( 'cart_page_slug' );
	$transaction_result_slug = wpsc_get_option( 'transaction_result_page_slug' );
	$customer_account_slug   = wpsc_get_option( 'customer_account_page_slug' );

	$regexp = "({$cart_slug}|{$transaction_result_slug}|{$customer_account_slug})(/.+?)?/?$";
	$rewrite = 'index.php?wpsc_page=$matches[1]&callback=$matches[2]';

	add_rewrite_rule( $regexp, $rewrite, 'top' );
}
add_action( 'init', 'wpsc_register_custom_page_rewrites', 1 );

/**
 * Post Updated Messages
 */
function wpsc_post_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['wpsc-product'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( __( 'Product updated. <a href="%s">View product</a>', 'wpsc' ), esc_url( get_permalink( $post_ID ) ) ),
		2  => __( 'Custom field updated.', 'wpsc' ),
		3  => __( 'Custom field deleted.', 'wpsc' ),
		4  => __( 'Product updated.', 'wpsc' ),
		// translators: %s: date and time of the revision
		5  => isset( $_GET['revision'] ) ? sprintf( __('Product restored to revision from %s', 'wpsc' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => sprintf( __( 'Product published. <a href="%s">View product</a>', 'wpsc' ), esc_url( get_permalink( $post_ID ) ) ),
		7  => __( 'Product saved.', 'wpsc' ),
		8  => sprintf( __( 'Product submitted. <a target="_blank" href="%s">Preview product</a>', 'wpsc' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		9  => sprintf( __( 'Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>', 'wpsc' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i', 'wpsc' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
		10 => sprintf( __( 'Product draft updated. <a target="_blank" href="%s">Preview product</a>', 'wpsc' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'wpsc_post_updated_messages' );

function wpsc_check_thumbnail_support() {
	if ( ! current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails' );
		add_action( 'init', 'wpsc_remove_post_type_thumbnail_support' );
	}

	$crop = wpsc_get_option( 'crop_thumbnails' );
	add_image_size( 'wpsc_product_single_thumbnail', get_option( 'single_view_image_width' ), get_option( 'single_view_image_height' ), $crop );
	add_image_size( 'wpsc_product_archive_thumbnail', get_option( 'product_image_width' ), get_option( 'product_image_height' ), $crop );
	add_image_size( 'wpsc_product_taxonomy_thumbnail', get_option( 'category_image_width' ), get_option( 'product_image_height' ), $crop );
}
add_action( 'after_setup_theme', 'wpsc_check_thumbnail_support', 99 );

function wpsc_remove_post_type_thumbnail_support() {
	remove_post_type_support( 'post', 'thumbnail' );
	remove_post_type_support( 'page', 'thumbnail' );
}

/**
 * This serializes the shopping cart variable as a backup in case the
 * unserialized one gets butchered by various things
 */
function wpsc_serialize_shopping_cart() {
	global $wpdb, $wpsc_start_time, $wpsc_cart;

	if ( is_object( $wpsc_cart ) )
		$wpsc_cart->errors = array( );

	$_SESSION['wpsc_cart'] = serialize( $wpsc_cart );

	return true;
}
add_action( 'shutdown', 'wpsc_serialize_shopping_cart' );

/**
 * add meta table where section for ordering by price
 *
 */
function wpsc_add_meta_table_where($where){
	global $wpdb;

	remove_filter( 'posts_where', 'wpsc_add_meta_table_where' );

	return $where . ' AND ' . $wpdb->postmeta . '.meta_key = "_wpsc_price"';
}

/**
 * add meta table join section for ordering by price
 *
 */
function wpsc_add_meta_table($join){
	global $wpdb;
	remove_filter( 'posts_join', 'wpsc_add_meta_table' );
	if(strpos($join, "INNER JOIN ON (".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id)") !== false){
		return  ' JOIN ' . $wpdb->postmeta . ' ON ' . $wpdb->posts. '.ID = ' . $wpdb->postmeta . '.post_id';
	}else{
		return $join;
	}
}

function wpsc_break_canonical_redirects( $redirect_url, $requested_url ) {
	global $wp_query;

	if ( ( isset( $wp_query->query_vars['products'] ) && ($wp_query->query_vars['products'] != '') ) || ( isset( $wp_query->query_vars['products'] ) && $wp_query->query_vars['products'] != 'wpsc_item') )
		return false;

	if ( stristr( $requested_url, $redirect_url ) )
		return false;

	return $redirect_url;
}

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
 * if the user is on a checkout page, force SSL if that option is so set
 */
function wpsc_force_ssl() {
	global $wp_query;
	if ( get_option( 'wpsc_force_ssl' ) && !is_ssl() && strpos( $wp_query->post->post_content, '[shoppingcart]' ) !== FALSE ) {
		$sslurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		header( 'Location: ' . $sslurl );
		echo 'Redirecting';
	}
}
add_action( 'get_header', 'wpsc_force_ssl' );


/**
 * Disable SSL validation for Curl. Added/removed on a per need basis, like so:
 *
 * add_filter('http_api_curl', 'wpsc_curl_ssl');
 * remove_filter('http_api_curl', 'wpsc_curl_ssl');
 *
 * @param resource $ch
 * @return resource $ch
 **/
function wpsc_curl_ssl($ch) {
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	return $ch;
}

/**
 * wpsc_add_https_to_page_url_options( $url )
 *
 * Forces SSL onto option URLs
 *
 * @param string $url
 * @return string
 */
function wpsc_add_https_to_page_url_options( $url ) {
	return str_replace( 'http://', 'https://', $url );
}
if ( is_ssl() ) {
	add_filter( 'option_product_list_url',  'wpsc_add_https_to_page_url_options' );
	add_filter( 'option_shopping_cart_url', 'wpsc_add_https_to_page_url_options' );
	add_filter( 'option_transact_url',      'wpsc_add_https_to_page_url_options' );
	add_filter( 'option_user_account_url',  'wpsc_add_https_to_page_url_options' );
}

function wpsc_cron() {
	foreach ( wp_get_schedules() as $cron => $schedule ) {
		if ( ! wp_next_scheduled( "wpsc_{$cron}_cron_task" ) )
			wp_schedule_event( time(), $cron, "wpsc_{$cron}_cron_task" );
	}
}

add_action( 'init', 'wpsc_cron' );

function wpsc_load_legacy_theme_engine() {
	$GLOBALS['wpsc_query_vars'] = array();

	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/functions.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/product-template.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/breadcrumbs.class.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/engine.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/ajax.php' );
	require_once( WPSC_CORE_THEME_PATH . 'functions/wpsc-transaction_results_functions.php' );
	require_once( WPSC_CORE_THEME_PATH . 'functions/wpsc-user_log_functions.php' );
	include_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/widgets/category_widget.php' );
	include_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/widgets/shopping_cart_widget.php' );
	include_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/widgets/donations_widget.php' );
	include_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/widgets/specials_widget.php' );
	include_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/widgets/latest_product_widget.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/shopping_cart_functions.php' );

	if ( is_admin() ) {
		require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/theming.class.php' );
		require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/settings.php' );
	}
	// Set page title array for important WPSC pages
	wpsc_core_load_page_titles();
}

/**
 * wpsc_user_enqueues products function,
 * enqueue all javascript and CSS for wp ecommerce
 */
function wpsc_enqueue_user_script_and_css() {
	global $wp_styles, $wpsc_theme_url, $wp_query;
	/**
	 * added by xiligroup.dev to be compatible with touchshop
	 */
	if ( has_filter( 'wpsc_enqueue_user_script_and_css' ) && apply_filters( 'wpsc_mobile_scripts_css_filters', false ) ) {
		do_action( 'wpsc_enqueue_user_script_and_css' );
	} else {
		/**
		 * end of added by xiligroup.dev to be compatible with touchshop
		 */
		$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
		$category_id = '';
		if (isset( $wp_query ) && isset( $wp_query->query_vars['taxonomy'] ) && ('wpsc_product_category' ==  $wp_query->query_vars['taxonomy'] ) || is_numeric( get_option( 'wpsc_default_category' ) )
		) {
			if ( isset($wp_query->query_vars['term']) && is_string( $wp_query->query_vars['term'] ) ) {
				$category_id = wpsc_get_category_id($wp_query->query_vars['term'], 'slug');
			} else {
				$category_id = get_option( 'wpsc_default_category' );
			}
		}

		$siteurl = get_option( 'siteurl' );

		$remote_protocol = is_ssl() ? 'https://' : 'http://';

		if( get_option( 'wpsc_share_this' ) == 1 )
		    wp_enqueue_script( 'sharethis', $remote_protocol . 'w.sharethis.com/button/buttons.js', array(), false, true );

		wp_enqueue_script( 'jQuery' );
		wp_enqueue_script( 'wp-e-commerce',               WPSC_CORE_JS_URL	. '/wp-e-commerce.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'infieldlabel',               WPSC_CORE_JS_URL	. '/jquery.infieldlabel.min.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-ajax-legacy',   WPSC_CORE_JS_URL	. '/ajax.js',                          false,             $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-dynamic', site_url( '/index.php?wpsc_user_dynamic_js=true' ), false,             $version_identifier );
		wp_localize_script( 'wp-e-commerce-dynamic', 'wpsc_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'livequery',                   WPSC_URL 			. '/wpsc-admin/js/jquery.livequery.js',   array( 'jquery' ), '1.0.3' );
		if( get_option( 'product_ratings' ) == 1 )
			wp_enqueue_script( 'jquery-rating',               WPSC_CORE_JS_URL 	. '/jquery.rating.js',                 array( 'jquery' ), $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-legacy',        WPSC_CORE_JS_URL 	. '/user.js',                          array( 'jquery' ), WPSC_VERSION . WPSC_MINOR_VERSION );
		if ( get_option( 'show_thumbnails_thickbox' ) == 1 ){
			$lightbox = get_option('wpsc_lightbox', 'thickbox');
			if( $lightbox == 'thickbox' ) {
				wp_enqueue_script( 'wpsc-thickbox',				WPSC_CORE_JS_URL . '/thickbox.js',                      array( 'jquery' ), 'Instinct_e-commerce' );
				wp_enqueue_style( 'wpsc-thickbox',				WPSC_CORE_JS_URL . '/thickbox.css',						false, $version_identifier, 'all' );
			} elseif( $lightbox == 'colorbox' ) {
				wp_enqueue_script( 'colorbox-min',				WPSC_CORE_JS_URL . '/jquery.colorbox-min.js',			array( 'jquery' ), 'Instinct_e-commerce' );
				wp_enqueue_script( 'wpsc_colorbox',				WPSC_CORE_JS_URL . '/wpsc_colorbox.js',					array( 'jquery', 'colorbox-min' ), 'Instinct_e-commerce' );
				wp_enqueue_style( 'wpsc-colorbox-css',				WPSC_CORE_JS_URL . '/wpsc_colorbox.css',			false, $version_identifier, 'all' );
			}
		}
		if( get_option( 'product_ratings' ) == 1 )
			wp_enqueue_style( 'wpsc-product-rater',           WPSC_CORE_JS_URL 	. '/product_rater.css',                                       false, $version_identifier, 'all' );
		wp_enqueue_style( 'wp-e-commerce-dynamic', site_url( "/index.php?wpsc_user_dynamic_css=true&category=$category_id" ), false, $version_identifier, 'all' );

	}


	if ( !defined( 'WPSC_MP3_MODULE_USES_HOOKS' ) && function_exists( 'listen_button' ) ) {

		function wpsc_legacy_add_mp3_preview( $product_id, &$product_data ) {
			global $wpdb;
			if ( function_exists( 'listen_button' ) ) {
				$file_data = $wpdb->get_row( "SELECT * FROM `" . WPSC_TABLE_PRODUCT_FILES . "` WHERE `id`='" . $product_data['file'] . "' LIMIT 1", ARRAY_A );
				if ( $file_data != null ) {
					echo listen_button( $file_data['idhash'], $file_data['id'] );
				}
			}
		}

		add_action( 'wpsc_product_before_description', 'wpsc_legacy_add_mp3_preview', 10, 2 );
	}
}
if ( !is_admin() )
	add_action( 'init', 'wpsc_enqueue_user_script_and_css' );

function wpsc_user_dynamic_css() {
	global $wpdb;
	header( 'Content-Type: text/css' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), (date( 'd' ) + 12 ), date( 'Y' ) ) ) . '' );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );

	$category_id = absint( $_GET['category'] );

	if ( !defined( 'WPSC_DISABLE_IMAGE_SIZE_FIXES' ) || (constant( 'WPSC_DISABLE_IMAGE_SIZE_FIXES' ) != true) ) {
		$thumbnail_width = get_option( 'product_image_width' );
		if ( $thumbnail_width <= 0 ) {
			$thumbnail_width = 96;
		}
		$thumbnail_height = get_option( 'product_image_height' );
		if ( $thumbnail_height <= 0 ) {
			$thumbnail_height = 96;
		}

		$single_thumbnail_width = get_option( 'single_view_image_width' );
		$single_thumbnail_height = get_option( 'single_view_image_height' );
		if ( $single_thumbnail_width <= 0 ) {
			$single_thumbnail_width = 128;
		}
		$category_height = get_option('category_image_height');
		$category_width = get_option('category_image_width');
?>

		/*
		* Default View Styling
		*/
		div.default_product_display div.textcol{
			margin-left: <?php echo $thumbnail_width + 10; ?>px !important;
			min-height: <?php echo $thumbnail_height; ?>px;
			_height: <?php echo $thumbnail_height; ?>px;
		}

		div.default_product_display  div.textcol div.imagecol{
			position:absolute;
			top:0px;
			left: 0px;
			margin-left: -<?php echo $thumbnail_width + 10; ?>px !important;
		}

		div.default_product_display  div.textcol div.imagecol a img {
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;
		}

		.wpsc_category_grid_item  {
			display:block;
			float:left;
			width: <?php echo $category_width; ?>px;
			height: <?php echo $category_height; ?>px;
		}
		.wpsc_category_grid_item  span{
			position:relative;
			top:<?php echo ($thumbnail_height - 2)/9; ?>px;
		}
		div.default_product_display div.item_no_image a  {
			width: <?php echo $thumbnail_width - 2; ?>px;
		}

		div.default_product_display .imagecol img.no-image, #content div.default_product_display .imagecol img.no-image {
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;
        }

		/*
		* Grid View Styling
		*/
		div.product_grid_display div.item_no_image  {
			width: <?php echo $thumbnail_width - 2; ?>px;
			height: <?php echo $thumbnail_height - 2; ?>px;
		}
		div.product_grid_display div.item_no_image a  {
			width: <?php echo $thumbnail_width - 2; ?>px;
		}

			.product_grid_display .product_grid_item  {
			width: <?php echo $thumbnail_width; ?>px;
		}
		.product_grid_display .product_grid_item img.no-image, #content .product_grid_display .product_grid_item img.no-image {
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;
        }
        <?php if(get_option('show_images_only') == 1): ?>
        .product_grid_display .product_grid_item  {
        	min-height:0 !important;
			width: <?php echo $thumbnail_width; ?>px;
			height: <?php echo $thumbnail_height; ?>px;

		}
		<?php endif; ?>



		/*
		* Single View Styling
		*/

		div.single_product_display div.item_no_image  {
			width: <?php echo $single_thumbnail_width - 2; ?>px;
			height: <?php echo $single_thumbnail_height - 2; ?>px;
		}
		div.single_product_display div.item_no_image a  {
			width: <?php echo $single_thumbnail_width - 2; ?>px;
		}

		div.single_product_display div.textcol{
			margin-left: <?php echo $single_thumbnail_width + 10; ?>px !important;
			min-height: <?php echo $single_thumbnail_height; ?>px;
			_height: <?php echo $single_thumbnail_height; ?>px;
		}


		div.single_product_display  div.textcol div.imagecol{
			position:absolute;

			margin-left: -<?php echo $single_thumbnail_width + 10; ?>px !important;
		}

		div.single_product_display  div.textcol div.imagecol a img {
			width: <?php echo $single_thumbnail_width; ?>px;
			height: <?php echo $single_thumbnail_height; ?>px;
		}

<?php
if (isset($product_image_size_list)) {
		foreach ( (array)$product_image_size_list as $product_image_sizes ) {
			$individual_thumbnail_height = $product_image_sizes['height'];
			$individual_thumbnail_width = $product_image_sizes['width'];
			$product_id = $product_image_sizes['id'];
			if ( $individual_thumbnail_height > $thumbnail_height ) {
				echo "		div.default_product_display.product_view_$product_id div.textcol{\n\r";
				echo "			min-height: " . ($individual_thumbnail_height + 10) . "px !important;\n\r";
				echo "			_height: " . ($individual_thumbnail_height + 10) . "px !important;\n\r";
				echo "		}\n\r";
			}

			if ( $individual_thumbnail_width > $thumbnail_width ) {
				echo "		div.default_product_display.product_view_$product_id div.textcol{\n\r";
				echo "			margin-left: " . ($individual_thumbnail_width + 10) . "px !important;\n\r";
				echo "		}\n\r";

				echo "		div.default_product_display.product_view_$product_id  div.textcol div.imagecol{\n\r";
				echo "			position:absolute;\n\r";
				echo "			top:0px;\n\r";
				echo "			left: 0px;\n\r";
				echo "			margin-left: -" . ($individual_thumbnail_width + 10) . "px !important;\n\r";
				echo "		}\n\r";
			}

			if ( ($individual_thumbnail_width > $thumbnail_width) || ($individual_thumbnail_height > $thumbnail_height) ) {
				echo "		div.default_product_display.product_view_$product_id  div.textcol div.imagecol a img{\n\r";
				echo "			width: " . $individual_thumbnail_width . "px;\n\r";
				echo "			height: " . $individual_thumbnail_height . "px;\n\r";
				echo "		}\n\r";
			}
		}
	}
	exit();
}
	if ( (isset($_GET['brand']) && is_numeric( $_GET['brand'] )) || (get_option( 'show_categorybrands' ) == 3) ) {
		$brandstate = 'block';
		$categorystate = 'none';
	} else {
		$brandstate = 'none';
		$categorystate = 'block';
	}
?>
	div#categorydisplay{
		display: <?php echo $categorystate; ?>;
	}

	div#branddisplay{
		display: <?php echo $brandstate; ?>;
	}
<?php
	exit();
}
if ( isset( $_GET['wpsc_user_dynamic_css'] ) && ($_GET['wpsc_user_dynamic_css'] == 'true') )
	add_action( "init", 'wpsc_user_dynamic_css' );

function wpsc_user_dynamic_js() {
	header( 'Content-Type: text/javascript' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), (date( 'd' ) + 12 ), date( 'Y' ) ) ) . '' );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );
	$siteurl = get_option( 'siteurl' );
?>
		jQuery.noConflict();

		/* base url */
		var base_url = "<?php echo $siteurl; ?>";
		var WPSC_URL = "<?php echo WPSC_URL; ?>";
		var WPSC_IMAGE_URL = "<?php echo WPSC_IMAGE_URL; ?>";
		var WPSC_DIR_NAME = "<?php echo WPSC_DIR_NAME; ?>";
		var WPSC_CORE_IMAGES_URL = "<?php echo WPSC_CORE_IMAGES_URL; ?>";

		/* LightBox Configuration start*/
		var fileLoadingImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/loading.gif";
		var fileBottomNavCloseImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/closelabel.gif";
		var fileThickboxLoadingImage = "<?php echo WPSC_CORE_IMAGES_URL; ?>/loadingAnimation.gif";
		var resizeSpeed = 9;  // controls the speed of the image resizing (1=slowest and 10=fastest)
		var borderSize = 10;  //if you adjust the padding in the CSS, you will need to update this variable
<?php
	exit();
}
if ( isset( $_GET['wpsc_user_dynamic_js'] ) && ($_GET['wpsc_user_dynamic_js'] == 'true') )
	add_action( "init", 'wpsc_user_dynamic_js' );

/**
 * If there are published pages using legacy shortcodes to display shop content,
 * that means the site is still using legacy theme engine.
 *
 * This function checks whether legacy theme engine is still being used or not.
 *
 * The number of pages using legacy shortcodes will be cached inside an option
 * called 'wpsc_legacy_theme_engine_page_count'. When any page is updated / trashed / created,
 * the option will be wiped.
 *
 * @return boolean
 * @since 4.0
 */
function wpsc_is_legacy_theme_engine_active() {
	global $wpdb;

	$count = get_option( 'wpsc_legacy_theme_engine_page_count' );

	if ( $count === false ) {
		$sql = "
			SELECT COUNT(*)
			FROM {$wpdb->posts}
			WHERE
				post_type = 'page'
				AND post_status = 'publish'
				AND (
					post_content LIKE '[productspage]'
					OR post_content LIKE '[shoppingcart]'
					OR post_content LIKE '[transactionresults]'
					OR post_content LIKE '[userlog]'
				)
			";

		$count = $wpdb->get_var( $sql );
		update_option( 'wpsc_legacy_theme_engine_page_count', $count );
	}

	return ( $count > 0 );
}

/**
 * Delete the cached option for detecting legacy theme.
 *
 * This will be triggered when a page is trashed / updated / created
 *
 * There is no need to trigger this function when a page is
 * permanently deleted though, because it has to be trashed
 * first anyways.
 *
 * @param int    $id   ID of the post
 * @param object $post post object
 * @since 4.0
 */
function wpsc_update_legacy_theme_status( $id, $post ) {
	if ( $post->post_type == 'page' )
		delete_option( 'wpsc_legacy_theme_engine_page_count' );
}
add_action( 'save_post', 'wpsc_update_legacy_theme_status', 10, 2 );

function wpsc_load_theme_engine() {
	require_once( WPSC_FILE_PATH . '/wpsc-includes/theme.functions.php'            );
	require_once( WPSC_FILE_PATH . '/wpsc-includes/template-tags.functions.php'    );
	require_once( WPSC_FILE_PATH . '/wpsc-includes/conditional-tags.functions.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-includes/theme-action.functions.php'     );
}