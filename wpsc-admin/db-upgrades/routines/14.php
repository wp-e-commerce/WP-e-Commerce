<?php
/**
 * Control database upgrade to version 14
*
* @access private
* @since 3.9.0
*
*/
function _wpsc_db_upgrade_14() {
	_wpsc_fix_latvia_currency();
}

/**
 * Change Latvian currency to Euro.
 *
 * @access private
 * @since 3.9.0
 */
function _wpsc_fix_latvia_currency() {
	$country = new WPSC_Country( array(
		'isocode'     => 'LV',
		'currency'    => __( 'Euro', 'wpsc' ),
		'symbol'      => __( '€', 'wpsc' ),
		'symbol_html' => __( '&#8364;', 'wpsc' ),
		'code'        => __( 'EUR', 'wpsc' )
	) );

}