<?php
/**
 * WP eCommerce Cart and Cart Item classes
 *
 * This is the class for the WP eCommerce Cart ,
 * The Cart class handles adding, removing and adjusting items in the cart, and totaling up the cost of the items in the cart.
 *
 *
 * @package wp-e-commerce
 * @since 3.7
 * @subpackage wpsc-cart-classes
*/

/*
 * @since 3.8.14
 *
 * We are going to do a check to see if the cart template API include file has no been included. Pre 3.8.14 the
 * template API functions were in the cart.class.php file before the class definition.  In 3.8.14 the functions
 * are in a separate that is included immediately before this file.  In the future we will want to have the option
 * of changing the order and classes may be included at a different point in the init sequence.
 *
 * If we find that a key function we expect to be present does not exist it tells is that this file has been
 * improperly included directly in outside code. We will give a doing it wrong message.
 *
 * So that backwards compatibility is preserved for 3.8.14 we also require_once the cart template API file.
 *
 */
if ( ! function_exists( 'wpsc_cart_need_to_recompute_shipping_quotes' ) ) {
	_wpsc_doing_it_wrong( 'cart.class.php', __( 'As of WPeC 3.8.14, A check is made to be sure that wpsc-includes\cart.class.php is not loaded directly by outside code. WPeC internals are likely to be re-organized going forward.  When this happens code that directly includes WPeC internal modules may fail.', 'wpsc' ), '3.8.14' );
}

require_once( WPSC_FILE_PATH . '/wpsc-includes/cart-template-api.php' );

/**
 * The WPSC Cart class
 */
class wpsc_cart {
	public $delivery_country;
	public $selected_country;
	public $delivery_region;
	public $selected_region;

	public $selected_shipping_method = null;
	public $selected_shipping_option = null;
	public $selected_shipping_amount = null;

	public $coupon;
	public $tax_percentage;
	public $unique_id;
	public $errors;

	// caching of frequently used values, these are wiped when the cart is modified and then remade when needed
	public $total_tax           = null;
	public $base_shipping       = null;
	public $total_item_shipping = null;
	public $total_shipping      = null;
	public $subtotal            = null;
	public $total_price         = null;
	public $uses_shipping       = null;

	public $is_incomplete = true;

	// The cart loop variables
	public $cart_items        = array();
	public $cart_item         = null;
	public $cart_item_count   = 0;
	public $current_cart_item = -1;
	public $in_the_loop       = false;

	// The shipping method loop variables
	public $shipping_methods        = array();
	public $shipping_method         = null;
	public $shipping_method_count   = 0;
	public $current_shipping_method = -1;
	public $in_the_method_loop      = false;

	// The shipping quote loop variables
	public $shipping_quotes        = array();
	public $shipping_quote         = null;
	public $shipping_quote_count   = 0;
	public $current_shipping_quote = -1;
	public $in_the_quote_loop      = false;

	//coupon variable
	public $coupons_name   = '';
	public $coupons_amount = 0;


    function wpsc_cart() {
		$coupon = 'percentage';
		$this->update_location();
		$this->wpsc_refresh_cart_items();
		$this->unique_id = sha1( uniqid( rand(), true ) );

   		add_action( 'wpsc_visitor_location_changing', array( &$this, 'shopper_location_changing' ), 10, 2);
    }

    /*
     * Action routine to start the processing that has to happen when the customer changes
     * location.
     *
     * @since 3.8.14
     * @param array names of checnout items that hav changed since the last time the location for this customer was changed
     *
     */
  	function shopper_location_changing( $what_changed, $visitor_id ) {
  		$this->update_location();
  	}

	/**
	 * update_location method, updates the location
     * @access public
     */

	public function update_location() {

		$delivery_country = wpsc_get_customer_meta( 'shippingcountry' );
		$billing_country  = wpsc_get_customer_meta( 'billingcountry'  );
		$delivery_region  = wpsc_get_customer_meta( 'shippingregion'  );
		$billing_region   = wpsc_get_customer_meta( 'billingregion'   );

		$this->delivery_country = $delivery_country;
		$this->selected_country = $billing_country ;
		$this->delivery_region  = $delivery_region ;
		$this->selected_region  = $billing_region;

		// adding refresh item
		$this->wpsc_refresh_cart_items();
	}

	/**
    * @description: refresh all items in the cart
    *
    * @param: void
    * @return: null
    **/
	public function wpsc_refresh_cart_items() {
		global $wpsc_cart;

		if ( is_object( $wpsc_cart ) && is_object( $wpsc_cart->cart_items ) ) {
			foreach ( $wpsc_cart->cart_items as $cart_item ) {
				$cart_item->refresh_item();
			}
		}
   }

