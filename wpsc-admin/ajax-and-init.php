<?php

/**
 * WP eCommerce Admin AJAX functions
 *
 * These are the WPSC Admin AJAX functions
 * 
 * @package wp-e-commerce
 * @since 3.7
 */

/**
 * Add new variation set via AJAX.
 *
 * If the variation set name is the same as an existing variation set,
 * the children variant terms will be added inside that existing set.
 * @since 3.8.8
 */
function wpsc_add_variation_set() {
	$new_variation_set = $_POST['variation_set'];
	$variants = preg_split( '/\s*,\s*/', $_POST['variants'] );

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
			die('-1');
		$variation_set_id = $results['term_id'];
	}

	$inserted_variants = array();

	if ( ! empty( $variation_set_id ) ) {
		foreach ( $variants as $variant ) {
			$results = wp_insert_term( $variant, 'wpsc-variation', array( 'parent' => $variation_set_id ) );

			if ( is_wp_error( $results ) )
				die('-1');

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

		wp_terms_checklist( (int) $_POST['post_id'], array(
			'taxonomy'      => 'wpsc-variation',
			'descendants_and_self' => $variation_set_id,
			'walker'        => new WPSC_Walker_Variation_Checklist( $inserted_variants ),
			'checked_ontop' => false,
		) );
	}
	exit();
}

add_action( 'wp_ajax_wpsc_add_variation_set', 'wpsc_add_variation_set' );

function wpsc_payment_gateway_settings_form() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/gateway.php' );

	$tab = new WPSC_Settings_Tab_Gateway();
	$tab->display_payment_gateway_settings_form();
	exit;
}

add_action( 'wp_ajax_wpsc_payment_gateway_settings_form', 'wpsc_payment_gateway_settings_form' );

function wpsc_shipping_module_settings_form() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/shipping.php' );

	$tab = new WPSC_Settings_Tab_Shipping();
	$tab->display_shipping_module_settings_form();
	exit;
}

add_action( 'wp_ajax_wpsc_shipping_module_settings_form', 'wpsc_shipping_module_settings_form' );

function wpsc_navigate_settings_tab() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	require_once( 'settings-page.php' );

	$settings_page = new WPSC_Settings_Page( $_POST['tab'] );
	$settings_page->display_current_tab();
	exit;
}

add_action( 'wp_ajax_wpsc_navigate_settings_tab', 'wpsc_navigate_settings_tab' );

function wpsc_display_region_list() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	require_once( 'settings-page.php' );
	require_once( 'includes/settings-tabs/general.php' );

	$tab = new WPSC_Settings_Tab_General();
	$tab->display_region_drop_down();
	exit;
}

add_action( 'wp_ajax_wpsc_display_region_list', 'wpsc_display_region_list' );

function wpsc_ajax_add_tracking() {
	global $wpdb;
	foreach ( $_POST as $key => $value ) {
		$parts = preg_split( '/^wpsc_trackingid/', $key );
		if ( count( $parts ) > '1' ) {
			$id = $parts[1];
			$trackingid = $value;
			$wpdb->update(
				WPSC_TABLE_PURCHASE_LOGS,
				array(
				    'track_id' => $trackingid
				    ),
				array(
				 'id' => $id
				),
				'%s',
				'%d'
			    );
		}

	}
}

if ( isset( $_REQUEST['submit'] ) && ($_REQUEST['submit'] == 'Add Tracking ID') ) {
	add_action( 'admin_init', 'wpsc_ajax_add_tracking' );
}

