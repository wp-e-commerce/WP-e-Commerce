<?php

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

   return apply_filters( 'wpsc_cart_item_image', $cart_image, $wpsc_cart->cart_item->product_id );
}

