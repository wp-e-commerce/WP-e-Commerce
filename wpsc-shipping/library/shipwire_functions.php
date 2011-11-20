<?php
function shipwire_build_xml($log_id) {
	global $wpdb;
	$email = get_option("shipwireemail"); 
	$passwd = get_option("shipwirepassword"); 
	$server = "Production"; // or "Production" 
	$warehouse = "00";
	$form_info = $wpdb->get_results("SELECT * FROM ".WPSC_TABLE_CHECKOUT_FORMS."", ARRAY_A);
	
	foreach ($form_info as $info) {
		if(($info['type'] == 'delivery_address') && ($info['active']=='1')) {
			$delivery_address=true;
		} else if(($info['type'] == 'delivery_city') && ($info['active']=='1')) {
			$delivery_city=true;
		} else if(($info['type'] == 'delivery_state') && ($info['active']=='1')) {
			$delivery_state=true;
		} else if(($info['type'] == 'delivery_country') && ($info['active']=='1')) {
			$delivery_country=true;
		}
	}
	
	foreach ($form_info as $info) {
		if ((($info['type'] == 'delivery_address') && ($info['active']=='1')) || (!$delivery_address && ($info['type'] == 'address') && ($info['active']=='1'))) {
			$address_key = $info['id'];
		} else if((($info['type'] == 'delivery_city') && ($info['active']=='1')) || (!$delivery_city && ($info['type'] == 'city') && ($info['active']=='1'))) {
			$city_key = $info['id'];
		} else if((($info['type'] == 'delivery_state') && ($info['active']=='1')) || (!$delivery_state && ($info['type'] == 'state') && ($info['active']=='1'))) {
			$state_key = $info['id'];
		} else if((($info['type'] == 'delivery_country') && ($info['active']=='1')) || (!$delivery_country && ($info['type'] == 'country') && ($info['active']=='1'))) {
			$country_key = $info['id'];
		} else if(($info['type'] == 'delivery_first_name') && ($info['active']=='1')) {
			$first_name_key = $info['id'];
		} else if(($info['type'] == 'delivery_last_name') && ($info['active']=='1')) {
			$last_name_key = $info['id'];
		}
	}
	
	$user_infos = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".WPSC_TABLE_SUBMITED_FORM_DATA." WHERE log_id = %d", $log_id ), ARRAY_A);
	foreach ($user_infos as $user_info) {
		if ($user_info['form_id'] == $address_key) {
			$address = $user_info['value'];
		}
		if ($user_info['form_id'] == $city_key) {
			$city = $user_info['value'];
		}
		if ($user_info['form_id'] == $state_key) {
			$state = $user_info['value'];
		}
		if ($user_info['form_id'] == $country_key) {
			$country = $user_info['value'];
		}
		if ($user_info['form_id'] == $first_name_key) {
			$first_name = $user_info['value'];
		}
		if ($user_info['form_id'] == $last_name_key) {
			$last_name = $user_info['value'];
		}
	}
	if (($first_name_key == '') || ($last_name_key == '')) {
		$log_info = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".WPSC_TABLE_PURCHASE_LOGS." WHERE id= %d", $log_id ) );
		$first_name = $log_info[0]['firstname'];
		$last_name = $log_info[0]['lastname'];
	}
	$full_name = $first_name." ".$last_name;
	$products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".WPSC_TABLE_CART_CONTENTS." WHERE purchaseid = %d", $log_id ),ARRAY_A);
	$xml = "<?xml version='1.0' encoding='utf-8'?>";
	$xml .= "<OrderList>";
	$xml .= "<EmailAddress>$email</EmailAddress>";
	$xml .= "<Password>$passwd</Password>";
	$xml .= "<Server>$server</Server>";
	$xml .= "<Referer>$server</Referer>";
	$xml .= "<Warehouse>$warehouse</Warehouse>";
	$xml .= "<Order id='".$log_id."'>";
	$xml .= "<AddressInfo type='ship'>";
	$xml .= "<Name>";
	$xml .= "<Full>".$full_name."</Full>";
	$xml .= "</Name>";
	$xml .= "<Address1>".$address."</Address1>";
	$xml .= "<Address2></Address2>";
	$xml .= "<City>".$city."</City>";
	$xml .= "<State>".$state."</State>";
	$xml .= "<Country>".$country."</Country>";
	$xml .= "<Zip>"."5011"."</Zip>";
	$xml .= "<Phone>"."3030303030"."</Phone>";
	$xml .= "<Email>"."hanzhimeng@gmail.com"."</Email>";
	$xml .= "</AddressInfo>";
	$xml .= "<Shipping>UPS Ground</Shipping>";//to be changed.
	//$xml 
	foreach($products as $product) {
		$xml .= "<Item num='0'>";
		$xml .="<Code>"."Book1"."</Code>";
		$xml .= "<Quantity>".$product['quantity']."</Quantity>";
		$xml .= "<Description>Austin Powers World Ransom</Description>";
		$xml .= "<Length>3</Length>";
		$xml .= "<Width>1</Width>";
		$xml .= "<Height>1</Height>";
		$xml .= "<Weight>1</Weight>";
		$xml .= "<DeclaredValue>".$product['price']."</DeclaredValue>";
		$xml .= "</Item>";
	}
	
	$xml .="</Order>";
	$xml .="</OrderList>";
	
	return $xml;
}

