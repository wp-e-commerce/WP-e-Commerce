<?php wpsc_user_messages(); ?>
<div class="wpsc-login-form-wrapper">
	<?php wpsc_login_form(); ?>
</div>
<div class="wpsc-create-account-offer">
	<p><?php esc_html_e( 'Register with us to enjoy personalized services, such as:', 'wpsc' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Online order status', 'wpsc' ); ?></li>
		<li><?php esc_html_e( 'Faster checkout process', 'wpsc' ); ?></li>
		<li><?php esc_html_e( 'Order history', 'wpsc' ); ?></li>
	</ul>
	<a class="wpsc-button" href="<?php wpsc_register_url(); ?>"><?php esc_html_e( 'Create your account now', 'wpsc' ); ?></a>
</div>
<br class="clear" />