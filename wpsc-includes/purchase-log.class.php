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

	private static $int_cols = array(
		'id',
		'statusno',
		'processed',
		'user_ID',
	);

	private static function get_column_format( $col ) {
		if ( in_array( $col, self::$string_cols ) )
			return '%s';

		if ( in_array( $col, self::$int_cols ) )
			return '%d';

		return '%f';
	}

	/**
	 * Contains the values fetched from the DB
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var array
	 */
	private $data = array();
	private $meta_data = array();

	private $gateway_data = array();

	/**
	 * True if the DB row is fetched into $this->data.
	 *
	 * @access private
	 * @since 3.9
	 *
	 * @var string
	 */
	private $fetched           = false;
	private $is_status_changed = false;
	private $previous_status   = false;

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
		wp_cache_set( $id, $log->cart_contents, 'wpsc_purchase_log_cart_contents' );
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
		if ( false === $value )
			return;

		if ( is_array( $value ) ) {
			$this->set( $value );
			return;
		}

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
		if ( $col == 'id' ) {
			$this->data = wp_cache_get( $value, 'wpsc_purchase_logs' );
			$this->cart_contents = wp_cache_get( $value, 'wpsc_purchase_log_cart_contents' );
		}

		// cache exists
		if ( $this->data ) {
			$this->fetched = true;
			$this->exists = true;
			return;
		}
	}

	private function set_total_shipping() {
		$total_shipping = 0;
		$base_shipping  = $this->get( 'base_shipping' );
		$item_shipping  = wp_list_pluck( $this->get_cart_contents(), 'pnp' );

		$this->meta_data['total_shipping'] = $base_shipping + array_sum( $item_shipping );
		return $this->meta_data['total_shipping'];
	}

	private function set_meta_props() {
		$this->set_total_shipping();
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
		if ( ! $this->args['col'] || ! $this->args['value'] )
			return;

		extract( $this->args );

		$format = self::get_column_format( $col );
		$sql = $wpdb->prepare( "SELECT * FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE {$col} = {$format}", $value );

		$this->exists = false;

		if ( $data = $wpdb->get_row( $sql, ARRAY_A ) ) {
			$this->exists = true;
			$this->data = apply_filters( 'wpsc_purchase_log_data', $data );
			$this->cart_contents = $this->get_cart_contents();

			$this->set_meta_props();
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

		if ( isset( $this->data[$key] ) )
			$value = $this->data[$key];
		elseif ( isset( $this->meta_data[$key] ) )
			$value = $this->meta_data[$key];
		else
			$value = null;

		return apply_filters( 'wpsc_purchase_log_get_property', $value, $key, $this );
	}

	public function get_cart_contents() {
		global $wpdb;

		if ( $this->fetched )
			return $this->cart_contents;

		$id = $this->get( 'id' );

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
		if ( ! $this->exists() )
			return array();

		$subtotal = 0;
		$shipping = wpsc_format_convert_price( (float) $this->get( 'base_shipping' ), $from_currency, $to_currency );
		$tax = 0;
		$items = array();

		$this->gateway_data = array(
			'amount'  => wpsc_format_convert_price( $this->get( 'totalprice' ), $from_currency, $to_currency ),
			'invoice' => $this->get( 'sessionid' ),
			'tax'     => wpsc_format_convert_price( $this->get( 'wpec_taxes_total' ), $from_currency, $to_currency ),
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

		$this->gateway_data['discount'] = wpsc_format_convert_price( (float) $this->get( 'discount_value' ), $from_currency, $to_currency );

		$this->gateway_data['items'] = $items;
		$this->gateway_data['shipping'] = $shipping;
		$this->gateway_data['subtotal'] = $subtotal;

		if ( $from_currency ) {
			// adjust total amount in case there's slight decimal error
			$total = $subtotal + $shipping + $this->gateway_data['tax'] - $this->get( 'discount' );
			if ( $this->gateway_data['amount'] != $total )
				$this->gateway_data['amount'] = $total;
		}

		$this->gateway_data = apply_filters( 'wpsc_purchase_log_gateway_data', $this->gateway_data, $this->get_data() );
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

		if ( array_key_exists( 'processed', $properties ) ) {
			$this->previous_status = $this->get( 'processed' );

			if ( $properties['processed'] != $this->previous_status )
				$this->is_status_changed = true;
		}

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
			$format[] = self::get_column_format( $key );
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

		if ( $this->is_status_changed ) {
			$current_status = $this->get( 'processed' );
			do_action( 'wpsc_update_purchase_log_status', $this->get( 'id' ), $this->get( 'processed' ), $this->previous_status, $this );
			if ( $this->is_transaction_completed() )
				$this->update_downloadable_status();
			$this->previous_status = false;
		}

		do_action( 'wpsc_purchase_log_save', $this );
		return $result;
	}

	private function update_downloadable_status() {
		foreach ( $this->get_cart_contents() as $item ) {
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

	public function is_transaction_completed() {
		$status = $this->get( 'processed' );
		$completed_statuses = array(
			self::ACCEPTED_PAYMENT,
			self::JOB_DISPATCHED,
			self::CLOSED_ORDER,
		);
		return in_array( $status, $completed_statuses );
	}

	private function get_table_args() {
		$log_id = $this->get( 'id' );
		$rows   = array();

		$headings = array(
			_x( 'Name'       , 'purchase log notification table heading', 'wpsc' ) => 'left',
			_x( 'Price'      , 'purchase log notification table heading', 'wpsc' ) => 'right',
			_x( 'Quantity'   , 'purchase log notification table heading', 'wpsc' ) => 'right',
			_x( 'Item Total' , 'purchase log notification table heading', 'wpsc' ) => 'right',
		);

		foreach( $this->cart_contents as $item ) {
			$cart_item_array = array(
				'purchase_id'  => $log_id,
				'cart_item'    => $item,
				'purchase_log' => $this->get_data()
			);

			do_action( 'wpsc_transaction_result_cart_item', $cart_item_array );
			do_action( 'wpsc_confirm_checkout', $log_id );

			$item_total = $item->quantity * $item->price;
			$item_total = wpsc_currency_display( $item_total , array( 'display_as_html' => false ) );
			$item_price = wpsc_currency_display( $item->price, array( 'display_as_html' => false ) );
			$rows[] = array( $item->name, $item_price, $item->quantity, $item_total );
		}

		return array( 'headings' => $headings, 'rows' => $rows );
	}

	private function create_email_product_list() {
		$table_args = $this->get_table_args();
		$output = wpsc_get_plaintext_table( $table_args['headings'], $table_args['rows'] );

		foreach ( $this->get_cart_contents() as $cart_item ) {
			if ( empty( $cart_item->custom_message ) )
				continue;

			$custom_message_string = apply_filters( 'wpsc_email_product_list_plaintext_custom_message', __( 'Customization for %s', 'wpsc' ) );
			$output .= "\r\n" . '=== ' . sprintf( $custom_message_string, $cart_item->name ) . ' ===' . "\r\n";
			$output .= $cart_item->custom_message;
			$output .= "\r\n";
		}

		$links = wpsc_get_downloadable_links( $this );
		if ( empty( $links ) )
			return $output;

		$output .= '==' . __( 'Downloadable items', 'wpsc' ) . "==\r\n";
		foreach ( $links as $item_name => $item_links ) {
			$output .= $item_name . "\r\n";
			foreach ( $item_links as $link_name => $url ) {
				$output .= '  - ' . $link_name . "\r\n" . '    ' . $url . "\r\n";
			}
			$output .= "\r\n";
		}
		$output .= "\r\n";

		return $output;
	}

	private function create_html_product_list() {
		$table_args = $this->get_table_args();

		$output = wpsc_get_purchase_log_html_table( $table_args['headings'], $table_args['rows'] );

		foreach ( $this->get_cart_contents() as $cart_item ) {
			if ( empty( $cart_item->custom_message ) )
				continue;

			$custom_message_string = apply_filters( 'wpsc_email_product_list_html_custom_message', __( 'Customization for %s', 'wpsc' ) );
			$output .= '<hr />';
			$output .= '<p><strong>' . sprintf( $custom_message_string, esc_html( $cart_item->name ) ) . '</strong></p>';
			$output .= wpautop( esc_html( $cart_item->custom_message ) );
		}

		$links = wpsc_get_downloadable_links( $this );
		if ( empty( $links ) )
			return $output;

		$output .= '<hr /><p><strong>' . esc_html__( 'Downloadable items', 'wpsc' ) . '</strong></p>';
		foreach ( $links as $item_name => $item_links ) {
			$output .= '<p>';
			$output .= '<em>' . esc_html( $item_name ) . '</em><br />';
			foreach ( $item_links as $link_name => $url ) {
				$output .= '<a href="' . esc_attr( $url ) . '">' . esc_html( $link_name ) . '</a><br />';
			}
			$output .= '</p>';
		}

		return $output;
	}

	private function get_notification_args() {
		$data = $this->get_gateway_data();
		$tax      = wpsc_currency_display( $data['tax'     ], array( 'display_as_html' => false ) );
		$shipping = wpsc_currency_display( $data['shipping'], array( 'display_as_html' => false ) );
		$total    = wpsc_currency_display( $data['amount'  ], array( 'display_as_html' => false ) );
		$discount = wpsc_currency_display( $data['discount'], array( 'display_as_html' => false ) );
		$subtotal = wpsc_currency_display( $data['subtotal'], array( 'display_as_html' => false ) );

		$args = array(
			// Legacy tags
			// These tags are dumb because they force the string to go with the amount, giving no
			// control to the user. Unfortunately we still have to support those for the next decade.
			'purchase_id'     => sprintf( __( "Purchase # %s"     , 'wpsc' ), $this->get( 'id' ) ) . "\r\n",
			'total_tax'       => sprintf( __( 'Total Tax: %s'     , 'wpsc' ), $tax               ) . "\r\n",
			'total_shipping'  => sprintf( __( 'Total Shipping: %s', 'wpsc' ), $shipping          ) . "\r\n",
			'total_price'     => sprintf( __( 'Total: %s'         , 'wpsc' ), $total             ) . "\r\n",
			'shop_name'       => get_option( 'blogname' ),
			'find_us'         => $this->get( 'find_us' ),
			'discount'        => sprintf( __( 'Discount Amount: %s (%s)', 'wpsc' ), $discount, $this->get( 'discount_data' ) ) . "\r\n",

			// New tags
			'coupon_code'     => $this->get( 'discount_data'  ),
			'transaction_id'  => $this->get( 'transactid'     ),
			'purchase_log_id' => $this->get( 'id'             ),
			'discount_amount' => $discount,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'total'           => $total,
			'subtotal'        => $subtotal,
		);

		return apply_filters( 'wpsc_purchase_log_notification_args', $args, $this );
	}

	public function get_email_args() {
		$args = $this->get_notification_args();
		$args['product_list'] = $this->create_email_product_list();
		return $args;
	}

	public function get_html_output_args() {
		$args = $this->get_notification_args();
		$args['product_list'] = $this->create_html_product_list();
		return $args;
	}

	public function filter_customer_notification_raw_message( $message, $id ) {
		if ( $id != $this->get( 'id' ) )
			return $message;

		if ( $this->get( 'gateway' ) == 'wpsc_merchant_testmode' )
			$message = get_option( 'payment_instructions', '' ) . "\r\n" . $message;

		// little hack to make sure discount is displayed even when the tag is not in the option value
		// which is a dumb behavior in previous versions
		if ( $this->get( 'discount_data' ) && strpos( $message, '%discount%' ) === false ) {
			$shipping_pos = strpos( $message, '%total_shipping%' );
			if ( $shipping_pos !== false )
				$message = str_replace( '%total_shipping%', '%total_shipping%%discount%', $message );
		}
		return $message;
	}

	public function send_customer_email() {
		add_filter( 'wpsc_purchase_log_customer_notification_raw_message', array( $this, 'filter_customer_notification_raw_message' ), 10, 2 );
		$address = wpsc_get_buyers_email( $this->get( 'id' ) );

		if ( $this->is_transaction_completed() ) {
			$email = new WPSC_Purchase_Log_Customer_Notification( $address, $this->get_email_args() );
			$email->send();
		} elseif ( $this->get( 'processed' ) == self::ORDER_RECEIVED ) {
			$email = new WPSC_Purchase_Log_Customer_Pending_Notification( $address, $this->get_email_args() );
			$email->send();
		}
		remove_filter( 'wpsc_purchase_log_customer_notification_raw_message', array( $this, 'filter_customer_notification_raw_message' ), 10, 2 );
		do_action( 'wpsc_transaction_send_email_to_customer', $this->get( 'id' ) );
	}

	public function get_html_output() {
		add_filter( 'wpsc_purchase_log_customer_html_notification_raw_message', array( $this, 'filter_customer_notification_raw_message' ), 10, 2 );

		$output = '';
		$address = wpsc_get_buyers_email( $this->get( 'id' ) );
		if ( $this->is_transaction_completed() ) {
			$notification = new WPSC_Purchase_Log_Customer_HTML_Notification( $address, $this->get_html_output_args() );
			$output = $notification->get_message();
		} elseif ( $this->get( 'processed' ) == self::ORDER_RECEIVED ) {
			$notification = new WPSC_Purchase_Log_Customer_Pending_HTML_Notification( $address, $this->get_html_output_args() );
			$output = $notification->get_message();
		}
		remove_filter( 'wpsc_purchase_log_customer_notification_raw_message', array( $this, 'filter_customer_notification_raw_message' ), 10, 2 );
		return $output;
	}

	public function send_admin_email() {
		if ( $this->get( 'email_sent' ) )
			return;

		$address = get_option( 'purch_log_email' );
		$email = new WPSC_Purchase_Log_Admin_Notification( $address, $this->get_email_args(), $this->get( 'id' ) );
		$email->send();
		$this->set( 'email_sent', 1 );
		$this->save();
	}
}
