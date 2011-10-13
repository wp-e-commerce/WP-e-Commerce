<?php

class WPSC_Purchase_Log
{
	const INCOMPLETE_SALE  = 1;
	const ORDER_RECEIVED   = 2;
	const ACCEPTED_PAYMENT = 3;
	const JOB_DISPATCHED   = 4;
	const CLOSED_ORDER     = 5;
	const PAYMENT_DECLINED = 6;
	const REFUNDED         = 7;
	const REFUND_PENDING   = 8;

	/**
	 * Names of column that requires escaping values as strings before being inserted
	 * into the database
	 *
	 * @access private
	 * @static
	 * @since 3.9
	 *
	 * @var array
	 */
	private static $string_cols = array(
		'sessionid',
		'transactid',
		'authcode',
		'date',
		'gateway',
		'billing_country',
		'shipping_country',
		'base_shipping',
		'email_sent',
		'stock_adjusted',
		'discount_data',
		'track_id',
		'billing_region',
		'shipping_region',
		'find_us',
		'engrave_text',
		'shipping_method',
		'shipping_option',
		'affiliate_id',
		'plugin_version',
		'notes',
	);

	/**
	 * Contains the values fetched from the DB
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $data = array();

	private $gateway_data = array();

	/**
	 * True if the DB row is fetched into $this->data.
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var string
	 */
	private $fetched = false;

	private $cart_contents = array();

	/**
	 * Contains the constructor arguments. This array is necessary because we will
	 * lazy load the DB row into $this->data whenever necessary. Lazy loading is,
	 * in turn, necessary because sometimes right after saving a new record, we need
	 * to fetch a property with the same object.
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $args = array(
		'col'   => '',
		'value' => '',
	);

	/**
	 * True if the row exists in DB
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var string
	 */
	private $exists = false;

	/**
	 * Update cache of the passed log object
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param WPSC_Purchase_Log $log The log object that you want to store into cache
	 * @return void
	 */
	public static function update_cache( &$log ) {
		// wpsc_purchase_logs stores the data array, while wpsc_purchase_logs_sessionid stores the
		// log id that's associated with the sessionid

		$id = $log->get( 'id' );
		wp_cache_set( $id, $log->data, 'wpsc_purchase_logs' );
		if ( $sessionid = $log->get( 'sessionid' ) )
			wp_cache_set( $sessionid, $id, 'wpsc_purchase_logs_sessionid' );

		do_action( 'wpsc_purchase_log_update_cache', $log );
	}

	/**
	 * Deletes cache of a log (either by using the log ID or sessionid)
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param string $value The value to query
	 * @param string $col Optional. Defaults to 'id'. Whether to delete cache by using
	 *                    a purchase log ID or sessionid
	 * @return void
	 */
	public static function delete_cache( $value, $col = 'id' ) {
		// this will pull from the old cache, so no worries there
		$log = new WPSC_Purchase_Log( $value, $col );
		wp_cache_delete( $log->get( 'id' ), 'wpsc_purchase_logs' );
		wp_cache_delete( $log->get( 'sessionid' ), 'wpsc_purchase_logs_sessionid' );
		wp_cache_delete( $log->get( 'id' ), 'wpsc_purchase_log_cart_contents' );
		do_action( 'wpsc_purchase_log_delete_cache', $log, $value, $col );
	}

	/**
	 * Deletes a log from the database
	 *
	 * @access public
	 * @static
	 * @since 3.9
	 *
	 * @param string $log_id ID of the log
	 * @return void
	 */
	public static function delete( $log_id ) {
		global $wpdb;
		do_action( 'wpsc_purchase_log_before_delete', $log_id );
		self::delete_cache( $log_id );
		$sql = $wpdb->prepare( "DELETE FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = %d", $log_id );
		$wpdb->query( $sql );
		do_action( 'wpsc_purchase_log_delete', $log_id );
	}

