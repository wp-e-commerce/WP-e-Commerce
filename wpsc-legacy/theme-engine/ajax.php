<?php

function wpsc_force_flush_theme_transients() {
	// Flush transients
	wpsc_flush_theme_transients( true );

	// Bounce back
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_flush_theme_transients'] ) && ( $_REQUEST['wpsc_flush_theme_transients'] == 'true' ) )
	add_action( 'admin_init', 'wpsc_force_flush_theme_transients' );

function wpsc_backup_theme() {
	$wp_theme_path = get_stylesheet_directory();
	wpsc_recursive_copy( $wp_theme_path, WPSC_THEME_BACKUP_DIR );
	$_SESSION['wpsc_themes_backup'] = true;
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'backup_themes' ) )
	add_action( 'admin_init', 'wpsc_backup_theme' );

/**
 * add_to_cart function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_add_to_cart() {
	global $wpsc_cart;
	/// default values
	$default_parameters['variation_values'] = null;
	$default_parameters['quantity'] = 1;
	$default_parameters['provided_price'] = null;
	$default_parameters['comment'] = null;
	$default_parameters['time_requested'] = null;
	$default_parameters['custom_message'] = null;
	$default_parameters['file_data'] = null;
	$default_parameters['is_customisable'] = false;
	$default_parameters['meta'] = null;

	$provided_parameters = array();

	/// sanitise submitted values
	$product_id = apply_filters( 'wpsc_add_to_cart_product_id', (int)$_POST['product_id'] );

	// compatibility with older themes
	if ( isset( $_POST['wpsc_quantity_update'] ) && is_array( $_POST['wpsc_quantity_update'] ) ) {
		$_POST['wpsc_quantity_update'] = $_POST['wpsc_quantity_update'][$product_id];
	}

	if( isset( $_POST['variation'] ) ){
		foreach ( (array)$_POST['variation'] as $key => $variation )
			$provided_parameters['variation_values'][(int)$key] = (int)$variation;

		if ( count( $provided_parameters['variation_values'] ) > 0 ) {
			$variation_product_id = wpsc_get_child_object_in_terms( $product_id, $provided_parameters['variation_values'], 'wpsc-variation' );
			if ( $variation_product_id > 0 )
				$product_id = $variation_product_id;
		}

	}

	if ( ( isset($_POST['quantity'] ) && $_POST['quantity'] > 0 ) && ( ! isset( $_POST['wpsc_quantity_update'] ) ) ) {
		$provided_parameters['quantity'] = (int)$_POST['quantity'];
	} else if ( isset( $_POST['wpsc_quantity_update'] ) ) {
		$wpsc_cart->remove_item( $_POST['key'] );
		$provided_parameters['quantity'] = (int)$_POST['wpsc_quantity_update'];
	}

	if (isset( $_POST['is_customisable']) &&  $_POST['is_customisable'] == 'true' ) {
		$provided_parameters['is_customisable'] = true;

		if ( isset( $_POST['custom_text'] ) ) {
			$provided_parameters['custom_message'] = $_POST['custom_text'];
		}
		if ( isset( $_FILES['custom_file'] ) ) {
			$provided_parameters['file_data'] = $_FILES['custom_file'];
		}
	}
	if ( isset($_POST['donation_price']) && ((float)$_POST['donation_price'] > 0 ) ) {
		$provided_parameters['provided_price'] = (float)$_POST['donation_price'];
	}
	$parameters = array_merge( $default_parameters, (array)$provided_parameters );

	$state = $wpsc_cart->set_item( $product_id, $parameters );

	$product = get_post( $product_id );

	if ( $state == true ) {
		$cart_messages[] = str_replace( "[product_name]", stripslashes( $product->post_title ), __( 'You just added "[product_name]" to your cart.', 'wpsc' ) );
	} else {
		if ( $parameters['quantity'] <= 0 ) {
			$cart_messages[] = __( 'Sorry, but you cannot add zero items to your cart', 'wpsc' );
		} else if ( $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] ) > 0 ) {
			$quantity = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] );
			$cart_messages[] = sprintf( _n( 'Sorry, but there is only %s of this item in stock.', 'Sorry, but there are only %s of this item in stock.', $quantity, 'wpsc' ), $quantity );
		} else {
			$cart_messages[] = sprintf( __( 'Sorry, but the item "%s" is out of stock.', 'wpsc' ), $product->post_title	);
		}
	}

	if ( isset( $_GET['ajax'] ) && $_GET['ajax'] == 'true' ) {
		if ( ( $product_id != null ) && ( get_option( 'fancy_notifications' ) == 1 ) ) {
			echo "if(jQuery('#fancy_notification_content')) {\n\r";
			echo "   jQuery('#fancy_notification_content').html(\"" . str_replace( array( "\n", "\r" ), array( '\n', '\r' ), addslashes( fancy_notification_content( $cart_messages ) ) ) . "\");\n\r";
			echo "   jQuery('#loading_animation').css('display', 'none');\n\r";
			echo "   jQuery('#fancy_notification_content').css('display', 'block');\n\r";
			echo "}\n\r";
			$error_messages = array( );
		}

		ob_start();

		include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );

		$output = ob_get_contents();
		ob_end_clean();
		$output = str_replace( array( "\n", "\r" ), array( "\\n", "\\r" ), addslashes( $output ) );
		echo "jQuery('div.shopping-cart-wrapper').html('$output');\n";


		if ( get_option( 'show_sliding_cart' ) == 1 ) {
			if ( ( wpsc_cart_item_count() > 0 ) || ( count( $cart_messages ) > 0 ) ) {
				$_SESSION['slider_state'] = 1;
				echo "
               jQuery('#sliding_cart').slideDown('fast',function(){
                  jQuery('#fancy_collapser').attr('src', ('".WPSC_CORE_IMAGES_URL."/minus.png'));
               });
         ";
			} else {
				$_SESSION['slider_state'] = 0;
				echo "
               jQuery('#sliding_cart').slideUp('fast',function(){
                  jQuery('#fancy_collapser').attr('src', ('".WPSC_CORE_IMAGES_URL."/plus.png'));
               });
         ";
			}
		}

		echo "jQuery('.cart_message').delay(3000).slideUp(500);";

		do_action( 'wpsc_alternate_cart_html', $cart_messages );
		exit();
	}
}

// execute on POST and GET
if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'add_to_cart') ) {
	add_action( 'init', 'wpsc_add_to_cart' );
}

function wpsc_get_cart() {
	global $wpsc_cart;

	ob_start();

	include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );
	$output = ob_get_contents();

	ob_end_clean();

	$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );
	echo "jQuery('div.shopping-cart-wrapper').html('$output');\n";


	if ( get_option( 'show_sliding_cart' ) == 1 ) {
		if ( (wpsc_cart_item_count() > 0) || (count( $cart_messages ) > 0) ) {
			$_SESSION['slider_state'] = 1;
			echo "
            jQuery('#sliding_cart').slideDown('fast',function(){
               jQuery('#fancy_collapser').attr('src', (WPSC_CORE_IMAGES_URL+'/minus.png'));
            });
      ";
		} else {
			$_SESSION['slider_state'] = 0;
			echo "
            jQuery('#sliding_cart').slideUp('fast',function(){
               jQuery('#fancy_collapser').attr('src', (WPSC_CORE_IMAGES_URL+'/plus.png'));
            });
      ";
		}
	}


	do_action( 'wpsc_alternate_cart_html', '' );
	exit();
}

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'get_cart') ) {
	add_action( 'init', 'wpsc_get_cart' );
}


/**
 * empty cart function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_empty_cart() {
	global $wpsc_cart;
	$wpsc_cart->empty_cart( false );

	if ( $_REQUEST['ajax'] == 'true' ) {
		ob_start();

		include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );
		$output = ob_get_contents();

		ob_end_clean();
		$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );
		echo "jQuery('div.shopping-cart-wrapper').html('$output');";
		do_action( 'wpsc_alternate_cart_html' );

		if ( get_option( 'show_sliding_cart' ) == 1 ) {
			$_SESSION['slider_state'] = 0;
			echo "
            jQuery('#sliding_cart').slideUp('fast',function(){
               jQuery('#fancy_collapser').attr('src', (WPSC_CORE_IMAGES_URL+'/plus.png'));
            });
      ";
		}
		exit();
	}

	// this if statement is needed, as this function also runs on returning from the gateway
	if ( $_REQUEST['wpsc_ajax_action'] == 'empty_cart' ) {
		wp_redirect( remove_query_arg( array( 'wpsc_ajax_action', 'ajax' ) ) );
		exit();
	}
}

// execute on POST and GET
if ( isset( $_REQUEST['wpsc_ajax_action'] ) && (($_REQUEST['wpsc_ajax_action'] == 'empty_cart') || (isset($_GET['sessionid'])  && ($_GET['sessionid'] > 0))) ) {
	add_action( 'init', 'wpsc_empty_cart' );
}


/**
 * update quantity function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_update_item_quantity() {
	global $wpsc_cart;

	if ( is_numeric( $_POST['key'] ) ) {
		$key = (int)$_POST['key'];
		if ( $_POST['quantity'] > 0 ) {
			// if the quantity is greater than 0, update the item;
			$parameters['quantity'] = (int)$_POST['quantity'];
			$wpsc_cart->edit_item( $key, $parameters );
		} else {
			// if the quantity is 0, remove the item.
			$wpsc_cart->remove_item( $key );
		}
		if ( isset( $_SESSION['coupon_numbers'] ) ) {
			wpsc_coupon_price( $_SESSION['coupon_numbers'] );
		}
	}

	if ( isset( $_REQUEST['ajax'] ) && $_REQUEST['ajax'] == 'true' ) {
		ob_start();

		include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );
		$output = ob_get_contents();

		ob_end_clean();

		$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );

		echo "jQuery('div.shopping-cart-wrapper').html('$output');\n";
		do_action( 'wpsc_alternate_cart_html' );


		exit();
	}
}

// execute on POST and GET
if ( isset( $_REQUEST['wpsc_update_quantity'] ) && ($_REQUEST['wpsc_update_quantity'] == 'true') ) {
	add_action( 'init', 'wpsc_update_item_quantity' );
}


/**
 * wpsc_change_tax function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_change_tax() {
	global $wpdb, $wpsc_cart;

	$form_id = absint( $_POST['form_id'] );

	$wpsc_selected_country = $wpsc_cart->selected_country;
	$wpsc_selected_region = $wpsc_cart->selected_region;

	$wpsc_delivery_country = $wpsc_cart->delivery_country;
	$wpsc_delivery_region = $wpsc_cart->delivery_region;


	$previous_country = $_SESSION['wpsc_selected_country'];
	if ( isset( $_POST['billing_country'] ) ) {
		$wpsc_selected_country = $_POST['billing_country'];
		$_SESSION['wpsc_selected_country'] = $wpsc_selected_country;
	}

	if ( isset( $_POST['billing_region'] ) ) {
		$wpsc_selected_region = absint( $_POST['billing_region'] );
		$_SESSION['wpsc_selected_region'] = $wpsc_selected_region;
	}

	$check_country_code = $wpdb->get_var( $wpdb->prepare( "SELECT `country`.`isocode` FROM `" . WPSC_TABLE_REGION_TAX . "` AS `region` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `region`.`country_id` = `country`.`id` WHERE `region`.`id` = %d LIMIT 1", $_SESSION['wpsc_selected_region'] ) );

	if ( $_SESSION['wpsc_selected_country'] != $check_country_code ) {
		$wpsc_selected_region = null;
	}

	if ( isset( $_POST['shipping_country'] ) ) {
		$wpsc_delivery_country = $_POST['shipping_country'];
		$_SESSION['wpsc_delivery_country'] = $wpsc_delivery_country;
	}
	if ( isset( $_POST['shipping_region'] ) ) {
		$wpsc_delivery_region = absint( $_POST['shipping_region'] );
		$_SESSION['wpsc_delivery_region'] = $wpsc_delivery_region;
	}

	$check_country_code = $wpdb->get_var( $wpdb->prepare( "SELECT `country`.`isocode` FROM `" . WPSC_TABLE_REGION_TAX . "` AS `region` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `region`.`country_id` = `country`.`id` WHERE `region`.`id` = %d LIMIT 1", $wpsc_delivery_region ) );

	if ( $wpsc_delivery_country != $check_country_code ) {
		$wpsc_delivery_region = null;
	}


	$wpsc_cart->update_location();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->get_shipping_option();
	if ( $wpsc_cart->selected_shipping_method != '' ) {
		$wpsc_cart->update_shipping( $wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option );
	}

	$tax = $wpsc_cart->calculate_total_tax();
	$total = wpsc_cart_total();
	$total_input = wpsc_cart_total(false);
	if($wpsc_cart->coupons_amount >= wpsc_cart_total(false) && !empty($wpsc_cart->coupons_amount)){
		$total = 0;
	}
	if ( $wpsc_cart->total_price < 0 ) {
		$wpsc_cart->coupons_amount += $wpsc_cart->total_price;
		$wpsc_cart->total_price = null;
		$wpsc_cart->calculate_total_price();
	}
	ob_start();

	include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );
	$output = ob_get_contents();

	ob_end_clean();

	$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );
	if ( get_option( 'lock_tax' ) == 1 ) {
		echo "jQuery('#current_country').val('" . $_SESSION['wpsc_delivery_country'] . "'); \n";
		if ( $_SESSION['wpsc_delivery_country'] == 'US' && get_option( 'lock_tax' ) == 1 ) {
			$output = wpsc_shipping_region_list( $_SESSION['wpsc_delivery_country'], $_SESSION['wpsc_delivery_region'] );
			$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );
			echo "jQuery('#region').remove();\n\r";
			echo "jQuery('#change_country').append(\"" . $output . "\");\n\r";
		}
	}


	foreach ( $wpsc_cart->cart_items as $key => $cart_item ) {
		echo "jQuery('#shipping_$key').html(\"" . wpsc_currency_display( $cart_item->shipping ) . "\");\n\r";
	}

	echo "jQuery('#checkout_shipping').html(\"" . wpsc_cart_shipping() . "\");\n\r";

	echo "jQuery('div.shopping-cart-wrapper').html('$output');\n";
	if ( get_option( 'lock_tax' ) == 1 ) {
		echo "jQuery('.shipping_country').val('" . $_SESSION['wpsc_delivery_country'] . "') \n";
		$sql = $wpdb->prepare( "SELECT `country` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `isocode`= '%s'", $_SESSION['wpsc_selected_country'] );
		$country_name = $wpdb->get_var( $sql );
		echo "jQuery('.shipping_country_name').html('" . $country_name . "') \n";
	}


	$form_selected_country = null;
	$form_selected_region = null;
	$onchange_function = null;

	if ( ! empty( $_POST['billing_country'] ) && $_POST['billing_country'] != 'undefined' && !isset( $_POST['shipping_country'] ) ) {
		$form_selected_country = $wpsc_selected_country;
		$form_selected_region = $wpsc_selected_region;
		$onchange_function = 'set_billing_country';
	} else if ( ! empty( $_POST['shipping_country'] ) && $_POST['shipping_country'] != 'undefined' && !isset( $_POST['billing_country'] ) ) {
		$form_selected_country = $wpsc_delivery_country;
		$form_selected_region = $wpsc_delivery_region;
		$onchange_function = 'set_shipping_country';
	}

	if ( ($form_selected_country != null) && ($onchange_function != null) ) {
		$region_list = $wpdb->get_results( $wpdb->prepare( "SELECT `" . WPSC_TABLE_REGION_TAX . "`.* FROM `" . WPSC_TABLE_REGION_TAX . "`, `" . WPSC_TABLE_CURRENCY_LIST . "`  WHERE `" . WPSC_TABLE_CURRENCY_LIST . "`.`isocode` IN('%s') AND `" . WPSC_TABLE_CURRENCY_LIST . "`.`id` = `" . WPSC_TABLE_REGION_TAX . "`.`country_id`", $form_selected_country ), ARRAY_A );
		if ( $region_list != null ) {
			$title = (empty($_POST['billing_country']))?'shippingstate':'billingstate';
			$output = "<select name='collected_data[" . $form_id . "][1]' class='current_region' onchange='$onchange_function(\"region_country_form_$form_id\", \"$form_id\");' title='" . $title . "'>\n\r";

			foreach ( $region_list as $region ) {
				if ( $form_selected_region == $region['id'] ) {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				$output .= "   <option value='" . $region['id'] . "' $selected>" . htmlspecialchars( $region['name'] ) . "</option>\n\r";
			}
			$output .= "</select>\n\r";

			$output = str_replace( Array( "\n", "\r" ), Array( "\\n", "\\r" ), addslashes( $output ) );
			echo "jQuery('#region_select_$form_id').html(\"" . $output . "\");\n\r";
			echo "
				var wpsc_checkout_table_selector = jQuery('#region_select_$form_id').parents('.wpsc_checkout_table').attr('class');
				wpsc_checkout_table_selector = wpsc_checkout_table_selector.replace(' ','.');
				wpsc_checkout_table_selector = '.'+wpsc_checkout_table_selector;
				jQuery(wpsc_checkout_table_selector + ' input.billing_region').attr('disabled', 'disabled');
				jQuery(wpsc_checkout_table_selector + ' input.shipping_region').attr('disabled', 'disabled');
				jQuery(wpsc_checkout_table_selector + ' .billing_region').parent().parent().hide();
				jQuery(wpsc_checkout_table_selector + ' .shipping_region').parent().parent().hide();
			";
		} else {
			if ( get_option( 'lock_tax' ) == 1 ) {
				echo "jQuery('#region').hide();";
			}
			echo "jQuery('#region_select_$form_id').html('');\n\r";
			echo "
				var wpsc_checkout_table_selector = jQuery('#region_select_$form_id').parents('.wpsc_checkout_table').attr('class');
				wpsc_checkout_table_selector = wpsc_checkout_table_selector.replace(' ','.');
				wpsc_checkout_table_selector = '.'+wpsc_checkout_table_selector;
				jQuery(wpsc_checkout_table_selector + ' input.billing_region').removeAttr('disabled');
				jQuery(wpsc_checkout_table_selector + ' input.shipping_region').removeAttr('disabled');
				jQuery(wpsc_checkout_table_selector + ' .billing_region').parent().parent().show();
				jQuery(wpsc_checkout_table_selector + ' .shipping_region').parent().parent().show();
			";
		}
	}





	if ( $tax > 0 ) {
		echo "jQuery(\"tr.total_tax\").show();\n\r";
	} else {
		echo "jQuery(\"tr.total_tax\").hide();\n\r";
	}
	echo "jQuery('#checkout_tax').html(\"<span class='pricedisplay'>" . wpsc_cart_tax() . "</span>\");\n\r";
	echo "jQuery('#checkout_total').html(\"{$total}<input id='shopping_cart_total_price' type='hidden' value='{$total_input}' />\");\n\r";
	exit();
}

// execute on POST and GET
if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'change_tax') ) {
	add_action( 'init', 'wpsc_change_tax' );
}

function wpsc_cart_html_page() {
	require_once(WPSC_FILE_PATH . "/wpsc-legacy/theme-engine/shopping_cart_container.php");
	exit();
}

// execute on POST and GET
if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == 'cart_html_page') ) {
	add_action( 'init', 'wpsc_cart_html_page', 110 );
}

/**
 * WP eCommerce AJAX and Init functions
 *
 * These are the WPSC AJAX and Init functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */
function wpsc_special_widget() {
	wpsc_add_to_cart();
}

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'special_widget' || $_REQUEST['wpsc_ajax_action'] == 'donations_widget') ) {
	add_action( 'init', 'wpsc_special_widget' );
}