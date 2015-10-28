<?php
$nzshpcrt_gateways[$num]['name'] = __( 'ChronoPay', 'wp-e-commerce' );
$nzshpcrt_gateways[$num]['internalname'] = 'chronopay';
$nzshpcrt_gateways[$num]['function'] = 'gateway_chronopay';
$nzshpcrt_gateways[$num]['form'] = "form_chronopay";
$nzshpcrt_gateways[$num]['submit_function'] = "submit_chronopay";
$nzshpcrt_gateways[$num]['payment_type'] = "credit_card";
$nzshpcrt_gateways[$num]['display_name'] = __( 'Credit Card', 'wp-e-commerce' );
$nzshpcrt_gateways[$num]['image'] = WPSC_URL . '/images/cc.gif';

function gateway_chronopay($separator, $sessionid)
{
	global $wpdb;
	$purchase_log_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= %s LIMIT 1", $sessionid );
	$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;

	$cart_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='".$purchase_log[0]['id']."'";
	$cart = $wpdb->get_results($cart_sql,ARRAY_A) ;

	// ChronoPay post variables
	$chronopay_url = get_option('chronopay_url');

	$data['product_id'] = get_option('chronopay_product_id');
	$data['product_name'] = get_option('chronopay_product_name');
	$data['product_price_currency'] = get_option('chronopay_curcode');
	$data['language'] = get_option('chronopay_language');
	$data['cb_url'] = add_query_arg( 'chronopay_callback', 'true', home_url( '/' ) );
	$data['cb_type'] = 'P';
	$data['decline_url'] = home_url( '/?chronopay_callback=true' );
	$data['cs1'] = $sessionid;
	$data['cs2'] = 'chronopay';
	$salt = get_option('chronopay_salt');
	$data['cs3'] = md5($salt . md5($sessionid . $salt));	// placed in here for security so that the return call can be validated as 'real'

	// User details
	if($_POST['collected_data'][get_option('chronopay_form_first_name')] != '')
    {
    	$data['f_name'] = $_POST['collected_data'][get_option('chronopay_form_first_name')];
    }
	if($_POST['collected_data'][get_option('chronopay_form_last_name')] != "")
    {
    	$data['s_name'] = $_POST['collected_data'][get_option('chronopay_form_last_name')];
    }
  	if($_POST['collected_data'][get_option('chronopay_form_address')] != '')
    {
    	$data['street'] = str_replace("\n",', ', $_POST['collected_data'][get_option('chronopay_form_address')]);
    }
   	if($_POST['collected_data'][get_option('chronopay_form_city')] != '')
    {
    	$data['city'] = $_POST['collected_data'][get_option('chronopay_form_city')];
    }

    	$data['country'] = (string) wpsc_get_customer_meta( 'billingcountry' );

  	// Change suggested by waxfeet@gmail.com, if email to be sent is not there, dont send an email address
  	$email_data = $wpdb->get_results("SELECT `id`,`type` FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` IN ('email') AND `active` = '1'",ARRAY_A);
  	foreach((array)$email_data as $email)
    {
    	$data['email'] = $_POST['collected_data'][$email['id']];
    }
  	if(($_POST['collected_data'][get_option('email_form_field')] != null) && ($data['email'] == null))
    {
    	$data['email'] = $_POST['collected_data'][get_option('email_form_field')];
    }

	// Get Currency details abd price
	$currency_code = WPSC_Countries::get_currency_code( get_option( 'currency_type' ) );
	$local_currency_code = $currency_code[0]['code'];
	$chronopay_currency_code = get_option('chronopay_curcode');

	// ChronoPay only processes in the set currency.  This is USD or EUR dependent on what the Chornopay account is set up with.
	// This must match the ChronoPay settings set up in wordpress.  Convert to the chronopay currency and calculate total.
	$curr=new CURRENCYCONVERTER();
	$decimal_places = 2;
	$total_price = 0;

	$i = 1;

	$all_donations = true;
	$all_no_shipping = true;

	foreach($cart as $item)
	{
		$product_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . $wpdb->posts . "` WHERE `id`= %d LIMIT 1", $item['prodid'] ), ARRAY_A );
		$product_data = $product_data[0];
		$variation_count = count($product_variations);

		//Does this even still work in 3.8? We're not using this table.
		$variation_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CART_ITEM_VARIATIONS."` WHERE `cart_id` = %d", $item['id'] );
		$variation_data = $wpdb->get_results( $variation_sql, ARRAY_A );
		$variation_count = count($variation_data);

		if($variation_count >= 1)
      	{
      		$variation_list = " (";
      		$j = 0;

      		foreach($variation_data as $variation)
        	{
        		if($j > 0)
          		{
          			$variation_list .= ", ";
          		}
        		$value_id = $variation['venue_id'];
        		$value_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_VARIATION_VALUES."` WHERE `id`= %d LIMIT 1", $value_id ), ARRAY_A);
        		$variation_list .= $value_data[0]['name'];
        		$j++;
        	}
      		$variation_list .= ")";
      	}
      	else
        {
        	$variation_list = '';
        }

    	$local_currency_productprice = $item['price'];

			$local_currency_shipping = $item['pnp'];


			$chronopay_currency_productprice = $local_currency_productprice;
			$chronopay_currency_shipping = $local_currency_shipping;

    	$data['item_name_'.$i] = $product_data['name'].$variation_list;
    	$data['amount_'.$i] = number_format(sprintf("%01.2f", $chronopay_currency_productprice),$decimal_places,'.','');
    	$data['quantity_'.$i] = $item['quantity'];
    	$data['item_number_'.$i] = $product_data['id'];

		if($item['donation'] !=1)
      	{
      		$all_donations = false;
      		$data['shipping_'.$i] = number_format($chronopay_currency_shipping,$decimal_places,'.','');
      		$data['shipping2_'.$i] = number_format($chronopay_currency_shipping,$decimal_places,'.','');
      	}
      	else
      	{
      		$data['shipping_'.$i] = number_format(0,$decimal_places,'.','');
      		$data['shipping2_'.$i] = number_format(0,$decimal_places,'.','');
      	}

    	if($product_data['no_shipping'] != 1) {
      		$all_no_shipping = false;
      	}


		$total_price = $total_price + ($data['amount_'.$i] * $data['quantity_'.$i]);

		if( $all_no_shipping != false )
			$total_price = $total_price + $data['shipping_'.$i] + $data['shipping2_'.$i];

    	$i++;
	}
  	$base_shipping = $purchase_log[0]['base_shipping'];
  	if(($base_shipping > 0) && ($all_donations == false) && ($all_no_shipping == false))
    {
		$data['handling_cart'] = number_format($base_shipping,$decimal_places,'.','');
		$total_price += number_format($base_shipping,$decimal_places,'.','');
    }

	$data['product_price'] = $total_price;

	// Create Form to post to ChronoPay
	$output = "
		<form id=\"chronopay_form\" name=\"chronopay_form\" method=\"post\" action=\"$chronopay_url\">\n";

	foreach($data as $n=>$v) {
			$output .= "			<input type=\"hidden\" name=\"$n\" value=\"$v\" />\n";
	}

	$output .= "			<input type=\"submit\" value=\"Continue to ChronoPay\" />
		</form>
	";

	// Output the form.
	echo $output;
	echo "<script language=\"javascript\" type=\"text/javascript\">document.getElementById('chronopay_form').submit();</script>";
  	exit();
}

