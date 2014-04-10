<?php
/**
 * The WPSC Cart API for templates
 */


/**
 * Does shipping information need to be recalculated for the current customer cart
 *
 * @since 3.8.14
 *
 * @uses wpsc_cart
 *
 */
function wpsc_cart_need_to_recompute_shipping_quotes() {
	global $wpsc_cart;

	$result = false;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		$result = $wpsc_cart->needs_shipping_recalc();
	}

	return $result;
}

/**
 * Clear all shipping method information for the current customer cart
 *
 * @since 3.8.14
 *
 * @uses wpsc_cart
 *
 */
function wpsc_cart_clear_shipping_info() {
	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		$wpsc_cart->clear_shipping_info();
	}
}

/**
 * tax is included function
 *
 * @uses wpec_taxes_controller
 *
 * @return boolean true or false depending on settings>general page
 */
function wpsc_tax_isincluded() {
	//uses new wpec_taxes functionality now
	$wpec_taxes_controller = new wpec_taxes_controller();
	return $wpec_taxes_controller->wpec_taxes_isincluded();
}

/**
 * cart item count function
 *
 * @uses wpsc_cart
 *
 * @return integer the item count
 */
function wpsc_cart_item_count() {
	global $wpsc_cart;

	$count = 0;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		foreach ( (array)$wpsc_cart->cart_items as $cart_item ) {
			$count += $cart_item->quantity;
		}
	}

	return $count;
}

/**
 * Get WPEC cart coupon value total
 *
 * @param boolean 	$format_for_display		should the output formatted for display, or returned as a number
 *
 * @uses wpsc_cart
 *
 * @return integer the item count
 */
function wpsc_coupon_amount( $format_for_display = true ) {
	global $wpsc_cart;

	$output = '';

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( $format_for_display ) {
			$output = wpsc_currency_display( $wpsc_cart->coupons_amount );
		} else {
			$output = $wpsc_cart->coupons_amount;
		}
	}

	return $output;
}

/**
 * cart total function
 *
 * @param boolean 	$format_for_display		should the output formatted for display, or returned as a number
 *
 * @uses wpsc_cart
 *
 * @return string the total price of the cart, with a currency sign
 */
function wpsc_cart_total( $format_for_display = true ) {
	global $wpsc_cart;
	$total = 0;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {

		$total = $wpsc_cart->calculate_total_price();

		if ( $format_for_display ) {
			$total = wpsc_currency_display( $total );
		} else {
			$total = $total;
		}
	}

	return $total;
}

/**
 * nzshpcrt_overall_total_price function
 *
 * @uses wpsc_cart
 *
 * @return string the total price of the cart, with a currency sign, empty string on failure
 */
function nzshpcrt_overall_total_price() {

	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		return  $wpsc_cart->calculate_total_price();
	} else {
		return '';
	}

}

/**
 * cart total weight function
 *
 * @uses wpsc_cart
 *
 * @return float the total weight of the cart
 */
function wpsc_cart_weight_total() {
	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		return $wpsc_cart->calculate_total_weight( true );
	} else {
		return 0.0;
	}
}

/**
 * tax total function, no parameters
 *
 * @uses wpsc_cart
 *
 * @return float the total weight of the cart
 */
function wpsc_cart_tax( $format_for_display = true ) {
	global $wpsc_cart;

	$cart_tax = $format_for_display ? '' : 0;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( $format_for_display ) {
			if ( ! wpsc_tax_isincluded() ) {
				$cart_tax = wpsc_currency_display( $wpsc_cart->calculate_total_tax() );
			} else {
				$cart_tax = '(' . wpsc_currency_display( $wpsc_cart->calculate_total_tax() ) . ')';
			}
		} else {
			$cart_tax = $wpsc_cart->calculate_total_tax();
		}
	}

	return $cart_tax;
}


/**
 * wpsc_cart_show_plus_postage function, no parameters
 * For determining whether to show "+ Postage & tax" after the total price
 *
 * @uses wpsc_cart
 *
 * @return boolean true or false
 */
function wpsc_cart_show_plus_postage() {

	// TODO: Deprecate the use of $_SESSION for cart state, see github issue report #997
	// https://github.com/wp-e-commerce/WP-e-Commerce/issues/997
	if (
		isset( $_SESSION['wpsc_has_been_to_checkout'] )
			&& ($_SESSION['wpsc_has_been_to_checkout'] == null )
				&& ( get_option( 'add_plustax' ) == 1
		) ) {

		return true;

	} else {
		return false;
	}
}

/**
 * Does the customers cart require/user shipping
 *
 * @uses wpsc_cart

 * @return boolean if true, all items in the cart do use shipping
 */
