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

// wpec_taxes_ajax_controller

/**
 * Add actions used by wpec-taxes module
 * */
add_action( 'wp_ajax_wpec_taxes_ajax', 'wpec_taxes_ajax_controller' );
?>
