<?php
class WPEC_Braintree_Helpers {

	private static $instance;

	/** @var array the admin notices to add */
	public static $notices = array();

	public function __construct() {
	}

	public static function get_instance() {
		if  ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPEC_Braintree_Helpers ) ) {
			if( version_compare( phpversion(), '5.4', '<' ) ) {
				return;
			} else {
				self::$instance = new WPEC_Braintree_Helpers;

				self::deactivate_plugins();
				self::includes();
				self::add_actions();
				self::add_filters();
			}
		}
		return self::$instance;
	}

	public static function deactivate_plugins() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			return;
		}
		if ( is_plugin_active( 'wpec-pp-braintree/pp-braintree.php' ) ) {
			deactivate_plugins( 'wpec-pp-braintree/pp-braintree.php' );
		}
	}

	public static function includes() {
		require_once( WPSC_MERCHANT_V3_SDKS_PATH . '/pp-braintree/sdk/lib/Braintree.php' );
	}

	public static function add_actions() {
		add_action( 'admin_notices', array( self::$instance, 'admin_notices' ), 15 );
		add_action( 'admin_init', array( self::$instance, 'handle_auth_connect' ) );
		add_action( 'admin_init', array( self::$instance, 'handle_auth_disconnect' ) );
		add_action( 'wpsc_loaded', array( self::$instance, 'init' ), 2 );
		add_action( 'admin_enqueue_scripts', array( self::$instance, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts' , array( self::$instance, 'pp_braintree_enqueue_js' ), 100 );
		add_action( 'wpsc_submit_gateway_options', array( self::$instance, 'update_payment_gateway_settings' ), 90 );
	}

	public static function add_filters() {
		add_filter( 'wpsc_gateway_checkout_form_wpsc_merchant_braintree_v_zero_pp', array( self::$instance, 'pp_braintree_pp_checkout_fields') );
		add_filter( 'wpsc_gateway_name', array( self::$instance, 'tev1_custom_gateway_name'), 10, 2 );
	}

	public function init() {
		// Add hidden field to hold token value Tev1
		add_action( 'wpsc_inside_shopping_cart', array( $this, 'te_v1_insert_hidden_field' ) );
		// Add hidden field to hold token value Tev2
		add_filter( 'wpsc_get_checkout_payment_method_form_args', array( $this, 'te_v2_insert_hidden_field' ) );
		//add_action( 'wpsc_default_credit_card_form_end', array( $this, 'te_v2_insert_hidden_field' ) );
	}

	public function tev1_custom_gateway_name( $name, $gateway ) {

		if ( $gateway['internalname'] == 'braintree-credit-cards' ) {
			$name = __( 'Cards', 'wp-e-commerce' );
		}

		if ( $gateway['internalname'] == 'braintree-paypal' ) {
			$name = __( 'PayPal', 'wp-e-commerce' );
		}
		return $name;
	}

	public function te_v1_insert_hidden_field() {
		echo '<input type="hidden" id="pp_btree_method_nonce" name="pp_btree_method_nonce" value="" />';
		echo '<input type="hidden" id="pp_btree_card_kount" name="pp_btree_card_kount" value="" />';
	}

	public function te_v2_insert_hidden_field( $args ) {
		if ( $args['id'] != 'wpsc-checkout-form' ) {
			return;
		}

		array_push( $args['form_actions'], array( 'type' => 'hidden', 'name' => 'pp_btree_method_nonce', 'value' => '' ) );
		array_push( $args['form_actions'], array( 'type' => 'hidden', 'name' => 'pp_btree_card_kount', 'value' => '' ) );

		return $args;
	}

	public function admin_scripts( $hook ) {
		if ( 'settings_page_wpsc-settings' !== $hook ) {
			return;
		}

		wp_register_script( 'pp-bt-admin', WPSC_MERCHANT_V3_SDKS_URL . '/pp-braintree/assets/js/admin.js', array( 'jquery' ), WPSC_VERSION, true );
		wp_enqueue_script( 'pp-bt-admin' );
	}

	public static function pp_braintree_enqueue_js() {
		if ( ! self::get_instance()->is_gateway_active( 'braintree-credit-cards' ) && ! self::get_instance()->is_gateway_active( 'braintree-paypal' ) ) {
			return;
		}

		if ( ! self::get_instance()->is_gateway_setup( 'braintree-credit-cards' ) && ! self::get_instance()->is_gateway_setup( 'braintree-paypal' ) ) {
			return;
		}

		$is_cart = wpsc_is_theme_engine( '1.0' ) ? wpsc_is_checkout() : ( _wpsc_get_current_controller_method() == 'payment' );
		if ( $is_cart ) {
			//Get Cards Gateway settings
			$bt_cc = new WPSC_Payment_Gateway_Setting( 'braintree-credit-cards' );
			//Get PayPal Gateway settings
			$bt_pp = new WPSC_Payment_Gateway_Setting( 'braintree-paypal' );

			// Check if we are using Auth and connected
			if ( self::get_instance()->bt_auth_is_connected() && self::get_instance()->bt_auth_is_connected() ) {
				$acc_token = get_option( 'wpec_braintree_auth_access_token' );
				$gateway = new Braintree_Gateway( array(
					'accessToken' => $acc_token
				));

				$clientToken = $gateway->clientToken()->generate();
				$pp_sandbox = self::get_auth_environment();
			} else {
				self::get_instance()->setBraintreeConfiguration();
				$clientToken = Braintree_ClientToken::generate();

				$bt_pp_sandbox = $bt_pp->get('sandbox');
				$pp_sandbox = $bt_pp_sandbox == '1' ? 'sandbox' : 'production';
			}

			// Set PP Button styles
			$pp_but_colour = $bt_pp->get('but_colour') != false ? $bt_pp->get('but_colour') : 'gold';
			$pp_but_size = $bt_pp->get('but_size') != false ? $bt_pp->get('but_size') : 'responsive';
			$pp_but_shape = $bt_pp->get('but_shape') != false ? $bt_pp->get('but_shape') : 'pill';

			wp_register_script( 'pp-braintree', WPSC_MERCHANT_V3_SDKS_URL . '/pp-braintree/assets/js/frontend.js', array( 'jquery' ), null, true );
			wp_localize_script( 'pp-braintree', 'wpec_ppbt', array(
				't3ds' => $bt_cc->get('three_d_secure'),
				't3dsonly' => $bt_cc->get('three_d_secure_only'),
				'ctoken' => $clientToken,
				'sandbox' => $pp_sandbox,
				'but_label' => 'pay',
				'but_colour' => $pp_but_colour,
				'but_size' => $pp_but_size,
				'but_shape' => $pp_but_shape,
				'cart_total' => wpsc_cart_total(false),
				'currency' => wpsc_get_currency_code(),
				'is_shipping' => wpsc_uses_shipping(),
				'is_cc_active' => self::get_instance()->is_gateway_active( 'braintree-credit-cards' ),
				'is_pp_active' => self::get_instance()->is_gateway_active( 'braintree-paypal' ),
				)
			);

			wp_enqueue_style( 'pp-braintree-css', WPSC_MERCHANT_V3_SDKS_URL . '/pp-braintree/assets/css/style.css' );

			wp_enqueue_script( 'pp-braintree' );
			wp_enqueue_script( 'ppbtclient', 'https://js.braintreegateway.com/web/3.20.0/js/client.min.js', array(), null, true );
			wp_enqueue_script( 'ppbthosted', 'https://js.braintreegateway.com/web/3.20.0/js/hosted-fields.min.js', array(), null, true );
			wp_enqueue_script( 'ppbtppcheckout', 'https://js.braintreegateway.com/web/3.20.0/js/paypal-checkout.min.js', array(), null, true );
			wp_enqueue_script( 'ppbtppapi', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			wp_enqueue_script( 'ppbtthreeds', 'https://js.braintreegateway.com/web/3.20.0/js/three-d-secure.min.js', array(), null, true );
			wp_enqueue_script( 'ppbtdata', 'https://js.braintreegateway.com/web/3.20.0/js/data-collector.min.js', array(), null, true );
		}
	}

	public function is_gateway_active( $gateway ) {

		return wpsc_is_gateway_active( $gateway );
	}

	public function is_gateway_setup( $gateway ) {
		$settings = new WPSC_Payment_Gateway_Setting( $gateway );

		if ( self::get_instance()->bt_auth_is_connected() ) {
			if ( self::get_instance()->bt_auth_is_connected() && self::is_client_token( $gateway ) ) {
				return true;
			} else {
				//Disconnect BT auth
				delete_option( 'wpec_braintree_auth_access_token' );
				delete_option( 'wpec_braintree_auth_environment' );
				delete_option( 'wpec_braintree_auth_merchant_id' );
				return false;
			}
		}

		if ( $settings->get( 'public_key' ) && $settings->get( 'private_key' ) && $settings->get( 'merchant_id' ) ) {

			if ( self::is_client_token( $gateway ) ) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	public function show_connect_button() {
		$output = '';
		if ( self::get_instance()->bt_auth_can_connect() ) {
			$connect_url = ! self::get_instance()->bt_auth_is_connected() ? self::wpec_bt_auth_get_connect_url() : self::wpec_bt_auth_get_disconnect_url();
			$button_image_url = WPSC_MERCHANT_V3_SDKS_URL . '/pp-braintree/sdk/images/connect-braintree.png';
			$output .= '<tr class="btpp-braintree-auth">
							<td>Connect/Disconnect</td>';
			if ( self::get_instance()->bt_auth_is_connected() ) {
				$output .= "<td><a href='". esc_url( $connect_url ) . "' class='button-primary'>" . esc_html__( 'Disconnect from PayPal Powered by Braintree', 'wp-e-commerce' ) . "</a>
							<p class='small description'>" . __( 'Merchant account: ', 'wp-e-commerce' ) . esc_attr( get_option( 'wpec_braintree_auth_merchant_id' ) ) ."</p></td>";
			} else {
				$output .= "<td><a href='" . esc_url( $connect_url ) . "' class='wpec-braintree-connect-button'><img src='" . esc_url( $button_image_url ) . "'/></a>
							<p class='small description'><a href='". esc_url( 'https://www.braintreepayments.com/partners/learn-more' ) ."' target='_blank'>" . __( 'Learn More ', 'wp-e-commerce' ) ."</a></p></td>
							<td></td>";
			}
			$output .= '</tr>';
		}

		return $output;
	}

	/**
	 * Gets the Braintree Auth disconnect URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function wpec_bt_auth_get_disconnect_url() {

		$url = add_query_arg( 'disconnect_paypal_braintree', 1, admin_url( esc_url_raw( 'options-general.php?page=wpsc-settings&tab=gateway' ) ) );

		return wp_nonce_url( $url, 'disconnect_paypal_braintree', 'wpec_paypal_braintree_admin_nonce' );
	}

	/**
	 * Gets the Braintree Auth connect URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function wpec_bt_auth_get_connect_url() {
		$base = wpsc_get_base_country();
		$connect_url = 'https://wpecommerce.org/wp-json/wpec/v1/braintree';

		$redirect_url = wp_nonce_url( admin_url( esc_url_raw( 'options-general.php?page=wpsc-settings&tab=gateway' ) ), 'connect_paypal_braintree', 'wpec_paypal_braintree_admin_nonce' );

		$current_user = wp_get_current_user();

		$environment = get_option( 'braintree_sandbox_mode' );
		$environment = $environment == 'on' ? 'sandbox' : 'production' ;

		// Note:  We doubly urlencode the redirect url to avoid Braintree's server
		// decoding it which would cause loss of query params on the final redirect
		$query_args = array(
			'Auth'              => 'WPeCBraintree',
			'user_email'        => $current_user->user_email,
			'business_currency' => wpsc_get_currency_code(),
			'business_website'  => get_bloginfo( 'url' ),
			'redirect'          => base64_encode( $redirect_url ),
		);

		if ( ! empty( $current_user->user_firstname ) ) {
			$query_args[ 'user_firstName' ] = $current_user->user_firstname;
		}

		if ( ! empty( $current_user->user_lastname ) ) {
			$query_args[ 'user_lastName' ] = $current_user->user_lastname;
		}

		// Let's go ahead and assume the user and business are in the same region and country,
		// because they probably are.  If not, they can edit these anyways
		$base_country = new WPSC_Country( $base );
		$region = new WPSC_Region( get_option( 'base_country' ), get_option( 'base_region' ) );

		$location = in_array( $base_country->get_isocode(), array( 'US', 'UK', 'FR' ) ) ? $base_country->get_isocode() : 'US';

		if ( ! empty( $base ) ) {
			$query_args['business_country'] = $query_args['user_country'] = $base;
		}

		if ( ! empty( $region ) ) {
			$query_args['business_region'] = $query_args['user_region'] = $region->get_code();
		}

		if ( $site_name = get_bloginfo( 'name' ) ) {
			$query_args[ 'business_name' ] = $site_name;
		}

		if ( $site_description = get_bloginfo( 'description' ) ) {
			$query_args[ 'business_description' ] = $site_description;
		}

		return add_query_arg( $query_args, $connect_url );
	}

	public function bt_auth_can_connect() {
		$base_country = new WPSC_Country( wpsc_get_base_country() );

		return in_array( $base_country->get_isocode(), array( 'US', 'UK', 'FR', 'GB' ) );
	}

	public function bt_auth_is_connected() {
		$token = get_option( 'wpec_braintree_auth_access_token', '' );

		return ! empty( $token );
	}

	/**
	 * Returns a list of merchant currencies
	 */
	public static function getMerchantCurrencies() {
		$merchant_currencies = array();
		// These are all the currencies supported by Braintree. Some have been commented out as trying to
		// load them all really slows down the display of the admin section for Braintree payments
		/*
		$merchant_currencies[] = array('currency'=>'AFN','currency_label'=>'Afghan Afghani');
		$merchant_currencies[] = array('currency'=>'ALL','currency_label'=>'Albanian Lek');
		$merchant_currencies[] = array('currency'=>'AMD','currency_label'=>'Armenian Dram');
		$merchant_currencies[] = array('currency'=>'ANG','currency_label'=>'Netherlands Antillean Gulden');
		$merchant_currencies[] = array('currency'=>'AOA','currency_label'=>'Angolan Kwanza');
		$merchant_currencies[] = array('currency'=>'ARS','currency_label'=>'Argentine Peso');
		*/
		$merchant_currencies[] = array('currency'=>'AUD','currency_label'=>'Australian Dollar');
		/*
		$merchant_currencies[] = array('currency'=>'AWG','currency_label'=>'Aruban Florin');
		$merchant_currencies[] = array('currency'=>'AZN','currency_label'=>'Azerbaijani Manat');
		$merchant_currencies[] = array('currency'=>'BAM','currency_label'=>'Bosnia and Herzegovina Convertible Mark');
		$merchant_currencies[] = array('currency'=>'BBD','currency_label'=>'Barbadian Dollar');
		$merchant_currencies[] = array('currency'=>'BDT','currency_label'=>'Bangladeshi Taka');
		$merchant_currencies[] = array('currency'=>'BGN','currency_label'=>'Bulgarian Lev');
		$merchant_currencies[] = array('currency'=>'BHD','currency_label'=>'Bahraini Dinar');
		$merchant_currencies[] = array('currency'=>'BIF','currency_label'=>'Burundian Franc');
		$merchant_currencies[] = array('currency'=>'BMD','currency_label'=>'Bermudian Dollar');
		$merchant_currencies[] = array('currency'=>'BND','currency_label'=>'Brunei Dollar');
		$merchant_currencies[] = array('currency'=>'BOB','currency_label'=>'Bolivian Boliviano');
		$merchant_currencies[] = array('currency'=>'BRL','currency_label'=>'Brazilian Real');
		$merchant_currencies[] = array('currency'=>'BSD','currency_label'=>'Bahamian Dollar');
		$merchant_currencies[] = array('currency'=>'BTN','currency_label'=>'Bhutanese Ngultrum');
		$merchant_currencies[] = array('currency'=>'BWP','currency_label'=>'Botswana Pula');
		$merchant_currencies[] = array('currency'=>'BYR','currency_label'=>'Belarusian Ruble');
		$merchant_currencies[] = array('currency'=>'BZD','currency_label'=>'Belize Dollar');
		*/
		$merchant_currencies[] = array('currency'=>'CAD','currency_label'=>'Canadian Dollar');
		//$merchant_currencies[] = array('currency'=>'CDF','currency_label'=>'Congolese Franc');
		$merchant_currencies[] = array('currency'=>'CHF','currency_label'=>'Swiss Franc');
		//$merchant_currencies[] = array('currency'=>'CLP','currency_label'=>'Chilean Peso');
		$merchant_currencies[] = array('currency'=>'CNY','currency_label'=>'Chinese Renminbi Yuan');
		/*
		$merchant_currencies[] = array('currency'=>'COP','currency_label'=>'Colombian Peso');
		$merchant_currencies[] = array('currency'=>'CRC','currency_label'=>'Costa Rican Col�n');
		$merchant_currencies[] = array('currency'=>'CUC','currency_label'=>'Cuban Convertible Peso');
		$merchant_currencies[] = array('currency'=>'CUP','currency_label'=>'Cuban Peso');
		$merchant_currencies[] = array('currency'=>'CVE','currency_label'=>'Cape Verdean Escudo');
		$merchant_currencies[] = array('currency'=>'CZK','currency_label'=>'Czech Koruna');
		$merchant_currencies[] = array('currency'=>'DJF','currency_label'=>'Djiboutian Franc');
		$merchant_currencies[] = array('currency'=>'DKK','currency_label'=>'Danish Krone');
		$merchant_currencies[] = array('currency'=>'DOP','currency_label'=>'Dominican Peso');
		$merchant_currencies[] = array('currency'=>'DZD','currency_label'=>'Algerian Dinar');
		$merchant_currencies[] = array('currency'=>'EEK','currency_label'=>'Estonian Kroon');
		$merchant_currencies[] = array('currency'=>'EGP','currency_label'=>'Egyptian Pound');
		$merchant_currencies[] = array('currency'=>'ERN','currency_label'=>'Eritrean Nakfa');
		$merchant_currencies[] = array('currency'=>'ETB','currency_label'=>'Ethiopian Birr');
		*/
		$merchant_currencies[] = array('currency'=>'EUR','currency_label'=>'Euro');
		//$merchant_currencies[] = array('currency'=>'FJD','currency_label'=>'Fijian Dollar');
		//$merchant_currencies[] = array('currency'=>'FKP','currency_label'=>'Falkland Pound');
		$merchant_currencies[] = array('currency'=>'GBP','currency_label'=>'British Pound');
		/*
		$merchant_currencies[] = array('currency'=>'GEL','currency_label'=>'Georgian Lari');
		$merchant_currencies[] = array('currency'=>'GHS','currency_label'=>'Ghanaian Cedi');
		$merchant_currencies[] = array('currency'=>'GIP','currency_label'=>'Gibraltar Pound');
		$merchant_currencies[] = array('currency'=>'GMD','currency_label'=>'Gambian Dalasi');
		$merchant_currencies[] = array('currency'=>'GNF','currency_label'=>'Guinean Franc');
		$merchant_currencies[] = array('currency'=>'GTQ','currency_label'=>'Guatemalan Quetzal');
		$merchant_currencies[] = array('currency'=>'GYD','currency_label'=>'Guyanese Dollar');
		*/
		$merchant_currencies[] = array('currency'=>'HKD','currency_label'=>'Hong Kong Dollar');
		/*
		$merchant_currencies[] = array('currency'=>'HNL','currency_label'=>'Honduran Lempira');
		$merchant_currencies[] = array('currency'=>'HRK','currency_label'=>'Croatian Kuna');
		$merchant_currencies[] = array('currency'=>'HTG','currency_label'=>'Haitian Gourde');
		$merchant_currencies[] = array('currency'=>'HUF','currency_label'=>'Hungarian Forint');
		$merchant_currencies[] = array('currency'=>'IDR','currency_label'=>'Indonesian Rupiah');
		$merchant_currencies[] = array('currency'=>'ILS','currency_label'=>'Israeli New Sheqel');
		$merchant_currencies[] = array('currency'=>'INR','currency_label'=>'Indian Rupee');
		$merchant_currencies[] = array('currency'=>'IQD','currency_label'=>'Iraqi Dinar');
		$merchant_currencies[] = array('currency'=>'IRR','currency_label'=>'Iranian Rial');
		$merchant_currencies[] = array('currency'=>'ISK','currency_label'=>'Icelandic Kr�na');
		$merchant_currencies[] = array('currency'=>'JMD','currency_label'=>'Jamaican Dollar');
		$merchant_currencies[] = array('currency'=>'JOD','currency_label'=>'Jordanian Dinar');
		*/
		$merchant_currencies[] = array('currency'=>'JPY','currency_label'=>'Japanese Yen');
		/*
		$merchant_currencies[] = array('currency'=>'KES','currency_label'=>'Kenyan Shilling');
		$merchant_currencies[] = array('currency'=>'KGS','currency_label'=>'Kyrgyzstani Som');
		$merchant_currencies[] = array('currency'=>'KHR','currency_label'=>'Cambodian Riel');
		$merchant_currencies[] = array('currency'=>'KMF','currency_label'=>'Comorian Franc');
		$merchant_currencies[] = array('currency'=>'KPW','currency_label'=>'North Korean Won');
		$merchant_currencies[] = array('currency'=>'KRW','currency_label'=>'South Korean Won');
		$merchant_currencies[] = array('currency'=>'KWD','currency_label'=>'Kuwaiti Dinar');
		$merchant_currencies[] = array('currency'=>'KYD','currency_label'=>'Cayman Islands Dollar');
		$merchant_currencies[] = array('currency'=>'KZT','currency_label'=>'Kazakhstani Tenge');
		$merchant_currencies[] = array('currency'=>'LAK','currency_label'=>'Lao Kip');
		$merchant_currencies[] = array('currency'=>'LBP','currency_label'=>'Lebanese Lira');
		$merchant_currencies[] = array('currency'=>'LKR','currency_label'=>'Sri Lankan Rupee');
		$merchant_currencies[] = array('currency'=>'LRD','currency_label'=>'Liberian Dollar');
		$merchant_currencies[] = array('currency'=>'LSL','currency_label'=>'Lesotho Loti');
		$merchant_currencies[] = array('currency'=>'LTL','currency_label'=>'Lithuanian Litas');
		$merchant_currencies[] = array('currency'=>'LVL','currency_label'=>'Latvian Lats');
		$merchant_currencies[] = array('currency'=>'LYD','currency_label'=>'Libyan Dinar');
		$merchant_currencies[] = array('currency'=>'MAD','currency_label'=>'Moroccan Dirham');
		$merchant_currencies[] = array('currency'=>'MDL','currency_label'=>'Moldovan Leu');
		$merchant_currencies[] = array('currency'=>'MGA','currency_label'=>'Malagasy Ariary');
		$merchant_currencies[] = array('currency'=>'MKD','currency_label'=>'Macedonian Denar');
		$merchant_currencies[] = array('currency'=>'MMK','currency_label'=>'Myanmar Kyat');
		$merchant_currencies[] = array('currency'=>'MNT','currency_label'=>'Mongolian T�gr�g');
		$merchant_currencies[] = array('currency'=>'MOP','currency_label'=>'Macanese Pataca');
		$merchant_currencies[] = array('currency'=>'MRO','currency_label'=>'Mauritanian Ouguiya');
		$merchant_currencies[] = array('currency'=>'MUR','currency_label'=>'Mauritian Rupee');
		$merchant_currencies[] = array('currency'=>'MVR','currency_label'=>'Maldivian Rufiyaa');
		$merchant_currencies[] = array('currency'=>'MWK','currency_label'=>'Malawian Kwacha');
		$merchant_currencies[] = array('currency'=>'MXN','currency_label'=>'Mexican Peso');
		$merchant_currencies[] = array('currency'=>'MYR','currency_label'=>'Malaysian Ringgit');
		$merchant_currencies[] = array('currency'=>'MZN','currency_label'=>'Mozambican Metical');
		$merchant_currencies[] = array('currency'=>'NAD','currency_label'=>'Namibian Dollar');
		$merchant_currencies[] = array('currency'=>'NGN','currency_label'=>'Nigerian Naira');
		$merchant_currencies[] = array('currency'=>'NIO','currency_label'=>'Nicaraguan C�rdoba');
		$merchant_currencies[] = array('currency'=>'NOK','currency_label'=>'Norwegian Krone');
		$merchant_currencies[] = array('currency'=>'NPR','currency_label'=>'Nepalese Rupee');
		*/
		$merchant_currencies[] = array('currency'=>'NZD','currency_label'=>'New Zealand Dollar');
		/*
		$merchant_currencies[] = array('currency'=>'OMR','currency_label'=>'Omani Rial');
		$merchant_currencies[] = array('currency'=>'PAB','currency_label'=>'Panamanian Balboa');
		$merchant_currencies[] = array('currency'=>'PEN','currency_label'=>'Peruvian Nuevo Sol');
		$merchant_currencies[] = array('currency'=>'PGK','currency_label'=>'Papua New Guinean Kina');
		$merchant_currencies[] = array('currency'=>'PHP','currency_label'=>'Philippine Peso');
		$merchant_currencies[] = array('currency'=>'PKR','currency_label'=>'Pakistani Rupee');
		$merchant_currencies[] = array('currency'=>'PLN','currency_label'=>'Polish Zloty');
		$merchant_currencies[] = array('currency'=>'PYG','currency_label'=>'Paraguayan Guaran�');
		$merchant_currencies[] = array('currency'=>'QAR','currency_label'=>'Qatari Riyal');
		$merchant_currencies[] = array('currency'=>'RON','currency_label'=>'Romanian Leu');
		$merchant_currencies[] = array('currency'=>'RSD','currency_label'=>'Serbian Dinar');
		$merchant_currencies[] = array('currency'=>'RUB','currency_label'=>'Russian Ruble');
		$merchant_currencies[] = array('currency'=>'RWF','currency_label'=>'Rwandan Franc');
		$merchant_currencies[] = array('currency'=>'SAR','currency_label'=>'Saudi Riyal');
		$merchant_currencies[] = array('currency'=>'SBD','currency_label'=>'Solomon Islands Dollar');
		$merchant_currencies[] = array('currency'=>'SCR','currency_label'=>'Seychellois Rupee');
		$merchant_currencies[] = array('currency'=>'SDG','currency_label'=>'Sudanese Pound');
		$merchant_currencies[] = array('currency'=>'SEK','currency_label'=>'Swedish Krona');
		$merchant_currencies[] = array('currency'=>'SGD','currency_label'=>'Singapore Dollar');
		$merchant_currencies[] = array('currency'=>'SHP','currency_label'=>'Saint Helenian Pound');
		$merchant_currencies[] = array('currency'=>'SKK','currency_label'=>'Slovak Koruna');
		$merchant_currencies[] = array('currency'=>'SLL','currency_label'=>'Sierra Leonean Leone');
		$merchant_currencies[] = array('currency'=>'SOS','currency_label'=>'Somali Shilling');
		$merchant_currencies[] = array('currency'=>'SRD','currency_label'=>'Surinamese Dollar');
		$merchant_currencies[] = array('currency'=>'STD','currency_label'=>'S�o Tom� and Pr�ncipe Dobra');
		$merchant_currencies[] = array('currency'=>'SVC','currency_label'=>'Salvadoran Col�n');
		$merchant_currencies[] = array('currency'=>'SYP','currency_label'=>'Syrian Pound');
		$merchant_currencies[] = array('currency'=>'SZL','currency_label'=>'Swazi Lilangeni');
		$merchant_currencies[] = array('currency'=>'THB','currency_label'=>'Thai Baht');
		$merchant_currencies[] = array('currency'=>'TJS','currency_label'=>'Tajikistani Somoni');
		$merchant_currencies[] = array('currency'=>'TMM','currency_label'=>'Turkmenistani Manat');
		$merchant_currencies[] = array('currency'=>'TMT','currency_label'=>'Turkmenistani Manat');
		$merchant_currencies[] = array('currency'=>'TND','currency_label'=>'Tunisian Dinar');
		$merchant_currencies[] = array('currency'=>'TOP','currency_label'=>'Tongan Pa?anga');
		$merchant_currencies[] = array('currency'=>'TRY','currency_label'=>'Turkish New Lira');
		$merchant_currencies[] = array('currency'=>'TTD','currency_label'=>'Trinidad and Tobago Dollar');
		$merchant_currencies[] = array('currency'=>'TWD','currency_label'=>'New Taiwan Dollar');
		$merchant_currencies[] = array('currency'=>'TZS','currency_label'=>'Tanzanian Shilling');
		$merchant_currencies[] = array('currency'=>'UAH','currency_label'=>'Ukrainian Hryvnia');
		$merchant_currencies[] = array('currency'=>'UGX','currency_label'=>'Ugandan Shilling');
		*/
		$merchant_currencies[] = array('currency'=>'USD','currency_label'=>'United States Dollar');
		/*
		$merchant_currencies[] = array('currency'=>'UYU','currency_label'=>'Uruguayan Peso');
		$merchant_currencies[] = array('currency'=>'UZS','currency_label'=>'Uzbekistani Som');
		$merchant_currencies[] = array('currency'=>'VEF','currency_label'=>'Venezuelan Bol�var');
		$merchant_currencies[] = array('currency'=>'VND','currency_label'=>'Vietnamese �?ng');
		$merchant_currencies[] = array('currency'=>'VUV','currency_label'=>'Vanuatu Vatu');
		$merchant_currencies[] = array('currency'=>'WST','currency_label'=>'Samoan Tala');
		$merchant_currencies[] = array('currency'=>'XAF','currency_label'=>'Central African Cfa Franc');
		$merchant_currencies[] = array('currency'=>'XCD','currency_label'=>'East Caribbean Dollar');
		$merchant_currencies[] = array('currency'=>'XOF','currency_label'=>'West African Cfa Franc');
		$merchant_currencies[] = array('currency'=>'XPF','currency_label'=>'Cfp Franc');
		$merchant_currencies[] = array('currency'=>'YER','currency_label'=>'Yemeni Rial');
		$merchant_currencies[] = array('currency'=>'ZAR','currency_label'=>'South African Rand');
		$merchant_currencies[] = array('currency'=>'ZMK','currency_label'=>'Zambian Kwacha');
		$merchant_currencies[] = array('currency'=>'ZWD','currency_label'=>'Zimbabwean Dollar');
		*/
		return $merchant_currencies;
	}
	/**
	 * Setup the Braintree configuration
	 */
	public function setBraintreeConfiguration( $gateway = 'braintree-credit-cards' ) {
		global $merchant_currency, $braintree_settings;

		if ( $gateway == 'paypal' ) {
			//Get PayPal Gateway settings
			$settings = new WPSC_Payment_Gateway_Setting( 'braintree-paypal' );
		} else {
			$settings = new WPSC_Payment_Gateway_Setting( 'braintree-credit-cards' );
		}

		$sandbox = $settings->get('sandbox') == '1' ? 'sandbox' : 'production';

		Braintree_Configuration::environment( $sandbox );
		Braintree_Configuration::merchantId( $settings->get('merchant_id') );
		Braintree_Configuration::publicKey( $settings->get('public_key') );
		Braintree_Configuration::privateKey( $settings->get('private_key') );
	}

	/**
	 * Handles the Braintree Auth connection response.
	 *
	 * @since 1.0.0
	 */
	public function handle_auth_connect() {
		// TO DO some sort of validation that we are on the correct page ? settings/gateways
		if ( isset( $_REQUEST['wpec_paypal_braintree_admin_nonce'] ) && isset( $_REQUEST['access_token'] ) ) {
			$nonce = isset( $_REQUEST[ 'wpec_paypal_braintree_admin_nonce' ] ) ? trim( $_REQUEST[ 'wpec_paypal_braintree_admin_nonce' ] ) : false;
			// if no nonce is present, then this probably wasn't a connection response
			if ( ! $nonce ) {
				return;
			}
			// verify the nonce
			if ( ! wp_verify_nonce( $nonce, 'connect_paypal_braintree' ) ) {
				wp_die( __( 'Invalid connection request', 'wp-e-commerce' ) );
			}
			$access_token = isset( $_REQUEST[ 'access_token' ] ) ? sanitize_text_field( base64_decode( $_REQUEST[ 'access_token' ] ) ) : false;
			if ( $access_token ) {
				update_option( 'wpec_braintree_auth_access_token', $access_token );
				list( $token_key, $environment, $merchant_id, $raw_token ) = explode( '$', $access_token );
				update_option( 'wpec_braintree_auth_environment', $environment );
				update_option( 'wpec_braintree_auth_merchant_id', $merchant_id );
				$connected = true;

				// BT Authentication successful.
				// Set 3D Secure setting here
				self::is_client_token();

			} else {
				// Show an error message maybe ?
				$connected = false;
			}
			wp_safe_redirect( add_query_arg( 'wpec_braintree_connected', $connected, admin_url( 'options-general.php?page=wpsc-settings&tab=gateway' ) ) );
			exit;
		}
	}

	/**
	 * Handles the Braintree Auth disconnect request
	 *
	 * @since 1.0.0
	 */
	public function handle_auth_disconnect() {
		// if this is not a disconnect request, bail
		if ( ! isset( $_REQUEST[ 'disconnect_paypal_braintree' ] ) ) {
			return;
		}
		$nonce = isset( $_REQUEST[ 'wpec_paypal_braintree_admin_nonce' ] ) ? trim( $_REQUEST[ 'wpec_paypal_braintree_admin_nonce' ] ) : false;
		// if no nonce is present, then this probably wasn't a disconnect request
		if ( ! $nonce ) {
			return;
		}
		// verify the nonce
		if ( ! wp_verify_nonce( $nonce, 'disconnect_paypal_braintree' ) ) {
			wp_die( __( 'Invalid disconnect request', 'wp-e-commerce' ) );
		}
		delete_option( 'wpec_braintree_auth_access_token' );
		delete_option( 'wpec_braintree_auth_environment' );
		delete_option( 'wpec_braintree_auth_merchant_id' );
		wp_safe_redirect( add_query_arg( 'wpec_braintree_disconnected', true, admin_url( 'options-general.php?page=wpsc-settings&tab=gateway' ) ) );
		exit;
	}

	/**
	 * Validates the access token or api credentials
	 *
	 * Generates a client token to verify credentials
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function is_client_token( $gateway= '' ) {
		$valid = true;

		if ( self::get_instance()->bt_auth_is_connected() ) {
			$acc_token = get_option( 'wpec_braintree_auth_access_token' );

			try {
				$gateway = new Braintree_Gateway( array( 'accessToken' => $acc_token ) );
				$clientToken = $gateway->clientToken()->generate();
			}
			catch ( Exception $e ) {
				$valid = false;;
			}
		} else {
			try {
				self::get_instance()->setBraintreeConfiguration( $gateway );
				$clientToken = Braintree_ClientToken::generate();
			}
			catch ( Exception $e ) {
				$valid = false;
			}
		}

		if ( false === $valid ) {
			return $valid;
		}

		if ( $clientToken ) {
			$decoded = json_decode( base64_decode( $clientToken ) );
			$three3ds = $decoded->threeDSecureEnabled;
			$bt_cc = new WPSC_Payment_Gateway_Setting( 'braintree-credit-cards' );
			if ( true == $three3ds ) {
				$bt_cc->set('three_d_secure', '1');
			} else {
				$bt_cc->set('three_d_secure', '0');
			}
		}

		return isset( $clientToken ) ? $clientToken : false;
	}

	public static function update_payment_gateway_settings() {
		if ( isset( $_POST['user_defined_name'] ) && $_POST['user_defined_name'] ) {
			$gateway = array_keys( $_POST['user_defined_name'] );
			if ( ! empty( $gateway ) && $gateway[0] == 'braintree-credit-cards' || $gateway[0] == 'braintree-paypal') {
				if ( $gateway[0] == 'braintree-credit-cards' ) {
					$token = self::is_client_token( 'braintree-credit-cards' );
				} else {
					$token = self::is_client_token('braintree-paypal');
				}

				if ( ! $token ) {
					// Show some error message
				}
			}
		}
	}

	public function set_payment_error_message( $error ) {
		if ( wpsc_is_theme_engine( '1.0' ) ) {
			$messages = wpsc_get_customer_meta( 'checkout_misc_error_messages' );
			if ( ! is_array( $messages ) ) {
				$messages = array();
			}
			$messages[] = $error;
			wpsc_update_customer_meta( 'checkout_misc_error_messages', $messages );
		} else {
			WPSC_Message_Collection::get_instance()->add( $error, 'error', 'main', 'flash' );
		}
	}

	/**
	 * Gets configured environment.
	 *
	 * If connected to Braintree Auth, the environment was explicitly set at
	 * the time of authentication.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_auth_environment() {
		$environment = false;

		if ( self::get_instance()->bt_auth_is_connected() ) {
			$environment = get_option( 'wpec_braintree_auth_environment', 'production' );
		}

		return $environment;
	}

	/**
	 * Get the failure status info for the given parameter, either code or message
	 * @since 1.0.0
	 * @param string $type status info type, either `code` or `message`
	 * @return string
	 */
	public function get_failure_status_info( $result, $type ) {

		// see https://developers.braintreepayments.com/reference/response/transaction/php#unsuccessful-result
		// As per recommendation show a generic response message
		$transaction = $result->transaction;
		switch ( $transaction->status ) {

			// gateway rejections are due to CVV, AVS, fraud, etc
			case 'gateway_rejected':

				$status = array(
					'code'    => $transaction->gatewayRejectionReason,
					'message' => 'There\'s been a problem processing your payment, please check and retry. If you continue to have an issue please choose an alternative payment method', //$result->message,
				);
				break;

			// soft/hard decline directly from merchant processor
			case 'processor_declined':

				$status = array(
					'code'    => $transaction->processorResponseCode,
					'message' => 'There\'s been a problem processing your payment, please check and retry. If you continue to have an issue please choose an alternative payment method', //$transaction->processorResponseText . ( ! empty( $transaction->additionalProcessorResponse ) ? ' (' . $transaction->additionalProcessorResponse . ')' : '' ),
				);
				break;

			// only can occur when attempting to settle a previously authorized charge
			case 'settlement_declined':

				$status = array(
					'code' => $transaction->processorSettlementResponseCode,
					'message' => 'There\'s been a problem processing your payment, please check and retry. If you continue to have an issue please choose an alternative payment method', //$transaction->processorSettlementResponseText,
				);
				break;

			// this path shouldn't execute, but for posterity
			default:
				$status = array(
					'code'    => $transaction->status,
					'message' => isset( $result->message ) ? $result->message : '',
				);
		}

		return isset( $status[ $type] ) ? $status[ $type ] : null;
	}

	public function admin_notices() {
		if ( ! self::get_instance()->is_gateway_active( 'braintree-credit-cards' ) && ! self::get_instance()->is_gateway_active( 'braintree-paypal' ) ) {
			return;
		}

		if ( self::get_instance()->is_gateway_setup( 'braintree-credit-cards' ) || self::get_instance()->is_gateway_setup( 'braintree-paypal' ) ) {
			return;
		}
		?>
		<div class="error notice">
			<p><?php _e( 'WP eCommerce PayPal powered by Braintree is active but not configured. Please check the Payment gateway settings page', 'wp-e-commerce' ); ?></p>
		</div>
		<?php
	}
}
