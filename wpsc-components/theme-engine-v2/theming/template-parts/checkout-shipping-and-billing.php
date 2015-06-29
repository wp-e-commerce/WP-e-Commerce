<?php
/**
 * The template part for displaying the shipping and billing view in the checkout process.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/checkout-shipping-and-billing.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_checkout_steps(); ?>
<?php wpsc_user_messages(); ?>
<div class="wpsc-checkout wpsc-checkout-shipping-and-billing">
	<?php wpsc_checkout_form(); ?>
</div>