<?php
/**
 * The template for displaying main product catalog.
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */
get_header( 'archive-wpsc-product' ); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php wpsc_get_template_part( 'archive', 'product-catalog' ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar( 'archive-wpsc-product' ); ?>
<?php get_footer( 'archive-wpsc-product' ); ?>