<?php
function _wpsc_ajax_purchase_log_refund_items() {
	if ( ! isset( $_POST['order_id'] ) ) {
		return new WP_Error( 'wpsc_ajax_invalid_purchase_log_refund_items', __( 'Refund failed.', 'wp-e-commerce' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'wpsc_ajax_not_allowed_purchase_log_refund', __( 'Refund failed. (Incorrect Permissions)', 'wp-e-commerce' ) );
	}

	$order_id      = absint( $_POST['order_id'] );
	$refund_reason = isset( $_POST['refund_reason'] ) ? sanitize_text_field( $_POST['refund_reason'] ) : '';
	$refund_amount = isset( $_POST['refund_amount'] ) ? sanitize_text_field( $_POST['refund_amount'] ) : false;
	$manual        = $_POST['api_refund'] === 'true' ? false : true;
	$response_data = array();

	$log           = wpsc_get_order( $order_id );
	$gateway_id    = $log->get( 'gateway' );
	$gateway       = wpsc_get_payment_gateway( $gateway_id );

	try {
		// Validate that the refund can occur
		$refund_amount  = $refund_amount ? $refund_amount : $log->get( 'totalprice' );

		if ( wpsc_payment_gateway_supports( $gateway_id, 'refunds' ) ) {
			// Send api request to process refund. Returns Refund transaction ID
			$result = $gateway->process_refund( $log, $refund_amount, $refund_reason, $manual );

			do_action( 'wpsc_refund_processed', $log, $result, $refund_amount, $refund_reason );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( ! $result ) {
				throw new Exception( __( 'Refund failed', 'wp-e-commerce' ) );
			}
		}

		if ( $log->get_remaining_refund() > 0 && $refund_amount != $log->get( 'totalprice' ) ) {
			/**
			 * wpsc_order_partially_refunded.
			 *
			 * @since 3.11.5
			 */
			do_action( 'wpsc_order_partially_refunded', $log );
			$response_data['status'] = 'partially_refunded';

		} else {
			/**
			 * wpsc_order_fully_refunded.
			 *
			 * @since 3.11.5
			 */
			do_action( 'wpsc_order_fully_refunded', $log );
			$response_data['status'] = 'fully_refunded';
		}

		return $response_data;

	} catch ( Exception $e ) {
		return new WP_Error( 'wpsc_ajax_purchase_log_refund_failed', $e->getMessage() );
	}
}

function _wpsc_ajax_purchase_log_capture_payment() {
	if ( ! isset( $_POST['order_id'] ) ) {
		return new WP_Error( 'wpsc_ajax_invalid_purchase_log_capture_payment', __( 'Capture failed.', 'wp-e-commerce' ) );
	}

	if ( ! wpsc_is_store_admin() ) {
		return new WP_Error( 'wpsc_ajax_not_allowed_purchase_log_capture_payment', __( 'Capture failed. (Incorrect Permissions)', 'wp-e-commerce' ) );
	}

	$order_id      = absint( $_POST['order_id'] );
	$response_data = array();

	$log           = wpsc_get_order( $order_id );
	$gateway_id    = $log->get( 'gateway' );
	$gateway       = wpsc_get_payment_gateway( $gateway_id );

	try {

		// Validate that the capture can occur
		if ( wpsc_payment_gateway_supports( $gateway_id, 'auth-capture' ) ) {

			if ( ! $log->is_order_received() ) {
				throw new Exception( __( 'Order must be in "Order Received" status to be captured.', 'wp-e-commerce' ) );
			}

			$transaction_id = $log->get( 'transactid' );

			if ( empty( $transaction_id ) ) {
				throw new Exception( __( 'Order must have a transaction ID to be captured.', 'wp-e-commerce' ) );
			}

			// Send api request to process capture. Returns capture transaction ID
			$result = $gateway->capture_payment( $log, $transaction_id );

			do_action( 'wpsc_payment_captured', $log, $result );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( ! $result ) {
				throw new Exception( __( 'Capture failed', 'wp-e-commerce' ) );
			}
		}

		return $response_data;

	} catch ( Exception $e ) {
		return new WP_Error( 'wpsc_ajax_purchase_log_payment_capture_failed', $e->getMessage() );
	}
}

/**
 * Verify nonce of an AJAX request
 *
 * @since  3.8.9
 * @access private
 *
 * @uses WP_Error           WordPress Error Class
 * @uses wp_verify_nonce()    Verify that correct nonce was used with time limit.
 *
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
		return _wpsc_error_invalid_nonce();

	// validate nonce
	if ( ! wp_verify_nonce( $nonce, 'wpsc_ajax_' . $ajax_action ) )
		return _wpsc_error_invalid_nonce();

	return true;
}

function _wpsc_error_invalid_nonce() {
	return new WP_Error( 'wpsc_ajax_invalid_nonce', __( 'Your session has expired. Please refresh the page and try again.', 'wp-e-commerce' ) );
}

/**
 * Verify AJAX callback and call it if it exists.
 *
 * @since  3.8.9
 * @access private
 *
 * @uses WP_Error   WordPress Error object
 *
 * @param  string $ajax_action Name of AJAX action
 * @return WP_Error|array Array of response args if callback is valid. WP_Error if otherwise.
 */
function _wpsc_ajax_fire_callback( $ajax_action ) {
	// if callback exists, call it and output JSON response
	$callback = "_wpsc_ajax_{$ajax_action}";

	if ( is_callable( $callback ) )
		$result = call_user_func( $callback );
	else
		$result = new WP_Error( 'wpsc_invalid_ajax_callback', __( 'Invalid AJAX callback.', 'wp-e-commerce' ) );

	return $result;
}

/**
 * AJAX handler for all WPEC ajax requests.
 *
 * This function automates nonce checking and outputs JSON response.
 *
 * @since 3.8.9
 * @access private
 *
 * @uses _wpsc_ajax_fire_callback()     Verify ajax callback if it exists
 * @uses _wpsc_ajax_verify_nonce()      Verify nonce of an ajax request
 * @uses is_wp_error()                  Check whether variable is a WordPress Error.
 *
 * @return array $output    json encoded response
 */
function _wpsc_ajax_handler() {
	$ajax_action = str_replace( '-', '_', $_REQUEST['wpsc_action'] );

	if ( is_callable( '_wpsc_ajax_verify_' . $ajax_action ) ) {
		$result = call_user_func( '_wpsc_ajax_verify_' . $ajax_action );
	} else {
		$result = _wpsc_ajax_verify_nonce( $ajax_action );
	}

	if ( ! is_wp_error( $result ) ) {
		$result = _wpsc_ajax_fire_callback( $ajax_action );
	}

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
 * Checks if WPSC is doing ajax
 *
 * @param   string  $action     req     The action we're checking
 * @return  bool    True if doing ajax
 */
function wpsc_is_doing_ajax( $action = '' ) {
	$ajax = defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'wpsc_ajax';

	if ( $action ) {
		$ajax = $ajax && ! empty( $_REQUEST['wpsc_action'] ) && $action == str_replace( '-', '_', $_REQUEST['wpsc_action'] );
	}

	return $ajax;
}

/**
 * Helper function that generates nonce for an AJAX action. Basically just a wrapper of
 * wp_create_nonce() but automatically add prefix.
 *
 * @since  3.8.9
 * @access private
 *
 * @uses wp_create_nonce()  Creates a random one time use token
 *
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
 *
 * @uses term_exists()                      Returns true if term exists
 * @uses get_term()                         Gets all term data by term_id
 * @uses wp_insert_term()                   Inserts a term to the WordPress database
 * @uses is_wp_error()                      Checks whether variable is a WordPress error
 * @uses WP_Error                           WordPress Error class
 * @uses clean_term_cache()                 Will remove all of the term ids from the cache.
 * @uses delete_option()                    Deletes option from the database
 * @uses wp_cache_set()                     Saves the data to the cache.
 * @uses _get_term_hierarchy()              Retrieves children of taxonomy as Term IDs.
 * @uses wp_terms_checklist()               Output an unordered list of checkbox <input> elements labelled
 * @uses WPSC_Walker_Variation_Checklist    Walker variation checklist
 *
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
		$results = wp_insert_term( apply_filters( 'wpsc_new_variation_set', $new_variation_set ), 'wpsc-variation' );
		if ( is_wp_error( $results ) )
			return $results;
		$variation_set_id = $results['term_id'];
	}

	if ( empty( $variation_set_id ) )
		return new WP_Error( 'wpsc_invalid_variation_id', __( 'Cannot retrieve the variation set in order to proceed.', 'wp-e-commerce' ) );

	foreach ( $variants as $variant ) {
		$results = wp_insert_term( apply_filters( 'wpsc_new_variant', $variant, $variation_set_id ), 'wpsc-variation', array( 'parent' => $variation_set_id ) );

		if ( is_wp_error( $results ) )
			return $results;

		$inserted_variants[] = $results['term_id'];
	}

	require_once( 'includes/walker-variation-checklist.php' );

	if ( ! version_compare( $GLOBALS['wp_version'], '3.8.3', '>' ) ) {

		/* --- DIRTY HACK START --- */
		/*
		There's a bug with term cache in WordPress core. See http://core.trac.wordpress.org/ticket/14485. Fixed in 3.9.
		The next 3 lines will delete children term cache for wpsc-variation.
		Without this hack, the new child variations won't be displayed on "Variations" page and
		also won't be displayed in wp_terms_checklist() call below.
		*/
		clean_term_cache( $variation_set_id, 'wpsc-variation' );
		delete_option('wpsc-variation_children');
		wp_cache_set( 'last_changed', 1, 'terms' );
		_get_term_hierarchy('wpsc-variation');
		/* --- DIRTY HACK END --- */

	}

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
 *
 * @uses WPSC_Settings_Tab_Gateway
 * @uses WPSC_Settings_Tab_Gateway::display_payment_gateway_settings_form()     Displays payment gateway form
 *
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
 *
 * @uses WPSC_Settings_Table_Shipping
 * @uses WPSC_Settings_Table_Shipping::display_shipping_module_settings_form()  Displays shipping module form
 *
 * @return array $return    Response args
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
 *
 * @uses WPSC_Settings_Page
 * @uses WPSC_Settings_Page::display_current_tab()  Shows current tab of settings page
 *
 * @return array $return    Response args
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
 *
 * @uses WPSC_Settings_Tab_General
 * @uses WPSC_Settings_Tab_General::display_region_drop_down()  Shows region dropdown
 *
 * @return array    $return     Response args
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

/**
 * Save tracking ID of a sales log.
 *
 * @since 3.8.9
 * @access private
 *
 * @uses WP_Error   WordPress Error class
 *
 * @return array|WP_Error   $return     Response args if successful, WP_Error if otherwise.
 */
function _wpsc_ajax_purchase_log_save_tracking_id() {
	global $wpdb;

	$result = $wpdb->update(
		WPSC_TABLE_PURCHASE_LOGS,
		array(
			'track_id' => $_POST['value']
		),
		array(
			'id' => $_POST['log_id']
		),
		'%s',
		'%d'
	);

	if ( ! $result )
		return new WP_Error( 'wpsc_cannot_save_tracking_id', __( "Couldn't save tracking ID of the transaction. Please try again.", 'wp-e-commerce' ) );

	$return = array(
		'rows_affected' => $result,
		'id'            => $_POST['log_id'],
		'track_id'      => $_POST['value'],
	);

	return $return;
}

/**
 * Send sales log tracking email via AJAX
 *
 * @since 3.8.9
 * @access private
 *
 * @uses $wpdb              WordPress database object for queries
 * @uses get_option()       Gets option from DB given key
 * @uses add_filter()       Calls 'wp_mail_from' which can replace the from email address
 * @uses add_filter()       Calls 'wp_mail_from_name' allows replacement of the from name on WordPress emails
 * @uses wp_mail()          All the emailses in WordPress are sent through this function
 * @uses WP_Error           WordPress Error class
 *
 * @return array|WP_Error   $return     Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_purchase_log_send_tracking_email() {
	global $wpdb;

	$id = absint( $_POST['log_id'] );
	$sql = $wpdb->prepare( "SELECT `track_id` FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE `id`=%d LIMIT 1", $id );
	$trackingid = $wpdb->get_var( $sql );

	$message = get_option( 'wpsc_trackingid_message' );
	$message = str_replace( '%trackid%', $trackingid, $message );
	$message = str_replace( '%shop_name%', get_option( 'blogname' ), $message );

	$email = wpsc_get_buyers_email( $id );

	$subject = get_option( 'wpsc_trackingid_subject' );
	$subject = str_replace( '%shop_name%', get_option( 'blogname' ), $subject );

	add_filter( 'wp_mail_from', 'wpsc_replace_reply_address', 0 );
	add_filter( 'wp_mail_from_name', 'wpsc_replace_reply_name', 0 );

	$result = wp_mail( $email, $subject, $message);

	if ( ! $result ) {
		return new WP_Error( 'wpsc_cannot_send_tracking_email', __( "Couldn't send tracking email. Please try again.", 'wp-e-commerce' ) );
	}

	$return = array(
		'id'          => $id,
		'tracking_id' => $trackingid,
		'subject'     => $subject,
		'message'     => $message,
		'email'       => $email
	);

	return $return;
}

/**
 * Do purchase log action link via AJAX
 *
 * @since   3.9.0
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_purchase_log_action_link() {

	if ( isset( $_POST['log_id'] ) && isset( $_POST['purchase_log_action_link'] ) && isset( $_POST['purchase_log_action_nonce'] ) ) {

		$log_id = absint( $_POST['log_id'] );
		$purchase_log_action_link = sanitize_key( $_POST['purchase_log_action_link'] );

		// Verify action nonce
		if ( wp_verify_nonce( $_POST['purchase_log_action_nonce'], 'wpsc_purchase_log_action_ajax_' . $purchase_log_action_link ) ) {

			// Expected to receive success = true by default, or false on error.
			$return = apply_filters( 'wpsc_purchase_log_action_ajax-' . $purchase_log_action_link, array( 'success' => null ), $log_id );

		} else {
			$return = _wpsc_error_invalid_nonce();
		}

		if ( ! is_wp_error( $return ) ) {
			$return['log_id'] = $log_id;
			$return['purchase_log_action_link'] = $purchase_log_action_link;
			$return['success'] = isset( $return['success'] ) ? (bool) $return['success'] : null;
		}

		return $return;

	}

	return new WP_Error( 'wpsc_ajax_invalid_purchase_log_action', __( 'Purchase log action failed.', 'wp-e-commerce' ) );

}

/**
 * Remove purchase log item.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_remove_log_item() {

	if ( isset( $_POST['item_id'], $_POST['log_id'] ) ) {

		$item_id = absint( $_POST['item_id'] );
		$log_id  = absint( $_POST['log_id'] );
		$log     = wpsc_get_order( $log_id );

		if ( $log->remove_item( $item_id ) ) {
			return _wpsc_init_log_items( $log );
		}
	}

	return new WP_Error( 'wpsc_ajax_invalid_remove_log_item', __( 'Removing log item failed.', 'wp-e-commerce' ) );
}

/**
 * Update purchase log item quantity.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_update_log_item_qty() {

	if ( isset( $_POST['item_id'], $_POST['log_id'], $_POST['qty'] ) ) {

		if ( empty( $_POST['qty'] ) ) {
			return _wpsc_ajax_remove_log_item();
		}

		$item_id = absint( $_POST['item_id'] );
		$log_id  = absint( $_POST['log_id'] );
		$log     = wpsc_get_order( $log_id );
		$result  = $log->update_item( $item_id, array( 'quantity' => absint( $_POST['qty'] ) ) );

		if ( 0 === $result ) {
			return true;
		} elseif ( false !== $result ) {
			return _wpsc_init_log_items( $log );
		}
	}

	return new WP_Error( 'wpsc_ajax_invalid_update_log_item_qty', __( 'Updating log item quantity failed.', 'wp-e-commerce' ) );
}

/**
 * Add purchase log item.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_add_log_item() {
	global $wpsc_cart;

	if (
		isset( $_POST['product_ids'], $_POST['log_id'] )
		&& is_array( $_POST['product_ids'] )
		&& ! empty( $_POST['product_ids'] )
	) {

		$existing = isset( $_POST['existing'] ) && is_array( $_POST['existing'] )
			? array_map( 'absint', $_POST['existing'] )
			: false;

		$item_ids = array();
		$log      = null;

		foreach ( $_POST['product_ids'] as $product_id ) {
			$product_id = absint( $product_id );
			$log_id     = absint( $_POST['log_id'] );
			$log        = wpsc_get_order( $log_id );

			// Is product is already in item list?
			if ( $existing && in_array( $product_id, $existing, true ) ) {
				$item = $log->get_item_from_product_id( $product_id );
				if ( $item ) {
					// Update item quantity...
					$log->update_item( $item->id, array( 'quantity' => ++$item->quantity ) );
					// And move on.
					continue;
				}
			}

			$item       = new wpsc_cart_item( $product_id, array(), $wpsc_cart );
			$item_id    = $item->save_to_db( $log_id );
			$item_ids[] = absint( $item_id );
		}

		return _wpsc_init_log_items( $log, $item_ids );
	}

	return new WP_Error( 'wpsc_ajax_invalid_add_log_item', __( 'Adding log item failed.', 'wp-e-commerce' ) );
}

function _wpsc_init_log_items( WPSC_Purchase_Log $log, $item_ids = array() ) {
	$log->init_items();

	require_once( WPSC_FILE_PATH . '/wpsc-admin/display-sales-logs.php' );

	$html = '';
	$htmls = array();
	$htmls[] = array();

	while ( wpsc_have_purchaselog_details() ) {
		wpsc_the_purchaselog_item();

		ob_start();
		WPSC_Purchase_Log_Page::purchase_log_cart_item( $log->can_edit() );
		$cart_item = ob_get_clean();

		$htmls[ wpsc_purchaselog_details_id() ] = $cart_item;
		if ( ! empty( $item_ids ) && in_array( absint( wpsc_purchaselog_details_id() ), $item_ids, true ) ) {
			$html .= $cart_item;
		}
	}

	return array(
		'quantities'     => wp_list_pluck( $log->get_items(), 'quantity', 'id' ),
		'html'           => $html,
		'htmls'          => $htmls,
		'discount_data'  => wpsc_purchlog_has_discount_data() ? esc_html__( 'Coupon Code', 'wp-e-commerce' ) . ': ' . wpsc_display_purchlog_discount_data() : '',
		'discount'       => wpsc_display_purchlog_discount(),
		'total_taxes'    => wpsc_display_purchlog_taxes(),
		'total_shipping' => wpsc_display_purchlog_shipping( false, true ),
		'final_total'    => wpsc_display_purchlog_totalprice(),
	);
}

/**
 * Edit log contact details.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_edit_contact_details() {

	if ( isset( $_POST['log_id'], $_POST['fields'] ) && ! empty( $_POST['fields'] ) ) {

		// Parse the URL query string of the fields array.
		parse_str( $_POST['fields'], $fields );

		$log_id = absint( $_POST['log_id'] );
		$log    = wpsc_get_order( $log_id );

		if ( isset( $fields['wpsc_checkout_details'] ) && is_array( $fields['wpsc_checkout_details'] ) ) {
			$details = wp_unslash( $fields['wpsc_checkout_details'] );

			// Save the new/updated contact details.
			WPSC_Checkout_Form_Data::save_form(
				$log,
				WPSC_Checkout_Form::get()->get_fields(),
				array_map( 'sanitize_text_field', $details ),
				false
			);

			require_once( WPSC_FILE_PATH . '/wpsc-admin/display-sales-logs.php' );

			$log->init_items();

			// Fetch the shipping/billing formatted output.

			ob_start();
			WPSC_Purchase_Log_Page::shipping_address_output();
			$shipping = ob_get_clean();

			ob_start();
			WPSC_Purchase_Log_Page::billing_address_output();
			$billing = ob_get_clean();

			ob_start();
			WPSC_Purchase_Log_Page::payment_details_output();
			$payment = ob_get_clean();

			return compact( 'shipping', 'billing', 'payment' );

		}

	}

	return new WP_Error( 'wpsc_ajax_invalid_edit_contact_details', __( 'Failed to update contact details for log.', 'wp-e-commerce' ) );
}

/**
 * Add a note to a log.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_add_note() {

	if ( isset( $_POST['log_id'], $_POST['note'] ) && ! empty( $_POST['note'] ) ) {

		$result = wpsc_purchlogs_update_notes(
			absint( $_POST['log_id'] ),
			wp_kses_post( wp_unslash( $_POST['note'] ) )
		);

		if ( $result instanceof WPSC_Purchase_Log_Notes ) {
			require_once( WPSC_FILE_PATH . '/wpsc-admin/display-sales-logs.php' );

			$data      = $result->get_data();
			$keys      = array_keys( $data );
			$note_id   = end( $keys );
			$note_args = end( $data );

			ob_start();
			WPSC_Purchase_Log_Page::note_output( $result, $note_id, $note_args );
			$row = ob_get_clean();

			return $row;
		}
	}

	return new WP_Error( 'wpsc_ajax_invalid_add_note', __( 'Failed adding log note.', 'wp-e-commerce' ) );
}

/**
 * Delete a note from a log.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_delete_note() {

	if ( isset( $_POST['log_id'], $_POST['note'] ) && is_numeric( $_POST['note'] ) ) {

		$notes = wpsc_get_order_notes( absint( $_POST['log_id'] ) );
		$notes->remove( absint( $_POST['note'] ) )->save();

		return true;
	}

	return new WP_Error( 'wpsc_ajax_invalid_delete_note', __( 'Failed to delete log note.', 'wp-e-commerce' ) );
}

/**
 * Search for products.
 *
 * @since   3.11.5
 * @access  private
 *
 * @return  array|WP_Error  $return  Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_search_products() {
	$pt_object = get_post_type_object( 'wpsc-product' );

	$s = wp_unslash( $_POST['search'] );
	$args = array(
		'post_type' => 'wpsc-product',
		'post_status' => array( 'publish', 'inherit' ),
		'posts_per_page' => 50,
	);
	if ( '' !== $s ) {
		$args['s'] = $s;
	}

	$posts = get_posts( $args );

	if ( ! $posts ) {
		return new WP_Error( 'wpsc_ajax_invalid_search_products', __( 'No items found.', 'wp-e-commerce' ) );
	}

	$alt = '';
	foreach ( $posts as $post ) {
		$post->title = trim( $post->post_title ) ? $post->post_title : __( '(no title)' );
		$alt = ( 'alternate' === $alt ) ? '' : 'alternate';

		$post->status = $post->post_status;

		switch ( $post->post_status ) {
			case 'publish' :
			case 'private' :
				$post->status = __( 'Published' );
				break;
			case 'future' :
				$post->status = __( 'Scheduled' );
				break;
			case 'pending' :
				$post->status = __( 'Pending Review' );
				break;
			case 'draft' :
				$post->status = __( 'Draft' );
				break;
			default :
				$post->status = $post->post_status;
				break;
		}

		if ( '0000-00-00 00:00:00' === $post->post_date ) {
			$post->time = '';
		} else {
			/* translators: date format in table columns, see https://secure.php.net/date */
			$post->time = mysql2date( __( 'Y/m/d' ), $post->post_date );
		}

		$post->class = $alt;
	}

	return $posts;
}

