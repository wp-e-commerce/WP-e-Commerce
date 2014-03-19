<?php
/**
 * WP eCommerce Main Admin functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

// admin includes
require_once( WPSC_FILE_PATH . '/wpsc-admin/display-update.page.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/display-items.page.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/display-upgrades.page.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/display-items-functions.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/product-functions.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/save-data.functions.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/updating-functions.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/display-coupons.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/purchaselogs.class.php' );
require_once( WPSC_FILE_PATH . '/wpsc-includes/theming.class.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/ajax.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/init.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/ajax-and-init.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/display-options-settings.page.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/db-upgrades/upgrade.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/media.php' );
require_once( WPSC_FILE_PATH . '/wpsc-admin/users.php' );

if ( ( isset( $_SESSION['wpsc_activate_debug_page'] ) && ( $_SESSION['wpsc_activate_debug_page'] == true ) ) || ( defined( 'WPSC_ADD_DEBUG_PAGE' ) && ( constant( 'WPSC_ADD_DEBUG_PAGE' ) == true ) ) )
	require_once( WPSC_FILE_PATH . '/wpsc-admin/display-debug.page.php' );

if ( ! get_option( 'wpsc_checkout_form_sets' ) ) {
	$form_sets = array( __( 'Default Checkout Forms', 'wpsc' ) );
	update_option( 'wpsc_checkout_form_sets', $form_sets );
}
/**
 * wpsc_query_vars_product_list sets the ordering for the edit-products page list
 *
 * @since 3.8
 * @access public
 *
 * @uses get_option()   Gets option from the DB given key
 *
 * @param array     $vars  req  Default query arguments
 * @return array    $vars       Modified query arguments
 */
function wpsc_query_vars_product_list( $vars ){

	if( 'wpsc-product' != $vars['post_type'] || in_array( $vars['orderby'], array( 'meta_value_num', 'meta_value' ) ) )
	    return $vars;

	$vars['posts_per_archive_page'] = 0;

	if( 'dragndrop' == get_option( 'wpsc_sort_by' ) ){
		$vars['orderby'] = 'menu_order title';
		$vars['order'] = 'desc';
		$vars['nopaging'] = true;
	}

    return $vars;
}

/**
 * Admin Edit Posts Order
 *
 * @since 3.8.12
 * @access public
 *
 * @param   string  $orderby_sql  Order by SQL.
 * @return  string  Filtered order by SQL.
 */
function wpsc_admin_edit_posts_orderby( $orderby_sql ) {
	global $wp_query, $wpdb;
	if ( 'dragndrop' == get_option( 'wpsc_sort_by' ) ) {
		if ( function_exists( 'is_main_query' ) && is_main_query() && 'wpsc-product' == get_query_var( 'post_type' ) && is_tax( 'wpsc_product_category' ) ) {
			if ( ! empty( $orderby_sql ) )
				$orderby_sql = ', ' . $orderby_sql;
			$orderby_sql = " {$wpdb->term_relationships}.term_order ASC" . $orderby_sql;
			remove_filter( 'posts_orderby', 'wpsc_admin_edit_posts_orderby' );
		}
	}
	return $orderby_sql;
}
add_filter( 'posts_orderby', 'wpsc_admin_edit_posts_orderby' );

/**
 * setting the screen option to between 1 and 999
 *
 * @since 3.8
 * @access public
 *
 * @uses update_user_option()   Updates user option given userid, key, value
 *
 * @param           $status
 * @param string    $option     req     Name of option being saved
 * @param string    $value      req     Value of option being saved
 * @return $value after changes...
 */
function wpsc_set_screen_option($status, $option, $value){
	if( in_array($option, array ("edit_wpsc_variation_per_page","edit_wpsc_product_per_page" )) ){
		if ( "edit_wpsc_variation_per_page" == $option ){
			global $user_ID;
			update_user_option($user_ID,'edit_wpsc-variation_per_page',$value);
		}
		return $value;
	}
}
add_filter('set-screen-option', 'wpsc_set_screen_option', 99, 3);

/**
 * When rearranging the products for drag and drop it is easiest to arrange them when they are all on the same page...
 * @access public
 *
 * @since 3.8
 * @access public
 *
 * @uses get_option()   Gets option from the database given key
 *
 * @param int       $per_page   req     number of products per page
 * @param string    $post_type  req     name of current post type
 * @return $per_page after changes...
 */