function wpsc_purchlog_email_trackid() {
	global $wpdb;
	$id = absint( $_POST['purchlog_id'] );
	$sql = $wpdb->prepare( "SELECT `track_id` FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE `id`=%d LIMIT 1", $id );
	$trackingid = $wpdb->get_var( $sql );

	$message = get_option( 'wpsc_trackingid_message' );
	$message = str_replace( '%trackid%', $trackingid, $message );
	$message = str_replace( '%shop_name%', get_option( 'blogname' ), $message );

	$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );
	$email = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id`=%d AND `form_id` = '$email_form_field' LIMIT 1", $id ) );

	$subject = get_option( 'wpsc_trackingid_subject' );
	$subject = str_replace( '%shop_name%', get_option( 'blogname' ), $subject );

	add_filter( 'wp_mail_from', 'wpsc_replace_reply_address', 0 );
	add_filter( 'wp_mail_from_name', 'wpsc_replace_reply_name', 0 );

	wp_mail( $email, $subject, $message);

	remove_filter( 'wp_mail_from_name', 'wpsc_replace_reply_name' );
	remove_filter( 'wp_mail_from', 'wpsc_replace_reply_address' );

	exit( true );
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'purchlog_email_trackid') ) {
	add_action( 'admin_init', 'wpsc_purchlog_email_trackid' );
}

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

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'wpsc_quarterly') ) {
	add_action( 'admin_init', 'wpsc_ajax_sales_quarterly' );
}


function wpsc_delete_file() {
	global $wpdb;
	$output = 0;
	$row_number = absint( $_REQUEST['row_number'] );
	$product_id = absint( $_REQUEST['product_id'] );
	$file_name = basename( $_REQUEST['file_name'] );
	check_admin_referer( 'delete_file_' . $file_name );

	$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_parent = %d AND post_type ='wpsc-product-file'", $file_name, $product_id );
	$product_id_to_delete = $wpdb->get_var( $sql );

	wp_delete_post( $product_id_to_delete, true );

	if ( $_POST['ajax'] !== 'true' ) {
		$sendback = wp_get_referer();
		wp_redirect( $sendback );
		exit;
	}

	echo "jQuery('#select_product_file_row_$row_number').fadeOut('fast',function() {\n";
	echo "   jQuery(this).remove();\n";
	echo "   jQuery('div.select_product_file p:even').removeClass('alt');\n";
	echo "   jQuery('div.select_product_file p:odd').addClass('alt');\n";
	echo "});\n";

	exit( "" );
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'delete_file') ) {
	add_action( 'admin_init', 'wpsc_delete_file' );
}

/**
  Function and action for publishing or unpublishing single products
 */
function wpsc_ajax_toggle_published() {
	$product_id = absint( $_GET['product'] );
	check_admin_referer( 'toggle_publish_' . $product_id );

	$status = (wpsc_toggle_publish_status( $product_id )) ? ('true') : ('false');
	$sendback = add_query_arg( 'flipped', "1", wp_get_referer() );
	wp_redirect( $sendback );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'toggle_publish') ) {
	add_action( 'admin_init', 'wpsc_ajax_toggle_published' );
}

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

function wpsc_duplicate_product_process( $post, $new_parent_id = false ) {
	$new_post_date = $post->post_date;
	$new_post_date_gmt = get_gmt_from_date( $new_post_date );

	$new_post_type = $post->post_type;
	$post_content = str_replace( "'", "''", $post->post_content );
	$post_content_filtered = str_replace( "'", "''", $post->post_content_filtered );
	$post_excerpt = str_replace( "'", "''", $post->post_excerpt );
	$post_title = str_replace( "'", "''", $post->post_title ) . " (Duplicate)";
	$post_name = str_replace( "'", "''", $post->post_name );
	$comment_status = str_replace( "'", "''", $post->comment_status );
	$ping_status = str_replace( "'", "''", $post->ping_status );

	$defaults = array(
		'post_status' 			=> $post->post_status,
		'post_type' 			=> $new_post_type,
		'ping_status' 			=> $ping_status,
		'post_parent' 			=> $new_parent_id ? $new_parent_id : $post->post_parent,
		'menu_order' 			=> $post->menu_order,
		'to_ping' 				=>  $post->to_ping,
		'pinged' 				=> $post->pinged,
		'post_excerpt' 			=> $post_excerpt,
		'post_title' 			=> $post_title,
		'post_content' 			=> $post_content,
		'post_content_filtered' => $post_content_filtered,
		'import_id' 			=> 0
		);

	// Insert the new template in the post table
	$new_post_id = wp_insert_post($defaults);

	// Copy the taxonomies
	wpsc_duplicate_taxonomies( $post->ID, $new_post_id, $post->post_type );

	// Copy the meta information
	wpsc_duplicate_product_meta( $post->ID, $new_post_id );

	// Finds children (Which includes product files AND product images), their meta values, and duplicates them.
	wpsc_duplicate_children( $post->ID, $new_post_id );

	return $new_post_id;
}

/**
 * Copy the taxonomies of a post to another post
 */
function wpsc_duplicate_taxonomies( $id, $new_id, $post_type ) {
	$taxonomies = get_object_taxonomies( $post_type ); //array("category", "post_tag");
	foreach ( $taxonomies as $taxonomy ) {
		$post_terms = wp_get_object_terms( $id, $taxonomy );
		for ( $i = 0; $i < count( $post_terms ); $i++ ) {
			wp_set_object_terms( $new_id, $post_terms[$i]->slug, $taxonomy, true );
		}
	}
}

/**
 * Copy the meta information of a post to another post
 */
function wpsc_duplicate_product_meta( $id, $new_id ) {
	global $wpdb;

	$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $id ) );

	if ( count( $post_meta_infos ) ) {
		$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES ";
		$values = array();
		foreach ( $post_meta_infos as $meta_info ) {
			$meta_key = $meta_info->meta_key;
			$meta_value = addslashes( $meta_info->meta_value );

			$sql_query_sel[] = "( $new_id, '$meta_key', '$meta_value' )";
			$values[] = $new_id;
			$values[] = $meta_key;
			$values[] = $meta_value;
			$values += array( $new_id, $meta_key, $meta_value );
		}
		$sql_query.= implode( ",", $sql_query_sel );
		$sql_query = $wpdb->prepare( $sql_query, $values );
		$wpdb->query( $sql_query );
	}
}

/**
 * Duplicates children product and children meta
 */
function wpsc_duplicate_children( $old_parent_id, $new_parent_id ) {

	//Get children products and duplicate them
	$child_posts = get_posts( array(
		'post_parent' => $old_parent_id,
		'post_type' => 'any',
		'post_status' => 'any',
		'numberposts' => -1,
	) );

	foreach ( $child_posts as $child_post )
	    wpsc_duplicate_product_process( $child_post, $new_parent_id );

}

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
				$output .= "\"" . $item['quantity'] . " x " . str_replace( '"', '\"', $item['name'] ) . "\"";
				$output .= "," . $skuvalue."," ;
			}
			$output .= "\n"; // terminates the row/line in the CSV file
		}
		// Get the most number of products and create a header for them
		$headers3 = "";
		for( $i = 0; $i < $count; $i++ ){
			$headers3 .= "\"Quantity - Product Name \", \" SKU \"";
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

function wpsc_admin_ajax() {
	global $wpdb;

	if ( isset( $_POST['action'] ) && $_POST['action'] == 'product-page-order' ) {
		$current_order = get_option( 'wpsc_product_page_order' );
		$new_order = $_POST['order'];

		if ( isset( $new_order["advanced"] ) ) {
			$current_order["advanced"] = array_unique( explode( ',', $new_order["advanced"] ) );
		}
		if ( isset( $new_order["side"] ) ) {
			$current_order["side"] = array_unique( explode( ',', $new_order["side"] ) );
		}

		update_option( 'wpsc_product_page_order', $current_order );
		exit( print_r( $order, 1 ) );
	}


	if ( isset( $_POST['save_image_upload_state'] ) && $_POST['save_image_upload_state'] == 'true' && is_numeric( $_POST['image_upload_state'] ) ) {
		$upload_state = (int)(bool)$_POST['image_upload_state'];
		update_option( 'wpsc_use_flash_uploader', $upload_state );
		exit( "done" );
	}

	if ( isset( $_POST['remove_variation_value'] ) && $_POST['remove_variation_value'] == "true" && is_numeric( $_POST['variation_value_id'] ) ) {
		$value_id = absint( $_GET['variation_value_id'] );
		echo wp_delete_term( $value_id, 'wpsc-variation' );
		exit();
	}

	if ( isset( $_POST['hide_ecom_dashboard'] ) && $_POST['hide_ecom_dashboard'] == 'true' ) {
		require_once (ABSPATH . WPINC . '/rss.php');
		$rss = fetch_rss( 'http://www.instinct.co.nz/feed/' );
		$rss->items = array_slice( $rss->items, 0, 5 );
		$rss_hash = sha1( serialize( $rss->items ) );
		update_option( 'wpsc_ecom_news_hash', $rss_hash );
		exit( 1 );
	}

	if ( isset( $_POST['remove_meta'] ) && $_POST['remove_meta'] == 'true' && is_numeric( $_POST['meta_id'] ) ) {
		$meta_id = (int)$_POST['meta_id'];
		if ( delete_meta( $meta_id ) ) {
			echo $meta_id;
			exit();
		}
		echo 0;
		exit();
	}

	if ( isset( $_REQUEST['log_state'] ) && $_REQUEST['log_state'] == "true" && is_numeric( $_POST['id'] ) && is_numeric( $_POST['value'] ) ) {
		$newvalue = $_POST['value'];
		if ( $_REQUEST['suspend'] == 'true' ) {
			if ( $_REQUEST['value'] == 1 && function_exists('wpsc_member_dedeactivate_subscriptions'))
					wpsc_member_dedeactivate_subscriptions( $_POST['id'] );
			elseif( function_exists('wpsc_member_deactivate_subscriptions'))
				wpsc_member_deactivate_subscriptions( $_POST['id'] );

			exit();
		} else {

			$log_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = '%d' LIMIT 1", $_POST['id'] ), ARRAY_A );
			if ( ($newvalue == 2) && function_exists( 'wpsc_member_activate_subscriptions' ) ) {
				wpsc_member_activate_subscriptions( $_POST['id'] );
			}

			$wpdb->update(
				    WPSC_TABLE_PURCHASE_LOGS,
				    array(
					'processed' => $newvalue
				    ),
				    array(
					'id' => $_POST['id']
				    ),
				    '%d',
				    '%d'
				);
			if ( ($newvalue > $log_data['processed']) && ($log_data['processed'] < 2) ) {
				transaction_results( $log_data['sessionid'], false );
			}

			$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );
			echo "document.getElementById(\"form_group_" . $_POST['id'] . "_text\").innerHTML = '" . $status_name . "';\n";

			$year = date( "Y" );
			$month = date( "m" );
			$start_timestamp = mktime( 0, 0, 0, $month, 1, $year );
			$end_timestamp = mktime( 0, 0, 0, ($month + 1 ), 0, $year );

			echo "document.getElementById(\"log_total_month\").innerHTML = '" . addslashes( wpsc_currency_display( admin_display_total_price( $start_timestamp, $end_timestamp ) ) ) . "';\n";
			echo "document.getElementById(\"log_total_absolute\").innerHTML = '" . addslashes( wpsc_currency_display( admin_display_total_price() ) ) . "';\n";
			exit();
		}
	}
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

//call to change view for purchase log

function wpsc_purchlog_filter_by() {
	wpsc_change_purchlog_view( $_POST['view_purchlogs_by'], $_POST['view_purchlogs_by_status'] );
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'purchlog_filter_by') ) {
	add_action( 'admin_init', 'wpsc_purchlog_filter_by' );
}

//bulk actions for purchase log
function wpsc_purchlog_bulk_modify() {
	if ( $_POST['purchlog_multiple_status_change'] != -1 ) {
		if ( is_numeric( $_POST['purchlog_multiple_status_change'] ) && $_POST['purchlog_multiple_status_change'] != 'delete' ) {
			foreach ( (array)$_POST['purchlogids'] as $purchlogid ) {
				wpsc_purchlog_edit_status( $purchlogid, $_POST['purchlog_multiple_status_change'] );
				$updated++;
			}
		} elseif ( $_POST['purchlog_multiple_status_change'] == 'delete' ) {
			foreach ( (array)$_POST['purchlogids'] as $purchlogid ) {

				wpsc_delete_purchlog( $purchlogid );
				$deleted++;
			}
		}
	}
	$sendback = wp_get_referer();
	if ( isset( $updated ) ) {
		$sendback = add_query_arg( 'updated', $updated, $sendback );
	}
	if ( isset( $deleted ) ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}
	if ( isset( $_POST['view_purchlogs_by'] ) ) {
		$sendback = add_query_arg( 'view_purchlogs_by', $_POST['view_purchlogs_by'], $sendback );
	}
	if ( isset( $_POST['view_purchlogs_by_status'] ) ) {
		$sendback = add_query_arg( 'view_purchlogs_by_status', $_POST['view_purchlogs_by_status'], $sendback );
	}
	wp_redirect( $sendback );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action2'] ) && ($_REQUEST['wpsc_admin_action2'] == 'purchlog_bulk_modify') ) {
	add_action( 'admin_init', 'wpsc_purchlog_bulk_modify' );
}

//edit purchase log status function
function wpsc_purchlog_edit_status( $purchlog_id='', $purchlog_status='' ) {
	global $wpdb;
	if ( empty($purchlog_id) && empty($purchlog_status) ) {
		$purchlog_id = absint( $_POST['purchlog_id'] );
		$purchlog_status = absint( $_POST['purchlog_status'] );
	}

	$log_data = $wpdb->get_row( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = '{$purchlog_id}' LIMIT 1", ARRAY_A );
	$is_transaction = wpsc_check_purchase_processed($log_data['processed']);
	if ( $is_transaction && function_exists('wpsc_member_activate_subscriptions')) {
		wpsc_member_activate_subscriptions( $_POST['id'] );
	}

	//in the future when everyone is using the 2.0 merchant api, we should use the merchant class to update the staus,
	// then you can get rid of this hook and have each person overwrite the method that updates the status.
	do_action('wpsc_edit_order_status', array('purchlog_id'=>$purchlog_id, 'purchlog_data'=>$log_data, 'new_status'=>$purchlog_status));

	$wpdb->update(
		    WPSC_TABLE_PURCHASE_LOGS,
		    array(
			'processed' => $purchlog_status
		    ),
		    array(
			'id' => $purchlog_id
		    ),
		    '%d',
		    '%d'
		);
	wpsc_clear_stock_claims();
	wpsc_decrement_claimed_stock($purchlog_id);

	if ( $purchlog_status == 3 )
		transaction_results($log_data['sessionid'],false,null);
}

add_action( 'wp_ajax_purchlog_edit_status', 'wpsc_purchlog_edit_status' );

function wpsc_save_product_order() {
	global $wpdb;

	$products = array( );
	foreach ( $_POST['post'] as $product ) {
		$products[] = absint( $product );
	}

	print_r( $products );

	foreach ( $products as $order => $product_id ) {
	    $wpdb->update(
			$wpdb->posts,
			array(
			    'menu_order' => $order
			),
			array(
			    'ID' => $product_id
			),
			'%d',
			'%d'
		    );
		}
	$success = true;

	exit( (string)$success );
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'save_product_order') ) {
	add_action( 'admin_init', 'wpsc_save_product_order' );
}

function wpsc_update_checkout_fields_order() {
	global $wpdb;

	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	$checkout_fields = $_REQUEST['sort_order'];
	$order = 1;
	foreach ( $checkout_fields as $checkout_field ) {
		// ignore new fields
		if ( strpos( $checkout_field, 'new-field' ) === 0 )
			continue;
		$checkout_field = absint( preg_replace('/[^0-9]+/', '', $checkout_field ) );

		$wpdb->update(
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
	}

	die( 'success' );
}

add_action( 'wp_ajax_wpsc_update_checkout_fields_order', 'wpsc_update_checkout_fields_order' );

/* Start Order Notes (by Ben) */
function wpsc_purchlogs_update_notes( $purchlog_id = '', $purchlog_notes = '' ) {
	global $wpdb;
	if ( wp_verify_nonce( $_POST['wpsc_purchlogs_update_notes_nonce'], 'wpsc_purchlogs_update_notes' ) ) {
		if ( ($purchlog_id == '') && ($purchlog_notes == '') ) {
			$purchlog_id = $_POST['purchlog_id'];
			$purchlog_notes = $_POST['purchlog_notes'];
		}
		$wpdb->update(
			    WPSC_TABLE_PURCHASE_LOGS,
			    array(
				'notes' => $purchlog_notes
			    ),
			    array(
				'id' => $purchlog_id
			    ),
			    '%s',
			    '%d'
			);
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'purchlogs_update_notes' ) )
	add_action( 'admin_init', 'wpsc_purchlogs_update_notes' );

/* End Order Notes (by Ben) */

//delete a purchase log
function wpsc_delete_purchlog( $purchlog_id = '' ) {
	global $wpdb;

	$deleted = 0;

	if ( $purchlog_id == '' ) {
		$purchlog_id = absint( $_GET['purchlog_id'] );
		check_admin_referer( 'delete_purchlog_' . $purchlog_id );
	}

	if ( is_numeric( $purchlog_id ) ) {
		$delete_log_form_sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = %d";
		$cart_content = $wpdb->get_results( $wpdb->prepare( $delete_log_form_sql, $purchlog_id ), ARRAY_A );
	}

	$purchlog_status = $wpdb->get_var( $wpdb->prepare( "SELECT `processed` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id`= %d", $purchlog_id ) );
	if ( $purchlog_status == 5 || $purchlog_status == 1 ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_CLAIMED_STOCK . "` WHERE `cart_id` = %d AND `cart_submitted` = '1'", $purchlog_id ) );
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = %d", $purchlog_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` IN (%d)", $purchlog_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = %d LIMIT 1", $purchlog_id ) );

	$deleted = 1;

	if ( is_numeric( $_GET['purchlog_id'] ) ) {
		$sendback = wp_get_referer();
		$sendback = remove_query_arg( 'purchaselog_id', $sendback );
		if ( isset( $deleted ) ) {
			$sendback = add_query_arg( 'deleted', $deleted, $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'delete_purchlog') ) {
	add_action( 'admin_init', 'wpsc_delete_purchlog' );
}

function wpsc_ajax_get_payment_form() {
	$paymentname = $_REQUEST['paymentname'];
	$payment_data = wpsc_get_payment_form( $paymentname );
	$html_payment_name = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $payment_data['name'] ) );
	$payment_form = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $payment_data['form_fields'] ) );
	echo "payment_name_html = '$html_payment_name'; \n\r";
	echo "payment_form_html = '$payment_form'; \n\r";
	echo "has_submit_button = '{$payment_data['has_submit_button']}'; \n\r";
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'get_payment_form') )
	add_action( 'admin_init', 'wpsc_ajax_get_payment_form' );

