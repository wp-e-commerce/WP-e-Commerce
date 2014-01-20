<div class="wpsc-shopping-cart">

	<?php wpsc_user_messages(); ?>

	<?php if ( wpsc_cart_has_items() ): ?>
		<?php wpsc_cart_item_table(); ?>
	<?php else: ?>
		<p><?php esc_html_e( "Oops, there's nothing in your cart.", 'wpsc' ); ?></p>
		<p><?php wpsc_keep_shopping_button(); ?></p>
	<?php endif ?>
</div>