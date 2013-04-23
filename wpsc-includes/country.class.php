<?php
class WPSC_Country {
	private static $string_cols = array(
		'country',
		'isocode',
		'currency',
		'symbol',
		'symbol_html',
		'code',
		'has_regions',
		'tax',
		'continent',
		'visible',
	);

	private static $outdated_isocodes = array(
		'YU',
		'UK',
		'AN',
		'TP',
		'GF',
	);

	private static $default_values = array(
		'country'     => '',
		'isocode'     => '',
		'currency'    => '',
		'symbol'      => '',
		'symbol_html' => '',
		'code'        => '',
		'has_regions' => '',
		'tax'         => '',
		'continent'   => '',
		'visible'     => '1',
	);

	private $args = array(
		'col' => '',
		'value' => '',
	);

	private $data = array();

	private $exists = false;

	private $fetched = false;

	public static function get_outdated_isocodes() {
		return self::$outdated_isocodes;
	}

	public static function get_all( $include_invisible = false ) {
		global $wpdb;

		$cache = wp_cache_get( 'all', 'wpsc_countries' );

		if ( $cache )
			return $cache;

		$sql = "SELECT * FROM " . WPSC_TABLE_CURRENCY_LIST;

		if ( ! $include_invisible )
			$sql .= " WHERE visible = '1'";

		$sql .= ' ORDER BY country';

		$db_results = $wpdb->get_results( $sql, ARRAY_A );
		$list = array();

		foreach ( $db_results as $row ) {
			self::update_cache( $row );

			if ( in_array( $row['isocode'], self::$outdated_isocodes ) && $row['isocode'] != get_option( 'base_country' ) )
				continue;

			$list[] = new WPSC_Country( $row['id'] );
		}

		wp_cache_set( 'all', $list, 'wpsc_countries' );

		return $list;
	}

	public static function get_cache( $value = null, $col = 'id' ) {
		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );

		// note that we can't store cache by currency code, the code is used by various countries
		// TODO: remove duplicate entry for Germany (Deutschland)
		if ( ! in_array( $col, array( 'id', 'isocode' ) ) )
			return false;

		if ( $col == 'isocode' ) {
			if ( ! $id = wp_cache_get( $value, 'wpsc_country_isocodes' ) )
				return false;

			$value = $id;
		}

		return wp_cache_get( $value, 'wpsc_countries' );
	}

	public static function update_cache( $data ) {
		wp_cache_set( $data['id'], $data, 'wpsc_countries' );
		wp_cache_set( $data['isocode'], $data['id'], 'wpsc_country_isocodes' );
	}

	public static function delete_cache( $value = null, $col = 'id' ) {
		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );
		if ( ! in_array( $col, array( 'id', 'isocode' ) ) )
			return false;

		$country = new WPSC_Country( $value, $col );
		wp_cache_delete( $country->get( 'id' ), 'wpsc_countries' );
		wp_cache_delete( $country->get( 'isocode' ), 'wpsc_country_isocodes' );
	}

	public function __construct( $value = null, $col = 'id' ) {
		global $wpdb;

		if ( is_array( $value ) ) {
			$data = wp_parse_args( $value, self::$default_values );
			$this->set( $data );
			return;
		}

		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );

		if ( ! in_array( $col, array( 'id', 'code', 'isocode' ) ) )
			return;

		$this->args = array(
			'col' => $col,
			'value' => $value,
		);

		$cache = self::get_cache( $value, $col );

		if ( $cache ) {
			$this->data = $cache;
			$this->exists = true;
			$this->fetched = true;
		}
	}

	private function fetch() {
		global $wpdb;

		if ( $this->fetched )
			return;

		$col = $this->args['col'];
		$value = $this->args['value'];

		if ( empty( $col ) || empty( $value ) )
			return;

		$format = in_array( $col, self::$string_cols ) ? '%s' : '%d';

		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_CURRENCY_LIST . " WHERE {$col} = {$format}", $value );

		$data = $wpdb->get_row( $sql, ARRAY_A );

		if ( ! empty( $data ) ) {
			$this->exists = true;
			$this->data = $data;
			self::update_cache( $this->data );
		}

		$this->fetched = true;
	}

	public function get( $key ) {
		$this->fetch();

		if ( array_key_exists( $key, $this->data ) )
			return apply_filters( 'wpsc_country_get_property', $this->data[$key], $key, $this );

		return null;
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
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'wpsc_country_get_data', $this->data, $this );
	}

	public function set( $key, $value = '' ) {
		if ( is_array( $key ) ) {
			foreach ( $key as $col => $value ) {
				$this->set( $col, $value );
			}

			return;
		}

		$this->data[$key] = $value;
	}

	public function save() {
		global $wpdb;

		$where_col = $this->args['col'];
		$result = false;

		$format = array();
		foreach ( $this->data as $key => $value ) {
			$format[] = in_array( $key, self::$string_cols ) ? '%s' : '%d';
		}
		$where_format = in_array( $this->args['col'], self::$string_cols ) ? '%s' : '%d';

		if ( $where_col && $this->exists() ) {
			$result = $wpdb->update( WPSC_TABLE_CURRENCY_LIST, $this->data, array( $this->args['col'] => $this->args['value'] ), $format, $where_format );
			self::delete_cache( $this->args['value'], $this->args['col'] );
		} else {
			if ( $where_col ) {
				$this->set( $this->args['col'], $this->args['value'] );
				$format[] = '%s';
			}

			$result = $wpdb->insert( WPSC_TABLE_CURRENCY_LIST, $this->data, $format );

			if ( $result ) {
				$this->set( 'id', $wpdb->insert_id );

				$this->args = array(
					'col'   => 'id',
					'value' => $wpdb->insert_id,
				);

				$this->fetched = false;
			}
		}

		return $result;
	}

	public function exists() {
		$this->fetch();
		return $this->exists;
	}
}

