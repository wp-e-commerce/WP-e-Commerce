<?php

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
