<?php
/**
 * Control database upgrade to version 11
*
* @access private
* @since 3.8.14
*
*/
function _wpsc_db_upgrade_13() {
	_wpsc_add_region_labe_to_uk();
}

/**
 * add the county region label to the uk
 *
 * @access private
 * @since 3.8.14.1
 */
function _wpsc_add_region_labe_to_uk() {
	$wpsc_country = new WPSC_Country( 'GB' );
	$wpsc_country->set( 'region_label', __( 'County', 'wpsc' ) );
}
