<?php

function wpsc_ajax_sales_quarterly() {
	$lastdate = $_POST['add_start'];
	$date = preg_split( '/-/', $lastdate );
	if ( !isset( $date[0] ) )
		$date[0] = 0;
	if ( !isset( $date[1] ) )
		$date[1] = 0;
	if ( !isset( $date[2] ) )
		$date[2] = 0;
	$lastquart = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );
	if ( $lastquart != get_option( 'wpsc_last_quarter' ) ) {
		update_option( 'wpsc_last_date', $lastdate );
		update_option( 'wpsc_fourth_quart', $lastquart );
		$thirdquart = mktime( 0, 0, 0, $date[1] - 3, $date[2], $date[0] );
		update_option( 'wpsc_third_quart', $thirdquart );
		$secondquart = mktime( 0, 0, 0, $date[1] - 6, $date[2], $date[0] );
		update_option( 'wpsc_second_quart', $secondquart );
		$firstquart = mktime( 0, 0, 0, $date[1] - 9, $date[2], $date[0] );
		update_option( 'wpsc_first_quart', $firstquart );
		$finalquart = mktime( 0, 0, 0, $date[1], $date[2], $date[0] - 1 );
		update_option( 'wpsc_final_quart', $finalquart );
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'wpsc_quarterly') )
	add_action( 'admin_init', 'wpsc_ajax_sales_quarterly' );

function wpsc_delete_file() {
	$product_id = absint( $_REQUEST['product_id'] );
	$file_name = basename( $_REQUEST['file_name'] );
	check_admin_referer( 'delete_file_' . $file_name );

	_wpsc_delete_file( $product_id, $file_name );

	$sendback = wp_get_referer();
	wp_redirect( $sendback );
	exit;
}


if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'delete_file') )
	add_action( 'admin_init', 'wpsc_delete_file' );

/**
 *  Function and action for publishing or unpublishing single products
 */
function wpsc_ajax_toggle_published() {
	$product_id = absint( $_GET['product'] );
	check_admin_referer( 'toggle_publish_' . $product_id );

	$status = (wpsc_toggle_publish_status( $product_id )) ? ('true') : ('false');
	$sendback = add_query_arg( 'flipped', "1", wp_get_referer() );
	wp_redirect( $sendback );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'toggle_publish') )
	add_action( 'admin_init', 'wpsc_ajax_toggle_published' );

/**
 * Function and action for duplicating products,
 * Refactored for 3.8
 * Purposely not duplicating stick post status (logically, products are most often duplicated because they share many attributes, where products are generally 'featured' uniquely.)
 */
function wpsc_duplicate_product() {

	// Get the original post
	$id = absint( $_GET['product'] );
	$post = get_post( $id );

	// Copy the post and insert it
	if ( isset( $post ) && $post != null ) {
		$new_id = wpsc_duplicate_product_process( $post );

		$duplicated = true;
		$sendback = wp_get_referer();
		$sendback = add_query_arg( 'duplicated', (int)$duplicated, $sendback );

		wp_redirect( $sendback );
		exit();
	} else {
		wp_die( __( 'Sorry, for some reason, we couldn\'t duplicate this product because it could not be found in the database, check there for this ID: ', 'wpsc' ) . $id );
	}
}

if ( isset( $_GET['wpsc_admin_action'] ) && ( $_GET['wpsc_admin_action'] == 'duplicate_product' ) )
    add_action( 'admin_init', 'wpsc_duplicate_product' );

