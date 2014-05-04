<?php
/**
 * Control database upgrade to version 11
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_db_upgrade_11() {
	_wpsc_fix_united_kingdom();
	_wpsc_set_legacy_country_meta();
}

/**
 * Reset United Kingdom country data to default, hide ISO code 'UK'
 *
 * @access private
 * @since 3.8.14
 */
function _wpsc_fix_united_kingdom() {

	if ( $wpsc_country = WPSC_Countries::get_country( 'UK' ) ) {

		$legacy_ok_country_was_visible = $wpsc_country->is_visible();

		$wpsc_country = new WPSC_Country(
				array(
						'visible'     => '0',
						'isocode'     => 'UK',
				)
		);

		$wpsc_country->set( '_is_country_legacy', true );
	}

	$wpsc_country = new WPSC_Country(
		array(
				'country'     => __( 'United Kingdom', 'wpsc' ),
				'isocode'     => 'GB',
				'currency'    => __( 'Pound Sterling', 'wpsc' ),
				'symbol'      => __( '£', 'wpsc' ),
				'symbol_html' => __( '&#163;', 'wpsc' ),
				'code'        => __( 'GBP', 'wpsc' ),
				'continent'   => 'europe',
				'visible'     =>  $legacy_ok_country_was_visible ? '0' : '1',
				'has_regions' => '0',
				'tax'         => '0',
		)
	);

	//make sure base country is ok after the UK/GB fix
	$base_country = get_option( 'base_country', '' );
	if ( ! empty( $base_country ) && is_numeric( $base_country ) ) {
		$wpsc_country = new WPSC_Country( $base_country );
		if ( 'UK' == $wpsc_country->get_isocode() ) {
			$wpsc_country = new WPSC_Country( 'GB' );
			update_option( 'base_country' , $wpsc_country->get_id() );
		}
	}

}


/**
 * Sets meta for countries that no longer exist in their former notation to be considered legacy.
 *
 * @access private
 * @since 3.8.14
 */
function _wpsc_set_legacy_country_meta() {
	if ( $wpsc_country = WPSC_Countries::get_country( 'YU' ) ) {
		$wpsc_country->set( '_is_country_legacy', true );
	}

	if ( $wpsc_country = WPSC_Countries::get_country( 'AN' ) ) {
		$wpsc_country->set( '_is_country_legacy', true );
	}

	if ( $wpsc_country = WPSC_Countries::get_country( 'TP' ) ) {
		$wpsc_country->set( '_is_country_legacy', true );
	}
}
