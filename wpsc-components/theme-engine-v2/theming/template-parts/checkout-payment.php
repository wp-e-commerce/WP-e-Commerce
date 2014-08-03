<?php wpsc_checkout_steps(); ?>
<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-review">
	<p><strong class="wpsc-large"><?php esc_html_e( 'Review Your Order', 'wpsc' ); ?></strong></p>
	<div class="wpsc-order-preview">
		<?php wpsc_checkout_order_preview(); ?>
	</div>

	<?php if ( ! wpsc_is_free_cart() ) : ?>
		<p><strong class="wpsc-large"><?php esc_html_e( 'Payment Method', 'wpsc' ); ?></strong></p>
	<?php endif; ?>

	<div class="wpsc-payment-method">
		<?php wpsc_checkout_payment_method_form(); ?>
	</div>
</div>