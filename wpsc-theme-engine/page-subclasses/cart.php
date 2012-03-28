<?php

class WPSC_Page_Cart extends WPSC_Page
{
	protected $template_name = 'wpsc-cart';

	public function __construct( $callback ) {
		global $wp_query;
		parent::__construct( $callback );
		$wp_query->wpsc_is_cart = true;
		$this->message_collection->add( __( 'Your subtotal amount does not yet include shipping and handling, tax or discount offers, which will be reflected at Checkout.', 'wpsc' ), 'info' );
	}

	public function _callback_add_to_cart() {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], "wpsc-add-to-cart-{$_REQUEST['product_id']}" ) )
			wp_die( __( 'Request expired. Please try adding the item to your cart again.', 'wpsc' ) );

		extract( $_REQUEST, EXTR_SKIP );

		$defaults = array(
			'variation_values' => array(),
			'quantity'         => 1,
			'provided_price'   => null,
			'comment'          => null,
			'time_requested'   => null,
			'custom_message'   => null,
			'file_data'        => null,
			'is_customisable'  => false,
			'meta'             => null,
		);

		$provided_parameters = array();
		$product_id = apply_filters( 'wpsc_add_to_cart_product_id', (int) $product_id );

		if ( ! empty( $wpsc_product_variations ) ) {
			foreach ( $wpsc_product_variations as $key => $variation )
				$provided_parameters['variation_values'][(int)$key] = (int)$variation;

			$variation_product_id = wpsc_get_child_object_in_terms( $product_id, $provided_parameters['variation_values'], 'wpsc-variation' );
			if ( $variation_product_id > 0 )
				$product_id = $variation_product_id;
		}

		if ( ! empty( $quantity ) )
			$provided_parameters['quantity'] = (int) $quantity;

		if ( ! empty( $is_customisable ) ) {
			$provided_parameters['is_customisable'] = true;

			if ( isset( $custom_text ) )
				$provided_parameters['custom_message'] = $custom_text;

			if ( isset( $_FILES['custom_file'] ) )
				$provided_parameters['file_data'] = $_FILES['custom_file'];
		}

		if ( isset( $donation_price ) && (float) $donation_price > 0 )
			$provided_parameters['provided_price'] = (float) $donation_price;

		$parameters = array_merge( $defaults, $provided_parameters );

		if ( $parameters['quantity'] <= 0 ) {
			$this->message_collection->add( __( 'Sorry, but the quantity you just entered is not valid. Please try again.', 'wpsc' ), 'error' );
			return;
		}

		$remaining_quantity = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'] );

		$product = get_post( $product_id );
		if ( $remaining_quantity !== true ) {
			if ( $remaining_quantity <= 0 ) {
				$message = __( 'Sorry, the product "%s" is out of stock.', 'wpsc' );
				$this->message_collection->add( sprintf( $message, $product->post_title ), 'error' );
				return;
			} elseif ( $remaining_quantity < $parameters['quantity'] ) {
				$message = __( 'Sorry, but the quantity you just specified is larger than the available stock. There are only %d of the item in stock.', 'wpsc' );
				$this->message_collection->add( sprintf( $message, $remaining_quantity ), 'error' );
				return;
			}
		}

		if ( $wpsc_cart->set_item( $product_id, $parameters ) ) {
			$message = sprintf( __( 'You just added %s to your cart.', 'wpsc' ), $product->post_title );
			$this->message_collection->add( $message, 'success' );
		} else {
			$this->message_collection->add( __( 'An unknown error just occured. Please contact the shop administrator.', 'wpsc' ), 'error' );
		}
	}

	public function _callback_update_quantity() {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], 'wpsc-cart-update' ) )
			wp_die( __( 'Request expired. Please try updating the items in your cart again.', 'wpsc' ) );

		$changed = 0;
		$has_errors = false;
		extract( $_REQUEST, EXTR_SKIP );

		foreach ( $wpsc_cart->cart_items as $key => &$item ) {
			if ( isset( $quantity[$key] ) && $quantity[$key] != $item->quantity ) {
				$product = get_post( $item->product_id );

				if ( ! is_numeric( $quantity[$key] ) ) {
					$message = sprintf( __( 'Invalid quantity for %s.', 'wpsc' ), $product->post_title );
					$this->message_collection->add( $message, 'error' );
					continue;
				}

				if ( $quantity[$key] > $item->quantity ) {
					$remaining_quantity = $wpsc_cart->get_remaining_quantity( $item->product_id, $item->variation_values );

					if ( $remaining_quantity !== true ) {
						if ( $remaining_quantity <= 0 ) {
							$message = __( "Sorry, all the remaining stocks of %s have been claimed. Now you can only checkout with the current number of that item in your cart.", 'wpsc' );
							$this->message_collection->add( sprintf( $message, $product->post_title ), 'error' );
							$has_errors = true;
							continue;
						} elseif ( $remaining_quantity < $item->quantity ) {
							$message = __( 'Sorry, but the quantity you just specified is larger than the available stock of %s. Besides the current number of that product in your cart, you can only add %d more.', 'wpsc' );
							$this->message_collection->add( sprintf( $message, $product->post_title, $remaining_quantity ), 'error' );
							$has_errors = true;
							continue;
						}
					}
				}

				$item->quantity = $quantity[$key];
				$item->refresh_item();
				$changed ++;
			}
		}

		if ( ! isset( $_POST['update_quantity'] ) && ! $has_errors ) {
			wp_redirect( wpsc_get_checkout_url() );
			exit;
		}

		if ( $changed ) {
			$message = _n( 'You just successfully updated the quantity for %d item.', 'You just successfully updated the quantity for %d items.', $changed, 'wpsc' );
			$this->message_collection->add( sprintf( $message, $changed ), 'success' );
		}
	}

	public function main() {
		$GLOBALS['wpsc_checkout'] = new wpsc_checkout();
		$GLOBALS['wpsc_gateway'] = new wpsc_gateways();
		if( isset( $_SESSION['coupon_numbers'] ) )
		   $GLOBALS['wpsc_coupons'] = new wpsc_coupons( $_SESSION['coupon_numbers'] );
	}

	public function clear() {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], 'wpsc-clear-cart' ) )
			wp_die( __( 'Request expired. Please go back and try clearing the cart again.', 'wpsc' ) );

		$wpsc_cart->clear();
		$this->message_collection->add( __( 'Shopping cart emptied.', 'wpsc' ) );
	}

	public function remove( $key ) {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], "wpsc-remove-cart-item-{$key}" ) )
			wp_die( __( 'Request expired. Please go back and try removing the cart item again.', 'wpsc' ) );

		$wpsc_cart->remove_item( $key );
		$this->message_collection->add( __( 'Item removed.', 'wpsc' ) );
	}
}