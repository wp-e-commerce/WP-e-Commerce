<?php
/**
 * The template part for displaying cart notification modal.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/feedback-no-products.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<div class="wpsc-cart-notification-inner wpsc-modal-{{ data.view }}-view">

	<div class="wpsc-close-modal wpsc-icon-remove"><?php _e( 'Close', 'wp-e-commerce' ); ?></div>

	<!-- WP eCommerce Checkout Table Begins -->
	<div class="wpsc-cart-table wpsc-table wpsc-cart-item-table">

		<div class="wpsc-cart-what-was-added">
			<# if ( data.numberChanged ) { #>
				<div class="wpsc-confirmation-message">
					<i class="wpsc-cart-action-icon {{ data.actionIcon }}"></i><span class="wpsc-confirmation-count {{ data.countClass }}">{{ data.numberChanged }}</span> {{ data.actionText }}

					<span class="wpsc-cart-view-toggle">
						<i data-view="expanded" class="wpsc-icon-th-large" title="<?php _e( 'Toggle large thumbnail view', 'wp-e-commerce' ); ?>"></i>
						<i data-view="normal" class="wpsc-icon-th-list" title="<?php _e( 'Toggle list view', 'wp-e-commerce' ); ?>"></i>
					</span>
				</div>
			<# } #>

			<div class="wpsc-cart-body"></div>
		</div>

		<div class="wpsc-cart-footer wpsc-confirmation-totals">

			<div class="wpsc-cart-status">
				<a class="wpsc-cart-link" href="<?php echo esc_url( wpsc_get_cart_url() ); ?>">Your Cart:</a> <span class="wpsc-cart-count">{{ data.numberItems }}</span> <?php _ex( 'item(s)', 'Number of items in the shopping cart', 'wp-e-commerce' ); // @todo _noop these strings. ?>
			</div>

			<div class="wpsc-totals-table">

				<# if ( data.subTotal ) { #>
					<div class="wpsc-cart-aggregate wpsc-cart-subtotal-row">
						<div class="wpsc-totals-row-label">
						   <?php _e( 'Order Subtotal:', 'wp-e-commerce' ); ?>
						</div>
						<div class="wpsc-totals-row-total">
							{{ data.subTotal }}
						</div>
					</div>
				<# } #>

				<# if ( data.shippingTotal ) { #>
					<div class="wpsc-cart-aggregate wpsc-cart-shipping-row">
						<div class="wpsc-totals-row-label">
							<?php _e( 'Est. Shipping + Handling:', 'wp-e-commerce' ); ?>
						</div>
						<div class="wpsc-totals-row-total">
							{{ data.shippingTotal }}
						</div>
					</div>
				<# } #>

				<# if ( data.formattedTotal ) { #>
					<div class="wpsc-cart-aggregate wpsc-cart-total-row">
						<div class="wpsc-totals-row-label">
							<?php _e( 'Subtotal:', 'wp-e-commerce' ); ?>
						</div>
						<div class="wpsc-totals-row-total">
							{{ data.formattedTotal }}
						</div>
					</div>
				<# } #>

			</div>

			<!-- WP eCommerce Cart Notification Form Begins -->
			<form class="wpsc-form wpsc-cart-form wpsc-form-actions bottom" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>"  method="post">
				<?php wpsc_form_button( '', __( 'Continue Shopping', 'wp-e-commerce' ), array( 'class' => 'wpsc-button wpsc-close-modal' ) ); ?>
				<# if ( data.numberItems ) { #>
					<?php
					// @todo maybe get URL to bypass first step of the cart.
					wpsc_begin_checkout_button(); ?>
				<# } #>
			</form>
			<!-- WP eCommerce Cart Form Ends -->

		</div>

	</div>
	<!-- WP eCommerce Checkout Table Ends -->
</div>