   /*
    * It os time to checkout, or at other points in the workflow and it's time to validate the shopping cart
    * call this function.
    *
    * The function will in turn execute all of the hooks that are built into WPEC, then any hooks added by
    * themes and plugins.  This means that validation rules beyond what WPEC has internally can be added as needed.
    */
   function validate_cart() {

   		/*
   		 * action: wpsc_pre_validate_cart
   		 *
   		 * Prior to validating the cart we give anyone whoe is interested a chance to do a little setup with this
   		 * wpsc_pre_validate_cart.
   		 *
   		 * This action can be used as a convenient point to change the logic that is esecuted when the 'wpsc_validate_cart'
   		 * action is fired.  For example, if you want to do different address checks based on which country is being shipped
   		 * to you can call add_action with different function paramters.  Or if you wnated to some extra validation when shipping
   		 * address is differnet than billing, perhaps a quick SOAP call to a fraud check service, you can conditionally do an
   		 * add action to your function that does the fraud check.
   		 *
   		 * @param wpsc_cart the cart object
   		 * @param current visitor id (use this to get customer meta for the current user
   		 */
   		do_action( 'wpsc_pre_validate_cart', $this, wpsc_get_current_customer_id() );

   		/*
 		 * action: wpsc_validate_cart
   		 *
   		 * Validate that the cart contents is valid.  Typically done just prior to checkout.  Most often error conditions
   		 * will be recorded to special customer meta values, but other processing can be implemented based on specific needs
   		 *
   		 * These are the customer/visitor meta values that are typically added to when errors are found:
   		 * 			checkout_error_messages
   		 * 			gateway_error_messages
   		 * 			registration_error_messages
   		 *
   		 * @param wpsc_cart the cart object
   		 * @param current visitor id (use this to get customer meta for the current user
   		 */
   		do_action( 'wpsc_validate_cart', $this, wpsc_get_current_customer_id() );
	}

	/**
	 * Clear all shipping method information for this cart
	 *
	 * @since 3.8.14
	 *
	 */
	function clear_shipping_info() {
		$this->selected_shipping_method = null;
		$this->selected_shipping_option = null;
		$this->shipping_option          = null;
		$this->shipping_method          = null;
		$this->shipping_methods         = array();
		$this->shipping_quotes          = array();
		$this->shipping_quote           = null;
		$this->shipping_method_count    = 0;
		$this->base_shipping            = null;
		$this->total_item_shipping      = null;
		$this->total_shipping           = null;
	}

	/**
	 * Does the cart have a valid shipping method selected
	 *
	 * @since 3.8.14.1
	 *
	 * @return boolean true if a valid shipping method is selected, false otherwise
	 */
	function shipping_method_selected() {

		$selected = true;

		// so the check could be written as one long expression, but thougth it better to make it more
		// readily understandable by someone who wants to see what is happening.
		// TODO:  All this logic would be unnecessary but for the lack of protected properties and
		// the legacy code that may choose to manipulate them directly avoiding class methods

		// is there a shipping method?
		if ( empty( $this->shipping_method ) ) {
			$selected = false;
		}

		// first we will check the shipping methods
		if ( $selected && ( ! is_array( $this->shipping_methods ) || empty( $this->shipping_methods ) ) ) {
			$selected = false;
		}

		// let's check the current shipping method
		if ( $selected && ( ( $this->shipping_method === null ) && ( $this->shipping_method === -1 ) && ! is_numeric( $this->shipping_method ) ) ) {
			$selected = false;
		}

		// check to be sure the shipping method name is not empty, and is also in the array
		if ( $selected && ( empty( $this->selected_shipping_method ) || ! in_array( $this->selected_shipping_method, $this->shipping_methods ) ) ) {
			$selected = false;
		}

		return $selected;
	}

	/**
	 * Does the cart have a valid shipping quote selected
	 *
	 * @since 3.8.14.1
	 *
	 * @return boolean true if a valid shipping method is selected, false otherwise
	 */
	function shipping_quote_selected() {

		$selected = true;

		// so the check could be written as one long expression, but thought it better to make it more
		// readily understandable by someone who wants to see what is happening.
		// TODO:  All this logic would be unnecessary but for the lack of protected properties and
		// the legacy code that may choose to manipulate them directly avoiding class methods

		// do we have a shipping quotes array
		if ( $selected && ( ! is_array( $this->shipping_quotes ) || empty( $this->shipping_quotes ) ) ) {
			$selected = false;
		}

		if ( ! isset( $this->shipping_quotes[$this->selected_shipping_option] )  ) {
			$selected = false;
		}


		return $selected;
	}


	/**
	 * Is all shipping method information for this cart empty
	 *
	 * @since 3.8.14
	 * @return boolean true if all the shipping fields in the cart are empty
	 */
	function shipping_info_empty() {
		return empty( $this->selected_shipping_method )
					&& empty( $this->selected_shipping_option )
							&& empty( $this->shipping_method )
								&& empty( $this->shipping_methods )
									&& empty( $this->shipping_quotes )
										&& empty( $this->shipping_quote )
											&& empty( $this->shipping_method_count )
												&& empty( $this->base_shipping )
													&& empty( $this->total_item_shipping )
														&& empty( $this->total_shipping );
	}

