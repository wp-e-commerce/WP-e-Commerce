<?php

define( 'WPSC_MERCHANT_V2_PATH', dirname( __FILE__ ) );

add_action( 'wpsc_includes', '_wpsc_action_merchant_v2_includes' );

function _wpsc_action_merchant_v2_includes() {
	if ( is_admin() )
		require_once( WPSC_MERCHANT_V2_PATH . '/helpers/admin.php' );
}