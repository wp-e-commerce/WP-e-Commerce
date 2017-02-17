<?php

/**
 * Tracking functions for reporting plugin usage to the WPEC site for users that have opted in
 *
 * @package     WPEC
 * @subpackage  Admin
 * @copyright   Copyright (c) 2017
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.12.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Usage tracking
 *
 * @access public
 * @since  3.12.0
 * @return void
 */
class WPSC_Tracking {

	/**
	 * The data to send to the WPEC site
	 *
	 * @since 3.12.0
	 * @access private
	 */
	private $data;

	/**
	 * Where we are sending the data to
	 *
	 * @since 3.12.0
	 * @access private
	 */
	private $api_url = 'https://wpecommerce.org/';

	/**
	 * Get things going
	 *
	 * @since 3.12.0
	 * @access public
	 */
	public function __construct() {
		add_action( 'admin_init'                     , array( $this, 'capture_tracking_settings' ) );
		add_action( 'wpsc_opt_into_tracking'         , array( $this, 'check_for_optin' ) );
		add_action( 'wpsc_opt_out_of_tracking'       , array( $this, 'check_for_optout' ) );
		add_action( 'wpsc_settings_page_save_options', array( $this, 'check_for_settings_optin' ), 10, 2 );
		add_action( 'admin_notices'                  , array( $this, 'admin_notice' ) );
	}

	/**
	 * Schedule a weekly checkin
	 *
	 * @since 3.12.0
	 * @access public
	 * @return void
	 */
	public function capture_tracking_settings() {

		if ( isset( $_REQUEST['wpsc_tracking_action'] ) && ( $_REQUEST['wpsc_tracking_action'] == 'opt_into_tracking' ) ) {
			do_action( 'wpsc_opt_into_tracking' );
		}

		if ( isset( $_REQUEST['wpsc_tracking_action'] ) && ( $_REQUEST['wpsc_tracking_action'] == 'opt_out_of_tracking' ) ) {
			do_action( 'wpsc_opt_out_of_tracking' );
		}
	}

	/**
	 * Check if the user has opted into tracking
	 *
	 * @since 3.12.0
	 * @access private
	 * @return bool
	 */
	private function tracking_allowed() {
		return (bool) get_option( 'wpsc_usage_tracking', false );
	}

	/**
	 * Get the last time a checkin was sent
	 *
	 * @since 3.12.0
	 * @access private
	 * @return false|string
	 */
	private function get_last_send() {
		return get_option( 'wpsc_usage_tracking_last_send' );
	}