function nzshpcrt_chronopay_callback()
{
	global $wpdb;
	// needs to execute on page start
	// look at page 36
	if(isset($_GET['chronopay_callback']) && ($_GET['chronopay_callback'] == 'true') && ($_POST['cs2'] == 'chronopay'))
	{
    	// This is a call from chronopay.  validate that it is from a chronopay server in the and process.
		// validate cs3 variable to see if it makes sense for security
		$salt = get_option('chronopay_salt');
		$gen_hash = md5($salt . md5($_POST['cs1'] . $salt));

		if($gen_hash == $_POST['cs3'])
		{
			// Added in to fake a TX number for testing.  ChronoPay dev accounts do not return a trans_id.
			//if($_POST['transaction_id'] == '')
			//	$_POST['transaction_id'] = 'testid123123';

			// process response.
		    $sessionid = trim(stripslashes($_POST['cs1']));
			$transaction_id = trim(stripslashes($_POST['transaction_id']));
			$verification_data['trans_id'] = trim(stripslashes($_POST['transaction_id']));
			$verification_data['trans_type'] = trim(stripslashes($_POST['transaction_type']));

			switch($verification_data['trans_type'])
			{
				case 'onetime': // All successful processing statuses.
	            case 'initial':
				case 'rebill':
					$data = array(
						'processed'  => 2,
						'transactid' => $transact_id,
						'date'       => time(),
					);
					wpsc_update_purchase_log_details( $sessionid, $data, 'sessionid' );
					transaction_results($sessionid, false, $transaction_id);
	            	break;

	            case 'decline': // if it fails, delete it
					$log_id = $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`=%s LIMIT 1", $sessionid ) );
	            	$delete_log_form_sql = $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=%d", $log_id );
	            	$cart_content = $wpdb->get_results($delete_log_form_sql,ARRAY_A);
	            	foreach((array)$cart_content as $cart_item)
	              	{
	              		$cart_item_variations = $wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_CART_ITEM_VARIATIONS."` WHERE `cart_id` = %d", $cart_item['id'] ), ARRAY_A);
	              	}
	            	$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`=%d", $log_id ) );
	            	$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_SUBMITTED_FORM_DATA."` WHERE `log_id` IN ( %d )", $log_id ) );
	            	$wpdb->query( $wpdb->prepare( "DELETE FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `id`=%d LIMIT 1", $log_id ) );
	            	break;

	            case 'Pending':      // need to wait for "Completed" before processing
					$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, array( 'transactid' => $transaction_id, 'date' => time() ), array( 'sessionid' => $sessionid ), array( '%d', '%s' ) );
	            	break;

	            default: // if nothing, do nothing, safest course of action here.
	            	break;

			}
		}
		else
		{
			// Security Hash failed!!.. notify someone..
			$message = "This message has been sent because a call to your ChronoPay function was made by a server that did not have the correct security key.  This could mean someone is trying to hack your payment site.  The details of the call are below.\n\r\n\r";
			$message .= "OUR_POST:\n\r".print_r($header . $req,true)."\n\r\n\r";
			$message .= "THEIR_POST:\n\r".print_r($_POST,true)."\n\r\n\r";
			$message .= "GET:\n\r".print_r($_GET,true)."\n\r\n\r";
			$message .= "SERVER:\n\r".print_r($_SERVER,true)."\n\r\n\r";
			mail(get_option('purch_log_email'), "ChronoPay Security Key Failed!", $message);
		}
	}
}

