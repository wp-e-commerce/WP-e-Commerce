<?php
add_action( 'wp_ajax_wpsc_shipping_same_as_billing', 'wpsc_shipping_same_as_billing' );
add_action( 'wp_ajax_shipping_same_as_billing_update', 'wpsc_update_shipping_quotes_on_shipping_same_as_billing' );
add_action( 'wp_ajax_nopriv_shipping_same_as_billing_update', 'wpsc_update_shipping_quotes_on_shipping_same_as_billing' );

if ( isset( $_GET['termsandconds'] ) && 'true' == $_GET['termsandconds'] )
	add_action( 'init', 'wpsc_show_terms_and_conditions' );

if ( isset( $_REQUEST['submitwpcheckout_profile'] ) ) {
	add_action( 'init', 'wpsc_save_user_profile', 10, 0 );
}

if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == 'submit_checkout') ) {
	add_action( 'init', 'wpsc_submit_checkout', 10, 0 );
}

if ( isset( $_REQUEST['wpsc_action'] ) && ($_REQUEST['wpsc_action'] == 'cart_html_page') )
	add_action( 'init', 'wpsc_cart_html_page', 110 );

if ( get_option( 'wpsc_also_bought' ) == 1 )
	add_action( 'wpsc_submit_checkout', 'wpsc_populate_also_bought_list' );

if ( isset( $_REQUEST['get_rating_count'] ) && ($_REQUEST['get_rating_count'] == 'true') && is_numeric( $_POST['product_id'] ) )
	add_action( 'init', 'wpsc_get_rating_count' );

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'special_widget' || $_REQUEST['wpsc_ajax_action'] == 'donations_widget') )
	add_action( 'init', 'wpsc_special_widget' );

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && (($_REQUEST['wpsc_ajax_action'] == 'empty_cart') || (isset($_GET['sessionid'])  && ($_GET['sessionid'] > 0))) )
	add_action( 'init', 'wpsc_empty_cart' );

if ( isset( $_POST['coupon_num'] ) )
	add_action( 'init', 'wpsc_coupon_price' );

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && 'add_to_cart' == $_REQUEST['wpsc_ajax_action'] )
    add_action( 'init', 'wpsc_add_to_cart' );

if ( isset( $_REQUEST['wpsc_update_quantity'] ) && ($_REQUEST['wpsc_update_quantity'] == 'true') )
	add_action( 'init', 'wpsc_update_item_quantity' );

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && ($_REQUEST['wpsc_ajax_action'] == 'rate_product') )
	add_action( 'init', 'wpsc_update_product_rating' );

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && 'update_location' == $_REQUEST['wpsc_ajax_action'] ) {
	add_action( 'init', 'wpsc_update_location' );
}

if ( isset( $_REQUEST['wpsc_ajax_action'] ) && 'update_shipping_price' == $_REQUEST['wpsc_ajax_action'] ) {
    add_action( 'init', 'wpsc_update_shipping_price' );
}

if ( isset( $_REQUEST['update_product_price'] ) && 'true' == $_REQUEST['update_product_price'] && ! empty( $_POST['product_id'] ) && is_numeric( $_POST['product_id'] ) ) {
    add_action( 'init', 'wpsc_update_product_price' );
}

add_action( 'wp_ajax_add_to_cart'       , 'wpsc_add_to_cart' );
add_action( 'wp_ajax_nopriv_add_to_cart', 'wpsc_add_to_cart' );
add_action( 'wp_ajax_get_cart'       , 'wpsc_get_cart' );
add_action( 'wp_ajax_nopriv_get_cart', 'wpsc_get_cart' );
add_action( 'wp_ajax_update_shipping_price'       , 'wpsc_update_shipping_price' );
add_action( 'wp_ajax_nopriv_update_shipping_price', 'wpsc_update_shipping_price' );
add_action( 'wp_ajax_update_product_price'       , 'wpsc_update_product_price' );
add_action( 'wp_ajax_nopriv_update_product_price', 'wpsc_update_product_price' );
add_action( 'wp_ajax_update_location'       , 'wpsc_update_location' );
add_action( 'wp_ajax_nopriv_update_location', 'wpsc_update_location' );
add_action( 'wp_ajax_change_tax'       , 'wpsc_change_tax' );
add_action( 'wp_ajax_nopriv_change_tax', 'wpsc_change_tax' );
add_action( 'wp_ajax_change_profile_country'       , '_wpsc_change_profile_country' );
add_action( 'wp_ajax_nopriv_change_profile_country', '_wpsc_change_profile_country' );


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

/**
 * add_to_cart function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 *
 * @uses wpsc_get_product_id_from_variations()              Given array of variation selections returns the variation product id as int
 */
