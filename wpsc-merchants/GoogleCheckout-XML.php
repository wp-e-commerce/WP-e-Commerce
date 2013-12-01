<?php

require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googlecart.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googleitem.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googleshipping.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googletax.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googleresponse.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googlemerchantcalculations.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googleresult.php');
require_once(WPSC_FILE_PATH.'/wpsc-merchants/library/googlerequest.php');


$nzshpcrt_gateways[$num]['name'] = __( 'Google Wallet', 'wpsc' );
$nzshpcrt_gateways[$num]['image'] = WPSC_URL . '/images/google_checkout.gif';
$nzshpcrt_gateways[$num]['internalname'] = 'google';
$nzshpcrt_gateways[$num]['function'] = 'gateway_google';
$nzshpcrt_gateways[$num]['form'] = "form_google";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_google";
$nzshpcrt_gateways[$num]['is_exclusive'] = true;
$nzshpcrt_gateways[$num]['payment_type'] = "google_checkout";
$nzshpcrt_gateways[$num]['display_name'] = __( 'Google Wallet', 'wpsc' );

function gateway_google($fromcheckout = false){
	global $wpdb, $wpsc_cart, $wpsc_checkout,$current_user,  $purchlogs;
	if(!isset($wpsc_checkout)){
		$wpsc_checkout = new wpsc_checkout();
	}

	$sessionid = (string) wpsc_get_customer_meta( 'google_checkout_session_id' );
	if( empty( $sessionid ) ){
		$sessionid = ( mt_rand( 100,999 ) . time() );
		wpsc_update_customer_meta( 'google_checkout_session_id', $sessionid );
	}

	$delivery_region = wpsc_get_customer_meta( 'shipping_region' );
	$billing_region  = wpsc_get_customer_meta( 'billing_region'  );

	if( ! $billing_region && ! $billing_region ){
		$base_region = get_option( 'base_region' );
		wpsc_update_customer_meta( 'shipping_region', $base_region );
		wpsc_update_customer_meta( 'billing_region' , $base_region );
	}

	$wpsc_cart->get_shipping_option();
	$wpsc_cart->get_shipping_quotes();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->google_shipping_quotes();
	$subtotal = $wpsc_cart->calculate_subtotal();
	$base_shipping = $wpsc_cart->calculate_total_shipping();
	$tax = $wpsc_cart->calculate_total_tax();
	$total = $wpsc_cart->calculate_total_price();
	if($total > 0 ){
		$update = $wpdb->update(
				WPSC_TABLE_PURCHASE_LOGS,
				array(
				'totalprice' => $total,
				'statusno' => 0,
				'user_ID' => wpsc_get_current_customer_id(),
				'date' => time(),
				'gateway' => 'google',
				'billing_country' => $wpsc_cart->delivery_country,
				'shipping_country' => $wpsc_cart->selected_country,
				'base_shipping' => $base_shipping,
				'shipping_method' => $wpsc_cart->selected_shipping_method,
				'shipping_option' => $wpsc_cart->selected_shipping_option,
				'plugin_version' => WPSC_VERSION,
				'discount_value' => $wpsc_cart->coupons_amount,
				'discount_data' => $wpsc_cart->coupons_name
				),
				array(
				'sessionid' => $sessionid
				),
				array(
				'%f',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				),
				'%s'
			);
		$sql = $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE sessionid = %s", $sessionid );
		$purchase_log_id = $wpdb->get_var($sql);
		if( !empty($purchase_log_id) ){
			$sql = $wpdb->prepare( "DELETE FROM  `".WPSC_TABLE_CART_CONTENTS."` WHERE purchaseid = %d", $purchase_log_id );
			$wpdb->query($sql);
		}
		if( ! $update ){
			$wpdb->insert(
				WPSC_TABLE_PURCHASE_LOGS,
				array(
				'totalprice' => $total,
				'statusno' => 0,
				'sessionid' => $sessionid,
				'user_ID' => wpsc_get_current_customer_id(),
				'date' => time(),
				'gateway' => 'google',
				'billing_country' => $wpsc_cart->delivery_country,
				'shipping_country' => $wpsc_cart->selected_country,
				'base_shipping' => $base_shipping,
				'shipping_method' => $wpsc_cart->selected_shipping_method,
				'shipping_option' => $wpsc_cart->selected_shipping_option,
				'plugin_version' => WPSC_VERSION,
				'discount_value' => $wpsc_cart->coupons_amount,
				'discount_data' => $wpsc_cart->coupons_name
				),
				array(
				'%f',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				),
				'%s'
			);
			$purchase_log_id = $wpdb->insert_id;

		}

		$wpsc_cart->save_to_db( $purchase_log_id );

		if( get_option( 'permalink_structure' ) != '' ) {
			$separator = "?";
		} else {
			$separator = "&";
		}
		Usecase($separator, $sessionid, $fromcheckout);
	}
}

