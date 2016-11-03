<?php

global $wpsc_purchlog_statuses;
if (!isset($wpsc_purchlog_statuses) || !count($wpsc_purchlog_statuses)) {
   wpsc_core_load_purchase_log_statuses();
}

function wpsc_instantiate_purchaselogitem() {
   global $purchlogitem;
   if ( isset( $_REQUEST['purchaselog_id'] ) )
	  $purchlogitem = new wpsc_purchaselogs_items( (int)$_REQUEST['purchaselog_id'] );

}
add_action( 'wpsc_core_included', 'wpsc_instantiate_purchaselogitem' );

function wpsc_display_purchlog_howtheyfoundus() {
   global $purchlogitem;
   return esc_attr( $purchlogitem->extrainfo->find_us );
}

function wpsc_display_purchlog_display_howtheyfoundus() {
   global $purchlogitem;
   if ( !empty( $purchlogitem->extrainfo->find_us ) )
	  return true;
   else
	  return false;
}

function wpsc_check_uniquenames() {
   global $wpdb;
   $sql = 'SELECT COUNT(`id`) FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE unique_name != "" ';
   $check_unique_names = $wpdb->get_var( $sql );
   if ( $check_unique_names > 0 ) {
	  return false;
   } else {
	  return true;
   }
}

/** Does the purchaselog have tracking information
 * @return boolean
 */
