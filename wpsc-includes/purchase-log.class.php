<?php
// by default, expire stats cache after 48 hours
// this doesn't have any effect if you're not using APC or memcached

if ( ! defined( 'WPSC_PURCHASE_LOG_STATS_CACHE_EXPIRE' ) ) {
	define( 'WPSC_PURCHASE_LOG_STATS_CACHE_EXPIRE', DAY_IN_SECONDS * 2 );
}

class WPSC_Purchase_Log extends WPSC_Query_Base {
	const INCOMPLETE_SALE  		= 1;
	const ORDER_RECEIVED  	 	= 2;
	const ACCEPTED_PAYMENT		= 3;
	const JOB_DISPATCHED   		= 4;
	const CLOSED_ORDER     		= 5;
	const PAYMENT_DECLINED 		= 6;
	const REFUNDED         		= 7;
	const REFUND_PENDING   		= 8;
	const PARTIALLY_REFUNDED 	= 9;

	/**
	 * Names of column that requires escaping values as strings before being inserted
	 * into the database
	 *
	 * @access private
	 * @static
	 * @since 3.8.9
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
	 * Names of column that requires escaping values as integers before being inserted
	 * into the database
	 *
	 * @static
	 * @since 3.8.9
	 * @var array
	 */
	private static $int_cols = array(
		'id',
		'statusno',
		'processed',
		'user_ID',
	);

	/**
	 * Names of column that requires escaping values as float before being inserted
	 * into the database
	 *
	 * @static
	 * @since 3.11.5
	 * @var array
	 */
	private static $float_cols = array(
		'totalprice',
		'base_shipping',
		'discount_value',
		'wpec_taxes_total',
		'wpec_taxes_rate',
	);

	/**
	 * Array of metadata
	 *
	 * @static
	 * @since 3.11.5
	 * @var array
	 */
	private static $metadata = array(
		'totalprice',
		'base_shipping',
		'discount_value',
		'wpec_taxes_total',
		'wpec_taxes_rate',
	);

	private $gateway_data = array();
	private $form_data_obj = null;

	private $is_status_changed = false;
	private $previous_status   = false;

	private $log_items = array();
	private $log_item_ids = array();
	private $can_edit = null;
	private static $multiple_meta = array(
		'notes' => 1,
	);

	/**
	 * Contains the constructor arguments. This array is necessary because we will
	 * lazy load the DB row into $this->data whenever necessary. Lazy loading is,
	 * in turn, necessary because sometimes right after saving a new record, we need
	 * to fetch a property with the same object.
	 *
	 * @access private
	 * @since 3.8.9
	 *
	 * @var array
	 */
	private $args = array(
		'col'   => '',
		'value' => '',
	);

   protected $buyers_name = null;
   protected $buyers_city = null;
   protected $buyers_email = null;
   protected $buyers_address = null;
   protected $buyers_state_and_postcode = null;
   protected $buyers_country = null;
   protected $buyers_phone = null;
   protected $shipping_name = null;
   protected $shipping_address = null;
   protected $shipping_city = null;
   protected $shipping_state_and_postcode = null;
   protected $shipping_country = null;
   protected $payment_method = null;
   protected $shipping_method = null;

	/**
	 * Get the SQL query format for a column
	 *
	 * @since 3.8.9
	 * @param  string $col Name of the column
	 * @return string      Placeholder
	 */
	private static function get_column_format( $col ) {
		if ( in_array( $col, self::$string_cols ) ) {
			return '%s';
		}

		if ( in_array( $col, self::$int_cols ) ) {
			return '%d';
		}

		return '%f';
	}

