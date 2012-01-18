<?php
/**
 * Loop products template part for Twenty Eleven
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */
 ?>

<article id="product-<?php wpsc_product_id(); ?>" <?php wpsc_product_class(); ?>>
	<?php wpsc_breadcrumb( array(
		'before' => '<nav class="%s">',
		'after'  => '</nav>',
	) ); ?>

	<div class="wpsc-product-summary">
		<header class="entry-header wpsc-product-header">
			<hgroup>
				<?php wpsc_product_header_before(); ?>
				<h1 class="entry-title wpsc-product-title">
					<a
						href="<?php wpsc_product_permalink(); ?>"
						title="<?php wpsc_product_title_attribute(); ?>"
						rel="bookmark"
					>
						<?php wpsc_product_title(); ?>
					</a>
				</h1>
				<h2 class="wpsc-product-price">
					<?php if ( wpsc_is_product_on_sale() ): ?>
						<ins><?php wpsc_product_sale_price(); ?></ins>
						<?php if ( ! wpsc_has_product_variations() ): ?>
							<del><?php wpsc_product_original_price(); ?></del>
						<?php endif; ?>
					<?php else: ?>
						<?php wpsc_product_original_price(); ?>
					<?php endif; ?>
				</h2>
				<?php wpsc_product_header_after(); ?>
			</hgroup>
		</header><!-- .entry-header -->

		<div class="entry-content wpsc-product-description-wrapper">
			<div class="wpsc-product-description">
				<?php wpsc_product_description(); ?>
			</div>
			<?php wpsc_get_template_part( 'form-add-to-cart' ); ?>
		</div>

		<footer class="entry-meta">
			<?php wpsc_twentyeleven_product_category_and_tag_list(); ?>
			<?php wpsc_edit_product_link( array(
				'before' => '<span class="edit-link">',
				'after'  => '</span>',
			) ) ?>
		</footer><!-- #entry-meta -->
	</div><!-- .wpsc-product-summary -->

	<div class="wpsc-thumbnail wpsc-product-thumbnail">
		<?php if ( wpsc_has_product_thumbnail() ): ?>
			<?php wpsc_product_thumbnail( 'single' ); ?>
		<?php else: ?>
			<?php wpsc_product_no_thumbnail_image( 'single' ); ?>
		<?php endif; ?>

		<?php if ( wpsc_is_product_on_sale() ): ?>
			<p class="wpsc-sale-overlay"><?php echo esc_html_x( 'Sale', 'twentyeleven sale overlay', 'wpsc' ); ?></p>
		<?php endif ?>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->