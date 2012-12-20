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
	if ( ! is_object( $screen ) || ! in_array( $screen->id, array( 'edit-wpsc-variation', 'edit-wpsc_product_category' ) ) )
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
	$form_types = array(
		__( 'Text', 'wpsc' )             => 'text',
		__( 'Email Address', 'wpsc' )    => 'email',
		__( 'Street Address', 'wpsc' )   => 'address',
		__( 'City', 'wpsc' )             => 'city',
		__( 'Country', 'wpsc' )          => 'country',
		__( 'Delivery Address', 'wpsc' ) => 'delivery_address',
		__( 'Delivery City', 'wpsc' )    => 'delivery_city',
		__( 'Delivery Country', 'wpsc' ) => 'delivery_country',
		__( 'Text Area', 'wpsc' )        => 'textarea',
		__( 'Heading', 'wpsc' )          => 'heading',
		__( 'Select', 'wpsc' )           => 'select',
		__( 'Radio Button', 'wpsc' )     => 'radio',
		__( 'Checkbox', 'wpsc' )         => 'checkbox'
	);

	$form_types = apply_filters( 'wpsc_add_form_types', $form_types );
	update_option( 'wpsc_checkout_form_fields', $form_types );

	$unique_names = array(
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

	$unique_names = apply_filters( 'wpsc_add_unique_names' , $unique_names );
	update_option( 'wpsc_checkout_unique_names', $unique_names );

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
	);
	$wpsc_purchlog_statuses = apply_filters('wpsc_set_purchlog_statuses',$wpsc_purchlog_statuses);
}

/**
 * wpsc_core_load_page_titles()
 *
 * Load the WPEC page titles
 *
 * @global array $wpsc_page_titles
 */
