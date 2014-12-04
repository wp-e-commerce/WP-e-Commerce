<?php

/**
 * WP eCommerce User Account class
 *
 * This class is responsible for theming the User Account page.
 *
 * @package wp-e-commerce
 * @since 3.8
 */
global $wpdb, $user_ID, $wpsc_purchlog_statuses, $separator;

if ( get_option( 'permalink_structure' ) != '' )
	$separator = "?";
else
	$separator = "&amp;";


/**
 * wpsc_display_form_fields()
 *
 * This function displays each of the form fields.
 *
 */
function wpsc_display_form_fields() {

	do_action( 'wpsc_start_display_user_log_form_fields' );

	$wpsc_checkout = wpsc_core_get_checkout();

	$i = 0;
	while ( wpsc_have_checkout_items() ) {
		wpsc_the_checkout_item();

		if ( wpsc_checkout_form_is_header() ) {
			$i ++;

			// display headers for form fields
			?>

            <?php if ( $i > 1 ) {?>
            	</table>
            	<table>
            <?php } ?>

               <tr class="checkout-heading-row <?php echo esc_attr( wpsc_the_checkout_item_error_class( false ) );?>">
	               <td <?php wpsc_the_checkout_details_class(); ?> colspan='2'>
	               		<h4><?php echo esc_html( wpsc_checkout_form_name() );?></h4>
	               </td>
               </tr>
               <?php if ( wpsc_is_shipping_details() ) {?>
               <tr class='same_as_shipping_row'>
		            <td colspan='2'>
		               	<?php
		               	$checked = '';
		               	$shipping_same_as_billing = wpsc_get_customer_meta( 'shippingSameBilling' );
		               	if ( $shipping_same_as_billing ) {
							$checked = 'checked="checked"';
						}
						?>
						<label for='shippingSameBilling'>
							<input type='checkbox'
								value='true'
								data-wpsc-meta-key="shippingSameBilling"
								class="wpsc-visitor-meta"
								name='shippingSameBilling'
								id='shippingSameBilling' <?php echo $checked; ?> />
							<?php _e( 'Same as billing address:', 'wpsc' ); ?>
						</label>
						<br>
						<span id="shippingsameasbillingmessage"><?php _e('Your orders will be shipped to the billing address', 'wpsc'); ?></span>
					</td>
			</tr>
			<?php }

			// Not a header so start display form fields
		} elseif ( $wpsc_checkout->checkout_item->unique_name == 'billingemail' ) {
			?>
               <?php

               	$email_markup = "<div class='wpsc_email_address'>
                  <p class='" . wpsc_checkout_form_element_id() . "'>
                     <label class='wpsc_email_address' for='" . wpsc_checkout_form_element_id() . "'>
                     " . __( 'Enter your email address', 'wpsc' ) . "
                     </label>
                  <p class='wpsc_email_address_p'>
                  <img src='https://secure.gravatar.com/avatar/empty?s=60&amp;d=mm' id='wpsc_checkout_gravatar' />
                  " . wpsc_checkout_form_field();

			if ( wpsc_the_checkout_item_error() != '' )
				$email_markup .= "<p class='validation-error'>" . wpsc_the_checkout_item_error() . "</p>";
			$email_markup .= "</div>";
		} else {
			?>
			<tr>
				<td class='<?php echo wpsc_checkout_form_element_id(); ?>'>
					<label
						for='<?php echo esc_attr( wpsc_checkout_form_element_id() ); ?>'>
		                <?php echo wpsc_checkout_form_name();?>
		            </label>
                </td>
                <td>
                  <?php echo wpsc_checkout_form_field();?>
                   <?php if ( wpsc_the_checkout_item_error() != '' ) { ?>
                          <p class='validation-error'><?php echo wpsc_the_checkout_item_error(); ?></p>
                  <?php } ?>
               </td>
            </tr>
         <?php }//endif; ?>
      <?php } // end while ?>
      <?php
}

