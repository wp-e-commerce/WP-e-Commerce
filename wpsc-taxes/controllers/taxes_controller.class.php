<?php

/**
 * @description: wpec_taxes_controller contains all of the functions necessary
 *               to communicate with the taxes system.
 * */
class wpec_taxes_controller {

	function __construct() {
		$this->wpec_taxes = new wpec_taxes();
	} // __construct

	/**
	* @description: wpec_taxes_isenabled - returns true or false depending
	*                                      on whether or not taxes are enabled
	* @param: void
	* @return: boolean true or false
	* */
	function wpec_taxes_isenabled() {
		return ($this->wpec_taxes->wpec_taxes_get_enabled() == 1) ? true : false;
	}// wpec_taxes_isenabled

	/**
	* @description: wpec_taxes_isincluded - returns true or false depending on
	*                                       whether or not the taxes are inclusive.
	* @param: void
	* @return: boolean true or false
	* */
	function wpec_taxes_isincluded() {
		return ($this->wpec_taxes->wpec_taxes_get_inprice() == 'inclusive') ? true : false;
	} // wpec_taxes_isincluded

	/**
	* @description: wpec_taxes_calculate_total - takes into account all tax logic
	*                   settings and returns the calculated total tax.
	*                   Expects wpsc_cart to be set.
	*
	* @param: void
	* @return: array containing total tax and rate if applicable
	* */
	function wpec_taxes_calculate_total() {
		//get the cart - NOTE: billing country is selected_country and shipping country is delivery_country
		global $wpsc_cart;

		//initialize return variable
		$returnable = array( 'total' => 0, 'rate' => 0 );

		//check if tax is enabled
		if ( $this->wpec_taxes->wpec_taxes_get_enabled() ) {
			//run tax logic and calculate tax
			if ( $this->wpec_taxes_run_logic() ) {
				//get selected country code
				$wpec_selected_country = $this->wpec_taxes_retrieve_selected_country();

				//set tax region
				$region = $this->wpec_taxes_retrieve_region();

				//get the rate for the country and region if set
				$tax_rate = $this->wpec_taxes->wpec_taxes_get_rate( $wpec_selected_country, $region );

				//start the total_tax off at 0
				$total_tax = 0;

				foreach ( $wpsc_cart->cart_items as $cart_item ) {
					//if the tax is inclusive calculate vat
					if ( $this->wpec_taxes_isincluded() ) {
						//run wpec_taxes_calculate_included_tax
						$taxes = $this->wpec_taxes_calculate_included_tax( $cart_item );

						$total_tax += $taxes['tax'];
					}
					else
					{
						//run wpec_taxes_calculate_excluded_tax
						$taxes = $this->wpec_taxes_calculate_excluded_tax( $cart_item, $tax_rate );

						$total_tax += $taxes['tax'];
					}// if
				}// foreach

				$free_shipping = false;
				$coupon_num = wpsc_get_customer_meta( 'coupon' );
				if ( $coupon_num ) {
					$coupon = new wpsc_coupons( $coupon_num );
					$free_shipping = $coupon->is_percentage == '2';
				}

				// minus coupon tax if we are using coupons, but make sure the coupon is not a free shipping coupon
				if ($wpsc_cart->coupons_amount > 0 && ! $free_shipping){

					if ( $this->wpec_taxes_isincluded() )
						$coupon_tax = $this->wpec_taxes_calculate_tax($wpsc_cart->coupons_amount, $tax_rate['rate'], false);
					else
						$coupon_tax = $this->wpec_taxes_calculate_tax($wpsc_cart->coupons_amount, $tax_rate['rate']);

					$total_tax -= $coupon_tax;
				}


				//add shipping tax if set
				if ( $tax_rate['shipping'] && ! $free_shipping ) {
					if ( $this->wpec_taxes_isincluded() )
						$total_tax += $this->wpec_taxes_calculate_tax( $wpsc_cart->calculate_total_shipping(), $tax_rate['rate'], false );
					else
						$total_tax += $this->wpec_taxes_calculate_tax( $wpsc_cart->calculate_total_shipping(), $tax_rate['rate'] );
				}// if

				$returnable = array( 'total' => $total_tax );

				if ( !$this->wpec_taxes_isincluded() ) {
					$returnable['rate'] = $tax_rate['rate'];
				}// if
			}// if
		} //if

		return $returnable;
	} // wpec_taxes_calculate_total

