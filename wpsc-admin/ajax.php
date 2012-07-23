<?php

/**
 * Verify nonce of an AJAX request
 *
 * @since  3.8.9
 * @access private
 * @param string $ajax_action Name of AJAX action
 * @return WP_Error|boolean True if nonce is valid. WP_Error if otherwise.
 */
function _wpsc_ajax_verify_nonce( $ajax_action ) {
	// nonce can be passed with name wpsc_nonce or _wpnonce
	$nonce = '';
	if ( isset( $_REQUEST['nonce'] ) )
		$nonce = $_REQUEST['nonce'];
	elseif ( isset( $_REQUEST['_wpnonce'] ) )
		$nonce = $_REQUEST['_wpnonce'];
	else
		return new WP_Error( 'wpsc_ajax_invalid_nonce', __( 'Your session has expired. Please refresh the page and try again.', 'wpsc' ) );

	// validate nonce
	if ( ! wp_verify_nonce( $nonce, 'wpsc_ajax_' . $ajax_action ) )
		return new WP_Error( 'wpsc_ajax_invalid_nonce', __( 'Your session has expired. Please refresh the page and try again.', 'wpsc' ) );

	return true;
}

/**
 * Verify AJAX callback and call it if it exists.
 *
 * @since  3.8.9
 * @access private
 * @param  string $ajax_action Name of AJAX action
 * @return WP_Error|array Array of response args if callback is valid. WP_Error if otherwise.
 */
function _wpsc_ajax_fire_callback( $ajax_action ) {
	// if callback exists, call it and output JSON response
	$callback = "_wpsc_ajax_{$ajax_action}";

	if ( is_callable( $callback ) )
		$result = call_user_func( $callback );
	else
		$result = new WP_Error( 'wpsc_invalid_ajax_callback', __( 'Invalid AJAX callback.', 'wpsc' ) );

	return $result;
}

/**
 * AJAX handler for all WPEC ajax requests.
 *
 * This function automates nonce checking and outputs JSON response.
 *
 * @since 3.8.9
 * @access private
 */
function _wpsc_ajax_handler() {
	$ajax_action = str_replace( '-', '_', $_REQUEST['wpsc_action'] );
	$result = _wpsc_ajax_verify_nonce( $ajax_action );

	if ( ! is_wp_error( $result ) )
		$result = _wpsc_ajax_fire_callback( $ajax_action );

	$output = array(
		'is_successful' => false,
	);

	if ( is_wp_error( $result ) ) {
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

/**
 * Add new variation set via AJAX.
 *
 * If the variation set name is the same as an existing variation set,
 * the children variant terms will be added inside that existing set.
 *
 * @since 3.8.8
 * @access private
 * @return array Response args
 */
function _wpsc_ajax_add_variation_set() {
	$new_variation_set = $_POST['variation_set'];
	$variants = preg_split( '/\s*,\s*/', $_POST['variants'] );

	$return = array();

	$parent_term_exists = term_exists( $new_variation_set, 'wpsc-variation' );

	// only use an existing parent ID if the term is not a child term
	if ( $parent_term_exists ) {
		$parent_term = get_term( $parent_term_exists['term_id'], 'wpsc-variation' );
		if ( $parent_term->parent == '0' )
			$variation_set_id = $parent_term_exists['term_id'];
	}

	if ( empty( $variation_set_id ) ) {
		$results = wp_insert_term( $new_variation_set, 'wpsc-variation' );
		if ( is_wp_error( $results ) )
			return $results;
		$variation_set_id = $results['term_id'];
	}

	if ( empty( $variation_set_id ) )
		return new WP_Error( 'wpsc_invalid_variation_id', __( 'Cannot retrieve the variation set in order to proceed.', 'wpsc' ) );

	foreach ( $variants as $variant ) {
		$results = wp_insert_term( $variant, 'wpsc-variation', array( 'parent' => $variation_set_id ) );

		if ( is_wp_error( $results ) )
			return $results;

		$inserted_variants[] = $results['term_id'];
	}

	require_once( 'includes/walker-variation-checklist.php' );

	/* --- DIRTY HACK START --- */
	/*
	There's a bug with term cache in WordPress core. See http://core.trac.wordpress.org/ticket/14485.
	The next 3 lines will delete children term cache for wpsc-variation.
	Without this hack, the new child variations won't be displayed on "Variations" page and
	also won't be displayed in wp_terms_checklist() call below.
	*/
	clean_term_cache( $variation_set_id, 'wpsc-variation' );
	delete_option('wpsc-variation_children');
	wp_cache_set( 'last_changed', 1, 'terms' );
	_get_term_hierarchy('wpsc-variation');
	/* --- DIRTY HACK END --- */

	ob_start();
	wp_terms_checklist( (int) $_POST['post_id'], array(
		'taxonomy'      => 'wpsc-variation',
		'descendants_and_self' => $variation_set_id,
		'walker'        => new WPSC_Walker_Variation_Checklist( $inserted_variants ),
		'checked_ontop' => false,
	) );
	$content = ob_get_clean();

	$return = array(
		'variation_set_id'  => $variation_set_id,
		'inserted_variants' => $inserted_variants,
		'content'           => $content,
	);

	return $return;
}

/**
 * Display gateway settings form via AJAX
 *
 * @since  3.8.9
 * @access private
 * @return array Response args
 */
function _wpsc_ajax_payment_gateway_settings_form() {
	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/gateway.php' );

	$return = array();
	ob_start();
	$tab = new WPSC_Settings_Tab_Gateway();
	$tab->display_payment_gateway_settings_form();
	$return['content'] = ob_get_clean();

	return $return;
}

/**
 * Display shipping module settings form via AJAX
 *
 * @since  3.8.9
 * @access private
 * @return array Response args
 */
function _wpsc_ajax_shipping_module_settings_form() {
	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/shipping.php' );

	$return = array();
	ob_start();
	$tab = new WPSC_Settings_Tab_Shipping();
	$tab->display_shipping_module_settings_form();
	$return['content'] = ob_get_clean();

	return $return;
}

/**
 * Display settings tab via AJAX
 *
 * @since 3.8.9
 * @access private
 * @return array Response args
 */
function _wpsc_ajax_navigate_settings_tab() {
	require_once( 'settings-page.php' );

	$return = array();
	ob_start();
	$settings_page = new WPSC_Settings_Page( $_POST['tab'] );
	$settings_page->display_current_tab();
	$return['content'] = ob_get_clean();

	return $return;
}
/**
 * Display base region list in Store Settings -> General
 *
 * @since 3.8.9
 * @access private
 * @return array Response args
 */
function _wpsc_ajax_display_region_list() {
	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/general.php' );

	$return = array();
	ob_start();
	$tab = new WPSC_Settings_Tab_General();
	$tab->display_region_drop_down();
	$return['content'] = ob_get_clean();

	return $return;
}
