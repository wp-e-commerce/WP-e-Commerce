<?php
class WPSC_Controller_Cart extends WPSC_Controller {
	public function __construct() {
		parent::__construct();
		require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table.php' );
		require_once( WPSC_TE_V2_CLASSES_PATH . '/cart-item-table-form.php' );
		$this->view  = 'cart';
		wpsc_enqueue_script( 'wpsc-products' );
		$this->title = wpsc_get_cart_title();
		$this->init_cart_item_table();
	}

	private function init_cart_item_table() {
		$cart_item_table = WPSC_Cart_Item_Table_Form::get_instance();
		$cart_item_table->show_tax = false;
		$cart_item_table->show_shipping = false;
		$cart_item_table->show_total = false;
	}

	public function add( $product_id ) {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], "wpsc-add-to-cart-{$product_id}" ) ) {
			wp_die( __( 'Request expired. Please try adding the item to your cart again.', 'wp-e-commerce' ) );
		}

		extract( $_REQUEST, EXTR_SKIP );

		$defaults = array(
			'variation_values' => array(),
			'quantity'         => 1,
			'provided_price'   => null,
			'comment'          => null,
			'time_requested'   => null,
			'custom_message'   => '',
			'file_data'        => null,
			'is_customisable'  => false,
			'meta'             => null,
		);

		$provided_parameters = array();
		$product_id          = apply_filters( 'wpsc_add_to_cart_product_id', (int) $product_id );

		if ( ! empty( $wpsc_product_variations ) ) {

			foreach ( $wpsc_product_variations as $key => $variation ) {
				$provided_parameters['variation_values'][ (int) $key ] = (int) $variation;
			}

			$variation_product_id = wpsc_get_child_object_in_terms( $product_id, $provided_parameters['variation_values'], 'wpsc-variation' );

			if ( $variation_product_id > 0 ) {
				$product_id = $variation_product_id;
			} else {
				$this->message_collection->add( __( 'This variation combination is no longer available.  Please choose a different combination.', 'wp-e-commerce' ), 'error', 'main', 'flash' );
				wp_safe_redirect( wpsc_get_cart_url() );
				exit;
			}
		}

		if ( ! empty( $quantity ) ) {
			$provided_parameters['quantity'] = (int) $quantity;
		}

		if ( ! empty( $is_customisable ) ) {
			$provided_parameters['is_customisable'] = true;

			if ( isset( $custom_text ) ) {
				$provided_parameters['custom_message'] = $custom_text;
			}

			if ( isset( $_FILES['custom_file'] ) ) {
				$provided_parameters['file_data'] = $_FILES['custom_file'];
			}
		}

		if ( isset( $donation_price ) && (float) $donation_price > 0 ) {
			$provided_parameters['provided_price'] = (float) $donation_price;
		}

		$parameters = array_merge( $defaults, $provided_parameters );

		if ( $parameters['quantity'] <= 0 ) {
			$this->message_collection->add( __( 'Sorry, but the quantity you just entered is not valid. Please try again.', 'wp-e-commerce' ), 'error', 'main', 'flash' );
			return;
		}

		$product = apply_filters( 'wpsc_add_to_cart_product_object', get_post( $product_id, OBJECT, 'display' ) );

		$stock = get_post_meta( $product_id, '_wpsc_stock', true );

		$remaining_quantity = $wpsc_cart->get_remaining_quantity( $product_id, $parameters['variation_values'] );

		if ( $stock !== '' && $remaining_quantity !== true ) {
			if ( $remaining_quantity <= 0 ) {
				$message = apply_filters( 'wpsc_add_to_cart_out_of_stock_message', __( 'Sorry, the product "%s" is out of stock.', 'wp-e-commerce' ) );
				$this->message_collection->add( sprintf( $message, $product->post_title ), 'error', 'main', 'flash' );
				wp_safe_redirect( wpsc_get_cart_url() );
				exit;
			} elseif ( $remaining_quantity < $parameters['quantity'] ) {
				$message = __( 'Sorry, but the quantity you just specified is larger than the available stock. There are only %d of the item in stock.', 'wp-e-commerce' );
				$this->message_collection->add( sprintf( $message, $remaining_quantity ), 'error', 'main', 'flash' );
				wp_safe_redirect( wpsc_get_cart_url() );
				exit;
			}
		}

		if ( wpsc_product_has_variations( $product_id ) && is_null( $parameters['variation_values'] ) ) {
			$message = apply_filters( 'wpsc_add_to_cart_variation_missing_message', sprintf( __( 'This product has several options to choose from.<br /><br /><a href="%s" style="display:inline; float:none; margin: 0; padding: 0;">Visit the product page</a> to select options.', 'wp-e-commerce' ), esc_url( get_permalink( $product_id ) ) ), $product_id );
			$this->message_collection->add( sprintf( $message, $product->post_title ), 'error', 'main', 'flash' );
			wp_safe_redirect( wpsc_get_cart_url() );
			exit;
		}

		if ( $wpsc_cart->set_item( $product_id, $parameters ) ) {
			$message = sprintf( __( 'You just added %s to your cart.', 'wp-e-commerce' ), $product->post_title );
			$this->message_collection->add( $message, 'success', 'main', 'flash' );
			wp_safe_redirect( wpsc_get_cart_url() );
			exit;
		} else {
			$this->message_collection->add( __( 'An unknown error just occurred. Please contact the shop administrator.', 'wp-e-commerce' ), 'error', 'main', 'flash' );
			wp_safe_redirect( wpsc_get_cart_url() );
			exit;
		}

	}

	public function _callback_update_quantity() {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], 'wpsc-cart-update' ) ) {
			wp_die( __( 'Request expired. Please try updating the items in your cart again.', 'wp-e-commerce' ) );
		}

		$changed    = 0;
		$has_errors = false;

		extract( $_REQUEST, EXTR_SKIP );

		foreach ( $wpsc_cart->cart_items as $key => &$item ) {
			if ( isset( $quantity[ $key ] ) && $quantity[ $key ] != $item->quantity ) {

				$product = get_post( $item->product_id );

				if ( ! is_numeric( $quantity[ $key ] ) ) {
					$message = sprintf( __( 'Invalid quantity for %s.', 'wp-e-commerce' ), $product->post_title );
					$this->message_collection->add( $message, 'error' );
					continue;
				}

				if ( $quantity[ $key ] < wpsc_product_min_cart_quantity( $item->product_id ) ) {
					$message = __( 'Sorry, but the quantity you just specified is lower than the minimum allowed quantity of %s. You must purchase at least %s at a time.', 'wp-e-commerce' );
					$this->message_collection->add( sprintf( $message, $product->post_title, number_format_i18n( wpsc_product_min_cart_quantity( $item->product_id ) ) ), 'error' );
					$has_errors = true;
					continue;
				}

				if ( $quantity[ $key ] > $item->quantity ) {
					$product = WPSC_Product::get_instance( $item->product_id );

					if ( ! $product->has_stock ) {
						$message = __( "Sorry, all the remaining stock of %s has been claimed. You can only checkout with the current quantity in your cart.", 'wp-e-commerce' );
						$this->message_collection->add( sprintf( $message, $product->post->post_title ), 'error' );
						$has_errors = true;
						continue;
					}

					if ( $product->has_limited_stock && $product->stock < $item->quantity ) {
						$message = __( 'Sorry, but the quantity you just specified is larger than the available stock of %s. Besides the current quantity of that product in your cart, you can only add %d more.', 'wp-e-commerce' );
						$this->message_collection->add( sprintf( $message, $product->post->post_title, $product->stock ), 'error' );
						$has_errors = true;
						continue;
					}

					if ( $quantity[ $key ] > wpsc_product_max_cart_quantity( $item->product_id ) ) {
						$message = __( 'Sorry, but the quantity you just specified is larger than the maximum allowed quantity of %s. You may only purchase %s at a time.', 'wp-e-commerce' );
						$this->message_collection->add( sprintf( $message, $product->post->post_title, number_format_i18n( wpsc_product_max_cart_quantity( $item->product_id ) ) ), 'error' );
						$has_errors = true;
						continue;
					}
				}

				$item->quantity = $quantity[ $key ];
				$item->refresh_item();
				$changed++;
			}
		}

		$wpsc_cart->clear_cache();

		if ( ! isset( $_POST['update_quantity'] ) && ! $has_errors ) {
			wp_redirect( wpsc_get_checkout_url() );
			exit;
		}

		if ( $changed ) {
			$message = _n( 'You just successfully updated the quantity for %d item.', 'You just successfully updated the quantity for %d items.', $changed, 'wp-e-commerce' );
			$this->message_collection->add( sprintf( $message, $changed ), 'success' );
		}
	}

	public function index() {

		if ( isset( $_POST['apply_coupon'] ) && empty( $_POST['coupon_code'] ) ) {
			$this->_callback_remove_coupon();
		}

		if ( isset( $_POST['apply_coupon'] ) && isset( $_POST['coupon_code'] ) ) {
			$this->_callback_apply_coupon();
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'update_quantity' ) {
			$this->_callback_update_quantity();
		}
	}

	public function _callback_remove_coupon() {
		global $wpsc_cart;

		$wpsc_cart->coupons_amount = 0;
		$wpsc_cart->coupons_name = '';
		wpsc_delete_customer_meta( 'coupon' );

		$this->message_collection->add( __( 'Coupon removed.', 'wp-e-commerce' ), 'error', 'main', 'flash' );

		wp_safe_redirect( wpsc_get_cart_url() );
		exit;
	}

	public function _callback_apply_coupon() {
		global $wpsc_coupons;

		wpsc_coupon_price( $_POST['coupon_code'] );

		$coupon = wpsc_get_customer_meta( 'coupon' );

		if ( ! empty( $coupon ) ) {
			$wpsc_coupons = new wpsc_coupons( $coupon );
		}

		if ( $wpsc_coupons->errormsg || empty( $_POST['coupon_code'] ) ) {
			$this->message_collection->add( __( 'Coupon not applied.', 'wp-e-commerce' ), 'error', 'main', 'flash' );
		} else {
			$this->message_collection->add( __( 'Coupon applied.', 'wp-e-commerce' ), 'success', 'main', 'flash' );
		}

		wp_safe_redirect( wpsc_get_cart_url() );
		exit;

	}

	public function clear() {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], 'wpsc-clear-cart' ) ) {
			wp_die( __( 'Request expired. Please go back and try clearing the cart again.', 'wp-e-commerce' ) );
		}

		$wpsc_cart->empty_cart();
		$this->message_collection->add( __( 'Shopping cart emptied.', 'wp-e-commerce' ) );
	}

	public function remove( $key ) {
		global $wpsc_cart;

		if ( ! wp_verify_nonce( $_REQUEST['_wp_nonce'], "wpsc-remove-cart-item-{$key}" ) ) {
			wp_die( __( 'Request expired. Please go back and try removing the cart item again.', 'wp-e-commerce' ) );
		}

		$wpsc_cart->remove_item( $key );
		$this->message_collection->add( __( 'Item removed.', 'wp-e-commerce' ), 'success', 'main', 'flash' );

		wp_safe_redirect( wpsc_get_cart_url() );
		exit;
	}

}
