<?php

/**
 * WP eCommerce checkout class
 *
 * These are the class for the WP eCommerce checkout
 * The checkout class handles dispaying the checkout form fields
 *
 * @package wp-e-commerce
 * @subpackage wpsc-checkout-classes
 */

/**
 * wpsc has regions checks to see whether a country has regions or not
 * @access public
 *
 * @since 3.8
 * @param $country (string) isocode for a country
 * @return (boolean) true is country has regions else false
 */
function wpsc_has_regions($country){
	global $wpdb;
	$country_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `isocode` IN(%s) LIMIT 1", $country ), ARRAY_A );
	if ($country_data['has_regions'] == 1)
		return true;
	else
		return false;

}

/**
 * wpsc_check_purchase_processed checks the given processed number and checks it against the global wpsc_purchlog_statuses
 * @access public
 *
 * @since 3.8
 * @param $processed (int) generally comes from the purchase log table `processed` column
 * @return $is_transaction (boolean) true if the process is a completed transaction false otherwise
 */
function wpsc_check_purchase_processed($processed){
	global $wpsc_purchlog_statuses;
	$is_transaction = false;
	foreach($wpsc_purchlog_statuses as $status)
		if($status['order'] == $processed && isset($status['is_transaction']) && 1 == $status['is_transaction'] )
			$is_transaction = true;

	return $is_transaction;
}

/**
 * get buyers email retrieves the email address associated to the checkout
 * @access public
 *
 * @since 3.8
 * @param purchase_id (int) the purchase id
 * @return email (strong) email addess
 */
function wpsc_get_buyers_email($purchase_id){
	global $wpdb;
	$email_form_field = $wpdb->get_results( "SELECT `id`,`type` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1", ARRAY_A );
	$email = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = '" . $email_form_field[0]['id'] . "' LIMIT 1", $purchase_id ) );
	return $email;

}

/**
 * wpsc google checkout submit used for google checkout (unsure whether necessary in 3.8)
 * @access public
 *
 * @since 3.7
 */
