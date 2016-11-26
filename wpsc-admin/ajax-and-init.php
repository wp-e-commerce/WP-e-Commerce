<?php

/**
 * WP eCommerce Admin AJAX functions
 *
 * These are the WPSC Admin AJAX functions
 *
 * @package wp-e-commerce
 * @since 3.7.0
 *
 * @uses update_option()                              Updates option in the database given key and value
 * @uses wp_delete_term()                             Removes term from the database
 * @uses fetch_rss()                                  DEPRECATED
 * @uses wpsc_member_dedeactivate_subscriptions()     @todo docs
 * @uses wpsc_member_deactivate_subscriptions()       @todo docs
 * @uses wpsc_update_purchase_log_status()            Updates the status of the logs for a purchase
 * @uses transaction_results()                        Main function for creating purchase reports
 * @uses wpsc_find_purchlog_status_name()             Finds name of given status
 */
function wpsc_admin_ajax() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;

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

			wpsc_update_purchase_log_status( $_POST['id'], $newvalue );

			if ( ($newvalue > $log_data['processed']) && ($log_data['processed'] < 2) ) {
				transaction_results( $log_data['sessionid'], false );
			}

			$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );
			echo "document.getElementById(\"form_group_" . absint( $_POST['id'] ) . "_text\").innerHTML = '" . $status_name . "';\n";


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

if ( isset( $_REQUEST['ajax'] ) && isset( $_REQUEST['admin'] ) && ($_REQUEST['ajax'] == "true") && ($_REQUEST['admin'] == "true") )
	add_action( 'admin_init', 'wpsc_admin_ajax' );

/**
 * The function that changes the main currency in the DB
 *
 * @uses $wpdb  WordPress database object for queries
 */
function wpsc_change_currency() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;

	if ( is_numeric( $_POST['currencyid'] ) ) {
		$currency_data = $wpdb->get_results( $wpdb->prepare( "SELECT `symbol`,`symbol_html`,`code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`=%d LIMIT 1", $_POST['currencyid'] ), ARRAY_A );
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

/**
 * @todo docs
 * @uses $wpdb  WordPress database object for queries
 */
function wpsc_rearrange_images() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

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
 * @todo docs
 *
 * @uses $wpdb              WordPress database object for queries
 * @uses $wp_rewrite        Global variable instance of the WP_Rewrite Class
 * @uses wp_get_referer()   Retrieve referer from '_wp_http_referer' or HTTP referer.
 * @uses add_query_arg()    Retrieve a modified URL query string.
 * @uses wp_redirect()      Redirects to string given as argument
 */
function wpsc_clean_categories() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb, $wp_rewrite;
	$sql_query = "SELECT `id`, `name`, `active` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "`";
	$sql_data = $wpdb->get_results( $sql_query, ARRAY_A );
	foreach ( (array)$sql_data as $datarow ) {
		if ( $datarow['active'] == 1 ) {
			$tidied_name = strtolower( trim( $datarow['name'] ) );
			$url_name = sanitize_title( $tidied_name );
			$similar_names = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS `count`, MAX(REPLACE(`nice-name`, '%s', '')) AS `max_number` FROM `" . WPSC_TABLE_PRODUCT_CATEGORIES . "` WHERE `nice-name` REGEXP '^( " . esc_sql( $url_name ) . " ){1}(\d)*$' AND `id` NOT IN (%d) ", $url_name, $datarow['id'] ), ARRAY_A );
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

	wp_redirect( esc_url_raw( $sendback ) );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'clean_categories') )
	add_action( 'admin_init', 'wpsc_clean_categories' );