function wpsc_add_to_cart() {
	global $wpsc_cart;

	$default_parameters = $cart_messages = $provided_parameters = array();

	/// default values
	$default_parameters['variation_values'] = null;
	$default_parameters['quantity'] = 1;
	$default_parameters['provided_price'] = null;
	$default_parameters['comment'] = null;
	$default_parameters['time_requested'] = null;
	$default_parameters['custom_message'] = '';
	$default_parameters['file_data'] = null;
	$default_parameters['is_customisable'] = false;
	$default_parameters['meta'] = null;

	$post_type_object = get_post_type_object( 'wpsc-product' );
	$permitted_post_statuses = current_user_can( $post_type_object->cap->edit_posts ) ? apply_filters( 'wpsc_product_display_status', array( 'publish' ) ) : array( 'publish' );

	/// sanitise submitted values
	$product_id = apply_filters( 'wpsc_add_to_cart_product_id'    , (int) $_REQUEST['product_id'] );
	$product    = apply_filters( 'wpsc_add_to_cart_product_object', get_post( $product_id, OBJECT, 'display' ) );

	if ( ! in_array( $product->post_status, $permitted_post_statuses ) || 'wpsc-product' != $product->post_type ) {
		return false;
	}

	// compatibility with older themes
	if ( isset( $_REQUEST['wpsc_quantity_update'] ) && is_array( $_REQUEST['wpsc_quantity_update'] ) ) {
		$_REQUEST['wpsc_quantity_update'] = $_REQUEST['wpsc_quantity_update'][$product_id];
	}

	if ( isset( $_REQUEST['variation'] ) ) {
		$return_variation_params                 = wpsc_get_product_data_from_variations( $_REQUEST['variation'], $product_id );
		$product_id                              = $return_variation_params['product_id'];
		$provided_parameters['variation_values'] = $return_variation_params['variation_values'];
	}

	if ( (isset( $_REQUEST['quantity'] ) && $_REQUEST['quantity'] > 0) && (!isset( $_REQUEST['wpsc_quantity_update'] )) ) {
		$provided_parameters['quantity'] = (int) $_REQUEST['quantity'];
	} else if ( isset( $_REQUEST['wpsc_quantity_update'] ) ) {
		$wpsc_cart->remove_item( $_REQUEST['key'] );
		$provided_parameters['quantity'] = (int) $_REQUEST['wpsc_quantity_update'];
	}

	if ( isset( $_REQUEST['is_customisable'] ) &&
		'true' == $_REQUEST['is_customisable'] ) {
		$provided_parameters['is_customisable'] = true;

		if ( isset( $_REQUEST['custom_text'] ) ) {
			$provided_parameters['custom_message'] = stripslashes( $_REQUEST['custom_text'] );
		}
		if ( isset( $_FILES['custom_file'] ) ) {
			$provided_parameters['file_data'] = $_FILES['custom_file'];
		}
	}

	if ( isset( $_REQUEST['donation_price'] ) && ( (float) $_REQUEST['donation_price'] > 0 ) ) {
		$provided_parameters['provided_price'] = (float) $_REQUEST['donation_price'];
	}

	$parameters = array_merge( $default_parameters, (array) $provided_parameters );

	$cart_item = $wpsc_cart->set_item( $product_id, $parameters );

	if ( is_object( $cart_item ) ) {

		do_action( 'wpsc_add_to_cart', $product, $cart_item );
		$cart_messages[] = str_replace( "[product_name]", $cart_item->get_title(), __( 'You just added "[product_name]" to your cart.', 'wp-e-commerce' ) );

	} else {
		if ( $parameters['quantity'] <= 0 ) {

			$cart_messages[] = __( 'Sorry, but you cannot add zero items to your cart', 'wp-e-commerce' );

		} else if ( wpsc_product_has_variations( $product_id ) && is_null( $parameters['variation_values'] ) ) {

			$cart_messages[] = apply_filters( 'wpsc_add_to_cart_variation_missing_message', sprintf( __( 'This product has several options to choose from.<br /><br /><a href="%s" style="display:inline; float:none; margin: 0; padding: 0;">Visit the product page</a> to select options.', 'wp-e-commerce' ), esc_url( get_permalink( $product_id ) ) ), $product_id );

		} else if ( $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] ) > 0 ) {

			$quantity        = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] );
			$cart_messages[] = sprintf( _n( 'Sorry, but there is only %s of this item in stock.', 'Sorry, but there are only %s of this item in stock.', $quantity, 'wp-e-commerce' ), $quantity );

		} else {

			$cart_messages[] = apply_filters( 'wpsc_add_to_cart_out_of_stock_message', __( 'Sorry, but this item is out of stock.', 'wp-e-commerce' ), $product_id );

		}
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$json_response = array( 'cart_messages' => $cart_messages, 'product_id' => $product_id, 'cart_total' => wpsc_cart_total() );

		$output = _wpsc_ajax_get_cart( false, $cart_messages );

		$json_response = apply_filters( 'wpsc_add_to_cart_json_response', $json_response + $output );

		die( json_encode( $json_response ) );
	}
}

function wpsc_get_cart() {
	_wpsc_ajax_get_cart();
}

/* 19-02-09
 * add cart button function used for php template tags and shortcodes
 */

function wpsc_add_to_cart_button( $product_id, $return = false ) {
	global $wpdb,$wpsc_variations;
	$output = '';
	if ( $product_id > 0 ) {
		// grab the variation form fields here
		$wpsc_variations = new wpsc_variations( $product_id );
		if ( $return )
			ob_start();
		?>
			<div class='wpsc-add-to-cart-button'>
				<form class='wpsc-add-to-cart-button-form' id='product_<?php echo esc_attr( $product_id ) ?>' action='' method='post'>
					<?php do_action( 'wpsc_add_to_cart_button_form_begin', $product_id ); ?>
					<div class='wpsc_variation_forms'>
						<?php while ( wpsc_have_variation_groups() ) : wpsc_the_variation_group(); ?>
							<p>
								<label for='<?php echo wpsc_vargrp_form_id(); ?>'><?php echo esc_html( wpsc_the_vargrp_name() ) ?>:</label>
								<select class='wpsc_select_variation' name='variation[<?php echo wpsc_vargrp_id(); ?>]' id='<?php echo wpsc_vargrp_form_id(); ?>'>
									<?php while ( wpsc_have_variations() ): wpsc_the_variation(); ?>
										<option value='<?php echo wpsc_the_variation_id(); ?>' <?php echo wpsc_the_variation_out_of_stock(); ?>><?php echo esc_html( wpsc_the_variation_name() ); ?></option>
									<?php endwhile; ?>
								</select>
							</p>
						<?php endwhile; ?>
					</div>
					<input type='hidden' name='wpsc_ajax_action' value='add_to_cart' />
					<input type='hidden' name='product_id' value='<?php echo $product_id; ?>' />
					<input type='submit' id='product_<?php echo $product_id; ?>_submit_button' class='wpsc_buy_button' name='Buy' value='<?php echo __( 'Add To Cart', 'wp-e-commerce' ); ?>'  />
					<?php do_action( 'wpsc_add_to_cart_button_form_end', $product_id ); ?>
				</form>
			</div>
		<?php

		if ( $return ) {
			return ob_get_clean();
		}
	}
}

/**
 * Add to cart shortcode function used for shortcodes calls the function in
 * product_display_functions.php
 *
 * @since  19-02-2009
 *
 * Note: Really old legacy shortcode support for add to cart buttons.
 * This isn't a proper WordPress shortcode!
 */
function add_to_cart_shortcode( $content = '' ) {
	if ( ! in_the_loop() )
		return $content;

	if ( preg_match_all( "/\[add_to_cart=([\d]+)\]/", $content, $matches ) ) {
		foreach ( $matches[1] as $key => $product_id ) {
			$original_string = $matches[0][$key];
			$output = wpsc_add_to_cart_button( $product_id, true );
			$content = str_replace( $original_string, $output, $content );
		}
	}
	return $content;
}

