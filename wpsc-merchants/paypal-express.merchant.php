<?php
/**
 * This is the PayPal Certified 2.0 Gateway.
 * It uses the wpsc_merchant class as a base class which is handy for collating user details and cart contents.
 */

 /*
  * This is the gateway variable $nzshpcrt_gateways, it is used for displaying gateway information on the wp-admin pages and also
  * for internal operations.
  */
$nzshpcrt_gateways[$num] = array(
	'name' =>  __( 'PayPal Express Checkout 2.0', 'wpsc' ),
	'api_version' => 2.0,
	'image' => WPSC_URL . '/images/paypal.gif',
	'class_name' => 'wpsc_merchant_paypal_express',
	'has_recurring_billing' => false,
	'wp_admin_cannot_cancel' => true,
	'display_name' => __( 'PayPal Express', 'wpsc' ),
	'requirements' => array(
		/// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		'php_version' => 4.3,
		 /// for modules that may not be present, like curl
		'extra_modules' => array()
	),

	// this may be legacy, not yet decided
	'internalname' => 'wpsc_merchant_paypal_express',

	// All array members below here are legacy, and use the code in paypal_multiple.php
	'form' => 'form_paypal_express',
	'submit_function' => 'submit_paypal_express',
	'payment_type' => 'paypal',
	'supported_currencies' => array(
		'currency_list' =>  array( 'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD', 'THB', 'TWD', 'USD' ),
		'option_name' => 'paypal_curcode'
	)
);



/**
	* WP eCommerce PayPal Express Checkout Merchant Class
	*
	* This is the paypal express checkout merchant class, it extends the base merchant class
	*
	* @package wp-e-commerce
	* @since 3.8
	* @subpackage wpsc-merchants
*/
class wpsc_merchant_paypal_express extends wpsc_merchant {
	var $name = '';
	var $paypal_ipn_values = array();

	function __construct( $purchase_id = null, $is_receiving = false ) {
		$this->name = __( 'PayPal Express', 'wpsc' );
		parent::__construct( $purchase_id, $is_receiving );
	}

	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access public
	*/
	function construct_value_array() {
		global $PAYPAL_URL;
		$PROXY_HOST = '127.0.0.1';
		$PROXY_PORT = '808';
		$USE_PROXY = false;
		$version = "71";

		// PayPal API Credentials
		$API_UserName = get_option( 'paypal_certified_apiuser' );
		$API_Password = get_option( 'paypal_certified_apipass' );
		$API_Signature = get_option( 'paypal_certified_apisign' );

		// BN Code 	is only applicable for partners
		$sBNCode = "PP-ECWizard";

		if ('sandbox'  == get_option( 'paypal_certified_server_type' ) ) {
			$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
			$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
		} else {
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
		}

		//$collected_gateway_data
		$paypal_vars = array();

		// User settings to be sent to paypal
		$paypal_vars += array(
			'email' => $this->cart_data['email_address'],
			'first_name' => $this->cart_data['shipping_address']['first_name'],
			'last_name' => $this->cart_data['shipping_address']['last_name'],
			'address1' => $this->cart_data['shipping_address']['address'],
			'city' => $this->cart_data['shipping_address']['city'],
			'country' => $this->cart_data['shipping_address']['country'],
			'zip' => $this->cart_data['shipping_address']['post_code']
		);
		if ( ! empty( $this->cart_data['shipping_address']['state'] ) ) {
			$paypal_vars += array(
				'state' => $this->cart_data['shipping_address']['state']
			);
		}

		$this->collected_gateway_data = $paypal_vars;
	}

	/**
	* parse_gateway_notification method, receives data from the payment gateway
	* @access private
	*/
	function parse_gateway_notification() {
		/// PayPal first expects the IPN variables to be returned to it within 30 seconds, so we do this first.
		if ( 'sandbox'  == get_option( 'paypal_certified_server_type' ) ) {
			$paypal_url = "https://www.sandbox.paypal.com/webscr";
		} else {
			$API_Endpoint = "https://api-3t.paypal.com/nvp";
			$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}
		$received_values = array();
		$received_values['cmd'] = '_notify-validate';
		$received_values += stripslashes_deep ( $_POST );
		$options = array(
			'timeout' => 20,
			'body' => $received_values,
			'httpversion' => '1.1',
			'user-agent' => ('WP e-Commerce/'.WPSC_PRESENTABLE_VERSION)
		);

		$response = wp_remote_post( $paypal_url, $options );

		do_action( 'wpsc_paypal_express_ipn', $received_values, $this );

		if ( 'VERIFIED' == $response['body'] ) {
			$this->paypal_ipn_values = $received_values;
			$this->session_id = $received_values['invoice'];
			if ( strtolower( $received_values['payment_status'] ) == 'completed' ) {
				$this->set_purchase_processed_by_sessionid( 3 );
				transaction_results( $this->session_id, false );
			} elseif ( strtolower( $received_values['payment_status'] ) == 'denied' ) {
				$this->set_purchase_processed_by_sessionid( 6 );
			}
		} else {
			exit( "IPN Request Failure" );
		}
	}

