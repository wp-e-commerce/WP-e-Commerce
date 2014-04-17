<?php
/*
 * WPSC_Countries
 *
 * Before your read to much farther , if you want to do things with countries, regions or currencies
 * you want to take a look at these classes:
 *
 *     WPSC_Countires  - found in file wpsc-countries.class.php
 *     WPSC_Regions - found in file wpsc-regions.class.php
 *     WPSC_Currency - found in file wpsc-currency.class.php
 *
 *
 * About WPSC_Countries:
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
 *  This class uses the static currency and region information distributed with WPeC to create an
 *  object that is optimized for access.  A copy of this object is cached.  When WPeC initialized
 *  this cached object is retrieved and used to service any request for geographical data.
 *
 * How is this data refreshed if it is cached?
 *  If an administrator changes country data in the WPeC admin tool the object will be rebuilt. If
 *  WPeC is upgraded the object will be rebuilt. And, because the object is stored as a transient, any
 *  action that would refresh the WordPress object cache would cause the object
 *
 * Where is the global so I can access this data?
 *  I'm not telling! (just kidding) ... There isn't one because I hate globals (and I want you to hate globals also).
 *  You access geography data through the static methods available in WPSC_Countries, or by instantiating
 *  objects of type WPSC_Country and WPSC_Region.
 *
 * What about the database?
 *  Can you identify the film this quote comes from? ... Forget about Dave. For our immediate purposes,
 *  there is no Dave. Dave does not exist.
 *
 * Why is that important?
 *  Forget about database. For our immediate purposes, there is no database. database does not exist.
 *  If you use the functionality in this module it is unlikely you will need to find the data storage for the raw
 *  geography data.
 *
 * Before this class existed the direct queries to the database where really simple. Did creating this
 * module really help?
 *  uhhh, Yes. The checkout page was used as a benchmark.  When this class was there were almost 200 fewer queries
 *  to the database on just that page. Besides that there was a lot of duplicated code scattered about WPeC do get
 *  the data from the database. Much of that code had subtle variations that made it hard to maintain.
 *
 * Any other benefits to this module over direct to database
 *  Going direct the database prevented us from improving the mechanism used to store and distribute country data and
 *  updates without changing a lot of code.  Now all the database access is centralized we can make some improvements
 *  when we have time
 *
 *
 * The implementation consists of three class
 *
 * WPSC_Country      Get anything about a single country you might want to know
 * WPSC_Region      Get anything about a single region you might want to know
 * WPSC_Countries   Get lists of countries, convert key fields to unique ids, and other useful functions,
 * 						Also abstracts data storage mechanism from
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
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int | boolean 			integer country id on success, false on failure
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
				$country_id = self::$country_code_by_iso_code->value( $country, $country_id );
			} else {
				_wpsc_doing_it_wrong( 'WPSC_Countries::country_id', __( 'Function "country_id" requires an integer country code or a string ISO code ', 'wpsc' ), '3.8.14' );
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
	 * @param int|string		$country	country being checked, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int|boolean 					integer country id on success, false on failure
	 */
	public static function get_country_isocode( $country ) {
		$country_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		if ( is_numeric( $country ) ) {
			$country_id = intval( $country );
		} else {
			$country_id = self::$country_code_by_iso_code->get( $country );
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
	 * @param int|string 	country being checked, if non-numeric country is treated as an isocode, number is the country id
	 * @param int|string 	region being checked, if non-numeric region is treated as an code, number is the region id
	 *
	 * @return int|boolean 					integer country id on success, false on failure
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
	 * @param int|string 				$country 	country being check, if non-numeric country is treated as an isocode,
	 *        										number is the country id
	 *
	 * @param boolean 					$as_array	return the result as an array, default is to return the result as an object
	 *
	 * @return object|array|boolean 				country information, false on failure
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
	 * @param int | string $country country being check, if non-numeric country is treated as an isocode,
	 *        	number is the country id
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
	 * @param int | string $country country being check, if non-numeric country is treated as an isocode,
	 *        	number is the country id
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
	 * @param int | string $country country being check, if non-numeric country is treated as an isocode,
	 *        	number is the country id
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
	 * @param int | string $country country being check, if non-numeric country is treated as an isocode,
	 *        	number is the country id
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
	 * @param int | string $country country being check, if non-numeric country is treated as an isocode,
	 *        	number is the country id
	 * @param boolean return the result as an array, default is to return the result as an object
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
	 * @param int | string $country		country being checked, if noon-numeric country is treated as an
	 *        isocode, number is the country id
	 *
	 * @param boolean $as_array the result as an array, default is to return the result as an object
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

		if ( $as_array ) {
			$json = json_encode( $regions );
			$regions = json_decode( $json, true );
		}

		return $regions;
	}

	/**
	 * The Countries as array of WPSC_Countries
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param boolean 	$include_invisible	return countries that are set to invisible
	 * @param boolean 	$sortbyname			return countries ordered by name
	 *
	 * @return array of region objects index by region idm sorted by country name
	 */
	public static function get_countries( $include_invisible = false, $sortbyname = true ) {
		if ( ! self::confirmed_initialization() ) {
			return array();
		}

		if ( $include_invisible ) {
			$countries = self::$all_wpsc_country_by_country_id->data();
		} else {
			$countries = self::$active_wpsc_country_by_country_id->data();
		}

		if ( $sortbyname && ! empty( $countries ) ) {
			uasort( $countries, array( __CLASS__, '_compare_countries_by_name' ) );
		} else {
			// countries should be sorted internally by id, but hust in case data was changed since the last data load
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
	public static function get_countries_array( $include_invisible = false ) {
		$countries = self::get_countries( $include_invisible );
		$json = json_encode( $countries );
		$countries = json_decode( $json, true );
		return $countries;
	}

	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if non-numeric country is treated as an isocode, number is the country
	 *        	id
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
	 * @param int|string 		$country		country being check, if noon-numeric country is treated as an isocode,
	 * 											number is the country
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

		$currencies = self::$currencies;

		if ( $as_array ) {
			$json = json_encode( $currencies );
			$currencies = json_decode( $json, true );
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
	private static $country_code_by_iso_code = null;

	/**
	 * An array that maps from country code to country id
	 *
	 * @access private
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
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @var boolean
	 */
	private static $_dirty = false;

	/**
	 * Constructor of an WPSC_countries instance.
	 * Cannot be called publicly
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 */
	public function __construct() {
		if ( self::$active_wpsc_country_by_country_id == null ) {
			self::_clean_data_maps();
			self::restore_myself();
		}

		if ( ! self::$active_wpsc_country_by_country_id->count() ) {
			self::_create_country_maps();
		}

		add_filter( '_wpsc_javascript_localizations', array( __CLASS__, '_wpsc_countries_localizations' ) );
		add_action( 'shutdown', array( __CLASS__, 'save_myself' ) );
		self::$_initialized = true;
	}


	/**
	 * add countries data to the wpec javascript localizations
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	public static function _wpsc_countries_localizations( $localizations_array ) {

		$localizations_array['no_country_selected']       = __( 'Please select a country', 'wpsc' );
		$localizations_array['no_region_selected_format'] = __( 'Please select a %s', 'wpsc' );

		$wpsc_checkout = new wpsc_checkout();
		$localizations_array['no_region_label']           = __( 'State/Province', 'wpsc' );

		$checkout_form    = new WPSC_Checkout_Form();
		$billing_state_id = $checkout_form->get_field_id_by_unique_name( 'billingstate' );
		$fields           = $checkout_form->get_fields();

		$in_this_country_a_region_is_called_a = $checkout_form->get( $billing_state_id );

		$country_list = array();

		foreach ( self::get_countries() as $country_id => $wpsc_country ) {
			if ( $wpsc_country->is_visible() ) {
				$country_list[$wpsc_country->get_isocode()] = $wpsc_country->get_name();

				if ( $wpsc_country->has_regions() ) {
					$regions = $wpsc_country->get_regions();
					$region_list = array();
					foreach ( $regions as $region_id => $wpsc_region ) {
						$region_list[$region_id] = $wpsc_region->get_name();
					}

					if ( ! empty ( $region_list ) ) {
						$localizations_array[ 'wpsc_country_'.$wpsc_country->get_isocode() . '_regions' ] = $region_list;
					}
				}

				$region_label = $wpsc_country->get( 'region_label' );
				if ( ! empty( $region_label ) ) {
					$localizations_array['wpsc_country_' . $wpsc_country->get_isocode() . '_region_label' ] = $region_label;
				}
			}
		}

		if ( ! empty( $country_list ) ) {
			$localizations_array['wpsc_countries'] = $country_list;
		}

		return $localizations_array;
	}

	/**
	 * creates the data maps used internally by this class to service requests
	 *
	 * @access private
	 * @since 3.8.14
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	public static function _create_country_maps() {
		self::clear_cache();

		global $wpdb;

		// now countries is a list with the key being the integer country id, the value is the country data
		$sql = 'SELECT id,
						country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible
					FROM `' . WPSC_TABLE_CURRENCY_LIST . '` WHERE `visible`= "1" ORDER BY id ASC';

		$countries_array = $wpdb->get_results( $sql, OBJECT_K );
		self::_add_country_arrays_to_wpsc_country_map( $countries_array, self::$active_wpsc_country_by_country_id );

		// there are also invisible countries
		$sql = 'SELECT id,
						country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible
					FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY id ASC';

		$countries_array = $wpdb->get_results( $sql, OBJECT_K );
		self::_add_country_arrays_to_wpsc_country_map( $countries_array, self::$all_wpsc_country_by_country_id );

		// now countries lists are a list with the key being the integer
		// country id, the value is the country data

		// build a global active currency list
		$sql = 'SELECT DISTINCT code, symbol, symbol_html, currency FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY code ASC';
		$currencies = $wpdb->get_results( $sql, OBJECT_K );

		foreach ( $currencies as $currency_code => $currency ) {
			$wpsc_currency = new WPSC_Currency( $currency->code, $currency->symbol, $currency->symbol_html, $currency->currency );
			self::$currencies->map( $currency_code, $wpsc_currency );
		}
	}

	private static function _add_country_arrays_to_wpsc_country_map( $countries_array, $data_map ) {
		global $wpdb;

		// build an array to map from iso code to country, while we do this get any region data for the country
		foreach ( $countries_array as $country_id => $country ) {

			// take this opportunity to clean up any types that have been turned into text by the query
			$country->id = intval( $countries_array[$country_id]->id );
			$country->has_regions = $countries_array[$country_id]->has_regions == '1';
			$country->visible = $countries_array[$country_id]->visible == '1';

			if ( ! empty( $country->tax ) && ( is_int( $country->tax ) ) || is_float( $country->tax ) ) {
				$wpsc_country = self::$all_wpsc_country_by_country_id->value( $country_id, false );
				$country->tax = floatval( $wpsc_country->tax );
			}

			self::$country_code_by_iso_code->map( $country->isocode, $country->id );
			self::$country_id_by_country_name->map( $country->country, $country->id );
			self::$country_code_by_country_id->map( $country->code, $country->id );

			// create a new empty country object, add the properties we know about, then we add our region info
			$wpsc_country = new WPSC_Country( null );
			$wpsc_country->_copy_properties_from_stdclass( $country );

			if ( $country->has_regions ) {
				$sql = 'SELECT id, code, country_id, name, tax FROM `' . WPSC_TABLE_REGION_TAX . '` ' . ' WHERE `country_id` = %d ' . ' ORDER BY code ASC ';

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

					self::$country_id_by_region_id->map( $region->id, $region->country_id );
					self::$region_by_region_id->map( $region->id, $wpsc_region );
					self::$region_code_by_region_id->map( $region->id, $region->code );
				}
			}

			$data_map->map( $country_id, $wpsc_country );
		}

		self::$_dirty = true;

		return $countries_array;
	}

	/**
	 * Returns a count of how many fields are in the checkout form
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param bool $exclude_heading Optional. Defaults to false. Whether to exclude heading
	 *        fields from the output
	 * @return array
	 */
	static function get_countries_count() {
		return count( self::get_countries() );
	}

	/**
	 * Create the empty maps used by this class to do it's work.
	 *
	 * This functions contributes greatly to the performance of the class. Data maps that are named
	 * can store and retrieve themselves at the time of the first request. That means they don'tneed to
	 * be rebuilt every time, nor does all of the data have to be loaded and waiting for a request that
	 * may never come.
	 *
	 * What this means is that we use unnamed maps for data that is small, or has a very very high
	 * probability of being requested. The unnamed maps get serialized with this main class.
	 *
	 * We use named maps for large data sets that might not be accessed.
	 *
	 * As an example the list of all countries known to WPeC might never be accessed becuase WPeC
	 * mostly looks at only the active countries. Not retriving the big list at startup
	 * improves performance, especcially for smaller sites not using caching, becuase the dataset isn't
	 * requested in the intial database transaction.
	 *
	 * @access private
	 * @static
	 *
	 * @since 3.8.14
	 *
	 */
	private static function _clean_data_maps() {

		// our current implementation is to rebuild all of the maps if any one of them disappears
		// if the country database grows beyond several hundreds of rows it would be beneficial
		// to hafve more targetted rebuild functions. But since rebuild of alomst all of the lists
		// requires touching the bulk of the country data we might as well do everything at the same
		// time
		$default_rebuild_callback = array( &$this, '_create_country_maps()' );

		/*
		 * maps without names will be loaded with the core class
		 */
		self::$_maps_to_save_with_core_class = array();

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$region_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$region_by_region_id->clear();
		} else {
			self::$region_by_region_id = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['region_by_region_id'] = true;

		// at 3.14 checked and this is about 1 KB of data
		if ( is_a( self::$region_code_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$region_code_by_region_id->clear();
		} else {
			self::$region_code_by_region_id = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['region_code_by_region_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_id_by_region_id, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_region_id->clear();
		} else {
			self::$country_id_by_region_id = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['country_id_by_region_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_code_by_iso_code, 'WPSC_Data_Map' ) ) {
			self::$country_code_by_iso_code->clear();
		} else {
			self::$country_code_by_iso_code = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['country_code_by_iso_code'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$country_code_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$country_code_by_country_id->clear();
		} else {
			self::$country_code_by_country_id = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['country_code_by_country_id'] = true;

		// at 3.8.14 checked and this is about 2KB of data with 7 countries active, including US and CA
		if ( is_a( self::$active_wpsc_country_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$active_wpsc_country_by_country_id->clear();
		} else {
			self::$active_wpsc_country_by_country_id = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['active_wpsc_country_by_country_id'] = true;

		// at 3.8.14 checked and this is about 1 KB of data
		if ( is_a( self::$currencies, 'WPSC_Data_Map' ) ) {
			self::$currencies->clear();
		} else {
			self::$currencies = new WPSC_Data_Map( null, $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['currencies'] = true;


		/*
		 * maps with names can optionally reload thier data themselves when the first request is processed, this class
		 * does not need to load them. Keeps size of transient down and intitialization fast
		 */

		// at 3.14 checked and this is about 3KB of data, this map isn't as frequently used so we will load it if it
		// needed
		if ( is_a( self::$country_id_by_country_name, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_country_name->clear();
		} else {
			self::$country_id_by_country_name = new WPSC_Data_Map( '$country_id_by_country_name', $default_rebuild_callback );
		}
		self::$_maps_to_save_with_core_class['country_id_by_country_name'] = false;

		// at 3.14 checked and this is about 23KB of data, not a big hit if there is a memory based object cache
		// but impacts perfomance on lower end (default) configurations that use the database to store transients
		if ( is_a( self::$all_wpsc_country_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$all_wpsc_country_by_country_id->clear();
		} else {
			self::$all_wpsc_country_by_country_id = new WPSC_Data_Map( '$all_wpsc_country_by_country_id', $default_rebuild_callback );
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
	public static function save_myself() {
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

			set_transient( self::transient_name(), $mydata, WEEK_IN_SECONDS * 13 );

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
	private function restore_myself() {
		$mydata = get_transient( self::transient_name() );

		$have_data = false;

		if ( is_array( $mydata ) ) {
			foreach ( $mydata as $variable => $value ) {
				if ( property_exists( $this, $variable ) ) {

					if ( is_a( $value, 'WPSC_Data_Map' ) ) {
						$value->clear_dirty();
					}

					self::$$variable = $value;
					$have_data = true;
				} else {
					// something went wrong with save / restore
					$have_data = false;
					break;
				}
			}
		}

		if ( ! $have_data && ( $mydata !== false ) ) {
			self::clear_cache();
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

		// Clear any data maps that restore on thier own
		if ( is_a( self::$country_id_by_country_name, 'WPSC_Data_Map' ) ) {
			self::$country_id_by_country_name->clear();
		}

		if ( is_a( self::$all_wpsc_country_by_country_id, 'WPSC_Data_Map' ) ) {
			self::$all_wpsc_country_by_country_id->clear();
		}

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
	 * the identifier for the tranient used to cache country data
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
			$an_instance = new WPSC_Countries();
		}

		return self::$_initialized;
	}
}


add_action( 'init', '_wpsc_make_countries_data_available' );

function _wpsc_make_countries_data_available() {
	static $wpsc_countries = null;
	if ( $wpsc_countries == null ) {
		$wpsc_countries = new WPSC_Countries();
	}
}
