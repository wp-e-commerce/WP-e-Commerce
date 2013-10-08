<?php

add_action( 'wpsc_core_included', 'wpsc_instantiate_purchaselogitem' );

global $wpsc_purchlog_statuses;
if (!isset($wpsc_purchlog_statuses) || !count($wpsc_purchlog_statuses))
   wpsc_core_load_purchase_log_statuses();

function wpsc_instantiate_purchaselogitem() {
   global $purchlogitem;
   if ( isset( $_REQUEST['purchaselog_id'] ) )
	  $purchlogitem = new wpsc_purchaselogs_items( (int)$_REQUEST['purchaselog_id'] );

}

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

function wpsc_purchlogs_has_tracking() {
   global $wpdb, $wpsc_shipping_modules, $purchlogitem;
   $custom_shipping = get_option( 'custom_shipping_options' );
   if ( in_array( 'nzpost', (array)$custom_shipping ) && $purchlogitem->extrainfo->track_id != '' ) {
	  return true;
   } else {
	  return false;
   }
}

function wpsc_purchlogitem_trackid() {
   global $purchlogitem;
   return esc_attr( $purchlogitem->extrainfo->track_id );
}

function wpsc_purchlogitem_trackstatus() {
   global $wpdb, $wpsc_shipping_modules, $purchlogitem;
   $custom_shipping = get_option( 'custom_shipping_options' );
   if ( in_array( 'nzpost', (array)$custom_shipping ) && $purchlogitem->extrainfo->track_id != '' ) {
	  $status = $wpsc_shipping_modules['nzpost']->getStatus( $purchlogitem->extrainfo->track_id );
   }

   return $status;
}

function wpsc_purchlogitem_trackhistory() {
   global $purchlogitem;
   $output = '<ul>';
   foreach ( (array)$_SESSION['wpsc_nzpost_parsed'][0]['children'][0]['children'][1]['children'] as $history ) {
	  $outputs[] = '<li>' . $history['children'][0]['tagData'] . " : " . $history['children'][1]['tagData'] . " </li>";
   }
   $outputs = array_reverse( $outputs );
   foreach ( $outputs as $o ) {
	  $output .= $o;
   }
   $output .='</ul>';
   return $output;
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
   return esc_html( apply_filters( 'the_title', $purchlogitem->purchitem->name ) );
}

function wpsc_purchaselog_details_id() {
   global $purchlogitem;
   return $purchlogitem->purchitem->id;
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
		 return __('N/A', 'wpsc');
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
   return date_i18n( apply_filters( 'wpsc_single_purchase_log_date_format', get_option( 'date_format' ) ), $purchlogitem->extrainfo->date );
}

function wpsc_purchaselog_details_date_time() {
   global $purchlogitem;
   return date_i18n( apply_filters( 'wpsc_single_purchase_log_date_time_format', get_option( 'date_format' ) . ' g:ia' ), $purchlogitem->extrainfo->date );
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
   return esc_html( $purchlogitem->userinfo['billingfirstname']['value'] ) . ' ' . esc_html( $purchlogitem->userinfo['billinglastname']['value'] );
}

function wpsc_display_purchlog_buyers_city() {
   global $purchlogitem;
   return esc_html( $purchlogitem->userinfo['billingcity']['value'] );
}

function wpsc_display_purchlog_buyers_email() {
   global $purchlogitem;
   return esc_html( $purchlogitem->userinfo['billingemail']['value'] );
}

function wpsc_display_purchlog_buyers_address() {
   global $purchlogitem;
   return nl2br( esc_html( $purchlogitem->userinfo['billingaddress']['value'] ) );
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
   return esc_html( wpsc_get_country( $purchlogitem->extrainfo->billing_country ) );}

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
   return nl2br( esc_html( $purchlogitem->shippinginfo['shippingaddress']['value'] ) );
}

function wpsc_display_purchlog_shipping_city() {
   global $purchlogitem;
   return esc_html( $purchlogitem->shippinginfo['shippingcity']['value'] );
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
			$state .= ', ' . esc_html( $purchlogitem->shippinginfo['shippingpostcode']['value'] );
   }

   return $state;
}

function wpsc_display_purchlog_shipping_country() {
   global $purchlogitem;
   return esc_html( wpsc_get_country( $purchlogitem->shippinginfo['shippingcountry']['value'] ) );
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
      return __( 'Manual Payment', 'wpsc' );

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
   if ( $purchlogitem->shippinginfo['shippingfirstname']['value'] != '' ) {
	  return true;
   } else {
	  return false;
   }
}

