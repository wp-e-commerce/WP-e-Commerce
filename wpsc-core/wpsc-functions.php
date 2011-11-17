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

add_filter( 'term_name', 'wpsc_term_list_levels', 10, 2 );

/**
 * When doing variation and product category drag&drop sort, we want to restrict
 * drag & drop to the same level (children of a category cannot be dropped under
 * another parent category). To do this, we need to be able to specify depth level
 * of the term items being output to the term list table.
 *
 * Unfortunately, there's no way we can do that with WP hooks. So this is a work around.
 * This function is added to "term_name" filter. Its job is to record the depth level of
 * each terms into a global variable. This global variable will later be output to JS in
 * wpsc_print_term_list_levels_script().
 *
 * Not an elegant solution, but it works.
 *
 * @param  string $term_name
 * @param  object $term
 * @return string
 */
function wpsc_term_list_levels( $term_name, $term ) {
	global $wp_list_table, $wpsc_term_list_levels;

	$screen = get_current_screen();
	if ( ! in_array( $screen->id, array( 'edit-wpsc-variation', 'edit-wpsc_product_category' ) ) )
		return $term_name;

	if ( ! isset( $wpsc_term_list_levels ) )
		$wpsc_term_list_levels = array();

	$wpsc_term_list_levels[$term->term_id] = $wp_list_table->level;

	return $term_name;
}

add_filter( 'admin_footer', 'wpsc_print_term_list_levels_script' );

/**
 * Print $wpsc_term_list_levels as JS.
 * @see wpsc_term_list_levels()
 * @return void
 */
function wpsc_print_term_list_levels_script() {
	global $wpsc_term_list_levels;
	$screen = get_current_screen();
	if ( ! in_array( $screen->id, array( 'edit-wpsc-variation', 'edit-wpsc_product_category' ) ) )
		return;

	?>
	<script type="text/javascript">
	//<![CDATA[
	var WPSC_Term_List_Levels = <?php echo json_encode( $wpsc_term_list_levels ); ?>;
	//]]>
	</script>
	<?php
}

add_filter( 'intermediate_image_sizes_advanced', 'wpsc_intermediate_image_sizes_advanced', 10, 1 );

function wpsc_intermediate_image_sizes_advanced($sizes){
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
	register_post_type( 'wpsc-product', array(
		'capability_type' => 'post',
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
			'slug' => $wpsc_page_titles['products'] . '/%wpsc_product_category%',
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
			'slug' => $wpsc_page_titles['products'],
			'with_front' => false,
			'hierarchical' => (bool) get_option( 'product_category_hierarchical_url', 0 ),
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
	if ( !current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails' );
		add_action( 'init', 'wpsc_remove_post_type_thumbnail_support' );
	}
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
	require_once( WPSC_FILE_PATH . '/wpsc-legacy/theme-engine/functions.php' );
	// Set page title array for important WPSC pages
	wpsc_core_load_page_titles();
}
?>