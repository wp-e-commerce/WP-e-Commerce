<?php

class WPSC_Country_Region {


	/**
	 * Change an country ISO code into a country id, if a country id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 */
	public static function country_id( $country_id_or_isocode ) {
		$country_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		if ( is_numeric( $country_id_or_isocode ) ) {
			$country_id = intval( $country_id_or_isocode );
		} else {
			if ( isset( self::$country_iso_code_map[$country_id_or_isocode] ) ) {
				$country_id = self::$country_iso_code_map[$country_id_or_isocode];
			}
		}

		return $country_id;
	}

	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if non-numeric country is treated as an isocode, number is the country id
	 */
	public static function country_name( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$region_count = 0;

		if ( $country_id = self::country_id( $country_id_or_isocode ) ) {
			if ( self::$countries[$country_id]->has_regions
				&& property_exists(  self::$countries[$country_id], 'regions' )
					&& is_array(  self::$countries[$country_id]->regions ) ) {
						$region_count = count( self::$countries[$country_id]->regions );
			}
		}

		return $region_count;
	}

	/**
	 * The country information
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 * @param boolean return the result as an array, default is to return the result as an object
	 *
	 * @return object|array country information
	 */
	public static function country( $country_id_or_isocode, $as_array = false ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$country = 0;

		if ( $country_id ) {
			if ( self::$countries[$country_id]->has_regions
				&& property_exists(  self::$countries[$country_id], 'regions' )
					&& is_array(  self::$countries[$country_id]->regions )
			) {
				$region_count = count( self::$countries[$country_id]->regions );
			}
		}

		if ( $as_array ) {
			$json  = json_encode( $country );
			$country = json_decode( $json, true );
		}

		return $country;
	}

	/**
	 * The currency for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 *
	 * @return string  currency code for the specified country
	 */
	public static function currency_code( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$currency_code = '';

		if ( $country_id ) {
			$currency_code = self::$countries[$country_id]->code;
		}

		return $currency_code;
	}