function Usecase($separator, $sessionid, $fromcheckout) {
	global $wpdb, $wpsc_cart ;

	$purchase_log_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid` = %s  LIMIT 1", $sessionid );
	$purchase_log     = $wpdb->get_results( $purchase_log_sql, ARRAY_A ) ;

	$cart_sql         = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid` = %d", $purchase_log[0]['id'] );
	$wp_cart          = $wpdb->get_results($cart_sql,ARRAY_A) ;

	$merchant_id      = get_option('google_id');
	$merchant_key     = get_option('google_key');
	$server_type      = get_option('google_server_type');
	$currency         = get_option('google_cur');
	$transact_url     = get_option('transact_url');
	$returnURL        =  $transact_url.$separator."sessionid=".$sessionid."&gateway=google";

	$cart             = new GoogleCart($merchant_id, $merchant_key, $server_type, $currency);
	$cart->SetContinueShoppingUrl($returnURL);
	$cart->SetEditCartUrl(get_option('shopping_cart_url'));

	//google prohibited items not implemented
	$currency_converter  =  new CURRENCYCONVERTER();
	$currency_code       = $wpdb->get_results("SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`='".get_option('currency_type')."' LIMIT 1",ARRAY_A);
	$local_currency_code = $currency_code[0]['code'];
	$google_curr         = get_option('google_cur');
	$currentcy_rate		 = 1;

	if($google_curr != $local_currency_code){
		$currentcy_rate = $currency_converter->convert( 1, $local_currency_code, $google_curr);
	}

	while (wpsc_have_cart_items()) {
		wpsc_the_cart_item();

		$google_currency_productprice = $currentcy_rate * (wpsc_cart_item_price(false)/wpsc_cart_item_quantity());

		$cart_item = new GoogleItem(wpsc_cart_item_name(),      	// Item name
									'', 							// Item description
									wpsc_cart_item_quantity(), 		// Quantity
									($google_currency_productprice) // Unit price
									);

		$cart->AddItem($cart_item);
	}

	//If there are coupons applied add coupon as a product with negative price
	if($wpsc_cart->coupons_amount > 0){

		$google_currency_productprice = $currentcy_rate * $wpsc_cart->coupons_amount;

		$coupon = new GoogleItem('Discount',      						// Item name
								 'Discount Price', 						// Item description
								 1, 									// Quantity
								 ('-'.$google_currency_productprice) 	// Unit price
								);

		$cart->AddItem($coupon);
	}

	$shipping_country = $purchase_log[0]['shipping_country'];
	$shipping_region  = $purchase_log[0]['shipping_region'];

	if ($shipping_country == "UK")
		$shipping_country = "GB";

	// Add shipping options
	if(wpsc_uses_shipping()){
		$shipping_name = ucfirst($wpsc_cart->selected_shipping_method)." - ".$wpsc_cart->selected_shipping_option;
		if ($shipping_name == "") $shipping_name = "Calculated";

		$shipping = new GoogleFlatRateShipping($shipping_name, $wpsc_cart->calculate_total_shipping() * $currentcy_rate);

		if (!empty($shipping_country)){
			$shipping_filter = new GoogleShippingFilters();

			if (!empty($shipping_region) && is_numeric($shipping_region)){
				$shipping_filter->AddAllowedPostalArea($shipping_country,wpsc_get_state_by_id($shipping_region,"code"));
				$shipping_filter->AddAllowedStateArea(wpsc_get_state_by_id($shipping_region,"code"));
			} else {
				$shipping_filter->AddAllowedPostalArea($shipping_country);
			}

			$shipping->AddShippingRestrictions($shipping_filter);
		}

		$cart->AddShipping($shipping);
	}

	// Add tax rules
	if (!empty($shipping_country)){
		$tax_rule = new GoogleDefaultTaxRule( (wpsc_cart_tax(false)/$wpsc_cart->calculate_subtotal() ));
		$tax_rule->AddPostalArea($shipping_country);
		$cart->AddDefaultTaxRules($tax_rule);
	}

	// Display Google Checkout button
	if (get_option('google_button_size') == '0'){
		$google_button_size = 'BIG';
	} elseif(get_option('google_button_size') == '1') {
		$google_button_size = 'MEDIUM';
	} elseif(get_option('google_button_size') == '2') {
		$google_button_size = 'SMALL';
	}
	echo $cart->CheckoutButtonCode($google_button_size);
}

