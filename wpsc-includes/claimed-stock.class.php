<?php

/**
 * Get Stock Keeping Time
 *
 * Defaults to day if not set.
 *
 * @since   3.8.13
 * @access  public
 *
 * @return  int  Stock keeping time.
 *
 * @uses  get_option()
 */
function wpsc_get_stock_keeping_time() {
	return (float) get_option( 'wpsc_stock_keeping_time', 1 );
}

/**
 * Get Stock Keeping Interval
 *
 * Gets the stock keeping interval unit - hour / day / week.
 * Defaults to day if not set.
 *
 * @since   3.8.13
 * @access  public
 *
 * @return  int  Stock keeping interval unit.
 *
 * @uses  get_option()
 */
function wpsc_get_stock_keeping_interval() {
	return get_option( 'wpsc_stock_keeping_interval', 'day' );
}

/**
 * Convert time interval to seconds.
 *
 * Takes a number an unit of time (hour/day/week) and converts it to seconds.
 * It allows decimal intervals like 1.5 days.
 *
 * @since   3.8.13
 * @access  public
 *
 * @param   int  $time      Stock keeping time.
 * @param   int  $interval  Stock keeping interval unit (hour/day/week).
 * @return  int             Seconds.
 */
function wpsc_convert_time_interval_to_seconds( $time, $interval ) {
	$convert = array(
		'hour' => 3600,
		'day'  => 86400,
		'week' => 604800,
	);
	return floor( $time * $convert[$interval] );
}

/**
 * WP eCommerce Claimed Stock Class
 *
 * The Cart class handles adding, removing and adjusting claimed stock.
 *
 * @package     wp-e-commerce
 * @since       3.8.13
 * @subpackage  wpsc-claimed-stock-class
 */
class WPSC_Claimed_Stock {

	/**
	 * Get Claimed Stock
	 *
	 * Gets total amount of claimed stock of a product.
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param   int  $product_id  WPEC Product ID.
	 * @return  int               Amount of claimed stock.
	 *
	 * @uses  wpdb::get_var()  Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public static function get_claimed_stock( $product_id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(`stock_claimed`) FROM `' . WPSC_TABLE_CLAIMED_STOCK . '` WHERE `product_id` IN(%d)', $product_id ) );
	}

	/**
	 * Get Claimed Variation Stock
	 *
	 * Gets total amount of claimed stock of a product.
	 *
	 * @todo  Is this actually neccessary or can we just use get_claimed_stock()?
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param   int  $product_id        WPEC Product ID.
	 * @param   int  $priceandstock_id  Variation Stock ID.
	 * @return  int                     Amount of claimed stock.
	 *
	 * @uses  wpdb::get_var()  Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public static function get_claimed_variation_stock( $product_id, $priceandstock_id = 0 ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(`stock_claimed`) FROM `' . WPSC_TABLE_CLAIMED_STOCK . '` WHERE `product_id` IN(%d) AND `variation_stock_id` IN(%d)', $product_id, $priceandstock_id ) );
	}

	/**
	 * Clear Claimed Stock
	 *
	 * Clear stock claims that are over a specified number of seconds old.
	 * If no seconds sepecific the default stock keeping time settings are used.
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param  int  $seconds  Clear stock over this number of seconds old.
	 *
	 * @uses  wpsc_get_stock_keeping_time()            Gets stock keeping time.
	 * @uses  wpsc_get_stock_keeping_interval()        Gets stock leeping unit (hour/day/week).
	 * @uses  wpsc_convert_time_interval_to_seconds()  Converts time and interval to seconds.
	 * @uses  wpdb::query()                            Queries DB.
	 * @uses  wpdb::prepare()                          Prepare DB query.
	 */
	public static function clear_claimed_stock( $seconds = null ) {
		global $wpdb;

		// If seconds not set, use default settings
		if ( ! is_int( $seconds ) ) {
			$time     = wpsc_get_stock_keeping_time();
			$interval = wpsc_get_stock_keeping_interval();
			$seconds  = wpsc_convert_time_interval_to_seconds( $time, $interval );
		}

		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . WPSC_TABLE_CLAIMED_STOCK . ' WHERE last_activity < UTC_TIMESTAMP() - INTERVAL %d SECOND', $seconds ) );
	}

	/**
	 * Update Claimed Stock
	 *
	 * Updates unclaimed stock for a cart instance.
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param  int  $cart_id        WPEC Cart ID.
	 * @param  int  $product_id     WPEC Product ID.
	 * @param  int  $stock_claimed  Amount of claimed stock.
	 *
	 * @uses  wpdb::query()    Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public static function update_claimed_stock( $cart_id, $product_id, $stock_claimed ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'REPLACE INTO `' . WPSC_TABLE_CLAIMED_STOCK . '`
			( `product_id` , `stock_claimed` , `last_activity` , `cart_id` )
			VALUES
			( %d, `%s`, `%s`, `%s` );',
			$product_id,
			$stock_claimed,
			date( 'Y-m-d H:i:s' ),
			$cart_id
		) );
	}

	/**
	 * Get Purchase Log Claimed Stock
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param  int  $purchase_log_id  Purchase Log ID.
	 *
	 * @uses  wpdb::get_results()  Queries DB.
	 * @uses  wpdb::prepare()      Prepare DB query.
	 */
	public static function get_purchase_log_claimed_stock( $purchase_log_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT `cs`.`product_id`, `cs`.`stock_claimed`, `pl`.`id`, `pl`.`processed`
			FROM `" . WPSC_TABLE_CLAIMED_STOCK . "` `cs`
			JOIN `" . WPSC_TABLE_PURCHASE_LOGS . "` `pl`
				ON `cs`.`cart_id` = `pl`.`id`
				WHERE `cs`.`cart_id` = '%s'",
			$purchase_log_id
		) );
	}

	/**
	 * Clear Purchase Log Claimed Stock
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param  int  $purchase_log_id  Purchase Log ID.
	 *
	 * @uses  wpdb::query()    Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public static function clear_purchase_log_claimed_stock( $purchase_log_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM `" . WPSC_TABLE_CLAIMED_STOCK . "` WHERE `cart_id` IN (%s)",
			$purchase_log_id
		) );
	}

	/**
	 * Submit Stock Claims
	 *
	 * Updates claimed stock when cart is submitted.
	 *
	 * @since   3.8.13
	 * @access  public
	 *
	 * @param  int  $cart_id          Cart ID.
	 * @param  int  $purchase_log_id  Purchase Log ID.
	 *
	 * @uses  wpdb::query()    Queries DB.
	 * @uses  wpdb::prepare()  Prepare DB query.
	 */
	public static function submit_stock_claims( $cart_id, $purchase_log_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"UPDATE `" . WPSC_TABLE_CLAIMED_STOCK . "` 
			SET `cart_id` = '%d', `cart_submitted` = '1' 
			WHERE `cart_id` IN('%s')",
			$purchase_log_id,
			$cart_id
		) );
	}

}