/**
 * Handle AJAX clear downloads lock purchase log action
 *
 * The _wpsc_ajax_purchase_log_action_link() function which triggers this function is nonce
 * and capability checked in _wpsc_ajax_handler().
 *
 * @since   3.9.0
 * @access  private
 *
 * @param  array  $response  AJAX response.
 * @param  int    $log_id    Purchase log ID.
 */
function wpsc_purchase_log_action_ajax_downloads_lock( $response, $log_id ) {

	$response['success'] = wpsc_purchlog_clear_download_items( $log_id );

	return $response;

}
add_action( 'wpsc_purchase_log_action_ajax-downloads_lock', 'wpsc_purchase_log_action_ajax_downloads_lock', 10, 2 );


/**
 * Handle AJAX email receipt purchase log action
 *
 * The _wpsc_ajax_purchase_log_action_link() function which triggers this function is nonce
 * and capability checked in _wpsc_ajax_handler().
 *
 * @since   3.9.0
 * @access  private
 *
 * @param  array  $response  AJAX response.
 * @param  int    $log_id    Purchase log ID.
 */
function wpsc_purchase_log_action_ajax_email_receipt( $response, $log_id ) {

	$response['success'] = wpsc_purchlog_resend_email( $log_id );

	return $response;

}
add_action( 'wpsc_purchase_log_action_ajax-email_receipt', 'wpsc_purchase_log_action_ajax_email_receipt', 10, 2 );