/**
 * empty cart function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_empty_cart() {
	global $wpsc_cart;
	$wpsc_cart->empty_cart( false );

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$output = apply_filters( 'wpsc_empty_cart_response', _wpsc_ajax_get_cart( false ) );
		die( json_encode( $output ) );
	}
}

/**
 * update quantity function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_update_item_quantity() {
	global $wpsc_cart;

	if ( is_numeric( $_POST['key'] ) ) {
		$key = (int)$_POST['key'];

		$quantity = isset( $_POST['wpsc_quantity_update'] ) ? $_POST['wpsc_quantity_update'] : '';

		if ( isset( $_POST['quantity'] ) )
			$quantity = $_POST['quantity'];

		if ( $quantity > 0 ) {
			// if the quantity is greater than 0, update the item;
			$parameters['quantity'] = (int) $quantity;
			$wpsc_cart->edit_item( $key, $parameters );
		} else {
			// if the quantity is 0, remove the item.
			$wpsc_cart->remove_item( $key );
		}

		$coupon = wpsc_get_customer_meta( 'coupon' );
		if ( $coupon ) {
			wpsc_coupon_price( $coupon );
		}
	}
	$die = ! ( ( isset( $_REQUEST['wpsc_ajax_action'] ) && 'true' == $_REQUEST['wpsc_ajax_action'] ) || ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) );
	_wpsc_ajax_get_cart( $die );
}


function wpsc_update_product_rating() {
	global $wpdb;
	$nowtime = time();
	$product_id = absint( $_POST['product_id'] );
	$ip_number = $_SERVER['REMOTE_ADDR'];
	$rating = absint( $_POST['product_rating'] );

	$cookie_data = explode( ",", $_COOKIE['voting_cookie'][$product_id] );

	if ( is_numeric( $cookie_data[0] ) && ($cookie_data[0] > 0) ) {
		$vote_id = absint( $cookie_data[0] );
		$wpdb->update( WPSC_TABLE_PRODUCT_RATING, array(
		'rated' => $rating
		), array( 'id' => $vote_id ) );
	} else {
		$wpdb->insert( WPSC_TABLE_PRODUCT_RATING, array(
		'ipnum' => $ip_number,
		'productid' => $product_id,
		'rated' => $rating,
		'time' => $nowtime
		) );
		$data = $wpdb->get_results( "SELECT `id`,`rated` FROM `" . WPSC_TABLE_PRODUCT_RATING . "` WHERE `ipnum`='" . $ip_number . "' AND `productid` = '" . $product_id . "'  AND `rated` = '" . $rating . "' AND `time` = '" . $nowtime . "' ORDER BY `id` DESC LIMIT 1", ARRAY_A );
		$vote_id = $data[0]['id'];
		setcookie( "voting_cookie[$product_id]", ($vote_id . "," . $rating ), time() + (60 * 60 * 24 * 360) );
	}
	if ( $_POST['ajax'] == 'true' ) {

		exit();
	}
}

/**
 * update_shipping_price function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_update_shipping_price() {
	global $wpsc_cart;

	$quote_shipping_method = $_POST['method'];
	$quote_shipping_option = str_replace( array( '®', '™' ), array( '&reg;', '&trade;' ), $_POST['option'] );

    if ( ! empty( $quote_shipping_option ) && ! empty( $quote_shipping_method ) ) {
        $wpsc_cart->update_shipping( $quote_shipping_method, $quote_shipping_option );
    }

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

    	$response = apply_filters( 'wpsc_update_shipping_price_response', array(
				'shipping'   => wpsc_cart_shipping(),
				'coupon'     => wpsc_coupon_amount(),
				'cart_total' => wpsc_cart_total(),
				'tax'        => wpsc_cart_tax()
    		),
    		$quote_shipping_method,
    		$quote_shipping_option
    	);
 		echo json_encode( $response );
    	exit();
    }

}

/**
 * update_shipping_price function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_get_rating_count() {
	global $wpdb, $wpsc_cart;
	$prodid = $_POST['product_id'];
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) AS `count` FROM `" . WPSC_TABLE_PRODUCT_RATING . "` WHERE `productid` = %d", $prodid ) );
	echo $count . "," . absint( $prodid );
	exit();
}

/**
 * update_product_page_price function, used through ajax with variations
 * No parameters, returns nothing
 */
function wpsc_update_product_price() {
	if ( empty( $_POST['product_id'] ) || ! is_numeric( $_POST['product_id'] ) ) {
		return;
	}

	$from = '';
	$change_price = true;
	$product_id = (int) $_POST['product_id'];
	$variations = array();
	$response   = array(
		'product_id'      => $product_id,
		'variation_found' => false,
	);

	if ( ! empty( $_POST['variation'] ) ) {
		foreach ( $_POST['variation'] as $variation ) {
			if ( is_numeric( $variation ) ) {
				$variations[] = (int)$variation;
			}
		}

		do_action( 'wpsc_update_variation_product', $product_id, $variations );

		$stock = wpsc_check_variation_stock_availability( $product_id, $variations );

		if ( $stock !== false ) {
			$response['variation_found'] = true;
			if ( $stock === 0 ) {
				$response += array(
					'product_msg'     =>  __( 'Sorry, but this variation is out of stock.', 'wp-e-commerce' ),
					'variation_msg'   => __( 'Variation not in stock', 'wp-e-commerce' ),
					'stock_available' => false,
				);
			} else {
				$response += array(
					'variation_msg'   => __( 'Product in stock', 'wp-e-commerce' ),
					'stock_available' => true,
				);
			}

			if ( $change_price ) {
				$old_price           = wpsc_calculate_price( $product_id, $variations, false );
				$you_save_amount     = wpsc_you_save( array( 'product_id' => $product_id, 'type' => 'amount', 'variations' => $variations ) );
				$you_save_percentage = wpsc_you_save( array( 'product_id' => $product_id, 'variations' => $variations ) );

				$price = wpsc_calculate_price( $product_id, $variations, true );

				$response += array(
					'old_price'         => wpsc_currency_display( $old_price, array( 'display_as_html' => false ) ),
					'numeric_old_price' => (float) $old_price,
					'you_save'          => wpsc_currency_display( $you_save_amount, array( 'display_as_html' => false ) ) . "! (" . $you_save_percentage . "%)",
					'price'             => $from . wpsc_currency_display( $price, array( 'display_as_html' => false ) ),
					'numeric_price'     => (float) $price,
				);
			}
		}
	}

	$response = apply_filters( 'wpsc_update_product_price', $response, $product_id );

	echo json_encode( $response );
	exit();
}