function wpsc_purchase_log_csv() {
	global $wpdb, $wpsc_gateways;
	get_currentuserinfo();
	$count = 0;
	if ( 'key' == $_REQUEST['rss_key'] && current_user_can( 'manage_options' ) ) {
		if ( isset( $_REQUEST['start_timestamp'] ) && isset( $_REQUEST['end_timestamp'] ) ) {
			$start_timestamp = $_REQUEST['start_timestamp'];
			$end_timestamp = $_REQUEST['end_timestamp'];
			$start_end_sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date` BETWEEN '%d' AND '%d' ORDER BY `date` DESC";
			$start_end_sql = apply_filters( 'wpsc_purchase_log_start_end_csv', $start_end_sql );
			$data = $wpdb->get_results( $wpdb->prepare( $start_end_sql, $start_timestamp, $end_timestamp ), ARRAY_A );
			$csv_name = 'Purchase Log ' . date( "M-d-Y", $start_timestamp ) . ' to ' . date( "M-d-Y", $end_timestamp ) . '.csv';
		} elseif ( isset( $_REQUEST['m'] ) ) {
			$year = (int) substr( $_REQUEST['m'], 0, 4);
			$month = (int) substr( $_REQUEST['m'], -2 );
			$month_year_sql = "
				SELECT *
				FROM " . WPSC_TABLE_PURCHASE_LOGS . "
				WHERE YEAR(FROM_UNIXTIME(date)) = %d AND MONTH(FROM_UNIXTIME(date)) = %d
			";
			$month_year_sql = apply_filters( 'wpsc_purchase_log_month_year_csv', $month_year_sql );
			$data = $wpdb->get_results( $wpdb->prepare( $month_year_sql, $year, $month ), ARRAY_A );
			$csv_name = 'Purchase Log ' . $month . '/' . $year . '.csv';
		} else {
			$sql = apply_filters( 'wpsc_purchase_log_month_year_csv', "SELECT * FROM " . WPSC_TABLE_PURCHASE_LOGS );
			$data = $wpdb->get_results( $sql, ARRAY_A );
			$csv_name = "All Purchase Logs.csv";
		}

		$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1' AND `type` != 'heading' ORDER BY `checkout_order` DESC;";
		$form_data = $wpdb->get_results( $form_sql, ARRAY_A );
		$csv = 'Purchase ID, Price, Firstname, Lastname, Email, Order Status, Data, ';

		$headers = "\"Purchase ID\",\"Purchase Total\","; //capture the headers
		$headers2  ="\"Payment Gateway\",";
		$headers2 .="\"Payment Status\",\"Purchase Date\",";

		$output = '';

		foreach ( (array)$data as $purchase ) {
			$form_headers = '';
			$output .= "\"" . $purchase['id'] . "\","; //Purchase ID
			$output .= "\"" . $purchase['totalprice'] . "\","; //Purchase Total
			foreach ( (array)$form_data as $form_field ) {
				$form_headers .="\"".$form_field['unique_name']."\",";
				$collected_data_sql = "SELECT * FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` = '" . $purchase['id'] . "' AND `form_id` = '" . $form_field['id'] . "' LIMIT 1";
				$collected_data = $wpdb->get_results( $collected_data_sql, ARRAY_A );
				$collected_data = $collected_data[0];
				$output .= "\"" . $collected_data['value'] . "\","; // get form fields
			}

			if ( isset( $wpsc_gateways[$purchase['gateway']] ) && isset( $wpsc_gateways[$purchase['gateway']]['display_name'] ) )
				$output .= "\"" . $wpsc_gateways[$purchase['gateway']]['display_name'] . "\","; //get gateway name
			else
				$output .= "\"\",";


			$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );

			$output .= "\"" . $status_name . "\","; //get purchase status
			$output .= "\"" . date( "jS M Y", $purchase['date'] ) . "\","; //date

			$cartsql = "SELECT `prodid`, `quantity`, `name` FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=" . $purchase['id'] . "";
			$cart = $wpdb->get_results( $cartsql, ARRAY_A );

			if( $count < count( $cart ) )
			    $count = count( $cart );
			// Go through all products in cart and display quantity and sku
			foreach ( (array)$cart as $item ) {
				$skuvalue = get_product_meta( $item['prodid'], 'sku', true );
				if( empty( $skuvalue ) )
				    $skuvalue = __( 'N/A', 'wpsc' );
				$output .= "\"" . $item['quantity'] . "\",";
				$output .= "\"" . str_replace( '"', '\"', $item['name'] ) . "\"";
				$output .= "," . $skuvalue."," ;
			}
			$output .= "\n"; // terminates the row/line in the CSV file
		}
		// Get the most number of products and create a header for them
		$headers3 = "";
		for( $i = 0; $i < $count; $i++ ){
			$headers3 .= "\"Quantity\",\"Product Name\",\"SKU\"";
			if( $i < ( $count - 1 ) )
			    $headers3 .= ",";
		}

		$headers = apply_filters( 'wpsc_purchase_log_csv_headers', $headers . $form_headers . $headers2 . $headers3, $data, $form_data );
		$output = apply_filters( 'wpsc_purchase_log_csv_output', $output, $data, $form_data );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: inline; filename="' . $csv_name . '"' );
		echo $headers . "\n". $output;
		exit;
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'wpsc_downloadcsv') ) {
	add_action( 'admin_init', 'wpsc_purchase_log_csv' );
}

