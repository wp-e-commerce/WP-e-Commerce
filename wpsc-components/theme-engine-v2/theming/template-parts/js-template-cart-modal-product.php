<?php
/**
 * The template part for displaying a product in the cart notification modal.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/feedback-no-products.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<# if ( data.thumb ) { #>
	<div class="wpsc-cart-cell image">
		{{{ data.thumb }}}
	</div>
<# }; #>

<div class="wpsc-cart-cell items">
	<div class="wpsc-cart-item-description">
		<div class="wpsc-cart-item-title">
			<strong>
				<a href="{{ data.url }}">{{ data.title }}</a>
			</strong>
		</div>
		<div class="wpsc-cart-item-details">
			<# _.each( data.variations, function( variation ) { #>
				<span class="wpsc-cart-item-variations"><span class="wpsc-cart-label">{{ variation.label }}:</span> {{ variation.value }}</span>
			<# }); #>
		</div>
	</div>

	<div class="wpsc-cart-quantity"><span class="wpsc-cart-label"><?php _e( 'Quantity:', 'wp-e-commerce' ); ?> </span>{{ data.quantity }}</div>

	<div class="wpsc-cart-item-total">{{ data.formattedPrice }}</div>

	<div class="wpsc-cart-item-row-actions">

		<span class="wpsc-cart-item-edit-wrap">

			<a alt="<?php esc_attr_e( 'Edit quantity', 'wp-e-commerce' ); ?>" class="wpsc-button wpsc-button-mini wpsc-cart-item-edit" href="<?php echo esc_url( wpsc_get_cart_url() ); ?>"><small><i class="wpsc-icon-edit"></i> <span><?php esc_html_e( 'Edit', 'wp-e-commerce' ); ?></span></small></a>

			<span class="wpsc-field-quantity wpsc-field wpsc-field-textfield <# if ( true !== data.editQty ) { #>wpsc-hide<# } #>">
				<label class="wpsc-control-label"><?php _e( 'Quantity:', 'wp-e-commerce' ); ?></label>
				<span class="wpsc-controls"><input class="modify-cart-quantity" name="cart-quantity" value="{{ data.quantity }}" type="text"><span class="inc wpsc-qty-button">+</span><span class="dec wpsc-qty-button">-</span></span>
			</span>

		</span>

		<a alt="<?php esc_attr_e( 'Remove from cart', 'wp-e-commerce' ); ?>" class="wpsc-button wpsc-button-mini wpsc-cart-item-remove" href="{{ data.remove_url }}"><i class="wpsc-icon-trash"><small></i> <span><?php esc_html_e( 'Remove', 'wp-e-commerce' ); ?></span></small></a>

	</div>
</div>
<?php
// echo '<# console.log( data ); #>';
