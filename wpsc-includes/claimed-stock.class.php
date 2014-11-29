<?php

/**
 * Get Stock Keeping Time
 *
 * Defaults to day if not set.
 *
 * @since   3.8.14
 * @access  public
 *
 * @return  int  Stock keeping time.
 *
 * @uses  get_option()
 * @uses  apply_filters() Filters output through wpsc_stock_keeping_time
 */
function wpsc_get_stock_keeping_time() {
	return apply_filters( 'wpsc_stock_keeping_time', (float) get_option( 'wpsc_stock_keeping_time', 1 ) );
}

/**
 * Get Stock Keeping Interval
 *
 * Gets the stock keeping interval unit - hour / day / week.
 * Defaults to day if not set.
 *
 * @since   3.8.14
 * @access  public
 *
 * @return  int  Stock keeping interval unit.
 *
 * @uses  get_option()
 * @uses  apply_filters() Filters output through wpsc_stock_keeping_interval
 */
function wpsc_get_stock_keeping_interval() {
	return apply_filters( 'wpsc_stock_keeping_interval', get_option( 'wpsc_stock_keeping_interval', 'day' ) );
}

/**
 * Get Stock Keeping Seconds
 *
 * Gets the stock keeping time in seconds.
 *
 * @since   3.8.14
 * @access  public
 *
 * @return  int  Stock keeping interval unit.
 *
 * @uses  get_option()
 * @uses  apply_filters() Filters output through wpsc_stock_keeping_seconds
 */
function wpsc_get_stock_keeping_seconds() {
	$time     = wpsc_get_stock_keeping_time();
	$interval = wpsc_get_stock_keeping_interval();
	return apply_filters( 'wpsc_stock_keeping_seconds', wpsc_convert_time_interval_to_seconds( $time, $interval ) );
}

/**
 * WP eCommerce Claimed Stock Class
 *
 * The Cart class handles adding, removing and adjusting claimed stock.
 *
 * @package     wp-e-commerce
 * @since       3.8.14
 * @subpackage  wpsc-claimed-stock-class
 */
class WPSC_Claimed_Stock {

	/**
	 * Product ID.
	 *
	 * @access  private
	 * @since  3.8.14
	 *
	 * @var  int
	 */
	private $product_id = 0;

	/**
	 * Cart ID.
	 *
	 * @access  private
	 * @since  3.8.14
	 *
	 * @var  string|array|bool
	 */
	private $cart_id = false;

	/**
	 * Cart Submitted.
	 *
	 * @access  private
	 * @since  3.8.14
	 *
	 * @var  int
	 */
	private $cart_submitted = null;

	/**
	 * Constructor of the claimed stock object.
	 *
	 * The claimed stock object can be used to perform actions on a set of claimed stock.
	 * Claimed stock will be queried based on `cart_id`, `product_id`, or both.
	 * If neither is supplied the methods will affect ALL claimed stock.
	 *
	 * Eg:
	 *
	 * // Clear all claimed stock immediately
	 * $claimed_stock = new WPSC_Claimed_Stock();
	 * $claimed_stock->clear_claimed_stock( 0 );
	 *
	 * // Get total amount of claimed stock for Product 345
	 * $claimed_stock = new WPSC_Claimed_Stock( array( 'product_id' => 345 ) );
	 * $claimed_stock->get_claimed_stock_count();
	 *
	 * // Submit stock claims for cart ID 'CLA1M3D5TOCK' to purchase log 1256
	 * $claimed_stock = new WPSC_Claimed_Stock( array( 'cart_id' => 'CLA1M3D5TOCK' ) );
	 * $claimed_stock->submit_claimed_stock( 1256 );
	 *
	 * @access  public
	 * @since  3.8.14
	 *
	 * @param  array  $args  Optional. Array of claimed stock query parameters.
	 */
	public function __construct( $args = null ) {
		$args = wp_parse_args( $args, array(
			'product_id'     => 0,
			'cart_id'        => false,
			'cart_submitted' => null
		) );
		$this->product_id = absint( $args['product_id'] );
		$this->cart_id = $args['cart_id'];
		if ( ! is_null( $args['cart_submitted'] ) )
			$args['cart_submitted'] = absint( $args['cart_submitted'] );
		$this->cart_submitted = $args['cart_submitted'];
	}