	/**
	 * Is shipping information calculated and ready to use
	 *
	 * @since 3.8.14
	 * @return boolean true if a recalc is necessary
	 */
	function needs_shipping_recalc() {
		global $wpsc_shipping_modules;

		if ( ! wpsc_is_shipping_enabled() ) {
			return false;
		}

		if ( $this->shipping_info_empty() && $this->uses_shipping() ) {
			return true;
		}

		$needs_shipping_recalc = false;

		$what_changed = _wpsc_visitor_location_what_changed();

		// TODO: this is where we will check the available shipping methods and see if
		// the parameters used to create the quotes have changes since the quotes where
		// created.  A function of the future shipping component

		if ( array_key_exists( 'shippingpostcode', $what_changed ) ) {
			$custom_shipping = get_option( 'custom_shipping_options' );
			foreach ( (array)$custom_shipping as $shipping ) {
				if ( isset( $wpsc_shipping_modules[$shipping]->needs_zipcode ) && $wpsc_shipping_modules[$shipping]->needs_zipcode == true ) {
					$needs_shipping_recalc = true;
					break;
				}
			}
		}

		// recalculate shipping if country changes
		if ( array_key_exists( 'shippingcountry', $what_changed ) ) {
			$needs_shipping_recalc = true;
		}

		// recalculate shipping if region changes
		if ( array_key_exists( 'shippingregion', $what_changed ) ) {
			$needs_shipping_recalc = true;
		}

		// recalculate shipping if state
		if ( array_key_exists( 'shippingstate', $what_changed ) ) {
			$needs_shipping_recalc = true;
		}

		return $needs_shipping_recalc;
	}

	/**
	 * get_shipping_rates method, gets the shipping rates
     * @access public
     */
	function get_shipping_method() {
		global $wpsc_shipping_modules;

		$this->clear_shipping_info();

		// set us up with a shipping method.
		$custom_shipping = get_option( 'custom_shipping_options' );
		if ( empty( $custom_shipping ) ) {
			$custom_shipping = array();
		} elseif ( ! is_array( $custom_shipping ) ) {
			$custom_shipping = (array) $custom_shipping;
		}

		$this->shipping_methods      = get_option( 'custom_shipping_options' );
		$this->shipping_method_count = count( $this->shipping_methods );

		$do_not_use_shipping = get_option( 'do_not_use_shipping', false );
		$ready_to_calculate_shipping = apply_filters( 'wpsc_ready_to_calculate_shipping', true, $this );

		if ( ! $do_not_use_shipping ) {

			if ( $this->shipping_method_count > 0 && $ready_to_calculate_shipping ) {
				do_action( 'wpsc_before_get_shipping_method', $this );

				$shipping_quotes = null;

				if ( $this->selected_shipping_method != null ) {

					// use the selected shipping module
					if ( is_callable( array( &$wpsc_shipping_modules[ $this->selected_shipping_method ], 'getQuote'  ) ) ) {
						$this->shipping_quotes      = $wpsc_shipping_modules[ $this->selected_shipping_method ]->getQuote();
						$this->shipping_quote_count = count( $this->shipping_quotes );
					}
				} else {

					foreach ( (array) $custom_shipping as $shipping_module ) {

						if ( empty( $wpsc_shipping_modules[ $shipping_module ] ) || ! is_callable( array( $wpsc_shipping_modules[ $shipping_module ], 'getQuote' ) ) ) {
							continue;
						}

						$raw_quotes = $wpsc_shipping_modules[ $shipping_module ]->getQuote();

						if ( empty( $raw_quotes ) || ! is_array( $raw_quotes ) ) {
							continue;
						}

						if ( is_array( $raw_quotes ) ) {
							foreach ( $raw_quotes as $key => $value ) {
								$this->shipping_quotes[$wpsc_shipping_modules[ $shipping_module ]->name. ' ' . $key] = $value;
							}
							$this->shipping_quote_count = count( $this->shipping_quotes );
						}
					}
				}

				if ( 1 == count( $this->shipping_methods ) ) {
					$this->selected_shipping_method = $this->shipping_methods[0];

					if ( 1 == count( $this->shipping_quotes ) ) {
						reset( $this->shipping_quotes );
						$this->selected_shipping_option = key( $this->shipping_quotes );
					}
				}

				do_action( 'wpsc_after_get_shipping_method', $this );
			}
		}
	}

	/**
	 * get_shipping_option method, gets the shipping option from the selected method and associated quotes
	 * @access public
	 */
	function get_shipping_option() {
		global $wpdb, $wpsc_shipping_modules;

		if ( ! isset( $wpsc_shipping_modules[$this->selected_shipping_method] ) ) {
			$wpsc_shipping_modules[$this->selected_shipping_method] = '';
		}

		if ( ( count( $this->shipping_quotes ) < 1 ) && is_callable( array( $wpsc_shipping_modules[$this->selected_shipping_method], 'getQuote'  ) ) ) {
			$this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
		}

		if ( count( $this->shipping_quotes ) < 1 ) {
			$this->selected_shipping_option = '';
		}

		if ( ( $this->shipping_quotes != null ) && ( array_search( $this->selected_shipping_option, array_keys( $this->shipping_quotes ) ) === false ) ) {

			$slice    = array_keys( array_slice( $this->shipping_quotes, 0, 1 ) );
			$selected = array_pop( $slice );

			$this->selected_shipping_option = apply_filters( 'wpsc_default_shipping_quote', $selected, $this->shipping_quotes );
		}
	}


