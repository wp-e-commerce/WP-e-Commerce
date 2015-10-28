<?php

/**
 * wpsc_currency_display function.
 *
 * @access public
 * @param mixed $price_in
 * @param mixed $args
 * @return string
 */
function wpsc_currency_display( $price_in, $args = null ) {
	global $wpdb;
	$currency_code = '';

	$args = apply_filters( 'wpsc_toggle_display_currency_code', $args );
	$query = shortcode_atts( array(
		'display_currency_symbol' => true,
		'display_decimal_point'   => true,
		'display_currency_code'   => false,
		'display_as_html'         => true,
		'isocode'                 => false,
	), $args );

	// No decimal point, no decimals
	if ( false == $query['display_decimal_point'] )
		$decimals = 0;
	else
		$decimals = 2; // default is 2

	$decimals = apply_filters('wpsc_modify_decimals' , $decimals);

	// Format the price for output
	$price_out = wpsc_format_number( $price_in, $decimals );

	// Get currency settings
	$currency_type = get_option( 'currency_type' );

	if ( ! $query['isocode'] ) {
		// @todo: can deprecate this caching because the WPSC_Countries class already caches the data
		if ( ! $wpsc_currency_data = wp_cache_get( $currency_type, 'wpsc_currency_id' ) ) {
			$wpsc_currency_data = WPSC_Countries::get_currency_data( $currency_type, true );
			wp_cache_set( $currency_type, $wpsc_currency_data, 'wpsc_currency_id' );
		}
	} elseif ( ! $wpsc_currency_data = wp_cache_get( $query['isocode'], 'wpsc_currency_isocode' ) ) {
		$wpsc_currency_data = WPSC_Countries::get_currency_data( $currency_type, true );
		wp_cache_set( $query['isocode'], $wpsc_currency_data, 'wpsc_currency_isocode' );
	}

	// Figure out the currency code
	if ( $query['display_currency_code'] ) {
		$currency_code = $wpsc_currency_data['code'];
	}

	// Figure out the currency sign
	$currency_sign = '';

	if ( $query['display_currency_symbol'] ) {
		if ( ! empty( $wpsc_currency_data['symbol'] ) ) {
			if ( $query['display_as_html'] && !empty($wpsc_currency_data['symbol_html']) ) {
				$currency_sign = $wpsc_currency_data['symbol_html'];
			} else {
				$currency_sign = $wpsc_currency_data['symbol'];
			}
		} else {
			$currency_sign = $wpsc_currency_data['code'];
			$currency_code = '';
		}
	}

	$currency_sign_location = get_option( 'currency_sign_location' );

	// Rejig the currency sign location
	switch ( $currency_sign_location ) {
		case 1:
			$format_string = '%3$s%1$s%2$s';
			break;

		case 2:
			$format_string = '%3$s %1$s%2$s';
			break;

		case 4:
			$format_string = '%1$s%2$s  %3$s';
			break;

		case 3:
		default:
			$format_string = '%1$s %2$s%3$s';
			break;
	}

	// Compile the output
	$output = trim( sprintf( $format_string, $currency_code, $currency_sign, $price_out ) );

	if ( !$query['display_as_html'] ) {
		$output = "".$output."";
	} else {
		$output = "<span class='pricedisplay'>".$output."</span>";
	}

	// Return results
	return apply_filters( 'wpsc_currency_display', $output );
}

/**
 * wpsc_decrement_claimed_stock method
 *
 * @param float a price
 * @return string a price with a currency sign
 */
function wpsc_decrement_claimed_stock( $purchase_log_id ) {

	// Processed
	$claimed_query = new WPSC_Claimed_Stock( array( 'cart_id' => $purchase_log_id ) );
	$all_claimed_stock = $claimed_query->get_purchase_log_claimed_stock();

	do_action( 'wpsc_pre_decrement_claimed_stock', $purchase_log_id, $claimed_query );

	if ( ! empty( $all_claimed_stock ) ) {

		do_action( 'wpsc_decrement_claimed_stock_' . $all_claimed_stock[0]->processed, $purchase_log_id, $claimed_query );
		do_action( 'wpsc_decrement_claimed_stock', $purchase_log_id, $claimed_query );

		switch( $all_claimed_stock[0]->processed ) {
			case 3:
			case 4:
			case 5:
				foreach ( (array) $all_claimed_stock as $claimed_stock ) {

					$product         = get_post( $claimed_stock->product_id );
					$current_stock   = get_post_meta( $product->ID, '_wpsc_stock', true );
					$remaining_stock = $current_stock - $claimed_stock->stock_claimed;

					update_product_meta( $product->ID, 'stock', $remaining_stock );

					$product_meta = get_product_meta( $product->ID, 'product_metadata', true );

					if ( $remaining_stock < 1 ) {
						// this is to make sure after upgrading to 3.8.9, products will have
						// "notify_when_none_left" enabled by default if "unpublish_when_none_left"
						// is enabled.
						if ( ! isset( $product_meta['notify_when_none_left'] ) ) {
							$product_meta['unpublish_when_none_left'] = 0;
							if ( ! empty( $product_meta['unpublish_when_none_left'] ) ) {
								$product_meta['unpublish_when_none_left'] = 1;
								update_product_meta( $product->ID, 'product_metadata', $product_meta );
							}
						}

						$email_message = sprintf( __( 'The product "%s" is out of stock.', 'wp-e-commerce' ), $product->post_title );

						if ( ! empty( $product_meta["unpublish_when_none_left"] ) ) {
							$result = wp_update_post( array(
								'ID'          => $product->ID,
								'post_status' => 'draft',
							) );

							if ( $result )
								$email_message = sprintf( __( 'The product "%s" is out of stock and has been unpublished.', 'wp-e-commerce' ), $product->post_title );
						}

						if ( $product_meta["notify_when_none_left"] == 1 )
							wp_mail(get_option('purch_log_email'), sprintf(__('%s is out of stock', 'wp-e-commerce'), $product->post_title), $email_message );
					}
				}
			case 6:
				$claimed_query = new WPSC_Claimed_Stock( array( 'cart_id' => $purchase_log_id ) );
				$claimed_query->clear_claimed_stock( 0 );
				break;
		}
	}

}