function wpsc_has_downloads() {
	global $wpdb, $user_ID, $files, $links, $products;

	$purchases = $wpdb->get_results( "SELECT `id`, `processed` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE user_ID = " . ( int ) $user_ID . "" );
	$rowcount = count( $purchases );

	if ( $rowcount >= 1 ) {
		$perchidstr = "(";
		$perchids = array();
		foreach ( ( array ) $purchases as $purchase ) {
			$is_transaction = wpsc_check_purchase_processed( $purchase->processed );
			if( $is_transaction ) {
				$perchids[] = $purchase->id;
			}
		}
		if(!empty($perchids)){
			$perchidstr .= implode( ',', $perchids );
			$perchidstr .= ")";
			$sql = "SELECT * FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `purchid` IN " . $perchidstr . " AND `active` IN ('1') ORDER BY `datetime` DESC";
			$products = $wpdb->get_results( $sql, ARRAY_A );
			$products = apply_filters( 'wpsc_has_downloads_products', $products );
		}
	}

	foreach ( (array)$products as $key => $product ) {
	if( empty( $product['uniqueid'] ) ) { // if the uniqueid is not equal to null, its "valid", regardless of what it is
			$links[] = home_url( '/?downloadid=' . $product['id'] );
		} else {
			$links[] = home_url( '/?downloadid=' . $product['uniqueid'] );
 		}
		$sql = $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE id = %d", $product['fileid'] );
		$file = $wpdb->get_results( $sql, ARRAY_A );
		$files[] = $file[0];
	}
	if ( count( $files ) > 0 ) {
		return true;
	} else {
		return false;
	}
}

function wpsc_has_purchases() {

	global $wpdb, $user_ID, $wpsc_purchlog_statuses, $gateway_checkout_form_fields, $purchase_log, $col_count;

	/*
	 * this finds the earliest timedit-profile in the shopping cart and sorts out the timestamp system for the month by month display
	 */

	$earliest_record_sql = "SELECT MIN(`date`) AS `date` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date`!=''";
	$earliest_record = $wpdb->get_results( $earliest_record_sql, ARRAY_A );

	if ( $earliest_record[0]['date'] != null ) {
		$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1' AND `display_log` = '1';";
		$col_count = 4; //+ count( $form_data );
		$sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `user_ID` IN ('" . $user_ID . "') AND `processed` IN (3,4,5) ORDER BY `date` DESC";
		$purchase_log = $wpdb->get_results( $sql, ARRAY_A );

		return true;
	} else {

		return false;
	}
}

function wpsc_has_purchases_this_month() {
	global $wpdb, $user_ID, $wpsc_purchlog_statuses, $gateway_checkout_form_fields, $purchase_log, $col_count;

	$i = 0;
	$subtotal = 0;

	if ( $purchase_log != null )
		return true;
	else
		return false;
}

/**
 * Displays the Account Page tabs
 *
 * @access public
 * @since 3.8.10
 *
 */
function wpsc_user_profile_links( $args = array() ) {
	global $current_tab, $separator;

	$defaults = array (
 		'before_link_list' => '',
 		'after_link_list'  => '',
 		'before_link_item' => '',
 		'after_link_item'  => '',
 		'link_separator'   => '|'
	);

	$args = wp_parse_args( $args, $defaults );

	$profile_tabs = apply_filters( 'wpsc_user_profile_tabs', array(
		'purchase_history' => __( 'Purchase History', 'wpsc' ),
		'edit_profile'     => __( 'Your Details', 'wpsc' ),
		'downloads'        => __( 'Your Downloads', 'wpsc' )
	) );

	echo $args['before_link_list'];

	$i = 0;
	$user_account_url = get_option( 'user_account_url' );
	$links = array();
	foreach ( $profile_tabs as $tab_id => $tab_title ) :
		$tab_link = $args['before_link_item'];
		$tab_url = add_query_arg( 'tab', $tab_id, $user_account_url );
		$tab_link = sprintf(
			'<a href="%1$s" class="%2$s">%3$s</a>',
			esc_url( $tab_url ),
			esc_attr( $current_tab == $tab_id ? 'current' : '' ),
			$tab_title
		);
		$tab_link .= $args['after_link_item'];
		$links[] = $tab_link;
	endforeach;

	echo implode( $args['link_separator'], $links );

	echo $args['after_link_list'];
}

