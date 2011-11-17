<?php

/**
 * WP eCommerce transaction results class
 *
 * This class is responsible for theming the transaction results page.
 *
 * @package wp-e-commerce
 * @since 3.8
 */
function wpsc_transaction_theme() {
	global $wpdb, $user_ID, $nzshpcrt_gateways, $sessionid, $cart_log_id, $errorcode;
	$errorcode = '';
	$transactid = '';
	$dont_show_transaction_results = false;
	if ( isset( $_GET['sessionid'] ) )
		$sessionid = $_GET['sessionid'];

	if ( !isset( $_GET['sessionid'] ) && isset( $_GET['ms'] ) )
		$sessionid = $_GET['ms'];

	if ( isset( $_GET['gateway'] ) && 'google' == $_GET['gateway'] ) {
		wpsc_google_checkout_submit();
		unset( $_SESSION['wpsc_sessionid'] );
	}

	if ( isset( $_SESSION['wpsc_previous_selected_gateway'] ) && in_array( $_SESSION['wpsc_previous_selected_gateway'], array( 'paypal_certified', 'wpsc_merchant_paypal_express' ) ) )
		$sessionid = $_SESSION['paypalexpresssessionid'];

	if ( isset( $_REQUEST['eway'] ) && '1' == $_REQUEST['eway'] )
		$sessionid = $_GET['result'];
	elseif ( isset( $_REQUEST['eway'] ) && '0' == $_REQUEST['eway'] )
		echo $_SESSION['eway_message'];
	elseif ( isset( $_REQUEST['payflow'] ) && '1' == $_REQUEST['payflow'] ){
		echo $_SESSION['payflow_message'];
		$_SESSION['payflow_message'] = '';
	}

	$dont_show_transaction_results = false;

	if ( isset( $_SESSION['wpsc_previous_selected_gateway'] ) ) {
		// Replaces the ugly if else for gateways
		switch($_SESSION['wpsc_previous_selected_gateway']){
			case 'paypal_certified':
			case 'wpsc_merchant_paypal_express':
				echo $_SESSION['paypalExpressMessage'];

				if(isset($_SESSION['reshash']['PAYMENTINFO_0_TRANSACTIONTYPE']) && in_array( $_SESSION['reshash']['PAYMENTINFO_0_TRANSACTIONTYPE'], array( 'expresscheckout', 'cart' ) ) )
					$dont_show_transaction_results = false;
				else
					$dont_show_transaction_results = true;
			break;
			case 'dps':
				$sessionid = decrypt_dps_response();
			break;
		}
	}

	$filtered_output = apply_filters( 'wpsc_transaction_results_page_content', false );
	if ( $filtered_output !== false ) {
		echo $filtered_output;
		return;
	}

	if(!$dont_show_transaction_results ) {
		if ( !empty($sessionid) ){
			$cart_log_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= %s LIMIT 1", $sessionid ) );
			return transaction_results( $sessionid, true );
		}else
		printf( __( 'Sorry your transaction was not accepted.<br /><a href="%1$s">Click here to go back to checkout page</a>.', 'wpsc' ), get_option( "shopping_cart_url" ) );
	}

}


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
	// Do we seriously need this many globals?
	global $wpdb, $wpsc_cart, $echo_to_screen, $purchase_log, $order_url;
	global $message_html, $cart, $errorcode,$wpsc_purchlog_statuses, $wpsc_gateways;

	$wpec_taxes_controller = new wpec_taxes_controller();
	$is_transaction = false;
	$errorcode = 0;
	$purchase_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid`= %s LIMIT 1", $sessionid ), ARRAY_A );
	$order_status = $purchase_log['processed'];
	$curgateway = $purchase_log['gateway'];
	
	if( !is_bool( $display_to_screen )  )
		$display_to_screen = true;

	$echo_to_screen = $display_to_screen;

	if ( is_numeric( $sessionid ) ) {
		if ( $echo_to_screen )
			echo apply_filters( 'wpsc_pre_transaction_results', '' );

		// New code to check whether transaction is processed, true if accepted false if pending or incomplete
		$is_transaction = wpsc_check_purchase_processed($purchase_log['processed']);
		$message_html = $message = stripslashes( get_option( 'wpsc_email_receipt' ) );

		if( $is_transaction ){
			$message = __('The Transaction was successful', 'wpsc')."\r\n".$message;
			$message_html = __('The Transaction was successful', 'wpsc')."<br />".$message_html;
		}
		$country = get_option( 'country_form_field' );
		$billing_country = '';
		$shipping_country = '';
		if ( !empty($purchase_log['shipping_country']) ) {
			$billing_country = $purchase_log['billing_country'];
			$shipping_country = $purchase_log['shipping_country'];
		} elseif (  !empty($country) ) {
			$country = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_log['id'], get_option( 'country_form_field' ) ) );

			$billing_country = $country;
			$shipping_country = $country;
		}

		$email = wpsc_get_buyers_email($purchase_log['id']);
		$previous_download_ids = array( );
		$product_list = $product_list_html = $report_product_list = '';

		$cart = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = %d", $purchase_log['id'] ), ARRAY_A );
		if ( ($cart != null) && ($errorcode == 0) ) {
		    
			foreach ( $cart as $row ) {
				$link = array( );
				$wpdb->update(WPSC_TABLE_DOWNLOAD_STATUS, array('active' => '1'), array('cartid' => $row['id'], 'purchid'=>$purchase_log['id']) );
				do_action( 'wpsc_transaction_result_cart_item', array( "purchase_id" => $purchase_log['id'], "cart_item" => $row, "purchase_log" => $purchase_log ) );

				if ( $is_transaction ) {

					$download_data = $wpdb->get_results( $wpdb->prepare( "SELECT *
					FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "`
					WHERE `active`='1'
					AND `purchid` = %d
					AND `cartid` = %d", $purchase_log['id'], $row['id'] ), ARRAY_A );

					if ( count( $download_data ) > 0 ) {
						foreach ( $download_data as $single_download ) {
							$file_data = get_post( $single_download['product_id'] );
							// if the uniqueid is not equal to null, its "valid", regardless of what it is
							if ( $single_download['uniqueid'] == null )
								$link[] = array( "url" => site_url( "?downloadid=" . $single_download['id'] ), "name" => $file_data->post_title );
							else
								$link[] = array( "url" => site_url( "?downloadid=" . $single_download['uniqueid'] ), "name" => $file_data->post_title );

						}
					} else {
						$order_status = $purchase_log['processed'];
					}
					if( isset( $download_data['id'] ) )
						$previous_download_ids[] = $download_data['id'];
				}

				do_action( 'wpsc_confirm_checkout', $purchase_log['id'] );

				$total = 0;
				$shipping = $row['pnp'];
				$total_shipping += $shipping;

				$total += ( $row['price'] * $row['quantity']);
				$message_price = wpsc_currency_display( $total, array( 'display_as_html' => false ) );
				$message_price_html = wpsc_currency_display( $total );
				$shipping_price = wpsc_currency_display( $shipping, array( 'display_as_html' => false ) );

				if ( isset( $purchase['gateway'] ) && 'wpsc_merchant_testmode' != $purchase['gateway'] ) {
					if ( $gateway['internalname'] == $purch_data[0]['gateway'] )
						$gateway_name = $gateway['name'];
				} else {
					$gateway_name = "Manual Payment";
				}

				$variation_list = '';

				if ( !empty( $link ) ) {
					$additional_content = apply_filters( 'wpsc_transaction_result_content', array( "purchase_id" => $purchase_log['id'], "cart_item" => $row, "purchase_log" => $purchase_log ) );
					if ( !is_string( $additional_content ) ) {
						$additional_content = '';
					}
					$product_list .= " - " . $row['name'] . "  " . $message_price . " " . __( 'Click to download', 'wpsc' ) . ":";
					$product_list_html .= " - " . $row['name'] . "  " . $message_price_html . "&nbsp;&nbsp;" . __( 'Click to download', 'wpsc' ) . ":\n\r";
					foreach ( $link as $single_link ) {
						$product_list .= "\n\r " . $single_link["name"] . ": " . $single_link["url"] . "\n\r";
						$product_list_html .= "<a href='" . $single_link["url"] . "'>" . $single_link["name"] . "</a>\n";
					}
					$product_list .= $additional_content;
					$product_list_html .= $additional_content;
				} else {

					$product_list.= " - " . $row['quantity'] . " " . $row['name'] . "  " . $message_price . "\n\r";
					if ( $shipping > 0 )
						$product_list .= sprintf(__( ' - Shipping: %s
', 'wpsc' ), $shipping_price);
					$product_list_html.= "\n\r - " . $row['quantity'] . " " . $row['name'] . "  " . $message_price_html . "\n\r";
					if ( $shipping > 0 )
						$product_list_html .=  sprintf(__( ' &nbsp; Shipping: %s
', 'wpsc' ), $shipping_price);
				}

				//add tax if included
				if($wpec_taxes_controller->wpec_taxes_isenabled() && $wpec_taxes_controller->wpec_taxes_isincluded())
				{
					$taxes_text = ' - - '.__('Tax Included', 'wpsc').': '.wpsc_currency_display( $row['tax_charged'], array( 'display_as_html' => false ) )."\n\r";
					$taxes_text_html = ' - - '.__('Tax Included', 'wpsc').': '.wpsc_currency_display( $row['tax_charged'] );
					$product_list .= $taxes_text;
					$product_list_html .= $taxes_text_html;
				}// if

				$report = get_option( 'wpsc_email_admin' );
				$report_product_list.= " - " . $row['quantity'] . " " . $row['name'] . "  " . $message_price . "\n\r";
			} // closes foreach cart as row

			// Decrement the stock here
			if ( $is_transaction )
				wpsc_decrement_claimed_stock( $purchase_log['id'] );

			if ( !empty($purchase_log['discount_data'])) {
				$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE coupon_code = %s LIMIT 1", $purchase_log['discount_data'] ), ARRAY_A );


				// check if coupon has a limit to how many times it can be used
				// if it does decriment to get how many uses left and update
				// if its the last time it can be used set is-used to 1
				if ( $coupon_data['use-x-times'] > 0 ) {
					$x_times_left = ($coupon_data['use-x-times'] - 1);
					$is_used = 0;
					$active = 1;
				}

				//if coupon is use once it also need to be set as inactive
				if ( $x_times_left == 0 || $coupon_data['use-once'] == 1) {
						$x_times_left = 0;
						$is_used = 1;
						$active = 0;
				}

				$wpdb->update(WPSC_TABLE_COUPON_CODES, array('use-x-times' => $x_times_left , 'is-used' => $is_used, 'active' => $active), array('id' => $coupon_data['id']) );
			}
			
			$total_shipping = $wpsc_cart->calculate_total_shipping();
			$total = $wpsc_cart->calculate_total_price();

			$total_price_email = '';
			$total_price_html = '';
			$total_tax_html = '';
			$total_tax = '';
			$total_shipping_html = '';
			$total_shipping_email = '';
			if ( wpsc_uses_shipping() )
				$total_shipping_email.= sprintf(__( 'Total Shipping: %s
	', 'wpsc' ), wpsc_currency_display( $total_shipping, array( 'display_as_html' => false ) ) );
			$total_price_email.= sprintf(__( 'Total: %s
', 'wpsc' ), wpsc_currency_display( $total, array( 'display_as_html' => false ) ));
			if ( $purchase_log['discount_value'] > 0 ) {
				$discount_email = __( 'Discount', 'wpsc' ) . "\n\r: ";
				$discount_email .=$purchase_log['discount_data'] . ' : ' . wpsc_currency_display( $purchase_log['discount_value'], array( 'display_as_html' => false ) ) . "\n\r";

				$report.= $discount_email . "\n\r";
				$total_shipping_email .= $discount_email;
				$total_shipping_html.= __( 'Discount', 'wpsc' ) . ": " . wpsc_currency_display( $purchase_log['discount_value'] ) . "\n\r";
			}

			//only show total tax if tax is not included
			if($wpec_taxes_controller->wpec_taxes_isenabled() && !$wpec_taxes_controller->wpec_taxes_isincluded()){
				$total_tax_html .= __('Total Tax', 'wpsc').': '. wpsc_currency_display( $purchase_log['wpec_taxes_total'] )."\n\r";
				$total_tax .= __('Total Tax', 'wpsc').': '. wpsc_currency_display( $purchase_log['wpec_taxes_total'] , array( 'display_as_html' => false ) )."\n\r";
			}
			if ( wpsc_uses_shipping() )
				$total_shipping_html.= '<hr>' . sprintf(__( 'Total Shipping: %s
	', 'wpsc' ), wpsc_currency_display( $total_shipping ));
			$total_price_html.= sprintf(__( 'Total: %s
', 'wpsc' ), wpsc_currency_display( $total ) );
			$report_id = sprintf(__("Purchase # %s
", 'wpsc'), $purchase_log['id']);

			if ( isset( $_GET['ti'] ) ) {
				$message.= "\n\r" . __( 'Your Transaction ID', 'wpsc' ) . ": " . $_GET['ti'];
				$message_html.= "\n\r" . __( 'Your Transaction ID', 'wpsc' ) . ": " . $_GET['ti'];
				$report.= "\n\r" . __( 'Transaction ID', 'wpsc' ) . ": " . $_GET['ti'];
			}
			$message = apply_filters( 'wpsc_transaction_result_message', $message );
			$message = str_replace( '%purchase_id%', $report_id, $message );
			$message = str_replace( '%product_list%', $product_list, $message );
			$message = str_replace( '%total_tax%', $total_tax, $message );
			$message = str_replace( '%total_shipping%', $total_shipping_email, $message );
			$message = str_replace( '%total_price%', $total_price_email, $message );
			$message = str_replace( '%shop_name%', get_option( 'blogname' ), $message );
			$message = str_replace( '%find_us%', $purchase_log['find_us'], $message );

			$report = apply_filters( 'wpsc_transaction_result_report', $report );
			$report = str_replace( '%purchase_id%', $report_id, $report );
			$report = str_replace( '%product_list%', $report_product_list, $report );
			$report = str_replace( '%total_tax%', $total_tax, $report );
			$report = str_replace( '%total_shipping%', $total_shipping_email, $report );
			$report = str_replace( '%total_price%', $total_price_email, $report );
			$report = str_replace( '%shop_name%', get_option( 'blogname' ), $report );
			$report = str_replace( '%find_us%', $purchase_log['find_us'], $report );

			$message_html = apply_filters( 'wpsc_transaction_result_message_html', $message_html );
			$message_html = str_replace( '%purchase_id%', $report_id, $message_html );
			$message_html = str_replace( '%product_list%', $product_list_html, $message_html );
			$message_html = str_replace( '%total_tax%', $total_tax_html, $message_html );
			$message_html = str_replace( '%total_shipping%', $total_shipping_html, $message_html );
			$message_html = str_replace( '%total_price%', $total_price_html, $message_html );
			$message_html = str_replace( '%shop_name%', get_option( 'blogname' ), $message_html );
			$message_html = str_replace( '%find_us%', $purchase_log['find_us'], $message_html );

			if ( !empty($email) ) {
				add_filter( 'wp_mail_from', 'wpsc_replace_reply_address', 0 );
				add_filter( 'wp_mail_from_name', 'wpsc_replace_reply_name', 0 );
				$message = apply_filters('wpsc_email_message', $message, $report_id, $product_list, $total_tax, $total_shipping_email, $total_price_email);


				//new variable to check whether function is being called from wpsc_purchlog_resend_email()
				$resend_email = isset( $_REQUEST['email_buyer_id'] ) ? true : false;
				
				if ( ! $is_transaction ) {

					$payment_instructions = strip_tags( stripslashes( get_option( 'payment_instructions' ) ) );
					if(!empty($payment_instructions))
						$payment_instructions .= "\n\r";
					$message = __( 'Thank you, your purchase is pending, you will be sent an email once the order clears.', 'wpsc' ) . "\n\r" . $payment_instructions . $message;
					$message_html = __( 'Thank you, your purchase is pending, you will be sent an email once the order clears.', 'wpsc' ) . "\n\r" . $payment_instructions . $message_html;
					
					// prevent email duplicates
				if ( ! get_transient( "{$sessionid}_pending_email_sent" ) || $resend_email ) {
						wp_mail( $email, __( 'Order Pending: Payment Required', 'wpsc' ), $message );
						set_transient( "{$sessionid}_pending_email_sent", true, 60 * 60 * 12 );
					}
				} elseif ( ! get_transient( "{$sessionid}_receipt_email_sent" ) || $resend_email ) {
					wp_mail( $email, __( 'Purchase Receipt', 'wpsc' ), $message );
					set_transient( "{$sessionid}_receipt_email_sent", true, 60 * 60 * 12 );
				}
			}

			remove_filter( 'wp_mail_from_name', 'wpsc_replace_reply_name' );
			remove_filter( 'wp_mail_from', 'wpsc_replace_reply_address' );

			$report_user = __( 'Customer Details', 'wpsc' ) . "\n\r";
			$form_sql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` = %d", $purchase_log['id'] );
			$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

			if ( $form_data != null ) {
				foreach ( $form_data as $form_field ) {
					$form_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `id` = %d LIMIT 1", $form_field['form_id'] ), ARRAY_A );

					switch ( $form_data['type'] ) {
						case "country":
							$country_code = $form_field['value'];
							$report_user .= $form_data['name'] . ": " . wpsc_get_country( $country_code ) . "\n";
							//check if country has a state then display if it does.
							$country_data = wpsc_country_has_state($country_code);
							if(($country_data['has_regions'] == 1))
								$report_user .= __( 'Billing State', 'wpsc' ) . ": " . wpsc_get_region( $purchase_log['billing_region'] ) . "\n";
							break;

						case "delivery_country":
							$report_user .= $form_data['name'] . ": " . wpsc_get_country( $form_field['value'] ) . "\n";
							break;

						default:
							if ($form_data['name'] == 'State' && is_numeric($form_field['value'])){
								$report_user .= __( 'Delivery State', 'wpsc' ) . ": " . wpsc_get_state_by_id( $form_field['value'], 'name' ) . "\n";
							}else{
							$report_user .= wp_kses( $form_data['name'], array( ) ) . ": " . $form_field['value'] . "\n";
							}
							break;
					}
				}
			}

			$report_user .= "\n\r";
			$report = $report_id . $report_user . $report;

			//echo '======REPORT======<br />'.$report.'<br />';
			//echo '======EMAIL======<br />'.$message.'<br />';
			if ( (get_option( 'purch_log_email' ) != null) && ( $purchase_log['email_sent'] != 1 ) ){
				wp_mail( get_option( 'purch_log_email' ), __( 'Purchase Report', 'wpsc' ), $report );
				$wpdb->update(WPSC_TABLE_PURCHASE_LOGS, array('email_sent' => '1'), array( 'sessionid' => $sessionid ) );
			}

			/// Adjust stock and empty the cart
			$wpsc_cart->submit_stock_claims( $purchase_log['id'] );
			$wpsc_cart->empty_cart();
		}
	}
}

?>