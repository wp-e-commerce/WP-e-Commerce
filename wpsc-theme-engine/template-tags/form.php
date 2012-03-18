<?php

function wpsc_add_to_cart_form_open() {
	do_action( 'wpsc_add_to_cart_form_open_before' );
	?>
	<form action="<?php echo wpsc_get_cart_url(); ?>" method="post">
	<?php
	do_action( 'wpsc_add_to_cart_form_open_after' );
}

function wpsc_add_to_cart_form_close() {
	do_action( 'wpsc_add_to_cart_form_close_before' );
	?>
	</form>
	<?php
	do_action( 'wpsc_add_to_cart_form_close_after' );
}

function wpsc_add_to_cart_form_fields( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	do_action( 'wpsc_add_to_cart_form_fields_before', $id );
	do_action( 'wpsc_add_to_cart_form_fields'       , $id );
	do_action( 'wpsc_add_to_cart_form_fields_after' , $id );
}

/**
 * Output or return the HTML of the "Add to Cart" button of a product.
 *
 * @since 4.0
 * @uses  apply_filters() Applies 'wpsc_product_add_to_cart_button_title' filter.
 * @uses  apply_filters() Applies 'wpsc_product_add_to_cart_button'       filter.
 * @uses  wpsc_get_product_id()
 *
 * @param  null|string $title Optional. Title of the button. Defaults to "Add to Cart'."
 * @param  null|int    $id    Optional. The product ID. Defaults to current product in the loop.
 * @param  bool        $echo  Optional. Whether to echo the HTML or to return it. Defaults to true.
 * @return null|string
 */
function wpsc_product_add_to_cart_button( $title = null, $id = null, $echo = true ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	if ( ! $title )
		$title = _x( 'Add to Cart', 'product add to cart button', 'wpsc' );

	$title  = apply_filters( 'wpsc_product_add_to_cart_button_title', $title, $id );
	$output = '<input class="wpsc-product-add-to-cart-button wpsc-primary-button" id="wpsc-product-add-to-cart-button-' . $id . '" type="submit" value="' . esc_attr( $title ) . '" />';
	$output = apply_filters( 'wpsc_product_add_to_cart_button', $output, $title, $id );
	if ( $echo )
		echo $output;
	else
		return $output;
}

/**
 * Output the hidden field for a product id.
 *
 * This function is attached to 'wpsc_product_add_to_cart_actions_after'.
 *
 * @since 4.0
 * @uses wpsc_get_product_id()
 *
 * @param  null|int $id Optional. The product ID. Defaults to the current product in the loop.
 */
function wpsc_product_add_to_cart_hidden_fields( $id = null ) {
	if ( ! $id )
		$id = wpsc_get_product_id();

	echo '<input type="hidden" name="product_id" value="' . esc_attr( $id ) . '" />';
	echo '<input type="hidden" name="prev"       value="' . esc_attr( home_url( $_SERVER['REQUEST_URI'] ) ) . '" />';
	echo '<input type="hidden" name="action"     value="add_to_cart" />';
}
add_action( 'wpsc_product_add_to_cart_actions_after', 'wpsc_product_add_to_cart_hidden_fields', 10, 1 );

function wpsc_product_variation_dropdowns() {
	wpsc_get_template_part( 'variations', 'product-catalog' );
}
add_action( 'wpsc_add_to_cart_form_fields', 'wpsc_product_variation_dropdowns' );

function wpsc_product_quantity_field() {
	?>
		<p>
			<label for="wpsc-product-add-to-cart-quantity-<?php wpsc_product_id(); ?>">
				<?php echo esc_html_x( 'Quantity', 'theme add to cart form', 'wpsc' ); ?>:
			</label>
			<input type="text" name="quantity" class="wpsc-product-add-to-cart-quantity wpsc-textfield" id="wpsc-product-add-to-cart-quantity-<?php wpsc_product_id(); ?>" value="1" />
		</p>
	<?php
}
add_action( 'wpsc_add_to_cart_form_fields', 'wpsc_product_quantity_field' );

function wpsc_cart_form_open() {
	do_action( 'wpsc_cart_form_open_before' );
	?>
	<form action="<?php echo wpsc_get_cart_url(); ?>" method="post">
	<?php
	do_action( 'wpsc_cart_form_open_after' );
}

function wpsc_cart_form_close() {
	?>
	</form>
	<?php
}

function wpsc_cart_item_table() {
	require_once( WPSC_FILE_PATH . '/wpsc-theme-engine/class-cart-item-table.php' );
	$cart_item_table = WPSC_Cart_Item_Table::get_instance();
	$cart_item_table->display();
}

function wpsc_keep_shopping_button() {
	$url = isset( $_REQUEST['prev'] ) ? esc_attr( $_REQUEST['prev'] ) : wpsc_get_catalog_url();
	?>
	<a class="wpsc-back-to-shopping" href="<?php echo $url; ?>"><?php esc_html_e( 'Keep Shopping' ); ?></a>
	<?php
}

function wpsc_begin_checkout_button() {
	?>
	<input type="submit" class="wpsc-begin-checkout wpsc-primary-button" name="begin_checkout" value="<?php esc_attr_e( 'Begin Checkout', 'wpsc' ); ?>" />
	<?php
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