	/**
	* @description: wpec_taxes_calculate_tax - a simple function to calculate tax based on a given
	*               price and tax percentage.
	*
	* @param: price - the price you wish to calculate tax for
	* @param: tax_percentage - the percentage you wish to use to calculate the tax
	* @return: calculated price
	* */
	function wpec_taxes_calculate_tax( $price, $tax_percentage, $exclusive = true ) {
		$returnable = 0;

		if ( ! empty( $tax_percentage ) ) {
			if ( $exclusive) {
				$returnable = $price * ($tax_percentage / 100);
			} else {
				$returnable = ($price / (100 + $tax_percentage) ) * $tax_percentage;
			}
		}
		return $returnable;
	} // wpec_taxes_calculate_tax

	function wpec_taxes_calculate_excluded_tax( $cart_item, $tax_rate ) {
		$returnable = false;

		//do not calculate tax for this item if it is not taxable
			if ( ! isset( $cart_item->meta[0]['wpec_taxes_taxable'] ) ) {
				if ( $this->wpec_taxes_run_logic() ) {
				//get the taxable amount
				if ( isset( $cart_item->meta[0]['wpec_taxes_taxable_amount'] ) && ! empty( $cart_item->meta[0]['wpec_taxes_taxable_amount'] ) ) {
					//if there is a taxable amount defined for this product use this to calculate taxes
					$taxable_amount = $cart_item->meta[0]['wpec_taxes_taxable_amount'];
				} else {
					// there is no taxable amount found - use the unit price
					$taxable_amount = $cart_item->unit_price;
				}
				// get the taxable price - unit price multiplied by qty
				$taxable_price = $taxable_amount * $cart_item->quantity;

				// calculate tax
				$returnable = array( 'tax' => $this->wpec_taxes_calculate_tax( $taxable_price, $tax_rate['rate'] ), 'rate' => $tax_rate['rate'] );
			}
		}

		return $returnable;
	}// wpec_taxes_calculate_excluded_tax

	/**
	* @description: wpec_taxes_calculate_included_tax - provided a cart item
	*               this function will calcuate the included tax for it. It returns
	*               the tax to be added as well as the rate that was charged.
	*
	* @param: cart_item - the cart item that you wish to retrieve tax for
	* @return: array containing the tax and rate or false depending on the logic settings
	* */
	function wpec_taxes_calculate_included_tax( $cart_item ) {
		global $wpsc_cart;
		$returnable = false;
		//do not calculate tax for this item if it is not taxable
		if ( ! isset( $cart_item->meta[0]['wpec_taxes_taxable'] ) ) {
			if ( $this->wpec_taxes_run_logic() ) {
				$wpec_base_country = $this->wpec_taxes_retrieve_selected_country();
				$region = $this->wpec_taxes_retrieve_region();

				$taxes_band = isset( $cart_item->meta[0]['wpec_taxes_band'] ) ? $cart_item->meta[0]['wpec_taxes_band'] : null;

				//get the tax percentage rate
				$tax_rate = $this->wpec_taxes->wpec_taxes_get_included_rate( $taxes_band, $wpec_base_country, $region );

				//get the taxable price - unit price multiplied by qty
				$taxable_price = $cart_item->unit_price * $cart_item->quantity;

				$returnable = array( 'tax' => $this->wpec_taxes_calculate_tax( $taxable_price, $tax_rate, false ), 'rate' => $tax_rate );
			}
		}

		return $returnable;
	} //wpec_taxes_calculate_included_tax

	function wpec_taxes_retrieve_selected_country() {
		global $wpsc_cart;

		switch ( $this->wpec_taxes->wpec_taxes_get_logic() ) {
			case 'billing_shipping':
				if('shipping_address' == $this->wpec_taxes->wpec_taxes_get_billing_shipping_preference()) {
					$returnable = $wpsc_cart->selected_country;
				} else {
					$returnable = $wpsc_cart->delivery_country;
				}
				break;
			case 'billing':
				$returnable = $wpsc_cart->selected_country;
				break;
			case 'shipping':
				$returnable = $wpsc_cart->delivery_country;
				break;
			default:
				$returnable = false;
		}// switch
		return $returnable;
	}
	/**
	* @description: wpec_taxes_run_logic - runs the tax logic as defined in the taxes settings page.
	*               returns true or false depending on whether taxes can be calculated.
	*
	* @param: void
	* @return: boolean true or false
	* */
	function wpec_taxes_run_logic() {
		//initalize variables
		global $wpsc_cart;
		switch( $this->wpec_taxes->wpec_taxes_get_logic() ) {
			case 'billing_shipping':
				//only apply taxes when billing and shipping country are equal
				$returnable = ( $wpsc_cart->selected_country == $wpsc_cart->delivery_country ) ? true : false;
				break;
			default:
				$returnable = true;
		}// switch

		return $returnable;

	} // wpec_taxes_run_logic

