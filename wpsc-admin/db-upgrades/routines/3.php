<?php

function _wpsc_db_upgrade_3() {
	_wpsc_maybe_create_UK();
}

function _wpsc_maybe_create_UK() {
	$country = new WPSC_Country( array(
		'isocode'     => 'GB',
		'id'          => 138,
		'country'     => __( 'United Kingdom', 'wpsc' ),
		'currency'    => __( 'Pound Sterling', 'wpsc' ),
		'symbol'      => __( '£', 'wpsc' ),
		'symbol_html' => __( '&#163;', 'wpsc' ),
		'code'        => __( 'GBP', 'wpsc' ),
		'continent'   => 'europe',
	) );
}