<?php

function _wpsc_get_exchange_rate( $from, $to ) {

	if ( $from == $to ) {
		return 1;
	}

	$key = "wpsc_exchange_{$from}_{$to}";

	if ( $rate = get_transient( $key ) ) {
		return (float) $rate;
	}

	$url = add_query_arg(
				array(
					'a'    => '1',
					'from' => $from,
					'to'   => $to
				),
				'http://www.google.com/finance/converter'
				);

	$response = wp_remote_retrieve_body( wp_remote_get( $url, array( 'timeout' => 10 ) ) );

	if ( empty( $response ) ) {
		return $response;
	} else {

        $rate = explode( 'bld>', $response );
        $rate = explode( $to, $rate[1] );
		$rate = trim( $rate[0] );
		set_transient( $key, $rate, DAY_IN_SECONDS );

		return $rate;
	}
}

function wpsc_convert_currency( $amt, $from, $to ) {
	if ( empty( $from ) || empty( $to ) )
		return $amt;

	$rate = _wpsc_get_exchange_rate( $from, $to );
	if ( is_wp_error( $rate ) )
		return $rate;

	return $rate * $amt;
}

function wpsc_string_to_float( $string ) {
	global $wp_locale;

	$decimal_separator = get_option(
		'wpsc_decimal_separator',
		$wp_locale->number_format['decimal_point']
	);

	$string = preg_replace( '/[^0-9\\' . $decimal_separator . ']/', '', $string );
	$string = str_replace( $decimal_separator, '.', $string );

	return (float) $string;
}

function wpsc_format_number( $number, $decimals = 2 ) {
	global $wp_locale;

	$decimal_separator = get_option(
		'wpsc_decimal_separator',
		$wp_locale->number_format['decimal_point']
	);

	$thousands_separator = get_option(
		'wpsc_thousands_separator',
		$wp_locale->number_format['thousands_sep']
	);

	$formatted = number_format(
		(float) $number,
		$decimals,
		$decimal_separator,
		$thousands_separator
	);

	return $formatted;
}