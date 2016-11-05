<?php
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
   var $purchstatus;
   //calculation of totals
   var $totalAmount;
   //used for csv
   var $current_start_timestamp;
   var $current_end_timestamp;
   var $start_timestamp;
   var $end_timestamp;

	/* Constructor function */
	public function __construct() {
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
	  $date_list = array();

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
		 return '<div id="message" class="updated fade"><p>' . __( 'Thanks, the purchase log record has been deleted', 'wp-e-commerce' ) . '</p></div>';
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

	  $emailformid = 0;
	  $fNameformid = 0;
	  $lNameformid = 0;

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
		 $namestring = __('N/A', 'wp-e-commerce');
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
		global $wpdb, $wp_version;

		if ( version_compare( $wp_version, '4.0', '>=' ) ) {
			$searchterm = '%' . $wpdb->esc_like( $searchterm ) . '%';
		} else {
			$searchterm = '%' . like_escape( $searchterm ) . '%';
		}

		$newlogs = $wpdb->get_results( $wpdb->prepare(
			"SELECT DISTINCT `" . WPSC_TABLE_PURCHASE_LOGS . "` . * FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`
			LEFT JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "`
			ON `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`log_id` = `" . WPSC_TABLE_PURCHASE_LOGS . "`.`id`
			WHERE `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "`.`value` LIKE %s
			OR `" . WPSC_TABLE_PURCHASE_LOGS . "`.`transactid` = %s
			OR `" . WPSC_TABLE_PURCHASE_LOGS . "`.`track_id` LIKE %s",
			$searchterm
			)
		);

		$_SESSION['newlogs'] = $newlogs;

		return $newlogs;
	}

}