function wpsc_drag_and_drop_ordering($per_page, $post_type){
	global $wpdb;
	if ( 'wpsc-product' == $post_type && 'dragndrop' == get_option( 'wpsc_sort_by' ) && $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_type`='wpsc-product' AND `post_parent`=0" ) )
		$per_page = $count;
	return $per_page;
}
add_filter( 'request', 'wpsc_query_vars_product_list' );
add_filter('edit_posts_per_page' , 'wpsc_drag_and_drop_ordering', 10, 2 );

/**
 * Checks whether to display or hide the update wp-e-commerce link
 *
 * @since 3.8
 * @access public
 *
 * @uses get_option()   Gets option from DB given key
 *
 * @return boolean true - show link, false- hide link
 */
function wpsc_show_update_link() {
	global $wpdb;
	// Check if old product_list table exists
	// If it exists AND get_option wpsc_upgrade_complete is not true then return true
	$sql = 'SHOW TABLES LIKE "'.$wpdb->prefix.'wpsc_product_list"';
	$var = $wpdb->get_var( $sql );
	if ( !empty( $var ) && false == get_option( 'wpsc_hide_update' ) )
		return true;
	else
		return false;
}
/**
 * wpsc_admin_pages function, all the definitons of admin pages are stores here.
 * No parameters, returns nothing
 *
 * Fairly standard wordpress plugin API stuff for adding the admin pages, rearrange the order to rearrange the pages
 * The bits to display the options page first on first use may be buggy, but tend not to stick around long enough to be identified and fixed
 * if you find bugs, feel free to fix them.
 *
 * If the permissions are changed here, they will likewise need to be changed for the other sections of the admin that either use ajax
 * or bypass the normal download system.
 *
 * @access public
 *
 * @uses wpsc_show_update_link()    Decides whether or not to show the update link
 * @uses add_submenu_page()         Adds a WordPress submenu page
 * @uses apply_filters()            Calls wpsc_upgrades_cap allows hooking caps for adiministrator
 * @uses apply_filters()            Calls wpsc_coupon_cap allows filtering for the coupon caps
 * @uses add_options_page()         Adds a submenu to the settings page
 * @uses add_action()               Calls 'admin_print_scripts.$edit_options_page prints out WPEC admin scripts
 * @uses apply_filters()            Calls 'wpsc_additional_pages' Passes the page_hooks and product_page URL
 * @uses do_action()                Calls 'wpsc_add_submenu' Allows you to hook in to the WPEC menu
 * @uses update_option()            Updates option given key and value
 */
function wpsc_admin_pages() {

	// Code to enable or disable the debug page
	if ( isset( $_GET['wpsc_activate_debug_page'] ) ) {
		if ( 'true' == $_GET['wpsc_activate_debug_page'] ) {
			$_SESSION['wpsc_activate_debug_page'] = true;
		} else if ( 'false' == $_GET['wpsc_activate_debug_page'] ) {
				$_SESSION['wpsc_activate_debug_page'] = false;
			}
	}

	// Add to Dashboard
	// $page_hooks[] = $purchase_log_page = add_submenu_page( 'index.php', __( 'Store Sales', 'wpsc' ), __( 'Store Sales', 'wpsc' ), 'administrator', 'wpsc-sales-logs', 'wpsc_display_sales_logs' );

	if ( wpsc_show_update_link() )
		$page_hooks[] = add_submenu_page( 'index.php', __( 'Update Store', 'wpsc' ), __( 'Store Update', 'wpsc' ), 'administrator', 'wpsc-update', 'wpsc_display_update_page' );

	$store_upgrades_cap = apply_filters( 'wpsc_upgrades_cap', 'administrator' );
	$page_hooks[] = add_submenu_page( 'index.php', __( 'Store Upgrades', 'wpsc' ), __( 'Store Upgrades', 'wpsc' ), $store_upgrades_cap, 'wpsc-upgrades', 'wpsc_display_upgrades_page' );

	$purchase_logs_cap = apply_filters( 'wpsc_purchase_logs_cap', 'administrator' );
	$page_hooks[] = $purchase_logs_page = add_submenu_page( 'index.php', __( 'Store Sales', 'wpsc' ), __( 'Store Sales', 'wpsc' ), $purchase_logs_cap, 'wpsc-purchase-logs', 'wpsc_display_purchase_logs_page' );

	// Set the base page for Products
	$products_page = 'edit.php?post_type=wpsc-product';

	$manage_coupon_cap = apply_filters( 'wpsc_coupon_cap', 'administrator' );
	$page_hooks[] = $edit_coupons_page = add_submenu_page( $products_page , __( 'Coupons', 'wpsc' ), __( 'Coupons', 'wpsc' ), $manage_coupon_cap, 'wpsc-edit-coupons', 'wpsc_display_coupons_page' );

	// Add Settings pages
	$page_hooks[] = $edit_options_page = add_options_page( __( 'Store Settings', 'wpsc' ), __( 'Store', 'wpsc' ), 'administrator', 'wpsc-settings', 'wpsc_display_settings_page' );
	add_action( 'admin_print_scripts-' . $edit_options_page , 'wpsc_print_admin_scripts' );

	// Debug Page
	if ( ( defined( 'WPSC_ADD_DEBUG_PAGE' ) && ( WPSC_ADD_DEBUG_PAGE == true ) ) || ( isset( $_SESSION['wpsc_activate_debug_page'] ) && ( true == $_SESSION['wpsc_activate_debug_page'] ) ) )
		$page_hooks[] = add_options_page( __( 'Store Debug', 'wpsc' ), __( 'Store Debug', 'wpsc' ), 'administrator', 'wpsc-debug', 'wpsc_debug_page' );

	$page_hooks = apply_filters( 'wpsc_additional_pages', $page_hooks, $products_page );

	do_action( 'wpsc_add_submenu' );

	// Include the javascript and CSS for this page
	// This is so important that I can't even express it in one line

	foreach ( $page_hooks as $page_hook ) {
		add_action( 'load-' . $page_hook, 'wpsc_admin_include_css_and_js_refac' );

		switch ( $page_hook ) {

		case $edit_options_page :
			add_action( 'load-' . $page_hook, 'wpsc_admin_include_optionspage_css_and_js' );
			break;

		case $purchase_logs_page :
			add_action( 'admin_head', 'wpsc_product_log_rss_feed' );
			add_action( 'load-' . $page_hook, 'wpsc_admin_include_purchase_logs_css_and_js' );
			break;

		case $edit_coupons_page :
			add_action( 'load-' . $page_hook, 'wpsc_admin_include_coupon_js' );
			break;
		}
	}

	// Some updating code is run from here, is as good a place as any, and better than some
	if ( ( null == get_option( 'wpsc_trackingid_subject' ) ) && ( null == get_option( 'wpsc_trackingid_message' ) ) ) {
		update_option( 'wpsc_trackingid_subject', __( 'Product Tracking Email', 'wpsc' ) );
		update_option( 'wpsc_trackingid_message', __( "Track & Trace means you may track the progress of your parcel with our online parcel tracker, just login to our website and enter the following Tracking ID to view the status of your order.\n\nTracking ID: %trackid%\n", 'wpsc' ) );
	}

	add_action( 'load-' . $edit_options_page, 'wpsc_load_settings_page', 1 );

	// only load the purchase log list table and page classes when it's necessary
	// also, the WPSC_Purchase_Logs_List_Table needs to be initializied before admin_header.php
	// is loaded, therefore wpsc_load_purchase_logs_page needs to do this as well
	add_action( 'load-' . $purchase_logs_page, 'wpsc_load_purchase_logs_page', 1 );

	// Help tabs
	add_action( 'load-' . $edit_options_page , 'wpsc_add_help_tabs' );
	add_action( 'load-' . $purchase_logs_page , 'wpsc_add_help_tabs' );
	add_action( 'load-' . $edit_coupons_page , 'wpsc_add_help_tabs' );
	add_action( 'load-edit.php'              , 'wpsc_add_help_tabs' );
	add_action( 'load-post.php'              , 'wpsc_add_help_tabs' );
	add_action( 'load-post-new.php'          , 'wpsc_add_help_tabs' );
	add_action( 'load-edit-tags.php'         , 'wpsc_add_help_tabs' );
}

/**
 * This function adds contextual help to all WPEC screens.
 * add_contextual_help() is supported as well as $screen->add_help_tab().
 *
 * @since 3.8.8
 * @access public
 *
 * @uses get_current_screen()   Returns WordPress admin screen object
 * @uses get_bloginfo()         Returns information about the WordPress site
 * @uses add_help_tab()         Used to add a tab to the contextual help menu
 */
function wpsc_add_help_tabs() {
	$tabs = array(
		// Store Settings Page
		'settings_page_wpsc-settings' => array(
			'title' => _x( 'Store Settings', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'category/configuring-your-store/store-settings/'   => _x( 'Store Settings Overview'          , 'contextual help link', 'wpsc' ),
				'category/configuring-your-store/payment-gateways/' => _x( 'Configuring Your Payment Gateways', 'contextual help link', 'wpsc' ),
				'category/configuring-your-store/shipping/'         => _x( 'Configuring Your Shipping Modules', 'contextual help link', 'wpsc' ),
			),
		),

		// Sales Log Page
		'dashboard_page_wpsc-purchase-logs' => array(
			'title' => _x( 'Sales Log', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'documentation/sales/' => _x( 'Monitor and Manage Your Sales', 'contextual help link', 'wpsc' ),
			),
		),

		// Main Products Listing Admin Page (edit.php?post_type=wpsc-product)
		'edit-wpsc-product' => array(
			'title' => _x( 'Product Catalog', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'category/managing-your-store/' => _x( 'Managing Your Store', 'contextual help link', 'wpsc' ),
			),
		),

		// Add and Edit Product Pages
		'wpsc-product' => array(
			'title' => _x( 'Add and Edit Product', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'category/managing-your-store/'   => _x( 'Managing Your Store'   , 'contextual help link', 'wpsc' ),
				'resource/video-adding-products/' => _x( 'Video: Adding Products', 'contextual help link', 'wpsc' ),
			),
		),

		// Product Tags Page
		'edit-product_tag' => array(
			'title' => _x( 'Product Tags', 'contextual help tab', 'wpsc' ),
			'links' =>array(
				'resource/video-product-tags/' => _x( 'Video: Product Tags', 'contextual help link', 'wpsc' ),
			),
		),

		// Product Category Page
		'edit-wpsc_product_category' => array(
			'title' => _x( 'Product Categories', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'resource/video-creating-product-categories/' => _x( 'Video: Creating Product Categories', 'contextual help link', 'wpsc' ),
			),
		),

		// Product Variations Page
		'edit-wpsc-variation' => array(
			'title' => _x( 'Product Variations', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'category/managing-your-store/' => _x( 'Managing Your Store', 'contextual help link', 'wpsc' ),
			),
		),

		// Coupon Page
		'wpsc-product_page_wpsc-edit-coupons' => array(
			'title' => _x( 'Coupons', 'contextual help tab', 'wpsc' ),
			'links' => array(
				'resource/video-creating-coupons/' => _x( 'Video: Creating Coupons', 'contextual help link', 'wpsc' ),
			),
		),
	);

	$screen = get_current_screen();
	if ( array_key_exists( $screen->id, $tabs ) ) {
		$tab = $tabs[$screen->id];
		$content = '<p><strong>' . __( 'For More Information', 'wpsc' ) . '</strong></p>';
		$links = array();
		foreach( $tab['links'] as $link => $link_title ) {
			$link = 'http://docs.getshopped.org/' . $link;
			$links[] = '<a target="_blank" href="' . esc_url( $link ) . '">' . esc_html( $link_title ) . '</a>';
		}
		$content .= '<p>' . implode( '<br />', $links ) . '</p>';

		$screen->add_help_tab( array(
			'id'      => $screen->id . '_help',
			'title'   => $tab['title'],
			'content' => $content,
		) );

	}
}

/**
 * Includes purchase logs CSS and JS
 *
 * @acces public
 *
 * @uses wp_enqueue_script()    Recommended way of adding scripts in WordPress
 * @uses wp_localize_script()   Adds noncing and other data to the logs script
 */
function wpsc_admin_include_purchase_logs_css_and_js() {
	wp_enqueue_script( 'wp-e-commerce-purchase-logs', WPSC_URL . '/wpsc-admin/js/purchase-logs.js', array( 'jquery' ), WPSC_VERSION . '.' . WPSC_MINOR_VERSION );
	wp_localize_script( 'wp-e-commerce-purchase-logs', 'WPSC_Purchase_Logs_Admin', array(
		'nonce'                                  => wp_create_nonce( 'wpsc_purchase_logs' ),
		'change_purchase_log_status_nonce'       => _wpsc_create_ajax_nonce( 'change_purchase_log_status' ),
		'purchase_log_save_tracking_id_nonce'    => _wpsc_create_ajax_nonce( 'purchase_log_save_tracking_id' ),
		'purchase_log_send_tracking_email_nonce' => _wpsc_create_ajax_nonce( 'purchase_log_send_tracking_email' ),
		'sending_message'                        => _x( 'sending...', 'sending tracking email for purchase log', 'wpsc' ),
		'sent_message'                           => _x( 'Email Sent!', 'sending tracking email for purchase log', 'wpsc' ),
		'current_view'                           => empty( $_REQUEST['status'] ) ? 'all' : $_REQUEST['status'],
		'current_filter'                         => empty( $_REQUEST['m'] ) ? '' : $_REQUEST['m'],
		'current_page'                           => empty( $_REQUEST['paged']) ? '' : $_REQUEST['paged'],
	) );
}

/**
 * Loads the WPEC settings page
 *
 * @access public
 *
 * @uses WPSC_Settings_Page::get_instance()   Gets instance of WPEC settings page
 */
function wpsc_load_settings_page() {
	require_once('settings-page.php');
	WPSC_Settings_Page::get_instance();
}

/**
 * Leads the purchase logs page
 *
 * @uses WPSC_Purchase_Log_Page()     Loads the edit and view sales page
 */
function wpsc_load_purchase_logs_page() {
	require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/purchase-log-list-table-class.php' );
	require_once( WPSC_FILE_PATH . '/wpsc-admin/display-sales-logs.php' );
	$page = new WPSC_Purchase_Log_Page();
}

/**
 * Displays the WPEC purchase logs
 *
 * @uses do_action()  Calls 'wpsc_display_purchase_logs_page' allows hooking of the sales log page
 */
function wpsc_display_purchase_logs_page() {
	do_action( 'wpsc_display_purchase_logs_page' );
}

/**
 * Produces an RSS feed for the product log
 *
 * @uses add_query_arg()  Allows you to add arguments to the end of a URL
 * @uses admin_url()      Retrieves URL to the WordPress admin
 */
function wpsc_product_log_rss_feed() {
	echo "<link type='application/rss+xml' href='" . add_query_arg( array( 'rss' => 'true', 'rss_key' => 'key', 'action' => 'purchase_log', 'type' => 'rss' ), admin_url( 'index.php' ) ) . "' title='" . esc_attr( 'WP e-Commerce Purchase Log RSS', 'wpsc' ) . "' rel='alternate' />";
}

/**
 * Includes and enqueues scripts and styles for coupons
 *
 * @uses wp_enqueue_style()   Includes and prints styles for WPEC in the WordPress admin
 * @uses wp_enqueue_script()  Includes and prints scripts for WPEC in the WordPress admin
 */
function wpsc_admin_include_coupon_js() {

	// Variables
	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;

	// Coupon CSS
	wp_enqueue_style( 'wp-e-commerce-admin_2.7',        WPSC_URL         . '/wpsc-admin/css/settingspage.css', false, false,               'all' );
	wp_enqueue_style( 'wp-e-commerce-admin',            WPSC_URL         . '/wpsc-admin/css/admin.css',        false, $version_identifier, 'all' );

	// Coupon JS
	wp_enqueue_script( 'wp-e-commerce-admin-parameters', admin_url( 'admin.php?wpsc_admin_dynamic_js=true' ), false,                     $version_identifier );
	wp_enqueue_script( 'livequery',                     WPSC_URL         . '/wpsc-admin/js/jquery.livequery.js',             array( 'jquery' ),         '1.0.3' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'wp-e-commerce-admin_legacy',    WPSC_URL         . '/wpsc-admin/js/admin-legacy.js',                 array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), $version_identifier );

	wp_enqueue_style ( 'wpsc-jquery-ui-datepicker', WPSC_URL . '/wpsc-admin/css/jquery.ui.datepicker-' . get_user_option( 'admin_color' ) . '.css', false, $version_identifier );
}

/**
 * Includes and enqueues scripts and styles for the WPEC options page
 *
 * @uses wp_enqueue_script()          Includes and prints out the JS for the WPEC options page
 * @uses wp_localize_script()         Sets up the JS vars needed
 * @uses _wpsc_create_ajax_nonce()    Alias for wp_create_nonce, creates a random one time use token
 * @uses get_current_tab_id()         Returns the current tab id
 * @uses wp_enqueue_style()           Includes and prints out the CSS for the WPEC options page
 */
function wpsc_admin_include_optionspage_css_and_js() {
	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
	wp_enqueue_script( 'wp-e-commerce-admin-settings-page', WPSC_URL . '/wpsc-admin/js/settings-page.js', array( 'jquery-query' ), $version_identifier );

	wp_localize_script( 'wp-e-commerce-admin-settings-page', 'WPSC_Settings_Page', array(
		'navigate_settings_tab_nonce'         => _wpsc_create_ajax_nonce( 'navigate_settings_tab' ),
		'payment_gateway_settings_form_nonce' => _wpsc_create_ajax_nonce( 'payment_gateway_settings_form' ),
		'shipping_module_settings_form_nonce' => _wpsc_create_ajax_nonce( 'shipping_module_settings_form' ),
		'display_region_list_nonce'           => _wpsc_create_ajax_nonce( 'display_region_list' ),
		'update_checkout_fields_order_nonce'  => _wpsc_create_ajax_nonce( 'update_checkout_fields_order' ),
		'add_tax_rate_nonce'                  => _wpsc_create_ajax_nonce( 'add_tax_rate' ),
		'current_tab'                         => WPSC_Settings_Page::get_instance()->get_current_tab_id(),
		'before_unload_dialog'                => __( 'The changes you made will be lost if you navigate away from this page.', 'wpsc' ),
		'ajax_navigate_confirm_dialog'        => __( 'The changes you made will be lost if you navigate away from this page.', 'wpsc' ) . "\n\n" . __( 'Click OK to discard your changes, or Cancel to remain on this page.' ),
		'edit_field_options'                  => __( 'Edit Options', 'wpsc' ),
		'hide_edit_field_options'             => __( 'Hide Options', 'wpsc' ),
		'delete_form_set_confirm'             => __( 'Are you sure you want to delete %s? Submitted data of this form set will also be removed from sales logs.', 'wpsc' ),
	) );

	wp_enqueue_style( 'wp-e-commerce-admin_2.7', WPSC_URL . '/wpsc-admin/css/settingspage.css', false, false, 'all' );
	wp_enqueue_style( 'wp-e-commerce-ui-tabs', WPSC_URL . '/wpsc-admin/css/jquery.ui.tabs.css', false, $version_identifier, 'all' );
}

/**
 * Sets up the WPEC metaboxes
 *
 * @uses remove_meta_box()    Removes the default taxonomy meta box so our own can be added
 * @uses add_meta_bax()       Adds metaboxes to the WordPress admin interface
 */
function wpsc_meta_boxes() {
	global $post;
	$pagename = 'wpsc-product';
	remove_meta_box( 'wpsc-variationdiv', 'wpsc-product', 'side' );

	//if a variation page do not show these metaboxes
	if ( is_object( $post ) && $post->post_parent == 0 ) {
		add_meta_box( 'wpsc_product_variation_forms'    , __( 'Variations', 'wpsc' )           , 'wpsc_product_variation_forms'    , $pagename, 'normal', 'high' );
	} else if( is_object( $post ) && $post->post_status == "inherit" ) {
		remove_meta_box( 'tagsdiv-product_tag'             , 'wpsc-product', 'core' );
		remove_meta_box( 'wpsc_product_categorydiv'        , 'wpsc-product', 'core' );
	}

	add_meta_box( 'wpsc_price_control_forms', __('Product Pricing', 'wpsc'), 'wpsc_price_control_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_stock_control_forms', __('Stock Inventory', 'wpsc'), 'wpsc_stock_control_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_product_taxes_forms', __('Taxes', 'wpsc'), 'wpsc_product_taxes_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_product_delivery_forms', __('Product Delivery', 'wpsc'), 'wpsc_product_delivery_forms', $pagename, 'normal', 'high' );
	add_meta_box( 'wpsc_product_details_forms', __('Product Details', 'wpsc'), 'wpsc_product_details_forms', $pagename, 'normal', 'high' );
}

add_action( 'admin_footer', 'wpsc_meta_boxes' );
add_action( 'admin_enqueue_scripts', 'wpsc_admin_include_css_and_js_refac' );

/**
 * Includes the JS and CSS
 *
 * @param string    $pagehook     The pagehook for the currently viewing page, provided by the 'admin_enqueue_scripts' action
 *
 * @uses wp_admin_css()               Enqueues or prints a stylesheet in the admin
 * @uses wp_enqueue_script()          Enqueues the specified script
 * @uses wp_localize_script()         Sets up the JS vars needed
 * @uses wp_enqueue_style()           Enqueues the styles
 * @uses wp_dequeue_script()          Removes a previously enqueued script by handle
 * @uses _wpsc_create_ajax_nonce()    Alias for wp_create_nonce, creates a random one time use token
 */
function wpsc_admin_include_css_and_js_refac( $pagehook ) {
	global $post_type, $post;

	$current_screen = get_current_screen();

	if ( version_compare( get_bloginfo( 'version' ), '3.3', '<' ) )
		wp_admin_css( 'dashboard' );

	if ( 'dashboard_page_wpsc-sales-logs' == $current_screen->id ) {
		// jQuery
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Metaboxes
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );
	}

	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
	$pages = array( 'index.php', 'options-general.php', 'edit.php', 'post.php', 'post-new.php' );

	if ( ( in_array( $pagehook, $pages ) && $post_type == 'wpsc-product' )  || $current_screen->id == 'edit-wpsc_product_category' || $current_screen->id == 'dashboard_page_wpsc-sales-logs' || $current_screen->id == 'dashboard_page_wpsc-purchase-logs' || $current_screen->id == 'settings_page_wpsc-settings' || $current_screen->id == 'wpsc-product_page_wpsc-edit-coupons' || $current_screen->id == 'edit-wpsc-variation' || $current_screen->id == 'wpsc-product-variations-iframe' || ( $pagehook == 'media-upload-popup' && get_post_type( $_REQUEST['post_id'] ) == 'wpsc-product' ) ) {
		wp_enqueue_script( 'livequery',                      WPSC_URL . '/wpsc-admin/js/jquery.livequery.js',             array( 'jquery' ), '1.0.3' );
		wp_enqueue_script( 'wp-e-commerce-admin-parameters', admin_url( '/?wpsc_admin_dynamic_js=true' ), false,             $version_identifier );
		wp_enqueue_script( 'wp-e-commerce-admin',            WPSC_URL . '/wpsc-admin/js/admin.js',                        array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), $version_identifier, false );
		wp_enqueue_script( 'wpsc-sortable-table', WPSC_URL . '/wpsc-admin/js/sortable-table.js', array( 'jquery' ) );

		if ( in_array( $current_screen->id, array( 'wpsc-product', 'edit-wpsc-variation', 'wpsc-product' ) ) ) {
			wp_enqueue_script( 'wp-e-commerce-variations', WPSC_URL . '/wpsc-admin/js/variations.js', array( 'jquery', 'wpsc-sortable-table' ), $version_identifier );
			wp_localize_script(
				'wp-e-commerce-variations',  // handle
				'WPSC_Variations',           // variable name
				array(                       // args
					'thickbox_title' => __( 'Add Media - %s', 'wpsc' ),
				)
			);
		}
		wp_enqueue_style( 'wp-e-commerce-admin', WPSC_URL . '/wpsc-admin/css/admin.css', false, $version_identifier, 'all' );
		wp_enqueue_style( 'wp-e-commerce-admin-dynamic', admin_url( "admin.php?wpsc_admin_dynamic_css=true" ), false, $version_identifier, 'all' );
		// Localize scripts
		wp_localize_script( 'wp-e-commerce-admin', 'wpsc_adminL10n', array(
			'dragndrop_set'            => ( get_option( 'wpsc_sort_by' ) == 'dragndrop' ? 'true' : 'false' ),
			'save_product_order_nonce' => _wpsc_create_ajax_nonce( 'save_product_order' ),
			'l10n_print_after'         => 'try{convertEntities(wpsc_adminL10n);}catch(e){};',
			'empty_coupon'             => esc_html__( 'Please enter a coupon code.', 'wpsc' ),
			'bulk_edit_no_vars'        => esc_html__( 'Quick Edit options are limited when editing products that have variations. You will need to edit the variations themselves.', 'wpsc' ),
			'wpsc_core_images_url'     => WPSC_CORE_IMAGES_URL,
			'variation_parent_swap'    => esc_html_x( 'New Variation Set', 'Variation taxonomy parent', 'wpsc' ),
			/* translators             : This string is prepended to the 'New Variation Set' string */
			'variation_helper_text'    => esc_html_x( 'Choose the Variation Set you want to add variants to. If you\'re creating a new variation set then select', 'Variation helper text', 'wpsc' ),
			'variations_tutorial'      => esc_html__( 'Variations allow you to create options for your products. For example, if you\'re selling T-Shirts, they will generally have a "Size" option. Size will be the Variation Set name, and it will be a "New Variant Set". You will then create variants (small, medium, large) which will have the "Variation Set" of Size. Once you have made your set you can use the table on the right to manage them (edit, delete). You will be able to order your variants by dragging and dropping them within their Variation Set.', 'wpsc' ),
			/* translators             : These strings are dynamically inserted as a drop-down for the Coupon comparison conditions */
			'coupons_compare_or'       => esc_html_x( 'OR'  , 'Coupon comparison logic', 'wpsc' ),
			'coupons_compare_and'      => esc_html_x( 'AND' , 'Coupon comparison logic', 'wpsc' ),
		) );
	}
	if ( $pagehook == 'wpsc-product-variations-iframe' ) {
		wp_enqueue_script( 'wp-e-commerce-product-variations', WPSC_URL . '/wpsc-admin/js/product-variations.js', array( 'jquery' ), $version_identifier );
		wp_localize_script( 'wp-e-commerce-product-variations', 'WPSC_Product_Variations', array(
			'product_id'              => $_REQUEST['product_id'],
			'add_variation_set_nonce' => _wpsc_create_ajax_nonce( 'add_variation_set' ),
		) );
	}

	if ( $pagehook == 'media-upload-popup' ) {
		$post = get_post( $_REQUEST['post_id'] );
		if ( $post->post_type == 'wpsc-product' && $post->post_parent ) {
			wp_dequeue_script( 'set-post-thumbnail' );
			wp_enqueue_script( 'wpsc-set-post-thumbnail', WPSC_URL . '/wpsc-admin/js/set-post-thumbnail.js', array( 'jquery', 'wp-e-commerce-admin' ), $version_identifier );
			wp_localize_script( 'wpsc-set-post-thumbnail', 'WPSC_Set_Post_Thumbnail', array(
				'link_text' => __( 'Use as Product Thumbnail', 'wpsc' ),
				'saving'    => __( 'Saving...' ),
				'error'     => __( 'Could not set that as the thumbnail image. Try a different attachment.' ),
				'done'      => __( 'Done' ),
				'nonce'     => _wpsc_create_ajax_nonce( 'set_variation_product_thumbnail' ),
			) );
		}
	}

	if ( 'dashboard_page_wpsc-upgrades' == $pagehook || 'dashboard_page_wpsc-update' == $pagehook )
		wp_enqueue_style( 'wp-e-commerce-admin', WPSC_URL . '/wpsc-admin/css/admin.css', false, $version_identifier, 'all' );
}

/**
 * @todo docs
 *
 * @uses get_option()     Gets an option by name from the WordPress database
 */
function wpsc_admin_dynamic_js() {
	header( 'Content-Type: text/javascript' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), ( date( 'd' ) + 12 ), date( 'Y' ) ) ) . '' );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );

	$hidden_boxes = get_option( 'wpsc_hidden_box' );

	$form_types1 = get_option( 'wpsc_checkout_form_fields' );
	$unique_names1 = get_option( 'wpsc_checkout_unique_names' );

	$form_types = '';
	foreach ( (array)$form_types1 as $form_type ) {
		$form_types .= "<option value='" . $form_type . "'>" . $form_type . "</option>";
	}

	$unique_names = "<option value='-1'>" . __('Select a Unique Name', 'wpsc') . "</option>";
	foreach ( (array)$unique_names1 as $unique_name ) {
		$unique_names.= "<option value='" . $unique_name . "'>" . $unique_name . "</option>";
	}

	$hidden_boxes = implode( ',', (array)$hidden_boxes );
	echo "var base_url = '" . esc_js( site_url() ) . "';\n\r";
	echo "var WPSC_URL = '" . esc_js( WPSC_URL ) . "';\n\r";
	echo "var WPSC_IMAGE_URL = '" . esc_js( WPSC_IMAGE_URL ) . "';\n\r";
	echo "var WPSC_DIR_NAME = '" . esc_js( WPSC_DIR_NAME ) . "';\n\r";
	echo "var WPSC_IMAGE_URL = '" . esc_js( WPSC_IMAGE_URL ) . "';\n\r";

	// LightBox Configuration start
	echo "var fileLoadingImage = '" . esc_js( WPSC_CORE_IMAGES_URL ) . "/loading.gif';\n\r";
	echo "var fileBottomNavCloseImage = '" . esc_js( WPSC_CORE_IMAGES_URL ) . "/closelabel.gif';\n\r";
	echo "var fileThickboxLoadingImage = '" . esc_js( WPSC_CORE_IMAGES_URL ) . "/loadingAnimation.gif';\n\r";

	echo "var resizeSpeed = 9;\n\r";

	echo "var borderSize = 10;\n\r";

	echo "var hidden_boxes = '" . esc_js( $hidden_boxes ) . "';\n\r";
	echo "var IS_WP27 = '" . esc_js( IS_WP27 ) . "';\n\r";
	echo "var TXT_WPSC_DELETE = '" . esc_js( __( 'Delete', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_TEXT = '" . esc_js( __( 'Text', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_EMAIL = '" . esc_js( __( 'Email', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_COUNTRY = '" . esc_js( __( 'Country', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_TEXTAREA = '" . esc_js( __( 'Textarea', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_HEADING = '" . esc_js( __( 'Heading', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_COUPON = '" . esc_js( __( 'Coupon', 'wpsc' ) ) . "';\n\r";

	echo "var HTML_FORM_FIELD_TYPES =\" " . esc_js( $form_types ) . "; \" \n\r";
	echo "var HTML_FORM_FIELD_UNIQUE_NAMES = \" " . esc_js( $unique_names ) . "; \" \n\r";

	echo "var TXT_WPSC_LABEL = '" . esc_js( __( 'Label', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_LABEL_DESC = '" . esc_js( __( 'Label Description', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_ITEM_NUMBER = '" . esc_js( __( 'Item Number', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_LIFE_NUMBER = '" . esc_js( __( 'Life Number', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_PRODUCT_CODE = '" . esc_js( __( 'Product Code', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_PDF = '" . esc_js( __( 'PDF', 'wpsc' ) ) . "';\n\r";

	echo "var TXT_WPSC_AND_ABOVE = '" . esc_js( __( ' and above', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_IF_PRICE_IS = '" . esc_js( __( 'If price is ', 'wpsc' ) ) . "';\n\r";
	echo "var TXT_WPSC_IF_WEIGHT_IS = '" . esc_js( __( 'If weight is ', 'wpsc' ) ) . "';\n\r";

	exit();
}

if ( isset( $_GET['wpsc_admin_dynamic_js'] ) && ( $_GET['wpsc_admin_dynamic_js'] == 'true' ) ) {
	add_action( "admin_init", 'wpsc_admin_dynamic_js' );
}

/**
 * @todo finish docs
 *
 * @uses apply_filters()      Allows manipulation of the flash upload params.
 */
function wpsc_admin_dynamic_css() {
	header( 'Content-Type: text/css' );
	header( 'Expires: ' . gmdate( 'r', mktime( 0, 0, 0, date( 'm' ), ( date( 'd' ) + 12 ), date( 'Y' ) ) ) . '' );
	header( 'Cache-Control: public, must-revalidate, max-age=86400' );
	header( 'Pragma: public' );
	$flash = 0;
	$flash = apply_filters( 'flash_uploader', $flash );

	if ( $flash = 1 ) {
?>
		div.flash-image-uploader {
			display: block;
		}

		div.browser-image-uploader {
			display: none;
		}
<?php
	} else {
?>
		div.flash-image-uploader {
			display: none;
		}

		div.browser-image-uploader {
			display: block;
		}
<?php
	}
	exit();
}

if ( isset( $_GET['wpsc_admin_dynamic_css'] ) && ( $_GET['wpsc_admin_dynamic_css'] == 'true' ) ) {
	add_action( "admin_init", 'wpsc_admin_dynamic_css' );
}

add_action( 'admin_menu', 'wpsc_admin_pages' );

/**
 * Displays latest activity in the Dashboard widget
 *
 * @uses $wpdb                          WordPress database object for queries
 * @uses get_var()                      Returns single variable from the database
 * @uses esc_html__()                   Gets translation of $text and escapes it for HTML output
 * @uses wpsc_currency_display()        Displays the currency
 * @uses admin_display_total_price()    Displays the total price
 * @uses esc_html_x()
 * @uses _n()                           Retrieves the singular or plural version
 */
function wpsc_admin_latest_activity() {
	global $wpdb;
	$totalOrders = $wpdb->get_var( "SELECT COUNT(*) FROM `" . WPSC_TABLE_PURCHASE_LOGS . "`" );

	/*
	 * This is the right hand side for the past 30 days revenue on the wp dashboard
	 */
	echo "<div id='leftDashboard'>";
	echo "<strong class='dashboardHeading'>" . esc_html__( 'Current Month', 'wpsc' ) . "</strong><br />";
	echo "<p class='dashboardWidgetSpecial'>";
	// calculates total amount of orders for the month
	$year = date( "Y" );
	$month = date( "m" );
	$start_timestamp = mktime( 0, 0, 0, $month, 1, $year );
	$end_timestamp = mktime( 0, 0, 0, ( $month + 1 ), 0, $year );
	$sql = "SELECT COUNT(*) FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date` BETWEEN '$start_timestamp' AND '$end_timestamp' AND `processed` IN (2,3,4) ORDER BY `date` DESC";
	$currentMonthOrders = $wpdb->get_var( $sql );

	//calculates amount of money made for the month
	$currentMonthsSales = wpsc_currency_display( admin_display_total_price( $start_timestamp, $end_timestamp ) );
	echo $currentMonthsSales;
	echo "<span class='dashboardWidget'>" . esc_html_x( 'Sales', 'the total value of sales in dashboard widget', 'wpsc' ) . "</span>";
	echo "</p>";
	echo "<p class='dashboardWidgetSpecial'>";
	echo "<span class='pricedisplay'>";
	echo $currentMonthOrders;
	echo "</span>";
	echo "<span class='dashboardWidget'>" . _n( 'Order', 'Orders', $currentMonthOrders, 'wpsc' ) . "</span>";
	echo "</p>";
	echo "<p class='dashboardWidgetSpecial'>";
	//calculates average sales amount per order for the month
	if ( $currentMonthOrders > 0 ) {
		$monthsAverage = ( (int)admin_display_total_price( $start_timestamp, $end_timestamp ) / (int)$currentMonthOrders );
		echo wpsc_currency_display( $monthsAverage );
	}
	//echo "</span>";
	echo "<span class='dashboardWidget'>" . esc_html__( 'Avg Order', 'wpsc' ) . "</span>";
	echo "</p>";
	echo "</div>";
	/*
	 * This is the left side for the total life time revenue on the wp dashboard
	 */

	echo "<div id='rightDashboard' >";
	echo "<strong class='dashboardHeading'>" . esc_html__( 'Total Income', 'wpsc' ) . "</strong><br />";

	echo "<p class='dashboardWidgetSpecial'>";
	echo wpsc_currency_display( admin_display_total_price() );
	echo "<span class='dashboardWidget'>" . esc_html_x( 'Sales', 'the total value of sales in dashboard widget', 'wpsc' ) . "</span>";
	echo "</p>";
	echo "<p class='dashboardWidgetSpecial'>";
	echo "<span class='pricedisplay'>";
	echo $totalOrders;
	echo "</span>";
	echo "<span class='dashboardWidget'>" . _n( 'Order', 'Orders', $totalOrders, 'wpsc' ) . "</span>";
	echo "</p>";
	echo "<p class='dashboardWidgetSpecial'>";
	//calculates average sales amount per order for the month
	if ( ( admin_display_total_price() > 0 ) && ( $totalOrders > 0 ) ) {
		$totalAverage = ( (int)admin_display_total_price() / (int)$totalOrders );
	} else {
		$totalAverage = 0;
	}
	echo wpsc_currency_display( $totalAverage );
	//echo "</span>";
	echo "<span class='dashboardWidget'>" . esc_html__( 'Avg Order', 'wpsc' ) . "</span>";
	echo "</p>";
	echo "</div>";
	echo "<div style='clear:both'></div>";
}
add_action( 'wpsc_admin_pre_activity', 'wpsc_admin_latest_activity' );

/*
 * Dashboard Widget Setup
 * Adds the dashboard widgets if the user is an admin
 *
 * Since 3.6
 *
 * @uses wp_enqueue_style()           Enqueues CSS
 * @uses wp_enqueue_script()          Enqueues JS
 * @uses wp_add_dashboard_widget()    Adds a new widget to the WordPress admin dashboard
 * @uses current_user_can()           Checks the capabilities of the current user
 */
function wpsc_dashboard_widget_setup() {
	$version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
	// Enqueue the styles and scripts necessary
	wp_enqueue_style( 'wp-e-commerce-admin', WPSC_URL . '/wpsc-admin/css/admin.css', false, $version_identifier, 'all' );
	wp_enqueue_script( 'datepicker-ui', WPSC_URL . "/wpsc-core/js/ui.datepicker.js", array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), $version_identifier );

	$news_cap            = apply_filters( 'wpsc_dashboard_news_cap'           , 'manage_options' );
	$sales_cap           = apply_filters( 'wpsc_dashboard_sales_summary_cap'  , 'manage_options' );
	$quarterly_sales_cap = apply_filters( 'wpsc_dashboard_quarterly_sales_cap', 'manage_options' );
	$monthly_sales_cap   = apply_filters( 'wpsc_dashboard_monthly_sales_cap'  , 'manage_options' );

	// Add the dashboard widgets
	if ( current_user_can( $news_cap ) )
		wp_add_dashboard_widget( 'wpsc_dashboard_news', __( 'WP e-Commerce News' , 'wpsc' ), 'wpsc_dashboard_news' );
	if ( current_user_can( $sales_cap ) )
		wp_add_dashboard_widget( 'wpsc_dashboard_widget', __( 'Sales Summary', 'wpsc' ), 'wpsc_dashboard_widget' );
	if ( current_user_can( $quarterly_sales_cap ) )
		wp_add_dashboard_widget( 'wpsc_quarterly_dashboard_widget', __( 'Sales by Quarter', 'wpsc' ), 'wpsc_quarterly_dashboard_widget' );
	if ( current_user_can( $monthly_sales_cap ) )
		wp_add_dashboard_widget( 'wpsc_dashboard_4months_widget', __( 'Sales by Month', 'wpsc' ), 'wpsc_dashboard_4months_widget' );

	// Sort the Dashboard widgets so ours it at the top
	global $wp_meta_boxes;
	$boxes  = $wp_meta_boxes['dashboard'];
	$normal = isset( $wp_meta_boxes['dashboard']['normal'] ) ? $wp_meta_boxes['dashboard']['normal'] : array();

	$normal_dashboard   = isset( $normal['core'] ) ? $normal['core'] : array();

	// Backup and delete our new dashbaord widget from the end of the array
	$wpsc_widget_backup = array();
	if ( isset( $normal_dashboard['wpsc_dashboard_news'] ) ) {
		$wpsc_widget_backup['wpsc_dashboard_news'] = $normal_dashboard['wpsc_dashboard_news'];
		unset( $normal_dashboard['wpsc_dashboard_news'] );
	}
	if ( isset( $normal_dashboard['wpsc_dashboard_widget'] ) ) {
		$wpsc_widget_backup['wpsc_dashboard_widget'] = $normal_dashboard['wpsc_dashboard_widget'];
		unset( $normal_dashboard['wpsc_dashboard_widget'] );
	}
	if ( isset( $normal_dashboard['wpsc_quarterly_dashboard_widget'] ) ) {
		$wpsc_widget_backup['wpsc_quarterly_dashboard_widget'] = $normal_dashboard['wpsc_quarterly_dashboard_widget'];
		unset( $normal_dashboard['wpsc_quarterly_dashboard_widget'] );
	}
	if ( isset( $normal_dashboard['wpsc_dashboard_4months_widget'] ) ) {
		$wpsc_widget_backup['wpsc_dashboard_4months_widget'] = $normal_dashboard['wpsc_dashboard_4months_widget'];
		unset( $normal_dashboard['wpsc_dashboard_4months_widget'] );
	}

	// Merge the two arrays together so our widget is at the beginning
	$sorted_dashboard = array_merge( $wpsc_widget_backup, $normal_dashboard );

	// Save the sorted array back into the original metaboxes

	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}

