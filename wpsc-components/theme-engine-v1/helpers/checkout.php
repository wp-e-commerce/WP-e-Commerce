<?php

/**
 * returns true or false depending on whether there are checkout items or not
 * @access public
 *
 * @since 3.7
 * @return (boolean)
 */
function wpsc_have_checkout_items() {
	global $wpsc_checkout;
	return $wpsc_checkout->have_checkout_items();
}

/**
 * The checkout item sets the checkout item to the next one in the loop
 * @access public
 *
 * @since 3.7
 * @return the checkout item array
 */
function wpsc_the_checkout_item() {
	global $wpsc_checkout;
	return $wpsc_checkout->the_checkout_item();
}

/**
 * Checks shipping details
 * @access public
 *
 * @since 3.7
 * @return (boolean)
 */
function wpsc_is_shipping_details() {
	global $wpsc_checkout;
	if ( $wpsc_checkout->checkout_item->unique_name == 'delivertoafriend' && get_option( 'shippingsameasbilling' ) == '1' ) {
		return true;
	} else {
		return false;
	}
}

/**
 * returns the class for shipping and billing forms
 * @access public
 *
 * @since 3.8
 * @param $additional_classes (string) additional classes to be
 * @return
 */
function wpsc_the_checkout_details_class($additional_classes = ''){
 if(wpsc_is_shipping_details())
 	echo "class='wpsc_shipping_forms ".$additional_classes."'";
 else
 	echo "class='wpsc_billing_forms ".$additional_classes."'";

}

