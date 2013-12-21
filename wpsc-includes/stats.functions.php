<?php

add_action( 'wpsc_update_purchase_log_status', '_wpsc_action_update_product_stats', 10, 3 );

/**
 * Update product stats when a purchase log containing it changes status
 *
 * @since 3.8.13
 *
 * @param WPSC_Purchase_Log $log purchase log
 * @param int $new_status New status
 * @param int $old_status Old status
 */
function _wpsc_action_update_product_stats( $log, $new_status, $old_status ) {
	$cart_contents = $log->get_cart_contents();
	$new_status_completed = $log->is_transaction_completed();
	$old_status_completed = WPSC_Purchase_Log::is_order_status_completed( $old_status );

	// if the order went through without any trouble, then it's a positive thing!
	if ( $new_status_completed && ! $old_status_completed )
		$yay_or_boo = 1;
	// if the order is declined or invalid, sad face :(
	elseif ( ! $new_status_completed && $old_status_completed )
		$yay_or_boo = -1;

	// this dramatic mood swing affects the stats of each products in the cart
	foreach ( $cart_contents as $cart_item ) {
		$product = new WPSC_Product( $cart_item->prodid );

		$diff_sales = $yay_or_boo * (int) $cart_item->quantity;
		$diff_earnings = $yay_or_boo * (int) $cart_item->price * (int) $cart_item->quantity;

		$product->sales += $diff_sales;
		$product->earnings += $diff_earnings;

		// if this product has parent, make the same changes to the parent
		if ( $product->post->post_parent ) {
			$parent = WPCS_Product::get_instance( $product->post->post_parent );
			$parent->sales += $diff_sales;
			$parent->earnings += $diff_earnings;
		}
	}
}
