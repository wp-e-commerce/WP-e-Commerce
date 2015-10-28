<?php
/**
 * The template part for displaying the shipping method view in the checkout process.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/checkout-shipping-method.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_checkout_steps(); ?>
<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-shipping-method">
	<p><strong class="wpsc-large"><?php esc_html_e( 'Your cart', 'wp-e-commerce' ); ?></strong></p>
	<div class="wpsc-order-preview">
		<?php wpsc_checkout_order_preview(); ?>
	</div>
	<p><strong class="wpsc-large"><?php esc_html_e( 'Select a Shipping Method', 'wp-e-commerce' ); ?></strong></p>
	<?php wpsc_checkout_shipping_form(); ?>
</div>