function wpsc_google_checkout_page(){
	global $wpsc_gateway;
	$script = "<script type='text/javascript'>
					jQuery(document).ready(
						function()
						 {
							jQuery('div#wpsc_shopping_cart_container h2').hide();
							jQuery('div#wpsc_shopping_cart_container .wpsc_cart_shipping').hide();
							jQuery('.wpsc_checkout_forms').hide();
						});
				</script>";

	$options = get_option('payment_gateway');

	if( in_array( 'google', (array) get_option( 'custom_gateway_options' ) ) && 'google' == wpsc_get_customer_meta( 'google_checkout' ) ) {
		wpsc_delete_customer_meta( 'google_checkout' );
		echo $script;
		gateway_google(true);
	}
}

add_action('wpsc_before_form_of_shopping_cart', 'wpsc_google_checkout_page');

function submit_google() {
	if(isset($_POST['google_id'])) {
		update_option('google_id', $_POST['google_id']);
	}

	if(isset($_POST['google_key'])) {
		update_option('google_key', $_POST['google_key']);
	}
	if(isset($_POST['google_cur'])) {
		update_option('google_cur', $_POST['google_cur']);
	}
	if(isset($_POST['google_button_size'])) {
		update_option('google_button_size', $_POST['google_button_size']);
	}
	if(isset($_POST['google_button_bg'])) {
		update_option('google_button_bg', $_POST['google_button_bg']);
	}
	if(isset($_POST['google_server_type'])) {
		update_option('google_server_type', $_POST['google_server_type']);
	}
	if(isset($_POST['google_auto_charge'])) {
		update_option('google_auto_charge', $_POST['google_auto_charge']);
	}
  return true;
  }