	/**
	* @description: wpec_taxes_retrieve_region - retrieves the taxable region based on the logic settings
	*
	* @param: void
	* @return: string containing region code
	* */
	function wpec_taxes_retrieve_region() {

		global $wpsc_cart;

		switch ( $this->wpec_taxes->wpec_taxes_get_logic() ) {
			case 'billing_shipping':
				//need another setting - user needs to be able to specify which address to charge off of
				if ( $this->wpec_taxes->wpec_taxes_get_billing_shipping_preference() == 'billing_address' ) {
					$returnable = $this->wpec_taxes->wpec_taxes_get_region_code_by_id( $wpsc_cart->selected_region );
				} else {
					$returnable = $this->wpec_taxes->wpec_taxes_get_region_code_by_id( $wpsc_cart->delivery_region );
				}// if
				break;
			case 'billing':
				$returnable = $this->wpec_taxes->wpec_taxes_get_region_code_by_id( $wpsc_cart->selected_region );
				break;
			case 'shipping':
				$returnable = $this->wpec_taxes->wpec_taxes_get_region_code_by_id( $wpsc_cart->delivery_region );
				break;
			default:
				$returnable = false;
		}// switch

		return $returnable;
	} // wpec_taxes_retrieve_region

	/**
	* @description: wpec_taxes_products_tax_exists - checks if tax is set
	*               for any of the cart items.
	*
	* @return: boolean - true if tax exists, false if not
	* */
	function wpec_taxes_products_tax_exists() {
		global $wpsc_cart;

		$returnable = false;

		//loop through items and check if tax is set
		foreach ( $wpsc_cart->cart_items as $cart_item ) {
			if ( !empty( $cart_item->tax ) ) {
				//tax is set - set returnable to true and break out of the loop
				$returnable = true;
				break;
			}
		}// foreach

		return $returnable;
	} // wpec_taxes_products_tax_exists

	/**
	* @description: wpec_taxes_display_tax_bands - used to retrieve a select menu
	*                   containing all of the tax bands setup including a "Disabled"
	*                   option. Used on Add and Edit product pages.
	*
	* @param: input_settings (optional) - Expects an array of settings for the
	*                                     select menu generated.
	*                                     See: wpec_taxes_build_select_options()
	* @param: custom_tax_band (optional) - Expects an array. If this is set then the
	*             default option for the generated select menu will be set to this band.
	* @return: string containing html select menu
	* */
	function wpec_taxes_display_tax_bands( $input_settings = array(), $custom_tax_band = false ) {
		$returnable = '';
		//if taxes are included and not disabled continue else notify customer
		if ( $this->wpec_taxes_isincluded() && $this->wpec_taxes->wpec_taxes_get_enabled() ) {
			//retrieve the bands and add the disabled value
			$tax_bands = $this->wpec_taxes->wpec_taxes_get_bands();
			if ( ! empty( $tax_bands ) ) {
				array_unshift( $tax_bands, __( 'Disabled', 'wpsc' ) );

				//set select settings
				$default_select_settings = array(
					'id' => 'wpec_taxes_band',
					'name' => 'wpec_taxes_band',
					'label' => __( 'Custom Tax Band', 'wpsc' )
				);
				$band_select_settings = wp_parse_args( $input_settings, $default_select_settings );

				//set the default option
				$default_option = ( isset( $custom_tax_band ) ) ? $custom_tax_band : __( 'Disabled', 'wpsc' );

				//echo select
				$returnable = $this->wpec_taxes_build_select_options( $tax_bands, 'index', 'name', $default_option, $band_select_settings );
			} else {
				$returnable = '<p>' . sprintf( __( 'No Tax Bands Setup. Set Tax Bands up in <a href="%s">Settings &gt; Taxes</a>', 'wpsc' ), admin_url( 'options-general.php?page=wpsc-settings&tab=taxes' ) ) . '</p>';
			}// if
		} elseif ( ! $this->wpec_taxes->wpec_taxes_get_enabled() ) {
			$returnable .= sprintf( __( 'Taxes are not enabled. See <a href="%s">Settings &gt; Taxes</a>', 'wpsc' ), admin_url( 'options-general.php?page=wpsc-settings&tab=taxes' ) );
		}// if

		return $returnable;
	} // wpec_taxes_display_tax_bands