function wpsc_user_purchases() {
	global $wpdb, $user_ID, $wpsc_purchlog_statuses, $gateway_checkout_form_fields, $purchase_log, $col_count, $nzshpcrt_gateways;

	$i = 0;
	$subtotal = 0;

	do_action( 'wpsc_pre_purchase_logs' );

	foreach ( (array) $purchase_log as $purchase ) {
		$status_state = "expand";
		$status_style = "display:none;";
		$alternate = "";
		$i++;

		if ( ($i % 2) != 0 )
			$alternate = "alt";

		echo "<tr class='$alternate'>\n\r";
		echo " <td class='status processed'>";
		echo "<a href=\"#\" onclick=\"return show_details_box('status_box_" . $purchase['id'] . "','log_expander_icon_" . $purchase['id'] . "');\">";

		if ( !empty($_GET['id']) && $_GET['id'] == $purchase['id'] ) {
			$status_state = "collapse";
			$status_style = "style='display: block;'";
		}

		echo "<img class=\"log_expander_icon\" id=\"log_expander_icon_" . $purchase['id'] . "\" src=\"" . WPSC_CORE_IMAGES_URL . "/icon_window_$status_state.gif\" alt=\"\" title=\"\" />";

		echo "<span id='form_group_" . $purchase['id'] . "_text'>" . __( 'Details', 'wpsc' ) . "</span>";
		echo "</a>";
		echo " </td>\n\r";

		echo " <td class='date'>";
		echo date( "jS M Y", $purchase['date'] );
		echo " </td>\n\r";

		echo " <td class='price'>";
		$country = get_option( 'country_form_field' );
		if ( $purchase['shipping_country'] != '' ) {
			$billing_country = $purchase['billing_country'];
			$shipping_country = $purchase['shipping_country'];
		} elseif ( !empty($country)) {
			$country_sql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $purchase['id'] ,get_option( 'country_form_field' ) );
			$country_data = $wpdb->get_results( $country_sql, ARRAY_A );
			$billing_country = $country_data[0]['value'];
			$shipping_country = $country_data[0]['value'];
		}
		echo wpsc_currency_display( $purchase['totalprice'], array('display_as_html' => false) );
		$subtotal += $purchase['totalprice'];
		echo " </td>\n\r";


		if ( get_option( 'payment_method' ) == 2 ) {
			echo " <td class='payment_method'>";
			$gateway_name = '';
			foreach ( (array)$nzshpcrt_gateways as $gateway ) {
				if ( $purchase['gateway'] != 'testmode' ) {
					if ( $gateway['internalname'] == $purchase['gateway'] ) {
						$gateway_name = $gateway['name'];
					}
				} else {
					$gateway_name = __( "Manual Payment", 'wpsc' );
				}
			}
			echo $gateway_name;
			echo " </td>\n\r";
		}

		echo "</tr>\n\r";
		echo "<tr>\n\r";
		echo " <td colspan='$col_count' class='details'>\n\r";
		echo "  <div id='status_box_" . $purchase['id'] . "' class='order_status' style=\"$status_style\">\n\r";
		echo "  <div>\n\r";

		//order status code lies here
		//check what $purchase['processed'] reflects in the $wpsc_purchlog_statuses array
		$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );
		echo "  <strong class='form_group'>" . __( 'Order Status', 'wpsc' ) . ":</strong>\n\r";
		echo $status_name . "<br /><br />";

                do_action( 'wpsc_user_log_after_order_status', $purchase );

		//written by allen
		$usps_id = get_option( 'usps_user_id' );
		if ( $usps_id != null ) {
			$XML1 = "<TrackFieldRequest USERID=\"$usps_id\"><TrackID ID=\"" . $purchase['track_id'] . "\"></TrackID></TrackFieldRequest>";
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "http://secure.shippingapis.com/ShippingAPITest.dll?" );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			$postdata = "API=TrackV2&XML=" . $XML1;
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $postdata );

			$parser = new xml2array;
			$parsed = $parser->parse( $result );
			$parsed = $parsed[0]['children'][0]['children'];
			if ( $purchase['track_id'] != null ) {
				echo "<br /><br />";
				echo " <strong class='form_group'>" . __( 'Shipping Address', 'wpsc' ) . "</strong>\n\r";
				echo "<table>";
				foreach ( (array)$parsed as $parse ) {
					if ( $parse['name'] == "TRACKSUMMARY" )
						foreach ( (array)$parse['children'] as $attrs ) {
							if ( $attrs['name'] != "EVENT" )
								$attrs['name'] = str_replace( "EVENT", "", $attrs['name'] );
							$bar = ucfirst( strtolower( $attrs['name'] ) );
							echo "<tr><td>" . $bar . "</td><td>" . $attrs['tagData'] . "</td></tr>";
						}
				}
				echo "</table>";
			}
			echo "<br /><br />";
		}
		//end of written by allen
		//cart contents display starts here;
		echo "  <strong class='form_group'>" . __( 'Order Details', 'wpsc' ) . ":</strong>\n\r";
		$cartsql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`= %d", $purchase['id'] );
		$cart_log = $wpdb->get_results( $cartsql, ARRAY_A );
		$j = 0;
		// /*
		if ( $cart_log != null ) {
			echo "<table class='logdisplay'>";
			echo "<tr class='toprow2'>";

			echo " <th class='details_name'>";
			_e( 'Name', 'wpsc' );
			echo " </th>";

			echo " <th class='details_quantity'>";
			_e( 'Quantity', 'wpsc' );
			echo " </th>";

			echo " <th class='details_price'>";
			_e( 'Price', 'wpsc' );
			echo " </th>";

			echo " <th class='details_tax'>";
			_e( 'GST', 'wpsc' );
			echo " </th>";

			echo " <th class='details_shipping'>";
			_e( 'Shipping', 'wpsc' );
			echo " </th>";

			echo " <th class='details_total'>";
			_e( 'Total', 'wpsc' );
			echo " </th>";

			echo "</tr>";

			$gsttotal = false;
			$endtotal = $total_shipping = 0;
			foreach ( (array)$cart_log as $cart_row ) {
				$alternate = "";
				$j++;

				if ( ($j % 2) != 0 )
					$alternate = "alt";

				$variation_list = '';

				$billing_country = !empty($country_data[0]['value']) ? $country_data[0]['value'] : '';
				$shipping_country = !empty($country_data[0]['value']) ? $country_data[0]['value'] : '';

				$shipping = $cart_row['pnp'];
				$total_shipping += $shipping;
				echo "<tr class='$alternate'>";

				echo " <td class='details_name'>";
				echo apply_filters( 'the_title', $cart_row['name'] );
				echo $variation_list;
				echo " </td>";

				echo " <td class='details_quantity'>";
				echo $cart_row['quantity'];
				echo " </td>";

				echo " <td class='details_price'>";
				$price = $cart_row['price'] * $cart_row['quantity'];
				echo wpsc_currency_display( $price );
				echo " </td>";

				echo " <td class='details_tax'>";
				$gst = $cart_row['tax_charged'];
				if( $gst > 0)
					$gsttotal += $gst;
				echo wpsc_currency_display( $gst , array('display_as_html' => false) );
				echo " </td>";

				echo " <td class='details_shipping'>";
				echo wpsc_currency_display( $shipping , array('display_as_html' => false) );
				echo " </td>";

				echo " <td class='details_total'>";
				$endtotal += $price;
				echo wpsc_currency_display( ( $shipping + $price ), array('display_as_html' => false)  );
				echo " </td>";

				echo '</tr>';
			}
			echo "<tr>";

			echo " <td>";
			echo " </td>";

			echo " <td>";
			echo " </td>";

			echo " <td>";
			echo " <td>";
			echo " </td>";
			echo " </td>";

			echo " <td class='details_totals_labels'>";
			echo "<strong>" . __( 'Total Shipping', 'wpsc' ) . ":</strong><br />";
			echo "<strong>" . __( 'Total Tax', 'wpsc' ) . ":</strong><br />";
			echo "<strong>" . __( 'Final Total', 'wpsc' ) . ":</strong>";
			echo " </td>";

			echo " <td class='details_totals_labels'>";
			$total_shipping += $purchase['base_shipping'];
			$endtotal += $total_shipping;
			$endtotal += $purchase['wpec_taxes_total'];
			echo wpsc_currency_display( $total_shipping, array('display_as_html' => false)  ) . "<br />";
			if ( $gsttotal ){ //if false then must be exclusive.. doesnt seem too reliable needs more testing
				echo wpsc_currency_display( $gsttotal , array('display_as_html' => false) ). "<br />";
			} else {
				echo wpsc_currency_display( $purchase['wpec_taxes_total'] , array('display_as_html' => false) ). "<br />";
			}
			echo wpsc_currency_display( $endtotal , array('display_as_html' => false) );
			echo " </td>";

			echo '</tr>';

			echo "</table>";
			echo "<br />";

			echo "<strong>" . __( 'Customer Details', 'wpsc' ) . ":</strong>";
			echo "<table class='customer_details'>";


			$usersql = $wpdb->prepare( "SELECT `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.value, `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN `".WPSC_TABLE_SUBMITTED_FORM_DATA."` ON `".WPSC_TABLE_CHECKOUT_FORMS."`.id = `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.`form_id` WHERE `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.log_id = %d OR `".WPSC_TABLE_CHECKOUT_FORMS."`.type = 'heading' ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_set`, `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`", $purchase['id'] );
			$formfields = $wpdb->get_results($usersql, ARRAY_A);
			if ( !empty($formfields) ) {

				foreach ( (array)$formfields as $form_field ) {
					// If its a heading display the Name otherwise continue on
					if( 'heading' == $form_field['type'] ){
						echo "  <tr><td colspan='2'>" . esc_html( $form_field['name'] ) . ":</td></tr>";
						continue;
					}

					switch ($form_field['unique_name']){
						case 'shippingcountry':
						case 'billingcountry':
						$country = maybe_unserialize($form_field['value']);
							 if(is_array($country))
							 	$country = $country[0];
							 else
							 	$country = $form_field['value'];

							 echo "  <tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>" . esc_html( $country ) . "</td></tr>";
							break;

						case 'billingstate':
						case 'shippingstate':
							if(is_numeric($form_field['value']))
								$state = wpsc_get_state_by_id($form_field['value'],'name');
 	   						else
 	            				$state = $form_field['value'];

 	            			 echo "  <tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>" . esc_html( $state ) . "</td></tr>";
							break;

						default:
							echo "  <tr><td>" . esc_html( $form_field['name'] ) . ":</td><td>" . esc_html( $form_field['value'] ) . "</td></tr>";

					}
				}
			}

			$payment_gateway_names = '';
			$payment_gateway_names = get_option('payment_gateway_names');

			foreach ( (array)$payment_gateway_names as $gatewayname ) {
				//if the gateway has a custom name
				if (!empty ($gatewayname) )
					$display_name = $payment_gateway_names[$purchase_log[0]['gateway']];
				else{
				//if not fall back on default name
					foreach ( (array)$nzshpcrt_gateways as $gateway ){
						if ( $gateway['internalname'] == $purchase['gateway'])
							$display_name = $gateway['name'];
					}
				}
			}

			echo "  <tr><td>" . __( 'Payment Method', 'wpsc' ) . ":</td><td>" . $display_name . "</td></tr>";
			echo "  <tr><td>" . __( 'Purchase #', 'wpsc' ) . ":</td><td>" . $purchase['id'] . "</td></tr>";
			if ( $purchase['transactid'] != '' ) {
				echo "  <tr><td>" . __( 'Transaction Id', 'wpsc' ) . ":</td><td>" . $purchase['transactid'] . "</td></tr>";
			}
			echo "</table>";
		}
		echo "  </div>\n\r";
		echo "  </div>\n\r";
		echo " </td>\n\r";
		echo "</tr>\n\r";
	}
}


