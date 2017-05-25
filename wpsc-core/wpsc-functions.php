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

if ( is_admin() ) {
	add_filter( 'term_name', 'wpsc_term_list_levels', 10, 2 );
}

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

	if ( ! is_object( $screen ) || ! in_array( $screen->id, array( 'edit-wpsc-variation', 'edit-wpsc_product_category' ) ) ) {
		return $term_name;
	}

	if ( ! isset( $wpsc_term_list_levels ) ) {
		$wpsc_term_list_levels = array();
	}

	if ( is_numeric( $term ) ) {
		$term = get_term_by( 'id', $term, str_replace( 'edit-', '', $screen->id ) );
	}

	if ( isset( $wp_list_table->level ) ) {
		$wpsc_term_list_levels[ $term->term_id ] = $wp_list_table->level;
	}

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
 * Determines whether or not a current user has the capability to do administrative actions in the store.
 *
 * @since  3.8.14.4
 *
 * @return  bool  Whether or not current user can administrate the store.
 */
function wpsc_is_store_admin() {
	return current_user_can( apply_filters( 'wpsc_store_admin_capability', 'manage_options' ) );
}

/**
 * wpsc_core_load_checkout_data()
 *
 * @return none
 */
function wpsc_core_load_checkout_data() {
	wpsc_checkout_form_fields();
	wpsc_checkout_unique_names();
}

/**
 * Get the checkout form fields and types
 *
 * @since 3.8.14
 *
 * @return array of strings     each key value being a checkout item's
 *                          	user presentable name, the value being the
 *                              checkout item type
 */
function wpsc_checkout_form_fields() {
	$form_types = array(
			__( 'Text', 'wp-e-commerce' )             => 'text',
			__( 'Email Address', 'wp-e-commerce' )    => 'email',
			__( 'Street Address', 'wp-e-commerce' )   => 'address',
			__( 'City', 'wp-e-commerce' )             => 'city',
			__( 'Country', 'wp-e-commerce' )          => 'country',
			__( 'Delivery Address', 'wp-e-commerce' ) => 'delivery_address',
			__( 'Delivery City', 'wp-e-commerce' )    => 'delivery_city',
			__( 'Delivery Country', 'wp-e-commerce' ) => 'delivery_country',
			__( 'Text Area', 'wp-e-commerce' )        => 'textarea',
			__( 'Heading', 'wp-e-commerce' )          => 'heading',
			__( 'Select', 'wp-e-commerce' )           => 'select',
			__( 'Radio Button', 'wp-e-commerce' )     => 'radio',
			__( 'Checkbox', 'wp-e-commerce' )         => 'checkbox',
	);

	$form_types = apply_filters( 'wpsc_add_form_types', $form_types );

	// TODO: there really isn't a good reason to save this as an option becuase it is recomputed
	// every time WPEC is reloaded.  Deprecate the option and replace any references to the option
	// with a call to this function
	update_option( 'wpsc_checkout_form_fields', $form_types );

	return $form_types;
}


/**
 * Get the unique names used in checkout forms
 *
 * @since 3.8.14
 *
 * @return array of strings, each string value being a checkout item's unique name
 */
function wpsc_checkout_unique_names() {

	static $unique_names = null;

	if ( empty( $unique_names ) ) {
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
				'billingregion',
				'shippingSameBilling',
				'shippingfirstname',
				'shippinglastname',
				'shippingaddress',
				'shippingcity',
				'shippingstate',
				'shippingcountry',
				'shippingpostcode',
				'shippingregion',
		);

		$unique_names = apply_filters( 'wpsc_add_unique_names' , $unique_names );

		// TODO: there really isn't a good reason to save this as an option becuase it is recomputed
		// every time WPEC is reloaded.  Deprecate the option and replace any references to the option
		// with a call to this function
		update_option( 'wpsc_checkout_unique_names', $unique_names );
	}

	return $unique_names;
}

/**
 * Get the unique names used in checkout forms
 *
 * @since 3.8.14
 * @access private
 *
 * @return array  local variables to add to both admin and front end WPEC javascript
 */
