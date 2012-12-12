<?php
/**
 * WP eCommerce Cart and Cart Item classes
 *
 * These are the classes for the WP eCommerce Cart and Cart Items,
 * The Cart class handles adding, removing and adjusting items in the cart, and totaling up the cost of the items in the cart.
 * The Cart Items class handles the same, but for cart items themselves.
 *
 *
 * @package wp-e-commerce
 * @since 3.7
 * @subpackage wpsc-cart-classes
*/
/**
 * The WPSC Cart API for templates
 */

/**
* cart item count function, no parameters
* * @return integer the item countf
*/
/**
* tax is included function, no parameters
* * @return boolean true or false depending on settings>general page
*/
function wpsc_tax_isincluded() {
   //uses new wpec_taxes functionality now
   $wpec_taxes_controller = new wpec_taxes_controller();
   return $wpec_taxes_controller->wpec_taxes_isincluded();
}

function wpsc_cart_item_count() {
   global $wpsc_cart;
   $count = 0;
   foreach((array)$wpsc_cart->cart_items as $cart_item) {
      $count += $cart_item->quantity;
   }
   return $count;
}


/**
* coupon amount function, no parameters
* * @return integer the item count
*/
function wpsc_coupon_amount($forDisplay=true) {
   global $wpsc_cart;

   if($forDisplay == true) {
     $output = wpsc_currency_display($wpsc_cart->coupons_amount);
   } else {
      $output = $wpsc_cart->coupons_amount;
   }
   return $output;
}

/**
* cart total function, no parameters
* @return string the total price of the cart, with a currency sign
*/
function wpsc_cart_total( $forDisplay = true ) {
   global $wpsc_cart;

   $total = $wpsc_cart->calculate_total_price();

    if( $forDisplay )
        return wpsc_currency_display( $total );
    else
        return $total;

}

/**
 * Cart Total Widget
 *
 * Can be used to display the cart total excluding shipping, tax or coupons.
 *
 * @since 3.7.6.2
 *
 * @return string The subtotal price of the cart, with a currency sign.
 */
function wpsc_cart_total_widget( $shipping = true, $tax = true, $coupons = true ) {

   global $wpsc_cart;

   $total = $wpsc_cart->calculate_subtotal();

   if ( $shipping ) {
      $total += $wpsc_cart->calculate_total_shipping();
   }
   if ( $tax && wpsc_tax_isincluded() == false ) {
      $total += $wpsc_cart->calculate_total_tax();
   }
   if ( $coupons ) {
      $total -= $wpsc_cart->coupons_amount;
   }

   if ( get_option( 'add_plustax' ) == 1 ) {
      return wpsc_currency_display( $wpsc_cart->calculate_subtotal() );
   } else {
      return wpsc_currency_display( $total );
   }

}

/**
* nzshpcrt_overall_total_price function, no parameters
* @return string the total price of the cart, with a currency sign
*/
function nzshpcrt_overall_total_price() {

   global $wpsc_cart;

   return  $wpsc_cart->calculate_total_price();

}

/**
* cart total weight function, no parameters
* @return float the total weight of the cart
*/
function wpsc_cart_weight_total() {
   global $wpsc_cart;
   if(is_object($wpsc_cart)) {
      return $wpsc_cart->calculate_total_weight(true);
   } else {
      return 0;
   }
}

/**
* tax total function, no parameters
* @return float the total weight of the cart
*/
function wpsc_cart_tax($forDisplay = true) {
   global $wpsc_cart;
   if($forDisplay){
       if(wpsc_tax_isincluded() == false){
         return wpsc_currency_display($wpsc_cart->calculate_total_tax());
      }else{
         return '(' . wpsc_currency_display($wpsc_cart->calculate_total_tax()) . ')';
      }

   }else{
      return $wpsc_cart->calculate_total_tax();
   }
}


/**
* wpsc_cart_show_plus_postage function, no parameters
* For determining whether to show "+ Postage & tax" after the total price
* @return boolean true or false, for use with an if statement
*/
function wpsc_cart_show_plus_postage() {
   global $wpsc_cart;
   if(isset($_SESSION['wpsc_has_been_to_checkout']) && ($_SESSION['wpsc_has_been_to_checkout'] == null ) && (get_option('add_plustax') == 1)) {

      return true;

   } else {
      return false;
   }
}

/**
* uses shipping function, no parameters
* @return boolean if true, all items in the cart do use shipping
*/
function wpsc_uses_shipping() {
//This currently requires
   global $wpsc_cart;
   $shippingoptions = get_option( 'custom_shipping_options' );
   if(get_option('do_not_use_shipping')){
      return false;
   }
   if( ( ! ( ( get_option( 'shipping_discount' )== 1 ) && (get_option('shipping_discount_value') <= $wpsc_cart->calculate_subtotal()))) || ( count($shippingoptions) >= 1 && $shippingoptions[0] != '') ) {
      $status = (bool) $wpsc_cart->uses_shipping();
   } else {
     $status = false;
   }
   return $status;
}

/**
* cart has shipping function, no parameters
* @return boolean true for yes, false for no
*/
function wpsc_cart_has_shipping() {
   global $wpsc_cart;
   if($wpsc_cart->calculate_total_shipping() > 0) {
      $output = true;
   } else {
      $output = false;
   }
   return $output;
}

/**
* cart shipping function, no parameters
* @return string the total shipping of the cart, with a currency sign
*/
function wpsc_cart_shipping() {
   global $wpsc_cart;
   return apply_filters( 'wpsc_cart_shipping', wpsc_currency_display( $wpsc_cart->calculate_total_shipping() ) );
}


/**
* cart item categories function, no parameters
* @return array array of the categories
*/
function wpsc_cart_item_categories($get_ids = false) {
   global $wpsc_cart;
   if(is_object($wpsc_cart)) {
      if($get_ids == true) {
         return $wpsc_cart->get_item_category_ids();
      } else {
         return $wpsc_cart->get_item_categories();
      }
   } else {
      return array();
   }
}

/**
* have cart items function, no parameters
* @return boolean true if there are cart items left
*/
function wpsc_have_cart_items() {
   global $wpsc_cart;
   return $wpsc_cart->have_cart_items();
}

function wpsc_the_cart_item() {
   global $wpsc_cart;
   return $wpsc_cart->the_cart_item();
}



/**
* cart item key function, no parameters
* @return integer - the cart item key from the array in the cart object
*/
function wpsc_the_cart_item_key() {
   global $wpsc_cart;
   return $wpsc_cart->current_cart_item;
}

 /**
* cart item name function, no parameters
* @return string the cart item name
*/
function wpsc_cart_item_name( $context = 'display' ) {
	global $wpsc_cart;
	$product_name = apply_filters( 'wpsc_cart_item_name', $wpsc_cart->cart_item->get_title(), $wpsc_cart->cart_item->product_id );
	return $product_name;
}
 /**
* cart item quantity function, no parameters
* @return string the selected quantity of items
*/
function wpsc_cart_item_product_id() {
   global $wpsc_cart;
   return $wpsc_cart->cart_item->product_id;
}
 /**
* cart item quantity function, no parameters
* @return string the selected quantity of items
*/
function wpsc_cart_item_quantity() {
   global $wpsc_cart;
   return $wpsc_cart->cart_item->quantity;
}

function wpsc_cart_item_quantity_single_prod($id) {
   global $wpsc_cart;
   return $wpsc_cart;
}
/**
* cart item price function, no parameters
* @return string the cart item price multiplied by the quantity, with a currency sign
*/
function wpsc_cart_item_price($forDisplay = true) {
   global $wpsc_cart;
   if($forDisplay){
      return wpsc_currency_display($wpsc_cart->cart_item->total_price);
   }else{
      return $wpsc_cart->cart_item->total_price;
   }
}

