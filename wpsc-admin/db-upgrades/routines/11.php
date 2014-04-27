<?php
/**
 * Control database upgrade to version 11
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_db_upgrade_11() {
	_wpsc_fixup_united_kingdom();
}


/**
 * Reset United Kingdom country data to default, hide ISO code 'UK'
 *
 * @access private
 * @since 3.8.14
 */
function _wpsc_fixup_united_kingdom() {
	$wpsc_country = new WPSC_Country(
									array(
											'country'     => __( 'United Kingdom', 'wpsc' ),
											'isocode'     => 'GB',
											'currency'    => __( 'Pound Sterling', 'wpsc' ),
											'symbol'      => __( '£', 'wpsc' ),
											'symbol_html' => __( '&#163;', 'wpsc' ),
											'code'        => __( 'GBP', 'wpsc' ),
											'continent'   => 'europe',
											'visible'     => '1',
											'has_regions' => '0',
											'tax'         => '0',
										)
									);

	if ( WPSC_Countries::get_country( 'UK' ) ) {
			$wpsc_country = new WPSC_Country(
											array(
													'isocode'     => 'UK',
													'visible'     => '0',
											)
										);

			$wpsc_country->set( 'country-is-legacy-hide-from-lists', true );
	}

}