function nzshpcrt_chronopay_results()
{
	// Function used to translate the ChronoPay returned cs1=sessionid POST variable into the recognised GET variable for the transaction results page.
	if(isset($_POST['cs1']) && ($_POST['cs1'] !='') && ($_GET['sessionid'] == ''))
	{
		$_GET['sessionid'] = $_POST['cs1'];
	}
}

function submit_chronopay()
{
	if(isset($_POST['chronopay_product_id']))
    {
    	update_option('chronopay_product_id', sanitize_text_field( $_POST['chronopay_product_id'] ) );
    }

  	if(isset($_POST['chronopay_product_name']))
    {
    	update_option('chronopay_product_name', sanitize_text_field( $_POST['chronopay_product_name'] ) );
    }

  	if(isset($_POST['chronopay_curcode']))
    {
    	update_option('chronopay_curcode', sanitize_text_field( $_POST['chronopay_curcode'] ) );
    }

  	if(isset($_POST['chronopay_language']))
    {
    	update_option('chronopay_language', sanitize_text_field( $_POST['chronopay_language'] ) );
    }

  	if(isset($_POST['chronopay_url']))
    {
    	update_option('chronopay_url', esc_url_raw( $_POST['chronopay_url'] ) );
    }

 	if(isset($_POST['chronopay_salt']))
    {
    	update_option('chronopay_salt', sanitize_text_field( $_POST['chronopay_salt'] ) );
    }

    if (!isset($_POST['chronopay_form'])) $_POST['chronopay_form'] = array();
	foreach((array)$_POST['chronopay_form'] as $form => $value)
    {
    	update_option(('chronopay_form_'.sanitize_text_field( $form ) ), sanitize_text_field( $value ) );
    }
	return true;
}

