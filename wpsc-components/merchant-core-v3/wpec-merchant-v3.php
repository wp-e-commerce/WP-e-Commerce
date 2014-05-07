<?php
/**
  * Plugin Name: WP e-Commerce Merchant API v3
  * Plugin URI: http://getshopped.org/
  * Description: Merchant API v3 to use with WP e-Commerce 3.8.10+
  * Version: 0.1
  * Author: Instinct Entertainment
  * Author URI: http://getshopped.org/
  **/

add_filter( 'wpsc_components', '_wpsc_merchant_v3_register_component' );

function _wpsc_merchant_v3_register_component( $components ) {
	$components['merchant']['core-v3'] = array(
		'title' => __( 'WP e-Commerce Merchant API v2', 'wpsc' ),
			'includes' =>
				dirname( __FILE__ ) . '/merchant-core-v3.php'
	);

	return $components;
}