function wpsc_purchlogs_have_downloads_locked() {
   global $purchlogitem;
   $ip = $purchlogitem->have_downloads_locked();
   if ( $ip != '' ) {
	  return sprintf( __( 'Release downloads locked to this IP address %s', 'wpsc' ), $ip );
   } else {
	  return false;
   }
}

/* Start Order Notes (by Ben) */

function wpsc_display_purchlog_notes() {
   global $purchlogitem;
   if ( isset( $purchlogitem->extrainfo->notes ) ) {
	  return $purchlogitem->extrainfo->notes;
   } else {
	  return false;
   }
}

/* End Order Notes (by Ben) */

/**
 * WP eCommerce purchaselogs AND purchaselogs_items class
 *
 * These is the classes for the WP eCommerce purchase logs,
 * The purchaselogs class handles adding, removing and adjusting details in the purchaselogs,
 * The purchaselogs_items class handles adding, removing and adjusting individual item details in the purchaselogs,
 *
 * @package wp-e-commerce
 * @since 3.7
 * @subpackage wpsc-cart-classes
 */
class wpsc_purchaselogs {

   var $earliest_timestamp;
   var $current_timestamp;
   var $earliest_year;
   var $current_year;
   var $form_data;
   var $purch_item_count;
   //individual purch log variables
   var $allpurchaselogs;
   var $currentitem = -1;
   var $purchitem;
   //used for purchase options
   var $currentstatus = -1;
   var $purch_status_count;
   var $allpurchaselogstatuses;
   //calculation of totals
   var $totalAmount;
   //used for csv
   var $current_start_timestamp;
   var $current_end_timestamp;

	/* Constructor function */
	function wpsc_purchaselogs() {
		$this->getall_formdata();
		if ( !isset( $_GET['view_purchlogs_by'] ) && !isset( $_GET['purchlogs_searchbox'] ) ) {
			$dates = $this->getdates();
			$dates = array_slice( $dates, 0, 3 );
			if(isset($dates[2]['start']))
				$this->current_start_timestamp = $dates[2]['start'];
			$this->current_end_timestamp = $dates[0]['end'];
			$newlogs = $this->get_purchlogs( $dates );
			$_SESSION['newlogs'] = $newlogs;
			$this->allpurchaselogs = $newlogs;
	  } else {
		 $this->getdates();
		 if ( isset( $_GET['view_purchlogs_by'] ) && isset( $_GET['view_purchlogs_by_status'] ) ) {
			$status = sanitize_text_field( $_GET['view_purchlogs_by_status'] );
			$viewby = sanitize_text_field( $_GET['view_purchlogs_by'] );
			if ( $viewby == 'all' ) {
			   $dates = $this->getdates();
			   $purchaselogs = $this->get_purchlogs( $dates, $status );
			   $_SESSION['newlogs'] = $purchaselogs;
			   $this->allpurchaselogs = $purchaselogs;
			} elseif ( $viewby == '3mnths' ) {
			   $dates = $this->getdates();

			   $dates = array_slice( $dates, 0, 3 );
			   $this->current_start_timestamp = $dates[count($dates)-1]['start'];
			   $this->current_end_timestamp = $dates[0]['end'];
			   $newlogs = $this->get_purchlogs( $dates, $status );
			   $_SESSION['newlogs'] = $newlogs;
			   $this->allpurchaselogs = $newlogs;
			} else {
			   $dates = explode( '_', $viewby );
			   $date[0]['start'] = $dates[0];
			   $date[0]['end'] = $dates[1];
			   $this->current_start_timestamp = $dates[0];
			   $this->current_end_timestamp = $dates[1];
			   $newlogs = $this->get_purchlogs( $date, $status );
			   $_SESSION['newlogs'] = $newlogs;
			   $this->allpurchaselogs = $newlogs;
			}
		 }
	  }
	  $this->purch_item_count = count( $this->allpurchaselogs );
	  $statuses = $this->the_purch_item_statuses();
	  if ( isset( $_SESSION['newlogs'] ) ) {
		 $this->allpurchaselogs = $_SESSION['newlogs'];
		 $this->purch_item_count = count( $_SESSION['newlogs'] );
	  }

	  return;
   }

