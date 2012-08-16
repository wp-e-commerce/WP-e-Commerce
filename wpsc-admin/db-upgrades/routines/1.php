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
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'currency'    => __( 'Mexican Peso', 'wpsc' ),
			'symbol'      => __( '$', 'wpsc' ),
			'symbol_html' => __( '&#036;', 'wpsc' ),
			'has_regions' => '1'
		),
		array(
			'id' => '141'
		)
	);
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
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'symbol'      => __( '$', 'wpsc' ),
			'symbol_html' => __( '&#036;', 'wpsc' ),
		),
		array(
			'id' => 90,
		)
	);
}

function _wpsc_fix_ukraine_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'code'        => __( 'UAH', 'wpsc' ),
			'symbol'      => __( '₴', 'wpsc' ),
			'symbol_html' => __( '&#8372;', 'wpsc' ),
		),
		array(
			'id' => '225',
		)
	 );
}

function _wpsc_fix_russia_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'code' => __( 'RUB', 'wpsc' ),
		),
		array(
			'id' => '180',
		)
	 );
}

function _wpsc_fix_belarus_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'code'    => __( 'BYR', 'wpsc' )
		),
		array(
			'id' => '117'
		)
	 );
}

function _wpsc_fix_estonia_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'currency'    => __( 'Euro', 'wpsc' ),
			'symbol'      => __( '€', 'wpsc' ),
			'symbol_html' => __( '&#8364;', 'wpsc' ),
			'code'        => __( 'EUR', 'wpsc' )
		),
		array(
			'id' => '70'
		)
	 );
}

function _wpsc_fix_slovenia_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'currency'    => __( 'Euro', 'wpsc' ),
			'symbol'      => __( '€', 'wpsc' ),
			'symbol_html' => __( '&#8364;', 'wpsc' ),
			'code'        => __( 'EUR', 'wpsc' )
		),
		array(
			'id' => '196'
		)
	 );
}

function _wpsc_fix_sudan_currency() {
	global $wpdb;
	$wpdb->update( WPSC_TABLE_CURRENCY_LIST,
		array(
			'currency'    => __( 'Sudanese Pound', 'wpsc' ),
			'code' => __( 'SDG', 'wpsc' )
		),
		array(
			'id' => '203'
		)
	 );
}

function _wpsc_create_south_sudan() {
	global $wpdb;
	$wpdb->insert( WPSC_TABLE_CURRENCY_LIST,
		array(
			'id'        => '242',
			'country'   => __( 'South Sudan', 'wpsc' ),
			'isocode'   => __( 'SS', 'wpsc' ),
			'currency'  => __( 'South Sudanese Pound', 'wpsc' ),
			'code'      => __( 'SSP', 'wpsc' ),
			'continent' => 'africa',
		)
	 );
}