/**
 * Checks to see is user login form needs to be displayed
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_show_user_login_form(){
	if(!is_user_logged_in() && get_option('users_can_register') && get_option('require_register'))
		return true;
	else
		return false;
}

/**
 * checks to see whether the country and categories selected have conflicts
 * i.e products of this category cannot be shipped to selected country
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_has_category_and_country_conflict(){
	$conflict = wpsc_get_customer_meta( 'category_shipping_conflict' );
	return ( ! empty( $conflict ) );
}

/**
 * Have valid shipping zipcode
 * Logic was modified in 3.8.9 to check if the Calculate button was ever actually hit
 * @see http://code.google.com/p/wp-e-commerce/issues/detail?id=1014
 *
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_have_valid_shipping_zipcode(){
	$zip = wpsc_get_customer_meta( 'shipping_zip' );

	if( ! $zip || ( __( 'Your Zipcode', 'wpsc' ) == $zip ) && ( wpsc_get_customer_meta( 'update_location' ) ) )
		return false;
	else
		return true;

}

/**
 * show find us checks whether the 'how you found us' drop down should be displayed
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_show_find_us(){
	if(get_option('display_find_us') == '1')
		return true;
	else
		return false;
}

/**
 * disregard state fields - checks to see whether selected country has regions or not,
 * depending on the scenario will return wither a true or false
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_disregard_shipping_state_fields(){
	global $wpsc_checkout;
	if ( ! wpsc_uses_shipping() ):
		$delivery_country = wpsc_get_customer_meta( 'shipping_country' );
	 	if ( 'shippingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions( $delivery_country ) )
	 		return true;
	 	else
	 		return false;
	elseif ( 'billingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions( wpsc_get_customer_meta( 'billing_country' ) ) ):
		return true;
	endif;

	return false;
}

function wpsc_disregard_billing_state_fields(){
	global $wpsc_checkout;
	if ( 'billingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions( wpsc_get_customer_meta( 'billing_country' ) ) )
		return true;
	return false;
}


function wpsc_shipping_details() {
	global $wpsc_checkout;
	if ( stristr( $wpsc_checkout->checkout_item->unique_name, 'shipping' ) != false ) {

		return ' wpsc_shipping_forms';
	} else {
		return "";
	}
}

function wpsc_the_checkout_item_error_class( $as_attribute = true ) {
	global $wpsc_checkout, $wpsc_checkout_error_messages;

	$class_name = '';

	if ( ! empty( $wpsc_checkout_error_messages ) && isset( $wpsc_checkout_error_messages[$wpsc_checkout->checkout_item->id] ) && $wpsc_checkout_error_messages[$wpsc_checkout->checkout_item->id] != '' ) {
		$class_name = 'validation-error';
	}
	if ( ($as_attribute == true ) ) {
		$output = "class='" . $class_name . wpsc_shipping_details() . "'";
	} else {
		$output = $class_name;
	}
	return $output;
}

function wpsc_the_checkout_item_error() {
	global $wpsc_checkout, $wpsc_checkout_error_messages;
	$output = false;
	if ( ! empty( $wpsc_checkout_error_messages ) && isset( $wpsc_checkout_error_messages[$wpsc_checkout->checkout_item->id] ) && $wpsc_checkout_error_messages[$wpsc_checkout->checkout_item->id] != '' ) {
		$output = $wpsc_checkout_error_messages[$wpsc_checkout->checkout_item->id];
	}

	return $output;
}

function wpsc_the_checkout_CC_validation() {
	global $wpsc_gateway_error_messages;

	$output = '';
	if ( ! empty( $wpsc_gateway_error_messages ) && ! empty( $wpsc_gateway_error_messages['card_number'] ) )
		$output = $wpsc_gateway_error_messages['card_number'];

	return $output;
}

function wpsc_the_checkout_CC_validation_class() {
	global $wpsc_gateway_error_messages;
	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['card_number'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCexpiry_validation_class() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['expdate'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCexpiry_validation() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['expdate'] ) ? '' : $wpsc_gateway_error_messages['expdate'];
}

function wpsc_the_checkout_CCcvv_validation_class() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['card_code'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCcvv_validation() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['card_code'] ) ? '' : $wpsc_gateway_error_messages['card_code'];
}

function wpsc_the_checkout_CCtype_validation_class() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['cctype'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCtype_validation() {
	global $wpsc_gateway_error_messages;

	if ( empty( $wpsc_gateway_error_messages ) )
		return '';

	return empty( $wpsc_gateway_error_messages['cctype'] ) ? '' : $wpsc_gateway_error_messages['cctype'];
}

function wpsc_checkout_form_is_header() {
	global $wpsc_checkout;
	if ( $wpsc_checkout->checkout_item->type == 'heading' ) {
		$output = true;
	} else {
		$output = false;
	}
	return $output;
}

function wpsc_checkout_form_name() {
	global $wpsc_checkout;
	return $wpsc_checkout->form_name();
}

function wpsc_checkout_form_element_id() {
	global $wpsc_checkout;
	return $wpsc_checkout->form_element_id();
}

function wpsc_checkout_form_field() {
	global $wpsc_checkout;
	return $wpsc_checkout->form_field();
}

function wpsc_shipping_region_list( $selected_country, $selected_region, $shippingdetails = false ) {
	global $wpdb;
	$output = '';
	$region_data = $wpdb->get_results( $wpdb->prepare( "SELECT `regions`.* FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN(%s) ORDER BY name ASC", $selected_country ), ARRAY_A );
	$js = '';
	if ( !$shippingdetails ) {
		$js = "onchange='submit_change_country();'";
	}
	if ( count( $region_data ) > 0 ) {
		$output .= "<select name='region'  id='region' " . $js . " >";
		foreach ( $region_data as $region ) {
			$selected = '';
			if ( $selected_region == $region['id'] ) {
				$selected = "selected='selected'";
			}
			$output .= "<option $selected value='{$region['id']}'>" . esc_attr( htmlspecialchars( $region['name'] ) ). "</option>";
		}
		$output .= "";

		$output .= "</select>";
	} else {
		$output .= " ";
	}
	return $output;
}

function wpsc_shipping_country_list( $shippingdetails = false ) {
	global $wpdb, $wpsc_shipping_modules, $wpsc_country_data;
	$js = '';
	$output = '';
	if ( !$shippingdetails ) {
		$output = "<input type='hidden' name='wpsc_ajax_action' value='update_location' />";
		$js = "  onchange='submit_change_country();'";
	}
	$selected_country = (string) wpsc_get_customer_meta( 'shipping_country' );
	$selected_region  = (string) wpsc_get_customer_meta( 'shipping_region'  );

	if ( empty( $selected_country ) )
		$selected_country = esc_attr( get_option( 'base_country' ) );

	if ( empty( $selected_region ) )
		$selected_region = esc_attr( get_option( 'base_region' ) );

	if ( empty( $wpsc_country_data ) )
		$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `visible`= '1' ORDER BY `country` ASC", ARRAY_A );
	else
		$country_data = $wpsc_country_data;

	$acceptable_countries = wpsc_get_acceptable_countries();

	$output .= wpsc_get_country_dropdown( array(
		'name'                  => 'country',
		'id'                    => 'current_country',
		'additional_attributes' => $js,
		'acceptable_ids'        => $acceptable_countries,
		'selected'              => $selected_country,
		'placeholder'           => '',
	) );

	$output .= wpsc_shipping_region_list( $selected_country, $selected_region, $shippingdetails );

	if ( isset( $_POST['wpsc_update_location'] ) && $_POST['wpsc_update_location'] == 'true' ) {
		wpsc_update_customer_meta( 'update_location', true );
	} else {
		wpsc_delete_customer_meta( 'update_location' );
	}

	$zipvalue = (string) wpsc_get_customer_meta( 'shipping_zip' );
	if ( ! empty( $_POST['zipcode'] ) )
		$zipvalue = $_POST['zipcode'];

	$zip_code_text = __( 'Your Zipcode', 'wpsc' );

	if ( ( $zipvalue != '' ) && ( $zipvalue != $zip_code_text ) ) {
		$color = '#000';
		wpsc_update_customer_meta( 'shipping_zip', $zipvalue );
	} else {
		$zipvalue = $zip_code_text;
		$color = '#999';
	}

	$uses_zipcode = false;
	$custom_shipping = get_option( 'custom_shipping_options' );
	foreach ( (array)$custom_shipping as $shipping ) {
		if ( isset( $wpsc_shipping_modules[$shipping]->needs_zipcode ) && $wpsc_shipping_modules[$shipping]->needs_zipcode == true ) {
			$uses_zipcode = true;
		}
	}

	if ( $uses_zipcode ) {
		$output .= " <input type='text' style='color:" . $color . ";' onclick='if (this.value==\"" . esc_js( $zip_code_text ) . "\") {this.value=\"\";this.style.color=\"#000\";}' onblur='if (this.value==\"\") {this.style.color=\"#999\"; this.value=\"" . esc_js( $zip_code_text ) . "\"; }' value='" . esc_attr( $zipvalue ) . "' size='10' name='zipcode' id='zipcode'>";
	}
	return $output;
}

function wpsc_get_gateway_list() {
	return apply_filters( 'wpsc_get_gateway_list', '' );
}

function wpsc_gateway_list() {
	echo wpsc_get_gateway_list();
}

function wpsc_gateway_count() {
	return apply_filters( 'wpsc_gateway_count', 0 );
}

function wpsc_get_gateway_hidden_field() {
	$output = sprintf(
		'<input name="custom_gateway" value="%s" type="hidden" />',
		apply_filters( 'wpsc_gateway_hidden_field_value', '' )
	);

	return $output;
}

function wpsc_gateway_hidden_field() {
	do_action( 'wpsc_before_gateway_hidden_field' );
	echo wpsc_get_gateway_hidden_field();
	do_action( 'wpsc_after_gateway_hidden_field' );
}