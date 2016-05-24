<?php
/**
 * The template part for displaying the product loop.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/loop-products.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>
<section id="wpsc-products">

<?php while ( wpsc_have_products() ): wpsc_the_product(); ?>

	<?php wpsc_get_template_part( 'product', 'excerpt' ); ?>

<?php endwhile; ?>

</section>