/**
* cart item individual single price function, no parameters
* @return string the cart individual single item price (1 quantity)
*/
function wpsc_cart_single_item_price($forDisplay = true) {
   global $wpsc_cart;
   if($forDisplay){
      return wpsc_currency_display(($wpsc_cart->cart_item->total_price) / ($wpsc_cart->cart_item->quantity));
   }else{
      return ($wpsc_cart->cart_item->total_price / $wpsc_cart->cart_item->quantity);
   }
}

/**
* cart item shipping function, no parameters
* @return string the cart item price multiplied by the quantity, with a currency sign
*/
function wpsc_cart_item_shipping($forDisplay = true) {
   global $wpsc_cart;
   if($forDisplay){
      return wpsc_currency_display($wpsc_cart->cart_item->shipping);
   }else{
      return $wpsc_cart->cart_item->shipping;
   }
}

/**
* cart item url function, no parameters
* @return string the cart item url
*/
function wpsc_cart_item_url() {
   global $wpsc_cart;
   return apply_filters( 'wpsc_cart_item_url', $wpsc_cart->cart_item->product_url, $wpsc_cart->cart_item->product_id );
}

/**
* cart item image function
* returns the url to the to the cart item thumbnail image, if a width and height is specified, it resizes the thumbnail image to that size using the preview code (which caches the thumbnail also)
* @param integer width
* @param integer height
* @return string url to the to the cart item thumbnail image
*/
function wpsc_cart_item_image( $width = 31, $height = 31 ) {
   global $wpsc_cart;

   $cart_image = wpsc_the_product_thumbnail( $width, $height, $wpsc_cart->cart_item->product_id, "shopping_cart");

    if( is_ssl() )
		$cart_image = str_replace( 'http://', 'https://', $cart_image );

   return $cart_image;
}

/**
 * cart all shipping quotes, used for google checkout
 * returns all the quotes for a selected shipping method
 * @access public
 *
 * @return array of shipping options
 */
function wpsc_selfURL() {
   $s = empty($_SERVER["HTTPS"]) ? "" : ($_SERVER["HTTPS"] == "on") ? "s" : "";
   $protocol = wpsc_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
   $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
   return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
}

function wpsc_strleft($s1, $s2) {
   $values = substr($s1, 0, strpos($s1, $s2));
   return  $values;
}
function wpsc_google_checkout(){
   $currpage = wpsc_selfURL();
   if (array_search("google",(array)get_option('custom_gateway_options')) !== false && $currpage != get_option('shopping_cart_url')) {
      global $nzshpcrt_gateways;
      foreach($nzshpcrt_gateways as $gateway) {
         if($gateway['internalname'] == 'google' ) {
            $gateway_used = $gateway['internalname'];
            $gateway['function'](true);
         }
      }
   }
}
function wpsc_empty_google_logs(){
   global $wpdb;
   $sql = $wpdb->prepare( "DELETE FROM  `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid` = '%s'", wpsc_get_customer_meta( 'checkout_session_id' ) );
   $wpdb->query( $sql );
   wpsc_delete_customer_meta( 'checkout_session_id' );

}
/**
* have shipping methods function, no parameters
* @return boolean
*/
function wpsc_have_shipping_methods() {
   global $wpsc_cart;
   return $wpsc_cart->have_shipping_methods();
}
/**
* the shipping method function, no parameters
* @return boolean
*/
function wpsc_the_shipping_method() {
   global $wpsc_cart;
   return $wpsc_cart->the_shipping_method();
}
/**
* the shipping method name function, no parameters
* @return string shipping method name
*/
function wpsc_shipping_method_name() {
   global $wpsc_cart, $wpsc_shipping_modules;
   $name = '';
   if ( ! empty( $wpsc_cart->shipping_method ) && isset( $wpsc_shipping_modules[$wpsc_cart->shipping_method] ) )
      $name = $wpsc_shipping_modules[$wpsc_cart->shipping_method]->name;

   return apply_filters( 'wpsc_shipping_method_name', $name );
}


/**
* the shipping method  internal name function, no parameters
* @return string shipping method internal name
*/
function wpsc_shipping_method_internal_name() {
   global $wpsc_cart, $wpsc_shipping_modules;
   return $wpsc_cart->shipping_method;
}


/**
* have shipping quotes function, no parameters
* @return string the cart item url
*/
function wpsc_have_shipping_quotes() {
   global $wpsc_cart;
   return $wpsc_cart->have_shipping_quotes();
}

/**
* the shipping quote function, no parameters
* @return string the cart item url
*/
function wpsc_the_shipping_quote() {
   global $wpsc_cart;
   return $wpsc_cart->the_shipping_quote();
}

/**
* the shipping quote name function, no parameters
* @return string shipping quote name
*/
function wpsc_shipping_quote_name() {
   global $wpsc_cart;
   return apply_filters( 'wpsc_shipping_quote_name', $wpsc_cart->shipping_quote['name'] );
}

/**
* the shipping quote value function, no parameters
* @return string shipping quote value
*/
function wpsc_shipping_quote_value( $numeric = false ) {
   global $wpsc_cart;

   $value = apply_filters( 'wpsc_shipping_quote_value', $wpsc_cart->shipping_quote['value'] );

   return ( $numeric ) ? $value : wpsc_currency_display( $value );

}

/**
* the shipping quote html ID function, no parameters
* @return string shipping quote html ID
*/
function wpsc_shipping_quote_html_id() {
   global $wpsc_cart;
   return $wpsc_cart->shipping_method."_".$wpsc_cart->current_shipping_quote;
}

/**
* the shipping quote selected state function, no parameters
* @return string true or false
*/
function wpsc_shipping_quote_selected_state() {
   global $wpsc_cart;
   if(($wpsc_cart->selected_shipping_method == $wpsc_cart->shipping_method) && ($wpsc_cart->selected_shipping_option == $wpsc_cart->shipping_quote['name']) ) {
	  $wpsc_cart->selected_shipping_amount = $wpsc_cart->base_shipping;
      return "checked='checked'";
   } else {
      return "";
   }
}
function wpsc_have_morethanone_shipping_quote(){
   global $wpsc_cart, $wpsc_shipping_modules;

    // if it's fixed rate shipping, and all the prices are the same, then there aren't really options.
    if ( count($wpsc_cart->shipping_methods) == 1 && $wpsc_cart->shipping_methods[0] == 'flatrate' ) {
        $last_price = false;
        $first_quote_name = false;

		$quotes = $wpsc_shipping_modules['flatrate']->getQuote();
		if ( empty( $quotes ) )
			return false;

        foreach ((array)$quotes as $name => $quote) {
            if (!$first_quote_name) $first_quote_name = $name;
            if ($last_price !== false && $quote != $last_price) return true;
            $last_price = $quote;
        }
        $wpsc_cart->rewind_shipping_methods();

        $wpsc_cart->update_shipping('flatrate', $name);
        return false;
    }
    return true;
}

function wpsc_have_morethanone_shipping_methods_and_quotes(){
   global $wpsc_cart;

   if(count($wpsc_cart->shipping_quotes) > 1 || count($wpsc_cart->shipping_methods) > 1 || count($wpsc_cart->shipping_quotes) == $wpsc_cart->shipping_quote_count){
      return true;
   }else{
      return false;
   }
}
/**
 * Whether or not there is a valid shipping quote/option available to the customer when checking out
 *
 * @return bool
 */
function wpsc_have_shipping_quote(){
   global $wpsc_cart;
   if ($wpsc_cart->shipping_quote_count > 0 || count($wpsc_cart->shipping_quotes) > 0) {
      return true;
   }
   return false;
}
function wpsc_update_shipping_single_method(){
   global $wpsc_cart;
   if(!empty($wpsc_cart->shipping_method)) {
      $wpsc_cart->update_shipping($wpsc_cart->shipping_method, $wpsc_cart->selected_shipping_option);
   }
}
function wpsc_update_shipping_multiple_methods(){
   global $wpsc_cart;
   if(!empty($wpsc_cart->selected_shipping_method)) {
      $wpsc_cart->update_shipping($wpsc_cart->selected_shipping_method, $wpsc_cart->selected_shipping_option);
   }
}

