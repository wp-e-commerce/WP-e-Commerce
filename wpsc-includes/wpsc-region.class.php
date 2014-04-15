<?php
/**
 * a geographic region
 *
 * Note: region properties are accessed though methods instead of directly.  This is intentional
 * so that in the future we have the opportunity to manipulate region data before it leaves the class.
 * Might be something foreseeable like translation tables, or could be something we haven't envisioned.
 *
 * @access public
 *
 * @since 3.8.14
 *
 * @param int|string 	required	the country identifier, can be the string ISO code, or the numeric WPeC country id
 *
 * @param int|string|null|array	required 	the region identifier, can be the text region code, or the numeric region id,
 * 											if an array is passed a new region will be created and saved in the permanent data store
 *
 * @return object WPSC_Region
 */
class WPSC_Region {

	/**
	 * constructor for a region object
	 *
	 * If null is passed for parameters an empty region is created
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @param int|string|null		required 	$country 	The country identifier, can be the string ISO code,
	 * 																	or the numeric wpec country id
	 *
	 * @param int|string|null|array	required 	$region		The region identifier, can be the text region code,
	 *																	or the numeric region id, if an array is passed a
	 *																	new region will be created and saved in the permanent
	 *																	data store
	 */
	public function __construct( $country, $region ) {

		// if a country id or code is passed make sure we have a valid coutnry_id
		if ( $country ) {
			$country_id = WPSC_Countries::get_country_id( $country );
		}

		// if we are creating a region use the country_id we just validated and get the region code
		if ( is_array( $region ) ) {
			$region['country_id'] = $country_id;
			$region_id_or_code = $this->_save_region_data( $region );
		} else {
			$region_id_or_code = $region;
		}

		// if we have both a country country id and a region id/code we can construct this object
		if ( $country && $region_id_or_code ) {
			$region_id = WPSC_Countries::get_region_id( $country_id, $region_id_or_code );

			if ( $country_id && $region_id ) {
				$wpsc_country = new WPSC_Country( $country_id );
				$wpsc_region  = WPSC_Countries::get_region( $country_id, $region_id );

				if ( $wpsc_region ) {
					$this->_code       = $wpsc_region->_code;
					$this->_id         = $wpsc_region->_id;
					$this->_country_id = $wpsc_region->_country_id;
					$this->_name       = $wpsc_region->_name;
					$this->_tax        = $wpsc_region->_tax;
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
	public function get_name() {
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
	public function get_id() {
		return $this->_id;
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
	public function get_code() {
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
	public function get_tax() {
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
	public function get_country_id() {
		return $this->_country_id;
	}


	/**
	 * get a region's information as an array
	 *
	 * @access public
	 *
	 * @since 3.8.14
	 *
	 * @return void
	 */
	public function as_array() {
		return $this->_country_id;
	}

	/**
	 * a back-door constructor used to copy data into the class after it is retrieved from the database
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
	 * saves country data to the database
	 *
	 * @access private
	 *
	 * @since 3.8.14
	 *
	 * @param array  key/value pairs that are put into the database columns
	 *
	 * @return int|boolean country_id on success, false on failure
	 */
	private function _save_region_data( $region_data ) {
		global $wpdb;

		/*
		 * We need to figure out if we are updating an existing country. There are three
		* possible unique identifiers for a country.  Look for a row that has any of the
		* identifiers.
		*/
		$region_id      = isset( $region_data['id'] ) ? intval( $region_data['id'] ) : 0;
		$country_id     = isset( $region_data['country_id'] ) ? intval( $region_data['country_id'] ) : 0;
		$region_code    = isset( $region_data['code'] ) ? $region_data['code'] : '';
		$region_name    = isset( $region_data['code'] ) ? $region_data['code'] : '';
		$tax			= isset( $region_data['tax'] ) ? $region_data['tax'] : 0;

		/*
		 *  If at least one of the key feilds ins't present we aren'y going to continue, we can't reliably update
		 *  a row in the table, nor could we insrt a row that could reliably be updated.
		 */
		if ( empty( $country_id ) || empty( $region_code ) || empty( $region_name ) ) {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'Creating a new region requires country id, region code and region name.', 'wpsc' ), '3.8.11' );
			return false;
		}

		if ( $region_id ) {
			$sql = $wpdb->prepare( 'SELECT id FROM ' . WPSC_TABLE_REGION_TAX . ' WHERE (`id` = %d )', $region_id );
			$region_id_from_db = $wpdb->get_var( $sql );
		}

		if ( empty( $region_id_from_db ) ) {
			// we are doing an insert of a new country
			$result = $wpdb->insert( WPSC_TABLE_REGION_TAX, $region_data );
			if ( $result ) {
				$region_id_from_db = $wpdb->insert_id;
			}
		} else {
			// we are doing an update of an existing country
			if ( isset( $region_data['id'] ) ) {
				// no need to update the id to itself, don't want to allow changing of region id's either
				unset( $region_data['id'] );
			}

			$wpdb->update( WPSC_TABLE_REGION_TAX, $region_data, array( 'id' => $region_id_from_db, ), '%s', array( '%d', )  );
		}

		// clear the cached data, force a rebuild
		WPSC_Countries::clear_cache();

		return $region_id_from_db;
	}

	/**
	 * private region class properties - note that they are marked as public so this object can
	 * be serialized, not to provide access. Consider yourself warned!
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

