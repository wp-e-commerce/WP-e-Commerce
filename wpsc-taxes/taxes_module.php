<?php

/**
 * Load required files
 * */
require_once WPSC_FILE_PATH.'/wpsc-taxes/models/taxes.class.php';
require_once WPSC_FILE_PATH.'/wpsc-taxes/controllers/taxes_controller.class.php';

function wpsc_include_taxes_js() {
  $version_identifier = WPSC_VERSION . "." . WPSC_MINOR_VERSION;
  //include required js file
  wp_enqueue_script( 'wp-e-commerce-taxes-functions', WPSC_URL . '/wpsc-taxes/view/js/taxes-functions.js', array( 'wp-e-commerce-admin' ), $version_identifier, false );
}
add_action( 'admin_enqueue_scripts', 'wpsc_include_taxes_js' );

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
				'class' => 'region'
			);
			$returnable = $wpec_taxes_controller->wpec_taxes_build_select_options( $regions, 'region_code', 'name', $default_option, $select_settings );
			break;
		case 'wpec_taxes_build_rate_form':
			$key = $_REQUEST['current_key'];
			$returnable = $wpec_taxes_controller->wpec_taxes_build_form( $key );
			break;
		case 'wpec_taxes_build_band_form':
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
 * @description: wpec_submit_taxes_options - filters the options submitted in $_POST. Uses
 *                                           wpsc_submit_options to submit filtered array.
 * @param: void
 * @return: null
 * */
function wpec_submit_taxes_options() {
	//define the name of the checkbox options
	$taxes_check_options = array( 'wpec_taxes_enabled' );

	//check if checkbox options are checked and modify post output
	foreach ( $taxes_check_options as $option ) {
		$_POST['wpsc_options'][$option] = (isset( $_POST['wpsc_options'][$option] )) ? 1 : 0;
	}// foreach
	//currently there are two types - bands and rates
	$taxes_rates_types = array( 'rates', 'bands' );

	foreach ( $taxes_rates_types as $taxes_type ) {
		$saved_rates = array( ); //keep track of saved rates
		$exists = array( ); //keep track of what rates or names have been saved
		//check the rates
		if ( isset( $_POST['wpsc_options']['wpec_taxes_' . $taxes_type] ) ) {
			foreach ( $_POST['wpsc_options']['wpec_taxes_' . $taxes_type] as $tax_rate ) {
				if( !isset( $tax_rate['region_code'] ) )
					$tax_rate['region_code'] = '';

				//if there is no country then skip
				if ( empty( $tax_rate['country_code'] ) ) {
					continue;
				}

				//bands - if the name already exists then skip - if not save it
				if ( $taxes_type == 'bands' ) {
					if ( empty( $tax_rate['name'] ) || in_array( $tax_rate['name'], $exists ) || $tax_rate['name'] == 'Disabled' ) {
						continue;
					} else {
						$exists[] = $tax_rate['name'];
						$saved_rates[] = $tax_rate;
					}// if
				}// if
				//rates - check the shipping checkbox
				if ( $taxes_type == 'rates' ) {
					//if there is no rate then skip
					if ( empty( $tax_rate['rate'] ) ) {
						continue;
					}

					$tax_rate['shipping'] = (isset( $tax_rate['shipping'] )) ? 1 : 0;

					//check if country exists
					if ( array_key_exists( $tax_rate['country_code'], $exists ) ) {
						//if region already exists skip
						if ( array_search( $tax_rate['region_code'], $exists[$tax_rate['country_code']] ) == $tax_rate['country_code'] ) {
							continue;
						} else {
							//it's not in the array add it
							$exists[$tax_rate['country_code']][] = $tax_rate['region_code'];

							//save it
							$saved_rates[] = $tax_rate;
						}// if
					} else {
						//add codes to exists array
						$exists[$tax_rate['country_code']][] = $tax_rate['region_code'];

						//save it
						$saved_rates[] = $tax_rate;
					}// if
				}// if
			}// foreach
		}// if
		//replace post tax rates with filtered rates
		$_POST['wpsc_options']['wpec_taxes_' . $taxes_type] = $saved_rates;
	}// foreach
	//submit options using built in functions
	wpsc_submit_options();
}

// wpec_submit_taxes_options

/**
 * Add actions used by wpec-taxes module
 * */
add_action( 'wp_ajax_wpec_taxes_ajax', 'wpec_taxes_ajax_controller' );

if ( isset( $_REQUEST['wpec_admin_action'] ) && $_REQUEST['wpec_admin_action'] == 'submit_taxes_options' ) {
	add_action( 'admin_init', 'wpec_submit_taxes_options' );
}
?>
