<?php

function _wpsc_db_upgrade_3() {
	_wpsc_maybe_create_UK();
}

function _wpsc_maybe_create_UK() {
	$country = new WPSC_Country( array(
		'isocode'     => 'GB',
		'id'          => 138,
		'country'     => __( 'United Kingdom', 'wp-e-commerce' ),
		'currency'    => __( 'Pound Sterling', 'wp-e-commerce' ),
		'symbol'      => __( '£', 'wp-e-commerce' ),
		'symbol_html' => __( '&#163;', 'wp-e-commerce' ),
		'code'        => __( 'GBP', 'wp-e-commerce' ),
		'continent'   => 'europe',
	) );
}