/**
 * update location function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_update_location() {
	global $wpsc_cart;

	/*
	 * Checkout page shipping calculator MAY provide a zip code using the identifier from prior
	 * releases.  Let's check for that.
	 */
	if ( isset( $_POST['zipcode'] ) ) {
		wpsc_update_customer_meta( 'shippingpostcode', sanitize_text_field( $_POST['zipcode'] ) );
	}

	/*
	 * Checkout page shipping calculator MAY provide a country code using the identifier from prior
	 * releases.  Let's check for that.
	 */
	if ( isset( $_POST['country'] ) ) {
		$wpsc_country = new WPSC_Country( $_POST['country'] );
		wpsc_update_customer_meta( 'shippingcountry', $wpsc_country->get_isocode() );
	}

	/*
	 * WPeC's totally awesome checkout page shipping calculator has a submit button that will send
	 * some of the shipping data to us in an AJAX request.  The format of the data as of version
	 * 3.8.14.1 uses the 'collected_data' array format just like in checkout. We should process
	 * this array in case it has some updates to the user meta (checkout information) that haven't been
	 * recorded at the time the calculate button was clicked.  If the country or zip code is set using the
	 * legacy 'country' or 'zip' code $_POST values they will be overwritten if they are also included
	 * in the collected_data $_POST value.
	 */
	if ( isset( $_POST['collected_data'] ) && is_array( $_POST['collected_data'] ) ) {
		_wpsc_checkout_customer_meta_update( $_POST['collected_data'] );
	}

	$wpsc_cart->update_location();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->get_shipping_option();

	if ( $wpsc_cart->selected_shipping_method != '' ) {
		$wpsc_cart->update_shipping( $wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option );
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'update_location' == $_REQUEST['action'] )
		exit;
}

function wpsc_cart_html_page() {
	require_once(WPSC_FILE_PATH . '/wpsc-includes/shopping_cart_container.php' );
	exit();
}

/**
 * Populate Also Bought List
 * Runs on checking out and populates the also bought list.
 */
function wpsc_populate_also_bought_list() {
	global $wpdb, $wpsc_cart, $wpsc_coupons;

	$new_also_bought_data = array();
	foreach ( $wpsc_cart->cart_items as $outer_cart_item ) {
		$new_also_bought_data[$outer_cart_item->product_id] = array();
		foreach ( $wpsc_cart->cart_items as $inner_cart_item ) {
			if ( $outer_cart_item->product_id != $inner_cart_item->product_id ) {
				$new_also_bought_data[$outer_cart_item->product_id][$inner_cart_item->product_id] = $inner_cart_item->quantity;
			} else {
				continue;
			}
		}
	}

	$insert_statement_parts = array();
	foreach ( $new_also_bought_data as $new_also_bought_id => $new_also_bought_row ) {
		$new_other_ids = array_keys( $new_also_bought_row );
		$also_bought_data = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `associated_product`, `quantity` FROM `" . WPSC_TABLE_ALSO_BOUGHT . "` WHERE `selected_product` IN(%d) AND `associated_product` IN('" . implode( "','", $new_other_ids ) . "')", $new_also_bought_id ), ARRAY_A );
		$altered_new_also_bought_row = $new_also_bought_row;

		foreach ( (array)$also_bought_data as $also_bought_row ) {
			$quantity = $new_also_bought_row[$also_bought_row['associated_product']] + $also_bought_row['quantity'];

			unset( $altered_new_also_bought_row[$also_bought_row['associated_product']] );
			$wpdb->update(
				WPSC_TABLE_ALSO_BOUGHT,
				array(
				    'quantity' => $quantity
				),
				array(
				    'id' => $also_bought_row['id']
				),
				'%d',
				'%d'
			);
	    }

		if ( count( $altered_new_also_bought_row ) > 0 ) {
			foreach ( $altered_new_also_bought_row as $associated_product => $quantity ) {
				$insert_statement_parts[] = "(" . absint( esc_sql( $new_also_bought_id ) ) . "," . absint( esc_sql( $associated_product ) ) . "," . absint( esc_sql( $quantity ) ) . ")";
			}
		}
	}

	if ( count( $insert_statement_parts ) > 0 ) {
		$insert_statement = "INSERT INTO `" . WPSC_TABLE_ALSO_BOUGHT . "` (`selected_product`, `associated_product`, `quantity`) VALUES " . implode( ",\n ", $insert_statement_parts );
		$wpdb->query( $insert_statement );
	}
}