/**
 *	wpsc_get_currency_symbol
 *	@param does not receive anything
 *  @return returns the currency symbol used for the shop
*/
function wpsc_get_currency_symbol(){
	$currency_type = get_option( 'currency_type' );
	$wpsc_currency_data = WPSC_Countries::get_currency_symbol( $currency_type );
	return $wpsc_currency_data;
}

/**
 *	wpsc_get_currency_code
 *
 *	@param does not receive anything
 *  @return returns the currency code used for the shop
*/
function wpsc_get_currency_code(){
	return WPSC_Countries::get_currency_code( get_option( 'currency_type' ) );
}

/**
* All the code below here needs commenting and looking at to see if it needs to be altered or disposed of.
* Correspondingly, all the code above here has been commented, uses the wpsc prefix, and has been made for or modified to work with the object oriented cart code.
*/

function admin_display_total_price($start_timestamp = '', $end_timestamp = '') {
  global $wpdb;

   if( ( $start_timestamp != '' ) && ( $end_timestamp != '' ) )
	$sql = $wpdb->prepare( "SELECT SUM(`totalprice`) FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `processed` IN (2,3,4,5) AND `date` BETWEEN %s AND %s", $start_timestamp, $end_timestamp );
    else
	$sql = "SELECT SUM(`totalprice`) FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `processed` IN (2,3,4,5) AND `date` != ''";

    $total = $wpdb->get_var($sql);
  return $total;
}

function wpsc_get_mimetype($file, $check_reliability = false) {
  // Sometimes we need to know how useless the result from this is, hence the "check_reliability" parameter
	if(file_exists($file)) {
		$mimetype_data = wp_check_filetype($file);
		$mimetype = $mimetype_data['type'];
		$is_reliable = true;
	} else {
		$mimetype = false;
		$is_reliable = false;
	}

	if ( $check_reliability ) {
		return array('mime_type' => $mimetype, 'is_reliable' => $is_reliable );
	} else {
		return $mimetype;
	}
}

function wpsc_convert_weight( $in_weight, $in_unit, $out_unit = 'pound', $raw = false ) {

	// first unit in each case block is the definitive unit name
	// other unit names are used when doing imports from CSV

	// convert $in_weight to grams, then convert that to whatever else.

	switch( strtolower( $in_unit ) ) {
		case "kilogram":
		case "kilograms":
		case "kg":
		case "kgs":
			$intermediate_weight = $in_weight * 1000;
			break;

		case "gram":
		case "grams":
		case "g":
		case "gs":
			$intermediate_weight = $in_weight;
			break;

		case "ounce":
		case "once":
		case "ounces":
		case "oz":
			$intermediate_weight = ( $in_weight / 16 ) * 453.59237;
			break;

		case "pound":
		case "pounds":
		case "lb":
		case "lbs":
		default:
			$intermediate_weight = $in_weight * 453.59237;
			break;
	}

	switch( strtolower( $out_unit ) ) {
		case "kilogram":
		case "kilograms":
		case "kg":
		case "kgs":
			$weight = $intermediate_weight / 1000;
			break;

		case "gram":
		case "grams":
		case "g":
		case "gs":
			$weight = $intermediate_weight;
			break;

		case "ounce":
		case "once":
		case "ounces":
		case "oz":
			$weight = ( $intermediate_weight / 453.59237 ) * 16;
			break;

		case "pound":
		case "pounds":
		case "lb":
		case "lbs":
		default:
			$weight = $intermediate_weight / 453.59237;
			break;
	}
	if ( $raw )
		return $weight;

	return round( $weight, 2 );
}

function wpsc_ping_services() {
	wp_schedule_single_event( time(), 'do_wpsc_pings' );
}

function wpsc_ping() {
	$services = get_option('ping_sites');
	$services = explode("\n", $services);
	foreach ( (array) $services as $service ) {
		$service = trim($service);
		if($service != '' ) {
			wpsc_send_ping($service);
		}
	}
}

