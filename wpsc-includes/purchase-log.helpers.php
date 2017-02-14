<?php

/**
 * Returns a purchase log.
 *
 * @since 3.11.5
 *
 * @param int $order_id Order ID.
 *
 * @return WPSC_Purchase_Log
 */
function wpsc_get_order( $order_id, $by = 'id' ) {
	$order = wpsc_is_order( $order_id );
	return $order ? $order : new WPSC_Purchase_Log( $order_id, $by );
}

/**
 * Determines if object is a an order (WPSC_Purchase_Log).
 *
 * @since 3.12.0
 *
 * @param mixed $order Object to check
 *
 * @return WPSC_Purchase_Log|bool
 */
function wpsc_is_order( $order ) {
	return $order instanceof WPSC_Purchase_Log ? $order : false;
}

/**
 * Returns a purchase log's notes object.
 *
 * @since 3.11.5
 *
 * @param int $order_id Order ID or WPSC_Purchase_Log object.
 *
 * @return WPSC_Purchase_Log
 */
function wpsc_get_order_notes( $order_id ) {
	return new WPSC_Purchase_Log_Notes( $order_id );
}

function wpsc_get_plaintext_table( $headings, $rows ) {
	$colwidths = array();
	$output    = array();
	$alignment = array_values( $headings );
	$headings  = array_keys( $headings );
	foreach ( $headings as $heading ) {
		$colwidths[] = strlen( $heading );
	}

	foreach ( $rows as $row ) {
		$i = 0;
		foreach ( $row as $col ) {
			$colwidths[$i] = max( strlen( $col ), $colwidths[$i] );
			$i ++;
		}
	}

	foreach ( $rows as &$row ) {
		$i = 0;
		foreach ( $row as &$col ) {
			$align = ( $alignment[$i] == 'left' ) ? STR_PAD_RIGHT : STR_PAD_LEFT;
			$col = str_pad( $col, $colwidths[$i], ' ', $align );
			$i ++;
		}
		$output[] = implode( '  ', $row );
	}

	$line = array();
	$i = 0;

	foreach ( $colwidths as $width ) {
		$line[] = str_repeat( '-', $width );
		$headings[$i] = str_pad( $headings[$i], $width );
		$i ++;
	}

	$line = implode( '--', $line );
	array_unshift( $output, $line );
	if ( ! empty( $headings ) ) {
		array_unshift( $output, implode( '  ', $headings ) );
		array_unshift( $output, $line );
	}
	$output[] = $line;

	return implode( "\r\n", $output ) . "\r\n";
}

function wpsc_update_purchase_log_status( $log_id, $new_status, $by = 'id' ) {
	$log = wpsc_get_order( $log_id, $by );

	$old_status = $log->get( 'processed' );
	$log->set( 'processed', $new_status );

	return $log->save();
}

function wpsc_update_purchase_log_details( $log_id, $details, $by = 'id' ) {
	$log = wpsc_get_order( $log_id, $by );
	$log->set( $details );

	return $log->save();
}

function wpsc_get_downloadable_links( $purchase_log ) {
	if ( ! $purchase_log->is_transaction_completed() ) {
		return array();
	}

	$cart_contents = $purchase_log->get_items();
	$links = array();
	foreach ( $cart_contents as $item ) {
		$item_links = _wpsc_get_cart_item_downloadable_links( $item, $purchase_log );
		if ( empty( $item_links ) ) {
			continue;
		}
		$links[$item->name] = $item_links;
	}

	return apply_filters( 'wpsc_get_downloadable_links', $links, $purchase_log );
}

