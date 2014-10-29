<?php
/**
 * wpsc_cart_item_custom_message()
 *
 * Deprecated function for checking whether a cart item has a custom message or not
 *
 * @return false
 */

function wpsc_cart_item_custom_message(){
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	return false;
}

/**
 * wpsc_merchants_modules_deprecated()
 *
 * Deprecated function for merchants modules
 *
 */
function wpsc_merchants_modules_deprecated($nzshpcrt_gateways){
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	$nzshpcrt_gateways = apply_filters( 'wpsc_gateway_modules', $nzshpcrt_gateways );
	return $nzshpcrt_gateways;
}

/**
 * nzshpcrt_price_range()
 * Deprecated
 * Alias of Price Range Widget content function
 *
 * Displays a list of price ranges.
 *
 * @param $args (array) Arguments.
 */
function nzshpcrt_price_range($args){
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	wpsc_price_range($args);
}

// preserved for backwards compatibility
function nzshpcrt_shopping_basket( $input = null, $override_state = null ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_shopping_cart');
	return wpsc_shopping_cart( $input, $override_state );
}


/**
 * Function show_cats_brands
 * deprecated as we do not have brands anymore...
 *
 */
function show_cats_brands($category_group = null , $display_method = null, $order_by = 'name', $image = null) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_shopping_cart');
}
/**
 * Filter: wpsc-purchlogitem-links-start
 *
 * This filter has been deprecated and replaced with one that follows the
 * correct naming conventions with underscores.
 *
 * @since 3.7.6rc2
 */
function wpsc_purchlogitem_links_start_deprecated() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	do_action( 'wpsc-purchlogitem-links-start' );
}


function nzshpcrt_donations($args){
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	wpsc_donations($args);
}

/**
 * Latest Product Widget content function
 *
 * Displays the latest products.
 *
 * @todo Make this use wp_query and a theme file (if no theme file present there should be a default output).
 * @todo Remove marketplace theme specific code and maybe replce with a filter for the image output? (not required if themeable as above)
 * @todo Should this latest products function live in a different file, seperate to the widget logic?
 *
 * Changes made in 3.8 that may affect users:
 *
 * 1. The product title link text does now not have a bold tag, it should be styled via css.
 * 2. <br /> tags have been ommitted. Padding and margins should be applied via css.
 * 3. Each product is enclosed in a <div> with a 'wpec-latest-product' class.
 * 4. The product list is enclosed in a <div> with a 'wpec-latest-products' class.
 * 5. Function now expects two arrays as per the standard Widget API.
 */
function nzshpcrt_latest_product( $args = null, $instance ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_latest_product');
	echo wpsc_latest_product( $args, $instance );
}

/**
 * nzshpcrt_currency_display function.
 * Obsolete, preserved for backwards compatibility
 *
 * @access public
 * @param mixed $price_in
 * @param mixed $tax_status
 * @param bool $nohtml deprecated
 * @param bool $id. deprecated
 * @param bool $no_dollar_sign. (default: false)
 * @return void
 */
function nzshpcrt_currency_display($price_in, $tax_status, $nohtml = false, $id = false, $no_dollar_sign = false) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );

	$output = wpsc_currency_display($price_in, array(
		'display_currency_symbol' => !(bool)$no_dollar_sign,
		'display_as_html' => ! (bool)$nohtml,
		'display_decimal_point' => true,
		'display_currency_code' => false
	));
	return $output;
}

/**
 * This should be deprecated using _wpsc_deprecated_function() however the
 * constants are still being used in admin-legacy.js, which is still enqueued
 * by default in wp-admin.
 *
 * @deprecated
 */
function wpsc_include_language_constants(){
	// _wpsc_deprecated_function( __FUNCTION__, '3.8' );

	if(!defined('TXT_WPSC_ABOUT_THIS_PAGE'))
		include_once(WPSC_FILE_PATH.'/wpsc-languages/EN_en.php');
}
add_action('init','wpsc_include_language_constants');

if(!function_exists('wpsc_has_noca_message')){
	function wpsc_has_noca_message(){
		_wpsc_deprecated_function( __FUNCTION__, '3.8' );
		if(isset($_SESSION['nocamsg']) && isset($_GET['noca']) && $_GET['noca'] == 'confirm')
			return true;
		else
			return false;
	}
}

if(!function_exists('wpsc_is_noca_gateway')){
	function wpsc_is_noca_gateway(){
		_wpsc_deprecated_function( __FUNCTION__, '3.8' );
		if(count($wpsc_gateway->wpsc_gateways) == 1 && $wpsc_gateway->wpsc_gateways[0]['name'] == 'Noca')
			return true;
		else
			return false;
	}
}

/**
 * wpsc current_page
 * @return (int) The current page number
 */
function wpsc_current_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );

	global $wpsc_query;

	$current_page = 1;

	if ( $wpsc_query->query_vars['page'] > 1) {
		$current_page = $wpsc_query->query_vars['page'];
	}

	return $current_page;

}

/**
 * wpsc showing products
 * Displays the number of page showing in the form "10 to 20".
 * If only on page is being display it will return the total amount of products showing.
 * @return (string) Number of products showing
 */
function wpsc_showing_products() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );

	global $wpsc_query;

	// If we are using pages...
	if ( ( get_option( 'use_pagination' ) == 1 ) ) {
		$products_per_page = $wpsc_query->query_vars['number_per_page'];
		if ( $wpsc_query->query_vars['page'] > 0 ) {
			$startnum = ( $wpsc_query->query_vars['page'] - 1 ) * $products_per_page;
		} else {
			$startnum = 0;
		}
		return ( $startnum + 1 ) . ' to ' . ( $startnum + wpsc_product_count() );
	}

	return wpsc_total_product_count();

}

/**
 * wpsc showing products page
 * Displays the number of page showing in the form "5 of 10".
 * @return (string) Number of pages showing.
 */
function wpsc_showing_products_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );

	global $wpsc_query;

	$output = $wpsc_query->page_count;
	$current_page = wpsc_current_page();

	return $current_page . ' of ' . $output;

}


/**
 * is wpsc profile page
 * Checks if the current account page tab is Edit Profile.
 * @deprecated since 3.8.10
 * @return (boolean) true if current tab ID is edit_profile.
 */
function is_wpsc_profile_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.10' );
	return !empty($_REQUEST['tab']) && ( $_REQUEST['tab'] == 'edit_profile' );
}

/**
 * is wpsc profile page
 * Checks if the current account page tab is Downloads.
 * @deprecated since 3.8.10
 * @return (boolean) true if current tab ID is downloads.
 */
function is_wpsc_downloads_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.10' );
	return !empty($_REQUEST['tab']) && ( $_REQUEST['tab'] == 'downloads' );
}


/**
 * wpsc user details
 * Displays the Purchase History account page section.
 * @deprecated since 3.8.10
 * @return (string) The Purchase History page template.
 */
function wpsc_user_details() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.10' );
	return wpsc_user_purchases();
}


/**
 * wpsc product search url
 * Add product_search parameter if required.
 * @param $url (string) URL.
 * @return (string) URL.
 */
function wpsc_product_search_url( $url ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	if ( isset( $_GET['product_search'] ) ) {
		if ( strrpos( $url, '?') ) {
			$url .= '&product_search=' . $_GET['product_search'];
		} else {
			$url .= '?product_search=' . $_GET['product_search'];
		}
	}

	return $url;

}

/**
 * wpsc adjacent products url
 * URL for the next or previous page of products on a category or group page.
 * @param $n (int) Page number.
 * @return (string) URL for the adjacent products page link.
 */
function wpsc_adjacent_products_url( $n ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;

}

/**
 * wpsc next products link
 * Links to the next page of products on a category or group page.
 * @param $text (string) Link text.
 * @param $show_disabled (bool) Show unlinked text if last page.
 * @return (string) Next page link or text.
 */
