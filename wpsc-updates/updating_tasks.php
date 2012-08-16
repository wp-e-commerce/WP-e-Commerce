<?php

if(get_option('wpsc_trackingid_message') == ''){
	update_option('wpsc_trackingid_message', __('Your purchase from %shop_name% has just been dispatched. It should arrive soon. To keep track of your products status a tracking id has been attached. \r\n your tracking id is: %trackid%', 'wpsc'));
}
if(get_option('wpsc_trackingid_subject') == ''){
	update_option('wpsc_trackingid_subject', __('Your Order from %shop_name% has been dispatched', 'wpsc'));
}

if($wpdb->get_results("SHOW FULL COLUMNS FROM `".WPSC_TABLE_REGION_TAX."` LIKE 'code';",ARRAY_A)) {

  if($wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_REGION_TAX."` WHERE `code` NOT IN ('')") < 51) {
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'AL' WHERE `name` IN ('Alabama') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'AK' WHERE `name` IN ('Alaska') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'AZ' WHERE `name` IN ('Arizona') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'AR' WHERE `name` IN ('Arkansas') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'CA' WHERE `name` IN ('California') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'CO' WHERE `name` IN ('Colorado') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'CT' WHERE `name` IN ('Connecticut') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'DE' WHERE `name` IN ('Delaware') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'FL' WHERE `name` IN ('Florida') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'GA' WHERE `name` IN ('Georgia')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'HI' WHERE `name` IN ('Hawaii')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'ID' WHERE `name` IN ('Idaho')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'IL' WHERE `name` IN ('Illinois')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'IN' WHERE `name` IN ('Indiana')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'IA' WHERE `name` IN ('Iowa')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'KS' WHERE `name` IN ('Kansas')  LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'KY' WHERE `name` IN ('Kentucky') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'LA' WHERE `name` IN ('Louisiana') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'ME' WHERE `name` IN ('Maine') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MD' WHERE `name` IN ('Maryland') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MA' WHERE `name` IN ('Massachusetts') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MI' WHERE `name` IN ('Michigan') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MN' WHERE `name` IN ('Minnesota') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MS' WHERE `name` IN ('Mississippi') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MO' WHERE `name` IN ('Missouri') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'MT' WHERE `name` IN ('Montana') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NE' WHERE `name` IN ('Nebraska') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NV' WHERE `name` IN ('Nevada') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NH' WHERE `name` IN ('New Hampshire') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NJ' WHERE `name` IN ('New Jersey') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NM' WHERE `name` IN ('New Mexico') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NY' WHERE `name` IN ('New York') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'NC' WHERE `name` IN ('North Carolina') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'ND' WHERE `name` IN ('North Dakota') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'OH' WHERE `name` IN ('Ohio') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'OK' WHERE `name` IN ('Oklahoma') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'OR' WHERE `name` IN ('Oregon') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'PA' WHERE `name` IN ('Pennsylvania') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'RI' WHERE `name` IN ('Rhode Island') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'SC' WHERE `name` IN ('South Carolina') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'SD' WHERE `name` IN ('South Dakota') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'TN' WHERE `name` IN ('Tennessee') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'TX' WHERE `name` IN ('Texas') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'UT' WHERE `name` IN ('Utah') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'VT' WHERE `name` IN ('Vermont') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'VA' WHERE `name` IN ('Virginia') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'WA' WHERE `name` IN ('Washington') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'DC' WHERE `name` IN ('Washington DC') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'WV' WHERE `name` IN ('West Virginia') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'WI' WHERE `name` IN ('Wisconsin') LIMIT 1 ;");
    $wpdb->query("UPDATE `".WPSC_TABLE_REGION_TAX."` SET `code` = 'WY' WHERE `name` IN ('Wyoming') LIMIT 1 ;");
  }
}


// here is the code to update the payment gateway options.
$selected_gateways = array();
$current_gateway = get_option('payment_gateway');
$selected_gateways = get_option('custom_gateway_options');
if($current_gateway == '') {
  // set the gateway to Manual Payment if it is not set.
  $current_gateway = 'testmode';
}
if(get_option('payment_method') != null) {
	switch(get_option('payment_method')) {
		case 2:
		// mode 2 is credit card and manual payment / test mode
		if($current_gateway == 'testmode') {
			$current_gateway = 'paypal_multiple';
		}
		$selected_gateways[] = 'testmode';
		$selected_gateways[] = $current_gateway;
		break;

		case 3;
		// mode 3 is manual payment / test mode
		$current_gateway = 'testmode';
		case 1:
		// mode 1 is whatever gateway is currently selected.
		default:
		$selected_gateways[] = $current_gateway;
		break;
	}
	update_option('custom_gateway_options', $selected_gateways);
	update_option('payment_method', null);
}


// switch this variable over to our own option name, seems default_category was used by wordpress
if(get_option('wpsc_default_category') == null) {
  update_option('wpsc_default_category', get_option('default_category'));
}