/*
 * 	Registers the widgets on the WordPress Dashboard
 */

add_action( 'wp_dashboard_setup', 'wpsc_dashboard_widget_setup' );

/**
 * Shows the RSS feed for the WPEC dashboard widget
 *
 * @uses fetch_feed()             Build SimplePie object based on RSS or Atom feed from URL.
 * @uses wp_widget_rss_output()   Display the RSS entries in a list
 */
function wpsc_dashboard_news() {
	$rss = fetch_feed( 'http://getshopped.org/feed/?category_name=wp-e-commerce-plugin' );
	$args = array( 'show_author' => 1, 'show_date' => 1, 'show_summary' => 1, 'items' => 3 );
	wp_widget_rss_output( $rss, $args );

}

/**
 * Gets the quarterly summary of revenue
 *
 * @uses get_option()                 Retrieves an option from the WordPress database
 * @uses admin_display_total_price()  Displays the total price
 *
 * @return array        The array of prices
 */
function wpsc_get_quarterly_summary() {
	(int)$firstquarter = get_option( 'wpsc_first_quart' );
	(int)$secondquarter = get_option( 'wpsc_second_quart' );
	(int)$thirdquarter = get_option( 'wpsc_third_quart' );
	(int)$fourthquarter = get_option( 'wpsc_fourth_quart' );
	(int)$finalquarter = get_option( 'wpsc_final_quart' );

	$results[] = admin_display_total_price( $thirdquarter + 1, $fourthquarter );
	$results[] = admin_display_total_price( $secondquarter + 1, $thirdquarter );
	$results[] = admin_display_total_price( $firstquarter + 1, $secondquarter );
	$results[] = admin_display_total_price( $finalquarter, $firstquarter );
	return $results;
}

