<?php

function _wpsc_db_upgrade_1() {
	_wpsc_fix_checkout_field_limitation();
	_wpsc_fix_mexico_currency_sign();
	_wpsc_fix_canadian_province_codes();
	_wpsc_fix_colombia_currency();
	_wpsc_fix_ukraine_currency();
	_wpsc_fix_russia_currency();
	_wpsc_fix_belarus_currency();
	_wpsc_fix_estonia_currency();
	_wpsc_fix_slovenia_currency();
	_wpsc_fix_sudan_currency();
	_wpsc_create_south_sudan();
	wpsc_update_permalink_slugs();
}

function _wpsc_fix_checkout_field_limitation() {
	global $wpdb;
	$wpdb->query( "ALTER TABLE " . WPSC_TABLE_CHECKOUT_FORMS . " MODIFY options longtext" );
}

function _wpsc_fix_mexico_currency_sign() {
	$country = new WPSC_Country( array(
		'isocode'     => 'MX',
		'currency'    => __( 'Mexican Peso', 'wp-e-commerce' ),
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
		'has_regions' => '1'
	) );
}

function _wpsc_fix_canadian_province_codes() {
	global $wpdb;

	$correct_provinces = array(
		'AB' => 'Alberta',
		'BC' => 'British Columbia',
		'MB' => 'Manitoba',
		'NB' => 'New Brunswick',
		'NT' => 'Northwest Territories',
		'NS' => 'Nova Scotia',
		'NU' => 'Nunavut',
		'ON' => 'Ontario',
		'PE' => 'Prince Edward Island',
		'QC' => 'Quebec',
		'SK' => 'Saskatchewan',
		'YT' => 'Yukon',
	);

	foreach( $correct_provinces as $code => $name ) {
		$wpdb->update( WPSC_TABLE_REGION_TAX,
			array(
				'code' => $code
			),
			array(
				'name' => $name
			)
		 );
	}

	$wpdb->update( WPSC_TABLE_REGION_TAX,
		array(
			'code' => 'NL',
			'name' => 'Newfoundland and Labrador',
		),
		array(
			'name' => 'Newfoundland',
		)
	);
}

function _wpsc_fix_colombia_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'CO',
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_ukraine_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'UA',
		'code'        => __( 'UAH', 'wp-e-commerce' ),
		'symbol'      => __( '₴', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8372;', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_russia_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'RU',
		'code'        => __( 'RUB', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_belarus_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'BY',
		'code'        => __( 'BYR', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_estonia_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'EE',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_slovenia_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'SI',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_sudan_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'SD',
		'currency' => __( 'Sudanese Pound', 'wp-e-commerce' ),
		'code'     => __( 'SDG', 'wp-e-commerce' )
	) );
}

function _wpsc_create_south_sudan() {
	$country = new WPSC_Country( array(
		'id'        => '242',
		'country'   => __( 'South Sudan', 'wp-e-commerce' ),
		'isocode'   => __( 'SS', 'wp-e-commerce' ),
		'currency'  => __( 'South Sudanese Pound', 'wp-e-commerce' ),
		'code'      => __( 'SSP', 'wp-e-commerce' ),
		'continent' => 'africa',
		'visible'   => 0,
	) );
}