/**
 * submit checkout function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_submit_checkout( $collected_data = true ) {
	global $wpdb, $wpsc_cart, $user_ID, $nzshpcrt_gateways, $wpsc_shipping_modules, $wpsc_gateways;

	if ( $collected_data && isset( $_POST['collected_data'] ) && is_array( $_POST['collected_data'] ) ) {
		_wpsc_checkout_customer_meta_update( $_POST['collected_data'] );
	}

	// initialize our checkout status variable, we start be assuming
	// checkout is falid, until we find a reason otherwise
	$is_valid           = true;
	$num_items          = 0;
	$use_shipping       = 0;
	$disregard_shipping = 0;

	do_action( 'wpsc_before_submit_checkout', $collected_data );

	$error_messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );

	if ( ! is_array( $error_messages ) ) {
		$error_messages = array();
	}

	$wpsc_checkout = new wpsc_checkout();

	$selected_gateways = get_option( 'custom_gateway_options' );
	$submitted_gateway = isset( $_POST['custom_gateway'] ) ? $_POST['custom_gateway'] : '';

	if ( $collected_data ) {
		$form_validity = $wpsc_checkout->validate_forms();
		extract( $form_validity ); // extracts $is_valid and $error_messages

		if ( wpsc_has_tnc() && ( ! isset( $_POST['agree'] ) || $_POST['agree'] != 'yes' ) ) {
			$error_messages[] = __( 'Please agree to the terms and conditions, otherwise we cannot process your order.', 'wp-e-commerce' );
			$is_valid = false;
		}
	} else {
		$is_valid = true;
		$error_messages = array();
	}

	if ( wpsc_uses_shipping() ) {
		$wpsc_country = new WPSC_Country( wpsc_get_customer_meta( 'shippingcountry' ) );
		$country_id   = $wpsc_country->get_id();
		$country_name = $wpsc_country->get_name();

		foreach ( $wpsc_cart->cart_items as $cartitem ) {

			if ( ! empty( $cartitem->meta[0]['no_shipping'] ) ) {
				continue;
			}

			$category_ids = $cartitem->category_id_list;

			foreach ( (array) $category_ids as $catid ) {
				if ( is_array( $catid ) ) {
					$countries = wpsc_get_meta( $catid[0], 'target_market', 'wpsc_category' );
				} else {
					$countries = wpsc_get_meta( $catid, 'target_market', 'wpsc_category' );
				}

				if ( ! empty( $countries ) && ! in_array( $country_id, (array) $countries ) ) {
					$errormessage = sprintf( __( '%s cannot be shipped to %s. To continue with your transaction, please remove this product from the list below.', 'wp-e-commerce' ), $cartitem->get_title(), $country_name );
					wpsc_update_customer_meta( 'category_shipping_conflict', $errormessage );
					$is_valid = false;
				}
			}

			//count number of items, and number of items using shipping
			$num_items++;

			if ( $cartitem->uses_shipping != 1 ) {
				$disregard_shipping++;
			} else {
				$use_shipping++;
			}
		}
	}

	// check to see if the current gateway is in the list of available gateways
	if ( array_search( $submitted_gateway, $selected_gateways ) !== false || wpsc_is_free_cart() ) {
		wpsc_update_customer_meta( 'selected_gateway', $submitted_gateway );
	} else {
		$is_valid = false;
	}

	if ( $collected_data ) {

		// Test for required shipping information
		if ( wpsc_core_shipping_enabled() && ( $num_items != $disregard_shipping ) ) {
			// for shipping to work we need a method, option and a quote, unless we have free shipping.

			$shipping_discount_value  = get_option( 'shipping_discount_value' );
			$is_free_shipping_enabled = get_option( 'shipping_discount' );
			$subtotal                 = $wpsc_cart->calculate_subtotal();

			$has_free_shipping = $is_free_shipping_enabled && $shipping_discount_value > 0 && $shipping_discount_value <= $subtotal;

			if ( ! $has_free_shipping ) {
				if ( ! $wpsc_cart->shipping_method_selected() || ! $wpsc_cart->shipping_quote_selected() ) {
					$error_messages[] = __( 'Please select one of the available shipping options, then we can process your order.', 'wp-e-commerce' );
					$is_valid = false;
				}
			}

			// if we don't have a valid zip code ( the function also checks if we need it ) we have an error
			if ( ! wpsc_have_valid_shipping_zipcode() ) {
					wpsc_update_customer_meta( 'category_shipping_conflict', __( 'Please enter a Zipcode and click calculate to proceed', 'wp-e-commerce' ) );
					$is_valid = false;
			}
		}
	}

	wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );

	if ( $is_valid == true ) {
		wpsc_delete_customer_meta( 'category_shipping_conflict' );
		// check that the submitted gateway is in the list of selected ones
		$sessionid = (mt_rand( 100, 999 ) . time());
		wpsc_update_customer_meta( 'checkout_session_id', $sessionid );
		$subtotal = $wpsc_cart->calculate_subtotal();
		if ( $wpsc_cart->has_total_shipping_discount() == false )
			$base_shipping = $wpsc_cart->calculate_base_shipping();
		else
			$base_shipping = 0;

		$delivery_country = $wpsc_cart->delivery_country;
		$delivery_region = $wpsc_cart->delivery_region;

		if ( wpsc_uses_shipping() ) {
			$shipping_method = $wpsc_cart->selected_shipping_method;
			$shipping_option = $wpsc_cart->selected_shipping_option;
		} else {
			$shipping_method = '';
			$shipping_option = '';
		}
		if ( isset( $_POST['how_find_us'] ) )
			$find_us = $_POST['how_find_us'];
		else
			$find_us = '';

		//keep track of tax if taxes are exclusive
		$wpec_taxes_controller = new wpec_taxes_controller();
		if ( ! $wpec_taxes_controller->wpec_taxes_isincluded() ) {
			$tax = $wpsc_cart->calculate_total_tax();
			$tax_percentage = $wpsc_cart->tax_percentage;
		} else {
			$tax = 0.00;
			$tax_percentage = 0.00;
		}

		$total = $wpsc_cart->calculate_total_price();

		$args = array(
			'totalprice'       => $total,
			'statusno'         => '0',
			'sessionid'        => $sessionid,
			'user_ID'          => (int) $user_ID,
			'date'             => time(),
			'gateway'          => $submitted_gateway,
			'billing_country'  => $wpsc_cart->selected_country,
			'shipping_country' => $delivery_country,
			'billing_region'   => $wpsc_cart->selected_region,
			'shipping_region'  => $delivery_region,
			'base_shipping'    => $base_shipping,
			'shipping_method'  => $shipping_method,
			'shipping_option'  => $shipping_option,
			'plugin_version'   => WPSC_VERSION,
			'discount_value'   => $wpsc_cart->coupons_amount,
			'discount_data'    => $wpsc_cart->coupons_name,
			'find_us'          => $find_us,
			'wpec_taxes_total' => $tax,
			'wpec_taxes_rate'  => $tax_percentage,
		);

		$purchase_log = new WPSC_Purchase_Log( $args );
		$purchase_log->save();
		$purchase_log_id = $purchase_log->get( 'id' );

		//Check to ensure log row was inserted successfully
		if(is_null($purchase_log_id)) {
			$error_messages[] = __( 'A database error occurred while processing your request.', 'wp-e-commerce' );
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $error_messages );
			return;
		}

		if ( $collected_data ) {
			$wpsc_checkout->save_forms_to_db( $purchase_log_id );
		}

		$wpsc_cart->save_to_db( $purchase_log_id );
		$wpsc_cart->submit_stock_claims( $purchase_log_id );

		if ( ! isset( $our_user_id ) && isset( $user_ID ) ) {
			$our_user_id = $user_ID;
		}

		$wpsc_cart->log_id = $purchase_log_id;
		do_action( 'wpsc_submit_checkout', array( 'purchase_log_id' => $purchase_log_id, 'our_user_id' => $our_user_id ) );
		do_action( 'wpsc_submit_checkout_gateway', $submitted_gateway, $purchase_log );
	}
}

/**
 * wpsc_change_tax function, used through ajax and in normal page loading.
 * No parameters, returns nothing
 */