	/**
	 * The currency symbol for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 *
	 * @return string  currency symbol for the specified country
	 */
	public static function currency_symbol( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$currency_symbol = '';

		if ( $country_id ) {
			$currency_symbol = self::$countries[$country_id]->symbol;
		}

		return $currency_symbol;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 *
	 * @return string  currency symbol html for the specified country
	 */
	public static function currency_symbol_html( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$currency_symbol = '';

		if ( $country_id ) {
			$currency_symbol = self::$countries[$country_id]->symbol_html;
		}

		return $currency_symbol;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 *
	 * @return string  currency symbol html for the specified country
	 */
	public static function currency_data( $country_id_or_isocode, $as_array = false ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$currency_data = new stdClass();

		if ( $country_id ) {
			$currency_data->code           = self::$countries[$country_id]->code;
			$currency_data->symbol         = self::$countries[$country_id]->symbol;
			$currency_data->symbol_html    = self::$countries[$country_id]->symbol_html;
		}

		if ( $as_array ) {
			$json  = json_encode( $currency_data );
			$currency_data = json_decode( $json, true );
		}


		return $currency_data;
	}


	/**
	 * The regions for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return array of region objects index by region id
	 */
	public static function regions( $country_id_or_isocode, $as_array = false ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$regions = array();

		if ( $country_id ) {
			if ( self::$countries[$country_id]->has_regions
				&& property_exists(  self::$countries[$country_id], 'regions' )
					&& is_array(  self::$countries[$country_id]->regions ) ) {
				$regions = self::$countries[$country_id]->regions;
			}
		}

		if ( $as_array ) {
			$json  = json_encode( $regions );
			$regions = json_decode( $json, true );
		}

		return $regions;
	}

	/**
	 * The Countries
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param boolean return the results as an associative array rather than an object
	 *
	 * @return array of region objects index by region id
	 */
	public static function countries( $as_array = false ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$countries = self::$countries;

		if ( $as_array ) {
			$json  = json_encode( $countries );
			$countries = json_decode( $json, true );
		}

		return $countries;
	}

	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int count of regions in a country, if region is invalid 0 is returned
	 */
	public static function region_count( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$region_count = 0;

		if ( $country_id = self::country_id( $country_id_or_isocode ) ) {
			if ( self::$countries[$country_id]->has_regions
				&& property_exists(  self::$countries[$country_id], 'regions' )
					&& is_array(  self::$countries[$country_id]->regions )
			) {
				$region_count = count( self::$countries[$country_id]->regions );
			}
		}

		return $region_count;
	}


	/**
	 * Does the country have regions
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return true if th country has regions, false otherwise
	 */
	public static function country_has_regions( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$has_regions = false;

		if ( $country_id = self::country_id( $country_id_or_isocode ) ) {
			if ( property_exists( self::$countries[$country_id], 'regions' ) ) {
				if ( count( self::$countries[$country_id]->regions ) ) {
					$has_regions = self::$countries[$country_id]->has_regions && count( self::$countries[$country_id]->regions );
				}
			}
		}

		return $has_regions;
	}

	/**
	 * Get the list of countries,
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @return array   country list with index as country, value as name, sorted by country name
	 */
	public static function get_countries() {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		// we have the return value in our country name to id map, all we have to do is swap the keys with the values
		return array_flip( self::$country_names );
	}

	/**
	 * Get the list of currencies,
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @return array   country list with index as country, value as name, sorted by country name
	 */
	public static function currencies( $as_array = false ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$currencies = self::$currencies;

		if ( $as_array ) {
			$json  = json_encode( $currencies );
			$currencies = json_decode( $json, true );
		}

		// we have the return value in our country name to id map, all we have to do is swap the keys with the values
		return $currencies;
	}


	/**
	 * Contains the countries data, an array of objects indexed by country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $countries = null;

	/**
	 * Can array that maps from country isocode to country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	*/
	private static $country_iso_code_map = array();

	/**
	 * Country names as key sorted in alpha order, data is country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $country_names = array();

	/**
	 * Array of unique known currencies, indexed by corrency code
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $currencies = array();

	/**
	 * Returns an instance of the form with a particular ID
	 *
	 * @access public
	 * @static
	 * @since 3.8.10
	 *
	 * @param int $id Optional. Defaults to 0. The ID of the form
	 * @return WPSC_Checkout_Form
	 */
	public static function &get() {
		if ( ! self::$countries ) {
			self::$countries = new WPSC_Country_Region();
		}

		return self::$countries;
	}

	/**
	 * Constructor of an WPSC_Checkout_Form instance. Cannot be called publicly
	 *
	 * @access private
	 * @since 3.8.10
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	public function __construct() {
		if ( self::$countries == null ) {
			self::restore_myself();
		}

		if ( self::$countries == null ) {
			global $wpdb;

			// now countries is a list with the key being the integer country id, the value is the country data
			$sql = 'SELECT * FROM `' . WPSC_TABLE_CURRENCY_LIST . '` WHERE `visible`= "1" ORDER BY country ASC';
			self::$countries = $wpdb->get_results( $sql, OBJECT_K );

			// build an array to map from iso code to country, while we do this get any region data for the country
			foreach ( self::$countries as $country_id => $country ) {

				self::$country_iso_code_map[$country->isocode] = intval( $country->id );
				self::$country_names[$country->country] = intval( $country->id );

				if ( $country->has_regions == '1' ) {
					$sql = 'SELECT * FROM `' . WPSC_TABLE_REGION_TAX . '` '
							. ' WHERE `country_id` = %d '
							. ' ORDER BY code ASC ';

					// put the regions list into our country object
					self::$countries[$country_id]->regions = $wpdb->get_results( $wpdb->prepare( $sql, $country_id, OBJECT_K ) );

					// take this opportunity to clean up any types that have been turned into text by the query
					self::$countries[$country_id]->id          = intval( self::$countries[$country_id]->id );
					self::$countries[$country_id]->has_regions = self::$countries[$country_id]->has_regions == '1';
					self::$countries[$country_id]->visible     = self::$countries[$country_id]->visible == '1';

					if ( ! empty( self::$countries[$country_id]->tax ) && is_number( self::$countries[$country_id]->tax ) ) {
						self::$countries[$country_id]->tax = floatval( self::$countries[$country_id]->tax );
					}
				}
			}

			// now countries is a list with the key being the integer country id, the value is the country data
			$sql = 'SELECT DISTINCT code, symbol, symbol_html, currency FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY code ASC';
			self::$currencies = $wpdb->get_results( $sql, OBJECT_K );

			self::save_myself();
		}
	}

	/**
	 * Returns a count of how many fields are in the checkout form
	 *
	 * @access public
	 * @since 3.8.10
	 *
	 * @param bool $exclude_heading Optional. Defaults to false. Whether to exclude heading
	 *                                        fields from the output
	 * @return array
	 */
	static function get_countries_count() {
		return count( self::get_countries() );
	}

	/**
	 * Clears the copy of the structured countries data we have cached
	 *
	 * @access public static
	 *
	 * @since 3.8.10
	 *
	 * @return none
	 */
	public static function clear_cache() {
		delete_transient( self::transient_name() );
	}

	/**
	 * Save the structured county data
	 *
	 * @access private
	 *
	 * @since 3.8.10
	 *
	 * @return none
	 */
	private function save_myself() {

		$mydata = array();
		$mydata['country_iso_code_map'] = self::$country_iso_code_map;
		$mydata['countries']            = self::$countries;
		$mydata['country_names']        = self::$country_names;
		$mydata['currencies']           = self::$currencies;

		set_transient( self::transient_name(), $mydata, WEEK_IN_SECONDS );
	}

	/**
	 * Restore the structured country data from the cache int the class
	 *
	 * @access private static
	 *
	 * @since 3.8.10
	 *
	 * @return none
	 */
	private function restore_myself() {

		$mydata = get_transient(  self::transient_name() );

		$have_data = false;

		if ( count( $mydata ) == 4 ) {

			if (
				is_array( $mydata['country_iso_code_map'] )
					&& is_array( $mydata['countries'] )
						&& is_array( $mydata['country_names'] )
							&& is_array( $mydata['currencies'] )
				) {
					self::$country_iso_code_map = $mydata['country_iso_code_map'];
					self::$countries            = $mydata['countries'];
					self::$country_names        = $mydata['country_names'];
					self::$currencies           = $mydata['currencies'];
					$have_data = true;
			}
		}

		if ( ! $have_data && ( $mydata === false ) ) {
			self::clear_cache();
		}

		return $this;
	}

	/**
	 * The identifier for the tranient used to cache country data
	 *
	 * @access public static
	 *
	 * @since 3.8.10
	 *
	 * @return none
	 */

	private static function transient_name() {
		return strtolower( __CLASS__ . '-' . WPSC_DB_VERSION );
	}

	/**
	 * Confirm the class is initialized
	 *
	 * @access private static
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private static function confirmed_initialization() {
		if ( self::$countries == null ) {
			$an_instance = new WPSC_Country_Region();
		}

		return (self::$countries != null);
	}


}

// a little tiny test stub
if ( true ) {
	function testit() {

		//WPSC_Country_Region::clear_cache();

		$x = WPSC_Country_Region::region_count( 'US' );
		//error_log( 'US static has ' . $x );

		$x = WPSC_Country_Region::region_count( '136' );
		//error_log( '136 static has ' . $x );


		//error_log( 'testit done' );

	}

	add_action( 'wpsc_setup_customer', 'testit' );
}