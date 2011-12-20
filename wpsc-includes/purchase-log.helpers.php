<?php

function wpsc_format_convert_price( $amt, $from_currency = false, $to_currency = false ) {
	$amt = wpsc_convert_currency( $amt, $from_currency, $to_currency );
	return wpsc_format_price( $amt, array( 'isocode' => $to_currency ) );
}