	/**
	 * Send the data to the WPEC server
	 *
	 * @since 3.12.0
	 * @access private
	 * @return void
	 */
	public function send_data( $override = false ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$home_url = trailingslashit( home_url() );

		// Allows us to stop our own site from checking in, and a filter for our additional sites
		if ( true === apply_filters( 'wpsc_disable_tracking_checkin', false ) ) {
			return false;
		}

		if ( ! $this->tracking_allowed() && ! $override ) {
			return false;
		}

		// Send a maximum of once per week
		$last_send = $this->get_last_send();

		if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) ) {
			return false;
		}

		$this->setup_data();

		$request = wp_safe_remote_post( $this->api_url . '?wpsc_tracking_action=checkin', array(
			'timeout'     => 20,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'body'        => $this->data,
			'user-agent'  => 'WPEC/' . WPSC_VERSION . '; ' . get_bloginfo( 'url' )
		) );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		update_option( 'wpsc_usage_tracking_last_send', time() );

		return true;
	}

	/**
	 * Check for a new opt-in via the admin notice
	 *
	 * @since 3.12.0
	 * @access public
	 * @return void
	 */
	public function check_for_optin() {

		update_option( 'wpsc_usage_tracking', '1' );

		$this->send_data( true );

		update_option( 'wpsc_usage_tracking_notice', '1' );

		wp_safe_redirect( esc_url_raw( remove_query_arg( 'wpsc_tracking_action' ) ) );
		exit;
	}

	/**
	 * Check for opt-out via the admin notice
	 *
	 * @since 3.12.0
	 * @access public
	 * @return void
	 */
	public function check_for_optout() {

		$allowed = get_option( 'wpsc_usage_tracking', false );

		if ( $allowed ) {
			delete_option( 'wpsc_usage_tracking' );
		}

		update_option( 'wpsc_usage_tracking_notice', '1' );

		wp_safe_redirect( esc_url_raw( remove_query_arg( 'wpsc_tracking_action' ) ) );
		exit;
	}

	/**
	 * Check for opt-in via the settings page
	 *
	 * @since 3.12.0
	 * @access public
	 * @return void
	 */
	public function check_for_settings_optin( $option, $value ) {
		if( isset( $option ) && $option == 'wpsc_usage_tracking' && $value == '1'  ) {
			$this->send_data( true );
		}
	}

	/**
	 * Display the admin notice to users that have not opted-in or out
	 *
	 * @since 3.12.0
	 * @access public
	 * @return void
	 */
	public function admin_notice() {
		$hide_notice = get_option( 'wpsc_usage_tracking_notice' );

		if ( $hide_notice ) {
			return;
		}

		if ( get_option( 'wpsc_usage_tracking', false ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$optin_url  = esc_url_raw( add_query_arg( 'wpsc_tracking_action', 'opt_into_tracking' ) );
		$optout_url = esc_url_raw( add_query_arg( 'wpsc_tracking_action', 'opt_out_of_tracking' ) );
		$extensions_url = $this->api_url . 'store/';
		echo '<div class="updated"><p>';
			echo sprintf( __( 'Allow WP eCommerce to track plugin usage? Opt-in to tracking and our newsletter and immediately be emailed a 20%s discount to the WPEC shop, valid towards the <a href="%s" target="_blank">purchase of extensions</a>. No sensitive data is tracked.', 'wp-e-commerce' ), '%', $extensions_url );
			echo '&nbsp;<a href="' . esc_url( $optin_url ) . '" class="button-secondary">' . __( 'Allow', 'wp-e-commerce' ) . '</a>';
			echo '&nbsp;<a href="' . esc_url( $optout_url ) . '" class="button-secondary">' . __( 'Do not allow', 'wp-e-commerce' ) . '</a>';
		echo '</p></div>';
	}

	/**
	 * Setup the data that is going to be tracked
	 *
	 * @since 3.12.0
	 * @access private
	 * @return void
	 */
	private function setup_data() {

		$data = array();

		// General site info
		$data['url']                = home_url();
		$data['email']              = get_option( 'admin_email' );
		$data['first_name']         = wp_get_current_user()->first_name;
		$data['last_name']          = wp_get_current_user()->last_name;

		// Theme info
		$data['theme']              = self::get_theme_info();

		// WordPress Info
		$data['wp']                 = self::get_wordpress_info();

		// Server Info
		$data['server']             = self::get_server_info();

		// Plugin info
		$all_plugins                = self::get_all_plugins();
		$data['active_plugins']     = $all_plugins['active_plugins'];
		$data['inactive_plugins']   = $all_plugins['inactive_plugins'];

		// WPEC Related Section
		$data['wpec']               = self::get_wpec_info();

		// Store count info
		$data['users']              = self::get_user_counts();
		$data['products']           = self::get_product_counts();
		$data['orders']             = self::get_order_counts();

		// Payment gateway info
		$data['gateways']           = self::get_active_payment_gateways();

		// Shipping method info
		$data['shipping_methods']   = self::get_active_shipping_methods();

		// Template overrides
		$data['template_overrides'] = self::get_all_template_overrides();

		$this->data = $data;
	}

	/**
	 * Get the current theme info, theme name and version.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	public static function get_theme_info() {
		$theme_data        = wp_get_theme();

		return array(
			'name'        => $theme_data->Name,
			'version'     => $theme_data->Version,
			'child_theme' => is_child_theme() ? 'Yes' : 'No'
		);
	}

	/**
	 * Get WordPress related data.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_wordpress_info() {
		$wp_data = array();

		$memory = self::wpsc_let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = self::wpsc_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		$wp_data['memory_limit'] = size_format( $memory );
		$wp_data['debug_mode']   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data['locale']       = get_locale();
		$wp_data['version']      = get_bloginfo( 'version' );
		$wp_data['multisite']    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get server related info.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_server_info() {
		$server_data = array();

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$server_data['software'] = $_SERVER['SERVER_SOFTWARE'];
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data['php_version'] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data['php_post_max_size'] = size_format( self::wpsc_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data['php_time_limt']      = ini_get( 'max_execution_time' );
			$server_data['php_max_input_vars'] = ini_get( 'max_input_vars' );
			$server_data['php_suhosin']        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		global $wpdb;

		$server_data['mysql_version'] = $wpdb->db_version();

		$server_data['php_max_upload_size']  = size_format( wp_max_upload_size() );
		$server_data['php_default_timezone'] = date_default_timezone_get();
		$server_data['php_soap']             = class_exists( 'SoapClient' )   ? 'Yes' : 'No';
		$server_data['php_fsockopen']        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data['php_curl']             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get all plugins grouped into activated or not.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_all_plugins() {

		// Ensure get_plugins function is loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        	 = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins 	 = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted = array();
			$formatted['name'] = strip_tags( $v['Name'] );

			if ( isset( $v['Version'] ) ) {
				$formatted['version'] = strip_tags( $v['Version'] );
			}

			if ( isset( $v['Author'] ) ) {
				$formatted['author'] = strip_tags( $v['Author'] );
			}

			if ( isset( $v['Network'] ) ) {
				$formatted['network'] = strip_tags( $v['Network'] );
			}

			if ( isset( $v['PluginURI'] ) ) {
				$formatted['plugin_uri'] = strip_tags( $v['PluginURI'] );
			}

			if ( in_array( $k, $active_plugins_keys ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return array(
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $plugins
		);
	}

	/**
	 * Get WP eCommerce related info.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_wpec_info() {
		$wpec_data = array();

		$base_country = new WPSC_Country( wpsc_get_base_country() );

		$wpec_data['version']      = WPSC_VERSION;
		$wpec_data['url']          = WPSC_URL;
		$wpec_data['base_country'] = $base_country->get_name();
		$wpec_data['debug']        = WPSC_DEBUG;

		return $wpec_data;
	}

	/**
	 * Get user totals based on user role.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_user_counts() {
		$user_count          = array();

		$user_count_data     = count_users();
		$user_count['total'] = $user_count_data['total_users'];

		// Get user count based on user role
		foreach ( $user_count_data['avail_roles'] as $role => $count ) {
			$user_count[ $role ] = $count;
		}

		return $user_count;
	}

	/**
	 * Get product totals based on product type.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_product_counts() {
		$product_count          = array();

		$product_count_data     = wp_count_posts( 'wpsc-product' );
		$product_count['total'] = $product_count_data->publish;

		return $product_count;
	}

	/**
	 * Get order counts based on order status.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_order_counts() {
		global $wpdb;

		$order_count = array();
		$curr_year   = date( 'Y' );

		$result = $wpdb->get_row(
			"SELECT FROM_UNIXTIME( DATE,  '%Y' ) AS year
			FROM `" . WPSC_TABLE_PURCHASE_LOGS . "`
			WHERE FROM_UNIXTIME( DATE,  '%Y' ) > 2000
			AND processed IN ( 3, 4, 5 )
			ORDER BY date ASC
			LIMIT 1 ", ARRAY_A
		);

		$start_year = $result['year'];

		if ( $start_year ) {
			while ( $start_year <= $curr_year ) {
				$sql = $wpdb->prepare(
					"SELECT SUM(`totalprice`) as total,
					COUNT(*) as cnt
					FROM `" . WPSC_TABLE_PURCHASE_LOGS . "`
					WHERE `processed` IN (3,4,5) AND `date` BETWEEN %s AND %s",
					mktime( 0, 0, 0, 1, 1, $start_year ),
					mktime( 23, 59, 59, 12, 31, $start_year )
				);

				$orders = $wpdb->get_row( $sql, ARRAY_A );

				if ( $orders ) {
					$order_count[ $start_year ] = array(
						'orders' => $orders['cnt'],
						'total'  => $orders['total']
					);
				}

				$start_year++;
			}
		}

		$currency_data           = WPSC_Countries::get_currency_data( get_option( 'currency_type' ), true );
		$order_count['currency'] = $currency_data['code'];

		return $order_count;
	}

	/**
	 * Get a list of all active payment gateways.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_active_payment_gateways() {
		$active_gateways = array();

		// First get merchant V2 gateways
		if ( _wpsc_is_merchant_v2_active() ) {
			$gateways = _wpsc_merchant_v2_get_active_gateways();

			if ( ! empty( $gateways ) ) {
				foreach ( $gateways as $id => $gateway ) {
					$active_gateways['mv2'][ $id ] = array( 'title' => $gateway['name'] );
				}
			}
		}

		// Merchant V3 gateways if any
		$gateways = WPSC_Payment_Gateways::get_active_gateways();

		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $id => $gateway ) {
				$meta = WPSC_Payment_Gateways::get_meta( $gateway );
				$name = isset( $meta['name'] ) ? $meta['name'] : $meta['class'];

				$active_gateways['mv3'][ $id ] = array( 'title' => $name );
			}
		}

		return $active_gateways;
	}

	/**
	 * Get a list of all active shipping methods.
	 *
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_active_shipping_methods() {
		$active_methods   = array();

		if ( wpsc_is_shipping_enabled() ) {
			global $wpsc_shipping_modules;

			$custom_shipping = get_option( 'custom_shipping_options' );

			foreach ( (array) $custom_shipping as $id => $shipping ) {
				$module_title = isset( $wpsc_shipping_modules[ $shipping ] ) && is_callable( array( $wpsc_shipping_modules[ $shipping ], 'getName' ) ) ? $wpsc_shipping_modules[ $shipping ]->getName() : '';
				$active_methods[ $id ] = array( 'name' => $shipping, 'title' => $module_title );
			}
		}

		return $active_methods;
	}

	/**
	 * Look for any template override and return filenames.
	 *
	 * @todo Implement a method of checking template overrides for tev2.
	 * @since 3.12.0
	 * @return array
	 */
	private static function get_all_template_overrides() {
		$override_data  = array();

		$te = get_option( 'wpsc_get_active_theme_engine', '1.0' );

		if( '1.0' == $te ) {
			$override_data = wpsc_check_theme_location();
		}

		return $override_data;
	}

	/**
	 * let_to_num function.
	 *
	 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
	 *
	 * @since 3.12.0
	 * @param $size
	 * @return int
	 */
	private static function wpsc_let_to_num( $size ) {
		$l   = substr( $size, -1 );
		$ret = substr( $size, 0, -1 );
		switch ( strtoupper( $l ) ) {
			case 'P':
				$ret *= 1024;
			case 'T':
				$ret *= 1024;
			case 'G':
				$ret *= 1024;
			case 'M':
				$ret *= 1024;
			case 'K':
				$ret *= 1024;
		}
		return $ret;
	}
}

$wpsc_tracking = new WPSC_Tracking;
