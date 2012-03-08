<?php
/**
 * This is the PayPal Payments Standard 2.0 Gateway.
 * It uses the wpsc_merchant class as a base class which is handy for collating user details and cart contents.
 */

 /*
  * This is the gateway variable $nzshpcrt_gateways, it is used for displaying gateway information on the wp-admin pages and also
  * for internal operations.
  */
$nzshpcrt_gateways[$num] = array(
	'name' => 'PayPal Payments Standard 2.0',
	'api_version' => 2.0,
	'image' => WPSC_URL . '/images/paypal.gif',
	'class_name' => 'wpsc_merchant_paypal_standard',
	'has_recurring_billing' => true,
	'wp_admin_cannot_cancel' => true,
	'display_name' => 'PayPal Payments Standard',
	'requirements' => array(
		/// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		'php_version' => 4.3,
		 /// for modules that may not be present, like curl
		'extra_modules' => array()
	),

	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_paypal_standard',

	// All array members below here are legacy, and use the code in paypal_multiple.php
	'form' => 'form_paypal_multiple',
	'submit_function' => 'submit_paypal_multiple',
	'payment_type' => 'paypal',
	'supported_currencies' => array(
		'currency_list' =>  array('AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TWD', 'USD'),
		'option_name' => 'paypal_curcode'
	)
);



