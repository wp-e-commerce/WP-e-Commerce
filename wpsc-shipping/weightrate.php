<?php
/**
 * shipping/weightrate.php
 *
 * @package WP e-Commerce
 */


class weightrate {
	var $internal_name, $name;

	/**
	 *
	 *
	 * @return unknown
	 */
	function weightrate() {
		$this->internal_name = "weightrate";
		$this->name="Weight Rate";
		$this->is_external=false;
		return true;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function getName() {
		return $this->name;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function getInternalName() {
		return $this->internal_name;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function getForm() {
		$output = "";
		$output.="<tr><th>".__('Total weight <br />(<abbr alt="You must enter the weight here in pounds, regardless of what you used on your products" title="You must enter the weight here in pounds, regardless of what you used on your products">in pounds</abbr>)', 'wpsc')."</th><th>".__('Shipping Price', 'wpsc')."</th></tr>";

		$layers = get_option("weight_rate_layers");

		if ($layers != '') {

			foreach ($layers as $key => $shipping) {

				$output.="<tr class='rate_row'><td >";
				$output .="<i style='color: grey;'>".__('If weight is ', 'wpsc')."</i><input type='text' value='$key' name='weight_layer[]'size='4'><i style='color: grey;'>".__(' and above', 'wpsc')."</i></td><td>".wpsc_get_currency_symbol()."<input type='text' value='".esc_attr($shipping)."' name='weight_shipping[]' size='4'>&nbsp;&nbsp;<a href='#' class='delete_button' >".__('Delete', 'wpsc')."</a></td></tr>";

			}

		}

		$output.="<input type='hidden' name='checkpage' value='weight'>";
		$output.="<tr class='addlayer'><td colspan='2'>Layers: <a style='cursor:pointer;' id='addweightlayer' >Add Layer</a></td></tr>";

		return $output;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function submit_form() {

		if (!isset($_POST['weight_layer'])) {
			$_POST['weight_layer'] = '';
		}
		if (!isset($_POST['weight_shipping'])) {
			$_POST['weight_shipping'] = '';
		}
		$new_layer = '';
		$layers = (array)$_POST['weight_layer'];
		$shippings = (array)$_POST['weight_shipping'];

		if ( !empty($shippings) ) {

			foreach ($shippings as $key => $price) {

				if ( empty($price) ) {

					unset($shippings[$key]);
					unset($layers[$key]);

				} else {

					$new_layer[$layers[$key]] = $price;

				}

			}

		}

		if ($_POST['checkpage'] == 'weight' && !empty($new_layer))
			update_option('weight_rate_layers', $new_layer);
		return true;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function getQuote() {

		global $wpdb, $wpsc_cart;

		$weight = wpsc_cart_weight_total();
		if (is_object($wpsc_cart)) {
			$cart_total = $wpsc_cart->calculate_subtotal(true);
		}

		$layers = get_option('weight_rate_layers');

		if ($layers != '') {

			krsort($layers);

			foreach ($layers as $key => $shipping) {

				if ($weight >= (float)$key) {

					if (stristr($shipping, '%')) {

						// Shipping should be a % of the cart total
						$shipping = str_replace('%', '', $shipping);
						$shipping_amount = $cart_total * ( $shipping / 100 );
						return array("Weight Rate"=>(float)$shipping_amount);

					} else {

						return array("Weight Rate"=>$shipping);

					}

				}

			}

			$shipping = array_shift($layers);

			if (stristr($shipping, '%')) {
				$shipping = str_replace('%', '', $shipping);
				$shipping_amount = $price * ( $shipping / 100 );
			} else {
				$shipping_amount = $shipping;
			}

			return array("Weight Rate"=>(float)$shipping_amount);
		}

	}

	/**
	 *
	 *
	 * @param unknown $cart_item (reference)
	 * @return unknown
	 */
	function get_item_shipping(&$cart_item) {

		global $wpdb, $wpsc_cart;

		$unit_price = $cart_item->unit_price;
		$quantity = $cart_item->quantity;
		$weight = $cart_item->weight;
		$product_id = $cart_item->product_id;

		$uses_billing_address = false;
		foreach ($cart_item->category_id_list as $category_id) {
			$uses_billing_address = (bool)wpsc_get_categorymeta($category_id, 'uses_billing_address');
			if ($uses_billing_address === true) {
				break; /// just one true value is sufficient
			}
		}

		if (is_numeric($product_id) && (get_option('do_not_use_shipping') != 1)) {
			if ($uses_billing_address == true) {
				$country_code = $wpsc_cart->selected_country;
			} else {
				$country_code = $wpsc_cart->delivery_country;
			}

			if ($cart_item->uses_shipping == true) {
				//if the item has shipping
				$additional_shipping = '';
				if (isset($cart_item->meta[0]['shipping'])) {
					$shipping_values = $cart_item->meta[0]['shipping'];
				}
				if (isset($shipping_values['local']) && $country_code == get_option('base_country')) {
					$additional_shipping = $shipping_values['local'];
				} else {
					if (isset($shipping_values['international'])) {
						$additional_shipping = $shipping_values['international'];
					}
				}
				$shipping = $quantity * $additional_shipping;
			} else {
				//if the item does not have shipping
				$shipping = 0;
			}
		} else {
			//if the item is invalid or all items do not have shipping
			$shipping = 0;
		}
		return $shipping;
	}
}


$weightrate = new weightrate();
$wpsc_shipping_modules[$weightrate->getInternalName()] = $weightrate;
