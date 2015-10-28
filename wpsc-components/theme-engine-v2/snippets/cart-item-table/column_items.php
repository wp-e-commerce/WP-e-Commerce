<?php if ( $this->show_thumbnails ): ?>
<div class="wpsc-thumbnail wpsc-product-thumbnail">
	<?php
		if ( wpsc_has_product_thumbnail( $item->product_id ) ) {
			echo wpsc_get_product_thumbnail( $item->product_id, 'cart' );
		} else {
			wpsc_product_no_thumbnail_image( 'cart' );
		}
	?>
</div><!-- .wpsc-product-thumbnail -->
<?php endif; ?>
<div class="wpsc-cart-item-description">
	<div class="wpsc-cart-item-title">
		<strong>
			<a href="<?php echo $permalink; ?>"><?php echo esc_html( $product_name ); ?></a>
		</strong>
	</div>
	<div class="wpsc-cart-item-details">
<?php 	if ( ! empty( $item->sku ) ): ?>
		<span class="wpsc-cart-item-sku"><span><?php esc_html_e( 'SKU', 'wp-e-commerce' ); ?>:</span> <?php echo esc_html( $item->sku ); ?></span>
<?php 	endif ?>

<?php 	if ( $separator ): ?>
		<span class="separator"><?php echo $separator; ?></span>
<?php 	endif ?>

<?php 	if ( ! empty( $variations ) ): ?>
		<span class="wpsc-cart-item-variations"><?php echo $variations; ?></span>
<?php 	endif ?>
	</div>
	<?php $this->cart_item_description( $item, $key ); ?>
</div><!-- .wpsc-cart-item-description -->
