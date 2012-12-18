<?php
/**
 * WP e-Commerce Australia Post shipping module - http://auspost.com.au
 *
 */
class australiapost {
	var $internal_name, $name;

	/**
	 * List of Valid Australia Post services
	 *
	 * @var Array
	 */
	var $services = array();

	/**
	 * Shipping module settings
	 *
	 * @var Array
	 */
	var $settings;

	var $base_country;
	var $base_zipcode;

	/**
	 * Constructor
	 */
	function australiapost () {
		$this->internal_name = "australiapost";
		$this->name = __( 'Australia Post', 'wpsc' );
		$this->is_external = true;
		$this->requires_weight = true;
		$this->needs_zipcode = true;
		$this->debug = false; // change to true to log (to the PHP error log) the API URLs and responses for each active service

		// Initialise the list of available postage services
		$this->services['STANDARD'] = __('Standard Parcel Post', 'wpsc');
		$this->services['EXPRESS'] = __('Express Post', 'wpsc');
		$this->services['AIR'] = __('Air Mail', 'wpsc');
		$this->services['SEA'] = __('Sea Mail', 'wpsc');
		$this->services['EPI'] = __('Express Post International', 'wpsc');

		// Attempt to load the existing settings
		$this->settings = get_option("wpsc_australiapost_settings");

		$this->base_country = get_option('base_country');
		$this->base_zipcode = get_option('base_zipcode');

		if (!$this->settings) {
			// Initialise the settings
			$this->settings = array();
			foreach ($this->services as $code => $value) {
				$this->settings['services'][$code] = true;
			}
			update_option('wpsc_australiapost_settings', $this->settings);
		}

		return true;
	}

	function getId() {
	}

	function setId($id) {
	}

	function getName() {
		return $this->name;
	}

	function getInternalName() {
		return $this->internal_name;
	}

	function getForm() {
		$output = '';
		// Only for Australian merchants
		if ($this->base_country != 'AU') {
			return __('This shipping module only works if the base country in settings, region is set to Australia.', 'wpsc');
		}

		// Base postcode must be set
		if (strlen($this->base_zipcode) != 4) {
			return __('You must set your base postcode above before this shipping module will work.', 'wpsc');
		}

		$output .= "<tr><td>" . __('Select the Australia Post services that you want to offer during checkout:', 'wpsc') . "</td></tr>\n\r";
		$output .= "<tr><td>\n\r";
		foreach ($this->services as $code => $value) {
			$checked = $this->settings['services'][$code] ? "checked='checked'" : '';
			$output .= "		<label style=\"margin-left: 50px;\"><input type='checkbox' {$checked} name='wpsc_australiapost_settings[services][{$code}]'/>{$this->services[$code]}</label><br />\n\r";
		}
		$output .= "<input type='hidden' name='{$this->internal_name}_updateoptions' value='true'>";
		$output .= "</td></tr>";
		$output .= "<tr><td><h4>" . __('Notes:', 'wpsc') . "</h4>";
		$output .= __('1. The actual services quoted to the customer during checkout will depend on the destination country. Not all methods are available to all destinations.', 'wpsc') . "<br />";
		$output .= __('2. Each product must have a valid weight configured. When editing a product, use the weight field.', 'wpsc') . "<br />";
		$output .= __('3. To ensure accurate quotes, each product must valid dimensions configured. When editing a product, use the height, width and length fields.', 'wpsc') . "<br />";
		$output .= __('4. The combined dimensions are estimated by calculating the volume of each item, and then calculating the cubed root of the overall order volume which becomes width, length and height.', 'wpsc') . "<br />";
		$output .= __('5. If no product dimensions are defined, then default package dimensions of 100mm x 100mm x 100mm will be used.', 'wpsc') . "<br />";
		$output .= "</tr></td>";
		return $output;
	}

	function submit_form() {
		$this->settings['services'] = array();

		// Only continue if this module's options were updated
		if ( !isset($_POST["{$this->internal_name}_updateoptions"]) || !$_POST["{$this->internal_name}_updateoptions"] ) return;

		if (isset($_POST['wpsc_australiapost_settings'])) {
			if (isset($_POST['wpsc_australiapost_settings']['services'])) {
				foreach ($this->services as $code => $name) {
					$this->settings['services'][$code] = isset($_POST['wpsc_australiapost_settings']['services'][$code]) ? true : false;
				}
			}
		}

		update_option('wpsc_australiapost_settings', $this->settings);
		return true;
	}

