<!-- WP eCommerce Table Begins -->
<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	<thead>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
	</tfoot>
	<tbody>
		<?php $this->display_rows(); ?>
	</tbody>
</table>
<!-- WP eCommerce Table Ends -->