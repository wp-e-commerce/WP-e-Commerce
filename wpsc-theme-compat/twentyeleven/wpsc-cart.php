<?php
/**
 * The template for displaying cart page.
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */

get_header( 'wpsc-cart' ); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php wpsc_get_template_part( 'cart', 'main' ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_footer( 'wpsc-cart' ); ?>