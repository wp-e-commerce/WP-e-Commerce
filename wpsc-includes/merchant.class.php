<?php

/**
 * WP eCommerce Base Merchant Class
 *
 * This is the base merchant class, all merchant files that use the new API extend this class.
 *
 *
 * @package wp-e-commerce
 * @since 3.7.6
 * @abstract
 * @subpackage wpsc-merchants
 * @todo change get_post_meta to get_product_meta
 */
/**
 * A Function to sort through merchant gateways alphabetically
 * @access public
 *
 * @since 3.7.*
 * @param gateway array, gateway array
 * @return name of gateway in alphabetical order
 */
if ( !function_exists( 'wpsc_merchant_sort' ) ) {

	function wpsc_merchant_sort( $a, $b ) {
		return strnatcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
	}

}

/**
 * This is the Merchant Gateway Class that all gateways should extend. It handles everything from collating user data,
 * cart data so all gateways have consistent data between them.
 *
 *
 */
class wpsc_merchant {

	var $name = 'Base Merchant';
	var $is_receiving = false;
	var $purchase_id = null;
	var $session_id = null;
	var $received_data = array( );
	/**
	 * This is where the cart data, like the address, country and email address is held
	 * @var array
	 */
	var $cart_data = array( );
	/**
	 * This is where the cart items are stored
	 * @var array
	 */
	var $cart_items = array( );
	/**
	 * This is where the data to be sent is gathered before being converted to the necessary format and sent.
	 * @var array
	 */
	var $collected_gateway_data = array( );

	/**
	 * collate_data method, collate purchase data, like addresses, like country
	 * @access public
	 */

	protected $address_keys = array (
		'billing' => array (
			'first_name' => 'billingfirstname',
			'last_name'  => 'billinglastname',
			'address'    => 'billingaddress',
			'city'       => 'billingcity',
			'state'      => 'billingstate',
			'country'    => 'billingcountry',
			'post_code'  => 'billingpostcode',
			'phone'      => 'billingphone',
		),
		'shipping' => array (
			'first_name' => 'shippingfirstname',
			'last_name'  => 'shippinglastname',
			'address'    => 'shippingaddress',
			'city'       => 'shippingcity',
			'state'      => 'shippingstate',
			'country'    => 'shippingcountry',
			'post_code'  => 'shippingpostcode',
		)
	);

	function __construct( $purchase_id = null, $is_receiving = false ) {
		global $wpdb;

		if ( ($purchase_id == null) && ($is_receiving == true) ) {
			$this->is_receiving = true;
			$this->parse_gateway_notification();
		}

		if ( $purchase_id > 0 ) {
			$this->purchase_id = $purchase_id;
		}
		$this->collate_data();
		$this->collate_cart();
	}

	function wpsc_merchant( $purchase_id = null, $is_receiving = false ) {
		$this->__construct( $purchase_id, $is_receiving );
	}