function wpsc_get_remaining_quantity($product_id, $variations = array(), $quantity = 1) {
	return wpsc_cart::get_remaining_quantity($product_id, $variations, $quantity);
}

/**
 * The WPSC Cart class
 */
class wpsc_cart {
  var $delivery_country;
   var $selected_country;
   var $delivery_region;
   var $selected_region;

   var $selected_shipping_method = null;
   var $selected_shipping_option = null;
   var $selected_shipping_amount = null;

   var $coupon;
   var $tax_percentage;
   var $unique_id;
   var $errors;


   // caching of frequently used values, these are wiped when the cart is modified and then remade when needed
   var $total_tax = null;
   var $base_shipping = null;
   var $total_item_shipping = null;
   var $total_shipping = null;
   var $subtotal = null;
   var $total_price = null;
   var $uses_shipping = null;

   var $is_incomplete = true;

   // The cart loop variables
   var $cart_items = array();
   var $cart_item;
   var $cart_item_count = 0;
   var $current_cart_item = -1;
   var $in_the_loop = false;

   // The shipping method loop variables
   var $shipping_methods = array();
   var $shipping_method;
   var $shipping_method_count = 0;
   var $current_shipping_method = -1;
   var $in_the_method_loop = false;

   // The shipping quote loop variables
   var $shipping_quotes = array();
   var $shipping_quote;
   var $shipping_quote_count = 0;
   var $current_shipping_quote = -1;
   var $in_the_quote_loop = false;

   //coupon variable
   var $coupons_name = '';
   var $coupons_amount = 0;

  function wpsc_cart() {
    global $wpdb, $wpsc_shipping_modules;
    $coupon = 'percentage';
     $this->update_location();
     $this->wpsc_refresh_cart_items();
     $this->unique_id = sha1(uniqid(rand(), true));
     $this->get_shipping_method();
  }

  /**
   * update_location method, updates the location
   * @access public
   */
   function update_location() {

      $delivery_country = wpsc_get_customer_meta( 'shipping_country' );
      $billing_country  = wpsc_get_customer_meta( 'billing_country'  );
      $delivery_region  = wpsc_get_customer_meta( 'shipping_region'  );
      $billing_region   = wpsc_get_customer_meta( 'billing_region'   );

      if( ! $billing_country && ! $delivery_country )
         $billing_country = $delivery_country = get_option( 'base_country' );
      elseif ( ! $billing_country )
         $billing_country = $delivery_country;
      elseif ( ! $delivery_country )
         $delivery_country = $billing_country;

      if( ! $billing_region && ! $delivery_region ) {
         $billing_region = $delivery_region = get_option('base_region');
      }

      wpsc_update_customer_meta( 'shipping_country', $delivery_country );
      wpsc_update_customer_meta( 'billing_country' , $billing_country  );
      wpsc_update_customer_meta( 'shipping_region' , $delivery_region  );
      wpsc_update_customer_meta( 'billing_region'  , $billing_region   );

      $this->delivery_country = $delivery_country;
      $this->selected_country = $billing_country ;
      $this->delivery_region  = $delivery_region ;
      $this->selected_region  = $billing_region;

      //adding refresh item
      $this->wpsc_refresh_cart_items();

   }

   /**
    * @description: refresh all items in the cart
    *
    * @param: void
    * @return: null
   **/
   function wpsc_refresh_cart_items() {
      global $wpsc_cart;

      if ( is_object( $wpsc_cart ) && is_object( $wpsc_cart->cart_items ) ) {
         foreach ( $wpsc_cart->cart_items as $cart_item ) {
            $cart_item->refresh_item();
         }
      }

   }

  /**
   * get_shipping_rates method, gets the shipping rates
   * @access public
   */
  function get_shipping_method() {
      global $wpdb, $wpsc_shipping_modules;
      // Reset all the shipping data in case the destination has changed
      $this->selected_shipping_method = null;
      $this->selected_shipping_option = null;
      $this->shipping_option = null;
      $this->shipping_method = null;
      $this->shipping_methods = array();
      $this->shipping_quotes = array();
      $this->shipping_quote = null;
      $this->shipping_method_count = 0;
     // set us up with a shipping method.
     $custom_shipping = get_option('custom_shipping_options');

     $this->shipping_methods = get_option('custom_shipping_options');
     $this->shipping_method_count = count($this->shipping_methods);

      if((get_option('do_not_use_shipping') != 1) && (count($this->shipping_methods) > 0)  ) {
         $shipping_quotes = null;
         if($this->selected_shipping_method != null) {
            // use the selected shipping module
            if(is_callable(array(& $wpsc_shipping_modules[$this->selected_shipping_method], "getQuote"  ))) {
               $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
            }
         } else {
            // select the shipping quote with lowest value
            $min_value = false;
            $min_quote = '';
            $min_method = '';
            foreach ( (array) $custom_shipping as $shipping_module ) {
               if ( empty( $wpsc_shipping_modules[$shipping_module] ) || ! is_callable( array( $wpsc_shipping_modules[$shipping_module], 'getQuote' ) ) )
                  continue;

               $raw_quotes = $wpsc_shipping_modules[$shipping_module]->getQuote();
               if ( empty( $raw_quotes ) || ! is_array( $raw_quotes ) )
                  continue;
               foreach ( $raw_quotes as $name => $value ) {
                  if ( $min_value === false || $value < $min_value ) {
                     $min_value = $value;
                     $min_quote = $name;
                     $min_method = $shipping_module;
                  }
               }
            }

            if ( $min_value !== false ) {
               $this->selected_shipping_method = $min_method;
               $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
               $this->selected_shipping_option = $min_quote;
            }
         }
      }
  }

  /**
   * get_shipping_option method, gets the shipping option from the selected method and associated quotes
   * @access public
   */
  function get_shipping_option() {
    global $wpdb, $wpsc_shipping_modules;

      if (!isset($wpsc_shipping_modules[$this->selected_shipping_method])) $wpsc_shipping_modules[$this->selected_shipping_method] = '';

      if((count($this->shipping_quotes) < 1) && is_callable(array($wpsc_shipping_modules[$this->selected_shipping_method], "getQuote"  ))) {
         $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
      }


         if(count($this->shipping_quotes) < 1) {
         $this->selected_shipping_option = '';
      }

      if(($this->shipping_quotes != null) && (array_search($this->selected_shipping_option, array_keys($this->shipping_quotes)) === false)) {
         $this->selected_shipping_option = apply_filters ( 'wpsc_default_shipping_quote', array_pop( array_keys( array_slice ($this->shipping_quotes, 0, 1 ) ) ), $this->shipping_quotes );
      }
  }