/**
	* WP eCommerce PayPal Standard Merchant Class
	*
	* This is the paypal standard merchant class, it extends the base merchant class
	*
	* @package wp-e-commerce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
class wpsc_merchant_paypal_standard extends wpsc_merchant {
  var $name = 'PayPal Payments Standard';
  var $paypal_ipn_values = array();

	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access public
	*/
	function construct_value_array() {
		$this->collected_gateway_data = $this->_construct_value_array();
	}

	function convert( $amt ){
		if ( empty( $this->rate ) ) {
			$this->rate = 1;
			$paypal_currency_code = $this->get_paypal_currency_code();
			$local_currency_code = $this->get_local_currency_code();
			if( $local_currency_code != $paypal_currency_code ) {
				$curr=new CURRENCYCONVERTER();
				$this->rate = $curr->convert( 1, $paypal_currency_code, $local_currency_code );
			}
		}
		return $this->format_price( $amt / $this->rate );
	}

	function get_local_currency_code() {
		if ( empty( $this->local_currency_code ) ) {
			global $wpdb;
			$this->local_currency_code = $wpdb->get_var( $wpdb->prepare( "SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id` = %d  LIMIT 1", get_option( 'currency_type' ) ) );
		}

		return $this->local_currency_code;
	}

	function get_paypal_currency_code() {
		if ( empty( $this->paypal_currency_code ) ) {
			global $wpsc_gateways;
			$this->paypal_currency_code = $this->get_local_currency_code();

			if ( ! in_array( $this->paypal_currency_code, $wpsc_gateways['wpsc_merchant_paypal_standard']['supported_currencies']['currency_list'] ) )
				$this->paypal_currency_code = get_option( 'paypal_curcode', 'USD' );
		}

		return $this->paypal_currency_code;
	}

	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access private
	* @param boolean $aggregate Whether to aggregate the cart data or not. Defaults to false.
	* @return array $paypal_vars The paypal vars
	*/
	function _construct_value_array($aggregate = false) {
		global $wpdb;
		$paypal_vars = array();
		$add_tax = ! wpsc_tax_isincluded();

		// Store settings to be sent to paypal
		$paypal_vars += array(
			'business' => get_option('paypal_multiple_business'),
			'return' => add_query_arg('sessionid', $this->cart_data['session_id'], $this->cart_data['transaction_results_url']),
			'cancel_return' => $this->cart_data['transaction_results_url'],
			'rm' => '2',
			'currency_code' => $this->get_paypal_currency_code(),
			'lc' => $this->cart_data['store_currency'],
			'bn' => $this->cart_data['software_name'],

			'no_note' => '1',
			'charset' => 'utf-8',
		);

		// IPN data
		if (get_option('paypal_ipn') == 1) {
			$notify_url = $this->cart_data['notification_url'];
			$notify_url = add_query_arg('gateway', 'wpsc_merchant_paypal_standard', $notify_url);
			$notify_url = apply_filters('wpsc_paypal_standard_notify_url', $notify_url);
			$paypal_vars += array(
				'notify_url' => $notify_url,
			);
		}

		// Shipping
		if ((bool) get_option('paypal_ship')) {
			$paypal_vars += array(
				'address_override' => '1',
				'no_shipping' => '0',
			);
		}

		// Customer details
		$paypal_vars += array(
			'email' => $this->cart_data['email_address'],
			'first_name' => $this->cart_data['shipping_address']['first_name'],
			'last_name' => $this->cart_data['shipping_address']['last_name'],
			'address1' => $this->cart_data['shipping_address']['address'],
			'city' => $this->cart_data['shipping_address']['city'],
			'country' => $this->cart_data['shipping_address']['country'],
			'zip' => $this->cart_data['shipping_address']['post_code'],
			'state' => $this->cart_data['shipping_address']['state'],
		);

		if ( $paypal_vars['country'] == 'UK' ) {
			$paypal_vars['country'] = 'GB';
		}

		// Order settings to be sent to paypal
		$paypal_vars += array(
			'invoice' => $this->cart_data['session_id']
		);

		// Two cases:
		// - We're dealing with a subscription
		// - We're dealing with a normal cart
		if ($this->cart_data['is_subscription']) {
			$paypal_vars += array(
				'cmd'=> '_xclick-subscriptions',
			);

			$reprocessed_cart_data['shopping_cart'] = array(
				'is_used' => false,
				'price' => 0,
				'length' => 1,
				'unit' => 'd',
				'times_to_rebill' => 1,
			);

			$reprocessed_cart_data['subscription'] = array(
				'is_used' => false,
				'price' => 0,
				'length' => 1,
				'unit' => 'D',
				'times_to_rebill' => 1,
			);

			foreach ($this->cart_items as $cart_row) {
				if ($cart_row['is_recurring']) {
					$reprocessed_cart_data['subscription']['is_used'] = true;
					$reprocessed_cart_data['subscription']['price'] = $this->convert( $cart_row['price'] );
					$reprocessed_cart_data['subscription']['length'] = $cart_row['recurring_data']['rebill_interval']['length'];
					$reprocessed_cart_data['subscription']['unit'] = strtoupper($cart_row['recurring_data']['rebill_interval']['unit']);
					$reprocessed_cart_data['subscription']['times_to_rebill'] = $cart_row['recurring_data']['times_to_rebill'];
				} else {
					$item_cost = ($cart_row['price'] + $cart_row['shipping'] + $cart_row['tax']) * $cart_row['quantity'];

					if ($item_cost > 0) {
						$reprocessed_cart_data['shopping_cart']['price'] += $item_cost;
						$reprocessed_cart_data['shopping_cart']['is_used'] = true;
					}
				}

				$paypal_vars += array(
					'item_name' => __('Your Subscription', 'wpsc'),
					// I fail to see the point of sending a subscription to paypal as a subscription
					// if it does not recur, if (src == 0) then (this == underfeatured waste of time)
					'src' => '1'
				);

				// This can be false, we don't need to have additional items in the cart/
				if ($reprocessed_cart_data['shopping_cart']['is_used']) {
					$paypal_vars += array(
						"a1" => $this->convert($reprocessed_cart_data['shopping_cart']['price']),
						"p1" => $reprocessed_cart_data['shopping_cart']['length'],
						"t1" => $reprocessed_cart_data['shopping_cart']['unit'],
					);
				}

				// We need at least one subscription product,
				// If this is not true, something is rather wrong.
				if ($reprocessed_cart_data['subscription']['is_used']) {
					$paypal_vars += array(
						"a3" => $this->convert($reprocessed_cart_data['subscription']['price']),
						"p3" => $reprocessed_cart_data['subscription']['length'],
						"t3" => $reprocessed_cart_data['subscription']['unit'],
					);

					// If the srt value for the number of times to rebill is not greater than 1,
					// paypal won't accept the transaction.
					if ($reprocessed_cart_data['subscription']['times_to_rebill'] > 1) {
						$paypal_vars += array(
							'srt' => $reprocessed_cart_data['subscription']['times_to_rebill'],
						);
					}
				}
			} // end foreach cart item
		} else {
			$paypal_vars += array(
				'upload' => '1',
				'cmd' => '_ext-enter',
				'redirect_cmd' => '_cart',
			);

			$free_shipping = false;
			if ( isset( $_SESSION['coupon_numbers'] ) ) {
				$coupon = new wpsc_coupons( $_SESSION['coupon_numbers'] );
				$free_shipping = $coupon->is_percentage == '2';
			}

			if ( $this->cart_data['has_discounts'] && $free_shipping )
				$handling = 0;
			else
				$handling = $this->cart_data['base_shipping'];

			$tax_total = 0;
			if ( $add_tax )
				$tax_total = $this->cart_data['cart_tax'];

			// Set base shipping
			$paypal_vars += array(
				'handling_cart' => $this->convert( $handling )
			);

			// Stick the cart item values together here
			$i = 1;

			if (!$aggregate) {
				foreach ($this->cart_items as $cart_row) {
					$paypal_vars += array(
						"item_name_$i" => $cart_row['name'],
						"amount_$i" => $this->convert($cart_row['price']),
						"quantity_$i" => $cart_row['quantity'],
						"item_number_$i" => $cart_row['product_id'],
						// additional shipping for the the (first item / total of the items)
						"shipping_$i" => $this->convert($cart_row['shipping']/ $cart_row['quantity'] ),
						// additional shipping beyond the first item
						"shipping2_$i" => $this->convert($cart_row['shipping']/ $cart_row['quantity'] ),
						"handling_$i" => '',
					);
					if ( $add_tax && ! empty( $cart_row['tax'] ) )
						$tax_total += $cart_row['tax'];
					++$i;
				}
				if ( $this->cart_data['has_discounts'] && ! $free_shipping )
					$paypal_vars['discount_amount_cart'] = $this->convert( $this->cart_data['cart_discount_value'] );
			} else {
				$paypal_vars['item_name_'.$i] = "Your Shopping Cart";
				$paypal_vars['amount_'.$i] = $this->convert( $this->cart_data['total_price'] ) - $this->convert( $this->cart_data['base_shipping'] );
				$paypal_vars['quantity_'.$i] = 1;
				$paypal_vars['shipping_'.$i] = 0;
				$paypal_vars['shipping2_'.$i] = 0;
				$paypal_vars['handling_'.$i] = 0;
			}

			$paypal_vars['tax_cart'] = $this->convert( $tax_total );
		}
		return apply_filters( 'wpsc_paypal_standard_post_data', $paypal_vars );
	}

	/**
	* submit method, sends the received data to the payment gateway
	* @access public
	*/
	function submit() {
		$name_value_pairs = array();
		foreach ($this->collected_gateway_data as $key => $value) {
			$name_value_pairs[] = $key . '=' . urlencode($value);
		}
		$gateway_values =  implode('&', $name_value_pairs);

		$redirect = get_option('paypal_multiple_url')."?".$gateway_values;
		// URLs up to 2083 characters long are short enough for an HTTP GET in all browsers.
		// Longer URLs require us to send aggregate cart data to PayPal short of losing data.
		// An exception is made for recurring transactions, since there isn't much we can do.
		if (strlen($redirect) > 2083 && !$this->cart_data['is_subscription']) {
			$name_value_pairs = array();
			foreach($this->_construct_value_array(true) as $key => $value) {
				$name_value_pairs[]= $key . '=' . urlencode($value);
			}
			$gateway_values =  implode('&', $name_value_pairs);

			$redirect = get_option('paypal_multiple_url')."?".$gateway_values;
		}

		if (defined('WPSC_ADD_DEBUG_PAGE') && WPSC_ADD_DEBUG_PAGE) {
			echo "<a href='".esc_url($redirect)."'>Test the URL here</a>";
			echo "<pre>".print_r($this->collected_gateway_data,true)."</pre>";
			exit();
		} else {
			wp_redirect($redirect);
			exit();
		}
	}


	/**
	* parse_gateway_notification method, receives data from the payment gateway
	* @access private
	*/
	function parse_gateway_notification() {
		/// PayPal first expects the IPN variables to be returned to it within 30 seconds, so we do this first.
		$paypal_url = get_option('paypal_multiple_url');
		$received_values = array();
		$received_values['cmd'] = '_notify-validate';
  		$received_values += stripslashes_deep ($_POST);
		$options = array(
			'timeout' => 5,
			'body' => $received_values,
			'user-agent' => ('WP e-Commerce/'.WPSC_PRESENTABLE_VERSION)
		);

		$response = wp_remote_post($paypal_url, $options);
		if( 'VERIFIED' == $response['body'] ) {
			$this->paypal_ipn_values = $received_values;
			$this->session_id = $received_values['invoice'];
		} else {
			exit("IPN Request Failure");
		}
	}

	/**
	* process_gateway_notification method, receives data from the payment gateway
	* @access public
	*/
	function process_gateway_notification() {
		$status = false;
		switch ( strtolower( $this->paypal_ipn_values['payment_status'] ) ) {
			case 'pending':
				$status = 2;
				break;
			case 'completed':
				$status = 3;
				break;
			case 'denied':
				$status = 6;
				break;
		}

		do_action( 'wpsc_paypal_standard_ipn', $this->paypal_ipn_values, $this );
		$paypal_email = strtolower( get_option( 'paypal_multiple_business' ) );

	  // Compare the received store owner email address to the set one
		if( strtolower( $this->paypal_ipn_values['receiver_email'] ) == $paypal_email || strtolower( $this->paypal_ipn_values['business'] ) == $paypal_email ) {
			switch($this->paypal_ipn_values['txn_type']) {
				case 'cart':
				case 'express_checkout':
					if ( $status )
						$this->set_transaction_details( $this->paypal_ipn_values['txn_id'], $status );
					if ( in_array( $status, array( 2, 3 ) ) )
						transaction_results($this->cart_data['session_id'],false);
				break;

				case 'subscr_signup':
				case 'subscr_payment':
					if ( in_array( $status, array( 2, 3 ) ) ) {
						$this->set_transaction_details( $this->paypal_ipn_values['subscr_id'], $status );
						transaction_results($this->cart_data['session_id'],false);
					}
					foreach($this->cart_items as $cart_row) {
						if($cart_row['is_recurring'] == true) {
							do_action('wpsc_activate_subscription', $cart_row['cart_item_id'], $this->paypal_ipn_values['subscr_id']);
						}
					}
				break;

				case 'subscr_cancel':
				case 'subscr_eot':
				case 'subscr_failed':
					foreach($this->cart_items as $cart_row) {
						$altered_count = 0;
						if((bool)$cart_row['is_recurring'] == true) {
							$altered_count++;
							wpsc_update_cartmeta($cart_row['cart_item_id'], 'is_subscribed', 0);
						}
					}
				break;

				default:
				break;
			}
		}

		$message = "
		{$this->paypal_ipn_values['receiver_email']} => ".get_option('paypal_multiple_business')."
		{$this->paypal_ipn_values['txn_type']}
		{$this->paypal_ipn_values['mc_gross']} => {$this->cart_data['total_price']}
		{$this->paypal_ipn_values['txn_id']}

		".print_r($this->cart_items, true)."
		{$altered_count}
		";
	}



	function format_price($price, $paypal_currency_code = null) {
		if (!isset($paypal_currency_code)) {
			$paypal_currency_code = get_option('paypal_curcode');
		}
		switch($paypal_currency_code) {
			case "JPY":
			$decimal_places = 0;
			break;

			case "HUF":
			$decimal_places = 0;

			default:
			$decimal_places = 2;
			break;
		}
		$price = number_format(sprintf("%01.2f",$price),$decimal_places,'.','');
		return $price;
	}
}


