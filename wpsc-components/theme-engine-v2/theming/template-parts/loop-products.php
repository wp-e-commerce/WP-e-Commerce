<?php
/**
 * Loop products template part
 *
 * @package wp-e-commerce
 * @subpackage theme_compat
 * @since 0.1
 */
 ?>

<?php while ( wpsc_have_products() ): wpsc_the_product(); ?>

	<?php wpsc_get_template_part( 'product', 'excerpt' ); ?>

<?php endwhile; ?>