function wpsc_purchlogs_has_tracking() {
	global $purchlogitem;
	if ( ! empty( $purchlogitem->extrainfo->track_id ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * * @return string  current tracking id or or empty string if there isn't a tracking id
 */
function wpsc_purchlogitem_trackid() {
	global $purchlogitem;
	return esc_attr( empty( $purchlogitem->extrainfo->track_id ) ? '' : $purchlogitem->extrainfo->track_id );
}

/** Purchase shipping status
 * @return string shipping status or empty string
 */
function wpsc_purchlogitem_trackstatus() {
	global $wpsc_shipping_modules, $purchlogitem;

	$callable = array( $purchlogitem->extrainfo->shipping_method, 'getStatus' );
	$shipping_status_is_callable = is_callable( $callable );

	if ( $shipping_status_is_callable && ! empty( $purchlogitem->extrainfo->track_id ) ) {
		$status = $wpsc_shipping_modules [$purchlogitem->extrainfo->shipping_method]->getStatus( $purchlogitem->extrainfo->track_id );
	} else {
		$status = '';
	}

	return $status;
}

/** Tracking history for purchase
 * @return string tracking history or empty string
 */
function wpsc_purchlogitem_trackhistory() {
	global $purchlogitem;

	if ( ( 'nzpost' == $purchlogitem->extrainfo->shipping_method ) && ! empty( $purchlogitem->extrainfo->track_id ) ) {

		$output  = '<ul>';
		$outputs = array();

		foreach ( ( array ) $_SESSION ['wpsc_nzpost_parsed'] [0] ['children'] [0] ['children'] [1] ['children'] as $history ) {
			$outputs[] = '<li>' . $history ['children'] [0] ['tagData'] . ' : ' . $history ['children'] [1] ['tagData'] . ' </li>';
		}

		$outputs = array_reverse( $outputs );
		foreach ( $outputs as $o ) {
			$output .= $o;
		}

		$output .= '</ul>';
		return $output;
	} else {
		// TODO: If there isn't one already, we should add a tracking callback to the shipping API
		return '';
	}
}


/**
 * Weight of current or specified purchase
 *
 * @since 3.8.14
 *
 *
 * @param string $id
 * @return float $weight in '$out_unit' of shipment
 */
function wpsc_purchlogs_get_weight( $id = '', $out_unit = 'pound' ) {
	global $purchlogitem;
	$weight = 0.0;
	$items_count = 0;

	if ( empty( $id ) || ( ! empty( $purchlogitem ) &&  ( $id == $purchlogitem->purchlogid ) ) ) {
		$thepurchlogitem = $purchlogitem;
	} else {
		$thepurchlogitem = new wpsc_purchaselogs_items( $id );
	}

	/**
	 * Filter wpsc_purchlogs_before_get_weight
	 *
	 * Allow the weight to be overridden, can be used to persistantly save weight and recall it rather than recalculate
	 *
	 * @since 3.8.14
	 *
	 * @param  float  $weight, purchase calculation will not continue if value is returned
	 * @param  string weight unit to use for return value
	 * @param  object wpsc_purchaselogs_items purchase log item being used
	 * @param  int    purchase log item id
	 * @return float  $weight
	 */
	$weight_override = apply_filters( 'wpsc_purchlogs_before_get_weight', false, $out_unit, $thepurchlogitem, $thepurchlogitem->purchlogid );
	if ( $weight_override !== false ) {
		return $weight_override;
	}

	// if there isn't a purchase log item we are done
	if ( empty( $thepurchlogitem ) ) {
		return false;
	}

	foreach ( ( array ) $thepurchlogitem->allcartcontent as $cartitem ) {
		$product_meta = get_product_meta( $cartitem->prodid, 'product_metadata', true );
		if ( ! empty( $product_meta ['weight'] ) ) {

			$converted_weight = wpsc_convert_weight( $product_meta ['weight'], $product_meta['weight_unit'], $out_unit, true );

			$weight += $converted_weight * $cartitem->quantity;
			$items_count += $cartitem->quantity;
		}
	}

	/**
	 * Filter wpsc_purchlogs_get_weight
	 *
	 * Allow the weight to be overridden
	 *
	 * @since 3.8.14
	 *
	 * @param  float  $weight                 calculated cart weight
	 * @param  object wpsc_purchaselogs_items purchase log item being used
	 * @param  int    purchase log item id
	 * @param  int    $items_count            how many items are in the cart, useful for
	 *                                        cases where packaging weight changes as more items are
	 *                                        added
	 */
	$weight = apply_filters( 'wpsc_purchlogs_get_weight', $weight, $thepurchlogitem, $thepurchlogitem->purchlogid, $items_count );

	return $weight;
}

/**
 * Weight of current or specified purchase formatted as text with units
 *
 * @since 3.8.14
 *
 * @param string $id
 * @return string $weight in KG and lbs and ounces
 */
function wpsc_purchlogs_get_weight_text( $id = '' ) {
	global $purchlogitem;

	if ( empty( $id ) ) {
		$id = $purchlogitem->purchlogid;
	}

	$weight_in_pounds = wpsc_purchlogs_get_weight( $id, 'pound' );

	if ( $weight_in_pounds > 0 ) {

		$pound = floor( $weight_in_pounds );
		$ounce = round( ( $weight_in_pounds - $pound ) * 16 );

		$weight_in_kg = wpsc_purchlogs_get_weight( $id, 'kg' );

		$weight_string = number_format( $weight_in_kg , 2 ) .' ' .  __( 'KG' , 'wp-e-commerce' ) . ' / ' .  $pound . ' ' .  __( 'LB', 'wp-e-commerce' ) . ' ' . $ounce . ' ' . __( 'OZ', 'wp-e-commerce' );

	} else {
		$weight_string = '';
	}

	/**
	 * Filter wpsc_purchlogs_get_weight_text
	 *
	 * Format weight as text suitable to inform user of purchase shipping weight
	 *
	 * @since 3.8.14
	 *
	 * @param  string weight of purchase as text string with both KG and pounds/ounces
	 * @param  object wpsc_purchaselogs_items purchase log item being used
	 */
	return apply_filters( 'wpsc_purchlogs_get_weight_text', $weight_string, $id  );

}

function wpsc_purchlogs_has_customfields( $id = '' ) {
   global $purchlogitem;
   if ( $id == '' ) {
	  foreach ( (array)$purchlogitem->allcartcontent as $cartitem ) {
		 if ( $cartitem->files != 'N;' || $cartitem->custom_message != '' ) {
			return true;
		 }
	  }
	  return false;
   } else {
	  $purchlogitem = new wpsc_purchaselogs_items( $id );
	  foreach ( (array)$purchlogitem->allcartcontent as $cartitem ) {
		 if ( $cartitem->files != 'N;' || $cartitem->custom_message != '' ) {
			return true;
		 }
	  }
	  return false;
   }
   return false;
}

function wpsc_trackingid_value() {
   global $purchlogs;
   return $purchlogs->purchitem->track_id;
}

function wpsc_purchlogs_custommessages() {
   global $purchlogitem;
   $messages = array();
   foreach ( $purchlogitem->allcartcontent as $cartitem ) {
	  if ( $cartitem->custom_message != '' ) {
		 $messages[] = array(
		 	'title'   => apply_filters( 'the_title', $cartitem->name ),
		 	'message' => $cartitem->custom_message,
		 );
	  }
   }
   return $messages;
}

function wpsc_purchlogs_customfiles() {
   global $purchlogitem;
   $files = array( );
   foreach ( $purchlogitem->allcartcontent as $cartitem ) {
	  if ( $cartitem->files != 'N;' ) {
		 $file = unserialize( $cartitem->files );

		 if ( $file["mime_type"] == "image/jpeg" || $file["mime_type"] == "image/png" || $file["mime_type"] == "image/gif" ) {
			$image = "<a href='" . esc_url ( WPSC_USER_UPLOADS_URL . $file['file_name'] ) . "' >";
			$image .= "<img width='150' src='".esc_url( WPSC_USER_UPLOADS_URL . $file['file_name'] ). "' alt='' />";
			$image .="</a>";
			$files[] = $cartitem->name . ' :<br />' . $image;
		 } else {
			$files[] = $cartitem->name . ' :<br />' . esc_url( $file['file_name'] );
		 }
	  }
   }
   return $files;
}

function wpsc_have_purch_items() {
   global $purchlogs;
   return $purchlogs->have_purch_items();
}

function wpsc_is_checked_status() {
   global $purchlogs;

   return $purchlogs->is_checked_status();
}

function wpsc_have_purchaselog_details() {
   global $purchlogitem;
   return $purchlogitem->have_purch_item();
}

function wpsc_purchaselog_details_name() {
   global $purchlogitem;
   return esc_html( apply_filters( 'the_title', $purchlogitem->purchitem->name, $purchlogitem->purchitem->prodid ) );
}

function wpsc_purchaselog_details_id() {
   global $purchlogitem;
   return $purchlogitem->purchitem->id;
}

function wpsc_purchaselog_product_id() {
   global $purchlogitem;
   return $purchlogitem->purchitem->prodid;
}

function wpsc_the_purchaselog_item() {
   global $purchlogitem;
   return $purchlogitem->the_purch_item();
}

function wpsc_purchaselog_details_SKU() {
   global $purchlogitem;
   $meta_value = wpsc_get_cart_item_meta( $purchlogitem->purchitem->id, 'sku', true );
   if ( $meta_value != null ) {
	  return esc_attr( $meta_value );
   } else {
	  $meta_value = get_product_meta( $purchlogitem->purchitem->prodid, 'sku', true );
	  if ( $meta_value != null ) {
		 return esc_attr( $meta_value );
	  } else {
		 return __('N/A', 'wp-e-commerce');
	  }
   }
}

function wpsc_purchaselog_details_quantity() {
   global $purchlogitem;
   return (float) $purchlogitem->purchitem->quantity;
}

function wpsc_purchaselog_details_price() {
   global $purchlogitem;
   return (float) $purchlogitem->purchitem->price;
}

function wpsc_purchaselog_details_shipping() {
   global $purchlogitem;
   return (float) $purchlogitem->purchitem->pnp;
}

function wpsc_purchaselog_details_tax() {
   global $purchlogitem, $wpsc_cart;

   return (float) $purchlogitem->purchitem->tax_charged;
}

function wpsc_purchaselog_details_discount() {
   global $purchlogitem;
   return (float) $purchlogitem->extrainfo->discount_value;
}

function wpsc_purchaselog_details_date() {
   global $purchlogitem;
   return date_i18n( apply_filters( 'wpsc_single_purchase_log_date_format', get_option( 'date_format' ) ), $purchlogitem->extrainfo->date + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
}

function wpsc_purchaselog_details_date_time() {
   global $purchlogitem;
   return date_i18n( apply_filters( 'wpsc_single_purchase_log_date_time_format', get_option( 'date_format' ) . ' g:ia' ),   $purchlogitem->extrainfo->date + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
}

function wpsc_purchaselog_details_total() {
   global $purchlogitem;
   $total = 0;
   $total += ( $purchlogitem->purchitem->price * $purchlogitem->purchitem->quantity);
   $total += ( $purchlogitem->purchitem->pnp );
   $purchlogitem->totalAmount += $total;
   return $total;
}

function wpsc_purchaselog_details_purchnumber() {
   global $purchlogitem;
   return $purchlogitem->extrainfo->id;
}

/*
 * Has Discount Data?
 */

function wpsc_purchlog_has_discount_data() {
   global $purchlogitem;
   return!empty( $purchlogitem->extrainfo->discount_data );
}

/*
 * Returns Discount Code
 */

function wpsc_display_purchlog_discount_data( $numeric = false ) {
   global $purchlogitem;
   return $purchlogitem->extrainfo->discount_data;
}

/*
 * Returns base shipping should make a function to calculate items shipping as well
 */

function wpsc_display_purchlog_discount( $numeric = false ) {
   global $purchlogitem;
   $discount = $purchlogitem->extrainfo->discount_value;
   if ( $numeric == true ) {
	  return $discount;
   } else {
	  return wpsc_currency_display( $discount,array( 'display_as_html' => false ) );
   }
}

/*
 * Returns base shipping should make a function to calculate items shipping as well
 */

function wpsc_display_purchlog_shipping( $numeric = false, $include_item = false ) {
   global $purchlogitem;
   $base_shipping = $purchlogitem->extrainfo->base_shipping;
   $per_item_shipping = 0;

   if ( $include_item ) {
      foreach ( (array)$purchlogitem->allcartcontent as $cart_item ) {
         if ( $cart_item->pnp > 0 ) {
            $per_item_shipping += ( $cart_item->pnp );
         }
      }
   }

   $total_shipping = $per_item_shipping + $base_shipping;

   if ( $numeric == true ) {
      return $total_shipping;
   } else {
      return wpsc_currency_display( $total_shipping,array( 'display_as_html' => false ) );
   }
}

/**
 * @description: returns taxes as set in purchase log
 * @param: numeric - if set will return unformatted price
 * */
function wpec_display_purchlog_taxes( $numeric = false ) {
	return wpsc_display_purchlog_taxes( $numeric );
}

/**
 * @description: determines whether or not to display the product tax or not
 * @return: boolean
**/
function wpec_display_product_tax()
{
   global $purchlogitem;
   return ($purchlogitem->extrainfo->wpec_taxes_total == 0.00) ? true : false;
}// wpec_display_product_tax


function wpsc_display_purchlog_taxes( $numeric = false ) {
	global $purchlogitem;
	return ($numeric) ? $purchlogitem->extrainfo->wpec_taxes_total : wpsc_currency_display( $purchlogitem->extrainfo->wpec_taxes_total,array( 'display_as_html' => false ) );
}

function wpsc_display_purchlog_totalprice() {
	global $purchlogitem;
	$total = $purchlogitem->totalAmount - wpsc_display_purchlog_discount( true ) + wpsc_display_purchlog_shipping( true ) + wpsc_display_purchlog_taxes( true );
	return wpsc_currency_display( $total, array( 'display_as_html' => false ) );
}

function wpsc_display_purchlog_buyers_name() {
   global $purchlogitem;

   $first_name = $last_name = '';

   if ( isset( $purchlogitem->userinfo['billingfirstname'] ) ) {
   		$first_name = $purchlogitem->userinfo['billingfirstname']['value'];
   }

   if ( isset( $purchlogitem->userinfo['billinglastname'] ) ) {
   		$last_name = ' ' . $purchlogitem->userinfo['billinglastname']['value'];
   }

   $name = trim( $first_name . $last_name );

   return esc_html( $name );
}

function wpsc_display_purchlog_buyers_city() {
   global $purchlogitem;
   return isset( $purchlogitem->userinfo['billingcity'] ) ? esc_html( $purchlogitem->userinfo['billingcity']['value'] ) : '';
}

function wpsc_display_purchlog_buyers_email() {
   global $purchlogitem;
   return esc_html( $purchlogitem->userinfo['billingemail']['value'] );
}

function wpsc_display_purchlog_buyers_address() {
   global $purchlogitem;
   return isset( $purchlogitem->userinfo['billingaddress'] ) ? nl2br( esc_html( $purchlogitem->userinfo['billingaddress']['value'] ) ) : '';
}

function wpsc_display_purchlog_buyers_state_and_postcode() {
   global $purchlogitem;
   if( is_numeric($purchlogitem->extrainfo->billing_region ) )
		 $state = wpsc_get_region($purchlogitem->extrainfo->billing_region);
   else
		 $state = $purchlogitem->userinfo['billingstate']['value'];

   $output = esc_html( $state );

   if ( isset( $purchlogitem->userinfo['billingpostcode']['value'] ) && ! empty( $purchlogitem->userinfo['billingpostcode']['value'] ) ) {
      if (! empty( $output ) ) {
         $output .= ', ';
      }
      $output .= esc_html( $purchlogitem->userinfo['billingpostcode']['value'] );
   }

   return $output;
}

function wpsc_display_purchlog_buyers_country() {
   global $purchlogitem;
   return esc_html( wpsc_get_country( $purchlogitem->userinfo['billingcountry']['value'] ) );
}

function wpsc_display_purchlog_buyers_phone() {
   global $purchlogitem;
   $value = '';
   if ( isset( $purchlogitem->userinfo['billingphone']['value'] ) )
      $value = $purchlogitem->userinfo['billingphone']['value'];

   return esc_html( $value );
}

function wpsc_display_purchlog_shipping_name() {
   global $purchlogitem;
   return esc_html( $purchlogitem->shippinginfo['shippingfirstname']['value'] ) . ' ' . esc_html( $purchlogitem->shippinginfo['shippinglastname']['value'] );
}

function wpsc_display_purchlog_shipping_address() {
	global $purchlogitem;

	if ( isset( $purchlogitem->shippinginfo['shippingaddress'] ) ) {
		return nl2br( esc_html( $purchlogitem->shippinginfo['shippingaddress']['value'] ) );
	} else {
		return '';
	}

}

function wpsc_display_purchlog_shipping_city() {
   global $purchlogitem;

	if ( isset( $purchlogitem->shippinginfo['shippingcity'] ) ) {
		return esc_html( $purchlogitem->shippinginfo['shippingcity']['value'] );
	} else {
		return '';
	}
}

function wpsc_display_purchlog_shipping_state_and_postcode() {
   global $purchlogitem;
   $state = '';
   if( is_numeric($purchlogitem->extrainfo->shipping_region) )
		$state = esc_html( wpsc_get_region($purchlogitem->extrainfo->shipping_region) );
   else
		$state = esc_html( $purchlogitem->shippinginfo['shippingstate']['value'] );

   if ( !empty( $purchlogitem->shippinginfo['shippingpostcode']['value'] ) ){
		if( empty( $state ) )
			$state = esc_html( $purchlogitem->shippinginfo['shippingpostcode']['value'] );
		else
			$state .= ' ' . esc_html( $purchlogitem->shippinginfo['shippingpostcode']['value'] );
   }

   return $state;
}

function wpsc_display_purchlog_shipping_country() {
   global $purchlogitem;

	if ( isset( $purchlogitem->shippinginfo['shippingcountry'] ) ) {
		return esc_html( wpsc_get_country( $purchlogitem->shippinginfo['shippingcountry']['value'] ) );
	} else {
		return '';
	}
}

function wpsc_display_purchlog_shipping_method() {
   global $purchlogitem, $wpsc_shipping_modules;

   if ( ! empty ( $wpsc_shipping_modules[$purchlogitem->extrainfo->shipping_method] ) ) {
	  $shipping_class = &$wpsc_shipping_modules[$purchlogitem->extrainfo->shipping_method];
	  return esc_html( $shipping_class->getName() );
   } else {
	  return esc_html( $purchlogitem->extrainfo->shipping_method );
   }
}

function wpsc_display_purchlog_shipping_option() {
   global $purchlogitem;
   return esc_html( $purchlogitem->extrainfo->shipping_option );
}

function wpsc_display_purchlog_paymentmethod() {
   global $purchlogitem, $nzshpcrt_gateways;
   $gateway_name = '';
   if('wpsc_merchant_testmode' == $purchlogitem->extrainfo->gateway)
      return __( 'Manual Payment', 'wp-e-commerce' );

   foreach ( (array)$nzshpcrt_gateways as $gateway ) {
	  if ( $gateway['internalname'] == $purchlogitem->extrainfo->gateway )
		 $gateway_name = $gateway['name'];
   }
   if( !empty($gateway_name) )
	  return esc_html( $gateway_name );
   else
	  return esc_html( $purchlogitem->extrainfo->gateway );

}

function wpsc_purchaselog_order_summary_headers() {
	global $purchlogitem;
	do_action( 'wpsc_purchaselog_order_summary_headers', $purchlogitem );
}

function wpsc_purchaselog_order_summary() {
	global $purchlogitem;
	do_action( 'wpsc_purchaselog_order_summary', $purchlogitem );
}

function wpsc_has_purchlog_shipping() {
   global $purchlogitem;
   if ( isset( $purchlogitem->shippinginfo['shippingfirstname'] ) && $purchlogitem->shippinginfo['shippingfirstname']['value'] != '' ) {
	  return true;
   } else {
	  return false;
   }
}

function wpsc_purchlogs_have_downloads_locked() {
   global $purchlogitem;
   $ip = $purchlogitem->have_downloads_locked();
   if ( $ip != '' ) {
	  return sprintf( __( 'Release downloads locked to this IP address %s', 'wp-e-commerce' ), $ip );
   } else {
	  return false;
   }
}

/**
 * Display Purchase Log Notes
 *
 * @return  string  Notes.
 */
function wpsc_display_purchlog_notes() {
	global $purchlogitem;
	$purchase_log = new WPSC_Purchase_Log( $purchlogitem->purchlogid );
	return $purchase_log->get( 'notes' );
}

//edit purchase log status function
function wpsc_purchlog_edit_status( $purchlog_id='', $purchlog_status='' ) {
   global $wpdb;
   if ( empty($purchlog_id) && empty($purchlog_status) ) {
      $purchlog_id = absint( $_POST['id'] );
      $purchlog_status = absint( $_POST['new_status'] );
   }

   $purchase_log = new WPSC_Purchase_Log( $purchlog_id );

   //in the future when everyone is using the 2.0 merchant api, we should use the merchant class to update the staus,
   // then you can get rid of this hook and have each person overwrite the method that updates the status.
   do_action('wpsc_edit_order_status', array('purchlog_id'=>$purchlog_id, 'purchlog_data'=>$purchase_log->get_data(), 'new_status'=>$purchlog_status));

   $result = wpsc_update_purchase_log_status( $purchlog_id, $purchlog_status );
   wpsc_clear_stock_claims();

   return $result;
}
