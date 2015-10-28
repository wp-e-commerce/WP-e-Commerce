<?php
/**
 * The template part for displaying the cart page.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/cart.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<div class="wpsc-shopping-cart">

	<?php wpsc_user_messages(); ?>

	<?php if ( wpsc_cart_has_items() ): ?>
		<?php wpsc_cart_item_table(); ?>
	<?php else: ?>
		<p><?php esc_html_e( "There is nothing in your cart.", 'wp-e-commerce' ); ?></p>
		<p><?php wpsc_keep_shopping_button(); ?></p>
	<?php endif ?>
</div>
