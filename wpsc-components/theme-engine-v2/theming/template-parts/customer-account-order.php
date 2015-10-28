<?php
/**
 * The template part for displaying the single order view in the customer account.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/customer-account-order.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>
<div class="wpsc-customer-account-order">
	<?php wpsc_breadcrumb(); ?>
	<p>
		<strong><?php echo esc_html_x( 'Date:', 'customer account template', 'wp-e-commerce' ); ?></strong>
		<?php wpsc_customer_account_order_date(); ?>
	</p>
	<p><strong><?php echo esc_html_x( 'Cart', 'customer account template', 'wp-e-commerce' ); ?></strong></p>
	<?php wpsc_customer_account_cart_items(); ?>
	<?php wpsc_customer_account_order_details(); ?>
</div>