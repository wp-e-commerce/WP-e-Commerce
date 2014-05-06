<?php

/**
* uses coupons function, no parameters
* @return boolean if true, all items in the cart do use shipping
*/
function wpsc_uses_coupons() {
	global $wpsc_coupons;

	if( empty( $wpsc_coupons ) )
		$wpsc_coupons = new wpsc_coupons();

	if( is_object( $wpsc_coupons ) )
		return $wpsc_coupons->uses_coupons();

	return false;
}
function wpsc_coupons_error(){
	global $wpsc_coupons;

	if(isset($wpsc_coupons->errormsg) && $wpsc_coupons->errormsg == true){
		return true;
	}else{
		return false;
	}
}
/**
 * Coupons class.
 *
 * Conditional coupons use an 'ALL' logic. Now admins can achieve an 'ANY' logic by adding multiple coupons.
 *
 * TODO: Implement 'ANY' logic of conditional coupons.
 *
 * @package wp-e-commerce
 * @since 3.7
 */
class wpsc_coupons {
	public $code;
	public $value;
	public $is_percentage;
	public $conditions;
	public $start_date;
	public $active;
	public $every_product ;
	public $end_date;
	public $use_once;
	public $is_used;

	public $discount;

	//for error message
	public $errormsg;

	/**
	 * Coupons constractor
	 *
	 * Instantiate a coupons object with optional variable $code;
	 *
	 * @param string code (optional) the coupon code you would like to use.
	 * @return bool True if coupon code exists, False otherwise.
	 */
	function wpsc_coupons( $code = '' ){
	    global $wpdb;

		if ( empty( $code ) ) {
			return false;
		}

		$this->code = $code;

		$coupon_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".WPSC_TABLE_COUPON_CODES."` WHERE coupon_code = %s LIMIT 1", $code ) , ARRAY_A );

		if ( empty( $coupon_data ) ) {
			$this->errormsg = true;
			wpsc_delete_customer_meta( 'coupon' );
			return false;
		} else {
			$coupon_data = array_merge( array(
				'value'         => '',
				'is-percentage' => '',
				'condition'     => '',
				'is-used'       => '',
				'active'        => '',
				'use-once'      => '',
				'start'         => '',
				'expiry'        => '',
				'every_product' => ''
			), $coupon_data );

			$this->value         = (float) $coupon_data['value'];
			$this->is_percentage = $coupon_data['is-percentage'];
			$this->conditions    = unserialize($coupon_data['condition']);
			$this->is_used       = $coupon_data['is-used'];
			$this->active        = $coupon_data['active'];
			$this->use_once      = $coupon_data['use-once'];
			$this->start_date    = $coupon_data['start'];
			$this->end_date      = $coupon_data['expiry'];
			$this->every_product = $coupon_data['every_product'];
			$this->errormsg      = false;

			return $this->validate_coupon();
		}

	}

	/**
	 * Coupons validator
	 *
	 * Checks if the current coupon is valid to use (Expiry date, Active, Used).
	 *
	 * @return bool True if coupon is not expried, used and still active, False otherwise.
	 */
	function validate_coupon() {

		$now = current_time( 'timestamp', true );

		$valid      = true;
		$start_date = strtotime( $this->start_date );
		$end_date   = strtotime( $this->end_date );

		if ( '1' != $this->active ) {
			$valid = false;
		}

		if ( '1' == $this->use_once && '1' == $this->is_used ) {
			$valid = false;
		}

		if ( $start_date && $now < $start_date ) {
			$valid = false;
		}

		if ( $end_date && $now > $end_date ) {
			$valid = false;
		}

		return apply_filters( 'wpsc_coupons_validate_coupon', $valid, $this );
	}

	/**
	 * Check whether the coupon has conditions
	 *
	 * @since  3.8.9
	 * @return boolean True if there are conditions
	 */
	public function has_conditions() {
		return ! empty( $this->conditions );
	}

	/**
	 * Check if item's name matches condition
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array  $condition Condition arguments
	 * @param  object $cart_item Cart item
	 * @return boolean
	 */
	public function _callback_condition_item_name( $condition, $cart_item ) {
		$product_data = get_post( $cart_item->product_id );

		switch( $condition['logic'] ) {
			case 'equal': //Checks if the product name is exactly the same as the condition value
				return $product_data->post_title == $condition['value'];
			break;

			case 'greater'://Checks if the product name is not the same as the condition value
				return $product_data->post_title > $condition['value'];
			break;

			case 'less'://Checks if the product name is not the same as the condition value
				return $product_data->post_title < $condition['value'];
			break;

			case 'contains'://Checks if the product name contains the condition value
				return preg_match( "/(.*)" . preg_quote( $condition['value'], '/' ) . "(.*)/", $product_data->post_title );
			break;

			case 'category'://Checks if the product category is the condition value
				$id = $product_data->ID;
				if ( $product_data->post_parent )
					$id = $product_data->post_parent;

				$category_condition = $condition['value'];
				if ( false !== strpos( $category_condition, ',' ) ) {
					$category_condition = explode( ',', $condition['value'] );
					$category_condition = array_map( 'trim', $cond );
				}
				return has_term( $category_condition, 'wpsc_product_category', $id );
			break;

			case 'not_contain'://Checks if the product name contains the condition value
				return ! preg_match( "/(.*)" . preg_quote( $condition['value'], '/' ) . "(.*)/", $product_data->post_title );
			break;

			case 'begins'://Checks if the product name begins with condition value
				return preg_match( "/^" . preg_quote( $condition['value'], '/' ) . "/", $product_data->post_title );
			break;

			case 'ends'://Checks if the product name ends with condition value
				return preg_match( "/" . preg_quote( $c['value'], '/' ) . "$/", $product_data->post_title );
			break;
		}

		return false;
	}

	/**
	 * Check whether item quantity matches condition
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array  $condition Condition arguments
	 * @param  object $cart_item Cart item
	 * @return boolean
	 */
	public function _callback_condition_item_quantity( $condition, $cart_item ) {
		$value = (int) $condition['value'];
		switch( $condition['logic'] ) {
			case 'equal': //Checks if the quantity of a product in the cart equals condition value
				return $cart_item->quantity == $value;
			break;

			case 'greater'://Checks if the quantity of a product is greater than the condition value
				return $cart_item->quantity > $value;
			break;

			case 'less'://Checks if the quantity of a product is less than the condition value
				return $cart_item->quantity < $value;
			break;

			case 'contains'://Checks if the product name contains the condition value
				return preg_match( "/(.*)" . $value . "(.*)/", $cart_item->quantity );
			break;

			case 'not_contain'://Checks if the product name contains the condition value
				return ! preg_match( "/(.*)" . $value . "(.*)/",$cart_item->quantity );
			break;

			case 'begins'://Checks if the product name begins with condition value
				return preg_match( "/^" . $value ."/", $cart_item->quantity );
			break;

			case 'ends'://Checks if the product name ends with condition value
				return preg_match( "/" . $value . "$/",$product_obj->quantity );
			break;
  		}

		return false;
	}

	/**
	 * Check whether total quantity matches condition
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array  $condition Condition arguments
	 * @param  object $cart_item Cart item
	 * @return boolean
	 */
	public function _callback_condition_total_quantity( $condition, $cart_item ) {
		$total_quantity = wpsc_cart_item_count();
		$value = (int) $condition['value'];

		switch( $condition['logic'] ) {
			case 'equal'://Checks if the quantity of products in the cart equals condition value
				return $total_quantity == $value;
			break;

			case 'greater'://Checks if the quantity in the cart is greater than the condition value
				return $total_quantity > $value;
			break;

			case 'less'://Checks if the quantity in the cart is less than the condition value
				return $total_quantity < $value;
			break;
		}

		return false;
	}

	/**
	 * Checks whether subtotal matches condition
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array  $condition Condition arguments
	 * @param  object $cart_item Cart item
	 * @return
	 */
	public function _callback_condition_subtotal_amount( $condition, $cart_item ) {
		global $wpsc_cart;
		$subtotal = $wpsc_cart->calculate_subtotal();
		$value = (float) $condition['value'];

		switch( $condition['logic'] ) {
			case 'equal'://Checks if the subtotal of products in the cart equals condition value
				return $subtotal == $value;
			break;

			case 'greater'://Checks if the subtotal of the cart is greater than the condition value
				return $subtotal > $value;
			break;

			case 'less'://Checks if the subtotal of the cart is less than the condition value
				return $subtotal < $value;
			break;
		}

		return false;
	}

	/**
	 * Filter out cart items that don't match coupon conditions
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  object $cart_item Cart item
	 * @return bool
	 */
	public function _filter_cart_item_conditions( $cart_item ) {
		global $wpsc_cart;

		$compare_logic = false;

		foreach ( $this->conditions as $condition ) {

			$callback = '_callback_condition_' . $condition['property'];

			if ( is_callable( array( $this, $callback ) ) ) {

				$result = $this->$callback( $condition, $cart_item );

			} else {

				/* This allows for a function outside of this class to override a custom condition. */
				if ( function_exists( $callback ) ) {
					$result = $callback( $condition, $cart_item );
				} else {
					/* This allows for a plugin to create a condition callback for the condition. Perk: doesn't have to follow $callback nomenclature. */
					$result = apply_filters( 'wpsc_coupon_conditions_default_callback', false, $callback, $condition, $cart_item );
				}

			}

			if ( ! $result ) {
				switch ( $condition['operator'] ) {
					case 'or':
						$compare_logic = $compare_logic || apply_filters( 'wpsc_coupon_compare_logic', false, $condition, $cart_item );
					break;
					case 'and':
						$compare_logic = $compare_logic && apply_filters( 'wpsc_coupon_compare_logic', false, $condition, $cart_item );
					break;
					default:
						$compare_logic = apply_filters( 'wpsc_coupon_compare_logic', false, $condition, $cart_item );
				}
			} else {
				switch ( $condition['operator'] ) {
					case 'or':
						$compare_logic = $compare_logic || $result;
					break;
					case 'and':
						$compare_logic = $compare_logic && $result;
					break;
					default:
						$compare_logic = $result;
				}
			}
		}

		return $compare_logic;
	}

	/**
	 * Get cart items that match coupon conditions
	 *
	 * @since  3.8.9
	 * @access private
	 * @return array Array containing eligible cart items
	 */
	public function get_eligible_items() {
		global $wpsc_cart;

		// cache product objects if we have a "item name" condition
		if ( in_array( 'item_name', $this->conditions ) ) {
			$ids = $wpsc_cart->get_items( array( 'fields' => 'product_id' ) );
			get_posts( array(
				'post_type'   => 'wpsc-product',
				'numberposts' => -1,
				'post__in'    => array( $ids ),
			) );
		}

		// sort the items by total price so that we can use this in $this->calculate_discount_conditions()
		$orderby = apply_filters( 'wpsc_coupon_select_item_orderby', 'unit_price' );
		$order   = apply_filters( 'wpsc_coupon_select_item_order'  , 'ASC'        );
		$cart_items = $wpsc_cart->get_items( array( 'orderby' => $orderby, 'order' => $order ) );

		$cart_items = array_filter( $cart_items, array( $this, '_filter_cart_item_conditions' ) );

		return $cart_items;
	}

	/**
	 * Calculate the subtotal of the items passed in as argument
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array $items Array of items
	 * @return float        Subtotal
	 */
	private function calculate_subtotal( $items ) {
		$total = 0;

		foreach ( $items as $item ) {
			$total += $item->total_price;
		}

		return $total;
	}

	/**
	 * Get the total quantity of the items passed in as argument
	 *
	 * @since  3.8.9
	 * @access private
	 * @param  array $items Array of items
	 * @return float        Subtotal
	 */
	private function get_total_quantity( $items ) {
		$total = 0;

		foreach ( $items as $item ) {
			$total += $item->quantity;
		}

		return $total;
	}

	/**
	 * Calculate the discount amount, taking coupon conditions into consideration
	 *
	 * @since  3.8.9
	 * @access private
	 * @return float Discount amount
	 */
	private function calculate_discount_conditions() {
		global $wpsc_cart;

		// findout whether the cart meet the conditions
		$items = $this->get_eligible_items();

		if ( empty( $items ) )
			return 0;

		// if this is free shipping, return the total shipping regardless of whether "Apply on all
		// products" is checked or not
		if ( $this->is_free_shipping() )
			return $this->calculate_free_shipping();

		// if  "Apply on all products" is checked, discount amount should be based on the total values
		// of eligible cart items
		if ( $this->applies_to_all_items() ) {
			if ( $this->is_percentage() ) {
				$subtotal = $this->calculate_subtotal( $items );
				$discount = $this->value * $subtotal / 100;
  			} else {
				$discount = $this->value * $this->get_total_quantity( $items );
			}
			return $discount;
		}

		// if "Apply on all products" is not checked and the coupon is percentage, the discount
		// amount should be based on the eligible cart item with lowest unit price
		if ( $this->is_percentage() ) {
			$field = apply_filters( 'wpsc_coupon_select_item_field'  , 'unit_price' );
			$item = array_shift( $items );
			return $item->$field * $this->value / 100;
		}

		// if "Apply on all products" is not checked and the coupon is a fixed value
		// return the discount value
		return $this->value;
	}

	/**
	 * Calculate discount amount without taking conditions into consideration
	 *
	 * @since  3.8.9
	 * @access private
	 * @return float Discount amount
	 */
	private function calculate_discount_without_conditions() {
		global $wpsc_cart;

		// if this is free shipping, return the total shipping regardless of whether "Apply on all
		// products" is checked or not
		if ( $this->is_free_shipping() )
			return $this->calculate_free_shipping();

		// if  "Apply on all products" is checked, discount amount should be based on the overall
		// cart
		if ( $this->applies_to_all_items() ) {
			if ( $this->is_percentage() ) {
				$subtotal = $wpsc_cart->calculate_subtotal();
				$discount = $this->value * $subtotal / 100;
			} else {
				$discount = $this->value * wpsc_cart_item_count();
  			}
			return $discount;
		}

		// if "Apply on all products" is not checked and the coupon is percentage, the discount
		// amount should be based on the cart item with lowest unit_price
		if ( $this->is_percentage() ) {
			$orderby = apply_filters( 'wpsc_coupon_select_item_orderby', 'unit_price' );
			$order   = apply_filters( 'wpsc_coupon_select_item_order'  , 'ASC'        );
			$field   = apply_filters( 'wpsc_coupon_select_item_field'  , 'unit_price' );
			$cart_items = $wpsc_cart->get_items( array( 'fields' => $field, 'orderby' => $orderby, 'order' => $order ) );
			if ( empty( $cart_items ) )
				return 0;

			$item = array_shift( $cart_items );

			return $item * $this->value / 100;
  		}

		// if "Apply on all products" is not checked and the coupon is a fixed value
		// return the discount value
		return $this->value;
	}

	/**
	 * Check whether this coupon is a "Free shipping" coupon
	 *
	 * @since  3.8.9
	 * @return boolean
	 */
	public function is_free_shipping() {
		return $this->is_percentage == '2';
	}

	/**
	 * Check whether this coupon is a "percentage" coupon
	 *
	 * @since  3.8.9
	 * @return boolean
	 */
	public function is_percentage() {
		return $this->is_percentage == '1';
	}

	/**
	 * Check whether this coupon is a fixed amount coupon
	 *
	 * @since  3.8.9
	 * @return boolean
	 */
	public function is_fixed_amount() {
		return ! $this->is_free_shipping() && ! $this->is_percentage();
	}

	/**
	 * Check whether this coupon can be applied to all items
	 *
	 * @since  3.8.9
	 * @return boolean
	 */
	public function applies_to_all_items() {
		return $this->every_product == 1;
	}

	/**
	 * Calculate the free shipping discount amount
	 * @return float
	 */
	private function calculate_free_shipping() {
		global $wpsc_cart;
		return $wpsc_cart->calculate_total_shipping();
	}


	/**
	 * Calculate the discount amount
	 *
	 * @since  3.8.9
	 * @return float
	 */
	public function calculate_discount() {
		global $wpdb, $wpsc_cart;

		$wpsc_cart->clear_cache();

		if ( $this->has_conditions() )
			return $this->calculate_discount_conditions();

		return $this->calculate_discount_without_conditions();
  	}

	/**
	 * Comparing logic with the product information
	 *
	 * Checks if the product matchs the logic
	 *
	 * @return bool True if all conditions are matched, False otherwise.
	 */
	function compare_logic($c, $product_obj) {
		global $wpdb;

		if ( 'item_name' == $c['property'] ) {
			$product_data = $wpdb->get_results( "SELECT * FROM " . $wpdb->posts . " WHERE id='{$product_obj->product_id}'" );
			$product_data = $product_data[0];

			switch( $c['logic'] ) {
				case 'equal': //Checks if the product name is exactly the same as the condition value
					if ( $product_data->post_title == $c['value'] )
						return true;
				break;

				case 'greater'://Checks if the product name is not the same as the condition value
					if ( $product_data->post_title > $c['value'] )
						return true;
				break;

				case 'less'://Checks if the product name is not the same as the condition value
					if ( $product_data->post_title < $c['value'] )
						return true;
				break;

				case 'contains'://Checks if the product name contains the condition value
					preg_match( "/(.*)" . preg_quote( $c['value'], '/' ) . "(.*)/", $product_data->post_title, $match );

					if ( ! empty( $match ) )
						return true;
				break;

				case 'category'://Checks if the product category is the condition value
					if ( $product_data->post_parent ) {
						$categories = wpsc_get_product_terms( $product_data->post_parent, 'wpsc_product_category' );
					} else {
						$categories = wpsc_get_product_terms( $product_data->ID, 'wpsc_product_category' );
					}
					foreach ( $categories as $cat ) {
						if ( strtolower( $cat->name ) == strtolower( $c['value'] ) )
							return true;
					}
				break;

				case 'not_contain'://Checks if the product name contains the condition value
					preg_match( "/(.*)" . preg_quote( $c['value'], '/' ) . "(.*)/", $product_data->post_title, $match );

					if ( empty( $match ) )
						return true;
				break;

				case 'begins'://Checks if the product name begins with condition value
					preg_match( "/^" . preg_quote( $c['value'], '/' ) . "/", $product_data->post_title, $match );
					if ( ! empty( $match ) )
						return true;
				break;

				case 'ends'://Checks if the product name ends with condition value
					preg_match( "/" . preg_quote( $c['value'], '/' ) . "$/", $product_data->post_title, $match );
					if ( ! empty( $match ) )
						return true;
				break;

				default:
				return false;
			}
		} else if ( 'item_quantity' == $c['property'] ) {

			switch( $c['logic'] ) {
				case 'equal'://Checks if the quantity of a product in the cart equals condition value
					if ( $product_obj->quantity == (int) $c['value'] )
						return true;
				break;

				case 'greater'://Checks if the quantity of a product is greater than the condition value
					if ( $product_obj->quantity > $c['value'] )
						return true;
				break;

				case 'less'://Checks if the quantity of a product is less than the condition value
					if ( $product_obj->quantity < $c['value'] )
						return true;
				break;

				case 'contains'://Checks if the product name contains the condition value
					preg_match( "/(.*)" . $c['value'] . "(.*)/", $product_obj->quantity, $match );
					if ( ! empty( $match ) )
						return true;
				break;

				case 'not_contain'://Checks if the product name contains the condition value
					preg_match("/(.*)".$c['value']."(.*)/",$product_obj->quantity, $match );
					if ( empty( $match ) )
						return true;
				break;

				case 'begins'://Checks if the product name begins with condition value
					preg_match("/^".$c['value']."/", $product_obj->quantity, $match );
					if ( ! empty( $match ) )
						return true;
				break;

				case 'ends'://Checks if the product name ends with condition value
					preg_match( "/" . $c['value'] . "$/", $product_obj->quantity, $match );
					if ( ! empty( $match ) )
						return true;
					break;
				default:
					return false;
			}
		} else if ($c['property'] == 'total_quantity'){
			$total_quantity = wpsc_cart_item_count();
			switch($c['logic']) {
				case 'equal'://Checks if the quantity of products in the cart equals condition value
				if ($total_quantity == $c['value'])
					return true;
				break;

				case 'greater'://Checks if the quantity in the cart is greater than the condition value
				if ($total_quantity > $c['value'])
					return true;
				break;

				case 'less'://Checks if the quantity in the cart is less than the condition value
				if ($total_quantity < $c['value'])
					return true;
				break;

				default:
				return false;
			}

		} else if ($c['property'] == 'subtotal_amount'){
			$subtotal = wpsc_cart_total(false);
			switch($c['logic']) {
				case 'equal'://Checks if the subtotal of products in the cart equals condition value
				if ($subtotal == $c['value'])
					return true;
				break;

				case 'greater'://Checks if the subtotal of the cart is greater than the condition value
				if ($subtotal > $c['value'])
					return true;
				break;

				case 'less'://Checks if the subtotal of the cart is less than the condition value
				if ($subtotal < $c['value']){
					return true;
				}else{
					return false;
				}

				break;

				default:
				return false;
			}
		} else {
			return apply_filters( 'wpsc_coupon_compare_logic', false, $c, $product_obj );
		}
	}

	/**
	* uses coupons function, no parameters
	* @return boolean if true, items in the cart do use coupons
	*/
	function uses_coupons() {
		global $wpdb;
		$num_active_coupons = $wpdb->get_var("SELECT COUNT(id) as c FROM `".WPSC_TABLE_COUPON_CODES."` WHERE active='1'");
		return ( $num_active_coupons > 0 );
	}


}
?>