function wpsc_next_products_link( $text = 'Next', $show_disabled = false ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc previous products link
 * Links to the previous page of products on a category or group page.
 * @param $text (string) Link text.
 * @param $show_disabled (bool) Show unlinked text if first page.
 * @return (string) Previous page link or text.
 */
function wpsc_previous_products_link( $text = 'Previous', $show_disabled = false ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc first products link
 * Links to the first page of products on a category or group page.
 * @param $text (string) Link text.
 * @param $show_disabled (bool) Show unlinked text if last page.
 * @return (string) First page link or text.
 */
function wpsc_first_products_link( $text = 'First', $show_disabled = false ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc last products link
 * Links to the last page of products on a category or group page.
 * @param $text (string) Link text.
 * @param $show_disabled (bool) Show unlinked text if first page.
 * @return (string) Last page link or text.
 */
function wpsc_last_products_link( $text = 'Last', $show_disabled = false ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;

}

/**
 * Saves the variation set data
 * @param nothing
 * @return nothing
 */
function wpsc_save_variation_set() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

/**
 * wpsc have pages function
 * @return boolean - true while we have pages to loop through
 */
function wpsc_have_pages() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc the page function
 * @return nothing - iterate through the pages
 */
function wpsc_the_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc page number function
 * @return integer - the page number
 */
function wpsc_page_number() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

function wpsc_ordersummary() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function display_ecomm_rss_feed() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function display_ecomm_admin_menu() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

// displays error messages if the category setup is odd in some way
// needs to be in a function because there are at least three places where this code must be used.
function wpsc_odd_category_setup() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_product_image_html( $image_name, $product_id ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_delete_currency_layer() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_send_mail() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_hide_pop() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_page() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_share_link($action = 'print') {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	if($action == 'print')
		echo '<div class="st_sharethis" displayText="ShareThis"></div>';
	else
		return '<div class="st_sharethis" displayText="ShareThis"></div>';
	return false;
}

function wpsc_akst_share_form() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_has_shipping_form() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

/**
 * wpsc_is_admin function.
 *
 * @access public
 * @return void
 * General use function for checking if user is on WPSC admin pages
 */

function wpsc_is_admin() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8');
    global $pagenow;

    $current_screen = get_current_screen();

        if( 'post.php' == $pagenow && 'wpsc-product' == $current_screen->post_type ) return true;

    return false;

}

/**
 * used in legacy theme templates
 * see http://plugins.svn.wordpress.org/wp-e-commerce/tags/3.7.8/themes/default/category_widget.php
 *
 * @return void
 */
function wpsc_print_product_list() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
}

/**
 * count total products on a page
 * see http://plugins.svn.wordpress.org/wp-e-commerce/tags/3.7.8/themes/iShop/products_page.php
 *
 * @return int
 */
function wpsc_total_product_count() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	return wpsc_product_count();
}

/**
 * WPSC_Query() is deprecated in favor of WP_Query()
 * Note that although we fall back to WP_Query() when WPSC_Query() is used,
 * the results might not be what you expect.
 *
 */
class WPSC_Query extends WP_Query {
	function WPSC_Query( $query = '' ) {
		_wpsc_deprecated_function( __FUNCTION__, '3.8', 'WP_Query()' );
		$query = wp_parse_args( $query );
		$query['post_type'] = 'wpsc-product';
		parent::WP_Query( $query );
	}
}

function wpec_get_the_post_id_by_shortcode( $shortcode ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'wpsc_get_the_post_id_by_shortcode' );
	return wpsc_get_the_post_id_by_shortcode( $shortcode );
}

/**
 * wpsc_update_permalinks update the product pages permalinks when WordPress permalinks are changed
 *
 * @public
 *
 * @deprecated Use _wpsc_action_permalink_structure_changed() instead.
 * @3.8
 * @returns nothing
 */
function wpsc_update_permalinks( $return = '' ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', '_wpsc_action_permalink_structure_changed' );
	_wpsc_action_permalink_structure_changed();
}

/**
 * @deprecated Use _wpsc_display_permalink_refresh_notice() instead;
 */
function wpsc_check_permalink_notice() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', '_wpsc_display_permalink_refresh_notice' );
	_wpsc_display_permalink_refresh_notice();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_display_tracking_id(){
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   $value = wpsc_trackingid_value();
   if(!empty($value))
	  return $value;
   else
	  return __('Add New','wpsc');
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_price() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( $purchlogs->purchitem->processed > 1 && $purchlogs->purchitem->processed != 6 ) {
	  $purchlogs->totalAmount += $purchlogs->purchitem->totalprice;
   }
   return $purchlogs->purchitem->totalprice;
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_date() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return date( 'M d Y,g:i a', $purchlogs->purchitem->date );
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_name() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( wpsc_purchlogs_has_customfields( wpsc_the_purch_item_id() ) ) {
      return $purchlogs->the_purch_item_name() . '<img src="' . WPSC_CORE_IMAGES_URL . '/info_icon.jpg" title="' . esc_attr__( 'This Purchase has custom user content', 'wpsc' ) . '" alt="' . esc_attr__( 'exclamation icon', 'wpsc' ) . '" />';
   } else {
	  return $purchlogs->the_purch_item_name();
   }
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_id() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->purchitem->id;
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_details() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->the_purch_item_details();
}

//status loop functions
/**
 * status loop functions
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_have_purch_items_statuses() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->have_purch_status();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_status() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->the_purch_status();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_purchlogs_is_google_checkout() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( $purchlogs->purchitem->gateway == 'google' ) {
	  return true;
   } else {
	  return false;
   }
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_total() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->totalAmount;
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( isset( $_SESSION['newlogs'] ) ) {
	  $purchlogs->allpurchaselogs = $_SESSION['newlogs'];
	  $purchlogs->purch_item_count = count( $_SESSION['newlogs'] );
   }
   return $purchlogs->the_purch_item();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_statuses() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->the_purch_item_statuses();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_item_status() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->the_purch_item_status();
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_status_id() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   return $purchlogs->purchstatus['order'];
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_purchlog_filter_by() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
	wpsc_change_purchlog_view( $_POST['view_purchlogs_by'], $_POST['view_purchlogs_by_status'] );
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_the_purch_status_name() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( isset( $purchlogs->purchstatus['label'] ) ) {
	  return $purchlogs->purchstatus['label'];
   }
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_purchlogs_getfirstdates() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   $dates = $purchlogs->getdates();
   $fDate = '';
   foreach ( $dates as $date ) {
	  $is_selected = '';
	  $cleanDate = date( 'M Y', $date['start'] );
	  $value = $date["start"] . "_" . $date["end"];
	  if ( $value == $_GET['view_purchlogs_by'] ) {
		 $is_selected = 'selected="selected"';
	  }
	  $fDate .= "<option value='{$value}' {$is_selected}>" . $cleanDate . "</option>";
   }
   return $fDate;
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_change_purchlog_view( $viewby, $status='' ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   if ( $viewby == 'all' ) {
	  $dates = $purchlogs->getdates();
	  $purchaselogs = $purchlogs->get_purchlogs( $dates, $status );
	  $_SESSION['newlogs'] = $purchaselogs;
	  $purchlogs->allpurchaselogs = $purchaselogs;
   } elseif ( $viewby == '3mnths' ) {
	  $dates = $purchlogs->getdates();
	  $dates = array_slice( $dates, 0, 3 );
	  $purchlogs->current_start_timestamp = $dates[count($dates)-1]['start'];
	  $purchlogs->current_end_timestamp = $dates[0]['end'];
	  $newlogs = $purchlogs->get_purchlogs( $dates, $status );
	  $_SESSION['newlogs'] = $newlogs;
	  $purchlogs->allpurchaselogs = $newlogs;
   } else {

	  $dates = explode( '_', $viewby );
	  $date[0]['start'] = $dates[0];
	  $date[0]['end'] = $dates[1];
	  $purchlogs->current_start_timestamp = $dates[0];
	  $purchlogs->current_end_timestamp = $dates[1];
	  $newlogs = $purchlogs->get_purchlogs( $date, $status );
	  $_SESSION['newlogs'] = $newlogs;
	  $purchlogs->allpurchaselogs = $newlogs;
   }
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_search_purchlog_view( $search ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogs;
   $newlogs = $purchlogs->search_purchlog_view( $search );
   $purchlogs->getDates();
   $purchlogs->purch_item_count = count( $newlogs );
   $purchlogs->allpurchaselogs = $newlogs;
}

/**
 * @deprecated since 3.8.8. Not used in core any more.
 */
