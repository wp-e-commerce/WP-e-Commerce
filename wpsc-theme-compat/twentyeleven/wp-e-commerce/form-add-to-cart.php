<div class="wpsc-product-add-to-cart-form" id="wpsc-product-add-to-cart-form-<?php wpsc_product_id(); ?>">
	<?php if ( wpsc_is_product_out_of_stock() ): ?>
		<p class="wpsc-out-of-stock"><?php esc_html_e( 'Out of stock', 'wpsc' ); ?></p>
	<?php else: ?>
		<?php wpsc_add_to_cart_form_open(); ?>
			<div class="wpsc-product-add-to-cart-form-fields">
				<?php wpsc_add_to_cart_form_fields(); ?>
			</div>
			<?php wpsc_get_template_part( 'form-add-to-cart-actions' ); ?>
		</form>
		<?php wpsc_add_to_cart_form_close(); ?>
	<?php endif; ?>
</div>