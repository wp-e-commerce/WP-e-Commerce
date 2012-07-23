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