/**
 * Called by wp_add_dashboard_widget and ads the quarterly revenue reports to the WordPress admin dashboard
 *
 * @uses get_option()     Gets the specified option from database
 * @uses esc_html_e()     Displays translated text that has been escaped for safe use in HTML
 */
function wpsc_quarterly_dashboard_widget() {
	if ( get_option( 'wpsc_business_year_start' ) == false ) {
?>
		<form action='' method='post'>
			<label for='date_start'><?php esc_html_e( 'Financial Year End' , 'wpsc' ); ?>: </label>
			<input id='date_start' type='text' class='pickdate' size='11' value='<?php echo get_option( 'wpsc_last_date' ); ?>' name='add_start' />
			   <!--<select name='add_start[day]'>
<?php
		for ( $i = 1; $i <= 31; ++$i ) {
			$selected = '';
			if ( $i == date( "d" ) ) {
				$selected = "selected='selected'";
			}
			echo "<option $selected value='$i'>$i</option>";
		}
?>
				   </select>
		   <select name='add_start[month]'>
	<?php
		for ( $i = 1; $i <= 12; ++$i ) {
			$selected = '';
			if ( $i == (int)date( "m" ) ) {
				$selected = "selected='selected'";
			}
			echo "<option $selected value='$i'>" . date( "M", mktime( 0, 0, 0, $i, 1, date( "Y" ) ) ) . "</option>";
		}
?>
				   </select>
		   <select name='add_start[year]'>
	<?php
		for ( $i = date( "Y" ); $i <= ( date( "Y" ) + 12 ); ++$i ) {
			$selected = '';
			if ( $i == date( "Y" ) ) {
				$selected = "selected='true'";
			}
			echo "<option $selected value='$i'>" . $i . "</option>";
		}
?>
				   </select>-->
		<input type='hidden' name='wpsc_admin_action' value='wpsc_quarterly' />
		<input type='submit' class='button primary' value='Submit' name='wpsc_submit' />
	</form>
<?php
		if ( get_option( 'wpsc_first_quart' ) != '' ) {
			$firstquarter = get_option( 'wpsc_first_quart' );
			$secondquarter = get_option( 'wpsc_second_quart' );
			$thirdquarter = get_option( 'wpsc_third_quart' );
			$fourthquarter = get_option( 'wpsc_fourth_quart' );
			$finalquarter = get_option( 'wpsc_final_quart' );
			$revenue = wpsc_get_quarterly_summary();
			$currsymbol = wpsc_get_currency_symbol();
			foreach ( $revenue as $rev ) {
				if ( $rev == '' ) {
					$totals[] = '0.00';
				} else {
					$totals[] = $rev;
				}
			}
?>
			<div id='box'>
				<p class='atglance'>
					<span class='wpsc_quart_left'><?php esc_html_e( 'At a Glance' , 'wpsc' ); ?></span>
					<span class='wpsc_quart_right'><?php esc_html_e( 'Revenue' , 'wpsc' ); ?></span>
				</p>
				<div style='clear:both'></div>
				<p class='quarterly'>
					<span class='wpsc_quart_left'><strong>01</strong>&nbsp; (<?php echo date( 'M Y', $thirdquarter ) . ' - ' . date( 'M Y', $fourthquarter ); ?>)</span>
					<span class='wpsc_quart_right'><?php echo $currsymbol . ' ' . $totals[0]; ?></span></p>
				<p class='quarterly'>
					<span class='wpsc_quart_left'><strong>02</strong>&nbsp; (<?php echo date( 'M Y', $secondquarter ) . ' - ' . date( 'M Y', $thirdquarter ); ?>)</span>
					<span class='wpsc_quart_right'><?php echo $currsymbol . ' ' . $totals[1]; ?></span></p>
				<p class='quarterly'>
					<span class='wpsc_quart_left'><strong>03</strong>&nbsp; (<?php echo date( 'M Y', $firstquarter ) . ' - ' . date( 'M Y', $secondquarter ); ?>)</span>
					<span class='wpsc_quart_right'><?php echo $currsymbol . ' ' . $totals[2]; ?></span></p>
				<p class='quarterly'>
					<span class='wpsc_quart_left'><strong>04</strong>&nbsp; (<?php echo date( 'M Y', $finalquarter ) . ' - ' . date( 'M Y', $firstquarter ); ?>)</span>
					<span class='wpsc_quart_right'><?php echo $currsymbol . ' ' . $totals[3]; ?></span>
				</p>
				<div style='clear:both'></div>
			</div>
<?php
		}
	}
}

