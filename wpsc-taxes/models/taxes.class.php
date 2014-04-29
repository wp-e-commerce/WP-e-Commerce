<?php

class wpec_taxes {

	/**
	 * WPEC Taxes Options - any of these can be retrieved by get_option
	 *
	 * @var array
	 *
	 **/
	private $taxes_options = array(
		'wpec_taxes_enabled'               => 0,
		'wpec_taxes_inprice'               => 'exclusive',
		'wpec_taxes_product'               => 'add',
		'wpec_taxes_logic'                 => 'billing_shipping',
		'wpec_billing_shipping_preference' => 'billing_address',
		'wpec_taxes_rates'                 => '',
		'wpec_taxes_bands'                 => '',
	);

	function __construct() {
		$this->wpec_taxes_set_options();
	} // __construct

	/**
	 * Get Functions
	 * */
	function wpec_taxes_get_enabled() {
		return $this->taxes_options['wpec_taxes_enabled'];
	}

	function wpec_taxes_get_inprice() {
		return $this->taxes_options['wpec_taxes_inprice'];
	}

	function wpec_taxes_get_product() {
		return $this->taxes_options['wpec_taxes_product'];
	}

	function wpec_taxes_get_logic() {
		return $this->taxes_options['wpec_taxes_logic'];
	}

	function wpec_taxes_get_billing_shipping_preference() {
		return $this->taxes_options['wpec_billing_shipping_preference'];
	}

	function wpec_taxes_get_rates() {
		return $this->taxes_options['wpec_taxes_rates'];
	}

	function wpec_taxes_get_bands() {
		return $this->taxes_options['wpec_taxes_bands'];
	}

	function wpec_taxes_get_options() {
		return $this->taxes_options;
	}

	/**
	 * @description: wpec_taxes_set_options - retrieves option information
	 *                   from the database.
	 * @param: void
	 * @return: null
	 * */
	function wpec_taxes_set_options() {

		$options = array();

		foreach ($this->taxes_options as $key => $value ) {
			$options[ $key ] = get_option( $key );
		}

		$returnable = wp_parse_args( $options, $this->taxes_options );
		extract( $returnable, EXTR_SKIP );

		$this->taxes_options = $returnable;
	}

	/**
	 * @description: wpec_taxes_get_rate - retrieves the tax rate for the given country
	 *                                     and, if specified, region.
	 *
	 * @param: country_code - the isocode for the country whose tax rate you wish to retrieve.
	 * @param: region_code (optional) - the region code for the region tax rate you wish to retrieve.
	 * @return: array or false
	 * */
	function wpec_taxes_get_rate( $country_code, $region_code = '' ) {
		//initialize return variable
		// use wpsc_tax_rate hook to provide your own tax solution
		$returnable = apply_filters( 'wpsc_tax_rate', false, $this, $country_code, $region_code );

		if ( $returnable !== false )
			return $returnable;

		$country = new WPSC_Country( $country_code );

		//first check if the region given is part of the country
		if ( ! empty( $region_code ) ) {
			$region = $country->get_region( $region_code );
			if ( ! $region ) {
				//reset region code if region provided not in country provided
				$region_code = '';
			}// if
		}// if

		if ( ! empty( $this->taxes_options['wpec_taxes_rates'] ) ) {
			foreach ( $this->taxes_options['wpec_taxes_rates'] as $tax_rate ) {
				//if there is a tax rate defined for all markets use this one unless it's overwritten
				if ( 'all-markets' == $tax_rate['country_code'] ) {
					$returnable = $tax_rate;
				}// if

				//if there is a specific tax rate for the given country use it
				if ( $tax_rate['country_code'] == $country_code ) {
					//if there is a tax rate defined for all regions use it, unless it's overwritten
					if ( 'all-markets' == $tax_rate['region_code'] ) {
						$returnable = $tax_rate;
					}

					//if there is a specific tax rate for the given region then use it.
					if ( ($region_code == '' && ! isset( $tax_rate['region_code'] )) || $region_code == $tax_rate['region_code'] ) {
						$returnable = $tax_rate;
						break;
					}// if
				}// if
			}// foreach
		}// if

		return $returnable;
	} // wpec_taxes_get_rate

	/**
	 * @description: wpec_taxes_get_band_from_name - retrieves the tax band for the given name
	 *
	 * @param: name - the name of the tax band you wish to retrieve.
	 * @return: array or false
	 * */
	function wpec_taxes_get_band_from_name( $name ) {
		//initialize return value
		$returnable = false;

		//search bands for name
		if ( ! empty( $this->taxes_options['wpec_taxes_bands'] ) ) {
			foreach ( $this->taxes_options['wpec_taxes_bands'] as $tax_band ) {
				if ( $tax_band['name'] == $name ) {
					$returnable = $tax_band;
					break;
				}// if
			}// foreach
		}// if

		return $returnable;
	} // wpec_taxes_get_band_from_name

