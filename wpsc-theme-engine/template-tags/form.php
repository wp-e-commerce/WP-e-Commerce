<?php

function wpsc_get_add_to_cart_form_args( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	$args = array(
		'class'  => 'wpsc-form wpsc-form-horizontal wpsc-add-to-cart-form',
		'action' => wpsc_get_cart_url(),
		'id'     => "wpsc-add-to-cart-form-{$id}",
		'fields' => array(
			array(
				'name'  => 'quantity',
				'type'  => 'textfield',
				'title' => __( 'Quantity', 'wpsc' ),
				'value' => 1,
			),
		),
	);

	$variations = WPSC_Product_Variations::get_instance( $id );
	foreach ( wpsc_get_product_variation_sets() as $variation_set_id => $title ) {
		$variation_terms = $variations->get_variation_terms( $variation_set_id );
		$args['fields'][] = array(
			'name'    => "wpsc_product_variations[{$variation_set_id}]",
			'type'    => 'select',
			'options' => $variation_terms,
			'title'   => $title,
		);
	}

	$args['form_actions'] = array(
		array(
			'type' => 'button',
			'primary' => true,
			'icon'    => array( 'shopping-cart', 'white' ),
			'title'   => apply_filters( 'wpsc_add_to_cart_button_title', __( 'Add to Cart', 'wpsc' ) ),
		),
		array(
			'type'    => 'hidden',
			'name'    => 'product_id',
			'value'   => $id,
		),
		array(
			'type'    => 'hidden',
			'name'    => '_wp_http_referer',
			'value'   => home_url( $_SERVER['REQUEST_URI'] ),
		),
		array(
			'type'    => 'hidden',
			'name'    => 'action',
			'value'   => 'add_to_cart',
		),
		array(
			'type'    => 'hidden',
			'name'    => '_wp_nonce',
			'value'   => wp_create_nonce( "wpsc-add-to-cart-{$id}" ),
		),
	);

	$args = apply_filters( 'wpsc_get_add_to_cart_form_args', $args );
}

function wpsc_get_add_to_cart_form( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	$args = wpsc_get_add_to_cart_form_args( $id );
	return apply_filters( 'wpsc_get_add_to_cart_form', wpsc_get_form_output( $args ) );
}

function wpsc_add_to_cart_form( $id = null ) {
	echo wpsc_get_add_to_cart_form( $id );
}

function wpsc_cart_item_table() {
	require_once( WPSC_FILE_PATH . '/wpsc-theme-engine/class-cart-item-table.php' );
	$cart_item_table = WPSC_Cart_Item_Table::get_instance();
	$cart_item_table->display();
}

function wpsc_get_login_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-login-form',
		'action' => wpsc_get_login_url(),
		'id'     => "wpsc-login-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-login-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username', 'wpsc' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'required',
			),
			array(
				'id'    => 'wpsc-login-password',
				'name'  => 'password',
				'type'  => 'password',
				'title' => __( 'Password', 'wpsc' ),
				'value' => '',
				'rules' => 'required',
			),
			array(
				'id' => 'wpsc-login-remember',
				'name' => 'remember',
				'type' => 'checkbox',
				'title' => __( 'Remember Me', 'wpsc' ),
			),
		),
		'form_actions' => array(
			array(
				'type' => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_login_button_title', __( 'Log in', 'wpsc' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'login',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-log-in" ),
			),
		),
	);

	$args = apply_filters( 'wpsc_get_login_form_args', $args );

	return $args;
}

function wpsc_get_login_form() {
	$args = wpsc_get_login_form_args();
	return apply_filters( 'wpsc_get_login_form', wpsc_get_form_output( $args ) );
}

function wpsc_login_form() {
	echo wpsc_get_login_form();
}

function wpsc_get_register_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-login-form',
		'action' => wpsc_get_register_url(),
		'id'     => "wpsc-login-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-register-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username', 'wpsc' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'trim|required|username|sanitize_username',
			),
			array(
				'id'    => 'wpsc-register-email',
				'name'  => 'email',
				'description' => __( 'A password will be e-mailed to you', 'wpsc' ),
				'type'  => 'textfield',
				'title' => __( 'E-mail', 'wpsc' ),
				'value' => wpsc_submitted_value( 'email' ),
				'rules' => 'trim|required|account_email',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_register_button_title', __( 'Register', 'wpsc' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'register',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-register" ),
			),
		),
	);

	$args = apply_filters( 'wpsc_get_register_form_args', $args );

	return $args;
}

function wpsc_get_register_form() {
	$args = wpsc_get_register_form_args();
	return apply_filters( 'wpsc_get_register_form', wpsc_get_form_output( $args ) );
}

function wpsc_register_form() {
	echo wpsc_get_register_form();
}

