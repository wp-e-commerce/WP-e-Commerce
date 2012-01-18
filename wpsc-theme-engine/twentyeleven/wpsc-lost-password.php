<?php
/**
 * The template for displaying "Forgot your password" page.
 *
 * @package wp-e-commerce
 * @subpackage Twenty_Eleven
 * @since 4.0
 */

get_header( 'wpsc-lost-password' ); ?>

		<section id="primary">
			<div id="content" role="main">

			<?php wpsc_get_template_part( 'lost-password', wpsc_page_get_current_slug() ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_footer( 'wpsc-lost-password' ); ?>