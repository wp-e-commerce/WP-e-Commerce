<?php

/**
 * Handles our current Shipwire integration.
 *
 * Integrates with Order Fulfillment API, Inventory Sync API, Shipping Quotes API and Tracking API.  Order Fullfillment is hooked into the ordering process.
 * Inventory and Tracking are handled through a manual 'Update Tracking and Inventory' click in Settings area.
 *
 * Shipping is done via a custom shipping module.  Upon Shipwire activation, we disable all others and enable this.
 *
 * @todo Determine demand for further automation of Inventory/Tracking APIs.  Easy enough to hook in to cron.
 * @todo Determine if there is any performance, elegance or feature-set gain to be made in converting the get_*_xml methods to DOMDocument or SimpleXML
 * @todo If and when purchase logs are moved to CPT, DO NOT FORGET TO REFACTOR!
 * @todo If and when checkout forms are refactored to not utilize unique_names - DO NOT FORGET TO REFACTOR!
 * @todo Use WPSC_Purchase_Log class where appropriate.  I should imagine this will mitigate the point of CPT refactoring, as that will be handled at that level.
 * @package wp-e-commerce
 * @subpackage WPSC_Shipwire
 * @since 3.8.9
 */

class WPSC_Shipwire {

	private static $instance;
	private static $email;
	private static $passwd;
	private static $server;
	private static $warehouse;
	private static $endpoint;

	/**
	 * Playing nicely.
	 * @since 3.8.9
	 * @return type
	 */
	public static function get_instance() {

		if ( empty( self::$instance ) )
			self::$instance = new WPSC_Shipwire();

		if ( self::is_active() )
			return self::$instance;

		return false;
	}

	/**
	 * Sets up properties, sends Order via API on checkout success.  Initial hook for AJAX link in admin
	 *
	 * @since 3.8.9
	 * @return type
	 */
	private function __construct() {

		self::$email     = get_option( 'shipwireemail' );
		self::$passwd    = get_option( 'shipwirepassword' );
		self::$server    = apply_filters( 'wpsc_shipwire_server'   , 'Production' );
		self::$warehouse = apply_filters( 'wpsc_shipwire_warehouse', '00' );
		self::$endpoint  = ( bool ) get_option( 'shipwire_test_server' ) ? 'https://api.beta.shipwire.com/exec/' : 'https://api.shipwire.com/exec/';

		if ( ! self::is_active() )
			return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			self::set_posted_properties();

		add_action( 'wpsc_transaction_results_shutdown', array( $this, 'shipwire_on_checkout' ), 10, 3 );

		//Hooks into ajax handler for Inventory Sync and Tracking API.  Handler is run upon clicking "Update Tracking and Inventory" in Shipping Settings
		add_action( 'wp_ajax_sync_shipwire_products', array( $this, 'sync_products' ) );

	}

	private static function set_posted_properties() {
		if ( isset( $_POST['email'] ) )
			self::$email = $_POST['email'];

		if ( isset( $_POST['password'] ) )
			self::$passwd = $_POST['password'];

		if ( isset( $_POST['server'] ) ) {
			self::$endpoint = ( bool ) $_POST['server'] ? 'https://api.beta.shipwire.com/exec/' : 'https://api.shipwire.com/exec/';
		}

	}

	/**
	 * Checks if Shipwire option is set and SimpleXML is present.  We'll use SimpleXML to parse responses from the server.
	 *
	 * @since 3.8.9
	 * @return boolean
	 */
	public static function is_active() {
		return ( (bool) get_option( 'shipwire' ) && function_exists( 'simplexml_load_string' ) ) || isset( $_POST['server'] );
	}

