<?php
/**
 * The template part for displaying the review order view in the checkout process.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/checkout-review-order.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>
<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-shipping-method">
	<h3><?php esc_html_e( 'Your Order', 'wp-e-commerce' ); ?></h3>
	<div class="wpsc-order-preview">
		<?php wpsc_checkout_order_preview(); ?>
	</div>
	<h3><?php esc_html_e( 'Customer Details', 'wp-e-commerce' ); ?></h3>
	<?php wpsc_checkout_customer_details(); ?>
	<h3><?php esc_html_e( 'Select a Shipping Method', 'wp-e-commerce' ); ?></h3>
	<?php wpsc_checkout_shipping_form(); ?>
</div>