function _wpsc_get_cart_item_downloadable_links( $item, $purchase_log ) {
	global $wpdb;
	$sql = $wpdb->prepare("
			SELECT *
			FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "`
			WHERE `active` = '1'
			AND `purchid` = %d
			AND `cartid` = %d
			", $purchase_log->get( 'id' ), $item->id
	);

	$results = $wpdb->get_results( $sql );
	$links = array();

	foreach ( $results as $single_download ) {
		$file_data = get_post( $single_download->product_id );
		$args = array(
			'post_type'   => 'wpsc-product-file',
			'post_parent' => $single_download->product_id,
			'numberposts' => -1,
			'post_status' => 'all',
		);
		$download_file_posts = (array) get_posts( $args );
		foreach( $download_file_posts as $single_file_post ) {
			if( $single_file_post->ID == $single_download->fileid ) {
				$current_Dl_product_file_post = $single_file_post;
				break;
			}
		}

		$file_name = $current_Dl_product_file_post->post_title;
		$downloadid = is_null( $single_download->uniqueid ) ? $single_download->id : $single_download->uniqueid;
		$links[] = array(
			'url' => add_query_arg( 'downloadid', $downloadid, home_url( '/' ) ),
			'name' => $file_name
		);
	}

	return $links;
}

function wpsc_get_purchase_log_html_table( $headings, $rows ) {
	ob_start();

	?>
	<table class="wpsc-purchase-log-transaction-results">
		<?php if ( ! empty( $headings ) ): ?>
			<thead>
				<?php foreach ( $headings as $heading => $align ): ?>
					<th><?php echo esc_html( $heading ); ?></th>
				<?php endforeach; ?>
			</thead>
		<?php endif; ?>
		<tbody>
			<?php foreach ( $rows as $row ): ?>
				<tr>
					<?php foreach ( $row as $col ): ?>
						<td><?php echo esc_html( $col ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	$output = ob_get_clean();
	$output = apply_filters( 'wpsc_get_purchase_log_html_table', $output, $headings, $rows );
	return $output;
}

function _wpsc_process_transaction_coupon( $purchase_log ) {
	global $wpdb;

	if ( ! is_object( $purchase_log ) )
		$purchase_log = wpsc_get_order( $purchase_log );

	$discount_data = $purchase_log->get( 'discount_data' );
	if ( ! empty( $discount_data ) ) {

		$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE coupon_code = %s LIMIT 1", $discount_data ), ARRAY_A );

		$coupon = new WPSC_Coupon( $coupon_data['id'] );
		$coupon->used();

	}
}

/**
 * Routine that runs when updating a purchase log's status.
 * Currently, only used to send customer and admin emails upon successful purchase.
 *
 * @since  3.8.9
 * @since  3.11.5 Removed coupons and stocks from email sending.  Much easier now to remove_action() on either
 *                of those functions when desiring to override.
 *
 * @param  int               $id             Purchase Log ID.
 * @param  int               $status         Current status.
 * @param  int               $old_status     Previous status.
 * @param  WPSC_Purchase_Log $purchase_log   Purchase Log Object.
 *
 * @return void
 */
function _wpsc_action_update_purchase_log_status( $id, $status, $old_status, $purchase_log ) {
	if ( $purchase_log->is_order_received() || $purchase_log->is_accepted_payment() ) {
		wpsc_send_customer_email( $purchase_log );
		wpsc_send_admin_email( $purchase_log );
	}
}

add_action( 'wpsc_update_purchase_log_status', '_wpsc_action_update_purchase_log_status', 10, 4 );

/**
 * Routine that runs when updating a purchase log's status, used to update status of coupon's used.
 *
 * @since  3.11.5
 *
 * @param  int               $id             Purchase Log ID.
 * @param  int               $status         Current status.
 * @param  int               $old_status     Previous status.
 * @param  WPSC_Purchase_Log $purchase_log   Purchase Log Object.
 *
 * @return void
 */
function _wpsc_update_purchase_log_coupon_status( $id, $status, $old_status, $purchase_log ) {

	if ( ! $purchase_log->is_transaction_completed() ) {
		return;
	}

	$already_processed = in_array(
		$old_status,
		array(
			WPSC_Purchase_Log::ACCEPTED_PAYMENT,
			WPSC_Purchase_Log::JOB_DISPATCHED,
			WPSC_Purchase_Log::CLOSED_ORDER,
		)
	);

	if ( $already_processed ) {
		return;
	}

	_wpsc_process_transaction_coupon( $purchase_log );
}

add_action( 'wpsc_update_purchase_log_status', '_wpsc_update_purchase_log_coupon_status', 11, 4 );

/**
 * Routine that runs when updating a purchase log's status, used to update status of inventory.
 *
 * @since  3.11.5
 *
 * @param  int               $id             Purchase Log ID.
 * @param  int               $status         Current status.
 * @param  int               $old_status     Previous status.
 * @param  WPSC_Purchase_Log $purchase_log   Purchase Log Object.
 *
 * @return void
 */

function _wpsc_update_purchase_log_stock_status( $id, $status, $old_status, $purchase_log ) {

	if ( ! $purchase_log->is_transaction_completed() ) {
		return;
	}

	$already_processed = in_array(
		$old_status,
		array(
			WPSC_Purchase_Log::ACCEPTED_PAYMENT,
			WPSC_Purchase_Log::JOB_DISPATCHED,
			WPSC_Purchase_Log::CLOSED_ORDER,
		)
	);

	if ( $already_processed ) {
		return;
	}

	wpsc_decrement_claimed_stock( $id );
}

add_action( 'wpsc_update_purchase_log_status', '_wpsc_update_purchase_log_stock_status', 12, 4 );

function wpsc_send_customer_email( $purchase_log ) {
	if ( ! is_object( $purchase_log ) ) {
		$purchase_log = wpsc_get_order( $purchase_log );
	}

	if ( ! $purchase_log->is_transaction_completed() && ! $purchase_log->is_order_received() ) {
		return;
	}

	$email = new WPSC_Purchase_Log_Customer_Notification( $purchase_log );
	$email_sent = $email->send();

	do_action( 'wpsc_transaction_send_email_to_customer', $email, $email_sent );
	return $email_sent;
}

function wpsc_send_admin_email( $purchase_log, $force = false ) {

	if ( ! is_object( $purchase_log ) ) {
		$purchase_log = wpsc_get_order( $purchase_log );
	}

	if ( $purchase_log->get( 'email_sent' ) && ! $force ) {
		return;
	}

	$email = new WPSC_Purchase_Log_Admin_Notification( $purchase_log );
	$email_sent = $email->send();

	if ( $email_sent ) {
		$purchase_log->set( 'email_sent', 1 )->save();
	}

	do_action( 'wpsc_transaction_send_email_to_admin', $email, $email_sent );

	return $email_sent;
}

function wpsc_get_transaction_html_output( $purchase_log ) {

	if ( ! is_object( $purchase_log ) ) {
		$purchase_log = wpsc_get_order( $purchase_log );
	}

	$notification = new WPSC_Purchase_Log_Customer_HTML_Notification( $purchase_log );
	$output = $notification->get_html_message();

	// see if the customer trying to view this transaction output is the person
	// who made the purchase.
	$checkout_session_id = wpsc_get_customer_meta( 'checkout_session_id' );

    if ( $checkout_session_id == $purchase_log->get( 'sessionid' ) ) {
    	$output = apply_filters( 'wpsc_get_transaction_html_output', $output, $notification );
	} else {
		$output = apply_filters( 'wpsc_get_transaction_unauthorized_view', __( "You don't have the permission to view this page", 'wp-e-commerce' ), $output, $notification );
	}

	return $output;
}

function _wpsc_update_log_total_with_item_update( $item_id, $purchase_log ) {
	$purchase_log->set( 'totalprice', $purchase_log->get_total() )->save();
}
add_action( 'wpsc_purchase_log_update_item', '_wpsc_update_log_total_with_item_update', 10, 2 );


function wpsc_update_order_status_partially_refunded( $log ) {
	if ( WPSC_Purchase_Log::PARTIALLY_REFUNDED !== $log->get( 'processed' ) ) {
		wpsc_update_purchase_log_status( $log, WPSC_Purchase_Log::PARTIALLY_REFUNDED );
	}
}

add_action( 'wpsc_order_partially_refunded', 'wpsc_update_order_status_partially_refunded' );

function wpsc_update_order_status_fully_refunded( $log ) {
	if ( WPSC_Purchase_Log::REFUNDED !== $log->get( 'processed' ) ) {
		wpsc_update_purchase_log_status( $log, WPSC_Purchase_Log::REFUNDED );
	}
}

add_action( 'wpsc_order_fully_refunded', 'wpsc_update_order_status_fully_refunded' );