function wpsc_purchlog_is_checked_status() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.8' );
   global $purchlogitem, $purchlogs;

   if ( $purchlogs->purchstatus['order'] == $purchlogitem->extrainfo->processed ) {
	  return 'selected="selected"';
   } else {
	  return '';
   }
}

/**
 * @deprecated since 3.8.9. Use _wpsc_country_dropdown_options instead.
 * @param  string $selected_country ISO code of selected country
 * @return string                   output
 */
function country_list( $selected_country = null ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', '_wpsc_country_dropdown_options' );
	return _wpsc_country_dropdown_options( array( 'selected' => $selected_country ) );
}

/**
 * @deprecated since 3.8.9. Use wpsc_get_the_product_tags() instead.
 * @param  integer $id Product ID
 * @return array       Product tags
 */
function get_the_product_tags( $id = 0 ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'wpsc_get_the_product_tags' );
	return wpsc_get_the_product_tags( $id );
}

/**
 * wpsc_product_rows function, copies the functionality of the wordpress code for displaying posts and pages, but is for products
 *
 * @deprecated since 3.8.9
 */
function wpsc_admin_product_listing( $parent_product = null, $args = array() ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9' );
	global $wp_query;

	if ( empty( $args ) )
		$args = $wp_query->query;

	add_filter( 'the_title','esc_html' );

	$args = array_merge( $args, array( 'posts_per_page' => '-1' ) );

	$GLOBALS['wpsc_products'] = get_posts( $args );

	if ( ! $GLOBALS['wpsc_products'] ) :

	?>
	<tr>
		<td colspan="8">
			<?php _e( 'You have no Variations added.', 'wpsc' ); ?>
		</td>
	</tr>
	<?php

	endif;

	foreach ( (array)$GLOBALS['wpsc_products'] as $product ) {
		wpsc_product_row( $product, $parent_product );
	}
}

/**
 * Spits out the current products details in a table row for manage products page and variations on edit product page.
 * @access public
 *
 * @deprecated since 3.8.9
 * @since 3.8
 * @param $product (Object), $parent_product (Int) Note: I believe parent_product is unused
 */
