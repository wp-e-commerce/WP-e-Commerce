<?php
/**
 * WP eCommerce edit and view sales page functions
 *
 * These are the main WPSC sales page functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */
global $purchlogs;
if(!isset($purchlogs)){
   $purchlogs = new wpsc_purchaselogs();
}

function wpsc_display_sales_logs() {
	$subpage = empty( $_GET['subpage'] ) ? '' : $_GET['subpage'];

	switch( $subpage ) {
		case 'upgrade-purchase-logs':
			wpsc_upgrade_purchase_logs();
		break;

		case 'update-purchase-logs-3.8':
			wpsc_update_purchase_logs_3dot8();
		break;

		default:
			wpsc_display_sales_log_index();
		break;
	}
}

function wpsc_update_purchase_logs_3dot8() {
	if ( _wpsc_purchlogs_need_update() )
		wpsc_update_purchase_logs();
	
	?>
		<div class="wrap">
			<h2><?php echo esc_html( __('Sales', 'wpsc') ); ?> </h2>	
			<p><?php printf( __( 'Your purchase logs have been updated! <a href="%s">Click here</a> to return.' , 'wpsc' ), remove_query_arg( 'subpage' ) ); ?></p>
		</div>
	<?php
}

function _wpsc_purchlogs_need_update() {
	global $wpdb;
	
	if ( get_option( '_wpsc_purchlogs_3.8_updated' ) )
		return false;
	
	$c = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE plugin_version IN ('3.6', '3.7')" );
	if ( $c > 0 )
		return true;
	
	update_option( '_wpsc_purchlogs_3.8_updated', true );
	return false;
}

 function wpsc_display_sales_log_index() {

    global $purchlogitem;

   ?>
   <div class="wrap">
      <h2><?php echo esc_html( __('Sales', 'wpsc') ); ?> </h2>
      <?php //START OF PURCHASE LOG DEFAULT VIEW ?>
      <?php
       if(isset($_GET['view_purchlogs_by']) || isset($_GET['view_purchlogs_by_status']))
         wpsc_change_purchlog_view($_GET['view_purchlogs_by'], $_GET['view_purchlogs_by_status']);

         if(isset($_POST['purchlogs_searchbox']))
            wpsc_search_purchlog_view($_POST['purchlogs_searchbox']);

         if(!isset($_REQUEST['purchaselog_id'])){
            $columns = array(
               'cb' => '<input type="checkbox" />',
               'purchid' => __( 'Order ID', 'wpsc' ),
               'date' => __( 'Date / Time', 'wpsc' ),
               'name' => '',
               'amount' => __( 'Amount', 'wpsc' ),
               'details' => __( 'Details', 'wpsc' ),
               'status' => __( 'Status', 'wpsc' ),
               'delete' => __( 'Delete', 'wpsc' ),
               'track' => __( 'Tracking ID', 'wpsc' )
            );
            register_column_headers('display-sales-list', $columns);
            ///// start of update message section //////

            $fixpage = get_option('siteurl').'/wp-admin/admin.php?page=wpsc-sales-logs&amp;subpage=upgrade-purchase-logs';
         if (isset($_GET['skipped']) || isset($_GET['updated']) || isset($_GET['deleted']) ||  isset($_GET['locked']) ) { ?>
         <div id="message" class="updated fade"><p>
         <?php if ( isset($_GET['updated']) && (int) $_GET['updated'] ) {
            printf( _n( '%s Purchase Log updated.', '%s Purchase Logs updated.', $_GET['updated'], 'wpsc' ), absint( $_GET['updated'] ) );
            unset($_GET['updated']);
         }

         if ( isset($_GET['skipped']) && (int) $_GET['skipped'] )
            unset($_GET['skipped']);

         if ( isset($_GET['locked']) && (int) $_GET['locked'] ) {
            printf( _n( '%s product not updated, somebody is editing it.', '%s products not updated, somebody is editing them.', $_GET['locked'], 'wpsc' ), absint( $_GET['locked'] ) );
            unset($_GET['locked']);
         }

         if ( isset($_GET['deleted']) && (int) $_GET['deleted'] ) {
            printf( _n( '%s Purchase Log deleted.', '%s Purchase Logs deleted.', $_GET['deleted'], 'wpsc' ), absint( $_GET['deleted'] ) );
            unset($_GET['deleted']);
         }
         ?>
         </p></div>
      <?php }

         if(get_option('wpsc_purchaselogs_fixed')== false || (wpsc_check_uniquenames()) ){ ?>
            <div class='error' style='padding:8px;line-spacing:8px;'><span ><?php printf( __('When upgrading the WP e-Commerce Plugin from 3.6.* to 3.7 it is required that you associate your checkout form fields with the new Purchase Logs system. To do so please <a href="%s">Click Here</a>', 'wpsc'), $fixpage); ?></span></div>
   <?php  }

		if ( _wpsc_purchlogs_need_update() ) {
			?>
				<div class='error' style='padding:8px;line-spacing:8px;'><span ><?php printf( __('It has been detected that some of your purchase logs were not updated properly when you upgrade to WP e-Commerce %s. Please <a href="%s">click here</a> to fix this problem.', 'wpsc'), WPSC_VERSION, add_query_arg( 'subpage', 'update-purchase-logs-3.8' ) ); ?></span></div>
			<?php
		}
		
      ///// end of update message section //////?>
      <div id='dashboard-widgets' style='min-width: 825px;'>
         <?php /* end of sidebar start of main column */ ?>
         <div id='post-body' class='has-sidebar metabox-holder' style='width:95%;'>
            <div id='dashboard-widgets-main-content-wpsc' class='has-sidebar-content'>

            <?php
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				do_meta_boxes('dashboard_page_wpsc-sales-logs', 'top', true);
			?>
               </div><br />
               <div id='wpsc_purchlog_searchbox'>
                  <?php wpsc_purchaselogs_searchbox(); ?>
               </div><br />
                  <?php wpsc_purchaselogs_displaylist(); ?>

         </div>
         <script type="text/javascript">
         	jQuery(document).ready(function(){postboxes.add_postbox_toggles(pagenow);});
         </script>
      </div>
      <?php }else{ //NOT IN GENERIC PURCHASE LOG PAGE, IN DETAILS PAGE PER PURCHASE LOG

            if(isset($_REQUEST['purchaselog_id'])){
               $purchlogitem = new wpsc_purchaselogs_items((int)$_REQUEST['purchaselog_id']);
            }
         if (isset($_GET['cleared']) || isset($_GET['cleared'])) { ?>
         <div id="message" class="updated fade"><p>
         <?php
            if ( isset($_GET['cleared']) && $_GET['cleared']==true ) {
               _e('Downloads for this log have been released.', 'wpsc' );
               unset($_GET['cleared']);
            }
            if ( isset($_GET['sent']) && (int) $_GET['sent'] ) {
               _e( 'Receipt has been resent ', 'wpsc' );
               unset($_GET['sent']);
            }
         ?> </p></div>
         <?php
         }
         ?>


         <?php
      $page_back = remove_query_arg( array('locked', 'skipped', 'updated', 'deleted','purchaselog_id'), $_SERVER['REQUEST_URI'] );

      $columns = array(
      'title' => __('Name','wpsc'),
         'sku' => __('SKU','wpsc'),
         'quantity' => __('Quantity','wpsc'),
         'price' => __('Price','wpsc'),
         'shipping' => __('Shipping','wpsc'),
         'tax' => '',
         'total' => __('Total','wpsc')
      );

      if(wpec_display_product_tax())
      {
         $columns['tax'] = __('Tax Included','wpsc');
      }// if

      register_column_headers('display-purchaselog-details', $columns);
      ?>
         <div id='post-body' class='has-sidebar' style='width:95%;'>
            <?php if(wpsc_has_purchlog_shipping()) { ?>
            <div id='wpsc_shipping_details_box'>
               <h3><?php _e('Shipping Address','wpsc'); ?></h3>
               <p><strong><?php echo wpsc_display_purchlog_shipping_name(); ?></strong></p>
               <p>
               <?php echo wpsc_display_purchlog_shipping_address(); ?><br />
               <?php echo wpsc_display_purchlog_shipping_city(); ?><br />
               <?php echo wpsc_display_purchlog_shipping_state_and_postcode(); ?><br />
               <?php echo wpsc_display_purchlog_shipping_country(); ?><br />
               </p>
               <strong><?php _e('Shipping Options','wpsc'); ?></strong>
               <p>

               <?php _e('Shipping Method:','wpsc'); ?> <?php echo wpsc_display_purchlog_shipping_method(); ?><br />
               <?php _e('Shipping Option:','wpsc'); ?> <?php echo wpsc_display_purchlog_shipping_option(); ?><br />
               <?php if(wpsc_purchlogs_has_tracking()) : ?>
                  <?php _e('Tracking ID:','wpsc'); ?> <?php echo wpsc_purchlogitem_trackid(); ?><br />
                  <?php _e('Shipping Status:','wpsc'); ?> <?php echo wpsc_purchlogitem_trackstatus(); ?><br />
                  <?php _e('Track History:','wpsc'); ?> <?php echo wpsc_purchlogitem_trackhistory(); ?>
               <?php endif; ?>
               </p>
            </div>
            <?php } ?>
            <div id='wpsc_billing_details_box'>
	           <?php do_action( 'wpsc_billing_details_top'); ?>
               <h3><?php _e('Billing Details','wpsc'); ?></h3>
               <p><strong><?php _e('Purchase Log Date:','wpsc'); ?> </strong><?php echo wpsc_purchaselog_details_date(); ?> </p>
               <p><strong><?php _e('Purchase Number:','wpsc'); ?> </strong><?php echo wpsc_purchaselog_details_purchnumber(); ?> </p>
               <p><strong><?php _e('Buyers Name:','wpsc'); ?> </strong><?php echo wpsc_display_purchlog_buyers_name(); ?></p>
               <p><strong><?php _e('Address:','wpsc'); ?> </strong><?php echo wpsc_display_purchlog_buyers_address(); ?></p>

               <p><strong><?php _e('Phone:','wpsc'); ?> </strong><?php echo wpsc_display_purchlog_buyers_phone(); ?></p>
               <p><strong><?php _e('Email:','wpsc'); ?> </strong><a href="mailto:<?php echo wpsc_display_purchlog_buyers_email(); ?>?subject=Message From '<?php echo get_option('siteurl'); ?>'"><?php echo wpsc_display_purchlog_buyers_email(); ?></a></p>
               <p><strong><?php _e('Payment Method:','wpsc'); ?> </strong><?php echo wpsc_display_purchlog_paymentmethod(); ?></p>
               <?php if(wpsc_display_purchlog_display_howtheyfoundus()) : ?>
               <p><strong><?php _e('How User Found Us:','wpsc'); ?> </strong><?php echo wpsc_display_purchlog_howtheyfoundus(); ?></p>
               <?php endif; ?>
               <?php do_action( 'wpsc_billing_details_bottom'); ?>
            </div>

            <div id='wpsc_items_ordered'>
               <br />
               <h3><?php _e('Items Ordered','wpsc'); ?></h3>
               <table class="widefat" cellspacing="0">
                  <thead>
                     <tr>
                  <?php print_column_headers('display-purchaselog-details'); ?>
                     </tr>
                  </thead>

                  <tbody>
                  <?php wpsc_display_purchlog_details(); ?>

                  <tr class="wpsc_purchaselog_start_totals">
                     <td colspan="5">
                        <?php if ( wpsc_purchlog_has_discount_data() ) { ?>
                        <?php _e('Coupon Code','wpsc'); ?>: <?php echo wpsc_display_purchlog_discount_data(); ?>
                        <?php } ?>
                     </td>
                     <th><?php _e('Discount','wpsc'); ?> </th>
                     <td><?php echo wpsc_display_purchlog_discount(); ?></td>
                  </tr>

                  <?php if(!wpec_display_product_tax()) { ?>
                     <tr>
                        <td colspan='5'></td>
                        <th><?php _e('Taxes','wpsc'); ?> </th>
                        <td><?php echo wpsc_display_purchlog_taxes(); ?></td>
                     </tr>
                  <?php } ?>

                  <tr>
                     <td colspan='5'></td>
                     <th><?php _e('Shipping','wpsc'); ?> </th>
                     <td><?php echo wpsc_display_purchlog_shipping(); ?></td>
                  </tr>
                  <tr>
                     <td colspan='5'></td>
                     <th><?php _e('Total','wpsc'); ?> </th>
                     <td><?php echo wpsc_display_purchlog_totalprice(); ?></td>
                  </tr>
                  </tbody>
            </table>
            <div id='wpsc_purchlog_order_status'>
               <form action='' method='post'>
               <p><label for='purchaselog-<?php echo absint( $_GET['purchaselog_id'] ); ?>'><?php _e('Order Status:','wpsc'); ?></label><select id='purchaselog-<?php echo absint( $_GET['purchaselog_id'] ); ?>' class='selector' name='<?php echo absint( $_GET['purchaselog_id'] ); ?>' title='<?php echo absint( $_GET['purchaselog_id'] ); ?>' >
            <?php while(wpsc_have_purch_items_statuses()) : wpsc_the_purch_status(); ?>
               <option value='<?php echo wpsc_the_purch_status_id(); ?>' <?php echo wpsc_purchlog_is_checked_status(); ?> ><?php echo wpsc_the_purch_status_name(); ?> </option>
            <?php endwhile; ?>
               </select></p>
               </form>
         </div>
		 <br style="clear: both;" />
            <?php wpsc_purchlogs_custom_fields(); ?>


            <!-- Start Order Notes (by Ben) -->
            <?php wpsc_purchlogs_notes(); ?>
            <!-- End Order Notes (by Ben) -->

            <?php wpsc_custom_checkout_fields(); ?>

            </div>
            </div>

            <div id='wpsc_purchlogitems_links'>
            <h3><?php _e('Actions','wpsc'); ?></h3>
            <?php do_action( 'wpsc_purchlogitem_links_start' ); ?>
            <?php if(wpsc_purchlogs_have_downloads_locked() != false): ?>
<img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/lock_open.png' alt='clear lock icon' />&ensp;<a href='<?php echo $_SERVER['REQUEST_URI'].'&amp;wpsc_admin_action=clear_locks'; ?>'><?php echo wpsc_purchlogs_have_downloads_locked(); ?></a><br /><br class='small' />
            <?php endif; ?>
<img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/printer.png' alt='printer icon' />&ensp;<a href='<?php echo add_query_arg('wpsc_admin_action','wpsc_display_invoice'); ?>'><?php _e('View Packing Slip', 'wpsc'); ?></a>

<br /><br class='small' /><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/email_go.png' alt='email icon' />&ensp;<a href='<?php echo add_query_arg( 'email_buyer_id', absint( $_GET['purchaselog_id'] ) ); ?>'><?php _e('Resend Receipt to Buyer', 'wpsc'); ?></a>

<br /><br class='small' /><a class='submitdelete' title='<?php echo esc_attr(__( 'Delete this log', 'wpsc' )); ?>' href='<?php echo wp_nonce_url("admin.php?wpsc_admin_action=delete_purchlog&amp;purchlog_id=" . absint ( $_GET['purchaselog_id'] ), 'delete_purchlog_' . absint( $_GET['purchaselog_id'] ) ); ?>' onclick="if ( confirm(' <?php echo esc_js(sprintf( __("You are about to delete this log '%s'\n 'Cancel' to stop, 'OK' to delete.",'wpsc'),  wpsc_purchaselog_details_date() )) ?>') ) { return true;}return false;"><img src='<?php echo WPSC_CORE_IMAGES_URL . "/cross.png"; ?>' alt='delete icon' />               &nbsp;<?php _e('Remove this record', 'wpsc') ?></a>

<br /><br class='small' />&emsp;&ensp;    <a href='<?php echo $page_back ?>'><?php _e('Go Back', 'wpsc'); ?></a>
<br /><br />
         </div>
         <br />
         <?php }  ?>
   </div>
   <?php

 }
    
 function wpsc_purchaselogs_displaylist(){
   global $purchlogs;
  ?>
   <form method='post' action=''>
     <div class='wpsc_purchaselogs_options'>
      <select id='purchlog_multiple_status_change' name='purchlog_multiple_status_change' class='purchlog_multiple_status_change'>
         <option selected='selected' value='-1'><?php _e('Bulk Actions', 'wpsc'); ?></option>
         <?php while(wpsc_have_purch_items_statuses()) : wpsc_the_purch_status(); ?>
            <option value='<?php echo wpsc_the_purch_status_id(); ?>' >
               <?php echo wpsc_the_purch_status_name(); ?>
            </option>
         <?php endwhile; ?>
         <option value="delete"><?php _e('Delete', 'wpsc'); ?></option>
      </select>
      <input type='hidden' value='purchlog_bulk_modify' name='wpsc_admin_action2' />
      <input type="submit" value="<?php _e('Apply', 'wpsc'); ?>" name="doaction" id="doaction" class="button-secondary action" />
      <?php /* View functions for purchlogs */?>
      <label for='view_purchlogs_by'><?php _e( 'View:', 'wpsc' ); ?></label>

      <select id='view_purchlogs_by' name='view_purchlogs_by'>
<?php
       $date_is_selected['3mnths'] = '';
       $date_is_selected['all'] = '';
		if( !isset($_GET['view_purchlogs_by']) )
			 $_GET['view_purchlogs_by'] = '';           
        switch($_GET['view_purchlogs_by']) {
               case 'all':
                  $date_is_selected['all'] = 'selected="selected"';
               break;
              
               default:
               case '3mnths':
               case '':
                  $date_is_selected['3mnths'] = 'selected="selected"';
               break;
        }

        ?>
         <option value='all' <?php echo $date_is_selected['all']; ?>><?php echo _x('All', 'all sales', 'wpsc'); ?></option>
            <option value='3mnths' <?php echo $date_is_selected['3mnths']; ?>><?php _e('Three Months', 'wpsc'); ?></option>
         <?php  echo wpsc_purchlogs_getfirstdates(); ?>
      </select>
      <select id='view_purchlogs_by_status' name='view_purchlogs_by_status'>
         <option value='-1'><?php _e('Status: All', 'wpsc'); ?></option>

         <?php while(wpsc_have_purch_items_statuses()) : wpsc_the_purch_status(); ?>

         <?php

                $current_status = wpsc_the_purch_status_id();
               $is_selected = '';
               if(isset($_GET['view_purchlogs_by_status']) && $_GET['view_purchlogs_by_status'] == $current_status) {
                  $is_selected = 'selected="selected"';
               }
         ?>
            <option value='<?php echo $current_status; ?>' <?php echo $is_selected; ?> >
               <?php echo wpsc_the_purch_status_name(); ?>
            </option>
         <?php endwhile; ?>
      </select>
      <input type='hidden' value='purchlog_filter_by' name='wpsc_admin_action' />
      <input type="submit" value="<?php _e('Filter', 'wpsc'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
   </div>
      <?php if( isset( $_POST['purchlogs_searchbox'] ) && wpsc_have_purch_items() == false ):  ?>
   <div class="updated settings-error"><p><?php _e('There are no purchase logs for your selection, please try again.', 'wpsc'); ?></p></div>
      <?php endif;?>
      <table class="widefat page fixed" cellspacing="0">
         <thead>
            <tr>
         <?php print_column_headers('display-sales-list'); ?>
            </tr>
         </thead>
         <tfoot>
            <tr>
         <?php print_column_headers('display-sales-list', false); ?>
            </tr>
         </tfoot>
         <tbody>
         <?php get_purchaselogs_content(); ?>
         </tbody>
      </table>
      <p><strong><?php _e('Total:', 'wpsc'); ?></strong> <?php echo wpsc_currency_display( wpsc_the_purch_total() ); ?></p>
<?php
         if(!isset($purchlogs->current_start_timestamp) && !isset($purchlogs->current_end_timestamp)){
            $purchlogs->current_start_timestamp = $purchlogs->earliest_timestamp;
            $purchlogs->current_end_timestamp = $purchlogs->current_timestamp;
         }
         $arr_params = array('wpsc_admin_action' => 'wpsc_downloadcsv',
                        'rss_key'         => 'key',
                         'start_timestamp'   => $purchlogs->current_start_timestamp,
                         'end_timestamp'  => $purchlogs->current_end_timestamp);
                         
         $piggy_url = 'http://www.bravenewcode.com/store/plugins/piggy/?utm_source=affiliate-6331&utm_medium=affiliates&utm_campaign=wpec#1';
      ?>
      <br />
      <p><a class='admin_download' href='<?php echo htmlentities(add_query_arg($arr_params), ENT_QUOTES, 'UTF-8') ; ?>' ><img class='wpsc_pushdown_img' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/download.gif' alt='' title='' /> <span> <?php _e('Download CSV', 'wpsc'); ?></span></a>
       <a target="_blank" class='admin_download' href='<?php echo htmlentities( $piggy_url, ENT_QUOTES, 'UTF-8') ; ?>'><img class='wpsc_pushdown_img' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/mobile.png' alt='' title='' /> <span> <?php _e('Mobile Sales App', 'wpsc'); ?></span></a></p>
   </form>
   <br />
   <script type="text/javascript">
   /* <![CDATA[ */
   (function($){
      $(document).ready(function(){
         $('#doaction, #doaction2').click(function(){
            if ( $('select[name^="purchlog_multiple_status_change"]').val() == 'delete' ) {
               var m = '<?php echo esc_js(__("You are about to delete the selected purchase logs.\n  'Cancel' to stop, 'OK' to delete.", "wpsc")); ?>';
               return showNotice.warn(m);
            }
         });
      });
   })(jQuery);
   //columns.init('edit');
   /* ]]> */
   </script>

<?php
 unset($_SESSION['newlogs']);
 }

 function get_purchaselogs_content(){
   while(wpsc_have_purch_items()) : wpsc_the_purch_item();
   ?>
   <tr>
      <th class="check-column" scope="row"><input type='checkbox' name='purchlogids[]' class='editcheckbox' value='<?php echo wpsc_the_purch_item_id(); ?>' /></th>
      <td><?php echo wpsc_the_purch_item_id(); ?></td><!-- purchase ID -->
      <td><?php echo wpsc_the_purch_item_date(); ?></td> <!--Date -->
      <td><?php echo wpsc_the_purch_item_name(); ?></td> <!--Name/email -->
      <td>
	<?php 
	    echo wpsc_currency_display( wpsc_the_purch_item_price() ); 
	    do_action( 'wpsc_additional_sales_amount_info', wpsc_purchaselog_details_id() );
	?>
      </td><!-- Amount -->
      <td><a href='<?php echo htmlentities(add_query_arg('purchaselog_id', wpsc_the_purch_item_id()), ENT_QUOTES, 'UTF-8') ; ?>'><?php
      $number_of_items = wpsc_the_purch_item_details();
      printf( _n( '%s Item', '%s Items', $number_of_items, 'wpsc' ), $number_of_items );
      ?></a></td><!-- Details -->
      <td>
      <?php if(!wpsc_purchlogs_is_google_checkout()){ ?>
		 <img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" class="ajax-loading" alt="" style="position:relative; top:3px;" />
         <select class='selector' name='<?php echo wpsc_the_purch_item_id(); ?>' title='<?php echo wpsc_the_purch_item_id(); ?>' >
         <?php while(wpsc_have_purch_items_statuses()) : wpsc_the_purch_status(); ?>
            <option value='<?php echo wpsc_the_purch_status_id(); ?>' <?php echo wpsc_is_checked_status(); ?> ><?php echo wpsc_the_purch_status_name(); ?> </option>
         <?php endwhile; ?>
         </select>
      <?php }else { ?>
         <a href='http://checkout.google.com/' rel=''><img class='google_checkout_logo' src='<?php echo WPSC_CORE_IMAGES_URL . "/checkout_logo.jpg"; ?>' alt='google checkout' /></a>
      <?php } ?>
      </td><!-- Status -->
      <td><a class='submitdelete' title='<?php echo esc_attr(__('Delete this log', 'wpsc')); ?>' href='<?php echo wp_nonce_url("admin.php?wpsc_admin_action=delete_purchlog&amp;purchlog_id=".wpsc_the_purch_item_id(), 'delete_purchlog_' . wpsc_the_purch_item_id()); ?>' onclick="if ( confirm(' <?php echo esc_js(sprintf( __("You are about to delete this log '%s'\n 'Cancel' to stop, 'OK' to delete.", 'wpsc'),  wpsc_the_purch_item_date() )) ?>') ) { return true;}return false;"><img class='wpsc_pushdown_img' src='<?php echo WPSC_CORE_IMAGES_URL . "/cross.png"; ?>' alt='delete icon' /></a></td><!-- Delete -->
      <td>
         <a class='wpsc_show_trackingid' title='<?php echo wpsc_the_purch_item_id(); ?>' href=''><?php echo wpsc_display_tracking_id(); ?></a>
      </td>
   </tr>
   <tr class='log<?php echo wpsc_the_purch_item_id(); ?> wpsc_trackingid_row'>
      <td class='wpsc_trackingid_row' colspan='2'>

         <label for='wpsc_trackingid<?php echo wpsc_the_purch_item_id(); ?>'><?php _e('Tracking ID','wpsc');?> :</label>
      </td>
      <td class='wpsc_trackingid_row' colspan='2'>
         <input type='text' name='wpsc_trackingid<?php echo wpsc_the_purch_item_id(); ?>' value='<?php echo wpsc_trackingid_value(); ?>' size='20' />
         <input type='submit' name='submit' class='button' value='Add Tracking ID' />
      </td>
      <td colspan='4'>
         <img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" class="ajax-loading" alt="" style="position:relative; top:3px;" />
         <a href='' title='<?php echo wpsc_the_purch_item_id(); ?>' class='sendTrackingEmail'><?php _e( 'Send Custom Message', 'wpsc' ); ?></a>
      </td>
   </tr>

   <?php
   endwhile;
 }
 function wpsc_purchaselogs_searchbox(){
   ?>
   <form  action='' method='post'>
      <input type='hidden' name='wpsc_admin_action' value='purchlogs_search' />
      <input type='text' value='<?php if(isset($_POST['purchlogs_searchbox'])) echo esc_attr( $_POST['purchlogs_searchbox'] ); ?>' name='purchlogs_searchbox' id='purchlogs_searchbox' />
      <input type="submit" value="<?php _e('Search Logs', 'wpsc'); ?>"  class="button-secondary action" />
   </form>
   <?php
 }

 function wpsc_display_purchlog_details(){
   while( wpsc_have_purchaselog_details() ) : wpsc_the_purchaselog_item(); ?>
   <tr>
      <td><?php echo wpsc_purchaselog_details_name(); ?></td> <!-- NAME! -->
      <td><?php echo wpsc_purchaselog_details_SKU(); ?></td> <!-- SKU! -->
      <td><?php echo wpsc_purchaselog_details_quantity(); ?></td> <!-- QUANTITY! -->
      <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_price() ); ?></td> <!-- PRICE! -->
      <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_shipping() ); ?></td> <!-- SHIPPING! -->
      <td><?php if(wpec_display_product_tax()) { echo wpsc_currency_display(wpsc_purchaselog_details_tax()); } ?></td> <!-- TAX! -->
      <!-- <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_discount() ); ?></td> --> <!-- DISCOUNT! -->
      <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_total() ); ?></td> <!-- TOTAL! -->
   </tr>
