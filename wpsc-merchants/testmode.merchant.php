<?php

/**
	* WP eCommerce Test Merchant Gateway
	* This is the file for the test merchant gateway
	*
	* @package wp-e-comemrce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
$nzshpcrt_gateways[$num] = array(
	'name' => 'Test Gateway',
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_testmode',
	'has_recurring_billing' => true,
	'display_name' => 'Manual Payment',	
	'wp_admin_cannot_cancel' => false,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		///'php_version' => 5.0,
	),
	
	'form' => 'form_testmode',
	
	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_testmode',
);

class wpsc_merchant_testmode extends wpsc_merchant {
	
	var $name = 'Test Gateway';
	
	function submit() {
		$this->set_purchase_processed_by_purchid(2);
	 	$this->go_to_transaction_results($this->cart_data['session_id']);
	
	 	exit();
		
	}
}

function form_testmode() {
	$output = "<tr>\n\r";
	$output .= "	<td colspan='2'>\n\r";
	
	$output .= "<strong>".__('Enter the payment instructions that you wish to display to your customers when they make a purchase', 'wpsc').":</strong><br />\n\r";
	$output .= "<textarea cols='40' rows='9' name='wpsc_options[payment_instructions]'>".stripslashes(get_option('payment_instructions'))."</textarea><br />\n\r";
	$output .= "<em>".__('For example, this is where you the Shop Owner might enter your bank account details or address so that your customer can make their manual payment.', 'wpsc')."</em>\n\r";
	$output .= "	</td>\n\r";
	$output .= "</tr>\n\r";
	
	return $output;
}