	/**
	* submit method, sends the received data to the payment gateway
	* @access public
	*/
	function submit() {

		$paymentAmount = $this->cart_data['total_price'];

		wpsc_update_customer_meta( 'paypal_express_converted_amount', $this->convert( $paymentAmount ) );
		wpsc_update_customer_meta( 'paypal_express_original_amount', $paymentAmount );
		wpsc_update_customer_meta( 'paypal_express_sessionid', $this->cart_data['session_id'] );
		$currencyCodeType = $this->get_paypal_currency_code();
		$paymentType = "Sale";

		if ( get_option( 'permalink_structure' ) != '' )
			$separator ="?";
		else
			$separator ="&";

		$transact_url = get_option( 'transact_url' );
		$returnURL = $transact_url . $separator . "sessionid=" . $this->cart_data['session_id'] . "&gateway=paypal";
		$cancelURL = get_option( 'shopping_cart_url' );
		$resArray = $this->CallShortcutExpressCheckout ( wpsc_get_customer_meta( 'paypal_express_converted_amount' ), $currencyCodeType, $paymentType, $returnURL, $cancelURL );
		$ack = strtoupper( $resArray["ACK"] );

		if ( $ack == "SUCCESS" ) {
			$this->RedirectToPayPal( $resArray["TOKEN"] );
		} else {
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			$ErrorCode = urldecode( $resArray["L_ERRORCODE0"] );
			$ErrorShortMsg = urldecode( $resArray["L_SHORTMESSAGE0"] );
			$ErrorLongMsg = urldecode( $resArray["L_LONGMESSAGE0"] );
			$ErrorSeverityCode = urldecode( $resArray["L_SEVERITYCODE0"] );

			echo "SetExpressCheckout API call failed. ";
			echo "<br />Detailed Error Message: " . $ErrorLongMsg;
			echo "<br />Short Error Message: " . $ErrorShortMsg;
			echo "<br />Error Code: " . $ErrorCode;
			echo "<br />Error Severity Code: " . $ErrorSeverityCode;
		}
		exit();

	}

	function format_price( $price ) {
		$paypal_currency_code = get_option( 'paypal_curcode', 'US' );

		switch($paypal_currency_code) {
			case "JPY":
				$decimal_places = 0;
				break;

			case "HUF":
				$decimal_places = 0;
				break;

			default:
				$decimal_places = 2;
				break;
		}
		return number_format( sprintf( "%01.2f", $price ), $decimal_places, '.', '' );
	}