   function get_purchlogs( $dates, $status='' ) {
	  global $wpdb;
	   $purchlog2 = array();
	  $orderby = apply_filters( 'wpsc_purchase_logs_orderby', "' ORDER BY `date` DESC" );
	  if ( $status == '' || $status == '-1' ) {
		 foreach ( (array)$dates as $date_pair ) {
			if ( ($date_pair['end'] >= $this->earliest_timestamp) && ($date_pair['start'] <= $this->current_timestamp) ) {
			   $sql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date` BETWEEN %s AND %s {$orderby}", $date_pair['start'], $date_pair['end'] );
			   $purchase_logs = $wpdb->get_results( $sql );
			   array_push( $purchlog2, $purchase_logs );
			}
		 }
	  } else {
		 foreach ( (array)$dates as $date_pair ) {
			if ( ($date_pair['end'] >= $this->earliest_timestamp) && ($date_pair['start'] <= $this->current_timestamp) ) {
			   $sql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date` BETWEEN %s AND %s AND `processed`=%s {$orderby}", $date_pair['start'], $date_pair['end'], $status );
			   $purchase_logs = $wpdb->get_results( $sql );
			   array_push( $purchlog2, $purchase_logs );
			}
		 }
	  }
	  $newarray = array( );
	  foreach ( $purchlog2 as $purch ) {
		 if ( is_array( $purch ) ) {
			foreach ( $purch as $log ) {
			   $newarray[] = $log;
			}
		 } else {
			exit( 'Else :' . print_r( $purch ) );
		 }
	  }
	  $this->allpurchaselogs = $newarray;
	  $this->purch_item_count = count( $this->allpurchaselogs );
	  return $newarray;
   }

   function getall_formdata() {
	  global $wpdb;
	  $form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1';";
	  $form_data = $wpdb->get_results( $form_sql, ARRAY_A );
	  $this->form_data = $form_data;
	  return $form_data;
   }

   /*
	* This finds the earliest time in the shopping cart and sorts out the timestamp system for the month by month display
	* or if there was a filter applied use the filter to sort the dates.
	*/

   function getdates() {
	  global $wpdb, $purchlogs;

	  $earliest_record_sql = "SELECT MIN(`date`) AS `date` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date`!=''";
	  $earliest_record = $wpdb->get_results( $earliest_record_sql, ARRAY_A );

	  $this->current_timestamp = time();
	  //if there are no reccords set the date to now.
	  $this->earliest_timestamp = ( isset( $earliest_record[0] ) && isset( $earliest_record[0]['date'] ) )?$earliest_record[0]['date']:time();

	  $this->current_year = date( "Y" );
	  $this->earliest_year = date( "Y", $this->earliest_timestamp );

	  $j = 0;
	  for ( $year = $this->current_year; $year >= $this->earliest_year; $year-- ) {
		 for ( $month = 12; $month >= 1; $month-- ) {
			$this->start_timestamp = mktime( 0, 0, 0, $month, 1, $year );
			$this->end_timestamp = mktime( 0, 0, 0, ($month + 1 ), 1, $year );
			if ( ($this->end_timestamp >= $this->earliest_timestamp) && ($this->start_timestamp <= $this->current_timestamp) ) {
			   $date_list[$j]['start'] = $this->start_timestamp;
			   $date_list[$j]['end'] = $this->end_timestamp;
			   $j++;
			}
		 }
	  }
	  if ( is_object( $purchlogs ) ) {
		 $purchlogs->current_start_timestamp = $purchlogs->earliest_timestamp;
		 $purchlogs->current_end_timestamp = $purchlogs->current_timestamp;
	  }
	  return $date_list;
   }

   function deletelog( $deleteid ) {
	global $wpdb;
	  if ( is_numeric( $deleteid ) ) {

		 $delete_log_form_sql = "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='$deleteid'";
		 $cart_content = $wpdb->get_results( $delete_log_form_sql, ARRAY_A );
		 $wpdb->query( "DELETE FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`='$deleteid'" );
		 $wpdb->query( "DELETE FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` IN ('$deleteid')" );
		 $wpdb->query( "DELETE FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id`='$deleteid' LIMIT 1" );
		 return '<div id="message" class="updated fade"><p>' . __( 'Thanks, the purchase log record has been deleted', 'wpsc' ) . '</p></div>';
	  }
   }

   //individual purchase log functions
   function next_purch_item() {
	  $this->currentitem++;

	  $this->purchitem = $this->allpurchaselogs[$this->currentitem];
	  return $this->purchitem;
   }

   function the_purch_item() {
	  $this->purchitem = $this->next_purch_item();
   }