<?php
   endwhile;
}

function wpsc_purchlogs_custom_fields(){
   if(wpsc_purchlogs_has_customfields()){?>
   <div class='metabox-holder'>
      <div id='purchlogs_customfields' class='postbox'>
      <h3 class='hndle'><?php _e( 'Users Custom Fields' , 'wpsc' ); ?></h3>
      <div class='inside'>
      <?php $messages = wpsc_purchlogs_custommessages(); ?>
      <?php $files = wpsc_purchlogs_customfiles(); ?>
      <?php if(count($files) > 0){ ?>
      <h4><?php _e( 'Cart Items with Custom Files' , 'wpsc' ); ?>:</h4>
      <?php
         foreach($files as $file){
            echo "<p>".esc_html($file)."</p>";
         }
      }?>
      <?php if(count($messages) > 0){ ?>
      <h4><?php _e( 'Cart Items with Custom Messages' , 'wpsc' ); ?>:</h4>
      <?php
         foreach($messages as $message){
            echo "<p>".esc_html($message)."</p>";
         }
      } ?>
      </div>
      </div>
      </div>
<?php }

}


/* Start Order Notes (by Ben) */
function wpsc_purchlogs_notes() {

   if ( true ) { // Need to check if notes column exists in DB and plugin version? ?>
   <div class="metabox-holder">
      <div id="purchlogs_notes" class="postbox">
      <h3 class='hndle'><?php _e( 'Order Notes' , 'wpsc' ); ?></h3>
      <div class='inside'>
         <form method="post" action="">
            <input type='hidden' name='wpsc_admin_action' value='purchlogs_update_notes' />
            <input type="hidden" name="wpsc_purchlogs_update_notes_nonce" id="wpsc_purchlogs_update_notes_nonce" value="<?php echo wp_create_nonce( 'wpsc_purchlogs_update_notes' ); ?>" />
            <input type='hidden' name='purchlog_id' value='<?php echo absint( $_GET['purchaselog_id'] ); ?>' />
            <p><textarea name="purchlog_notes" rows="3" wrap="virtual" id="purchlog_notes" style="width:100%;"><?php if ( isset($_POST['purchlog_notes']) ) { echo stripslashes( esc_textarea( $_POST['purchlog_notes'] ) ); } else { echo wpsc_display_purchlog_notes(); } ?></textarea></p>
            <p><input class="button" type="submit" name="button" id="button" value="<?php _e( 'Update Notes', 'wpsc' ); ?>" /></p>
         </form>
      </div>
      </div>
   </div>
   <?php }

}
/* End Order Notes (by Ben) */
function wpsc_custom_checkout_fields(){
   global $purchlogitem;
   if(!empty($purchlogitem->customcheckoutfields)){
   ?>
      <div class="metabox-holder">
         <div id="custom_checkout_fields" class="postbox">
            <h3 class='hndle'><?php _e( 'Additional Checkout Fields' , 'wpsc' ); ?></h3>
            <div class='inside'>
            <?php
            foreach((array)$purchlogitem->customcheckoutfields as $key=>$value){
               $value['value'] = maybe_unserialize($value['value']);
               if(is_array($value['value'])){
                  ?>
                  <p><strong><?php echo $key; ?> :</strong> <?php echo implode(stripslashse($value['value']), ','); ?></p>
                  <?php


               }else{
                  ?>
                  <p><strong><?php echo $key; ?> :</strong> <?php echo stripslashes($value['value']); ?></p>
                  <?php
               }
            }
            ?>
            </div>
         </div>
      </div>
      <?php
   }
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

function wpsc_upgrade_purchase_logs() {
   include(WPSC_FILE_PATH.'/wpsc-admin/includes/purchlogs_upgrade.php');
}
?>