add_action( 'update_option_product_category_hierarchical_url', 'wpsc_update_option_product_category_hierarchical_url' );

function wpsc_update_option_product_category_hierarchical_url() {
	flush_rewrite_rules( false );
}

function wpsc_change_currency() {
	if ( is_numeric( $_POST['currencyid'] ) ) {
		$currency_data = $wpdb->get_results( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . $_POST['currencyid'] . "' LIMIT 1", ARRAY_A );
		$price_out = null;
		if ( $currency_data[0]['symbol'] != '' ) {
			$currency_sign = $currency_data[0]['symbol_html'];
		} else {
			$currency_sign = $currency_data[0]['code'];
		}
		echo $currency_sign;
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'change_currency') )
	add_action( 'admin_init', 'wpsc_change_currency' );

function wpsc_rearrange_images() {
	global $wpdb;
	$images = explode( ",", $_POST['order'] );
	$product_id = absint( $_POST['product_id'] );
	$timestamp = time();

	$new_main_image = null;
	$have_set_first_item = false;
	$i = 0;
	foreach ( $images as $image ) {
		if ( $image > 0 ) {
			$wpdb->update(
				    $wpdb->posts,
				    array(
					'menu_order' => $i
				    ),
				    array(
					'ID' => $image
				    ),
				    '%d',
				    '%d'
				);
			$i++;
		}
	}
	$output = wpsc_main_product_image_menu( $product_id );
	echo "image_menu = '';\n\r";
	echo "image_id = '" . $new_main_image . "';\n\r";
	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'rearrange_images') )
	add_action( 'admin_init', 'wpsc_rearrange_images' );