function wpsc_uses_shipping() {

	//This currently requires
	global $wpsc_cart;

	$shippingoptions = get_option( 'custom_shipping_options' );

	if ( get_option( 'do_not_use_shipping' ) ) {
		return false;
	}

	$uses_shipping = false;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {

		if ( ( ! ( ( get_option( 'shipping_discount' ) == 1 ) && ( get_option( 'shipping_discount_value' ) <= $wpsc_cart->calculate_subtotal() ) ) )
					|| ( count( $shippingoptions ) >= 1 && $shippingoptions[0] != '')
		) {
			$uses_shipping = (bool) $wpsc_cart->uses_shipping();
		} else {
			$uses_shipping = false;
		}
	}

	return $uses_shipping;
}

/**
 * Check if the shipping charges are non-zero for the customer cart
 *
 * @uses wpsc_cart
 *
 * @return boolean true for yes, false for no
 */
function wpsc_cart_has_shipping() {
	global $wpsc_cart;

	$has_shipping = false;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( $wpsc_cart->calculate_total_shipping() > 0 ) {
			$has_shipping = true;
		} else {
			$has_shipping = false;
		}
	}

	return $has_shipping;
}

/**
 * Get cart total
 *
 * @uses wpsc_cart
 *
 * @return string the total shipping of the cart, with a currency sign
 */
function wpsc_cart_shipping() {
	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		$result = wpsc_currency_display( $wpsc_cart->calculate_total_shipping() );
	} else {
		$result = '';
	}

	$result = apply_filters( 'wpsc_cart_shipping', $result );

	return $result;
}


/**
 * Get cart item categories function
 *
 * @since
 * @uses wpsc_cart
 *
 * @return array array of the categories
 */
function wpsc_cart_item_categories( $get_ids = false ) {
	global $wpsc_cart;

	$categories = array();

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( $get_ids ) {
			$categories = $wpsc_cart->get_item_category_ids();
		} else {
			$categories = $wpsc_cart->get_item_categories();
		}
	}

	return $categories;
}

/**
 * Product Maximum Cart Quantity
 *
 * @since  3.8.10
 * @access public
 *
 * @param  int  $prod_id    Optional. Product ID.
 * @return int              The maximum quantity that can be added to the cart.
 *
 * @uses   apply_filters    Calls 'wpsc_product_max_cart_quantity' passing product ID.
 */
function wpsc_product_max_cart_quantity( $product_id = 0 ) {
	$product_id = absint( $product_id );
	return apply_filters( 'wpsc_product_max_cart_quantity', 10000, $product_id );
}

/**
 * Product Minimum Cart Quantity
 *
 * @since  3.8.13
 * @access public
 *
 * @param  int  $prod_id    Optional. Product ID.
 * @return int              The minimum quantity that can be added to the cart.
 *
 * @uses   apply_filters    Calls 'wpsc_product_min_cart_quantity' passing product ID.
 */
function wpsc_product_min_cart_quantity( $product_id = 0 ) {
	$product_id = absint( $product_id );
	return apply_filters( 'wpsc_product_min_cart_quantity', 1, $product_id );
}

/**
 * Validate Product Cart Quantity
 * Checks that the quantity is within the permitted bounds and return a valid quantity.
 *
 * @since  3.8.10
 * @access public
 *
 * @param  int  $quantity                    Cart item product quantity.
 * @param  int  $prod_id                     Optional. Product ID.
 * @return int                               The maximum quantity that can be added to the cart.
 *
 * @uses   wpsc_product_max_cart_quantity    Gets the maximum product cart quantity.
 * @uses   wpsc_product_min_cart_quantity    Gets the minimum product cart quantity.
 * @uses wpsc_cart
 */
function wpsc_validate_product_cart_quantity( $quantity, $product_id = 0 ) {

	$max_quantity = wpsc_product_max_cart_quantity( $product_id );
	$min_quantity = wpsc_product_min_cart_quantity( $product_id );

	if ( $quantity > $max_quantity ) {
		return $max_quantity;
	}

	if ( $quantity < $min_quantity ) {
		return $min_quantity;
	}

	return $quantity;
}

/**
 * Validate Cart Product Quantity
 * Triggered by 'wpsc_add_item' and 'wpsc_edit_item' actions when products are added to the cart.
 *
 * @since  3.8.10
 * @access private
 *
 * @param int     $product_id                    Cart product ID.
 * @param array   $parameters                    Cart item parameters.
 * @param object  $cart                          Cart object.
 *
 * @uses wpsc_cart
 * @uses  wpsc_validate_product_cart_quantity    Filters and restricts the product cart quantity.
 */
