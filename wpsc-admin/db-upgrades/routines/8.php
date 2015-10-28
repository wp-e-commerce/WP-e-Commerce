<?php

function _wpsc_db_upgrade_8() {
	_wpsc_fix_bulgaria_currency();
}

function _wpsc_fix_bulgaria_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'BG',
		'code' => __( 'BGN', 'wp-e-commerce' ),
	) );
}