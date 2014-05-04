<!-- WP e-Commerce Checkout Table Begins -->
<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	<thead>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
	</thead>
	<tfoot>
		<tr class="wpsc-cart-aggregate wpsc-cart-subtotal-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Subtotal:' ,'wpsc' ); ?><br />
			</th>
			<td><?php echo wpsc_format_currency( $this->get_subtotal() ); ?></td>
		</tr>

<?php 	if ( wpsc_is_shipping_enabled() ): ?>
		<tr <?php $this->show_shipping_style(); ?> class="wpsc-cart-aggregate wpsc-cart-shipping-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Shipping:' ,'wpsc' ); ?><br />
			</th>
			<td>
				<?php echo wpsc_format_currency( $this->get_total_shipping() ); ?>
			</td>
		</tr>
<?php 	endif; ?>
<?php 	if ( wpsc_is_tax_enabled() ): ?>
		<tr <?php $this->show_tax_style(); ?> class="wpsc-cart-aggregate wpsc-cart-tax-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Tax:' ,'wpsc' ); ?><br />
			</th>
			<td>
<?php 			if ( wpsc_is_tax_included() ): ?>
				<span class="wpsc-tax-included"><?php echo _x( '(included)', 'tax is included in product prices', 'wpsc' ); ?></span>
<?php 			else:
					echo esc_html( wpsc_format_currency( $this->get_tax() ) );
				endif; ?>
			</td>
		</tr>
<?php 	endif; ?>
		<tr <?php $this->show_total_style(); ?> class="wpsc-cart-aggregate wpsc-cart-total-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Total:' ,'wpsc' ); ?><br />
			</th>
			<td>
				<?php echo esc_html( wpsc_format_currency( $this->get_total_price() ) ); ?> </td>
		</tr>
		<?php $this->tfoot_append(); ?>
	</tfoot>

	<tbody>
		<?php $this->display_rows(); ?>
	</tbody>
</table>
<!-- WP e-Commerce Checkout Table Ends -->