function _wpsc_is_country_disabled( $country, $args ) {
	extract( $args, EXTR_SKIP );

	$isocode = $country->get( 'isocode' );
	$id      = $country->get( 'id' );

	if ( is_array( $acceptable ) && ! in_array( $isocode, $acceptable ) )
		return true;

	if ( is_array( $acceptable_ids ) && ! in_array( $id, $acceptable_ids ) )
		return true;

	if ( is_array( $disabled ) && in_array( $isocode, $disabled ) )
		return true;

	if ( is_array( $disabled_ids ) && in_array( $id, $disabled_ids ) )
		return true;

	return false;
}

function _wpsc_country_dropdown_options( $args = '' ) {
	$defaults = array(
		'acceptable'        => null,
		'acceptable_ids'    => null,
		'selected'          => '',
		'disabled'          => null,
		'disabled_ids'      => null,
		'placeholder'       => __( 'Please select', 'wpsc' ),
		'include_invisible' => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$output = '';

	if ( $args['placeholder'] )
		$output .= "<option value=''>" . esc_html( $args['placeholder'] ) . "</option>";

	$countries = WPSC_Country::get_all( $args['include_invisible'] );
	$base_country = get_option( 'base_country' );

	foreach ( $countries as $country ) {
		$isocode = $country->get( 'isocode' );
		$name = $country->get( 'country' );

		// if we're in admin area, and the legacy country code "UK" or "TP" is selected as the
		// base country, we should display both this and the more proper "GB" or "TL" options
		// and distinguish these choices somehow
		if ( is_admin() ) {
			if ( in_array( $isocode, array( 'TP', 'UK' ) ) )
				/* translators: This string will mark the legacy isocode "UK" and "TP" in the country selection dropdown as "legacy" */
				$name = sprintf( __( '%s (legacy)', 'wpsc' ), $name );
			elseif ( in_array( $isocode, array( 'GB', 'TL' ) ) )
				/* translators: This string will mark the legacy isocode "GB" and "TL" in the country selection dropdown as "ISO 3166" */
				$name = sprintf( __( '%s (ISO 3166)', 'wpsc' ), $name );
		}

		$output .= sprintf(
			'<option value="%1$s" %2$s %3$s>%4$s</option>',
			/* %1$s */ esc_attr( $isocode ),
			/* %2$s */ selected( $args['selected'], $isocode, false ),
			/* %3$s */ disabled( _wpsc_is_country_disabled( $country, $args ), true, false ),
			/* %4$s */ esc_html( $name )
		);
	}

	return $output;
}

function wpsc_get_country_dropdown( $args = '' ) {
	static $count = 0;
	$count ++;

	$defaults = array(
		'name'                  => 'wpsc_countries',
		'id'                    => "wpsc-country-dropdown-{$count}",
		'class'                 => 'wpsc_country_dropdown',
		'additional_attributes' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$output = sprintf(
		'<select name="%1$s" id="%2$s" class="%3$s" %4$s>',
		/* %1$s */ esc_attr( $args['name'] ),
		/* %2$s */ esc_attr( $args['id'] ),
		/* %3$s */ esc_attr( $args['class'] ),
		/* %4$s */ $args['additional_attributes']
	);

	$output .= _wpsc_country_dropdown_options( $args );

	$output .= '</select>';

	return $output;
}

function wpsc_country_dropdown( $args = '' ) {
	echo wpsc_get_country_dropdown( $args );
}
