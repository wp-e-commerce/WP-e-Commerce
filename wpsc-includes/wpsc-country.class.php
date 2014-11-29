<?php
/**
 * WPSC_Country class
 *
 * @access public
 *
 * @since 3.8.14
 *
 */
class WPSC_Country {

	/**
	 * a country's constructor
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string|array 	required 	$country_identifier 	the country identifier, can be the string ISO code,
	 * 																or the integer country id, or an array of data used
	 * 																to create a new country
	 *
	 * @return object WPSC_Country
	 */
	public function __construct( $country, $deprecated = null ) {

		if ( $country ) {

			if ( is_array( $country ) ) {
				// if we get an array as an argument we are making a new country
				$country_id_or_isocode = WPSC_Countries::_save_country_data( $country );
			}  else {
				// we are constructing a country using a numeric id or ISO code
				$country_id_or_isocode = $country;
			}

			// make sure we have a valid country id
			$country_id = WPSC_Countries::get_country_id( $country_id_or_isocode );
			if ( $country_id ) {
				$wpsc_country = WPSC_Countries::get_country( $country_id );
				foreach ( $wpsc_country as $property => $value ) {
					// copy the properties in this copy of the country
					$this->$property = $value;
				}
			}
		}

		// if the regions maps has not been initialized we should create an empty map now
		if ( empty( $this->_regions ) ) {
			$this->_regions = new WPSC_Data_Map();
		}

		if ( empty( $this->_region_id_by_region_code ) ){
			$this->_region_id_by_region_code = new WPSC_Data_Map();
		}

		if ( empty( $this->_region_id_by_region_name ) ){
			$this->_region_id_by_region_name = new WPSC_Data_Map();
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
		if ( ! empty ( $deprecated ) ) {
			if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
				_wpsc_deprecated_argument( __FUNCTION__, '3.8.14', $this->_parameter_no_longer_used_message( 'col', __FUNCTION__ ) );
			}
		}

		// setup default properties filter
		add_filter( 'wpsc_country_get_property', array( __CLASS__, '_wpsc_country_default_properties' ), 10, 2 );
	}

