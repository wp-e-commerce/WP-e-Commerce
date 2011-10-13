<?php
/*
 * Display Settings page
 */

// Clear the previously selected shipping form session variable if you are not on the shipping page
if ( isset( $_GET['tab'] ) )
	if( $_GET['tab'] == 'shipping' )
		if( isset( $_GET['shipping_module'] ) )
			$_SESSION['previous_shipping_name'] = $_GET['shipping_module'];

function wpsc_display_settings_page() {
	WPSC_Settings_Page::get_instance()->display();
}

/*
 * Create settings page tabs
 */

function wpsc_settings_tabs() {
	return WPSC_Settings_Page::get_instance()->get_tabs();
}

/*
 * Display settings tabs
 */

function wpsc_the_settings_tabs() {
	WPSC_Settings_Page::get_instance()->output_tabs();
}

function country_list( $selected_country = null ) {
	global $wpdb;

	$output       = '';
	$output      .= "<option value=''></option>";
	$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` ORDER BY `country` ASC", ARRAY_A );

	foreach ( (array)$country_data as $country ) {
		$selected = '';

		if ( $selected_country == $country['isocode'] )
			$selected = "selected='selected'";

		$output .= "<option value='" . $country['isocode'] . "' $selected>" . htmlspecialchars( $country['country'] ) . "</option>";
	}

	return $output;
}

/*
 * Get Shipping Form for wp-admin
 */
function wpsc_get_shipping_form( $shippingname ) {
	global $wpsc_shipping_modules;

	if ( array_key_exists( $shippingname, $wpsc_shipping_modules ) ) {
		$shipping_forms       = $wpsc_shipping_modules[$shippingname]->getForm();
		$shipping_module_name = $wpsc_shipping_modules[$shippingname]->name;
		$output = array( 'name' => $shipping_module_name, 'form_fields' => $shipping_forms, 'has_submit_button' => 1 );
	} else {
		$output = array( 'name' => '&nbsp;', 'form_fields' => __( 'To configure a shipping module select one on the left.', 'wpsc' ), 'has_submit_button' => 0 );
	}

	return $output;
}

/***
 * Get Payment Form for wp-admin
 */
function wpsc_get_payment_form( $paymentname ,$selected_gateway_data='') {
	global $nzshpcrt_gateways;

	$payment_gateway_names = get_option('payment_gateway_names');
	$form                  = array();
	$output                = array( 'name' => '&nbsp;', 'form_fields' => __( 'To configure a payment module select one on the left.', 'wpsc' ), 'has_submit_button' => 0 );

	foreach ( $nzshpcrt_gateways as $gateway ) {
		if ( $gateway["internalname"] != $paymentname ) {
			continue;
		} else {
			$selected_gateway_data	= $gateway;
			$form = $gateway;
		}
	}

	if ( $form ) {
		$output ='';
		$output .="<tr>
					  <td style='border-top: none;'>
					  ".__("Display Name", 'wpsc')."
					  </td>
					  <td style='border-top: none;'>";

		if (isset($payment_gateway_names[$paymentname]) ) {
			$display_name = $payment_gateway_names[$paymentname];
		} elseif(!empty($selected_gateway_data['display_name'])){
			$display_name =$selected_gateway_data['display_name'];
		}else{
			switch($selected_gateway_data['payment_type']) {
				case "paypal";
					$display_name = "PayPal";
					break;

				case "manual_payment":
					$display_name = "Manual Payment";
					break;

				case "google_checkout":
					$display_name = "Google Checkout";
					break;

				case "credit_card":
				default:
					$display_name = "Credit Card";
					break;
			}
		}

		$output .="<input type='text' name='user_defined_name[".$paymentname."]' value='". $display_name ."' /><br />
					<span class='small description'>".__('The text that people see when making a purchase', 'wpsc')."</span>
					</td>
					</tr>";
		$payment_forms = $form["form"]();
		$payment_module_name = $form["name"];

		$output = array( 'name' => $payment_module_name, 'form_fields' => $output.$payment_forms, 'has_submit_button' => 1 );
	} else {
		$output = array( 'name' => '&nbsp;', 'form_fields' => __( 'To configure a payment module select one on the left.', 'wpsc' ), 'has_submit_button' => 0 );
	}

	return $output;
}

function wpsc_settings_page_update_notification() {

	if ( isset( $_GET['skipped'] ) || isset( $_GET['updated'] ) || isset( $_GET['regenerate'] ) || isset( $_GET['deleted'] ) || isset( $_GET['shipadd'] ) ) { ?>

	<div id="message" class="updated fade"><p>
		<?php

		if ( isset( $_GET['updated'] ) && (int)$_GET['updated'] ) {
			printf( _n( '%s Setting options updated.', ' %s Settings options updated.', $_GET['updated'], 'wpsc' ), absint( $_GET['updated'] ) );
			unset( $_GET['updated'] );
			$message = true;
		}
		if ( isset( $_GET['deleted'] ) && (int)$_GET['deleted'] ) {
			printf( _n( '%s Setting option deleted.', '%s Setting option deleted.', $_GET['deleted'], 'wpsc' ), absint( $_GET['deleted'] ) );
			unset( $_GET['deleted'] );
			$message = true;
		}
		if ( isset( $_GET['shipadd'] ) && (int)$_GET['shipadd'] ) {
			printf( _n( '%s Shipping option updated.', '%s Shipping option updated.', $_GET['shipadd'], 'wpsc' ), absint( $_GET['shipadd'] ) );
			unset( $_GET['shipadd'] );
			$message = true;
		}
		if ( isset( $_GET['added'] ) && (int)$_GET['added'] ) {
			printf( _n( '%s Checkout field added.', '%s Checkout fields added.', $_GET['added'], 'wpsc' ), absint( $_GET['added'] ) );
			unset( $_GET['added'] );
			$message = true;
		}

		if ( !isset( $message ) )
			_e( 'Settings successfully updated.', 'wpsc' );

		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'locked', 'regenerate', 'skipped', 'updated', 'deleted', 'wpsc_downloadcsv', 'rss_key', 'start_timestamp', 'end_timestamp', 'email_buyer_id' ), $_SERVER['REQUEST_URI'] ); ?>
	</p></div>

<?php
	}
}

?>