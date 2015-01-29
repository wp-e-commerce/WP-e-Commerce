<?php

class WPSC_Coupon {

	const IS_PERCENTAGE    = 1;
	const IS_FREE_SHIPPING = 2;

	/**
	 * Contains the constructor argument. This $id is necessary because we will
	 * lazy load the DB row into $this->data whenever necessary. Lazy loading is,
	 * in turn, necessary because sometimes right after saving a new record, we need
	 * to fetch a property with the same object.
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @var  int
	 */
	private $id = 0;

	/**
	 * Contains the values fetched from the DB.
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @var  array
	 */
	private $data = array();

	/**
	 * True if the DB row is fetched into $this->data.
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @var  boolean
	 */
	private $fetched = false;

	/**
	 * True if the row exists in DB.
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @var  boolean
	 */
	private $exists = false;

	/**
	 * Names of columns that requires escaping values as integers before being inserted
	 * into the database.
	 *
	 * @access  private
	 * @static
	 * @since   4.0
	 *
	 * @var  array
	 */
	private static $int_cols = array(
		'id'
	);

	/**
	 * Names of columns that requires escaping values as floats before being inserted
	 * into the database.
	 *
	 * @access  private
	 * @static
	 * @since   4.0
	 *
	 * @var  array
	 */
	private static $float_cols = array(
		'value'
	);

	/**
	 * Constructor of the coupon object. If no argument is passed, this simply
	 * create a new empty object. If an array is passed it will populate the empty
	 * object with the array data. Otherwise, this will get the coupon log from the
	 * DB using the coupon id.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @param  false|integer|array  $value  Optional. Defaults to false.
	 */
	public function __construct( $value = false ) {

		if ( false === $value ) {
			return;
		}

		// If array of data, populate
		if ( is_array( $value ) ) {
			$this->set( $value );
			return;
		}

		// Store the ID
		$this->id = is_numeric( $value ) && $value > 0 ? absint( $value ) : 0;

		// If the ID is specified, try to get from cache.
		$this->data = wp_cache_get( $this->id, 'wpsc_coupons' );

		// Cache exists
		if ( ! empty( $this->data ) ) {
			$this->fetched = true;
			$this->exists = true;
			return;
		}

	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @param   string|array         $key    Name of the property (column), or an array containing key value pairs.
	 * @param   string|integer|null  $value  Optional. Defaults to false. In case $key is a string, this should be specified.
	 * @return  WPSC_Coupon                  The current object (for method chaining).
	 */
	public function set( $key, $value = null ) {

		if ( is_array( $key ) ) {
			$properties = $key;
		} else {
			if ( is_null( $value ) ) {
				return $this;
			}
			$properties = array( $key => $value );
		}

		$properties = apply_filters( 'wpsc_coupon_set_properties', $properties, $this );

		if ( ! is_array( $this->data ) ) {
			$this->data = array();
		}

		$this->data = array_merge( $this->data, $properties );

		return $this;

	}

	/**
	 * Returns the value of the specified property of the coupon.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @param   string  $key  Name of the property (column).
	 * @return  mixed
	 */
	public function get( $key ) {

		// Lazy load the purchase log row if it's not fetched from the database yet.
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) ) {
			$this->fetch();
		}

		if ( isset( $this->data[ $key ] ) ) {
			$value = $this->data[ $key ];
		} else {
			$value = null;
		}

