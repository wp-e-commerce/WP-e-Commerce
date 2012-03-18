<article>
	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Shopping Cart', 'wpsc' ); ?>
		</h1>
	</header>

	<?php wpsc_user_messages(); ?>

	<?php if ( wpsc_cart_has_items() ): ?>
		<?php wpsc_cart_form_open(); ?>
			<p class="wpsc-subtotal-notice"><?php esc_html_e( 'Shipping and tax will be calculated when you proceed to checkout.') ?></p>
			<?php wpsc_cart_item_table(); ?>
			<p class="wpsc-cart-primary-buttons">
				<?php wpsc_keep_shopping_button(); ?>
				<?php wpsc_begin_checkout_button(); ?>
			</p>
		<?php wpsc_cart_form_close(); ?>
	<?php else: ?>
		<p><?php esc_html_e( "Oops, there's nothing in your cart.", 'wpsc' ); ?></p>
		<p><?php wpsc_keep_shopping_button(); ?></p>
	<?php endif ?>
</article>