	/**
	 * Constructor of the purchase log object. If no argument is passed, this simply
	 * create a new empty object. Otherwise, this will get the purchase log from the
	 * DB either by using purchase log id or sessionid (specified by the 2nd argument).
	 *
	 * Eg:
	 *
	 * // get purchase log with ID number 23
	 * $log = new WPSC_Purchase_Log( 23 );
	 *
	 * // get purchase log with sessionid "asdf"
	 * $log = new WPSC_Purchase_Log( 'asdf', 'sessionid' )
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string $value Optional. Defaults to false.
	 * @param string $col Optional. Defaults to 'id'.
	 */
	public function __construct( $value = false, $col = 'id' ) {
		if ( $value === false )
			return;

		global $wpdb;

		if ( ! in_array( $col, array( 'id', 'sessionid' ) ) )
			return;

		// store the constructor args into an array so that later we can lazy load the data
		$this->args = array(
			'col'   => $col,
			'value' => $value,
		);

		// if the sessionid is in cache, pull out the id
		if ( $col == 'sessionid'  && $id = wp_cache_get( $value, 'wpsc_purchase_logs_sessionid' ) ) {
				$col = 'id';
				$value = $id;
		}

		// if the id is specified, try to get from cache
		if ( $col == 'id' )
			$this->data = wp_cache_get( $value, 'wpsc_purchase_logs' );

		// cache exists
		if ( $this->data ) {
			$this->fetched = true;
			$this->exists = true;
			return;
		}

	}

	/**
	 * Fetches the actual record from the database
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @return void
	 */
	private function fetch() {
		global $wpdb;

		if ( $this->fetched )
			return;

		// If $this->args is not set yet, it means the object contains a new unsaved
		// row so we don't need to fetch from DB
		if ( ! $this->args['col'] )
			return;

		extract( $this->args );

		$format = in_array( $col, self::$string_cols ) ? '%s' : '%d';
		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;

		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) {
			$this->exists = true;
			$this->data = apply_filters( 'wpsc_purchase_log_data', $data );
			self::update_cache( $this );
		}

		do_action( 'wpsc_purchase_log_fetched', $this );