function wpsc_product_row(&$product, $parent_product = null) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9' );
	global $mode, $current_user, $wpsc_products;

	//is this good practice? <v.bakaitis@gmail.com>
	static $rowclass, $object_terms_cache = array();

	// store terms associated with variants inside a cache array. This only requires 1 DB query.
	if ( empty( $object_terms_cache ) ) {
		$ids = wp_list_pluck( $wpsc_products, 'ID' );
		$object_terms = wp_get_object_terms( $ids, 'wpsc-variation', array( 'fields' => 'all_with_object_id' ) );
		foreach ( $object_terms as $term ) {
			if ( ! array_key_exists( $term->object_id, $object_terms_cache ) )
				$object_terms_cache[$term->object_id] = array();

			$object_terms_cache[$term->object_id][$term->parent] = $term->name;
		}
	}

	$global_product = $product;
	setup_postdata($product);
	$product_post_type_object = get_post_type_object('wpsc-product');
	$current_user_can_edit_this_product = current_user_can( $product_post_type_object->cap->edit_post, $product->ID );

	$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
	$post_owner = ( $current_user->ID == $product->post_author ? 'self' : 'other' );
	$edit_link = get_edit_post_link( $product->ID );

	if ( isset( $object_terms_cache[$product->ID] ) ) {
		ksort( $object_terms_cache[$product->ID] );
		$title = implode( ', ', $object_terms_cache[$product->ID] );
	} else {
		$title = get_the_title( $product->ID );
	}

	if ( empty( $title ) )
		$title = __( '(no title)', 'wpsc' );

	?>

	<tr id='post-<?php echo $product->ID; ?>' class='<?php echo trim( $rowclass . ' author-' . $post_owner . ' status-' . $product->post_status ); ?> iedit <?php if ( get_option ( 'wpsc_sort_by' ) == 'dragndrop') { echo 'product-edit'; } ?>' valign="top">
	<?php
	$posts_columns = get_column_headers( 'wpsc-product_variants' );

	if(empty($posts_columns))
		$posts_columns = array('image' => '', 'title' => __('Name', 'wpsc') , 'weight' => __('Weight', 'wpsc'), 'stock' => __('Stock', 'wpsc'), 'price' => __('Price', 'wpsc'), 'sale_price' => __('Sale Price', 'wpsc'), 'SKU' => __('SKU', 'wpsc'), 'hidden_alerts' => '');

	foreach ( $posts_columns as $column_name=>$column_display_name ) {
		$attributes = "class=\"$column_name column-$column_name\"";

		switch ($column_name) {

                    case 'date': /* !date case */
			if ( '0000-00-00 00:00:00' == $product->post_date && 'date' == $column_name ) {
				$t_time = $h_time = __( 'Unpublished', 'wpsc' );
				$time_diff = 0;
			} else {
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'wpsc' ) );
				$m_time = $product->post_date;
				$time = get_post_time('G', true, $post);

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'wpsc' ), human_time_diff( $time ) );
				else
					$h_time = mysql2date(__( 'Y/m/d', 'wpsc' ), $m_time);
			}

			echo '<td ' . $attributes . '>';
			if ( 'excerpt' == $mode )
				echo apply_filters('post_date_column_time', $t_time, $post, $column_name, $mode);
			else
				echo '<abbr title="' . $t_time . '">' . apply_filters('post_date_column_time', $h_time, $post, $column_name, $mode) . '</abbr>';
			echo '<br />';
			if ( 'publish' == $product->post_status ) {
				_e( 'Published', 'wpsc' );
			} elseif ( 'future' == $product->post_status ) {
				if ( $time_diff > 0 )
					echo '<strong class="attention">' . __( 'Missed schedule', 'wpsc' ) . '</strong>';
				else
					_e( 'Scheduled', 'wpsc' );
			} else {
				_e( 'Last Modified', 'wpsc' );
			}
			echo '</td>';
		break;

		case 'title': /* !title case */
			$attributes = 'class="post-title column-title"';

			$edit_link = wp_nonce_url( $edit_link, 'edit-product_'.$product->ID );
		?>
		<td <?php echo $attributes ?>>
			<strong>
			<?php if ( $current_user_can_edit_this_product && $product->post_status != 'trash' ) { ?>
				<span><a class="row-title" href="<?php echo esc_url( $edit_link ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'wpsc' ), $title ) ); ?>"><?php echo esc_html( $title ) ?></a></span>
				<?php if($parent_product): ?>
					<a href="<?php echo esc_url( $edit_link ); ?>" title="<?php echo esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'wpsc' ), $title ) ); ?>"><?php echo esc_html( $title ) ?></a>

				<?php endif; ?>
			<?php } else {
				echo esc_html( $title );
			};

			 _post_states($product);
			$product_alert = apply_filters('wpsc_product_alert', array(false, ''), $product);
			if(!empty($product_alert['messages']))
				$product_alert['messages'] = implode("\n",(array)$product_alert['messages']);

			if($product_alert['state'] === true) {
				?>
				<img alt='<?php echo $product_alert['messages'];?>' title='<?php echo $product_alert['messages'];?>' class='product-alert-image' src='<?php echo  WPSC_CORE_IMAGES_URL;?>/product-alert.jpg' alt='' />
				<?php
			}

			// If a product alert has stuff to display, show it.
			// Can be used to add extra icons etc
			if ( !empty( $product_alert['display'] ) ) {
				echo $product_alert['display'];
			}

			 ?>
			</strong>
			<?php
 			$has_var = '';
 			if(! $parent_product && wpsc_product_has_children($product->ID))
 				$has_var = 'wpsc_has_variation';
			$actions = array();
			if ( $current_user_can_edit_this_product && 'trash' != $product->post_status ) {
				$actions['edit'] = '<a class="edit-product" href="'.$edit_link.'" title="' . esc_attr__( 'Edit this product', 'wpsc' ) . '">'. __( 'Edit', 'wpsc' ) . '</a>';
				//commenting this out for now as we are trying new variation ui quick edit boxes are open by default so we dont need this link.
				//$actions['quick_edit'] = "<a class='wpsc_editinline ".$has_var."' title='".esc_attr(__('Quick Edit', 'wpsc'))."' href='#'>".__('Quick Edit', 'wpsc')."</a>";
			}

			$actions = apply_filters('post_row_actions', $actions, $product);
			$action_count = count($actions);
			$i = 0;
			echo '<div class="row-actions">';

			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				echo "<span class='$action'>$link$sep</span>";
			}

			echo '</div>';
		?>
		</td>
		<?php
		break;

		case 'image':  /* !image case */
			?>
			<td class="product-image ">
			<?php
			$attachment_args = array(
		          'post_type' => 'attachment',
		          'numberposts' => 1,
		          'post_status' => null,
		          'post_parent' => $product->ID,
		          'orderby' => 'menu_order',
		          'order' => 'ASC'
			    );

		 	 if(isset($product->ID) && has_post_thumbnail($product->ID)){
				echo get_the_post_thumbnail($product->ID, 'admin-product-thumbnails');
		     } else {
		      	$image_url = WPSC_CORE_IMAGES_URL . "/no-image-uploaded.gif";
				?>
					<img title='<?php esc_attr_e( 'Drag to a new position', 'wpsc' ); ?>' src='<?php echo esc_url( $image_url ); ?>' alt='<?php echo esc_attr( $title ); ?>' width='38' height='38' />
			<?php
	    		  }
			?>
			</td>
			<?php
		break;

		case 'price':  /* !price case */

			$price = get_product_meta($product->ID, 'price', true);
			?>
				<td  <?php echo $attributes ?>>
					<?php echo wpsc_currency_display( $price ); ?>
					<input type="text" class="wpsc_ie_field wpsc_ie_price" value="<?php echo esc_attr( $price ); ?>">
					<a href="<?php echo $edit_link?>/#wpsc_downloads"><?php esc_html_e( 'Variant Download Files', 'wpsc' ); ?></a>
				</td>
			<?php
		break;

		case 'weight' :

			$product_data['meta'] = array();
			$product_data['meta'] = get_post_meta($product->ID, '');
				foreach($product_data['meta'] as $meta_name => $meta_value) {
					$product_data['meta'][$meta_name] = maybe_unserialize(array_pop($meta_value));
				}
		$product_data['transformed'] = array();
		if(!isset($product_data['meta']['_wpsc_product_metadata']['weight'])) $product_data['meta']['_wpsc_product_metadata']['weight'] = "";
		if(!isset($product_data['meta']['_wpsc_product_metadata']['weight_unit'])) $product_data['meta']['_wpsc_product_metadata']['weight_unit'] = "";

		$product_data['transformed']['weight'] = wpsc_convert_weight($product_data['meta']['_wpsc_product_metadata']['weight'], "pound", $product_data['meta']['_wpsc_product_metadata']['weight_unit'], false);
			$weight = $product_data['transformed']['weight'];
			if($weight == ''){
				$weight = '0';
			}
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo esc_html( $weight ); ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_weight" value="<?php echo esc_attr( $weight ); ?>">
					<a href="<?php echo $edit_link?>/#wpsc_tax"><?php esc_html_e( 'Set Variant Tax', 'wpsc' ); ?></a>
				</td>
			<?php

		break;

		case 'stock' :
			$stock = get_post_meta($product->ID, '_wpsc_stock', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo $stock ? $stock : __( 'N/A', 'wpsc' ) ; ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_stock" value="<?php echo esc_attr( $stock ); ?>">
					<a href="<?php echo $edit_link?>/#wpsc_shipping"><?php esc_html_e( 'Set Variant Shipping', 'wpsc' ); ?></a>
				</td>
	<?php
		break;

		case 'categories':  /* !categories case */
		?>
		<td <?php echo $attributes ?>><?php
			$categories = get_the_product_category($product->ID);
			if ( !empty( $categories ) ) {
				$out = array();
				foreach ( $categories as $c )
					$out[] = "<a href='admin.php?page=wpsc-edit-products&amp;category={$c->slug}'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
					echo join( ', ', $out );
			} else {
				esc_html_e( 'Uncategorized', 'wpsc' );
			}
		?></td>
		<?php
		break;

		case 'tags':  /* !tags case */
		?>
		<td <?php echo $attributes ?>><?php
			$tags = get_the_tags($product->ID);
			if ( !empty( $tags ) ) {
				$out = array();
				foreach ( $tags as $c )
					$out[] = "<a href='edit.php?tag=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'post_tag', 'display')) . "</a>";
				echo join( ', ', $out );
			} else {
				esc_html_e( 'No Tags', 'wpsc' );
			}
		?></td>
		<?php
		break;
		case 'SKU':
			$sku = get_post_meta($product->ID, '_wpsc_sku', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo $sku ? $sku : esc_html__( 'N/A', 'wpsc' ); ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_sku" value="<?php echo esc_attr( $sku ); ?>">
										<input type="hidden" class="wpsc_ie_id wpsc_ie_field" value="<?php echo $product->ID ?>">
					<div class="wpsc_inline_actions"><input type="button" class="button-primary wpsc_ie_save" value="Save"><img src="<?php echo admin_url( 'images/wpspin_light.gif' ) ?>" class="loading_indicator"><br/></div>
				</td>
			<?php
		break;
		case 'sale_price':

			$sale_price = get_post_meta($product->ID, '_wpsc_special_price', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo wpsc_currency_display( $sale_price ); ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_special_price" value="<?php echo esc_attr( $sale_price ); ?>">
				</td>
			<?php

		break;

		case 'comments':  /* !comments case */
		?>
		<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
		<?php
			$pending_phrase = sprintf( __( '%s pending', 'wpsc' ), number_format( $pending_comments ) );
			if ( $pending_comments )
				echo '<strong>';
				comments_number("<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x( '0', 'comment count', 'wpsc' ) . '</span></a>', "<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x('1', 'comment count', 'wpsc') . '</span></a>', "<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link: % will be substituted by comment count */ _x('%', 'comment count', 'wpsc') . '</span></a>');
				if ( $pending_comments )
				echo '</strong>';
		?>
		</div></td>
		<?php
		break;

		case 'author':  /* !author case */
		?>
		<td <?php echo $attributes ?>><a href="edit.php?author=<?php the_author_meta('ID'); ?>"><?php the_author() ?></a></td>
		<?php
		break;

		case 'control_view':  /* !control view case */
		?>
		<td><a href="<?php the_permalink(); ?>" rel="permalink" class="view"><?php esc_html_e( 'View', 'wpsc' ); ?></a></td>
		<?php
		break;

		case 'control_edit':  /* !control edit case */
		?>
		<td><?php if ( $current_user_can_edit_this_product ) { echo "<a href='$edit_link' class='edit'>" . esc_html__( 'Edit', 'wpsc' ) . "</a>"; } ?></td>
		<?php
		break;

		case 'control_delete':  /* !control delete case */
		?>
		<td><?php if ( $current_user_can_edit_this_product ) { echo "<a href='" . wp_nonce_url("post.php?action=delete&amp;post=$id", 'delete-post_' . $product->ID) . "' class='delete'>" . __( 'Delete', 'wpsc' ) . "</a>"; } ?></td>
		<?php
		break;

		case 'featured': /* !control featured case */
		?>
			<td><?php do_action( 'manage_posts_featured_column', $product->ID ); ?></td>
		<?php
		break;
		default:   /* !default case */
		?>
		<td <?php echo $attributes ?>><?php do_action( 'manage_posts_custom_column', $column_name, $product->ID ); ?></td>
		<?php
		break;
	}
}
?>
	</tr>
<?php
	$product = $global_product;
}

/**
 * Adds the -trash status in the product row of manage products page
 *
 * Gary asks      : Why do we need this?
 * Justin answers : We don't.  Deprecate?
 *
 * @access public
 *
 * @deprecated since 3.8.9
 * @since 3.8
 * @param $post_status (array) of current posts statuses
 * @return $post_status (array)
 */
function wpsc_trashed_post_status($post_status){
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9' );
	$post = get_post(get_the_ID());
	if( !empty($post) && 'wpsc-product' == $post->post_type && 'trash' == $post->post_status && !in_array('trash', $post_status))
		$post_status[] = 'Trash';

	return $post_status;
}

function wpsc_product_label_forms() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	return false;
}

