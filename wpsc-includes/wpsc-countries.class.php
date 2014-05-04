<?php
/*
 * WPSC_Countries
 *
 * Before your read too much further , if you want to do things with countries, regions or currencies
 * you want to take a look at these classes:
 *
 *     WPSC_Countries  - found in file wpsc-countries.class.php
 *     WPSC_Regions    - found in file wpsc-regions.class.php
 *     WPSC_Currency   - found in file wpsc-currency.class.php
 *
 *
 * About WPSC_Countries:
 *
 * WPSC_Countries is a WPeC class used to provide easy access to country, region
 * and currency information for all of WPeC an any extensions. Because this data is
 * accessed a lot throughout WPeC it is designed to be quick and avoid accessing the database.
 *
 * This class is largely procedural, and has many static methods that let the caller get access
 * to country/region/currency data
 *
 * HOWEVER, the primary purpose of the is class is to centralize the access to country/region/currency data,
 * act as a service provider for access of the data, act as a central place to keep the data set to avoid
 * replicating it many times during execution of the code, and in the end make it very fast and efficient to
 * work with country/region/currency data.
 *
 * How does it work?
 *
 *  This class uses the static currency and region information distributed with WPeC to create an
 *  object that is optimized for access.  A copy of this object is cached.  When WPeC initialized
 *  this cached object is retrieved and used to service any request for geographical data.
 *
 * How is this data refreshed if it is cached?
 *
 *  If an administrator changes country data in the WPeC admin tool the object will be rebuilt. If
 *  WPeC is upgraded the object will be rebuilt. And, because the object is stored as a transient, any
 *  action that would refresh the WordPress object cache would cause the object
 *
 *
 * The implementation consists of three class
 *
 * WPSC_Country     Retrieves the data model for single countries.
 * WPSC_Region      Retrieves the data model for single regions.
 * WPSC_Countries   Get lists of countries, convert key fields to unique ids, and other useful functions,
 *                  Also abstracts data storage mechanism from database.
 *
 */

/**
 * Our geography data class that handles access to the countries, regions and currency information
 *
 * @access public
 *
 * @since 3.8.14
 *
 * @param
 *
 *
 * @return void
 */
class WPSC_Countries {

	const INCLUDE_INVISIBLE        = true;
	const DO_NOT_INCLUDE_INVISIBLE = false;

	/**
	 * Change an country ISO code into a country id, if a country id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param int | string       country being checked, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int | boolean     integer country id on success, false on failure
	 */
	public static function get_country_id( $country ) {

		// set default return value
		$country_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		if ( $country ) {
			if ( is_numeric( $country ) ) {
				$country_id = intval( $country );
			} elseif ( is_string( $country ) ) {
				$country_id = self::$country_id_by_iso_code->value( $country, $country_id );
			} else {
				_wpsc_doing_it_wrong( 'WPSC_Countries::country_id', __( 'Method "get_country_id" of WPSC_Countries requires an integer country code or a string ISO code ', 'wpsc' ), '3.8.14' );
			}
		}

		return $country_id;
	}

	/**
	 * Change an country ISO code into a country id, if a country id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param int|string        $country    country being checked, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int|boolean                  integer country id on success, false on failure
	 */
	public static function get_country_isocode( $country ) {
		$country_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		if ( is_numeric( $country ) ) {
			$country_id = intval( $country );
		} else {
			$country_id = self::$country_id_by_iso_code->value( $country );
		}

		return $country_id;
	}

	/**
	 * Change an region code into a region id, if a region id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param int|string    $country    country being checked, if non-numeric country is treated as an isocode, number is the country id
	 * @param int|string    $region     region being checked, if non-numeric region is treated as an code, number is the region id
	 *
	 * @return int|boolean 	integer country id on success, false on failure
	 */
	public static function get_region_id( $country, $region ) {

		// set default return value
		$region_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::get_country_id( $country );

		if ( is_numeric( $region ) ) {
			$region_id = intval( $region );
		} else {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country && $wpsc_country->has_regions() ) {
				$region_id = $wpsc_country->get_region_id_by_region_code( $region );
			}
		}

