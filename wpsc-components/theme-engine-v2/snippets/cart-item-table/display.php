<!-- WP eCommerce Checkout Table Begins -->
<table class="<?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
	<thead>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
	</thead>
	<tfoot>
		<?php  if ( wpsc_is_cart() ) : ?>

		<tr class="wpsc-cart-aggregate wpsc-cart-actions-row">
			<td>
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-update" name="update_quantity" value="<?php esc_html_e( 'Update Quantity', 'wp-e-commerce' ); ?>" />
				<input type="hidden" name="action" value="update_quantity" />
			</td>
			<td class="apply-coupon" colspan="<?php echo count( $this->columns ) -1; ?>">
			<?php if ( wpsc_uses_coupons() && $this->show_coupon_field ) : ?>
				<input type="text" name="coupon_code" placeholder="<?php _e( 'Coupon code', 'wp-e-commerce' ); ?>" id="coupon_code" value="<?php echo esc_attr( wpsc_get_customer_meta( 'coupon' ) ); ?>">
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-apply-coupon" name="apply_coupon" value="<?php esc_html_e( 'Apply Coupon', 'wp-e-commerce' ); ?>" />
			<?php endif; ?>
			</td>
		</tr>

		<?php endif; ?>
		<tr class="wpsc-cart-aggregate wpsc-cart-subtotal-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Subtotal:' ,'wp-e-commerce' ); ?><br />
			</th>
			<td><?php echo wpsc_format_currency( $this->get_subtotal() ); ?></td>
		</tr>

<?php 	if ( wpsc_is_shipping_enabled() ): ?>
		<tr <?php $this->show_shipping_style(); ?> class="wpsc-cart-aggregate wpsc-cart-shipping-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Shipping:' ,'wp-e-commerce' ); ?><br />
			</th>
			<td>
				<?php echo wpsc_format_currency( $this->get_total_shipping() ); ?>
			</td>
		</tr>
<?php 	endif; ?>
<?php 	if ( wpsc_is_tax_enabled() ): ?>
		<tr <?php $this->show_tax_style(); ?> class="wpsc-cart-aggregate wpsc-cart-tax-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Tax:' ,'wp-e-commerce' ); ?><br />
			</th>
			<td>
<?php 			if ( wpsc_is_tax_included() ): ?>
				<span class="wpsc-tax-included"><?php echo _x( '(included)', 'tax is included in product prices', 'wp-e-commerce' ); ?></span>
<?php 			else:
					echo esc_html( wpsc_format_currency( $this->get_tax() ) );
				endif; ?>
			</td>
		</tr>
<?php 	endif; ?>
	<?php 	if ( wpsc_uses_coupons() && $this->get_total_discount() > 0 ) : ?>
	<tr class="wpsc-cart-aggregate wpsc-cart-discount-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Discount:' ,'wp-e-commerce' ); ?><br />
			</th>
			<td>
				<?php echo wpsc_format_currency( $this->get_total_discount() ); ?>
			</td>
		</tr>
	<?php 	endif; ?>
		<tr <?php $this->show_total_style(); ?> class="wpsc-cart-aggregate wpsc-cart-total-row">
			<th scope="row" colspan="<?php echo count( $this->columns ) - 1; ?>">
				<?php esc_html_e( 'Total:' ,'wp-e-commerce' ); ?><br />
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
<!-- WP eCommerce Checkout Table Ends -->
