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

		$response = $_REQUEST;

		if ( ! empty( $meta_key ) ) {
			$response['value'] = wpsc_get_customer_meta( $meta_key );
			$response['type'] = __( 'success', 'wpsc' );
			$response['error'] = '';
		} else {
			$response['value'] = '';
			$response['type']  = __( 'error', 'wpsc' );
			$response['error'] = __( 'no meta key', 'wpsc' );
			_wpsc_doing_it_wrong( __FUNCTION, __( 'missing meta key', 'wpsc' ), '3.8.14' );
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

		$response = $_REQUEST;

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
		$response = $_REQUEST;

		if ( ! empty( $meta_key ) ) {
			$success = wpsc_update_customer_meta( $meta_key, $meta_value  );
		} else {
			_wpsc_doing_it_wrong( __FUNCTION, __( 'missing meta key', 'wpsc' ), '3.8.14' );
		}

		$response['meta_key'] = $meta_key;

		if ( ! empty( $meta_key ) && $success ) {
			$response['meta_value']    = $meta_value;
			$response['type']          = __( 'success', 'wpsc' );
			$response['error']         = '';

			$response = apply_filters( 'customer_meta_response_' . $meta_key, $response, $meta_key, $meta_value );
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

		$response = $_REQUEST;

		if ( ! empty( $meta_key ) ) {
			$response['value'] = wpsc_get_customer_meta( $meta_key );
			$response['type'] = __( 'success', 'wpsc' );
			$response['error'] = '';
			wpsc_delete_customer_meta( $meta_key );
		} else {
			$response['value'] = '';
			$response['type']  = __( 'error', 'wpsc' );
			$response['error'] = __( 'no meta key', 'wpsc' );
			_wpsc_doing_it_wrong( __FUNCTION, __( 'missing meta key', 'wpsc' ), '3.8.14' );
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
		$response = $_REQUEST;

		if ( isset( $_POST['meta_keys'] ) && ! empty( $_POST['meta_keys']) ) {
			$meta_keys = $_POST['meta_keys'];

			foreach ( $meta_keys as $meta_key ) {
				$response[$meta_key] = wpsc_get_customer_meta( $meta_key );
			}
		}

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
}