function shipwire_built_sync_xml() {
	global $wpdb;
	$email = get_option("shipwireemail"); 
	$passwd = get_option("shipwirepassword"); 
	$server = "Production"; // or "Production" 
	$warehouse = "00";
	$xml = "<?xml version='1.0' encoding='utf-8'?>";
	$xml .= "<InventoryUpdate>";
	$xml .= "<EmailAddress>".$email."</EmailAddress>";
	$xml .= "<Password>".$passwd."</Password>";
	$xml .= "<Server>".$server."</Server>";
	$xml .= "<Warehouse></Warehouse>";
	$xml .= "<ProductCode></ProductCode>";
	$xml .= "</InventoryUpdate>";
	
	return $xml;
}

function shipwire_built_tracking_xml() {
	global $wpdb;
	$email = get_option("shipwireemail");
	$passwd = get_option("shipwirepassword");
	$server = "Production";
	$warehouse = "00";
	$xml = "<?xml version='1.0' encoding='utf-8'?>";
	$xml .= "<TrackingUpdate>";
	$xml .= "<EmailAddress>".$email."</EmailAddress>";
	$xml .= "<Password>".$passwd."</Password>";
	$xml .= "<Server>".$server."</Server>";
	$xml .= "<Bookmark>1</Bookmark>";
	$xml .= "</TrackingUpdate>";
	return $xml;
}

function shipwire_send_sync_request($xml) {
	$OrderList = urlencode($xml);
	$shipwire_ch = curl_init("https://www.shipwire.com/exec/InventoryServices.php");
	curl_setopt ($shipwire_ch, CURLOPT_POST, 1);
	curl_setopt ($shipwire_ch, CURLOPT_HTTPHEADER, array('Accept: application/xml', "Content-type:application/x-www-form-urlencoded"));
	curl_setopt ($shipwire_ch, CURLOPT_POSTFIELDS, "InventoryUpdateXML=".$OrderList);
	ob_start();
	curl_exec($shipwire_ch);
	$orderSubmitted = ob_get_contents();
	ob_end_clean();
	
	return $orderSubmitted;
}

function shipwire_sent_request($xml) {
	$OrderList = urlencode($xml);
	$shipwire_ch = curl_init("https://www.shipwire.com/exec/FulfillmentServices.php");
	curl_setopt ($shipwire_ch, CURLOPT_POST, 1);
	curl_setopt ($shipwire_ch, CURLOPT_HTTPHEADER, "Content-type:"."application/x-www-form-urlencoded");
	curl_setopt ($shipwire_ch, CURLOPT_POSTFIELDS, "InventoryUpdateXML=".$OrderList);
	ob_start();
	curl_exec($shipwire_ch);
	$orderSubmitted = ob_get_contents();
	ob_end_clean();
	
	return $orderSubmitted;
}

function shipwire_send_tracking_request($xml) {
	$OrderList = urlencode($xml);
	$shipwire_ch = curl_init("https://www.shipwire.com/exec/TrackingServices.php");
	curl_setopt ($shipwire_ch, CURLOPT_POST, 1);
	curl_setopt ($shipwire_ch, CURLOPT_HTTPHEADER, "Content-type:"."application/x-www-form-urlencoded");
	curl_setopt ($shipwire_ch, CURLOPT_POSTFIELDS, "InventoryUpdateXML=".$OrderList);
	ob_start();
	curl_exec($shipwire_ch);
	$orderSubmitted = ob_get_contents();
	ob_end_clean();
	
	return $orderSubmitted;
}
?>