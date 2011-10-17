<?php

/**
 * Load required files
 * */
require_once WPSC_FILE_PATH.'/wpsc-taxes/models/taxes.class.php';
require_once WPSC_FILE_PATH.'/wpsc-taxes/controllers/taxes_controller.class.php';

/**
 * @description: wpec_taxes_settings_page - used by wpec to display the admin settings page.
 * @param: void
 * @return: null;
 * */
function wpec_taxes_settings_page() {
	require_once WPSC_FILE_PATH.'/wpsc-admin/includes/settings-pages/taxes.php';
	wpec_options_taxes();
}

// wpec_taxes_settings_page

/**
 * @description: wpec_taxes_ajax_controller - controller for any ajax
 *               functions needed for wpec_taxes
 * @param: void
 * @return: null
 * */
function wpec_taxes_ajax_controller() {
	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpsc_settings_page_nonce' ) )
		die( 'Session expired. Try refreshing your settings page.' );

	//include taxes controller
	$wpec_taxes_controller = new wpec_taxes_controller;

	switch ( $_REQUEST['wpec_taxes_action'] ) {
		case 'wpec_taxes_get_regions':
			$regions = $wpec_taxes_controller->wpec_taxes->wpec_taxes_get_regions( $_REQUEST['country_code'] );
			$key = $_REQUEST['current_key'];
			$type = $_REQUEST['taxes_type'];

			$default_option = array( 'region_code' => 'all-markets', 'name' => 'All Markets' );
			$select_settings = array(
				'id' => "{$type}-region-{$key}",
				'name' => "wpsc_options[wpec_taxes_{$type}][{$key}][region_code]",
				'class' => 'wpsc-taxes-region-drop-down'
			);
			$returnable = $wpec_taxes_controller->wpec_taxes_build_select_options( $regions, 'region_code', 'name', $default_option, $select_settings );
			break;
		case 'wpec_taxes_build_rates_form':
			$key = $_REQUEST['current_key'];
			$returnable = $wpec_taxes_controller->wpec_taxes_build_form( $key );
			break;
		case 'wpec_taxes_build_bands_form':
			$key = $_REQUEST['current_key'];
			//get a new key if a band is already defined for this key
			while($wpec_taxes_controller->wpec_taxes->wpec_taxes_get_band_from_index($key))
			{
				$key++;
			}
			$returnable = $wpec_taxes_controller->wpec_taxes_build_form( $key, false, 'bands' );
			break;
	}// switch
	//return the results
	echo $returnable;

	//die to avoid default 0 in ajax response
	die();
}

// wpec_taxes_ajax_controller

/**
 * Add actions used by wpec-taxes module
 * */
add_action( 'wp_ajax_wpec_taxes_ajax', 'wpec_taxes_ajax_controller' );
?>
