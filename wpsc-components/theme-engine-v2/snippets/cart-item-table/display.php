<!-- WP eCommerce Checkout Table Begins -->
<div class="wpsc-cart-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" >
	<div class= "wpsc-cart-header">
		<div class="wpsc-row">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
	<div class="wpsc-cart-body">
		<?php $this->display_rows(); ?>
	</div>
	<div class="wpsc-cart-footer">

		<?php if ( $this->show_coupon_field || $this->show_quantity_field ) : ?>
		<div class="wpsc-row wpsc-cart-aggregate wpsc-cart-actions-row">
			<div class="wpsc-cart-cell apply-coupon">
			<?php if ( wpsc_uses_coupons() && $this->show_coupon_field ) : ?>
				<input type="text" name="coupon_code" placeholder="<?php _e( 'Coupon code', 'wp-e-commerce' ); ?>" id="coupon_code" value="<?php echo esc_attr( wpsc_get_customer_meta( 'coupon' ) ); ?>">
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-apply-coupon" name="apply_coupon" value="<?php esc_html_e( 'Apply Coupon', 'wp-e-commerce' ); ?>" />
			<?php endif; ?>
			</div>
			<div class="wpsc-cart-cell update-quantity">
				<div class="wpsc-quantity-field-wrapper">
					<?php if ( $this->show_quantity_field ) : ?>
				</div>
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-update" name="update_quantity" value="<?php esc_html_e( 'Update Quantity', 'wp-e-commerce' ); ?>" />
				<input type="hidden" name="action" value="update_quantity" />
			<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="wpsc-cart-aggregate wpsc-cart-subtotal-row">
			<div class="wpsc-cart-cell-header" scope="row">
				<?php esc_html_e( 'Subtotal:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="wpsc-cart-cell"><?php echo wpsc_format_currency( $this->get_subtotal() ); ?></div>
		</div>

<?php 	if ( wpsc_is_shipping_enabled() ): ?>
		<div <?php $this->show_shipping_style(); ?> class="wpsc-cart-aggregate wpsc-cart-shipping-row">
			<div class="wpsc-cart-cell-header" scope="row">
				<?php esc_html_e( 'Shipping:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="wpsc-cart-cell">
				<?php echo wpsc_format_currency( $this->get_total_shipping() ); ?>
			</div>
		</div>
<?php 	endif; ?>
<?php 	if ( wpsc_is_tax_enabled() ): ?>
		<div <?php $this->show_tax_style(); ?> class="wpsc-cart-aggregate wpsc-cart-tax-row">
			<div class="wpsc-cart-cell-header" scope="row">
				<?php esc_html_e( 'Tax:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="wpsc-cart-cell">
<?php 			if ( wpsc_is_tax_included() ): ?>
				<span class="wpsc-tax-included"><?php echo _x( '(included)', 'tax is included in product prices', 'wp-e-commerce' ); ?></span>
<?php 			else:
					echo esc_html( wpsc_format_currency( $this->get_tax() ) );
				endif; ?>
			</div>
		</div>
<?php 	endif; ?>
	<?php 	if ( wpsc_uses_coupons() && $this->get_total_discount() > 0 ) : ?>
	<div class="wpsc-cart-aggregate wpsc-cart-discount-row">
			<div class="wpsc-cart-cell-header" scope="row">
				<?php esc_html_e( 'Discount:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="wpsc-cart-cell">
				<?php echo wpsc_format_currency( $this->get_total_discount() ); ?>
			</div>
		</div>
	<?php 	endif; ?>
		<div <?php $this->show_total_style(); ?> class="wpsc-cart-aggregate wpsc-cart-total-row">
			<div class="wpsc-cart-cell-header" scope="row">
				<?php esc_html_e( 'Total:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="wpsc-cart-cell">
				<?php echo esc_html( wpsc_format_currency( $this->get_total_price() ) ); ?> </div>
		</div>
		<?php $this->tfoot_append(); ?>
	</div>

</div>
<!-- WP eCommerce Checkout Table Ends -->