function wpsc_get_password_reminder_form_args() {
	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-password-reminder-form',
		'action' => wpsc_get_password_reminder_url(),
		'id'     => "wpsc-password-reminder-form",
		'fields' => array(
			array(
				'id'    => 'wpsc-password-reminder-username',
				'name'  => 'username',
				'type'  => 'textfield',
				'title' => __( 'Username or E-mail', 'wpsc' ),
				'value' => wpsc_submitted_value( 'username' ),
				'rules' => 'trim|required|valid_username_or_email',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_password_reminder_button_title', __( 'Get New Password', 'wpsc' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'new_password',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-password-reminder" ),
			),
		),
	);

	return apply_filters( 'wpsc_get_password_reminder_form_args', $args );
}

function wpsc_get_password_reminder_form() {
	$args = wpsc_get_password_reminder_form_args();
	return apply_filters( 'wpsc_get_password_reminder_form', wpsc_get_form_output( $args ) );
}

function wpsc_password_reminder_form() {
	echo wpsc_get_password_reminder_form();
}

function wpsc_get_password_reset_form_args() {
	global $wpsc_page_instance;
	$username = $wpsc_page_instance->get_arg(0);
	$key = $wpsc_page_instance->get_arg(1);

	$args = array(
		'class'  => 'wpsc-form wpsc-form-vertical wpsc-password-reminder-form',
		'action' => wpsc_get_password_reset_url( $username, $key ),
		'id'     => "wpsc-password-reminder-form",
		'fields' => array(
			array(
				'name'  => 'pass1',
				'type'  => 'password',
				'title' => __( 'New password', 'wpsc' ),
				'value' => wpsc_submitted_value( 'pass1' ),
				'rules' => 'trim|required',
			),
			array(
				'name'  => 'pass2',
				'type'  => 'password',
				'title' => __( 'Confirm new password', 'wpsc' ),
				'value' => wpsc_submitted_value( 'pass2' ),
				'rules' => 'trim|required|matches[pass1]',
			),
		),
		'form_actions' => array(
			array(
				'type'    => 'submit',
				'primary' => true,
				'title'   => apply_filters( 'wpsc_password_reset_button_title', __( 'Reset Password', 'wpsc' ) ),
			),
			array(
				'type'    => 'hidden',
				'name'    => 'action',
				'value'   => 'reset_password',
			),
			array(
				'type'    => 'hidden',
				'name'    => '_wp_nonce',
				'value'   => wp_create_nonce( "wpsc-password-reset" ),
			),
		),
	);

	return $args;
}

function wpsc_get_password_reset_form() {
	$args = wpsc_get_password_reset_form_args();
	return apply_filters( 'wpsc_get_password_reset_form', wpsc_get_form_output( $args ) );
}

function wpsc_password_reset_form() {
	echo wpsc_get_password_reset_form();
}

function wpsc_checkout_details_form_open() {
	do_action( 'wpsc_checkout_details_form_open_before' );
	?>
	<form method="post" action="<?php wpsc_checkout_url(); ?>">
	<?php
	do_action( 'wpsc_checkout_details_form_open_after' );
}

function wpsc_checkout_details_form_close() {
	do_action( 'wpsc_checkout_details_form_close_before' );
	?>
	</form>
	<?php
	do_action( 'wpsc_checkout_details_form_close_after' );
}

function wpsc_checkout_details_form_fields() {
	do_action( 'wpsc_checkout_details_form_fields_before' );
	do_action( 'wpsc_checkout_details_form_fields'        );
	do_action( 'wpsc_checkout_details_form_fields_after'  );
}

function wpsc_checkout_details_form_hidden_fields() {
	?>
	<input type="hidden" name="action" value="validate_details" />
	<?php
}
add_action( 'wpsc_checkout_details_form_fields_after', 'wpsc_checkout_details_form_hidden_fields' );

function wpsc_checkout_details_form_fields_main() {
	$form = WPSC_Checkout_Form::get();
	$form->output_fields();
}
add_action( 'wpsc_checkout_details_form_fields', 'wpsc_checkout_details_form_fields_main' );

function wpsc_checkout_submit_button() {
	?>
	<input type="submit" class="wpsc-checkout-submit-button wpsc-primary-button" name="submit" value="<?php esc_attr_e( 'Continue', 'wpsc' ); ?>" />
	<?php
}

function wpsc_checkout_payment_delivery_form_open() {
	do_action( 'wpsc_checkout_payment_delivery_form_open_before' );
	?>
	<form method="post" action="<?php wpsc_checkout_url(); ?>">
	<?php
	do_action( 'wpsc_checkout_payment_delivery_form_open_after' );
}

function wpsc_checkout_payment_delivery_form_close() {
	do_action( 'wpsc_checkout_payment_delivery_form_close_before' );
	?>
	</form>
	<?php
	do_action( 'wpsc_checkout_payment_delivery_form_close_after' );
}

function wpsc_checkout_payment_delivery_form_fields() {
	do_action( 'wpsc_checkout_payment_delivery_form_fields_before' );
	do_action( 'wpsc_checkout_payment_delivery_form_fields'        );
	do_action( 'wpsc_checkout_payment_delivery_form_fields_after'  );
}
