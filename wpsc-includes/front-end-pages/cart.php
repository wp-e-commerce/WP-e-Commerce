<?php

class WPSC_Front_End_Page_Cart extends WPSC_Front_End_Page
{
	protected $template_name = 'cart';
	protected $messages = array(
		'success' => array(),
		'error'   => array(),
	);

	public function __construct( $callback ) {
		global $wp_query;

		parent::__construct( $callback );

		$wp_query->wpsc_is_cart = true;
	}

	public function process_add_to_cart() {
		global $wpsc_cart;

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
			$this->set_message( __( 'Sorry, but the quantity you just entered is not valid. Please try again.', 'wpsc' ), 'error' );
			return;
		}

		$remaining_quantity = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'] );

		$product = get_post( $product_id );
		if ( $remaining_quantity !== true ) {
			if ( $remaining_quantity <= 0 ) {
				$message = __( 'Sorry, the product "%s" is out of stock.', 'wpsc' );
				$this->set_message( sprintf( $message, $product->post_title ), 'error' );
				return;
			} elseif ( $remaining_quantity < $parameters['quantity'] ) {
				$message = __( 'Sorry, but the quantity you just specified is larger than the available stock. There are only %d of the item in stock.', 'wpsc' );
				$this->set_message( sprintf( $message, $remaining_quantity ), 'error' );
				return;
			}
		}

		if ( $wpsc_cart->set_item( $product_id, $parameters ) ) {
			$message = sprintf( __( 'You just added %s to your cart.', 'wpsc' ), $product->post_title );
			$this->set_message( $message, 'success' );
		} else {
			$this->set_message( __( 'An unknown error just occured. Please contact the shop administrator.', 'wpsc' ), 'error' );
		}
	}

	public function process_update_quantity() {
		global $wpsc_cart;
		$changed = 0;
		extract( $_REQUEST, EXTR_SKIP );

		foreach ( $wpsc_cart->cart_items as $key => &$item ) {
			if ( isset( $quantity[$key] ) && $quantity[$key] != $item->quantity ) {
				$product = get_post( $item->product_id );

				if ( $quantity[$key] > $item->quantity ) {
					$remaining_quantity = $wpsc_cart->get_remaining_quantity( $item->product_id, $item->variation_values );

					if ( $remaining_quantity !== true ) {
						if ( $remaining_quantity <= 0 ) {
							$message = __( "Sorry, all the remaining stocks of %s have been claimed. Now you can only checkout with the current number of that item in your cart.", 'wpsc' );
							$this->set_message( sprintf( $message, $product->post_title ), 'error' );
							continue;
						} elseif ( $remaining_quantity < $item->quantity ) {
							$message = __( 'Sorry, but the quantity you just specified is larger than the available stock of %s. Besides the current number of that product in your cart, you can only add %d more.', 'wpsc' );
							$this->set_message( sprintf( $message, $product->post_title, $remaining_quantity ), 'error' );
							continue;
						}
					}
				}

				$item->quantity = $quantity[$key];
				$item->refresh_item();
				$changed ++;
			}
		}

		if ( $changed ) {
			$message = __( 'You just successfully updated the quantity for %d items.', 'wpsc' );
			$this->set_message( sprintf( $message, $changed ), 'success' );
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
		$wpsc_cart->clear();
	}

	public function delete( $key ) {
		global $wpsc_cart;
		$wpsc_cart->remove_item( $key );
	}
}