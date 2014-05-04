<?php

function wpsc_save_customer_details( $customer_details ) {
	$customer_details = apply_filters( 'wpsc_update_customer_checkout_details', $customer_details );
	// legacy filter
	if ( is_user_logged_in() )
		$customer_details = apply_filters( 'wpsc_checkout_user_profile_update', $customer_details, get_current_user_id() );
	wpsc_update_customer_meta( 'checkout_details', $customer_details );
}

function _wpsc_copy_billing_details() {
	$form = WPSC_Checkout_Form::get();
	$fields = $form->get_fields();
	$fields_to_copy = array(
		'firstname',
		'lastname',
		'address',
		'city',
		'state',
		'country',
		'postcode',
	);

	$field_ids = array(
		'shipping' => array(),
		'billing' => array()
	);

	foreach ( $fields as $field ) {
		if (
			   ! empty( $field->unique_name )
			&& preg_match( '/^(billing|shipping)(.+)/', $field->unique_name, $matches )
			&& in_array( $matches[2], $fields_to_copy )
		) {
			$field_ids[$matches[1]][$matches[2]] = $field->id;
		}
	}

	$post_data =& $_POST['wpsc_checkout_details'];
	foreach ( $field_ids['shipping'] as $name => $id ) {
		$billing_field_id = $field_ids['billing'][$name];
		$post_data[$id] = $post_data[$billing_field_id];
	}
}

function _wpsc_update_location() {
	global $wpsc_cart;

	$wpsc_cart->update_location();
	$wpsc_cart->get_shipping_method();
	$wpsc_cart->get_shipping_option();
}