<?php
/**
 * shipping/flatrate.php
 *
 * @package WP e-Commerce
 */


class flatrate {
	var $internal_name, $name;

	/**
	 * Constructor
	 *
	 * @return boolean Always returns true.
	 */
	function flatrate() {
		$this->internal_name = "flatrate";
		$this->name= __( "Flat Rate", 'wpsc' );
		$this->is_external = false;
		return true;
	}

	/**
	 * Returns i18n-ized name of shipping module.
	 *
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Returns internal name of shipping module.
	 *
	 * @return string
	 */
	function getInternalName() {
		return $this->internal_name;
	}

	/**
	 * Returns HTML settings form. Should be a collection of <tr> elements containing two columns.
	 *
	 * @return string HTML snippet
	 */
	function getForm() {
		global $wpdb;

		$shipping = wp_parse_args(
			get_option( 'flat_rates' ),
			array(
				'southisland'  => '',
				'northisland'  => '',
				'continental'  => '',
				'all'          => '',
				'local'        => '',
				'northamerica' => '',
				'southamerica' => '',
				'asiapacific'  => '',
				'europe'       => '',
				'africa'       => '',
			)
		);

		$output = "<tr><td colspan='2'>";

		$output .= "<table>";

		$output .= "<tr><th colspan='2'><strong>" . __( 'Base Local', 'wpsc' ) . "</strong></th></tr>";
		switch ( get_option( 'base_country' ) ) {
			case 'NZ':
				$output .= $this->settings_form_shipping_price_field( 'southisland', __( 'South Island', 'wpsc' ),  $shipping['southisland'] );
				$output .= $this->settings_form_shipping_price_field( 'northisland', __( 'North Island', 'wpsc' ),  $shipping['northisland'] );
				break;

			case 'US':
				$output .= $this->settings_form_shipping_price_field( 'continental', __( 'Continental 48 States', 'wpsc' ),  $shipping['continental'] );
				$output .= $this->settings_form_shipping_price_field( 'all',         __( 'All 50 States'        , 'wpsc' ), $shipping['all'] );
				break;

			default:
				$output .= $this->settings_form_shipping_price_field( 'local',       __( 'Domestic', 'wpsc' ),      $shipping['local'] );
				break;
		}

		$output .= "<tr><th colspan='2'><strong>" . __( 'Base International', 'wpsc' ) . "</strong></th></tr>";
		$output .= $this->settings_form_shipping_price_field( 'northamerica', __( 'North America', 'wpsc' ),    $shipping['northamerica'] );
		$output .= $this->settings_form_shipping_price_field( 'southamerica', __( 'South America', 'wpsc' ),    $shipping['southamerica'] );
		$output .= $this->settings_form_shipping_price_field( 'asiapacific',  __( 'Asia and Pacific', 'wpsc' ), $shipping['asiapacific'] );
		$output .= $this->settings_form_shipping_price_field( 'europe',       __( 'Europe', 'wpsc' ),           $shipping['europe'] );
		$output .= $this->settings_form_shipping_price_field( 'africa',       __( 'Africa', 'wpsc' ),           $shipping['africa'] );
		$output .= "</table>";

		$output .= "<br /><p class='description'>" . __( 'If you do not wish to ship to a particular region, leave the field blank. To offer free shipping to a region, enter 0.', 'wpsc' ) . "</p>";
		$output .= "</td></tr>";

		return $output;
	}

	/**
	 * Create shipping price field
	 *
	 * @return string HTML snippet, a <tr> with two columns.
	 */
	function settings_form_shipping_price_field( $id, $label, $value ) {
		$output = "<tr><td>" . $label . "</td>";
		$output .= "<td>";
		$output .= esc_attr( wpsc_get_currency_symbol() );
		$output .= "<input size='4' type='text' name='shipping[" . esc_attr( $id ) . "]' value='" . esc_attr( $value ) . "'>";
		$output .= "</td></tr>";

		return $output;
	}

	/**
	 * Saves shipping module settings.
	 *
	 * @return boolean Always returns true.
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
	 * returns shipping quotes using this shipping module.
	 *
	 * @param boolean $for_display (optional) (unused)
	 * @return array collection of rates applicable.
	 */
	function getQuote($for_display = false) {
		global $wpdb, $wpsc_cart;
		$quote_shipping_method = wpsc_get_customer_meta( 'quote_shipping_method' );
		$quote_shipping_option = wpsc_get_customer_meta( 'quote_shipping_option' );

		$country = '';

		if (isset($_POST['country'])) {
			$country = $_POST['country'];
			wpsc_update_customer_meta( 'shipping_country', $country );
		} else {
			$country = (string) wpsc_get_customer_meta( 'shipping_country' );
		}

		if (is_object($wpsc_cart)) {
			$cart_total = $wpsc_cart->calculate_subtotal(true);
		}

		if (get_option('base_country') != $country) {

			$results = $wpdb->get_var($wpdb->prepare("SELECT `continent` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `isocode` IN(%s) LIMIT 1",$country));

			$flatrates = get_option('flat_rates');

			if ($flatrates != '') {
				if ( $quote_shipping_method == $this->internal_name && $quote_shipping_option != __( "Flat Rate", 'wpsc' ) )
					wpsc_delete_customer_meta( 'quote_shipping_option' );

				if ( isset ( $flatrates[$results] ) ) {

				    if (stristr($flatrates[$results],'%')) {

					    $shipping_percent = str_replace('%', '', $flatrates[$results]);
					    $shipping_amount = $cart_total * ( $shipping_percent / 100 );
					    $flatrates[$results] = (float)$shipping_amount;

				    }

                    return array( __( "Flat Rate", 'wpsc' ) => (float) $flatrates[$results] );
                }
			}

		} else {

			$flatrates = get_option('flat_rates');
			$shipping_quotes = array();

			switch ($country) {
			case 'NZ':
				if (strlen($flatrates['northisland']) > 0) {
					$shipping_quotes[__( 'North Island', 'wpsc' )] = esc_attr($flatrates['northisland']);
				}
				if (strlen($flatrates['southisland']) > 0) {
					$shipping_quotes[__( 'South Island', 'wpsc' )] = esc_attr($flatrates['southisland']);
				}
				break;

			case 'US':
				if (strlen($flatrates['continental']) > 0) {
					$shipping_quotes[__( 'Continental 48 States', 'wpsc' )] = esc_attr($flatrates['continental']);
				}
				if (strlen($flatrates['all']) > 0) {
					$shipping_quotes[__( 'All 50 States', 'wpsc' )] = esc_attr($flatrates['all']);
				}
				break;

			default:
				if (strlen($flatrates['local']) > 0) {
					$shipping_quotes[__( 'Local Shipping', 'wpsc' )] = esc_attr($flatrates['local']);
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

			if ( $quote_shipping_method == $this->internal_name ) {

				$shipping_options = array_keys($shipping_quotes);

				if ( array_search( $quote_shipping_option, $shipping_options ) === false) {
					wpsc_delete_customer_meta( 'quote_shipping_option' );
				}

			}

			return $shipping_quotes;
		}

	}

	/**
	 * calculates shipping price for an individual cart item.
	 *
	 * @param object $cart_item (reference)
	 * @return float price of shipping for the item.
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