/**
 * Displays the Purchase History template
 *
 * @access private
 * @since 3.8.10
 *
 */
function _wpsc_action_purchase_history_section() {
	include( wpsc_get_template_file_path( 'wpsc-account-purchase-history.php' ) );
}
add_action( 'wpsc_user_profile_section_purchase_history', '_wpsc_action_purchase_history_section' );

/**
 * Displays the Edit Profile template
 *
 * @access private
 * @since 3.8.10
 *
 */
function _wpsc_action_edit_profile_section() {
	include( wpsc_get_template_file_path( 'wpsc-account-edit-profile.php' ) );
}
add_action( 'wpsc_user_profile_section_edit_profile', '_wpsc_action_edit_profile_section' );

/**
 * Displays the Downloads template
 *
 * @access private
 * @since 3.8.10
 *
 */
function _wpsc_action_downloads_section() {
	global $files, $products;

	$items = array();
	if ( wpsc_has_downloads() && ! empty( $files ) ) {
		foreach ( $files as $key => $file ) {
			$item = array();
			if ( $products[$key]['downloads'] > 0 ) {
				$url = add_query_arg(
					'downloadid',
					$products[$key]['uniqueid'],
					home_url()
				);
				$item['title'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $url ),
					esc_html( $file['post_title'] )
				);
			} else {
				$item['title'] = esc_html( $file['post_title'] );
			}

			$item['downloads'] = $products[$key]['downloads'];
			$item['datetime'] = date( get_option( 'date_format' ), strtotime( $products[$key]['datetime'] ) );
			$items[] = (object) $item;
		}
	}

	include( wpsc_get_template_file_path( 'wpsc-account-downloads.php' ) );
}
add_action( 'wpsc_user_profile_section_downloads', '_wpsc_action_downloads_section' );

?>