	/**
	 * @description: wpec_taxes_get_band_from_index - retrieves the tax band for the given name
	 *
	 * @param: index - the index of the tax band you wish to retrieve.
	 * @return: array or false
	 * */
	function wpec_taxes_get_band_from_index( $index ) {
		//initialize return value
		$returnable = false;

		//search bands for index
		if ( ! empty( $this->taxes_options['wpec_taxes_bands'] ) ) {
			foreach ( $this->taxes_options['wpec_taxes_bands'] as $tax_band ) {
				if ( $tax_band['index'] == $index ) {
					$returnable = $tax_band;
					break;
				}// if
			}// foreach
		}// if

		return $returnable;
	} // wpec_taxes_get_band_from_index

	/**
	 * @description: wpec_taxes_get_included_rate - returns the precentage rate for the given tax band index,
	 *               country code and region code. This retrieves the rate based on the current
	 *               tax settings.
	 *
	 * @param: taxes_band_index - the index of the tax band you wish to retrieve a percentage rate for
	 * @param: country_code - isocode of the country that you wish to retrieve a percentage rate for
	 * @param: region_code(optional) - the code code for the region that you wish to retrieve a
	 *         percentage rate for
	 * */
	function wpec_taxes_get_included_rate( $taxes_band_index, $country_code, $region_code = '' ) {
		//get the tax band and tax rate
		$tax_band = $this->wpec_taxes_get_band_from_index( $taxes_band_index );
		$rate_array = $this->wpec_taxes_get_rate( $country_code, $region_code );

		//set the tax rate depending on product rate settings
		if ( isset( $tax_band['rate'] ) ) {
			switch ( $this->wpec_taxes_get_product() ) {
				case 'add':
					$tax_rate = $rate_array['rate'] + $tax_band['rate'];
					break;
				case 'replace':
				default:
						$tax_rate = $tax_band['rate'];
					break;
			}// switch
		} else {
			$tax_rate = $rate_array['rate'];
		}

		//return tax for this item
		return $tax_rate;

	} // wpec_taxes_get_included_rate

	/**
	 * @description: wpec_taxes_get_countries - retrieves an array of countries
	 *
	 * @param: visibility (optional) - set to 'visible' or 'hidden' to retrieve
	 *                                 visible or hidden countries. Default action
	 *                                 is to retrieve any country.
	 * @return: array or false
	 * */
	function wpec_taxes_get_countries( $visibility = 'any' ) {
		switch ( $visibility ) {
			case 'visible': $where = array( 'visible' => 1 );
				break;
			case 'hidden': $where = array( 'visible' => 0 );
				break;
			default: $where = array();
		}// switch

		$returnable = $this->wpec_taxes_get_country_information( array( 'country', 'isocode' ), $where, 'country' );

		//add all markets
		array_unshift( $returnable, array( 'isocode' => 'all-markets', 'country' => __( 'All Markets', 'wpsc' ) ) );

		return $returnable;
	} // wpec_taxes_get_countries

	/**
	 * @description: wpec_get_country_information - retrieves information about a country.
	 *               Note: If only one column is specified this function will return the value
	 *                     of that column. If two or more columns are specified the results are
	 *                     returned in an array.
	 * @param: columns(optional) - specify a column name or multiple column names in an array.
	 *                             Default action is to return all columns.
	 * @param: where(optional) - specify where conditions in array format. Key is column
	 *                           and value is column value.
	 *                           Example: wpec_taxes_get_country_information('id', array('isocode'=>'CA'))
	 *                           Default action is to not limit results.
	 *                           Note: this function only compares using the equals sign (=).
	 * @param: order_by(optional) - specify a column name or multiple column names in an array.
	 *                              Default action is to not include an order by statement.
	 * @return: array, int, string or false
	 * */
	function wpec_taxes_get_country_information( $columns = false, $where = array(), $order_by = false ) {
		//check for all-markets
		if ( 'country' == $columns && 1 == count( $where ) && 'all-markets' == $where['isocode'] ) {
			$returnable = 'All Markets';
		} else {
			//database connection
			global $wpdb;

			//if columns are not set select everything
			$columns = ($columns) ? $columns : array( '*' );

			//change columns to array if not an array
			if ( ! is_array( $columns ) )
				$columns = array( $columns );

			$columns = array_map( 'esc_sql', $columns );

			//if where is set then formulate conditions
			if ( ! empty( $where ) ) {
				foreach ( $where as $column => $condition ) {
					$condition = esc_sql( $condition );
					$where_query[] = ( is_numeric( $condition ) ) ? "{$column}={$condition}" : "{$column}='{$condition}'";
				}// foreach
			}// if

			//formulate query
			$query = 'SELECT ' . implode( ',', $columns ) . ' FROM ' . WPSC_TABLE_CURRENCY_LIST;

			if ( isset( $where_query ) )
				$query .= ' WHERE ' . implode( ' AND ', $where_query );

			//if order_by is set, add to query
			if ( $order_by ) {
				if ( ! is_array( $order_by ) )
					$order_by = array( $order_by );

				$order_by = array_map( 'esc_sql', $order_by );
				$query .= ' ORDER BY ' . implode( ',', $order_by );
			}// if

			$returnable = ( count( $columns ) > 1 ) ? $wpdb->get_results( $query, ARRAY_A ) : $wpdb->get_var( $query );
		}// if

		//return the result
		return $returnable;
	} // wpec_taxes_get_country_information