if($wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `continent` NOT IN ('')") <230) {
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='1'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='2'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='3'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='4'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='5'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='6'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='7'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='8'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='9'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='10'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='11'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='12'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='13'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='14'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='15'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='16'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='17'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='18'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='19'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='20'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='21'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='22'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='23'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='24'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='25'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='26'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='27'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='28'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='29'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='30'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='31'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='32'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='33'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='34'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='35'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='36'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='37'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='38'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='39'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='40'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='41'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='42'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='43'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='44'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='45'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='46'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='47'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='48'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='49'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='50'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='51'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='52'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='53'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='54'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='55'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='56'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='57'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='58'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='59'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='60'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='61'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='62'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='63'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='64'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='65'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='66'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='67'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='68'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='69'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='70'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='71'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='72'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='73'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='74'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='75'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='76'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='77'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='78'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='79'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='80'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='81'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='82'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='83'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='84'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='85'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamrica' WHERE id='86'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='87'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='88'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='89'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='90'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='91'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='92'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='93'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='94'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='95'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='96'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='97'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='98'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='99'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='100'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='101'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='102'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='103'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='104'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='105'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='106'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='107'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='108'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='109'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='110'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='111'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='112'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='113'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='114'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='115'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='116'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='117'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='118'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='119'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='120'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='121'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='122'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='123'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='124'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='125'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='126'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='127'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='antarctica' WHERE id='128'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='129'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='130'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='131'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='132'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='133'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='134'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='135'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='136'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='137'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='138'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='139'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='140'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='141'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='142'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='143'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='144'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='145'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='146'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='147'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='148'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='149'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='150'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='151'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='152'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='153'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='154'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='155'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='156'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='157'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='158'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='159'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='160'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='161'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='162'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='163'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='164'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='165'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='166'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='167'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='168'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='169'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='170'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='171'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='172'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='173'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='174'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='175'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='176'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='177'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='178'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='179'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='180'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='181'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='182'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='183'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='184'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='185'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='186'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='187'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='188'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='189'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='190'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='191'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='192'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='193'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='194'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='195'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='196'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='197'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='198'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='199'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='200'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='201'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='202'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='203'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='southamerica' WHERE id='204'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='' WHERE id='205'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='206'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='207'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='208'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='209'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='210'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='211'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='212'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='213'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='214'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='215'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='216'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='217'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='218'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='219'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='220'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='' WHERE id='221'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='222'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='223'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='224'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='225'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='226'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='227'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='' WHERE id='228'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='229'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='230'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='231'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='232'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='233'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='234'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='northamerica' WHERE id='235'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='236'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='237'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='asiapacific' WHERE id='238'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='239'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='240'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='africa' WHERE id='241'");
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET continent='europe' WHERE id='242'");
}


if($wpdb->get_var("SELECT COUNT(*) FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `continent` IN ('asiapasific')") > 0) {
	$wpdb->query("UPDATE `".WPSC_TABLE_CURRENCY_LIST."` SET `continent`='asiapacific' WHERE `continent`='asiapasific'");
}

add_option('wpsc_email_receipt', '', __('Thank you for purchasing with %shop_name%, any items to be shipped will be processed as soon as possible, any items that can be downloaded can be downloaded using the links on this page.All prices include tax and postage and packaging where applicable.You ordered these items:%product_list%%total_shipping%%total_price%', 'wpsc'), 'yes');
add_option('wpsc_email_admin', '', __('%product_list%%total_shipping%%total_price%', 'wpsc'), 'yes');

if(get_option('wpsc_email_receipt') == '') {
	if(get_option('email_receipt') != '') {
		update_option('wpsc_email_receipt', get_option('email_receipt'));
	} else {
		update_option('wpsc_email_receipt', __('Thank you for purchasing with %shop_name%, any items to be shipped will be processed as soon as possible, any items that can be downloaded can be downloaded using the links on this page.All prices include tax and postage and packaging where applicable.You ordered these items:%product_list%%total_shipping%%total_price%', 'wpsc'));
	}
}
if(get_option('wpsc_email_admin') == '') {
  if(get_option('email_admin') != '') {
		update_option('wpsc_email_admin', get_option('email_admin'));
	} else {
		update_option('wpsc_email_admin', __('%product_list%%total_shipping%%total_price%', 'wpsc'));
	}
}

if($wpdb->get_var("SELECT `option_id` FROM `{$wpdb->options}` WHERE `option_name` LIKE 'custom_gateway_options'") < 1) {
		update_option('custom_gateway_options', array('testmode'));
}

if((get_option('flat_rates') == null) || (count(get_option('flat_rates')) < 1)) {
	$local_shipping = get_option('base_local_shipping');
	$international_shipping = get_option('base_international_shipping');

	// Local Shipping Settings
	$shipping['local'] = $local_shipping;

	$shipping['southisland'] = $local_shipping;
	$shipping['northisland'] = $local_shipping;

	// International Shipping Settings
	$shipping['continental'] = $international_shipping;
	$shipping['all'] = $international_shipping;
	$shipping['canada'] = $international_shipping;

	$shipping['northamerica'] = $international_shipping;
	$shipping['southamerica'] = $international_shipping;
	$shipping['asiapacific'] = $international_shipping;
	$shipping['europe'] = $international_shipping;
	$shipping['africa'] = $international_shipping;

	update_option('flat_rates',$shipping);
}

if(get_option('custom_shipping_options') == null ) {
	update_option('custom_shipping_options',array('flatrate'));
}
?>