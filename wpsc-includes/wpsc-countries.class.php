<?php

/*
 * This WPeC geography module provides
 *
 * The WPSC_Countries is a WPeC class used to provide easy access to country, region
 * and currency information for all of WPeC an any extensions. Because this data is
 * accessed a lot throughout WPeC it is designed to be quick and avoid accessing the database.
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
 * Why is there a WPSC_Country class not WPSC_Country?
 *  At the time this module was created there was already a WPSC_Country class.  WPSC_Country was only used in a
 *  couple of places, and the module is on the fast track to being deprecated.
 *
 * What about the database?
 *  Can you identify the film this quote comes from? ... Forget about Dave. For our immediate purposes, there is no Dave. Dave does not exist.
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
 * description
 *
 * @access public
 *
 * @since 3.8.14
 *
 * @param
 *
 * @return void
 */
class WPSC_Countries {

	const INCLUDE_INVISIBLE = true;
	const DO_NOT_INCLUDE_INVISIBLE = false;

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

		if ( $country_id_or_isocode ) {
			if ( is_numeric( $country_id_or_isocode ) ) {
				$country_id = intval( $country_id_or_isocode );
			} elseif ( is_string( $country_id_or_isocode ) ) {
				$country_id = self::$country_iso_code_map->value( $country_id_or_isocode );
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
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 */
	public static function country_isocode( $country_id_or_isocode ) {
		$country_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		if ( is_numeric( $country_id_or_isocode ) ) {
			$country_id = intval( $country_id_or_isocode );
		} else {
			$country_id = self::$country_iso_code_map->get( $country_id_or_isocode );
		}

		return $country_id;
	}

	/**
	 * Change an region code into a region id, if a region id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if noon-numeric country is treated as an isocode, number is the country id
	 */
	public static function region_id( $country_id_or_isocode, $region_id_or_code ) {
		$region_id = false;

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		if ( is_numeric( $region_id_or_code ) ) {
			$region_id = intval( $region_id_or_code );
		} else {
			$country = self::$countries[$country_id];
			$region_id = $country->region_id_from_region_code( $region_id_or_code );
		}

		return $region_id;
	}


	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string country being check, if non-numeric country is treated as an isocode, number is the country id
	 */
	public static function region( $country_id_or_isocode, $region_id_or_code ) {

		if ( ! self::confirmed_initialization() ) {
			return null;
		}

		$region = null;

		$country_id = self::country_id( $country_id_or_isocode );
		$region_id = self::region_id( $country_id_or_isocode, $region_id_or_code );

		if ( $country_id && $region_id ) {
			$wpsc_country = self::$countries->get( $country_id );
			if ( $wpsc_country->has_regions() ) {
				$wpsc_country = self::$countries->region( $region_id_or_code );
			}
		} else {
			$region = null;
		}

		return $region;
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

		$country = null;

		if ( $country_id ) {
			if ( isset( self::$countries[$country_id] )	) {
				$country = self::$countries[$country_id];
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
	 * The content for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country_id_or_isocode    country being check, if non-numeric country is treated as an isocode, number is the country id
	 *
	 * @return string content for the country, or empty string if it is not defined
	 */
	public static function continent( $country_id_or_isocode ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		$country_id = self::country_id( $country_id_or_isocode );

		$continent = '';

		if ( $continent ) {
			$continent = self::$countries[$country_id]->continent();
		}

		return $continent;
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
 	 * @param boolean return the result as an array, default is to return the result as an object
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
			$currency_data->code           = self::$countries[$country_id]->currency_code();
			$currency_data->symbol         = self::$countries[$country_id]->currency_symbol();
			$currency_data->symbol_html    = self::$countries[$country_id]->currency_symbol_html();
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
	 * @param boolean return the result as an array, default is to return the result as an object
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
			if ( self::$countries[$country_id]->has_regions() ) {
				$regions = self::$countries[$country_id]->regions();
			}
		}

		if ( $as_array ) {
			$json  = json_encode( $regions );
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
	 * @param boolean return countries that are set to invisible
	 *
	 * @return array of region objects index by region id
	 */
	public static function countries( $include_invisible = false ) {

		if ( ! self::confirmed_initialization() ) {
			return array();
		}

		$countries = self::$countries;

		if ( $include_invisible ) {
			$countries = array_merge( $countries, self::$invisible_countries );
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
	public static function countries_array( $include_invisible = false ) {
		$countries = self::countries( $include_invisible );
		$json  = json_encode( $countries );
		$countries = json_decode( $json, true );
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
			$region_count = self::$countries[$country_id]->region_count();
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
			$has_regions = self::$countries[$country_id]->has_regions();
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
	public static function get_country_names() {

		if ( ! self::confirmed_initialization() ) {
			return array();
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
	 * @param boolean return the result as an array, default is to return the result as an object
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
	 * get the country id from a region id,
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @return array   country list with index as country, value as name, sorted by country name
	 */
	public static function country_id_from_region_id( $region_id ) {

		if ( ! self::confirmed_initialization() ) {
			return 0;
		}

		return self::$region_id_to_country_id_map->value( $region_id );

	}

	/**
	 * get the country id from a country code
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @return int|boolean Country id or false on failure
	 */
	public static function country_id_from_country_code( $country_code  ) {

		if ( ! self::confirmed_initialization() ) {
			return false;
		}

		if ( isset( self::$country_code_map[$country_code] ) ) {
			return self::$country_code_map[$country_code];
		}

		return false;
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
	private static $countries = array();

	/**
	 * Contains the invisible countries data, an array of objects indexed by country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $invisible_countries = array();

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
	 * An array that maps from country isocode to country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	 */
	private static $country_iso_code_map = null;

	/**
	 * An array that maps from country code to country id
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var array
	*/
	private static $country_code_map = null;

	/**
	 * map of unique region id to WPSC_Region objects held within WPSC_Country objects
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var WPSC_Data_Map object
	 */
	private static $region_id_to_region_code_map = null;
	private static $region_id_to_country_id_map = null;

	/**
	 * Have we initialized this global class?
	 *
	 * @access private
	 * @static
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
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $active_wpsc_country_from_country_id = null;

	/**
	 * Contains the countries data for all countries, potentially a much bigger data set than
	 * active countires
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $all_wpsc_country_from_country_id = null;

	/**
	 * Contains the regions data for all countries, potentially a much bigger data set than regions for
	 * active countires
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 * @var object WPSC_Data_Map
	 */
	private static $all_wpsc_region_from_region_id = null;

	/**
	 * Constructor of an WPSC_countries instance. Cannot be called publicly
	 *
	 * @access private
	 * @since 3.8.10
	 *
	 * @param string $id Optional. Defaults to 0.
	 */
	public function __construct() {

		//self::$iso_map = new WPSC_Data_Map( 'iso' );
		//self::$code_map = new WPSC_Data_Map( 'code' );

		if ( self::$countries == null ) {
			self::restore_myself();
			self::$countries					= new WPSC_Data_Map( '$countries' );
			self::$region_id_to_region_code_map = new WPSC_Data_Map( '$region_id_to_region_code_map' );
			self::$country_iso_code_map         = new WPSC_Data_Map( '$country_iso_code_map' );
			self::$country_names                = new WPSC_Data_Map( '$country_names' );
			self::$country_code_map             = new WPSC_Data_Map( '$country_code_map' );
			self::$region_id_to_country_id_map  = new WPSC_Data_Map( '$region_id_to_country_id_map', array( __CLASS__, 'create_region_id_region_object_map' ) );
			//self::$wpsc_country_from_country_id = new WPSC_Data_Map( 'wpsc_country_from_country_id' );
		}

		if ( self::$countries == null ) {
			self::_create_country_from_country_id_maps();
			self::save_myself();
		}

		self::$_initialized = true;
	}


	/**
	 * Create the empty maps used by this class to do it's work.
	 *
	 * This functions contributes greatly to the performance of the class.  Data maps that are named
	 * can store and retrieve themselves at the time of the first request.  That means they don'tneed to
	 * be rebuilt every time, nor does all of the data have to be loaded and waiting for a request that
	 * may never come.
	 *
	 * What this means is that we use unnamed maps for data that is small, or has a very very high
	 * probability of being requested. The unnamed maps get serialized with this main class.
	 *
	 * We use named maps for large data sets that might not be accessed.
	 *
	 * As an example the list of all countries known to WPeC might never be accessed becuase WPeC
	 * mostly looks at only the active countries.  Not retriving the big list at startup
	 * improves performance, especcially for smaller sites not using caching, becuase the dataset isn't
	 * requested in the intial database transaction.
	 *
	 * @access private
	 * @static
	 * @since 3.8.14
	 *
	 */
	private static function _clean_data_maps() {
		// maps without names will be loaded with the core class

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$region_id_to_region_code_map = new WPSC_Data_Map( '$region_id_to_region_code_map' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$country_iso_code_map = new WPSC_Data_Map( '$country_iso_code_map' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$country_names = new WPSC_Data_Map( '$country_names' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$country_code_map = new WPSC_Data_Map( '$country_code_map' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$active_wpsc_country_from_country_id   = new WPSC_Data_Map( '$active_wpsc_country_from_country_id' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$all_wpsc_country_from_country_id      = new WPSC_Data_Map( '$all_wpsc_country_from_country_id' );
		}

		// maps with names can optionally reload thier data themselves when the first request is processed
		if ( is_a( self::$region_id_to_region_code_map, 'WPSC_Data_Map' ) ) {
			self::$region_id_to_region_code_map->clear();
		} else {
			self::$all_wpsc_region_from_region_id        = new WPSC_Data_Map( '$all_wpsc_region_from_region_id' );
		}

	}

	public static function _create_country_from_country_id_maps() {

		self::clear_cache();

		// if we are re-creating this data map, re-create all the others also!
		self::$region_id_to_region_code_map = new WPSC_Data_Map( '$region_id_to_region_code_map' );
		self::$country_iso_code_map         = new WPSC_Data_Map( '$country_iso_code_map' );
		self::$country_names                = new WPSC_Data_Map( '$country_names' );
		self::$country_code_map             = new WPSC_Data_Map( '$country_code_map' );
		self::$region_id_to_country_id_map  = new WPSC_Data_Map( '$region_id_to_country_id_map', array( __CLASS__, 'create_region_id_region_object_map' ) );

		self::$active_wpsc_country_from_country_id   = new WPSC_Data_Map( '$active_wpsc_country_from_country_id' );
		self::$all_wpsc_country_from_country_id      = new WPSC_Data_Map( '$all_wpsc_country_from_country_id' );
		self::$all_wpsc_region_from_region_id        = new WPSC_Data_Map( '$all_wpsc_region_from_region_id' );

		self::$currencies = new WPSC_Data_Map( '$currencies' );

		global $wpdb;

		// now countries is a list with the key being the integer country id, the value is the country data
		$sql = 'SELECT id,
						country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible
					FROM `' . WPSC_TABLE_CURRENCY_LIST . '` WHERE `visible`= "1" ORDER BY id ASC';

		$countries_array = $wpdb->get_results( $sql, OBJECT_K );
		self::_add_country_arrays_to_wpsc_country_map( $countries_array, self::$active_wpsc_country_from_country_id );

		// there are also invisible countries
		$sql = 'SELECT id,
						country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible
					FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY id ASC';

		$countries_array = $wpdb->get_results( $sql, OBJECT_K );
		self::_add_country_arrays_to_wpsc_country_map( $countries_array, self::$all_wpsc_country_from_country_id  );

		// now countries lists are a list with the key being the integer
		// country id, the value is the country data

		// build a global active currency list
		$sql = 'SELECT DISTINCT code, symbol, symbol_html, currency FROM `' . WPSC_TABLE_CURRENCY_LIST . '` ORDER BY code ASC';
		$currencies = $wpdb->get_results( $sql, OBJECT_K );

		foreach ( $currencies as $currency_code => $currency ) {
			$wpsc_currency = new WPSC_Currency( $currency->code, $currency->symbol, $currnecy->symbol_html, $currency->currency );
			self::$currencies->map( $currency_code, $wpsc_currency );
		}

		self::save_myself();
	}

	private static function _add_country_arrays_to_wpsc_country_map( $countries_array, $data_map ) {

		global $wpdb;

		// build an array to map from iso code to country, while we do this get any region data for the country
		foreach ( $countries_array as $country_id => $country ) {

			// take this opportunity to clean up any types that have been turned into text by the query
			$country->id          = intval( $countries_array[$country_id]->id );
			$country->has_regions = $countries_array[$country_id]->has_regions == '1';
			$country->visible     = $countries_array[$country_id]->visible == '1';

			if ( ! empty( $country->tax ) && ( is_int( $country->tax ) ) || is_float( $country->tax ) ) {
				$country->tax = floatval( self::$countries[$country_id]->tax );
			}

			self::$country_iso_code_map->map( $country->isocode, $country->id );
			self::$country_names->map( $country->country, $country->id );
			self::$country_code_map->map( $country->code, $country->id );

			if ( $country->has_regions ) {
				$sql = 'SELECT code, country_id, name, tax, id FROM `' . WPSC_TABLE_REGION_TAX . '` '
						. ' WHERE `country_id` = %d '
								. ' ORDER BY code ASC ';

				// put the regions list into our country object
				$regions = $wpdb->get_results( $wpdb->prepare( $sql, $country_id ) , OBJECT_K );

				$country->region_id_to_region_code_map = array();

				// any properties that came in as text that should be numbers or boolean get adjusted here, we also build
				// an array to map from region code to region id
				foreach ( $regions as $region_code => $region ) {
					$region->id         = intval( $region->id );
					$region->country_id = intval( $region->country_id );
					$region->tax        = floatval( $region->tax );

					$country->region_id_to_region_code_map[$region->id] = $region->code;

					// create a new empty region object, then copy our region data into it.
					$wpsc_region = new WPSC_Region( null, null );
					$wpsc_region->_copy_properties_from_stdclass( $region );
					self::$all_wpsc_region_from_region_id->map( $region->id, $wpsc_region );
				}

				ksort( $country->region_id_to_region_code_map );
			}

			// create a new empty country object, then copy our region data into it.
			$wpsc_country = new WPSC_Country( null );
			$wpsc_country->_copy_properties_from_stdclass( $country );
			$data_map->map( $country_id, $wpsc_country );
		}

		return $countries_array;
	}


	private static function _convert_country_arrays_to_objects( $countries_array ) {

		global $wpdb;

		// build an array to map from iso code to country, while we do this get any region data for the country
		foreach ( $countries_array as $country_id => $country ) {

			// take this opportunity to clean up any types that have been turned into text by the query
			$country->id          = intval( $countries_array[$country_id]->id );
			$country->has_regions = $countries_array[$country_id]->has_regions == '1';
			$country->visible     = $countries_array[$country_id]->visible == '1';

			if ( ! empty( $countries_array[$country_id]->tax ) && ( is_int( $countries_array[$country_id]->tax ) ) || is_float( $countries_array[$country_id]->tax ) ) {
				$country->tax = floatval( self::$countries[$country_id]->tax );
			}

			self::$country_iso_code_map->map( $country->isocode, $country->id );
			self::$country_names->map( $country->country, $country->id );
			self::$country_code_map->map( $country->code, $country->id );

			if ( $country->has_regions ) {
				$sql = 'SELECT code, country_id, name, tax, id FROM `' . WPSC_TABLE_REGION_TAX . '` '
						. ' WHERE `country_id` = %d '
								. ' ORDER BY code ASC ';

				// put the regions list into our country object
				$countries_array[$country_id]->regions = $wpdb->get_results( $wpdb->prepare( $sql, $country_id ) , OBJECT_K );

				$countries_array[$country_id]->region_id_to_region_code_map = array();

				// any properties that came in as text that should be numbers or boolean get adjusted here, we also build
				// an array to map from region code to region id
				foreach ( $countries_array[$country_id]->regions as $region_code => $region ) {
					$region->id         = intval( $region->id );
					$region->country_id = intval( $region->country_id );
					$region->tax        = floatval( $region->tax );

					$countries_array[$country_id]->region_id_to_region_code_map[$region->id] = $region->code;

					// create a new empty region object, then copy our region data into it.
					$countries_array[$country_id]->regions[$region_code] = new WPSC_Region( null, null );
					$countries_array[$country_id]->regions[$region_code]->_copy_properties_from_stdclass( $region );

				}

				ksort( $countries_array[$country_id]->region_id_to_region_code_map );
			}

			// create a new empty country object, then copy our region data into it.
			$countries_array[$country_id] = new WPSC_Country( null );
			$countries_array[$country_id]->_copy_properties_from_stdclass( $country );

		}

		return $countries_array;
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

		$mydata['countries'] = array();


		// when we clear the cached copy of the sdata, we also clear the resident copy of the data
		// so it is rebuilt and stays in sync
		self::_clean_data_maps();
		self::$_initialized = false;
	}

	/**
	 * Save the structured county data
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @return none
	 */
	private function save_myself() {
		$mydata = array();

		// which maps to we want to have available as soon as this class is initialized?  Serialize those
		// maps into the saved verison of this object.
		$mydata['country_iso_code_map']     = self::$country_iso_code_map;
		$mydata['country_code_map']         = self::$country_code_map;
		$mydata['country_names']            = self::$country_names;
		$mydata['countries']                = self::$countries;
		$mydata['invisible_countries']      = self::$invisible_countries;
		$mydata['$currencies']              = self::$currencies;

		$mydata['$region_id_to_region_code_map'] = self::$region_id_to_region_code_map;
		$mydata['$country_iso_code_map']         = self::$country_iso_code_map;
		$mydata['$country_names']                = self::$country_names;
		$mydata['$country_code_map']             = self::$country_code_map;
		$mydata['$region_id_to_country_id_map']  = self::$region_id_to_country_id_map;

		$mydata['$active_wpsc_country_from_country_id'] = self::$active_wpsc_country_from_country_id   = new WPSC_Data_Map( 'active_wpsc_country_from_country_id' );
		$mydata['$all_wpsc_country_from_country_id']    = self::$all_wpsc_country_from_country_id      = new WPSC_Data_Map( 'all_wpsc_country_from_country_id' );
		$mydata['$all_wpsc_region_from_region_id']      = self::$all_wpsc_region_from_region_id        = new WPSC_Data_Map( 'wpsc_region_from_region_id' );



		set_transient( self::transient_name(), $this, WEEK_IN_SECONDS );

		$data = get_transient( self::transient_name() );
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

		if ( count( $mydata ) == 6 ) {

			if (
					is_array( $mydata['countries'] )
						&& is_array( $mydata['invisible_countries'] )
								&& is_array( $mydata['currencies'] )
				) {
					self::$countries            = $mydata['$countries'];
					self::$invisible_countries  = $mydata['invisible_countries'];
					self::$currencies           = $mydata['currencies'];
					$have_data = true;
			}
		}

		if ( ! $have_data && ( $mydata === false ) ) {
			self::clear_cache();
		}

		//self::create_region_id_region_object_map();

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
	 * Create a master map of region ids to region objects
	 *
	 * @access private static
	 *
	 * @since 3.8.10
	 *
	 * @return none
	 */
	public static function create_region_id_region_object_map( $data_map = null ) {
		foreach ( self::$countries as $country_id => $country ) {
			foreach ( $country->regions() as $region_id => $region_code ) {
				$data_map->map( $region_id, $country_id );
			}
		}

		foreach ( self::$invisible_countries as $country_id => $country ) {
			foreach ( $country->regions() as $region_id => $region_code ) {
				$data_map->map( $region_id, $country_id );
			}
		}
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
		if ( ! self::$_initialized ) {
			$an_instance = new WPSC_Countries();
		}

		return self::$_initialized;
	}
}
