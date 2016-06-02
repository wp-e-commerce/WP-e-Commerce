<?php
/**
 * The template part for displaying the payment method view in the checkout process.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/checkout-payment.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_checkout_steps(); ?>
<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-review">
	<p><strong class="wpsc-large"><?php esc_html_e( 'Review Your Order', 'wp-e-commerce' ); ?></strong></p>
	<div class="wpsc-order-preview">
		<?php wpsc_checkout_order_preview(); ?>
	</div>

	<div class="wpsc-payment-method">

		<?php if ( ! wpsc_is_free_cart() ) : ?>
			<p class="wpsc-payment-title"><strong class="wpsc-large"><?php esc_html_e( 'Payment Method', 'wp-e-commerce' ); ?></strong></p>
		<?php endif; ?>

		<?php wpsc_checkout_payment_method_form(); ?>
		
	</div>
</div>