<?php
/**
 * wpsc_cart_item_custom_message()
 *
 * Deprecated function for checking whether a cart item has a custom message or not
 *
 * @return false
 * @todo Actually correctly deprecate this
 */

function wpsc_cart_item_custom_message(){
	return false;
}

/**
 * nzshpcrt_get_gateways()
 *
 * Deprecated function for returning the merchants global
 *
 * @global array $nzshpcrt_gateways
 * @return array
 * @todo Actually correctly deprecate this
 */
function nzshpcrt_get_gateways() {
	global $nzshpcrt_gateways;

	if ( !is_array( $nzshpcrt_gateways ) )
		wpsc_core_load_gateways();

	return $nzshpcrt_gateways;

}

/**
 * wpsc_merchants_modules_deprecated()
 *
 * Deprecated function for merchants modules
 *
 */
function wpsc_merchants_modules_deprecated($nzshpcrt_gateways){

	$nzshpcrt_gateways = apply_filters( 'wpsc_gateway_modules', $nzshpcrt_gateways );
	return $nzshpcrt_gateways;
}
add_filter('wpsc_merchants_modules','wpsc_merchants_modules_deprecated',1);

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
	wpsc_price_range($args);
}

// preserved for backwards compatibility
function nzshpcrt_shopping_basket( $input = null, $override_state = null ) {
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_shopping_cart');
	return wpsc_shopping_cart( $input, $override_state );
}


/**
 * Function show_cats_brands
 * deprecated as we do not have brands anymore...
 *
 */
function show_cats_brands($category_group = null , $display_method = null, $order_by = 'name', $image = null) {
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_shopping_cart'); 
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
	do_action( 'wpsc-purchlogitem-links-start' );
}
add_action( 'wpsc_purchlogitem_links_start', 'wpsc_purchlogitem_links_start_deprecated' );


function nzshpcrt_donations($args){
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
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_latest_product');
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
	//_deprecated_function( __FUNCTION__, '3.8', 'wpsc_currency_display' );
	$output = wpsc_currency_display($price_in, array(
		'display_currency_symbol' => !(bool)$no_dollar_sign,
		'display_as_html' => (bool)$nohtml,
		'display_decimal_point' => true,
		'display_currency_code' => false
	));
	return $output;
}


function wpsc_include_language_constants(){
	if(!defined('TXT_WPSC_ABOUT_THIS_PAGE'))
		include_once(WPSC_FILE_PATH.'/wpsc-languages/EN_en.php');
}
add_action('init','wpsc_include_language_constants');

if(!function_exists('wpsc_has_noca_message')){
	function wpsc_has_noca_message(){
		if(isset($_SESSION['nocamsg']) && isset($_GET['noca']) && $_GET['noca'] == 'confirm')
			return true;
		else
			return false;
	}
}

if(!function_exists('wpsc_is_noca_gateway')){
	function wpsc_is_noca_gateway(){
		if(count($wpsc_gateway->wpsc_gateways) == 1 && $wpsc_gateway->wpsc_gateways[0]['name'] == 'Noca')
			return true;
		else
			return false;
	}
}


/**
 * wpsc pagination
 * It is intended to move some of this functionality to a paging class
 * so that paging functionality can easily be created for multiple uses.
 */



/**
 * wpsc current_page
 * @return (int) The current page number
 */
function wpsc_current_page() {
	
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
	
	global $wpsc_query;
	
	$output = $wpsc_query->page_count;
	$current_page = wpsc_current_page();
	
	return $current_page . ' of ' . $output;
	
}



/**
 * wpsc product search url
 * Add product_search parameter if required.
 * @param $url (string) URL.
 * @return (string) URL.
 */
function wpsc_product_search_url( $url ) {
			
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
	
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
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
	
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
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
	
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;;
	
}

/**
 * wpsc first products link
 * Links to the first page of products on a category or group page.
 * @param $text (string) Link text.
 * @param $show_disabled (bool) Show unlinked text if last page.
 * @return (string) First page link or text.
 */
function wpsc_first_products_link( $text = 'First', $show_disabled = false ) {
	
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
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
	
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
	
}

/**
 * Saves the variation set data
 * @param nothing
 * @return nothing
 */
function wpsc_save_variation_set() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

/**
 * wpsc have pages function
 * @return boolean - true while we have pages to loop through
 */
function wpsc_have_pages() {
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc the page function
 * @return nothing - iterate through the pages
 */
function wpsc_the_page() {
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

/**
 * wpsc page number function
 * @return integer - the page number
 */
function wpsc_page_number() {
	_deprecated_function( __FUNCTION__, '3.8', 'wpsc_pagination');
	return false;
}

function wpsc_ordersummary() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function display_ecomm_rss_feed() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function display_ecomm_admin_menu() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

// displays error messages if the category setup is odd in some way
// needs to be in a function because there are at least three places where this code must be used.
function wpsc_odd_category_setup() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_product_image_html( $image_name, $product_id ) {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_delete_currency_layer() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_send_mail() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_hide_pop() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_page() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_akst_share_link($action = 'print') {
	_deprecated_function( __FUNCTION__, '3.8');
	if($action == 'print')
		echo '<div class="st_sharethis" displayText="ShareThis"></div>';
	else
		return '<div class="st_sharethis" displayText="ShareThis"></div>';
	return false;
}

function wpsc_akst_share_form() {
	_deprecated_function( __FUNCTION__, '3.8');
	return false;
}

function wpsc_has_shipping_form() {
	_deprecated_function( __FUNCTION__, '3.8');
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
	_deprecated_function( __FUNCTION__, '3.8');
    global $pagenow, $current_screen;

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
	_deprecated_function( __FUNCTION__, '3.8' );
}

/**
 * count total products on a page
 * see http://plugins.svn.wordpress.org/wp-e-commerce/tags/3.7.8/themes/iShop/products_page.php
 *
 * @return int
 */
function wpsc_total_product_count() {
	_deprecated_function( __FUNCTION__, '3.8' );
	return wpsc_product_count();
}

/**
 * WPSC_Query() is deprecated in favor of WP_Query()
 * Note that although we fall back to WP_Query() when WPSC_Query() is used,
 * the results might not be what you expect.
 *
 */
class WPSC_Query extends WP_Query
{
	function WPSC_Query( $query = '' ) {
		$query = wp_parse_args( $query );
		$query['post_type'] = 'wpsc-product';
		_deprecated_function( __FUNCTION__, '3.8', 'WP_Query class' );
		parent::WP_Query( $query );
	}
}