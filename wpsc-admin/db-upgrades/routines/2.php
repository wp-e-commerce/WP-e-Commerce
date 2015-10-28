<?php

function _wpsc_db_upgrade_2() {
	_wpsc_fix_UK_country_code();
	_wpsc_fix_guernsey_country_code();
	_wpsc_new_country_serbia();
	_wpsc_new_country_montenegro();
	_wpsc_fix_timor_leste_name();
	_wpsc_new_country_aland_islands();
	_wpsc_new_country_saint_barthelemy();
	_wpsc_new_country_bonaire_et_al();
	_wpsc_new_country_curacao();
	_wpsc_new_country_saint_martin_french();
	_wpsc_new_country_palestinian_territories();
	_wpsc_update_israeli_new_shekel_symbol();
	_wpsc_new_country_sint_maarten_dutch();
	_wpsc_new_country_french_guiana();
	_wpsc_fix_netherlands_antille();
	_wpsc_fix_angola_kwanza();
	_wpsc_fix_aruban_florin();
	_wpsc_fix_azerbaijani_manat();
	_wpsc_fix_cyprus_currency();
	_wpsc_fix_republic_of_the_congo();
	_wpsc_fix_currency_el_salvador();
	_wpsc_fix_ghanaian_currency_code();
	_wpsc_fix_guatemala_currency();
	_wpsc_fix_guinea_bissau_currency();
	_wpsc_fix_madagascar_currency();
	_wpsc_fix_malta_currency();
	_wpsc_fix_mozambique_currency();
	_wpsc_fix_nicaragua_currency();
	_wpsc_fix_romania_currency();
	_wpsc_fix_san_marino_currency();
	_wpsc_fix_somalia_currency();
	_wpsc_fix_suriname_currency();
	_wpsc_fix_taiwan_currency();
	_wpsc_fix_tajikistan_currency();
	_wpsc_fix_tunisia_currency();
	_wpsc_fix_uganda_currency();
	_wpsc_fix_turkey_currency();
	_wpsc_fix_uruguay_currency();
	_wpsc_fix_venezuela_currency();
	_wpsc_fix_zimbabwe_currency();
}