   function have_purch_items() {
	  if ( $this->currentitem + 1 < $this->purch_item_count ) {
		 return true;
	  } else if ( $this->currentitem + 1 == $this->purch_item_count && $this->purch_item_count > 0 ) {
		 // Do some cleaning up after the loop,
		 $this->rewind_purch_items();
	  }
	  return false;
   }

   function rewind_purch_items() {
	  $this->currentitem = -1;
	  if ( $this->purch_item_count > 0 ) {
		 $this->purchitem = $this->allpurchaselogs[0];
	  }
   }

   function the_purch_item_statuses() {
	  global $wpdb, $wpsc_purchlog_statuses;
	  $this->purch_status_count = count( $wpsc_purchlog_statuses );
	  $this->allpurchaselogstatuses = $wpsc_purchlog_statuses;
	  return $wpsc_purchlog_statuses;
   }

   // purchase status loop functions
   function next_purch_status() {
	  $this->currentstatus++;
	  $this->purchstatus = $this->allpurchaselogstatuses[$this->currentstatus];
	  return $this->purchstatus;
   }

   function the_purch_status() {
	  $this->purchstatus = $this->next_purch_status();
   }

   function have_purch_status() {
	  if ( $this->currentstatus + 1 < $this->purch_status_count ) {
		 return true;
	  } else if ( $this->currentstatus + 1 == $this->purch_status_count && $this->purch_status_count > 0 ) {
		 // Do some cleaning up after the loop,
		 $this->rewind_purch_status();
	  }
	  return false;
   }

   function rewind_purch_status() {
	  $this->currentstatus = -1;
	  if ( $this->purch_status_count > 0 ) {
		 $this->purchstatus = $this->allpurchaselogstatuses[0];
	  }
   }

   function is_checked_status() {
	  if ( isset( $this->purchstatus['order'] ) && isset( $this->purchitem->processed ) && ($this->purchstatus['order'] == $this->purchitem->processed) ) {
		 return 'selected="selected"';
	  } else {
		 return '';
	  }
   }

   function the_purch_item_name() {
	  global $wpdb;
	  $i = 0;
	  if ( $this->form_data == null ) {
		 $this->getall_formdata();
	  }
	  foreach ( (array)$this->form_data as $formdata ) {
		 if ( in_array( 'billingemail', $formdata ) ) {
			$emailformid = $formdata['id'];
		 }
		 if ( in_array( 'billingfirstname', $formdata ) ) {
			$fNameformid = $formdata['id'];
		 }
		 if ( in_array( 'billinglastname', $formdata ) ) {
			$lNameformid = $formdata['id'];
		 }
		 $i++;
	  }

	  $sql = "SELECT value FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id=" . $this->purchitem->id . " AND form_id=" . $emailformid;
	  $email = $wpdb->get_var( $sql );
	  $sql = "SELECT value FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id=" . $this->purchitem->id . " AND form_id=" . $fNameformid;
	  $fname = $wpdb->get_var( $sql );
	  $sql = "SELECT value FROM " . WPSC_TABLE_SUBMITTED_FORM_DATA . " WHERE log_id=" . $this->purchitem->id . " AND form_id=" . $lNameformid;
	  $lname = $wpdb->get_var( $sql );
	  $namestring = esc_html( $fname ) . ' ' . esc_html( $lname ) . ' (<a href="mailto:' . esc_attr( $email ) . '?subject=Message From ' . home_url() . '">' . esc_html( $email ) . '</a>) ';
	  if ( $fname == '' && $lname == '' && $email == '' ) {
		 $namestring = __('N/A', 'wpsc');
	  }
	  return $namestring;
   }

   function the_purch_item_details() {
	  global $wpdb;
	  $sql = "SELECT SUM(quantity) FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid=" . $this->purchitem->id;
	  $sum = $wpdb->get_var( $sql );
	  return $sum;
   }

   function search_purchlog_view( $searchterm ) {
	  global $wpdb;
	  $sql = $wpdb->prepare( "SELECT DISTINCT `" . WPSC_TABLE_PURCHASE_LOGS . "` . * FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` LEFT JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` ON `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`log_id` = `" . WPSC_TABLE_PURCHASE_LOGS . "`.`id` WHERE `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`value` LIKE '%" . like_escape( $searchterm ) . "%' OR `" . WPSC_TABLE_PURCHASE_LOGS . "`.`transactid` = %s OR `" . WPSC_TABLE_PURCHASE_LOGS . "`.`track_id` LIKE '%" . like_escape( $searchterm )."%'", $searchterm );
	  $newlogs = $wpdb->get_results( $sql );
	  $_SESSION['newlogs'] = $newlogs;
	  return $newlogs;
   }

}