		return $region_id;
	}

	/**
	 * Return a WPSC_Region to the caller
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string|null optional	if non-numeric country is treated as an ISO code, number is the country id
	 *
	 * @param int|string required if non-numeric country is treated as an region code, number is the region id,
	 *        if the region id is passed then country_id is ignored
	 *
	 * @return WPSC_Region boolean object or false on failure
	 *
	 */
	public static function get_region( $country, $region ) {

		if ( ! self::confirmed_initialization() ) {
			return false;
		}

		// set default return value
		$wpsc_region = false;

		// we want to get to the unique region id to retrieve the region object, it might have been passed, or we
		// will have to figure it out from the country and the region
		if ( is_int( $region ) ) {
			$region_id  = $region;
			$country_id = self::$country_id_by_region_id->value( $region_id, false );
		} else {
			$country_id = self::get_country_id( $country );
			$region_id  = self::get_region_id( $country_id, $region );
		}

		if ( $country_id && $region_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$wpsc_region = $wpsc_country->get_region( $region_id );
			}
		}

		return $wpsc_region;
	}

	/**
	 * The country information
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string      $country    country being checked, if non-numeric country is treated as an isocode,
	 *                                    if number used as the country id
	 *
	 * @param boolean         $as_array   return the result as an array, default is to return the result as an object
	 *
	 * @return object|array|boolean       country information, false on failure
	 */
	public static function get_country( $country, $as_array = false ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::get_country_id( $country );

		// set default return value
		$wpsc_country = false;

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, $wpsc_country );
		}

		if ( $as_array && $wpsc_country ) {
			$wpsc_country = $wpsc_country->as_array();
		}

		return $wpsc_country;
	}

	/**
	 * The currency for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string    $country  country being checked, if non-numeric country is treated as an isocode,
	 *                                  number is the country id
	 *
	 * @return string currency code for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_code( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return '';
		}

		$country_id = self::get_country_id( $country );

		// set default return value
		$currency_code = '';

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$currency_code = $wpsc_country->get_currency_code();
			}
		}

		return $currency_code;
	}

	/**
	 * The currency symbol for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string   $country    country being checked, if non-numeric country is treated as an isocode,
	 *                                   number is the country id
	 *
	 * @return string currency symbol for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_symbol( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return '';
		}

		$country_id = self::get_country_id( $country );

		// set default return value
		$currency_symbol = '';

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$currency_symbol = $wpsc_country->get_currency_symbol();
			}
		}

		return $currency_symbol;
	}

	/**
	 * The content for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string    $country    country being checked, if non-numeric country is treated as an isocode,
	 *                                    number is the country id
	 *
	 * @return string content for the country, or empty string if it is not defined
	 */
	public static function get_continent( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::get_country_id( $country );

		// set default return value
		$continent = '';

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$continent = $wpsc_country->get_continent();
			}
		}

		return $continent;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string    $country     country being checked, if non-numeric country is treated as an isocode,
	 *                                     number is the country id
	 *
	 * @return string currency symbol HTML for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_symbol_html( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::get_country_id( $country );

		$currency_symbol = '';

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$currency_symbol = $wpsc_country->symbol_html;
			}
		}

		return $currency_symbol;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string    $country     country being checked, if non-numeric country is treated as an isocode,
	 *                                     number is the country id
	 *
	 * @param boolean         $as_array    return the result as an array, default is to return the result as an object
	 *
	 * @return string currency symbol HTML for the specified country, empty stdClass on failure
	 */
	public static function get_currency_data( $country, $as_array = false ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::get_country_id( $country );

		$currency_data = new stdClass;

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
			if ( $wpsc_country ) {
				$currency_data->code        = $wpsc_country->get_currency_code();
				$currency_data->symbol      = $wpsc_country->get_currency_symbol();
				$currency_data->symbol_html = $wpsc_country->get_currency_symbol_html();
				$currency_data->currency    = $wpsc_country->get_currency_name();
			}
		}

		if ( $as_array ) {
			$json          = json_encode( $currency_data );
			$currency_data = json_decode( $json, true );
		}

		return $currency_data;
	}

	/**
	 * get the country's regions
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string    $country     country being checked, if noon-numeric country is treated as an
	 *                                   isocode, number is the country id
	 *
	 * @param boolean       $as_array    the result as an array, default is to return the result as an object
	 *
	 * @return array of region objects index by region id, empty array if no regions
	 */
	public static function get_regions( $country, $as_array = false ) {
		if ( ! self::confirmed_initialization() ) {
			return array();
		}

		$country_id = self::get_country_id( $country );

		$regions = array();

		if ( $country_id ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id );

			if ( $wpsc_country->has_regions() ) {
				$regions = $wpsc_country->get_regions( $as_array );
			}
		}

		return $regions;
	}

	/**
	 * The Countries as array of WPSC_Countries
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param boolean  $include_invisible  return countries that are set to invisible
	 * @param boolean  $sortbyname         return countries ordered by name
	 *
	 * @return array of region objects index by region idm sorted by country name
	 */
	public static function get_countries( $include_invisible = false, $sortbyname = true ) {
		if ( ! self::confirmed_initialization() ) {
			return array();
		}

		if ( $include_invisible ) {
			$countries = self::$all_wpsc_country_by_country_id->data();
			foreach ( $countries as $country_id => $wpsc_country ) {
				$country_is_legacy = (bool) $wpsc_country->get( '_is_country_legacy' );
				if ( $country_is_legacy ) {
					unset( $countries[ $country_id ] );
				}
			}
		} else {
			$countries = self::$active_wpsc_country_by_country_id->data();
		}

		if ( $sortbyname && ! empty( $countries ) ) {
			uasort( $countries, array( __CLASS__, '_compare_countries_by_name' ) );
		} else {
			// countries should be sorted internally by id, but just in case data was changed since the last data load
			uasort( $countries, array( __CLASS__, '_compare_countries_by_id' ) );
		}

		return $countries;
	}

	/**
	 * The Countries as arrays of arrays
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param boolean return the results as an associative array rather than an object
	 *
	 * @return array of arrays index by region id, each element array index by property
	 */
	public static function get_countries_array( $include_invisible = false, $sortbyname = true ) {

		$countries = self::get_countries( $include_invisible, $sortbyname );
		$countries_list = array();

		foreach ( $countries as $country_key => $wpsc_country ) {
			$country = get_object_vars( $wpsc_country );

			$keys = array_keys( $country );
			foreach ( $keys as $index => $key ) {
				// clear out the data map classes from the array
				if ( is_a( $country[$key], 'WPSC_Data_Map' ) ) {
					unset( $country[$key] );
				}
			}

			$keys = array_keys( $country );
			foreach ( $keys as $index => $key ) {
				if ( substr( $key, 0, 1 ) == '_' ) {
					$keys[$index] = substr( $key, 1 );
				}
			}

			$country = array_combine( $keys, array_values( $country ) );

			// the return value is supporting legacy code that may look for the
			// country name using 'country' rather than name, put it there
			$country['country'] = $country['name'];

			$countries_list[] = $country;
		}

		return $countries_list;
	}

	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string    $country    country being checked, if non-numeric country is treated as an isocode,
	 *                                  number is the country id
	 *
	 * @return int count of regions in a country, if region is invalid 0 is returned
	 */
	public static function get_region_count( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$region_count = 0;

		if ( $country_id = self::get_country_id( $country ) ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );

			if ( $wpsc_country ) {
				$region_count = $wpsc_country->get_region_count();
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
	 * @param int|string      $country     country being checked, if noon-numeric country is treated as an isocode,
	 *                                     number is the country
	 *
	 * @return true if the country has regions, false otherwise
	 */
	public static function country_has_regions( $country ) {
		if ( ! self::confirmed_initialization() ) {
			return false;
		}

		// set default return value
		$has_regions = false;

		if ( $country_id = self::get_country_id( $country ) ) {
			$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );

			if ( $wpsc_country ) {
				$has_regions = $wpsc_country->has_regions();
			}
		}

		return $has_regions;
	}

	/**
	 * Get the list of countries,
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @return array country list with index as country, value as name, sorted by country name
	 */
	public static function get_country_names() {
		if ( ! self::confirmed_initialization() ) {
			return array();
		}

		$country_names = array_flip( self::$country_id_by_country_name->data() );

		// we have the return value in our country name to id map, all we have to do is swap the keys with the values
		return $country_names;
	}

	/**
	 * Get the currency
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param boolean return the result as an array, default is to return the result as an object
	 *
	 * @return array country list with index as country, value as name, sorted by country name
	 */
	public static function get_currency( $code, $as_array = false ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$wpsc_currency = self::$currencies->value( $code, false );

		if ( $as_array && $wpsc_currency ) {
			$wpsc_currency = $wpsc_currency->as_array();
		}

		// we have the return value in our country name to id map, all we have to do is swap the keys with the values
		return $wpsc_currency;
	}

	/**
	 * Get the list of currencies,
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param boolean return the result as an array, default is to return the result as an object
	 *
	 * @return array country list with index as country, value as name, sorted by country name
	 */
	public static function get_currencies( $as_array = false ) {
		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$currencies = self::$currencies->data();

		if ( $as_array ) {
			$currencies_list = array();

			foreach ( $currencies as $currencies_key => $currency ) {
				$currency_array                           = get_object_vars( $currency );
				$currency_array['currency']               = $currency_array['name'];   // some  legacy code looks for 'currency' rather than name, so we put both in the array
				$currencies_list[$currency_array['code']] = $currency_array;
			}

			$currencies = $currencies_list;
		}

		// we have the return value in our country name to id map, all we have to do is swap the keys with the values
		return $currencies;
	}

	/**
	 * get the country id from a region id,
	 *
	 * @access public
	 *
	 * @static
	 *
	 *
	 * @since 3.8.14
	 *
	 * @param int $region_id region idnetifier
	 *
	 * @return int|boolean country identifier, false on failure
	 */
	public static function get_country_id_by_region_id( $region_id ) {
		if ( ! self::confirmed_initialization() ) {
			return false;
		}

		if ( is_numeric( $region_id ) ) {
			$region_id = intval( $region_id );
		} else {
			$region_id = 0;
		}

		if ( ! $region_id ) {
			_wpsc_doing_it_wrong( 'WPSC_Countries::getcountry_id_by_region_id', __( 'Function "get_country_id_by_region_id" requires an integer $region_id', 'wpsc' ), '3.8.14' );
			return false;
		}

		return self::$country_id_by_region_id->value( $region_id, false );
	}

	/**
	 * get the country id from a country code
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @return int boolean id or false on failure
	 */
	public static function get_country_id_by_country_code( $country_code ) {
		if ( ! self::confirmed_initialization() ) {
			return false;
		}

		return self::$country_code_by_country_id->value( $country_code, false );
	}

	/**
	 * Country names as key sorted in alpha order, data is country id
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $country_id_by_country_name = null;

	/**
	 * Array of unique known currencies, indexed by corrency code
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $currencies = array();

	/**
	 * An array that maps from country isocode to country id
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $country_id_by_iso_code = null;

	/**
	 * An array that maps from country code to country id
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $country_code_by_country_id = null;

	/**
	 * map of unique region id to WPSC_Region class object
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var WPSC_Data_Map object
	 */
	private static $region_by_region_id = null;

	/**
	 * map of not necessarily unqiue region code from unique region id
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var WPSC_Data_Map object
	 */
	private static $region_code_by_region_id = null;

	/**
	 * map of unqiue region id to unique country id
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var WPSC_Data_Map object
	 */
	private static $country_id_by_region_id = null;

	/**
	 * Have we initialized this global class?
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $_initialized = false;

	/**
	 * Contains the countries data for active countries, potentially a much smaller data set than
	 * all countires
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $active_wpsc_country_by_country_id = null;

	/**
	 * Contains the countries data for all countries, potentially a much bigger data set than
	 * active countires
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $all_wpsc_country_by_country_id = null;

	/**
	 * Contains the regions data for all countries, potentially a much bigger data set than regions for
	 * active countires
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $all_wpsc_region_by_region_id = null;

	/**
	 * Has the data in this class been changed
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var boolean
	 */
	private static $_dirty = false;

	/**
	 * Constructor of an WPSC_countries instance
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 */
	public function __construct() {
		if ( self::$active_wpsc_country_by_country_id == null ) {
			self::_clean_data_maps();
			self::restore();
		}

		// respect the notification that temporary data has been flushed, clear our own cache and ite will rebuild the
		// country data structures on the next request
		add_action( 'wpsc_core_flush_temporary_data', array( __CLASS__, 'clear_cache' ) );

		// save our class data when processing is done
		add_action( 'shutdown', array( __CLASS__, 'save' ) );
		self::$_initialized = true;
	}

	/**
	 * Returns a count of how many fields are in the checkout form
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param bool     $exclude_heading     Optional.   Defaults to false. Whether to exclude heading
	 *                                                  fields from the output
	 * @return array
	 */
	static function get_countries_count() {
		return count( self::get_countries() );
	}

	/**
	 * Create the empty maps used by this class to do it's work.
	 *
	 * This functions contributes greatly to the performance of the class. Data maps that are named
	 * can store and retrieve themselves at the time of the first request. That means they don't need to
	 * be rebuilt every time, nor does all of the data have to be loaded and waiting for a request that
	 * may never come.
	 *
	 * Because the geographic data managed by this class can be accessed hundreds or even
	 * thousands of times when creating WPeC pages, moderate gains here can translate into
	 * substantial gains in end user perfroamnce. For this reason this class will try to keep
	 * the smaller frequently references data sets (data maps) intact and always available.
	 * Potentially large data sets, such as the global list of all countires with all regions,
	 * are only loaded when they are accessed. The WPSC_Data_Map provides the transpernet
	 * loading and creating functions for these data sets.
	 *
	 *
	 * @access private
	 *
	 * @static
	 *
	 * @since 3.8.14
	 *
	 */
	private static function _clean_data_maps() {

		/*
		 * maps without names will be loaded with the core class, we maintain
		 * a list as they are created
		 */
		self::$_maps_to_save_with_core_class = array();

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$region_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$region_by_region_id->clear();
		} else {
			self::$region_by_region_id = new WPSC_Data_Map( null, array( __CLASS__, '_create_region_by_region_id_map' )  );
		}
		self::$_maps_to_save_with_core_class['region_by_region_id'] = true;

		// at 3.14 checked and this is about 1 KB of data
		if ( is_a( self::$region_code_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$region_code_by_region_id->clear();
		} else {
			self::$region_code_by_region_id = new WPSC_Data_Map( null, array( __CLASS__, '_create_region_code_by_region_id_map' ) );
		}
		self::$_maps_to_save_with_core_class['region_code_by_region_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_id_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_region_id->clear();
		} else {
			self::$country_id_by_region_id = new WPSC_Data_Map( null, array( __CLASS__, '_create_country_id_by_region_id_map' ) );
		}
		self::$_maps_to_save_with_core_class['country_id_by_region_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_id_by_iso_code, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_iso_code->clear();
		} else {
			self::$country_id_by_iso_code = new WPSC_Data_Map( null, array( __CLASS__, '_create_country_id_by_iso_code_map' ) );
		}
		self::$_maps_to_save_with_core_class['country_id_by_iso_code'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_code_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$country_code_by_country_id->clear();
		} else {
			self::$country_code_by_country_id = new WPSC_Data_Map( null, array( __CLASS__, '_create_country_code_by_country_id' ) );
		}
		self::$_maps_to_save_with_core_class['country_code_by_country_id'] = true;

		// at 3.8.14 checked and this is about 2KB of data with 7 countries active, including US and CA
		if ( is_a( self::$active_wpsc_country_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$active_wpsc_country_by_country_id->clear();
		} else {
			self::$active_wpsc_country_by_country_id = new WPSC_Data_Map( null, array( __CLASS__, '_create_active_countries_map' ) );
		}
		self::$_maps_to_save_with_core_class['active_wpsc_country_by_country_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$currencies, 'WPSC_Data_Map' ) ) {
			self::$currencies->clear();
		} else {
			self::$currencies = new WPSC_Data_Map( null, array( __CLASS__, '_create_currency_by_currency_code_map' ) );
		}
		self::$_maps_to_save_with_core_class['currencies'] = true;


		/*
		 * maps with names can optionally reload thier data themselves when the first request
		 * is processed, this class does not need to load/ re-load them because the WPSC_Data_Map
		 * class takes care of that transparently. This goes a long way towards keeping the size of
		 * of the transient used to cache this classes data small, making WPeC intitialization faster.
		 */

		// at 3.14 checked and this is about 3KB of data, this map isn't as frequently used so we will load it if it
		// needed
		if ( is_a( self::$country_id_by_country_name, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_country_name->clear();
		} else {
			self::$country_id_by_country_name = new WPSC_Data_Map( '$country_id_by_country_name', array( __CLASS__, '_create_country_id_by_country_name_map' ) );
		}
		self::$_maps_to_save_with_core_class['country_id_by_country_name'] = false;

		// at 3.14 checked and this is about 23KB of data, not a big hit if there is a memory based object cache
		// but impacts perfomance on lower end (default) configurations that use the database to store transients
		if ( is_a( self::$all_wpsc_country_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$all_wpsc_country_by_country_id->clear();
		} else {
			self::$all_wpsc_country_by_country_id = new WPSC_Data_Map( '$all_wpsc_country_by_country_id', array( __CLASS__, '_create_all_countries_map' ) );
		}
		self::$_maps_to_save_with_core_class['all_wpsc_country_by_country_id'] = false;
	}

	/**
	 * keeps track of which maps should be saved with the class
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 */
	private static $_maps_to_save_with_core_class = null;

	/**
	 * save the contents of this class as a transient
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	public static function save() {
		if ( self::_dirty() ) {
			$mydata = array();

			// This all about which maps to we want to have available as soon as this class is initialized?
			// Serialize those maps into the saved verison of this object.

			$mydata['_maps_to_save_with_core_class'] = self::$_maps_to_save_with_core_class;

			foreach ( self::$_maps_to_save_with_core_class as $map_name => $save_map ) {
				if ( $save_map ) {
					self::$$map_name->clear_dirty();
					$mydata[$map_name] = self::$$map_name;
				}
			}

			set_transient( self::transient_name(), $mydata );

			self::$_dirty = false;
		}
	}

	/**
	 * Restore the structured country data from the cache int the class
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private function restore() {
		$data = get_transient( self::transient_name() );

		$has_data = false;

		if ( is_array( $data ) ) {
			foreach ( $data as $variable => $value ) {
				if ( property_exists( $this, $variable ) ) {

					if ( is_a( $value, 'WPSC_Data_Map' ) ) {
						$value->clear_dirty();
					}

					self::$$variable = $value;
					$has_data = true;
				} else {
					// something went wrong with save / restore
					$has_data = false;
					break;
				}
			}
		}

		if ( ! $has_data && ( $data !== false ) ) {
			delete_transient( self::transient_name() );
		} else {
			self::$_initialized = true;
		}

		self::$_dirty = false;

		return $this;
	}

	/**
	 * Clears the copy of the structured countries data we have cached
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	public static function clear_cache() {

		// delete anthing that is stored in the transient cache
		delete_transient( self::transient_name() );

		// when we clear the cached copy of the sdata, we also clear the resident copy of the data
		// so it is rebuilt and stays in sync
		self::_clean_data_maps();

		self::$_initialized = false;
		self::$_dirty = false;
	}

	/**
	 * has any data in this object or child objects changd
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private static function _dirty() {
		$dirty = self::$_dirty;

		foreach ( self::$_maps_to_save_with_core_class as $map_name => $save_map ) {
			$map = &self::$$map_name;
			if ( $map->dirty() ) {
				$dirty = true;
			}
		}

		return $dirty;
	}

	/**
	 * Comapre countries using country's name
	 *
	 * @param unknown $a instance of WPSC_Country class
	 * @param unknown $b instance of WPSC_Country class
	 *
	 * @return 0 if country names are the same, -1 if country name of a comes before country b, 1 otherwise
	 */
	private static function _compare_countries_by_name( $a, $b ) {
		return strcmp( $a->get_name(), $b->get_name() );
	}


	/**
	 * Comapre countries using country's id
	 *
	 * @param unknown $a instance of WPSC_Country class
	 * @param unknown $b instance of WPSC_Country class
	 *
	 * @return 0 if country id's are the same, -1 if country id of a comes before country b, 1 otherwise
	 */
	private static function _compare_countries_by_id( $a, $b ) {
		if ( $a->get_id() == $b->get_id() ) {
			return 0;
		}

		return ( $a->get_id() < $b->get_id() ) ? -1 : 1;
	}


	/**
	 * the identifier for the transient used to cache country data
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private static function transient_name() {
		return strtolower( __CLASS__ . '-' . WPSC_DB_VERSION );
	}

	/**
	 * Confirm the class is initialized
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private static function confirmed_initialization() {

		if ( ! self::$_initialized ) {
			$countries = new WPSC_Countries();
			self::$_initialized = (bool) $countries;
		}

		return self::$_initialized;
	}


	/////////////////////////////////////////////////////////////////////////////////////////
	//
	// class internal functions used to create the data maps used to provide fast access to
	// global country / region / currency data.
	//
	////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * create a map that lets us find the country iso code using a numeric country id
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_country_code_by_country_id_map( $data_map ) {
		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {
			$data_map->map( $wpsc_country->isocode, $country_id );
		}
	}

	/**
	 * create a map that lets us find the numeric country code using a country's iso code
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_country_id_by_iso_code_map( $data_map ) {
		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {
			$data_map->map( $wpsc_country->get_isocode(), $country_id );
		}
	}


	/**
	 * create a map that lets us find the numeric country code using a country's name
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_country_id_by_country_name_map( $data_map ) {
		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {
			$data_map->map( $wpsc_country->get_name(), $country_id );
		}
	}

	/**
	 * create a map that lets us find the numeric country id using a numeric region id
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_country_id_by_region_id_map( $data_map ) {

		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {

			if ( $wpsc_country->has_regions() ) {
				$regions = $wpsc_country->get_regions();
				foreach ( $regions as $region_id => $wpsc_region ) {
					$data_map->map( $region_id, $country_id );
				}
			}
		}
	}

	/**
	 * create a map that lets us find the WPSC_Region using a numeric region id
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_region_by_region_id_map( $data_map ) {
		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {

			if ( $wpsc_country->has_regions() ) {
				$regions = $wpsc_country->get_regions();
				foreach ( $regions as $region_id => $wpsc_region ) {
					$data_map->map( $region_id, $wpsc_region );
				}
			}
		}
	}

	/**
	 * create a map that lets us find the region code using a numeric region id
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_region_code_by_region_id_map( $data_map ) {

		$all_countries_data = self::$all_wpsc_country_by_country_id->data();

		foreach ( $all_countries_data as $country_id => $wpsc_country ) {

			if ( $wpsc_country->has_regions() ) {
				$regions = $wpsc_country->get_regions();
				foreach ( $regions as $region_id => $wpsc_region ) {
					$data_map->map( $region_id, $wpsc_region->get_code() );
				}
			}
		}
	}

	/**
	 * create a map that lets us find the country iso code using a numeric country id
	 *
	 * This function should be rarely called as the results are cached with the class
	 * data.  But because caches can be cleared at any time and for multitudes of reasons
	 * we provide this callback to recreate the data. We use the data in other data maps
	 * so that we don't have to query the database for this maps data.
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 *
	 */
	static function _create_currency_by_currency_code_map( $data_map ) {
		global $wpdb;

		// build a global active currency list
		$sql = 'SELECT DISTINCT code, symbol, symbol_html, currency FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY code ASC';
		$currencies = $wpdb->get_results( $sql, OBJECT_K );

		foreach ( $currencies as $currency_code => $currency ) {
			$wpsc_currency = new WPSC_Currency( $currency->code, $currency->symbol, $currency->symbol_html, $currency->currency );
			$data_map->map( $currency_code, $wpsc_currency );
		}

	}

	/**
	 * callback that creates / re-creates the data map mapping all known country ids to all know countries
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 */
	public static function _create_all_countries_map( $data_map ) {

		global $wpdb;

		// there are also invisible countries
		$sql = 'SELECT '
				. ' id, country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible '
					. ' FROM `' . WPSC_TABLE_CURRENCY_LIST
						. '` ORDER BY id ASC';

		$countries_array = $wpdb->get_results( $sql, OBJECT_K );

		// build an array to map from iso code to country, while we do this get any region data for the country
		foreach ( $countries_array as $country_id => $country ) {

			// create a new empty country object, add the properties we know about, then we add our region info
			$wpsc_country = new WPSC_Country( null );
			$wpsc_country->_copy_properties_from_stdclass( $country );

			if ( $country->has_regions ) {
				$sql = 'SELECT id, code, country_id, name, tax '
						. ' FROM `' . WPSC_TABLE_REGION_TAX . '` '
							. ' WHERE `country_id` = %d '
								. ' ORDER BY code ASC ';

				// put the regions list into our country object
				$regions = $wpdb->get_results( $wpdb->prepare( $sql, $country_id ), OBJECT_K );

				// any properties that came in as text that should be numbers or boolean get adjusted here, we also
				// build
				// an array to map from region code to region id
				foreach ( $regions as $region_id => $region ) {
					$region->id = intval( $region_id );
					$region->country_id = intval( $region->country_id );
					$region->tax = floatval( $region->tax );

					// create a new empty region object, then copy our region data into it.
					$wpsc_region = new WPSC_Region( null, null );
					$wpsc_region->_copy_properties_from_stdclass( $region );
					$wpsc_country->_regions->map( $region->id, $wpsc_region );
					$wpsc_country->_region_id_by_region_code->map( $region->code, $region->id );
					$wpsc_country->_region_id_by_region_name->map( strtolower( $region->name ), $region->id );
				}
			}

			$data_map->map( $country_id, $wpsc_country );
		}

		self::$_dirty = true;
	}

	/**
	 * callback that creates / re-creates the data map mapping all active country ids to all active countries
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param WPSC_Data_Map    $data_map     Data map object being intitilized
	 */
	public static function _create_active_countries_map( $data_map ) {

		global $wpdb;

		// there are also invisible countries
		$sql = 'SELECT '
				. ' id, country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible '
					. ' FROM `' . WPSC_TABLE_CURRENCY_LIST
						. '` WHERE `visible`= "1" '
							. ' ORDER BY id ASC';


		$countries_array = $wpdb->get_results( $sql, OBJECT_K );

		// build an array to map from iso code to country, while we do this get any region data for the country
		foreach ( $countries_array as $country_id => $country ) {

			// create a new empty country object, add the properties we know about, then we add our region info
			$wpsc_country = new WPSC_Country( null );
			$wpsc_country->_copy_properties_from_stdclass( $country );

			if ( $country->has_regions ) {
				$sql = 'SELECT id, code, country_id, name, tax '
						. ' FROM `' . WPSC_TABLE_REGION_TAX . '` '
							. ' WHERE `country_id` = %d '
								. ' ORDER BY code ASC ';

				// put the regions list into our country object
				$regions = $wpdb->get_results( $wpdb->prepare( $sql, $country_id ), OBJECT_K );

				/*
				 * any properties that came in as text that should be numbers or boolean
				 * get adjusted here, we also build an array to map from region code to region id
				 */
				foreach ( $regions as $region_id => $region ) {
					$region->id = intval( $region_id );
					$region->country_id = intval( $region->country_id );
					$region->tax = floatval( $region->tax );

					// create a new empty region object, then copy our region data into it.
					$wpsc_region = new WPSC_Region( null, null );
					$wpsc_region->_copy_properties_from_stdclass( $region );
					$wpsc_country->_regions->map( $region->id, $wpsc_region );
					$wpsc_country->_region_id_by_region_code->map( $region->code, $region->id );
					$wpsc_country->_region_id_by_region_name->map( strtolower( $region->name ), $region->id );
				}
			}

			$data_map->map( $country_id, $wpsc_country );
		}

		self::$_dirty = true;
	}


	/**
	 * saves country data to the database
	 *
	 * @access WPeC private
	 *
	 * @since 3.8.14
	 *
	 * @param array  key/value pairs that are put into the database columns
	 *
	 * @return int|boolean country_id on success, false on failure
	 */
	public static function _save_country_data( $country_data ) {
		global $wpdb;

		/*
		 * We need to figure out if we are updating an existing country. There are three
		 * possible unique identifiers for a country.  Look for a row that has any of the
		 * identifiers.
		 */
		$country_id       = isset( $country_data['id'] )      ? intval( $country_data['id'] ) : 0;
		$country_iso_code = isset( $country_data['isocode'] ) ? $country_data['isocode']      : '';

		/*
		 *  If at least one of the key feilds ins't present we aren'y going to continue, we can't reliably update
		 *  a row in the table, nor could we insrt a row that could reliably be updated.
		 */
		if ( empty( $country_id ) && empty( $country_iso_code ) ) {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'To insert a country either country id or country ISO code must be specified.', 'wpsc' ), '3.8.11' );
			return false;
		}

		// check the database to find the country id
		$sql = $wpdb->prepare(
				'SELECT id FROM ' . WPSC_TABLE_CURRENCY_LIST . ' WHERE (`id` = %d ) OR ( `isocode` = %s ) ',
				$country_id,
				$country_iso_code
		);

		$country_id_from_db = $wpdb->get_var( $sql );

		// do a little data clean up prior to inserting into the database
		if ( isset( $country_data['has_regions'] ) ) {
			$country_data['has_regions'] = $country_data['has_regions'] ? 1:0;
		}

		if ( isset( $country_data['visible'] ) ) {
			$country_data['visible'] = $country_data['visible'] ? 1 : 0;
		}

		// insert or update the information
		if ( empty( $country_id_from_db ) ) {
			// we are doing an insert of a new country
			$result = $wpdb->insert( WPSC_TABLE_CURRENCY_LIST, $country_data );
			if ( $result ) {
				$country_id_from_db = $wpdb->insert_id;
			}
		} else {
			// we are doing an update of an existing country
			if ( isset( $country_data['id'] ) ) {
				// no nead to update the id to itself
				unset( $country_data['id'] );
			}
			$wpdb->update( WPSC_TABLE_CURRENCY_LIST, $country_data, array( 'id' => $country_id_from_db, ), '%s', array( '%d', )  );
		}

		// clear the cached data, force a rebuild by getting a country
		self::clear_cache();

		return $country_id_from_db;
	}


}

add_action( 'init', '_wpsc_make_countries_data_available' );

function _wpsc_make_countries_data_available() {
	static $wpsc_countries = null;
	if ( $wpsc_countries == null ) {
		$wpsc_countries = new WPSC_Countries();
	}
}
