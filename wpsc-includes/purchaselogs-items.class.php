<?php
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


   public function __construct( $id ) {
	  $this->purchlogid = $id;
	  $this->get_purchlog_details();
   }

   function shippingstate( $id ) {
	  global $wpdb;
	  if ( is_numeric( $id ) ) {
		 $name = wpsc_get_region( $id );
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

      $additional_fields = $billingdetails = $shippinginfo = array();

      foreach ( (array) $userinfo as $input_row ) {
         if ( stristr( $input_row['unique_name'], 'shipping' ) ) {
            $shippinginfo[$input_row['unique_name']] = $input_row;
         } elseif ( stristr( $input_row['unique_name'], 'billing' ) ) {
            $billingdetails[ $input_row['unique_name'] ] = $input_row;
         } else {
            $additionaldetails[ $input_row['name'] ] = $input_row;
            $additional_fields[] = $input_row;
         }
      }
      $this->userinfo     = $billingdetails;
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
