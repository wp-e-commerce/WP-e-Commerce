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

if ( is_admin() )
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
	$wpsc_purchlog_statuses = apply_filters( 'wpsc_set_purchlog_statuses', $wpsc_purchlog_statuses );
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

	if ( ! get_option( 'do_not_use_shipping' ) )
		add_action( 'wpsc_setup_customer', '_wpsc_action_get_shipping_method' );
}

/**
 * If shipping is enabled and shipping methods have not been initialized, then
 * do so.
 *
 * @access private
 * @since 3.8.13
 */
function _wpsc_action_get_shipping_method() {
	global $wpsc_cart;

	if ( empty( $wpsc_cart->selected_shipping_method ) ) {
		$wpsc_cart->get_shipping_method();
	}
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
		'menu_icon'            => version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ? WPSC_CORE_IMAGES_URL . '/credit_cards.png' : 'dashicons-cart',
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


/**
 * This serializes the shopping cart variable as a backup in case the
 * unserialized one gets butchered by various things
 */
function wpsc_serialize_shopping_cart() {
	global $wpsc_cart;

	if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
		return;

	// avoid flooding transients with bots hitting feeds
	if ( is_feed() ) {
		wpsc_delete_all_customer_meta();
		return;
	}

	if ( is_object( $wpsc_cart ) )
		$wpsc_cart->errors = array();

	// need to prevent set_cookie from being called at this stage in case the user just logged out
	// because by now, some output must have been printed out
	$customer_id = wpsc_get_current_customer_id();

	if ( $customer_id )
		wpsc_update_customer_meta( 'cart', base64_encode( serialize( $wpsc_cart ) ) );

	return true;
}

add_action( 'shutdown', 'wpsc_serialize_shopping_cart' );

/**
 * wpsc_get_page_post_names function.
 *
 * @since 3.8
 * @access public
 * @return void
 */
function wpsc_get_page_post_names() {
	$wpsc_page['products'] = basename( get_option( 'product_list_url' ) );

	if ( empty( $wpsc_page['products'] ) || false !== strpos( $wpsc_page['products'], '?page_id=' ) ) {
		// Products page either doesn't exist, or is a draft
		// Default to /product/xyz permalinks for products
		$wpsc_page['products'] = 'product';
	}

	$wpsc_page['checkout']            = basename( get_option( 'checkout_url' ) );
	$wpsc_page['transaction_results'] = basename( get_option( 'transact_url' ) );
	$wpsc_page['userlog']             = basename( get_option( 'user_account_url' ) );

	return $wpsc_page;
}

function wpsc_cron() {
	foreach ( wp_get_schedules() as $cron => $schedule ) {
		if ( ! wp_next_scheduled( "wpsc_{$cron}_cron_task" ) )
			wp_schedule_event( time(), $cron, "wpsc_{$cron}_cron_task" );
	}
}
add_action( 'init', 'wpsc_cron' );

/**
 * Updates permalink slugs
 *
 * @since 3.8.9
 * @return type
 */
function wpsc_update_permalink_slugs() {
	global $wpdb;

	$wpsc_pageurl_option = array(
		'product_list_url'  => '[productspage]',
		'shopping_cart_url' => '[shoppingcart]',
		'checkout_url'      => '[shoppingcart]',
		'transact_url'      => '[transactionresults]',
		'user_account_url'  => '[userlog]'
	);

	$ids = array();

	foreach ( $wpsc_pageurl_option as $option_key => $page_string ) {
		$id = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` = 'page' AND `post_content` LIKE '%$page_string%' LIMIT 1" );

		if ( ! $id )
			continue;

		$ids[ $page_string ] = $id;

		$the_new_link = _get_page_link( $id );

		if ( stristr( get_option( $option_key ), "https://" ) )
			$the_new_link = str_replace( 'http://', "https://", $the_new_link );

		if ( $option_key == 'shopping_cart_url' )
			update_option( 'checkout_url', $the_new_link );

		update_option( $option_key, $the_new_link );
	}

	update_option( 'wpsc_shortcode_page_ids', $ids );
}

/**
 * Return an array of terms assigned to a product.
 *
 * This function is basically a wrapper for get_the_terms(), and should be used
 * instead of get_the_terms() and wp_get_object_terms() because of two reasons:
 *
 * - wp_get_object_terms() doesn't utilize object cache.
 * - get_the_terms() returns false when no terms are found. We want something
 *   that returns an empty array instead.
 *
 * @since 3.8.10
 * @param  int    $product_id Product ID
 * @param  string $tax        Taxonomy
 * @param  string $field      If you want to return only an array of a certain field, specify it here.
 * @return stdObject[]
 */
function wpsc_get_product_terms( $product_id, $tax, $field = '' ) {
	$terms = get_the_terms( $product_id, $tax );

	if ( ! $terms )
		$terms = array();

	if ( $field )
		$terms = wp_list_pluck( $terms, $field );

	// remove the redundant array keys, could cause issues in loops with iterator
	$terms = array_values( $terms );
	return $terms;
}

/**
 * Abstracts Suhosin check into a function.  Used primarily in relation to target markets.
 *
 * @since 3.8.9
 * @return boolean
 */
function wpsc_is_suhosin_enabled() {
	return @ extension_loaded( 'suhosin' ) && @ ini_get( 'suhosin.post.max_vars' ) > 0 && @ ini_get( 'suhosin.post.max_vars' ) < 500;
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
	$wpsc_page_titles = apply_filters( 'wpsc_page_titles', false );

	if ( empty( $wpsc_page_titles ) )
		$wpsc_page_titles = wpsc_get_page_post_names();
}

