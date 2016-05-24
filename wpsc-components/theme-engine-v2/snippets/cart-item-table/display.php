<!-- WP eCommerce Checkout Table Begins -->
<div class="table <?php echo implode( ' ', $this->get_table_classes() ); ?>" >
	<div class= "thead">
		<div class="tr">
			<?php $this->print_column_headers(); ?>
		</div>
	</div>
	<div class="tbody">
		<?php $this->display_rows(); ?>
	</div>
	<div class="tfoot">
		<?php  if ( wpsc_is_cart() ) : ?>

		<div class="tr wpsc-cart-aggregate wpsc-cart-actions-row">
			<div class="td">
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-update" name="update_quantity" value="<?php esc_html_e( 'Update Quantity', 'wp-e-commerce' ); ?>" />
				<input type="hidden" name="action" value="update_quantity" />
			</div>
			<div class="td apply-coupon">
			<?php if ( wpsc_uses_coupons() && $this->show_coupon_field ) : ?>
				<input type="text" name="coupon_code" placeholder="<?php _e( 'Coupon code', 'wp-e-commerce' ); ?>" id="coupon_code" value="<?php echo esc_attr( wpsc_get_customer_meta( 'coupon' ) ); ?>">
				<input type="submit" class="wpsc-button wpsc-button-small wpsc-cart-apply-coupon" name="apply_coupon" value="<?php esc_html_e( 'Apply Coupon', 'wp-e-commerce' ); ?>" />
			<?php endif; ?>
			</div>
		</div>

		<?php endif; ?>
		<div class="tr wpsc-cart-aggregate wpsc-cart-subtotal-row">
			<div class="th" scope="row">
				<?php esc_html_e( 'Subtotal:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="td"><?php echo wpsc_format_currency( $this->get_subtotal() ); ?></div>
		</div>

<?php 	if ( wpsc_is_shipping_enabled() ): ?>
		<div <?php $this->show_shipping_style(); ?> class="tr wpsc-cart-aggregate wpsc-cart-shipping-row">
			<div class="th" scope="row">
				<?php esc_html_e( 'Shipping:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="td">
				<?php echo wpsc_format_currency( $this->get_total_shipping() ); ?>
			</div>
		</div>
<?php 	endif; ?>
<?php 	if ( wpsc_is_tax_enabled() ): ?>
		<div <?php $this->show_tax_style(); ?> class="tr wpsc-cart-aggregate wpsc-cart-tax-row">
			<div class="th" scope="row">
				<?php esc_html_e( 'Tax:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="td">
<?php 			if ( wpsc_is_tax_included() ): ?>
				<span class="wpsc-tax-included"><?php echo _x( '(included)', 'tax is included in product prices', 'wp-e-commerce' ); ?></span>
<?php 			else:
					echo esc_html( wpsc_format_currency( $this->get_tax() ) );
				endif; ?>
			</div>
		</div>
<?php 	endif; ?>
	<?php 	if ( wpsc_uses_coupons() && $this->get_total_discount() > 0 ) : ?>
	<div class="tr wpsc-cart-aggregate wpsc-cart-discount-row">
			<div class="th" scope="row">
				<?php esc_html_e( 'Discount:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="td">
				<?php echo wpsc_format_currency( $this->get_total_discount() ); ?>
			</div>
		</div>
	<?php 	endif; ?>
		<div <?php $this->show_total_style(); ?> class="tr wpsc-cart-aggregate wpsc-cart-total-row">
			<div class="th" scope="row">
				<?php esc_html_e( 'Total:' ,'wp-e-commerce' ); ?><br />
			</div>
			<div class="td">
				<?php echo esc_html( wpsc_format_currency( $this->get_total_price() ) ); ?> </td>
		</div>
		<?php $this->tfoot_append(); ?>
	</div>

</div>
<!-- WP eCommerce Checkout Table Ends -->