/**
 * Delete an attached downloadable file via AJAX.
 *
 * @since 3.8.9
 * @access private
 *
 * @uses _wpsc_delete_file()    Deletes files associated with a product
 * @uses WP_Error               WordPress error class
 *
 * @return WP_Error|array  $return     Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_delete_file() {
	$product_id = absint( $_REQUEST['product_id'] );
	$file_name = basename( $_REQUEST['file_name'] );

	$result = _wpsc_delete_file( $product_id, $file_name );

	if ( ! $result )
		return new WP_Error( 'wpsc_cannot_delete_file', __( "Couldn't delete the file. Please try again.", 'wp-e-commerce' ) );

	$return = array(
		'product_id' => $product_id,
		'file_name'  => $file_name,
	);

	return $return;
}

/**
 * Delete a product meta via AJAX
 *
 * @since 3.8.9
 * @access private
 *
 * @uses delete_meta()      Deletes metadata by meta id
 * @uses WP_Error           WordPress error class
 *
 * @return  WP_Error|array  $return     Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_remove_product_meta() {
	$meta_id = (int) $_POST['meta_id'];
	if ( ! delete_meta( $meta_id ) )
		return new WP_Error( 'wpsc_cannot_delete_product_meta', __( "Couldn't delete product meta. Please try again.", 'wp-e-commerce' ) );

	return array( 'meta_id' => $meta_id );
}

/**
 * Modify a purchase log's status.
 *
 * @since 3.8.9
 * @access private
 *
 * @uses wpsc_purchlog_edit_status()                    Edits purchase log status
 * @uses WP_Error                                       WordPress Error class
 * @uses WPSC_Purchase_Log_List_Table
 * @uses WPSC_Purchase_Log_List_Table::prepare_items()
 * @uses WPSC_Purchase_Log_List_Table::views()
 * @uses WPSC_Purchase_Log_List_Table::display_tablenav()   @todo docs
 *
 * @return WP_Error|array   $return     Response args if successful, WP_Error if otherwise.
 */
