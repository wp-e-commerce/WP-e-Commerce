<?php
/**
 * The template part for displaying the customer login form.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/login.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<?php wpsc_user_messages(); ?>
<div class="wpsc-login-form-wrapper">
	<?php wpsc_login_form(); ?>
</div>
<div class="wpsc-create-account-offer">
	<p><?php esc_html_e( 'Register with us to enjoy personalized services, such as:', 'wp-e-commerce' ); ?></p>
	<ul>
		<li><?php
		 esc_html_e( 'Online order status', 'wp-e-commerce' ); ?></li>
		<li><?php esc_html_e( 'Faster checkout process', 'wp-e-commerce' ); ?></li>
		<li><?php esc_html_e( 'Order history', 'wp-e-commerce' ); ?></li>
	</ul>
	<a class="wpsc-button" href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create your account now', 'wp-e-commerce' ); ?></a>
</div>
<br class="clear" />