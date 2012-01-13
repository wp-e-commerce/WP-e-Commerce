<?php
/**
 * shipping/flatrate.php
 *
 * @package WP e-Commerce
 */


class flatrate {
	var $internal_name, $name;

	/**
	 *
	 *
	 * @return unknown
	 */
	function flatrate() {
		$this->internal_name = "flatrate";
		$this->name="Flat Rate";
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

		$shipping = get_option('flat_rates');
		$output = "<tr><td colspan='2'>" . __('If you do not wish to ship to a particular region, leave the field blank. To offer free shipping to a region, enter 0.', 'wpsc') . "</td>";
		$output .= "<tr><td colspan='1'><strong>Base Local</strong></td>";

		switch (get_option('base_country')) {
		case 'NZ':
			$output .= "<tr class='rate_row'><td>South Island</td><td>$<input type='text' size='4' name='shipping[southisland]' value='".esc_attr($shipping['southisland'])."'></td></tr>";
			$output .= "<tr class='rate_row'><td>North Island</td><td>$<input type='text' size='4' name='shipping[northisland]'	value='".esc_attr($shipping['northisland'])."'></td></tr>";
			break;

		case 'US':
			$output .= "<tr class='rate_row'><td>Continental 48 States</td><td>$<input type='text' size='4' name='shipping[continental]' value='".esc_attr($shipping['continental'])."'></td></tr>";
			$output .= "<tr class='rate_row'><td>All 50 States</td><td>$<input type='text' size='4' name='shipping[all]'	value='".esc_attr($shipping['all'])."'></td></tr>";
			break;

		default:
			$output .= "<td>$<input type='text' name='shipping[local]' size='4' value='".esc_attr($shipping['local'])."'></td></tr>";
			break;
		}

		$output.= "<tr ><td colspan='2'><strong>Base International</strong></td></tr>";
		$output .= "<tr class='rate_row'><td>North America</td><td>$<input size='4' type='text' name='shipping[northamerica]'	value='".esc_attr($shipping['northamerica'])."'></td></tr>";
		$output .= "<tr class='rate_row'><td>South America</td><td>$<input size='4' type='text' name='shipping[southamerica]'	value='".esc_attr($shipping['southamerica'])."'></td></tr>";
		$output .= "<tr class='rate_row'><td>Asia and Pacific</td><td>$<input size='4' type='text' name='shipping[asiapacific]'	value='".esc_attr($shipping['asiapacific'])."'></td></tr>";
		$output .= "<tr class='rate_row'><td>Europe</td><td>$<input type='text' size='4' name='shipping[europe]'	value='".esc_attr($shipping['europe'])."'></td></tr>";
		$output .= "<tr class='rate_row'><td>Africa</td><td>$<input type='text' size='4' name='shipping[africa]'	value='".esc_attr($shipping['africa'])."'></td></tr>";
		return $output;
	}

	/**
	 *
	 *
	 * @return unknown
	 */
	function submit_form() {
		if (!isset($_POST['shipping'])) $_POST['shipping'] = null;

		if ($_POST['shipping'] != null) {
			$shipping = (array)get_option('flat_rates');
			$submitted_shipping = (array)$_POST['shipping'];
			update_option('flat_rates', array_merge($shipping, $submitted_shipping));
		}
		return true;
	}

	/**
	 *
	 *
	 * @param unknown $for_display (optional)
	 * @return unknown
	 */
	function getQuote($for_display = false) {

		global $wpdb, $wpsc_cart;

		$country = '';

		if (isset($_POST['country'])) {

			$country = $_POST['country'];
			$_SESSION['wpsc_delivery_country'] = $country;

		} elseif ( isset( $_SESSION['wpsc_delivery_country'] ) ) {

			$country = $_SESSION['wpsc_delivery_country'];

		}

		if (is_object($wpsc_cart)) {
			$cart_total = $wpsc_cart->calculate_subtotal(true);
		}

		if (get_option('base_country') != $country) {

			$results = $wpdb->get_var($wpdb->prepare("SELECT `continent` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `isocode` IN(%s) LIMIT 1",$country));

			$flatrates = get_option('flat_rates');

			if ($flatrates != '') {

				if (isset($_SESSION['quote_shipping_method']) && $_SESSION['quote_shipping_method'] == $this->internal_name) {

					if ($_SESSION['quote_shipping_option'] != "Flat Rate") {
						$_SESSION['quote_shipping_option'] = null;
					}

				}
				
				if ( isset ( $flatrates[$results] ) ) {

				    if (stristr($flatrates[$results],'%')) {

					    $shipping_percent = str_replace('%', '', $flatrates[$results]);
					    $shipping_amount = $cart_total * ( $shipping_percent / 100 );
					    $flatrates[$results] = (float)$shipping_amount;

				    } 

                    return array("Flat Rate"=>(float)$flatrates[$results]);
                }
			}

		} else {

			$flatrates = get_option('flat_rates');
			$shipping_quotes = array();

			switch ($country) {
			case 'NZ':
				if (strlen($flatrates['northisland']) > 0) {
					$shipping_quotes["North Island"] = esc_attr($flatrates['northisland']);
				}
				if (strlen($flatrates['southisland']) > 0) {
					$shipping_quotes["South Island"] = esc_attr($flatrates['southisland']);
				}
				break;

			case 'US':
				if (strlen($flatrates['continental']) > 0) {
					$shipping_quotes["Continental 48 States"] = esc_attr($flatrates['continental']);
				}
				if (strlen($flatrates['all']) > 0) {
					$shipping_quotes["All 50 States"] = esc_attr($flatrates['all']);
				}
				break;

			default:
				if (strlen($flatrates['local']) > 0) {
					$shipping_quotes["Local Shipping"] = esc_attr($flatrates['local']);
				}
				break;
			}

			// Deal with % shipping rates
			foreach (array_keys($shipping_quotes) as $quote_name) {

					if (stristr($shipping_quotes[$quote_name],'%')) {
						$shipping_percent = str_replace('%', '', $shipping_quotes[$quote_name]);
						$shipping_amount = $cart_total * ( $shipping_percent / 100 );
						$shipping_quotes[$quote_name] = (float)$shipping_amount;
					} else {
						$shipping_quotes[$quote_name] = (float)$shipping_quotes[$quote_name];
					}

			}

			if (isset($_SESSION['quote_shipping_method']) && $_SESSION['quote_shipping_method'] == $this->internal_name) {

				$shipping_options = array_keys($shipping_quotes);

				if (array_search($_SESSION['quote_shipping_option'], $shipping_options) === false) {
					$_SESSION['quote_shipping_option'] = null;
				}

			}

			return $shipping_quotes;
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

	/**
	 *
	 *
	 * @param unknown $total_price
	 * @param unknown $weight
	 * @return unknown
	 */
	function get_cart_shipping($total_price, $weight) {
		return $output;
	}
}


$flatrate = new flatrate();
$wpsc_shipping_modules[$flatrate->getInternalName()] = $flatrate;