	/**
	 * Get Where SQL
	 *
	 * Gets SQL where clause based on the objects cart and product IDs.
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @param   string  $where  Optional. An already prepared where clause not prefixed by WHERE or AND.
	 * @return  string  SQL WHERE clause.
	 *
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	private function _get_where_sql( $where = '' ) {
		global $wpdb;
		$where_clauses = array();
		if ( $this->product_id > 0 )
			$where_clauses[] = $wpdb->prepare( '`product_id` IN(%d)', $this->product_id );

		// Handle array of cart IDs or single cart ID
		if ( ! empty( $this->cart_id ) ) {
			if ( is_array( $this->cart_id ) ) {
				$where_clauses[] = $wpdb->prepare( '`cart_id` IN(' . implode( ', ', array_fill( 0, count( $this->cart_id ), '%s' ) ) . ')', $this->cart_id );
			} else {
				$where_clauses[] = $wpdb->prepare( '`cart_id` IN(%s)', $this->cart_id );
			}
		}

		if ( ! is_null( $this->cart_submitted ) )
			$where_clauses[] = $wpdb->prepare( '`cart_submitted` = %d', $this->cart_submitted );
		if ( ! empty( $where ) )
			$where_clauses[] = $where;
		if ( count( $where_clauses ) > 0 )
			return ' WHERE ' . implode( ' AND ', $where_clauses );
		return '';
	}

	/**
	 * Get Claimed Stock Count
	 *
	 * Gets total amount of claimed stock for the WPSC_Claimed_Stock object.
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @return  int  Amount of claimed stock.
	 *
	 * @uses  wpdb::get_var()                       Queries DB.
	 * @uses  WPSC_Claimed_Stock::_get_where_sql()  Gets product_id/cart_id SQL WHERE clause.
	 */
	public function get_claimed_stock_count() {
		global $wpdb;
		$where = $this->_get_where_sql();
		return $wpdb->get_var( 'SELECT SUM(`stock_claimed`) FROM `' . WPSC_TABLE_CLAIMED_STOCK . '`' . $where );
	}

	/**
	 * Clear Claimed Stock
	 *
	 * Clear stock claims that are over a specified number of seconds old.
	 * If no seconds sepecific the default stock keeping time settings are used.
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @param  int  $seconds  Clear stock over this number of seconds old.
	 *
	 * @uses  wpsc_get_stock_keeping_seconds()      Gets stock keeping time in seconds.
	 * @uses  wpdb::query()                         Queries DB.
	 * @uses  wpdb::prepare()                       Prepare DB query.
	 * @uses  WPSC_Claimed_Stock::_get_where_sql()  Extends product_id/cart_id SQL WHERE clause.
	 */
	public function clear_claimed_stock( $seconds = null ) {
		global $wpdb;

		// If seconds not set, use default settings
		if ( ! is_int( $seconds ) ) {
			$seconds  = wpsc_get_stock_keeping_seconds();
		}

		$where_clause = $wpdb->prepare( 'last_activity < UTC_TIMESTAMP() - INTERVAL %d SECOND', $seconds );
		$where = $this->_get_where_sql( $where_clause );
		$wpdb->query( 'DELETE FROM ' . WPSC_TABLE_CLAIMED_STOCK . $where );
	}

	/**
	 * Update Claimed Stock
	 *
	 * Updates unclaimed product stock for a cart instance.
	 * Will only work for instances that have a cart_id and product_id.
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @param  int  $stock_claimed  Amount of claimed stock.
	 *
	 * @uses  wpdb::query()    Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public function update_claimed_stock( $stock_claimed ) {
		global $wpdb;

		if ( empty( $this->cart_id ) || empty( $this->product_id ) )
			return;

		$wpdb->query( $wpdb->prepare( 'REPLACE INTO `' . WPSC_TABLE_CLAIMED_STOCK . '`
			( `product_id` , `stock_claimed` , `last_activity` , `cart_id` )
			VALUES
			( %d, %s, %s, %s );',
			$this->product_id,
			$stock_claimed,
			date( 'Y-m-d H:i:s' ),
			$this->cart_id
		) );
	}

	/**
	 * Submit Claimed Stock
	 *
	 * Updates claimed stock when cart is submitted to associate it with a purchase log instead.
	 * Will only work for instances that have a cart_id.
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @param  int|object  $log  Purchase Log object or ID.
	 *
	 * @uses  wpdb::query()    Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public function submit_claimed_stock( $log ) {
		global $wpdb;

		// Only process if query include cart ID
		if ( empty( $this->cart_id ) ) {
			return;
		}

		// Accept WPSC_Purchase_Log object or ID
		if ( is_numeric( $log ) ) {
			$purchase_log_id = $log;
		} else if ( is_a( $log, 'WPSC_Purchase_Log' ) ) {
			$purchase_log_id = $log;
		} else {
			return;
		}

		$wpdb->query( $wpdb->prepare(
			"UPDATE `" . WPSC_TABLE_CLAIMED_STOCK . "`
			SET `cart_id` = '%d', `cart_submitted` = '1'
			WHERE `cart_id` IN(%s)",
			$purchase_log_id,
			$this->cart_id
		) );
	}

	/**
	 * Get Purchase Log Claimed Stock
	 *
	 * Gets an array of claimed stock data for purchase log (submitted cart).
	 *
	 * @since   3.8.14
	 * @access  public
	 *
	 * @return  array  Purchase log claimed stock results.
	 *
	 * @uses  wpdb::get_results()  Queries DB.
	 * @uses  wpdb::prepare()      Prepare DB query.
	 */
	public function get_purchase_log_claimed_stock() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT `cs`.`product_id`, `cs`.`stock_claimed`, `pl`.`id`, `pl`.`processed`
			FROM `" . WPSC_TABLE_CLAIMED_STOCK . "` `cs`
			JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` `pl`
				ON `cs`.`cart_id` = `pl`.`id`
				WHERE `cs`.`cart_id` = %s",
			$this->cart_id
		) );
	}

}
