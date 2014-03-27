<?php

function _wpsc_db_upgrade_8() {
	_wpsc_fix_bulgaria_currency();
}

function _wpsc_fix_bulgaria_currency() {
	$country = new WPSC_Country( 'BG', 'isocode' );
	$country->set( array(
		'code' => __( 'BGN', 'wpsc' ),
	) );
	$country->save();
}