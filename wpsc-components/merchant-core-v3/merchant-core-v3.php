<?php

define( 'WPSC_MERCHANT_V3_PATH'     , dirname( __FILE__ ) );
define( 'WPSC_MERCHANT_V3_SDKS_PATH', dirname( __FILE__ ) . '/libraries' );
define( 'WPSC_MERCHANT_V3_SDKS_URL' , plugin_dir_url(  __FILE__ ) . 'libraries' );
define( 'WPSC_PAYMENT_STATUS_INCOMPLETE', 1 );
define( 'WPSC_PAYMENT_STATUS_RECEIVED'  , 2 );
define( 'WPSC_PAYMENT_STATUS_ACCEPTED'  , 3 );
define( 'WPSC_PAYMENT_STATUS_DISPATCHED', 4 );
define( 'WPSC_PAYMENT_STATUS_CLOSED'    , 5 );
define( 'WPSC_PAYMENT_STATUS_DECLINED'  , 6 );

add_action( 'wpsc_includes', '_wpsc_merchant_v3_includes', 15 );

function _wpsc_merchant_v3_includes() {
	require_once( WPSC_MERCHANT_V3_PATH . '/helpers/common.php' );
	require_once( WPSC_MERCHANT_V3_PATH . '/classes/http.php' );
	require_once( WPSC_MERCHANT_V3_PATH . '/classes/payment-gateway.php' );
	require_once( WPSC_MERCHANT_V3_PATH . '/helpers/payment-gateway.php' );
	require_once( WPSC_MERCHANT_V3_PATH . '/helpers/checkout.php' );

	if ( is_admin() ) {
		require_once( WPSC_MERCHANT_V3_PATH . '/helpers/admin.php' );
	}
}
