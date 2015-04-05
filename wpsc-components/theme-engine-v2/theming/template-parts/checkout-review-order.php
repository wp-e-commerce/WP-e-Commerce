<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-shipping-method">
	<h3><?php esc_html_e( 'Your Order', 'wpsc' ); ?></h3>
	<div class="wpsc-order-preview">
		<?php wpsc_checkout_order_preview(); ?>
	</div>
	<h3><?php esc_html_e( 'Customer Details', 'wpsc' ); ?></h3>
	<?php wpsc_checkout_customer_details(); ?>
	<h3><?php esc_html_e( 'Select a Shipping Method', 'wpsc' ); ?></h3>
	<?php wpsc_checkout_shipping_form(); ?>	
</div>
