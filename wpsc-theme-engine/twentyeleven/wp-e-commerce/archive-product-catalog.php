<?php
/**
 * Catalog archive content template part for Twenty Eleven
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */
?>

<?php if ( wpsc_have_products() ) : ?>

	<header class="page-header">
		<h1 class="page-title">
			<?php esc_html_e( 'Product Catalog', 'wpsc' ); ?>
		</h1>
	</header>

	<?php wpsc_product_pagination( 'top' ); ?>
	<?php wpsc_get_template_part( 'loop', 'products' ); ?>
	<?php wpsc_product_pagination( 'bottom' ); ?>

<?php else : ?>

	<article id="post-0" class="post no-results not-found wpsc-no-products">
		<header class="entry-header">
			<h1 class="entry-title"><?php esc_html_e( 'Nothing Found', 'wpsc' ); ?></h1>
		</header><!-- .entry-header -->

		<div class="entry-content">
			<?php wpsc_get_template_part( 'feedback', 'no-products' ); ?>
			<?php get_search_form(); ?>
		</div><!-- .entry-content -->
	</article><!-- #post-0 -->

<?php endif; ?>