	/**
	 * update_shipping method, updates the shipping
	 * @access public
	 */
	function update_shipping( $method, $option ) {
		global $wpdb, $wpsc_shipping_modules;

		$this->selected_shipping_method = $method;

		$this->shipping_quotes = $wpsc_shipping_modules[$method]->getQuote();

		$this->selected_shipping_option = $option;

		foreach ( $this->cart_items as $key => $cart_item ) {
			$this->cart_items[$key]->calculate_shipping();
		}

		$this->get_shipping_option();

		// reapply coupon in case it's free shipping
		if ( $this->coupons_name ) {
			$coupon = new wpsc_coupons( $this->coupons_name );
			if ( $coupon->is_free_shipping() )
				$this->apply_coupons( $coupon->calculate_discount(), $this->coupons_name );
		}
	}

	/**
	 * get_tax_rate method, gets the tax rate as a percentage, based on the selected country and region
	 * * EDIT: Replaced with WPEC Taxes - this function should probably be deprecated
	 * Note: to refresh cart items use wpsc_refresh_cart_items
	 *
	 * @access public
	 */
	function get_tax_rate() {
		$country = new WPSC_Country( get_option( 'base_country' ) );

		$country_data = WPSC_Countries::get_country( get_option( 'base_country' ), true );
		$add_tax = false;

		if ( $this->selected_country == get_option( 'base_country' ) ) {
			// Tax rules for various countries go here, if your countries tax rules
			// deviate from this, please supply code to add your region
			switch ( $this->selected_country ) {
				case 'US' : // USA!
					$tax_region = get_option( 'base_region' );
					if ( $this->selected_region == get_option( 'base_region' ) && ( get_option( 'lock_tax_to_shipping' ) != '1' ) ) {
						// if they in the state, they pay tax
						$add_tax = true;
					} else if ( $this->delivery_region == get_option( 'base_region' ) ) {

						// if they live outside the state, but are delivering to within the state, they pay tax also
						$add_tax = true;
					}
					break;

				case 'CA' : // Canada! apparently in canada, the region that you are in is used for tax purposes
					if ( $this->selected_region != null ) {
						$tax_region = $this->selected_region;
					} else {
						$tax_region = get_option( 'base_region' );
					}

					$add_tax = true;
					break;

				default : // Everywhere else!
					$tax_region = get_option( 'base_region' );
					if ( $country->has_regions() ) {
						if ( get_option( 'base_region' ) == $region ) {
							$add_tax = true;
						}
					} else {
						$add_tax = true;
					}
					break;
			}
		}

		if ( $add_tax == true ) {
			if ( $country->has_regions() ) {
				$region = $country->get_region( $tax_region );
				$tax_percentage = $region->get_tax();
			} else {
				$tax_percentage = $country->get_tax();
			}
		} else {
			// no tax charged = tax equal to 0%
			$tax_percentage = 0;
		}

		if ( $this->tax_percentage != $tax_percentage ) {
			$this->clear_cache();
			$this->tax_percentage = $tax_percentage;
			$this->wpsc_refresh_cart_items();
		}
	}

	/**
	 * Set Item method, requires a product ID and the parameters for the product
	 *
	 * @access public
	 *
	 * @param integer the product ID
	 * @param array parameters
	 * @return boolean true on sucess, false on failure
	 */
	function set_item( $product_id, $parameters, $updater = false ) {

		// default action is adding
		$add_item        = false;
		$edit_item       = false;
		$variation_check = true;

		if ( wpsc_product_has_variations( $product_id ) && is_null( $parameters['variation_values'] ) ) {
			$variation_check = false;
		}

		if ( $variation_check && $parameters['quantity'] > 0 && $this->check_remaining_quantity( $product_id, $parameters['variation_values'], $parameters['quantity'] ) ) {

			$new_cart_item = new wpsc_cart_item( $product_id, $parameters, $this );

			do_action( 'wpsc_set_cart_item', $product_id, $parameters, $this, $new_cart_item );

			$add_item = true;
			$edit_item = false;

			if ( count( $this->cart_items ) > 0 && $new_cart_item->is_donation != 1 ) {

				// loop through each cart item
				foreach ( $this->cart_items as $key => $cart_item ) {

					// compare product ids and variations.
					if ( $cart_item->product_id == $new_cart_item->product_id && $cart_item->product_variations == $new_cart_item->product_variations && $cart_item->custom_message == $new_cart_item->custom_message && $cart_item->custom_file == $new_cart_item->custom_file && $cart_item->item_meta_equal( $new_cart_item ) ) {

						// if they are the same, increment the count, and break out;
						if ( ! $updater ) {
							$this->cart_items[$key]->quantity += $new_cart_item->quantity;
						} else {
							$this->cart_items[$key]->quantity = $new_cart_item->quantity;
						}

						$this->cart_items[$key]->refresh_item();

						$add_item = false;
						$edit_item = true;

						do_action( 'wpsc_edit_item', $product_id, $parameters, $this );
					}
				}
			}

			// if we are still adding the item, add it
			if ( $add_item ) {
				$this->cart_items[] = $new_cart_item;
				do_action( 'wpsc_add_item', $product_id, $parameters, $this );
			}
		}

		// if some action was performed, return true, otherwise, return false;
		$status = false;
		if ( $add_item || $edit_item ) {
			$status = $new_cart_item;
		}

		$this->cart_item_count = count( $this->cart_items );
		$this->clear_cache();

		return $status;
	}