	/**
	 * Query the purchase log table to get sales and earning stats
	 *
	 * Accepts an array of arguments:
	 * 	- 'ids': IDs of products for which you want to get stats
	 * 	- 'products': array of WPSC_Product objects for which you want to get stats
	 * 	- 'start' and 'end': the timestamp range (integers) during which you want
	 * 	                     to collect the stats.
	 * 	                     You can use none, either, or both of them.
	 * 	                     Note that the [start, end) interval is a left-closed,
	 * 	                     right-open.
	 * 	                     E.g.: to get stats from Jan 1st, 2013 to
	 * 	                     Dec 31st, 2013 (23:59:59),
	 * 	                     set "start" to the timestamp for Jan 1st, 2013, set
	 * 	                     "end" to the timestamp for Jan 1st, 2014
	 *  - 'order': what to sort the results by, defaults to 'id'.
	 *            Can be 'ids', 'sales', 'earnings' or empty string to disable sorting
	 *  - 'orderby': how to sort the results, defaults to 'ASC'.
	 *              Can be 'ASC', 'DESC' (lowercase is fine too)
	 *  - 'per_page': How many items to fetch, defaults to 0, which fetches all results
	 *  - 'page': Which page of the results to fetch, defaults to 1.
	 *            Has no effect if per_page is set to 0.
	 *
	 * @since 3.8.14
	 * @param  array|string $args Arguments
	 * @return array       Array containing 'sales' and 'earnings' stats
	 */
	public static function fetch_stats( $args ) {
		global $wpdb;

		$defaults = array(
			'ids'      => array(), // IDs of the products to be queried
			'products' => array(), // Array of WPSC_Products objects
			'start'    => 0,       // Int. timestamp, has to be UTC
			'end'      => 0,       // Int. timestamp, has to be UTC
			'order'    => 'ASC',
			'orderby'  => 'id',
			'per_page' => 0,
			'page'     => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		// convert more advanced date / time args into "start" and "end"
		$args = self::convert_date_args( $args );

		// build an array of WPSC_Product objects based on 'ids' and 'products' args
		$products = array_merge(
			$args['products'],
			array_map( array( 'WPSC_Product', 'get_instance' ), $args['ids'] )
		);

		// eliminate duplicates (Note: usage of this requires PHP 5.2.9)
		$products = array_unique( $products, SORT_REGULAR );

		if ( empty( $products ) ) {
			return null;
		}

		$needs_fetching = array();

		$stats = array(
			'sales'    => 0,
			'earnings' => 0,
		);

		// if we have date restriction, that means we won't be able to rely upon
		// individual stats cache inside WPSC_Product objects
		$has_date_restriction = ! ( empty( $args['start'] ) && empty( $args['end'] ) );

		// if we do NOT have date restriction, find out which WPSC_Product object
		// has stats cache, and which don't
		if ( ! $has_date_restriction ) {
			foreach ( $products as $product ) {
				// store the ID if this product doesn't have a stats cache yet
				if ( $product->post->_wpsc_stats === '' ) {
					$needs_fetching[] = $product->post->ID;
				} else {

					// tally up the sales and earnings if this one has cache already
					$prod_meta = get_post_meta( $product->post->ID, '_wpsc_stats', true );

					if ( isset( $prod_meta['sales'] ) && isset( $prod_meta['earnings'] ) ) {
						$stats['sales']    += $prod_meta['sales'];
						$stats['earnings'] += $prod_meta['earnings'];
					}
					$needs_fetching[]   = $product->post->ID;
				}
			}
		}

		// never hurts to make sure
		$needs_fetching = array_map( 'absint', $needs_fetching );

		// pagination arguments
		$limit = '';

		if ( ! empty( $args['per_page'] ) ) {
			$offset = ( $args['page'] - 1 ) * $args['per_page'];
			$limit = "LIMIT " . absint( $args['per_page'] ) . " OFFSET " . absint( $offset );
		}

		// sorting
		$order = '';

		if ( ! empty( $args['orderby'] ) )
			$order = "ORDER BY " . esc_sql( $args['orderby'] ) . " " . esc_sql( $args['order'] );

		// date
		$where = "WHERE p.processed IN (3, 4, 5)";

		if ( $has_date_restriction ) {
			// start date equal or larger than $args['start']
			if ( ! empty( $args['start'] ) )
				$where .= " AND CAST(p.date AS UNSIGNED) >= " . absint( $args['start'] );

			// end date less than $args['end'].
			// the "<" sign is not a typo, such upper limit makes it easier for
			// people to specify range.
			// E.g.: [1/1/2013 - 1/1/2014) rather than:
			//       [1/1/2013 - 31/12/2013 23:59:59]
			if ( ! empty( $args['end'] ) )
				$where .= " AND CAST(p.date AS UNSIGNED) < " . absint( $args['end'] );
		}

		// assemble the SQL query
		$sql = "
			SELECT cc.prodid AS id, SUM(cc.quantity) AS sales, SUM(cc.quantity * cc.price) AS earnings
			FROM $wpdb->wpsc_purchase_logs AS p
			INNER JOIN
				$wpdb->wpsc_cart_contents AS cc
				ON p.id = cc.purchaseid AND cc.prodid IN (" . implode( ', ', $needs_fetching ) . ")
			{$where}
			GROUP BY cc.prodid
			{$order}
			{$limit}
		";

		// if the result is cached, don't bother querying the database
		$cache_key = md5( $sql );
		$results   = wp_cache_get( $cache_key, 'wpsc_purchase_logs_stats' );

		if ( false === $results ) {
			$results = $wpdb->get_results( $sql );
			wp_cache_set( $cache_key, $results, 'wpsc_purchase_logs_stats', WPSC_PURCHASE_LOG_STATS_CACHE_EXPIRE );
		}

		// tally up the sales and earnings from the query results
		foreach ( $results as $row ) {
			if ( ! $has_date_restriction ) {
				$product           = WPSC_Product::get_instance( $row->id );
				$product->sales    = $row->sales;
				$product->earnings = $row->earnings;
			}

			$stats['sales']    += $row->sales;
			$stats['earnings'] += $row->earnings;
		}

		return $stats;
	}

	/**
	 * Convert advanced date/time arguments like year, month, day, 'ago' etc.
	 * into basic arguments which are "start" and "end".
	 *
	 * @since  3.8.14
	 * @param  array $args Arguments
	 * @return array       Arguments after converting
	 */
	private static function convert_date_args( $args ) {
		// TODO: Implement this
		return $args;
	}

	/**
	 * Get overall sales and earning stats for just one product
	 *
	 * @since 3.8.14
	 * @param  int $id ID of the product
	 * @return array   Array containing 'sales' and 'earnings' stats
	 */
	public static function get_stats_for_product( $id, $args = '' ) {

		$product = WPSC_Product::get_instance( $id );

		// if this product has variations
		if ( $product->has_variations ) {
			// get total stats of variations
			$args['products'] = $product->variations;
		} else {
			// otherwise, get stats of only this product
			$args['products'] = array( $product );
		}

		return self::fetch_stats( $args );
	}

	/**
	 * Check whether the status code indicates a completed status
	 *
	 * @since 3.8.13
	 * @param int  $status Status code
	 * @return boolean
	 */
	public static function is_order_status_completed( $status ) {
		$completed_status = apply_filters( 'wpsc_order_status_completed', array(
			self::ACCEPTED_PAYMENT,
			self::JOB_DISPATCHED,
			self::CLOSED_ORDER,
		) );

		return in_array( $status, $completed_status );
	}

	/**
	 * Update cache of the passed log object
	 *
	 * @access public
	 * @static
	 * @since 3.8.9
	 *
	 * @param WPSC_Purchase_Log $log The log object that you want to store into cache
	 * @return void
	 */
	public static function update_cache( &$log ) {
		return $log->update_caches();
	}

	/**
	 * Update caches.
	 *
	 * @access public
	 * @static
	 * @since 3.11.5
	 *
	 * @return void
	 */
	public function update_caches() {

		// wpsc_purchase_logs stores the data array, while wpsc_purchase_logs_sessionid stores the
		// log id that's associated with the sessionid
		$id = $this->get( 'id' );
		wp_cache_set( $id, $this->data, 'wpsc_purchase_logs' );

		if ( $sessionid = $this->get( 'sessionid' ) ) {
			wp_cache_set( $sessionid, $id, 'wpsc_purchase_logs_sessionid' );
		}

		wp_cache_set( $id, $this->log_items, 'wpsc_purchase_log_items' );
		do_action( 'wpsc_purchase_log_update_cache', $this );
	}

	/**
	 * Deletes cache of a log (either by using the log ID or sessionid)
	 *
	 * @access public
	 * @static
	 * @since 3.8.9
	 *
	 * @param string $value The value to query
	 * @param string $col Optional. Defaults to 'id'. Whether to delete cache by using
	 *                    a purchase log ID or sessionid
	 * @return void
	 */
	public static function delete_cache( $value, $col = 'id' ) {
		// this will pull from the old cache, so no worries there
		$log = new WPSC_Purchase_Log( $value, $col );
		$log->delete_caches( $value, $col );
	}

	/**
	 * Deletes caches.
	 *
	 * @access public
	 * @static
	 * @since 3.11.5
	 *
	 * @param string|null $value Optional (left for back-compatibility). The value which was queried.
	 * @param string|null $col   Optional (left for back-compatibility). The column used as the identifier.
	 *
	 * @return void
	 */
	public function delete_caches( $value = null, $col = null ) {
		wp_cache_delete( $this->get( 'id' ), 'wpsc_purchase_logs' );
		wp_cache_delete( $this->get( 'sessionid' ), 'wpsc_purchase_logs_sessionid' );
		wp_cache_delete( $this->get( 'id' ), 'wpsc_purchase_log_items' );
		wp_cache_delete( $this->get( 'id' ), 'wpsc_purchase_meta' );

		if ( null === $value ) {
			$value = $this->args['value'];
		}

		if ( null === $col ) {
			$col = $this->args['col'];
		}

		do_action( 'wpsc_purchase_log_delete_cache', $this, $value, $col );
	}

	/**
	 * Deletes a log from the database.
	 *
	 * @access  public
	 * @since   3.8.9
	 *
	 * @uses  $wpdb                              Global database instance.
	 * @uses  wpsc_is_store_admin()              Check user has admin capabilities.
	 * @uses  WPSC_Purchase_Log::delete_cache()  Delete purchaselog cache.
	 * @uses  WPSC_Claimed_Stock                 Claimed Stock class.
	 *
	 * @param   string   $log_id   ID of the log.
	 * @return  boolean            Deleted successfully.
	 */
	public function delete( $log_id = false ) {

		global $wpdb;

		if ( ! ( isset( $this ) && get_class( $this ) == __CLASS__ ) ) {
			_wpsc_doing_it_wrong( 'WPSC_Purchase_Log::delete', __( 'WPSC_Purchase_Log::delete() is no longer a static method and should not be called statically.', 'wp-e-commerce' ), '3.9.0' );
		}

		if ( false !== $log_id ) {
			_wpsc_deprecated_argument( __FUNCTION__, '3.9.0', 'The $log_id param is not used. You must first create an instance of WPSC_Purchase_Log before calling this method.' );
		}

		if ( ! wpsc_is_store_admin() ) {
			return false;
		}

		$log_id = $this->get( 'id' );

		if ( $log_id > 0 ) {

			do_action( 'wpsc_purchase_log_before_delete', $log_id, $this );

			$this->delete_caches();

			// Delete claimed stock
			$purchlog_status = $wpdb->get_var( $wpdb->prepare( "SELECT `processed` FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id`= %d", $log_id ) );
			if ( $purchlog_status == WPSC_Purchase_Log::CLOSED_ORDER || $purchlog_status == WPSC_Purchase_Log::INCOMPLETE_SALE ) {
				$claimed_query = new WPSC_Claimed_Stock( array(
					'cart_id'        => $log_id,
					'cart_submitted' => 1
				) );
				$claimed_query->clear_claimed_stock( 0 );
			}

			// Delete cart content, submitted data, then purchase log
			$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = %d", $log_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` IN (%d)", $log_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = %d LIMIT 1", $log_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_PURCHASE_META . "` WHERE `wpsc_purchase_id` = %d", $log_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `purchid` = %d ", $log_id ) );

			do_action( 'wpsc_purchase_log_delete', $log_id, $this );

			return true;

		}

		return false;

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
	 * @since 3.8.9
	 *
	 * @param string $value Optional. Defaults to false.
	 * @param string $col Optional. Defaults to 'id'.
	 */
	public function __construct( $value = false, $col = 'id' ) {
		if ( false === $value ) {
			return;
		}

		if ( is_array( $value ) ) {
			$this->set( $value );
			return;
		}

		global $wpdb;

		if ( ! in_array( $col, array( 'id', 'sessionid' ) ) ) {
			return;
		}

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
		if ( $col == 'id' ) {
			$this->data = wp_cache_get( $value, 'wpsc_purchase_logs' );
			$this->log_items = wp_cache_get( $value, 'wpsc_purchase_log_items' );
		}

		// cache exists
		if ( $this->data ) {
			$this->set_meta_props();
			$this->fetched = true;
			$this->exists  = true;
			return;
		}
	}

	private function set_total_shipping() {

		$base_shipping  = $this->get( 'base_shipping' );
		$item_shipping  = wp_list_pluck( $this->get_items(), 'pnp' );

		$this->meta_data['total_shipping'] = $base_shipping + array_sum( $item_shipping );

		return $this->meta_data['total_shipping'];
	}

	private function set_gateway_name() {
		global $wpsc_gateways;
		$gateway = $this->get( 'gateway' );
		$gateway_name = $gateway;

		if( 'wpsc_merchant_testmode' == $gateway )
			$gateway_name = __( 'Manual Payment', 'wp-e-commerce' );
		elseif ( isset( $wpsc_gateways[$gateway] ) )
			$gateway_name = $wpsc_gateways[$gateway]['name'];

		$this->meta_data['gateway_name'] = $gateway_name;
		return $this->meta_data['gateway_name'];
	}

	private function set_shipping_method_names() {
		global $wpsc_shipping_modules;

		$shipping_method = $this->get( 'shipping_method' );
		$shipping_option = $this->get( 'shipping_option' );
		$shipping_method_name = $shipping_method;
		$shipping_option_name = $shipping_option;

		if ( ! empty ( $wpsc_shipping_modules[$shipping_method] ) ) {
			$shipping_class = $wpsc_shipping_modules[$shipping_method];
			$shipping_method_name = $shipping_class->name;
		}

		$this->meta_data['shipping_method_name'] = $shipping_method_name;
		$this->meta_data['shipping_option_name'] = $shipping_option_name;
	}

	private function set_meta_props() {

		foreach ( wpsc_get_purchase_custom( $this->get( 'id' ) ) as $key => $value  ) {
			$is_multiple_meta = isset( self::$multiple_meta[ $key ] );
			$this->meta_data[ $key ] = wpsc_get_purchase_meta( $this->get( 'id' ), $key, ! $is_multiple_meta );
		}

		$this->set_total_shipping();
		$this->set_gateway_name();
		$this->set_shipping_method_names();
	}

	public function get_meta() {

		if ( empty( $this->data ) || empty( $this->meta_data ) ) {
			$this->fetch();
		}

		return (array) apply_filters( 'wpsc_purchase_log_meta_data', $this->meta_data );
	}

	/**
	 * Fetches the actual record from the database
	 *
	 * @access protected
	 * @since 3.8.9
	 *
	 * @return WPSC_Purchase_Log
	 */
	protected function fetch() {
		global $wpdb;

		if ( $this->fetched ) {
			return;
		}

		// If $this->args is not set yet, it means the object contains a new unsaved
		// row so we don't need to fetch from DB
		if ( ! $this->args['col'] || ! $this->args['value'] ) {
			return;
		}

		$col = $this->args['col'];

		$format = self::get_column_format( $col );
		$sql    = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE {$col} = {$format}", $this->args['value'] );

		$this->exists = false;

		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) {
			$this->exists    = true;
			$this->data      = apply_filters( 'wpsc_purchase_log_data', $data );
			$this->log_items = $this->get_items();

			$this->set_meta_props();
			$this->update_caches();
		}

		do_action( 'wpsc_purchase_log_fetched', $this );

		$this->fetched = true;

		return $this;
	}

	/**
	 * Returns the value of the specified property of the $data array if it exists.
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @param  string $key Name of the property (column)
	 * @return mixed
	 */
	public function get( $key ) {
		if ( 'notes' === $key ) {
			_wpsc_doing_it_wrong( __FUNCTION__, __( 'Getting notes from the Log object has been deprecated in favor of the wpsc_get_order_notes() function.', 'wp-e-commerce' ), '3.11.5' );
		}

		return parent::get( $key );
	}

	/**
	 * Prepares the return value for get() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @param  mixed  $value Value fetched
	 * @param  string $key   Key for $data.
	 *
	 * @return mixed
	 */
	protected function prepare_get( $value, $key ) {
		return apply_filters( 'wpsc_purchase_log_get_property', $value, $key, $this );
	}

	/**
	 * Prepares the return value for get_data() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	protected function prepare_get_data() {
		return apply_filters( 'wpsc_purchase_log_get_data', $this->data, $this );
	}

	/**
	 * Prepares the return value for get_meta() (apply_filters, etc).
	 *
	 * @access protected
	 * @since  3.11.5
	 *
	 * @return mixed
	 */
	protected function prepare_get_meta() {
		return (array) apply_filters( 'wpsc_purchase_log_meta_data', $this->meta_data );
	}

	public function get_cart_contents() {
		_wpsc_doing_it_wrong( __FUNCTION__, __( 'This function has been deprecated in favor of the get_items() method.', 'wp-e-commerce' ), '3.11.5' );
		return $this->get_items();
	}

	public function get_items() {
		global $wpdb;

		if ( ! empty( $this->log_items ) && $this->fetched ) {
			return $this->log_items;
		}

		$id = $this->get( 'id' );

		// Bail if we don't have a log object yet (no id).
		if ( empty( $id ) ) {
			return $this->log_items;
		}

		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_CART_CONTENTS . " WHERE purchaseid = %d", $id );
		$this->log_items = $wpdb->get_results( $sql );

		if ( is_array( $this->log_items ) ) {
			foreach ( $this->log_items as $index => $item ) {
				$this->log_item_ids[ absint( $item->id ) ] = $index;
			}
		}

		return $this->log_items;
	}

	public function get_item( $item_id ) {
		$item_id = absint( $item_id );
		$items   = $this->get_items();

		if ( isset( $this->log_item_ids[ $item_id ] ) ) {
			return $items[ $this->log_item_ids[ $item_id ] ];
		}

		return false;
	}

	public function get_item_from_product_id( $product_id ) {
		$product_id = absint( $product_id );
		$items      = $this->get_items();

		foreach ( $items as $item ) {
			if ( $product_id === absint( $item->prodid ) ) {
				return $item;
			}
		}

		return false;
	}

	public function update_item( $item_id, $data ) {
		global $wpdb;

		$item_id = absint( $item_id );
		$item = $this->get_item( $item_id );

		if ( $item ) {
			do_action( 'wpsc_purchase_log_before_update_item', $item_id, $this );

			$data = wp_unslash( $data );
			$result = $wpdb->update( WPSC_TABLE_CART_CONTENTS, $data, array( 'id' => $item_id  ) );

			if ( $result ) {

				$this->log_items = array();
				$this->get_item( $item_id );

				do_action( 'wpsc_purchase_log_update_item', $item_id, $this );
			}

			return $result;
		}

		return false;
	}

	public function remove_item( $item_id ) {
		global $wpdb;

		$item_id = absint( $item_id );
		$item = $this->get_item( $item_id );

		if ( $item ) {
			do_action( 'wpsc_purchase_log_before_remove_item', $item_id, $this );

			$result = $wpdb->delete( WPSC_TABLE_CART_CONTENTS, array( 'id' => $item_id ) );

			if ( $result ) {

				unset( $this->log_items[ $this->log_item_ids[ $item_id ] ] );
				unset( $this->log_item_ids[ $item_id ] );

				do_action( 'wpsc_purchase_log_remove_item', $item_id, $this );
			}

			return $result;
		}

		return false;
	}

	public function form_data() {
		if ( null === $this->form_data_obj ) {
			$this->form_data_obj = new WPSC_Checkout_Form_Data( $this->get( 'id' ), false );
		}

		return $this->form_data_obj;
	}

	public function get_gateway_data( $from_currency = false, $to_currency = false ) {
		if ( ! $this->exists() ) {
			return array();
		}

		$subtotal = 0;
		$shipping = wpsc_convert_currency( (float) $this->get( 'base_shipping' ), $from_currency, $to_currency );
		$items    = array();

		$this->gateway_data = array(
			'amount'  => wpsc_convert_currency( $this->get( 'totalprice' ), $from_currency, $to_currency ),
			'invoice' => $this->get( 'sessionid' ),
			'tax'     => wpsc_convert_currency( $this->get( 'wpec_taxes_total' ), $from_currency, $to_currency ),
		);

		foreach ( $this->log_items as $item ) {
			$item_price = wpsc_convert_currency( $item->price, $from_currency, $to_currency );
			$items[] = array(
				'name'     => $item->name,
				'amount'   => $item_price,
				'tax'      => wpsc_convert_currency( $item->tax_charged, $from_currency, $to_currency ),
				'quantity' => $item->quantity,
			);
			$subtotal += $item_price * $item->quantity;
			$shipping += wpsc_convert_currency( $item->pnp, $from_currency, $to_currency );
		}

		$this->gateway_data['discount'] = wpsc_convert_currency( (float) $this->get( 'discount_value' ), $from_currency, $to_currency );

		$this->gateway_data['items'] = $items;
		$this->gateway_data['shipping'] = $shipping;
		$this->gateway_data['subtotal'] = $subtotal;

		if ( $from_currency ) {
			// adjust total amount in case there's slight decimal error
			$total = $subtotal + $shipping + $this->gateway_data['tax'] - $this->gateway_data['discount'];
			if ( $this->gateway_data['amount'] != $total ) {
				$this->gateway_data['amount'] = $total;
			}
		}

		$this->gateway_data = apply_filters( 'wpsc_purchase_log_gateway_data', $this->gateway_data, $this->get_data() );
		return $this->gateway_data;
	}

	/**
	 * Sets a property to a certain value. This function accepts a key and a value
	 * as arguments, or an associative array containing key value pairs.
	 *
	 * Loops through data, comparing against database, and saves as meta if not found in purchase log table.
	 *
	 * @access public
	 * @since 3.8.9
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
			if ( is_null( $value ) ) {
				return $this;
			}

			$properties = array( $key => $value );
		}

		$properties = apply_filters( 'wpsc_purchase_log_set_properties', $properties, $this );

		if ( array_key_exists( 'processed', $properties ) ) {
			$this->previous_status = $this->get( 'processed' );

			if ( $properties['processed'] != $this->previous_status ) {
				$this->is_status_changed = true;
			}
		}

		if ( ! is_array( $this->data ) ) {
			$this->data = array();
		}

		foreach ( $properties as $key => $value ) {
			if ( ! in_array( $key, array_merge( self::$string_cols, self::$int_cols, self::$float_cols ) ) ) {
				$this->meta_data[ $key ] = $value;
				unset( $properties[ $key ] );
			}
		}

		$this->data = array_merge( $this->data, $properties );
		return $this;
	}

	/**
	 * Returns an array containing the parameter format so that this can be used in
	 * $wpdb methods (update, insert etc.)
	 *
	 * @access private
	 * @since 3.8.9
	 *
	 * @param array $data
	 * @return array
	 */
	private function get_data_format( $data ) {
		$format = array();

		foreach ( $data as $key => $value ) {
			$format[] = self::get_column_format( $key );
		}

		return $format;
	}

	/**
	 * Saves the purchase log back to the database
	 *
	 * @access public
	 * @since 3.8.9
	 *
	 * @return void
	 */
	public function save() {
		global $wpdb;

		do_action( 'wpsc_purchase_log_pre_save', $this );

		// $this->args['col'] is empty when the record is new and needs to
		// be inserted. Otherwise, it means we're performing an update
		$where_col = $this->args['col'];

		$result = false;

		if ( $where_col ) {
			$where_val = $this->args['value'];
			$where_format = self::get_column_format( $where_col );
			do_action( 'wpsc_purchase_log_pre_update', $this );
			self::delete_cache( $where_val, $where_col );
			$data = apply_filters( 'wpsc_purchase_log_update_data', $this->data );
			$format = $this->get_data_format( $data );
			$result = $wpdb->update( WPSC_TABLE_PURCHASE_LOGS, $data, array( $where_col => $where_val ), $format, array( $where_format ) );
			do_action( 'wpsc_purchase_log_update', $this );
		} else {
			do_action( 'wpsc_purchase_log_pre_insert', $this );
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

		if ( $this->is_status_changed ) {

			if ( $this->is_transaction_completed() ) {
				$this->update_downloadable_status();
			}

			$current_status          = $this->get( 'processed' );
			$previous_status         = $this->previous_status;
			$this->previous_status   = $current_status;
			$this->is_status_changed = false;

			do_action( 'wpsc_update_purchase_log_status', $this->get( 'id' ), $current_status, $previous_status, $this );
		}

		if ( ! empty( $this->meta_data ) ) {
			$this->save_meta();
		}

		do_action( 'wpsc_purchase_log_save', $this );

		return $result;
	}

	/**
	 * Save meta data for purchase log, if any was set via set().
	 *
	 * @access public
	 * @since  3.11.5
	 *
	 * @return WPSC_Purchase_Log  The current object (for method chaining)
	 */
	public function save_meta() {
		do_action( 'wpsc_purchase_log_pre_save_meta', $this );

		$meta = $this->get_meta();

		foreach ( $meta as $key => $value ) {

			if ( 'notes' === $key ) {
				wpsc_get_order_notes( $this )->save();
				continue;
			}

			$is_multiple_meta = isset( self::$multiple_meta[ $key ] );

			if ( $is_multiple_meta ) {

				if ( is_array( $value ) ) {
					foreach ( $value as $val ) {
						wpsc_add_purchase_meta( $this->get( 'id' ), $key, $val );
					}
				}
			} else {
				wpsc_update_purchase_meta( $this->get( 'id' ), $key, $value );
			}
		}

		do_action( 'wpsc_purchase_log_save_meta', $this );

		return $this;
	}

	private function update_downloadable_status() {
		global $wpdb;

		foreach ( $this->get_items() as $item ) {
			$wpdb->update(
				WPSC_TABLE_DOWNLOAD_STATUS,
				array(
					'active' => '1'
				),
				array(
					'cartid'  => $item->id,
					'purchid' => $this->get( 'id' ),
				)
			);
		}
	}

	public function have_downloads_locked() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `ip_number` FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `purchid` = %d ", $this->get( 'id' ) );
		$ip_number = $wpdb->get_var( $sql );

		return $ip_number;
	}

	/**
	 * Adds ability to retrieve a purchase log by a meta key or value.
	 *
	 * @since  3.11.5
	 *
	 * @param  string $key   Meta key. Optional.
	 * @param  string $value Meta value. Optional.
	 *
	 * @return false|WPSC_Purchase_Log  False if no log is found or meta key and value are both not provided. WPSC_Purchase_Log object if found.
	 */
	public static function get_log_by_meta( $key = '', $value = '' ) {

		if ( empty( $key ) && empty( $value ) ) {
			return false;
		}

		global $wpdb;

		if ( ! empty( $key ) && empty( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT wpsc_purchase_id FROM ' . WPSC_TABLE_PURCHASE_META . ' WHERE meta_key = %s', $key );
		} else if ( empty( $key ) && ! empty( $value ) ) {
			$sql = $wpdb->prepare( 'SELECT wpsc_purchase_id FROM ' . WPSC_TABLE_PURCHASE_META . ' WHERE meta_value = %s', $value );
		} else {
			$sql = $wpdb->prepare( 'SELECT wpsc_purchase_id FROM ' . WPSC_TABLE_PURCHASE_META . ' WHERE meta_key = %s AND meta_value = %s', $key, $value );
		}

		$id = $wpdb->get_var( $sql );

		if ( $id ) {
			return new WPSC_Purchase_Log( $id );
		} else {
			return false;
		}
	}

	public function get_next_log_id() {
		if ( ! $this->exists() ) {
			return false;
		}

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT MIN(id) FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id > %d",
			$this->get( 'id' )
		);

		return $wpdb->get_var( $sql );
	}

	public function get_previous_log_id() {
		if ( ! $this->exists() ) {
			return false;
		}

		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT MAX(id) FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id < %d",
			$this->get( 'id' )
		);

		return $wpdb->get_var( $sql );
	}

	public function is_transaction_completed() {
		return WPSC_Purchase_Log::is_order_status_completed( $this->get( 'processed' ) );
	}

	public function can_edit() {
		if ( null === $this->can_edit ) {
			$can_edit = current_user_can( 'edit_others_posts' ) && ! $this->is_transaction_completed();

			/**
			 * This filter allows overriding the default order-edit behavior.
			 * Default behavior only allows editing orders which are not yet completed.
			 *
			 * If you want to allow editing completed orders via this fitler, you will
			 * be responsible for ensuring that the item stock is adjusted accordingly.
			 *
			 * @since 3.11.5
			 *
			 * @var bool              $can_edit Whether this order can be edited
			 * @var WPSC_Purchase_Log $log      This log object
			 */
			$this->can_edit = apply_filters( 'wpsc_can_edit_order', $can_edit, $this );
		}

		return $this->can_edit;
	}

	public function is_order_received() {
		return $this->get( 'processed' ) == self::ORDER_RECEIVED;
	}

	public function is_incomplete_sale() {
		return $this->get( 'processed' ) == self::INCOMPLETE_SALE;
	}

	public function is_accepted_payment() {
		return $this->get( 'processed' ) == self::ACCEPTED_PAYMENT;
	}

	public function is_job_dispatched() {
		return $this->get( 'processed' ) == self::JOB_DISPATCHED;
	}

	public function is_closed_order() {
		return $this->get( 'processed' ) == self::CLOSED_ORDER;
	}

	public function is_payment_declined() {
		return $this->get( 'processed' ) == self::PAYMENT_DECLINED;
	}

	public function is_refunded() {
		return $this->get( 'processed' ) == self::REFUNDED;
	}

	public function is_refund_pending() {
		return $this->get( 'processed' ) == self::REFUND_PENDING;
	}

	/*
	 * Utility methods using the $purchlogitem global.. Global usage to be replaced in the future.
	 *
	 * TODO: seriously get rid of all these badly coded purchaselogs.functions.php functions
	 * and wpsc_purchaselogs/wpsc_purchaselogs_items classes.
	 */

	/**
	 * Init the purchase log items for this purchase log.
	 *
	 * @since  3.11.5
	 *
	 * @return wpsc_purchaselogs_items|false The purhchase log item object or false.
	 */
	public function init_items() {
		global $purchlogitem;
		if ( ! $this->exists() ) {
			return false;
		}

		$purchlogitem = new wpsc_purchaselogs_items( $this->get( 'id' ), $this );
	}

	public function buyers_name() {
		global $purchlogitem;

		if ( null === $this->buyers_name ) {
			$first_name = $last_name = '';

			if ( isset( $purchlogitem->userinfo['billingfirstname'] ) ) {
				$first_name = $purchlogitem->userinfo['billingfirstname']['value'];
			}

			if ( isset( $purchlogitem->userinfo['billinglastname'] ) ) {
				$last_name = ' ' . $purchlogitem->userinfo['billinglastname']['value'];
			}

			$this->buyers_name = trim( $first_name . $last_name );
		}

		return $this->buyers_name;
	}

	public function buyers_city() {
		global $purchlogitem;

		if ( null === $this->buyers_city ) {
			$this->buyers_city = isset( $purchlogitem->userinfo['billingcity']['value'] ) ? $purchlogitem->userinfo['billingcity']['value'] : '';
		}

		return $this->buyers_city;
	}

	public function buyers_email() {
		global $purchlogitem;

		if ( null === $this->buyers_email ) {
			$this->buyers_email = isset( $purchlogitem->userinfo['billingemail']['value'] ) ? $purchlogitem->userinfo['billingemail']['value'] : '';
		}

		return $this->buyers_email;
	}

	public function buyers_address() {
		global $purchlogitem;

		if ( null === $this->buyers_address ) {
			$this->buyers_address = isset( $purchlogitem->userinfo['billingaddress']['value'] ) ? nl2br( esc_html( $purchlogitem->userinfo['billingaddress']['value'] ) ) : '';
		}

		return $this->buyers_address;
	}

	public function buyers_state_and_postcode() {
		global $purchlogitem;

		if ( null === $this->buyers_state_and_postcode ) {

			if ( is_numeric( $this->get( 'billing_region' ) ) ) {
				$state = wpsc_get_region( $this->get( 'billing_region' ) );
			} else {
				$state = $purchlogitem->userinfo['billingstate']['value'];
				$state = is_numeric( $state ) ? wpsc_get_region( $state ) : $state;
			}

			$output = esc_html( $state );

			if ( isset( $purchlogitem->userinfo['billingpostcode']['value'] ) && ! empty( $purchlogitem->userinfo['billingpostcode']['value'] ) ) {
				if ( $output ) {
					$output .= ', '; // TODO determine if it's ok to make this a space only (like shipping_state_and_postcode)
				}
				$output .= $purchlogitem->userinfo['billingpostcode']['value'];
			}

			$this->buyers_state_and_postcode = $output;
		}

		return $this->buyers_state_and_postcode;
	}

	public function buyers_country() {
		global $purchlogitem;

		if ( null === $this->buyers_country ) {
			$this->buyers_country = isset( $purchlogitem->userinfo['billingcountry']['value'] ) ? wpsc_get_country( $purchlogitem->userinfo['billingcountry']['value'] ) : '';
		}

		return $this->buyers_country;
	}

	public function buyers_phone() {
		global $purchlogitem;

		if ( null === $this->buyers_phone ) {
			$this->buyers_phone = isset( $purchlogitem->userinfo['billingphone']['value'] ) ? $purchlogitem->userinfo['billingphone']['value'] : '';
		}

		return $this->buyers_phone;
	}

	public function shipping_name() {
		global $purchlogitem;

		if ( null === $this->shipping_name ) {
			$this->shipping_name = isset( $purchlogitem->shippinginfo['shippingfirstname']['value'] ) ? $purchlogitem->shippinginfo['shippingfirstname']['value'] : '';

			if ( isset( $purchlogitem->shippinginfo['shippinglastname']['value'] ) ) {
				$this->shipping_name .= ' ' . $purchlogitem->shippinginfo['shippinglastname']['value'];
			}
		}

		return $this->shipping_name;
	}

	public function shipping_city() {
		global $purchlogitem;

		if ( null === $this->shipping_city ) {
			$this->shipping_city = isset( $purchlogitem->shippinginfo['shippingcity']['value'] ) ? $purchlogitem->shippinginfo['shippingcity']['value'] : '';
		}

		return $this->shipping_city;
	}

	public function shipping_address() {
		global $purchlogitem;

		if ( null === $this->shipping_address ) {
			$this->shipping_address = isset( $purchlogitem->shippinginfo['shippingaddress']['value'] ) ? nl2br( esc_html( $purchlogitem->shippinginfo['shippingaddress']['value'] ) ) : '';
		}

		return $this->shipping_address;
	}

	public function shipping_state_and_postcode() {
		global $purchlogitem;

		if ( null === $this->shipping_state_and_postcode ) {
			if ( is_numeric( $this->get( 'shipping_region' ) ) ) {
				$output = wpsc_get_region( $this->get( 'shipping_region' ) );
			} else {
				$state = $purchlogitem->shippinginfo['shippingstate']['value'];
				$output = is_numeric( $state ) ? wpsc_get_region( $state ) : $state;
			}

			if ( !empty( $purchlogitem->shippinginfo['shippingpostcode']['value'] ) ){
				if ( $output ) {
					$output .= ' ';
				}

				$output .= $purchlogitem->shippinginfo['shippingpostcode']['value'];
			}

			$this->shipping_state_and_postcode = $output;
		}

		return $this->shipping_state_and_postcode;
	}

	public function shipping_country() {
		global $purchlogitem;

		if ( null === $this->shipping_country ) {
			$this->shipping_country = isset( $purchlogitem->shippinginfo['shippingcountry'] )
				? wpsc_get_country( $purchlogitem->shippinginfo['shippingcountry']['value'] )
				: '';
		}

		return $this->shipping_country;
	}

	public function payment_method() {
		global $nzshpcrt_gateways;

		if ( null === $this->payment_method ) {
			if ( 'wpsc_merchant_testmode' == $this->get( 'gateway' ) ) {
				$this->payment_method = __( 'Manual Payment', 'wp-e-commerce' );
			} else {
				foreach ( (array) $nzshpcrt_gateways as $gateway ) {
					if ( isset( $gateway['internalname'] ) && $gateway['internalname'] == $this->get( 'gateway' ) ) {
						$this->payment_method = $gateway['name'];
					}
				}

				if ( ! $this->payment_method ) {
					$this->payment_method = $this->get( 'gateway' );
				}
			}
		}

		return $this->payment_method;
	}

	public function shipping_method() {
		global $wpsc_shipping_modules;

		if ( null === $this->shipping_method ) {

			if ( ! empty( $wpsc_shipping_modules[ $this->get( 'shipping_method' ) ] ) ) {
				$this->shipping_method = $wpsc_shipping_modules[ $this->get( 'shipping_method' ) ]->getName();
			} else {
				$this->shipping_method = $this->get( 'shipping_method' );
			}
		}

		return $this->shipping_method;
	}

	/**
	 * Returns base shipping should make a function to calculate items shipping as well
	 *
	 * @since  3.11.5
	 *
	 * @param  boolean $numeric Return numeric value.
	 *
	 * @return mixed
	 */
	public function discount( $numeric = false ) {
		$discount = $this->get( 'discount_value' );
		if ( ! $numeric ) {
			$discount = wpsc_currency_display( $discount, array( 'display_as_html' => false ) );
		}

		return $discount;
	}

	/**
	 * Returns base shipping should make a function to calculate items shipping as well
	 *
	 * @since  3.11.5
	 *
	 * @param  boolean $numeric       Return numeric value.
	 * @param  boolean $include_items Whether to calculate per-item-shipping.
	 *
	 * @return mixed
	 */
	public function shipping( $numeric = false, $include_items = false ) {
		$total_shipping = $this->get( 'base_shipping' );

		if ( $include_items ) {
			$total_shipping = $this->meta_data['total_shipping'];
		}

		if ( ! $numeric ) {
			$total_shipping = wpsc_currency_display( $total_shipping, array( 'display_as_html' => false ) );
		}

		return $total_shipping;
	}

	/**
	 * Returns taxes total.
	 *
	 * @since  3.11.5
	 *
	 * @param  boolean $numeric Return numeric value.
	 *
	 * @return mixed
	 */
	public function taxes( $numeric = false ) {
		$taxes = $this->get( 'wpec_taxes_total' );

		if ( ! $numeric ) {
			$taxes = wpsc_currency_display( $taxes, array( 'display_as_html' => false ) );
		}

		return $taxes;
	}

	public function get_subtotal() {
		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += ( $item->price * $item->quantity );
			$subtotal += ( $item->pnp );
		}

		return $subtotal;
	}

	/**
	 * Get total price.
	 *
	 * @since  3.11.7
	 *
	 * @return float Price.
	 */
	public function get_total() {
		return $this->get_subtotal() - $this->discount( true ) + $this->shipping( true ) + $this->taxes( true );
	}

	/**
	 * Get total price display.
	 *
	 * @param  array $args Args for wpsc_currency_display().
	 *
	 * @return mixed Price.
	 */
	public function total_price( $args = array() ) {
		$args = wp_parse_args( $args, array( 'display_as_html' => false ) );
		return wpsc_currency_display( $this->get_total(), $args );
	}

	public function get_total_refunded() {
		$total_refund = $this->get( 'total_order_refunded' );

		return empty( $total_refund ) ? '0.00' : $this->get( 'total_order_refunded' );
	}

	public function get_remaining_refund() {
		return $this->get( 'totalprice' ) - $this->get_total_refunded();
	}

	/**
	 * Add a purchase log note.
	 *
	 * @since 3.12.0
	 *
	 * @param mixed $note_text  String to add note. Optionally Accepts an array to specify note attributes: {
	 *    @type string $type    The note type. Defaults to 'default', but can be 'error'.
	 *    @type string $status  The note status. Defaults to 'public'.
	 *    @type int    $time    The note timestamp. Defaults to time().
	 *    @type string $content The note text.
	 * }
	 *
	 * @return WPSC_Purchase_Log The current object (for method chaining)
	 */
	public function add_note( $note_text ) {
		static $notes = null;

		if ( ! ( $notes instanceof WPSC_Purchase_Log_Notes ) ) {
			$notes = wpsc_get_order_notes( $this );
		}

		$notes->add( $note_text )->save();

		return $this;
	}

	/**
	 * Add a purchase log refund note.
	 *
	 * @since 3.12.0
	 *
	 * @param  mixed  $note_text         String to add refund note.
	 * @param  string $reason_for_refund Optional reason for refund. Will display on a new line from default text.
	 *
	 * @return WPSC_Purchase_Log         The current object (for method chaining)
	 */
	public function add_refund_note( $note_text, $reason_for_refund = '' ) {

		if ( ! empty( $reason_for_refund ) ) {
			$note_text .= sprintf( __( "\nReason: %s", 'wp-e-commerce' ), $reason_for_refund );
		}

		return $this->add_note( $note_text );
	}

}
