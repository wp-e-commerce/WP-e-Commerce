<?php

/**
 * transaction_results function main function for creating the purchase reports, transaction results page, and email receipts
 * @access public
 *
 * @since 3.7
 * @param $sessionid (string) unique session id
 * @param echo_to_screen (boolean) whether to output the results or return them (potentially redundant)
 * @param $transaction_id (int) the transaction id
 */
function transaction_results( $sessionid, $display_to_screen = true, $transaction_id = null ) {
	global $message_html, $echo_to_screen, $wpsc_cart, $purchase_log;

	// pre-3.8.9 variable
	$echo_to_screen = $display_to_screen;

	$purchase_log_object = new WPSC_Purchase_Log( $sessionid, 'sessionid' );

	// compatibility with pre-3.8.9 templates where they use a global
	// $purchase_log object which is simply just a database row
	$purchase_log = $purchase_log_object->get_data();

	// pre-3.8.9 templates also use this global variable
	$message_html = wpsc_get_transaction_html_output( $purchase_log_object );

	$wpsc_cart->empty_cart();

	do_action( 'wpsc_transaction_results_shutdown', $purchase_log_object, $sessionid, $display_to_screen );

	return $message_html;
}

function wpsc_transaction_html_output() {
	global $message_html;
	echo $message_html;
}

function wpsc_transaction_theme() {
	global $wpdb, $user_ID, $nzshpcrt_gateways, $sessionid, $cart_log_id, $errorcode;
	$errorcode = '';
	$transactid = '';
	$dont_show_transaction_results = false;
	if ( isset( $_GET['sessionid'] ) )
		$sessionid = $_GET['sessionid'];

	if ( !isset( $_GET['sessionid'] ) && isset( $_GET['ms'] ) )
		$sessionid = $_GET['ms'];

	$selected_gateway = wpsc_get_customer_meta( 'selected_gateway' );
	if ( $selected_gateway && in_array( $selected_gateway, array( 'paypal_certified', 'wpsc_merchant_paypal_express' ) ) )
		$sessionid = wpsc_get_customer_meta( 'paypal_express_sessionid' );

	if ( isset( $_REQUEST['eway'] ) && '1' == $_REQUEST['eway'] )
		$sessionid = $_GET['result'];
	elseif ( isset( $_REQUEST['eway'] ) && '0' == $_REQUEST['eway'] )
		echo wpsc_get_customer_meta( 'eway_message' );
	elseif ( isset( $_REQUEST['payflow'] ) && '1' == $_REQUEST['payflow'] ){
		echo wpsc_get_customer_meta( 'payflow_message' );
		wpsc_delete_customer_meta( 'payflow_message' );
	}

	$dont_show_transaction_results = false;

	if ( $selected_gateway ) {
		// Replaces the ugly if else for gateways
		switch( $selected_gateway ){
			case 'paypal_certified':
			case 'wpsc_merchant_paypal_express':
				echo wpsc_get_customer_meta( 'paypal_express_message' );

				$reshash = wpsc_get_customer_meta( 'paypal_express_reshash' );
				if( isset( $reshash['PAYMENTINFO_0_TRANSACTIONTYPE'] ) && in_array( $reshash['PAYMENTINFO_0_TRANSACTIONTYPE'], array( 'expresscheckout', 'cart' ) ) )
					$dont_show_transaction_results = false;
				else
					$dont_show_transaction_results = true;
			break;
			case 'dps':
				$sessionid = decrypt_dps_response();
			break;
					//paystation was not updating the purchase logs for successful payment - this is ugly as need to have the databse update done in one place by all gatways on a sucsessful transaction hook not some within the gateway and some within here and some not at all??? This is getting a major overhaul but for here and now it just needs to work for the gold cart people!
			case 'paystation':
				$ec = $_GET['ec'];
				$result= $_GET['em'];

				if($result == 'Transaction successful' && $ec == 0)
						$processed_id = '3';

				if($result == 'Insufficient Funds' && $ec == 5){
					$processed_id = '6';

					$payment_instructions = printf( __( 'Sorry your transaction was not accepted due to insufficient funds <br /><a href="%1$s">Click here to go back to checkout page</a>.', 'wpsc' ), get_option( "shopping_cart_url" ) );
				}
				if ( $processed_id )
					wpsc_update_purchase_log_status( $sessionid, $processed_id, 'sessionid' );

			break;
			case 'wpsc_merchant_paymentexpress' :
               // Payment Express sends back there own session id, which is temporarily stored in the Auth field
               // so just swapping that over here
               $result = $wpdb->get_var( $wpdb->prepare( "SELECT `sessionid` FROM  `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `authcode` = %s", $sessionid ) );
               if($result != null){
                   // just in case they are using an older version old gold cart (pre 2.9.5)
                   $sessionid = $result;
                   $dont_show_transaction_results = true;
               }
           break;
           case 'eway_hosted':
               $sessionid = decrypt_eway_uk_response();
           break;
           //default filter for other payment gateways to use
		   default:
           		$sessionid = apply_filters('wpsc_previous_selected_gateway_' . $selected_gateway, $sessionid);
           break;
 		}
 	}

	if( ! $dont_show_transaction_results ) {
		if ( !empty($sessionid) ){
			$cart_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= %s LIMIT 1", $sessionid ) );
			return transaction_results( $sessionid, true );
		}else
		printf( __( 'Sorry your transaction was not accepted.<br /><a href="%1$s">Click here to go back to checkout page</a>.', 'wpsc' ), get_option( "shopping_cart_url" ) );
	}
}