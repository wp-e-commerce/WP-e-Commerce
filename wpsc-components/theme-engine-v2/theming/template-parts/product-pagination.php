<?php if ( wpsc_product_pagination_page_count() > 1 ): ?>
	<div class="wpsc-pagination-links">
		<?php wpsc_product_pagination_links(); ?>
	</div>
	<div class="wpsc-pagination-count">
		<?php wpsc_product_pagination_count(); ?>
	</div>
<?php endif; ?>