/**
 * Called by wp_add_dashboard_widget to add the WPSC dashboard widget
 *
 * @uses do_action()    Calls 'wpsc_admin_pre_activity'
 * @uses do_action()    Calls 'wpsc_admin_post_activity'
 */
function wpsc_dashboard_widget() {
	do_action( 'wpsc_admin_pre_activity' );
	do_action( 'wpsc_admin_post_activity' );
}

/*
 * END - Dashboard Widget for 2.7
 */


/*
 * Dashboard Widget Last Four Month Sales.
 *
 * @uses $wpdb                      WordPress database object for queries
 * @uses get_results()              Gets generic multiple row results from the WordPress database
 * @uses get_var()                  Returns a single variable from the database
 * @uses wpsc_currency_display()    Returns the currency with the display options applied
 */
function wpsc_dashboard_4months_widget() {
	global $wpdb;

	$this_year = date( "Y" ); //get current year and month
	$this_month = date( "n" );

	$months[] = mktime( 0, 0, 0, $this_month - 3, 1, $this_year ); //generate  unix time stamps fo 4 last months
	$months[] = mktime( 0, 0, 0, $this_month - 2, 1, $this_year );
	$months[] = mktime( 0, 0, 0, $this_month - 1, 1, $this_year );
	$months[] = mktime( 0, 0, 0, $this_month, 1, $this_year );

	$products = $wpdb->get_results( "SELECT `cart`.`prodid`,
	 `cart`.`name`
	 FROM `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
	 INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
	 ON `cart`.`purchaseid` = `logs`.`id`
	 WHERE `logs`.`processed` >= 2
	 AND `logs`.`date` >= " . $months[0] . "
	 GROUP BY `cart`.`prodid`
	 ORDER BY SUM(`cart`.`price` * `cart`.`quantity`) DESC
	 LIMIT 4", ARRAY_A ); //get 4 products with top income in 4 last months.

	$timeranges[0]["start"] = mktime( 0, 0, 0, $this_month - 3, 1, $this_year ); //make array of time ranges
	$timeranges[0]["end"] = mktime( 0, 0, 0, $this_month - 2, 1, $this_year );
	$timeranges[1]["start"] = mktime( 0, 0, 0, $this_month - 2, 1, $this_year );
	$timeranges[1]["end"] = mktime( 0, 0, 0, $this_month - 1, 1, $this_year );
	$timeranges[2]["start"] = mktime( 0, 0, 0, $this_month - 1, 1, $this_year );
	$timeranges[2]["end"] = mktime( 0, 0, 0, $this_month, 1, $this_year );
	$timeranges[3]["start"] = mktime( 0, 0, 0, $this_month, 1, $this_year );
	$timeranges[3]["end"] = time(); // using mktime here can generate a php runtime warning

	$prod_data = array( );
	foreach ( (array)$products as $product ) { //run through products and get each product income amounts and name
		$sale_totals = array( );
		foreach ( $timeranges as $timerange ) { //run through time ranges of product, and get its income over each time range
			$prodsql = "SELECT
			SUM(`cart`.`price` * `cart`.`quantity`) AS sum
			FROM `" . WPSC_TABLE_CART_CONTENTS . "` AS `cart`
			INNER JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` AS `logs`
				ON `cart`.`purchaseid` = `logs`.`id`
			WHERE `logs`.`processed` >= 2
				AND `logs`.`date` >= " . $timerange["start"] . "
				AND `logs`.`date` < " . $timerange["end"] . "
				AND `cart`.`prodid` = " . $product['prodid'] . "
			GROUP BY `cart`.`prodid`"; //get the amount of income that current product has generaterd over current time range
			$sale_totals[] = $wpdb->get_var( $prodsql ); //push amount to array
		}
		$prod_data[] = array(
			'sale_totals' => $sale_totals,
			'product_name' => $product['name'] ); //result: array of 2: $prod_data[0] = array(income)
		$sums = array( ); //reset array    //$prod_data[1] = product name
	}

	$tablerow = 1;
	ob_start();
	?>
	<div style="padding-bottom:15px; "><?php esc_html_e( 'Last four months of sales on a per product basis:', 'wpsc' ); ?></div>
    <table style="width:100%" border="0" cellspacing="0">
    	<tr style="font-style:italic; color:#666;" height="20">
    		<td colspan="2" style=" font-family:\'Times New Roman\', Times, serif; font-size:15px; border-bottom:solid 1px #000;"><?php esc_html_e( 'At a Glance', 'wpsc' ); ?></td>
			<?php foreach ( $months as $mnth ): ?>
			<td align="center" style=" font-family:\'Times New Roman\'; font-size:15px; border-bottom:solid 1px #000;"><?php echo date( "M", $mnth ); ?></td>
			<?php endforeach; ?>
		</tr>
	<?php foreach ( (array)$prod_data as $sales_data ): ?>
		<tr height="20">
			<td width="20" style="font-weight:bold; color:#008080; border-bottom:solid 1px #000;"><?php echo $tablerow; ?></td>
			<td style="border-bottom:solid 1px #000;width:60px"><?php echo $sales_data['product_name']; ?></td>
			<?php foreach ( $sales_data['sale_totals'] as $amount ): ?>
				<td align="center" style="border-bottom:solid 1px #000;"><?php echo wpsc_currency_display($amount); ?></td>
			<?php endforeach; ?>
		</tr>
		<?php
		$tablerow++;
		endforeach; ?>
	</table>
	<?php
	ob_end_flush();
}