	/**
	 * Edit Item method
	 *
	 * @access public
	 *
	 * @param integer a cart_items key
	 * @param array an array of parameters to change
	 * @return boolean true on sucess, false on failure
	 */
	function edit_item( $key, $parameters ) {
		if ( isset( $this->cart_items[$key] ) ) {
			$product_id = $this->cart_items[$key]->product_id;
			$quantity = $parameters ['quantity'] - $this->cart_items[$key]->quantity;
			if ( $this->check_remaining_quantity( $product_id, $this->cart_items[$key]->variation_values, $quantity ) == true ) {
				foreach ( $parameters as $name => $value ) {
					$this->cart_items[$key]->$name = $value;
				}
				$this->cart_items[$key]->refresh_item();
				do_action( 'wpsc_edit_item', $product_id, $parameters, $this, $key );
				$this->clear_cache();
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * check remaining quantity method
	 * currently only checks remaining stock, in future will do claimed stock and quantity limits
	 * will need to return errors, then, rather than true/false, maybe use the wp_error object?
	 *
	 * @access public
	 *
	 * @param integer a product ID key
	 * @param array variations on the product
	 * @return boolean true on sucess, false on failure
	 */
	function check_remaining_quantity( $product_id, $variations = array(), $quantity = 1 ) {

		$stock = get_post_meta( $product_id, '_wpsc_stock', true );
		$stock = apply_filters( 'wpsc_product_stock', $stock, $product_id );

		$result = true;

		if ( is_numeric( $stock ) ) {
			$remaining_quantity = wpsc_get_remaining_quantity( $product_id, $variations, $quantity );
			if ( $remaining_quantity < $quantity ) {
				$result = false;
			}
		}
		return $result;
	}

	/**
    * get remaining quantity method
    * currently only checks remaining stock, in future will do claimed stock and quantity limits
    * will need to return errors, then, rather than true/false, maybe use the wp_error object?
    * @access public
    *
    * @param integer a product ID key
    * @param array  variations on the product
    * @return boolean true on sucess, false on failure
    */
	function get_remaining_quantity( $product_id, $variations = array(), $quantity = 1 ) {
		return wpsc_get_remaining_quantity( $product_id, $variations, $quantity );
	}

	/**
	 * Remove Item method
	 *
	 * @access public
	 *
	 * @param integer a cart_items key
	 * @return boolean true on sucess, false on failure
	 */
	function remove_item( $key ) {
		if ( isset( $this->cart_items[$key] ) ) {
			$cart_item = & $this->cart_items[$key];
			$cart_item->update_item( 0 );
			unset( $this->cart_items[$key] );
			$this->cart_items = array_values( $this->cart_items );
			$this->cart_item_count = count( $this->cart_items );
			$this->current_cart_item = - 1;
			do_action( 'wpsc_remove_item', $key, $this );

			$this->clear_cache();
			return true;
		} else {
			$this->clear_cache();
			return false;
		}
	}

	/**
	 * Empty Cart method
	 *
	 * @access public
	 *
	 *         No parameters, nothing returned
	 */
	function empty_cart( $fromwidget = true ) {
		$claimed_query = new WPSC_Claimed_Stock( array(	'cart_id' => $this->unique_id ) );
		$claimed_query->clear_claimed_stock( 0 );

		$this->cart_items        = array();
		$this->cart_item         = null;
		$this->cart_item_count   = 0;
		$this->current_cart_item = - 1;
		$this->coupons_amount    = 0;
		$this->coupons_name      = '';

		$this->clear_cache();
		$this->cleanup();
		do_action( 'wpsc_clear_cart', $this );
	}

	/**
	 * Clear Cache method, used to clear the cached totals
	 *
	 * @access public
	 *
	 *         No parameters, nothing returned
	 */
	function clear_cache() {
		$this->total_tax = null;
		$this->base_shipping = null;
		$this->total_item_shipping = null;
		$this->total_shipping = null;
		$this->subtotal = null;
		$this->total_price = null;
		$this->uses_shipping = null;
		$this->shipping_quotes = null;
		$this->get_shipping_option();
		do_action( 'wpsc_after_cart_clear_cache', $this );
	}

	/**
	 * submit_stock_claims method, changes the association of the stock claims from the cart unique to the purchase log
	 * ID
	 *
	 * @access public
	 *
	 *         No parameters, nothing returned
	 */
	function submit_stock_claims( $purchase_log_id ) {
		$claimed_query = new WPSC_Claimed_Stock( array( 'cart_id' => $this->unique_id ) );
		$claimed_query->submit_claimed_stock( $purchase_log_id );
	}

	/**
	 * cleanup method, cleans up the cart just before final destruction
	 *
	 * @access public
	 *
	 *         No parameters, nothing returned
	 */
	function cleanup() {
		$claimed_query = new WPSC_Claimed_Stock( array( 'cart_id' => $this->unique_id ) );
		$claimed_query->clear_claimed_stock( 0 );
	}

	/**
	 * Calculate total price method
	 *
	 * @access public
	 *
	 * @return float returns the price as a floating point value
	 */
	function calculate_total_price() {

		// Calculate individual component that comprise the cart total
		$subtotal = $this->calculate_subtotal();
		$shipping = $this->calculate_total_shipping();

		// Get tax only if it is included
		$tax = ( ! wpsc_tax_isincluded() ) ? $this->calculate_total_tax() : 0.00;

		// Get coupon amount, note that no matter what float precision this
		// coupon amount is, it's always saved to the database with rounded
		// value anyways
		$coupons_amount = round( $this->coupons_amount, 2 );

		// Calculate the total
		$total = ( $subtotal > $coupons_amount ) ? ( ( $subtotal - $coupons_amount ) + $shipping + $tax ) : ( $tax + $shipping );

		// Filter total
		$total = apply_filters( 'wpsc_calculate_total_price', $total, $subtotal, $shipping, $tax, $coupons_amount );

		// Set variable and return
		$this->total_price = $total;

		return $total;
	}

	/**
	 * calculate_subtotal method
	 *
	 * @access public
	 *
	 * @param boolean for_shipping = exclude items with no shipping,
	 * @return float returns the price as a floating point value
	 */
	function calculate_subtotal( $for_shipping = false ) {
		global $wpdb;
		if ( $for_shipping == true ) {
			$total = 0;
			foreach ( $this->cart_items as $key => $cart_item ) {
				if ( $cart_item->uses_shipping == 1 ) {
					$total += $cart_item->total_price;
				}
			}
		} else {
			$total = 0;
			if ( $this->subtotal == null ) {
				foreach ( $this->cart_items as $key => $cart_item ) {
					$total += $cart_item->total_price;
				}
				$this->subtotal = $total;
			} else {
				$total = $this->subtotal;
			}
		}

		return $total;
	}

	/**
	 * Return the cart items.
	 *
	 * Accept an array of arguments:
	 *
	 * - 'fields': Defaults to 'all', which returns all the fields. Otherwise, specify a field such
	 *             as 'quantity' or 'pnp' to get an array of that field only.
	 * - 'orderby': Specify a field to sort the cart items. Default to '', which means "unsorted".
	 * - 'order'  : Specify the direction of the sort, 'ASC' for ascending, 'DESC' for descending.
	 *              Defaults to 'DESC'
	 * @since  3.8.9
	 * @access public
	 * @param  array  $args Array of arguments
	 * @return array        Cart items
	 */
	public function get_items( $args = array() ) {
		$defaults = array(
				'fields'  => 'all',
				'orderby' => '',
				'order'   => 'ASC',
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		$results = $this->cart_items;

		if ( ! empty( $orderby ) ) {
			$comparison = new _WPSC_Comparison( $orderby, $order );
			usort( $results, array( $comparison, 'compare' ) );
		}

		if ( $fields != 'all' )
			$results = wp_list_pluck( $results, $fields );

		return $results;
	}

	/**
	 * calculate total tax method
	 *
	 * @access public
	 * @return float returns the price as a floating point value
	 */
	function calculate_total_tax() {
		$wpec_taxes_controller = new wpec_taxes_controller();
		$taxes_total = $wpec_taxes_controller->wpec_taxes_calculate_total();
		$this->total_tax = $taxes_total ['total'];

		if ( isset( $taxes_total ['rate'] ) )
			$this->tax_percentage = $taxes_total ['rate'];

		return apply_filters( 'wpsc_calculate_total_tax', $this->total_tax );
	}

	/**
	 * calculate_total_weight method
	 *
	 * @access public
	 *
	 * @param boolean for_shipping = exclude items with no shipping,
	 * @return float returns the price as a floating point value
	 */
	function calculate_total_weight( $for_shipping = false ) {
		global $wpdb;
		$total = '';
		if ( $for_shipping == true ) {
			foreach ( $this->cart_items as $key => $cart_item ) {
				if ( $cart_item->uses_shipping == 1 ) {
					$total += $cart_item->weight * $cart_item->quantity;
				}
			}
		} else {
			foreach ( $this->cart_items as $key => $cart_item ) {
				$total += $cart_item->weight * $cart_item->quantity;
			}
		}
		return $total;
	}

	public function get_total_shipping_quantity() {
		$total = 0;

		foreach ( $this->cart_items as $key => $cart_item ) {
			if ( $cart_item->uses_shipping )
				$total += $cart_item->quantity;
		}

		return $total;
	}

	/**
	 * get category url name method
	 *
	 * @access public
	 *
	 * @return float returns the price as a floating point value
	 */
	function get_item_categories() {
		$category_list = array();
		foreach ( $this->cart_items as $key => $cart_item ) {
			$category_list = array_merge( ( array ) $cart_item->category_list, $category_list );
		}
		return $category_list;
	}

	/**
	 * get category IDs total price method
	 *
	 * @access public
	 *
	 * @return float returns the price as a floating point value
	 */
	function get_item_category_ids() {
		$category_list = array();
		foreach ( $this->cart_items as $key => $cart_item ) {
			$category_list = array_merge( ( array ) $cart_item->category_id_list, $category_list );
		}
		return $category_list;
	}

	/**
	 * calculate_total_shipping method, gets the shipping option from the selected method and associated quotes
	 *
	 * @access public
	 * @return float returns the shipping as a floating point value
	 */
	function calculate_total_shipping() {
		$shipping_discount_value = get_option( 'shipping_discount_value' );
		$is_free_shipping_enabled = get_option( 'shipping_discount' );
		$subtotal = $this->calculate_subtotal();

		$has_free_shipping = $is_free_shipping_enabled && $shipping_discount_value > 0 && $shipping_discount_value <= $subtotal;

		if ( ! wpsc_uses_shipping() || $has_free_shipping ) {
			$total = 0;
		} else {
			$total = $this->calculate_base_shipping();
			$total += $this->calculate_per_item_shipping();
		}

		return apply_filters( 'wpsc_convert_total_shipping', $total );
	}

	/**
	 * has_total_shipping_discount method, checks whether the carts subtotal is larger or equal to the shipping discount
	 * * value
	 *
	 * @access public
	 * @return float returns true or false depending on whether the cart subtotal is larger or equal to the shipping *
	 *         discount value.
	 */
	function has_total_shipping_discount() {
		$shipping_discount_value = get_option( 'shipping_discount_value' );
		return get_option( 'shipping_discount' ) && $shipping_discount_value > 0 && $shipping_discount_value <= $this->calculate_subtotal();
	}

	/**
	 * calculate_base_shipping method, gets the shipping option from the selected method and associated quotes
	 *
	 * @access public
	 * @return float returns the shipping as a floating point value
	 */
	function calculate_base_shipping() {
		global $wpdb, $wpsc_shipping_modules;

		if ( $this->uses_shipping() ) {
			if (
					isset( $this->shipping_quotes ) && empty( $this->shipping_quotes )
						&& isset( $wpsc_shipping_modules [$this->selected_shipping_method] )
						&& is_callable( array( $wpsc_shipping_modules [$this->selected_shipping_method], 'getQuote' ) )
				) {
					$this->shipping_quotes = $wpsc_shipping_modules [$this->selected_shipping_method]->getQuote();
			}

			if ( $this->selected_shipping_option == null ) {
				$this->get_shipping_option();
			}

			$total = isset( $this->shipping_quotes [$this->selected_shipping_option] ) ? ( float ) $this->shipping_quotes [$this->selected_shipping_option] : 0;
			$this->base_shipping = $total;
		} else {

			$total = 0;
		}
		return $total;
	}

	/**
	 * calculate_per_item_shipping method, gets the shipping option from the selected method and associated quotesing
	 *
	 * @access public
	 * @return float returns the shipping as a floating point value
	 */
	function calculate_per_item_shipping( $method = null ) {
		global $wpdb, $wpsc_shipping_modules;
		$total = '';
		if ( $method == null ) {
			$method = $this->selected_shipping_method;
		}
		foreach ( ( array ) $this->cart_items as $cart_item ) {
			$total += $cart_item->calculate_shipping( $method );
		}
		if ( $method == $this->selected_shipping_method ) {
			$this->total_item_shipping = $total;
		}
		return $total;
	}

	/**
	 * uses shipping method, to determine if shipping is used.
	 *
	 * @access public
	 *         (!(get_option('shipping_discount')== 1) && (get_option('shipping_discount_value') <=
	 *         $wpsc_cart->calculate_subtotal()))
	 * @return float returns the price as a floating point value
	 */
	function uses_shipping() {
		global $wpdb;
		if ( get_option( 'do_not_use_shipping' ) ) {
			return false;
		}
		$uses_shipping = 0;
		if ( ( $this->uses_shipping == null ) ) {
			foreach ( $this->cart_items as $key => $cart_item ) {
				$uses_shipping += ( int ) $cart_item->uses_shipping;
			}
		} else {
			$uses_shipping = $this->uses_shipping;
		}

		$this->uses_shipping = $uses_shipping;

		return $uses_shipping;
	}

	/**
	 * process_as_currency method
	 *
	 * @access public
	 *
	 * @param float a price
	 * @return string a price with a currency sign
	 */
	function process_as_currency( $price ) {
		_wpsc_deprecated_function( __FUNCTION__, '3.8', 'wpsc_currency_display' );
		return wpsc_currency_display( $price );
	}

	/**
	 * save_to_db method, saves the cart to the database
	 *
	 * @access public
	 *
	 */
	function save_to_db( $purchase_log_id ) {
		global $wpdb;

		foreach ( $this->cart_items as $key => $cart_item ) {
			$cart_item->save_to_db( $purchase_log_id );
		}
	}

	public function empty_db( $purchase_log_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( 'DELETE FROM ' . WPSC_TABLE_CART_CONTENTS . ' WHERE purchaseid = %d', $purchase_log_id );
		$wpdb->query( $sql );
	}

	/**
	 * cart loop methods
	 */
	function next_cart_item() {
		$this->current_cart_item ++;
		$this->cart_item = $this->cart_items[$this->current_cart_item];
		return $this->cart_item;
	}

	function the_cart_item() {
		$this->in_the_loop = true;
		$this->cart_item = $this->next_cart_item();
		if ( $this->current_cart_item == 0 ) // loop has just started
			do_action( 'wpsc_cart_loop_start' );
	}

	function have_cart_items() {
		if ( $this->current_cart_item + 1 < $this->cart_item_count ) {
			return true;
		} else if ( $this->current_cart_item + 1 == $this->cart_item_count && $this->cart_item_count > 0 ) {
			do_action( 'wpsc_cart_loop_end' );
			// Do some cleaning up after the loop,
			$this->rewind_cart_items();
		}

		$this->in_the_loop = false;
		return false;
	}

	function rewind_cart_items() {
		$this->current_cart_item = - 1;
		if ( $this->cart_item_count > 0 ) {
			$this->cart_item = $this->cart_items[0];
		}
	}

	/**
	 * shipping_methods methods
	 */
	function next_shipping_method() {
		$this->current_shipping_method ++;
		$this->shipping_method = $this->shipping_methods [$this->current_shipping_method];
		return $this->shipping_method;
	}

	function the_shipping_method() {
		$this->shipping_method = $this->next_shipping_method();
		$this->get_shipping_quotes();
	}

	function have_shipping_methods() {
		if ( $this->current_shipping_method + 1 < $this->shipping_method_count ) {
			return true;
		} else if ( $this->current_shipping_method + 1 == $this->shipping_method_count && $this->shipping_method_count > 0 ) {
			// Do some cleaning up after the loop,
			$this->rewind_shipping_methods();
		}
		return false;
	}

	function rewind_shipping_methods() {
		$this->current_shipping_method = - 1;
		if ( $this->shipping_method_count > 0 ) {
			$this->shipping_method = $this->shipping_methods [0];
		}
	}

	/**
	 * shipping_quotes methods
	 */
	function get_shipping_quotes() {

		global $wpdb, $wpsc_shipping_modules;
		$this->shipping_quotes = array();
		if ( $this->shipping_method == null ) {
			$this->get_shipping_method();
		}
		if ( isset( $wpsc_shipping_modules [$this->shipping_method] ) && is_callable( array( $wpsc_shipping_modules [$this->shipping_method], 'getQuote' ) ) ) {
			$unprocessed_shipping_quotes = $wpsc_shipping_modules [$this->shipping_method]->getQuote();
		}
		$num = 0;
		if ( ! empty( $unprocessed_shipping_quotes ) ) {
			foreach ( ( array ) $unprocessed_shipping_quotes as $shipping_key => $shipping_value ) {
				$per_item_shipping = $this->calculate_per_item_shipping( $this->shipping_method );
				$this->shipping_quotes [$num] ['name'] = $shipping_key;
				$this->shipping_quotes [$num] ['value'] = ( float ) $shipping_value + ( float ) $per_item_shipping;
				$num ++;
			}
		}

		$this->shipping_quote_count = count( $this->shipping_quotes );
	}

	function google_shipping_quotes() {
		if ( defined( 'WPEC_LOAD_DEPRECATED' ) && WPEC_LOAD_DEPRECATED ) {
			/*
			 * Couldn't find an easy way to deprecate this function without creating a new class, so it is being
			 * deprecated in place. Google checkout is gone, so this function should not have any purpose going forward
			 * @since 3.8.14
			 */

			global $wpsc_shipping_modules;
			$shipping_quote_count = 0;
			$custom_shipping = get_option( 'custom_shipping_options' );
			$shipping_quotes = null;
			if ( $this->selected_shipping_method != null ) {
				$this->shipping_quotes = $wpsc_shipping_modules [$this->selected_shipping_method]->getQuote();
				// use the selected shipping module
				if ( is_callable( array( $wpsc_shipping_modules [$this->selected_shipping_method], 'getQuote' ) ) ) {
					$this->shipping_quotes = $wpsc_shipping_modules [$this->selected_shipping_method]->getQuote();
				}
			} else {
				// otherwise select the first one with any quotes
				foreach ( ( array ) $custom_shipping as $shipping_module ) {

					// if the shipping module does not require a weight, or requires one and the weight is larger than
					// zero
					$this->selected_shipping_method = $shipping_module;
					if ( is_callable( array( $wpsc_shipping_modules [$this->selected_shipping_method], 'getQuote' ) ) ) {

						$this->shipping_quotes = $wpsc_shipping_modules [$this->selected_shipping_method]->getQuote();
					}

					// if we have any shipping quotes, break the loop.
					if ( count( $this->shipping_quotes ) > $shipping_quote_count ) {
						break;
					}
				}
			}
		} // end load deprecated
	}

	function next_shipping_quote() {
		$this->current_shipping_quote ++;
		$this->shipping_quote = $this->shipping_quotes [$this->current_shipping_quote];
		return $this->shipping_quote;
	}

	function the_shipping_quote() {
		$this->shipping_quote = $this->next_shipping_quote();
	}

	function have_shipping_quotes() {
		if ( $this->current_shipping_quote + 1 < $this->shipping_quote_count ) {
			return true;
		} else if ( $this->current_shipping_quote + 1 == $this->shipping_quote_count && $this->shipping_quote_count > 0 ) {
			// Do some cleaning up after the loop,
			$this->rewind_shipping_quotes();
		}
		return false;
	}

	function rewind_shipping_quotes() {
		$this->current_shipping_quote = - 1;
		if ( $this->shipping_quote_count > 0 ) {
			$this->shipping_quote = $this->shipping_quotes [0];
		}
	}

	/**
	 * Applying Coupons
	 */
	function apply_coupons( $coupons_amount = '', $coupon_name = '' ) {
		$this->clear_cache();
		$this->coupons_name = $coupon_name;
		$this->coupons_amount = apply_filters( 'wpsc_coupons_amount', $coupons_amount, $coupon_name );

		$this->calculate_total_price();
		if ( $this->total_price < 0 ) {
			$this->coupons_amount += $this->total_price;
			$this->total_price = null;
			$this->calculate_total_price();
		}
	}

}