/**
 * wpsc_update_page_urls gets the permalinks for products pages and stores them in the options for quick reference
 * @public
 *
 * @since 3.6
 * @param $auto (Boolean) true if coming from WordPress Permalink Page, false otherwise
 * @return nothing
 */
function wpsc_update_page_urls($auto = false) {
	global $wpdb;

	$wpsc_pageurl_option['product_list_url'] = '[productspage]';
	$wpsc_pageurl_option['shopping_cart_url'] = '[shoppingcart]';
	$check_chekout = $wpdb->get_var( "SELECT `guid` FROM `{$wpdb->posts}` WHERE `post_content` LIKE '%[checkout]%' LIMIT 1" );
	if ( $check_chekout != null ) {
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';
	} else {
		$wpsc_pageurl_option['checkout_url'] = '[checkout]';
	}
	$wpsc_pageurl_option['transact_url'] = '[transactionresults]';
	$wpsc_pageurl_option['user_account_url'] = '[userlog]';
	$changes_made = false;
	foreach ( $wpsc_pageurl_option as $option_key => $page_string ) {
		$post_id = $wpdb->get_var( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` IN('page','post') AND `post_content` LIKE '%$page_string%' LIMIT 1" );
		if ( ! $post_id )
			continue;
		$the_new_link = _get_page_link( $post_id );
		if ( stristr( get_option( $option_key ), "https://" ) ) {
			$the_new_link = str_replace( 'http://', "https://", $the_new_link );
		}

		update_option( $option_key, $the_new_link );
	}

	if(!$auto){
		$sendback = wp_get_referer();
		if ( isset( $updated ) )
			$sendback = add_query_arg( 'updated', $updated, $sendback );

		if ( isset( $_SESSION['wpsc_settings_curr_page'] ) )
			$sendback = add_query_arg( 'tab', $_SESSION['wpsc_settings_curr_page'], $sendback );

		wp_redirect( $sendback );
		exit();
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'update_page_urls') )
	add_action( 'admin_init', 'wpsc_update_page_urls' );

function wpsc_clean_categories() {
	global $wpdb, $wp_rewrite;
	$sql_query = "SELECT `id`, `name`, `active` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "`";
	$sql_data = $wpdb->get_results( $sql_query, ARRAY_A );
	foreach ( (array)$sql_data as $datarow ) {
		if ( $datarow['active'] == 1 ) {
			$tidied_name = strtolower( trim( $datarow['name'] ) );
			$url_name = sanitize_title( $tidied_name );
			$similar_names = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS `count`, MAX(REPLACE(`nice-name`, '%s', '')) AS `max_number` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "` WHERE `nice-name` REGEXP '^(" . esc_sql( $url_name ) . "){1}(\d)*$' AND `id` NOT IN (%d) ", $url_name, $datarow['id'] ), ARRAY_A );
			$extension_number = '';

			if ( $similar_names['count'] > 0 )
			    $extension_number = (int)$similar_names['max_number'] + 2;

			$url_name .= $extension_number;

			$wpdb->update(
				WPSC_TABLE_PRODUCT_CATEGORIES,
				array(
				    'nice-name' => $url_name
				),
				array(
				    'id' => $datarow['id']
				),
				'%s',
				'%d'
			    );

			$updated;

		} else if ( $datarow['active'] == 0 ) {
			$wpdb->update(
				WPSC_TABLE_PRODUCT_CATEGORIES,
				array(
				    'nice-name' => ''
				),
				array(
				    'id' => $datarow['id']
				),
				'%s',
				'%d'
			    );
			$updated;
		}
	}
	$wp_rewrite->flush_rules();
	$sendback = wp_get_referer();

	if ( isset( $updated ) ) {
		$sendback = add_query_arg( 'updated', $updated, $sendback );
	}
	if ( isset( $_SESSION['wpsc_settings_curr_page'] ) ) {
		$sendback = add_query_arg( 'tab', $_SESSION['wpsc_settings_curr_page'], $sendback );
	}

	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'clean_categories') )
	add_action( 'admin_init', 'wpsc_clean_categories' );

