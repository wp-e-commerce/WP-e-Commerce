<?php

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
	if ( ! empty( $wpsc_cart->shipping_method ) && isset( $wpsc_shipping_modules[$wpsc_cart->shipping_method] ) ) {
		$name = $wpsc_shipping_modules[$wpsc_cart->shipping_method]->getName();
	}
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

/**
 * Is there more than one quote option for the user to choose from
 *
 * @access public
 *
 * @return boolean
 */
function wpsc_have_morethanone_shipping_quote(){
	global $wpsc_cart, $wpsc_shipping_modules;

	// if it's fixed rate shipping, and all the prices are the same, then there aren't really options.
	if ( count( $wpsc_cart->shipping_methods ) == 1 && $wpsc_cart->shipping_methods[0] == 'flatrate' ) {
		$last_price       = false;
		$first_quote_name = false;

		$quotes = $wpsc_shipping_modules['flatrate']->getQuote();

		if ( empty( $quotes ) ) {
			return false;
		}

		foreach ( (array) $quotes as $name => $quote ) {
			if ( ! $first_quote_name ) {
				$first_quote_name = $name;
			}

			if ( $last_price !== false && $quote != $last_price ) {
				return true;
			}

			$last_price = $quote;
		}

		$wpsc_cart->rewind_shipping_methods();

		$wpsc_cart->update_shipping( 'flatrate', $name );
		return false;
	}

	return count( $wpsc_cart->shipping_quotes ) > 1;
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