	/**
	 * collate_data method, collate purchase data, like addresses, like country
	 * @access public
	 */
	function collate_data() {
		global $wpdb;

		// Get purchase data, regardless of being fed the ID or the sessionid
		if ( $this->purchase_id > 0 ) {
			$purchase_id = & $this->purchase_id;
			$purchase_logs = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `id` = %d LIMIT 1", $purchase_id ), ARRAY_A );
		} else if ( $this->session_id != null ) {
			$purchase_logs = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` = %s LIMIT 1", $this->session_id ), ARRAY_A );
			$this->purchase_id = $purchase_logs['id'];
			$purchase_id = & $this->purchase_id;
		}

		$email_address       = $wpdb->get_var( "SELECT `value` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` AS `form_field` INNER JOIN `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` AS `collected_data` ON `form_field`.`id` = `collected_data`.`form_id` WHERE `form_field`.`type` IN ( 'email' ) AND `collected_data`.`log_id` IN ( '{$purchase_id}' )" );
		$currency_code       = $wpdb->get_var( "SELECT `code` FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`='" . get_option( 'currency_type' ) . "' LIMIT 1" );
		$collected_form_data = $wpdb->get_results( "SELECT `data_names`.`id`, `data_names`.`unique_name`, `collected_data`.`value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` AS `collected_data` JOIN `" . WPSC_TABLE_CHECKOUT_FORMS . "` AS `data_names` ON `collected_data`.`form_id` = `data_names`.`id` WHERE `log_id` = '" . $purchase_id . "'", ARRAY_A );

		$address_data = array(
			'billing'  => array(),
			'shipping' => array()
		);

		foreach ( $collected_form_data as $collected_form_row ) {
			$address_data_set = 'billing';
			$address_key      = array_search( $collected_form_row['unique_name'], $this->address_keys['billing'] );

			if ( $address_key == null ) {
				$address_data_set = 'shipping';
				$address_key      = array_search( $collected_form_row['unique_name'], $this->address_keys['shipping'] );
			}

			if ( $address_key == null )
				continue;

			switch ( $collected_form_row['unique_name'] ) {
				case 'billingcountry':
				case 'shippingcountry':
					$country = maybe_unserialize( $collected_form_row['value'] );

					if ( is_array( $country ) ) {
						$address_data[$address_data_set]['state'] = wpsc_get_state_by_id( $country[1], 'code' );
						$country = $country[0];
					}

					$address_data[$address_data_set][$address_key] = $country;
					break;

				case 'billingstate':
				case 'shippingstate':
					if ( empty( $address_data[$address_data_set]['state'] ) )
						$address_data[$address_data_set]['state'] = is_numeric( $collected_form_row['value'] ) ? wpsc_get_state_by_id( $collected_form_row['value'], 'code' ) : $collected_form_row['value'];
					break;
				default :
					$address_data[$address_data_set][$address_key] = $collected_form_row['value'];
					break;
			}
		}

		if ( count( $address_data['shipping'] ) < 1 )
			$address_data['shipping'] = $address_data['billing'];
		if( !empty($purchase_logs['discount_value']) && $purchase_logs['discount_value'] > 0 )
			$has_discount = true;
		else
			$has_discount = false;

		$this->cart_data = array(
			'software_name'           => 'WP e-Commerce/' . WPSC_PRESENTABLE_VERSION . '',
			'store_location'          => get_option( 'base_country' ),
			'store_currency'          => $currency_code,
			'is_subscription'         => false,
			'has_discounts'           => $has_discount,
			'cart_discount_value'     => $purchase_logs['discount_value'],
			'cart_discount_coupon'    => $purchase_logs['discount_data'],
			'cart_tax'                => $purchase_logs['wpec_taxes_total'],
			'notification_url'        => add_query_arg( 'wpsc_action', 'gateway_notification', home_url( '/' ) ),
			'transaction_results_url' => get_option( 'transact_url' ),
			'shopping_cart_url'       => get_option( 'shopping_cart_url' ),
			'products_page_url'       => get_option( 'product_list_url' ),
			'base_shipping'           => $purchase_logs['base_shipping'],
			'total_price'             => $purchase_logs['totalprice'],
			'session_id'              => $purchase_logs['sessionid'],
			'transaction_id'          => $purchase_logs['transactid'], // Transaction ID might not  be set yet
			'email_address'           => $email_address,
			'billing_address'         => $address_data['billing'],
			'shipping_address'        => $address_data['shipping'],
		);

	}

	/**
	 * collate_cart method, collate cart data
	 * @access public
	 *
	 */
	function collate_cart() {
		global $wpdb;
		$purchase_id = & $this->purchase_id;
		$original_cart_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid` = {$purchase_id}", ARRAY_A );

		foreach ( $original_cart_data as $cart_row ) {
			$is_downloadable = false;

			if ( $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `cartid` = {$cart_row['id']}" ) )
				$is_downloadable = true;

			$is_recurring = (bool)get_post_meta( $cart_row['prodid'], '_wpsc_is_recurring', true );

			if ( $is_recurring == true )
				$this->cart_data['is_subscription'] = true;


			if ( ! $rebill_interval = get_post_meta( $cart_row['prodid'], '_wpsc_rebill_interval', true ) )
				$rebill_interval = array();


			$new_cart_item = array(
				"cart_item_id"         => $cart_row['id'],
				"product_id"           => $cart_row['prodid'],
				"name"                 => $cart_row['name'],
				"price"                => $cart_row['price'],
				"shipping"             => $cart_row['pnp'],
				"tax"                  => $cart_row['tax_charged'],
				"quantity"             => $cart_row['quantity'],
				"is_downloadable"      => $is_downloadable,
				"is_capability"        => (bool) wpsc_get_cart_item_meta( $cart_row['id'], 'provided_capabilities', true ),
				"is_recurring"         => $is_recurring,
				"is_subscription"      => $is_recurring,
				"recurring_data"       => array(
					"rebill_interval"  => array(
						'unit'         => isset( $rebill_interval['unit'] ) ? $rebill_interval['unit'] : null,
						'length'       => isset( $rebill_interval['number'] ) ? $rebill_interval['number'] : null,
					),
					"charge_to_expiry" => (bool)get_post_meta( $cart_row['prodid'], '_wpsc_charge_to_expiry', true ),
					"times_to_rebill"  => get_post_meta( $cart_row['prodid'], '_wpsc_rebill_number', true )
				)
			);

			$this->cart_items[] = $new_cart_item;
		}
	}

	/**
	 * set_error_message, please don't extend this without very good reason
	 * saves error message, data it is stored in may need to change, hence the need to not extend this.
	 */
	function set_error_message( $error_message ) {
		global $wpdb;

		$messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
		if ( ! is_array( $messages ) )
			$messages = array();

		$messages[] = $error_message;
		wpsc_update_customer_meta( 'checkout_misc_error_messages', $messages );
	}

	/**
	 * return_to_checkout, please don't extend this without very good reason
	 * returns to checkout, if this changes and you extend this, your merchant module may go to the wrong place
	 */
	function return_to_checkout() {
		global $wpdb;

		wp_redirect( get_option( 'shopping_cart_url' ) );

		exit(); // follow the redirect with an exit, just to be sure.
	}

	/**
	 * go_to_transaction_results, please don't extend this without very good reason
	 * go to transaction results, if this changes and you extend this, your merchant module may go to the wrong place
	 */
	function go_to_transaction_results( $session_id ) {
		$purchase_log = new WPSC_Purchase_Log( $this->purchase_id );

		//Now to do actions once the payment has been attempted
		switch ( $purchase_log->get( 'processed' ) ) {
			case WPSC_Purchase_Log::ACCEPTED_PAYMENT:
				// payment worked
				do_action('wpsc_payment_successful');
				break;
			case WPSC_Purchase_Log::INCOMPLETE_SALE:
				// payment declined
				do_action('wpsc_payment_failed');
				break;
			case WPSC_Purchase_Log::ORDER_RECEIVED:
				// something happened with the payment
				do_action('wpsc_payment_incomplete');
				break;
		}

		$transaction_url_with_sessionid = add_query_arg( 'sessionid', $session_id, get_option( 'transact_url' ) );
		wp_redirect( $transaction_url_with_sessionid );

		exit(); // follow the redirect with an exit, just to be sure.
	}

	/**
	 * set_purchase_processed_by_purchid, this helps change the purchase log status
	 * $status = integer status order
	 */
	function set_purchase_processed_by_purchid( $status = 1 ) {
		wpsc_update_purchase_log_status( $this->purchase_id, $status );
	}

	/**
	 * set_purchase_processed_by_sessionid, this helps change the purchase log status
	 * $status = integer status order
	 */
	function set_purchase_processed_by_sessionid( $status = 1 ) {
		wpsc_update_purchase_log_status( $this->session_id, $status, 'sessionid' );
	}

	/**
	 * set_transaction_details, maybe extended in merchant files
	 */
	function set_transaction_details( $transaction_id, $status = 1 ) {
		wpsc_update_purchase_log_details( $this->purchase_id, array( 'processed' => $status, 'transactid' => $transaction_id ) );
	}

	/**
	 * set_authcode, generaly speaking a payment gateway gives you an authcode to be able to refer back to the transaction
	 * if an authcode already exsits, you can either append another (2931932839|29391839482) or replace depending on the $append flag
	 * @param string $authcode
	 * @param bool   $append
	 * @return bool  result
	 */
	function set_authcode($authcode, $append = false){
		global $wpdb;

		$wpdb->show_errors();
		if($append === false){
			return $wpdb->update(WPSC_TABLE_PURCHASE_LOGS,array('authcode'=>$authcode), array('id'=>absint($this->purchase_id)),array('%s'), array('%d'));
		}else{
			$current_authcode = $wpdb->get_var( "SELECT authcode FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `sessionid` = " . absint( $this->session_id ) . " LIMIT 1" );
			//this is overwrite
			$new_authcode = isset($current_authcode) ? $current_authcode.'|' :'';
			$new_authcode .= $authcode;
			return $wpdb->update(WPSC_TABLE_PURCHASE_LOGS,array('authcode'=>$new_authcode), array('id'=>absint($this->purchase_id)),array('%s'), array('%d'));
		}
	}

	/**
	 * construct_value_array gateway specific data array, extended in merchant files
	 * @abstract
	 * @todo When we drop support for PHP 4, make this a proper abstract method
	 */
	function construct_value_array() {
		return false;
	}

	/**
	 * submit to gateway, extended in merchant files
	 * @abstract
	 * @todo When we drop support for PHP 4, make this a proper abstract method
	 */
	function submit() {
		return false;
	}

	/**
	 * parse gateway notification, recieves and converts the notification to an array, if possible, extended in merchant files
	 * @abstract
	 * @todo When we drop support for PHP 4, make this a proper abstract method
	 */
	function parse_gateway_notification() {
		return false;
	}

	/**
	 * process gateway notification, checks and decides what to do with the data from the gateway, extended in merchant files
	 * @abstract
	 * @todo When we drop support for PHP 4, make this a proper abstract method
	 */
	function process_gateway_notification() {
		return false;
	}
}

?>
