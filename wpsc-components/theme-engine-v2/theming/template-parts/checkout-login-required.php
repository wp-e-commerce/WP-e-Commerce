<?php
/**
 * The template part for displaying the login prompt on the checkout page when logging in is required.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/checkout-login-required.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_user_messages(); ?>
<div class="wpsc-login-form-wrapper">
	<p><?php esc_html_e( 'For returning customers, please sign in before proceeding with checkout.', 'wp-e-commerce' ) ?></p>
	<?php wpsc_login_form(); ?>
</div>
<div class="wpsc-create-account-offer">
	<p><?php esc_html_e( 'New customers need to create an account before checking out.', 'wp-e-commerce' ); ?></p>
	<p><?php esc_html_e( 'Please register with us to enjoy personalized services, such as:', 'wp-e-commerce' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Online order status', 'wp-e-commerce' ); ?></li>
		<li><?php esc_html_e( 'Faster checkout process', 'wp-e-commerce' ); ?></li>
		<li><?php esc_html_e( 'Order history', 'wp-e-commerce' ); ?></li>
	</ul>
	<a class="wpsc-button" href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create your account now', 'wp-e-commerce' ); ?></a>
</div>
<br class="clear" />