function wpsc_convert_weights($weight, $unit) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_convert_weight' );
	if (is_array($weight)) {
		$weight = $weight['weight'];
	}
	return wpsc_convert_weight( $weight, $unit, 'gram', true  );
}

/**
 * wpsc in the loop function,
 * @return boolean - true if we are in the loop
 */
function wpsc_in_the_loop() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	global $wpsc_query;
	return $wpsc_query->in_the_loop;
}

/**
 * wpsc rewind products function, rewinds back to the first product
 * @return nothing
 */
function wpsc_rewind_products() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	global $wpsc_query;
	return $wpsc_query->rewind_posts();
}

/**
 * wpsc product has file function
 * @return boolean - true if the product has a file
 */
function wpsc_product_has_file() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	global $wpsc_query, $wpdb;
	if ( is_numeric( $wpsc_query->product['file'] ) && ($wpsc_query->product['file'] > 0) )
		return true;

	return false;
}

/**
 * wpsc currency sign function
 * @return string - the selected currency sign for the store
 */
function wpsc_currency_sign() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	global $wpdb;
	$currency_sign_location = get_option( 'currency_sign_location' );
	$currency_type = get_option( 'currency_type' );
	$currency_symbol = $wpdb->get_var( $wpdb->prepare( "SELECT `symbol_html` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` = %d LIMIT 1", $currency_type ) );

	return $currency_symbol;
}

/**
 * wpsc page is selected function
 * @return boolean - true if the page is selected
 */
function wpsc_page_is_selected() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	// determine if we are on this page
	global $wpsc_query;
	return $wpsc_query->page['selected'];
}

/**
 * wpsc page URL function
 * @return string - the page URL
 */
function wpsc_page_url() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8' );
	// generate the page URL
	global $wpsc_query;
	return $wpsc_query->page['url'];
}

function shipwire_build_xml( $log_id ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::get_order_xml( $log_id );
}

function shipwire_built_sync_xml() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::get_inventory_xml();
}

function shipwire_built_tracking_xml() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::get_tracking_xml();
}

function shipwire_send_sync_request( $xml ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::send_inventory_request( $xml );
}

function shipwire_sent_request( $xml ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::send_order_request( $xml );
}

function shipwire_send_tracking_request( $xml ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9', 'WPSC_Shipwire' );
	return WPSC_Shipwire::send_tracking_request( $xml );
}

function wpsc_rage_where( $where ) {
    _wpsc_deprecated_function( __FUNCTION__, '3.8.8', 'wpsc_range_where()' );
    return wpsc_range_where( $where );
}

/**
 * WPSC Product Variation Price Available
 * Gets the formatted lowest price of a product's available variations.
 *
 * @param  $product_id         (int)     Product ID
 * @param  $from_text          (string)  From text with price placeholder eg. 'from %s'
 * @param  $only_normal_price  (bool)    Don't show sale price
 * @return                     (string)  Number formatted price
 *
 * @uses   wpsc_product_variation_price_from()
 */
function wpsc_product_variation_price_available( $product_id, $from_text = false, $only_normal_price = false ) {
    _wpsc_deprecated_function( __FUNCTION__, '3.8.10', 'wpsc_product_variation_price_from()' );
	$args = array(
		'from_text'         => $from_text,
		'only_normal_price' => $only_normal_price,
		'only_in_stock'     => true
	);
	return wpsc_product_variation_price_from( $product_id, $args );
}

/**
 * Deprecated function
 *
 * @deprecated 3.8.9
 */
function wpsc_post_title_seo( $title ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.9' );
	global $wpdb, $page_id, $wp_query;
	$new_title = wpsc_obtain_the_title();
	if ( $new_title != '' ) {
		$title = $new_title;
	}
	return esc_html( $title );
}

function wpsc_product_image_forms() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	global $post;

	edit_multiple_image_gallery( $post );

?>

    <p><strong <?php if ( isset( $display ) ) echo $display; ?>><a href="media-upload.php?parent_page=wpsc-edit-products&amp;post_id=<?php echo $post->ID; ?>&amp;type=image&amp;tab=gallery&amp;TB_iframe=1&amp;width=640&amp;height=566" class="thickbox" title="<?php esc_attr_e( 'Manage Product Images', 'wpsc' ); ?>"><?php esc_html_e( 'Manage Product Images', 'wpsc' ); ?></a></strong></p>
<?php
}

function edit_multiple_image_gallery( $post ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	global $wpdb;

	// Make sure thumbnail isn't duplicated
	if ( $post->ID > 0 ) {
		if ( has_post_thumbnail( $post->ID ) )
			echo get_the_post_thumbnail( $post->ID, 'admin-product-thumbnails' );

		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => $post->ID,
			'orderby' => 'menu_order',
			'order' => 'ASC'
		);

		$attached_images = (array)get_posts( $args );

		if ( count( $attached_images ) > 0 ) {
			foreach ( $attached_images as $images ) {
				$attached_image = wp_get_attachment_image( $images->ID, 'admin-product-thumbnails' );
				echo $attached_image. '&nbsp;';
			}
		}

	}
}

function wpsc_media_upload_tab_gallery( $tabs ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	unset( $tabs['gallery'] );
	$tabs['gallery'] = __( 'Product Image Gallery', 'wpsc' );

	return $tabs;
}

function wpsc_media_upload_url( $form_action_url ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	$form_action_url = esc_url( add_query_arg( array( 'parent_page'=>'wpsc-edit-products' ) ) );

	return $form_action_url;

}

function wpsc_gallery_css_mods() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	print '<style type="text/css">
			#gallery-settings *{
			display:none;
			}
			a.wp-post-thumbnail {
					color:green;
			}
			#media-upload a.del-link {
				color:red;
			}
			#media-upload a.wp-post-thumbnail {
				margin-left:0px;
			}
			td.savesend input.button {
				display:none;
			}
	</style>';
	print '
	<script type="text/javascript">
	jQuery(function(){
		jQuery("td.A1B1").each(function(){

			var target = jQuery(this).next();
				jQuery("p > input.button", this).appendTo(target);

		});

		jQuery("a.wp-post-thumbnail").each(function(){
			var product_image = jQuery(this).text();
			if (product_image == "' . __( 'Use as featured image' ) . '") {
				jQuery(this).text("' . __( 'Use as Product Thumbnail', 'wpsc' ) . '");
			}
		});
	});

	</script>';
}

