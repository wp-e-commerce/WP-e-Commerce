<?php

function _wpsc_is_merchant_v2_active() {
	return defined( 'WPSC_MERCHANT_V2_PATH' ) && WPSC_MERCHANT_V2_PATH;
}