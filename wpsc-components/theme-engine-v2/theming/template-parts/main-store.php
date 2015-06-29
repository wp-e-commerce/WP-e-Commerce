<?php
/**
 * The template part for displaying the main store view.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/main-store.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */

wpsc_user_messages();

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