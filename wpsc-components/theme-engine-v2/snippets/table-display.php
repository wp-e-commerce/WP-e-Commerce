<!-- WP eCommerce Table Begins -->
<div class="table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	<div class="thead">
		<div class="tr">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
	<div class="tfoot">
		<div class="tr">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
	<div class="tbody">
		<?php $this->display_rows(); ?>
	</div>
</div>
<!-- WP eCommerce Table Ends -->