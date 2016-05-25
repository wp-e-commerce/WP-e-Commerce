<!-- WP eCommerce Table Begins -->
<div class="wpsc-cart-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<div class="wpsc-cart-header">
		<div class="wpsc-row">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
	<div class="wpsc-cart-body">
		<?php $this->display_rows(); ?>
	</div>
	<div class="wpsc-cart-footer">
		<div class="wpsc-row">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
</div>
<!-- WP eCommerce Table Ends -->