//change the regions tax settings
function wpsc_change_region_tax() {
	global $wpdb;
	if ( is_array( $_POST['region_tax'] ) ) {
		foreach ( $_POST['region_tax'] as $region_id => $tax ) {
			if ( is_numeric( $region_id ) && is_numeric( $tax ) ) {
				$previous_tax = $wpdb->get_var( $wpdb->prepare( "SELECT `tax` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id` = %d LIMIT 1", $region_id ) );
				if ( $tax != $previous_tax ) {
					$wpdb->update(
						WPSC_TABLE_REGION_TAX,
						array(
						    'tax' => $tax
						),
						array(
						    'id' => $region_id
						),
						'%s',
						'%d'
					    );
					$changes_made = true;
				}
			}
		}
		$sendback = wp_get_referer();
		wp_redirect( $sendback );
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'change_region_tax') )
	add_action( 'admin_init', 'wpsc_change_region_tax' );

function wpsc_product_files_existing() {
	//List all product_files, with checkboxes

	$product_id = absint( $_GET["product_id"] );
	$file_list = wpsc_uploaded_files();

	$args = array(
		'post_type' => 'wpsc-product-file',
		'post_parent' => $product_id,
		'numberposts' => -1,
		'post_status' => 'all'
	);
	$attached_files = (array)get_posts( $args );

	foreach ( $attached_files as $key => $attached_file ) {
		$attached_files_by_file[$attached_file->post_title] = & $attached_files[$key];
	}

	$output = "<span class='admin_product_notes select_product_note '>" . __( 'Choose a downloadable file for this product:', 'wpsc' ) . "</span><br>";
	$output .= "<form method='post' class='product_upload'>";
	$output .= "<div class='ui-widget-content multiple-select select_product_file'>";
	$num = 0;
	foreach ( (array)$file_list as $file ) {
		$num++;
		$checked_curr_file = "";
		if ( isset( $attached_files_by_file[$file['display_filename']] ) ) {
			$checked_curr_file = "checked='checked'";
		}

		$output .= "<p " . ((($num % 2) > 0) ? '' : "class='alt'") . " id='select_product_file_row_$num'>\n";
		$output .= "  <input type='checkbox' name='select_product_file[]' value='" . $file['real_filename'] . "' id='select_product_file_$num' " . $checked_curr_file . " />\n";
		$output .= "  <label for='select_product_file_$num'>" . $file['display_filename'] . "</label>\n";
		$output .= "</p>\n";
	}

	$output .= "</div>";
	$output .= "<input type='hidden' id='hidden_id' value='$product_id' />";
	$output .= "<input type='submit' name='save' name='product_files_submit' class='button-primary prdfil' value='Save Product Files' />";
	$output .= "</form>";
	$output .= "<div class='" . ((is_numeric( $product_id )) ? "edit_" : "") . "select_product_handle'><div></div></div>";
	$output .= "<script type='text/javascript'>\n\r";
	$output .= "var select_min_height = " . (25 * 3) . ";\n\r";
	$output .= "var select_max_height = " . (25 * ($num + 1)) . ";\n\r";
	$output .= "</script>";


	echo $output;
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'product_files_existing') )
	add_action( 'admin_init', 'wpsc_product_files_existing' );

