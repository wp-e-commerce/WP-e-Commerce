<?php
/**
 * The template part for displaying the product view.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/single.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_user_messages(); ?>
<div id="product-<?php wpsc_product_id(); ?>" itemscope itemtype="http://schema.org/Product">
	<?php wpsc_breadcrumb(); ?>

	<div class="wpsc-product-summary">

		<div class="wpsc-product-price" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
			<meta itemprop="priceCurrency" content="<?php wpsc_base_country_code(); ?>" />
			<?php if ( wpsc_is_product_on_sale() ): ?>
				<del class="wpsc-old-price">
					<strong><?php esc_html_e( 'Old Price', 'wp-e-commerce' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_original_price(); ?></span>
				</del><br />
				<ins class="wpsc-sale-price">
					<strong><?php esc_html_e( 'Price', 'wp-e-commerce' ); ?>:</strong> <span class="wpsc-amount" itemprop="price"><?php wpsc_product_sale_price(); ?></span>
				</ins><br />
				<span class="wpsc-you-save">
					<strong><?php esc_html_e( 'You save', 'wp-e-commerce' ); ?>:</strong> <span class="wpsc-amount"><?php wpsc_product_you_save(); ?></span>
				</span>
			<?php else: ?>
				<strong><?php esc_html_e( 'Price', 'wp-e-commerce' ); ?>:</strong> <span class="wpsc-amount" itemprop="price"><?php wpsc_product_original_price(); ?></span>
			<?php endif; ?>
		</div>

		<div class="wpsc-product-description" itemprop="description">
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
		>
			<?php if ( wpsc_has_product_thumbnail() ): ?>
				<?php wpsc_product_thumbnail(); ?>
			<?php else: ?>
				<?php wpsc_product_no_thumbnail_image(); ?>
			<?php endif; ?>
		</a>
	</div>
</div><!-- #product-<?php the_ID(); ?> -->