	/**
	* @description: wpec_taxes_product_rate_percentage - returns the percentage for the specified tax band.
	*
	* @param: tax_band - the index of the band you wish to retrieve a percentage for
	* @return: percentage rate
	* */
	function wpec_taxes_product_rate_percentage( $tax_band ) {
		//include global variables
		global $wpsc_cart;

		//initialize variables
		$returnable = 0;

		if ( $this->wpec_taxes_isincluded() ) {
			//get the base country
			$wpec_base_country = wpec_taxes_retrieve_selected_country();

			//get the region
			$region = $this->wpec_taxes_retrieve_region();

			//get the tax percentage rate
			$returnable = $this->wpec_taxes->wpec_taxes_get_included_rate( $tax_band, $wpec_base_country, $region );
		}

		return $returnable;
	} // wpec_taxes_product_rate_percentage

	/**
	* @description: wpec_taxes_rate_exists - given a country code this will
	*                                        check if a tax rate exists for it.
	* @param: country_code - the code of the country you wish to check rates for
	* @return: true or false
	* */
	function wpec_taxes_rate_exists( $country_code ) {
		//initalize return variable
		$returnable = false;

		//retrieve rates
		$tax_rates = $this->wpec_taxes->wpec_taxes_get_rates();

		if ( !empty( $tax_rates ) ) {
			foreach ( $tax_rates as $rate ) {
				if ( $rate['country_code'] == $country_code ) {
					$returnable = true;
					break;
				}
			}
		}

		return $returnable;
	} // wpec_taxes_rate_exists

	/**
	* @description: wpec_taxes_build_input - builds a form input based on
	*                                        defined input settings.
	*
	* @param: input_settings(optional) - the settings for your input in array format.
	*                          Example: wpec_taxes_build_input('id'=>'myforminput', 'type'=>'radio')
	*                          Defaults to text input with wpec-taxes-input class.
	* @return: string containing form input html
	* */
	function wpec_taxes_build_input( $input_settings = array( ) ) {
		//input defaults
		$defaults = array(
			'type' => 'text',
			'class' => 'wpec-taxes-input',
			'label' => '',
		);
		$settings = wp_parse_args( $input_settings, $defaults );
		//extract( $settings, EXTR_SKIP );

		//begin the input html
		$returnable = '<input ';
		//loop through the defined settings and add them to the input
		foreach ( $settings as $key => $setting ) {
			if ( $key == 'label' ) {
				continue;
			} elseif ( $key == 'value' ) {
				$setting = stripslashes($setting);
			}
			$returnable .= $key . '="' . esc_attr( $setting ) .'"';
		}// foreach
		//close the input
		$returnable .= ' />';

		//wrap the input in the label if one was specified
		if ( ! empty( $settings['label'] ) ) {
			if ( $settings['type'] == 'checkbox' ) {
				$returnable = '<label>' . $returnable . ' ' . $settings['label'] . '</label>';
			} else {
				$returnable = '<span class="wpec-taxes-form-field"><label for="' . $settings['id'] . '">' . $settings['label'] . '</label>' . $returnable;
			}
		}

		if ( ! empty( $settings['description'] ) ) {
			$returnable .= '<br /><small>' . $settings['description'] . '</small>';
		}

		// if ( $settings['type'] != 'hidden' ) {
		// 	$returnable = '<div class="wpec-taxes-form-field">' . $returnable . '</div>';
		// }

		return $returnable;
	} // wpec_taxes_build_input

