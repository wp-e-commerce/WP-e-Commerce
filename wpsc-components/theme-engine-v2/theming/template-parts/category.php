<?php
/**
 * The template part for displaying the product categories.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/category.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */

if ( wpsc_have_products() ) : ?>
	<?php wpsc_breadcrumb(); ?>
	<?php wpsc_category_filter(); ?>
	<?php wpsc_product_pagination( 'top' ); ?>
	<?php wpsc_get_template_part( 'loop', 'products' ); ?>
	<?php wpsc_product_pagination( 'bottom' ); ?>
<?php else : ?>
	<?php wpsc_category_filter(); ?>
	<?php wpsc_get_template_part( 'feedback', 'no-products' ); ?>
<?php endif; ?>