function wpsc_filter_delete_text( $translation, $text, $domain ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	if ( 'Delete' == $text && isset( $_REQUEST['post_id'] ) && isset( $_REQUEST['parent_page'] ) ) {
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( 'Trash' ) ;
	}
	return $translation;
}

/*
 * This filter translates string before it is displayed
 * specifically for the words 'Use as featured image' with 'Use as Product Thumbnail' when the user is selecting a Product Thumbnail
 * using media gallery.
 *
 * @todo As this feature is entirely cosmetic and breaks with WP_DEBUG on in WP 3.5+, we've removed the filter for it.  Will revisit the functionality in 3.9 when we look at new media workflows.
 * @param $translation The current translation
 * @param $text The text being translated
 * @param $domain The domain for the translation
 * @return string The translated / filtered text.
 */
function wpsc_filter_feature_image_text( $translation, $text, $domain ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );
	if ( 'Use as featured image' == $text && isset( $_REQUEST['post_id'] ) ) {
		$post = get_post( $_REQUEST['post_id'] );
		if ( $post->post_type != 'wpsc-product' ) return $translation;
		$translations = &get_translations_for_domain( $domain );
		return $translations->translate( 'Use as Product Thumbnail', 'wpsc' );
		//this will never happen, this is here only for gettexr to pick up the translation
		return __( 'Use as Product Thumbnail', 'wpsc' );
	}

	return $translation;
}

function wpsc_display_invoice() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$purchase_id = (int)$_REQUEST['purchaselog_id'];
	add_action('wpsc_packing_slip', 'wpsc_packing_slip');
	do_action('wpsc_before_packing_slip', $purchase_id);
	do_action('wpsc_packing_slip', $purchase_id);
	exit();
}

function wpsc_packing_slip( $purchase_id ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );
	echo "<!DOCTYPE html><html><meta http-equiv=\"content-type\" content=\"text-html; charset=utf-8\"><head><title>" . __( 'Packing Slip', 'wpsc' ) . "</title></head><body id='wpsc-packing-slip'>";
	global $wpdb;
	$purch_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`=%d", $purchase_id );
	$purch_data = $wpdb->get_row( $purch_sql, ARRAY_A ) ;

	$cartsql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=%d", $purchase_id );
	$cart_log = $wpdb->get_results($cartsql,ARRAY_A) ;
	$j = 0;

	if($cart_log != null) {
		echo "<div class='packing_slip'>\n\r";
		echo apply_filters( 'wpsc_packing_slip_header', '<h2>' . esc_html__( 'Packing Slip', 'wpsc' ) . "</h2>\n\r" );
		echo "<strong>". esc_html__( 'Order', 'wpsc' )." #</strong> ".$purchase_id."<br /><br />\n\r";

		echo "<table>\n\r";

		$form_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_SUBMITTED_FORM_DATA."` WHERE `log_id` = %d", $purchase_id );
		$input_data = $wpdb->get_results($form_sql,ARRAY_A);

		foreach($input_data as $input_row) {
			$rekeyed_input[$input_row['form_id']] = $input_row;
		}


		if($input_data != null) {
			$form_data = $wpdb->get_results( "SELECT * FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `active` = '1' ORDER BY `checkout_order`" , ARRAY_A );

			foreach($form_data as $form_field) {

				switch($form_field['type']) {
					case 'country':
						$region_count_sql = $wpdb->prepare( "SELECT COUNT(`regions`.`id`) FROM `".WPSC_TABLE_REGION_TAX."` AS `regions` INNER JOIN `".WPSC_TABLE_CURRENCY_LIST."` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN('%s')", $purch_data['billing_country'] );
						$delivery_region_count = $wpdb->get_var( $region_count_sql );

						if(is_numeric($purch_data['billing_region']) && ($delivery_region_count > 0))
							echo "	<tr><td>".esc_html__('State', 'wpsc').":</td><td>".wpsc_get_region($purch_data['billing_region'])."</td></tr>\n\r";

						 echo "	<tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>" . esc_html(  $rekeyed_input[$form_field['id']]['value'] ) . "</td></tr>\n\r";
					break;

					case 'delivery_country':

						if(is_numeric($purch_data['shipping_region']) && ($delivery_region_count > 0))
							echo "	<tr><td>".esc_html__('State', 'wpsc').":</td><td>".wpsc_get_region($purch_data['shipping_region'])."</td></tr>\n\r";

						 echo "	<tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>" . esc_html( $rekeyed_input[ $form_field['id']]['value'] ) . "</td></tr>\n\r";
					break;

					case 'heading':

                        if($form_field['name'] == "Hidden Fields")
                          continue;
                        else
                          echo "	<tr class='heading'><td colspan='2'><strong>" . esc_html( $form_field['name'] ) . ":</strong></td></tr>\n\r";
					break;

					default:
						if ($form_field['name']=="State" && !empty($purch_data['billing_region']) || $form_field['name']=="State" && !empty($purch_data['billing_region']))
							echo "";
						else
							echo "	<tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>".
								( isset( $rekeyed_input[$form_field['id']] ) ? esc_html( $rekeyed_input[$form_field['id']]['value'] ) : '' ) .
								"</td></tr>\n\r";
					break;
				}

			}
		} else {
			echo "	<tr><td>".esc_html__('Name', 'wpsc').":</td><td>".$purch_data['firstname']." ".$purch_data['lastname']."</td></tr>\n\r";
			echo "	<tr><td>".esc_html__('Address', 'wpsc').":</td><td>".$purch_data['address']."</td></tr>\n\r";
			echo "	<tr><td>".esc_html__('Phone', 'wpsc').":</td><td>".$purch_data['phone']."</td></tr>\n\r";
			echo "	<tr><td>".esc_html__('Email', 'wpsc').":</td><td>".$purch_data['email']."</td></tr>\n\r";
		}

		if ( 2 == get_option( 'payment_method' ) ) {
			$gateway_name = '';
			global $nzshpcrt_gateways;

			foreach( $nzshpcrt_gateways as $gateway ) {
				if ( $purch_data['gateway'] != 'testmode' ) {
					if ( $gateway['internalname'] == $purch_data['gateway'] ) {
						$gateway_name = $gateway['name'];
					}
				} else {
					$gateway_name = esc_html__('Manual Payment', 'wpsc');
				}
			}
		}

		echo "</table>\n\r";


		do_action ('wpsc_packing_slip_extra_info',$purchase_id);


		echo "<table class='packing_slip'>";

		echo "<tr>";
		echo " <th>".esc_html__('Quantity', 'wpsc')." </th>";

		echo " <th>".esc_html__('Name', 'wpsc')."</th>";


		echo " <th>".esc_html__('Price', 'wpsc')." </th>";

		echo " <th>".esc_html__('Shipping', 'wpsc')." </th>";
		echo '<th>' . esc_html__('Tax', 'wpsc') . '</th>';
		echo '</tr>';
		$endtotal = 0;
		$all_donations = true;
		$all_no_shipping = true;
		$file_link_list = array();
		$total_shipping = 0;
		foreach($cart_log as $cart_row) {
			$alternate = "";
			$j++;
			if(($j % 2) != 0) {
				$alternate = "class='alt'";
			}
			// product ID will be $cart_row['prodid']. need to fetch name and stuff

			$variation_list = '';

			if($cart_row['donation'] != 1) {
				$all_donations = false;
			}

			if($cart_row['no_shipping'] != 1) {
				$shipping = $cart_row['pnp'];
				$total_shipping += $shipping;
				$all_no_shipping = false;
			} else {
				$shipping = 0;
			}

			$price = $cart_row['price'] * $cart_row['quantity'];
			$gst = $price - ($price	/ (1+($cart_row['gst'] / 100)));

			if($gst > 0) {
				$tax_per_item = $gst / $cart_row['quantity'];
			}


			echo "<tr $alternate>";


			echo " <td>";
			echo $cart_row['quantity'];
			echo " </td>";

			echo " <td>";
			echo apply_filters( 'the_title', $cart_row['name'] );
			echo $variation_list;
			echo " </td>";


			echo " <td>";
			echo wpsc_currency_display( $price );
			echo " </td>";

			echo " <td>";
			echo wpsc_currency_display($shipping );
			echo " </td>";



			echo '<td>';
			echo wpsc_currency_display( $cart_row['tax_charged'] );
			echo '</td>';
			echo '</tr>';
		}

		echo "</table>";
		echo '<table class="packing-slip-totals">';
		if ( floatval( $purch_data['discount_value'] ) )
			echo '<tr><th>'.esc_html__('Discount', 'wpsc').'</th><td>(' . wpsc_currency_display( $purch_data['discount_value'] ) . ')</td></tr>';

		echo '<tr><th>'.esc_html__('Base Shipping','wpsc').'</th><td>' . wpsc_currency_display( $purch_data['base_shipping'] ) . '</td></tr>';
		echo '<tr><th>'.esc_html__('Total Shipping','wpsc').'</th><td>' . wpsc_currency_display( $purch_data['base_shipping'] + $total_shipping ) . '</td></tr>';
        //wpec_taxes
        if($purch_data['wpec_taxes_total'] != 0.00)
        {
           echo '<tr><th>'.esc_html__('Taxes','wpsc').'</th><td>' . wpsc_currency_display( $purch_data['wpec_taxes_total'] ) . '</td></tr>';
        }
		echo '<tr><th>'.esc_html__('Total Price','wpsc').'</th><td>' . wpsc_currency_display( $purch_data['totalprice'] ) . '</td></tr>';
		echo '</table>';

		echo "</div>\n\r";
	} else {
		echo "<br />".esc_html__('This users cart was empty', 'wpsc');
	}
}