function _wpsc_ajax_change_purchase_log_status() {
	$result = wpsc_purchlog_edit_status( $_POST['id'], $_POST['new_status'] );
	if ( ! $result )
		return new WP_Error( 'wpsc_cannot_edit_purchase_log_status', __( "Couldn't modify purchase log's status. Please try again.", 'wp-e-commerce' ) );

	$args = array();

	$args['screen'] = 'dashboard_page_wpsc-sales-logs';

	require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/purchase-log-list-table-class.php' );
	$purchaselog_table = new WPSC_Purchase_Log_List_Table( $args );
	$purchaselog_table->prepare_items();

	ob_start();
	$purchaselog_table->views();
	$views = ob_get_clean();

	ob_start();
	$purchaselog_table->display_tablenav( 'top' );
	$tablenav_top = ob_get_clean();

	ob_start();
	$purchaselog_table->display_tablenav( 'bottom' );
	$tablenav_bottom = ob_get_clean();

	$return = array(
		'id'              => $_POST['id'],
		'new_status'      => $_POST['new_status'],
		'views'           => $views,
		'tablenav_top'    => $tablenav_top,
		'tablenav_bottom' => $tablenav_bottom,
	);

	return $return;
}

/**
 * Save product ordering after drag-and-drop sorting
 *
 * @since 3.8.9
 * @access private
 *
 * @uses $wpdb              WordPress database object for use in queries
 * @uses wp_update_post()   Updates post based on passed $args. Needs a post_id
 * @uses WP_Error           WordPress Error class
 *
 * @return WP_Error|array Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_save_product_order() {

	$products = array( );
	foreach ( $_POST['post'] as $product ) {
		$products[] = (int) str_replace( 'post-', '', $product );
	}

	$failed = array();
	foreach ( $products as $order => $product_id ) {
		$result = wp_update_post( array(
			'ID' => $product_id,
			'menu_order' => $order,
		) );

		if ( ! $result )
			$failed[] = $product_id;
	}

	// Validate data before exposing to action
	$category = isset( $_POST['category_id'] ) ? get_term_by( 'slug', $_POST['category_id'], 'wpsc_product_category' ) : false;
	do_action( 'wpsc_save_product_order', $products, $category );

	if ( ! empty( $failed ) ) {
		$error_data = array(
			'failed_ids' => $failed,
		);

		return new WP_Error( 'wpsc_cannot_save_product_sort_order', __( "Couldn't save the products' sort order. Please try again.", 'wp-e-commerce' ), $error_data );
	}

	return array(
		'ids' => $products,
	);
}

/**
 * Save Category Product Order
 *
 * Note that this uses the 'term_order' field in the 'term_relationships' table to store
 * the order. Although this column presently seems to be unused by WordPress, the intention
 * is it should be used to store the order of terms associates to a post, not the order
 * of posts as we are doing. This shouldn't be an issue for WPEC unless WordPress adds a UI
 * for this. More info at http://core.trac.wordpress.org/ticket/9547
 *
 * @since 3.9
 * @access private
 *
 * @uses $wpdb   WordPress database object used for queries
 */