function form_google(){
	if (get_option('google_button_size') == '0'){
		$button_size1="checked='checked'";
	} elseif(get_option('google_button_size') == '1') {
		$button_size2="checked='checked'";
	} elseif(get_option('google_button_size') == '2') {
		$button_size3="checked='checked'";
	}

	if (get_option('google_server_type') == 'sandbox'){
		$google_server_type1="checked='checked'";
	} elseif(get_option('google_server_type') == 'production') {
		$google_server_type2="checked='checked'";
	}

	if (get_option('google_auto_charge') == '1'){
		$google_auto_charge1="checked='checked'";
	} elseif(get_option('google_auto_charge') == '0') {
		$google_auto_charge2="checked='checked'";
	}

	if (get_option('google_button_bg') == 'trans'){
		$button_bg1="selected='selected'";
	} else {
		$button_bg2="selected='selected'";
	}

	if (!isset($google_auto_charge1)) $google_auto_charge1 = '';
	if (!isset($google_auto_charge2)) $google_auto_charge2 = '';
	if (!isset($google_server_type1)) $google_server_type1 = '';
	if (!isset($google_server_type2)) $google_server_type2 = '';

	if (!isset($button_size1)) $button_size1 = '';
	if (!isset($button_size2)) $button_size2 = '';
	if (!isset($button_size3)) $button_size3 = '';

	if (!isset($button_bg1)) $button_bg1 = '';
	if (!isset($button_bg2)) $button_bg2 = '';

	$output = "
	<tr>
		<td>" . __( 'Merchant ID', 'wpsc' ) . "		</td>
		<td>
			<input type='text' size='40' value='".get_option('google_id')."' name='google_id' />
		</td>
	</tr>
	<tr>
		<td>" . __( 'Merchant Key', 'wpsc' ) . "
		</td>
		<td>
			<input type='text' size='40' value='".get_option('google_key')."' name='google_key' />
		</td>
	</tr>
	<tr>
		<td>
		" . __( 'Turn on auto charging', 'wpsc' ) . "
		</td>
		<td>
			<input $google_auto_charge1 type='radio' name='google_auto_charge' value='1' id='google_auto_charge1' /> <label for='google_auto_charge1'>" . __( 'Yes', 'wpsc' ) . "</label> &nbsp;
			<input $google_auto_charge2 type='radio' name='google_auto_charge' value='0' id='google_auto_charge2' /> <label for='google_auto_charge2'>" . __( 'No', 'wpsc' ) . "</label>
		</td>
	</tr>
	<tr>
		<td>
		" . __( 'Server Type', 'wpsc' ) . "
		</td>
		<td>
			<input $google_server_type1 type='radio' name='google_server_type' value='sandbox' id='google_server_type_sandbox' /> <label for='google_server_type_sandbox'>" . __( 'Sandbox', 'wpsc' ) . "</label> &nbsp;
			<input $google_server_type2 type='radio' name='google_server_type' value='production' id='google_server_type_production' /> <label for='google_server_type_production'>" . __( 'Production', 'wpsc' ) . "</label>
		</td>
	</tr>
	<tr>
		<td>
		" . __( 'Select your currency', 'wpsc' ) . "
		</td>
		<td>
			<select name='google_cur'>\n";

	if ( get_option( 'google_cur' ) == 'USD' ) {
		$output .= "<option selected='selected' value='USD'>" . __( 'USD', 'wpsc' ) . "</option>
			<option value='GBP'>" . __( 'GBP', 'wpsc' ) . "</option>";
	} else {
		$output .= "<option value='USD'>" . __( 'USD', 'wpsc' ) . "</option>
			<option value='GBP' selected='selected'>" . __( 'GBP', 'wpsc' ) . "</option>";
	}

	$output .= "</select>
		  </td>
	</tr>
	<tr>
		<td>
		" . __( 'Select Shipping Countries', 'wpsc' ) . "
		</td>
		<td>
			<a href='" . add_query_arg( array( "googlecheckoutshipping" =>  1, "page" => "wpsc-settings" ) ) . "' alt='" . __( 'Set Shipping Options', 'wpsc' ) . "'>" . __( 'Set Shipping Countries', 'wpsc' ) . "</a>
		</td>
	</tr>

	<tr>
		<td>
		" . __( 'Button Styles', 'wpsc' ) . "
		</td>
		<td>
			<span class='label'>" . __( 'Size', 'wpsc') . ":</span> &nbsp;
			<input $button_size1 type='radio' name='google_button_size' value='0' id='google_button_size_0' /> <label for='google_button_size_0'>180&times;46</label> &nbsp;
			<input $button_size2 type='radio' name='google_button_size' value='1' id='google_button_size_1' /> <label for='google_button_size_1'>168&times;44</label> &nbsp;
			<input $button_size3 type='radio' name='google_button_size' value='2' id='google_button_size_2' /> <label for='google_button_size_2'>160&times;43</label> &nbsp;
		</td>
	</tr>
	<tr>
		<td>
		</td>
		<td>
			<label for='google_button_bg'>" . __( 'Background:', 'wpsc' ) . "</label> &nbsp;
			<select name='google_button_bg'>
				<option $button_bg1 value='trans'>" . __( 'Transparent', 'wpsc' ) . "</option>
				<option $button_bg2 value='white'>" . __( 'White', 'wpsc' ) . "</option>
			</select>
		</td>
	</tr>

	<tr>
		<td>" . __( 'API version', 'wpsc' ) . ":</td>
		<td>
			<strong>2.0</strong>
		</td>
	<td>
	<tr>
		<td>".__('API callback URL','wpsc').":</td>
		<td><code><strong>" . home_url( '/' ) . "</strong></code></td>
	</tr>
	<tr>
		<td colspan='2'>
			<p class='description'>
				" . sprintf( __( "For more help configuring Google Checkout, please read our documentation <a href='%s'>here</a>", 'wpsc' ), esc_url( 'http://docs.getshopped.org/documentation/google-checkout/' ) ) . "
			</p>
		</td>
	</tr>\n";

	return $output;
}