function _wpsc_validate_cart_product_quantity( $product_id, $parameters, $cart ) {
	foreach ( $cart->cart_items as $key => $cart_item ) {
		if ( $cart_item->product_id == $product_id ) {
			$cart->cart_items[$key]->quantity = wpsc_validate_product_cart_quantity( $cart->cart_items[$key]->quantity, $product_id );
			$cart->cart_items[$key]->refresh_item();
		}
	}
}

add_action( 'wpsc_add_item' , '_wpsc_validate_cart_product_quantity', 10, 3 );
add_action( 'wpsc_edit_item', '_wpsc_validate_cart_product_quantity', 10, 3 );

/**
 * cart all shipping quotes, used for google checkout
 * returns all the quotes for a selected shipping method
 *
 * @since
 * @access public
 *
 * @uses wpsc_cart
 *
 * @return array of shipping options
*/
function wpsc_selfURL() {
	$s = empty( $_SERVER ['HTTPS'] ) ? '' : ( $_SERVER ['HTTPS'] == 'on' ) ? 's' : '';
	$protocol = wpsc_strleft( strtolower( $_SERVER ['SERVER_PROTOCOL'] ), '/' ) . $s;
	$port = ( $_SERVER ['SERVER_PORT'] == '80' ) ? '' : ( ':' . $_SERVER ['SERVER_PORT'] );
	return $protocol . '://' . $_SERVER ['SERVER_NAME'] . $port . $_SERVER ['REQUEST_URI'];
}

function wpsc_strleft( $s1, $s2 ) {
	$values = substr( $s1, 0, strpos( $s1, $s2 ) );
	return  $values;
}

/**
 * WPEC cart API template function
 *
 * @since
 *
 * @param
 *
 * @uses wpsc_cart
 */
function wpsc_update_shipping_single_method(){
	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( ! empty( $wpsc_cart->shipping_method ) ) {
			$wpsc_cart->update_shipping( $wpsc_cart->shipping_method, $wpsc_cart->selected_shipping_option );
		}
	}
}

/**
 * WPEC cart API template function
 *
 * @since
 *
 * @param
 *
 * @uses wpsc_cart
 */
function wpsc_update_shipping_multiple_methods(){
	global $wpsc_cart;

	if ( _wpsc_verify_global_cart_has_been_initialized( __FUNCTION__ ) ) {
		if ( ! empty( $wpsc_cart->selected_shipping_method ) ) {
			$wpsc_cart->update_shipping( $wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option );
		}
	}
}

/**
 * Get remaining product quantity
 *
 * @since
 * @access public
 *
 * @param int     $product_id                    Cart product ID.
 * @param array   $variations                    Cart item parameters.
 * @param int     $quantity                      Cart object.
 *
 * @uses  wpsc_product_stock    Filters and restricts the product cart quantity.
 */
function wpsc_get_remaining_quantity( $product_id, $variations = array(), $quantity = 1 ) {

	$stock = get_post_meta( $product_id, '_wpsc_stock', true );
	$stock = apply_filters( 'wpsc_product_stock', $stock, $product_id );
	$output = 0;

	// check to see if the product uses stock
	if ( is_numeric( $stock ) ) {

		if ( $stock > 0 ) {
			$claimed_query = new WPSC_Claimed_Stock( array( 'product_id' => $product_id ) );
			$claimed_stock = $claimed_query->get_claimed_stock_count();
			$output = $stock - $claimed_stock;
		}
	}

	return $output;
}


/**
 * Prior to using the global cart variable cart template API functions should check
 * to be sure the global cart variable has been initialized.
 *
 * @access private
 * @static
 * @since 3.8.14
 *
 * @uses wpsc_cart
 * @return boolean true if we have a valid cart, false otherwise
 *
 */
function _wpsc_verify_global_cart_has_been_initialized( $function = __FUNCTION__ ) {

	global $wpsc_cart;

	$we_have_a_valid_cart = ! empty( $wpsc_cart ) && is_a( $wpsc_cart, 'wpsc_cart' );

	if ( ! $we_have_a_valid_cart ) {
		$wpsc_cart = wpsc_get_customer_cart();
		$we_have_a_valid_cart = ! empty( $wpsc_cart ) && is_a( $wpsc_cart, 'wpsc_cart' );
	}

	// We will try to give a helpful message to the developer so that they can adjust their code
	static $already_gave_no_valid_cart_message = false;
	if ( ! $we_have_a_valid_cart && ! $already_gave_no_valid_cart_message ) {
		_wpsc_doing_it_wrong( $function, __( 'The WPeC global cart is not yet initialized, accessing global cart properties and methods will not work.', 'wpsc' ), '3.8.14' );
		$already_gave_no_valid_cart_message = true;
	}

	return $we_have_a_valid_cart;
}
