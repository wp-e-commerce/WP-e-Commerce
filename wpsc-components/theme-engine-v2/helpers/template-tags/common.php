<?php
/**
 * Format a price amount.
 *
 * The available options that you can specify in the $args argument include:
 *     'display_currency_symbol' - Whether to attach the currency symbol to the figure.
 *                                 Defaults to true.
 *     'display_decimal_point'   - Whether to display the decimal point.
 *                                 Defaults to true.
 *     'display_currency_code'   - Whether to attach the currency code to the figure.
 *                                 Defaults to fault.
 *     'isocode'                 - Specify the isocode of the base country that you want to use for
 *                                 this price.
 *                                 Defaults to the settings in Settings->Store->General.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_format_currency'                     filter
 * @uses  apply_filters() Applies 'wpsc_format_currency_currency_code'       filter.
 * @uses  apply_filters() Applies 'wpsc_format_currency_currency_symbol'     filter.
 * @uses  apply_filters() Applies 'wpsc_format_currency_decimal_separator'   filter.
 * @uses  apply_filters() Applies 'wpsc_format_currency_thousands_separator' filter.
 * @uses  apply_filters() Applies 'wpsc_modify_decimals' filter.
 * @uses  get_option()    Gets the value of 'currency_sign_location' in Settings->Store->General.
 * @uses  get_option()    Gets the value of 'currency_type' in Settings->Store->General.
 * @uses  WPSC_Country::__construct()
 * @uses  WPSC_Country::get()
 * @uses  wp_parse_args()
 *
 * @param  float|int|string $amt  The price you want to format.
 * @param  string|array     $args A query string or array containing the options. Defaults to ''.
 * @return string                 The formatted price.
 */
function wpsc_format_currency( $amt, $args = '' ) {
	$defaults = array(
		'display_currency_symbol' => true,
		'display_decimal_point'   => true,
		'display_currency_code'   => false,
		'isocode'                 => false,
		'currency_code'           => false,
	);

	$args = wp_parse_args( $args );

	// Either display symbol or code, not both
	if ( array_key_exists( 'display_currency_symbol', $args ) ) {
		$args['display_currency_code'] = ! $args['display_currency_symbol'];
	} elseif ( array_key_exists( 'display_currency_code', $args ) ) {
		$args['display_currency_symbol'] = ! $args['display_currency_code'];
	}

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	$currencies_without_fractions = WPSC_Payment_Gateways::currencies_without_fractions();

	if ( $isocode ) {
		$currency = new WPSC_Country( $isocode );
	} else {
		$currency = new WPSC_Country( get_option( 'currency_type' ) );
	}

	$currency_code = $currency->get_currency_code();
	// No decimal point, no decimals
	if ( ! $display_decimal_point || in_array( $currency_code, $currencies_without_fractions ) ) {
		$decimals = 0;
	} else {
		$decimals = 2; // default is 2
	}

	$decimals            = apply_filters( 'wpsc_modify_decimals'                    , $decimals, $isocode );
	$decimal_separator   = apply_filters( 'wpsc_format_currency_decimal_separator'  , wpsc_get_option( 'decimal_separator' ), $isocode );
	$thousands_separator = apply_filters( 'wpsc_format_currency_thousands_separator', wpsc_get_option( 'thousands_separator' ), $isocode );

	// Format the price for output
	$formatted = number_format( $amt, $decimals, $decimal_separator, $thousands_separator );

	if ( ! $display_currency_code ) {
		$currency_code = '';
	}

	$symbol = $display_currency_symbol ? $currency->get_currency_symbol() : '';
	$symbol = esc_html( $symbol );
	$symbol = apply_filters( 'wpsc_format_currency_currency_symbol', $symbol, $isocode );

	$currency_sign_location = get_option( 'currency_sign_location' );

	// Rejig the currency sign location
	switch ( $currency_sign_location ) {
		case 1:
			$format_string = '%3$s%1$s%2$s';
			break;

		case 2:
			$format_string = '%3$s %1$s%2$s';
			break;

		case 4:
			$format_string = '%1$s%2$s  %3$s';
			break;

		case 3:
		default:
			$format_string = '%1$s %2$s%3$s';
			break;
	}
	$currency_code = apply_filters( 'wpsc_format_currency_currency_code', $currency_code, $isocode );

	// Compile the output
	$output = trim( sprintf( $format_string, $currency_code, $symbol, $formatted ) );
	return $output;
}

function wpsc_get_store_title() {
	return wpsc_get_option( 'store_title' );
}

function wpsc_store_title() {
	echo wpsc_get_store_title();
}

/**
 * Get the slugs of WP eCommerce related pages
 *
 * @access public
 * @since  0.1
 *
 * @return array Array of "page name" => "slug"
 */
function wpsc_get_page_slugs() {
	// get main store slug
	$store_slug = wpsc_get_option( 'store_slug' );

	// if main store is not displayed as front page, append it with slash
	if ( $store_slug ) {
		$store_slug .= '/';
	}

	// names of pages
	$pages = array(
		'cart',
		'checkout',
		'transaction-result',
		'customer-account',
		'login',
		'password-reminder',
		'register',
	);

	$slugs = array();

	// fetch the slugs corresponding to each page's name
	foreach ( $pages as $key => $page ) {

		$option = str_replace( '-', '_', $page ) . '_page_slug';
		$slug   = wpsc_get_option( $option );

		if ( ! empty( $slug ) ) {
			$slugs[ $page ] = $store_slug . $slug;
		}
	}

	return $slugs;
}

function wpsc_get_cart_title() {
	return wpsc_get_option( 'cart_page_title' );
}

function wpsc_cart_title() {
	echo wpsc_get_cart_title();
}

function wpsc_get_login_title() {
	return wpsc_get_option( 'login_page_title' );
}

function wpsc_login_title() {
	echo wpsc_get_login_title();
}

function wpsc_get_register_title() {
	return wpsc_get_option( 'register_page_title' );
}

function wpsc_register_title() {
	echo wpsc_get_register_title();
}

function wpsc_get_password_reminder_title() {
	return wpsc_get_option( 'password_reminder_page_title' );
}

function wpsc_password_reminder_title() {
	echo wpsc_get_password_reminder_title();
}

function wpsc_get_checkout_title() {
	return wpsc_get_option( 'checkout_page_title' );
}

function wpsc_checkout_title() {
	echo wpsc_get_checkout_title();
}

function wpsc_get_customer_account_title() {
	return wpsc_get_option( 'customer_account_page_title' );
}

function wpsc_customer_account_title() {
	echo wpsc_get_customer_account_title();
}