function wpsc_change_tax() {
	global $wpdb, $wpsc_cart;


	$form_id = absint( $_POST['form_id'] );

	$wpsc_selected_country = $wpsc_cart->selected_country;
	$wpsc_selected_region  = $wpsc_cart->selected_region;

	$wpsc_delivery_country = $wpsc_cart->delivery_country;
	$wpsc_delivery_region  = $wpsc_cart->delivery_region;

	$previous_country = wpsc_get_customer_meta( 'billingcountry' );

	global $wpdb, $user_ID, $wpsc_customer_checkout_details;

	if ( isset( $_POST['billing_country'] ) ) {
		$wpsc_selected_country = sanitize_text_field( $_POST['billing_country'] );
		wpsc_update_customer_meta( 'billingcountry', $wpsc_selected_country );
	}

	if ( isset( $_POST['billing_region'] ) ) {
		$wpsc_selected_region = absint( $_POST['billing_region'] );
		wpsc_update_customer_meta( 'billingregion', $wpsc_selected_region );
	}

	$check_country_code = WPSC_Countries::get_country_id_by_region_id( wpsc_get_customer_meta( 'billing_region' ) );

	if ( wpsc_get_customer_meta( 'billingcountry' ) != $check_country_code ) {
		$wpsc_selected_region = null;
	}

	if ( isset( $_POST['shipping_country'] ) ) {
		$wpsc_delivery_country = sanitize_text_field( $_POST['shipping_country'] );
		wpsc_update_customer_meta( 'shippingcountry', $wpsc_delivery_country );
	}
	if ( isset( $_POST['shipping_region'] ) ) {
		$wpsc_delivery_region = absint( $_POST['shipping_region'] );
		wpsc_update_customer_meta( 'shippingregion', $wpsc_delivery_region );
	}

	$check_country_code = WPSC_Countries::get_country_id_by_region_id( $wpsc_delivery_region );
	if ( $wpsc_delivery_country != $check_country_code ) {
		$wpsc_delivery_region = null;
	}

	$wpsc_cart->update_location();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->get_shipping_option();

	if ( $wpsc_cart->selected_shipping_method != '' ) {
		$wpsc_cart->update_shipping( $wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option );
	}

	$tax         = $wpsc_cart->calculate_total_tax();
	$total       = wpsc_cart_total();
	$total_input = wpsc_cart_total( false );

	if ( $wpsc_cart->coupons_amount >= $total_input && ! empty( $wpsc_cart->coupons_amount ) ) {
		$total = 0;
	}

	if ( $wpsc_cart->total_price < 0 ) {
		$wpsc_cart->coupons_amount += $wpsc_cart->total_price;
		$wpsc_cart->total_price     = null;
		$wpsc_cart->calculate_total_price();
	}

	$delivery_country = wpsc_get_customer_meta( 'shipping_country' );
	$output           = _wpsc_ajax_get_cart( false );
	$output           = $output['widget_output'];

	$json_response = array();

	global $wpsc_checkout;
	if ( empty( $wpsc_checkout ) ) {
		$wpsc_checkout = new wpsc_checkout();
	}

	$json_response['delivery_country'] = esc_js( $delivery_country );
	$json_response['billing_country']  = esc_js( $wpsc_selected_country );
	$json_response['widget_output']    = $output;
	$json_response['shipping_keys']    = array();
	$json_response['cart_shipping']    = wpsc_cart_shipping();
	$json_response['form_id']          = $form_id;
	$json_response['tax']              = $tax;
	$json_response['display_tax']      = wpsc_cart_tax();
	$json_response['total']            = $total;
	$json_response['total_input']      = $total_input;

	$json_response['lock_tax']     = get_option( 'lock_tax' );
	$json_response['country_name'] = wpsc_get_country( $delivery_country );

	if ( 'US' == $delivery_country || 'CA' == $delivery_country ) {
		$output = wpsc_shipping_region_list( $delivery_country, wpsc_get_customer_meta( 'shipping_region' ) );
		$output = str_replace( array( "\n", "\r" ), '', $output );
		$json_response['shipping_region_list'] = $output;
	}

	foreach ( $wpsc_cart->cart_items as $key => $cart_item ) {
		$json_response['shipping_keys'][ $key ] = wpsc_currency_display( $cart_item->shipping );
	}

	$form_selected_country = null;
	$form_selected_region  = null;
	$onchange_function     = null;

	if ( ! empty( $_POST['billing_country'] ) && $_POST['billing_country'] != 'undefined' && ! isset( $_POST['shipping_country'] ) ) {
		$form_selected_country = $wpsc_selected_country;
		$form_selected_region  = $wpsc_selected_region;
		$onchange_function     = 'set_billing_country';
	} else if ( ! empty( $_POST['shipping_country'] ) && $_POST['shipping_country'] != 'undefined' && ! isset( $_POST['billing_country'] ) ) {
		$form_selected_country = $wpsc_delivery_country;
		$form_selected_region  = $wpsc_delivery_region;
		$onchange_function     = 'set_shipping_country';
	}

	if ( $form_selected_country != null && $onchange_function != null ) {

		$checkoutfields = 'set_shipping_country' == $onchange_function;
		$region_list = wpsc_country_region_list( $form_id, false, $form_selected_country, $form_selected_region, $form_id, $checkoutfields );

		if ( $region_list != null ) {
			$json_response['region_list'] = str_replace( array( "\n", "\r" ), '', $region_list );
		}
	}

	echo json_encode( $json_response );
	exit();
}