//other actions are here
if ( isset( $_GET['display_invoice'] ) && ( 'true' == $_GET['display_invoice'] ) )
	add_action( 'admin_init', 'wpsc_display_invoice', 0 );

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc_display_invoice' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_display_invoice' );


/**
 * Disable SSL validation for Curl. Added/removed on a per need basis, like so:
 *
 * add_filter('http_api_curl', 'wpsc_curl_ssl');
 * remove_filter('http_api_curl', 'wpsc_curl_ssl');
 *
 * @param resource $ch
 * @return resource $ch
 **/
function wpsc_curl_ssl( $ch ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13', "add_filter( 'https_ssl_verify', '__return_false' )" );

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	return $ch;
}


/**
 * Get cart item meta
 * @access public
 *
 * @deprecated since 3.8.13
 */
function wpsc_get_cartmeta( $cart_item_id, $meta_key ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13', 'wpsc_get_cart_item_meta');
	return wpsc_get_cart_item_meta( $cart_item_id, $meta_key, true );
}

/**
 * Update cart item meta
 * @access public
 *
 * @deprecated since 3.8.13
 */
function wpsc_update_cartmeta( $cart_item_id, $meta_key, $meta_value ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13', 'wpsc_update_cart_item_meta');
	return wpsc_update_cart_item_meta( $cart_item_id, $meta_key, $meta_value );
}

/**
 * Delete cart item meta
 * @access public
 *
 * @deprecated since 3.8.13
 */
function wpsc_delete_cartmeta( $cart_item_id, $meta_key, $meta_value = '' ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13', 'wpsc_delete_cart_item_meta');
	return wpsc_delete_cart_item_meta( $cart_item_id, $meta_key, $meta_value );
}

function wpsc_get_exchange_rate( $from, $to ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13' );
	return _wpsc_get_exchange_rate( $from, $to );
}


/**
 * @access public
 * @param unknown $stuff
 * @param unknown $post_ID
 * @return string
 * @deprecated since 3.8.13.3
 */
function wpsc_the_featured_image_fix( $stuff, $post_ID ){
	_wpsc_deprecated_function( __FUNCTION__, '3.8.13.2', 'wpsc_the_featured_image_fix');
	global $wp_query;

	$is_tax = is_tax( 'wpsc_product_category' );

	$queried_object = get_queried_object();
	$is_single = is_single() && $queried_object->ID == $post_ID && get_post_type() == 'wpsc-product';

	if ( $is_tax || $is_single ) {
		$header_image = get_header_image();
		$stuff = '';

		if ( $header_image )
			$stuff = '<img src="' . esc_url( $header_image ) . '" width="' . HEADER_IMAGE_WIDTH . '" height="' . HEADER_IMAGE_HEIGHT . '" alt="" />';
	}

	remove_action( 'post_thumbnail_html', 'wpsc_the_featured_image_fix' );

	return $stuff;
}

/**
 * @access public
 * @param string $meta_object_type Type of object metadata is for (e.g., variation. cart, etc)
 * @return string Name of the custom meta table defined in $wpdb, or the name as it would be defined
 * @deprecated since 3.8.13.4
 */
function wpsc_meta_table_name( $meta_object_type ) {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14', '_wpsc_meta_table_name' );
	return _wpsc_meta_table_name( $meta_object_type );
}

/**
 * Google checkout not longer available or supported, so we are deprecating this function
 *
 * @access public

 * @deprecated since 3.8.14
 */
function wpsc_google_checkout(){
	$currpage = wpsc_selfURL();
	if (array_search("google",(array)get_option('custom_gateway_options')) !== false && $currpage != get_option('shopping_cart_url')) {
		global $nzshpcrt_gateways;
		foreach($nzshpcrt_gateways as $gateway) {
			if($gateway['internalname'] == 'google' ) {
				$gateway_used = $gateway['internalname'];
				$gateway['function'](true);
			}
		}
	}
}

/**
 * Google checkout not longer available or supported, so we are deprecating this function
 *
 * @access public
 * @deprecated since 3.8.14
 */
function wpsc_empty_google_logs(){
	global $wpdb;
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14', 'wpsc_empty_google_logs' );
	$sql = $wpdb->prepare( "DELETE FROM  `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid` = '%s'", wpsc_get_customer_meta( 'checkout_session_id' ) );
	$wpdb->query( $sql );
	wpsc_delete_customer_meta( 'checkout_session_id' );
}

/**
 * @access public
 * @deprecated since 3.8.13.4
 */
function wpsc_user_dynamic_js() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14', 'wpsc_javascript_localizations' );
}

/*
 * Over time certain javascript variables that were once localized into scripts will become obsolete
 * When they do moving them here will continue to create the variables for older javascript to use.
 */
function _wpsc_deprecated_javascript_localization_vars() {

	/**
	 * @deprecated since 3.8.14
	 *
	 * wpsc_deprecated_vars as an object with the properties below has been replaced and each of the properties
	 * is available as it's own variable, that means devs instead of referencing "wpsc_ajax.base_url" do
	 * "base_url"
	 */

	$wpsc_deprecated_js_vars = array();

	$wpsc_deprecated_js_vars['WPSC_DIR_NAME'] 			= WPSC_DIR_NAME;
	$wpsc_deprecated_js_vars['fileLoadingImage'] 		= WPSC_CORE_IMAGES_URL . '/loading.gif';
	$wpsc_deprecated_js_vars['fileBottomNavCloseImage'] = WPSC_CORE_IMAGES_URL . '/closelabel.gif';
	$wpsc_deprecated_js_vars['resizeSpeed'] 			= 9;  // controls the speed of the image resizing (1=slowest and 10=fastest)
	$wpsc_deprecated_js_vars['borderSize'] 				= 10; //if you adjust the padding in the CSS, you will need to update this variable

	return $wpsc_deprecated_js_vars;
}