class wpsc_purchaselogs_items {

   var $purchlogid;
   var $extrainfo;
   //the loop
   var $currentitem = -1;
   var $purchitem;
   var $allcartcontent;
   var $purch_item_count;
   //grand total
   var $totalAmount;
   //usersinfo
   var $userinfo;
   var $shippinginfo;
   var $customcheckoutfields = array( );
   var $additional_fields = array();


   function wpsc_purchaselogs_items( $id ) {
	  $this->purchlogid = $id;
	  $this->get_purchlog_details();
   }

   function shippingstate( $id ) {
	  global $wpdb;
	  if ( is_numeric( $id ) ) {
		 $sql = "SELECT `name` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE id=" . $id;
		 $name = $wpdb->get_var( $sql );
		 return $name;
	  } else {
		 return $id;
	  }
   }

   function get_purchlog_details() {
      global $wpdb;

      $cartcontent = $wpdb->get_results( "SELECT *  FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=" . $this->purchlogid . "" );

      $this->allcartcontent = $cartcontent;
      $sql = "SELECT DISTINCT `" . WPSC_TABLE_PURCHASE_LOGS . "` . * FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` LEFT JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` ON `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`log_id` = `" . WPSC_TABLE_PURCHASE_LOGS . "`.`id` WHERE `" . WPSC_TABLE_PURCHASE_LOGS . "`.`id`=" . $this->purchlogid;
      $extrainfo = $wpdb->get_results( $sql );

      $this->extrainfo = $extrainfo[0];

      $usersql = "SELECT `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`id`, `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`value`, `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`name`, `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`unique_name` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` LEFT JOIN `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` ON `" . WPSC_TABLE_CHECKOUT_FORMS . "`.id = `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`form_id` WHERE `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`log_id`=" . $this->purchlogid . " ORDER BY `" . WPSC_TABLE_CHECKOUT_FORMS . "`.`checkout_order`";
      $userinfo = $wpdb->get_results( $usersql, ARRAY_A );

      // the $additionaldetails array is buggy because if the fields have the same name, they will
      // overwrite each other.
      // $additional_fields is introduced to fix this. However, the $additionaldetails array as well
      // as $this->customcheckoutfields needs to be kept for compatibility purposes.
      $additional_fields = array();
      foreach ( (array)$userinfo as $input_row ) {
         if ( stristr( $input_row['unique_name'], 'shipping' ) ) {
            $shippinginfo[$input_row['unique_name']] = $input_row;
         } elseif ( stristr( $input_row['unique_name'], 'billing' ) ) {
            $billingdetails[$input_row['unique_name']] = $input_row;
         } else {
            $additionaldetails[$input_row['name']] = $input_row;
            $additional_fields[] = $input_row;
         }
      }
      $this->userinfo = $billingdetails;
      $this->shippinginfo = $shippinginfo;
      if ( isset( $additionaldetails ) ) {
         $this->customcheckoutfields = $additionaldetails;
      }
      if ( isset( $additional_fields ) )
         $this->additional_fields = $additional_fields;

      $this->purch_item_count = count( $cartcontent );
   }

   function next_purch_item() {
	  $this->currentitem++;
	  $this->purchitem = $this->allcartcontent[$this->currentitem];
	  return $this->purchitem;
   }

   function the_purch_item() {
	  $this->purchitem = $this->next_purch_item();
   }

   function have_purch_item() {
	  if ( $this->currentitem + 1 < $this->purch_item_count ) {
		 return true;
	  } else if ( $this->currentitem + 1 == $this->purch_item_count && $this->purch_item_count > 0 ) {
		 // Do some cleaning up after the loop,
		 $this->rewind_purch_item();
	  }
	  return false;
   }

   function rewind_purch_item() {
	  $this->currentitem = -1;
	  if ( $this->purch_item_count > 0 ) {
		 $this->purchitem = $this->allcartcontent[0];
	  }
   }

   function have_downloads_locked() {
	  global $wpdb;
	  $sql = "SELECT `ip_number` FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE purchid=" . $this->purchlogid;
	  $ip_number = $wpdb->get_var( $sql );
	  return $ip_number;
   }
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