	function CallShortcutExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL ) {
		global $wpdb;

		$nvpstr = '';
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
		$data = array();
		if( ! isset( $this->cart_data['shipping_address']['first_name'] ) && ! isset( $this->cart_data['shipping_address']['last_name'] ) ) {
			$this->cart_data['shipping_address']['first_name'] =$this->cart_data['billing_address']['first_name'];
			$this->cart_data['shipping_address']['last_name'] = $this->cart_data['billing_address']['last_name'];

		}

		if ( $this->cart_data['shipping_address']['country'] == 'UK' )
			$this->cart_data['shipping_address']['country'] = 'GB';

		$data += array(
			'PAYMENTREQUEST_0_SHIPTONAME'		=> $this->cart_data['shipping_address']['first_name'].' '.$this->cart_data['shipping_address']['last_name'],
			'PAYMENTREQUEST_0_SHIPTOSTREET' 		=> $this->cart_data['shipping_address']['address'],
			'PAYMENTREQUEST_0_SHIPTOCITY'		=> $this->cart_data['shipping_address']['city'],
			'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $this->cart_data['shipping_address']['country'],
			'PAYMENTREQUEST_0_SHIPTOZIP'			=> $this->cart_data['shipping_address']['post_code'],
		);

		if ( ! empty( $this->cart_data['shipping_address']['state'] ) ){
			$data += array(
				'PAYMENTREQUEST_0_SHIPTOSTATE' => $this->cart_data['shipping_address']['state']
			);
		}

		$i = 0;
		$item_total = 0;
		$tax_total = 0;
		$shipping_total = 0;

		$is_free_shipping = false;
		if ( $this->cart_data['has_discounts'] && (float) $this->cart_data['cart_discount_value'] > 0 ) {
			$coupon = new wpsc_coupons( $this->cart_data['cart_discount_coupon'] );
			$is_free_shipping = $coupon->is_free_shipping();
		}

		foreach ( $this->cart_items as $cart_item ) {
			$data["L_PAYMENTREQUEST_0_NAME{$i}"] = urlencode( apply_filters( 'the_title', $cart_item['name'] ) );
			$data["L_PAYMENTREQUEST_0_AMT{$i}"] = $this->convert( $cart_item['price'] );
			$data["L_PAYMENTREQUEST_0_NUMBER{$i}"] = $i;
			$data["L_PAYMENTREQUEST_0_QTY{$i}"] = $cart_item['quantity'];
			$item_total += $this->convert( $cart_item['price'] ) * $cart_item['quantity'];
			$shipping_total += $cart_item['shipping'];
			$i ++;
		}

		//if we have a discount then include a negative amount with that discount
		// in php 0.00 = true so we will change that here
		if($this->cart_data['cart_discount_value'] == 0.00)
			$this->cart_data['cart_discount_value'] = 0;

		$discount_value = $this->convert( $this->cart_data['cart_discount_value']);

		if ( $this->cart_data['cart_discount_value'] && ! $is_free_shipping ){
			// if item total < discount amount, leave at least 0.01 unit in item total, then subtract
			// 0.01 from shipping as well
			if ( ! $is_free_shipping && $discount_value >= $item_total ) {
				$discount_value = $item_total - 0.01;
				$shipping_total -= 0.01;
			}
			$item_total -= $discount_value;
			$data["L_PAYMENTREQUEST_0_NAME{$i}"] = "Discount / Coupon";
			$data["L_PAYMENTREQUEST_0_AMT{$i}"] = -$discount_value;
			$data["L_PAYMENTREQUEST_0_NUMBER{$i}"] = $i;
			$data["L_PAYMENTREQUEST_0_QTY{$i}"] = 1;
		}
		$data["PAYMENTREQUEST_0_ITEMAMT"] = $this->format_price( $item_total ) ;

		if ( $discount_value && $is_free_shipping )
			$data["PAYMENTREQUEST_0_SHIPPINGAMT"] = 0;
		else
			$data["PAYMENTREQUEST_0_SHIPPINGAMT"] = $this->convert( $this->cart_data['base_shipping'] + $shipping_total );

		$total = $data["PAYMENTREQUEST_0_ITEMAMT"] + $data["PAYMENTREQUEST_0_SHIPPINGAMT"];

		if ( ! wpsc_tax_isincluded() ) {
			$data["PAYMENTREQUEST_0_TAXAMT"] = $this->convert( $this->cart_data['cart_tax'] );
			$total += $data["PAYMENTREQUEST_0_TAXAMT"];
		}

		// adjust total amount in case we had to round up after converting currency
		// or discount calculation
		if ( $total != $paymentAmount )
			$paymentAmount = $total;

		$data["PAYMENTREQUEST_0_AMT"] = $paymentAmount;

		if( count( $data ) >= 4 ) {
			$temp_data = array();
			foreach( $data as $key => $value )
				$temp_data[] = $key . "=" . $value;

			$nvpstr = $nvpstr . "&" . implode( "&", $temp_data );
		}

		wpsc_update_customer_meta( 'paypal_express_currency_code_type', $currencyCodeType );
		wpsc_update_customer_meta( 'paypal_express_payment_type', $paymentType );

		$resArray = paypal_hash_call( "SetExpressCheckout", $nvpstr );
		$ack = strtoupper( $resArray["ACK"] );
		if( $ack == "SUCCESS")	{
			$token = urldecode( $resArray["TOKEN"] );
			wpsc_update_customer_meta( 'paypal_express_token', $token );
		}

		return $resArray;
	}

	function RedirectToPayPal ( $token ){
		global $PAYPAL_URL;
		// Redirect to paypal.com here
		$payPalURL = $PAYPAL_URL . $token;
//		echo 'REDIRECT:'.$payPalURL;
		wp_redirect( $payPalURL );
//		exit();
	}

	function convert( $amt ){
		if ( empty( $this->rate ) ) {
			$this->rate = 1;
			$paypal_currency_code = $this->get_paypal_currency_code();
			$local_currency_code = $this->get_local_currency_code();
			if( $local_currency_code != $paypal_currency_code ) {
				$curr = new CURRENCYCONVERTER();
				$this->rate = $curr->convert( 1, $paypal_currency_code, $local_currency_code );
			}
		}

		return $this->format_price( $amt * $this->rate );
	}

	function get_local_currency_code() {
		if ( empty( $this->local_currency_code ) ) {
			global $wpdb;
			$this->local_currency_code = $wpdb->get_var( $wpdb->prepare( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`= %d LIMIT 1", get_option( 'currency_type' ) ) );
		}

		return $this->local_currency_code;
	}

	function get_paypal_currency_code() {
		if ( empty( $this->paypal_currency_code ) ) {
			global $wpsc_gateways;
			$this->paypal_currency_code = $this->get_local_currency_code();

			if ( ! in_array( $this->paypal_currency_code, $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] ) )
				$this->paypal_currency_code = get_option( 'paypal_curcode', 'USD' );
		}

		return $this->paypal_currency_code;
	}

} // end of class

// terrible code duplication just to hot fix the "missing Description in email receipt" bug
// see paypal_processingfunctions() for more details
function wpsc_paypal_express_convert( $amt ) {
	global $wpdb;

	static $rate;
	static $paypal_currency_code;
	static $local_currency_code;

	if ( empty( $rate ) ) {
		$rate = 1;
		if ( empty( $local_currency_code ) ) {
			$local_currency_code = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
		}
		if ( empty( $paypal_currency_code ) ) {
			global $wpsc_gateways;
			$paypal_currency_code = $local_currency_code;
			if ( ! in_array( $paypal_currency_code, $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] ) )
				$paypal_currency_code = get_option( 'paypal_curcode', 'USD' );
		}

		if ( $local_currency_code != $paypal_currency_code ) {
			$curr = new CURRENCYCONVERTER();
			$rate = $curr->convert( 1, $paypal_currency_code, $local_currency_code );
		}
	}

	return wpsc_paypal_express_format( $amt * $rate );
}

function wpsc_paypal_express_format( $price ) {
	$paypal_currency_code = get_option( 'paypal_curcode', 'US' );

	switch( $paypal_currency_code ) {
		case "JPY":
			$decimal_places = 0;
			break;

		case "HUF":
			$decimal_places = 0;
			break;

		default:
			$decimal_places = 2;
			break;
	}
	return number_format( sprintf( "%01.2f", $price ), $decimal_places, '.', '' );
}

/**
 * Saving of PayPal Express Settings
 * @access public
 *
 * @since 3.8
 */
function submit_paypal_express() {
	if ( isset ( $_POST['paypal_certified_apiuser'] ) ) {
		update_option( 'paypal_certified_apiuser', $_POST['paypal_certified_apiuser'] );
	}
	if ( isset ( $_POST['paypal_certified_apipass'] ) ) {
		update_option( 'paypal_certified_apipass', $_POST['paypal_certified_apipass'] );
	}
	if ( isset ( $_POST['paypal_curcode'] ) ) {
		update_option( 'paypal_curcode', $_POST['paypal_curcode'] );
	}
	if ( isset ( $_POST['paypal_certified_apisign'] ) ) {
		update_option( 'paypal_certified_apisign', $_POST['paypal_certified_apisign'] );
	}
	if ( isset ( $_POST['paypal_certified_server_type'] ) ) {
		update_option( 'paypal_certified_server_type', $_POST['paypal_certified_server_type'] );
	}
	if ( isset ( $_POST['paypal_ipn'])) {
		update_option( 'paypal_ipn', (int)$_POST['paypal_ipn'] );
	}

	return true;
}

/**
 * Form Express Returns the Settings Form Fields
 * @access public
 *
 * @since 3.8
 * @return $output string containing Form Fields
 */
function form_paypal_express() {
	global $wpdb, $wpsc_gateways;

	$serverType1 = '';
	$serverType2 = '';
	$select_currency[ get_option( 'paypal_curcode' ) ] = "selected='selected'";

	if ( get_option( 'paypal_certified_server_type' ) == 'sandbox' ) {
		$serverType1 = "checked='checked'";
	} elseif ( get_option( 'paypal_certified_server_type' ) == 'production' ) {
		$serverType2 = "checked='checked'";
	}

	$paypal_ipn = get_option( 'paypal_ipn' );
	$output = "
	<tr>
		<td>" . __('API Username', 'wpsc' ) . "
		</td>
		<td>
			<input type='text' size='40' value='" . get_option( 'paypal_certified_apiuser') . "' name='paypal_certified_apiuser' />
		</td>
	</tr>
	<tr>
		<td>" . __('API Password', 'wpsc' ) . "
		</td>
		<td>
			<input type='text' size='40' value='" . get_option( 'paypal_certified_apipass') . "' name='paypal_certified_apipass' />
		</td>
	</tr>
	<tr>
		<td>" . __('API Signature', 'wpsc' ) . "
		</td>
		<td>
			<input type='text' size='70' value='" . get_option( 'paypal_certified_apisign') . "' name='paypal_certified_apisign' />
		</td>
	</tr>
	<tr>
		<td>" . __('Server Type', 'wpsc' ) . "
		</td>
		<td>
			<input $serverType1 type='radio' name='paypal_certified_server_type' value='sandbox' id='paypal_certified_server_type_sandbox' /> <label for='paypal_certified_server_type_sandbox'>" . __('Sandbox (For testing)', 'wpsc' ) . "</label> &nbsp;
			<input $serverType2 type='radio' name='paypal_certified_server_type' value='production' id='paypal_certified_server_type_production' /> <label for='paypal_certified_server_type_production'>" . __('Production', 'wpsc' ) . "</label>
			<p class='description'>
				" . sprintf( __( "Only use the sandbox server if you have a sandbox account with PayPal you can find out more about this <a href='%s'>here</a>", 'wpsc' ), esc_url( 'https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/howto_testing_sandbox' ) ) . "
			</p>
		</td>
	</tr>

	<tr>
		<td>
		" . __( 'IPN', 'wpsc' ) . "
		</td>
		<td>
			<input type='radio' value='1' name='paypal_ipn' id='paypal_ipn1' " . checked( $paypal_ipn, 1, false ) . " /> <label for='paypal_ipn1'>".__('Yes', 'wpsc')."</label> &nbsp;
			<input type='radio' value='0' name='paypal_ipn' id='paypal_ipn2' " . checked( $paypal_ipn, 0, false ) . " /> <label for='paypal_ipn2'>".__('No', 'wpsc')."</label>
			<p class='description'>
			" . __( "IPN (instant payment notification) will automatically update your sales logs to 'Accepted payment' when a customers payment is successful. For IPN to work you also need to have IPN turned on in your Paypal settings. If it is not turned on, the sales sill remain as 'Order Pending' status until manually changed. It is highly recommend using IPN, especially if you are selling digital products.", 'wpsc' ) . "
			</p>
		</td>
  	</tr>\n";

	$paypal_ipn = get_option( 'paypal_ipn' );
	$store_currency_code = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id` IN ('" . absint( get_option( 'currency_type' ) ) . "')" );
	$current_currency = get_option( 'paypal_curcode' );

	if ( ( $current_currency == '' ) && in_array( $store_currency_code, $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] ) ) {
		update_option( 'paypal_curcode', $store_currency_code );
		$current_currency = $store_currency_code;
	}
	if ( $current_currency != $store_currency_code ) {
		$output .= "<tr> <td colspan='2'><strong class='form_group'>" . __( 'Currency Converter', 'wpsc' ) . "</td> </tr>
		<tr>
			<td colspan='2'>
			" . __( 'Your website is using a currency not accepted by PayPal, select an accepted currency using the drop down menu bellow. Buyers on your site will still pay in your local currency however we will convert the currency and send the order through to PayPal using the currency you choose below.', 'wpsc' ) . "
			</td>
		</tr>

		<tr>
			<td>
			" . __('Convert to', 'wpsc' ) . "
			</td>
			<td>
				<select name='paypal_curcode'>\n";

		if ( ! isset( $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] ) ) {
			$wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] = array();
		}

		$paypal_currency_list = array_map( 'esc_sql', $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] );

		$currency_list = $wpdb->get_results( "SELECT DISTINCT `code`, `currency` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `code` IN ('" . implode( "','", $paypal_currency_list ) . "')", ARRAY_A );
		foreach ( $currency_list as $currency_item ) {
			$selected_currency = '';
			if( $current_currency == $currency_item['code'] ) {
				$selected_currency = "selected='selected'";
			}
			$output .= "<option ".$selected_currency." value='{$currency_item['code']}'>{$currency_item['currency']}</option>";
		}

		$output .= "
				</select>
			</td>
		</tr>\n";
	}

	$output .="
	<tr>
		<td colspan='2'>
			<p class='description'>
	 		" . sprintf( __( "For more help configuring Paypal Express, please read our documentation <a href='%s'>here</a>", 'wpsc' ), esc_url( 'http://docs.getshopped.org/documentation/paypal-express-checkout/' ) ) . "
	 		</p>
		</td>
   	</tr>\n";

	return $output;
}