	/**
	* @description: wpec_taxes_get_select_options - takes an input array and returns html formatted options
	*
	* @param: input_array - expects array format to be like those returned by: get_results($query, ARRAY_A);
	* @param: option_value - specify a key from input_array that you wish to use as the option value
	* @param: option_text - specify a key from input_array that you wish to use as the option text
	* @param: option_selected (optional) - specify a default option for your select.
	* @param: select_settings (optional) - if you wish to return an entire select input you must provide
	*                                      select settings. This would be an array consisting of key-value
	*                                      pairs that you wish to specify.
	*                                      Example: array('id'=>'my-select-id', 'name'=>'my-name')
	* @return: string
	* */
	function wpec_taxes_build_select_options( $input_array, $option_value, $option_text, $option_selected = false, $select_settings = '' ) {
		$returnable = '';
		$options = '';
		if ( empty( $input_array ) ) {
			return;
		}

		foreach ( $input_array as $value ) {

			// As of 3.8.9, we deprecated Great Britain as a country in favor of the UK.
			// See http://code.google.com/p/wp-e-commerce/issues/detail?id=1079

			if ( ! is_array( $value ) && 'GB' != get_option( 'base_country' ) &&
				(
					(
						isset( $input_array[$value] ) &&
						'GB' == $input_array[$value]
					) || (
						is_array( $value ) &&
						'GB' != get_option( 'base_country' ) &&
						in_array( 'GB', $value )
					)
				)
			) {
		  		continue;
		  	}

			//if the selected value exists in the input array skip it and continue processing
			if ( is_array( $value ) ) {

			if ( ( isset( $option_selected[$option_value] ) && esc_attr($value[$option_value]) == $option_selected[$option_value] ) || ( isset( $option_selected ) && (esc_attr($value[$option_value]) == $option_selected) ) )
			   continue;
			}

			if ( is_array( $value ) ) {
				$options .= '<option value="' . esc_attr( $value[$option_value] ) . '">' . esc_attr( $value[$option_text] ) . '</option>';
			} else {
				$options .= '<option value="' . esc_attr( $value ) . '">' . esc_attr( $value ) . '</option>';
			}
		}// foreach

		if ( ! empty( $options ) ) {
			//add default option - using !== operator so that blank values can be passed as the selected option
			if ( $option_selected !== false ) {
				if ( is_array( $option_selected ) ) {
					$selected_option = '<option selected="selected" value="' . esc_attr( $option_selected[$option_value] ) . '">' . esc_attr( $option_selected[$option_text] ) . '</option>';
				} else {
					$selected_option = '<option selected="selected" value="' . $option_selected . '">' . $option_selected . '</option>';
				}
				$options = $selected_option . $options;
			}
			//create select if necessary or just return options
			if ( $select_settings ) {
				$returnable = '<select ';
				foreach ( $select_settings as $key => $setting ) {
					if ( $key == 'label' ) {
						continue;
					} elseif ( $key == 'value') {
						$setting = esc_attr( $setting );
					}
					$returnable .= $key."='".$setting."'";
				}// foreach
				$returnable .= ">{$options}</select>";

				if ( ! empty( $select_settings['label'] ) ) {
					$returnable = "<label for='{$select_settings['id']}'>{$select_settings['label']} {$returnable}</label>";
				}
			} else {
				$returnable = $options;
			}
		}// if

		return $returnable;
	} // wpec_taxes_get_select_options

