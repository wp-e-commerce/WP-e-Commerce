<?php wpsc_user_messages(); ?>
<div class="wpsc-login-form-wrapper">
	<p><?php esc_html_e( 'For returning customers, please sign in before proceeding with checkout.', 'wpsc' ) ?></p>
	<?php wpsc_login_form(); ?>
</div>
<div class="wpsc-create-account-offer">
	<p><?php esc_html_e( 'New customers need to create an account before checking out.', 'wpsc' ); ?></p>
	<p><?php esc_html_e( 'Please register with us to enjoy personalized services, such as:', 'wpsc' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Online order status', 'wpsc' ); ?></li>
		<li><?php esc_html_e( 'Faster checkout process', 'wpsc' ); ?></li>
		<li><?php esc_html_e( 'Order history', 'wpsc' ); ?></li>
	</ul>
	<a class="wpsc-button" href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create your account now', 'wpsc' ); ?></a>
</div>
<br class="clear" />