function _wpsc_save_category_product_order( $products, $category ) {
	global $wpdb;

	// Only save category product order if in category
	if ( ! $category )
		return;

	// Save product order in term_relationships table
	foreach ( $products as $order => $product_id ) {
		$wpdb->update( $wpdb->term_relationships,
			array( 'term_order' => $order ),
			array( 'object_id' => $product_id, 'term_taxonomy_id' => $category->term_taxonomy_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}
}
add_action( 'wpsc_save_product_order', '_wpsc_save_category_product_order', 10, 2 );

/**
 * Update Checkout fields order
 *
 * @since 3.8.9
 * @access private
 *
 * @uses $wpdb      WordPress database object used for queries
 * @uses WP_Error   WordPress error class
 *
 * @return array|WP_Error Response args or WP_Error
 */
function _wpsc_ajax_update_checkout_fields_order() {
	global $wpdb;

	$checkout_fields = $_REQUEST['sort_order'];
	$order = 1;
	$failed = array();
	$modified = array();
	foreach ( $checkout_fields as &$checkout_field ) {
		// ignore new fields
		if ( strpos( $checkout_field, 'new-field' ) === 0 )
			continue;
		$checkout_field = absint( preg_replace('/[^0-9]+/', '', $checkout_field ) );
		$result = $wpdb->update(
			WPSC_TABLE_CHECKOUT_FORMS,
			array(
				'checkout_order' => $order
			),
			array(
				'id' => $checkout_field
			),
			'%d',
			'%d'
		);
		$order ++;
		if ( $result === false )
			$failed[] = $checkout_field;
		elseif ( $result > 0 )
			$modified[] = $checkout_field;
	}

	if ( ! empty( $failed ) )
		return new WP_Error( 'wpsc_cannot_save_checkout_field_sort_order', __( "Couldn't save checkout field sort order. Please try again.", 'wp-e-commerce' ), array( 'failed_ids' => $failed ) );

	return array(
		'modified' => $modified,
	);
}

/**
 * Save a downloadable file to a product
 *
 * @since 3.8.9
 * @access private
 *
 * @uses $wpdb                          WordPress database object for use in queries
 * @uses _wpsc_create_ajax_nonce()      Creates nonce for an ajax action
 * @uses wpsc_get_mimetype()            Returns mimetype of file
 * @uses wp_insert_post()               Inserts post to WordPress database
 * @uses wp_nonce_url()                 Retrieve URL with nonce added to URL query.
 * @uses wpsc_convert_bytes()           Formats bytes
 * @uses wpsc_get_extension()           Gets extension of file
 * @uses esc_attr()                     Escapes HTML attributes
 * @uses _x()                           Retrieve translated string with gettext context
 *
 * @return array|WP_Error Response args if successful, WP_Error if otherwise.
 */
function _wpsc_ajax_upload_product_file() {
	global $wpdb;
	$product_id = absint( $_POST["product_id"] );
	$output = '';
	$delete_nonce = _wpsc_create_ajax_nonce( 'delete_file' );

	foreach ( $_POST["select_product_file"] as $selected_file ) {
		// if we already use this file, there is no point doing anything more.
		$sql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = 'wpsc-product-file' AND post_title = %s", $selected_file ); // TODO it's safer to select by post ID, in that case we will use get_posts()
		$file_post_data = $wpdb->get_row( $sql, ARRAY_A );
		$selected_file_path = WPSC_FILE_DIR . basename( $selected_file );
		$file_url = WPSC_FILE_URL . basename( $selected_file );
		$file_size = filesize( $selected_file_path );
		if ( empty( $file_post_data ) ) {
			$type = wpsc_get_mimetype( $selected_file_path );
			$attachment = array(
				'post_mime_type' => $type,
				'post_parent' => $product_id,
				'post_title' => $selected_file,
				'post_content' => '',
				'post_type' => "wpsc-product-file",
				'post_status' => 'inherit'
			);
			$id = wp_insert_post( $attachment );
		} else {
			// already attached
			if ( $file_post_data['post_parent'] == $product_id )
				continue;
			$type = $file_post_data["post_mime_type"];
			$url = $file_post_data["guid"];
			$title = $file_post_data["post_title"];
			$content = $file_post_data["post_content"];
			// Construct the attachment
			$attachment = array(
				'post_mime_type' => $type,
				'guid' => $url,
				'post_parent' => absint( $product_id ),
				'post_title' => $title,
				'post_content' => $content,
				'post_type' => "wpsc-product-file",
				'post_status' => 'inherit'
			);
			// Save the data
			$id = wp_insert_post( $attachment );
		}

		$deletion_url = wp_nonce_url( "admin.php?wpsc_admin_action=delete_file&amp;file_name={$attachment['post_title']}&amp;product_id={$product_id}", 'delete_file_' . $attachment['post_title'] );

		$output .= '<tr class="wpsc_product_download_row">';
		$output .= '<td style="padding-right: 30px;">' . $attachment['post_title'] . '</td>';
		$output .= '<td>' . wpsc_convert_byte( $file_size ) . '</td>';
		$output .= '<td>.' . wpsc_get_extension( $attachment['post_title'] ) . '</td>';
		$output .= "<td><a data-file-name='" . esc_attr( $attachment['post_title'] ) . "' data-product-id='" . esc_attr( $product_id ) . "' data-nonce='" . esc_attr( $delete_nonce ) . "' class='file_delete_button' href='{$deletion_url}' >" . _x( 'Delete', 'Digital Download UI row', 'wp-e-commerce' ) . "</a></td>";
		$output .= '<td><a href=' .$file_url .'>' . _x( 'Download', 'Digital Download UI row', 'wp-e-commerce' ) . '</a></td>';
		$output .= '</tr>';
	}

	return array(
		'content' => $output,
	);
}

/**
 * Generate variations
 *
 * @since 3.8.9
 * @access private
 *
 * @uses wpsc_update_variations()       Updates product variations given
 * @uses wpsc_admin_product_listing()   DEPRECATED
 *
 * @return array|WP_Error Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_update_variations() {
	$product_id = absint( $_REQUEST["product_id"] );
	wpsc_update_variations();

	ob_start();
	wpsc_admin_product_listing( $product_id );
	$content = ob_get_clean();

	return array( 'content' => $content );
}

/**
 * Display the shortcode generator.
 *
 * @since  3.8.9
 * @access private
 */
function _wpsc_action_tinymce_window() {
	require_once( WPSC_CORE_JS_PATH . '/tinymce3/window.php' );
	exit;
}
add_action( 'wp_ajax_wpsc_tinymce_window', '_wpsc_action_tinymce_window' );

/**
 * Add tax rate
 * @since  3.8.9
 * @access private
 *
 * @uses wpec_taxes_controller                                                  Contains all the logic to communicate with the taxes system
 * @uses wpec_taxes_controller::wpec_taxes::wpec_taxes_get_regions()            Gets tax regions based on input country code
 * @uses wpec_taxes_controller::wpec_taxes_build_select_options()               Returns HTML formatted options from input array
 * @uses wpec_taxes_controller::wpec_taxes_build_form()                         Builds the tax rate form
 * @uses wpec_taxes_controller::wpec_taxes::wpec_taxes_get_band_from_index()    Retrieves tax band for given name
 *
 * @return array|WP_Error Response args if successful, WP_Error if otherwise
 */
function _wpsc_ajax_add_tax_rate() {
	//include taxes controller
	$wpec_taxes_controller = new wpec_taxes_controller;

	switch ( $_REQUEST['wpec_taxes_action'] ) {
		case 'wpec_taxes_get_regions':
			$regions = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_regions( $_REQUEST['country_code'] );
			$key = $_REQUEST['current_key'];
			$type = $_REQUEST['taxes_type'];
			$default_option = array( 'region_code' => 'all-markets', 'name' => 'All Markets' );
			$select_settings = array(
				'id' => "{$type}-region-{$key}",
				'name' => "wpsc_options[wpec_taxes_{$type}][{$key}][region_code]",
				'class' => 'wpsc-taxes-region-drop-down'
			);
			$returnable = $wpec_taxes_controller->wpec_taxes_build_select_options( $regions, 'region_code', 'name', $default_option, $select_settings );
			break;
	}// switch

	return array(
		'content' => $returnable,
	);
}

/**
 * Displays the WPSC product variations table
 *
 * @uses check_admin_referrer()                     Makes sure user was referred from another admin page
 * @uses WPSC_Product_Variations_Page               The WPSC Product variations class
 * @uses WPSC_Product_Variations_Page::display()    Displays the product variations page
 */
function wpsc_product_variations_table() {
	check_admin_referer( 'wpsc_product_variations_table' );
	set_current_screen( 'wpsc-product' );
	require_once( WPSC_FILE_PATH . '/wpsc-admin/includes/product-variations-page.class.php' );
	$page = new WPSC_Product_Variations_Page();
	$page->display();

	exit;
}
add_action( 'wp_ajax_wpsc_product_variations_table', 'wpsc_product_variations_table' );

/**
 * @access private
 *
 * @uses current_user_can()             Checks user capabilities given string
 * @uses delete_post_thumbnail()        Deletes post thumbnail given thumbnail id
 * @uses set_post_thumbnail()           Sets post thumbnail given post_id and thumbnail_id
 * @uses wpsc_the_product_thumbnail()   Returns URL to the product thumbnail
 *
 * @return array    $response           Includes the thumbnail URL and success bool value
 */
function _wpsc_ajax_set_variation_product_thumbnail() {
	$response = array(
		'success' => false
	);

	$post_ID = intval( $_POST['post_id'] );
	if ( current_user_can( 'edit_post', $post_ID ) ) {
		$thumbnail_id = intval( $_POST['thumbnail_id'] );

		if ( $thumbnail_id == '-1' )
			delete_post_thumbnail( $post_ID );

		set_post_thumbnail( $post_ID, $thumbnail_id );

		$thumbnail = wpsc_the_product_thumbnail( 50, 50, $post_ID, '' );
		if ( ! $thumbnail )
			$thumbnail = WPSC_CORE_IMAGES_URL . '/no-image-uploaded.gif';
		$response['src'] = $thumbnail;
		$response['success'] = true;
	}

	echo json_encode( $response );
	exit;
}
add_action( 'wp_ajax_wpsc_set_variation_product_thumbnail', '_wpsc_ajax_set_variation_product_thumbnail' );

/**
 * Delete WPSC product image from gallery
 *
 * @uses check_ajax_referer()		Verifies the AJAX request to prevent processing external requests
 * @uses get_post_meta()		Returns meta from the specified post
 * @uses update_post_meta()		Updates meta from the specified post
 */
function product_gallery_image_delete_action() {

	$product_gallery = array();
	$gallery_image_id = $gallery_post_id = '';

	$gallery_image_id = absint($_POST['product_gallery_image_id']);
	$gallery_post_id = absint($_POST['product_gallery_post_id']);

	check_ajax_referer( 'wpsc_gallery_nonce', 'wpsc_gallery_nonce_check' );

	$product_gallery = get_post_meta( $gallery_post_id, '_wpsc_product_gallery', true );

	foreach ( $product_gallery as $index => $image_id ) {
		if ( $image_id == $gallery_image_id ) {
			unset( $product_gallery[$index] );
		}
	}

	update_post_meta( $gallery_post_id, '_wpsc_product_gallery', $product_gallery );
}
add_action( 'wp_ajax_product_gallery_image_delete', 'product_gallery_image_delete_action' );