function _wpsc_change_profile_country() {
	global $wpdb;

	$country_field_id = absint( $_REQUEST['form_id'] );
	$country          = $_REQUEST['country'];

	$sql = $wpdb->prepare( 'SELECT unique_name FROM `'.WPSC_TABLE_CHECKOUT_FORMS.'` WHERE `id`= %d', $country_field_id );
	$country_field_unique_name = $wpdb->get_var( $sql );

	$has_regions = wpsc_has_regions( $country );
	$response = array( 'has_regions' => $has_regions );

	$region_unique_name = 'shippingstate';
	if ( $country_field_unique_name == 'billingcountry' )
		$region_unique_name = 'billingstate';

	$sql = $wpdb->prepare( 'SELECT id FROM ' . WPSC_TABLE_CHECKOUT_FORMS . ' WHERE unique_name=%s AND active="1"', $region_unique_name );
	$response['region_field_id'] = $wpdb->get_var( $sql );

	if ( $has_regions )
		$response['html'] = "<select name='collected_data[" . $country_field_id . "][1]'>" . nzshpcrt_region_list( $country, '' ) . "</select>";

	echo json_encode( $response );
	exit;
}

function wpsc_shipping_same_as_billing(){
	wpsc_update_customer_meta( 'shippingSameBilling', sanitize_text_field( $_POST['wpsc_shipping_same_as_billing'] ) );
}

function wpsc_update_shipping_quotes_on_shipping_same_as_billing() {
	global $wpsc_cart;

	wpsc_update_location();

	if ( get_option( 'do_not_use_shipping' ) )
		die( '-1' );

	if ( ! wpsc_have_shipping_quote() ) {
		die( '0' );
	}
	else {
		?>
   <tr class="wpsc_shipping_info">
            <td colspan="5">
               <?php _e( 'Please choose a country below to calculate your shipping costs', 'wp-e-commerce' ); ?>
            </td>
         </tr>

         <?php if ( ! wpsc_have_shipping_quote() ) : // No valid shipping quotes ?>
            <?php if ( ! wpsc_have_valid_shipping_zipcode() ) : ?>
                  <tr class='wpsc_update_location'>
                     <td colspan='5' class='shipping_error' >
                        <?php _e('Please provide a ZIP code and click Calculate in order to continue.', 'wp-e-commerce'); ?>
                     </td>
                  </tr>
            <?php else: ?>
               <tr class='wpsc_update_location_error'>
                  <td colspan='5' class='shipping_error' >
                     <?php _e('Sorry, online ordering is unavailable for this destination and/or weight. Please double check your destination details.', 'wp-e-commerce'); ?>
                  </td>
               </tr>
            <?php endif; ?>
         <?php endif; ?>
         <tr class='wpsc_change_country'>
            <td colspan='5'>
               <form name='change_country' id='change_country' action='' method='post'>
                  <?php echo wpsc_shipping_country_list();?>
                  <input type='hidden' name='wpsc_update_location' value='true' />
                  <input type='submit' name='wpsc_submit_zipcode' value='Calculate' />
               </form>
            </td>
         </tr>

         <?php if (wpsc_have_morethanone_shipping_quote()) :?>
            <?php while (wpsc_have_shipping_methods()) : wpsc_the_shipping_method(); ?>
                  <?php    if (!wpsc_have_shipping_quotes()) { continue; } // Don't display shipping method if it doesn't have at least one quote ?>
                  <tr class='wpsc_shipping_header'><td class='shipping_header' colspan='5'><?php echo wpsc_shipping_method_name().__(' - Choose a Shipping Rate', 'wp-e-commerce'); ?> </td></tr>
                  <?php while (wpsc_have_shipping_quotes()) : wpsc_the_shipping_quote();  ?>
                     <tr class='<?php echo wpsc_shipping_quote_html_id(); ?>'>
                        <td class='wpsc_shipping_quote_name wpsc_shipping_quote_name_<?php echo wpsc_shipping_quote_html_id(); ?>' colspan='3'>
                           <label for='<?php echo wpsc_shipping_quote_html_id(); ?>'><?php echo wpsc_shipping_quote_name(); ?></label>
                        </td>
                        <td class='wpsc_shipping_quote_price wpsc_shipping_quote_price_<?php echo wpsc_shipping_quote_html_id(); ?>' style='text-align:center;'>
                           <label for='<?php echo wpsc_shipping_quote_html_id(); ?>'><?php echo wpsc_shipping_quote_value(); ?></label>
                        </td>
                        <td class='wpsc_shipping_quote_radio wpsc_shipping_quote_radio_<?php echo wpsc_shipping_quote_html_id(); ?>' style='text-align:center;'>
                           <?php if(wpsc_have_morethanone_shipping_methods_and_quotes()): ?>
                              <input type='radio' id='<?php echo wpsc_shipping_quote_html_id(); ?>' <?php echo wpsc_shipping_quote_selected_state(); ?>  onclick='switchmethod("<?php echo wpsc_shipping_quote_name(); ?>", "<?php echo wpsc_shipping_method_internal_name(); ?>")' value='<?php echo wpsc_shipping_quote_value(true); ?>' name='shipping_method' />
                           <?php else: ?>
                              <input <?php echo wpsc_shipping_quote_selected_state(); ?> disabled='disabled' type='radio' id='<?php echo wpsc_shipping_quote_html_id(); ?>'  value='<?php echo wpsc_shipping_quote_value(true); ?>' name='shipping_method' />
                                 <?php wpsc_update_shipping_single_method(); ?>
                           <?php endif; ?>
                        </td>
                     </tr>
                  <?php endwhile; ?>
            <?php endwhile; ?>
         <?php endif; ?>

         <?php wpsc_update_shipping_multiple_methods(); ?>

		<?php

	}
	exit;

}

function _wpsc_get_alternate_html( $cart_messages ) {
	// These shenanigans are necessary for two reasons.
	// 1) Some hook into POST, some GET, some REQUEST. They check for the conditional params below.
	// 2) Most functions properly die() - that means that our output buffer stops there and won't continue on for our purposes.
	// If there is a better way to get that output without dying, I'm all ears.  A nice slow HTTP request for now.

	$cookies = array();
	foreach ( $_COOKIE as $name => $value ) {
		if ( 'PHPSESSID' == $name )
			continue;

		$cookies[] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
	}

	wpsc_serialize_shopping_cart();

	$javascript = wp_remote_retrieve_body(
		wp_safe_remote_post(
			esc_url_raw( add_query_arg( array( 'wpsc_action' => 'wpsc_get_alternate_html', 'ajax' => 'true', 'wpsc_ajax_action' => 'add_to_cart' ), home_url() ),
			array(
				'body' =>
					array(
						'cart_messages' => $cart_messages, 'ajax' => 'true', 'wpsc_ajax_action' => 'add_to_cart', 'product_id' => empty( $_REQUEST['product_id'] ) ? '' : $_REQUEST['product_id'], '_wpsc_compat_ajax' => true
					),

				'cookies'    => $cookies,
				'user-agent' => $_SERVER['HTTP_USER_AGENT']
			)
		) )
	);
	return $javascript;
}