	/**
	 * Builds XML API request for Order API
	 *
	 * @todo Use WPSC_Purchase_Log class instead of direct queries.
	 * @uses apply_filters() Switch for 'wpsc_shipwire_show_declared_value' send declared value to Shipwire.  Defaults to true
	 * @uses apply_filters() Switch for 'wpsc_shipwire_show_affiliate' to send referring affiliate to Shipwire. Defaults to false
	 * @uses apply_filters() Switch for 'wpsc_shipwire_show_dimensions' to send dimensions to Shipwire. Defaults to false
	 * @uses apply_filters() 'get_order_xml' filters final XML
	 *
	 * @param int $log_id
	 * @since 3.8.9
	 * @return string $xml
	 */
	public static function get_order_xml( $log_id ) {
		global $wpdb;

		$form_info = $wpdb->get_results( 'SELECT * FROM ' . WPSC_TABLE_CHECKOUT_FORMS, ARRAY_A );

		$form_ids = array();

		//Sets up array of form IDs to compare against customer data
		foreach ( $form_info as $info ) {
			if ( '1' == $info['active'] )
				$form_ids[$info['unique_name']] = $info['id'];
		}

		//Extracts unique name variables for comparison.
		extract( $form_ids );

		$customer_data = $wpdb->get_results( $wpdb->prepare( 'SELECT form_id, value FROM ' . WPSC_TABLE_SUBMITTED_FORM_DATA . ' WHERE log_id = %d', $log_id ) );

		foreach ( $customer_data as $data ) {

			if ( $data->form_id == $shippingfirstname )
				$first_name = $data->value;

			if ( $data->form_id == $shippinglastname )
				$last_name = $data->value;

			if ( $data->form_id == $shippingaddress )
				$address = $data->value;

			if ( $data->form_id == $shippingcity )
				$city = $data->value;

			if ( $data->form_id == $shippingstate )
				$state = wpsc_get_state_by_id( $data->value, 'code' );

			if ( $data->form_id == $shippingpostcode )
				$zip = $data->value;

			if ( $data->form_id == $shippingcountry )
				$country = $data->value;

			if ( $data->form_id == $shippingcountry )
				$country = $data->value;

			if ( $data->form_id == $billingphone )
				$phone = $data->value;

			if ( $data->form_id == $billingemail )
				$email = $data->value;

		}

		$full_name = $first_name . ' ' . $last_name;

		$products = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . WPSC_TABLE_CART_CONTENTS . ' WHERE purchaseid = %d', $log_id ) );

		$shipping = self::get_shipping( $log_id );

		$xml = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<OrderList>';
		$xml .= '<EmailAddress>' . self::$email . '</EmailAddress>';
		$xml .= '<Password>' . self::$passwd . '</Password>';
		$xml .= '<Server>' . self::$server . '</Server>';

		$referrer = apply_filters( 'wpsc_shipwire_show_affiliate', false );

		if ( $referrer )
			$xml .= '<Referer>' . $referrer . '</Referer>';

		$xml .= '<Warehouse>' . self::$warehouse . '</Warehouse>';
		$xml .= '<Order id="' . $log_id . '">';
		$xml .= '<AddressInfo type="ship">';
		$xml .= '<Name>';
		$xml .= '<Full>' . $full_name . '</Full>';
		$xml .= '</Name>';
		$xml .= '<Address1>' . $address . '</Address1>';
		$xml .= '<City>' . $city . '</City>';
		$xml .= '<State>' . $state . '</State>';
		$xml .= '<Country>' . $country . '</Country>';
		$xml .= '<Zip>' . $zip . '</Zip>';
		$xml .= '<Phone>' . $phone . '</Phone>';
		$xml .= '<Email>' . $email . '</Email>';
		$xml .= '</AddressInfo>';
		$xml .= '<Shipping>' . $shipping . '</Shipping>';

		$num = 0;

		$show_declared_value = apply_filters( 'wpsc_shipwire_show_declared_value', true );
		$show_dimensions     = apply_filters( 'wpsc_shipwire_show_dimensions', false );

		foreach ( $products as $product ) {

			if ( $product->no_shipping )
				continue;

			$xml .= '<Item num="' . $num . '">';
			$xml .='<Code>' . get_post_meta( $product->prodid, '_wpsc_sku', true ) . '</Code>';
			$xml .= '<Quantity>' . $product->quantity . '</Quantity>';

			if ( $show_dimensions ) {
				$dimensions = self::get_dimensions( $product->prodid );
				$xml .= '<Length>' . $dimensions['length'] . '</Length>';
				$xml .= '<Width>' . $dimensions['width'] . '</Width>';
				$xml .= '<Height>' . $dimensions['height'] . '</Height>';
				$xml .= '<Weight>' . $dimensions['weight'] . '</Weight>';
			}

			if ( $show_declared_value )
				$xml .= '<DeclaredValue>' . $product->price . '</DeclaredValue>';

			$xml .= '</Item>';

			$num++;

		}

		$xml .='</Order>';
		$xml .='</OrderList>';

		return apply_filters( 'get_order_xml', $xml, $log_id );
	}

	/**
	 * Returns shipping string for order XML file.
	 *
	 * @param int $log_id
	 * @since 3.8.9
	 * @return string Shipwire-ready shipping code
	 */
	public static function get_shipping( $log_id ) {
		global $wpdb;

		$shipping_option = $wpdb->get_var( $wpdb->prepare( "SELECT shipping_option FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE id = %d", $log_id ) );

		return convert_service_to_code( $shipping_option );

	}

	/**
	 * Shipwire requires dimensions to be in inches and pounds.  This handles the conversion process, if one is required.
	 * @param int $product_id
	 * @since 3.8.9
	 * @return array $dimensions
	 */
	public static function get_dimensions( $product_id ) {

		$product_meta = get_post_meta( $product_id, '_wpsc_product_metadata', true );
		$original_dimensions = $product_meta['dimensions'];

		$dimensions = array();

		$dimensions['weight'] = ( 'pound' == $original_dimensions['weight_unit'] ) ? $original_dimensions['weight'] : wpsc_convert_weight( $original_dimensions['weight'], $original_dimensions['weight_unit'] );
		$dimensions['length'] = ( 'in' == $original_dimensions['length_unit'] ) ? $original_dimensions['length'] : $this->convert_dimensions( $original_dimensions['length'], $original_dimensions['length_unit'] );
		$dimensions['width']  = ( 'in' == $original_dimensions['width_unit'] ) ? $original_dimensions['width'] : $this->convert_dimensions( $original_dimensions['width'], $original_dimensions['width_unit'] );
		$dimensions['height'] = ( 'in' == $original_dimensions['height_unit'] ) ? $original_dimensions['height'] : $this->convert_dimensions( $original_dimensions['height'], $original_dimensions['height_unit'] );

		return $dimensions;
	}

	/**
	 * The wpsc_convert_weight function essentially converts weights to grams (based on input parameters).  Then converts grams to output unit.
	 *
	 * We do the same in this method, but for dimensions rather than weight, using centimeters as the base.  This is used if dimensions are sent to Shipwire
	 *
	 * @param float $measurement_in
	 * @param string $unit_in
	 * @param string $unit_out
	 * @param boolean $raw
	 * @since 3.8.9
	 * @return float $dimension
	 */
	public static function convert_dimensions( $measurement_in, $unit_in, $unit_out = 'inch', $raw = false ) {

		switch ( $unit_in ) {
			case 'meter':
				$intermediate_dimension = $measurement_in / 100;
			break;

			case 'cm':
				$intermediate_dimension = $measurement_in;
			break;

			case 'in':
			default:
				$intermediate_dimension = $measurement_in * 2.54;
			break;
		}

		switch ( $unit_out ) {
			case 'meter':
				$dimension = $intermediate_dimension * 100;
			break;

			case 'cm':
				$dimension = $intermediate_dimension;
			break;

			case 'inch':
			default:
				$dimension = $intermediate_dimension / 2.54;
			break;
		}

		if( $raw )
			return $dimension;

		return round( $dimension, 2 );
	}

	/**
	 * Sends API request for Order API
	 * @param string $xml
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function send_order_request( $xml ) {
		return self::_api_request_handler( 'FulfillmentServices.php', 'OrderListXML', $xml );
	}

	/**
	 * Hooks into to checkout process. Sends order to shipwire on successful checkout
	 * @param type $object
	 * @param type $sessionid
	 * @param type $display
	 * @since 3.8.9
	 * @return type
	 */
	public function shipwire_on_checkout( $purchase_log_object, $sessionid, $display ) {
		global $wpdb;
		self::process_order_request( $purchase_log_object->get( 'id' ) );
	}

	/**
	 * Processes Order Request
	 *
	 * Grabs XML via self::get_order_xml( $log_id ).  Sends through to Shipwire via self::send_order_request( $xml ).
	 *
	 * @param int $log_id
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function process_order_request( $log_id ) {

		$order_info = self::get_order_xml( $log_id );

		return self::send_order_request( $order_info );
	}

	/**
	 * Builds XML API request for Inventory API
	 *
	 * @uses apply_filters() Ability to query inventory from specific warehouse on 'wpsc_shipwire_inventory_warehouse'
	 * @uses apply_filters() 'get_inventory_xml' filters final XML
	 * @since 3.8.9
	 * @return string $xml
	 */
	public static function get_inventory_xml( $product_code = '' ) {

		$xml  = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<InventoryUpdate>';
		$xml .= '<EmailAddress>' . self::$email . '</EmailAddress>';
		$xml .= '<Password>' . self::$passwd . '</Password>';
		$xml .= '<Server>' . self::$server . '</Server>';

		if ( false !== ( $warehouse = apply_filters( 'wpsc_shipwire_inventory_warehouse', false ) ) )
			$xml .= '<Warehouse>' . $warehouse . '</Warehouse>';

		$xml .= '<ProductCode>' . $product_code . '</ProductCode>';
		$xml .= '</InventoryUpdate>';

		return apply_filters( 'get_inventory_xml', $xml );
	}

	/**
	 * Sends API request for Inventory API
	 * @param string $xml
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function send_inventory_request( $xml ) {
		return self::_api_request_handler( 'InventoryServices.php', 'InventoryUpdateXML', $xml );
	}

	/**
	 * Builds XML API request for Tracking API
	 *
	 * @uses apply_filters() 'wpsc_shipwire_tracking_bookmark' filters tracking bookmark
	 * @uses apply_filters() 'get_tracking_xml' filters final XML
	 * @since 3.8.9
	 * @return string $xml
	 */
	public static function get_tracking_xml() {

		$xml  = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<TrackingUpdate>';
		$xml .= '<EmailAddress>' . self::$email . '</EmailAddress>';
		$xml .= '<Password>' . self::$passwd . '</Password>';
		$xml .= '<Server>' . self::$server . '</Server>';
		$xml .= '<Bookmark>' . apply_filters( 'wpsc_shipwire_tracking_bookmark', '1' ) .'</Bookmark>';
		$xml .= '</TrackingUpdate>';

		return apply_filters( 'get_tracking_xml', $xml );
	}

	/**
	 * Sends API request for Tracking API
	 * @param string $xml
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function send_tracking_request( $xml ) {
		return self::_api_request_handler( 'TrackingServices.php', 'TrackingUpdateXML', $xml );
	}

	/**
	 * Builds XML API request for Shipping Rates API
	 * 	 *
	 * @uses apply_filters - filters XML on return
	 * @todo Get ZIP as transient when #437 is complete
	 * @since 3.8.9
	 * @return string $xml
	 */
	public static function get_shipping_xml() {

		global $wpsc_cart;

		$zip      = wpsc_get_customer_meta( 'shipping_zip' );
		$state    = wpsc_get_state_by_id( $wpsc_cart->delivery_region, 'code' );
		$country  = $wpsc_cart->delivery_country;
		$products = $wpsc_cart->cart_items;

		$products_xml = '';
		$num = 0;

		if ( count ( $products ) ) {

			foreach ( $products as $product ) {

				if ( ! $product->uses_shipping )
					continue;

				$products_xml .= '<Item num="' . $num . '">';
				$products_xml .= '<Code>' . wpsc_esc_xml( $product->sku ) . '</Code>';
				$products_xml .= '<Quantity>' . wpsc_esc_xml( $product->quantity ) . '</Quantity>';
				$products_xml .= '</Item>';
				$num++;
			}

		}

		if ( empty( $products_xml ) )
			return false;

	 	$xml  = '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<RateRequest>';
		$xml .= '<Username>' . wpsc_esc_xml( self::$email ) . '</Username>';
		$xml .= '<Password>' . wpsc_esc_xml( self::$passwd ) . '</Password>';
		$xml .= '<Order>';
		$xml .= '<AddressInfo type="ship">';
		$xml .= '<State>' . wpsc_esc_xml( $state ) . '</State>';
		$xml .= '<Country>' . wpsc_esc_xml( $country ) . '</Country>';
		$xml .= '<Zip>' . wpsc_esc_xml( $zip ) . '</Zip>';
		$xml .= '</AddressInfo>';
		$xml .= $products_xml;
		$xml .='</Order>';
		$xml .= '</RateRequest>';

		return apply_filters( 'get_shipping_xml', $xml );
	}

	/**
	 * Sends API request for Shipping Rates API
	 *
	 * @param string $xml
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function send_shipping_request( $xml ) {

		return self::_api_request_handler( 'RateServices.php', 'RateRequestXML', $xml );
	}

	/**
	 * Creates cache key for current cart and ZIP code for shipping rates.
	 * @since 3.8.9
	 * @return string
	 */
	public function get_cache_key() {
		global $wpsc_cart;

		if ( ! is_object( $wpsc_cart ) || empty( $wpsc_cart->cart_items ) )
			return false;

		$cached_object = array();
		$products      = $wpsc_cart->cart_items;
		$zip           = wpsc_get_customer_meta( 'shipping_zip' );

		$num = 0;

		foreach ( $products as $product ) {
			if ( ! $product->uses_shipping )
				continue;

			$cached_object['products'][$num]['sku'] = $product->sku;
			$cached_object['products'][$num]['qty'] = $product->quantity;

			$num++;
		}

		$cached_object['zip'] = $zip;

		return 'rates_' . hash( 'md5', json_encode( $cached_object ) );
	}

	/**
	 * Returns XML Response from Shipwire API for Shipping Quotes
	 *
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, false on no shipping, XML response on success
	 */
	public static function get_shipping_quotes() {

		//Returns false if no products in cart
		if ( ! self::get_shipping_xml() )
			return false;

		$cache_key = self::get_cache_key();

		//Returns live shipping request if no cached response exists, cached response if one does
		if ( false === ( $rates = get_transient( $cache_key ) ) )
			$rates = self::fetch_fresh_quotes();

		return $rates;

	}

	/**
	 * WordPress has some notable deficiencies when storing object data in the database.
	 * It's generally inadvisable anyways - so we convert the shipping quote object into a simple array.
	 * After that, we store the array as the quote in a transient.  Transient is stored for one hour - the expiration is filterable
	 *
	 * @uses simplexml_load_string()
	 * @uses apply_filters() 'wpsc_shipwire_methods' filters the methods returns - the $methods array and $quotes object are both passed to the filter
	 * @uses apply_filters() 'wpsc_shipwire_rates_cache_expiration' filters the expiration for the transient, defaults to one hour
	 * @since 3.8.9
	 * @return array
	 */
	public static function fetch_fresh_quotes() {
		$quotes = self::send_shipping_request( self::get_shipping_xml() );

		$quotes  = $quotes ? simplexml_load_string( $quotes ) : false;
		$methods = array();

		$quotes = is_object( $quotes ) && isset( $quotes->Order )  ? $quotes->Order  : $methods;
		$quotes = is_object( $quotes ) && isset( $quotes->Quotes ) ? $quotes->Quotes : $methods;
		$quotes = is_object( $quotes ) && isset( $quotes->Quote )  ? $quotes->Quote  : $methods;

		if ( ! is_object( $quotes ) )
			return $methods;

		foreach ( $quotes as $quote ) {

			$service = (string) $quote['method'];
			$service = convert_code_to_service( $service );
			$cost = (string) $quote->Cost;

			$methods[$service] = $cost;
		}

		$methods = apply_filters( 'wpsc_shipwire_methods', $methods, $quotes );

		set_transient( self::get_cache_key(), $methods, apply_filters( 'wpsc_shipwire_rates_cache_expiration', 60 * 60 ) );

		return $methods;
	}

	/**
	 * AJAX Handler for sync products link in shipping admin
	 *
	 * Pings Shipwire server to get real-time inventory and tracking information for products
	 * Processes results by updating inventory on-site for each product
	 * Updates tracking numbers for each purchase log with one of the numbers presented (sometimes multiples are presented).
	 * We need to figure out a good UX for multiple tracking numbers. Could potentially update the notes, but that feels janky.
	 * Also emails customer with tracking ID.  Email attempts to work out multiple tracking numbers
	 *
	 * @uses do_action() Calls 'wpsc_shipwire_pre_sync' on the $tracking and $inventory variables before database interaction
	 * @uses do_action() Calls 'wpsc_shipwire_post_sync' on the $tracking and $inventory variables after database interaction
	 * @uses apply_filters() Calls 'wpsc_shipwire_send_tracking_email' on the $order_id and $tracking_numbers arrays - a bool switch for sending the tracking email
	 * @global $wpdb
	 * @todo Use WPSC_Purchase_Log class to update tracking information
	 * @since 3.8.9
	 * @return json Number of rows updated by each method
	 */
	public static function sync_products( $product_code = '' ) {
		global $wpdb;

		if ( defined ( 'DOING_AJAX' ) && DOING_AJAX ) {

			self::set_posted_properties();
			if ( ! _wpsc_ajax_verify_nonce( 'shipping_module_settings_form' ) ) {
				die( __( 'Session expired. Try refreshing your Shipping Settings page.', 'wpsc' ) );
			}

			// A bit tricky here - as we'd like this method available for all processes, not just AJAX, we have the product_code variable.
			// That variable will be set to the $_REQUEST['action'] from the AJAX handler.  Resetting the $product_code to empty fixes the issue.
			// There may certainly be better ways to do this.
			$product_code = '';

		}

		$product_code  = isset( $_POST['product_code'] ) ? $_POST['product_code'] : $product_code;
		$tracking      = self::get_tracking_info();
		$inventory     = self::get_inventory_info( $product_code );

		do_action( 'wpsc_shipwire_pre_sync', $tracking, $inventory );

		$tracking_updates = 0;

		foreach ( $tracking as $order_id => $tracking_number ) {
			$tracking_numbers  = array_keys( $tracking_number );
			$update = (int) $wpdb->update(
					WPSC_TABLE_PURCHASE_LOGS,
					array(
						'track_id' => $tracking_numbers[0]
					),
					array(
						'id'       => $order_id
					),
					'%s',
					'%d'
				);

			$tracking_updates += $update;

			if ( apply_filters( 'wpsc_shipwire_send_tracking_email', true, $order_id, $tracking_number ) && $update )
				self::_send_tracking_email( $order_id, $tracking_number );
		}

		$inventory_updates = 0;
		$product_ids = array();
		$queries = array();

		foreach ( $inventory as $sku => $qty ) {
			$sql                = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpsc_sku' AND meta_value = %s", $sku );
			$queries[]          = $sql;
			$synced_product_ids = $wpdb->get_col( $sql );
			foreach ( $synced_product_ids as $product_id ) {
				$product = get_post( $product_id );
				if ( ! $product->post_status == 'publish' )
					continue;
				$product_ids[]      = $product_id;
				$inventory_updates += (int) update_post_meta( $product_id, '_wpsc_stock', $qty );
			}
		}

		do_action( 'wpsc_shipwire_post_sync', $tracking, $inventory );

		$sync_response = array(
							'tracking'  => sprintf( _n( 'Shipwire updated %d tracking number.', 'Shipwire updated %d tracking numbers.', $tracking_updates, 'wpsc' ), $tracking_updates ),
							'inventory' => sprintf( _n( 'Shipwire updated inventory on %d product.', 'Shipwire updated inventory on %d products.', $inventory_updates, 'wpsc' ), $inventory_updates ),
						);

		if ( defined ( 'DOING_AJAX' ) && DOING_AJAX )
			die( json_encode( $sync_response ) );

		return $sync_response;

	}

	/**
	 * Essentially copies functionality from wpsc_purchase_log_send_tracking_email().
	 * We should consider making "AJAX" functions like that process-agnostic.  Would be great to be able to utilize it from here.
	 * A simple DOING_AJAX check for the nonces and die() and adding a parameter to the function to check before the $_POST would suffice.
	 * Making private, primarily because I'd prefer this not to be used, even internally, pending AJAX refactor as suggested
	 *
	 * @access private
	 * @global $wpdb
	 * @param int $order_id
	 * @param mixed $tracking_numbers Expects the $tracking_number object from self::get_tracking_info()
	 * @todo Use new Notification class from Issue 490 when that is implemented
	 * @since 3.8.9
	 * @return bool Whether or not the email was sent successfully
	 */
	public static function _send_tracking_email( $order_id, $tracking_number = '' ) {
		global $wpdb;

		$id = absint( $order_id );

		$tracking_numbers = array();

		foreach ( $tracking_number as $tn => $array ) {
			$tracking_numbers[] = '<a href="' .  esc_url( $array['link'] ) . '">' . esc_html( $tn ) . '</a>';
		}

		$tracking_numbers = implode( '<br />', $tracking_numbers );
		$site_name        = get_option( 'blogname' );

		$message = nl2br( get_option( 'wpsc_trackingid_message' ) );
		$message = str_replace( '%trackid%', $tracking_numbers, $message );
		$message = str_replace( '%shop_name%', $site_name, $message );

		$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );
		$email            = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = %d LIMIT 1", $id, $email_form_field ) );

		$subject = get_option( 'wpsc_trackingid_subject' );
		$subject = str_replace( '%shop_name%', $site_name, $subject );

		add_filter( 'wp_mail_from',         'wpsc_replace_reply_address', 0 );
		add_filter( 'wp_mail_from_name',    'wpsc_replace_reply_name', 0 );
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'tracking_email_html' ) );

		$send = wp_mail( $email, $subject, $message );

		remove_filter( 'wp_mail_from',         'wpsc_replace_reply_address', 0 );
		remove_filter( 'wp_mail_from_name',    'wpsc_replace_reply_name', 0 );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'tracking_email_html' ) );

		return $send;

	}

	/**
	 * Changes content type of email to HTML for sending tracking email
	 * @since 3.8.9
	 * @return string
	 */
	public static function tracking_email_html() {
		return "text/html";
	}

	/**
	 * Gathers tracking info from Shipwire API.  Called primarily from sync product link.
	 *
	 * Some interesting stuff happening here - Shipwire can send multiple tracking numbers per order, so we add them to an array, along with other potentially helpful information for plugins to use
	 * The multiple tracking numbers does introduce a reality we should address - we need to be able to support multiple numbers in our UX.
	 *
	 * @since 3.8.9
	 * @return array $orders
	 */
	public static function get_tracking_info() {

		$tracking = simplexml_load_string( self::send_tracking_request( self::get_tracking_xml() ) );

		$orders = array();

		foreach ( $tracking->children() as $key => $order ) {
			if ( 'Order' != $key )
				continue;

			if ( ! empty ( $order->TrackingNumber ) ) {
				$id = absint( $order['id'] );
				$tn = (string) $order->TrackingNumber;

				$orders[$id][$tn]['link']                   = (string) $order['href'];
				$orders[$id][$tn]['expected_delivery_date'] = (string) $order['expectedDeliveryDate'];
				$orders[$id][$tn]['ship_date']              = (string) $order['shipDate'];
			}
		}

		return $orders;
	}

	/**
	 * Gets updated inventory information from Shipwire.  Returns array of name-value pairs where the name is the SKU, value is quantity
	 *
	 * @param string $product_code
	 * @since 3.8.9
	 * @return array $products
	 */
	public static function get_inventory_info( $product_code = '' ) {

		$inventory = simplexml_load_string( self::send_inventory_request( self::get_inventory_xml( $product_code ) ) );

		$products = array();

		foreach ( $inventory->children() as $key => $product ) {
			if ( 'Product' != $key )
				continue;

				$qty  = absint( $product['good'] );
				$code = (string) $product['code'] ;

				$products[$code] = $qty;
			}

		return $products;
	}

	/**
	 * API Request Handler.
	 *
	 * Sets content type to urlencoded form, sslverify to false (due to SSL cert issues on many setups) and timeout to 30 (more than sufficient for the most laborious API, Shipping Rates)
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $body
	 * @since 3.8.9
	 * @return mixed - false on WP_Error, XML response on success
	 */
	public static function _api_request_handler( $url, $method, $body ) {

		$url = esc_url_raw( self::$endpoint . $url );

		$args = array(
				'body'      => array( $method => trim( $body ) ),
				'headers'   => array(
								'accept'       => 'application/xml',
								'content-type' => 'application/x-www-form-urlencoded'
							),
				'sslverify' => false,
				'timeout'   => 30
			);

		$request = wp_remote_post( $url, $args );

		if ( ! is_wp_error( $request ) )
			return $request['body'];

		return false;

	}
}