function prod_upload() {
	global $wpdb;
	$product_id = absint( $_POST["product_id"] );
	$output = '';
	foreach ( $_POST["select_product_file"] as $selected_file ) {
		// if we already use this file, there is no point doing anything more.

		$sql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type = 'wpsc-product-file' AND post_title = %s", $selected_file ); // TODO it's safer to select by post ID, in that case we will use get_posts()
		$file_post_data = $wpdb->get_row( $sql, ARRAY_A );
		$selected_file_path = WPSC_FILE_DIR . basename( $selected_file );

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

		$output .= '<tr class="wpsc_product_download_row ' . $class . '"  id="elect_product_file_row_id_' . $id . '">';
		$output .= '<td style="padding-right: 30px;">' . $attachment['post_title'] . '</td>';
		$output .= '<td>' . wpsc_convert_byte( $file_size ) . '</td>';
		$output .= '<td>.' . wpsc_get_extension( $attachment['post_title'] ) . '</td>';
		$output .= "<td><a class='file_delete_button' href='{$deletion_url}' >" . _x( 'Delete', 'Digital Downliad UI row', 'wpsc' ) . "</a></td>";
		$output .= '<td><a href=' .$file_url .'>' . _x( 'Download', 'Digital Downliad UI row', 'wpsc' ) . '</a></td>';				
		$output .= '</tr>';
	}

	echo $output;
}
if ( isset( $_GET['wpsc_admin_action'] ) && ($_GET['wpsc_admin_action'] == 'product_files_upload') )
	add_action( 'admin_init', 'prod_upload' );

//change the gateway settings
function wpsc_gateway_settings() {
	//To update options
	if ( isset( $_POST['wpsc_options'] ) ) {
		foreach ( $_POST['wpsc_options'] as $key => $value ) {
			if ( $value != get_option( $key ) ) {
				update_option( $key, $value );
			}
		}
		unset( $_POST['wpsc_options'] );
	}

	if ( isset( $_POST['user_defined_name'] ) && is_array( $_POST['user_defined_name'] ) ) {
		$payment_gateway_names = get_option( 'payment_gateway_names' );

		if ( !is_array( $payment_gateway_names ) ) {
			$payment_gateway_names = array( );
		}
		$payment_gateway_names = array_merge( $payment_gateway_names, (array)$_POST['user_defined_name'] );
		update_option( 'payment_gateway_names', $payment_gateway_names );
	}
	$custom_gateways = get_option( 'custom_gateway_options' );

	$nzshpcrt_gateways = nzshpcrt_get_gateways();
	foreach ( $nzshpcrt_gateways as $gateway ) {
		if ( in_array( $gateway['internalname'], $custom_gateways ) ) {
			if ( isset( $gateway['submit_function'] ) ) {
				call_user_func_array( $gateway['submit_function'], array( ) );
				$changes_made = true;
			}
		}
	}
	if ( (isset( $_POST['payment_gw'] ) && $_POST['payment_gw'] != null ) ) {
		update_option( 'payment_gateway', $_POST['payment_gw'] );
	}

	do_action( 'wpsc_update_payment_gateway_settings' );
}

if ( isset( $_REQUEST['wpsc_gateway_settings'] ) && ($_REQUEST['wpsc_gateway_settings'] == 'gateway_settings') )
	add_action( 'admin_init', 'wpsc_gateway_settings' );

function wpsc_google_shipping_settings() {
	if ( isset( $_POST['submit'] ) ) {
		foreach ( (array)$_POST['google_shipping'] as $key => $country ) {
			if ( $country == 'on' ) {
				$google_shipping_country[] = $key;
				$updated++;
			}
		}
		update_option( 'google_shipping_country', $google_shipping_country );
		$sendback = wp_get_referer();
		$sendback = remove_query_arg( 'googlecheckoutshipping', $sendback );

		if ( isset( $updated ) ) {
			$sendback = add_query_arg( 'updated', $updated, $sendback );
		}

		wp_redirect( $sendback );
		exit();
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'google_shipping_settings') ) {
	add_action( 'admin_init', 'wpsc_google_shipping_settings' );
}

function wpsc_update_variations() {
	$product_id = absint( $_POST["product_id"] );
	$product_type_object = get_post_type_object('wpsc-product');
	if (!current_user_can($product_type_object->cap->edit_post, $product_id))
		return;

	//Setup postdata
	$post_data = array( );
	$post_data['edit_var_val'] = isset( $_POST['edit_var_val'] ) ? $_POST["edit_var_val"] : '';
	$post_data['description'] = isset( $_POST['description'] ) ? $_POST["description"] : '';
	$post_data['additional_description'] = isset( $_POST['additional_description'] ) ? $_POST['additional_description'] : '';
	$post_data['name'] = (!empty($_POST['name']))?$_POST['name']:$_POST["post_title"];

	//Add or delete variations
	wpsc_edit_product_variations( $product_id, $post_data );
	if (defined('DOING_AJAX') && DOING_AJAX) {
		wpsc_admin_product_listing( $product_id );
		die();
	}
}

if ( isset($_POST["edit_var_val"]) )
	add_action( 'admin_init', 'wpsc_update_variations', 50 );
add_action('wp_ajax_wpsc_update_variations', 'wpsc_update_variations', 50 );

function wpsc_delete_variation_set() {
	check_admin_referer( 'delete-variation' );

	if ( is_numeric( $_GET['deleteid'] ) ) {
		$variation_id = absint( $_GET['deleteid'] );

		$variation_set = get_term( $variation_id, 'wpsc-variation', ARRAY_A );


		$variations = get_terms( 'wpsc-variation', array(
					'hide_empty' => 0,
					'parent' => $variation_id
				) );

		foreach ( (array)$variations as $variation ) {
			$return_value = wp_delete_term( $variation->term_id, 'wpsc-variation' );
		}

		if ( !empty( $variation_set ) ) {
			$return_value = wp_delete_term( $variation_set['term_id'], 'wpsc-variation' );
		}
		$deleted = 1;
	}

	$sendback = wp_get_referer();
	if ( isset( $deleted ) ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}
	$sendback = remove_query_arg( array(
				'deleteid',
				'variation_id'
					), $sendback );

	wp_redirect( $sendback );
	exit();
}

