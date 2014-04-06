<?php


/**
 * a geographic nation
 *
 * @access public
 *
 * @since 3.8.14
 *
 * @param int|string 	required	the nation (country) identifier, can be the string iso code, or the numeric wpec country id
 *
 * @return object WPSC_Country
 */
class WPSC_Country {

	/**
	 * a geographic nation constructor
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string|array 	required 	the country identifier, can be the string iso code,
	 * 										or the numeric wpec country id,
	 * 										or an array of data used to create a new country
	 *
	 * @return object WPSC_Country
	 */
	public function __construct( $country_id_or_isocode_or_new_country_data, $deprecated_paramater_col = null ) {

		if ( $country_id_or_isocode_or_new_country_data ) {

			if ( is_array( $country_id_or_isocode_or_new_country_data ) ) {
				// if we get an array as an argument we are making a new country
				$country_id_or_isocode = $this->_save_country_data( $country_id_or_isocode_or_new_country_data );
			}  else {
				// we are constructing a country using a numeric id or iso code
				$country_id_or_isocode = $country_id_or_isocode_or_new_country_data;
			}

			// make sure we have a valid country id
			$country_id = WPSC_Countries::country_id( $country_id_or_isocode );
			if ( $country_id ) {
				$country = WPSC_Countries::country( $country_id );
				foreach ( $country as $property => $value ) {
					// copy the properties in this copy of the country
					$this->$property = $value;
				}
			}
		}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// As a result of merging the legacy WPSC_Country class we no longer need the "col" constructor parameter
		// that was in the prior version of this class.
		//
		// if deprecated processing is enabled we will give a message, just as if we were allowed to put class
		// methods in the deprecated file, if deprecated processing is not enabled we exit with the method, much
		// like would happen with an undefined function call.
		//
		// TODO: This processing is added at version 3.8.14 and intended to be removed after a reasonable number
		// of interim releases. See GitHub Issue https://github.com/wp-e-commerce/WP-e-Commerce/issues/1016
		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		if ( ! empty ( $deprecated_paramater_col) ) {
			if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
				_wpsc_deprecated_argument( __FUNCTION__, '3.8.14',$this->_parameter_no_longer_used_message( 'col', __FUNCTION__ ) );
			} else {
				wp_die(  $this->_parameter_no_longer_used_message( 'col', __FUNCTION__ ) );
			}
		}
	}

	/**
	 * get nation's(country's) name
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation name
	 */
	public function name() {
		return $this->_name;
	}

	/**
	 * get nation's (country's) id
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return void
	 */
	public function id() {
		return $this->_id;
	}

	/**
	 * get nation's (country's) ISO code
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string country ISO code
	 */
	public function isocode() {
		return $this->_isocode;
	}

	/**
	 * get nation's (country's) currency name
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency name
	 */
	public function currency_name() {
		return $this->_currency_name;
	}

	/**
	 * get nation's (country's) currency symbol
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string	currency symbol
	 */
	public function currency_symbol() {
		return $this->_currency_symbol;
	}

	/**
	 * get nation's (country's) currency symbol HTML
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency symbol HTML
	 */
	public function currency_symbol_html() {
		return $this->_currency_symbol_html;
	}

	/**
	 * get nation's (country's) currency code
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency code
	 */
	public function currency_code() {
		return $this->_currency_code;
	}

	/**
	 * does the nation use a region list
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param
	 *
	 * @return boolean	true if we have a region lsit for the nation, false otherwise
	 */
	public function has_regions() {
		return $this->_has_regions;
	}

	/**
	 *  get nation's (country's) tax rate
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return float	nations tax rate
	 */
	public function tax() {
		return $this->_tax;
	}

	/**
	 *  get nation's (country's) continent
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param
	 *
	 * @return string	nation's continent
	 */
	public function continent() {
		return $this->_continent;
	}

	/**
	 * should the country be displayed to the user
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return boolean true if the country should be displayed, false otherwise
	 */
	public function visible() {
		return $this->_visible;
	}

	/**
	 * get a region that is in a country
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string	required	the region identifier, can be the text region code, or the numeric region id
	 *
	 * @return WPSC_Region|boolean The region, or false if the region code is not valid for the counry
	 */
	public function region( $region_id_or_code ) {

		$region = false;

		if ( $this->_id ) {
			if ( $region_id = WPSC_Countries::region_id( $this->_id, $region_id_or_code ) ) {
				$region = new WPSC_Region( $this->_id, $region_id_or_code );
			}
		}

		return $region;
	}

	/**
	 * how many regions does the nation (country) have
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string	required	the region identifier, can be the text region code, or the numeric region id
	 *
	 * @return WPSC_Region
	 */
	public function region_count() {
		return count( $this->_regions );
	}

	/**
	 * get a list of regions for this country as an array of WPSC_Region objects
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param boolean return the result as an array, default is to return the result as an object
	 *
	 * @return array of WPSC_Region
	 */
	public function regions() {
		return $this->_regions;
	}

	/**
	 * get a list of regions for this country as an array of arrays
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param boolean return the result as an array, default is to return the result as an object
	 *
	 * @return array of WPSC_Region
	 */
	public function regions_array() {

		$regions = $this-> regions();
		$json  = json_encode( $regions );
		$regions = json_decode( $json, true );

		return $regions;
	}

	/**
	 * get a region code from a region id
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 *
	 * @return string region code
	 */
	public function region_code_from_region_id( $region_id ) {
		$region_code = false;

		if ( isset( $this->_regions[$region_id] ) ) {
			$region_code = $this->region_id_to_region_code_map[$region_id];
		}

		return $region_code;
	}

	/**
	 * get a region code from a region id
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 *
	 * @return int region id
	 */
	public function region_id_from_region_code( $region_code ) {
		$region_id = false;

		if ( isset( $this->_regions[$region_code] ) ) {
			$region_id = $this->_regions[$region_code]->id();
		}

		return $region_id;
	}

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
	public function _copy_properties_from_stdclass( $country ) {

		$this->_id 								= $country->id;
		$this->_name 							= $country->country;
		$this->_isocode 						= $country->isocode;
		$this->_currency_name					= $country->currency;
		$this->_currency_symbol 				= $country->symbol;
		$this->_currency_symbol_html			= $country->symbol_html;
		$this->_currency_code					= $country->code;
		$this->_has_regions 					= $country->has_regions;
		$this->_tax 							= $country->tax;
		$this->_continent 						= $country->continent;
		$this->_visible 						= $country->visible;

		if ( property_exists( $country, 'region_id_to_region_code_map' ) ) {
			$this->_region_id_to_region_code_map 	= $country->region_id_to_region_code_map;
		}

		if ( property_exists( $country, 'regions' ) ) {
			$this->_regions 						= $country->regions;
		}
	}

	/**
	 * saves country data to the databse
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param array  key/value pairs that are put into the database columns
	 *
	 * @return int|boolean country_id on success, false on failure
	 */
	public function _save_country_data( $country_data ) {
		global $wpdb;

		/*
		 * We need to figure out if we are updating an existing country. There are three
		 * possible unique identifiers for a country.  Look for a row that has any of the
		 * identifiers.
		 */
		$country_id       = isset( $country_data['id'] ) ? intval( $country_data['id'] ) : 0;
		$country_code     = isset( $country_data['code'] ) ? $country_data['code'] : '';
		$country_iso_code = isset( $country_data['isocode'] ) ? $country_data['isocode'] : '';

		/*
		 *  If at least one of the key feilds ins't present we aren'y going to continue, we can't reliably update
		 *  a row in the table, nor could we insrt a row that could reliably be updated.
		 */
		if ( empty( $country_id ) && empty( $country_code ) && empty( $country_iso_code ) ) {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'To insert a country one of country id, country code or country ISO code must be included.', 'wpsc' ), '3.8.11' );
			return false;
		}

		$sql = $wpdb->prepare(
				'SELECT id FROM ' . WPSC_TABLE_CURRENCY_LIST . ' WHERE (`id` = %d ) OR ( `code` = %s ) OR ( `isocode` = %s ) ',
				$country_id,
				$country_code,
				$country_iso_code
			);

		$country_id_from_db = $wpdb->get_var( $sql );

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

		// clear the cahned data, force a rebuild
		WPSC_Countries::clear_cache();

		return $country_id_from_db;
	}

	/**
	 * A country's properties, these are private to this class (notice the prefix '_'!).  They are marked as public so that
	 * object serialization will work properly
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param
	 *
	 * @return void
	 */
	public $_id 							= null;
	public $_name 							= null;
	public $_isocode 						= null;
	public $_currency_name 				= '';
	public $_currency_symbol 				= '';
	public $_currency_symbol_html 			= '';
	public $_code 							= '';
	public $_has_regions 					= false;
	public $_tax 							= '';
	public $_continent 					= '';
	public $_visible 						= true;
	public $_regions 						= array();
	public $_region_id_to_region_code_map 	= array();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	// As a result of merging the legacy WPSC_Country class we no longer need several of the public class
	// functions that where in the prior version of this class.
	//
	// if deprecated processing is enabled we will give a message, just as if we were allowed to put class
	// methods in the deprecated file, if deprecated processing is not enabled we exit with the method, much
	// like would happen with an undefined function call.
	//
	// TODO: This processing is added at version 3.8.14 and intended to be removed after a reasonable number
	// of interim releases. See GitHub Issue https://github.com/wp-e-commerce/WP-e-Commerce/issues/1016

	/////////////////////////////////////////////////////////////////////////////////////////////////////////

	public static function get_outdated_isocodes() {
		// TODO: Move this to the database
		$outdated_isocodes = array(
				'YU',
				'UK',
				'AN',
				'TP',
				'GF',
		);

		return $outdated_isocodes;
	}


	/*
	 * deprected since 3.8.14
	*/
	public static function get_all( $include_invisible = false ) {

		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::country()';

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( $function, '3.8.14', $replacement );
		} else {
			wp_die( self::_function_not_available_message( $function , $replacement ) );
		}

		$list = WPSC_Countries::countries_array( WPSC_Countries::INCLUDE_INVISIBLE );
		return apply_filters( 'wpsc_country_get_all_countries', $list );
	}

	/*
	 * deprected since 3.8.14
	*/
	public static function get_cache( $value = null, $col = 'id' ) {

		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::country()';

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( $function, '3.8.14', $replacement );
		} else {
			wp_die( self::_function_not_available_message( $function , $replacement ) );
		}

		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );

		// note that we can't store cache by currency code, the code is used by various countries
		// TODO: remove duplicate entry for Germany (Deutschland)
		if ( ! in_array( $col, array( 'id', 'isocode' ) ) ) {
			return false;
		}

		return WPSC_Countries::country( $value, WPSC_Countries::RETURN_AN_ARRAY );
	}

	/*
	 * deprected since 3.8.14
	*/
	public static function update_cache( $data ) {
		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		} else {
			wp_die( self::_function_not_available_message( __FUNCTION__ ) );
		}
	}

	/*
	 * deprected since 3.8.14
	*/
	public static function delete_cache( $value = null, $col = 'id' ) {
		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		} else {
			wp_die( self::_function_not_available_message( __FUNCTION__ ) );
		}
	}

	/*
	 * deprected since 3.8.14
	 */
	public function get( $key ) {

		$function = __CLASS__ . '::' . __FUNCTION__ . '( "' . $key . '" )';
		$replacement = __CLASS__ . '::' . $key . '()';

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( $function, '3.8.14', $replacement );
		} else {
			wp_die( self::_function_not_available_message( $function , $replacement ) );
		}

		$property_name = '_' . $key;

		if ( property_exists( $this, $property_name ) ) {
			return apply_filters( 'wpsc_country_get_property', $this->$property_name, $key, $this );
		}

		return null;
	}

	/**
	 * Returns the whole database row in the form of an associative array
	 *
	 * @deprectated since 3.8.14
	 *
	 * @access public
	 * @since 3.8.11
	 *
	 * @return array
	 */
	public function get_data() {

		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::countries_array()';

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( $function, '3.8.14', $replacement );
		} else {
			wp_die( self::_function_not_available_message( $function , $replacement ) );
		}

		$data = WPSC_Countries::countries_array();

		return apply_filters( 'wpsc_country_get_data', $data, $this );
	}

	/*
	 * @deprecated since 3.8.14
	*
	*/
	public function set( $key, $value = '' ) {
		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::__construct()';

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( $function, '3.8.14', $replacement );
		} else {
			wp_die( self::_function_not_available_message( $function , $replacement ) );
		}

		if ( ! empty( $this->_id ) ){
			if ( ! is_array( $key ) ) {
				$country_data = array( $key => $value );
			}

			$this->_save_country_data( $country_data );
		}

		$data = WPSC_Countries::countries_array();

		return apply_filters( 'wpsc_country_get_data', $data, $this );
	}

	/*
	 * @deprecated since 3.8.14
	*
	*/
	public function save() {
		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		} else {
			wp_die( self::_function_not_available_message( __FUNCTION__ ) );
		}
	}

	/*
	 * @deprecated since 3.8.14
	 *
	 */
	public function exists() {

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_argument( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		} else {
			wp_die( self::_function_not_available_message( __FUNCTION__ ) );
		}


		return true;
	}

	private static function _function_not_available_message( $function = 'called', $replacement = '' ) {
		$message = sprintf(
							__( 'As of version 3.8.14 the function "%s" is no longer available in class %s. Use %s instead', 'wpsc' ),
							$function,
							$replacement,
							__CLASS__
						);

		return $message;
	}

	private static function _parameter_no_longer_used_message( $parameter, $function = 'called' ) {
		$message = sprintf(
				__( 'As of version 3.8.14 the parameter "%s" for function %s is no longer used in class %s.', 'wpsc' ),
				$parameter,
				$function,
				__CLASS__
		);

		return $message;
	}
}

