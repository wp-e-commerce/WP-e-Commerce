<?php

/**
 * Get replacement elements for country and region fields on the checkout form
 *
 *  Note: extracted from the wpsc_change_tax function in ajax.php as of version 3.8.13.3
 *
 * @since 3.8.14
 * @access private
 * @return array  checkout information
 */
function _wpsc_get_checkout_info() {
	global $wpsc_cart;

	// Checkout info is what we will return to the AJAX client
	$checkout_info = array();

	// start with items that have no dependencies

	$checkout_info['delivery_country'] = wpsc_get_customer_meta( 'shippingcountry' );
	$checkout_info['billing_country']  = wpsc_get_customer_meta( 'billingcountry' );
	$checkout_info['country_name']     = wpsc_get_country( $checkout_info['delivery_country'] );
	$checkout_info['lock_tax']         = get_option( 'lock_tax' );  // TODO: this is set anywhere, probably deprecated

	$checkout_info['needs_shipping_recalc'] = wpsc_cart_need_to_recompute_shipping_quotes();
	$checkout_info['shipping_keys']         = array();

	foreach ( $wpsc_cart->cart_items as $key => $cart_item ) {
		$checkout_info['shipping_keys'][ $key ] = wpsc_currency_display( $cart_item->shipping );
	}

	if ( ! $checkout_info['needs_shipping_recalc'] ) {

		$wpsc_cart->update_location();
		$wpsc_cart->get_shipping_method();
		$wpsc_cart->get_shipping_option();

		if ( $wpsc_cart->selected_shipping_method != '' ) {
			$wpsc_cart->update_shipping( $wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option );
		}

		$tax         = $wpsc_cart->calculate_total_tax();
		$total       = wpsc_cart_total();
		$total_input = wpsc_cart_total( false );

		if ( $wpsc_cart->coupons_amount >= $total_input && ! empty( $wpsc_cart->coupons_amount ) ) {
			$total = 0;
		}

		if ( $wpsc_cart->total_price < 0 ) {
			$wpsc_cart->coupons_amount += $wpsc_cart->total_price;
			$wpsc_cart->total_price     = null;
			$wpsc_cart->calculate_total_price();
		}

		$cart_widget = _wpsc_ajax_get_cart( false );

		if ( isset( $cart_widget['widget_output'] ) && ! empty ( $cart_widget['widget_output'] ) ) {
			$checkout_info['widget_output'] = $cart_widget['widget_output'];
		}

		$checkout_info['cart_shipping'] = wpsc_cart_shipping();
		$checkout_info['tax']           = $tax;
		$checkout_info['display_tax']   = wpsc_cart_tax();
		$checkout_info['total']         = $total;
		$checkout_info['total_input']   = $total_input;
	}

	return apply_filters( 'wpsc_ajax_checkout_info', $checkout_info );
}


/**
 * remove checkout info that has not changed
 *
 * @since 3.8.14
 * @access private
 * @return array  checkout information
 */
function _wpsc_remove_unchanged_checkout_info( $old_checkout_info, $new_checkout_info ) {

	foreach ( $new_checkout_info as $key => $value ) {
		if ( isset( $old_checkout_info[ $key ] ) ) {
			$old_checkout_info_crc = crc32( json_encode( $old_checkout_info[ $key ] ) );
			$new_checkout_info_crc = crc32( json_encode( $value ) );

			if ( $old_checkout_info_crc == $new_checkout_info_crc ) {
				unset( $new_checkout_info[ $key ] );
			}
		}
	}

	return $new_checkout_info;
}



/**
 * Get replacement elements for country and region fields on the checkout form
 *
 * @since 3.8.14
 * @access private
 * @param array $replacements
 * @return array $replacements array
 */
function _wpsc_get_country_and_region_replacements( $replacements = null, $replacebilling = true, $replaceshipping = true ) {
	global $wpsc_checkout;
	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
	}

	if ( empty( $replacements ) ) {
		$replacements = array();
	}

	while ( wpsc_have_checkout_items() ) {
		$checkoutitem = wpsc_the_checkout_item();

		if ( $replaceshipping && 'shippingcountry' == $checkoutitem->unique_name ) {
			$element_id = 'region_country_form_' . wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['shippingcountry'] = $replacement;
		}

		if ( $replaceshipping && 'shippingstate' == $checkoutitem->unique_name ) {
			$element_id = wpsc_checkout_form_element_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['shippingstate'] = $replacement;
		}

		if ( $replacebilling && 'billingcountry' == $checkoutitem->unique_name  ) {
			$element_id = 'region_country_form_' . wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['billingcountry'] = $replacement;
		}

		if ( $replacebilling && 'billingstate' == $checkoutitem->unique_name ) {
			$element_id = wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['billingstate'] = $replacement;
		}
	}

	return $replacements;
}

/**
 * Get the current values for checkout meta
 *
 * @since 3.8.14
 * @access private
 *
 * @param array values being readied to send back to javascript in the json encoded AJAX response
 * @param string|array|null meta keys to retrieve, if not specified all meta keys are retrieved
 * @return JSON encoded array with results, results include original request parameters
 */
function _wpsc_get_checkout_meta( $meta_keys = null ) {

	if ( ! empty( $meta_keys ) ) {
		if ( ! is_array( $meta_keys ) ) {
			$meta_keys = array( $meta_keys );
		}
	} else {
		$meta_keys = wpsc_checkout_unique_names();
	}

	$checkout_meta = array();

	foreach ( $meta_keys as $a_meta_key ) {
		$checkout_meta[$a_meta_key] = wpsc_get_customer_meta( $a_meta_key );
	}

	return $checkout_meta;
}