	/**
	* generates a row for use in tax settings tables
	*
	* @param string specifies mode of row to generate. Options: rates, bands
	* @param string the key number for the row
	* @param array tax rate settings (used keys: rate, name, country_code, region_code, shipping, index, row_class)
	* */
	function wpsc_build_taxes_row( $row_mode = 'rates', $row_key = 0, $tax_rate = false ) {

		$defaults = array(
			'rate' => null,
			'name' => null,
			'country_code' => null,
			'region_code' => null,
			'shipping' => null,
			'index' => null,
			'row_class' => null,
		);

		$tax_rate = array_merge( $defaults, (array) $tax_rate );

		$countries = $this->wpec_taxes->wpec_taxes_get_countries();

		if ( ! empty( $tax_rate['country_code'] ) && $tax_rate['country_code'] != 'all-markets' ) {
			$selected_country = array(
				'isocode' => $tax_rate['country_code'],
				'country' => wpsc_get_country( $tax_rate['country_code'] )
			);
		} else {
			$selected_country = array(
				'isocode' => 'all-markets',
				'country' => __('All Markets', 'wpsc')
			);
		}

		ob_start();
		?>
		<tr id='wpsc-taxes-<?php esc_attr_e( $row_mode ); ?>-row-<?php esc_attr_e( $row_key ); ?>' data-row-key="<?php esc_attr_e( $row_key ); ?>" class='wpsc-tax-<?php esc_attr_e( $row_mode ); ?>-row <?php esc_attr_e( $tax_rate['row_class'] ); ?>'>

			<?php if ( $row_mode == 'bands' ) : // BAND NAME ?>
				<td>
					<input type='hidden' id='band-index-<?php esc_attr_e( $row_key ); ?>' name="wpsc_options[wpec_taxes_bands][<?php esc_attr_e( $row_key ); ?>][index]" value="<?php esc_attr_e( $row_key ); ?>" />
					<input id='band-name-<?php esc_attr_e( $row_key ); ?>' name="wpsc_options[wpec_taxes_bands][<?php esc_attr_e( $row_key ); ?>][name]" class='taxes-band' type='text' value='<?php esc_attr_e( $tax_rate['name'] ); ?>' />
				</td>
			<?php endif; ?>

			<td>
				<?php // MARKET COUNTRY SELECT

					echo $this->wpec_taxes_build_select_options(
						$countries,
						'isocode',
						'country',
						$selected_country,
						array( // select settings
							'id' => $row_mode . "-country-" . $row_key,
							'name' => "wpsc_options[wpec_taxes_". $row_mode . "][" . $row_key . "][country_code]",
							'class' => 'wpsc-taxes-country-drop-down',
							'data-row-key' => $row_key,
							'data-row-mode' => $row_mode,
						)
					);
					// MARKET REGION SELECT
					if ( ! empty( $tax_rate['region_code'] ) ) {

						$regions = $this->wpec_taxes->wpec_taxes_get_regions( $tax_rate['country_code'] );

						if ( ! empty( $regions ) ) {

							echo $this->wpec_taxes_build_select_options(
								$regions,
								'region_code',
								'name',
								array( // selected region
									'region_code' => $tax_rate['region_code'],
									'name' => $this->wpec_taxes->wpec_taxes_get_region_information( $tax_rate['region_code'], 'name' )
								),
								array( // select settings
									'id' => $row_mode . "-region-" . $row_key,
									'name' =>  "wpsc_options[wpec_taxes_". $row_mode . "][" . $row_key . "][region_code]",
									'class' => 'wpsc-taxes-region-drop-down'
								)
							);
						}
					} // if
				?>
				<img src="<?php echo esc_url( wpsc_get_ajax_spinner() ); ?>" class="ajax-feedback" title="" alt="" />
			</td>


			<td><?php // TAX RATE ?>
				<input type='text' size='3' id="<?php esc_attr_e( $row_mode ); ?>-rate-<?php esc_attr_e( $row_key ); ?>" name="wpsc_options[wpec_taxes_<?php esc_attr_e( $row_mode ); ?>][<?php esc_attr_e( $row_key ); ?>][rate]" class="taxes-<?php esc_attr_e( $row_mode ); ?>" value="<?php esc_attr_e( $tax_rate['rate'] ); ?>" /> %
			</td>

			<?php if ( $row_mode == 'rates' ): // TAX SHIPPING ? ?>
				<td>
					<label>
						<input type='checkbox' id="shipping-<?php esc_attr_e( $row_key ); ?>" name="wpsc_options[wpec_taxes_<?php esc_attr_e( $row_mode ); ?>][<?php esc_attr_e( $row_key ); ?>][shipping]" class="taxes-<?php esc_attr_e( $row_mode ); ?>" <?php checked( $tax_rate['shipping'] == 1 ); ?> />
						<?php _e( 'Apply to Shipping', 'wpsc' ); ?>
					</label>
				</td>
			<?php endif; ?>

			<?php // ACTIONS ?>
			<td>
				<a tabindex="-1" title="<?php _e( 'Delete Field', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-minus wpsc-taxes-<?php esc_attr_e( $row_mode ); ?>-delete" id="wpsc-taxes-<?php esc_attr_e( $row_mode ); ?>-delete-<?php esc_attr_e( $row_key ); ?>" href="#"><?php echo _x( '&ndash;', 'delete item', 'wpsc' ); ?></a>
				<a tabindex="-1" title="<?php _e( 'Add Field', 'wpsc' ); ?>" class="button-secondary wpsc-button-round wpsc-button-plus wpsc-taxes-<?php esc_attr_e( $row_mode ); ?>-add" href="#"><?php echo _x( '+', 'add item', 'wpsc' ); ?></a>
			</td>

		</tr>
		<?php
		return ob_get_clean();
	}

} // wpec_taxes_controller

?>