	function getQuote() {
		global $wpdb, $wpsc_cart;

		if ($this->base_country != 'AU' || strlen($this->base_zipcode) != 4 || !count($wpsc_cart->cart_items)) return;

		$dest = wpsc_get_customer_meta( 'shipping_country' );

		$destzipcode = (string) wpsc_get_customer_meta( 'shipping_zip' );
		if( isset($_POST['zipcode'] ) ) {
			$destzipcode = $_POST['zipcode'];
			wpsc_update_customer_meta( 'shipping_zip', $destzipcode );
		}

		if ($dest == 'AU' && strlen($destzipcode) != 4) {
		    // Invalid Australian Post Code entered, so just return an empty set of quotes instead of wasting time contactin the Aus Post API
		    return array();
		}

		/*
		3 possible scenarios:

		1.
		Cart consists of only item(s) that have "disregard shipping" ticked.

		In this case, WPEC doesn't mention shipping at all during checkout, and this shipping module probably won't be executed at all.

		Just in case it does get queried, we should still query the Australia Post API for valid shipping estimates,
		and then override the quoted price(s) to $0.00 so the customer is able to get free shipping.


		2.
		Cart consists of only item(s) where "disregard shipping" isn't ticked (ie. all item(s) attract shipping charges).

		In this case, we should query the Australia Post API as per normal.


		3.
		Cart consists of one or more "disregard shipping" product(s), and one or more other products that attract shipping charges.

		In this case, we should query the Aus Post API, only taking into account the product(s) that attract shipping charges.
		Products with "disregard shipping" ticked shouldn't have their weight or dimensions included in the quote.
		*/


		// Weight is in grams
		$weight = wpsc_convert_weight($wpsc_cart->calculate_total_weight(true), 'pound', 'gram');

		// Calculate the total cart dimensions by adding the volume of each product then calculating the cubed root
		$volume = 0;

		// Total number of item(s) in the cart
		$numItems = count($wpsc_cart->cart_items);

		if ($numItems == 0) {
		    // The customer's cart is empty. This probably shouldn't occur, but just in case!
		    return array();
		}

		// Total number of item(s) that don't attract shipping charges.
		$numItemsWithDisregardShippingTicked = 0;

		foreach($wpsc_cart->cart_items as $cart_item) {

			if ( !$cart_item->uses_shipping ) {
			    // The "Disregard Shipping for this product" option is ticked for this item.
			    // Don't include it in the shipping quote.
			    $numItemsWithDisregardShippingTicked++;
			    continue;
			}

			// If we are here then this item attracts shipping charges.

			$meta = get_product_meta($cart_item->product_id,'product_metadata',true);
			$meta = $meta['dimensions'];

			if ($meta && is_array($meta)) {
				$productVolume = 1;
				foreach (array('width','height','length') as $dimension) {
					// default dimension to 100mm
					if ( empty( $meta[$dimension] ) ) {
						$meta[$dimension] = 100;
						$meta["{$dimension}_unit"] = 'mm';
					}
					switch ($meta["{$dimension}_unit"]) {
						// we need the units in mm
						case 'cm':
							// convert from cm to mm
							$meta[$dimension] *= 10;
							break;
						case 'meter':
							// convert from m to mm
							$meta[$dimension] *= 1000;
							break;
						case 'in':
							// convert from in to mm
							$meta[$dimension] *= 25.4;
							break;
					}

					$productVolume *= $meta[$dimension];
				}

				$volume += floatval($productVolume) * $cart_item->quantity;
			}
		}

		// If there's only one item in the cart, its dimensions will be used
		// But if there are multiple items, cubic root of total volume will be used instead
		if ( $wpsc_cart->get_total_shipping_quantity() == 1 ) {
			$height = $meta['height'];
			$width = $meta['width'];
			$length = $meta['length'];
		} else {
			// Calculate the cubic root of the total volume, rounding up
			$cuberoot = ceil(pow($volume, 1 / 3));

			if ($cuberoot > 0)
			    $height = $width = $length = $cuberoot;
		}

		// As per http://auspost.com.au/personal/parcel-dimensions.html: if the parcel is box-shaped, both its length and width must be at least 15cm.
		if ($length < 150) $length = 150;
		if ($width < 150) $width = 150;

		// By default we should use Australia Post's quoted rate(s)
		$shippingPriceNeedsToBeZero = false;

		if ($numItemsWithDisregardShippingTicked == $numItems) {
		    // The cart consists of entirely "disregard shipping" products, so the shipping quote(s) should be $0.00
		    // Set the weight to 1 gram so that we can obtain valid Australia Post quotes (which we will then ignore the quoted price of)
		    $weight = 1;
		    $shippingPriceNeedsToBeZero = true;
		}

		// API Documentation: http://drc.edeliver.com.au/
		$url = "http://drc.edeliver.com.au/ratecalc.asp";

		$params = array(
		    'Pickup_Postcode' => $this->base_zipcode
		    , 'Destination_Postcode' => $destzipcode
		    , 'Quantity' => 1
		    , 'Weight' => $weight
		    , 'Height' => $height
		    , 'Width' => $width
		    , 'Length' => $length
		    , 'Country' => $dest
		);

		// URL encode the parameters to prevent issues where postcodes contain spaces (eg London postcodes)
		$params = array_map('urlencode', $params);

		$url = add_query_arg($params, $url);

		$log = '';
		$methods = array();
		foreach ($this->services as $code => $service) {
			if (!$this->settings['services'][$code]) continue;

			$fullURL = add_query_arg('Service_Type', $code, $url);

			// This cache key should be unique for a cart with these contents and destination
			// Needs to be less than 45 characters (as per http://core.trac.wordpress.org/ticket/15058)
			$cacheKey = 'wpec_apq_' . md5($fullURL);

			// See if this Australia Post quote is cached
			$cachedResult = get_transient($cacheKey);

			if ( false === $cachedResult ) {

			    // Quote isn't cached -> query the Australia Post API and then cache the result for 10 minutes

			    $response = wp_remote_get($fullURL);

			    // Silently ignore any API server errors
			    if ( is_wp_error($response) || $response['response']['code'] != '200' || empty($response['body']) ) continue;

			    if ($this->debug) {
				$log .="  {$fullURL}\n    " . $response['body'] . "\n";
			    }

			    $lines = explode("\n", $response['body']);

			    foreach($lines as $line) {
			    	if ( empty( $line ) )
			    		continue;
				    list($key, $value) = explode('=', $line);
				    $key = trim($key);
				    $value = trim($value);
				    switch ($key) {
					    case 'charge':
						    if ($shippingPriceNeedsToBeZero) {
							// All shipping prices quoted should be zero
							$methods[$code]['charge'] = 0.00;
							$log .="  NB: the price for the above quote has been overridden to $0.00\n\n";
						    } else {
							// Use the Australia Post quoted price
							$methods[$code]['charge'] = floatval($value);
						    }
						    break;
					    case 'days':
						    $methods[$code]['days'] = floatval($value);
						    break;
					    case 'err_msg':
						    $methods[$code]['err_msg'] = trim($value);
						    break;
				    }
			    }
			    $methods[$code]['name'] = $this->services[$code];

			    // Cache this quote for 10 minutes
			    set_transient($cacheKey, $methods[$code], 600);

			} else {
			    // This quote is cached so use that result instead
			    $methods[$code] = $cachedResult;
			}
		}
		if ( $this->debug && strlen($log) )
		    error_log( 'WP e-Commerce Australia Post shipping quotes for ' . site_url() . ":\n----------\n$log----------" );

		// Allow another WordPress plugin to override the quoted method(s)/amount(s)

		$methods = apply_filters('wpsc_australia_post_methods', $methods, $this->base_zipcode, $destzipcode, $dest, $weight);

		$quotedMethods = array();

		foreach ($methods as $code => $data) {
			// Only include methods with an OK response
			if ($data['err_msg'] != 'OK') continue;

			if ($data['days']) {
				// If the estimated number of days is specified, so include it in the quote
				$text = sprintf(_n('%1$s (estimated delivery time: %2$d business day)', '%1$s (estimated delivery time: %2$d business days)', $data['days'], 'wpsc'), $data['name'], $data['days']);
			} else {
				// No time estimate
				$text = $data['name'];
			}
			$quotedMethods[$text] = $data['charge'];
		}
		return $quotedMethods;
	}

	function get_item_shipping() {
	}
}
$australiapost = new australiapost();
$wpsc_shipping_modules[$australiapost->getInternalName()] = $australiapost;
?>