/**
 * Update customer information using information supplied by shopper on WPeC pages
 *
 * @since 3.8.14
 *
 * @global  $_REQUEST['meta_data']  array of key value pairs that the user has changed, key is meta item name, value is new value
 *
 * @return JSON encoded response array with results
 *
 * 			$RESPONSE['request']		: 	array containing the original AJAX $_REQUEST that was sent to
 * 											the server, use to match up asynchronous AJAX transactions, or
 * 											to see original rquiest paramters
 *
 * 			$RESPONSE['customer_meta']	: 	array of key value pairs containing updated meta values. The
 * 											specific value changed is not included. If there isn't any updated
 * 											customer meta, other than the original meta changed, this array element
 * 											may not be present, or may be present and empty
 *
 * 			$response['checkout_info']  :	array of updated checkout information, array key is the HTML element ID
 * 											where the information is presented on the checkout form. If there isn't
 * 											any updated	checkout information this array element	may not be present,
 * 											or may be present and empty
 *
 *
 */
function wpsc_customer_updated_data_ajax() {

	$success = true;

	// we will echo back the request in the (likely async) response so that the client knows
	// which transaction the response matches
	$response = array( 'request' => $_REQUEST );

	// update can be a single key/value pair or an array of key value pairs
	if ( ! empty ( $_REQUEST['meta_data'] ) ) {
		$customer_meta = isset( $_REQUEST['meta_data'] ) ?  $_REQUEST['meta_data'] : array();
	} elseif ( ! empty( $_REQUEST['meta_key'] ) && isset( $_REQUEST['meta_value'] ) ) {
		$customer_meta = array( $_REQUEST['meta_key'] => $_REQUEST['meta_value'] );
	} else {
		_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key or meta array', 'wp-e-commerce' ), '3.8.14' );
		$customer_meta = array();
	}

	// We will want to know which interface elements have changed as a result of this meta update,
	// capture the current state of the elements
	$checkout_info_before_updates = _wpsc_get_checkout_info();

	// We will want to know which, if any, checkout meta changes as a result of hooks and filters
	// that may fire as we update each meta item
	$all_checkout_meta_before_updates = _wpsc_get_checkout_meta();

	if ( ! empty( $customer_meta ) ) {

		foreach ( $customer_meta as $meta_key => $meta_value ) {

			// this will echo back any fields to the requester. It's a
			// means for the requester to maintain some state during
			// asynchronous requests

			if ( ! empty( $meta_key ) ) {
				$updated = wpsc_update_customer_meta( $meta_key, $meta_value  );
				$success = $success & $updated;
			}
		}

		// loop through a second time so that all of the meta has been set, tht way if there are
		// dependencies in response calculation
		foreach ( $customer_meta as $meta_key => $meta_value ) {
			$response = apply_filters( 'wpsc_customer_meta_response_' . $meta_key, $response, $meta_key, $meta_value );
		}

		if ( $success ) {
			$response['type']  = 'success';
			$response['error'] = '';
		} else {
			$response['type']  = 'error';
			$response['error'] = __( 'meta values may not have been updated', 'wp-e-commerce' );
		}
	} else {
		$response['type']  = 'error';
		$response['error'] = __( 'invalid parameters, meta array or meta key value pair required', 'wp-e-commerce' );
	}

	// Let's see what the current state of the customer meta set is after we applied the requested updates
	$all_checkout_meta_after_updates = _wpsc_get_checkout_meta();

	foreach ( $all_checkout_meta_after_updates as $current_meta_key => $current_meta_value ) {

		// if the meta key and value are the same as what was sent in the request we don't need to
		// send them back because the client already knows about this.
		//
		// But we have to check just in case a data rule or a plugin that used our hooks made some adjustments
		if ( isset( $all_checkout_meta_before_updates[$current_meta_key] ) && ( $all_checkout_meta_before_updates[$current_meta_key] == $current_meta_value ) ) {
			// new value s the same as the old value, why send it?
			unset( $all_checkout_meta_after_updates[$current_meta_key] );
			unset( $all_checkout_meta_before_updates[$current_meta_key] );
			continue;
		}

		// if the meta value we are considering sending back is one of the values the client gave, we don't send it
		// because the client already knows the meta value and it is probably already visible in the user interface
		if ( isset( $customer_meta[$current_meta_key] ) && ( $customer_meta[$current_meta_key] == $current_meta_value ) ) {
			// new value s the same as the old value, why send it?
			unset( $all_checkout_meta_after_updates[$current_meta_key] );
			continue;
		}
	}

	// Any checkout meta that has changed as a result of the requeeted updates remains
	// in our array, add it to the response
	$response['customer_meta'] = $all_checkout_meta_after_updates;

	// Get the changed checkout information and if something has changed add it to the repsonse
	$new_checkout_info = _wpsc_remove_unchanged_checkout_info( $checkout_info_before_updates, _wpsc_get_checkout_info() );

	if ( ! empty( $new_checkout_info ) ) {
		$response['checkout_info'] = $new_checkout_info;
	} else {
		if ( isset( $response['checkout_info'] ) ) {
			unset( $response['checkout_info'] );
		}
	}

	// do the shipping quotes need to be recalcualted?
	$response['needs_shipping_recalc'] = wpsc_cart_need_to_recompute_shipping_quotes();

	wp_send_json_success( $response );
}

add_action( 'wp_ajax_wpsc_customer_updated_data'       , 'wpsc_customer_updated_data_ajax' );
add_action( 'wp_ajax_nopriv_wpsc_customer_updated_data', 'wpsc_customer_updated_data_ajax' );