/**
 * submit_paypal_multiple function.
 *
 * Use this for now, but it will eventually be replaced with a better form API for gateways
 * @access public
 * @return void
 */
function submit_paypal_multiple(){
  if(isset($_POST['paypal_multiple_business'])) {
    update_option('paypal_multiple_business', $_POST['paypal_multiple_business']);
	}

  if(isset($_POST['paypal_multiple_url'])) {
    update_option('paypal_multiple_url', $_POST['paypal_multiple_url']);
	}

  if(isset($_POST['paypal_curcode'])) {
    update_option('paypal_curcode', $_POST['paypal_curcode']);
	}

  if(isset($_POST['paypal_curcode'])) {
    update_option('paypal_curcode', $_POST['paypal_curcode']);
	}

  if(isset($_POST['paypal_ipn'])) {
    update_option('paypal_ipn', (int)$_POST['paypal_ipn']);
	}

  if(isset($_POST['address_override'])) {
    update_option('address_override', (int)$_POST['address_override']);
	}
  if(isset($_POST['paypal_ship'])) {
    update_option('paypal_ship', (int)$_POST['paypal_ship']);
	}

  if (!isset($_POST['paypal_form'])) $_POST['paypal_form'] = array();
  foreach((array)$_POST['paypal_form'] as $form => $value) {
    update_option(('paypal_form_'.$form), $value);
	}

  return true;
}