function wpsc_google_checkout_submit() {
	global $wpdb, $wpsc_cart, $current_user;
	$wpsc_checkout = new wpsc_checkout();
	$purchase_log_id = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` IN(%s) LIMIT 1", $_SESSION['wpsc_sessionid'] );
	get_currentuserinfo();
	if ( $current_user->display_name != '' ) {
		foreach ( $wpsc_checkout->checkout_items as $checkoutfield ) {
			if ( $checkoutfield->unique_name == 'billingfirstname' ) {
				$checkoutfield->value = $current_user->display_name;
			}
		}
	}
	if ( $current_user->user_email != '' ) {
		foreach ( $wpsc_checkout->checkout_items as $checkoutfield ) {
			if ( $checkoutfield->unique_name == 'billingemail' ) {
				$checkoutfield->value = $current_user->user_email;
			}
		}
	}

	$wpsc_checkout->save_forms_to_db( $purchase_log_id );
	$wpsc_cart->save_to_db( $purchase_log_id );
	$wpsc_cart->submit_stock_claims( $purchase_log_id );
}

/**
 * returns the tax label
 * @access public
 *
 * @since 3.7
 * @param $checkout (unused)
 * @return string Tax Included or Tax
 */
function wpsc_display_tax_label( $checkout = false ) {
	global $wpsc_cart;
	if ( wpsc_tax_isincluded ( ) ) {
		return __( 'Tax Included', 'wpsc' );
	} else {
		return __( 'Tax', 'wpsc' );
	}
}

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
	if(isset($_SESSION['categoryAndShippingCountryConflict']) && !empty($_SESSION['categoryAndShippingCountryConflict']))
		return true;
	else
		return false;
}

/**
 * Have valid shipping zipcode
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_have_valid_shipping_zipcode(){
	if(empty($_SESSION['wpsc_zipcode']) || ('Your Zipcode' == $_SESSION['wpsc_zipcode']) && ($_SESSION['wpsc_update_location']))
		return true;
	else
		return false;

}

/**
 * Checks to see whether terms and conditions are empty
 * @access public
 *
 * @since 3.8
 * @return (boolean) true or false
 */
function wpsc_has_tnc(){
	if('' == get_option('terms_and_conditions'))
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
	if(!wpsc_uses_shipping()):
	 	if( 'shippingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions($_SESSION['wpsc_delivery_country']))
	 		return true;
	 	else
	 		return false;
	 elseif('billingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions($_SESSION['wpsc_selected_country'])):
	 	return true;
	 endif;
}

function wpsc_disregard_billing_state_fields(){
	global $wpsc_checkout;
	if('billingstate' == $wpsc_checkout->checkout_item->unique_name && wpsc_has_regions($_SESSION['wpsc_selected_country']))
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
	global $wpsc_checkout;

	$class_name = '';

	if ( isset( $_SESSION['wpsc_checkout_error_messages'][$wpsc_checkout->checkout_item->id] ) && $_SESSION['wpsc_checkout_error_messages'][$wpsc_checkout->checkout_item->id] != '' ) {
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
	global $wpsc_checkout;
	$output = false;
	if ( isset( $_SESSION['wpsc_checkout_error_messages'][$wpsc_checkout->checkout_item->id] ) && $_SESSION['wpsc_checkout_error_messages'][$wpsc_checkout->checkout_item->id] != '' ) {
		$output = $_SESSION['wpsc_checkout_error_messages'][$wpsc_checkout->checkout_item->id];
	}

	return $output;
}

function wpsc_the_checkout_CC_validation() {
	$output = '';
	if ( $_SESSION['wpsc_gateway_error_messages']['card_number'] != '' ) {
		$output = $_SESSION['wpsc_gateway_error_messages']['card_number'];
	}
	return $output;
}

function wpsc_the_checkout_CC_validation_class() {
	return empty( $_SESSION['wspc_gateway_error_messages']['card_number'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCexpiry_validation_class() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['expdate'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCexpiry_validation() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['expdate'] ) ? '' : $_SESSION['wpsc_gateway_error_messages']['expdate'];
}

function wpsc_the_checkout_CCcvv_validation_class() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['card_code'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCcvv_validation() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['card_code'] ) ? '' : $_SESSION['wpsc_gateway_error_messages']['card_code'];
}

function wpsc_the_checkout_CCtype_validation_class() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['cctype'] ) ? '' : 'class="validation-error"';
}

function wpsc_the_checkout_CCtype_validation() {
	return empty( $_SESSION['wpsc_gateway_error_messages']['cctype'] ) ? '' : $_SESSION['wpsc_gateway_error_messages']['cctype'];
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
	$region_data = $wpdb->get_results( $wpdb->prepare( "SELECT `regions`.* FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN(%s)", $selected_country ), ARRAY_A );
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
	global $wpdb, $wpsc_shipping_modules;
	$js = '';
	$output = '';
	if ( !$shippingdetails ) {
		$output = "<input type='hidden' name='wpsc_ajax_actions' value='update_location' />";
		$js = "  onchange='submit_change_country();'";
	}
	$selected_country = isset( $_SESSION['wpsc_delivery_country'] ) ? $_SESSION['wpsc_delivery_country'] : '';
	$selected_region = isset( $_SESSION['wpsc_delivery_region'] ) ? $_SESSION['wpsc_delivery_region'] : '';

	if ( empty( $selected_country ) )
		$selected_country = esc_attr( get_option( 'base_country' ) );

	if ( empty( $selected_region ) )
		$selected_region = esc_attr( get_option( 'base_region' ) );

	$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `visible`= '1' ORDER BY `country` ASC", ARRAY_A );

	$output .= "<select name='country' id='current_country' " . $js . " >";
	foreach ( $country_data as $country ) {
			$selected = '';
			if ( $selected_country == $country['isocode'] ) {
				$selected = "selected='selected'";
			}
			$output .= "<option value='" . $country['isocode'] . "' $selected>" . esc_attr(htmlspecialchars( $country['country'] ) ) . "</option>";

	}

	$output .= "</select>";

	$output .= wpsc_shipping_region_list( $selected_country, $selected_region, $shippingdetails );

	if ( isset( $_POST['wpsc_update_location'] ) && $_POST['wpsc_update_location'] == 'true' ) {
		$_SESSION['wpsc_update_location'] = true;
	} else {
		$_SESSION['wpsc_update_location'] = false;
	}

	if ( isset( $_POST['zipcode'] ) ) {
		if ( $_POST['zipcode'] == '' ) {
			$zipvalue = '';
			$_SESSION['wpsc_zipcode'] = '';
		} else {
			$zipvalue = $_POST['zipcode'];
			$_SESSION['wpsc_zipcode'] = $_POST['zipcode'];
		}
	} else if ( isset( $_SESSION['wpsc_zipcode'] ) && ($_SESSION['wpsc_zipcode'] != '') ) {
		$zipvalue = $_SESSION['wpsc_zipcode'];
	} else {
		$zipvalue = '';
		$_SESSION['wpsc_zipcode'] = '';
	}

	if ( ($zipvalue != '') && ($zipvalue != 'Your Zipcode') ) {
		$color = '#000';
	} else {
		$zipvalue = 'Your Zipcode';
		$color = '#999';
	}

	$uses_zipcode = false;
	$custom_shipping = get_option( 'custom_shipping_options' );
	foreach ( (array)$custom_shipping as $shipping ) {
		if ( isset( $wpsc_shipping_modules[$shipping]->needs_zipcode ) && $wpsc_shipping_modules[$shipping]->needs_zipcode == true ) {
			$uses_zipcode = true;
		}
	}

	if ( $uses_zipcode == true ) {
		$output .= " <input type='text' style='color:" . $color . ";' onclick='if (this.value==\"Your Zipcode\") {this.value=\"\";this.style.color=\"#000\";}' onblur='if (this.value==\"\") {this.style.color=\"#999\"; this.value=\"Your Zipcode\"; }' value='" . esc_attr( $zipvalue ) . "' size='10' name='zipcode' id='zipcode'>";
	}
	return $output;
}

/**
 * The WPSC Checkout class
 */
class wpsc_checkout {

	// The checkout loop variables
	var $checkout_items = array( );
	var $checkout_item;
	var $checkout_item_count = 0;
	var $current_checkout_item = -1;
	var $in_the_loop = false;
	//the ticket additions
	var $additional_fields = array( );
	var $formfield_count = 0;

	/**
	 * wpsc_checkout method, gets the tax rate as a percentage, based on the selected country and region
	 * @access public
	 */
	function wpsc_checkout( $checkout_set = 0 ) {
		global $wpdb;
		$this->checkout_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'  AND `checkout_set`= %s ORDER BY `checkout_order`;", $checkout_set ) );

		$category_list = wpsc_cart_item_categories( true );
		$additional_form_list = array( );
		foreach ( $category_list as $category_id ) {
			$additional_form_list[] = wpsc_get_categorymeta( $category_id, 'use_additional_form_set' );
		}
		if ( function_exists( 'wpsc_get_ticket_checkout_set' ) ) {
			$checkout_form_fields_id = array_search( wpsc_get_ticket_checkout_set(), $additional_form_list );
			unset( $additional_form_list[$checkout_form_fields_id] );
		}
		if ( count( $additional_form_list ) > 0 ) {
			$this->category_checkout_items = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'  AND `checkout_set` IN ('" . implode( "','", $additional_form_list ) . "') ORDER BY `checkout_set`, `checkout_order`;" );
			$this->checkout_items = array_merge( (array)$this->checkout_items, (array)$this->category_checkout_items );
		}
		if ( function_exists( 'wpsc_get_ticket_checkout_set' ) ) {
			$sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'  AND `checkout_set`='" . wpsc_get_ticket_checkout_set() . "' ORDER BY `checkout_order`;";
			$this->additional_fields = $wpdb->get_results( $sql );
			$count = wpsc_ticket_checkoutfields();
			$j = 1;
			$fields = $this->additional_fields;
			$this->formfield_count = count( $fields ) + $this->checkout_item_count;
			while ( $j < $count ) {
				$this->additional_fields = array_merge( (array)$this->additional_fields, (array)$fields );
				$j++;
			}
			if ( wpsc_ticket_checkoutfields() > 0 ) {
				$this->checkout_items = array_merge( (array)$this->checkout_items, (array)$this->additional_fields );
			}
		}

		$this->checkout_item_count = count( $this->checkout_items );
	}

	function form_name() {
		if ( $this->form_name_is_required() && ($this->checkout_item->type != 'heading') )
			return esc_html( stripslashes( $this->checkout_item->name ) ) . ' <span class="asterix">*</span> ';
		else
			return esc_html( stripslashes( $this->checkout_item->name ) );
	}

	function form_name_is_required() {
		if ( $this->checkout_item->mandatory == 0 ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * form_element_id method, returns the form html ID
	 * @access public
	 */
	function form_element_id() {
		return 'wpsc_checkout_form_' . $this->checkout_item->id;
	}

	/**
	 * get_checkout_options, returns the form field options
	 * @access public
	 */
	function get_checkout_options( $id ) {
		global $wpdb;
		$sql = $wpdb->prepare( 'SELECT `options` FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE `id` = %d', $id );
		$options = $wpdb->get_var( $sql );
		$options = unserialize( $options );
		return $options;
	}

	/**
	 * form_field method, returns the form html
	 * @access public
	 */
	function form_field() {
		global $wpdb, $user_ID;

		if ( ( $user_ID > 0 ) ) {
			if( ! isset( $_SESSION['wpsc_checkout_saved_values'] ) ) {
				$meta_data = get_user_meta( $user_ID, 'wpshpcrt_usr_profile', 1 );
				$meta_data = apply_filters( 'wpsc_checkout_user_profile_get', $user_ID, $meta_data );
				$_SESSION['wpsc_checkout_saved_values'] = $meta_data;
			}

			$delivery_country_id = wpsc_get_country_form_id_by_type( 'delivery_country' );
     		$billing_country_id = wpsc_get_country_form_id_by_type( 'country' );
		}

		$saved_form_data = isset( $_SESSION['wpsc_checkout_saved_values'][$this->checkout_item->id] ) ? $_SESSION['wpsc_checkout_saved_values'][$this->checkout_item->id] : null;

		$an_array = '';
		if ( function_exists( 'wpsc_get_ticket_checkout_set' ) ) {
			if ( $this->checkout_item->checkout_set == wpsc_get_ticket_checkout_set() )
				$an_array = '[]';
		}
		$output = '';
		switch ( $this->checkout_item->type ) {
			case "address":
			case "delivery_address":
			case "textarea":

				$output .= "<textarea title='" . $this->checkout_item->unique_name . "' class='text' id='" . $this->form_element_id() . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' rows='3' cols='40' >" . esc_html( (string) $saved_form_data ) . "</textarea>";
				break;

			case "checkbox":
				$options = $this->get_checkout_options( $this->checkout_item->id );
				if ( $options != '' ) {
					$i = mt_rand();
					foreach ( $options as $label => $value ) {
						?>
							<label>
								<input <?php checked( in_array( $value, (array) $saved_form_data ) ); ?> type="checkbox" name="collected_data[<?php echo esc_attr( $this->checkout_item->id ); ?>]<?php echo $an_array; ?>[]" value="<?php echo esc_attr( $value ); ?>"  />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php
					}
				}
				break;

			case "country":
				$output = wpsc_country_region_list( $this->checkout_item->id, false, $_SESSION['wpsc_selected_country'], $_SESSION['wpsc_selected_region'], $this->form_element_id() );
				break;

			case "delivery_country":
				if ( wpsc_uses_shipping ( ) ) {
					$country_name = $wpdb->get_var( $wpdb->prepare( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `isocode`= %s LIMIT 1", $_SESSION['wpsc_delivery_country'] ) );
					$output = "<input title='" . $this->checkout_item->unique_name . "' type='hidden' id='" . $this->form_element_id() . "' class='shipping_country' name='collected_data[{$this->checkout_item->id}]' value='" . esc_attr( $_SESSION['wpsc_delivery_country'] ) . "' size='4' /><span class='shipping_country_name'>" . $country_name . "</span> ";
				} else {
					$checkoutfields = true;
					$output = wpsc_country_region_list( $this->checkout_item->id, false, $_SESSION['wpsc_delivery_country'], $_SESSION['wpsc_delivery_region'], $this->form_element_id(), $checkoutfields );
				}
				break;
			case "select":
				$options = $this->get_checkout_options( $this->checkout_item->id );
				if ( $options != '' ) {
					$output = "<select name='collected_data[{$this->checkout_item->id}]" . $an_array . "'>";
					$output .= "<option value='-1'>" . _x( 'Select an Option', 'Dropdown default when called within checkout class' , 'wpsc' ) . "</option>";
					foreach ( (array)$options as $label => $value ) {
						$value = esc_attr(str_replace( ' ', '', $value ) );
						$output .="<option " . selected( $value, $saved_form_data, false ) . " value='" . esc_attr( $value ) . "'>" . esc_html( $label ) . "</option>\n\r";
					}
					$output .="</select>";
				}
				break;
			case "radio":
				$options = $this->get_checkout_options( $this->checkout_item->id );
				if ( $options != '' ) {
					foreach ( (array)$options as $label => $value ) {
						?>
							<label>
								<input type="radio" <?php checked( $value, $saved_form_data ); ?> name="collected_data[<?php echo esc_attr( $this->checkout_item->id ); ?>]<?php echo $an_array; ?>" value="<?php echo esc_attr( $value ); ?>"  />
								<?php echo esc_html( $label ); ?>
							</label>
						<?php
					}
				}
				break;
			case "text":
			case "city":
			case "delivery_city":
			case "email":
			case "coupon":
			default:
				if ( $this->checkout_item->unique_name == 'shippingstate' ) {
					if ( wpsc_uses_shipping() && wpsc_has_regions($_SESSION['wpsc_delivery_country']) ) {
						$region_name = $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id`= %d LIMIT 1", $_SESSION['wpsc_delivery_region'] ) );
						$output = "<input title='" . $this->checkout_item->unique_name . "' type='hidden' id='" . $this->form_element_id() . "' class='shipping_region' name='collected_data[{$this->checkout_item->id}]' value='" . esc_attr( $_SESSION['wpsc_delivery_region'] ) . "' size='4' /><span class='shipping_region_name'>" . esc_html( $region_name ) . "</span> ";
					} else {
						$disabled = '';
						if(wpsc_disregard_shipping_state_fields())
							$disabled = 'disabled = "disabled"';
						$output = "<input class='shipping_region text' title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' ".$disabled." />";
					}
				} elseif ( $this->checkout_item->unique_name == 'billingstate' ) {
					if ( wpsc_uses_shipping() && wpsc_has_regions($_SESSION['wpsc_selected_country']) ) {
						$output = '';
					} else {
						$disabled = '';
						if(wpsc_disregard_billing_state_fields())
							$disabled = 'disabled = "disabled"';
						$output = "<input class='billing_region text' title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' ".$disabled." />";
					}
				} else {
					$output = "<input title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' class='text' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' />";
				}

				break;
		}
		return $output;
	}

	/**
	 * validate_forms method, validates the input from the checkout page
	 * @access public
	 */
	function validate_forms() {
		global $wpdb, $current_user, $user_ID;
		$any_bad_inputs = false;
		$bad_input_message = '';
		// Credit Card Number Validation for PayPal Pro and maybe others soon
		if ( isset( $_POST['card_number'] ) ) {
			//should do some php CC validation here~
		} else {
			$_SESSION['wpsc_gateway_error_messages']['card_number'] = '';
		}
		if ( isset( $_POST['card_number1'] ) && isset( $_POST['card_number2'] ) && isset( $_POST['card_number3'] ) && isset( $_POST['card_number4'] ) ) {
			if ( $_POST['card_number1'] != '' && $_POST['card_number2'] != '' && $_POST['card_number3'] != '' && $_POST['card_number4'] != '' && is_numeric( $_POST['card_number1'] ) && is_numeric( $_POST['card_number2'] ) && is_numeric( $_POST['card_number3'] ) && is_numeric( $_POST['card_number4'] ) ) {
				$_SESSION['wpsc_gateway_error_messages']['card_number'] = '';
			} else {

				$any_bad_inputs = true;
				$bad_input = true;
				$_SESSION['wpsc_gateway_error_messages']['card_number'] = __( 'Please enter a valid card number.', 'wpsc' );
				$_SESSION['wpsc_checkout_saved_values']['card_number'] = '';
			}
		}
		if ( isset( $_POST['expiry'] ) ) {
			if ( !empty($_POST['expiry']['month']) && !empty($_POST['expiry']['month']) && is_numeric( $_POST['expiry']['month'] ) && is_numeric( $_POST['expiry']['year'] ) ) {
				$_SESSION['wpsc_gateway_error_messages']['expdate'] = '';
			} else {
				$any_bad_inputs = true;
				$bad_input = true;
				$_SESSION['wpsc_gateway_error_messages']['expdate'] = __( 'Please enter a valid expiry date.', 'wpsc' );
				$_SESSION['wpsc_checkout_saved_values']['expdate'] = '';
			}
		}
		if ( isset( $_POST['card_code'] ) ) {
			if ( empty($_POST['card_code']) || (!is_numeric( $_POST['card_code'] )) ) {
				$any_bad_inputs = true;
				$bad_input = true;
				$_SESSION['wpsc_gateway_error_messages']['card_code'] = __( 'Please enter a valid CVV.', 'wpsc' );
				$_SESSION['wpsc_checkout_saved_values']['card_code'] = '';
			} else {
				$_SESSION['wpsc_gateway_error_messages']['card_code'] = '';
			}
		}
		if ( isset( $_POST['cctype'] ) ) {
			if ( $_POST['cctype'] == '' ) {
				$any_bad_inputs = true;
				$bad_input = true;
				$_SESSION['wpsc_gateway_error_messages']['cctype'] = __( 'Please enter a valid CVV.', 'wpsc' );
				$_SESSION['wpsc_checkout_saved_values']['cctype'] = '';
			} else {
				$_SESSION['wpsc_gateway_error_messages']['cctype'] = '';
			}
		}
		if ( isset( $_POST['log'] ) || isset( $_POST['pwd'] ) || isset( $_POST['user_email'] ) ) {
			$results = wpsc_add_new_user( $_POST['log'], $_POST['pwd'], $_POST['user_email'] );
			$_SESSION['wpsc_checkout_user_error_messages'] = array( );
			if ( is_callable( array( $results, "get_error_code" ) ) && $results->get_error_code() ) {
				foreach ( $results->get_error_codes() as $code ) {
					foreach ( $results->get_error_messages( $code ) as $error ) {
						$_SESSION['wpsc_checkout_user_error_messages'][] = $error;
					}

					$any_bad_inputs = true;
				}
			}
			if ( $results->ID > 0 ) {
				$our_user_id = $results->ID;
			} else {
				$any_bad_inputs = true;
				$our_user_id = '';
			}
		}
		if ( isset( $our_user_id ) && $our_user_id < 1 ) {
			$our_user_id = $user_ID;
		}
		// check we have a user id
		if ( isset( $our_user_id ) && $our_user_id > 0 ) {
			$user_ID = $our_user_id;
		}
		//Basic Form field validation for billing and shipping details
		foreach ( $this->checkout_items as $form_data ) {
			$value = '';

			if( isset( $_POST['collected_data'][$form_data->id] ) )
				$value = stripslashes_deep( $_POST['collected_data'][$form_data->id] );

			$_SESSION['wpsc_checkout_saved_values'][$form_data->id] = $value;
			$bad_input = false;
			if ( ($form_data->mandatory == 1) || ($form_data->type == "coupon") ) {
				// dirty hack
				if ( $form_data->unique_name == 'billingstate' && empty( $value ) ) {
					$billing_country_id = $wpdb->get_var( "SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'billingcountry' AND active = '1' " );
					$value = $_POST['collected_data'][$billing_country_id][1];
				}

				switch ( $form_data->type ) {
					case "email":
						if ( !preg_match( "/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-.]+\.[a-zA-Z]{2,5}$/", $value ) ) {
							$any_bad_inputs = true;
							$bad_input = true;
						}
						break;

					case "delivery_country":
					case "country":
					case "heading":
						break;
					case "select":
						if ( $value == '-1' ) {
							$any_bad_inputs = true;
							$bad_input = true;
						}
						break;
					default:
						if ( $value == null ) {
							$any_bad_inputs = true;
							$bad_input = true;
						}
						break;
				}
				if ( $bad_input === true ) {
					$_SESSION['wpsc_checkout_error_messages'][$form_data->id] = sprintf(__( 'Please enter a valid <span class="wpsc_error_msg_field_name">%s</span>.', 'wpsc' ), esc_attr($form_data->name) );
					$_SESSION['wpsc_checkout_saved_values'][$form_data->id] = '';
				}
			}
		}

		if ( ( $any_bad_inputs == false ) && ( $user_ID > 0 ) ) {
			$meta_data = $_POST['collected_data'];
			$meta_data = apply_filters( 'wpsc_checkout_user_profile_update', $user_ID, $meta_data );
			update_user_meta( $user_ID, 'wpshpcrt_usr_profile', $meta_data );
		}


		$states = array( 'is_valid' => !$any_bad_inputs, 'error_messages' => $bad_input_message );
		$states = apply_filters('wpsc_checkout_form_validation', $states);
		return $states;
	}

	/**
	 * validate_forms method, validates the input from the checkout page
	 * @access public
	 */
	function save_forms_to_db( $purchase_id ) {
		global $wpdb;

		// needs refactoring badly
		$shipping_state_id = $wpdb->get_var( "SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'shippingstate' " );
		$billing_state_id = $wpdb->get_var( "SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'billingstate' " );
		$shipping_state = $billing_state = '';

		$_POST['collected_data'] = stripslashes_deep( $_POST['collected_data'] );

		foreach ( $this->checkout_items as $form_data ) {
			if ( $form_data->type == 'heading' )
				continue;

			$value = '';
			if( isset( $_POST['collected_data'][$form_data->id] ) )
				$value = $_POST['collected_data'][$form_data->id];
			if ( empty( $value ) && isset( $form_data->value ) )
				$value = $form_data->value;
			if ( $form_data->unique_name == 'billingstate' ) {
				$billing_state = $value;
				continue;
			} elseif( $form_data->unique_name == 'shippingstate' ) {
				$shipping_state = $value;
				continue;
			} elseif ( is_array( $value ) ) {
				if ( in_array( $form_data->unique_name, array( 'billingcountry' , 'shippingcountry' ) ) ) {
					if ( isset( $value[1] ) )
						if ( $form_data->unique_name == 'billingcountry' )
							$billing_state = $value[1];
						else
							$shipping_state = $value[1];

					$value = $value[0];
					$prepared_query = $wpdb->insert(
								    WPSC_TABLE_SUBMITED_FORM_DATA,
								    array(
									'log_id' => $purchase_id,
									'form_id' => $form_data->id,
									'value' => $value
								    ),
								    array(
									'%d',
									'%d',
									'%s'
								    )
								);
				} else {
					foreach ( (array)$value as $v ) {
					    $prepared_query = $wpdb->insert(
								    WPSC_TABLE_SUBMITED_FORM_DATA,
								    array(
									'log_id' => $purchase_id,
									'form_id' => $form_data->id,
									'value' => $v
								    ),
								    array(
									'%d',
									'%d',
									'%s'
								    )
								);					}
				}
			} else {
			    $prepared_query = $wpdb->insert(
							WPSC_TABLE_SUBMITED_FORM_DATA,
							array(
							    'log_id' => $purchase_id,
							    'form_id' => $form_data->id,
							    'value' => $value
							),
							array(
							    '%d',
							    '%d',
							    '%s'
							)
						    );
			}
		}

		// update the states
		$wpdb->insert(
			    WPSC_TABLE_SUBMITED_FORM_DATA,
			    array(
				'log_id' => $purchase_id,
				'form_id' => $shipping_state_id,
				'value' => $shipping_state
			    ),
			    array(
				'%d',
				'%d',
				'%s'
			    )
			);
		$wpdb->insert(
			    WPSC_TABLE_SUBMITED_FORM_DATA,
			    array(
				'log_id' => $purchase_id,
				'form_id' => $billing_state_id,
				'value' => $billing_state
			    ),
			    array(
				'%d',
				'%d',
				'%s'
			    )
			);
	    }

	/**
	 * Function that checks how many checkout fields are stored in checkout form fields table
	 */
	function get_count_checkout_fields() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` !='heading' AND `active`='1'";
		$count = $wpdb->get_var( $sql );
		return (int) $count;
	}

	/**
	 * checkout loop methods
	 */
	function next_checkout_item() {
		$this->current_checkout_item++;
		$this->checkout_item = $this->checkout_items[$this->current_checkout_item];
		return $this->checkout_item;
	}

	function the_checkout_item() {
		$this->in_the_loop = true;
		$this->checkout_item = $this->next_checkout_item();
		if ( $this->current_checkout_item == 0 ) // loop has just started
			do_action( 'wpsc_checkout_loop_start' );
	}

	function have_checkout_items() {
		if ( $this->current_checkout_item + 1 < $this->checkout_item_count ) {
			return true;
		} else if ( $this->current_checkout_item + 1 == $this->checkout_item_count && $this->checkout_item_count > 0 ) {
			do_action( 'wpsc_checkout_loop_end' );
			// Do some cleaning up after the loop,
			$this->rewind_checkout_items();
		}

		$this->in_the_loop = false;
		return false;
	}

	function rewind_checkout_items() {
		$_SESSION['wpsc_checkout_error_messages'] = array( );
		$this->current_checkout_item = -1;
		if ( $this->checkout_item_count > 0 ) {
			$this->checkout_item = $this->checkout_items[0];
		}
	}

}

/**
 * The WPSC Gateway functions
 */
function wpsc_gateway_count() {
	global $wpsc_gateway;
	return $wpsc_gateway->gateway_count;
}

function wpsc_have_gateways() {
	global $wpsc_gateway;
	return $wpsc_gateway->have_gateways();
}

function wpsc_the_gateway() {
	global $wpsc_gateway;
	return $wpsc_gateway->the_gateway();
}

//return true only when gateway has image set
function wpsc_show_gateway_image(){
	global $wpsc_gateway;
	if( isset($wpsc_gateway->gateway['image']) && !empty($wpsc_gateway->gateway['image']) )
		return true;
	else
		return false;
}


//return gateway image url (string) or false if none.
function wpsc_gateway_image_url(){
	global $wpsc_gateway;
	if( wpsc_show_gateway_image() )
		return $wpsc_gateway->gateway['image'];
	else
		return false;
}

function wpsc_gateway_name() {
	global $wpsc_gateway;
	$display_name = '';

	$payment_gateway_names = get_option( 'payment_gateway_names' );

	if ( isset( $payment_gateway_names[$wpsc_gateway->gateway['internalname']] ) && ( $payment_gateway_names[$wpsc_gateway->gateway['internalname']] != '' || wpsc_show_gateway_image() ) ) {
		$display_name = $payment_gateway_names[$wpsc_gateway->gateway['internalname']];
	} elseif ( isset( $wpsc_gateway->gateway['payment_type'] ) ) {
		switch ( $wpsc_gateway->gateway['payment_type'] ) {
			case "paypal":
			case "paypal_pro":
			case "wpsc_merchant_paypal_pro";
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
	if ( $display_name == '' && !wpsc_show_gateway_image() ) {
		$display_name = 'Credit Card';
	}
	return $display_name;
}

function wpsc_gateway_internal_name() {
	global $wpsc_gateway;
	return $wpsc_gateway->gateway['internalname'];
}

function wpsc_gateway_is_checked() {
	global $wpsc_gateway;
	$is_checked = false;
	if ( isset( $_SESSION['wpsc_previous_selected_gateway'] ) ) {
		if ( $wpsc_gateway->gateway['internalname'] == $_SESSION['wpsc_previous_selected_gateway'] ) {
			$is_checked = true;
		}
	} else {
		if ( $wpsc_gateway->current_gateway == 0 ) {
			$is_checked = true;
		}
	}
	if ( $is_checked == true ) {
		$output = 'checked="checked"';
	} else {
		$output = '';
	}
	return $output;
}

function wpsc_gateway_cc_check() {

}

function wpsc_gateway_form_fields() {
	global $wpsc_gateway, $gateway_checkout_form_fields;

	$messages = isset( $_SESSION['wpsc_gateway_error_messages'] ) ? $_SESSION['wpsc_gateway_error_messages'] : array();

	$error = array(
		'card_number' => empty( $messages['card_number'] ) ? '' : $messages['card_number'],
		'expdate' => empty( $messages['expdate'] ) ? '' : $messages['expdate'],
		'card_code' => empty( $messages['card_code'] ) ? '' : $messages['card_code'],
		'cctype' => empty( $messages['cctype'] ) ? '' : $messages['cctype'],
	);

	// Match fields to gateway
	switch ( $wpsc_gateway->gateway['internalname'] ) {

		case 'paypal_pro' : // legacy
		case 'wpsc_merchant_paypal_pro' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate'],
				wpsc_the_checkout_CCcvv_validation_class(), $error['card_code'],
				wpsc_the_checkout_CCtype_validation_class(), $error['cctype']
			);
			break;

		case 'authorize' :
		case 'paypal_payflow' :
			$output = @sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate'],
				wpsc_the_checkout_CCcvv_validation_class(), $error['card_code']
			);
			break;

		case 'eway' :
		case 'bluepay' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate']
			);
			break;
		case 'linkpoint' :
			$output = sprintf( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']], wpsc_the_checkout_CC_validation_class(), $error['card_number'],
				wpsc_the_checkout_CCexpiry_validation_class(), $error['expdate']
			);
			break;

	}

	if ( isset( $output ) && !empty( $output ) )
		return $output;
	elseif ( isset( $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']] ) )
		return $gateway_checkout_form_fields[$wpsc_gateway->gateway['internalname']];
}

function wpsc_gateway_form_field_style() {
	global $wpsc_gateway;
	$is_checked = false;
	if ( isset( $_SESSION['wpsc_previous_selected_gateway'] ) ) {
		if ( $wpsc_gateway->gateway['internalname'] == $_SESSION['wpsc_previous_selected_gateway'] ) {
			$is_checked = true;
		}
	} else {
		if ( $wpsc_gateway->current_gateway == 0 ) {
			$is_checked = true;
		}
	}
	if ( $is_checked == true ) {
		$output = 'checkout_forms';
	} else {
		$output = 'checkout_forms_hidden';
	}
	return $output;
}

/**
 * The WPSC Gateway class
 */
class wpsc_gateways {

	var $wpsc_gateways;
	var $gateway;
	var $gateway_count = 0;
	var $current_gateway = -1;
	var $in_the_loop = false;

	function wpsc_gateways() {
		global $nzshpcrt_gateways;

		foreach ( WPSC_Payment_Gateways::get_active_gateways() as $gateway_name ) {
			$this->wpsc_gateways[] = WPSC_Payment_Gateways::get_meta( $gateway_name );
		}

		$gateway_options = get_option( 'custom_gateway_options' );
		foreach ( $nzshpcrt_gateways as $gateway ) {
			if ( array_search( $gateway['internalname'], (array)$gateway_options ) !== false ) {
				$this->wpsc_gateways[] = $gateway;
			}
		}
		$this->gateway_count = count( $this->wpsc_gateways );
	}

	/**
	 * checkout loop methods
	 */
	function next_gateway() {
		$this->current_gateway++;
		$this->gateway = $this->wpsc_gateways[$this->current_gateway];
		return $this->gateway;
	}

	function the_gateway() {
		$this->in_the_loop = true;
		$this->gateway = $this->next_gateway();
		if ( $this->current_gateway == 0 ) // loop has just started
			do_action( 'wpsc_checkout_loop_start' );
	}

	function have_gateways() {
		if ( $this->current_gateway + 1 < $this->gateway_count ) {
			return true;
		} else if ( $this->current_gateway + 1 == $this->gateway_count && $this->gateway_count > 0 ) {
			do_action( 'wpsc_checkout_loop_end' );
			// Do some cleaning up after the loop,
			$this->rewind_gateways();
		}

		$this->in_the_loop = false;
		return false;
	}

	function rewind_gateways() {
		$this->current_gateway = -1;
		if ( $this->gateway_count > 0 ) {
			$this->gateway = $this->wpsc_gateways[0];
		}
	}

}

?>