/**
 * Returns the jQuery that is likely included in calls to this action.  For back compat only, will be deprecated soon.
 * Couldn't think up a better way to return this output, which most often will end in die(), without die()ing early ourselves.
 *
 * @param  array  $cart_messages [description]
 */
function _wpsc_ajax_return_alternate_html() {
	$cart_messages = empty( $_POST['cart_messages'] ) ? array() : (array) $_POST['cart_messages'];
	do_action( 'wpsc_alternate_cart_html', $cart_messages );
	die;
}

if ( isset( $_REQUEST['wpsc_action'] ) && 'wpsc_get_alternate_html' == $_REQUEST['wpsc_action'] )
	add_action( 'init', '_wpsc_ajax_return_alternate_html' );

/**
 * Returns the Cart Widget
 *
 * @param  boolean $die          Whether or not to return the output (for new JSON requests) or to die() on the old $output / action.
 * @param  array   $cart_message An array of cart messages to be optionally passed.  Primarily passed via wpsc_add_to_cart().
 *
 * @since 3.8.11
 * @return mixed                 Returns an array of output data, alternatively
 */
function _wpsc_ajax_get_cart( $die = true, $cart_messages = array() ) {
	$return = array();

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		ob_start();
		include_once( wpsc_get_template_file_path( 'wpsc-cart_widget.php' ) );

		$output = ob_get_contents();
		ob_end_clean();
		$output = str_replace( array( '\n', '\r' ), '', $output );

		$return['widget_output']   = $output;
		$return['core_images_url'] = WPSC_CORE_IMAGES_URL;

		if ( 1 == get_option( 'show_sliding_cart' ) || empty( $cart_messages ) ) {
			if ( wpsc_cart_item_count()  ) {
				$_SESSION['slider_state']     = 1;
				$return['sliding_cart_state'] = 'show';
			} else {
				$_SESSION['slider_state']     = 0;
				$return['sliding_cart_state'] = 'hide';
			}
		}

		$action_output = '';
		if ( has_action( 'wpsc_alternate_cart_html' ) && empty( $_REQUEST['_wpsc_compat_ajax'] ) ) {
			//Deprecated action. Do not use.  We now have a custom JS event called 'wpsc_fancy_notification'. There is access to the complete $json_response object.
			ob_start();

			echo _wpsc_get_alternate_html( $cart_messages );
			$action_output = ob_get_contents();
			$output = '';
			ob_end_clean();
		}

		if ( ! empty( $action_output ) ) {
			_wpsc_doing_it_wrong( 'wpsc_alternate_cart_html', __( 'As of WPeC 3.8.11, it is improper to hook into "wpsc_alternate_cart_html" to output javascript.  We now have a custom javascript event called "wpsc_fancy_notification" you can hook into.', 'wp-e-commerce' ), '3.8.11' );
			$return['wpsc_alternate_cart_html'] = $action_output;
		}
	}

	if ( $die ) {
		echo $output . $action_output;
		die();
	} else {
		return $return;
	}
}


/**
 * Update the customer mata values that are passed to the application from the checkout form POST
 *
 * With the submit checkout we should get an array of all the checkout values.  These values should already
 * be stored as customer meta, bet there are cases where the submit processing may arrive before or in parallel
 * with the request to update meta.  There is also value in cehcking to be sure the meta stored is what is coming
 * with the POST as it preserves non-js compatibility and being able to use the submit action as an API
 *
 * @since  3.8.14.1
 *
 * @access private
 *
 * @param  array $checkout_post_data
 *
 * @return none
 */
function _wpsc_checkout_customer_meta_update( $checkout_post_data ) {
	global $wpdb;

	if ( empty ( $checkout_post_data ) || ! is_array( $checkout_post_data ) ) {
		return;
	}

	$id = wpsc_get_current_customer_id();

	$form_sql  = 'SELECT * FROM `' . WPSC_TABLE_CHECKOUT_FORMS . '` WHERE `active` = "1" ORDER BY `checkout_set`, `checkout_order`;';
	$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

	foreach ( $form_data as $index => $form_field ) {
		if (  isset( $checkout_post_data[$form_field['id']] ) ) {

			$meta_key   = $form_field['unique_name'];
			$meta_value = $checkout_post_data[$form_field['id']];

			switch ( $form_field['type'] ) {
				case 'delivery_country':
					if ( is_array( $meta_value ) ) {

						if ( isset( $meta_value[0] ) ) {
							wpsc_update_visitor_meta( $id, 'shippingcountry', $meta_value[0] );
						}

						if ( isset( $meta_value[1] ) ) {
							wpsc_update_visitor_meta( $id, 'shippingregion', $meta_value[1] );
						}
					} else {
						// array had only country, update the country
						wpsc_update_visitor_meta( $id, 'shippingcountry', $meta_value );
					}

					break;

				case 'country':
					if ( is_array( $meta_value ) && count( $meta_value ) == 2 ) {
						wpsc_update_visitor_meta( $id, 'billingcountry', $meta_value[0] );
						wpsc_update_visitor_meta( $id, 'billingregion', $meta_value[1] );
					} else {
						if ( is_array( $meta_value ) ) {
							$meta_value = $meta_value[0];
						}

						wpsc_update_visitor_meta( $id, 'billingcountry', $meta_value );
					}

					break;

				default:
					wpsc_update_visitor_meta( $id, $meta_key, $meta_value );
					break;
			}
		}
	}
}

function wpsc_save_user_profile() {
	if ( isset( $_POST['collected_data'] ) && is_array( $_POST['collected_data'] ) ) {
		_wpsc_checkout_customer_meta_update( $_POST['collected_data'] );
	}
}
