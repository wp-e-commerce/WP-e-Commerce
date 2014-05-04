<?php
/**
 * Main store template part
 *
 * @package wp-e-commerce
 */
?>

<?php if ( wpsc_have_products() ) : ?>
	<?php wpsc_breadcrumb(); ?>
	<?php wpsc_category_filter(); ?>
	<?php wpsc_product_pagination( 'top' ); ?>
	<?php wpsc_get_template_part( 'loop', 'products' ); ?>
	<?php wpsc_product_pagination( 'bottom' ); ?>
<?php else : ?>
	<?php wpsc_category_filter(); ?>
	<?php wpsc_get_template_part( 'feedback', 'no-products' ); ?>
<?php endif; ?>