	/**
	 * @description: wpec_taxes_get_region_information - given a region code and column
	 *                   this function will return the resulting value.
	 * @param: region_code - code for this region
	 * @param: attribute (optional) - specify a column to retrieve
	 *                            Default action is to retrieve the id column.
	 * @return: int, string, or false
	 * */
	function wpec_taxes_get_region_information( $region, $attribute = 'id', $country = null ) {

		$returnable = false;

		if ( ( 'all-markets' == $region ) || ( 'all-markets' == $country ) ) {
			$returnable = __( 'All Markets', 'wpsc' );
		} else {

			// prior to version 3.8.14 this function was made available without the country parameter, because
			// there is no assurance that region code ar unique across the globe we need to specify a country
			// at the time of this change this function was not called with WPeC, but just in case we have a
			// little logic here to try to catch an improper call, but it isn't perfect.

			if ( ( $country == null ) ) {
				_wpsc_doing_it_wrong( __FUNCTION__, __( 'Prior to version 3.8.14 this function was made available without the country parameter, because there is no assurance that region code ar unique across the globe we need to specify a country.', 'wpsc' ), '3.8.14' );
			}

			//check for all markets ifset return the string 'All Markets'
			if ( ! $returnable ) {
				$wpsc_country = new WPSC_Country( $country );
				if ( $wpsc_country ) {
					$wpsc_region = $wpsc_country->get_region( $region );
					if ( $wpsc_region ) {
						$returnable = $wpsc_region->get( $attribute );
					}
				}
			}
		}

		return $returnable;
	} // wpec_taxes_get_region_information

	/**
	 * @description: wpec_taxes_get_regions - given a isocode, such as CA, this function
	 *               will return an array of regions within that country.
	 * @param: country - string variable containing isocode
	 * @return: array or false
	 * */
	function wpec_taxes_get_regions( $country ) {

		$wpsc_country = new WPSC_Country( $country );

		$regions = $wpsc_country->get_regions_array();

		// backwards compatability at version 3.8.14, the rest of the module needs region to be called 'region_code'.
		// TODO: update taxes module to use WPSC_Region objects
		foreach ( $regions as $index => $region ) {
			$region['region_code'] = $region['code'];
			unset( $region['region'] );
			$regions[$index] = $region;
		}

		if ( isset( $country ) && 'all-markets' == $country ) {
			return;
		}

		//add the all markets option to the list
		if ( ! empty( $regions ) ) {
			array_unshift( $regions , array( 'region_code' => 'all-markets', 'name' => __( 'All Markets', 'wpsc' ), ) );
		}

		return $regions;
	} // wpec_taxes_get_regions

	/**
	 * @description: wpec_taxes_get_region_code_by_id - given an id this funciton will
	 * return the region code.
	 * @param: id - a region id
	 * @return: int or false
	 * */
	function wpec_taxes_get_region_code_by_id( $region ) {
		$region_code = false;

		if ( ! empty( $region ) ) {
			$country_id = WPSC_Countries::get_country_id_by_region_id( $region );
			if ( $country_id ) {
				$wpsc_country = new WPSC_Country( $country_id );
			}

			if ( isset( $wpsc_country ) ) {
				$wpsc_region = $wpsc_country->get_region( $region );
				if ( $wpsc_region ) {
					$region_code = $wpsc_region->get_code();
				}
			}
		}

		return $region_code;
	} // wpec_taxes_get_region_code_by_id
} // wpec_taxes

?>