function wpsc_get_paypal_currency_code() {
	global $wpdb, $wpsc_gateways;
	$paypal_currency_code = $wpdb->get_var( $wpdb->prepare( "SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`= %d LIMIT 1", get_option( 'currency_type' ) ) );
	if ( ! in_array( $paypal_currency_code, $wpsc_gateways['wpsc_merchant_paypal_express']['supported_currencies']['currency_list'] ) )
		$paypal_currency_code = get_option( 'paypal_curcode', 'USD' );

	return $paypal_currency_code;
}

/**
 * prcessing functions, this is where the main logic of paypal express lives
 * @access public
 *
 * @since 3.8
 */
function paypal_processingfunctions(){
	global $wpdb, $wpsc_cart;

	$sessionid = (string) wpsc_get_customer_meta( 'paypal_express_sessionid' );

	if ( isset( $_REQUEST['act'] ) && ( 'error' == $_REQUEST['act'] ) ) {

		$resArray = wpsc_get_customer_meta( 'paypal_express_reshash' );
		$paypal_express_message = '
		<center>
		<table width="700" align="left">
		<tr>
			<td colspan="2" class="header">' . __('The PayPal API has returned an error!', 'wpsc' ) . '</td>
		</tr>
		';

		//it will print if any URL errors
		if ( wpsc_get_customer_meta( 'paypal_express_curl_error_msg' ) ) {

			$errorMessage = wpsc_get_customer_meta( 'paypal_express_curl_error_msg' );
			$response = wpsc_get_customer_meta( 'paypal_express_response' );

			$paypal_express_message .= '
			<tr>
				<td>response:</td>
				<td>'.$response.'</td>
			</tr>

			<tr>
				<td>Error Message:</td>
				<td>'.$errorMessage.'</td>
			</tr>';
		 } else {

			/* If there is no URL Errors, Construct the HTML page with
			   Response Error parameters.   */
			$paypal_express_message .="
				<tr>
					<td>Ack:</td>
					<td>".$resArray['ACK']."</td>
				</tr>
				<tr>
					<td>Correlation ID:</td>
					<td>".$resArray['CORRELATIONID']."</td>
				</tr>
				<tr>
					<td>Version:</td>
					<td>".$resArray['VERSION']."</td>
				</tr>";

			$count=0;
			while ( isset( $resArray["L_SHORTMESSAGE".$count] ) ) {
				$errorCode    = $resArray["L_ERRORCODE".$count];
				$shortMessage = $resArray["L_SHORTMESSAGE".$count];
				$longMessage  = $resArray["L_LONGMESSAGE".$count];
				$count=$count+1;
				$paypal_express_message .="
					<tr>
						<td>" . __('Error Number:', 'wpsc' ) . "</td>
						<td> $errorCode </td>
					</tr>
					<tr>
						<td>" . __('Short Message:', 'wpsc' ) . "</td>
						<td> $shortMessage </td>
					</tr>
					<tr>
						<td>" . __('Long Message:', 'wpsc' ) . "</td>
						<td> $longMessage </td>
					</tr>";

			}//end while
		}// end else
		$paypal_express_message .="
			</center>
				</table>";
		wpsc_update_customer_meta( 'paypal_express_message', $paypal_express_message );
	} else if ( isset( $_REQUEST['act'] ) && (  $_REQUEST['act']== 'do' ) ) {
		/* Gather the information to make the final call to
		   finalize the PayPal payment.  The variable nvpstr
		   holds the name value pairs   */

		$token = urlencode( $_REQUEST['token'] );

		$paymentAmount = urlencode( wpsc_get_customer_meta( 'paypal_express_converted_amount' ) );
		$paymentType = urlencode( wpsc_get_customer_meta( 'paypal_express_payment_type' ) );
		$currCodeType = urlencode( wpsc_get_paypal_currency_code() );
		$payerID = urlencode( $_REQUEST['PayerID'] );
		$serverName = urlencode( $_SERVER['SERVER_NAME'] );
		$BN = 'Instinct_e-commerce_wp-shopping-cart_NZ';
		$nvpstr = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=Sale&PAYMENTREQUEST_0_CURRENCYCODE=' . $currCodeType . '&IPADDRESS=' . $serverName . "&BUTTONSOURCE=" . $BN . "&PAYMENTREQUEST_0_INVNUM=" . urlencode( $sessionid );
		// IPN data
		if ( get_option( 'paypal_ipn' ) == 1 ) {
			$notify_url = add_query_arg( 'wpsc_action', 'gateway_notification', ( get_option( 'siteurl' ) . "/index.php" ) );
			$notify_url = add_query_arg( 'gateway', 'wpsc_merchant_paypal_express', $notify_url );
			$notify_url = apply_filters( 'wpsc_paypal_express_notify_url', $notify_url );
			$nvpstr .= '&PAYMENTREQUEST_0_NOTIFYURL=' . urlencode( $notify_url );
		}

		// Horrible code that I had to write to hot fix the issue with missing item detail in email receipts. arrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrgh!!!!! @#@$%@#%@##$#$
		$purchase_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` = %s", $sessionid ), ARRAY_A );
		$cart_data = $original_cart_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = {$purchase_log['id']}", ARRAY_A );
		$i = 0;
		$item_total = 0;
		$shipping_total = 0;
		foreach ( $cart_data as $cart_item ) {
			$converted_price = wpsc_paypal_express_convert( $cart_item['price'] );
			$nvpstr .= "&L_PAYMENTREQUEST_0_NAME{$i}=" . urlencode( apply_filters( 'the_title', $cart_item['name'] ) );
			$nvpstr .= "&L_PAYMENTREQUEST_0_AMT{$i}=" . $converted_price;
			$nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER{$i}=" . $i;
			$nvpstr .= "&L_PAYMENTREQUEST_0_QTY{$i}=" . $cart_item['quantity'];
			$item_total += $converted_price * $cart_item['quantity'];
			$shipping_total += wpsc_paypal_express_convert( $cart_item['pnp'] );
			$i ++;
		}
		//if we have a discount then include a negative amount with that discount
		if ( $purchase_log['discount_value'] ){
			$discount_value = wpsc_paypal_express_convert( $purchase_log['discount_value'] );

			// if item total < discount amount, leave at least 0.01 unit in item total, then subtract
			// 0.01 from shipping as well
			if ( $discount_value >= $item_total ) {
				$discount_value = $item_total - 0.01;
				$shipping_total -= 0.01;
			}

			$nvpstr .= "&L_PAYMENTREQUEST_0_NAME{$i}=" . urlencode( "Discount / Coupon" );
			$nvpstr .= "&L_PAYMENTREQUEST_0_AMT{$i}=-" . urlencode( $discount_value );
			$nvpstr .= "&L_PAYMENTREQUEST_0_NUMBER{$i}={$i}";
			$nvpstr .= "&L_PAYMENTREQUEST_0_QTY{$i}=1";
			$item_total -= $discount_value;
		}
		$item_total = wpsc_paypal_express_format( $item_total );
		$shipping_total = wpsc_paypal_express_convert( $purchase_log['base_shipping'] ) +  $shipping_total;
		$nvpstr .= '&PAYMENTREQUEST_0_ITEMAMT=' . $item_total;
		$nvpstr .= '&PAYMENTREQUEST_0_SHIPPINGAMT=' . $shipping_total;

		$total = $item_total + $shipping_total;

		if ( ! wpsc_tax_isincluded() ) {
			$tax = wpsc_paypal_express_convert( $purchase_log['wpec_taxes_total'] );
			$nvpstr .= '&PAYMENTREQUEST_0_TAXAMT=' . $tax;
			$total += $tax;
		}

		// adjust total amount in case we had to round up after converting currency
		if ( $total != $paymentAmount )
			$paymentAmount = $total;

		$nvpstr .= "&PAYMENTREQUEST_0_AMT={$paymentAmount}";
		$resArray = paypal_hash_call( "DoExpressCheckoutPayment", $nvpstr );

		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php. */
		$ack = strtoupper( $resArray["ACK"] );
		wpsc_update_customer_meta( 'paypal_express_reshash', $resArray );

		if ( $ack != "SUCCESS" ) {
			$location = get_option( 'transact_url' ) . "&act=error";
		} else {
			$transaction_id = $resArray['PAYMENTINFO_0_TRANSACTIONID'];
			switch( $resArray['PAYMENTINFO_0_PAYMENTSTATUS'] ) {
				case 'Processed': // I think this is mostly equivalent to Completed
				case 'Completed':
					wpsc_update_purchase_log_status( $sessionid, 3, 'sessionid' );
					transaction_results( $sessionid, false );
					break;

				case 'Pending': // need to wait for "Completed" before processing
					wpsc_update_purchase_log_details(
						$sessionid,
						array(
							'processed' => 2,
							'date' => time(),
							'transactid' => $transaction_id,
						),
						'sessionid'
					);
					break;
			}
			$location = add_query_arg( 'sessionid', $sessionid, get_option( 'transact_url' ) );

			wpsc_delete_customer_meta( 'paypal_express_message' );
			wp_redirect( $location );
			exit();
		}

		wpsc_delete_customer_meta( 'nzshpcrt_serialized_cart' );
		wpsc_delete_customer_meta( 'nzshpcart' );
		$wpsc_cart->empty_cart();

	} else if ( isset( $_REQUEST['paymentType'] ) || isset( $_REQUEST['token'] ) ) {

		$token = $_REQUEST['token'];
		if( ! isset( $token ) ) {
		   $paymentAmount = wpsc_get_customer_meta( 'paypal_express_converted_amount' );
		   $currencyCodeType = wpsc_get_paypal_currency_code();
		   $paymentType = 'Sale';
			if ( get_option( 'permalink_structure' ) != '' )
				$separator = "?";
			else
				$separator = "&";

			$returnURL = urlencode( get_option( 'transact_url' ) . $separator . 'currencyCodeType=' . $currencyCodeType . '&paymentType=' . $paymentType . '&paymentAmount=' . $paymentAmount );
			$cancelURL = urlencode( get_option( 'transact_url' ) . $separator . 'paymentType=$paymentType' );

			/* Construct the parameter string that describes the PayPal payment
			the varialbes were set in the web form, and the resulting string
			is stored in $nvpstr */

			$nvpstr = "&PAYMENTREQUEST_0_AMT=" . $paymentAmount . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType . "&ReturnUrl=" . $returnURL . "&CANCELURL=" . $cancelURL . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;

			/* Make the call to PayPal to set the Express Checkout token
			If the API call succeded, then redirect the buyer to PayPal
			to begin to authorize payment.  If an error occured, show the
			resulting errors
			*/
		   $resArray = paypal_hash_call( "SetExpressCheckout", $nvpstr );
		   wpsc_update_customer_meta( 'paypal_express_reshash', $resArray );
		   $ack = strtoupper( $resArray["ACK"] );

		   if ( $ack == "SUCCESS" ){
				// Redirect to paypal.com here
				$token = urldecode( $resArray["TOKEN"] );
				$payPalURL = $PAYPAL_URL . $token;
				wp_redirect( $payPalURL );
		   } else {
				// Redirecting to APIError.php to display errors.
				$location = get_option( 'transact_url' ) . "&act=error";
				wp_redirect( $location );
		   }
		   exit();
		} else {
		 /* At this point, the buyer has completed in authorizing payment
			at PayPal.  The script will now call PayPal with the details
			of the authorization, incuding any shipping information of the
			buyer.  Remember, the authorization is not a completed transaction
			at this state - the buyer still needs an additional step to finalize
			the transaction
			*/

		   $token = urlencode( $_REQUEST['token'] );

		 /* Build a second API request to PayPal, using the token as the
			ID to get the details on the payment authorization
			*/
		   $nvpstr = "&TOKEN=" . $token;

		 /* Make the API call and store the results in an array.  If the
			call was a success, show the authorization details, and provide
			an action to complete the payment.  If failed, show the error
			*/
			$resArray = paypal_hash_call( "GetExpressCheckoutDetails", $nvpstr );

			wpsc_update_customer_meta( 'paypal_express_reshash', $resArray );

		   $ack = strtoupper( $resArray["ACK"] );
		   if( $ack == "SUCCESS" ){

				/********************************************************
				GetExpressCheckoutDetails.php

				This functionality is called after the buyer returns from
				PayPal and has authorized the payment.

				Displays the payer details returned by the
				GetExpressCheckoutDetails response and calls
				DoExpressCheckoutPayment.php to complete the payment
				authorization.

				Called by ReviewOrder.php.

				Calls DoExpressCheckoutPayment.php and APIError.php.

				********************************************************/

				/* Collect the necessary information to complete the
				authorization for the PayPal payment
				*/

				wpsc_update_customer_meta( 'paypal_express_token', $_REQUEST['token'] );
				wpsc_update_customer_meta( 'paypal_express_payer_id', $_REQUEST['PayerID'] );

				$resArray = wpsc_get_customer_meta( 'paypal_express_reshash' );

				if ( get_option( 'permalink_structure' ) != '')
					$separator ="?";
				else
					$separator ="&";


				/* Display the  API response back to the browser .
				If the response from PayPal was a success, display the response parameters
				*/
				if( isset( $_REQUEST['TOKEN'] ) && ! isset( $_REQUEST['PAYERID'] ) ) {

					wpsc_update_customer_meta( 'paypal_express_message', _x( '<h4>TRANSACTION CANCELED</h4>', 'paypal express cancel header', 'wpsc' ) );

				}else{
					if ( ! isset( $resArray['SHIPTOSTREET2'] ) )
						$resArray['SHIPTOSTREET2'] = '';
					$output ="
					   <table width='400' class='paypal_express_form'>
						<tr>
							<td align='left' class='firstcol'><b>" . __( 'Order Total:', 'wpsc' ) . "</b></td>
							<td align='left'>" . wpsc_currency_display( wpsc_get_customer_meta( 'paypal_express_original_amount' ) ) . "</td>
						</tr>
						<tr>
							<td align='left' colspan='2'><b>" . __( 'Shipping Address:', 'wpsc' ) . " </b></td>
						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'Street 1:', 'wpsc' ) . "</td>
							<td align='left'>" . $resArray['SHIPTOSTREET'] . "</td>

						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'Street 2:', 'wpsc' ) . "</td>
							<td align='left'>" . $resArray['SHIPTOSTREET2'] . "
							</td>
						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'City:', 'wpsc' ) . "</td>

							<td align='left'>" . $resArray['SHIPTOCITY'] . "</td>
						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'State:', 'wpsc' ) . "</td>
							<td align='left'>" . $resArray['SHIPTOSTATE'] . "</td>
						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'Postal code:', 'wpsc' ) . "</td>

							<td align='left'>" . $resArray['SHIPTOZIP'] . "</td>
						</tr>
						<tr>
							<td align='left' class='firstcol'>
								" . __( 'Country:', 'wpsc' ) . "</td>
							<td align='left'>" . $resArray['SHIPTOCOUNTRYNAME'] . "</td>
						</tr>
						<tr>
							<td colspan='2'>";

					$output .= "<form action=" . get_option( 'transact_url' ) . " method='post'>\n";
					$output .= "	<input type='hidden' name='totalAmount' value='" . wpsc_cart_total(false) . "' />\n";
					$output .= "	<input type='hidden' name='shippingStreet' value='" . $resArray['SHIPTOSTREET'] . "' />\n";
					$output .= "	<input type='hidden' name='shippingStreet2' value='" . $resArray['SHIPTOSTREET2'] . "' />\n";
					$output .= "	<input type='hidden' name='shippingCity' value='" . $resArray['SHIPTOCITY'] . "' />\n";
					$output .= "	<input type='hidden' name='shippingState' value='" . $resArray['SHIPTOSTATE'] . "' />\n";
					$output .= "	<input type='hidden' name='postalCode' value='" . $resArray['SHIPTOZIP'] . "' />\n";
					$output .= "	<input type='hidden' name='country' value='" . $resArray['SHIPTOCOUNTRYNAME'] . "' />\n";
					$output .= "	<input type='hidden' name='token' value='"  .  wpsc_get_customer_meta( 'paypal_express_token' )  .  "' />\n";
					$output .= "	<input type='hidden' name='PayerID' value='"  .  wpsc_get_customer_meta( 'paypal_express_payer_id' )  .  "' />\n";
					$output .= "	<input type='hidden' name='act' value='do' />\n";
					$output .= "	<p>  <input name='usePayPal' type='submit' value='" . __('Confirm Payment','wpsc') . "' /></p>\n";
					$output .= "</form>";
					$output .=" </td>
							</tr>
						</table>
					</center>
					";
					wpsc_update_customer_meta( 'paypal_express_message', $output );
				}
			}
		}

	}

}



function paypal_hash_call( $methodName, $nvpStr ) {
	//declaring of variables
	$version = 71;
	if ( 'sandbox' == get_option( 'paypal_certified_server_type' ) ) {
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$paypal_certified_url  = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=";
	} else {
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$paypal_certified_url  = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=";
	}

	$USE_PROXY = false;
	$API_UserName = get_option( 'paypal_certified_apiuser' );
	$API_Password = get_option( 'paypal_certified_apipass' );
	$API_Signature = get_option( 'paypal_certified_apisign' );
	$sBNCode = "PP-ECWizard";
	//NVPRequest for submitting to server
	$nvpreq = "METHOD=" . urlencode( $methodName ) . "&VERSION=" . urlencode( $version ) . "&PWD=" . urlencode( $API_Password ) . "&USER=" . urlencode( $API_UserName ) . "&SIGNATURE=" . urlencode( $API_Signature ) . $nvpStr . "&BUTTONSOURCE=" . urlencode( $sBNCode );

	// Configure WP_HTTP
	if ( $USE_PROXY ) {
		if ( ! defined( 'WP_PROXY_HOST' ) && ! defined( 'WP_PROXY_PORT' ) ) {
			define( 'WP_PROXY_HOST', $PROXY_HOST );
			define( 'WP_PROXY_PORT', $PROXY_PORT );
		}
	}
	add_filter( 'https_ssl_verify', '__return_false' );

	$options = array(
		'timeout' => 20,
		'body' => $nvpreq,
		'httpversion' => '1.1',
		'sslverify' => false,
	);

	$nvpReqArray = paypal_deformatNVP( $nvpreq );

	wpsc_update_customer_meta( 'paypal_express_nvp_req_array', $nvpReqArray );

	$res = wp_remote_post( $API_Endpoint, $options );

	if ( is_wp_error( $res ) ) {
		wpsc_update_customer_meta( 'paypal_express_curl_error_msg', 'WP HTTP Error: ' . $res->get_error_message() );
		$nvpResArray = paypal_deformatNVP( '' );
	} else {
		$nvpResArray = paypal_deformatNVP( $res['body'] );
	}

	return $nvpResArray;
}

function paypal_deformatNVP( $nvpstr ) {
	$intial = 0;
	$nvpArray = array();

	while ( strlen( $nvpstr ) ) {
		//postion of Key
		$keypos = strpos( $nvpstr, '=' );
		//position of value
		$valuepos = strpos( $nvpstr, '&' ) ? strpos( $nvpstr,'&' ) : strlen( $nvpstr );

		/*getting the Key and Value values and storing in a Associative Array*/
		$keyval = substr( $nvpstr, $intial, $keypos );
		$valval = substr( $nvpstr, $keypos + 1, $valuepos - $keypos - 1 );
		//decoding the respose
		$nvpArray[ urldecode( $keyval ) ] = urldecode( $valval );
		$nvpstr = substr( $nvpstr, $valuepos + 1, strlen( $nvpstr ) );
	}
	return $nvpArray;
}

if ( in_array( 'wpsc_merchant_paypal_express', get_option( 'custom_gateway_options' ) ) )
	add_action('init', 'paypal_processingfunctions');