function _wpsc_fix_UK_country_code() {
	$country = new WPSC_Country( array(
		'isocode' => 'GB',
		'country', __( 'United Kingdom', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_guernsey_country_code() {
	$existing_wpsc_country = new WPSC_Country( 'GF' );
	// replace the ISO country code in the existing country
	$updated_wpsc_country = new WPSC_Country( array( 'id' => $existing_wpsc_country->get_id(), 'isocode' => 'GG', ) );
}

function _wpsc_new_country_serbia() {
	$country = new WPSC_Country( array(
		'id'        => 243,
		'country'   => __( 'Serbia', 'wp-e-commerce' ),
		'isocode'   => 'RS',
		'currency'  => __('Serbian Dinar', 'wp-e-commerce'),
		'code'      => __('RSD', 'wp-e-commerce'),
		'continent' => 'europe',
		'visible'   => '0',
	) );
}

function _wpsc_new_country_montenegro() {
	$country = new WPSC_Country( array(
		'id'          => 244,
		'country'     => __( 'Montenegro', 'wp-e-commerce' ),
		'isocode'     => 'ME',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
		'continent'   => 'europe',
		'visible'     => '0',
	) );
}

function _wpsc_fix_timor_leste_name() {
	$country = new WPSC_Country( array(
		'id'          => 245,
		'country'     => __( 'Timor-Leste', 'wp-e-commerce' ),
		'isocode'     => 'TL',
		'currency'    => __( 'US Dollar', 'wp-e-commerce' ),
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
		'code'        => 'USD',
		'continent'   => 'asiapacific',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_aland_islands() {
	$country = new WPSC_Country( array(
		'id'          => 246,
		'country'     => __( 'Aland Islands', 'wp-e-commerce' ),
		'isocode'     => 'AX',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
		'continent'   => 'europe',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_saint_barthelemy() {
	$country = new WPSC_Country( array(
		'id'          => 247,
		'country'     => __( 'Saint Barthelemy', 'wp-e-commerce' ),
		'isocode'     => 'BL',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
		'continent'   => 'europe',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_bonaire_et_al() {
	$country = new WPSC_Country( array(
		'id'          => 248,
		'country'     => __( 'Bonaire, Sint Eustatius and Saba', 'wp-e-commerce' ),
		'isocode'     => 'BQ',
		'currency'    => __( 'US Dollar', 'wp-e-commerce' ),
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
		'code'        => __( 'USD', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_curacao() {
	$country = new WPSC_Country( array(
		'id'          => 249,
		'country'     => __( 'Curacao', 'wp-e-commerce' ),
		'isocode'     => 'CW',
		'currency'    => __( 'Netherlands Antillean Guilder', 'wp-e-commerce' ),
		'symbol'      => __( 'ƒ', 'wp-e-commerce' ),
		'symbol_html' => __( '&#402;', 'wp-e-commerce' ),
		'code'        => __( 'ANG', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_saint_martin_french() {
	$country = new WPSC_Country( array(
		'id'          => 250,
		'country'     => __( 'Saint Martin (French Part)', 'wp-e-commerce' ),
		'isocode'     => 'MF',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_palestinian_territories() {
	$country = new WPSC_Country( array(
		'id'          => 251,
		'country'     => __( 'Palestinian Territories', 'wp-e-commerce' ),
		'isocode'     => 'PS',
		'currency'    => __( 'Israeli New Shekel', 'wp-e-commerce' ),
		'symbol'      => __( '₪', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8362;', 'wp-e-commerce' ),
		'code'        => __( 'ILS', 'wp-e-commerce' ),
		'continent'   => 'asiapacific',
		'visible'     => '0',
	) );
}

function _wpsc_update_israeli_new_shekel_symbol() {
	$country = new WPSC_Country( array(
		'isocode'     => 'IL',
		'symbol'      => __( '₪', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8362;', 'wp-e-commerce' ),
	) );
}

function _wpsc_new_country_sint_maarten_dutch() {
	$country = new WPSC_Country( array(
		'id'          => 252,
		'country'     => __( 'Sint Maarten (Dutch Part)', 'wp-e-commerce' ),
		'isocode'     => 'SX',
		'currency'    => __( 'Netherlands Antillean Guilder', 'wp-e-commerce' ),
		'symbol'      => __( 'ƒ', 'wp-e-commerce' ),
		'symbol_html' => __( '&#402;', 'wp-e-commerce' ),
		'code'        => __( 'ANG', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
		'visible'     => '0',
	) );
}

function _wpsc_new_country_french_guiana() {
	$country = new WPSC_Country( array(
		'id'          => 253,
		'country'     => __( 'French Guiana', 'wp-e-commerce' ),
		'isocode'     => 'GF',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
		'visible'     => '0',
	) );
}

function _wpsc_fix_netherlands_antille() {
	$country = new WPSC_Country( array(
		'isocode'     => 'AN',
		'symbol'      => __( 'ƒ', 'wp-e-commerce' ),
		'symbol_html' => __( '&#402;', 'wp-e-commerce' ),
		'continent'   => 'southamerica',
	) );
}

function _wpsc_fix_angola_kwanza() {
	$country = new WPSC_Country( array(
		'isocode'     => 'AO',
		'code'        => 'AOA',
		'currency'    => __( 'Angolan Kwanza', 'wp-e-commerce' ),
		'symbol'      => __( 'Kz', 'wp-e-commerce' ),
		'symbol_html' => __( 'Kz', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_aruban_florin() {
	$country = new WPSC_Country( array(
		'isocode'     => 'AW',
		'currency'    => __( 'Aruban Florin', 'wp-e-commerce' ),
		'symbol'      => __( 'Afl.', 'wp-e-commerce' ),
		'symbol_html' => __( 'Afl.', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_azerbaijani_manat() {
	$country = new WPSC_Country( array(
		'isocode'     => 'AZ',
		'currency'    => __('Azerbaijani Manat', 'wp-e-commerce'),
		'code'        => 'AZN',
		'symbol'      => _x( 'm', 'azerbaijani manat symbol', 'wp-e-commerce' ),
		'symbol_html' => _x( 'm', 'azerbaijani manat symbol html', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_cyprus_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'CY',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_republic_of_the_congo() {
	$country = new WPSC_Country( array(
		'isocode' => 'CG',
		'country' => __( 'Republic of the Congo', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_currency_el_salvador() {
	$country = new WPSC_Country( array(
		'isocode'     => 'SV',
		'currency'    => __( 'US Dollar', 'wp-e-commerce' ),
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
		'code'        => 'USD',
	) );
}

function _wpsc_fix_ghanaian_currency_code() {
	$country = new WPSC_Country( array(
		'isocode' => 'GH',
		'code' => 'GHS',
	) );
}

function _wpsc_fix_guatemala_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'GT',
		'code' => 'GTQ',
	) );
}

function _wpsc_fix_guinea_bissau_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'GW',
		'currency' => __( 'CFA Franc BEAC', 'wp-e-commerce' ),
		'code'     => __('XAF', 'wp-e-commerce'),
	) );
}

function _wpsc_fix_madagascar_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'MG',
		'currency' => __( 'Malagasy Ariary', 'wp-e-commerce' ),
		'code'     => __( 'MGA', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_malta_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'MT',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_mozambique_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'MZ',
		'code'    => __( 'MZN', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_nicaragua_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'NI',
		'code'    => __( 'NIO', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_romania_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'RO',
		'currency' => __( 'Romanian New Leu', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_san_marino_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'SM',
		'currency'    => __( 'Euro', 'wp-e-commerce' ),
		'symbol'      => __( '€', 'wp-e-commerce' ),
		'symbol_html' => __( '&#8364;', 'wp-e-commerce' ),
		'code'        => __( 'EUR', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_somalia_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'SO',
		'code'    => __( 'SOS', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_suriname_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'SR',
		'currency' => __( 'Surinamese Dollar', 'wp-e-commerce' ),
		'code' => __( 'SRD', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_taiwan_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'TW',
		'currency' => __( 'New Taiwanese Dollar', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_tajikistan_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'TJ',
		'currency' => __( 'Tajikistan Somoni', 'wp-e-commerce' ),
		'code'     => __( 'TJS', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_tunisia_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'TN',
		'currency' => __( 'Tunisian Dollar', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_turkey_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'TR',
		'code'    => __( 'TRY', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_uganda_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'UG',
		'code'    => __( 'UGX', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_uruguay_currency() {
	$country = new WPSC_Country( array(
		'isocode' => 'UY',
		'code'    => __( 'UYU', 'wp-e-commerce' )
	) );
}

function _wpsc_fix_venezuela_currency() {
	$country = new WPSC_Country( array(
		'isocode'  => 'VE',
		'currency' => __( 'Venezuelan Bolivar Fuerte', 'wp-e-commerce' ),
		'code'     => __( 'VEF', 'wp-e-commerce' ),
	) );
}

function _wpsc_fix_zimbabwe_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'ZW',
		'currency'    => __( 'US Dollar', 'wp-e-commerce' ),
		'symbol'      => __( '$', 'wp-e-commerce' ),
		'symbol_html' => __( '&#036;', 'wp-e-commerce' ),
		'code'        => 'USD',
		'continent'   => 'asiapacific',
	) );
}