function wpsc_force_flush_theme_transients() {
	// Flush transients
	wpsc_flush_theme_transients( true );

	// Bounce back
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_flush_theme_transients'] ) && ( $_REQUEST['wpsc_flush_theme_transients'] == 'true' ) )
	add_action( 'admin_init', 'wpsc_force_flush_theme_transients' );

function wpsc_backup_theme() {
	$wp_theme_path = get_stylesheet_directory();
	wpsc_recursive_copy( $wp_theme_path, WPSC_THEME_BACKUP_DIR );
	$_SESSION['wpsc_themes_backup'] = true;
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'backup_themes' ) )
	add_action( 'admin_init', 'wpsc_backup_theme' );


function wpsc_delete_coupon(){
	global $wpdb;

	check_admin_referer( 'delete-coupon' );
	$coupon_id = (int)$_GET['delete_id'];

	if(isset($coupon_id)) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_COUPON_CODES."` WHERE `id` = %d LIMIT 1", $coupon_id ) );
			$deleted = 1;
	}
	$sendback = wp_get_referer();
	if ( isset( $deleted ) )
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );

	$sendback = remove_query_arg( array('deleteid',), $sendback );
	wp_redirect( $sendback );
	exit();
}

if ( isset( $_GET['action'] ) && ( 'purchase_log' == $_GET['action'] ) )
	add_action( 'admin_init', 'wpsc_admin_sale_rss' );

if ( isset( $_GET['purchase_log_csv'] ) && ( 'true' == $_GET['purchase_log_csv'] ) )
	add_action( 'admin_init', 'wpsc_purchase_log_csv' );

if ( isset( $_REQUEST['ajax'] ) && isset( $_REQUEST['admin'] ) && ($_REQUEST['ajax'] == "true") && ($_REQUEST['admin'] == "true") )
	add_action( 'admin_init', 'wpsc_admin_ajax' );

// Variation set deleting init code starts here
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-variation-set' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_delete_variation_set' );

//Delete Coupon
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-coupon' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_delete_coupon' );


function flat_price( $price ) {
	if ( ! empty( $price ) && strchr( $price, '-' ) === false && strchr( $price, '+' ) === false && strchr( $price, '%' ) === false )
		return true;
}

function percentile_price( $price ) {
	if ( ! empty( $price ) && ( strchr( $price, '-' ) || strchr( $price, '+' ) ) && strchr( $price, '%' ) )
		return true;
}

function differential_price( $price ) {
	if ( ! empty( $price ) && ( strchr( $price, '-' ) || strchr( $price, '+' ) ) && strchr( $price, '%' ) === false )
		return true;
}

/**
 * If it doesn't exist, let's create a multi-dimensional associative array
 * that will contain all of the term/price associations
 *
 * @param <type> $variation
 */