//Modification to allow for multiple column layout

/**
 * @todo docs
 * @param $columns
 * @param $screen
 * @return mixed
 */
function wpec_two_columns( $columns, $screen ) {
	if ( $screen == 'toplevel_page_wpsc-edit-products' )
		$columns['toplevel_page_wpsc-edit-products'] = 2;

	return $columns;
}
add_filter( 'screen_layout_columns', 'wpec_two_columns', 10, 2 );

/**
 * @todo docs
 * @param $actions
 * @return mixed
 */
function wpsc_fav_action( $actions ) {
	$actions['post-new.php?post_type=wpsc-product'] = array( 'New Product', 'manage_options' );
	return $actions;
}
add_filter( 'favorite_actions', 'wpsc_fav_action' );

/**
 * Prits out the admin scripts
 *
 * @uses wp_enqueue_script()      Enqueues scripts
 * @uses home_url()               Returns the base url for the site
 */
function wpsc_print_admin_scripts() {
	wp_enqueue_script( 'wp-e-commerce-dynamic', home_url( '/index.php?wpsc_user_dynamic_js=true' ) );
}

/**
 * Update products page URL options when permalink scheme changes.
 *
 * @since  3.8.9
 * @access private
 *
 * @uses get_bloginfo()                               Returns information about your site to be used elsewhere
 * @uses version_compare()                            Compares two "PHP-standardized" version number strings
 * @uses _wpsc_display_permalink_refresh_notice()     Display warning on older WordPress versions
 * @uses wpsc_update_page_urls()                      Gets the premalinks for product pages and stores for quick reference
 */
