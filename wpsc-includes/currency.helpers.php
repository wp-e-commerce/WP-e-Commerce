<?php

function wpsc_get_exchange_rate( $from, $to ) {
	if ( $from == $to )
		return 1;

	$key = "wpsc_exchange_{$from}_{$to}";

	if ( $rate = get_transient( $key ) )
		return (float) $rate;

	$url = 'http://www.google.com/ig/calculator?hl=en&q=1' . urlencode( "{$from}=?{$to}" );
	$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

	if ( is_wp_error( $response ) )
		return $response;

	$response = str_replace( array( "\n", "\r" ), '', $response['body'] );
	$response = preg_replace( '/([{,])(\s*)([^"]+?)\s*:/', '$1"$3":', $response );
	$response = json_decode( $response );
	$rate = explode( ' ', $response->rhs );
	$rate = $rate[0];

	set_transient( $key, $rate, 3600 * 24 );

	return $rate;
}

function wpsc_convert_currency( $amt, $from, $to ) {
	if ( empty( $from ) || empty( $to ) )
		return $amt;

	$rate = wpsc_get_exchange_rate( $from, $to );
	if ( is_wp_error( $rate ) )
		return $rate;

	return $rate * $amt;
}
