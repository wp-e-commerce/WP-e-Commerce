<?php
/**
 * The template for displaying main product catalog.
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */

get_header( 'wpsc-product-category' ); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php wpsc_get_template_part( 'archive', 'product-category' ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar( 'wpsc-product-category' ); ?>
<?php get_footer( 'wpsc-product-category' ); ?>