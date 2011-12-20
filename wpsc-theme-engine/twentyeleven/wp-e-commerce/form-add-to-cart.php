<div class="wpsc-product-add-to-cart-form" id="wpsc-product-add-to-cart-form-<?php wpsc_product_id(); ?>">
	<?php if ( wpsc_is_product_out_of_stock() ): ?>
		<p class="wpsc-out-of-stock"><?php esc_html_e( 'Out of stock', 'wpsc' ); ?></p>
	<?php else: ?>
		<form action="" method="post">
			<div class="wpsc-product-add-to-cart-form-fields">
				<?php wpsc_theme_add_to_cart_fields_before(); ?>
				<?php wpsc_get_template_part( 'variations', 'product-catalog' ); ?>
				<p>
					<label for="wpsc-product-add-to-cart-quantity-<?php wpsc_product_id(); ?>">
						<?php echo esc_html_x( 'Quantity', 'theme add to cart form', 'wpsc' ); ?>:
					</label>
					<input type="text" class="wpsc-product-add-to-cart-quantity wpsc-textfield" id="wpsc-product-add-to-cart-quantity-<?php wpsc_product_id(); ?>" value="1" />
				</p>
				<?php wpsc_theme_add_to_cart_fields_after(); ?>
			</div>
			<?php wpsc_get_template_part( 'form', 'add-to-cart-actions' ); ?>
		</form>
	<?php endif; ?>
</div>