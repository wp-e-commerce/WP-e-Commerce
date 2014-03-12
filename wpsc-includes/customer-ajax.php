<?php

/*
 * Customer meta API via AJAX.  Note that the API only permits access for the current customer (visitor)
 * because of security considerations.
 */

/**
 * Are we processing a customer meta AJAX request
 * @param string $action optional parameter to see if we are processing a specific action
 * @return boolean
 * @since 3.8.14
 */
function _wpsc_doing_customer_meta_ajax( $action = '' ) {

	$result = ( defined( 'DOING_AJAX' ) && DOING_AJAX  && isset( $_REQUEST['action'] )
			&& ( strpos( $_REQUEST['action'], 'wpsc_' ) === 0 ) );

	if ( $result && ! empty( $action ) ) {
		$result = $_REQUEST['action'] == $action;
	}
	return $result;
}

if ( _wpsc_doing_customer_meta_ajax() ) {

	/**
	 * Validate the current customer, get the current customer id
	 * @param string
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_validate_customer_ajax() {
		// most of the validation should be done by the WPEC initialization, just return the current customer values
		$response = array( 'valid' => (_wpsc_validate_customer_cookie() !== false), 'id' => wpsc_get_current_customer_id() );
		$response = apply_filters( '_wpsc_validate_customer_ajax', $response );
		$response = json_encode( $response );
		echo $response;
		die();
	}

	/**
	 * Get a customer meta values
	 * @param string
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_get_customer_meta_ajax() {

		$meta_key = isset( $_POST['meta_key'] ) ?  $_REQUEST['meta_key'] : '';

		$response = array( 'request' => $_REQUEST );

		if ( ! empty( $meta_key ) ) {
			$response['value'] = wpsc_get_customer_meta( $meta_key );
			$response['type'] = __( 'success', 'wpsc' );
			$response['error'] = '';
		} else {
			$response['value'] = '';
			$response['type']  = __( 'error', 'wpsc' );
			$response['error'] = __( 'no meta key', 'wpsc' );
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key', 'wpsc' ), '3.8.14' );
		}

		$response = json_encode( $response );
		echo $response;
		die();

	}

	/**
	 * Update more than one customer meta
	 * @param meta_data - array of key value pairs to set
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_update_customer_metas_ajax() {

		$success = true;

		$metas = isset( $_REQUEST['meta_data'] ) ?  $_REQUEST['meta_data'] : array();

		$response = array( 'request' => $_REQUEST );

		foreach ( $metas as $meta_key => $meta_value ) {

			// this will echo back any fields to the requester. It's a
			// means for the requester to maintain some state during
			// asynchronous requests

			if ( ! empty( $meta_key ) ) {
				$updated = wpsc_update_customer_meta( $meta_key, $meta_value  );
				$success = $success & $updated;
			}
		}

		if ( $success ) {
			$response['type']          = __( 'success', 'wpsc' );
			$response['error']         = '';
		} else {
			$response['type']       = __( 'error', 'wpsc' );
			$response['error']      = __( 'meta values may not have been updated', 'wpsc' );
		}

		$response['elapsed'] = microtime( true ) - $start;

		echo json_encode( $response );
		die();
	}

	/**
	 * Update a single customer meta
	 * @param string
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_update_customer_meta_ajax() {

		$success = false;

		$meta_key = isset( $_REQUEST['meta_key'] ) ?  $_REQUEST['meta_key'] : '';
		$meta_value = isset( $_REQUEST['meta_value'] ) ?  $_REQUEST['meta_value'] : '';

		// this will echo back any fields to the requester. It's a
		// means for the requester to maintain some state during
		// asynchronous requests
		$response = array( 'request' => $_REQUEST );

		if ( ! empty( $meta_key ) ) {
			$success = wpsc_update_customer_meta( $meta_key, $meta_value  );
		} else {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key', 'wpsc' ), '3.8.14' );
		}

		$response['meta_key'] = $meta_key;

		if ( ! empty( $meta_key ) && $success ) {
			$response['meta_value']    = $meta_value;
			$response['type']          = __( 'success', 'wpsc' );
			$response['error']         = '';

			$all_meta_keys = wpsc_checkout_unique_names();

			$customer_meta = array();

			foreach ( $all_meta_keys as $a_meta_key ) {
				$customer_meta[$a_meta_key] = wpsc_get_customer_meta( $a_meta_key );
			}

			$response['customer_meta'] = $customer_meta;
			$response = apply_filters( 'wpsc_customer_meta_response_' . $meta_key, $response, $meta_key, $meta_value );

		} else {
			$response['meta_value']      = wpsc_get_customer_meta( $meta_key );
			$response['type']       = __( 'error', 'wpsc' );
			$response['error']      = __( 'meta value was not updated', 'wpsc' );
		}

		echo json_encode( $response );
		die();
	}



	/**
	 * Delete a customer meta
	 * @param string
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_delete_customer_meta_ajax() {

		$meta_key = isset( $_POST['meta_key'] ) ?  $_REQUEST['meta_key'] : '';

		$response = array( 'request' => $_REQUEST );

		if ( ! empty( $meta_key ) ) {
			$response['value'] = wpsc_get_customer_meta( $meta_key );
			$response['type'] = __( 'success', 'wpsc' );
			$response['error'] = '';
			wpsc_delete_customer_meta( $meta_key );
		} else {
			$response['value'] = '';
			$response['type']  = __( 'error', 'wpsc' );
			$response['error'] = __( 'no meta key', 'wpsc' );
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key', 'wpsc' ), '3.8.14' );
		}

		$response = json_encode( $response );
		echo $response;
		die();
	}

	/**
	 * Get all customer metas
	 * @param string
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_get_customer_metas_ajax() {
		$response = array( 'request' => $_REQUEST );

		if ( isset( $_POST['meta_keys'] ) && ! empty( $_POST['meta_keys']) ) {
			$meta_keys = $_POST['meta_keys'];
		} else {
			$meta_keys = wpsc_checkout_unique_names();
		}

		foreach ( $meta_keys as $meta_key ) {
			$response[$meta_key] = wpsc_get_customer_meta( $meta_key );
		}

		$response['type'] = 'success';

		echo json_encode( $response );
		die();
	}

	if ( _wpsc_doing_customer_meta_ajax() ) {
		add_action( 'wp_ajax_wpsc_validate_customer'       		, 'wpsc_validate_customer_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_validate_customer'		, 'wpsc_validate_customer_ajax' );

		add_action( 'wp_ajax_wpsc_get_customer_meta'       		, 'wpsc_get_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_get_customer_meta'		, 'wpsc_get_customer_meta_ajax' );

		add_action( 'wp_ajax_wpsc_get_customer_metas'       	, 'wpsc_get_customer_metas_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_get_customer_metas'	, 'wpsc_get_customer_metas_ajax' );

		add_action( 'wp_ajax_wpsc_delete_customer_meta'       	, 'wpsc_delete_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_delete_customer_meta'	, 'wpsc_delete_customer_meta_ajax' );

		add_action( 'wp_ajax_wpsc_update_customer_meta'       	, 'wpsc_update_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_update_customer_meta'	, 'wpsc_update_customer_meta_ajax' );

		add_action( 'wp_ajax_wpsc_update_customer_metas'       	, 'wpsc_update_customer_metas_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_update_customer_metas'	, 'wpsc_update_customer_metas_ajax' );
	}


	/*************************************************************************************************
	 *  Here start the built in processing that happens when a shopper changes customer meta
	 *  on the user facing interface
	 *
	 *  Note:
	 *  the hook priority is set higher than typical user priorities so that other hooks
	 *  that want to modify the results are triggered after the built ins
	 *
	 *  Note:
	 *  the update customer meta AJAX routine returns a JSON encoded esponse to the browser. Within
	 *  the reponse is the original request, key value pairs for any customer meta items that may need
	 *  to be updated in the user interface, and a replacements array that has interfce elements that
	 *  may need to be replaced in the user interface.   For an example of the replacements array
	 *  element format see _wpsc_shipping_same_as_billing_ajax_response
	 *
	 *
	 *************************************************************************************************/
	if ( ! defined( '_WPSC_USER_META_HOOK_PRIORITY' ) ) {
		define( '_WPSC_USER_META_HOOK_PRIORITY' , 2 );
	}


	/**
	 * Update the shipping same as billing meta value, return updated content to the user via the filter response
	 *
	 * @since 3.8.14
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return response array   val;ues to be encoded in JSON response to browser
	 */
	function _wpsc_shipping_same_as_billing_ajax_response( $response, $meta_key, $meta_value ) {

		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		if ( $meta_value == 1 ) {
			_wpsc_get_country_and_region_replacements( $replacements );
		}

		$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
		$replacements['change_country'] = $replacement;

		$response['replacements']  = $replacements;

		return $response;
	}

	add_filter( 'wpsc_customer_meta_response_shippingSameBilling', '_wpsc_shipping_same_as_billing_ajax_response', _WPSC_USER_META_HOOK_PRIORITY, 3 );


	/**
	 * The html used on the default checkout for to create the select shipping desination selector,code lifted from the default
	 * checkout form in version 3.8.14
	 *
	 * @since 3.8.14
	 *
	 * @return string
	 */
	function _wpsc_calculate_shipping_price_form() {
		ob_start();
		?>
		<form name='change_country' id='change_country' action='' method='post'>
			<?php echo wpsc_shipping_country_list();?>
		    <input type='hidden' name='wpsc_update_location' value='true' />
		    <input type='submit' name='wpsc_submit_zipcode' value='<?php esc_attr_e( 'Calculate', 'wpsc' ); ?>' />
		</form>
		<?php
		$result = ob_get_clean();

		return $result;
	}


	/**
	 * when the billing region is updated set the billing state meta value to the plain text version of the region
	 *
	 * @since 3.8.14
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return response array   val;ues to be encoded in JSON response to browser
	 */
	function _wpsc_update_customer_billingregion( $response, $meta_key, $meta_value ) {

		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		if ( wpsc_get_customer_meta( 'shippingSameBilling' ) == '1' ) {
			$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
			$replacements['change_country'] = $replacement;
			$response['replacements']  = $replacements;
		}

		$response['checkout_info'] = _wpsc_get_checkout_info();

		return $response;
	}

	add_action( 'wpsc_customer_meta_response_billingregion', '_wpsc_update_customer_billingregion', _WPSC_USER_META_HOOK_PRIORITY, 3 );

	/**
	 * Update the shipping same as billing meta value, return updated content to the user
	 *
	 * @since 3.8.14
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return response array   val;ues to be encoded in JSON response to browser
	 */
	function _wpsc_update_customer_billingcountry( $response, $meta_key, $meta_value ) {

		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		$replacements = _wpsc_get_country_and_region_replacements( $replacements, true, false );

		if ( wpsc_get_customer_meta( 'shippingSameBilling' ) == '1' ) {
			$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
			$replacements['change_country'] = $replacement;
		}

		$response['replacements']  = $replacements;

		$response['checkout_info'] = _wpsc_get_checkout_info();

		return $response;
	}

	add_action( 'wpsc_customer_meta_response_billingcountry', '_wpsc_update_customer_billingcountry', _WPSC_USER_META_HOOK_PRIORITY, 3 );


	/**
	 * when the shipping region is updated set the shipping state meta value to the plain text version of the region
	 *
	 * @since 3.8.14
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return response array   val;ues to be encoded in JSON response to browser
	 */
	function _wpsc_update_customer_shippingregion( $response, $meta_key, $meta_value ) {
		global $wpdb;
		$region_name = $wpdb->get_var( $wpdb->prepare( 'SELECT `name` FROM `' . WPSC_TABLE_REGION_TAX . '` WHERE `id`= %d LIMIT 1', $meta_value ) );
		wpsc_update_customer_meta( 'shippingstate', $region_name );


		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
		$replacements['change_country'] = $replacement;
		$response['replacements']  = $replacements;

		$response['checkout_info'] = _wpsc_get_checkout_info();

		return $response;
	}

	add_action( 'wpsc_customer_meta_response_shippingregion', '_wpsc_update_customer_shippingregion', _WPSC_USER_META_HOOK_PRIORITY, 3 );


	/**
	 * when the shipping region is updated set the shipping state meta value to the plain text version of the region
	 *
	 * @since 3.8.14
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return response array   val;ues to be encoded in JSON response to browser
	 */
	function _wpsc_update_customer_shippingcountry( $response, $meta_key, $meta_value ) {

		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		$replacements = _wpsc_get_country_and_region_replacements( $replacements, false, true );

		$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
		$replacements['change_country'] = $replacement;
		$response['replacements']  = $replacements;

		$response['checkout_info'] = _wpsc_get_checkout_info();

		return $response;
	}

	add_action( 'wpsc_customer_meta_response_shippingcountry', '_wpsc_update_customer_shippingcountry', _WPSC_USER_META_HOOK_PRIORITY, 3 );




	/***************************************************************************************************************************************
	 * Customer meta is built on a lower level API, Visitor meta.  Some visitor meta values are dependant on each other and need to
	 * be changed when other visitor meta values change.  For example, shipping same as billing.  Below is the built in functionality
	 * that enforces those changes.  Developers are free to add additional relationships as needed in plugins
	 ***************************************************************************************************************************************/

	/**
	 * when visitor meta is updated we need to check if the shipping same as billing
	 * option is selected.  If so we need to update the corresponding meta value.
	 *
	 * @since 3.8.14
	 * @access private
	 * @param $meta_value any value being stored
	 * @param $meta_key string name of the attribute being stored
	 * @param $visitor_id int id of the visitor to which the attribute applies
	 * @return n/a
	 */
	function _wpsc_vistor_shipping_same_as_billing_meta_update( $meta_value, $meta_key, $visitor_id ) {

		// remove the action so we don't cause an infinite loop
		remove_action( 'wpsc_updated_visitor_meta', '_wpsc_vistor_shipping_same_as_billing_meta_update', _WPSC_USER_META_HOOK_PRIORITY );

		// if the shipping same as billing option is being checked then copy meta from billing to shipping
		if ( $meta_key == 'shippingSameBilling' ) {
			if ( $meta_value == 1 ) {

				$checkout_names = wpsc_checkout_unique_names();

				foreach ( $checkout_names as $meta_key ) {
					$meta_key_starts_with_billing = strpos( $meta_key, 'billing', 0 ) === 0;

					if ( $meta_key_starts_with_billing ) {
						$other_meta_key_name = 'shipping' . substr( $meta_key, strlen( 'billing' ) );
						if ( in_array( $other_meta_key_name, $checkout_names ) ) {
							$billing_meta_value = wpsc_get_customer_meta( $meta_key );
							wpsc_update_customer_meta( $other_meta_key_name, $billing_meta_value );
						}
					}
				}
			}
		} else {
			$shipping_same_as_billing = wpsc_get_customer_meta( 'shippingSameBilling' );

			if ( $shipping_same_as_billing ) {

				$meta_key_starts_with_billing = strpos( $meta_key, 'billing', 0 ) === 0;
				$meta_key_starts_with_shipping = strpos( $meta_key, 'shipping', 0 ) === 0;

				if ( $meta_key_starts_with_billing ) {
					$checkout_names = wpsc_checkout_unique_names();

					$other_meta_key_name = 'shipping' . substr( $meta_key, strlen( 'billing' ) );

					if ( in_array( $other_meta_key_name, $checkout_names ) ) {
						wpsc_update_customer_meta( $other_meta_key_name, $meta_value );
					}
				} elseif ( $meta_key_starts_with_shipping ) {
					$checkout_names = wpsc_checkout_unique_names();

					$other_meta_key_name = 'billing' . substr( $meta_key, strlen( 'shipping' ) );

					if ( in_array( $other_meta_key_name, $checkout_names ) ) {
						wpsc_update_customer_meta( $other_meta_key_name, $meta_value );
					}
				}
			}
		}

		// restore the action we removed at the start
		add_action( 'wpsc_updated_visitor_meta', '_wpsc_vistor_shipping_same_as_billing_meta_update', _WPSC_USER_META_HOOK_PRIORITY, 3 );
	}

	add_action( 'wpsc_updated_visitor_meta', '_wpsc_vistor_shipping_same_as_billing_meta_update', _WPSC_USER_META_HOOK_PRIORITY, 3 );


	/**
	 * Values to change in response to the shopper updating shipping same as billing
	 *
	 * @since 3.8.14
	 * @access private
	 * @param unknown $response
	 * @param unknown $meta_key
	 * @param unknown $meta_value
	 * @return AJAX response to be pass to client via JSON encode
	 */
	function _wpsc_customer_meta_response_shippingSameBilling( $response, $meta_key, $meta_value ) {

		if ( ! isset( $response['replacements'] ) ) {
			$replacements = array();
		} else {
			$replacements = $response['replacements'];
		}

		$replacements = _wpsc_get_country_and_region_replacements( $replacements, true, true );

		$replacement = array( 'elementid' => 'change_country', 'element' => _wpsc_calculate_shipping_price_form() );
		$replacements['change_country'] = $replacement;
		$response['replacements']  = $replacements;

		$response['checkout_info'] = _wpsc_get_checkout_info();

		return $response;
	}


	add_filter( 'wpsc_customer_meta_response_shippingSameBilling', '_wpsc_customer_meta_response_shippingSameBilling', _WPSC_USER_META_HOOK_PRIORITY, 3 );


	function _wpsc_customer_shipping_quotes_need_recalc( $meta_value, $meta_key, $customer_id ) {
		wpsc_clear_cart_shipping_info();
	}

	add_action( 'wpsc_updated_customer_meta_shippinggregion', '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
	add_action( 'wpsc_updated_customer_meta_shippingcountry',  '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
	add_action( 'wpsc_updated_customer_meta_shippingpostcode',  '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
	add_action( 'wpsc_updated_customer_meta_shippingstate', '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
} // end if doing customer meta ajax




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
		$wpsc_checkout = new WPSC_Checkout();
	}

	if ( empty( $replacements ) ) {
		$replacements = array();
	}

	while ( wpsc_have_checkout_items() ) {
		$checkoutitem = wpsc_the_checkout_item();

		if ( $replaceshipping && ( $checkoutitem->unique_name == 'shippingcountry' ) ) {
			$element_id = 'region_country_form_' . wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['shippingcountry'] = $replacement;
		}

		if ( $replaceshipping && ( $checkoutitem->unique_name == 'shippingstate' ) ) {
			$element_id = wpsc_checkout_form_element_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['shippingstate'] = $replacement;
		}

		if ( $replacebilling && ( $checkoutitem->unique_name == 'billingcountry' ) ) {
			$element_id = 'region_country_form_' . wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['billingcountry'] = $replacement;
		}

		if ( $replacebilling && ( $checkoutitem->unique_name == 'billingstate' ) ) {
			$element_id = wpsc_checkout_form_item_id();
			$replacement = array( 'elementid' => $element_id, 'element' => wpsc_checkout_form_field() );
			$replacements['billingstate'] = $replacement;
		}
	}

	return $replacements;
}

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
	global $wpdb, $wpsc_cart;
	global $wpdb, $user_ID, $wpsc_customer_checkout_details;

	// Checkout info is what we will return to the AJAX client
	$checkout_info = array();

	// start with items that have no dependancies

	$checkout_info['delivery_country'] = wpsc_get_customer_meta( 'shippingcountry' );
	$checkout_info['billing_country']  = wpsc_get_customer_meta( 'billingcountry' );
	$checkout_info['country_name']     = wpsc_get_country( $checkout_info['delivery_country'] );
	$checkout_info['lock_tax']         = get_option( 'lock_tax' );  // TODO:this is set anywhere, probably deprecated

	$checkout_info['needs_shipping_recalc'] = wpsc_need_to_recompute_shipping_quotes();

	$checkout_info['shipping_keys']    = array();

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

		$cart_widget         = _wpsc_ajax_get_cart( false );
		$cart_widget_output  = $cart_widget['widget_output'];

		$checkout_info['widget_output']    = $cart_widget_output;
		$checkout_info['cart_shipping']    = wpsc_cart_shipping();
		$checkout_info['tax']              = $tax;
		$checkout_info['display_tax']      = wpsc_cart_tax();
		$checkout_info['total']            = $total;
		$checkout_info['total_input']      = $total_input;
	}

	return $checkout_info;
}

