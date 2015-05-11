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

	const INCLUDE_INVISIBLE = true;
	const DO_NOT_INCLUDE_INVISIBLE = false;

	/** Refers to a single instance of this class. */
	private static $instance = null;

	/**
	 * Array of visible countries, indexed by country id
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var WPSC_Country[]
	 */
	private static $countries_by_id = array();

	/**
	 * Array of unique known countries, indexed by is code
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var WPSC_Country[]
	 */
	private static $countries_by_iso_code = array();

	/**
	 * Array of visible countries, indexed by country id
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var WPSC_Country[]
	 */
	private static $visible_countries_by_id = array();

	/**
	 * Array of unique known regions, indexed by region id
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var WPSC_Region[]
	 */
	private static $regions_by_region_id = array();

	/**
	 * Array of unique known currencies, indexed by currency code
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var WPSC_Currency[]
	 */
	private static $currencies = array();

	/**
	 * Array of countryiso codes that have had thier visibility modified from the default
	 *
	 * @access private
	 * @static
	 *
	 * @since 4.1
	 *
	 * @var bool[]
	 */
	private static $country_visibility_overrides;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return  WPSC_Countries A single instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	} // end get_instance;

	/**
	 * Change an country ISO code into a country id, if a country id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param int | string country being checked, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int | boolean     integer country id on success, false on failure
	 */
	public static function get_country_id( $country ) {

		// set default return value
		$country_id = false;

		if ( $country ) {
			if ( is_numeric( $country ) ) {
				$country_id = intval( $country );
			} elseif ( is_string( $country ) ) {
				if ( isset( self::$countries_by_iso_code[ $country ] ) ) {
					$country_id = self::$countries_by_iso_code[ $country ]->get_id();
				} else {
					_wpsc_doing_it_wrong( 'WPSC_Countries::country_id', __( 'Method "get_country_id" of WPSC_Countries requires a valid integer country code or a string ISO code ', 'wpsc' ), '4.1' );
				}
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
	 * @param int|string $country country being checked, if noon-numeric country is treated as an isocode, number is the country id
	 *
	 * @return int|boolean                  integer country id on success, false on failure
	 */
	public static function get_country_isocode( $country ) {
		$iso_code = false;

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country ) {
			$iso_code = $wpsc_country->get_code();
		}

		return $iso_code;
	}

	/**
	 * Change an region code into a region id, if a region id is passed it is returned intact
	 *
	 * @access public
	 * @static
	 *
	 * @since 3.8.14
	 *
	 * @param int|string $country country being checked, if non-numeric country is treated as an isocode, number is the country id
	 * @param int|string $region region being checked, if non-numeric region is treated as an code, number is the region id
	 *
	 * @return int|boolean    integer country id on success, false on failure
	 */
	public static function get_region_id( $country, $region ) {

		// set default return value
		$region_id = false;

		if ( is_numeric( $region ) ) {
			$region_id = intval( $region );
		} else {
			$wpsc_country = self::get_country_using_code_or_id( $country );

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
	 * @param int|string|null optional if non-numeric country is treated as an ISO code, number is the country id
	 *
	 * @param int|string required if non-numeric country is treated as an region code, number is the region id,
	 *        if the region id is passed then country_id is ignored
	 *
	 * @return WPSC_Region boolean object or false on failure
	 *
	 */
	public static function get_region( $country, $region ) {

		// set default return value
		$wpsc_region = false;

		if ( is_numeric( $region ) ) {
			$region_id = intval( $region );
		} else {
			$wpsc_country = self::get_country_using_code_or_id( $country );

			if ( $wpsc_country && $wpsc_country->has_regions() ) {
				$region_id = $wpsc_country->get_region_id_by_region_code( $region );
			}
		}

		// we want to get to the unique region id to retrieve the region object, it might have been passed, or we
		// will have to figure it out from the country and the region
		if ( is_int( $region ) ) {

			$region_id = $region;
			if ( isset( self::$regions_by_region_id[ $region ] ) ) {
				$wpsc_region = self::$regions_by_region_id[ $region ];
			}
		} else {
			$wpsc_country = self::get_country_using_code_or_id( $country );

			if ( $wpsc_country && $wpsc_country->has_regions() ) {
				$wpsc_region = $wpsc_country->get_region( $region );
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
	 * @param int|string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                    if number used as the country id
	 *
	 * @param boolean $as_array return the result as an array, default is to return the result as an object
	 *
	 * @return object|array|boolean       country information, false on failure
	 */
	public static function get_country( $country, $as_array = false ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

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
	 * @param int | string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                  number is the country id
	 *
	 * @return string currency code for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_code( $country ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

		// set default return value
		$currency_code = '';

		if ( $wpsc_country ) {
			$currency_code = $wpsc_country->get_currency_code();
		}

		return $currency_code;
	}

	/**
	 * The currency symbol for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                   number is the country id
	 *
	 * @return string currency symbol for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_symbol( $country ) {
		// set default return value
		$currency_symbol = '';

		$wpsc_country = self::get_country_using_code_or_id( $country );
		if ( $wpsc_country ) {
			$currency_symbol = $wpsc_country->get_currency_symbol();
		}

		return $currency_symbol;
	}

	/**
	 * The content for a country
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                    number is the country id
	 *
	 * @return string content for the country, or empty string if it is not defined
	 */
	public static function get_continent( $country ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country ) {
			$continent = $wpsc_country->get_continent();
		} else {
			$continent = '';
		}

		return $continent;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                     number is the country id
	 *
	 * @return string currency symbol HTML for the specified country, or empty string if it is not defined
	 */
	public static function get_currency_symbol_html( $country ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country ) {
			$currency_symbol = $wpsc_country->get_currency_symbol_html();
		} else {
			$currency_symbol = '';
		}

		return $currency_symbol;
	}

	/**
	 * The currency_code
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int | string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                     number is the country id
	 *
	 * @param boolean $as_array return the result as an array, default is to return the result as an object
	 *
	 * @return string currency symbol HTML for the specified country, empty stdClass on failure
	 */
	public static function get_currency_data( $country, $as_array = false ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

		$currency_data = new stdClass;

		if ( $wpsc_country ) {
			$currency_data->code        = $wpsc_country->get_currency_code();
			$currency_data->symbol      = $wpsc_country->get_currency_symbol();
			$currency_data->symbol_html = $wpsc_country->get_currency_symbol_html();
			$currency_data->currency    = $wpsc_country->get_currency_name();
		} else {
			$currency_data->code        = '';
			$currency_data->symbol      = '';
			$currency_data->symbol_html = '';
			$currency_data->currency    = '';
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
	 * @param int|string $country country being checked, if noon-numeric country is treated as an
	 *                                   isocode, number is the country id
	 *
	 * @param boolean $as_array the result as an array, default is to return the result as an object
	 *
	 * @return array of region objects index by region id, empty array if no regions
	 */
	public static function get_regions( $country, $as_array = false ) {

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country && $wpsc_country->has_regions() ) {
			$regions = $wpsc_country->get_regions( $as_array );
		} else {
			$regions = array();
		}

		return $regions;
	}

	/**
	 * The Countries as array of WPSC_Countries
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param boolean $include_invisible return countries that are set to invisible
	 * @param boolean $sortbyname return countries ordered by name
	 *
	 * @return WPSC_Country[] array of countires  indexed by country id sorted by country name
	 */
	public static function get_countries( $include_invisible = false, $sortbyname = true ) {

		if ( $include_invisible ) {
			$countries = self::$countries_by_id;
			foreach ( $countries as $country_id => $wpsc_country ) {
				$country_is_legacy = (bool) $wpsc_country->get( '_is_country_legacy' );
				if ( $country_is_legacy ) {
					unset( $countries[ $country_id ] );
				}
			}
		} else {
			$countries = self::$visible_countries_by_id;
		}

		if ( $sortbyname && ! empty( $countries ) ) {
			uasort( $countries, array( __CLASS__, '_compare_countries_by_name' ) );
		} else {
			// countries should be sorted internally by id, but just in case data was changed since the last data load
			uasort( $countries, array( __CLASS__, '_compare_countries_by_id' ) );
		}

		return apply_filters( 'wpsc_get_countries', $countries );
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

		$countries      = self::get_countries( $include_invisible, $sortbyname );
		$countries_list = array();

		foreach ( $countries as $country_id => $wpsc_country ) {
			$countries_list[ $country_id ] = $wpsc_country->get_array();
		}

		return apply_filters( 'wpsc_get_countries_array', $countries_list );

	}

	/*
	 * Get a WPSC_country using the numeric country id or the string country code
	 *
	 * @param int|string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                  number is the country id
	 *
	 * return WPSC_Country
	 */
	private static function get_country_using_code_or_id( $country_code_or_id ) {
		$wpsc_country = false;
		if ( is_numeric( $country_code_or_id ) ) {
			if ( isset( self::$countries_by_id[ $country_code_or_id ] ) ) {
				$wpsc_country = self::$countries_by_id[ $country_code_or_id ];
			}
		} else {
			if ( isset( self::$countries_by_iso_code[ $country_code_or_id ] ) ) {
				$wpsc_country = self::$countries_by_iso_code[ $country_code_or_id ];
			}
		}

		return $wpsc_country;
	}

	/**
	 * How many regions does the country have
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string $country country being checked, if non-numeric country is treated as an isocode,
	 *                                  number is the country id
	 *
	 * @return int count of regions in a country, if region is invalid 0 is returned
	 */
	public static function get_region_count( $country ) {
		$region_count = 0;

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country ) {
			$region_count = $wpsc_country->get_region_count();
		}

		return $region_count;
	}

	/**
	 * Does the country have regions
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @param int|string $country country being checked, if noon-numeric country is treated as an isocode,
	 *                                     number is the country
	 *
	 * @return true if the country has regions, false otherwise
	 */
	public static function country_has_regions( $country ) {

		// set default return value
		$has_regions = false;

		$wpsc_country = self::get_country_using_code_or_id( $country );

		if ( $wpsc_country ) {
			$has_regions = $wpsc_country->has_regions();
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
		$country_names = array();

		foreach ( self::$countries_by_iso_code as $country_id => $wpsc_country ) {
			$country_names[ $country_id ] = $wpsc_country->get_name();
		}

		asort( $country_names );

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
	 * @param $code $as_array return the result as an array, default is to return the result as an object
	 * @param boolean $as_array return the result as an array, default is to return the result as an object
	 *
	 * @return boolean|WPSC_Currency|array
	 */
	public static function get_currency( $code, $as_array = false ) {

		if ( isset( self::$currencies[$code] ) ) {
			$wpsc_currency = self::$currencies[ $code ];

			if ( $as_array && $wpsc_currency ) {
				$wpsc_currency = $wpsc_currency->as_array();
			}
		} else {
			if ( $as_array ) {
				$wpsc_currency = array();
			} else {
				$wpsc_currency = false;
			}

		}

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

		$currencies = self::$currencies;

		if ( $as_array ) {
			$currencies_list = array();

			foreach ( $currencies as $currencies_key => $currency ) {
				$currency_array                             = get_object_vars( $currency );
				$currency_array['currency']                 = $currency_array['name'];   // some  legacy code looks for 'currency' rather than name, so we put both in the array
				$currencies_list[ $currency_array['code'] ] = $currency_array;
			}

			$currencies = $currencies_list;
		}

		// we have the return value in our country name to id map,
		// all we have to do is swap the keys with the values
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
	 * @param int $region_id region identifier
	 *
	 * @return int|boolean country identifier, false on failure
	 */
	public static function get_country_id_by_region_id( $region_id ) {

		if ( is_numeric( $region_id ) ) {
			$region_id = intval( $region_id );
		} else {
			$region_id = 0;
		}

		if ( ! $region_id ) {
			_wpsc_doing_it_wrong( 'WPSC_Countries::get_country_id_by_region_id', __( 'Function "get_country_id_by_region_id" requires an integer $region_id', 'wpsc' ), '3.8.14' );

			return false;
		}

		if ( isset( self::$regions_by_region_id[ $region_id ] ) ) {
			$wpsc_region = self::$regions_by_region_id[ $region_id ];
			$country_id  = $wpsc_region->get_country_id();
		} else {
			$country_id = false;
		}

		return $country_id;
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
		$wpsc_country = self::get_country_using_code_or_id( $country_code );
		if ( $wpsc_country ) {
			$country_id = $wpsc_country->get_id();
		} else {
			$country_id = false;
		}

		return $country_id;
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
	 * Constructor of an WPSC_countries instance
	 *
	 * @access protected
	 * @since 3.8.14
	 *
	 */
	protected function __construct() {

		self::$country_visibility_overrides = get_option( 'wpsc_country_visibility_overrides', array() );

		if ( ! empty( self::$country_visibility_overrides ) ) {
			add_action( 'wpsc_is_country_visible', array( &$this, '_get_country_visible_override' ) , 10, 2 );
		}

		self::$countries_by_id         = array();
		self::$visible_countries_by_id = array();
		self::$countries_by_iso_code   = array();

		$countries_data_array = _wpsc_get_countries_data_array();

		foreach ( $countries_data_array as $country_id => $country_data_array ) {
			$country_data_array['id'] = $country_id;
			$wpsc_country             = new WPSC_Country( $country_data_array );

			if ( $wpsc_country->get_id() && $wpsc_country->get_isocode() ) {
				self::$countries_by_iso_code[ $wpsc_country->get_isocode() ] = $wpsc_country;

				self::$countries_by_id[ $wpsc_country->get_id() ] = $wpsc_country;

				if ( $wpsc_country->is_visible() ) {
					self::$visible_countries_by_id[ $wpsc_country->get_id() ] = $wpsc_country;
				}

				self::$currencies[ $country_data_array['currency_code'] ] = new WPSC_Currency( $country_data_array['currency_code'], $country_data_array['currency_symbol'], $country_data_array['currency_symbol_html'], $country_data_array['name'] );
			}
		}

		$regions_data_arrays = _wpsc_get_regions_data_array();

		foreach ( $regions_data_arrays as $region_id => $region_data_array ) {
			$region_data_array['id'] = $region_id;
			$wpsc_region             = new WPSC_Region( $region_data_array['country_id'], $region_data_array );

			self::$regions_by_region_id[ $wpsc_region->get_id() ] = $wpsc_region;

			if ( isset( self::$countries_by_id[ $wpsc_region->get_country_id() ] ) ) {
				self::$countries_by_id[ $wpsc_region->get_country_id() ]->add_region( $wpsc_region );
			}
		}

		add_action( 'wpsc_country_visibility_changed', array( &$this, 'save_country_visibility_override' ) , 10, 1 );
	}

	/**
	 * @param WPSC_Country $wpsc_country
	 */
	function _get_country_visible_override( $visibility, $wpsc_country ) {
		if ( isset( self::$country_visibility_overrides[ $wpsc_country->get_isocode() ] ) ) {
			$visibility =  self::$country_visibility_overrides[ $wpsc_country->get_isocode() ];
		}

		return $visibility;
	}


	static function set_visibility( $visibility ) {
		foreach ( self::$countries_by_id as $country_id => $wpsc_country ) {
			$wpsc_country->set_visible( $visibility );
		}
	}

	/**
	 * Save any cahnges to a countries visibility persistantly
	 *
	 * @param WPSC_Country $wpsc_country
	 */
	function save_country_visibility_override( $wpsc_country ) {
		$update_saved_option = false;

		$country_visibility_overrides = get_option( 'wpsc_country_visibility_overrides', -1 );
		if ( -1 == $country_visibility_overrides ) {
			// make sure the option is initialized
			$update_saved_option = true;
			$country_visibility_overrides = array();
		}

		if ( isset( $country_visibility_overrides[ $wpsc_country->get_isocode() ] ) ) {
			if ( $wpsc_country->is_visible() == $wpsc_country->default_is_visible() ) {
				unset( $country_visibility_overrides[ $wpsc_country->get_isocode() ] );
				$update_saved_option = true;
			}
		} else {
			if ( $wpsc_country->is_visible() != $wpsc_country->default_is_visible() ) {
				$country_visibility_overrides[ $wpsc_country->get_isocode() ] = $wpsc_country->is_visible();
				$update_saved_option = true;
			}
		}

		if ( $update_saved_option ) {
			update_option( 'wpsc_country_visibility_overrides', $country_visibility_overrides );
		}
	}


	/**
	 * @param WPSC_Country $wpsc_country
	 * @param bool $default
	 *
	 * @return bool
	 */
	private function get_country_visibilty_with_override( $wpsc_country, $default = true ) {

		$result = $default;
		$country_visibility_overrides = get_option( 'wpsc_country_visibility_overrides', -1 );
		if ( is_array( $country_visibility_overrides ) && isset( $country_visibility_overrides[ $wpsc_country->get_isocode() ] ) ) {
			$result = $country_visibility_overrides[ $wpsc_country->get_isocode() ];
		}

		return $result;

	}

	static function get_all_regions( ) {
		return self::$regions_by_region_id;
	}

	/**
	 * Returns a count of how many fields are in the checkout form
	 *
	 * @access public
	 * @since 3.8.14
	 *
	 * @return array
	 */
	static function get_countries_count() {
		return count( self::$countries_by_id );
	}

	/**
	 * Compare countries using country's name
	 *
	 * @param WPSC_Country $a instance of WPSC_Country class
	 * @param WPSC_Country $b instance of WPSC_Country class
	 *
	 * @return int 0 if country names are the same, -1 if country name of a comes before country b, 1 otherwise
	 */
	private static function _compare_countries_by_name( $a, $b ) {
		return strcmp( $a->get_name(), $b->get_name() );
	}


	/**
	 * Compare countries using country's id
	 *
	 * @param WPSC_Country $a instance of WPSC_Country class
	 * @param WPSC_Country $b instance of WPSC_Country class
	 *
	 * @return int  if country id's are the same, -1 if country id of a comes before country b, 1 otherwise
	 */
	private static function _compare_countries_by_id( $a, $b ) {
		if ( $a->get_id() == $b->get_id() ) {
			return 0;
		}

		return ( $a->get_id() < $b->get_id() ) ? - 1 : 1;
	}

	/**
	 * Private clone method to prevent cloning of the instance of the this instance.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Private unserialize method to prevent unserializing of the this instance.
	 *
	 * @return void
	 */
	private function __wakeup() {
	}

}


/**
 * Make the countries data available to WP eCommerce
 *
 * @return void
 */
function _wpsc_make_countries_data_available() {
	$wpsc_countries = WPSC_Countries::get_instance();
}

add_action( 'wpsc_loaded', '_wpsc_make_countries_data_available' );