function wpsc_core_load_page_titles() {
	global $wpsc_page_titles;
	$wpsc_page_titles = wpsc_get_page_post_names();
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
	global $wpsc_shipping_modules, $wpsc_cart;

	$shipping_directory     = WPSC_FILE_PATH . '/wpsc-shipping';
	$nzshpcrt_shipping_list = wpsc_list_dir( $shipping_directory );

	foreach ( $nzshpcrt_shipping_list as $nzshpcrt_shipping ) {
		if ( stristr( $nzshpcrt_shipping, '.php' ) ) {
			require( WPSC_FILE_PATH . '/wpsc-shipping/' . $nzshpcrt_shipping );
		}
	}

	$wpsc_shipping_modules = apply_filters( 'wpsc_shipping_modules', $wpsc_shipping_modules );

	if ( ! get_option( 'do_not_use_shipping' ) && empty( $wpsc_cart->selected_shipping_method ) )
		$wpsc_cart->get_shipping_method();
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

	// Products
    $labels = array(
		'name'               => _x( 'Products'                  , 'post type name'             , 'wpsc' ),
		'singular_name'      => _x( 'Product'                   , 'post type singular name'    , 'wpsc' ),
		'add_new'            => _x( 'Add New'                   , 'admin menu: add new product', 'wpsc' ),
		'add_new_item'       => __( 'Add New Product'           , 'wpsc' ),
		'edit_item'          => __( 'Edit Product'              , 'wpsc' ),
		'new_item'           => __( 'New Product'               , 'wpsc' ),
		'view_item'          => __( 'View Product'              , 'wpsc' ),
		'search_items'       => __( 'Search Products'           , 'wpsc' ),
		'not_found'          => __( 'No products found'         , 'wpsc' ),
		'not_found_in_trash' => __( 'No products found in Trash', 'wpsc' ),
		'menu_name'          => __( 'Products'                  , 'wpsc' ),
		'parent_item_colon'  => '',
      );
    $args = array(
		'capability_type'      => 'post',
		'supports'             => array( 'title', 'editor', 'thumbnail' ),
		'hierarchical'         => true,
		'exclude_from_search'  => false,
		'public'               => true,
		'show_ui'              => true,
		'show_in_nav_menus'    => true,
		'menu_icon'            => WPSC_CORE_IMAGES_URL . "/credit_cards.png",
		'labels'               => $labels,
		'query_var'            => true,
		'register_meta_box_cb' => 'wpsc_meta_boxes',
		'rewrite'              => array(
			'slug'       => str_replace( basename( home_url() ), '', $wpsc_page_titles['products'] ) . '/%wpsc_product_category%',
			'with_front' => false
		)
	);
	$args = apply_filters( 'wpsc_register_post_types_products_args', $args );
	register_post_type( 'wpsc-product', $args );

	// Purchasable product files
	$args = array(
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'hierarchical'        => false,
		'exclude_from_search' => true,
		'rewrite'             => false,
		'labels'              => array(
			'name'          => __( 'Product Files', 'wpsc' ),
			'singular_name' => __( 'Product File' , 'wpsc' ),
		),
	);
	$args = apply_filters( 'wpsc_register_post_types_product_files_args', $args );
	register_post_type( 'wpsc-product-file', $args );

	// Product tags
	$labels = array(
		'name'          => _x( 'Product Tags'        , 'taxonomy general name' , 'wpsc' ),
		'singular_name' => _x( 'Product Tag'         , 'taxonomy singular name', 'wpsc' ),
		'search_items'  => __( 'Product Search Tags' , 'wpsc' ),
		'all_items'     => __( 'All Product Tags'    , 'wpsc' ),
		'edit_item'     => __( 'Edit Tag'            , 'wpsc' ),
		'update_item'   => __( 'Update Tag'          , 'wpsc' ),
		'add_new_item'  => __( 'Add new Product Tag' , 'wpsc' ),
		'new_item_name' => __( 'New Product Tag Name', 'wpsc' ),
	);

	$args = array(
		'hierarchical' => false,
		'labels' => $labels,
		'rewrite' => array(
			'slug' => '/' . sanitize_title_with_dashes( _x( 'tagged', 'slug, part of url', 'wpsc' ) ),
			'with_front' => false )
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_tag_args', $args );
	register_taxonomy( 'product_tag', 'wpsc-product', $args );

	// Product categories, is heirarchical and can use permalinks
	$labels = array(
		'name'              => _x( 'Product Categories'       , 'taxonomy general name' , 'wpsc' ),
		'singular_name'     => _x( 'Product Category'         , 'taxonomy singular name', 'wpsc' ),
		'search_items'      => __( 'Search Product Categories', 'wpsc' ),
		'all_items'         => __( 'All Product Categories'   , 'wpsc' ),
		'parent_item'       => __( 'Parent Product Category'  , 'wpsc' ),
		'parent_item_colon' => __( 'Parent Product Category:' , 'wpsc' ),
		'edit_item'         => __( 'Edit Product Category'    , 'wpsc' ),
		'update_item'       => __( 'Update Product Category'  , 'wpsc' ),
		'add_new_item'      => __( 'Add New Product Category' , 'wpsc' ),
		'new_item_name'     => __( 'New Product Category Name', 'wpsc' ),
		'menu_name'         => _x( 'Categories'               , 'taxonomy general name', 'wpsc' ),
	);
	$args = array(
		'labels'       => $labels,
		'hierarchical' => true,
		'rewrite'      => array(
			'slug'         => str_replace( basename( home_url() ), '', $wpsc_page_titles['products'] ),
			'with_front'   => false,
			'hierarchical' => (bool) get_option( 'product_category_hierarchical_url', 0 ),
		),
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_category_args', $args );

	register_taxonomy( 'wpsc_product_category', 'wpsc-product', $args );
	$labels = array(
		'name'              => _x( 'Variations'        , 'taxonomy general name' , 'wpsc' ),
		'singular_name'     => _x( 'Variation'         , 'taxonomy singular name', 'wpsc' ),
		'search_items'      => __( 'Search Variations' , 'wpsc' ),
		'all_items'         => __( 'All Variations'    , 'wpsc' ),
		'parent_item'       => __( 'Parent Variation'  , 'wpsc' ),
		'parent_item_colon' => __( 'Parent Variations:', 'wpsc' ),
		'edit_item'         => __( 'Edit Variation'    , 'wpsc' ),
		'update_item'       => __( 'Update Variation'  , 'wpsc' ),
		'add_new_item'      => __( 'Add New Variation' , 'wpsc' ),
		'new_item_name'     => __( 'New Variation Name', 'wpsc' ),
	);
	$args = array(
		'hierarchical' => true,
		'query_var'    => 'variations',
		'rewrite'      => false,
		'public'       => true,
		'labels'       => $labels
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_variation_args', $args );
	// Product Variations, is internally heirarchical, externally, two separate types of items, one containing the other
	register_taxonomy( 'wpsc-variation', 'wpsc-product', $args );

	do_action( 'wpsc_register_post_types_after' );
	do_action( 'wpsc_register_taxonomies_after' );
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

function wpsc_filter_request( $q ) {
	if ( empty( $q['wpsc-product'] ) )
		return $q;

	$components = explode( '/', $q['wpsc-product'] );
	$end_node = array_pop( $components );
	$parent_node = array_pop( $components );

	$posts = get_posts( array(
		'post_type' => 'wpsc-product',
		'name' => $end_node,
	) );

	if ( ! empty( $posts ) ) {
		$q['wpsc-product'] = $q['name'] = $end_node;
		if ( !empty( $parent_node ) )
			$q['wpsc_product_category'] = $parent_node;
	} else {
		$q['wpsc_product_category'] = $end_node;
		unset( $q['name'] );
		unset( $q['wpsc-product'] );
	}
	return $q;
}

if ( get_option( 'product_category_hierarchical_url' ) )
	add_filter( 'request', 'wpsc_filter_request' );

/**
 * This serializes the shopping cart variable as a backup in case the
 * unserialized one gets butchered by various things
 */
function wpsc_serialize_shopping_cart() {
	global $wpdb, $wpsc_start_time, $wpsc_cart;

	// avoid flooding transients with bots hitting feeds
	if ( is_feed() ) {
		wpsc_delete_all_customer_meta();
		return;
	}

	if ( is_object( $wpsc_cart ) )
		$wpsc_cart->errors = array( );

	// need to prevent set_cookie from being called at this stage in case the user just logged out
	// because by now, some output must have been printed out
	$customer_id = wpsc_get_current_customer_id();
	if ( $customer_id )
		wpsc_update_customer_meta( 'cart', serialize( $wpsc_cart ) );

	return true;
}
add_action( 'shutdown', 'wpsc_serialize_shopping_cart' );

add_filter( 'request', 'wpsc_filter_query_request' );

/**
 * Fixes for some inconsistencies about $wp_query when viewing WPEC pages.
 *
 * Causes the following URLs to work (with pagination enabled):
 *
 * /products-page/ (product listing)
 * /products-page/car-audio/ (existing product category)
 * /products-page/car-audio/page/2/ (existing product category, page 2)
 * /products-page/page/2/  (product listing, page 2)
 * /products-page/checkout/  (existing built-in sub page)
 * /products-page/anotherpage/  (another sub page that may exist)
 *
 * @param string $q Query String
 */
function wpsc_filter_query_request( $args ) {
	global $wpsc_page_titles;
	if ( is_admin() )
		return $args;

	$is_sub_page =    ! empty( $args['wpsc_product_category'] )
	               &&   'page' != $args['wpsc_product_category']
	               && ! term_exists( $args['wpsc_product_category'], 'wpsc_product_category' );

	// Make sure no 404 error is thrown for any sub pages of products-page
	if ( $is_sub_page ) {
		// Probably requesting a page that is a sub page of products page
		$pagename = "{$wpsc_page_titles['products']}/{$args['wpsc_product_category']}";
		if ( isset($args['name']) ) {
			$pagename .= "/{$args['name']}";
		}
		$args = array();
		$args['pagename'] = $pagename;
	}

	// When product page is set to display all products or a category, and pagination is enabled, $wp_query is messed up
	// and is_home() is true. This fixes that.
	$needs_pagination_fix =      isset( $args['post_type'] )
	                        && ! empty( $args['wpsc_product_category'] )
	                        &&   'wpsc-product' == $args['post_type']
	                        && ! empty( $args['wpsc-product'] )
	                        &&   'page' == $args['wpsc_product_category'];
	if ( $needs_pagination_fix ) {
		$default_category = get_option( 'wpsc_default_category' );
		if ( $default_category == 'all' || $default_category != 'list' ) {
			$page = $args['wpsc-product'];
			$args = array();
			$args['pagename'] = "{$wpsc_page_titles['products']}";
			$args['page'] = $page;
		}
	}
	return $args;
}

function _wpsc_menu_exists( $args ) {
	$args = (object) $args;
	// Get the nav menu based on the requested menu
	$menu = wp_get_nav_menu_object( $args->menu );

	// Get the nav menu based on the theme_location
	if ( ! $menu && $args->theme_location && ( $locations = get_nav_menu_locations() ) && isset( $locations[ $args->theme_location ] ) )
		$menu = wp_get_nav_menu_object( $locations[ $args->theme_location ] );

	// get the first menu that has items if we still can't find a menu
	if ( ! $menu && !$args->theme_location ) {
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu_maybe ) {
			if ( $menu_items = wp_get_nav_menu_items($menu_maybe->term_id) ) {
				$menu = $menu_maybe;
				break;
			}
		}
	}

	return (bool) $menu;
}

function _wpsc_switch_the_query( $stuff = '' ) {
	global $wp_query, $wpsc_query;
	list( $wp_query, $wpsc_query ) = array( $wpsc_query, $wp_query );
	return $stuff;
}

/**
 * Switch $wp_query and $wpsc_query when outputting the navigation menu, but only if we're on a product
 * category page.
 *
 * We need to do this because the function _wp_menu_item_classes_by_context(), which generates classes
 * for menu items, depends on $wp_query. As a result, without this fix, when viewing a product category
 * page, the corresponding product category menu item will not be highlighted.
 *
 * Because there are no action hooks in wp_nav_menu(), we have to use two filters that are applied at
 * the beginning and the end of the function.
 *
 * @param mixed $stuff
 * @return mixed
 */
function wpsc_switch_the_query( $args ) {
	global $wp_query, $wpsc_query;
	$qv = $wpsc_query->query_vars;
	if ( ! empty( $qv['wpsc_product_category'] ) && ! empty( $qv['taxonomy'] ) && ! empty( $qv['term'] ) && ! is_single() && _wpsc_menu_exists( $args ) ) {
		_wpsc_switch_the_query();
		add_filter( 'wp_nav_menu', '_wpsc_switch_the_query', 99 );
	}
	return $args;
}

// switch $wp_query and $wpsc_query at the beginning and the end of wp_nav_menu()
add_filter( 'wp_nav_menu_args', 'wpsc_switch_the_query', 99 );

function _wpsc_pre_get_posts_reset_taxonomy_globals( $query ) {
	global $wp_the_query;

	if ( is_admin() || $query !== $wp_the_query )
		return;

	if ( ! $query->get( 'page' ) && ! $query->get( 'paged' ) )
		return;

	if ( ! get_option( 'use_pagination' ) )
		return;

	$query->set( 'posts_per_page', get_option( 'wpsc_products_per_page' ) );

	$post_type_object = get_post_type_object( 'wpsc-product' );

	if ( current_user_can( $post_type_object->cap->edit_posts ) )
		$query->set( 'post_status', 'private,draft,pending,publish' );
	else
		$query->set( 'post_status', 'publish' );
}
add_action( 'pre_get_posts', '_wpsc_pre_get_posts_reset_taxonomy_globals', 1 );

/**
 * wpsc_start_the_query
 */
function wpsc_start_the_query() {
	global $wpsc_page_titles, $wp_query, $wpsc_query, $wpsc_query_vars;

	$is_404 = false;
	if ( null == $wpsc_query ) {
		if( ( $wp_query->is_404 && !empty($wp_query->query_vars['paged']) ) || (isset( $wp_query->query['pagename']) && strpos( $wp_query->query['pagename'] , $wpsc_page_titles['products'] ) !== false ) && !isset($wp_query->post)){
			global $post;
			$is_404 = true;
			if( !isset( $wp_query->query_vars['wpsc_product_category'] ) && ! isset( $wp_query->query_vars['product_tag'] ) )
				$wp_query = new WP_Query('post_type=wpsc-product&name='.$wp_query->query_vars['name']);

			if(isset($wp_query->post->ID))
				$post = $wp_query->post;
			else
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['name'];
		}
		if ( count( $wpsc_query_vars ) <= 1 ) {
			$post_type_object = get_post_type_object( 'wpsc-product' );
			$wpsc_query_vars = array(
				'post_status' => current_user_can( $post_type_object->cap->edit_posts ) ? 'private,draft,pending,publish' : 'publish',
				'post_parent' => 0,
				'order'       => apply_filters( 'wpsc_product_order', get_option( 'wpsc_product_order', 'ASC' ) )
			);
			if($wp_query->query_vars['preview'])
				$wpsc_query_vars['post_status'] = 'any';

			if( isset( $_GET['product_order'] ) )
				$wpsc_query_vars['order'] = $_GET['product_order'];

			if(isset($wp_query->query_vars['product_tag'])){
				$wpsc_query_vars['product_tag'] = $wp_query->query_vars['product_tag'];
				$wpsc_query_vars['taxonomy'] = get_query_var( 'taxonomy' );
				$wpsc_query_vars['term'] = get_query_var( 'term' );
			}elseif( isset($wp_query->query_vars['wpsc_product_category']) ){
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
				$wpsc_query_vars['taxonomy'] = get_query_var( 'taxonomy' );
				$wpsc_query_vars['term'] = get_query_var( 'term' );
			}else{
				$wpsc_query_vars['post_type'] = 'wpsc-product';
				$wpsc_query_vars['pagename'] = 'products-page';
			}
			if(1 == get_option('use_pagination')){
				$wpsc_query_vars['nopaging'] = false;

				$wpsc_query_vars['posts_per_page'] = get_option('wpsc_products_per_page');

				$wpsc_query_vars['paged'] = get_query_var('paged');
				if(isset($wpsc_query_vars['paged']) && empty($wpsc_query_vars['paged'])){
					$wpsc_query_vars['paged'] = get_query_var('page');

				}

			}

			$orderby = ( isset( $_GET['product_order'] ) ) ? 'title' : null;
			$wpsc_query_vars = array_merge( $wpsc_query_vars, wpsc_product_sort_order_query_vars($orderby) );

			add_filter( 'pre_get_posts', 'wpsc_generate_product_query', 11 );

			$wpsc_query = new WP_Query( $wpsc_query_vars );

			//for 3.1 :|
			if(empty($wpsc_query->posts) && isset($wpsc_query->tax_query) && isset($wp_query->query_vars['wpsc_product_category'])){
				$wpsc_query_vars = array();
				$wpsc_query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
				if(1 == get_option('use_pagination')){
					$wpsc_query_vars['posts_per_page'] = get_option('wpsc_products_per_page');
					$wpsc_query_vars['paged'] = get_query_var('paged');
					if(empty($wpsc_query_vars['paged']))
						$wpsc_query_vars['paged'] = get_query_var('page');
				}
				$wpsc_query = new WP_Query( $wpsc_query_vars );

			}
		}
	}

	if(  $is_404 || ( ( isset($wpsc_query->post_count) && $wpsc_query->post_count == 0 ) && isset($wpsc_query_vars['wpsc_product_category'] )  )){

		$args = array_merge($wp_query->query, array('posts_per_page' => get_option('wpsc_products_per_page')));
		$wp_query = new WP_Query($args);

		if( empty( $wp_query->posts ) ){
			$product_page_id = wpsc_get_the_post_id_by_shortcode('[productspage]');
			$wp_query = new WP_Query( 'page_id='.$product_page_id);
		}
	}
	if ( isset( $wp_query->post->ID ) )
		$post_id = $wp_query->post->ID;
	else
		$post_id = 0;

	if ( get_permalink( $post_id ) == get_option( 'shopping_cart_url' ) )
		$_SESSION['wpsc_has_been_to_checkout'] = true;
}
add_action( 'template_redirect', 'wpsc_start_the_query', 8 );

/**
 * Obtain the necessary product sort order query variables based on the specified product sort order.
 * If no sort order is specified, the sort order configured in Dashboard -> Settings -> Store -> Presentation -> 'Sort Product By' is used.
 *
 * @param string $orderby optional product sort order
 * @return array Array of query variables
 */
function wpsc_product_sort_order_query_vars( $orderby = null ) {
	if ( is_null($orderby) )
		$orderby = get_option( 'wpsc_sort_by' );

	$query_vars = array();

	switch ( $orderby ) {

		case "dragndrop":
			$query_vars["orderby"] = 'menu_order';
			break;

		case "name":
			$query_vars["orderby"] = 'title';
			break;

		//This only works in WP 3.0.
		case "price":
			add_filter( 'posts_join', 'wpsc_add_meta_table' );
			add_filter( 'posts_where', 'wpsc_add_meta_table_where' );
			$query_vars["meta_key"] = '_wpsc_price';
			$query_vars["orderby"] = 'meta_value_num';
			break;

		case "id":
			$query_vars["orderby"] = 'ID';
			break;
		default:
			// Allow other WordPress 'ordery' values as defined in http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
			$query_vars["orderby"] = $orderby;
			break;
	}
	return $query_vars;
}


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

/**
 * wpsc_taxonomy_rewrite_rules function.
 * Adds in new rewrite rules for categories, products, category pages, and ambiguities (either categories or products)
 * Also modifies the rewrite rules for product URLs to add in the post type.
 *
 * @since 3.8
 * @access public
 * @param array $rewrite_rules
 * @return array - the modified rewrite rules
 */
function wpsc_taxonomy_rewrite_rules( $rewrite_rules ) {
	global $wpsc_page_titles;
	$products_page = $wpsc_page_titles['products'];
	$checkout_page = $wpsc_page_titles['checkout'];
	$target_string = "index.php?product";
	$replacement_string = "index.php?post_type=wpsc-product&product";
	$target_rule_set_query_var = 'products';

	$target_rule_set = array( );
	foreach ( $rewrite_rules as $rewrite_key => $rewrite_query ) {
		if ( stristr( $rewrite_query, "index.php?product" ) ) {
			$rewrite_rules[$rewrite_key] = str_replace( $target_string, $replacement_string, $rewrite_query );
		}
		if ( stristr( $rewrite_query, "$target_rule_set_query_var=" ) ) {
			$target_rule_set[] = $rewrite_key;
		}
	}

	$new_rewrite_rules[$products_page . '/(.+?)/product/([^/]+)/comment-page-([0-9]{1,})/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&name=$matches[2]&cpage=$matches[3]';
	$new_rewrite_rules[$products_page . '/(.+?)/product/([^/]+)/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&name=$matches[2]';
	$new_rewrite_rules[$products_page . '/(.+?)/([^/]+)/comment-page-([0-9]{1,})/?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&wpsc_item=$matches[2]&cpage=$matches[3]';
	$new_rewrite_rules[$products_page . '/(.+?)/([^/]+)?$'] = 'index.php?post_type=wpsc-product&products=$matches[1]&wpsc_item=$matches[2]';

	$last_target_rule = array_pop( $target_rule_set );

	$rebuilt_rewrite_rules = array( );
	foreach ( $rewrite_rules as $rewrite_key => $rewrite_query ) {
		if ( $rewrite_key == $last_target_rule ) {
			$rebuilt_rewrite_rules = array_merge( $rebuilt_rewrite_rules, $new_rewrite_rules );
		}
		$rebuilt_rewrite_rules[$rewrite_key] = $rewrite_query;
	}

	// fix pagination issue with product category hirarchical URL
	if ( get_option( 'product_category_hierarchical_url', false ) ) {
		$rule = $rebuilt_rewrite_rules[$products_page . '/(.+?)/page/?([0-9]{1,})/?$'];
		unset( $rebuilt_rewrite_rules[$products_page . '/(.+?)/page/?([0-9]{1,})/?$'] );
		$rebuilt_rewrite_rules = array_merge(
			array(
				'(' . $products_page . ')/page/([0-9]+)/?' => 'index.php?pagename=$matches[1]&page=$matches[2]',
				$products_page . '/(.+?)(/.+?)?/page/?([0-9]{1,})/?$' => 'index.php?wpsc_product_category=$matches[1]&wpsc-product=$matches[2]&page=$matches[3]',
			),
			$rebuilt_rewrite_rules
		);
	}

	// fix pagination in WordPress 3.4
	if ( version_compare( get_bloginfo( 'version' ), '3.4', '>=' ) ) {
		$rebuilt_rewrite_rules = array_merge(
			array(
				'(' . $products_page . ')/([0-9]+)/?$' => 'index.php?pagename=$matches[1]&page=$matches[2]',
			),
			$rebuilt_rewrite_rules
		);
	}
	return $rebuilt_rewrite_rules;
}

add_filter( 'rewrite_rules_array', 'wpsc_taxonomy_rewrite_rules' );

/**
 * wpsc_query_vars function.
 * adds in the post_type and wpsc_item query vars
 *
 * @since 3.8
 * @access public
 * @param mixed $vars
 * @return void
 */
function wpsc_query_vars( $vars ) {
	// post_type is used to specify that we are looking for products
	$vars[] = "post_type";
	// wpsc_item is used to find items that could be either a product or a product category, it defaults to category, then tries products
	$vars[] = "wpsc_item";
	return $vars;
}

add_filter( 'query_vars', 'wpsc_query_vars' );

/**
 * wpsc_query_modifier function.
 *
 * @since 3.8
 * @access public
 * @param object - reference to $wp_query
 * @return $query
 */
function wpsc_split_the_query( $query ) {
	global $wpsc_page_titles, $wpsc_query, $wpsc_query_vars;
	// These values are to be dynamically defined
	$products_page = $wpsc_page_titles['products'];
	$checkout_page = $wpsc_page_titles['checkout'];
	$userlog_page = $wpsc_page_titles['userlog'];
	$transaction_results_page = $wpsc_page_titles['transaction_results'];

	// otherwise, check if we are looking at a product, if so, duplicate the query and swap the old one out for a products page request
	// JS - 6.4.1020 - Added is_admin condition, as the products condition broke categories in backend
	if ( !empty($query->query_vars['pagename']) && ($query->query_vars['pagename'] == $products_page) || isset( $query->query_vars['products'] ) && !is_admin() ) {
		// store a copy of the wordpress query
		$wpsc_query_data = $query->query;

		// wipe and replace the query vars
		$query->query                   = array();
		$query->query['pagename']       = "$products_page";
		$query->query_vars['pagename']  = "$products_page";
		$query->query_vars['name']      = '';
		$query->query_vars['post_type'] = '';

		$query->queried_object = & get_page_by_path( $query->query['pagename'] );

		if ( !empty( $query->queried_object ) )
			$query->queried_object_id = (int)$query->queried_object->ID;
		else
			unset( $query->queried_object );

		unset( $query->query_vars['products'] );
		unset( $query->query_vars['name'] );
		unset( $query->query_vars['taxonomy'] );
		unset( $query->query_vars['term'] );
		unset( $query->query_vars['wpsc_item'] );

		$query->is_singular = true;
		$query->is_page     = true;
		$query->is_tax      = false;
		$query->is_archive  = false;
		$query->is_single   = false;

		if ( ($wpsc_query_vars == null ) ) {
			unset( $wpsc_query_data['pagename'] );
			$wpsc_query_vars = $wpsc_query_data;
		}
	}

	add_filter( 'redirect_canonical', 'wpsc_break_canonical_redirects', 10, 2 );
	remove_filter( 'pre_get_posts', 'wpsc_split_the_query', 8 );
}

/**
 * wpsc_generate_product_query function.
 *
 * @access public
 * @param mixed $query
 * @return void
 */
function wpsc_generate_product_query( $query ) {
	global $wp_query;
	$prod_page = wpsc_get_the_post_id_by_shortcode('[productspage]');
	$prod_page = get_post($prod_page);
	remove_filter( 'pre_get_posts', 'wpsc_generate_product_query', 11 );
	$query->query_vars['taxonomy'] = null;
	$query->query_vars['term'] = null;

	// default product selection
	if ( $query->query_vars['pagename'] != '' ) {
		$query->query_vars['post_type'] = 'wpsc-product';
		$query->query_vars['pagename']  = '';
		$query->is_page     = false;
		$query->is_tax      = false;
		$query->is_archive  = true;
		$query->is_singular = false;
		$query->is_single   = false;
	}

	// If wpsc_item is not null, we are looking for a product or a product category, check for category
	if ( isset( $query->query_vars['wpsc_item'] ) && ($query->query_vars['wpsc_item'] != '') ) {
		$test_term = get_term_by( 'slug', $query->query_vars['wpsc_item'], 'wpsc_product_category' );
		if ( $test_term->slug == $query->query_vars['wpsc_item'] ) {
			// if category exists (slug matches slug), set products to value of wpsc_item
			$query->query_vars['products'] = $query->query_vars['wpsc_item'];
		} else {
			// otherwise set name to value of wpsc_item
			$query->query_vars['name'] = $query->query_vars['wpsc_item'];
		}
	}

	if ( isset( $query->query_vars['products'] ) && ($query->query_vars['products'] != null) && ($query->query_vars['name'] != null) ) {
		unset( $query->query_vars['taxonomy'] );
		unset( $query->query_vars['term'] );
		$query->query_vars['post_type'] = 'wpsc-product';
		$query->is_tax      = false;
		$query->is_archive  = true;
		$query->is_singular = false;
		$query->is_single   = false;
	}
	if( isset($wp_query->query_vars['wpsc_product_category']) && !isset($wp_query->query_vars['wpsc-product'])){
		$query->query_vars['wpsc_product_category'] = $wp_query->query_vars['wpsc_product_category'];
		$query->query_vars['taxonomy'] = $wp_query->query_vars['taxonomy'];
		$query->query_vars['term'] = $wp_query->query_vars['term'];
	}elseif( '' != ($default_category = get_option('wpsc_default_category')) && !isset($wp_query->query_vars['wpsc-product'])){
		$default_term = get_term($default_category,'wpsc_product_category');
		if(!empty($default_term) && empty($wp_query->query_vars['category_name'])){
			$query->query_vars['taxonomy'] = 'wpsc_product_category';
			$query->query_vars['term'] = $default_term->slug;
			$query->is_tax = true;
		}elseif(isset($wp_query->query_vars['name']) && $wp_query->is_404 && $wp_query->query_vars['category_name'] != $prod_page->post_name){
			unset( $query->query_vars['taxonomy'] );
			unset( $query->query_vars['term'] );
			$query->query_vars['wpsc-product'] = $wp_query->query_vars['name'];
			$query->query_vars['name'] = $wp_query->query_vars['name'];

		}else{
			$query->is_tax = true;
			$term =	get_term_by('slug',$wp_query->query_vars['name'], 'wpsc_product_category' );
			if(!empty($term)){
				$query->query_vars['taxonomy'] = 'wpsc_product_category';
				$query->query_vars['wpsc_product_category__in'] = array($term->term_taxonomy_id);
				$query->query_vars['wpsc_product_category'] = $wp_query->query_vars['name'];
				$query->query_vars['term'] = $wp_query->query_vars['name'];
			}elseif(is_numeric($default_category)){
				$query->query_vars['taxonomy'] = 'wpsc_product_category';
			}else{
				$query->is_tax = false;
			}
		}
	}
	//If Product Tag Taxonomy
	if (isset($wp_query->query_vars['product_tag']) && $wp_query->query_vars['product_tag']){
		$query->query_vars['product_tag'] = $wp_query->query_vars['product_tag'];
		$query->query_vars['term'] = $wp_query->query_vars['term'];
		$query->query_vars['taxonomy'] = 'product_tag';
		$query->is_tax      = true;
	}
	if(1 == get_option('use_pagination')){
		$query->query_vars['posts_per_page'] = get_option('wpsc_products_per_page');
		if( isset( $_GET['items_per_page'] ) ) {
			if ( is_numeric( $_GET['items_per_page'] ) ) {
				$query->query_vars['posts_per_page'] = (int) $_GET['items_per_page'];
			} elseif ( $_GET['items_per_page'] == 'all' ) {
				$query->query_vars['posts_per_page'] = -1;
				$query->query_vars['nopaging'] = 1;
			}
		}
	} else {
		$query->query_vars['posts_per_page'] = -1;
		$query->query_vars['nopaging'] = 1;
	}
	if ( $query->is_tax == true )
		new wpsc_products_by_category( $query );
	return $query;
}

function wpsc_mark_product_query( $query ) {

	if ( isset( $query->query_vars['post_type'] ) && ($query->query_vars['post_type'] == 'wpsc-product') )
		$query->is_product = true;

	return $query;
}
add_filter( 'pre_get_posts', 'wpsc_split_the_query', 8 );
add_filter( 'parse_query', 'wpsc_mark_product_query', 12 );

/**
 * wpsc_products_by_category class.
 *
 */
class wpsc_products_by_category {

	var $sql_components = array( );

	/**
	 * wpsc_products_by_category function.
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function wpsc_products_by_category( $query ) {
		global $wpdb;
		$q = $query->query_vars;


		// Category stuff for nice URLs
		if ( !empty( $q['wpsc_product_category'] ) && !$query->is_singular ) {
			$q['taxonomy'] = 'wpsc_product_category';
			$q['term'] = $q['wpsc_product_category'];
			$in_cats = '';
			$join = " INNER JOIN $wpdb->term_relationships
				ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
			INNER JOIN $wpdb->term_taxonomy
				ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
			";
			if(isset($q['meta_key']))
				$join .= " INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";

			$whichcat = " AND $wpdb->term_taxonomy.taxonomy = '{$q['taxonomy']}' ";

			$term_data = get_term_by( 'slug', $q['term'], $q['taxonomy'] );

			if( is_object( $term_data ) )
				$in_cats = array( $term_data->term_id );

			if('0' != get_option('show_subcatsprods_in_cat') && is_object($term_data)){
				$term_children_data = get_term_children( $term_data->term_id, $q['taxonomy'] );
				$in_cats = array_reverse( array_merge( $in_cats, $term_children_data ) );
			}
			if( is_array( $in_cats ) ){
				$in_cats = "'" . implode( "', '", $in_cats ) . "'";
				$whichcat .= "AND $wpdb->term_taxonomy.term_id IN ($in_cats)";
			}

			$post_type_object = get_post_type_object( 'wpsc-product' );
			$permitted_post_statuses = current_user_can( $post_type_object->cap->edit_posts ) ? "'private', 'draft', 'pending', 'publish'" : "'publish'";


			$whichcat .= " AND $wpdb->posts.post_status IN ($permitted_post_statuses) ";
			$groupby = "{$wpdb->posts}.ID";

			$this->sql_components['join']     = $join;
			$this->sql_components['fields']   = "{$wpdb->posts}.*, {$wpdb->term_taxonomy}.term_id";
			$this->sql_components['group_by'] = $groupby;

			//what about ordering by price
			if(isset($q['meta_key']) && '_wpsc_price' == $q['meta_key']){
				$whichcat .= " AND $wpdb->postmeta.meta_key = '_wpsc_price'";
			}else{

				$this->sql_components['order_by'] = "{$wpdb->term_taxonomy}.term_id";
			}
			$this->sql_components['where']    = $whichcat;
			add_filter( 'posts_join', array( &$this, 'join_sql' ) );
			add_filter( 'posts_where', array( &$this, 'where_sql' ) );
			add_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
			add_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
			add_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		}
	}

	function join_sql( $sql ) {
		if ( isset( $this->sql_components['join'] ) )
			$sql = $this->sql_components['join'];

		remove_filter( 'posts_join', array( &$this, 'join_sql' ) );
		return $sql;
	}

	function where_sql( $sql ) {
		if ( isset( $this->sql_components['where'] ) )
			$sql = $this->sql_components['where'];

		remove_filter( 'posts_where', array( &$this, 'where_sql' ) );
		return $sql;
	}

	function order_by_sql( $sql ) {
		$order_by_parts   = array( );
		$order_by_parts[] = $sql;

		if ( isset( $this->sql_components['order_by'] ) )
			$order_by_parts[] = $this->sql_components['order_by'];

		$order_by_parts = array_reverse( $order_by_parts );
		$sql = implode( ',', $order_by_parts );

		remove_filter( 'posts_orderby', array( &$this, 'order_by_sql' ) );
		return $sql;
	}

	function fields_sql( $sql ) {
		if ( isset( $this->sql_components['fields'] ) )
			$sql = $this->sql_components['fields'];

		remove_filter( 'posts_fields', array( &$this, 'fields_sql' ) );
		return $sql;
	}

	function group_by_sql( $sql ) {
		if ( isset( $this->sql_components['group_by'] ) )
			$sql = $this->sql_components['group_by'];

		remove_filter( 'posts_groupby', array( &$this, 'group_by_sql' ) );
		return $sql;
	}

	function request_sql( $sql ) {
		echo $sql . "<br />";
		remove_filter( 'posts_request', array( &$this, 'request_sql' ) );
		return $sql;
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
 * wpsc_product_link function.
 * Gets the product link, hooks into post_link
 * Uses the currently selected, only associated or first listed category for the term URL
 * If the category slug is the same as the product slug, it prefixes the product slug with "product/" to counteract conflicts
 *
 * @access public
 * @return void
 */
function wpsc_product_link( $permalink, $post, $leavename ) {
	global $wp_query, $wpsc_page_titles, $wpsc_query, $wp_current_filter;
	$rewritecode = array(
		'%wpsc_product_category%',
		$leavename ? '' : '%postname%',
	);
	if ( is_object( $post ) ) {
		// In wordpress 2.9 we got a post object
		$post_id = $post->ID;
	} else {
		// In wordpress 3.0 we get a post ID
		$post_id = $post;
		$post = get_post( $post_id );
	}

	// Only applies to WPSC products, don't stop on permalinks of other CPTs
	// Fixes http://code.google.com/p/wp-e-commerce/issues/detail?id=271
	if ($post->post_type != 'wpsc-product')
		return $permalink;

	$permalink_structure = get_option( 'permalink_structure' );

	// This may become customiseable later
	$our_permalink_structure = str_replace( basename( home_url() ), '', $wpsc_page_titles['products'] ) . "/%wpsc_product_category%/%postname%/";
	// Mostly the same conditions used for posts, but restricted to items with a post type of "wpsc-product "

	if ( '' != $permalink_structure && !in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
		$product_categories = wp_get_object_terms( $post_id, 'wpsc_product_category' );
		$product_category_slugs = array( );
		foreach ( $product_categories as $product_category ) {
			$product_category_slugs[] = $product_category->slug;
		}
		// If the product is associated with multiple categories, determine which one to pick
		if ( count( $product_categories ) == 0 ) {
			$category_slug = 'uncategorized';
		} elseif ( count( $product_categories ) > 1 ) {
			if ( (isset( $wp_query->query_vars['products'] ) && $wp_query->query_vars['products'] != null) && in_array( $wp_query->query_vars['products'], $product_category_slugs ) ) {
				$product_category = $wp_query->query_vars['products'];
			} else {
				$link = $product_categories[0]->slug;
				if ( ! in_array( 'wp_head', $wp_current_filter) && isset( $wpsc_query->query_vars['wpsc_product_category'] ) ) {
					$current_cat = $wpsc_query->query_vars['wpsc_product_category'];
					if ( in_array( $current_cat, $product_category_slugs ) )
						$link = $current_cat;
				}

				$product_category = $link;
			}
			$category_slug = $product_category;
		} else {
			// If the product is associated with only one category, we only have one choice
			if ( !isset( $product_categories[0] ) )
				$product_categories[0] = '';

			$product_category = $product_categories[0];

			if ( !is_object( $product_category ) )
				$product_category = new stdClass();

			if ( !isset( $product_category->slug ) )
				$product_category->slug = null;

			$category_slug = $product_category->slug;
		}

		$post_name = $post->post_name;

		if ( get_option( 'product_category_hierarchical_url', 0 ) ) {
			$selected_term = get_term_by( 'slug', $category_slug, 'wpsc_product_category' );
			if ( is_object( $selected_term ) ) {
				$term_chain = array( $selected_term->slug );
				while ( $selected_term->parent ) {
					$selected_term = get_term( $selected_term->parent, 'wpsc_product_category' );
					array_unshift( $term_chain, $selected_term->slug );
				}
				$category_slug = implode( '/', $term_chain );
			}
		}

		if( isset( $category_slug ) && empty( $category_slug ) )
			$category_slug = 'product';

		$category_slug = apply_filters( 'wpsc_product_permalink_cat_slug', $category_slug, $post_id );

		$rewritereplace = array(
			$category_slug,
			$post_name
		);

		$permalink = str_replace( $rewritecode, $rewritereplace, $our_permalink_structure );
		$permalink = user_trailingslashit( $permalink, 'single' );

		$permalink = home_url( $permalink );
	}
	return apply_filters( 'wpsc_product_permalink', $permalink, $post->ID );
}
	add_filter( 'post_type_link', 'wpsc_product_link', 10, 3 );

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
 * wpsc_get_page_post_names function.
 *
 * @since 3.8
 * @access public
 * @return void
 */
function wpsc_get_page_post_names() {
	$wpsc_page['products']            = basename( get_option( 'product_list_url' ) );
	$wpsc_page['checkout']            = basename( get_option( 'checkout_url' ) );
	$wpsc_page['transaction_results'] = basename( get_option( 'transact_url' ) );
	$wpsc_page['userlog']             = basename( get_option( 'user_account_url' ) );

	return $wpsc_page;
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
add_action( 'wp', 'wpsc_select_theme_functions', 10, 1 );

/**
 * if the user is on a checkout page, force SSL if that option is so set
 */
function wpsc_force_ssl() {
	global $wp_query;
	if ( '1' == get_option( 'wpsc_force_ssl' ) && ! is_ssl() && false !== strpos( $wp_query->post->post_content, '[shoppingcart]' ) ) {
		$sslurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		wp_redirect( $sslurl );
		exit;
	}
}
add_action( 'wp', 'wpsc_force_ssl' );


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

/**
 * Checks if current page is shopping cart, and it should be SSL, but is not.
 * Used primarily for str_replacing links or content for https
 *
 * @since 3.8.8.1
 * @deprecated 3.8.8.2
 * @return boolean true if we're on the shopping cart page and should be ssl, false if not
 */
function wpsc_is_ssl() {
	global $wp_query;

	return '1' == get_option( 'wpsc_force_ssl' ) && ! is_ssl() && false !== strpos( $wp_query->post->post_content, '[shoppingcart]' );
}


/**
 * In case the user is not logged in, create a customer cookie with a unique
 * ID to pair with the transient in the database.
 *
 * @access public
 * @since 3.8.9
 * @return string Customer ID
 */
function wpsc_create_customer_id() {
	$expire = time() + WPSC_CUSTOMER_DATA_EXPIRATION; // valid for 48 hours
	$secure = is_ssl();
	$id = '_' . wp_generate_password(); // make sure the ID is a string
	$data = $id . $expire;
	$hash = hash_hmac( 'md5', $data, wp_hash( $data ) );
	// store ID, expire and hash to validate later
	$cookie = $id . '|' . $expire . '|' . $hash;

	setcookie( WPSC_CUSTOMER_COOKIE, $cookie, $expire, WPSC_CUSTOMER_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
	$_COOKIE[WPSC_CUSTOMER_COOKIE] = $cookie;
	return $id;
}

/**
 * Make sure the customer cookie is not compromised.
 *
 * @access public
 * @since 3.8.9
 * @return mixed Return the customer ID if the cookie is valid, false if otherwise.
 */
function wpsc_validate_customer_cookie() {
	$cookie = $_COOKIE[WPSC_CUSTOMER_COOKIE];
	list( $id, $expire, $hash ) = explode( '|', $cookie );
	$data = $id . $expire;
	$hmac = hash_hmac( 'md5', $data, wp_hash( $data ) );

	if ( $hmac != $hash )
		return false;

	return $id;
}

/**
 * Merge anonymous customer data (stored in transient) with an account meta data when the customer
 * logs in.
 *
 * This is done to preserve customer settings and cart.
 *
 * @since 3.8.9
 * @access private
 */
function _wpsc_merge_customer_data() {
	$account_id = get_current_user_id();
	$cookie_id = wpsc_validate_customer_cookie();

	if ( ! $cookie_id )
		return;

	$cookie_data = get_transient( "wpsc_customer_meta_{$cookie_id}" );
	if ( ! is_array( $cookie_data ) || empty( $cookie_data ) )
		return;

	foreach ( $cookie_data as $key => $value ) {
		wpsc_update_customer_meta( $key, $value, $account_id );
	}

	delete_transient( "wpsc_customer_meta_{$cookie_id}" );
	setcookie( WPSC_CUSTOMER_COOKIE, '', time() - 3600, WPSC_CUSTOMER_COOKIE_PATH, COOKIE_DOMAIN, is_ssl(), true );
	unset( $_COOKIE[WPSC_CUSTOMER_COOKIE] );
}

/**
 * Get current customer ID.
 *
 * If the user is logged in, return the user ID. Otherwise return the ID associated
 * with the customer's cookie.
 *
 * If $mode is set to 'create', WPEC will create the customer ID if it hasn't
 * already been created yet.
 *
 * @access public
 * @since 3.8.9
 * @param  string $mode Set to 'create' to create customer cookie and ID
 * @return mixed        User ID (if logged in) or customer cookie ID
 */
function wpsc_get_current_customer_id( $mode = '' ) {
	if ( is_user_logged_in() && isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) )
		_wpsc_merge_customer_data();

	if ( is_user_logged_in() )
		return get_current_user_id();
	elseif ( isset( $_COOKIE[WPSC_CUSTOMER_COOKIE] ) )
		return wpsc_validate_customer_cookie();
	elseif ( $mode == 'create' )
		return wpsc_create_customer_id();

	return false;
}

/**
 * Return an array containing all metadata of a customer
 *
 * @access public
 * @since 3.8.9
 * @param  mixed $id Customer ID. Default to the current user ID.
 * @return WP_Error|array Return an array of metadata if no error occurs, WP_Error
 *                        if otherwise.
 */
function wpsc_get_all_customer_meta( $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	if ( ! $id )
		return new WP_Error( 'wpsc_customer_meta_invalid_customer_id', __( 'Invalid customer ID', 'wpsc' ), $id );

	// take multisite into account
	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';
	if ( is_numeric( $id ) )
		$profile = get_user_meta( $id, "_wpsc_{$blog_prefix}customer_profile", true );
	else
		$profile = get_transient( "wpsc_customer_meta_{$blog_prefix}{$id}" );

	if ( ! is_array( $profile ) )
		$profile = array();

	return apply_filters( 'wpsc_get_all_customer_meta', $profile, $id );
}

/**
 * Get a customer meta value.
 *
 * @access public
 * @since  3.8.9
 * @param  string  $key Meta key
 * @param  int|string $id  Customer ID. Optional, defaults to current customer
 * @return mixed           Meta value, or null if it doesn't exist or if the
 *                         customer ID is invalid.
 */
function wpsc_get_customer_meta( $key = '', $id = false ) {
	global $wpdb;

	$profile = wpsc_get_all_customer_meta( $id );

	// attempt to regenerate current customer ID if it's invalid
	if ( is_wp_error( $profile ) && ! $id ) {
		wpsc_create_customer_id();
		$profile = wpsc_get_all_customer_meta();
	}

	if ( is_wp_error( $profile ) || ! array_key_exists( $key, $profile ) )
		return null;

	return $profile[$key];
}

/**
 * Overwrite customer meta with an array of meta_key => meta_value.
 *
 * @access public
 * @since  3.8.9
 * @param  array      $profile Customer meta array
 * @param  int|string $id      Customer ID. Optional. Defaults to current customer.
 * @return boolean             True if meta values are updated successfully. False
 *                             if otherwise.
 */
function wpsc_update_all_customer_meta( $profile, $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id( 'create' );

	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';

	if ( is_numeric( $id ) )
		return update_user_meta( $id, "_wpsc_{$blog_prefix}customer_profile", $profile );
	else
		return set_transient( "wpsc_customer_meta_{$blog_prefix}{$id}", $profile, WPSC_CUSTOMER_DATA_EXPIRATION );
}

/**
 * Update a customer meta.
 *
 * @access public
 * @since  3.8.9
 * @param  string     $key   Meta key
 * @param  mixed      $value Meta value
 * @param  string|int $id    Customer ID. Optional. Defaults to current customer.
 * @return boolean|WP_Error  True if successful, false if not successful, WP_Error
 *                           if there are any errors.
 */
function wpsc_update_customer_meta( $key, $value, $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id( 'create' );

	$profile = wpsc_get_all_customer_meta( $id );

	if ( is_wp_error( $profile ) )
		return $profile;

	$profile[$key] = $value;

	return wpsc_update_all_customer_meta( $profile, $id );
}

/**
 * Delete customer meta.
 *
 * @access public
 * @since  3.8.9
 * @param  string     $key  Meta key
 * @param  string|int $id   Customer ID. Optional. Defaults to current customer.
 * @return boolean|WP_Error True if successful. False if not successful. WP_Error
 *                          if there are any errors.
 */
function wpsc_delete_customer_meta( $key, $id = false ) {
	$profile = wpsc_get_all_customer_meta( $id );

	if ( is_wp_error( $profile ) )
		return $profile;

	if ( array_key_exists( $key, $profile ) )
		unset( $profile[$key] );

	return wpsc_update_all_customer_meta( $profile, $id );
}

/**
 * Create customer ID upon 'plugins_loaded' to make sure there's one exists before
 * anything else.
 *
 * @access private
 * @since  3.8.9
 */
function _wpsc_action_create_customer_id() {
	wpsc_get_current_customer_id( 'create' );
}

/**
 * Delete all customer meta for a certain customer ID
 *
 * @since  3.8.9.4
 * @param  string|int $id Customer ID. Optional. Defaults to current customer
 * @return boolean        True if successful, False if otherwise
 */
function wpsc_delete_all_customer_meta( $id = false ) {
	global $wpdb;

	if ( ! $id )
		$id = wpsc_get_current_customer_id();

	$blog_prefix = is_multisite() ? $wpdb->get_blog_prefix() : '';

	if ( is_numeric( $id ) )
		return delete_user_meta( $id, "_wpsc_{$blog_prefix}customer_profile" );
	else
		return delete_transient( "wpsc_customer_meta_{$blog_prefix}{$id}" );
}