function _wpsc_action_permalink_structure_changed() {
	$wp_version = get_bloginfo( 'version' );

	// see WordPress core trac ticket:
	// http://core.trac.wordpress.org/ticket/16736
	// this has been fixed in WordPress 3.3
	if ( version_compare( $wp_version, '3.3', '<' ) )
		_wpsc_display_permalink_refresh_notice();
		add_action( 'admin_notices', 'wpsc_check_permalink_notice' );

	wpsc_update_page_urls( true );
}

/**
 * Display warning if the user is using WordPress prior to 3.3 because there is a bug with custom
 * post type and taxonomy permalink generation.
 *
 * @since 3.8.9
 * @access private
 */
function _wpsc_display_permalink_refresh_notice(){
	?>
	<div id="notice" class="error fade">
		<p>
			<?php printf( __( 'Due to <a href="%1$s">a bug in WordPress prior to version 3.3</a>, you might run into 404 errors when viewing your products. To work around this, <a href="%2$s">upgrade to WordPress 3.3 or later</a>, or simply click "Save Changes" below a second time.' , 'wpsc' ), 'http://core.trac.wordpress.org/ticket/16736', 'http://codex.wordpress.org/Updating_WordPress' ); ?>
		</p>
	</div>
	<?php
}


/**
 * wpsc_ajax_ie_save save changes made using inline edit
 *
 * @since  3.8
 * @access public
 *
 * @uses get_post_type_object()       Gets post object for given registered post type name
 * @uses current_user_can()           Checks the capabilities of the current user
 * @uses absint()                     Converts to a nonnegative integer
 * @uses get_post()                   Gets the post object given post id
 * @uses wp_get_object_terms()        Gets terms for given post object
 * @uses wp_update_post()             Updates the post in the database
 * @uses get_product_meta()           An alias for get_post_meta prefixes with the WPSC key
 * @uses wpsc_convert_weight()        Converts to weight format specified by user
 * @uses json_encode()                Encodes array for JS
 * @uses esc_js()                     Escape single quotes, htmlspecialchar " < > &, and fix line endings.
 *
 * @returns nothing
 */
function wpsc_ajax_ie_save() {

	$product_post_type = get_post_type_object( 'wpsc-product' );

	if ( !current_user_can( $product_post_type->cap->edit_posts ) ) {
		echo '({"error":"' . __( 'Error: you don\'t have required permissions to edit this product', 'wpsc' ) . '", "id": "'. esc_js( $_POST['id'] ) .'"})';
		die();
	}

	$id = absint( $_POST['id'] );
	$post = get_post( $_POST['id'] );
	$parent = get_post( $post->post_parent );
	$terms = wpsc_get_product_terms( $id, 'wpsc-variation', 'name' );

	$product = array(
		'ID' => $_POST['id'],
		'post_title' => $parent->post_title . ' (' . implode( ', ', $terms ) . ')',
	);

	$id = wp_update_post( $product );
	if ( $id > 0 ) {
		//need parent meta to know which weight unit we are using
		$parent_meta = get_product_meta($post->post_parent, 'product_metadata', true );
		$product_meta = get_product_meta( $product['ID'], 'product_metadata', true );
		if ( is_numeric( $_POST['weight'] ) || empty( $_POST['weight'] ) ){
			$product_meta['weight'] = wpsc_convert_weight($_POST['weight'], $parent_meta['weight_unit'], 'pound', true);
			$product_meta['weight_unit'] = $parent_meta['weight_unit'];
		}

		update_product_meta( $product['ID'], 'product_metadata', $product_meta );
		update_product_meta( $product['ID'], 'price', (float)$_POST['price'] );
		update_product_meta( $product['ID'], 'special_price', (float)$_POST['special_price'] );
		update_product_meta( $product['ID'], 'sku', $_POST['sku'] );
		if ( !is_numeric($_POST['stock']) )
			update_product_meta( $product['ID'], 'stock', '' );
		else
			update_product_meta( $product['ID'], 'stock', absint( $_POST['stock'] ) );

		$meta = get_product_meta( $id, 'product_metadata', true );
		$price = get_product_meta( $id, 'price', true );
		$special_price = get_product_meta( $id, 'special_price', true );
		$sku = get_product_meta( $id, 'sku', true );
		$sku = ( $sku )?$sku:__('N/A', 'wpsc');
		$stock = get_product_meta( $id, 'stock', true );
		$stock = ( $stock === '' )?__('N/A', 'wpsc'):$stock;
		$results = array( 'id' => $id, 'title' => $post->post_title, 'weight' => wpsc_convert_weight($meta['weight'], 'pound', $parent_meta['weight_unit']), 'price' => wpsc_currency_display( $price ), 'special_price' => wpsc_currency_display( $special_price ), 'sku' => $sku, 'stock' => $stock );
		echo '(' . json_encode( $results ) . ')';
		die();
	} else {
		echo '({"error":"' . __( 'Error updating product', 'wpsc' ) . '", "id": "'. esc_js( $_POST['id'] ) .'"})';
	}
	die();
}

/**
 * @todo docs
 *
 * @uses add_meta_box  Allows addition of metaboxes to the wpsc_add_meta_boxes admin
 */
function wpsc_add_meta_boxes(){
	add_meta_box( 'dashboard_right_now', __( 'Current Month', 'wpsc' ), 'wpsc_right_now', 'dashboard_page_wpsc-sales-logs', 'top' );
}

/**
 * Displays notice if user has Great Britain selected as their base country
 * Since 3.8.9, we have deprecated Great Britain in favor of the UK
 *
 * @since 3.8.9
 * @access private
 * @link http://code.google.com/p/wp-e-commerce/issues/detail?id=1079
 *
 * @uses get_option()             Retrieves option from the WordPress database
 * @uses get_outdate_isocodes()   Returns outdated isocodes
 * @uses admin_url()              Returns admin_url of the site
 *
 * @return string  The admin notices for deprecated countries
 */
function _wpsc_action_admin_notices_deprecated_countries_notice() {
	$base_country = get_option( 'base_country' );

	if ( ! in_array( $base_country, WPSC_Country::get_outdated_isocodes() ) )
		return;

	switch ( $base_country ) {
		case 'YU':
			$message = __( 'Yugoslavia is no longer a valid official country name according to <a href="%1$s">ISO 3166</a> while both Serbia and Montenegro have been added to the country list.<br /> As a result, we highly recommend changing your <em>Base Country</em> to reflect this change on the <a href="%2$s">General Settings</a> page.', 'wpsc' );
			break;
		case 'UK':
			$message = __( 'Prior to WP e-Commerce 3.8.9, in your database, United Kingdom\'s country code is UK and you have already selected that country code as the base country. However, now that you\'re using WP e-Commerce version %3$s, it is recommended that you change your base country to the official "GB" country code, according to <a href="%1$s">ISO 3166</a>.<br /> Please go to <a href="%2$s">General Setings</a> page to make this change.<br />The legacy "UK" item will be marked as "U.K. (legacy)" on the country drop down list. Simply switch to the official "United Kingdom (ISO 3166)" to use the "GB" country code.' , 'wpsc' );
			break;
		case 'AN':
			$message = __( 'Netherlands Antilles is no longer a valid official country name according to <a href="%1$s">ISO 3166</a>.<br />Please consider changing your <em>Base Country</em> to reflect this change on the <a href="%2$s">General Settings</a> page.', 'wpsc' );
		case 'TP':
			$message = __( 'Prior to WP e-Commerce 3.8.9, in your database, East Timor\'s country code is TP and you have already selected that country code as the base country. However, now that you\'re using WP e-Commerce version %3$s, it is recommended that you change your base country to the official "TL" country code, according to <a href="%1$s">ISO 3166</a>.<br /> Please go to <a href="%2$s">General Setings</a> page to make this change.<br />The legacy "TP" item will be marked as "East Timor (legacy)" on the country drop down list. Simply switch to the official "Timor-Leste (ISO 3166)" to use the "TL" country code.' , 'wpsc' );
			break;
	}

	$message = sprintf(
		/* message */ $message,
		/* %1$s    */ 'http://en.wikipedia.org/wiki/ISO_3166-1',
		/* %2$s    */ admin_url( 'options-general.php?page=wpsc-settings&tab=general' ),
		/* %3$s    */ WPSC_VERSION
	);
	echo '<div id="wpsc-warning" class="error"><p>' . $message . '</p></div>';
}

add_action( 'admin_notices', '_wpsc_action_admin_notices_deprecated_countries_notice' );
add_action( 'permalink_structure_changed' , '_wpsc_action_permalink_structure_changed' );
add_action( 'wp_ajax_category_sort_order', 'wpsc_ajax_set_category_order' );
add_action( 'wp_ajax_variation_sort_order', 'wpsc_ajax_set_variation_order' );
add_action( 'wp_ajax_wpsc_ie_save', 'wpsc_ajax_ie_save' );
add_action('in_admin_header', 'wpsc_add_meta_boxes');

/**
 * Deletes file associated with a product.
 *
 * @access private
 *
 * @uses $wpdb              WordPress database object for queries
 * @uses prepare()          Prepares a database query by escaping
 * @uses wp_delete_post()   Removes a post attachment or page*
 *
 * @param int       $product_id     req        The id of the product
 * @param string    $file_name      req        The string
 *
 * @return mixed
 *
 */
