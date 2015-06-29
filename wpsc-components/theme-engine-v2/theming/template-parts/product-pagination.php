<?php
/**
 * The template part for displaying the product pagination view.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/product-pagination.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>
<?php if ( wpsc_product_pagination_page_count() > 1 ): ?>
	<div class="wpsc-pagination-links">
		<?php wpsc_product_pagination_links(); ?>
	</div>
	<div class="wpsc-pagination-count">
		<?php wpsc_product_pagination_count(); ?>
	</div>
<?php endif; ?>