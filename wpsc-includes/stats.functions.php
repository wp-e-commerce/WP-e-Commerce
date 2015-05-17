<?php

add_action( 'wpsc_update_purchase_log_status', '_wpsc_action_update_product_stats', 10, 4 );

/**
 * Update product stats when a purchase log containing it changes status
 *
 * @since 3.8.13
 *
 * @param int               $log_id     Purchase Log ID
 * @param int               $new_status New status
 * @param int               $old_status Old status
 * @param WPSC_Purchase_Log $log        Purchase Log
 */
function _wpsc_action_update_product_stats( $log_id, $new_status, $old_status, $log ) {
	$cart_contents        = $log->get_cart_contents();
	$new_status_completed = $log->is_transaction_completed();
	$old_status_completed = WPSC_Purchase_Log::is_order_status_completed( $old_status );

	if ( $new_status_completed && ! $old_status_completed ) {
		// if the order went through without any trouble, then it's a positive thing!
		$yay_or_boo = 1;
	} elseif ( ! $new_status_completed && $old_status_completed ) {
		// if the order is declined or invalid, sad face :(
		$yay_or_boo = -1;
	} else {
		// Not one of the above options then we will be indifferent
		$yay_or_boo = 0;
	}

	// this dramatic mood swing affects the stats of each products in the cart
	foreach ( $cart_contents as $cart_item ) {
		$product = new WPSC_Product( $cart_item->prodid );

		if ( $product->exists() ) {

			$diff_sales    = $yay_or_boo * (int) $cart_item->quantity;
			$diff_earnings = $yay_or_boo * (int) $cart_item->price * (int) $cart_item->quantity;

			$product->sales    += $diff_sales;
			$product->earnings += $diff_earnings;

			// if this product has parent, make the same changes to the parent
			if ( $product->post->post_parent ) {
				$parent = WPSC_Product::get_instance( $product->post->post_parent );
				$parent->sales    += $diff_sales;
				$parent->earnings += $diff_earnings;
			}
		}
	}
}