/**
 * form_paypal_multiple function.
 *
 * Use this for now, but it will eventually be replaced with a better form API for gateways
 * @access public
 * @return void
 */
function form_paypal_multiple() {
  global $wpdb, $wpsc_gateways;

  $account_type = get_option( 'paypal_multiple_url' );
  $account_types = array(
  	'https://www.paypal.com/cgi-bin/webscr' => __( 'Live Account', 'wpsc' ),
  	'https://www.sandbox.paypal.com/cgi-bin/webscr' => __( 'Sandbox Account', 'wpsc' ),
  );

  $output = "
  <tr>
      <td>" . __( 'Username:', 'wpsc' ) . "
      </td>
      <td>
      <input type='text' size='40' value='".get_option('paypal_multiple_business')."' name='paypal_multiple_business' />
      </td>
  </tr>
  <tr>
  	<td></td>
  	<td colspan='1'>
  	<span  class='wpscsmall description'>
  	" . __( 'This is your PayPal email address.', 'wpsc' ) . "
  	</span>
  	</td>
  </tr>

  <tr>
      <td>" . __( 'Account Type:', 'wpsc' ) . "
      </td>
      <td>
		<select name='paypal_multiple_url'>";

  foreach ( $account_types as $url => $label ) {
  	$output .= "<option value='{$url}' ". selected( $url, $account_type, false ) .">" . esc_html( $label ) . "</option>";
  }

  $output .= "</select>
	   </td>
  </tr>
  <tr>
	 <td colspan='1'>
	 </td>
	 <td>
		<span  class='wpscsmall description'>
  			" . __( 'If you have a PayPal developers Sandbox account please use Sandbox mode, if you just have a standard PayPal account then you will want to use Live mode.', 'wpsc' ) . "
  		</span>
  	  </td>
  </tr>";


	$paypal_ipn = get_option('paypal_ipn');
	$paypal_ipn1 = "";
	$paypal_ipn2 = "";
	switch($paypal_ipn) {
		case 0:
		$paypal_ipn2 = "checked ='checked'";
		break;

		case 1:
		$paypal_ipn1 = "checked ='checked'";
		break;
	}
	$paypal_ship = get_option('paypal_ship');
	$paypal_ship1 = "";
	$paypal_ship2 = "";
	switch($paypal_ship){
		case 1:
		$paypal_ship1 = "checked='checked'";
		break;

		case 0:
		default:
		$paypal_ship2 = "checked='checked'";
		break;

	}
	$address_override = get_option('address_override');
	$address_override1 = "";
	$address_override2 = "";
	switch($address_override) {
		case 1:
		$address_override1 = "checked ='checked'";
		break;

		case 0:
		default:
		$address_override2 = "checked ='checked'";
		break;
	}
	$output .= "
   <tr>
     <td>IPN :
     </td>
     <td>
       <input type='radio' value='1' name='paypal_ipn' id='paypal_ipn1' ".$paypal_ipn1." /> <label for='paypal_ipn1'>".__('Yes', 'wpsc')."</label> &nbsp;
       <input type='radio' value='0' name='paypal_ipn' id='paypal_ipn2' ".$paypal_ipn2." /> <label for='paypal_ipn2'>".__('No', 'wpsc')."</label>
     </td>
  </tr>
  <tr>
  	<td colspan='2'>
  	<span  class='wpscsmall description'>
  	IPN (instant payment notification ) will automatically update your sales logs to 'Accepted payment' when a customers payment is successful. For IPN to work you also need to have IPN turned on in your Paypal settings. If it is not turned on, the sales sill remain as 'Order Pending' status until manually changed. It is highly recommend using IPN, especially if you are selling digital products.
  	</span>
  	</td>
  </tr>
  <tr>
     <td style='padding-bottom: 0px;'>Send shipping details:
     </td>
     <td style='padding-bottom: 0px;'>
       <input type='radio' value='1' name='paypal_ship' id='paypal_ship1' ".$paypal_ship1." /> <label for='paypal_ship1'>".__('Yes', 'wpsc')."</label> &nbsp;
       <input type='radio' value='0' name='paypal_ship' id='paypal_ship2' ".$paypal_ship2." /> <label for='paypal_ship2'>".__('No', 'wpsc')."</label>

  	</td>
  </tr>
  <tr>
  	<td colspan='2'>
  	<span  class='wpscsmall description'>
  	Note: If your checkout page does not have a shipping details section, or if you don't want to send Paypal shipping information. You should change Send shipping details option to No.</span>
  	</td>
  </tr>
  <tr>
     <td style='padding-bottom: 0px;'>
      Address Override:
     </td>
     <td style='padding-bottom: 0px;'>
       <input type='radio' value='1' name='address_override' id='address_override1' ".$address_override1." /> <label for='address_override1'>".__('Yes', 'wpsc')."</label> &nbsp;
       <input type='radio' value='0' name='address_override' id='address_override2' ".$address_override2." /> <label for='address_override2'>".__('No', 'wpsc')."</label>
     </td>
   </tr>
   <tr>
  	<td colspan='2'>
  	<span  class='wpscsmall description'>
  	This setting affects your PayPal purchase log. If your customers already have a PayPal account PayPal will try to populate your PayPal Purchase Log with their PayPal address. This setting tries to replace the address in the PayPal purchase log with the Address customers enter on your Checkout page.
  	</span>
  	</td>
   </tr>\n";



	$store_currency_data = $wpdb->get_row( $wpdb->prepare( "SELECT `code`, `currency` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id` IN (%d)", get_option( 'currency_type' ) ), ARRAY_A);
	$current_currency = get_option('paypal_curcode');
	if(($current_currency == '') && in_array($store_currency_data['code'], $wpsc_gateways['wpsc_merchant_paypal_standard']['supported_currencies']['currency_list'])) {
		update_option('paypal_curcode', $store_currency_data['code']);
		$current_currency = $store_currency_data['code'];
	}

	if($current_currency != $store_currency_data['code']) {
		$output .= "
  <tr>
      <td colspan='2'><strong class='form_group'>" . __( 'Currency Converter', 'wpsc' ) . "</td>
  </tr>
  <tr>
		<td colspan='2'>".sprintf(__('Your website uses <strong>%s</strong>. This currency is not supported by PayPal, please  select a currency using the drop down menu below. Buyers on your site will still pay in your local currency however we will send the order through to Paypal using the currency you choose below.', 'wpsc'), $store_currency_data['currency'])."</td>
		</tr>\n";

		$output .= "    <tr>\n";



		$output .= "    <td>Select Currency:</td>\n";
		$output .= "          <td>\n";
		$output .= "            <select name='paypal_curcode'>\n";

		$paypal_currency_list = array_map( 'esc_sql', $wpsc_gateways['wpsc_merchant_paypal_standard']['supported_currencies']['currency_list'] );

		$currency_list = $wpdb->get_results("SELECT DISTINCT `code`, `currency` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `code` IN ('".implode("','",$paypal_currency_list)."')", ARRAY_A);

		foreach($currency_list as $currency_item) {
			$selected_currency = '';
			if($current_currency == $currency_item['code']) {
				$selected_currency = "selected='selected'";
			}
			$output .= "<option ".$selected_currency." value='{$currency_item['code']}'>{$currency_item['currency']}</option>";
		}
		$output .= "            </select> \n";
		$output .= "          </td>\n";
		$output .= "       </tr>\n";
	}


$output .= "
   <tr class='update_gateway' >
		<td colspan='2'>
			<div class='submit'>
			<input type='submit' value='".__('Update &raquo;', 'wpsc')."' name='updateoption'/>
		</div>
		</td>
	</tr>

	<tr class='firstrowth'>
		<td style='border-bottom: medium none;' colspan='2'>
			<strong class='form_group'>Forms Sent to Gateway</strong>
		</td>
	</tr>

    <tr>
      <td>
      First Name Field
      </td>
      <td>
      <select name='paypal_form[first_name]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_first_name'))."
      </select>
      </td>
  </tr>
    <tr>
      <td>
      Last Name Field
      </td>
      <td>
      <select name='paypal_form[last_name]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_last_name'))."
      </select>
      </td>
  </tr>
    <tr>
      <td>
      Address Field
      </td>
      <td>
      <select name='paypal_form[address]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_address'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      City Field
      </td>
      <td>
      <select name='paypal_form[city]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_city'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      State Field
      </td>
      <td>
      <select name='paypal_form[state]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_state'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Postal code/Zip code Field
      </td>
      <td>
      <select name='paypal_form[post_code]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_post_code'))."
      </select>
      </td>
  </tr>
  <tr>
      <td>
      Country Field
      </td>
      <td>
      <select name='paypal_form[country]'>
      ".nzshpcrt_form_field_list(get_option('paypal_form_country'))."
      </select>
      </td>
  </tr>
  <tr>
  	<td colspan='2'>
  	<span  class='wpscsmall description'>
  	  For more help configuring Paypal Standard, please read our documentation <a href='http://docs.getshopped.org/wiki/documentation/payments/paypal-payments-standard'>here </a>  	</span>
  	</td>
   </tr>

  ";

  return $output;
}
?>
