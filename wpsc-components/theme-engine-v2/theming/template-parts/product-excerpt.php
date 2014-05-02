<div id="product-<?php wpsc_product_id(); ?>" <?php wpsc_product_class(); ?>>
	<div class="wpsc-product-summary">
		<div class="wpsc-product-header">
			<h2 class="wpsc-product-title">
				<a
					href="<?php wpsc_product_permalink(); ?>"
					title="<?php wpsc_product_title_attribute(); ?>"
					rel="bookmark"
				><?php wpsc_product_title(); ?></a>

				<?php if ( wpsc_is_product_on_sale() ): ?>
					<span class="wpsc-label wpsc-label-important"><?php echo esc_html_x( 'Sale', 'sale label', 'wpsc' ); ?></span>
				<?php endif ?>
			</h2>
			<div class="wpsc-product-price">
				<?php if ( wpsc_is_product_on_sale() ): ?>
					<del class="wpsc-old-price">
						<strong><?php esc_html_e( 'Old Price', 'wpsc' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_original_price(); ?></span>
					</del><br />
					<ins class="wpsc-sale-price">
						<strong><?php esc_html_e( 'Price', 'wpsc' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_sale_price(); ?></span>
					</ins><br />
					<span class="wpsc-you-save">
						<strong><?php esc_html_e( 'You save', 'wpsc' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_you_save(); ?></span>
					</span>
				<?php else: ?>
					<strong><?php esc_html_e( 'Price', 'wpsc' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_original_price(); ?></span>
				<?php endif; ?>
			</div>
		</div><!-- .entry-header -->

		<div class="wpsc-product-description">
			<?php wpsc_product_description(); ?>
		</div>

		<div class="wpsc-add-to-cart-form-wrapper">
			<?php wpsc_add_to_cart_form(); ?>
		</div>

		<div class="wpsc-product-meta">
			<?php wpsc_edit_product_link() ?>
		</div><!-- .entry-meta -->
	</div><!-- .wpsc-product-summary -->

	<div class="wpsc-thumbnail-wrapper">
		<a
			class="wpsc-thumbnail wpsc-product-thumbnail"
			href="<?php wpsc_product_permalink(); ?>"
			title="<?php wpsc_product_title_attribute(); ?>"
		>
			<?php if ( wpsc_has_product_thumbnail() ): ?>
				<?php wpsc_product_thumbnail(); ?>
			<?php else: ?>
				<?php wpsc_product_no_thumbnail_image(); ?>
			<?php endif; ?>
		</a>
	</div>
</div><!-- #post-<?php the_ID(); ?> -->