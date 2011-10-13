<?php
function wpsc_format_price( $amt, $currency = false ) {
	$currencies_without_fractions = array( 'JPY', 'HUF' );
	if ( ! $currency ) {
		$country = new WPSC_Country( get_option( 'currency_type' ) );
		$currency = $country->get( 'code' );
	}

	$dec = in_array( $currency, $currencies_without_fractions ) ? 0 : 2;
	return number_format( $amt, $dec );
}

function wpsc_format_convert_price( $amt, $from_currency = false, $to_currency = false ) {
	return wpsc_format_price( wpsc_convert_currency( $amt, $from_currency, $to_currency ), $to_currency );
}