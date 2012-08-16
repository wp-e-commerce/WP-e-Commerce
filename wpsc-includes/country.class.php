<?php
class WPSC_Country
{
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

	private $args = array(
		'col' => '',
		'value' => '',
	);

	private $data = array();

	public static function &query( $args ) {
		// when this is a custom post type, we'll have a separate WP_Query instance that handles this
		$args = wp_parse_args( $args );
		if ( array_key_exists( 'id', $args ) ) {
			$country = new WPSC_Country( $args['id'] );
			return array( $country );
		} elseif ( array_key_exists( 'isocode', $args ) ) {
			$country = new WPSC_Country( $args['isocode'], 'isocode' );
			return array( $country );
		}
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

		return wp_cache_get( $value, 'id' );
	}

	public static function update_cache( $country ) {
		$id = $country->get( 'id' );
		wp_cache_set( $id, $country->data, 'wpsc_countries' );
		wp_cache_set( $country->get( 'isocode' ), $id, 'wpsc_country_isocodes' );
	}

	public static function delete_cache( $value = null, $col = 'id' ) {
		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );
		if ( ! in_array( $col, array( 'id', 'isocode' ) ) )
			return false;

		$country = new WPSC_Country( $value, $col );
		wp_cache_delete( $country->id, 'wpsc_countries' );
		wp_cache_delete( $country->isocode, 'wpsc_country_isocodes' );
	}

	public function __construct( $value = null, $col = 'id' ) {
		global $wpdb;

		if ( is_null( $value ) && $col == 'id' )
			$value = get_option( 'currency_type' );

		if ( ! in_array( $col, array( 'id', 'code', 'isocode' ) ) )
			return;

		$this->args = array(
			'col' => $col,
			'value' => $value,
		);

		if ( $this->data = self::get_cache( $value, $col ) )
			return;

		$format = in_array( $col, self::$string_cols ) ? '%s' : '%d';

		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_CURRENCY_LIST . " WHERE {$col} = {$format}", $value );
		$this->data = $wpdb->get_row( $sql, ARRAY_A );

		self::update_cache( $this );
	}

	public function get( $key ) {
		if ( array_key_exists( $key, $this->data ) )
			return $this->data[$key];

		return null;
	}

	public function set( $key, $value ) {
		$this->data[$key] = $value;
	}

	public function save() {
		global $wpdb;
		$format = array();
		foreach ( $this->data as $key => $value ) {
			$format[] = in_array( $key, self::$string_cols ) ? '%s' : '%d';
		}
		$where_format = in_array( $this->args['col'], self::$string_cols ) ? '%s' : '%d';
		$wpdb->update( WPSC_TABLE_CURRENCY_LIST, $this->data, array( $this->args['col'] => $this->args['value'] ), $format, $where_format );
		self::delete_cache( $this->args['value'], $this->args['col'] );
	}
}