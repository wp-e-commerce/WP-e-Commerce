<?php


/**
 * a geographic region
 *
 * @access public
 *
 * @since 3.8.14
 *
 * @param int|string 	required	the country identifier, can be the string ISO code, or the numeric WPeC country id
 * @param int|string	required	the region identifier, can be the text region code, or the numeric region id
 *
 * @return object WPSC_Region
 */
class WPSC_Region {

	/**
	 * constructor for a geographic region
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string 	required 	the country identifier, can be the string iso code, or the numeric wpec country id
	 * @param int|string 	required 	the region identifier, can be the text region code, or the numberic region id
	 *
	 * @return object WPSC_Region
	 */
	public function __construct( $country_id_or_isocode, $region_id_or_code ) {

		if ( $country_id_or_isocode && $region_id_or_code ) {
			$country_id = WPSC_Countries::country_id( $country_id_or_isocode );
			$region_id = WPSC_Countries::region_id( $country_id_or_isocode, $region_id_or_code );

			if ( $country_id && $region_id ) {
				$this->_country_name = WPSC_Countries::country( $country_id )->name();
				$region = WPSC_Countries::country( $country_id_or_isocode, $region_id_or_code );
				foreach ( $region as $property => $value ) {
					$this->$property = $value;
				}
			}
		}
	}

	/**
	 * get region's name
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string region name
	 */
	public function name() {
		return $this->_name;
	}

	/**
	 * get region's numeric id
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return int region id
	 */
	public function id() {
		return $this->_name;
	}

	/**
	 * get region's code
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return string region code
	 */
	public function code() {
		return $this->_code;
	}

	/**
	 * get region's tax percentage
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return float tax percentage
	 */
	public function tax() {
		return $this->_tax;
	}

	/**
	 * get region's country id
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return void
	 */
	public function country_id() {
		return $this->_country_id;
	}

	/**
	 * a backdoor constructor used to copy data into the class after it is retrieved from the database
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @param stdClass 	required	data from WPeC distribution to be put into region
	 *
	 * @return void
	 */
	public function _copy_properties_from_stdclass( $region ) {
		$this->_country_id	= $region->country_id;
		$this->_name 		= $region->name;
		$this->_code 		= $region->code;
		$this->_id 			= $region->id;
		$this->_tax			= $region->tax;
	}

	/**
	 * private region class properties - note that they are marked as public so this object can be serialized
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 */
	public $_id 			= false;
	public $_country_id 	= '';
	public $_name 			= '';
	public $_code 			= '';
	public $_tax 			= 0;
}