function _wpsc_delete_file( $product_id, $file_name ) {
	global $wpdb;

	$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_parent = %d AND post_type ='wpsc-product-file'", $file_name, $product_id );
	$product_id_to_delete = $wpdb->get_var( $sql );
	return wp_delete_post( $product_id_to_delete, true );
}

/**
 * Duplicates a product
 *
 * @uses wp_insert_post()                 Inserts a new post to the database
 * @uses wpsc_duplicate_taxonomies()      Copy the taxonomies of a post to another post
 * @uses wpsc_duplicate_product_meta()    Copy the metadata of a post to another post
 * @uses wpsc_duplicate_children()        Copy the children of the product
 *
 * @param object    $post           req     The post object
 * @param bool      $new_parent_id  opt     The parent post id
 *
 * @return int|WP_Error     New post id or error
 */
function wpsc_duplicate_product_process( $post, $new_parent_id = false ) {
	$new_post_date     = $post->post_date;
	$new_post_date_gmt = get_gmt_from_date( $new_post_date );

	$new_post_type         = $post->post_type;
	$post_content          = $post->post_content;
	$post_content_filtered = $post->post_content_filtered;
	$post_excerpt          = $post->post_excerpt;
	$post_title            = sprintf( __( '%s (Duplicate)', 'wpsc' ), $post->post_title );
	$post_name             = $post->post_name;
	$comment_status        = $post->comment_status;
	$ping_status           = $post->ping_status;

	$defaults = array(
		'post_status'           => $post->post_status,
		'post_type'             => $new_post_type,
		'ping_status'           => $ping_status,
		'post_parent'           => $new_parent_id ? $new_parent_id : $post->post_parent,
		'menu_order'            => $post->menu_order,
		'to_ping'               => $post->to_ping,
		'pinged'                => $post->pinged,
		'post_excerpt'          => $post_excerpt,
		'post_title'            => $post_title,
		'post_content'          => $post_content,
		'post_content_filtered' => $post_content_filtered,
		'post_mime_type'        => $post->post_mime_type,
		'import_id'             => 0
		);

	if ( 'attachment' == $post->post_type )
		$defaults['guid'] = $post->guid;

	$defaults = stripslashes_deep( $defaults );

	// Insert the new template in the post table
	$new_post_id = wp_insert_post($defaults);

	// Copy the taxonomies
	wpsc_duplicate_taxonomies( $post->ID, $new_post_id, $post->post_type );

	// Copy the meta information
	wpsc_duplicate_product_meta( $post->ID, $new_post_id );

	// Finds children (Which includes product files AND product images), their meta values, and duplicates them.
	wpsc_duplicate_children( $post->ID, $new_post_id );

	return $new_post_id;
}

/**
 * Copy the taxonomies of a post to another post
 *
 * @uses get_object_taxonomies()  Gets taxonomies for the give object
 * @uses wp_get_object_terms()    Gets terms for the taxonomies
 * @uses wp_set_object_terms()    Sets the terms for a post object
 *
 * @param int       $id         req     ID of the post we are duping
 * @param int       $new_id     req     ID of the new post
 * @param string    $post_type  req     The post type we are setting
 */
function wpsc_duplicate_taxonomies( $id, $new_id, $post_type ) {
	$taxonomies = get_object_taxonomies( $post_type ); //array("category", "post_tag");
	foreach ( $taxonomies as $taxonomy ) {
		$post_terms = wpsc_get_product_terms( $id, $taxonomy );
		foreach ( $post_terms as $post_term ) {
			wp_set_object_terms( $new_id, $post_term->slug, $taxonomy, true );
		}
	}
}

/**
 * Copy the meta information of a post to another post
 *
 * @uses $wpdb              WordPress database object for queries
 * @uses get_results()      Gets generic multirow results from the database
 * @uses prepare()          Prepares a database query making it safe
 * @uses query()            Runs an SQL query
 *
 * @param int   $id     req ID of the post we are duping
 * @param int   $new_id req ID of the new post
 */
function wpsc_duplicate_product_meta( $id, $new_id ) {
	global $wpdb;

	$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $id ) );

	if ( count( $post_meta_infos ) ) {
		$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
		$values = array();
		foreach ( $post_meta_infos as $meta_info ) {
			$meta_key = $meta_info->meta_key;
			$meta_value = addslashes( $meta_info->meta_value );

			$sql_query_sel[] = "( $new_id, '$meta_key', '$meta_value' )";
			$values[] = $new_id;
			$values[] = $meta_key;
			$values[] = $meta_value;
			$values += array( $new_id, $meta_key, $meta_value );
		}
		$sql_query.= implode( ",", $sql_query_sel );
		$sql_query = $wpdb->prepare( $sql_query, $values );
		$wpdb->query( $sql_query );
	}
}

/**
 * Duplicates children product and children meta
 *
 * @uses get_posts()                          Gets an array of posts given array of arguments
 * @uses wpsc_duplicate_product_process()     Duplicates product
 *
 * @param   int     $old_parent_id  req     Post id for old parent
 * @param   int     $new_parenc_id  req     Post id for the new parent
 */
function wpsc_duplicate_children( $old_parent_id, $new_parent_id ) {

	//Get children products and duplicate them
	$child_posts = get_posts( array(
		'post_parent' => $old_parent_id,
		'post_type'   => 'any',
		'post_status' => 'any',
		'numberposts' => -1,
		'order'       => 'ASC',
	) );

	foreach ( $child_posts as $child_post )
	    wpsc_duplicate_product_process( $child_post, $new_parent_id );

}

/**
 * @todo docs
 * @access private
 *
 * @uses add_query_arg()      Adds argument to the WordPress query
 * @uses update_option()      Updates an option in the WordPress database given string and value
 * @uses get_option()         Gets option from the database given string
 */
function _wpsc_admin_notices_3dot8dot9() {
	$message = '<p>' . __( 'You are currently using WP e-Commerce 3.8.9. There have been major changes in WP e-Commerce 3.8.9, so backward-compatibility with existing plugins might not always be guaranteed. If you are unsure, please roll back to 3.8.8.5, and set up a test site with 3.8.9 to make sure WP e-Commerce 3.8.9 is compatible with your existing themes and plugins.<br />If you find any incompatibility issues, please <a href="%1$s">report them to us</a> as well as the other plugins or themes\' developers.' , 'wpsc' ) . '</p>';
	$message .= "\n<p>" . __( '<a href="%2$s">Hide this warning</a>', 'wpsc' ) . '</p>';
	$message = sprintf(
		$message,
		'http://getshopped.org/wp-e-commerce-3-8-9-compatibility-issues/',
		add_query_arg( 'dismiss_389_upgrade_notice', 1 )
	);

	echo '<div id="wpsc-3.8.9-notice" class="error">' . $message . '</div>';
}

if ( isset( $_REQUEST['dismiss_389_upgrade_notice'] ) || version_compare( WPSC_VERSION, '3.8.10', '>=' ) )
	update_option( 'wpsc_hide_3.8.9_notices', true );

if ( ! get_option( 'wpsc_hide_3.8.9_notices' ) )
	add_action( 'admin_notices', '_wpsc_admin_notices_3dot8dot9' );

/**
 * @todo docs
 * @access private
 *
 * @uses add_query_arg()      Adds argument to the WordPress query
 * @uses update_option()      Updates an option in the WordPress database given string and value
 * @uses get_option()         Gets option from the database given string
 */
function _wpsc_admin_notices_3dot8dot11() {
	$message  = '<p>' . __( 'You are currently using WPeC %1$s.  We introduced a regression in WPeC 3.8.10 which affects your customer user account page. We have included a fix for a <a href="%2$s">bug on the User Account management page</a>. We are able to fix this automatically on most sites, but it appears that you have made changes to your wpsc-user-log.php page.  For that reason, we have some <a href="%3$s">simple instructions for you to follow</a> to resolve the issue.  We are sorry for the inconvenience.' , 'wpsc' ) . '</p>';
	$message .= "\n<p>" . __( '<a href="%4$s">Hide this warning</a>', 'wpsc' ) . '</p>';
	$message  = sprintf(
		$message,
		WPSC_VERSION,
		'https://github.com/wp-e-commerce/WP-e-Commerce/issues/359',
		'http://docs.getshopped.org/documentation/3-8-11-user-logs',
		add_query_arg( 'dismiss_3811_upgrade_notice', 1 )
	);

	echo '<div id="wpsc-3.8.11-notice" class="error">' . $message . '</div>';
}

if ( isset( $_REQUEST['dismiss_3811_upgrade_notice'] ) )
	update_option( '_wpsc_3811_user_log_notice', false );

if ( get_option( '_wpsc_3811_user_log_notice' ) )
	add_action( 'admin_notices', '_wpsc_admin_notices_3dot8dot11' );

function _wpsc_notify_google_checkout_deprecation() {
	$gateways = get_option( 'custom_gateway_options', array() );

	if ( false !== ( $key = array_search( 'google', $gateways ) ) ) {
		unset( $gateways[ $key ] );
	}

	if ( empty( $gateways ) ) {
		$gateways[] = 'wpsc_merchant_testmode';
	}

	update_option( 'custom_gateway_options', $gateways );

	$message  = '<p>' . __( 'Effective November 20th, 2013, Google Checkout was shut down and is no longer processing payments.  You are seeing this warning because it appears that Google Checkout was your payment gateway processor.  If it was your sole processor, we have enabled the Test Gateway to ensure that orders are coming through on your site, but we highly recommend enabling a proper gateway.  If you have no preference, we highly recommend Stripe.' , 'wpsc' ) . '</p>';

	echo '<div id="wpsc-3.8.11-notice" class="error">' . $message . '</div>';
}

if ( in_array( 'google', get_option( 'custom_gateway_options', array() ) ) ) {
	add_action( 'admin_notices', '_wpsc_notify_google_checkout_deprecation' );
}