function wpsc_admin_sale_rss() {
	global $wpdb;
	if ( ($_GET['rss'] == "true") && ($_GET['rss_key'] == 'key') && ($_GET['action'] == "purchase_log") ) {
		$sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date`!='' ORDER BY `date` DESC";
		$purchase_log = $wpdb->get_results( $sql, ARRAY_A );
		header( "Content-Type: application/xml; charset=UTF-8" );
		header( 'Content-Disposition: inline; filename="WP_E-Commerce_Purchase_Log.rss"' );
		$output = '';
		$output .= "<?xml version='1.0'?>\n\r";
		$output .= "<rss version='2.0'>\n\r";
		$output .= "  <channel>\n\r";
		$output .= "    <title>WP e-Commerce Product Log</title>\n\r";
		$output .= "    <link>" . get_option( 'siteurl' ) . "/wp-admin/admin.php?page=" . WPSC_DIR_NAME . "/display-log.php</link>\n\r";
		$output .= "    <description>This is the WP e-Commerce Product Log RSS feed</description>\n\r";
		$output .= "    <generator>WP e-Commerce Plugin</generator>\n\r";

		foreach ( (array)$purchase_log as $purchase ) {
			$purchase_link = get_option( 'siteurl' ) . "/wp-admin/admin.php?page=" . WPSC_DIR_NAME . "/display-log.php&amp;purchaseid=" . $purchase['id'];
			$output .= "    <item>\n\r";
			$output .= "      <title>Purchase # " . $purchase['id'] . "</title>\n\r";
			$output .= "      <link>$purchase_link</link>\n\r";
			$output .= "      <description>This is an entry in the purchase log.</description>\n\r";
			$output .= "      <pubDate>" . date( "r", $purchase['date'] ) . "</pubDate>\n\r";
			$output .= "      <guid>$purchase_link</guid>\n\r";
			$output .= "    </item>\n\r";
		}
		$output .= "  </channel>\n\r";
		$output .= "</rss>";
		echo $output;
		exit();
	}
}

if ( isset( $_GET['action'] ) && ( 'purchase_log' == $_GET['action'] ) )
	add_action( 'admin_init', 'wpsc_admin_sale_rss' );

function wpsc_display_invoice() {
	$purchase_id = (int)$_REQUEST['purchaselog_id'];
	add_action('wpsc_packing_slip', 'wpsc_packing_slip');
	do_action('wpsc_before_packing_slip', $purchase_id);
	do_action('wpsc_packing_slip', $purchase_id);
	exit();
}
//other actions are here
if ( isset( $_GET['display_invoice'] ) && ( 'true' == $_GET['display_invoice'] ) )
	add_action( 'admin_init', 'wpsc_display_invoice', 0 );

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc_display_invoice' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_display_invoice' );

/**
 * Purchase log ajax code starts here
 */
function wpsc_purchlog_resend_email() {
	global $wpdb;
	$log_id = $_REQUEST['email_buyer_id'];
	$wpec_taxes_controller = new wpec_taxes_controller();
	if ( is_numeric( $log_id ) ) {
		$selectsql = "SELECT `sessionid` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id`= %d LIMIT 1";
		$purchase_log = $wpdb->get_var( $wpdb->prepare( $selectsql, $log_id ) );
		transaction_results( $purchase_log, false );
		$sent = true;
	}
	$sendback = wp_get_referer();
	if ( isset( $sent ) )
	    $sendback = add_query_arg( 'sent', $sent, $sendback );

	wp_redirect( $sendback );
	exit();
}

if ( isset( $_REQUEST['email_buyer_id'] ) && is_numeric( $_REQUEST['email_buyer_id'] ) ) {
	add_action( 'admin_init', 'wpsc_purchlog_resend_email' );
}

function wpsc_purchlog_clear_download_items() {
	global $wpdb;
	if ( is_numeric( $_GET['purchaselog_id'] ) ) {
		$purchase_id = (int)$_GET['purchaselog_id'];
		$downloadable_items = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `purchid` IN ('$purchase_id')", ARRAY_A );

		$clear_locks_sql = "UPDATE`" . WPSC_TABLE_DOWNLOAD_STATUS . "` SET `ip_number` = '' WHERE `purchid` IN ('$purchase_id')";
		$wpdb->query( $clear_locks_sql );
		$cleared = true;

		$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );
		$email_address = $wpdb->get_var( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id`='{$purchase_id}' AND `form_id` = '{$email_form_field}' LIMIT 1" );

		foreach ( (array)$downloadable_items as $downloadable_item ) {
			$download_links .= $siteurl . "?downloadid=" . $downloadable_item['uniqueid'] . "\n";
		}


		wp_mail( $email_address, __( 'The administrator has unlocked your file', 'wpsc' ), str_replace( "[download_links]", $download_links, __( 'Dear CustomerWe are pleased to advise you that your order has been updated and your downloads are now active.Please download your purchase using the links provided below.[download_links]Thank you for your custom.', 'wpsc' ) ), "From: " . get_option( 'return_email' ) . "" );


		$sendback = wp_get_referer();

		if ( isset( $cleared ) ) {
			$sendback = add_query_arg( 'cleared', $cleared, $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'clear_locks') ) {
	add_action( 'admin_init', 'wpsc_purchlog_clear_download_items' );
}