add_action( 'init', array( 'WPSC_Shipwire', 'get_instance' ) );

/**
 * Handy little XML escaping function.  Used primarily in shipping rate XML request.
 *
 * @param string $value
 * @since 3.8.9
 * @return string
 */
function wpsc_esc_xml( $value ) {
	return '<![CDATA[' . esc_html( $value ) . ']]>';
}

/**
 * Helper function for getting services to the front-end from codes.  Actual carriers are irrelevant, as that can change based on cost, availability, etc.
 * @param string $service
 * @since 3.8.9
 * @return string
 */
function convert_code_to_service( $service ) {

	switch ( $service ) :
		case 'GD' :
			$service = _x( 'Ground', 'shipwire shipping method', 'wpsc' );
			break;
		case '1D' :
			$service = _x( 'One-Day Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case '2D' :
			$service = _x( 'Two-Day Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case 'INTL' :
			$service = _x( 'Standard Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case 'FT' :
			$service = _x( 'Freight Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case 'E-INTL' :
			$service = _x( 'Economy Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case 'PL-INTL' :
			$service = _x( 'Plus Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		case 'PM-INTL' :
			$service = _x( 'Premium Shipping', 'shipwire shipping method', 'wpsc' );
			break;
		endswitch;

	return $service;
}

/**
 * Helper function for getting codes to the API from services.  Actual carriers are irrelevant, as that can change based on cost, availability, etc.
 * @param string $service
 * @since 3.8.9
 * @return string
 */
function convert_service_to_code( $service ) {

	switch ( $service ) :
		case _x( 'Ground', 'shipwire shipping method', 'wpsc' ) :
			$service = 'GD';
			break;
		case _x( 'One-Day Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = '1D';
			break;
		case _x( 'Two-Day Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = '2D';
			break;
		case _x( 'Standard Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = 'INTL';
			break;
		case _x( 'Freight Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = 'FT';
			break;
		case _x( 'Economy Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = 'E-INTL';
			break;
		case _x( 'Plus Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = 'PL-INTL';
			break;
		case _x( 'Premium Shipping', 'shipwire shipping method', 'wpsc' ) :
			$service = 'PM-INTL';
			break;
		endswitch;

	return $service;
}