function wpsc_javascript_localizations( $localizations = false ) {

	if ( ! is_array( $localizations ) ) {
		$localizations = array();
	}

	// The default localizations should only be added once per page as we don't want them to be
	// defined more than once in the javascript.
	static $already_added_default_localizations = false;

	if ( ! $already_added_default_localizations ) {

		$localizations['wpsc_ajax'] = array(
			'ajaxurl'                 => admin_url( 'admin-ajax.php', 'relative' ),
			'spinner'                 => esc_url( wpsc_get_ajax_spinner() ),
			'no_quotes'               => __( 'It appears that there are no shipping quotes for the shipping information provided.  Please check the information and try again.', 'wp-e-commerce' ),
			'ajax_get_cart_error'     => __( 'There was a problem getting the current contents of the shopping cart.', 'wp-e-commerce' ),
			'slide_to_shipping_error' => true,
		);

		$localizations['base_url']  	 	       = site_url();
		$localizations['WPSC_URL'] 	               = WPSC_URL;
		$localizations['WPSC_IMAGE_URL']           = WPSC_IMAGE_URL;
		$localizations['WPSC_CORE_IMAGES_URL']     = WPSC_CORE_IMAGES_URL;
		$localizations['fileThickboxLoadingImage'] = WPSC_CORE_IMAGES_URL . '/loadingAnimation.gif';
		$localizations['msg_shipping_need_recalc'] = __( 'Please click the <em>Calculate</em> button to refresh your shipping quotes, as your shipping information has been modified.', 'wp-e-commerce' );
	}

	/**
	 * a filter for WPeC components, plugins and themes to alter or add to what is localized into the WPeC javascript.
	 *
	 * @since 3.8.14
	 *
	 * @access public
	 *
	 * @param array $localizations array of localizations being sent to the javascript
	 *
	 */
	return apply_filters( 'wpsc_javascript_localizations', $localizations );
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
			'label'        => __( 'Incomplete Sale', 'wp-e-commerce' ),
			'view_label'   => _nx_noop(
				'Incomplete Sale <span class="count">(%d)</span>',
				'Incomplete Sale <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'        => 1,
		),
		array(
			'internalname' => 'order_received',
			'label'        => __( 'Order Received', 'wp-e-commerce' ),
			'view_label'   => _nx_noop(
				'Order Received <span class="count">(%d)</span>',
				'Order Received <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'        => 2,
		),
		array(
			'internalname'   => 'accepted_payment',
			'label'          => __( 'Accepted Payment', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Accepted Payment <span class="count">(%d)</span>',
				'Accepted Payment <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'is_transaction' => true,
			'order'          => 3,
		),
		array(
			'internalname'   => 'job_dispatched',
			'label'          => __( 'Job Dispatched', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Job Dispatched <span class="count">(%d)</span>',
				'Job Dispatched <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'is_transaction' => true,
			'order'          => 4,
		),
		array(
			'internalname'   => 'closed_order',
			'label'          => __( 'Closed Order', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Closed Order <span class="count">(%d)</span>',
				'Closed Order <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'is_transaction' => true,
			'order'          => 5,
		),
		array(
			'internalname'   => 'declined_payment',
			'label'          => __( 'Payment Declined', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Payment Declined <span class="count">(%d)</span>',
				'Payment Declined <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'          => 6,
		),
		array(
			'internalname'   => 'refunded',
			'label'          => __( 'Refunded', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Refunded <span class="count">(%d)</span>',
				'Refunded <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'          => 7,
		),
		array(
			'internalname'   => 'refund_pending',
			'label'          => __( 'Refund Pending', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Refund Pending <span class="count">(%d)</span>',
				'Refund Pending <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'          => 8,
		),
		array(
			'internalname'   => 'partially_refunded',
			'label'          => __( 'Partially Refunded', 'wp-e-commerce' ),
			'view_label'     => _nx_noop(
				'Partially Refunded <span class="count">(%d)</span>',
				'Partially Refunded <span class="count">(%d)</span>',
				'Purchase log view links',
				'wp-e-commerce'
			),
			'order'          => 9,
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
	$info_title = __( 'Please backup your website before updating!', 'wp-e-commerce' );
	$info_text =  __( 'Before updating please backup your database and files in case anything goes wrong.', 'wp-e-commerce' );
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
		'name'               => _x( 'Products'                  , 'post type name'             , 'wp-e-commerce' ),
		'singular_name'      => _x( 'Product'                   , 'post type singular name'    , 'wp-e-commerce' ),
		'add_new'            => _x( 'Add New'                   , 'admin menu: add new product', 'wp-e-commerce' ),
		'add_new_item'       => __( 'Add New Product'           , 'wp-e-commerce' ),
		'edit_item'          => __( 'Edit Product'              , 'wp-e-commerce' ),
		'new_item'           => __( 'New Product'               , 'wp-e-commerce' ),
		'view_item'          => __( 'View Product'              , 'wp-e-commerce' ),
		'search_items'       => __( 'Search Products'           , 'wp-e-commerce' ),
		'not_found'          => __( 'No products found'         , 'wp-e-commerce' ),
		'not_found_in_trash' => __( 'No products found in Trash', 'wp-e-commerce' ),
		'menu_name'          => __( 'Products'                  , 'wp-e-commerce' ),
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
			'name'          => __( 'Product Files', 'wp-e-commerce' ),
			'singular_name' => __( 'Product File' , 'wp-e-commerce' ),
		),
	);
	$args = apply_filters( 'wpsc_register_post_types_product_files_args', $args );
	register_post_type( 'wpsc-product-file', $args );

	// Product tags
	$labels = array(
		'name'          => _x( 'Product Tags'        , 'taxonomy general name' , 'wp-e-commerce' ),
		'singular_name' => _x( 'Product Tag'         , 'taxonomy singular name', 'wp-e-commerce' ),
		'search_items'  => __( 'Search Product Tags' , 'wp-e-commerce' ),
		'all_items'     => __( 'All Product Tags'    , 'wp-e-commerce' ),
		'edit_item'     => __( 'Edit Tag'            , 'wp-e-commerce' ),
		'update_item'   => __( 'Update Tag'          , 'wp-e-commerce' ),
		'add_new_item'  => __( 'Add New Product Tag' , 'wp-e-commerce' ),
		'new_item_name' => __( 'New Product Tag Name', 'wp-e-commerce' ),
		'choose_from_most_used' => __('Choose from most used Product Tags', 'wp-e-commerce' ),
		'not_found'	=> __('No Product Tags found', 'wp-e-commerce'),
	);

	$args = array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_in_rest' => true,
		'rest_controller_class' => 'WPSC_REST_Tags_Controller',
		'rewrite' => array(
			'slug' => '/' . sanitize_title_with_dashes( _x( 'tagged', 'slug, part of url', 'wp-e-commerce' ) ),
			'with_front' => false )
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_tag_args', $args );
	register_taxonomy( 'product_tag', 'wpsc-product', $args );

	// Product categories, is heirarchical and can use permalinks
	$labels = array(
		'name'              => _x( 'Product Categories'       , 'taxonomy general name' , 'wp-e-commerce' ),
		'singular_name'     => _x( 'Product Category'         , 'taxonomy singular name', 'wp-e-commerce' ),
		'search_items'      => __( 'Search Product Categories', 'wp-e-commerce' ),
		'all_items'         => __( 'All Product Categories'   , 'wp-e-commerce' ),
		'parent_item'       => __( 'Parent Product Category'  , 'wp-e-commerce' ),
		'parent_item_colon' => __( 'Parent Product Category:' , 'wp-e-commerce' ),
		'edit_item'         => __( 'Edit Product Category'    , 'wp-e-commerce' ),
		'update_item'       => __( 'Update Product Category'  , 'wp-e-commerce' ),
		'add_new_item'      => __( 'Add New Product Category' , 'wp-e-commerce' ),
		'new_item_name'     => __( 'New Product Category Name', 'wp-e-commerce' ),
		'menu_name'         => _x( 'Categories'               , 'taxonomy general name', 'wp-e-commerce' ),
	);
	$args = array(
		'labels'       => $labels,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rest_controller_class' => 'WPSC_REST_Categories_Controller',
		'rewrite'      => array(
			'slug'         => str_replace( basename( home_url() ), '', $wpsc_page_titles['products'] ),
			'with_front'   => false,
			'hierarchical' => (bool) get_option( 'product_category_hierarchical_url', 0 ),
		),
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_category_args', $args );

	register_taxonomy( 'wpsc_product_category', 'wpsc-product', $args );
	$labels = array(
		'name'              => _x( 'Variations'        , 'taxonomy general name' , 'wp-e-commerce' ),
		'singular_name'     => _x( 'Variation'         , 'taxonomy singular name', 'wp-e-commerce' ),
		'search_items'      => __( 'Search Variations' , 'wp-e-commerce' ),
		'all_items'         => __( 'All Variations'    , 'wp-e-commerce' ),
		'parent_item'       => __( 'Parent Variation'  , 'wp-e-commerce' ),
		'parent_item_colon' => __( 'Parent Variations:', 'wp-e-commerce' ),
		'edit_item'         => __( 'Edit Variation'    , 'wp-e-commerce' ),
		'update_item'       => __( 'Update Variation'  , 'wp-e-commerce' ),
		'add_new_item'      => __( 'Add New Variation' , 'wp-e-commerce' ),
		'new_item_name'     => __( 'New Variation Name', 'wp-e-commerce' ),
	);
	$args = array(
		'hierarchical' => true,
		'show_in_rest' => true,
		'rest_controller_class' => 'WPSC_REST_Variations_Controller',
		'query_var'    => 'variations',
		'rewrite'      => false,
		'public'       => true,
		'labels'       => $labels
	);
	$args = apply_filters( 'wpsc_register_taxonomies_product_variation_args', $args );
	// Product Variations, is internally heirarchical, externally, two separate types of items, one containing the other
	register_taxonomy( 'wpsc-variation', 'wpsc-product', $args );

	/**
	 * Fires after the WPSC post types are registered
	 *
	 * no params
	 */
	do_action( 'wpsc_register_post_types_after' );

	/**
	 * Fires after the WPSC taxonomies are registered
	 *
	 * no params
	 */
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
		1  => sprintf( __( 'Product updated. <a href="%s">View product</a>', 'wp-e-commerce' ), esc_url( get_permalink( $post_ID ) ) ),
		2  => __( 'Custom field updated.', 'wp-e-commerce' ),
		3  => __( 'Custom field deleted.', 'wp-e-commerce' ),
		4  => __( 'Product updated.', 'wp-e-commerce' ),
		// translators: %s: date and time of the revision
		5  => isset( $_GET['revision'] ) ? sprintf( __('Product restored to revision from %s', 'wp-e-commerce' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => sprintf( __( 'Product published. <a href="%s">View product</a>', 'wp-e-commerce' ), esc_url( get_permalink( $post_ID ) ) ),
		7  => __( 'Product saved.', 'wp-e-commerce' ),
		8  => sprintf( __( 'Product submitted. <a target="_blank" href="%s">Preview product</a>', 'wp-e-commerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		9  => sprintf( __( 'Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>', 'wp-e-commerce' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i', 'wp-e-commerce' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
		10 => sprintf( __( 'Product draft updated. <a target="_blank" href="%s">Preview product</a>', 'wp-e-commerce' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
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

	if ( is_object( $wpsc_cart ) ) {
		$wpsc_cart->errors = array();
	}

	if ( function_exists( 'wpsc_get_current_customer_id' ) ) {
		// Need to prevent set_cookie from being called at this stage in case the user
		// just logged out because by now, some output must have been printed out.
		$customer_id = wpsc_get_current_customer_id();

		if ( $customer_id ) {
			wpsc_update_customer_cart( $wpsc_cart, $customer_id );
		}
	}

	return true;
}

add_action( 'shutdown', 'wpsc_serialize_shopping_cart' );

/**
 * Changes default "Enter title here" placeholder
 *
 * @param string $title Default Title Placeholder
 * @return string $title New Title Placeholder
 */
function wpsc_change_title_placeholder( $title ) {
	$screen = get_current_screen();

	if  ( 'wpsc-product' == $screen->post_type ) {
		$title =  __( 'Enter product title here', 'wp-e-commerce' );
	}
	return $title;
}

add_filter( 'enter_title_here', 'wpsc_change_title_placeholder' );

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


/**
 * wpsc_cron()
 *
 * Schedules wpsc worpress cron tasks
 *
 * @param none
 * @return void
 */
function wpsc_cron() {
	$default_schedules = array( 'hourly', 'twicedaily', 'daily', 'weekly' );

	/*
	 * Create a cron event for each likely cron schedule.  The likely cron schedules
	 * are the default WordPress cron intervals (hourly, twicedaily and daily are
	 * defined in wordpress 3.5.1) and any cron schedules added by our plugin or
	 * it's related plugins.  We recognize these by checking if the schedule
	 * name is prefixed by 'wpsc_'.
	 */
	foreach ( wp_get_schedules() as $cron => $schedule ) {
		if ( in_array($cron, $default_schedules) || ( stripos($cron, 'wpsc_', 0) === 0 ) ) {
			if ( ! wp_next_scheduled( "wpsc_{$cron}_cron_task" ) )
				wp_schedule_event( time(), $cron, "wpsc_{$cron}_cron_task" );
		}
	}
}
add_action( 'init', 'wpsc_cron' );

/**
 * wpsc_add_weekly_schedule()
 *
 * Creates a weekly schedule event
 *
 * @param none
 * @return void
 */
function wpsc_add_weekly_schedule( $schedules = array()) {
    $schedules['weekly'] = array(
        'interval' => 604800,
        'display'  => __( 'Once Weekly', 'wp-e-commerce' )
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'wpsc_add_weekly_schedule' );

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

/**
 * get the global checkout object, will create it
 *
 * @return wpsc_checkout       the global checkout object
 */
function wpsc_core_get_checkout() {
	global $wpsc_checkout;

	if ( empty( $wpsc_checkout ) || ! is_a( $wpsc_checkout, 'wpsc_checkout' ) ) {
		$wpsc_checkout = new wpsc_checkout();
	}

	$wpsc_checkout->rewind_checkout_items();

	return $wpsc_checkout;

}

/**
 * get the current WPeC database version
 *
 * @return int current database version
 */
function wpsc_core_get_db_version() {
	return intval( get_option( 'wpsc_db_version', 0 ) );
}

/**
 * get the current WPeC database version
 *
 * @return int current database version
 */
function wpsc_core_shipping_enabled() {
	$shipping_disabled = get_option( 'do_not_use_shipping', -1 );

	if ( $shipping_disabled === -1 ) {
		// if shipping enabled comes back as -1 we want to set it to the default value, this is
		// because unset WordPress options are not cached.  That means if this option isn't in the database
		// we could make a trip to the database every time this option is looked at.  This variable
		// can be tested many times per page view, so let's make it clean
		update_option( 'do_not_use_shipping', false );
		$shipping_disabled = false;
	}

	$shipping_disabled = _wpsc_make_value_into_bool( $shipping_disabled );

	return ! $shipping_disabled;
}

/**
 *  flush all WPeC temporary stored data
 *
 * WordPress generallay has two places it stores temporary data, the object cache and the transient store.  When
 * an object cache is configured for WordPress transients are stored in the object cache.  When there isn't an
 * object cache available transients are stored in the WordPress options table.  When clearing temporary data
 * we need to consider both places.
 *
 * @since 3.8.14.1
 *
 */
function wpsc_core_flush_temporary_data() {

	//
	/**
	 * Tell the the rest of the WPeC world it's time to flush all cache data
	 *
	 * @since 3.8.14.1
	 *
	 * no params
	 */
	do_action( 'wpsc_core_flush_transients' );

	$our_saved_transients = _wpsc_remembered_transients();

	// strip off the WordPress transient prefix to get the transient name used when storing the transient, then delete it
	foreach ( $our_saved_transients as $transient_name => $timestamp ) {
		delete_transient( $transient_name );
	}

	/**
	 * Tell the the rest of the WPeC world we have just flushed all temporary data,
	 *
	 * @since 3.8.14.1
	 *
	 * no params
	 */
	do_action( 'wpsc_core_flushed_transients' );
}

/**
 * Rememeber whenever WPeC saves data in a transient
 *
 * @param string $transient
 * @param varies|null $value
 * @param int|null $expiration
 * @return array[string]|false  names of transients saved, false when nothing has been stored
 */
function _wpsc_remembered_transients( $transient = '', $value = null, $expiration = null ) {
	static $wpsc_transients = false;

	if ( $wpsc_transients === false ) {

		// get our saved transients
		$wpsc_transients = get_option( __FUNCTION__,  false );

		if ( $wpsc_transients === false ) {
			// look at the database and see if there are WPeC transients in the options table.  Note that it is possible to track these,
			// using WordPress hooks, but because we need to check for transients that are stored by prior releases of WPeC we go right
			// at the database.
			global $wpdb;

			$wpsc_transients_from_db = $wpdb->get_col( 'SELECT option_name FROM ' . $wpdb->options . ' WHERE `option_name` LIKE "\_transient\_wpsc\_%"' );

			$wpsc_transients = array();

			// strip off the WordPress transient prefix to get the transient name used when storing the transient, then delete it
			foreach ( $wpsc_transients_from_db as $index => $transient_name ) {
				$transient_name = substr( $transient_name, strlen( '_transient_' ) );
				$wpsc_transients[$transient_name] = time();
			}

			// we are all initialized, save our known transients list for later
			update_option( __FUNCTION__, $wpsc_transients );
		}
	}

	// if we are setting a transient, and it is one of ours, and we havn't seen it before, save the name
	if ( ! empty ( $transient ) ) {
		if ( strpos( $transient, 'wpsc_' ) === 0 ||  strpos( $transient, '_wpsc_' ) === 0 ) {
			if ( ! isset( $wpsc_transients[$transient] ) ) {
				$wpsc_transients[$transient] = time();
				update_option( __FUNCTION__, $wpsc_transients );
			}
		}
	}

	return $wpsc_transients;
}

add_action( 'setted_transient', '_wpsc_remembered_transients' , 10, 3 );

/**
 * When we change versions, aggressively clear temporary data and WordPress cache.
 *
 * @since 3.8.14.1
 *
 * @access private
 */
function _wpsc_clear_wp_cache_on_version_change() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$version_we_last_stored = get_option( __FUNCTION__, false );

	if ( $version_we_last_stored != WPSC_VERSION ) {

		// version changed, clear temporary data
		wpsc_core_flush_temporary_data();

		// version changed, flush the object cache
		wp_cache_flush();

		if ( false === $version_we_last_stored ) {
			// first time through we create the autoload option, we will read it every time
			add_option( __FUNCTION__, WPSC_VERSION, null, true );
		} else {
			update_option( __FUNCTION__, WPSC_VERSION );
		}
	}
}

add_action( 'admin_init', '_wpsc_clear_wp_cache_on_version_change', 1 );

/**
 * Adds custom WP eCommerce tables to `tables_to_repair` array.
 *
 * WordPress provides a link, `admin_url( 'maint/repair.php' )`, that allows users to repair database tables.
 * We find that this becomes necessary often times when visitor/visitor meta tables become corrupt.
 * Symptoms of a corrupt visitor/meta table include disappearing carts, refreshing checkout pages, etc.
 *
 * In a future version, we will likely have a `System` page that would include a link to the repair.php page.
 *
 * @since  3.11.0
 *
 * @param  array $tables Core tables
 *
 * @return array $tables Core + WP eCommerce tables
 */
function wpsc_add_tables_to_repair( $tables ) {
	global $wpec;

	return array_merge( $wpec->setup_table_names(), $tables );
}

add_filter( 'tables_to_repair', 'wpsc_add_tables_to_repair' );


/**
 * Updates a user's digital downloads.
 *
 * @param  integer $user_id [description]
 * @return [type]           [description]
 */
function wpsc_update_user_downloads( $user_id = 0 ) {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	$purchase_ids = $wpdb->get_results( $wpdb->prepare( "SELECT c.prodid, p.id, c.id as cart_id FROM " . WPSC_TABLE_CART_CONTENTS . " as c INNER JOIN " . WPSC_TABLE_PURCHASE_LOGS . " as p ON p.id = c.purchaseid WHERE p.user_ID = %d AND p.processed IN (3,4,5) GROUP BY c.prodid", $user_id ) );

	if ( empty( $purchase_ids ) || apply_filters( 'wpsc_do_not_update_downloads', false ) ) {
		return;
	}

	$downloads = get_option( 'max_downloads' );

	foreach ( $purchase_ids as $key => $id_pairs ) {
		if ( ! apply_filters( "wpsc_update_downloads_{$id_pairs->prodid}", true, $id_pairs ) ) {
			unset( $purchase_ids[ $key ] );
		} else {

			if ( get_post_field( 'post_parent', $id_pairs->prodid ) ) {
				$parents = array( $id_pairs->prodid, get_post_field( 'post_parent', $id_pairs->prodid ) );
			} else {
				$parents = array( $id_pairs->prodid );
			}

			$args = apply_filters( 'wpsc_update_user_downloads_file_args', array(
				'post_type'       => 'wpsc-product-file',
				'post_parent__in' => $parents,
				'numberposts'     => -1,
				'post_status'     => 'inherit'
			), $id_pairs, $user_id );

			$product_files = (array) get_posts( $args );

			foreach ( $product_files as $file ) {

				if ( ! apply_filters( "wpsc_update_downloads_{$id_pairs->prodid}_{$file->ID}", true, $id_pairs, $file ) ) {
					continue;
				}

				$user_has_download = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . WPSC_TABLE_DOWNLOAD_STATUS . " WHERE fileid = %d AND purchid = %d AND product_id = %d", $file->ID, $id_pairs->id, $id_pairs->prodid ) );

				if ( $user_has_download ) {
					continue;
				}

				$unique_id = sha1( uniqid( mt_rand(), true ) );

				$wpdb->insert(
					WPSC_TABLE_DOWNLOAD_STATUS,
					array(
							'product_id' => $id_pairs->prodid,
							'fileid'     => $file->ID,
							'purchid'    => $id_pairs->id,
							'cartid'     => $id_pairs->cart_id,
							'uniqueid'   => $unique_id,
							'downloads'  => $downloads,
							'active'     => 1,
							'datetime'   => date( 'Y-m-d H:i:s' )
					),
					array(
							'%d',
							'%d',
							'%d',
							'%d',
							'%s',
							'%s',
							'%d',
							'%s',
					)
				);
			}
		}
	}
}

add_action( 'wpsc_template_before_customer-account-digital-content', 'wpsc_update_user_downloads', 5 );
add_action( 'wpsc_user_profile_section_downloads'                  , 'wpsc_update_user_downloads', 5 );

/**
 * Checks visitor and visitor meta table for corruption.
 *
 * If tables are corrupted, site admins are alerted and given the ability to repair them.
 *
 * @since  3.9.4
 * @return void
 */
function wpsc_check_visitor_tables() {

	// Don't check if current user is not a store admin or if we have checked in the last hour.
	if ( wpsc_is_store_admin() && ! ( $check = get_transient( 'wpsc_tables_intact' ) ) ) {
		global $wpdb;

		$visitor_check      = $wpdb->get_row( "CHECK TABLE {$wpdb->wpsc_visitors}" );
		$visitor_meta_check = $wpdb->get_row( "CHECK TABLE {$wpdb->wpsc_visitormeta}" );

		// If both tables are fine
		if ( 'OK' == $visitor_check->Msg_text && 'OK' == $visitor_meta_check->Msg_text )  {
			set_transient( 'wpsc_tables_intact', true, HOUR_IN_SECONDS );
			return;
		} else {
			set_transient( 'wpsc_tables_intact', false, HOUR_IN_SECONDS );
		}

		add_action( 'all_admin_notices', 'wpsc_visitor_tables_need_repair' );
	}
}

add_action( 'init', 'wpsc_check_visitor_tables' );

/**
 * Adds admin notice to all screens, for store administators, when database tables are in need of repair.
 *
 * @since  3.9.4
 * @return void
 */
function wpsc_visitor_tables_need_repair() {
	echo '<div class="error"><p>' . sprintf( __( 'It appears that your WP eCommerce database tables are in need of repair. This is very important for both security and performance. <a href="%s">Repair your tables now</a>. <br />Note: If you encounter errors upon repairing your tables, simply refresh the page.', 'wp-e-commerce' ), esc_url( admin_url( 'maint/repair.php' ) ) ) . '</p></div>';
}

/**
 * Defines `WP_ALLOW_REPAIR` to true when WP eCommerce tables are in need of repair.
 *
 * @since  3.9.4
 * @return void
 */
function wpsc_repair_tables() {

	$needs_repair = ! get_transient( 'wpsc_tables_intact' );

	if ( ! defined( 'WP_ALLOW_REPAIR' ) && apply_filters( 'wpsc_tables_need_repair', $needs_repair ) && ( defined( 'WP_REPAIRING' ) && WP_REPAIRING ) ) {
		define( 'WP_ALLOW_REPAIR', true );
	}
}

add_action( 'wpsc_init', 'wpsc_repair_tables' );

/**
 * Addes 'wpsc' to the list of Say What aliases after moving to WordPress.org * * language packs.
 *
 * @since  3.11.0
 *
 * @param  array $aliases Say What domain aliases
 * @return array          Say What domain alises with 'wpsc' added
 */
function wpsc_say_what_domain_aliases( $aliases ) {
	$aliases['wp-e-commerce'][] = 'wpsc';

	return $aliases;
}

add_filter( 'say_what_domain_aliases', 'wpsc_say_what_domain_aliases' );

/**
 * Checks if system is using a specific version of the theme engine.
 *
 * Defaults to 1.0.
 *
 * @since 3.11.5
 * @param  string  $version Version number
 * @return boolean          Whether or not this is the theme engine being used.
 */
function wpsc_is_theme_engine( $version = '1.0' ) {
	$te = get_option( 'wpsc_get_active_theme_engine', $version );

	return $version == $te;
}
