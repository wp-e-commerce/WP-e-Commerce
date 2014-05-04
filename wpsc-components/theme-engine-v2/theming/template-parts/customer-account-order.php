<div class="wpsc-customer-account-order">
	<?php wpsc_breadcrumb(); ?>
	<p>
		<strong><?php echo esc_html_x( 'Date:', 'customer account template', 'wpsc' ); ?></strong>
		<?php wpsc_customer_account_order_date(); ?>
	</p>
	<p><strong><?php echo esc_html_x( 'Cart', 'customer account template', 'wpsc' ); ?></strong></p>
	<?php wpsc_customer_account_cart_items(); ?>
	<?php wpsc_customer_account_order_details(); ?>
</div>