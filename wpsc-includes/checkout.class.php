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
	$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );

	if ( ! $email_form_field )
		return '';
	$email = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase_id, $email_form_field ) );
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
	$purchase_log_id = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` IN(%s) LIMIT 1", wpsc_get_customer_meta( 'checkout_session_id' ) );
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
 * Cycles through the categories represented by the products in the cart.
 * Retrieves their target markets and returns an array of acceptable markets
 * We're only listing target markets that are acceptable for ALL categories in the cart
 *
 * @since 3.8.9
 * @return array Countries that can be shipped to.  If empty, sets session variable with appropriate error message
 */
function wpsc_get_acceptable_countries() {
	global $wpdb;

	$cart_category_ids = array_unique( wpsc_cart_item_categories( true ) );

	$target_market_ids = array();

	foreach ( $cart_category_ids as $category_id ) {
		$target_markets = wpsc_get_meta( $category_id, 'target_market', 'wpsc_category' );
		if ( ! empty( $target_markets ) )
			$target_market_ids[$category_id] = $target_markets;
	}

	$have_target_market = ! empty( $target_market_ids );

	//If we're comparing multiple categories
	if ( count( $target_market_ids ) > 1 ) {
		$target_market_ids = call_user_func_array( 'array_intersect', $target_market_ids );
	} elseif ( $have_target_market ) {
		$target_market_ids = array_values( $target_market_ids );
		$target_market_ids = $target_market_ids[0];
	}

	$country_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `visible`= '1' ORDER BY `country` ASC", ARRAY_A );
	$have_target_market = $have_target_market && count( $country_data ) != count( $target_market_ids );
	$GLOBALS['wpsc_country_data'] = $country_data;

	$conflict_error = wpsc_get_customer_meta( 'category_shipping_conflict' );
	$target_conflict = wpsc_get_customer_meta( 'category_shipping_target_market_conflict' );

	// Return true if there are no restrictions
	if ( ! $have_target_market ) {
		// clear out the target market messages
		if ( ! empty( $target_conflict ) )
			wpsc_delete_customer_meta( 'category_shipping_conflict' );

		wpsc_update_customer_meta( 'category_shipping_target_market_conflict', false );
		wpsc_update_customer_meta( 'category_shipping_conflict', false );
		return true;
	}

	// temporarily hijack this session variable to display target market restriction warnings
	if ( ! empty( $target_conflict ) || ! wpsc_has_category_and_country_conflict() ) {
		wpsc_update_customer_meta( 'category_shipping_target_market_conflict', true );
		wpsc_update_customer_meta( 'category_shipping_conflict', __( "Some of your cart items are targeted specifically to certain markets. As a result, you can only select those countries as your shipping destination.", 'wpsc' ) );
	}

	if ( empty( $target_market_ids ) ) {
		wpsc_update_customer_meta( 'category_shipping_target_market_conflict', true );
		wpsc_update_customer_meta( 'category_shipping_conflict', __( 'It appears that some products in your cart have conflicting target market restrictions. As a result, there is no common destination country where your cart items can be shipped to. Please contact the site administrator for more information.', 'wpsc' ) );
	}

	return $target_market_ids;

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
	var $additional_fields = array();
	var $formfield_count = 0;

	/**
	 * wpsc_checkout method, gets the tax rate as a percentage, based on the selected country and region
	 * @access public
	 */
	function wpsc_checkout( $checkout_set = 0 ) {
		global $wpdb;
		$this->checkout_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1'  AND `checkout_set`= %s ORDER BY `checkout_order`;", $checkout_set ) );

		$GLOBALS['wpsc_checkout_error_messages'    ] = wpsc_get_customer_meta( 'checkout_error_messages'     );
		$GLOBALS['wpsc_gateway_error_messages'     ] = wpsc_get_customer_meta( 'gateway_error_messages'      );
		$GLOBALS['wpsc_registration_error_messages'] = wpsc_get_customer_meta( 'registration_error_messages' );
		$GLOBALS['wpsc_customer_checkout_details'  ] = apply_filters( 'wpsc_get_customer_checkout_details', wpsc_get_customer_meta( 'checkout_details' ) );

		// legacy filter
		if ( is_user_logged_in() )
			$GLOBALS['wpsc_customer_checkout_details'] = apply_filters( 'wpsc_checkout_user_profile_get', $GLOBALS['wpsc_customer_checkout_details'], get_current_user_id() );

		if ( ! is_array( $GLOBALS['wpsc_customer_checkout_details'] ) )
			$GLOBALS['wpsc_customer_checkout_details'] = array();

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
			return esc_html( apply_filters( 'wpsc_checkout_field_name', $this->checkout_item->name ) ) . ' <span class="asterix">*</span> ';
		else
			return esc_html( apply_filters( 'wpsc_checkout_field_name', $this->checkout_item->name ) );
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
		global $wpdb, $user_ID, $wpsc_customer_checkout_details;

		if ( ( $user_ID > 0 ) ) {
			$delivery_country_id = wpsc_get_country_form_id_by_type( 'delivery_country' );
     		$billing_country_id = wpsc_get_country_form_id_by_type( 'country' );
		}

		$saved_form_data = empty( $wpsc_customer_checkout_details[$this->checkout_item->id] ) ? null : $wpsc_customer_checkout_details[$this->checkout_item->id];
		$an_array = '';
		if ( function_exists( 'wpsc_get_ticket_checkout_set' ) ) {
			if ( $this->checkout_item->checkout_set == wpsc_get_ticket_checkout_set() )
				$an_array = '[]';
		}
		$output = '';
		$delivery_country = wpsc_get_customer_meta( 'shipping_country' );
		$billing_country  = wpsc_get_customer_meta( 'billing_country'  );
		$delivery_region  = wpsc_get_customer_meta( 'shipping_region'  );
		$billing_region   = wpsc_get_customer_meta( 'billing_region'   );
		switch ( $this->checkout_item->type ) {
			case "address":
			case "delivery_address":
			case "textarea":
				$placeholder = apply_filters( 'wpsc_checkout_field_placeholder', apply_filters( 'wpsc_checkout_field_name', $this->checkout_item->name ), $this->checkout_item );
				$output .= "<textarea title='" . $this->checkout_item->unique_name . "' class='text' id='" . $this->form_element_id() . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' placeholder='" . esc_attr( $placeholder ) . "' rows='3' cols='40' >" . esc_html( (string) $saved_form_data ) . "</textarea>";
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
				$output = wpsc_country_region_list( $this->checkout_item->id, false, $billing_country, $billing_region, $this->form_element_id() );
				break;

			case "delivery_country":
				if ( wpsc_uses_shipping ( ) ) {
					$country_name = $wpdb->get_var( $wpdb->prepare( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `isocode`= %s LIMIT 1", $delivery_country ) );
					$output = "<input title='" . $this->checkout_item->unique_name . "' type='hidden' id='" . $this->form_element_id() . "' class='shipping_country' name='collected_data[{$this->checkout_item->id}]' value='" . esc_attr( $delivery_country ) . "' size='4' /><span class='shipping_country_name'>" . $country_name . "</span> ";
				} else {
					$checkoutfields = true;
					$output = wpsc_country_region_list( $this->checkout_item->id, false, $delivery_country, $delivery_region, $this->form_element_id(), $checkoutfields );
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
				$placeholder = apply_filters( 'wpsc_checkout_field_placeholder', apply_filters( 'wpsc_checkout_field_name', $this->checkout_item->name ), $this->checkout_item );
				if ( $this->checkout_item->unique_name == 'shippingstate' ) {
					if ( wpsc_uses_shipping() && wpsc_has_regions($delivery_country) ) {
						$region_name = $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id`= %d LIMIT 1", $delivery_region ) );
						$output = "<input title='" . $this->checkout_item->unique_name . "' type='hidden' id='" . $this->form_element_id() . "' class='shipping_region' name='collected_data[{$this->checkout_item->id}]' placeholder='" . esc_attr( $placeholder ) . "' value='" . esc_attr( $delivery_region ) . "' size='4' /><span class='shipping_region_name'>" . esc_html( $region_name ) . "</span> ";
					} else {
						$disabled = '';
						if(wpsc_disregard_shipping_state_fields())
							$disabled = 'disabled = "disabled"';
						$output = "<input class='shipping_region text' title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' placeholder='" . esc_attr( $placeholder ) . "' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' ".$disabled." />";
					}
				} elseif ( $this->checkout_item->unique_name == 'billingstate' ) {
					$disabled = '';
					if(wpsc_disregard_billing_state_fields())
						$disabled = 'disabled = "disabled"';
					$output = "<input class='billing_region text' title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' placeholder='" . esc_attr( $placeholder ) . "' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' ".$disabled." />";
				} else {
					$output = "<input title='" . $this->checkout_item->unique_name . "' type='text' id='" . $this->form_element_id() . "' class='text' placeholder='" . esc_attr( $placeholder ) . "' value='" . esc_attr( $saved_form_data ) . "' name='collected_data[{$this->checkout_item->id}]" . $an_array . "' />";
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
		global $wpsc_cart, $wpdb, $current_user, $user_ID, $wpsc_gateway_error_messages, $wpsc_checkout_error_messages, $wpsc_customer_checkout_details, $wpsc_registration_error_messages;
		$any_bad_inputs = false;
		$bad_input_message = '';
		$wpsc_gateway_error_messages      = array();
		$wpsc_checkout_error_messages     = array();
		$wpsc_registration_error_messages = array();
		// Credit Card Number Validation for PayPal Pro and maybe others soon
		if ( isset( $_POST['card_number'] ) ) {
			//should do some php CC validation here~
		} else {
			$wpsc_gateway_error_messages['card_number'] = '';
		}
		if ( isset( $_POST['card_number1'] ) && isset( $_POST['card_number2'] ) && isset( $_POST['card_number3'] ) && isset( $_POST['card_number4'] ) ) {
			if ( $_POST['card_number1'] != '' && $_POST['card_number2'] != '' && $_POST['card_number3'] != '' && $_POST['card_number4'] != '' && is_numeric( $_POST['card_number1'] ) && is_numeric( $_POST['card_number2'] ) && is_numeric( $_POST['card_number3'] ) && is_numeric( $_POST['card_number4'] ) ) {
				$wpsc_gateway_error_messages['card_number'] = '';
			} else {

				$any_bad_inputs = true;
				$bad_input = true;
				$wpsc_gateway_error_messages['card_number'] = __( 'Please enter a valid card number.', 'wpsc' );
				$wpsc_customer_checkout_details['card_number'] = '';
			}
		}
		if ( isset( $_POST['expiry'] ) ) {
			if ( !empty($_POST['expiry']['month']) && !empty($_POST['expiry']['month']) && is_numeric( $_POST['expiry']['month'] ) && is_numeric( $_POST['expiry']['year'] ) ) {
				$wpsc_gateway_error_messages['expdate'] = '';
			} else {
				$any_bad_inputs = true;
				$bad_input = true;
				$wpsc_gateway_error_messages['expdate'] = __( 'Please enter a valid expiry date.', 'wpsc' );
				$wpsc_customer_checkout_details['expdate'] = '';
			}
		}
		if ( isset( $_POST['card_code'] ) ) {
			if ( empty($_POST['card_code']) || (!is_numeric( $_POST['card_code'] )) ) {
				$any_bad_inputs = true;
				$bad_input = true;
				$wpsc_gateway_error_messages['card_code'] = __( 'Please enter a valid CVV.', 'wpsc' );
				$wpsc_customer_checkout_details['card_code'] = '';
			} else {
				$wpsc_gateway_error_messages['card_code'] = '';
			}
		}
		if ( isset( $_POST['cctype'] ) ) {
			if ( $_POST['cctype'] == '' ) {
				$any_bad_inputs = true;
				$bad_input = true;
				$wpsc_gateway_error_messages['cctype'] = __( 'Please enter a valid CVV.', 'wpsc' );
				$wpsc_customer_checkout_details['cctype'] = '';
			} else {
				$wpsc_gateway_error_messages['cctype'] = '';
			}
		}
		if ( isset( $_POST['log'] ) || isset( $_POST['pwd'] ) || isset( $_POST['user_email'] ) ) {
			$results = wpsc_add_new_user( $_POST['log'], $_POST['pwd'], $_POST['user_email'] );
			if ( is_callable( array( $results, "get_error_code" ) ) && $results->get_error_code() ) {
				foreach ( $results->get_error_codes() as $code ) {
					foreach ( $results->get_error_messages( $code ) as $error ) {
						$wpsc_registration_error_messages[] = $error;
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

		$location_changed = false;
		//Basic Form field validation for billing and shipping details
		foreach ( $this->checkout_items as $form_data ) {
			$value = '';

			if( isset( $_POST['collected_data'][$form_data->id] ) )
				$value = stripslashes_deep( $_POST['collected_data'][$form_data->id] );

			$wpsc_customer_checkout_details[$form_data->id] = $value;
			$bad_input = false;
			if ( ($form_data->mandatory == 1) || ($form_data->type == "coupon") ) {
				// dirty hack
				if ( $form_data->unique_name == 'billingstate' && empty( $value ) ) {
					$billing_country_id = $wpdb->get_var( "SELECT `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `unique_name` = 'billingcountry' AND active = '1' " );

					if ( isset( $_POST['collected_data'][ $billing_country_id ][1] ) ) {
						$value = $_POST['collected_data'][ $billing_country_id ][1];
					} else {
						$any_bad_inputs = true;
						$bad_input      = true;
					}
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
					$wpsc_checkout_error_messages[$form_data->id] = sprintf(__( 'Please enter a valid <span class="wpsc_error_msg_field_name">%s</span>.', 'wpsc' ), esc_attr($form_data->name) );
					$wpsc_customer_checkout_details[$form_data->id] = '';
				}
			}

			if ( ! $bad_input ) {
				if ( $form_data->unique_name == 'shippingstate' ) {
					$shipping_country_field_id = wpsc_get_country_form_id_by_type( 'delivery_country' );
					$shipping_country = $_POST['collected_data'][$shipping_country_field_id];
					if ( ! is_array( $shipping_country ) || ! isset( $shipping_country[1] ) ) {
						wpsc_update_customer_meta( 'shipping_region', $value );
						$location_changed = true;
					}
				} elseif ( $form_data->unique_name == 'billingstate' ) {
					$billing_country_field_id = wpsc_get_country_form_id_by_type( 'country' );
					$billing_country = $_POST['collected_data'][$billing_country_field_id];
					if ( ! is_array( $billing_country ) || ! isset( $billing_country[1] ) ) {
						wpsc_update_customer_meta( 'billing_region', $value );
						$location_changed = true;
					}
				}
			}
		}

		wpsc_update_customer_meta( 'checkout_error_messages'     , $wpsc_checkout_error_messages     );
		wpsc_update_customer_meta( 'gateway_error_messages'      , $wpsc_gateway_error_messages      );
		wpsc_update_customer_meta( 'registration_error_messages' , $wpsc_registration_error_messages );

		$filtered_checkout_details = apply_filters( 'wpsc_update_customer_checkout_details', $wpsc_customer_checkout_details );
		// legacy filter
		if ( is_user_logged_in() )
			$filtered_checkout_details = apply_filters( 'wpsc_checkout_user_profile_update', $wpsc_customer_checkout_details, get_current_user_id() );
		wpsc_update_customer_meta( 'checkout_details', $filtered_checkout_details );

		if ( $location_changed )
			$wpsc_cart->update_location();

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
								    WPSC_TABLE_SUBMITTED_FORM_DATA,
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
								    WPSC_TABLE_SUBMITTED_FORM_DATA,
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
								);
					}
				}
			} else {
			    $prepared_query = $wpdb->insert(
							WPSC_TABLE_SUBMITTED_FORM_DATA,
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
			    WPSC_TABLE_SUBMITTED_FORM_DATA,
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
			    WPSC_TABLE_SUBMITTED_FORM_DATA,
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
		global $wpsc_checkout_error_messages;
		$wpsc_checkout_error_messages = array();
		wpsc_delete_customer_meta( 'checkout_error_messages' );
		$this->current_checkout_item = -1;
		if ( $this->checkout_item_count > 0 ) {
			$this->checkout_item = $this->checkout_items[0];
		}
	}

}