/**
 * wpsc google checkout submit used for google checkout (unsure whether necessary in 3.8)
 * @access public
 *
 * @deprecated since 3.8.14
 */
function wpsc_google_checkout_submit() {

	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

	global $wpdb, $wpsc_cart, $current_user;
	$wpsc_checkout = new wpsc_checkout();
	$purchase_log_id = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` IN(%s) LIMIT 1", wpsc_get_customer_meta( 'checkout_session_id' ) );
	get_currentuserinfo();
	if ( $current_user->display_name != '' ) {
		foreach ( $wpsc_checkout->checkout_items as $checkoutfield ) {
			if ( $checkoutfield->unique_name == 'billingfirstname' ) {
				$checkoutfield->value = $current_user->display_name;
			}
		}
	}
	if ( $current_user->user_email != '' ) {
		foreach ( $wpsc_checkout->checkout_items as $checkoutfield ) {
			if ( $checkoutfield->unique_name == 'billingemail' ) {
				$checkoutfield->value = $current_user->user_email;
			}
		}
	}

	$wpsc_checkout->save_forms_to_db( $purchase_log_id );
	$wpsc_cart->save_to_db( $purchase_log_id );
	$wpsc_cart->submit_stock_claims( $purchase_log_id );
}

/**
 *
 * @deprecated 3.8.14
 * @uses apply_filters()      Allows manipulation of the flash upload params.
 */
function wpsc_admin_dynamic_css() {

	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

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

/**
 * everywhere else in the code we use "wpsc_ajax_action", not the plural, deprecate this version
 * @deprecated 3.8.14
 *
 */
if ( isset( $_REQUEST['wpsc_ajax_actions'] ) && 'update_location' == $_REQUEST['wpsc_ajax_actions'] ) {
	_wpsc_deprecated_function( 'wpsc_ajax_actions', '3.8.14', 'wpsc_ajax_action' );
	add_action( 'init', 'wpsc_update_location' );
}

if ( isset( $_REQUEST['wpsc_ajax_actions'] ) && 'update_location' == $_REQUEST['wpsc_ajax_actions'] ) {
	_wpsc_doing_it_wrong( 'wpsc_ajax_actions', __( 'wpsc_ajax_actions is not the proper parameter to pass AJAX handlers to WPeC.  Use wpsc_ajax_action instead.', 'wpsc' ) );
	add_action( 'init', 'wpsc_update_location' );
}

/**
 * Update products page URL options when permalink scheme changes.
 *
 * @since  3.8.9
 * @access private
 *
 * @uses wpsc_update_page_urls() Gets the premalinks for product pages and stores for quick reference
 */
function _wpsc_action_permalink_structure_changed() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

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
function _wpsc_display_permalink_refresh_notice() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );
	?>
	<div id="notice" class="error fade">
		<p>
			<?php printf( __( 'Due to <a href="%1$s">a bug in WordPress prior to version 3.3</a>, you might run into 404 errors when viewing your products. To work around this, <a href="%2$s">upgrade to WordPress 3.3 or later</a>, or simply click "Save Changes" below a second time.' , 'wpsc' ), 'http://core.trac.wordpress.org/ticket/16736', 'http://codex.wordpress.org/Updating_WordPress' ); ?>
		</p>
	</div>
	<?php
}

/* These deprecated functions were quite horribly named, begging for namespace colliding. */
if ( ! function_exists( 'change_context' ) )  {
	/**
	 * Adding function to change text for media buttons
	 */
	function change_context( $context ) {
		_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

		$current_screen = get_current_screen();

		if ( $current_screen->id != 'wpsc-product' )
			return $context;
		return __( 'Upload Image%s', 'wpsc' );

	}
}

if ( ! function_exists( 'change_link' ) ) {
	function change_link( $link ) {
		_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

		global $post_ID;
		$current_screen = get_current_screen();
		if ( $current_screen && $current_screen->id != 'wpsc-product' )
			return $link;

		$uploading_iframe_ID = $post_ID;
		$media_upload_iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";

		return $media_upload_iframe_src . "&amp;type=image&parent_page=wpsc-edit-products";
	}
}

function wpsc_google_shipping_settings() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	if ( isset( $_POST['submit'] ) ) {
		foreach ( (array) $_POST['google_shipping'] as $key => $country ) {
			if ( $country == 'on' ) {
				$google_shipping_country[] = $key;
				$updated++;
			}
		}
		update_option( 'google_shipping_country', $google_shipping_country );
		$sendback = wp_get_referer();
		$sendback = remove_query_arg( 'googlecheckoutshipping', $sendback );

		if ( isset( $updated ) ) {
			$sendback = add_query_arg( 'updated', $updated, $sendback );
		}

		wp_redirect( $sendback );
		exit();
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'google_shipping_settings') ) {
	add_action( 'admin_init', 'wpsc_google_shipping_settings' );
}

function wpsc_css_header() {
	_wpsc_deprecated_function( __FUNCTION__, '3.8.14' );
}

/**
 * deprecating item filters from wpsc_display_form_fields() in release 3.8.13.4
 *
 *  @deprecated 3.8.14
 *
 * This function displays each of the form fields.
 *
 * Each of them are filterable via 'wpsc_account_form_field_$tag'
 * where tag is permalink-styled name or uniquename. i.e. First Name under Shipping would be
 * 'wpsc_account_form_field_shippingfirstname' - while Your Billing Details would be filtered
 * via 'wpsc_account_form_field_your-billing-details'.
 *
 * @param varies  $meta_value
 * @param string  $meta_key
 *
 */
function wpsc_user_log_deprecated_filter_values( $meta_value, $meta_key ) {
	$filter = 'wpsc_account_form_field_' . $meta_key;
	if ( has_filter( $filter ) ) {
		$meta_value = apply_filters( $filter , esc_html( $meta_value ) );
		_wpsc_doing_it_wrong( $filter, __( 'The filter being used has been deprecated. Use wpsc_get_visitor_meta or wpsc_get_visitor_meta_$neta_name instead.' ), '3.8.14' );
	}

	return $meta_value;
}
add_filter( 'wpsc_get_visitor_meta', 'wpsc_user_log_deprecated_filter_values', 10, 2 );

/**
 * deprecating user log filter for getting all customer meta as an array.
 *
 *@deprecated 3.8.14
 *
 * @return none
 */
function wpsc_deprecated_filter_user_log_get() {
	if ( has_filter( 'wpsc_user_log_get' ) ) {
		$meta_data = wpsc_get_customer_meta( 'checkout_details' );
		$meta_data = apply_filters( 'wpsc_user_log_get', $meta_data, wpsc_get_current_customer_id() );
		wpsc_update_customer_meta( 'checkout_details', $meta_data );
		_wpsc_doing_it_wrong( 'wpsc_user_log_get', __( 'The filter being used has been deprecated. Use wpsc_get_visitor_meta or wpsc_get_visitor_meta_$neta_name instead.' ), '3.8.14' );
	}
}
add_filter( 'wpsc_start_display_user_log_form_fields', 'wpsc_deprecated_filter_user_log_get', 10, 0 );


/**
 * function to privide deprecated variables to older shipping modules
 *
 * @since 3.8.14
 */
function wpsc_deprecated_vars_for_shipping( $wpsc_cart ) {
	// extracted from the insticnt fedex module
	$_POST['country'] = wpsc_get_customer_meta( 'shippingcountry' );
	$_POST['region']  = wpsc_get_customer_meta( 'shippingregion' );
	$_POST['zipcode'] = wpsc_get_customer_meta( 'shippingpostcode' );
}
add_action( 'wpsc_before_get_shipping_method', 'wpsc_deprecated_vars_for_shipping' );