	/**
	 * sets the default global values for any custom properties when they are retrieved
	 *
	 * @since 3.8.14
	 *
	 * @param 	mixed 					$property_value
	 * @param 	string 					$property_name
	 *
	 * @return	mixed 					the new proprty value
	*/
	public static function _wpsc_country_default_properties( $property_value, $property_name ) {

		switch ( $property_name ) {
			case 'region_label':
				if ( empty( $property_value ) ) {
					$property_value = __( 'State/Province', 'wpsc' );
				}

				break;
		}

		return $property_value;
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
	public function get_name() {
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
	public function get_id() {
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
	public function get_isocode() {
		return $this->_isocode;
	}

	/**
	 * get this country's currency
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return WPSC_Currency 		country's currency
	 */
	public function get_currency() {
		return new WPSC_Currency( $this->_currency_name );
	}

	/**
	 * get this country's  currency name
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency name
	 */
	public function get_currency_name() {
		return $this->_currency_name;
	}

	/**
	 * get this country's  currency symbol
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string	currency symbol
	 */
	public function get_currency_symbol() {
		return $this->_currency_symbol;
	}

	/**
	 * get this country's  currency symbol HTML
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency symbol HTML
	 */
	public function get_currency_symbol_html() {
		return $this->_currency_symbol_html;
	}

	/**
	 * get this country's currency code
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string 	nation's (country's) currency code
	 */
	public function get_currency_code() {
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
		return ( $this->_regions->count() > 0 );
	}

	/**
	 * Is the region valid for this country
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string 		$region 		region id, or string region name.  If string is used comparison is case insensitive
	 *
	 * @return boolean	true if the region is valid for the country, false otherwise
	 */
	public function has_region( $region ) {
		return false !== $this->get_region( $region );
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
	public function get_tax() {
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
	public function get_continent() {
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
	public function is_visible() {
		return $this->_visible;
	}

	/**
	 * returns a country's property matching the key, either a well know property or a property defined elsewhere
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return varies 	value of the property. Returns NULL for nonexistent property.
	 */
	public function get( $key ) {

		$property_name = '_' . $key;

		if ( property_exists( $this, $property_name ) ) {
			$value = $this->$property_name;
		} else {
			$value = wpsc_get_meta( $this->_id, $key, __CLASS__ );
		}

		return apply_filters( 'wpsc_country_get_property', $value, $key, $this );
	}


	/**
	 * sets a property for a country, well know properties are not allowed to be set using this function,
	 * but arbitrary properties can be set (and accessed later with get)
	 *
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return self, to support method chaining
	 */
	public function set( $property, $value = '' ) {

		if ( is_array( $property ) ) {
			foreach ( $property as $key => $value ) {
				$this->set( $key, $value );
			}
		} else {

			$key = $property;

			$property_name = '_' . $key;

			if ( property_exists( $this, $property_name ) ) {
				$value = $this->$property_name;
				_wpsc_doing_it_wrong( __FUNCTION__, __( 'Using set to change a well-known WPSC_Country property is deprecated as of version 3.8.14.  Use the class constructor and specify all properties together to perform and insert or an update.', 'wpsc' ), '3.8.14' );
				if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
					$country_array = $this->as_array();
					$country_array[$key] = $value;
					$this->_save_country_data( $country_array );
				}
			} else {
				wpsc_update_meta( $this->_id, $key, $value, __CLASS__  );
			}
		}

		return $this;
	}

	/**
	 * get a region that is in this country
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string     required      $region_identifier 	The region identifier, can be the text region code, or the numeric region id
	 *
	 * @return WPSC_Region|boolean The region, or false if the region code is not valid for the country
	 */
	public function get_region( $region ) {

		$wpsc_region = false;

		if ( $region ) {
			if ( is_numeric( $region ) ) {
				$region_id = intval( $region );
				$wpsc_region = $this->_regions->value( $region_id, $wpsc_region );
			} else {
				// check to see if it is a valid region code
				if ( $region_id = $this->_region_id_by_region_code->value( $region ) ) {
					$wpsc_region = $this->_regions->value( $region_id, $wpsc_region );
				} else {
					// check to see if we have a valid region name
					if ( $region_id = $this->_region_id_by_region_name->value( strtolower( $region ) ) ) {
						$wpsc_region = $this->_regions->value( $region_id, $wpsc_region );
					}
				}
			}
		}

		return $wpsc_region;
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
	 * @return int
	 */
	public function get_region_count() {
		return $this->_regions->count();
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
	 * @return WPSC_Region[] objects, indexed by region id, sorted by region
	 */
	public function get_regions( $as_array = false ) {
		$regions_list = $this->_regions->data();

		uasort( $regions_list, array( __CLASS__, '_compare_regions_by_name' ) );

		if ( $as_array ) {

			foreach ( $regions_list as $region_key => $wpsc_region ) {
				$region = get_object_vars( $wpsc_region );

				$keys = array_keys( $region );
				foreach ( $keys as $index => $key ) {
					if ( substr( $key, 0, 1 ) == '_' ) {
						$keys[$index] = substr( $key, 1 );
					}
				}

				$region = array_combine( $keys, array_values( $region ) );

				$regions_list[$region_key] = $region;
			}
		}

		return $regions_list;
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
	 * @return array[]   array of arrays containing region attributes
	 */
	public function get_regions_array() {

		$regions = $this->get_regions( true );
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
	public function get_region_code_by_region_id( $region_id ) {
		$region_code = false;

		if ( isset( $this->_regions[$region_id] ) ) {
			$region_code = $this->_region_id_to_region_code_map[$region_id];
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
	 * @param string 	$region_name	the name of the region for which we are looking for an id, case insensitive!
	 *
	 * @return int region id
	 */
	public function get_region_id_by_region_code( $region_code ) {
		$region_id = false;

		if ( $region_code ) {
			$region_id = $this->_region_id_by_region_code->value( $region_code, $region_id );
		}

		return $region_id;
	}

	/**
	 * get a region code from a region name
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param string 	$region_name	the name of the region for which we are looking for an id, case insensitive!
	 *
	 * @return int region id
	 */
	public function get_region_id_by_region_name( $region_name ) {
		$region_id = false;

		if ( $region_name ) {
			$region_id = $this->_region_id_by_region_name->value( strtolower( $region_name ) );
		}

		return $region_id;
	}

	/**
	 * Copy the country properties from a stdClass object to this class object.  Needed when retrieving
	 * objects from the database, but could be useful elsewhere in WPeC?
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @param
	 *
	 * @return void
	 */
	public function _copy_properties_from_stdclass( $country ) {

		$this->_id 			   = $country->id;
		$this->_name 		   = $country->country;
		$this->_isocode 	   = $country->isocode;
		$this->_currency_name  = $country->currency;
		$this->_has_regions    = $country->has_regions;
		$this->_tax 		   = $country->tax;
		$this->_continent 	   = $country->continent;
		$this->_visible        = $country->visible;

		// TODO: perhaps the currency information embedded in a country should reference a WPSC_Currency object by code?
		$this->_currency_symbol 	 = $country->symbol;
		$this->_currency_symbol_html = $country->symbol_html;
		$this->_currency_code		 = $country->code;

		if ( property_exists( $country, '_region_id_to_region_code_map' ) ) {
			$this->_region_id_to_region_code_map = $country->_region_id_to_region_code_map;
		}

		if ( property_exists( $country, 'regions' ) ) {
			$this->_regions = $country->regions;
		}
	}

	/**
	 * return country as an array
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return array
	 */
	public function as_array() {

		$result = array(
			'id' 				   => $this->_id,
			'country' 			   => $this->_name,
			'name' 				   => $this->_name, 			// backwards compatibility to before 3.8.14
			'isocode' 			   => $this->_isocode,
			'currency_name' 	   => $this->_currency_name,
			'currency_symbol' 	   => $this->_currency_symbol,
			'currency_symbol_html' => $this->_currency_symbol_html,
			'currency_code' 	   => $this->_currency_code,
			'has_regions' 		   => $this->_has_regions,
			'tax' 				   => $this->_tax,
			'continent'            => $this->_continent,
			'visible'              => $this->_visible,
			);

		return $result;
	}

	/**
	 * Comapre regions using regions's name
	 *
	 * @param unknown $a instance of WPSC_Country class
	 * @param unknown $b instance of WPSC_Country class
	 *
	 * @return 0 if country names are the same, -1 if country name of a comes before country b, 1 otherwise
	 */
	private static function _compare_regions_by_name( $a, $b ) {
		return strcmp( $a->get_name(), $b->get_name() );
	}

	/**
	 * A country's private properties, these are private to this class (notice the prefix '_'!).  They are marked as public so that
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
	public $_id                           = null;
	public $_name                         = null;
	public $_isocode                      = null;
	public $_currency_name                = '';
	public $_currency_code                = '';
	public $_currency_symbol              = '';
	public $_currency_symbol_html         = '';
	public $_code                         = '';
	public $_has_regions                  = false;
	public $_tax                          = '';
	public $_continent                    = '';
	public $_visible                      = true;
	public $_region_id_by_region_code     = null;
	public $_region_id_by_region_name     = null;
	public $_region_id_to_region_code_map = null;
	public $_regions                      = null;

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
	 * deprecated since 3.8.14
	*/
	public static function get_all( $include_invisible = false ) {

		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::get_country()';
		_wpsc_deprecated_function( $function, '3.8.14', $replacement );

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			$list = WPSC_Countries::get_countries( $include_invisible );
			return apply_filters( 'wpsc_country_get_all_countries', $list );
		}
	}

	/*
	 * deprecated since 3.8.14
	*/
	public static function get_cache( $value = null, $col = 'id' ) {

		$function = __CLASS__ . '::' . __FUNCTION__ . '()';
		$replacement = 'WPSC_Countries::get_country()';
		_wpsc_deprecated_function( $function, '3.8.14', $replacement );

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			if ( is_null( $value ) && $col == 'id' )
				$value = get_option( 'currency_type' );

			// note that we can't store cache by currency code, the code is used by various countries
			// TODO: remove duplicate entry for Germany (Deutschland)
			if ( ! in_array( $col, array( 'id', 'isocode' ) ) ) {
				return false;
			}

			return WPSC_Countries::get_country( $value, WPSC_Countries::RETURN_AN_ARRAY );
		}
	}

	/*
	 * @deprecated since 3.8.14
	*/
	public static function update_cache( $data ) {
		_wpsc_deprecated_function( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
	}

	/*
	 * @deprecated since 3.8.14
	*/
	public static function delete_cache( $value = null, $col = 'id' ) {
		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_function( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		}
	}

	/**
	 * Returns the whole database row in the form of an associative array
	 *
	 * @access public
	 * @since 3.8.11
	 *
	 * @return array
	 */
	public function get_data() {

		return apply_filters( 'wpsc_country_get_data', $this->as_array(), $this );
	}


	/*
	 * @deprecated since 3.8.14
	*
	*/
	public function save() {
		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'As of version 3.8.14 calling WPSC_Country class method "save" is not required. Changes to WPSC_Country properties are saved automatically.', 'wpsc' ), '3.8.14'  );
		}
	}

	/*
	 * @deprecated since 3.8.14
	 *
	 */
	public function exists() {

		if ( defined( 'WPSC_LOAD_DEPRECATED' ) && WPSC_LOAD_DEPRECATED ) {
			_wpsc_deprecated_argument( __FUNCTION__, '3.8.14', self::_function_not_available_message( __FUNCTION__ ) );
		}

		return true;
	}

	private static function _function_not_available_message( $function = 'called', $replacement = '' ) {
		$message = sprintf(
							__( 'As of version 3.8.14 the function "%s" is no longer available in class %s. Use %s instead', 'wpsc' ),
							$function,
							__CLASS__,
							$replacement
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

