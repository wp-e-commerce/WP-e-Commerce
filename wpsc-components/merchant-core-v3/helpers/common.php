<?php

function _wpsc_is_merchant_v2_active() {
	return defined( 'WPSC_MERCHANT_V2_PATH' ) && WPSC_MERCHANT_V2_PATH;
}

/**
 * Return True if the gateway is registered and active, false otherwise.
 *
 * @param string $gateway_id
 * @return bool
 */
function wpsc_is_gateway_active( $gateway_id ) {
	$active_gateways = WPSC_Payment_Gateways::get_active_gateways();
	return in_array( $gateway_id, $active_gateways );
}