		return apply_filters( 'wpsc_coupon_get_property', $value, $key, $this );

	}

	/**
	 * Returns the whole database row in the form of an associative array.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  array
	 */
	public function get_data() {

		if ( empty( $this->data ) ) {
			$this->fetch();
		}

		return apply_filters( 'wpsc_coupon_get_data', $this->data, $this );

	}

	/**
	 * Get the SQL query format for a column.
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @param   string  $col  Name of the column.
	 * @return  string        Placeholder.
	 */
	private function get_column_format( $col ) {

		if ( in_array( $col, self::$int_cols ) ) {
			return '%d';
		}

		if ( in_array( $col, self::$float_cols ) ) {
			return '%f';
		}

		return '%s';

	}

	/**
	 * Returns an array containing the parameter format so that this can be used in
	 * $wpdb methods (update, insert etc.)
	 *
	 * @access  private
	 * @since   4.0
	 *
	 * @param   array  $data
	 * @return  array
	 */
	private function get_data_format( $data ) {

		$format = array();

		foreach ( $data as $key => $value ) {
			$format[] = $this->get_column_format( $key );
		}

		return $format;

	}

	/**
	 * Fetches the actual record from the database.
	 *
	 * @access  private
	 * @since   4.0
	 */
	private function fetch() {

		global $wpdb;

		if ( $this->fetched ) {
			return;
		}

		// If $this->id is not set yet, it means the object contains a new unsaved
		// row so we don't need to fetch from DB
		if ( ! $this->id ) {
			return;
		}

		$format = $this->get_column_format( $this->id );
		$sql = $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE id = {$format} LIMIT 1", $this->id );

		$this->exists = false;

		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) {
			$this->exists = true;
			$this->data = wp_parse_args( apply_filters( 'wpsc_coupon_data', $data ), array(
				'value'         => '',
				'is-percentage' => '',
				'condition'     => '',
				'is-used'       => '',
				'active'        => '',
				'use-once'      => '',
				'start'         => '',
				'expiry'        => '',
				'every_product' => ''
			) );
			$this->data['value'] = (float) $this->data['value'];
			$this->data['condition'] = unserialize( $this->data['condition'] );

			$this->update_cache();
		}

		do_action( 'wpsc_coupon_fetched', $this );

		$this->fetched = true;

	}

	/**
	 * Whether the DB row for this coupon exists.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean  True if it exists. Otherwise false.
	 */
	public function exists() {

		$this->fetch();
		return $this->exists;

	}

	/**
	 * Update cache of the passed coupon object.
	 *
	 * @access  public
	 * @since   4.0
	 */
	public function update_cache() {

		$id = $this->get( 'id' );

		wp_cache_set( $id, $this->data, 'wpsc_coupons' );
		do_action( 'wpsc_coupon_update_cache', $this );

	}

	/**
	 * Deletes cache of a coupon.
	 *
	 * @access  public
	 * @since   4.0
	 */
	public function delete_cache() {

		wp_cache_delete( $this->get( 'id' ), 'wpsc_coupons' );
		do_action( 'wpsc_coupon_delete_cache', $this );

		$this->reset();

	}

	/**
	 * Saves the coupon back to the database.
	 *
	 * @access  public
	 * @since   4.0
	 */
	public function save() {

		global $wpdb;

		if ( ! function_exists( 'wpsc_is_store_admin' ) || ! wpsc_is_store_admin() ) {
			return false;
		}

		do_action( 'wpsc_coupon_pre_save', $this );

		$result = false;

		// Do save functions and trigger actions.
		if ( $this->id > 0 ) {

			do_action( 'wpsc_coupon_pre_update', $this );

			// Update coupon
			$data = $this->validate_insert_data( apply_filters( 'wpsc_purchase_log_update_data', $this->data ) );
			$format = $this->get_data_format( $data );
			$result = $wpdb->update( WPSC_TABLE_COUPON_CODES, $data, array( 'id' => $this->id ), $format, array( $this->get_column_format( 'id' ) ) );

			$this->delete_cache();

			do_action( 'wpsc_coupon_update', $this );

		} else {

			do_action( 'wpsc_coupon_pre_insert', $this );

			// Create coupon
			$data = $this->validate_insert_data( apply_filters( 'wpsc_coupon_insert_data', $this->data ) );
			$format = $this->get_data_format( $data );
			$result = $wpdb->insert( WPSC_TABLE_COUPON_CODES, $data, $format );

			// Set ID so that coupon can be lazy loaded right after insert
			if ( $result ) {
				$this->set( 'id', $wpdb->insert_id );
			}

			do_action( 'wpsc_coupon_insert', $this );

		}

		do_action( 'wpsc_coupon_save', $this );

		return $result;

	}

	/**
	 * Deletes a coupon from the database.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function delete() {

		global $wpdb;

		if ( ! function_exists( 'wpsc_is_store_admin' ) || ! wpsc_is_store_admin() ) {
			return;
		}

		do_action( 'wpsc_coupon_before_delete', $this->id );

		$this->delete_cache();

		$deleted = $wpdb->delete(
			WPSC_TABLE_COUPON_CODES,
			array( 'id' => $this->id ),
			array( $this->get_column_format( $this->id ) )
		);

		do_action( 'wpsc_coupon_delete', $this->id );

		return $deleted;

	}

	/**
	 * Reset Coupon
	 *
	 * Clears all the coupon data apart from the ID so any subsequent requests
	 * will be refreshed.
	 */
	private function reset() {

		$this->data = array();
		$this->fetched = false;
		$this->exists = false;

	}

	/**
	 * Activate
	 *
	 * @return  int|false  Number or updated rows or false.
	 */
	public function activate() {

		global $wpdb;

		$this->set( 'active', 1 );

		return $wpdb->update(
			WPSC_TABLE_COUPON_CODES,
			array( 'active' => 1 ),
			array( 'id' => $this->id ),
			array( '%s' ),
			array( '%d' )
		);

	}

	/**
	 * Deactivate
	 *
	 * @return  int|false  Number or updated rows or false.
	 */
	public function deactivate() {

		global $wpdb;

		$this->set( 'active', 0 );

		return $wpdb->update(
			WPSC_TABLE_COUPON_CODES,
			array( 'active' => 0 ),
			array( 'id' => $this->id ),
			array( '%s' ),
			array( '%d' )
		);

	}

	/**
	 * Validate Insert Data
	 *
	 * Checks data just before saving to database.
	 * Serializes conditions.
	 *
	 * @access  private
	 *
	 * @param   array  $data  Data.
	 * @return  array         Validated data.
	 */
	private function validate_insert_data( $data ) {

		// Serialize conditions
		if ( isset( $data['condition'] ) ) {
			$data['condition'] = serialize( $data['condition'] );
		}

		return $data;

	}

	/**
	 * Is valid?
	 *
	 * Checks if the current coupon is valid to use (expiry date, active, used).
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean  True if coupon is not expired, used and still active, false otherwise.
	 */
	public function is_valid() {

		if ( ! $this->is_active() || $this->is_used() || $this->is_scheduled() || $this->is_expired() ) {
			$valid = false;
		} else {
			$valid = true;
		}

		return apply_filters( 'wpsc_validate_coupon', $valid, $this );

	}

	/**
	 * Is Scheduled?
	 *
	 * Checks wether the coupon has a start date and if so
	 * is the current date after the start date?
	 *
	 * @return  boolean
	 */
	public function is_scheduled() {

		$now   = current_time( 'timestamp', true );
		$start = $this->get( 'start' );

		$start_date = '0000-00-00 00:00:00' == $start ? 0 : strtotime( $start );

		return $start_date && $now < $start_date;

	}

	/**
	 * Is Expired?
	 *
	 * Checks wether the coupon has expired.
	 *
	 * @return  boolean
	 */
	public function is_expired() {

		$now    = current_time( 'timestamp', true );
		$expiry = $this->get( 'expiry' );

		$end_date = '0000-00-00 00:00:00' == $expiry ? 0 : strtotime( $expiry );

		return $end_date > 0 && $end_date && $now > $end_date;

	}

	/**
	 * Check whether this coupon is active.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_active() {

		return $this->get( 'active' ) == 1;

	}

	/**
	 * Check whether this coupon is a "Free shipping" coupon.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_free_shipping() {

		return $this->get( 'is-percentage' ) == self::IS_FREE_SHIPPING;

	}

	/**
	 * Check whether this coupon is a "percentage" coupon.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_percentage() {

		return $this->get( 'is-percentage' ) == self::IS_PERCENTAGE;

	}

	/**
	 * Check whether this coupon is a fixed amount coupon.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_fixed_amount() {

		return ! $this->is_free_shipping() && ! $this->is_percentage();

	}

	/**
	 * Check whether this coupon can only be used once.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_use_once() {

		return $this->get( 'use-once' ) == 1;

	}

	/**
	 * Check if a single use coupon is used.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function is_used() {

		return $this->is_use_once() && $this->get( 'is-used' ) == 1;

	}

	/**
	 * Mark a coupon as used.
	 *
	 * If the coupon can only be used once it will be marked as used and made inactive.
	 *
	 * @access  public
	 * @since   4.0
	 */
	public function used() {

		if ( $this->is_use_once() ) {
			$this->set( 'active', '0' );
			$this->set( 'is-used', '1' );
			$this->save();
		}

	}

	/**
	 * Check whether this coupon can be applied to all items.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean
	 */
	public function applies_to_all_items() {

		return $this->get( 'every_product' ) == 1;

	}

	/**
	 * Check whether the coupon has conditions.
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @return  boolean  True if there are conditions.
	 */
	public function has_conditions() {

		$condition = $this->get( 'condition' );

		return ! empty( $condition );

	}

	/**
	 * Get Percentage Discount
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @param   integer|double  $price  Price.
	 * @return  integer|double          Discount amount.
	 */
	public function get_percentage_discount( $price ) {

		if ( $this->is_percentage() ) {

			return $price * ( $this->get( 'value' ) / 100 );

		}

		return 0;

	}

	/**
	 * Get Fixed Discount
	 *
	 * @access  public
	 * @since   4.0
	 *
	 * @param   int  $quantity  Discount multiplier.
	 * @return  int             Discount amount.
	 */
	public function get_fixed_discount( $quantity = 1 ) {

		if ( $this->is_fixed_amount() ) {

			return $this->get( 'value' ) * $quantity;

		}

		return 0;

	}

}
