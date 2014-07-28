<?php

/*
 * Customer meta API via AJAX.  Note that the API only permits access for the current customer (visitor)
 * because of security considerations.
 */

/**
 * Are we processing a customer meta AJAX request
 *
 * @param string $action optional parameter to see if we are processing a specific action
 * @return boolean
 * @since 3.8.14
 */
function _wpsc_doing_customer_meta_ajax( $action = '' ) {

	$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

	$result = $doing_ajax && isset( $_REQUEST['action'] ) && ( strpos( $_REQUEST['action'], 'wpsc_' ) === 0  );

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
		wp_send_json_success( $response );
	}


	/**
	 * Get customer meta values
	 * @uses$_POST[meta] array of meta keys to retrieve, if not present all
	 * 'registered' meta keys are returned.  See wpsc_checkout_unique_names() for the list
	 *  of registered meta keys.
	 *
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */

	function wpsc_get_customer_meta_ajax() {

		if ( empty( $_POST['meta']  ) ) {
			$meta = null;
		} elseif ( ! is_array( $_POST['meta'] ) ) {
			$meta = array( $meta );
		} else {
			$meta = $_POST['meta'];
		}

		$response = array( 'request' => $_REQUEST );
		$response = _wpsc_add_customer_meta_to_response( $response, $meta );

		$response['type'] = __( 'success', 'wpsc' );
		$response['error'] = '';

		wp_send_json_success( $response );
	}

	/**
	 * Update more than one customer meta
	 * @param meta_data - array of key value pairs to set
	 * @return JSON encoded array with results, results include original request parameters
	 * @since 3.8.14
	 */
	function wpsc_update_customer_meta_ajax() {

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
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key or meta array', 'wpsc' ), '3.8.14' );
			$customer_meta = array();
		}

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
				$response['type']          = __( 'success', 'wpsc' );
				$response['error']         = '';
			} else {
				$response['type']       = __( 'error', 'wpsc' );
				$response['error']      = __( 'meta values may not have been updated', 'wpsc' );
			}
		} else {
				$response['type']       = __( 'error', 'wpsc' );
				$response['error']      = __( 'invalid parameters, meta array or meta key value pair required', 'wpsc' );
		}

		wp_send_json_success( $response );
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
			$response['old_value'] = wpsc_get_customer_meta( $meta_key );
			$response['type'] = __( 'success', 'wpsc' );
			$response['error'] = '';
			wpsc_delete_customer_meta( $meta_key );
		} else {
			$response['old_value'] = '';
			$response['type']  = __( 'error', 'wpsc' );
			$response['error'] = __( 'no meta key', 'wpsc' );
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'missing meta key', 'wpsc' ), '3.8.14' );
		}

		$response = _wpsc_add_customer_meta_to_response( $response );
		wp_send_json_success( $response );
	}

	/**
	 * Common routine to put the current customer meta values into an jax
	 * response in a format to be consumed by the wp-e-commerce.js ajax processings
	 *
	 * @since 3.8.14
	 * @access private
	 *
	 * @param array values being readied to send back to javascript in the json encoded AJAX response
	 * @param string|array|null meta keys to retrieve, if not specified all meta keys are retrieved
	 * @return JSON encoded array with results, results include original request parameters
	 */
	function _wpsc_add_customer_meta_to_response( $response, $meta_keys = null, $meta_key = 'customer_meta' ) {

		if ( ! empty( $meta_keys ) ) {
			if ( ! is_array( $meta_keys ) ) {
				$meta_keys = array( $meta_keys );
			}
		} else {
			$meta_keys = wpsc_checkout_unique_names();
		}

		$customer_meta = array();

		foreach ( $meta_keys as $a_meta_key ) {
			$customer_meta[$a_meta_key] = wpsc_get_customer_meta( $a_meta_key );
		}

		$response[$meta_key] = $customer_meta;
		$response = apply_filters( 'wpsc_ajax_response_customer_meta' , $response );

		return $response;
	}

	if ( _wpsc_doing_customer_meta_ajax() ) {
		add_action( 'wp_ajax_wpsc_validate_customer'        , 'wpsc_validate_customer_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_validate_customer'	, 'wpsc_validate_customer_ajax' );

		add_action( 'wp_ajax_wpsc_get_customer_meta'       	, 'wpsc_get_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_get_customer_meta'	, 'wpsc_get_customer_meta_ajax' );

		add_action( 'wp_ajax_wpsc_delete_customer_meta'       , 'wpsc_delete_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_delete_customer_meta', 'wpsc_delete_customer_meta_ajax' );

		add_action( 'wp_ajax_wpsc_update_customer_meta'       , 'wpsc_update_customer_meta_ajax' );
		add_action( 'wp_ajax_nopriv_wpsc_update_customer_meta', 'wpsc_update_customer_meta_ajax' );
	}
} // end if doing customer meta ajax

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

function _wpsc_customer_shipping_quotes_need_recalc( $meta_value, $meta_key, $customer_id ) {
	wpsc_cart_clear_shipping_info();
}

add_action( 'wpsc_updated_customer_meta_shippinggregion', '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
add_action( 'wpsc_updated_customer_meta_shippingcountry',  '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
add_action( 'wpsc_updated_customer_meta_shippingpostcode',  '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );
add_action( 'wpsc_updated_customer_meta_shippingstate', '_wpsc_customer_shipping_quotes_need_recalc' , 10 , 3 );