function nzsc_googleResponse() {
	global $wpdb, $user_ID;
	$merchant_id = get_option('google_id');
	$merchant_key = get_option('google_key');
	$server_type = get_option('google_server_type');
	$currency = get_option('google_cur');

	define('RESPONSE_HANDLER_ERROR_LOG_FILE', 'library/googleerror.log');
	define('RESPONSE_HANDLER_LOG_FILE', 'library/googlemessage.log');
	if (stristr($_SERVER['HTTP_USER_AGENT'],"Google Checkout Notification Agent")) {
		$Gresponse = new GoogleResponse($merchant_id, $merchant_key);
		$xml_response = isset($HTTP_RAW_POST_DATA)?$HTTP_RAW_POST_DATA:file_get_contents("php://input");

		if (get_magic_quotes_gpc()) {
			$xml_response = stripslashes($xml_response);
		}
		list($root, $data) = $Gresponse->GetParsedXML($xml_response);

		$message = "<pre>".print_r($user_marketing_preference,1)."</pre>";

		$sessionid = (mt_rand(100,999).time());
		if ($root == "new-order-notification") {
			wpsc_delete_customer_meta( 'nzshpcart' );
			$cart_items = $data['new-order-notification']['shopping-cart']['items'];
			$user_marketing_preference=$data['new-order-notification']['buyer-marketing-preferences']['email-allowed']['VALUE'];
			$shipping_name = $data['new-order-notification']['buyer-shipping-address']['contact-name']['VALUE'];
			$shipping_name = explode(" ",$shipping_name);
			$shipping_firstname = $shipping_name[0];
			$shipping_lastname = $shipping_name[count($shipping_name)-1];
			$shipping_country = $data['new-order-notification']['buyer-shipping-address']['country-code']['VALUE'];
			$shipping_address1 = $data['new-order-notification']['buyer-shipping-address']['address1']['VALUE'];
			$shipping_address2 = $data['new-order-notification']['buyer-shipping-address']['address2']['VALUE'];
			$shipping_city = $data['new-order-notification']['buyer-shipping-address']['city']['VALUE'];
			$shipping_region = $data['new-order-notification']['buyer-shipping-address']['region']['VALUE'];
			$billing_name = $data['new-order-notification']['buyer-billing-address']['contact-name']['VALUE'];
			$billing_name = explode(" ",$shipping_name);
			$billing_firstname = $shipping_name[0];
			$billing_lastname = $shipping_name[count($shipping_name)-1];
			$billing_region = $data['new-order-notification']['buyer-billing-address']['region']['VALUE'];
			$billing_country = $data['new-order-notification']['buyer-billing-address']['country-code']['VALUE'];
			$total_price = $data['new-order-notification']['order-total']['VALUE'];
			$billing_email = $data['new-order-notification']['buyer-billing-address']['email']['VALUE'];
			$billing_phone = $data['new-order-notification']['buyer-billing-address']['phone']['VALUE'];
			$billing_address = $data['new-order-notification']['buyer-billing-address']['address1']['VALUE'];
			$billing_address .= " ".$data['new-order-notification']['buyer-billing-address']['address2']['VALUE'];
			$billing_address .= " ". $data['new-order-notification']['buyer-billing-address']['city']['VALUE'];
			$billing_city = $data['new-order-notification']['buyer-billing-address']['city']['VALUE'];
			$google_order_number = $data['new-order-notification']['google-order-number']['VALUE'];
			$pnp = $data['new-order-notification']['order-adjustment']['shipping']['flat-rate-shipping-adjustment']['shipping-cost']['VALUE'];
			$affiliate_id=$data['new-order-notification']['shopping-cart']['merchant-private-data'];
			$affiliate_id=explode('=',$affiliate_id);
			if ($affiliate_id[0]=='affiliate_id') {
				if ($affiliate_id[1] == '') {
					$affiliate_id = null;
				} else {
					$affiliate_id = $affiliate_id[1];
				}
			}
			$Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type,$currency);
			$result = $Grequest->SendProcessOrder($google_order_number);
			$region_number = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM ".WPSC_TABLE_REGION_TAX."` WHERE code = %s", $billing_region ) );

			$wpdb->insert(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'totalprice' => $total_price,
					'sessionid' => $sessionid,
					'date' => time(),
					'billing_country' => $billing_country,
					'shipping_country' => $shipping_country,
					'base_shipping' => $pnp,
					'shipping_region' => $region_number,
					'user_ID' => wpsc_get_current_customer_id(),
					'discount_value' => wpsc_get_customer_meta( 'wpsc_discount' ),
					'gateway' => get_option('payment_gateway'),
					'google_order_number' => $google_order_number,
					'google_user_marketing_preference' => $user_marketing_preference,
					'affiliate_id' => $affiliate_id
					),
					array(
					'%f',
					'%s',
					'%s',
					'%s',
					'%s',
					'%f',
					'%s',
					'%d',
					'%f',
					'%s',
					'%s',
					'%s',
					'%s'
					)
				);

			$log_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid` IN(%s) LIMIT 1", $sessionid ) ) ;

			$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'firstname' => $shipping_firstname,
					'lastname' => $shipping_lastname,
					'email' => $billing_email,
					'phone' => $billing_phone,

					),
					array(
					'id' => $log_id
					),
					'%s',
					'%d'
				);

			if (array_key_exists(0,$cart_items['item'])) {
				$cart_items = $cart_items['item'];
			}
			//logging to submited_form_data
			$billing_fname_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='first_name' LIMIT 1") ;
			$sql = "INSERT INTO `".WPSC_TABLE_SUBMITTED_FORM_DATA."` (log_id, form_id, value) VALUES ('".$log_id."','".$billing_fname_id."','". esc_sql( $billing_firstname ) ."')";
			$billing_lname_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='last_name' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$billing_lname_id."','" . esc_sql( $billing_lastname ) . "')";
			$billing_address_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='address' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$billing_address_id."','" . esc_sql( $billing_address ) . "')";
			$billing_city_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='city' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$billing_city_id."','" . esc_sql( $billing_city ) . "')";
			$billing_country_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='country' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$billing_country_id."','" . esc_sql( $billing_country ) . "')";
			$billing_state_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='state' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$billing_state_id."','" . esc_sql( $billing_region ) . "')";
			$shipping_fname_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_first_name' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_fname_id."','" . esc_sql( $shipping_firstname ) . "')";
			$shipping_lname_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_last_name' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_lname_id."','" . esc_sql( $shipping_lastname ) . "')";
			$shipping_address_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_address' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_address_id."','" . esc_sql( $shipping_address1 ) . " " . esc_sql( $shipping_address2 ) . "')";
			$shipping_city_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_city' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_city_id."','" . esc_sql( $shipping_city ) . "')";
			$shipping_state_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_state' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_state_id."','" . esc_sql( $shipping_region ) . "')";
			$shipping_country_id = $wpdb->get_var("SELECT `id` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type`='delivery_country' LIMIT 1") ;
			$sql .= ", ('".$log_id."','".$shipping_country_id."','" . esc_sql( $shipping_country ) . "')";

			$wpdb->query( $sql ) ;

			foreach($cart_items as $cart_item) {
				$product_id = $cart_item['merchant-item-id']['VALUE'];
				$item_name = $cart_item['item-name']['VALUE'];
				$item_desc = $cart_item['item-description']['VALUE'];
				$item_unit_price = $cart_item['unit-price']['VALUE'];
				$item_quantity = $cart_item['quantity']['VALUE'];
				$product_info = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . $wpdb->posts . "` WHERE id= %d LIMIT 1", $product_id ), ARRAY_A) ;
				$product_info = $product_info[0];
				if($product_info['notax'] != 1) {
					if(get_option('base_country') == $billing_country) {
						$country_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `isocode` IN(%s) LIMIT 1", get_option( 'base_country' ) ),ARRAY_A);
						if(($country_data['has_regions'] == 1)) {
							if(get_option('base_region') == $region_number) {
								$region_data = $wpdb->get_row( $wpdb->prepare( "SELECT `".WPSC_TABLE_REGION_TAX."`.* FROM `".WPSC_TABLE_REGION_TAX."` WHERE `".WPSC_TABLE_REGION_TAX."`.`country_id` IN(%d) AND `".WPSC_TABLE_REGION_TAX."`.`id` IN(%d)", $country_data['id'], get_option( 'base_region' ) ), ARRAY_A ) ;
							}
							$gst =  $region_data['tax'];
						} else {
							$gst =  $country_data['tax'];
						}
					} else {
						$gst = 0;
					}
				} else {
					$gst = 0;
				}

				if ($product_info['no_shipping'] == '0') {
					if ($shipping_country == get_option('base_country')) {
						$pnp = $product_info['pnp'];
					} else {
						$pnp = $product_info['international_pnp'];
					}
				} else {
					$pnp=0;
				}

				$wpdb->insert(
						WPSC_TABLE_CART_CONTENTS,
						array(
						'prodid' => $product_id,
						'purchaseid' => $log_id,
						'price' => $item_unit_price,
						'pnp' => $pnp,
						'gst' => $gst,
						'quantity' => $item_quantity,
						'donation' => $product_info['donation'],
						'no_shipping' => $product_info['no_shipping']
						),
						array(
						'%d',
						'%d',
						'%s',
						'%f',
						'%f',
						'%d',
						'%s',
						'%s',
						)
					);
				}
		}

		if ($root == "order-state-change-notification") {
			$google_order_number = $data['order-state-change-notification']['google-order-number']['VALUE'];
			$google_status=$wpdb->get_var( $wpdb->prepare( "SELECT google_status FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
			$google_status = unserialize($google_status);

			if (($google_status[0]!='Partially Charged') && ($google_status[0]!='Partially Refunded')) {
				$google_status[0]=$data['order-state-change-notification']['new-financial-order-state']['VALUE'];
				$google_status[1]=$data['order-state-change-notification']['new-fulfillment-order-state']['VALUE'];
			}
			$google_status = serialize($google_status);

			$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'google_status' => $google_status
					),
					array(
					'google_order_number' => $google_order_number
					)
				);

			if (($data['order-state-change-notification']['new-financial-order-state']['VALUE'] == 'CHARGEABLE') && (get_option('google_auto_charge') == '1')) {
				$Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type,$currency);
				$result = $Grequest->SendChargeOrder($google_order_number);

				wpsc_delete_customer_meta( 'nzshpcart' );
				wpsc_delete_customer_meta( 'coupon' );
				wpsc_delete_customer_meta( 'google_session' );

				$wpdb->update(
						WPSC_TABLE_PURCHASE_LOGS,
						array(
						'processed' => '3'
						),
						array(
						'google_order_number' => $google_order_number
						)
					);
			}
		}

		if ($root == "charge-amount-notification") {
			$google_order_number = $data['charge-amount-notification']['google-order-number']['VALUE'];
			$google_status = $wpdb->get_var( $wpdb->prepare( "SELECT google_status FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
			$google_status = unserialize($google_status);
			$total_charged = $data['charge-amount-notification']['total-charge-amount']['VALUE'];
			$google_status['partial_charge_amount'] = $total_charged;
			$totalprice = $wpdb->get_var( $wpdb->prepare( "SELECT totalprice FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
			if ($totalprice>$total_charged) {
				$google_status[0] = 'Partially Charged';
			} else if ($totalprice=$total_charged) {
				$google_status[0] = 'CHARGED';
			}
			$google_status = serialize($google_status);

			$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'google_status' => $google_status,
					),
					array(
					'google_order_number' => $google_order_number
					)
				);
			}

		if ($root == "refund-amount-notification") {
			$google_order_number = $data['refund-amount-notification']['google-order-number']['VALUE'];
			$google_status=$wpdb->get_var( $wpdb->prepare( "SELECT google_status FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number= %s", $google_order_number ) );
			$google_status = unserialize($google_status);
			$total_charged = $data['refund-amount-notification']['total-refund-amount']['VALUE'];
			$google_status['partial_refund_amount'] = $total_charged;
			$totalprice=$wpdb->get_var( $wpdb->prepare( "SELECT totalprice FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
			if ($totalprice>$total_charged) {
				$google_status[0] = 'Partially refunded';
			} else if ($totalprice=$total_charged) {
				$google_status[0] = 'REFUNDED';
			}
			$google_status = serialize($google_status);

			$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'google_status' => $google_status
					),
					array(
					'google_order_number' => $google_order_number
					)
				);
		}

		if ($root == "risk-information-notification") {
			$google_order_number = $data['risk-information-notification']['google-order-number']['VALUE'];
			$google_status=$wpdb->get_var( $wpdb->prepare( "SELECT google_status FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
			$google_status = unserialize($google_status);
			$google_status['cvn']=$data['risk-information-notification']['risk-information']['cvn-response']['VALUE'];
			$google_status['avs']=$data['risk-information-notification']['risk-information']['avs-response']['VALUE'];
			$google_status['protection']=$data['risk-information-notification']['risk-information']['eligible-for-protection']['VALUE'];
			$google_status = serialize($google_status);
			$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'google_status' => $google_status
					),
					array(
					'google_order_number' => $google_order_number
					)
				);
			if ($data['risk-information-notification']['risk-information']['cvn-response']['VALUE'] == 'E') {
				$google_risk='cvn';
			}
			if (in_array($data['risk-information-notification']['risk-information']['avs-response']['VALUE'],array('N','U'))) {
				if (isset($google_risk)) {
					$google_risk = 'cvn+avs';
				} else {
					$google_risk='avs';
				}
			}
			if (isset($google_risk)) {
				$wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
					'google_risk' => $google_risk
					),
					array(
					'google_order_number' => $google_order_number
					)
				);
			}
		}

		if ($root == "order-state-change-notification") {
			$google_order_number = $data['order-state-change-notification']['google-order-number']['VALUE'];
			if ($data['order-state-change-notification']['new-financial-order-state']['VALUE'] == "CANCELLED_BY_GOOGLE") {
				$google_status = $wpdb->get_var( $wpdb->prepare( "SELECT google_status FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE google_order_number = %s", $google_order_number ) );
				$google_status = unserialize($google_status);
				$google_status[0] = "CANCELLED_BY_GOOGLE";

				$wpdb->update(
						WPSC_TABLE_PURCHASE_LOGS,
						array(
						'google_status' => serialize( $google_status )
						),
						array(
						'google_order_number' => $google_order_number
						)
					);

				}
		}
		exit();
	}
}

add_action('init', 'nzsc_googleResponse');

?>