		$this->fetched = true;
	}

	/**
	 * Whether the DB row for this purchase log exists
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return bool True if it exists. Otherwise false.
	 */
	public function exists() {
		$this->fetch();
		return $this->exists;
	}

	/**
	 * Returns the value of the specified property of the purchase log
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param string $key Name of the property (column)
	 * @return mixed
	 */
	public function get( $key ) {
		// lazy load the purchase log row if it's not fetched from the database yet
		if ( empty( $this->data ) || ! array_key_exists( $key, $this->data ) )
			$this->fetch();

		$value = isset( $this->data[$key] ) ? $this->data[$key] : null;
		return apply_filters( 'wpsc_purchase_log_get_property', $value, $key, $this );
	}

	public function get_cart_contents() {
		global $wpdb;

		$id = $this->get( 'id' );
		if ( $this->cart_contents = wp_cache_get( $id, 'wpsc_purchase_log_cart_contents' ) )
			return $this->cart_contents;

		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = %d", $id );
		$this->cart_contents = $wpdb->get_results( $sql );

		return $this->cart_contents;
	}

	/**
	 * Returns the whole database row in the form of an associative array
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return array
	 */
	public function get_data() {
		if ( empty( $this->data ) )
			$this->fetch();

		return apply_filters( 'wpsc_purchase_log_get_data', $this->data, $this );
	}

	public function get_gateway_data( $from_currency = false, $to_currency = false ) {
		if ( empty( $this->data ) )
			$this->fetch();
		$subtotal = 0;
		$shipping = wpsc_format_convert_price( (float) $this->data['base_shipping'], $from_currency, $to_currency );
		$tax = 0;
		$items = array();
		$this->get_cart_contents();

		$this->gateway_data = array(
			'amount'  => wpsc_format_convert_price( $this->data['totalprice'], $from_currency, $to_currency ),
			'invoice' => $this->data['sessionid'],
			'tax'     => wpsc_format_convert_price( $this->data['wpec_taxes_total'], $from_currency, $to_currency ),
		);

		foreach ( $this->cart_contents as $item ) {
			$item_price = wpsc_format_convert_price( $item->price, $from_currency, $to_currency );
			$items[] = array(
				'name'     => $item->name,
				'amount'   => $item_price,
				'tax'      => wpsc_format_convert_price( $item->tax_charged, $from_currency, $to_currency ),
				'quantity' => $item->quantity,
			);
			$subtotal += $item_price * $item->quantity;
			$shipping += wpsc_format_convert_price( $item->pnp, $from_currency, $to_currency );
		}

		$this->gateway_data['discount'] = wpsc_format_convert_price( (float) $this->data['discount_value'], $from_currency, $to_currency );

		$this->gateway_data['items'] = $items;
		$this->gateway_data['shipping'] = $shipping;
		$this->gateway_data['subtotal'] = $subtotal;

		if ( $from_currency ) {
			// adjust total amount in case there's slight decimal error
			$total = $subtotal + $shipping + $this->gateway_data['tax'] - $this->gateway_data['discount'];
			if ( $this->gateway_data['amount'] != $total )
				$this->gateway_data['amount'] = $total;
		}

		$this->gateway_data = apply_filters( 'wpsc_purchase_log_gateway_data', $this->gateway_data, $this->data );
		return $this->gateway_data;
	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @param mixed $key Name of the property (column), or an array containing key
	 *                   value pairs
	 * @param string|int $value Optional. Defaults to false. In case $key is a string,
	 *                          this should be specified.
	 * @return WPSC_Purchase_Log The current object (for method chaining)
	 */
	public function set( $key, $value = null ) {
		if ( is_array( $key ) ) {
			$properties = $key;
		} else {
			if ( is_null( $value ) )
				return $this;

			$properties = array( $key => $value );
		}

		$properties = apply_filters( 'wpsc_purchase_log_set_properties', $properties, $this );

		$this->data = array_merge( $this->data, $properties );
		return $this;
	}

	/**
	 * Returns an array containing the parameter format so that this can be used in
	 * $wpdb methods (update, insert etc.)
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @param array $data
	 * @return array
	 */
	private function get_data_format( $data ) {
		$format = array();

		foreach ( $data as $key => $value ) {
			$format[] = in_array( $key, self::$string_cols ) ? '%s' : '%d';
		}

		return $format;
	}

	/**
	 * Saves the purchase log back to the database
	 *
	 * @access public
	 * @since 3.9
	 *
	 * @return void
	 */
	public function save() {
		global $wpdb;

		do_action( 'wpsc_purchase_log_pre_save', $this );

		// $this->args['col'] is empty when the record is new and needs to
		// be inserted. Otherwise, it means we're performing an update
		$where_col = $this->args['col'];

		if ( $where_col ) {
			$where_val = $this->args['value'];
			$where_format = in_array( $where_col, self::$string_cols ) ? '%s' : '%d';
			do_action( 'wpsc_purchase_log_pre_update', $this );
			self::delete_cache( $where_val, $where_col );
			$data = apply_filters( 'wpsc_purchase_log_update_data', $this->data );
			$format = $this->get_data_format( $data );
			$wpdb->update( WPSC_TABLE_PURCHASE_LOGS, $data, array( $where_col => $where_val ), $format, array( $where_format ) );
			do_action( 'wpsc_purchase_log_update', $this );
		} else {
			do_action( 'wpsc_purchase_log_pre_insert' );
			$data = apply_filters( 'wpsc_purchase_log_insert_data', $this->data );
			$format = $this->get_data_format( $data );
			$result = $wpdb->insert( WPSC_TABLE_PURCHASE_LOGS, $data, $format );

			if ( $result ) {
				$this->set( 'id', $wpdb->insert_id );

				// set $this->args so that properties can be lazy loaded right after
				// the row is inserted into the db
				$this->args = array(
					'col'   => 'id',
					'value' => $this->get( 'id' ),
				);
			}

			do_action( 'wpsc_purchase_log_insert', $this );
		}

		do_action( 'wpsc_purchase_log_save', $this );
		return $this;
	}
}