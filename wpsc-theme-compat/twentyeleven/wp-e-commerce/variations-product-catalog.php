<div class="wpsc-product-variations">
	<?php foreach ( wpsc_get_product_variation_sets() as $variation_set_id => $title ): ?>
		<p>
			<label for="wpsc-product-<?php wpsc_product_id(); ?>-variation-<?php echo esc_attr( $id ); ?>">
				<?php echo esc_html( $title ); ?>:
			</label>
			<?php wpsc_product_variation_set_dropdown( $variation_set_id ); ?>
		</p>
	<?php endforeach; ?>
</div>