  /**
   * update_shipping method, updates the shipping
   * @access public
   */
  function update_shipping($method, $option) {
    global $wpdb, $wpsc_shipping_modules;
      $this->selected_shipping_method = $method;

      $this->shipping_quotes = $wpsc_shipping_modules[$method]->getQuote();

      $this->selected_shipping_option = $option;

      foreach($this->cart_items as $key => $cart_item) {
         $this->cart_items[$key]->calculate_shipping();
      }
      $this->clear_cache();
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
   *         Note: to refresh cart items use wpsc_refresh_cart_items
   * @access public
   */
  function get_tax_rate() {
    global $wpdb;

    $country_data = $wpdb->get_row("SELECT * FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `isocode` IN('".get_option('base_country')."') LIMIT 1",ARRAY_A);
      $add_tax = false;


      if($this->selected_country == get_option('base_country')) {
        // Tax rules for various countries go here, if your countries tax rules deviate from this, please supply code to add your region
        switch($this->selected_country) {
         case 'US': // USA!
               $tax_region = get_option('base_region');
               if($this->selected_region == get_option('base_region') && (get_option('lock_tax_to_shipping') != '1')) {
                  // if they in the state, they pay tax
                  $add_tax = true;
               } else if($this->delivery_region == get_option('base_region')) {

                  // if they live outside the state, but are delivering to within the state, they pay tax also
                  $add_tax = true;
               }
         break;

         case 'CA': // Canada!
           // apparently in canada, the region that you are in is used for tax purposes
           if($this->selected_region != null) {
                  $tax_region = $this->selected_region;
               } else {
                  $tax_region = get_option('base_region');
               }
               $add_tax = true;
         break;

         default: // Everywhere else!
               $tax_region = get_option('base_region');
               if($country_data['has_regions'] == 1) {
                  if(get_option('base_region') == $region ) {
                     $add_tax = true;
                  }
               } else {
                  $add_tax = true;
               }
         break;
        }
      }

      if($add_tax == true) {
         if(($country_data['has_regions'] == 1)) {
            $region_data = $wpdb->get_row( $wpdb->prepare( "SELECT `".WPSC_TABLE_REGION_TAX."`.* FROM `".WPSC_TABLE_REGION_TAX."` WHERE `".WPSC_TABLE_REGION_TAX."`.`country_id` IN('%s') AND `".WPSC_TABLE_REGION_TAX."`.`id` IN('%s') ", $country_data['id'], $tax_region ), ARRAY_A) ;
            $tax_percentage =  $region_data['tax'];
         } else {
            $tax_percentage =  $country_data['tax'];
         }
      } else {
        // no tax charged = tax equal to 0%
         $tax_percentage = 0;
      }
      if($this->tax_percentage != $tax_percentage ) {
         $this->clear_cache();
         $this->tax_percentage = $tax_percentage;

         $this->wpsc_refresh_cart_items();
      }
  }

   /**
    * Set Item method, requires a product ID and the parameters for the product
    * @access public
    *
    * @param integer the product ID
    * @param array parameters
    * @return boolean true on sucess, false on failure
   */
  function set_item($product_id, $parameters, $updater = false) {
    // default action is adding

    $add_item = false;
    $edit_item = false;

    if(($parameters['quantity'] > 0) && ($this->check_remaining_quantity($product_id, $parameters['variation_values'], $parameters['quantity']) == true)) {
         $new_cart_item = new wpsc_cart_item($product_id,$parameters, $this);
         do_action('wpsc_set_cart_item' , $product_id , $parameters , $this);
         $add_item = true;
         $edit_item = false;
         if((count($this->cart_items) > 0) && ($new_cart_item->is_donation != 1)) {
            //loop through each cart item
            foreach($this->cart_items as $key => $cart_item) {
               // compare product ids and variations.
               if(($cart_item->product_id == $new_cart_item->product_id) &&
                 ($cart_item->product_variations == $new_cart_item->product_variations) &&
                 ($cart_item->custom_message == $new_cart_item->custom_message) &&
                 ($cart_item->custom_file == $new_cart_item->custom_file)) {
                  // if they are the same, increment the count, and break out;
                  if(!$updater){
                     $this->cart_items[$key]->quantity  += $new_cart_item->quantity;
                  } else {
                     $this->cart_items[$key]->quantity  = $new_cart_item->quantity;

                  }
                  $this->cart_items[$key]->refresh_item();
                  $add_item = false;
                  $edit_item = true;
                  do_action('wpsc_edit_item' , $product_id , $parameters , $this);

               }
            }

         }

         // if we are still adding the item, add it
         if($add_item === true) {
            $this->cart_items[] = $new_cart_item;
            do_action( 'wpsc_add_item', $product_id, $parameters, $this );
         }

      }

     // if some action was performed, return true, otherwise, return false;
     $status = false;
      if(($add_item == true) || ($edit_item == true)) {
         $status = $new_cart_item;
      }
      $this->cart_item_count = count($this->cart_items);
      $this->clear_cache();

      return $status;
   }

   /**
    * Edit Item method
    * @access public
    *
    * @param integer a cart_items key
    * @param array an array of parameters to change
    * @return boolean true on sucess, false on failure
   */
  function edit_item($key, $parameters) {
    if(isset($this->cart_items[$key])) {
         $product_id = $this->cart_items[$key]->product_id;
         $quantity = $parameters['quantity'] - $this->cart_items[$key]->quantity;
         if($this->check_remaining_quantity($product_id, $this->cart_items[$key]->variation_values, $quantity) == true) {
            foreach($parameters as $name => $value) {
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
    * @access public
    *
    * @param integer a product ID key
    * @param array  variations on the product
    * @return boolean true on sucess, false on failure
   */
   function check_remaining_quantity($product_id, $variations = array(), $quantity = 1) {
       global $wpdb;
      $stock = get_post_meta($product_id, '_wpsc_stock', true);
     $stock = apply_filters('wpsc_product_stock', $stock, $product_id);
      // check to see if the product uses stock
      if(is_numeric($stock)){
         $priceandstock_id = 0;

         if($stock > 0) {
            $claimed_stock = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(`stock_claimed`) FROM `".WPSC_TABLE_CLAIMED_STOCK."` WHERE `product_id` IN(%d) AND `variation_stock_id` IN('%d')", $product_id, $priceandstock_id  ) );
            if(($claimed_stock + $quantity) <= $stock) {
               $output = true;
            } else {
               $output = false;
            }
         } else {
            $output = false;
         }

      } else {
         $output = true;
      }
      return $output;
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
	function get_remaining_quantity($product_id, $variations = array(), $quantity = 1) {
		global $wpdb;

		$stock = get_post_meta($product_id, '_wpsc_stock', true);
		$stock = apply_filters('wpsc_product_stock', $stock, $product_id);
		$output = 0;

		// check to see if the product uses stock
		if (is_numeric( $stock ) ) {
			$priceandstock_id = 0;

			if ( $stock > 0 ) {
				$claimed_stock = $wpdb->get_var( "SELECT SUM(`stock_claimed`) FROM `" . WPSC_TABLE_CLAIMED_STOCK . "` WHERE `product_id` IN('$product_id') AND `variation_stock_id` IN('$priceandstock_id')" );
				$output = $stock - $claimed_stock;
			}
		}

		return $output;
	}


   /**
    * Remove Item method
    * @access public
    *
    * @param integer a cart_items key
    * @return boolean true on sucess, false on failure
   */
  function remove_item($key) {
    if(isset($this->cart_items[$key])) {
       $cart_item =& $this->cart_items[$key];
      $cart_item->update_item(0);
      unset($this->cart_items[$key]);
       $this->cart_items = array_values($this->cart_items);
      $this->cart_item_count = count($this->cart_items);
       $this->current_cart_item = -1;
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
    * @access public
    *
    * No parameters, nothing returned
   */
  function empty_cart($fromwidget = true) {
      global $wpdb;
      $wpdb->query($wpdb->prepare("DELETE FROM `".WPSC_TABLE_CLAIMED_STOCK."` WHERE `cart_id` IN ('%s');", $this->unique_id));

      $this->cart_items = array();
      $this->cart_item = null;
      $this->cart_item_count = 0;
      $this->current_cart_item = -1;
      $this->coupons_amount = 0;
      $this->coupons_name = '';
      $this->clear_cache();
      $this->cleanup();
      do_action( 'wpsc_clear_cart', $this );
  }



   /**
    * Clear Cache method, used to clear the cached totals
    * @access public
    *
    * No parameters, nothing returned
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
	  do_action ( 'wpsc_after_cart_clear_cache', $this );
   }

   /**
    * submit_stock_claims method, changes the association of the stock claims from the cart unique to the purchase log ID
    * @access public
    *
    * No parameters, nothing returned
   */
  function submit_stock_claims($purchase_log_id) {
    global $wpdb;
      $wpdb->query($wpdb->prepare("UPDATE `".WPSC_TABLE_CLAIMED_STOCK."` SET `cart_id` = '%d', `cart_submitted` = '1' WHERE `cart_id` IN('%s')", $purchase_log_id, $this->unique_id));
   }

      /**
    * cleanup method, cleans up the cart just before final destruction
    * @access public
    *
    * No parameters, nothing returned
   */
  function cleanup() {
    global $wpdb;
      $wpdb->query($wpdb->prepare("DELETE FROM `".WPSC_TABLE_CLAIMED_STOCK."` WHERE `cart_id` IN ('%s')", $this->unique_id));
   }

  /**
    * calculate total price method
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
      $total = ( ( $subtotal + $shipping + $tax ) > $coupons_amount ) ? ( $subtotal + $shipping + $tax - $coupons_amount ) : 0.00;

      // Filter total
      $total = apply_filters( 'wpsc_calculate_total_price', $total, $subtotal, $shipping, $tax, $coupons_amount );

      // Set variable and return
      $this->total_price = $total;

      return $total;

  }

  /**
    * calculate_subtotal method
    * @access public
    *
    * @param boolean for_shipping = exclude items with no shipping,
    * @return float returns the price as a floating point value
   */
  function calculate_subtotal($for_shipping = false) {
    global $wpdb;
    if($for_shipping == true ) {
         $total = 0;
         foreach($this->cart_items as $key => $cart_item) {
           if($cart_item->uses_shipping == 1) {
               $total += $cart_item->total_price;
            }
         }
    } else {
     $total = 0;
      if($this->subtotal == null) {
         foreach($this->cart_items as $key => $cart_item) {
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
    * @access public
    * @return float returns the price as a floating point value
   */
   function calculate_total_tax() {

      $wpec_taxes_controller = new wpec_taxes_controller();
      $taxes_total = $wpec_taxes_controller->wpec_taxes_calculate_total();
      $this->total_tax = $taxes_total['total'];

      if( isset( $taxes_total['rate'] ) )
         $this->tax_percentage = $taxes_total['rate'];

      return apply_filters( 'wpsc_calculate_total_tax', $this->total_tax );
   }



   /**
    * calculate_total_weight method
    * @access public
    *
    * @param boolean for_shipping = exclude items with no shipping,
    * @return float returns the price as a floating point value
   */
   function calculate_total_weight($for_shipping = false) {
    global $wpdb;
   $total = '';
    if($for_shipping == true ) {
         foreach($this->cart_items as $key => $cart_item) {
            if($cart_item->uses_shipping == 1) {
               $total += $cart_item->weight*$cart_item->quantity;
            }
         }
    } else {
         foreach($this->cart_items as $key => $cart_item) {
            $total += $cart_item->weight*$cart_item->quantity;
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
    * get category url name  method
    * @access public
    *
    * @return float returns the price as a floating point value
   */
  function get_item_categories() {
   $category_list = array();
      foreach($this->cart_items as $key => $cart_item) {
         $category_list = array_merge((array)$cart_item->category_list, $category_list);
      }
      return $category_list;
  }

  /**
    * get category IDs total price method
    * @access public
    *
    * @return float returns the price as a floating point value
   */
  function get_item_category_ids() {
   $category_list = array();
      foreach($this->cart_items as $key => $cart_item) {
         $category_list = array_merge((array)$cart_item->category_id_list, $category_list);
      }
      return $category_list;
  }


   /**
   * calculate_total_shipping method, gets the shipping option from the selected method and associated quotes
   * @access public
    * @return float returns the shipping as a floating point value
   */
  function calculate_total_shipping() {
   $shipping_discount_value  = get_option( 'shipping_discount_value' );
   $is_free_shipping_enabled = get_option( 'shipping_discount' );
   $subtotal                 = $this->calculate_subtotal();

   $has_free_shipping =    $is_free_shipping_enabled
                        && $shipping_discount_value > 0
                        && $shipping_discount_value <= $subtotal;

   if ( ! wpsc_uses_shipping() || $has_free_shipping ) {
      $total = 0;
   } else {
      $total = $this->calculate_base_shipping();
      $total += $this->calculate_per_item_shipping();
   }

    return apply_filters( 'wpsc_convert_total_shipping', $total );

}

   /**
   * has_total_shipping_discount method, checks whether the carts subtotal is larger or equal to the shipping discount     * value
   * @access public
   * @return float returns true or false depending on whether the cart subtotal is larger or equal to the shipping         * discount value.
   */
  function has_total_shipping_discount() {
   $shipping_discount_value = get_option( 'shipping_discount_value' );
   return get_option( 'shipping_discount' )
          && $shipping_discount_value > 0
          && $shipping_discount_value <= $this->calculate_subtotal();
  }

    /**
   * calculate_base_shipping method, gets the shipping option from the selected method and associated quotes
   * @access public
    * @return float returns the shipping as a floating point value
   */
  function calculate_base_shipping() {
    global $wpdb, $wpsc_shipping_modules;

    if($this->uses_shipping()) {
         if ( isset( $this->shipping_quotes ) && empty( $this->shipping_quotes ) && isset( $wpsc_shipping_modules[$this->selected_shipping_method] ) && is_callable( array( $wpsc_shipping_modules[$this->selected_shipping_method], "getQuote" ) ) ) {
            $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
         }
         if($this->selected_shipping_option == null){
            $this->get_shipping_option();
         }

         $total = isset( $this->shipping_quotes[$this->selected_shipping_option] ) ? (float)$this->shipping_quotes[$this->selected_shipping_option] : 0;
         $this->base_shipping = $total;
      } else {

        $total = 0;
      }
      return $total;
  }

    /**
   * calculate_per_item_shipping method, gets the shipping option from the selected method and associated quotesing
   * @access public
    * @return float returns the shipping as a floating point value
   */
  function calculate_per_item_shipping($method = null) {
    global $wpdb, $wpsc_shipping_modules;
   $total ='';
    if($method == null) {
      $method = $this->selected_shipping_method;
    }
      foreach((array)$this->cart_items as $cart_item) {
         $total += $cart_item->calculate_shipping($method);
      }
      if($method == $this->selected_shipping_method) {
         $this->total_item_shipping = $total;
      }
      return $total;
  }


	/**
	* uses shipping method, to determine if shipping is used.
	* @access public
	*  (!(get_option('shipping_discount')== 1) && (get_option('shipping_discount_value') <= $wpsc_cart->calculate_subtotal()))
	* @return float returns the price as a floating point value
	*/
	function uses_shipping() {
		global $wpdb;
		if(get_option('do_not_use_shipping')){
			return false;
		}
		$uses_shipping = 0;
		if( ( $this->uses_shipping == null ) ) {
			foreach($this->cart_items as $key => $cart_item) {
				$uses_shipping += (int)$cart_item->uses_shipping;
			}
		} else {
			$uses_shipping = $this->uses_shipping;
		}

		$this->uses_shipping = $uses_shipping;

		return $uses_shipping;
	}

   /**
    * process_as_currency method
    * @access public
    *
    * @param float a price
    * @return string a price with a currency sign
   */
   function process_as_currency($price) {
   	  _deprecated_function( __FUNCTION__, '3.8', 'wpsc_currency_display');
      return wpsc_currency_display($price);
  }

   /**
    * save_to_db method, saves the cart to the database
    * @access public
    *
   */
  function save_to_db($purchase_log_id) {
    global $wpdb;

      foreach($this->cart_items as $key => $cart_item) {
        $cart_item->save_to_db($purchase_log_id);
      }
  }

  /**
    * cart loop methods
   */


  function next_cart_item() {
      $this->current_cart_item++;
      $this->cart_item = $this->cart_items[$this->current_cart_item];
      return $this->cart_item;
   }


  function the_cart_item() {
      $this->in_the_loop = true;
      $this->cart_item = $this->next_cart_item();
      if ( $this->current_cart_item == 0 ) // loop has just started
         do_action('wpsc_cart_loop_start');
   }

   function have_cart_items() {
      if ($this->current_cart_item + 1 < $this->cart_item_count) {
         return true;
      } else if ($this->current_cart_item + 1 == $this->cart_item_count && $this->cart_item_count > 0) {
         do_action('wpsc_cart_loop_end');
         // Do some cleaning up after the loop,
         $this->rewind_cart_items();
      }

      $this->in_the_loop = false;
      return false;
   }

   function rewind_cart_items() {
      $this->current_cart_item = -1;
      if ($this->cart_item_count > 0) {
         $this->cart_item = $this->cart_items[0];
      }
   }

  /**
    * shipping_methods methods
   */
   function next_shipping_method() {
      $this->current_shipping_method++;
      $this->shipping_method = $this->shipping_methods[$this->current_shipping_method];
      return $this->shipping_method;
   }


   function the_shipping_method() {
      $this->shipping_method = $this->next_shipping_method();
      $this->get_shipping_quotes();
   }

   function have_shipping_methods() {
      if ($this->current_shipping_method + 1 < $this->shipping_method_count) {
         return true;
      } else if ($this->current_shipping_method + 1 == $this->shipping_method_count && $this->shipping_method_count > 0) {
         // Do some cleaning up after the loop,
         $this->rewind_shipping_methods();
      }
      return false;
   }

   function rewind_shipping_methods() {
      $this->current_shipping_method = -1;
      if ($this->shipping_method_count > 0) {
         $this->shipping_method = $this->shipping_methods[0];
      }
   }

   /**
    * shipping_quotes methods
   */
  function get_shipping_quotes() {
    global $wpdb, $wpsc_shipping_modules;
    $this->shipping_quotes = array();
   if($this->shipping_method == null){
      $this->get_shipping_method();
   }
      if( isset( $wpsc_shipping_modules[$this->shipping_method] ) && is_callable( array( $wpsc_shipping_modules[$this->shipping_method], "getQuote" ) ) ) {
         $unprocessed_shipping_quotes = $wpsc_shipping_modules[$this->shipping_method]->getQuote();

    }
    $num = 0;
	if ( ! empty( $unprocessed_shipping_quotes ) ) {
		foreach((array)$unprocessed_shipping_quotes as $shipping_key => $shipping_value) {
	      $per_item_shipping = $this->calculate_per_item_shipping($this->shipping_method);
	      $this->shipping_quotes[$num]['name'] = $shipping_key;
	      $this->shipping_quotes[$num]['value'] = (float)$shipping_value+(float)$per_item_shipping;
	      $num++;
	    }
	}

    $this->shipping_quote_count = count($this->shipping_quotes);
  }

  function google_shipping_quotes(){
   global $wpsc_shipping_modules;
   $shipping_quote_count = 0;
      $custom_shipping = get_option('custom_shipping_options');
         $shipping_quotes = null;
         if($this->selected_shipping_method != null) {
      $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
            // use the selected shipping module
            if ( is_callable( array( $wpsc_shipping_modules[$this->selected_shipping_method], "getQuote" ) ) ) {

               $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
            }
         } else {
            // otherwise select the first one with any quotes
            foreach((array)$custom_shipping as $shipping_module) {

               // if the shipping module does not require a weight, or requires one and the weight is larger than zero
               $this->selected_shipping_method = $shipping_module;
               if ( is_callable( array( $wpsc_shipping_modules[$this->selected_shipping_method], "getQuote" ) ) ) {

                  $this->shipping_quotes = $wpsc_shipping_modules[$this->selected_shipping_method]->getQuote();
               }
               if(count($this->shipping_quotes) >  $shipping_quote_count) { // if we have any shipping quotes, break the loop.

                  break;
               }
            }

         }

  }

   function next_shipping_quote() {
      $this->current_shipping_quote++;
      $this->shipping_quote = $this->shipping_quotes[$this->current_shipping_quote];
      return $this->shipping_quote;
   }


   function the_shipping_quote() {
      $this->shipping_quote = $this->next_shipping_quote();

   }

   function have_shipping_quotes() {
      if ($this->current_shipping_quote + 1 < $this->shipping_quote_count) {
         return true;
      } else if ($this->current_shipping_quote + 1 == $this->shipping_quote_count && $this->shipping_quote_count > 0) {
         // Do some cleaning up after the loop,
         $this->rewind_shipping_quotes();
      }
      return false;
   }

   function rewind_shipping_quotes() {
      $this->current_shipping_quote = -1;
      if ($this->shipping_quote_count > 0) {
         $this->shipping_quote = $this->shipping_quotes[0];
      }
   }

   /**
    * Applying Coupons
    */
   function apply_coupons( $coupons_amount = '', $coupon_name = '' ){
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

/**
 * The WPSC Cart Items class
 */
class wpsc_cart_item {

   // Variation Cache
   private static $variation_cache;

  // each cart item contains a reference to the cart that it is a member of
   var $cart;

  // provided values
   var $product_id;
   var $variation_values;
   var $product_variations;
   var $variation_data;
   var $quantity = 1;
   var $provided_price;


   //values from the database
   var $product_name;
   var $category_list = array();
   var $category_id_list = array();
   var $unit_price;
   var $total_price;
   var $taxable_price = 0;
   var $tax = 0;
   var $weight = 0;
   var $shipping = 0;
   var $sku = null;
   var $product_url;
   var $image_id;
   var $thumbnail_image;
   var $custom_tax_rate = null;
   var $meta = array();

   var $is_donation = false;
   var $apply_tax = true;
   var $priceandstock_id;

   // user provided values
   var $custom_message = null;
   var $custom_file = null;

   public static function refresh_variation_cache() {
      global $wpsc_cart;

      $variation_product_ids = array();

     foreach ( $wpsc_cart->get_items() as $cart_item ) {
        if ( ! empty( $cart_item->variation_values ) )
           $variation_product_ids[] = $cart_item->product_id;
     }

      if ( empty( $variation_product_ids ) )
         return;

      self::$variation_cache = wp_get_object_terms( $variation_product_ids, 'wpsc-variation', array( 'fields' => 'all_with_object_id' ) );

      foreach ( self::$variation_cache as $term ) {
         if ( ! array_key_exists( $term->object_id, self::$variation_cache ) )
            self::$variation_cache[$term->object_id] = array();

        self::$variation_cache[$term->object_id][$term->parent] = $term->name;
      }

      return self::$variation_cache;
   }

   /**
    * wpsc_cart_item constructor, requires a product ID and the parameters for the product
    * @access public
    *
    * @param integer the product ID
    * @param array parameters
    * @param objcet  the cart object
    * @return boolean true on sucess, false on failure
   */
   function wpsc_cart_item($product_id, $parameters, $cart) {
    global $wpdb;
    // still need to add the ability to limit the number of an item in the cart at once.
    // each cart item contains a reference to the cart that it is a member of, this makes that reference
    // The cart is in the cart item, which is in the cart, which is in the cart item, which is in the cart, which is in the cart item...
    $this->cart = &$cart;


    foreach($parameters as $name => $value) {
         $this->$name = $value;
    }


      $this->product_id = absint($product_id);
      // to preserve backwards compatibility, make product_variations a reference to variations.
      $this->product_variations =& $this->variation_values;



    if(($parameters['is_customisable'] == true) && ($parameters['file_data'] != null)) {
      $this->save_provided_file($this->file_data);
    }
      $this->refresh_item();

      if ( ! has_action( 'wpsc_add_item', array( 'wpsc_cart_item', 'refresh_variation_cache' ) ) )
         add_action( 'wpsc_add_item', array( 'wpsc_cart_item', 'refresh_variation_cache' ) );

   }

      /**
    * update item method, currently can only update the quantity
    * will require the parameters to update (no, you cannot change the product ID, delete the item and make a new one)
    * @access public
    *
    * @param integer quantity
    * #@param array parameters
    * @return boolean true on sucess, false on failure
   */
   function update_item($quantity) {
      $this->quantity = (int)$quantity;
      $this->refresh_item();
      $this->update_claimed_stock();


   }

   /**
    * refresh_item method, refreshes the item, calculates the prices, gets the name
    * @access public
    *
    * @return array array of monetary and other values
    */
   function refresh_item() {
   	global $wpdb, $wpsc_shipping_modules, $wpsc_cart;
   	$product_id = $this->product_id;
   	$product = get_post( $this->product_id );
   	$product_meta = get_metadata( 'post', $this->product_id );
   	$this->sku = get_post_meta( $product_id, '_wpsc_sku', true );
   	$price = get_post_meta( $product_id, '_wpsc_price', true );
   	$special_price = get_post_meta( $product_id, '_wpsc_special_price', true );
   	$product_meta = get_post_meta( $product_id, '_wpsc_product_metadata' );
   	$this->stock = get_post_meta( $product_id, '_wpsc_stock', true );
   	$this->is_donation = get_post_meta( $product_id, '_wpsc_is_donation', true );

   	if ( isset( $special_price ) && $special_price > 0 && $special_price < $price )
   		$price = $special_price;
   	$priceandstock_id = 0;
   	$this->weight = isset( $product_meta[0]['weight'] ) ? $product_meta[0]["weight"] : 0;
   	// if we are using table rate price
   	if ( isset( $product_meta[0]['table_rate_price'] ) ) {
   		$levels = $product_meta[0]['table_rate_price'];
   		if ( ! empty( $levels['quantity'] ) ) {
   			foreach((array)$levels['quantity'] as $key => $qty) {
   				if ($this->quantity >= $qty) {
   					$unit_price = $levels['table_price'][$key];
   					if ($unit_price != '')
   						$price = $unit_price;
   				}
   			}
   		}
   	}

   	$price = apply_filters( 'wpsc_price', $price, $product_id );

   	// create the string containing the product name.
   	$this->product_name = $this->get_title( 'raw' );
   	$this->priceandstock_id = $priceandstock_id;
   	$this->meta = $product_meta;

   	// change no_shipping to boolean and invert it
   	if( isset( $product_meta[0]['no_shipping'] ) && $product_meta[0]['no_shipping'] == 1)
   		$this->uses_shipping = 0 ;
   	else
   		$this->uses_shipping = 1;

   	$quantity_limited = get_product_meta($product_id, 'stock', true);
   	$this->has_limited_stock = (bool)$quantity_limited;

   	if($this->is_donation == 1)
   		$this->unit_price = (float) $this->provided_price;
   	else
   		$this->unit_price = (float) $price;

   	$this->total_price = $this->unit_price * $this->quantity;
   	if ( $product->post_parent )
   		$category_data = get_the_product_category( $product->post_parent );
   	else
   		$category_data = get_the_product_category( $product_id );

   	$this->category_list = array();
   	$this->category_id_list = array();

   	foreach( (array) $category_data as $category_row ) {
   		$this->category_list[] = $category_row->slug;
   		$this->category_id_list[] = $category_row->term_id;
   	}

   	//wpec_taxes - calculate product tax and add to total price
   	$wpec_taxes_controller = new wpec_taxes_controller();
   	if ( $wpec_taxes_controller->wpec_taxes_isincluded() && $wpec_taxes_controller->wpec_taxes_isenabled() ){
   		$taxes = $wpec_taxes_controller->wpec_taxes_calculate_included_tax($this);
   		$this->tax_rate = $taxes['rate'];
   		$this->tax = $taxes['tax'];
   	}

   	$this->product_url = wpsc_product_url( $product_id );

   	if( ! is_array( $this->variation_values ) )
   		$attach_parent = $product_id;
   	else
   		$attach_parent = $wpdb->get_var( $wpdb->prepare("SELECT post_parent FROM $wpdb->posts WHERE ID = %d", $product_id ) );


   	$att_img_args = array(
   		'post_type'   => 'attachment',
   		'numberposts' => 1,
   		'post_parent' => $attach_parent,
   		'orderby'     => 'menu_order',
   		'order'       => 'DESC'
   	);

   	$attached_image = get_posts( $att_img_args );


   	if ( $attached_image != null )
   	$this->thumbnail_image = array_shift( $attached_image );

   	$product_files = (array) get_posts( array(
   		'post_type'   => 'wpsc-product-file',
   		'post_parent' => $this->product_id,
   		'numberposts' => -1,
   		'post_status' => 'inherit'
   	) );

   	if(count($product_files) > 0) {
   		$this->file_id = null;
   		$this->is_downloadable = true;
   	} else {
   		$this->file_id = null;
   		$this->is_downloadable = false;
   	}

   	if ( isset( $this->cart->selected_shipping_method ) && isset( $wpsc_shipping_modules[$this->cart->selected_shipping_method] ) && is_callable( array( $wpsc_shipping_modules[$this->cart->selected_shipping_method], "get_item_shipping" ) ) )
   		$this->shipping = $wpsc_shipping_modules[$this->cart->selected_shipping_method]->get_item_shipping( $this );

   	 // update the claimed stock here
   	$this->update_claimed_stock();

       do_action_ref_array( 'wpsc_refresh_item', array( &$this ) );
   }

   public function get_title( $mode = 'display' ) {

      if ( ! get_post_field( 'post_parent', $this->product_id ) )
         return get_post_field( 'post_title', $this->product_id);

      if ( empty( self::$variation_cache ) )
         self::refresh_variation_cache();

      $primary_product_id = get_post_field( 'post_parent', $this->product_id );
      $title = get_post_field( 'post_title', $primary_product_id );

      if ( isset( self::$variation_cache[$this->product_id] ) ) {
         ksort( self::$variation_cache[$this->product_id] );
         $vars   = implode( ', ', self::$variation_cache[$this->product_id] );
         $title .= ' (' . $vars . ')';
      }

      $title = apply_filters( 'wpsc_cart_product_title', $title, $this->product_id );

      if ( $mode == 'display' )
         $title = apply_filters( 'the_title', $title );

      return $title;
   }

   /**
    * Calculate shipping method
    * if no parameter passed, takes the currently selected method
    * @access public
    *
    * @param string shipping method
    * @return boolean true on sucess, false on failure
   */

   function calculate_shipping($method = null) {
      global $wpdb, $wpsc_cart, $wpsc_shipping_modules;
      $shipping = '';
      if($method === null)
        $method = $this->cart->selected_shipping_method;

      if( $method && isset( $wpsc_shipping_modules[$method] ) && method_exists( $wpsc_shipping_modules[$method], "get_item_shipping"  ))
         $shipping = $wpsc_shipping_modules[$method]->get_item_shipping($this);

      if($method == $this->cart->selected_shipping_method && !empty( $shipping ) )
         $this->shipping = $shipping;

      return $shipping;
   }

   /**
    * user provided file method
    * @access public
    * @param string shipping method
    * @return boolean true on sucess, false on failure
   */

   function save_provided_file($file_data) {
    global $wpdb;
      $accepted_file_types['mime'][] = 'image/jpeg';
      $accepted_file_types['mime'][] = 'image/gif';
      $accepted_file_types['mime'][] = 'image/png';

      $accepted_file_types['mime'][] = 'image/pjpeg';  // Added for IE compatibility
      $accepted_file_types['mime'][] = 'image/x-png';  // Added for IE compatibility


      $accepted_file_types['ext'][] = 'jpeg';
      $accepted_file_types['ext'][] = 'jpg';
      $accepted_file_types['ext'][] = 'gif';
      $accepted_file_types['ext'][] = 'png';


      $can_have_uploaded_image = get_product_meta($this->product_id,'product_metadata',true);
     $product = get_post($this->product_id);
     if(0 != $product->post_parent ){
      $product = get_post($product->post_parent);
       $can_have_uploaded_image = get_product_meta($product->ID,'product_metadata',true);
     }
     $can_have_uploaded_image = $can_have_uploaded_image['can_have_uploaded_image'];
      if ('on' == $can_have_uploaded_image || 1 == $can_have_uploaded_image) {
         $name_parts = explode('.',basename($file_data['name']));
         $extension = array_pop($name_parts);

         if((   (array_search($file_data['type'], $accepted_file_types['mime']) !== false) || (get_option('wpsc_check_mime_types') == 1) ) && (array_search($extension, $accepted_file_types['ext']) !== false) ) {

           if(is_file(WPSC_USER_UPLOADS_DIR.$file_data['name'])) {
               $name_parts = explode('.',basename($file_data['name']));
               $extension = array_pop($name_parts);
               $name_base = implode('.',$name_parts);
               $file_data['name'] = null;
               $num = 2;
               //  loop till we find a free file name, first time I get to do a do loop in yonks
               do {
                  $test_name = "{$name_base}-{$num}.{$extension}";
                  if(!file_exists(WPSC_USER_UPLOADS_DIR.$test_name))
               $file_data['name'] = $test_name;
                  $num++;
               } while ($file_data['name'] == null);
           }

           $unique_id =  sha1(uniqid(rand(),true));
            if(move_uploaded_file($file_data['tmp_name'], WPSC_USER_UPLOADS_DIR.$file_data['name']) )
              $this->custom_file = array(
               'file_name' => $file_data['name'],
               'mime_type' => $file_data['type'],
               'unique_id' => $unique_id
              );

         }
      }
   }


   /**
    * update_claimed_stock method
    * Updates the claimed stock table, to prevent people from having more than the existing stock in their carts
    * @access public
    *
    * no parameters, nothing returned
   */
   function update_claimed_stock() {
      global $wpdb;

      if($this->has_limited_stock == true) {
         $current_datetime = date( "Y-m-d H:i:s" );
         $wpdb->query($wpdb->prepare("REPLACE INTO `".WPSC_TABLE_CLAIMED_STOCK."`
         ( `product_id` , `variation_stock_id` , `stock_claimed` , `last_activity` , `cart_id` )
         VALUES
         ('%d', '%d', '%s', '%s', '%s');",
         $this->product_id,
         $this->priceandstock_id,
         $this->quantity,
         $current_datetime,
         $this->cart->unique_id));
      }
   }


   /**
    * save to database method
    * @access public
    *
    * @param integer purchase log id
   */
   function save_to_db($purchase_log_id) {
      global $wpdb, $wpsc_shipping_modules;

	$method = $this->cart->selected_shipping_method;
	$shipping = 0;

	if( ! empty( $method ) && method_exists( $wpsc_shipping_modules[$method], "get_item_shipping" ) )
	    $shipping = $wpsc_shipping_modules[$this->cart->selected_shipping_method]->get_item_shipping( $this );

	if( $this->cart->has_total_shipping_discount() )
	    $shipping = 0;

	$shipping = apply_filters( 'wpsc_item_shipping_amount_db', $shipping, $this );

      //initialize tax variables
      $tax = 0;
      $tax_rate = 0;

      //wpec_taxes - calculate product tax and add to total price
      $wpec_taxes_controller = new wpec_taxes_controller();

      if($wpec_taxes_controller->wpec_taxes_isincluded() && $wpec_taxes_controller->wpec_taxes_isenabled()){
         $taxes = $wpec_taxes_controller->wpec_taxes_calculate_included_tax($this);
         $tax_rate = $taxes['rate'];
         $tax = $taxes['tax'];
      }

$wpdb->insert(
		WPSC_TABLE_CART_CONTENTS,
		array(
		    'prodid' => $this->product_id,
		    'name' => $this->get_title(),
		    'purchaseid' => $purchase_log_id,
		    'price' => $this->unit_price,
		    'pnp' => $shipping,
		    'tax_charged' => $tax,
		    'gst' => $tax_rate,
		    'quantity' => $this->quantity,
		    'donation' => $this->is_donation,
		    'no_shipping' => 0,
		    'custom_message' => $this->custom_message,
		    'files' => serialize($this->custom_file),
		    'meta' => NULL
		),
		array(
		    '%d',
		    '%s',
		    '%d',
		    '%f',
		    '%f',
		    '%f',
		    '%f',
		    '%s',
		    '%d',
		    '%d',
		    '%s',
		    '%s',
		    '%s'
		)
	      );

      $cart_id = $wpdb->get_var( "SELECT " . $wpdb->insert_id . " AS `id` FROM `".WPSC_TABLE_CART_CONTENTS."` LIMIT 1");

      wpsc_update_cartmeta($cart_id, 'sku', $this->sku);

       $downloads = get_option('max_downloads');
      if($this->is_downloadable == true) {

         $product_files = (array)get_posts(array(
            'post_type' => 'wpsc-product-file',
            'post_parent' => $this->product_id,
            'numberposts' => -1,
            'post_status' => 'inherit'
         ));
         foreach($product_files as $file){
            // if the file is downloadable, check that the file is real
            $unique_id = sha1(uniqid(mt_rand(), true));

	    $wpdb->insert(
			WPSC_TABLE_DOWNLOAD_STATUS,
			array(
			    'product_id' => $this->product_id,
			    'fileid' => $file->ID,
			    'purchid' => $purchase_log_id,
			    'cartid' => $cart_id,
			    'uniqueid' => $unique_id,
			    'downloads' => $downloads,
			    'active' => 0,
			    'datetime' => date( 'Y-m-d H:i:s' )
			),
			array(
			   '%d',
			   '%d',
			   '%d',
			   '%d',
			   '%s',
			   '%s',
			   '%d',
			   '%s',
		       )
		   );

            $download_id = $wpdb->get_var( "SELECT " . $wpdb->insert_id . " AS `id` FROM `".WPSC_TABLE_DOWNLOAD_STATUS."` LIMIT 1");
            wpsc_update_meta($download_id, '_is_legacy', 'false', 'wpsc_downloads');
         }

      }

      do_action('wpsc_save_cart_item', $cart_id, $this->product_id);
   }

}


/**
 * Comparison object that helps with ordering cart items
 *
 * @since  3.8.9
 * @access private
 */
class _WPSC_Comparison
{
 private $orderby = '';
 private $order = 'ASC';

   /**
    * Constructor
    *
    * @param string $orderby Field to order by
    * @param string $order   Order direction, defaults to ASC for ascending.
    */
 public function __construct( $orderby, $order = 'ASC' ) {
    $this->orderby = $orderby;
    $this->order = $order;
 }

   /**
    * This compare method can be passed into usort when sorting an array of object
    *
    * @since  3.8.9
    *
    * @param  object|array $a
    * @param  object|array $b
    * @return int          See usort() documentation for the meaning of this return value.
    */
 public function compare( $a, $b ) {
    // cast them all to object, just in case any of them is an array
    $a = (object) $a;
    $b = (object) $b;

    $key = $this->orderby;

    $val_a = isset( $a->$key ) ? $a->$key : 0;
    $val_b = isset( $b->$key ) ? $b->$key : 0;

    $diff = $val_a - $val_b;

    if ( $this->order != 'ASC' )
       $diff = $diff * -1;

    return $diff;
 }
}
