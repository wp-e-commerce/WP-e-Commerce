<?php

function wpsc_get_add_to_cart_form( $id = null ) {
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

	$output = '<input type="hidden" name="product_id" value="' . esc_attr( $id ) . '" />';
	$output .= '<input type="hidden" name="prev"       value="' . esc_attr( home_url( $_SERVER['REQUEST_URI'] ) ) . '" />';
	$output .=  '<input type="hidden" name="action"     value="add_to_cart" />';

	$args['form_actions'] = array(
		/* array(
			'type'    => 'submit',
			'primary' => true,
			'title'   => apply_filters( 'wpsc_add_to_cart_button_title', __( 'Add to Cart', 'wpsc' ) ),
		), */
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

function wpsc_login_button() {
	?>
	<input type="submit" class="wpsc-login-button wpsc-primary-button" name="submit" value="<?php esc_attr_e( 'Log in', 'wpsc' ); ?>" />
	<?php
}

function wpsc_password_reminder_button() {
	?>
	<input type="submit" class="wpsc-password-reminder-button wpsc-primary-button" name="submit" value="<?php esc_attr_e( 'Get New Password', 'wpsc' ); ?>" />
	<?php
}

function wpsc_login_form_open() {
	do_action( 'wpsc_login_form_open_before' );
	?>
	<form method="post" action="<?php wpsc_login_url(); ?>">
	<?php
	do_action( 'wpsc_login_form_open_after' );
}

function wpsc_login_form_close() {
	do_action( 'wpsc_login_form_close_before' );
	echo '</form>';
	do_action( 'wpsc_login_form_close_after' );
}

function wpsc_login_form_fields() {
	do_action( 'wpsc_login_form_fields_before' );
	do_action( 'wpsc_login_form_fields'        );
	do_action( 'wpsc_login_form_fields_after'  );
}

function wpsc_login_form_fields_main() {
	wpsc_get_template_part( 'form-login-fields' );
}
add_action( 'wpsc_login_form_fields', 'wpsc_login_form_fields_main' );

function wpsc_password_reminder_form_fields() {
	do_action( 'wpsc_password_reminder_form_fields_before' );
	do_action( 'wpsc_password_reminder_form_fields'        );
	do_action( 'wpsc_password_reminder_form_fields_after'  );
}

function wpsc_password_reminder_form_fields_main() {
	wpsc_get_template_part( 'form-password-reminder-fields' );
}
add_action( 'wpsc_password_reminder_form_fields', 'wpsc_password_reminder_form_fields_main' );

function wpsc_password_reminder_form_open() {
	do_action( 'wpsc_password_reminder_form_open_before' );
	?>
	<form method="post" action="<?php wpsc_password_reminder_url(); ?>">
	<?php
	do_action( 'wpsc_password_reminder_form_open_after' );
}

function wpsc_password_reminder_form_close() {
	do_action( 'wpsc_password_reminder_form_close_before' );
	echo '</form>';
	do_action( 'wpsc_password_reminder_form_close_after' );
}

function wpsc_password_reminder_reset_form_open() {
	$uri = '';
	if ( wpsc_is_password_reminder( 'reset' ) )
		$uri = wpsc_get_password_reminder_url( get_query_var( 'wpsc_callback' ) );

	do_action( 'wpsc_password_reminder_reset_form_open_before' );
	?>
	<form method="post" action="<?php echo esc_url( $uri ); ?>">
	<?php
	do_action( 'wpsc_password_reminder_reset_form_open_after' );
}

function wpsc_password_reminder_reset_form_close() {
	do_action( 'wpsc_password_reminder_reset_form_close_before' );
	echo '</form>';
	do_action( 'wpsc_password_reminder_reset_form_close_after' );
}

function wpsc_password_reminder_reset_form_fields() {
	do_action( 'wpsc_password_reminder_reset_form_fields_before' );
	do_action( 'wpsc_password_reminder_reset_form_fields'        );
	do_action( 'wpsc_password_reminder_reset_form_fields_after'  );
}

function wpsc_password_reminder_reset_form_hidden_fields() {
	?>
	<input type="hidden" name="action" value="reset_password" />
	<?php
}
add_action( 'wpsc_password_reminder_reset_form_fields_after', 'wpsc_password_reminder_reset_form_hidden_fields' );

function wpsc_password_reminder_reset_form_fields_main() {
	wpsc_get_template_part( 'form-password-reminder-reset-fields' );
}
add_action( 'wpsc_password_reminder_reset_form_fields', 'wpsc_password_reminder_reset_form_fields_main' );

function wpsc_reset_password_button() {
	?>
	<input type="submit" class="wpsc-password-reminder-reset-button wpsc-primary-button" name="submit" value="<?php esc_attr_e( 'Reset Password', 'wpsc' ); ?>" />
	<?php
}

function wpsc_register_form_open() {
	do_action( 'wpsc_register_form_open_before' );
	?>
	<form method="post" action="<?php wpsc_register_url(); ?>">
	<?php
	do_action( 'wpsc_register_form_open_after' );
}

function wpsc_register_form_close() {
	do_action( 'wpsc_register_form_close_before' );
	?>
	</form>
	<?php
	do_action( 'wpsc_register_form_close_after' );
}

function wpsc_register_form_fields() {
	do_action( 'wpsc_register_form_fields_before' );
	do_action( 'wpsc_register_form_fields'        );
	do_action( 'wpsc_register_form_fields_after'  );
}

function wpsc_register_form_fields_main() {
	wpsc_get_template_part( 'form-register-fields' );
}
add_action( 'wpsc_register_form_fields', 'wpsc_register_form_fields_main' );

function wpsc_register_button() {
	?>
	<input type="submit" class="wpsc-register-button wpsc-primary-button" name="submit" value="<?php esc_attr_e( 'Register', 'wpsc' ); ?>" />
	<?php
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