function wpsc_send_ping($server) {
	global $wp_version;
	$path = "";
	include_once(ABSPATH . WPINC . '/class-IXR.php');

	// using a timeout of 3 seconds should be enough to cover slow servers
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/'.$wp_version;

	// when set to true, this outputs debug messages by itself
	$client->debug = false;
	$home = trailingslashit( get_option('product_list_url') );
	$rss_url = add_query_arg( array( 'rss' => 'true', 'action' => 'product_list' ), home_url( '/' ) );
	if ( !$client->query('weblogUpdates.extendedPing', get_option('blogname'), $home, $rss_url ) ) {
		$client->query('weblogUpdates.ping', get_option('blogname'), $home);
	}
}

add_action( 'publish_wpsc-product', 'wpsc_ping_services' );
add_action( 'do_wpsc_pings', 'wpsc_ping' );


/*
 * this function checks every product on the products page to see if it has any stock remaining
 * it is executed through the wpsc_product_alert filter
 */
function wpsc_check_stock($state, $product) {
	global $wpdb;
	// if quantity is enabled and is zero
	$state['state'] = false;
	$state['messages'] = array();
	$out_of_stock = false;
	$is_parent = ! $product->post_parent && wpsc_product_has_children( $product->ID );
	if( !$is_parent ){
		$stock_count = get_product_meta( $product->ID, 'stock',true );
		// only do anything if the quantity is limited.
		if( $stock_count === '0' ) // otherwise, use the stock from the products list table
			$out_of_stock = true;

		if( $out_of_stock === true ) {
			$state['state'] = true;
			$state['messages'][] = __( 'This product has no available stock', 'wp-e-commerce' );
		}
	}else{
		$no_stock = $wpdb->get_col('
		SELECT
			`pm`.`post_id`
		FROM
			`' . $wpdb->postmeta . '` `pm`
		JOIN
			`' . $wpdb->posts . '` `p`
			ON
			`pm`.`post_id` = `p`.`id`
		WHERE
			`p`.`post_type`= "wpsc-product"
			AND
			`p`.`post_parent` = ' . $product->ID . '
			AND
			`pm`.`meta_key` = "_wpsc_stock"
			AND
			`pm`.`meta_value` = "0"
	');

		if ( ! empty( $no_stock ) ) {
			$state['state']      = true;
			$state['messages'][] = __( 'One or more of this products variations are out of stock.', 'wp-e-commerce' );
		}
	}

	return array( 'state' => $state['state'], 'messages' => $state['messages'] );
}

/*
 * if UPS is on, this function checks every product on the products page to see if it has a weight
 * it is executed through the wpsc_product_alert filter
 */
function wpsc_check_weight($state, $product) {
	global $wpdb;
	$custom_shipping = (array)get_option( 'custom_shipping_options' );
	$has_no_weight = false;
	$shipping_modules = array();
	$product_meta = get_product_meta( $product->ID, 'product_metadata',true );
	if(! $product->post_parent && wpsc_product_has_children($product->ID)) return $state;
	// only do anything if UPS is on and shipping is used
	if( array_search( 'ups', $custom_shipping ) !== false )
		$shipping_modules[] = 'UPS';
	if( array_search( 'weightrate', $custom_shipping ) !== false )
		$shipping_modules[] = 'Weight Rate';
	if( array_search( 'usps', $custom_shipping ) !== false )
		$shipping_modules[] = 'Weight Rate';

	if ( empty( $product_meta['no_shipping'] ) && !empty( $shipping_modules ) ) {

		if ( ! isset( $product_meta['weight'] ) || ( isset( $product_meta['weight'] ) && $product_meta['weight'] == 0 ) ) // otherwise, use the weight from the products list table
			$has_no_weight = true;

		if( $has_no_weight === true ) {
			$state['state'] = true;
			$state['messages'][] = implode( ',',$shipping_modules ). __(' does not support products without a weight set. Please either disable shipping for this product or give it a weight', 'wp-e-commerce' );
		}
	}
	return array( 'state' => $state['state'], 'messages' => $state['messages'] );
}

add_filter('wpsc_product_alert', 'wpsc_check_stock', 10, 2);
add_filter('wpsc_product_alert', 'wpsc_check_weight', 10, 2);

/**
 * WPSC Image Quality
 *
 * Returns the value to use for image quality when creating jpeg images.
 * By default the quality is set to 75%. It is then run through the main jpeg_quality WordPress filter
 * to add compatibility with other plugins that customise image quality.
 *
 * It is then run through the wpsc_jpeg_quality filter so that it is possible to override
 * the quality setting just for WPSC images.
 *
 * @since 3.7.6
 *
 * @param (int) $quality Optional. Image quality when creating jpeg images.
 * @return (int) The image quality.
 */
function wpsc_image_quality( $quality = 75 ) {
	$quality = apply_filters( 'jpeg_quality', $quality );
	return apply_filters( 'wpsc_jpeg_quality', $quality );
}