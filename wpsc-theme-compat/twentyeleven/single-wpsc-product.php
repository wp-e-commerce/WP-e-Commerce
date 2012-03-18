<?php
/**
 * The template for displaying single product.
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */

get_header( 'single-wpsc-product' ); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php wpsc_get_template_part( 'loop', 'single' ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_footer( 'single-wpsc-product' ); ?>