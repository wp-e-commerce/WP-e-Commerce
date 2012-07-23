<?php

/**
 * AJAX handler for all WPEC ajax requests.
 *
 * This function automates nonce checking and outputs JSON response.
 *
 * @since 3.8.9
 * @access private
 */
function _wpsc_ajax_handler() {
	// sanitize ajax action
	$ajax_action = str_replace( '-', '_', $_REQUEST['wpsc_action'] );

	// nonce can be passed with name wpsc_nonce or _wpnonce
	$nonce = '';
	if ( isset( $_REQUEST['nonce'] ) )
		$nonce = $_REQUEST['nonce'];
	elseif ( isset( $_REQUEST['_wpnonce'] ) )
		$nonce = $_REQUEST['_wpnonce'];
	else
		die( '-1' );

	// validate nonce
	if ( ! wp_verify_nonce( $nonce, 'wpsc_ajax_' . $ajax_action ) )
		die( '-1' );

	// if callback exists, call it and output JSON response
	$callback = "_wpsc_ajax_{$ajax_action}";
	if ( is_callable( $callback ) ) {
		$result = call_user_func( $callback );
		$output = array();
		if ( is_wp_error( $result ) ) {
			$output['is_successful'] = false;
			$output['error'] = array(
				'code'     => $result->get_error_code(),
				'messages' => $result->get_error_messages(),
				'data'     => $result->get_error_data(),
			);
		} else {
			$output['is_successful'] = true;
			$output['obj'] = $result;
		}
		echo json_encode( $output );
		exit;
	}

	die ( '0' );
}
add_action( 'wp_ajax_wpsc_ajax', '_wpsc_ajax_handler' );

/**
 * Helper function that generates nonce for an AJAX action. Basically just a wrapper of
 * wp_create_nonce() but automatically add prefix.
 *
 * @since  3.8.9
 * @access private
 * @param  string $action AJAX action without prefix
 * @return string         The generated nonce.
 */
function _wpsc_create_ajax_nonce( $ajax_action ) {
	return wp_create_nonce( "wpsc_ajax_{$ajax_action}" );
}