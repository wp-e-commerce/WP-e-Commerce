<?php

add_action( 'wp_enqueue_scripts', '_wpsc_te2_register_scripts', 1 );

function _wpsc_te2_register_scripts() {

	$engine = WPSC_Template_Engine::get_instance();

	$scripts = apply_filters( 'wpsc_registered_scripts', $engine->get_core_scripts_data() );

	foreach ( $scripts as $handle => $script_data ) {
		wp_register_script(
			$handle,
			wpsc_locate_asset_uri( $script_data['path'] ),
			$script_data['dependencies'],
			$script_data['version'],
			! isset( $script_data['in_footer'] ) || $script_data['in_footer']
		);
	}

	$enqueued = false;

	foreach ( $engine->get_queued_scripts() as $handle => $script_data ) {
		$enqueued = true;

		_wpsc_enqueue_and_localize_script( $handle, $script_data );
	}

	// Output our namespace.
	?><script type='text/javascript'>/* <![CDATA[ */window.WPSC = window.WPSC || {};/* ]]> */</script><?php

	do_action( 'wpsc_register_scripts' );
	do_action( 'wpsc_enqueue_scripts' );
}

function _wpsc_enqueue_shipping_billing_scripts() {
	add_action(
		'wp_enqueue_scripts',
		'_wpsc_action_enqueue_shipping_billing_scripts'
	);
}

function _wpsc_action_enqueue_shipping_billing_scripts() {
	wpsc_enqueue_script( 'wpsc-country-region' );
	wpsc_enqueue_script( 'wpsc-copy-billing-info', array(
		'property_name' => 'copyBilling',
		'data' => array(
			'strings' => array(
				'billing_and_shipping' => apply_filters( 'wpsc_checkout_billing_header_label' , __( '<h2>Billing &amp; Shipping Details</h2>', 'wp-e-commerce' ) ),
				'shipping'             => apply_filters( 'wpsc_checkout_shipping_header_label' , __( '<h2>Shipping Details</h2>', 'wp-e-commerce' ) ),
				'billing'              => apply_filters( 'wpsc_checkout_billing_only_header_label', __( '<h2>Billing Details</h2>', 'wp-e-commerce' ) ),
			),
		),
	) );
}

function _wpsc_enqueue_float_label_scripts() {
    add_action(
        'wp_enqueue_scripts',
        '_wpsc_action_enqueue_float_label_scripts'
    );
}

function _wpsc_action_enqueue_float_label_scripts() {
    wpsc_enqueue_script( 'wpsc-float-labels' );
    wpsc_enqueue_script( 'wpsc-checkout' );
}

function _wpsc_enqueue_product_scripts() {
    add_action(
        'wp_enqueue_scripts',
        '_wpsc_action_enqueue_product_scripts'
    );
}

function _wpsc_action_enqueue_product_scripts() {
    wpsc_enqueue_script( 'wpsc-products' );
}

/**
 * Enqueue a registered wpsc script (and optionally localize its JS data).
 * If script cannot be enqueued yet, register the queued script for later enqueue.
 *
 * @see WPSC_Template_Engine::register_queued_script()
 * @see wp_enqueue_script()
 * @see wpsc_localize_script()
 *
 * @since 4.0
 *
 * @param string $handle      Name of the registered wpsc script.
 * @param array  $script_data (Optional) data to send to wp_localize_script under the WPSC namespace.
 */
function wpsc_enqueue_script( $handle, $script_data = array() ) {
	if ( ! did_action( 'wpsc_enqueue_scripts' ) ) {
		WPSC_Template_Engine::get_instance()->register_queued_script( $handle, $script_data );
	} else {
		_wpsc_enqueue_and_localize_script( $handle, $script_data );
	}
}

/**
 * Enqueue a registered wpsc script (and optionally localize its JS data).
 *
 * @see wp_enqueue_script()
 * @see wpsc_localize_script()
 *
 * @access private
 *
 * @since 4.0
 *
 * @param string $handle      Name of the registered wpsc script.
 * @param array  $script_data (Optional) data to send to wp_localize_script under the WPSC namespace.
 */
function _wpsc_enqueue_and_localize_script( $handle, $script_data = array() ) {
	wp_enqueue_script( $handle );

	if ( ! empty( $script_data ) && isset( $script_data['property_name'], $script_data['data'] ) ) {

		$add_to_namespace = ! isset( $script_data['add_to_namespace'] ) || $script_data['add_to_namespace'];

		wpsc_localize_script(
			$handle,
			$script_data['property_name'],
			$script_data['data'],
			$add_to_namespace
		);
	}
}

/**
 * Localize a script under the WPSC namespace.
 *
 * Works only if the script has already been registered or enqueued.
 *
 * Accepts an associative array $data and creates a JavaScript object:
 *
 *     window.WPSC.{$property_name} = {
 *         key: value,
 *         key: value,
 *         ...
 *     }
 *
 *
 * @see wp_localize_script()
 * @see WP_Dependencies::get_data()
 * @see WP_Dependencies::add_data()
 * @global WP_Scripts $wp_scripts The WP_Scripts object for printing scripts.
 *
 * @since 4.0
 *
 * @param string $handle          Script handle the data will be attached to.
 * @param string $property_name   Name for the property applied to the global WPSC object.
 *                                Passed directly, so it should be qualified JS variable.
 *                                Example: '/[a-zA-Z0-9_]+/'.
 * @param array $data             The data itself. The data can be either a single or multi-dimensional array.
 * @param bool  $add_to_namespace Whether to add to the WPSC object, or default wp_localize_script output.
 *
 * @return bool True if the script was successfully localized, false otherwise.
 */
function wpsc_localize_script( $handle, $property_name, $data, $add_to_namespace = true ) {
	global $wp_scripts;

	if ( $add_to_namespace ) {

		// Make sure this variable does not break the WPSC namespace.
		$property_name = 'WPSC.' . sanitize_html_class( maybe_serialize( $property_name ) );
	}

	$result = wp_localize_script( $handle, $property_name, $data );

	if ( $add_to_namespace ) {

		$script = $wp_scripts->get_data( $handle, 'data' );

		$script = str_replace(
			"var {$property_name} = {",
			"window.{$property_name} = window.{$property_name} || {",
			$script
		);

		$result = $wp_scripts->add_data( $handle, 'data', $script );
	}

	return $result;
}