function variation_price_field( $variation ) {
	$term_prices = get_option( 'term_prices' );

	if ( is_object( $variation ) )
		$term_id = $variation->term_id;

	if ( empty( $term_prices ) || !is_array( $term_prices ) ) {

		$term_prices = array( );
		if ( isset( $term_id ) ) {
			$term_prices[$term_id] = array( );
			$term_prices[$term_id]["price"] = '';
			$term_prices[$term_id]["checked"] = '';
		}
		add_option( 'term_prices', $term_prices );
	}

	if ( isset( $term_id ) && is_array( $term_prices ) && array_key_exists( $term_id, $term_prices ) )
		$price = esc_attr( $term_prices[$term_id]["price"] );
	else
		$price = '';

	if( !isset( $_GET['action'] ) ) {
	?>
	<div class="form-field">
		<label for="variation_price"><?php _e( 'Variation Price', 'wpsc' ); ?></label>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php _e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
	</div>
	<script type="text/javascript">
		jQuery('#parent option:contains("   ")').remove();
		jQuery('#parent').mousedown(function(){
			jQuery('#parent option:contains("   ")').remove();
		});
	</script>
	<?php
	} else{
	?>
	<tr class="form-field">
            <th scope="row" valign="top">
		<label for="variation_price"><?php _e( 'Variation Price', 'wpsc' ); ?></label>
            </th>
            <td>
		<input type="text" name="variation_price" id="variation_price" style="width:50px;" value="<?php echo $price; ?>"><br />
		<span class="description"><?php _e( 'You can list a default price here for this variation.  You can list a regular price (18.99), differential price (+1.99 / -2) or even a percentage-based price (+50% / -25%).', 'wpsc' ); ?></span>
            </td>
	</tr>
	<?php
	}
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_price_field' );
add_action( 'wpsc-variation_add_form_fields', 'variation_price_field' );

/*
WordPress doesnt let you change the custom post type taxonomy form very easily
Use Jquery to move the set variation (parent) field to the top and add a description
*/
function variation_set_field(){
?>
	<script>
		/* change the text on the variation set from (none) to new variation set*/
		jQuery("#parent option[value='-1']").text("New Variation Set");
		/* Move to the top of the form and add a description */
		jQuery("#tag-name").parent().before( jQuery("#parent").parent().append('<p>Choose the Variation Set you want to add variants to. If your\'e creating a new variation set then select "New Variation Set"</p>') );
		/*
		create a small description about variations below the add variation / set title
		we can then get rid of the big red danger warning
		*/
		( jQuery("div#ajax-response").after('<p>Variations allow you to create options for your products, for example if you\'re selling T-Shirts they will have a size option you can create this as a variation. Size will be the Variation Set name, and it will be a "New Variant Set". You will then create variants (small, medium, large) which will have the "Variation Set" of Size. Once you have made your set you can use the table on the right to manage them (edit, delete). You will be able to order your variants by draging and droping them within their Variation Set.</p>') );
	</script>
<?php
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_set_field' );
add_action( 'wpsc-variation_add_form_fields', 'variation_set_field' );


function category_edit_form(){
?>
	<script type="text/javascript">

	</script>
<?php
}

function variation_price_field_check( $variation ) {

	$term_prices = get_option( 'term_prices' );

	if ( is_array( $term_prices ) && array_key_exists( $variation->term_id, $term_prices ) )
		$checked = ($term_prices[$variation->term_id]["checked"] == 'checked') ? 'checked' : '';
	else
		$checked = ''; ?>

	<tr class="form-field">
		<th scope="row" valign="top"><label for="apply_to_current"><?php _e( 'Apply to current variations?', 'wpsc' ) ?></label></th>
		<td>
			<span class="description"><input type="checkbox" name="apply_to_current" id="apply_to_current" style="width:2%;" <?php echo $checked; ?> /><?php _e( 'By checking this box, the price rule you implement above will be applied to all variations that currently exist.  If you leave it unchecked, it will only apply to products that use this variation created or edited from now on.  Take note, this will apply this rule to <strong>every</strong> product using this variation.  If you need to override it for any reason on a specific product, simply go to that product and change the price.', 'wpsc' ); ?></span>
		</td>
	</tr>
<?php
}
add_action( 'wpsc-variation_edit_form_fields', 'variation_price_field_check' );



/**
 * @todo - Should probably refactor this at some point - very procedural,
 *		   WAY too many foreach loops for my liking :)  But it does the trick
 *
 * @param <type> $term_id
 */
function save_term_prices( $term_id ) {

	// First - Saves options from input
	if ( isset( $_POST['variation_price'] ) || isset( $_POST["apply_to_current"] ) ) {

		$term_prices = get_option( 'term_prices' );

		$term_prices[$term_id]["price"] = $_POST["variation_price"];
		$term_prices[$term_id]["checked"] = (isset( $_POST["apply_to_current"] )) ? "checked" : "unchecked";

		update_option( 'term_prices', $term_prices );
	}

	// Second - If box was checked, let's then check whether or not it was flat, differential, or percentile, then let's apply the pricing to every product appropriately
	if ( isset( $_POST["apply_to_current"] ) ) {

		//Check for flat, percentile or differential
		$var_price_type = '';

		if ( flat_price( $_POST["variation_price"] ) )
			$var_price_type = 'flat';
		elseif ( differential_price( $_POST["variation_price"] ) )
			$var_price_type = 'differential';
		elseif ( percentile_price( $_POST["variation_price"] ) )
			$var_price_type = 'percentile';

		//Now, find all products with this term_id, update their pricing structure (terms returned include only parents at this point, we'll grab relevent children soon)
		$products_to_mod = get_objects_in_term( $term_id, "wpsc-variation" );
		$product_parents = array( );

		foreach ( (array)$products_to_mod as $get_parent ) {

			$post = get_post( $get_parent );

			if ( !$post->post_parent )
				$product_parents[] = $post->ID;
		}

		//Now that we have all parent IDs with this term, we can get the children (only the ones that are also in $products_to_mod, we don't want to apply pricing to ALL kids)

		foreach ( $product_parents as $parent ) {
			$args = array(
				'post_parent' => $parent,
				'post_type' => 'wpsc-product'
			);
			$children = get_children( $args, ARRAY_A );

			foreach ( $children as $childrens ) {
				$parent = $childrens["post_parent"];
				$children_ids[$parent][] = $childrens["ID"];
				$children_ids[$parent] = array_intersect( $children_ids[$parent], $products_to_mod );
			}
		}

		//Got the right kids, let's grab their parent pricing and modify their pricing based on var_price_type

		foreach ( (array)$children_ids as $parents => $kids ) {

			$kids = array_values( $kids );

			foreach ( $kids as $kiddos ) {
				$price = wpsc_determine_variation_price( $kiddos );
				update_product_meta( $kiddos, 'price', $price );
			}
		}
	}
}
add_action( 'edited_wpsc-variation', 'save_term_prices' );
add_action( 'created_wpsc-variation', 'save_term_prices' );

function wpsc_delete_variations( $postid ) {
	$post = get_post( $postid );
	if ( $post->post_type != 'wpsc-product' || $post->post_parent != 0 )
		return;
	$variations = get_posts( array(
		'post_type' => 'wpsc-product',
		'post_parent' => $postid,
		'post_status' => 'any',
		'numberposts' => -1,
	) );

	if ( ! empty( $variations ) )
		foreach ( $variations as $variation ) {
			wp_delete_post( $variation->ID, true );
		}
}
add_action( 'delete_post', 'wpsc_delete_variations' );

/*
Save the variations that have been
created on the products page
*/
function wpsc_add_variant_from_products_page() {
/* This is the parent term / vartiation set we will save this first */
	$variation_set_term = $_POST['variation'];
	$variants[0] = $_POST['variant'];

	/*
	variants can be coma separated so we check for
	these and put them into an array
	*/
	$variants = explode( ',', $variants[0] );
	wp_insert_term( $variation_set_term, 'wpsc-variation', $args = array() );

	/* now get the parent id so we can save all the kids*/
	$parent_term = term_exists( $variation_set_term, 'wpsc-variation' ); // array is returned if taxonomy is given
	$parent_term_id = $parent_term['term_id']; // get numeric term id
	/* if we have a parent and some kids then we will add kids now */
	if( !empty($parent_term_id) && !empty($variants) ){
		foreach( $variants as $variant ){
			wp_insert_term( $variant, 'wpsc-variation', $args = array('parent' => $parent_term_id) );
			/* want to get out the id so we can return it with the response */
			$varient_term = term_exists( $variant, 'wpsc-variation', $parent_term_id );
			$variant_term_id[] = $varient_term['term_id']; // get numeric term id
		}
	}
	$response = new WP_Ajax_Response;
	$response -> add( array(
		'data' 			=> 'success',
		'supplemental' 	=> array(
		'variant_id' 	=> implode(",",$variant_term_id),
		),
	)
	);
	$response -> send();
	exit();
}

add_action( 'wp_ajax_wpsc_add_variant_from_products_page', 'wpsc_add_variant_from_products_page' );

function wpsc_delete_variant_from_products_page(){
	$variant_id = $_POST['variant_id'];
	/* should never be empty but best to check first*/
	if (!empty($variant_id))
		wp_delete_term( $variant_id, 'wpsc-variation');
	exit();
}
add_action( 'wp_ajax_wpsc_delete_variant_from_products_page', 'wpsc_delete_variant_from_products_page' );
?>