function form_chronopay()
{
	$select_currency[get_option('chronopay_curcode')] = "selected='selected'";
	$select_language[get_option('chronopay_language')] = "selected='selected'";
	$chronopay_url = ( get_option('chronopay_url')=='' ? 'https://secure.chronopay.com/index_shop.cgi' : get_option('chronopay_url') );
	$chronopay_salt = ( get_option( 'chronopay_salt' ) == '' ? wp_generate_password( 24, true, true ) : get_option( 'chronopay_salt' ) );

	if (!isset($select_currency['USD'])) $select_currency['USD'] = '';
	if (!isset($select_currency['EUR'])) $select_currency['EUR'] = '';
	if (!isset($select_language['EN'])) $select_language['EN'] = '';
	if (!isset($select_language['ES'])) $select_language['ES'] = '';
	if (!isset($select_language['NL'])) $select_language['NL'] = '';
	if (!isset($select_language['RU'])) $select_language['RU'] = '';

	$output = "
		<tr>
			<td>" . __( 'Product ID', 'wp-e-commerce' ) . "</td>
			<td>
				<input type='text' size='40' value='" . get_option( 'chronopay_product_id' ) . "' name='chronopay_product_id' />
				<p class='description'>
					" . __( 'This should be set to your product ID that has been set up in the ChronoPay client interface. This is the ChronoPay product that all purchases will be processed against. The cost will be changed depending on the grand total of the users cart.', 'wp-e-commerce' ) . "
				</p>
			</td>
		</tr>
		<tr>
			<td>" . __( 'Product Name', 'wp-e-commerce' ) . "</td>
			<td>
				<input type='text' size='40' value='" . get_option( 'chronopay_product_name' ) . "' name='chronopay_product_name' />
				<p class='description'>
					" . __( 'This is not important and is usually set to the name of the web shop. It is displayed on the ChronoPay secure processing page.', 'wp-e-commerce' ) . "
				</p>
		</tr>
		<tr>
			<td>" . __( 'Accepted Currency', 'wp-e-commerce' ) . "</td>
			<td>
				<select name='chronopay_curcode'>
					<option " . $select_currency['USD'] . " value='USD'>" . __( 'USD - U.S. Dollar', 'wp-e-commerce' ) . "</option>
					<option " . $select_currency['EUR'] . " value='EUR'>" . __( 'EUR - Euros', 'wp-e-commerce' ) . "</option>
				</select>
				<p class='description'>
					" . __( 'The currency code that ChronoPay will process the payment in. All products must be set up in this currency.', 'wp-e-commerce' ) . "
				</p>
		</tr>
		<tr>
			<td>" . __( 'Language', 'wp-e-commerce' ) . "</td>
			<td>
				<select name='chronopay_language'>
					<option " . $select_language['EN'] . " value='EN'>Engish</option>
					<option " . $select_language['ES'] . " value='ES'>Spanish</option>
					<option " . $select_language['NL'] . " value='NL'>Dutch</option>
					<option " . $select_language['RU'] . " value='RU'>Russian</option>
				</select>
				<p class='description'>
					" . __( 'The language that the ChronoPay secure processing page will be displayed in.', 'wp-e-commerce' ) . "
				</p>
		</tr>
		<tr>
			<td>" . __( 'Processing URL', 'wp-e-commerce' ) . "</td>
			<td>
				<input type='text' size='40' value='" . $chronopay_url . "' name='chronopay_url' />
				<p class='description'>
					" . __( 'URL of the secure payment page customers are sent to for payment processing. If unsure leave at default setting.', 'wp-e-commerce' ) . "
				</p>
		</tr>
		<tr>
			<td>" . __( 'Return URL', 'wp-e-commerce' ) . "</td>
			<td>
				<input type='text' size='40' value='".get_option('transact_url')."' name='chronopay_return_url' />
				<p class='description'>
					" . __( 'Enter this URL in the ChronoPay web client against the Product ID that you have set up. This page is the transaction details page that you have configured in Shop Options.  It can not be edited on this page.', 'wp-e-commerce' ) . "
				</p>
		</tr>
		<tr>
			<td>" . __( 'Security Key', 'wp-e-commerce' ) . "</td>
			<td>
				<input type='text' size='40' value='" . $chronopay_salt . "' name='chronopay_salt' />
				<p class='description'>
					" . __( 'A bit of security... This is a keyword that is used to ensure transaction approval calls from ChronoPay to this application are real and were instigated from this server.  Enter a unique word into this field.' , 'wp-e-commerce' ) . "
				</p>
		</tr>

		<tr class='firstrowth'>
			<td style='border-bottom: medium none;' colspan='2'>
				<strong class='form_group'>" . __( 'Forms Sent to Gateway', 'wp-e-commerce' ) . "</strong>
			</td>
		</tr>

		<tr>
			<td>" . __( 'First Name Field', 'wp-e-commerce' ) . "</td>
			<td>
				<select name='chronopay_form[first_name]'>
				" . nzshpcrt_form_field_list(get_option('chronopay_form_first_name')) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'Last Name Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[last_name]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_last_name' ) ) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'Address Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[address]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_address' ) ) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'City Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[city]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_city' ) ) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'State Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[state]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_state' ) ) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'Postal / ZIP Code Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[post_code]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_post_code' ) ) . "
				</select>
			</td>
		</tr>
		<tr>
			<td>" . __( 'Country Field', 'wp-e-commerce' ) . "</td>
			<td><select name='chronopay_form[country]'>
				" . nzshpcrt_form_field_list( get_option ( 'chronopay_form_country' ) ) . "
				</select>
			</td>
		</tr>
		   <tr>
           <td colspan='2'>
           	" . sprintf( __( 'For more help configuring Chronopay, read our documentation <a href="%s">here</a>', 'wp-e-commerce' ), esc_url( 'https://docs.wpecommerce.org/documentation/chronopay/' ) ) . "
           </td>
       </tr>";

	return $output;
}


add_action('init', 'nzshpcrt_chronopay_callback');
add_action('init', 'nzshpcrt_chronopay_results');

?>
